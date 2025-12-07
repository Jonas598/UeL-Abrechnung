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
    public function getMeineUelAbteilungen(Request $request)
    {
        $user = $request->user();

        $zuweisungen = UserRolleAbteilung::where('fk_userID', $user->UserID)
            // HIER IST DER FILTER:
            ->whereHas('rolle', function ($query) {
                $query->where('bezeichnung', 'Uebungsleiter');
            })
            ->with('abteilung')
            ->get();

        // Mapping und Formatierung (bleibt gleich)
        $abteilungen = $zuweisungen->map(function ($item) {
            return $item->abteilung;
        })->unique('AbteilungID');

        $result = $abteilungen->map(function ($dept) {
            return [
                'id' => $dept->AbteilungID,
                'name' => $dept->name
            ];
        })->values();

        return response()->json($result);
    }

    public function getMeineLeiterAbteilungen(Request $request)
    {
        $user = $request->user();

        $zuweisungen = UserRolleAbteilung::where('fk_userID', $user->UserID)
            // HIER IST DER FILTER:
            ->whereHas('rolle', function ($query) {
                $query->where('bezeichnung', 'Abteilungsleiter');
            })
            ->with('abteilung')
            ->get();

        // Mapping und Formatierung
        $abteilungen = $zuweisungen->map(function ($item) {
            return $item->abteilung;
        })->unique('AbteilungID');

        $result = $abteilungen->map(function ($dept) {
            return [
                'id' => $dept->AbteilungID,
                'name' => $dept->name
            ];
        })->values();

        return response()->json($result);
    }
}
