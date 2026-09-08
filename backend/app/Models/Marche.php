<?php

namespace App\Models;

use App\Services\MarcheWorkflowService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marche extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_marche',
        'aoo_id',
        'lot_id',
        'fournisseur_id',
        'lot',
        'titulaire',
        'objet_marche',
        'qualite_gerant',
        'exercice',
        'type_budget',
        'code_budget',
        'intitule_budget',
        'montant',
        'date_signature',
        'date_approbation',
        'date_notification_marche',
        'os_numero',
        'os_date_signature',
        'os_date_effet',
        'num_decision',
        'date_decision',
        'date_reunion_commission',
        'heure_reunion_commission',
        'lieu_reunion_commission',
        'statut',
        'statut_acte_engagement',
        'statut_marche',
        'statut_approbation',
        'statut_os',
        'date_effet_os',
        'montant_caution_definitive',
        'date_accuse_os',
        'date_accuse_approbation',
        'agent_suivi',
        'date_reception_finale',
        'commission_reception',
        'taux_tva',
        'delai_execution',
        'os_arret_numero',
        'os_arret_date_signature',
        'os_arret_date_effet',
        'os_arret_motif',
        'os_reprise_numero',
        'os_reprise_date_signature',
        'os_reprise_date_effet',
        'num_engagement',
        'reference_engagement',
        'forme_engagement',
        'date_engagement',
        'article_budget',
        'paragraphe_budget',
        'ligne_budget',
        'credit_budget_cp',
        'credit_budget_ce',
        'depenses_engagees_cp',
        'depenses_engagees_ce',
        'disponible_cp',
        'disponible_ce',
        'engagement_propose_cp',
        'engagement_propose_ce',
        'pieces_jointes',
    ];

    protected $casts = [
        'date_signature' => 'date',
        'date_approbation' => 'date',
        'date_notification_marche' => 'date',
        'date_engagement' => 'date',
        'os_date_signature' => 'date',
        'os_date_effet' => 'date',
        'date_decision' => 'date',
        'date_reunion_commission' => 'date',
        'heure_reunion_commission' => 'datetime',
        'date_reception_finale' => 'date',
        'os_arret_date_signature' => 'date',
        'os_arret_date_effet' => 'date',
        'os_reprise_date_signature' => 'date',
        'os_reprise_date_effet' => 'date',
        'montant' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'credit_budget_cp' => 'decimal:2',
        'credit_budget_ce' => 'decimal:2',
        'depenses_engagees_cp' => 'decimal:2',
        'depenses_engagees_ce' => 'decimal:2',
        'disponible_cp' => 'decimal:2',
        'disponible_ce' => 'decimal:2',
        'engagement_propose_cp' => 'decimal:2',
        'engagement_propose_ce' => 'decimal:2',
        'delai_execution' => 'integer',
        'commission_reception' => 'array',
    ];

    protected $appends = ['workflow', 'montant_ht', 'montant_tva', 'interet_moratoire', 'montant_total_engagement'];

    public function getInteretMoratoireAttribute(): float
    {
        $montant = (float) ($this->montant ?? 0);
        return round($montant * 0.01, 2);
    }

    public function getMontantTotalEngagementAttribute(): float
    {
        $montant = (float) ($this->montant ?? 0);
        return round($montant + $this->getInteretMoratoireAttribute(), 2);
    }

    public function getMontantHtAttribute(): ?float
    {
        if ($this->relationLoaded('bordereauItems') && $this->bordereauItems->count() > 0) {
            return round($this->bordereauItems->sum('montant_ht'), 2);
        }

        if ($this->montant && $this->taux_tva) {
            return round($this->montant / (1 + ($this->taux_tva / 100)), 2);
        }
        return null;
    }

    public function getMontantTvaAttribute(): ?float
    {
        if ($this->relationLoaded('bordereauItems') && $this->bordereauItems->count() > 0) {
            $tva = 0;
            foreach ($this->bordereauItems as $item) {
                $tva += $item->montant_ht * ($item->taux_tva / 100);
            }
            return round($tva, 2);
        }

        if ($this->montant && $this->taux_tva) {
            return round($this->montant - $this->getMontantHtAttribute(), 2);
        }
        return null;
    }

    public function getWorkflowAttribute(): array
    {
        return [
            'current_phase' => MarcheWorkflowService::currentPhase($this->statut),
            'progress_percent' => MarcheWorkflowService::progressPercent($this->statut),
            'phases' => MarcheWorkflowService::phases(),
        ];
    }

    /**
     * Get the AOO that owns the marche.
     */
    public function aoo(): BelongsTo
    {
        return $this->belongsTo(Aoo::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function bordereauItems(): HasMany
    {
        return $this->hasMany(MarcheBordereauItem::class);
    }

    public function liquidations(): HasMany
    {
        return $this->hasMany(Liquidation::class);
    }

    public function cloture()
    {
        return $this->hasOne(MarcheCloture::class);
    }

    public function notificationLigne()
    {
        return $this->belongsTo(NotificationLigne::class);
    }

<<<<<<< HEAD
    public function ordonnancements(): HasMany
    {
        return $this->hasMany(Ordonnancement::class);
=======
    public function registreEngagement()
    {
        return $this->hasOne(RegistreEngagement::class);
>>>>>>> 1d0ea52a5d6fe5cef016a727d8466ed21044934d
    }
}
