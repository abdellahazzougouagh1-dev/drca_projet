<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'art',
        'par',
        'lig',
        'code_imputation',
        'intitule_ligne',
        'exercice_budgetaire',
        'montant_estimatif_ht',
        'tva',
        'montant_ttc',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // Règle de gestion critique: Le montant TTC est toujours calculé avant la sauvegarde
        static::saving(function (Budget $budget) {
            if ($budget->montant_estimatif_ht !== null && $budget->tva !== null) {
                // Formule: TTC = HT * (1 + TVA/100)
                $budget->montant_ttc = $budget->montant_estimatif_ht * (1 + ($budget->tva / 100));
            }
        });
    }

    /**
     * Un Budget appartient à une Consultation.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
