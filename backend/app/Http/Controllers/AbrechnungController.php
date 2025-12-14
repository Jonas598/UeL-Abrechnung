<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Stundeneintrag;
use App\Models\Abrechnung;
use App\Models\AbrechnungStatusLog; // Das neue Model für das Abrechnungs-Log
use App\Models\StundeneintragStatusLog;

class AbrechnungController extends Controller
{
    /**
     * Erstellt eine neue Abrechnung aus ausgewählten Stundeneinträgen.
     */
    public function erstellen(Request $request)
    {
        // 1. Validierung
        $validated = $request->validate([
            'stundeneintrag_ids' => 'required|array|min:1',
            'stundeneintrag_ids.*' => 'integer|exists:stundeneintrag,EintragID',
        ]);

        $userId = Auth::id();
        $ids = $validated['stundeneintrag_ids'];

        // 2. Einträge laden
        // Wir holen nur Einträge, die noch keiner Abrechnung zugeordnet sind (fk_abrechnungID is null)
        $eintraege = Stundeneintrag::whereIn('EintragID', $ids)
            ->where('createdBy', $userId)
            ->whereNull('fk_abrechnungID')
            ->get();

        if ($eintraege->count() !== count($ids)) {
            return response()->json([
                'message' => 'Einige Einträge sind ungültig oder bereits abgerechnet.'
            ], 422);
        }

        // 3. Metadaten für die Abrechnung ermitteln
        // Da die Tabelle 'abrechnung' kein Feld 'summe_stunden' hat, speichern wir das nicht (oder berechnen es on-the-fly).
        // Aber wir brauchen zeitraumVon, zeitraumBis und die Abteilung.

        $minDatum = $eintraege->min('datum');
        $maxDatum = $eintraege->max('datum');

        // Wir nehmen an, alle Einträge gehören zur selben Abteilung. Wir nehmen die vom ersten Eintrag.
        $abteilungId = $eintraege->first()->fk_abteilung;

        // IDs für Status definieren (Beispielwerte - musst du an deine status_definition Tabelle anpassen)
        $statusIdNeu = 20; // z.B. "Neu" oder "Eingereicht"
        $statusIdEintragInAbrechnung = 11; // Status für den Stundeneintrag

        try {
            DB::transaction(function () use ($eintraege, $minDatum, $maxDatum, $abteilungId, $userId, $statusIdNeu, $statusIdEintragInAbrechnung) {

                // A. Abrechnung erstellen
                $abrechnung = Abrechnung::create([
                    'zeitraumVon'  => $minDatum,
                    'zeitraumBis'  => $maxDatum,
                    'fk_abteilung' => $abteilungId,
                    'createdBy'    => $userId,
                    // 'createdAt' wird durch timestamps/Model automatisch gesetzt
                ]);

                // B. Abrechnung Log schreiben (DAS WAR DEIN WUNSCH)
                AbrechnungStatusLog::create([
                    'fk_abrechnungID' => $abrechnung->AbrechnungID, // Wichtig: Primary Key nutzen
                    'fk_statusID'     => $statusIdNeu,
                    'modifiedBy'      => $userId,
                    'modifiedAt'      => now(),
                    'kommentar'       => 'Abrechnung initial erstellt.'
                ]);

                // C. Stundeneinträge aktualisieren und loggen
                foreach ($eintraege as $eintrag) {

                    // Verknüpfung herstellen
                    $eintrag->update([
                        'fk_abrechnungID' => $abrechnung->AbrechnungID
                    ]);

                    // Log für den Stundeneintrag schreiben
                    StundeneintragStatusLog::create([
                        'fk_stundeneintragID' => $eintrag->EintragID,
                        'fk_statusID'         => $statusIdEintragInAbrechnung,
                        'modifiedBy'          => $userId,
                        'modifiedAt'          => now(),
                        'kommentar'           => 'Zu Abrechnung #' . $abrechnung->AbrechnungID . ' hinzugefügt.',
                    ]);
                }
            });

            return response()->json([
                'message' => 'Abrechnung erfolgreich erstellt.',
                'anzahl_eintraege' => $eintraege->count(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen der Abrechnung',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMeineAbrechnungen(Request $request)
    {
        $userId = Auth::id();

        // 1. Alle Abrechnungen des Users laden
        // Wir sortieren nach Erstellungsdatum absteigend (neueste oben)
        $abrechnungen = Abrechnung::where('createdBy', $userId)
            ->with([
                'stundeneintraege', // Für die Summe der Stunden
                'statusLogs.statusDefinition' // Für den Status-Text
            ])
            ->orderBy('createdAt', 'desc')
            ->get();

        // 2. Daten formatieren
        $result = $abrechnungen->map(function($a) {
            // Aktueller Status ist der neueste Log-Eintrag
            $neuestesLog = $a->statusLogs->sortByDesc('modifiedAt')->first();
            $statusName = $neuestesLog ? $neuestesLog->statusDefinition->name : 'Unbekannt';
            $statusId   = $neuestesLog ? $neuestesLog->fk_statusID : 0;

            return [
                'id' => $a->AbrechnungID,
                'zeitraum' => \Carbon\Carbon::parse($a->zeitraumVon)->format('d.m.Y') . ' - ' . \Carbon\Carbon::parse($a->zeitraumBis)->format('d.m.Y'),
                'stunden' => round($a->stundeneintraege->sum('dauer'), 2),
                'status' => $statusName,
                'status_id' => $statusId, // Für Farben im Frontend
                'datum_erstellt' => $a->createdAt->format('d.m.Y'),
            ];
        });

        return response()->json($result);
    }
}
