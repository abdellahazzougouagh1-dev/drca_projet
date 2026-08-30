<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationPiece extends Model
{
    protected $fillable = [
        'liquidation_id',
        'nom',
        'type_document',
        'chemin_fichier',
        'statut_piece',
    ];

    public function liquidation()
    {
        return $this->belongsTo(Liquidation::class);
    }
}
