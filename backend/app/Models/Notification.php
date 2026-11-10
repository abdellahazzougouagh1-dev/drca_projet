<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'type_budget',
        'domaine',
        'exercice',
        'date_notification',
        'montant',
        'objet',
        'reference',
        'observations',
    ];

    protected $casts = [
        'date_notification' => 'date',
        'exercice' => 'integer',
        'montant' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => NotificationLigne::clearBudgetCache());
        static::deleted(fn () => NotificationLigne::clearBudgetCache());
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(NotificationLigne::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(NotificationMouvement::class);
    }
}
