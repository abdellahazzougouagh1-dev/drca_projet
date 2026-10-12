<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcheAoo extends Model
{
    use HasFactory;

    protected $table = 'marches_aoo';

    protected $fillable = [
        'num_aoo',
        'objet',
        'date_ouverture',
        'heure_ouverture',
        'nombre_lots',
        'journal_francais',
        'journal_arabe',
        'budget_type',
        'code_budget',
        'art',
        'par',
        'lig',
        'caution_provisoire',
        'estimation_administrative',
        'aoo_president',
        'aoo_membre1',
        'aoo_membre2',
        'num_marche',
        'titulaire',
        'montant_max',
        'delai_execution',
        'date_reception',
        'membre_reception_1',
    ];

    protected $casts = [
        'date_ouverture' => 'date',
        'date_reception' => 'date',
        'nombre_lots' => 'integer',
        'caution_provisoire' => 'decimal:2',
        'estimation_administrative' => 'decimal:2',
        'montant_max' => 'decimal:2',
    ];
}
