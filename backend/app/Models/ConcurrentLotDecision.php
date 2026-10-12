<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcurrentLotDecision extends Model
{
    protected $fillable = [
        'aoo_id',
        'lot_id',
        'fournisseur_id',
        'montant_propose',
        'statut',
        'motif_ecartement',
    ];

    protected $casts = [
        'montant_propose' => 'decimal:2',
    ];

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
}
