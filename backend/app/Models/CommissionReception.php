<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionReception extends Model
{
    use HasFactory;

    protected $table = 'commission_receptions';

    protected $fillable = [
        'consultation_id',
        'numero_bc',
        'numero_decision',
        'type_reception',
        'date_reception_definitive',
        'periode_du',
        'periode_au',
        'prestations_receptionnees',
        'date_decision',
        'date_reunion',
        'heure_reunion',
        'heure_fin',
        'lieu_reunion',
        'membres_commission',
    ];

    protected $casts = [
        'membres_commission' => 'array',
        'prestations_receptionnees' => 'array',
        'date_decision' => 'date',
        'date_reunion' => 'date',
        'date_reception_definitive' => 'date',
        'periode_du' => 'date',
        'periode_au' => 'date',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
