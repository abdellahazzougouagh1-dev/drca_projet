<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotItem extends Model
{
    protected $fillable = [
        'lot_id',
        'numero',
        'designation',
        'unite',
        'quantite',
        'prix_unitaire_ht',
        'montant_ht',
        'tva_taux',
    ];

    protected $casts = [
        'numero' => 'integer',
        'quantite' => 'decimal:2',
        'prix_unitaire_ht' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'tva_taux' => 'decimal:2',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function marcheBordereauItems(): HasMany
    {
        return $this->hasMany(MarcheBordereauItem::class);
    }
}
