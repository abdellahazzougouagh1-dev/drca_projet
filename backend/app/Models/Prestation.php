<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    protected $fillable = [
        'consultation_id',
        'numero_prix',
        'designation',
        'specification',
        'unite',
        'quantite',
        'prix_unitaire_ht',
        'montant_ht',
        'tva',
        'montant_ttc',
        'garantie_exigee',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'prix_unitaire_ht' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Prestation $prestation) {
            $montantHt = (float) $prestation->quantite * (float) $prestation->prix_unitaire_ht;
            $prestation->montant_ht = round($montantHt, 2);
            $prestation->montant_ttc = round($montantHt * (1 + ((float) $prestation->tva / 100)), 2);
        });
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
