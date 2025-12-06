<?php

namespace App\Http\Controllers;

use App\Models\AbteilungDefinition;
use App\Models\UserRolleAbteilung; // <--- WICHTIG: Importieren
use Illuminate\Http\Request;

class AbteilungController extends Controller
{
    // Deine bestehende Funktion (für alle Abteilungen)
    public function getAbteilung()
    {
        return AbteilungDefinition::select('AbteilungID as id', 'name')->get();
    }

    // NEU: Nur die Abteilungen des Users
    public function getMeineAbteilungen(Request $request)
    {
        $user = $request->user();

        // 1. Zuweisungen laden (inklusive der Abteilungs-Daten)
        $zuweisungen = UserRolleAbteilung::where('fk_userID', $user->UserID)
            ->with('abteilung') // Eager Loading
            ->get();

        // 2. Abteilungen extrahieren und Duplikate entfernen
        // (Unique basierend auf der AbteilungID)
        $abteilungen = $zuweisungen->map(function ($item) {
            return $item->abteilung;
        })->unique('AbteilungID');

        // 3. Formatieren (gleich wie bei getAbteilung: 'id' und 'name')
        $result = $abteilungen->map(function ($dept) {
            return [
                'id' => $dept->AbteilungID,
                'name' => $dept->name
            ];
        })->values(); // 'values()' sorgt dafür, dass das JSON-Array sauber bei Index 0 beginnt

        return response()->json($result);
    }
}
