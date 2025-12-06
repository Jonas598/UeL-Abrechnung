<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRolleAbteilung; // Dein Model für die Verknüpfung
use App\Models\RolleDefinition;    // Dein Model für die Rollen
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password; // Wichtig für den Reset-Token
use App\Mail\UserInviteMail;    // <--- Importieren
use Illuminate\Support\Facades\Mail; // <--- Importieren
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    /**
     * Neuer Benutzer registrieren (Admin legt an)
     */
    public function createUser(Request $request)
    {
        // 1. Validierung anpassen auf das, was Vue sendet
        $validated = $request->validate([
            'name' => 'required|string',    // Nachname
            'vorname' => 'required|string', // Vorname
            'email' => 'required|string|email|unique:user,email', // Tabelle 'user' beachten
            'isGeschaeftsstelle' => 'boolean',
            'roles.departmentHead' => 'array|max:1', // Array von IDs
            'roles.trainer' => 'array',        // Array von IDs
        ]);

        return DB::transaction(function () use ($validated) {

            // 2. User erstellen
            // Passwort bleibt null für den Invite-Flow (User setzt es selbst über Link)
            $user = User::create([
                'name' => $validated['name'],
                'vorname' => $validated['vorname'],
                'email' => $validated['email'],
                'password' => null, // WICHTIG: Null setzen (Spalte muss nullable sein!)
                'isGeschaeftsstelle' => $validated['isGeschaeftsstelle'] ?? false,
                'isAdmin' => false, // Default wert
            ]);

            // 3. Rollen IDs aus der Datenbank holen
            // Wir gehen davon aus, dass die Rollen "Abteilungsleiter" und "Übungsleiter" heißen
            $roleHead = RolleDefinition::where('bezeichnung', 'Abteilungsleiter')->first();
            $roleTrainer = RolleDefinition::where('bezeichnung', 'Uebungsleiter')->first();

            // Falls Rollen noch nicht existieren, Fehler werfen oder anlegen
            if (!$roleHead || !$roleTrainer) {
                // Optional: Lege sie an, falls sie fehlen, oder wirf Fehler
                throw new \Exception("Rollen-Definitionen (Abteilungsleiter/Uebungsleiter) fehlen in der DB!");
            }

            // 4. Abteilungsleiter verknüpfen
            if (!empty($validated['roles']['departmentHead'])) {
                foreach ($validated['roles']['departmentHead'] as $deptId) {
                    UserRolleAbteilung::create([
                        'fk_userID' => $user->UserID,      // Achte auf Großschreibung laut deinem Model/Bild
                        'fk_abteilungID' => $deptId,
                        'fk_rolleID' => $roleHead->RolleID // ID der Abteilungsleiter-Rolle
                    ]);
                }
            }

            // 5. Übungsleiter verknüpfen
            if (!empty($validated['roles']['trainer'])) {
                foreach ($validated['roles']['trainer'] as $deptId) {
                    UserRolleAbteilung::create([
                        'fk_userID' => $user->UserID,
                        'fk_abteilungID' => $deptId,
                        'fk_rolleID' => $roleTrainer->RolleID // ID der Übungsleiter-Rolle
                    ]);
                }
            }

            // 6. Invite-Token generieren
            // Nutzt Laravel's Password Broker. Das User Model muss "CanResetPassword" unterstützen
            // und die "password_resets" Tabelle muss existieren.
            $token = Password::createToken($user);

            // 7. E-Mail Trigger
            // Hier würdest du die Mail versenden mit dem Link:
            // https://dein-frontend.de/reset-password?token={token}&email={email}
            Mail::to($user->email)->send(new UserInviteMail($user, $token));

            return response([
                'message' => 'Benutzer erfolgreich angelegt und Invite-Token generiert',
                'user_id' => $user->UserID,
                'invite_token' => $token // Zum Debuggen zurückgeben (in Prod nur per Mail!)
            ], 201);
        });
    }

    public function setNewPassword(Request $request)
    {
        // 1. Validierung
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8', // 'confirmed' prüft, ob 'password_confirmation' übereinstimmt
        ]);

        // 2. Laravel Password Broker nutzen
        // Prüft Token & Email in 'password_reset_tokens' Tabelle
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Callback: Wird nur ausgeführt, wenn Token gültig ist

                $user->forceFill([
                    'password' => $password // Model-Cast 'hashed' übernimmt das Hashing
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        // 3. Ergebnis zurückgeben
        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Passwort erfolgreich gespeichert. Login ist jetzt möglich.']);
        }

        // Fehlerfall (z.B. Token abgelaufen oder E-Mail falsch)
        return response()->json(['message' => __($status)], 400);
    }
    /**
     * Login und Token-Ausgabe
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // User suchen
        $user = User::where('email', $fields['email'])->first();

        // Check: User existiert UND Passwort stimmt (und ist nicht null)
        if (!$user || !$user->password || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Falsche Zugangsdaten (E-Mail oder Passwort)'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token,
            'message' => 'Login erfolgreich'
        ], 200);
    }


    /**
     * Dashboard Daten abrufen (User + Rollen/Abteilungen)
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $zuweisungen = UserRolleAbteilung::where('fk_userID', $user->UserID)
            ->with(['rolle', 'abteilung'])
            ->get();

        $formattedData = $zuweisungen->map(function ($item) {
            return [
                'rolle' => $item->rolle->bezeichnung ?? 'Unbekannt',
                'abteilung' => $item->abteilung->name ?? 'Keine Abteilung',

                // HIER NEU: Wir brauchen die ID für das Dropdown!
                'abteilungId' => $item->fk_abteilungID,
            ];
        });

        // 4. Alles zurückgeben
        return response()->json([
            'user' => [
                'name' => $user->name,
                'vorname' => $user->vorname,
                'email' => $user->email,
                'isAdmin' => $user->isAdmin,
                'isGeschaeftsstelle' => $user->isGeschaeftsstelle
            ],
            'berechtigungen' => $formattedData
        ]);
    }

    /**
     * Logout (Token löschen)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return [
            'message' => 'Erfolgreich ausgeloggt'
        ];
    }
}
