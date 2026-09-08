<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aoo extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_aoo',
        'notification_ligne_id',
        'objet',
        'reference',
        'date_preparation',
        'journal_fr',
        'reference_publication_fr',
        'date_publication_fr',
        'journal_ar',
        'reference_publication_ar',
        'date_publication_ar',
        'date_ouverture',
        'heure_ouverture',
        'nombre_lots',
        'budget',
        'type_budget',
        'art',
        'par',
        'lig',
        'statut',
        'president_commission',
        'rapporteur_commission',
        'commission_validee',
        'membres_commission',
        'etat_avancement',
        'num_decision_nomination',
        'date_lettre',
        'lieu_ouverture',
        'lieu_ouverture_ar',
        'objet_ar',
        'articles_rc',
        'num_aoo_interne',
        'heure_levee',
        'date_decision_nomination',
        'references_juridiques',
        'signataire_nom',
        'signataire_fonction',
        'publications_journaux',
        'date_publication_portail',
        'ref_publication_portail',
        'date_mise_en_ligne_portail',
        'mode_passation',
        'prix_reference',
        'numero_engagement',
        'date_engagement',
        'reference_engagement',
        'credit_ouvert_cp',
        'credit_ouvert_ce',
        'depenses_anterieures_cp',
        'depenses_anterieures_ce',
        'depenses_credits_engagement',
        'depenses_credits_consolides',
        'depenses_rap',
        'montant_depense_neuf',
        'interets_moratoires',
        'montant_engager_neuf',
        'pieces_jointes',
    ];

    protected $casts = [
        'date_preparation' => 'date',
        'date_publication_fr' => 'date',
        'date_publication_ar' => 'date',
        'date_publication_portail' => 'date',
        'date_mise_en_ligne_portail' => 'date',
        'date_ouverture' => 'date',
        'date_lettre' => 'date',
        'nombre_lots' => 'integer',
        'budget' => 'decimal:2',
        'prix_reference' => 'decimal:2',
        'date_engagement' => 'date',
        'credit_ouvert_cp' => 'decimal:2',
        'credit_ouvert_ce' => 'decimal:2',
        'depenses_anterieures_cp' => 'decimal:2',
        'depenses_anterieures_ce' => 'decimal:2',
        'depenses_credits_engagement' => 'decimal:2',
        'depenses_credits_consolides' => 'decimal:2',
        'depenses_rap' => 'decimal:2',
        'montant_depense_neuf' => 'decimal:2',
        'interets_moratoires' => 'decimal:2',
        'montant_engager_neuf' => 'decimal:2',
        'commission_validee' => 'boolean',
        'membres_commission' => 'array',
        'publications_journaux' => 'array',
        'date_decision_nomination' => 'date',
        'references_juridiques' => 'array',
    ];

    protected $appends = ['objet_aoo'];

    public function getObjetAooAttribute(): ?string
    {
        return $this->objet;
    }

    public function notificationLigne(): BelongsTo
    {
        return $this->belongsTo(NotificationLigne::class);
    }

    /**
     * Get the marches for the AOO.
     */
    public function marches(): HasMany
    {
        return $this->hasMany(Marche::class);
    }

    /** Ligne du registre créée depuis la fiche d'engagement de cet AOO. */
    public function registreEngagement(): HasOne
    {
        return $this->hasOne(RegistreEngagement::class);
    }

    /**
     * Get the concurrents for the AOO.
     */
    public function concurrents(): HasMany
    {
        return $this->hasMany(OuverturePlisConcurrent::class);
    }

    /**
     * Get the lots for the AOO.
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function concurrentLotDecisions(): HasMany
    {
        return $this->hasMany(ConcurrentLotDecision::class);
    }
}
