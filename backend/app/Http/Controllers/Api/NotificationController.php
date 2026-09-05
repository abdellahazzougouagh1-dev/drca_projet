<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\NotificationMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $exercice = $request->query('exercice');
        $query = Notification::with(['lignes.mouvements', 'mouvements'])
            ->orderBy('date_notification', 'desc');

        if ($exercice) {
            $query->where('exercice', $exercice);
        }

        $notifications = $query->get();
        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|string|unique:notifications,numero',
            'type_budget' => 'nullable|string',
            'domaine' => 'nullable|in:FONCTIONNEMENT,INVESTISSEMENT',
            'exercice' => 'required|integer',
            'date_notification' => 'required|date',
            'montant' => 'nullable|numeric|min:0',
            'objet' => 'nullable|string',
            'reference' => 'nullable|string',
            'observations' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.article' => 'required|string',
            'lignes.*.paragraphe' => 'required|string',
            'lignes.*.ligne_budgetaire' => 'required|string',
            'lignes.*.libelle' => 'required|string',
            'lignes.*.type_budget' => 'nullable|string',
            'lignes.*.domaine' => 'nullable|string|in:FONCTIONNEMENT,INVESTISSEMENT',
            'lignes.*.reports' => 'nullable|numeric|min:0',
            'lignes.*.diminution_report' => 'nullable|numeric|min:0',
            'lignes.*.credits_neufs' => 'nullable|numeric|min:0',
            'lignes.*.diminution_credit_neuf' => 'nullable|numeric|min:0',
            'lignes.*.credits_engagements' => 'nullable|numeric|min:0',
            'lignes.*.diminution_credit_engagement' => 'nullable|numeric|min:0',
            'lignes.*.mouvements' => 'nullable|array',
            'lignes.*.mouvements.*.type_budget' => 'nullable|string',
            'lignes.*.mouvements.*.domaine' => 'nullable|string',
            'lignes.*.mouvements.*.nature' => 'nullable|string',
            'lignes.*.mouvements.*.type_credit' => 'required_with:lignes.*.mouvements|string',
            'lignes.*.mouvements.*.numero_notification' => 'nullable|string',
            'lignes.*.mouvements.*.date_mouvement' => 'nullable|date',
            'lignes.*.mouvements.*.montant' => 'required_with:lignes.*.mouvements|numeric|min:0.01',
            'lignes.*.mouvements.*.motif' => 'nullable|string',
        ]);

        $exercice = $validated['exercice'];

        // RÈGLE MÉTIER STRICTE : Le REPORT est lié UNIQUEMENT à l'EXERCICE.
        // Pour un même exercice, il ne peut être saisi qu'un seul REPORT au total, toutes lignes et notifications confondues.
        $hasReportInPayload = false;
        $reportCountInPayload = 0;
        $totalReportMontantPayload = 0;

        foreach ($validated['lignes'] as $ligneData) {
            $ligneHasReport = false;
            $montantLigneReport = 0;

            if (floatval($ligneData['reports'] ?? 0) > 0) {
                $ligneHasReport = true;
                $montantLigneReport = floatval($ligneData['reports']);
            }

            if (!empty($ligneData['mouvements'])) {
                foreach ($ligneData['mouvements'] as $mvt) {
                    if (($mvt['type_credit'] ?? '') === 'REPORT' && floatval($mvt['montant'] ?? 0) > 0) {
                        $ligneHasReport = true;
                        $montantLigneReport = floatval($mvt['montant']);
                    }
                }
            }

            if ($ligneHasReport && $montantLigneReport > 0) {
                $hasReportInPayload = true;
                $reportCountInPayload++;
                $totalReportMontantPayload += $montantLigneReport;
            }
        }

        if ($reportCountInPayload > 1) {
            return response()->json([
                'message' => "⚠️ Un seul crédit de REPORT peut être saisi au total pour l'exercice {$exercice}. Vous avez saisi un montant de report sur {$reportCountInPayload} lignes distinctes dans cette notification.",
            ], 422);
        }

        if ($hasReportInPayload && $totalReportMontantPayload > 0) {
            $existingReportLigne = NotificationLigne::where('reports', '>', 0)
                ->whereHas('notification', function ($q) use ($exercice) {
                    $q->where('exercice', $exercice);
                })
                ->with('notification')
                ->first();

            if ($existingReportLigne) {
                $notifNum = $existingReportLigne->notification ? $existingReportLigne->notification->numero : 'existante';
                return response()->json([
                    'message' => "⚠️ Un REPORT existe déjà pour l'exercice {$exercice} (Notification : {$notifNum}, Ligne : {$existingReportLigne->article}/{$existingReportLigne->paragraphe}/{$existingReportLigne->ligne_budgetaire}, Montant : " . number_format($existingReportLigne->reports, 2, ',', ' ') . " DH). Le REPORT ne peut être saisi qu'une seule fois par exercice, quelle que soit la ligne budgétaire ou la notification.",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $globalHasReport = false;
            $globalHasNotifier = false;

            foreach ($validated['lignes'] as $ligneData) {
                if (floatval($ligneData['reports'] ?? 0) > 0) {
                    $globalHasReport = true;
                }
                if (
                    floatval($ligneData['credits_neufs'] ?? 0) > 0 ||
                    floatval($ligneData['credits_engagements'] ?? 0) > 0 ||
                    floatval($ligneData['diminution_report'] ?? 0) > 0 ||
                    floatval($ligneData['diminution_credit_neuf'] ?? 0) > 0 ||
                    floatval($ligneData['diminution_credit_engagement'] ?? 0) > 0
                ) {
                    $globalHasNotifier = true;
                }
                if (!empty($ligneData['mouvements'])) {
                    foreach ($ligneData['mouvements'] as $mvt) {
                        if (($mvt['type_credit'] ?? '') === 'REPORT') {
                            $globalHasReport = true;
                        } else {
                            $globalHasNotifier = true;
                        }
                    }
                }
            }

            $computedTypeBudget = 'NOTIFIER';
            if ($globalHasReport && $globalHasNotifier) {
                $computedTypeBudget = 'REPORT + NOTIFIER';
            } elseif ($globalHasReport) {
                $computedTypeBudget = 'REPORT';
            }

            $notification = Notification::create([
                'numero' => $validated['numero'],
                'type_budget' => $validated['type_budget'] ?? $computedTypeBudget,
                'domaine' => $validated['domaine'] ?? null,
                'exercice' => $validated['exercice'],
                'date_notification' => $validated['date_notification'],
                'montant' => $validated['montant'] ?? 0,
                'objet' => $validated['objet'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'observations' => $validated['observations'] ?? null,
            ]);

            $notificationTotalCredits = 0;

            foreach ($validated['lignes'] as $ligneData) {
                $mouvementsData = $ligneData['mouvements'] ?? [];

                $reports = 0;
                $diminutionReport = 0;
                $creditsNeufs = 0;
                $diminutionCreditNeuf = 0;
                $creditsEngagements = 0;
                $diminutionCreditEngagement = 0;

                if (!empty($mouvementsData)) {
                    foreach ($mouvementsData as $mvt) {
                        $montant = floatval($mvt['montant'] ?? 0);
                        $typeCredit = $mvt['type_credit'] ?? '';

                        switch ($typeCredit) {
                            case 'REPORT':
                                $reports += $montant;
                                break;
                            case 'DIMINUTION_REPORT':
                                $diminutionReport += $montant;
                                break;
                            case 'NEUF_CC':
                            case 'NEUF_CPN':
                            case 'CREDIT_NEUF':
                                $creditsNeufs += $montant;
                                break;
                            case 'DIMINUTION_PAIEMENT':
                            case 'DIMINUTION_CREDIT_NEUF':
                                $diminutionCreditNeuf += $montant;
                                break;
                            case 'ENGAGEMENT':
                            case 'CREDIT_ENGAGEMENT':
                                $creditsEngagements += $montant;
                                break;
                            case 'DIMINUTION_ENGAGEMENT':
                                $diminutionCreditEngagement += $montant;
                                break;
                        }
                    }
                } else {
                    $reports = floatval($ligneData['reports'] ?? 0);
                    $diminutionReport = floatval($ligneData['diminution_report'] ?? 0);
                    $creditsNeufs = floatval($ligneData['credits_neufs'] ?? 0);
                    $diminutionCreditNeuf = floatval($ligneData['diminution_credit_neuf'] ?? 0);
                    $creditsEngagements = floatval($ligneData['credits_engagements'] ?? 0);
                    $diminutionCreditEngagement = floatval($ligneData['diminution_credit_engagement'] ?? 0);
                }

                $totalCredits = $reports + $creditsNeufs + $creditsEngagements;
                $notificationTotalCredits += $totalCredits;

                $ligneTypeBudget = 'NOTIFIER';
                if ($reports > 0 && ($creditsNeufs > 0 || $creditsEngagements > 0 || $diminutionReport > 0 || $diminutionCreditNeuf > 0 || $diminutionCreditEngagement > 0)) {
                    $ligneTypeBudget = 'REPORT + NOTIFIER';
                } elseif ($reports > 0) {
                    $ligneTypeBudget = 'REPORT';
                }

                $ligneDomaine = $ligneData['domaine'] ?? $notification->domaine;

                $ligne = $notification->lignes()->create([
                    'article' => trim($ligneData['article']),
                    'paragraphe' => trim($ligneData['paragraphe']),
                    'ligne_budgetaire' => trim($ligneData['ligne_budgetaire']),
                    'libelle' => trim($ligneData['libelle']),
                    'type_budget' => $ligneTypeBudget,
                    'domaine' => $ligneDomaine,
                    'reports' => $reports,
                    'diminution_report' => $diminutionReport,
                    'credits_neufs' => $creditsNeufs,
                    'diminution_credit_neuf' => $diminutionCreditNeuf,
                    'credits_engagements' => $creditsEngagements,
                    'diminution_credit_engagement' => $diminutionCreditEngagement,
                    'total_credits' => $totalCredits,
                ]);

                if ($reports > 0) {
                    DB::table('exercice_reports')->updateOrInsert(
                        ['exercice' => $notification->exercice],
                        [
                            'notification_id' => $notification->id,
                            'notification_ligne_id' => $ligne->id,
                            'montant' => $reports,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                if (!empty($mouvementsData)) {
                    foreach ($mouvementsData as $mvt) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $mvt['type_budget'] ?? $ligneTypeBudget,
                            'domaine' => $mvt['domaine'] ?? $ligneDomaine,
                            'nature' => $mvt['nature'] ?? null,
                            'type_credit' => $mvt['type_credit'],
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $mvt['date_mouvement'] ?? $notification->date_notification,
                            'montant' => floatval($mvt['montant']),
                            'motif' => $mvt['motif'] ?? null,
                        ]);
                    }
                } else {
                    if ($reports > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => 'REPORT',
                            'nature' => 'REPORT',
                            'type_credit' => 'REPORT',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $reports,
                        ]);
                    }
                    if ($creditsNeufs > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $ligneTypeBudget,
                            'domaine' => $ligneDomaine,
                            'nature' => 'ALIMENTATION',
                            'type_credit' => 'NEUF_CPN',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $creditsNeufs,
                        ]);
                    }
                    if ($creditsEngagements > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $ligneTypeBudget,
                            'domaine' => $ligneDomaine,
                            'nature' => 'ALIMENTATION',
                            'type_credit' => 'ENGAGEMENT',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $creditsEngagements,
                        ]);
                    }
                    if ($diminutionReport > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $ligneTypeBudget,
                            'domaine' => $ligneDomaine,
                            'nature' => 'DIMINUTION',
                            'type_credit' => 'DIMINUTION_REPORT',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $diminutionReport,
                            'motif' => 'Diminution report',
                        ]);
                    }
                    if ($diminutionCreditNeuf > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $ligneTypeBudget,
                            'domaine' => $ligneDomaine,
                            'nature' => 'DIMINUTION',
                            'type_credit' => 'DIMINUTION_PAIEMENT',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $diminutionCreditNeuf,
                            'motif' => 'Diminution crédit neuf',
                        ]);
                    }
                    if ($diminutionCreditEngagement > 0) {
                        $ligne->mouvements()->create([
                            'notification_id' => $notification->id,
                            'type_budget' => $ligneTypeBudget,
                            'domaine' => $ligneDomaine,
                            'nature' => 'DIMINUTION',
                            'type_credit' => 'DIMINUTION_ENGAGEMENT',
                            'numero_notification' => $notification->numero,
                            'date_mouvement' => $notification->date_notification,
                            'montant' => $diminutionCreditEngagement,
                            'motif' => 'Diminution engagement',
                        ]);
                    }
                }
            }

            if (empty($validated['montant']) || $validated['montant'] == 0) {
                $notification->update(['montant' => $notificationTotalCredits]);
            }

            DB::commit();
            return response()->json($notification->load(['lignes.mouvements', 'mouvements']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de la notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $notification = Notification::with([
            'lignes.consultations',
            'lignes.marches',
            'lignes.mouvements',
            'mouvements',
        ])->findOrFail($id);

        $notification->lignes->each(function ($ligne) {
            $ligne->append(['credits_engages', 'credits_disponibles']);
        });

        return response()->json($notification);
    }

    public function getLignes(Request $request)
    {
        $exercice = $request->query('exercice');
        $query = NotificationLigne::with(['notification', 'mouvements', 'marches.aoo', 'consultations.engagement', 'aoos']);

        if ($exercice) {
            $query->whereHas('notification', function ($q) use ($exercice) {
                $q->where('exercice', $exercice);
            });
        }

        $lignes = $query->get();
        $lignes->each->append(['credits_engages', 'credits_disponibles']);

        return response()->json($lignes);
    }

    public function getLignesExistantes(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));

        $lignes = NotificationLigne::whereHas('notification', function ($q) use ($exercice) {
            $q->where('exercice', $exercice);
        })
            ->with(['notification', 'consultations', 'marches'])
            ->get();

        $grouped = [];

        foreach ($lignes as $ligne) {
            $key = $ligne->article . '-' . $ligne->paragraphe . '-' . $ligne->ligne_budgetaire;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'article' => $ligne->article,
                    'paragraphe' => $ligne->paragraphe,
                    'ligne_budgetaire' => $ligne->ligne_budgetaire,
                    'libelle' => $ligne->libelle,
                    'domaine' => $ligne->domaine,
                    'has_report' => false,
                    'report_montant' => 0,
                    'report_notification' => null,
                ];
            }

            if ($ligne->reports > 0) {
                $grouped[$key]['has_report'] = true;
                $grouped[$key]['report_montant'] += floatval($ligne->reports);
                $grouped[$key]['report_notification'] = $ligne->notification ? $ligne->notification->numero : null;
            }
        }

        return response()->json(array_values($grouped));
    }

    public function getRecapitulatifExercice(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));

        $lignes = NotificationLigne::whereHas('notification', function ($q) use ($exercice) {
            $q->where('exercice', $exercice);
        })
            ->with(['notification', 'consultations', 'marches', 'mouvements'])
            ->get();

        $recap = [];

        foreach ($lignes as $ligne) {
            $ligneDomaine = strtoupper($ligne->domaine ?: ($ligne->notification ? $ligne->notification->domaine : '') ?: 'INVESTISSEMENT');
            $imputationKey = $ligneDomaine . '_' . $ligne->article . '/' . $ligne->paragraphe . '/' . $ligne->ligne_budgetaire;

            if (!isset($recap[$imputationKey])) {
                $recap[$imputationKey] = [
                    'imputation' => $ligne->article . '/' . $ligne->paragraphe . '/' . $ligne->ligne_budgetaire,
                    'article' => $ligne->article,
                    'paragraphe' => $ligne->paragraphe,
                    'ligne_budgetaire' => $ligne->ligne_budgetaire,
                    'libelle' => $ligne->libelle,
                    'domaine' => $ligneDomaine,
                    'reports' => 0,
                    'diminution_report' => 0,
                    'credits_neufs' => 0,
                    'diminution_credit_neuf' => 0,
                    'credits_engagements' => 0,
                    'diminution_credit_engagement' => 0,
                    'total_credits' => 0,
                    'credits_engages' => 0,
                    'credits_disponibles' => 0,
                    'notifications_count' => 0,
                    'notifications' => [],
                    'mouvements' => [],
                ];
            }

            $recap[$imputationKey]['reports'] += floatval($ligne->reports);
            $recap[$imputationKey]['diminution_report'] += floatval($ligne->diminution_report);
            $recap[$imputationKey]['credits_neufs'] += floatval($ligne->credits_neufs);
            $recap[$imputationKey]['diminution_credit_neuf'] += floatval($ligne->diminution_credit_neuf);
            $recap[$imputationKey]['credits_engagements'] += floatval($ligne->credits_engagements);
            $recap[$imputationKey]['diminution_credit_engagement'] += floatval($ligne->diminution_credit_engagement);
            $recap[$imputationKey]['credits_engages'] += floatval($ligne->credits_engages);
            $recap[$imputationKey]['notifications_count']++;

            if ($ligne->notification) {
                $recap[$imputationKey]['notifications'][] = [
                    'id' => $ligne->notification->id,
                    'numero' => $ligne->notification->numero,
                    'date' => $ligne->notification->date_notification,
                    'type_budget' => $ligne->type_budget,
                    'domaine' => $ligne->domaine,
                    'total_ligne' => floatval($ligne->total_credits),
                ];
            }

            if ($ligne->mouvements) {
                foreach ($ligne->mouvements as $mvt) {
                    $recap[$imputationKey]['mouvements'][] = $mvt;
                }
            }
        }

        foreach ($recap as $key => &$row) {
            $row['total_credits'] = round($row['reports'] + $row['credits_neufs'] + $row['credits_engagements'], 2);
            $netReports = $row['reports'] - $row['diminution_report'];
            $netCreditsNeufs = $row['credits_neufs'] - $row['diminution_credit_neuf'];
            $netEngagement = $row['credits_engagements'] - $row['diminution_credit_engagement'];
            $totalNet = $netReports + $netCreditsNeufs + $netEngagement;
            $row['credits_disponibles'] = round($totalNet - $row['credits_engages'], 2);
        }

        return response()->json([
            'exercice' => intval($exercice),
            'lignes' => array_values($recap),
            'totaux' => [
                'total_reports' => array_sum(array_column($recap, 'reports')),
                'total_diminution_report' => array_sum(array_column($recap, 'diminution_report')),
                'total_credits_neufs' => array_sum(array_column($recap, 'credits_neufs')),
                'total_diminution_credit_neuf' => array_sum(array_column($recap, 'diminution_credit_neuf')),
                'total_credits_engagements' => array_sum(array_column($recap, 'credits_engagements')),
                'total_diminution_credit_engagement' => array_sum(array_column($recap, 'diminution_credit_engagement')),
                'total_credits' => array_sum(array_column($recap, 'total_credits')),
                'total_engages' => array_sum(array_column($recap, 'credits_engages')),
                'total_disponibles' => array_sum(array_column($recap, 'credits_disponibles')),
            ],
        ]);
    }

    public function updateLigne(Request $request, $id)
    {
        $ligne = NotificationLigne::findOrFail($id);
        $ligne->update($request->all());
        return response()->json($ligne->load('mouvements'));
    }

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification supprimée avec succès']);
    }

    public function getReportExerciceStatus(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));

        $reportLigne = NotificationLigne::where('reports', '>', 0)
            ->whereHas('notification', function ($q) use ($exercice) {
                $q->where('exercice', $exercice);
            })
            ->with('notification')
            ->first();

        if ($reportLigne) {
            return response()->json([
                'has_report' => true,
                'exercice' => intval($exercice),
                'report_montant' => floatval($reportLigne->reports),
                'notification_numero' => $reportLigne->notification ? $reportLigne->notification->numero : null,
                'notification_date' => $reportLigne->notification ? $reportLigne->notification->date_notification : null,
                'ligne_budgetaire' => "{$reportLigne->article}/{$reportLigne->paragraphe}/{$reportLigne->ligne_budgetaire}",
                'libelle' => $reportLigne->libelle,
            ]);
        }

        return response()->json([
            'has_report' => false,
            'exercice' => intval($exercice),
            'report_montant' => 0,
            'notification_numero' => null,
            'notification_date' => null,
            'ligne_budgetaire' => null,
            'libelle' => null,
        ]);
    }

    public function getDashboardStats(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));
        $recap = $this->getRecapitulatifExercice($request)->getData(true);

        $totaux = $recap['totaux'] ?? [
            'total_credits' => 0,
            'total_engages' => 0,
            'total_disponibles' => 0,
        ];

        $totalNotifie = $totaux['total_credits'];
        $totalEngage = $totaux['total_engages'];
        $totalDisponible = $totaux['total_disponibles'];
        $tauxConsommation = $totalNotifie > 0 ? round(($totalEngage / $totalNotifie) * 100, 2) : 0;

        return response()->json([
            'total_notifie' => $totalNotifie,
            'total_engage' => $totalEngage,
            'total_disponible' => $totalDisponible,
            'total_liquide' => 0,
            'total_ordonnance' => 0,
            'taux_consommation' => $tauxConsommation,
        ]);
    }
}
