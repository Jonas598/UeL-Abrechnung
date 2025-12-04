<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusDefinition extends Model
{
    protected $table = 'status_definition';
    protected $primaryKey = 'StatusID';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'beschreibung'
    ];
}
