<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offre extends Model
{
    protected $fillable = [
        'consultation_id',
        'fournisseur_id',
        'date_envoi',
        'montant_propose',
        'statut_reponse',
        'montant_apres_verification',
        'retenu',
        'observations_offre',
    ];

    protected $casts = [
        'retenu' => 'boolean',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }
}
