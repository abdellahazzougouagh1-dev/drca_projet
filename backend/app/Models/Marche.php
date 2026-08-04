<?php

namespace App\Models;

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
        'agent_suivi',
        'date_reception_finale',
        'commission_reception',
    ];

    protected $casts = [
        'date_signature' => 'date',
        'date_approbation' => 'date',
        'date_notification_marche' => 'date',
        'os_date_signature' => 'date',
        'os_date_effet' => 'date',
        'date_decision' => 'date',
        'date_reunion_commission' => 'date',
        'heure_reunion_commission' => 'datetime',
        'date_reception_finale' => 'date',
        'montant' => 'decimal:2',
        'commission_reception' => 'array',
    ];

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
}
