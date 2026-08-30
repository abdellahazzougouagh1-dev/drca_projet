<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OuverturePlisConcurrent extends Model
{
    use HasFactory;

    protected $fillable = [
        'aoo_id',
        'fournisseur_id',
        'nom_soumissionnaire',
        'dh',
        'cp',
        'rc',
        'cps',
        'm_hum',
        'montant_engagement',
        'observations',
        'ref_courrier',
        'signataire_titre',
        'gerant_nom',
        'statut_analyse',
        'motif_ecartement',
        'admin_conforme',
        'admin_motif_rejet',
        'admin_observations',
        'tech_conforme',
        'tech_note',
        'tech_observations',
        'montant_ht',
        'tva',
        'montant_ttc',
        'classement',
    ];

    protected $casts = [
        'dh' => 'boolean',
        'cp' => 'boolean',
        'rc' => 'boolean',
        'cps' => 'boolean',
        'm_hum' => 'boolean',
        'montant_engagement' => 'decimal:2',
        'admin_conforme' => 'boolean',
        'tech_conforme' => 'boolean',
        'tech_note' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'classement' => 'integer',
    ];

    public function aoo(): BelongsTo
    {
        return $this->belongsTo(Aoo::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function lotDecisions(): HasMany
    {
        return $this->hasMany(ConcurrentLotDecision::class, 'fournisseur_id', 'fournisseur_id');
    }
}
