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
        $domaine = strtoupper($this->domaine ?: ($this->notification?->domaine ?? 'INVESTISSEMENT'));
        if (in_array($domaine, ['INVESTISSEMENT', 'FONCTIONNEMENT'], true)) {
            // Sum each saved registry row once, using its linked budget line.
            $engageNeuf = RegistreEngagement::query()
                ->where('budget', $domaine === 'FONCTIONNEMENT' ? 'Fonctionnement' : 'Investissement')
                ->where(function ($query) {
                    $query->whereHas('consultation', fn ($q) => $q->where('notification_ligne_id', $this->id))
                        ->orWhereHas('aoo', fn ($q) => $q->where('notification_ligne_id', $this->id))
                        ->orWhereHas('marche', fn ($q) => $q->where('notification_ligne_id', $this->id));
                })
                ->sum('montant_engager_neuf');

            if ($domaine === 'FONCTIONNEMENT') {
                return round(floatval($this->reports ?? 0)
                    - floatval($this->diminution_report ?? 0)
                    + floatval($engageNeuf), 2);
            }

            $creditsConsolides = $this->mouvements()
                ->where('type_credit', 'NEUF_CC')
                ->where('nature', 'ALIMENTATION')
                ->sum('montant');

            return round(floatval($this->reports ?? 0)
                + floatval($this->credits_engagements ?? 0)
                + floatval($creditsConsolides)
                + floatval($engageNeuf), 2);
        }

        $engagementsConsultations = \App\Models\Engagement::whereIn('consultation_id', $this->consultations()->pluck('id'))->sum('montant_global');
        $engagementsMarches = $this->marches()->where('statut_acte_engagement', '!=', 'Annulé')->sum('montant') ?? 0;
        $engageNeuf = floatval($engagementsConsultations + $engagementsMarches);

        $report = floatval($this->reports ?? 0);
        $engagement = floatval($this->credits_engagements ?? 0);
        $creditsConsolides = floatval($this->credits_consolides ?? 0);

        return round($report + $creditsConsolides + $engagement + $engageNeuf, 2);
    }

    public function getCreditsDisponiblesAttribute()
    {
        $netCreditsNeufs = max(0, floatval($this->credits_neufs ?? 0) - floatval($this->diminution_credit_neuf ?? 0));
        $engagementsConsultations = \App\Models\Engagement::whereIn('consultation_id', $this->consultations()->pluck('id'))->sum('montant_global');
        $engagementsMarches = $this->marches()->where('statut_acte_engagement', '!=', 'Annulé')->sum('montant') ?? 0;
        $engageNeuf = floatval($engagementsConsultations + $engagementsMarches);

        return round(max(0, $netCreditsNeufs - $engageNeuf), 2);
    }
}
