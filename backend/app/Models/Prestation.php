<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    protected $fillable = [
        'consultation_id',
        'designation',
        'unite',
        'quantite',
        'prix_unitaire_ht',
        'montant_ht',
        'tva',
        'montant_ttc',
    ];

    protected static function booted(): void
    {
        static::saving(function (Prestation $prestation) {
            $prestation->montant_ht = $prestation->quantite * $prestation->prix_unitaire_ht;
            $prestation->montant_ttc = $prestation->montant_ht * (1 + ($prestation->tva / 100));
        });
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
