<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationLigne extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function liquidation()
    {
        return $this->belongsTo(Liquidation::class);
    }
}
