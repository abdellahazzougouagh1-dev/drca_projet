<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistreEngagement extends Model
{
    protected $table = 'registre_engagements';

    protected $fillable = [
        'consultation_id',
        'engagement_id',
        'numero_ordre',
        'date_engagement',
        'numero_rubrique',
        'mode_engagement',
        'reference',
        'reference_2',
        'budget',
        'code',
        'art',
        'par',
        'lig',
        's_lig',
        'intitule',
        'credit_ouvert_cp',
        'credit_ouvert_ce',
        'credit_consolide',
        'depenses_anterieures_ce',
        'depenses_anterieures_cp',
        'depenses_credits_engagement',
        'depenses_credits_consolides',
        'depenses_rap',
        'montant_depense_neuf',
        'interets_moratoires',
        'montant_engager_neuf',
        'objet',
        'beneficiaire',
    ];

    protected $casts = [
        'date_engagement' => 'date',
        'credit_ouvert_cp' => 'decimal:2',
        'credit_ouvert_ce' => 'decimal:2',
        'credit_consolide' => 'decimal:2',
        'depenses_anterieures_ce' => 'decimal:2',
        'depenses_anterieures_cp' => 'decimal:2',
        'depenses_credits_engagement' => 'decimal:2',
        'depenses_credits_consolides' => 'decimal:2',
        'depenses_rap' => 'decimal:2',
        'montant_depense_neuf' => 'decimal:2',
        'interets_moratoires' => 'decimal:2',
        'montant_engager_neuf' => 'decimal:2',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }
}
