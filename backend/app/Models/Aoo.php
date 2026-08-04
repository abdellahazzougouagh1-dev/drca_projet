<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aoo extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_aoo',
        'objet',
        'journal_fr',
        'journal_ar',
        'date_ouverture',
        'heure_ouverture',
        'nombre_lots',
        'budget',
        'art',
        'par',
        'lig',
        'statut',
        'president_commission',
        'membres_commission',
        'etat_avancement',
        'num_decision_nomination',
        'date_lettre',
        'lieu_ouverture',
        'num_aoo_interne',
    ];

    protected $casts = [
        'date_ouverture' => 'date',
        'date_lettre' => 'date',
        'nombre_lots' => 'integer',
        'budget' => 'decimal:2',
        'membres_commission' => 'array',
    ];

    /**
     * Get the marches for the AOO.
     */
    public function marches(): HasMany
    {
        return $this->hasMany(Marche::class);
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
