<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stundeneintrag extends Model
{
    protected $table = 'stundeneintrag';
    protected $primaryKey = 'EintragID';

    // 3. Timestamps konfigurieren
    // Da du 'createdAt' hast, aber kein 'updated_at', deaktivieren wir
    // die Standard-Timestamps, damit Laravel nicht abstürzt.
    public $timestamps = false;

    // Wir sagen Laravel, wie die Creation-Spalte heißt, falls du sie nutzen willst
    const CREATED_AT = 'createdAt';

    protected $fillable = [
        'datum',
        'beginn',
        'ende',
        'dauer',
        'kurs',
        'createdBy',
        'fk_abrechnungID',
        'fk_abteilung',
        'createdAt' // Explizit erlauben, falls manuell gesetzt
    ];

    // --- BEZIEHUNGEN ---

    // Wer hat den Eintrag erstellt?
    public function ersteller()
    {
        // belongsTo(Model, 'eigener_fremdschlüssel', 'fremde_id')
        return $this->belongsTo(User::class, 'createdBy', 'UserID');
    }

    // Zu welcher Abteilung gehört das?
    // (Annahme: Du hast ein Model 'AbteilungDefinition' erstellt)
    public function abteilung()
    {
        return $this->belongsTo(AbteilungDefinition::class, 'fk_abteilung', 'AbteilungID');
    }

    // Historie aller Status-Änderungen
    public function statusLogs()
    {
        return $this->hasMany(StundeneintragStatusLog::class, 'fk_stundeneintragID', 'EintragID');
    }

    // Hilfsfunktion: Holt den aktuellsten Status
    public function aktuellerStatusLog()
    {
        return $this->hasOne(StundeneintragStatusLog::class, 'fk_stundeneintragID', 'EintragID')
            ->latest('modifiedAt');
    }
}
