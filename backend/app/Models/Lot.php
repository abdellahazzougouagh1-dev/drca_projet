<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'aoo_id',
        'num_lot',
        'objet_lot',
        'objet_lot_ar',
        'estimation',
        'tva_taux',
        'cautionnement_provisoire',
        'notification_ligne_id',
        'art',
        'par',
        'lig',
        'imputation',
        'attributaire_fournisseur_id',
        'montant_attribue_ht',
        'tva_taux_attribue',
        'montant_attribue_ttc',
        'delai_execution_jours',
        'date_debut_prevue',
        'date_fin_prevue',
        'observations_attribution',
    ];

    protected $casts = [
        'estimation' => 'decimal:2',
        'tva_taux' => 'decimal:2',
        'cautionnement_provisoire' => 'decimal:2',
    ];

    public function aoo(): BelongsTo
    {
        return $this->belongsTo(Aoo::class);
    }

    public function notificationLigne(): BelongsTo
    {
        return $this->belongsTo(NotificationLigne::class, 'notification_ligne_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ConcurrentLotDecision::class);
    }

    public function attributaire(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'attributaire_fournisseur_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LotItem::class)->orderBy('numero');
    }
}
