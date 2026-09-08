<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ordonnancement extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'date_ordonnancement' => 'date',
        'date_transmission_tresorier' => 'date',
        'date_paiement' => 'date',
        'montant_brut' => 'decimal:2',
        'retenue_tva' => 'decimal:2',
        'retenue_ias' => 'decimal:2',
        'autres_retenues' => 'decimal:2',
        'net_a_payer' => 'decimal:2',
        'credit_consolide' => 'decimal:2',
        'credit_neuf' => 'decimal:2',
        'ras_total' => 'decimal:2',
        'rap_total' => 'decimal:2',
    ];

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(Liquidation::class);
    }

    public function marche(): BelongsTo
    {
        return $this->belongsTo(Marche::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function notificationLigne(): BelongsTo
    {
        return $this->belongsTo(NotificationLigne::class);
    }

    public function ordres(): HasMany
    {
        return $this->hasMany(OrdonnancementOrdre::class);
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(OrdonnancementHistorique::class)->orderBy('created_at', 'desc');
    }
}
