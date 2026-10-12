<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuiviExecution extends Model
{
    protected $fillable = [
        'consultation_id',
        'date_debut',
        'date_fin_previsionnelle',
        'avancement_pourcentage',
        'statut_execution',
        'observations',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function evenements()
    {
        return $this->hasMany(ExecutionEvenement::class);
    }
}
