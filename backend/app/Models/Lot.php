<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'aoo_id',
        'num_lot',
        'objet_lot',
        'estimation',
        'attributaire_fournisseur_id',
    ];

    protected $casts = [
        'estimation' => 'decimal:2',
    ];

    public function aoo(): BelongsTo
    {
        return $this->belongsTo(Aoo::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ConcurrentLotDecision::class);
    }

    public function attributaire(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'attributaire_fournisseur_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LotItem::class)->orderBy('numero');
    }
}
