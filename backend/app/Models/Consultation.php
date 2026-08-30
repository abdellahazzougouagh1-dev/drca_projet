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
        'mode_engagement',
        'type_budget',
        'delai_execution',
        'statut_dossier',
        'fournisseur_id',
        'date_reunion',
        'heure_reunion',
        'lieu_reunion',
        'president_commission',
        'membres_commission',
        'observations_commission',
        'objet_consultation_ar',
        'lieu_reunion_ar',
        'cautionnement_provisoire',
    ];

    protected $casts = [
        'membres_commission' => 'array',
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
}
