<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stundeneintrag;
use App\Models\StundeneintragStatusLog;
use Illuminate\Support\Facades\DB; // Wichtig für Transactions
use Illuminate\Support\Facades\Auth; // Um den User zu holen
use Carbon\Carbon; // Für Zeitberechnungen

class StundeneintragController extends Controller
{
    /**
     * Speichert einen neuen Stundeneintrag.
     */
    public function store(Request $request)
    {
        // 1. Validierung der Eingaben
        $validated = $request->validate([
            'datum'         => 'required|date',
            'beginn'        => 'required|date_format:H:i',
            'ende'          => 'required|date_format:H:i|after:beginn', // Ende muss nach Beginn sein
            'kurs'          => 'nullable|string',
            'fk_abteilung'  => 'nullable|exists:abteilung_definition,AbteilungID', // Prüft, ob Abteilung existiert
        ]);

        // 2. Dauer automatisch berechnen (optional, aber praktisch)
        // Wir nehmen an, Dauer ist ein Double (Industriestunden, z.B. 1.5 für 1h 30m)
        $start = Carbon::createFromFormat('H:i', $validated['beginn']);
        $end   = Carbon::createFromFormat('H:i', $validated['ende']);

        // Berechnet Differenz in Stunden als Dezimalzahl
        $dauer = $end->floatDiffInHours($start);

        // 3. Speichern in einer Transaktion
        try {
            DB::transaction(function () use ($validated, $dauer) {

                // A. Den Stundeneintrag erstellen
                $eintrag = Stundeneintrag::create([
                    'datum'           => $validated['datum'],
                    'beginn'          => $validated['beginn'],
                    'ende'            => $validated['ende'],
                    'dauer'           => $dauer,
                    'kurs'            => $validated['kurs'] ?? null,
                    'fk_abteilung'    => $validated['fk_abteilung'] ?? null,
                    // Aktuell eingeloggter User als Ersteller
                    'createdBy'       => Auth::id(),
                    // Automatisch aktueller Zeitstempel (falls nicht durch DB gesetzt)
                    'createdAt'       => now(),
                ]);

                // B. Den initialen Status setzen (Log-Eintrag)
                // Angenommen: StatusID 1 = "Neu" oder "Erstellt"
                $initialStatusID = 2;

                StundeneintragStatusLog::create([
                    'fk_stundeneintragID' => $eintrag->EintragID, // Die ID vom gerade erstellten Eintrag
                    'fk_statusID'         => $initialStatusID,
                    'modifiedBy'          => Auth::id(),
                    'modifiedAt'          => now(),
                    'kommentar'           => 'Eintrag neu erstellt.',
                ]);
            });

        } catch (\Exception $e) {
            // HIER ÄNDERN: Immer JSON zurückgeben bei Fehler
            return response()->json([
                'message' => 'Fehler beim Speichern',
                'error' => $e->getMessage()
            ], 500);
        }

        // HIER ÄNDERN: Am Ende der Funktion immer JSON zurückgeben (kein redirect!)
        return response()->json([
            'message' => 'Stundeneintrag erfolgreich erstellt',
        ], 201);
    }
}
