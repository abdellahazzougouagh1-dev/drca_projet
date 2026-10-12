<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarcheBordereauItem extends Model
{
    protected $fillable = [
        'marche_id',
        'lot_item_id',
        'prix_unitaire_attributaire',
        'taux_tva',
        'montant_ht',
    ];

    protected $casts = [
        'prix_unitaire_attributaire' => 'decimal:2',
        'montant_ht' => 'decimal:2',
    ];

    public function marche(): BelongsTo
    {
        return $this->belongsTo(Marche::class);
    }

    public function lotItem(): BelongsTo
    {
        return $this->belongsTo(LotItem::class);
    }
}
