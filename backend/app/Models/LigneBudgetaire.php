<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneBudgetaire extends Model
{
    use HasFactory;

    protected $table = 'ligne_budgetaires';

    protected $fillable = [
        'type_budget',
        'code_imputation',
        'article',
        'paragraphe',
        'ligne',
        'intitule',
        'niveau',
        'parent_code',
    ];

    /**
     * Scope pour filtrer par type de budget (Investissement ou Fonctionnement)
     */
    public function scopeOfType($query, $type)
    {
        if (!$type) return $query;
        return $query->where('type_budget', 'like', '%' . $type . '%');
    }

    /**
     * Scope pour filtrer les lignes sélectionnables (avec ligne définie ou opérationnelle)
     */
    public function scopeSelectable($query)
    {
        return $query->whereNotNull('ligne')->orWhere('niveau', 'ligne');
    }
}
