<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_consultation',
        'annee',
        'date_consultation',
        'objet_consultation',
        'description_detaillee',
        'categorie',
        'type_prestation',
        'intitule',
        'mode_engagement',
        'type_budget',
        'delai_execution',
        'statut_dossier',
        'fournisseur_id',
        'numero_avis',
        'date_limite_devis',
        'heure_limite_devis',
        'lieu_execution',
        'date_reunion',
        'heure_reunion',
        'lieu_reunion',
        'president_commission',
        'membres_commission',
        'observations_commission',
        'objet_consultation_ar',
        'lieu_reunion_ar',
        'cautionnement_provisoire',
        'numero_bc',
        'reference_2',
        's_lig',
        'numero_engagement',
        'credit_ouvert_cp',
        'credit_ouvert_ce',
        'depenses_anterieures_ce',
        'depenses_anterieures_cp',
        'depenses_credits_engagement',
        'depenses_credits_consolides',
        'depenses_rap',
        'montant_depense_neuf',
        'interets_moratoires',
        'montant_engager_neuf',
    ];

    protected $casts = [
        'membres_commission' => 'array',
        'credit_ouvert_cp' => 'decimal:2',
        'credit_ouvert_ce' => 'decimal:2',
        'depenses_anterieures_ce' => 'decimal:2',
        'depenses_anterieures_cp' => 'decimal:2',
        'depenses_credits_engagement' => 'decimal:2',
        'depenses_credits_consolides' => 'decimal:2',
        'depenses_rap' => 'decimal:2',
        'montant_depense_neuf' => 'decimal:2',
        'interets_moratoires' => 'decimal:2',
        'montant_engager_neuf' => 'decimal:2',
    ];

    /**
     * Obtenir le fournisseur attribué à cette consultation (si applicable).
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Obtenir le budget associé à cette consultation.
     */
    public function budget(): HasOne
    {
        return $this->hasOne(Budget::class);
    }

    /**
     * Obtenir les prestations de cette consultation.
     */
    public function prestations()
    {
        return $this->hasMany(Prestation::class);
    }

    /**
     * Obtenir les offres (fournisseurs consultés) de cette consultation.
     */
    public function offres()
    {
        return $this->hasMany(Offre::class);
    }

    public function engagement()
    {
        return $this->hasOne(Engagement::class);
    }

    public function registreEngagement()
    {
        return $this->hasOne(RegistreEngagement::class);
    }

    public function suiviExecution()
    {
        return $this->hasOne(SuiviExecution::class);
    }

    public function receptions()
    {
        return $this->hasMany(Reception::class);
    }

    public function receptionCommission(): HasOne
    {
        return $this->hasOne(CommissionReception::class);
    }

    public function liquidation()
    {
        return $this->hasOne(LiquidationFinanciere::class);
    }

    public function notificationLigne()
    {
        return $this->belongsTo(NotificationLigne::class);
    }

    public function ordonnancements()
    {
        return $this->hasMany(Ordonnancement::class);
    }
}
