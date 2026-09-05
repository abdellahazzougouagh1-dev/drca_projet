<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'notification_ligne_id',
        'type_budget',
        'domaine',
        'nature',
        'type_credit',
        'numero_notification',
        'date_mouvement',
        'montant',
        'motif',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
        'montant' => 'decimal:2',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function notificationLigne(): BelongsTo
    {
        return $this->belongsTo(NotificationLigne::class, 'notification_ligne_id');
    }
}
