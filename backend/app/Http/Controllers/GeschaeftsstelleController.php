<?php

namespace App\Http\Controllers;

use App\Models\Abrechnung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeschaeftsstelleController extends Controller
{
    /**
     * [GET] Geschäftsstelle: Alle Abrechnungen, die vom AL genehmigt wurden.
     * Inklusive Info, WER genehmigt hat.
     */
    public function getAbrechnungenFuerGeschaeftsstelle(Request $request)
    {
        $statusGenehmigtAL = 21; // ID für "Genehmigt durch AL"

        $abrechnungen = Abrechnung::with([
            'creator',
            'abteilung',
            'stundeneintraege',
            'statusLogs' => function($q) {
                $q->orderBy('modifiedAt', 'desc');
            },
            // NEU: Wir laden den User, der den Status geändert hat
            'statusLogs.modifier'
        ])
            ->get();

        // Filtern: Nur Abrechnungen, die aktuell auf Status 21 stehen
        $filterteAbrechnungen = $abrechnungen->filter(function($a) use ($statusGenehmigtAL) {
            $neuestesLog = $a->statusLogs->first();
            return $neuestesLog && $neuestesLog->fk_statusID == $statusGenehmigtAL;
        });

        // Formatieren
        $result = $filterteAbrechnungen->map(function($a) use ($statusGenehmigtAL) {

            // Wir suchen den spezifischen Log-Eintrag für die AL-Genehmigung
            // (Das ist meistens der neueste, aber sicherheitshalber suchen wir nach ID 21)
            $genehmigungsLog = $a->statusLogs->firstWhere('fk_statusID', $statusGenehmigtAL);

            $genehmigerName = 'Unbekannt';
            if ($genehmigungsLog && $genehmigungsLog->modifier) {
                $genehmigerName = $genehmigungsLog->modifier->vorname . ' ' . $genehmigungsLog->modifier->name;
            }

            return [
                'AbrechnungID' => $a->AbrechnungID,
                'mitarbeiterName' => $a->creator->vorname . ' ' . $a->creator->name,
                'abteilung' => $a->abteilung->name ?? 'Unbekannt',
                'stunden' => round($a->stundeneintraege->sum('dauer'), 2),
                'zeitraum' => \Carbon\Carbon::parse($a->zeitraumVon)->format('d.m.Y') . ' - ' . \Carbon\Carbon::parse($a->zeitraumBis)->format('d.m.Y'),

                // Datum aus dem Log nehmen
                'datumGenehmigtAL' => $genehmigungsLog ? \Carbon\Carbon::parse($genehmigungsLog->modifiedAt)->format('d.m.Y') : '-',

                // NEU: Wer hat genehmigt?
                'genehmigtDurch' => $genehmigerName,

                'details' => $a->stundeneintraege->map(function($e) {
                    return [
                        'id'    => $e->EintragID, // <<— NEU: eindeutige ID des Stundeneintrags
                        'datum' => \Carbon\Carbon::parse($e->datum)->format('d.m.Y'),
                        'dauer' => $e->dauer,
                        'kurs'  => $e->kurs
                    ];
                })
            ];
        })->values();

        return response()->json($result);
    }

    /**
     * [POST] Geschäftsstelle: Finale Freigabe (Auszahlung)
     */
    public function finalize(Request $request, $id)
    {
        $userId = Auth::id();
        $abrechnung = Abrechnung::findOrFail($id);

        $statusFinal = 22; // "Abgeschlossen" / "Ausbezahlt"

        // Log schreiben
        \App\Models\AbrechnungStatusLog::create([
            'fk_abrechnungID' => $abrechnung->AbrechnungID,
            'fk_statusID'     => $statusFinal,
            'modifiedBy'      => $userId,
            'modifiedAt'      => now(),
            'kommentar'       => 'Finale Freigabe durch Geschäftsstelle'
        ]);

        return response()->json(['message' => 'Abrechnung final abgeschlossen.']);
    }
    /**
     * [GET] Geschäftsstelle: Historische Abrechnungen nach Quartal/Jahr.
     * Wird von /api/geschaeftsstelle/abrechnungen-historie aufgerufen.
     */
    public function getAbrechnungenHistorieFuerGeschaeftsstelle(Request $request)
    {
        $year = (int) $request->query('year', \Carbon\Carbon::now()->year);
        $quarter = $request->query('quarter'); // 'Q1' | 'Q2' | 'Q3' | 'Q4' | null

        // Start-/Enddatum anhand Quartal bestimmen
        if ($quarter === 'Q1') {
            $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
            $end   = \Carbon\Carbon::create($year, 3, 31)->endOfDay();
        } elseif ($quarter === 'Q2') {
            $start = \Carbon\Carbon::create($year, 4, 1)->startOfDay();
            $end   = \Carbon\Carbon::create($year, 6, 30)->endOfDay();
        } elseif ($quarter === 'Q3') {
            $start = \Carbon\Carbon::create($year, 7, 1)->startOfDay();
            $end   = \Carbon\Carbon::create($year, 9, 30)->endOfDay();
        } elseif ($quarter === 'Q4') {
            $start = \Carbon\Carbon::create($year, 10, 1)->startOfDay();
            $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();
        } else {
            // Kein Quartal angegeben: komplettes Jahr
            $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
            $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();
        }

        $abrechnungen = Abrechnung::with([
            'creator',
            'abteilung',
            'stundeneintraege',
            'statusLogs' => function ($q) {
                $q->orderBy('modifiedAt', 'desc');
            },
            'statusLogs.statusDefinition',
        ])
            // Abrechnung fällt ins Quartal, wenn ihr Zeitraum sich mit dem Quartalszeitraum schneidet
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('zeitraumVon', [$start, $end])
                    ->orWhereBetween('zeitraumBis', [$start, $end]);
            })
            ->orderBy('zeitraumVon', 'asc')
            ->get();

        $result = $abrechnungen->map(function ($a) {
            $latestLog = $a->statusLogs->first();
            $statusName = $latestLog && $latestLog->statusDefinition
                ? $latestLog->statusDefinition->name
                : 'Unbekannt';

            return [
                'AbrechnungID'    => $a->AbrechnungID,
                'mitarbeiterName' => $a->creator->vorname . ' ' . $a->creator->name,
                'abteilung'       => $a->abteilung->name ?? 'Unbekannt',
                'stunden'         => round($a->stundeneintraege->sum('dauer'), 2),
                'zeitraum'        => \Carbon\Carbon::parse($a->zeitraumVon)->format('d.m.Y') . ' - ' . \Carbon\Carbon::parse($a->zeitraumBis)->format('d.m.Y'),
                'status'          => $statusName,
            ];
        })->values();

        return response()->json($result);
    }
}
