<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StundeneintragStatusLog extends Model
{
    protected $table = 'stundeneintrag_status_log';
    protected $primaryKey = 'ID';

    // Wieder: Timestamps aus, da du 'modifiedAt' nutzt und kein 'updated_at' hast
    public $timestamps = false;

    // Optional: Wenn du willst, dass Laravel 'modifiedAt' automatisch füllt:
    const CREATED_AT = 'modifiedAt';

    protected $fillable = [
        'fk_stundeneintragID',
        'fk_statusID',
        'modifiedBy',
        'modifiedAt',
        'kommentar'
    ];

    // --- BEZIEHUNGEN ---

    // Welcher Status ist das? (z.B. "Offen", "Genehmigt")
    public function status()
    {
        return $this->belongsTo(StatusDefinition::class, 'fk_statusID', 'StatusID');
    }

    // Zu welchem Stundeneintrag gehört das Log?
    public function eintrag()
    {
        return $this->belongsTo(Stundeneintrag::class, 'fk_stundeneintragID', 'EintragID');
    }

    // Wer hat den Status geändert?
    public function bearbeiter()
    {
        return $this->belongsTo(User::class, 'modifiedBy', 'UserID');
    }
}
