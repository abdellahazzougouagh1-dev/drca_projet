<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationLigne extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'article',
        'paragraphe',
        'ligne_budgetaire',
        'libelle',
        'type_budget',
        'domaine',
        'reports',
        'diminution_report',
        'credits_neufs',
        'diminution_credit_neuf',
        'credits_engagements',
        'diminution_credit_engagement',
        'total_credits',
    ];

    protected $casts = [
        'reports' => 'decimal:2',
        'diminution_report' => 'decimal:2',
        'credits_neufs' => 'decimal:2',
        'diminution_credit_neuf' => 'decimal:2',
        'credits_engagements' => 'decimal:2',
        'diminution_credit_engagement' => 'decimal:2',
        'total_credits' => 'decimal:2',
    ];

    protected $appends = ['credits_engages', 'credits_disponibles'];

    protected static function booted(): void
    {
        static::saving(function (NotificationLigne $ligne) {
            $ligne->total_credits = ($ligne->reports ?? 0) 
                                  + ($ligne->credits_neufs ?? 0) 
                                  + ($ligne->credits_engagements ?? 0);
        });
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(NotificationMouvement::class, 'notification_ligne_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function marches(): HasMany
    {
        return $this->hasMany(Marche::class);
    }

    public function aoos(): HasMany
    {
        return $this->hasMany(Aoo::class);
    }

    public function getCreditsEngagesAttribute()
    {
        $engagementsConsultations = \App\Models\Engagement::whereIn('consultation_id', $this->consultations()->pluck('id'))->sum('montant_global');
        
        // Assuming Marches engagements are handled by the 'montant' field or a specific related field
        // as the requirement stated 'Engagement consomme les crédits' 
        // In the existing architecture, AOO uses Marche, and might have engagements there
        $engagementsMarches = $this->marches()->where('statut_acte_engagement', '!=', 'Annulé')->sum('montant') ?? 0;
        
        return round($engagementsConsultations + $engagementsMarches, 2);
    }

    public function getCreditsDisponiblesAttribute()
    {
        $netReports = ($this->reports ?? 0) - ($this->diminution_report ?? 0);
        $netCreditsNeufs = ($this->credits_neufs ?? 0) - ($this->diminution_credit_neuf ?? 0);
        $netEngagement = ($this->credits_engagements ?? 0) - ($this->diminution_credit_engagement ?? 0);
        $totalNet = $netReports + $netCreditsNeufs + $netEngagement;

        return round($totalNet - $this->getCreditsEngagesAttribute(), 2);
    }
}
