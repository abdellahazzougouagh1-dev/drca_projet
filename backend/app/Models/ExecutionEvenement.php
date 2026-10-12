<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionEvenement extends Model
{
    protected $fillable = [
        'suivi_execution_id',
        'type_evenement',
        'description',
        'date_evenement',
    ];

    public function suiviExecution()
    {
        return $this->belongsTo(SuiviExecution::class);
    }
}
