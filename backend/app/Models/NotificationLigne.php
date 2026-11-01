<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    protected static $engageNeufCache = null;
    protected static $mouvementsCache = null;

    protected static function getCachedEngageNeuf(int $ligneId, string $domaine): float
    {
        $data = Cache::remember('notif_lignes_engage_neuf_map', 60, function () {
            $map = [];

            // Group sum by consultation.notification_ligne_id
            $consultations = RegistreEngagement::query()
                ->join('consultations', 'registre_engagements.consultation_id', '=', 'consultations.id')
                ->whereNotNull('consultations.notification_ligne_id')
                ->selectRaw('consultations.notification_ligne_id as lid, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, sum(COALESCE(registre_engagements.montant_engager_neuf, 0)) as total')
                ->groupBy('consultations.notification_ligne_id', 'registre_engagements.budget')
                ->get();

            foreach ($consultations as $row) {
                $b = $row->bgt ?: 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float)$row->total;
            }

            // Group sum by aoo.notification_ligne_id
            $aoos = RegistreEngagement::query()
                ->join('aoos', 'registre_engagements.aoo_id', '=', 'aoos.id')
                ->whereNotNull('aoos.notification_ligne_id')
                ->selectRaw('aoos.notification_ligne_id as lid, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, sum(COALESCE(registre_engagements.montant_engager_neuf, 0)) as total')
                ->groupBy('aoos.notification_ligne_id', 'registre_engagements.budget')
                ->get();

            foreach ($aoos as $row) {
                $b = $row->bgt ?: 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float)$row->total;
            }

            // Group sum by marche.notification_ligne_id
            $marches = RegistreEngagement::query()
                ->join('marches', 'registre_engagements.marche_id', '=', 'marches.id')
                ->whereNotNull('marches.notification_ligne_id')
                ->selectRaw('marches.notification_ligne_id as lid, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, sum(COALESCE(registre_engagements.montant_engager_neuf, 0)) as total')
                ->groupBy('marches.notification_ligne_id', 'registre_engagements.budget')
                ->get();

            foreach ($marches as $row) {
                $b = $row->bgt ?: 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float)$row->total;
            }

            return $map;
        });

        $b = $domaine === 'FONCTIONNEMENT' ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
        return (float) ($data[$ligneId][$b] ?? 0);
    }

    protected static function getCachedMouvements(int $ligneId): float
    {
        $map = Cache::remember('notif_lignes_mouvements_map', 60, function () {
            return NotificationMouvement::query()
                ->where('type_credit', 'NEUF_CC')
                ->where('nature', 'ALIMENTATION')
                ->selectRaw('notification_ligne_id, sum(COALESCE(montant, 0)) as total')
                ->groupBy('notification_ligne_id')
                ->pluck('total', 'notification_ligne_id')
                ->all();
        });

        return (float) ($map[$ligneId] ?? 0);
    }

    public function getCreditsEngagesAttribute()
    {
        $domaine = strtoupper($this->domaine ?: ($this->relationLoaded('notification') ? $this->notification?->domaine : null) ?: 'INVESTISSEMENT');
        $engageNeuf = static::getCachedEngageNeuf($this->id, $domaine);

        if ($domaine === 'FONCTIONNEMENT') {
            return round(floatval($this->reports ?? 0)
                - floatval($this->diminution_report ?? 0)
                + floatval($engageNeuf), 2);
        }

        $creditsConsolides = static::getCachedMouvements($this->id);

        return round(floatval($this->reports ?? 0)
            + floatval($this->credits_engagements ?? 0)
            + floatval($creditsConsolides)
            + floatval($engageNeuf), 2);
    }

    public function getCreditsDisponiblesAttribute()
    {
        $netCreditsNeufs = max(0, floatval($this->credits_neufs ?? 0) - floatval($this->diminution_credit_neuf ?? 0));
        $domaine = strtoupper($this->domaine ?: ($this->relationLoaded('notification') ? $this->notification?->domaine : null) ?: 'INVESTISSEMENT');
        $engageNeuf = static::getCachedEngageNeuf($this->id, $domaine);

        return round(max(0, $netCreditsNeufs - $engageNeuf), 2);
    }
}
