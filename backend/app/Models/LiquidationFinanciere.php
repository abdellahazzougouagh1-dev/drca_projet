<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationFinanciere extends Model
{
    protected $fillable = [
        'consultation_id',
        'montant_a_payer',
        'reference_facture',
        'date_facture',
        'ordre_imputation',
        'ordre_paiement',
        'ordre_virement'
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
