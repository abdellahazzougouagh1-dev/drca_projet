<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'raison_sociale',
        'ice',
        'if',
        'patente',
        'rc',
        'adresse',
        'ville',
        'telephone',
        'email',
        'representant',
        'qualite_representant',
        'cnss',
        'banque',
        'agence_bancaire',
        'rib',
        'titulaire_compte',
        'domaine_activite',
    ];

    protected $appends = ['gerant_nom'];

    public function getGerantNomAttribute(): ?string
    {
        return $this->representant;
    }

    /**
     * Obtenir les consultations attribuées à ce fournisseur.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }
}
