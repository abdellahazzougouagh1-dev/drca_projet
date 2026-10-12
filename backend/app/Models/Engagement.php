<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    protected $fillable = [
        'numero',
        'consultation_id',
        'fournisseur_id',
        'montant_global',
        'type_engagement_id',
        'objet',
        'statut_actuel',
        'data_specifique',
        'date_engagement',
        'delai_execution',
        'date_notification',
        'date_commencement',
    ];

    protected $casts = [
        'data_specifique' => 'array',
        'date_engagement' => 'date',
        'date_notification' => 'date',
        'date_commencement' => 'date',
    ];

    public function getParam($key, $default = null)
    {
        return data_get($this->data_specifique, $key, $default);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function typeEngagement()
    {
        return $this->belongsTo(TypeEngagement::class);
    }

    public function etapeInstances()
    {
        return $this->hasMany(EtapeInstance::class);
    }

    public function historiqueStatuts()
    {
        return $this->hasMany(HistoriqueStatut::class);
    }
}

