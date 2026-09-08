<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liquidation extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'commission_reception' => 'array',
    ];

    public function marche()
    {
        return $this->belongsTo(Marche::class);
    }

    public function pieces()
    {
        return $this->hasMany(LiquidationPiece::class);
    }

    public function lignes()
    {
        return $this->hasMany(LiquidationLigne::class);
    }

    public function ordonnancements()
    {
        return $this->hasMany(Ordonnancement::class);
    }
}
