<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\NotificationMouvement;
use App\Models\Ordonnancement;
use App\Services\BudgetHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function authorizeDirector(Request $request): void
    {
        if ($request->user()?->role !== 'directeur') {
            abort(403, 'Seul le Directeur peut gérer les notifications budgétaires.');
        }
    }

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
        $this->authorizeDirector($request);

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
        $query = NotificationLigne::with(['notification', 'mouvements']);

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
        NotificationLigne::clearBudgetCache();
        $snapshot = BudgetHierarchyService::computeExerciceSnapshot($exercice);

        $lignes = NotificationLigne::whereHas('notification', function ($q) use ($exercice) {
            $q->where('exercice', $exercice);
        })
            ->with(['notification', 'consultations', 'marches', 'mouvements'])
            ->get();

        $recap = [];

        foreach ($lignes as $ligne) {
            $ligneDomaine = BudgetHierarchyService::normalizeDomaine($ligne->domaine ?: ($ligne->notification ? $ligne->notification->domaine : ''));
            $imputationKey = BudgetHierarchyService::imputationKey($ligneDomaine, $ligne->article, $ligne->paragraphe, $ligne->ligne_budgetaire);

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
                    'total_net' => 0,
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
            $group = $snapshot['groups'][$key] ?? null;
            $row['total_credits'] = round($row['reports'] + $row['credits_neufs'] + $row['credits_engagements'], 2);
            $netReports = max(0, $row['reports'] - $row['diminution_report']);
            $netCreditsNeufs = max(0, $row['credits_neufs'] - $row['diminution_credit_neuf']);
            $netEngagement = max(0, $row['credits_engagements'] - $row['diminution_credit_engagement']);
            $totalNet = round($netReports + $netCreditsNeufs + $netEngagement, 2);

            $row['total_net'] = $group ? $group['total_net'] : $totalNet;
            $row['credits_engages'] = $group ? $group['total_engaged'] : round((float) $row['credits_engages'], 2);
            $row['credits_disponibles'] = $group ? $group['available_total'] : round(max(0, $totalNet - $row['credits_engages']), 2);
        }
        unset($row);

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
                'total_net' => $snapshot['total_net'],
                'total_engages' => $snapshot['total_engaged'],
                'total_disponibles' => $snapshot['available_total'],
            ],
        ]);
    }

    public function updateLigne(Request $request, $id)
    {
        $this->authorizeDirector($request);

        $validated = $request->validate([
            'article' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paragraphe' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ligne_budgetaire' => ['sometimes', 'nullable', 'string', 'max:255'],
            'libelle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type_budget' => ['sometimes', 'nullable', 'string', 'max:100'],
            'domaine' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reports' => ['sometimes', 'numeric', 'min:0'],
            'diminution_report' => ['sometimes', 'numeric', 'min:0'],
            'credits_neufs' => ['sometimes', 'numeric', 'min:0'],
            'diminution_credit_neuf' => ['sometimes', 'numeric', 'min:0'],
            'credits_engagements' => ['sometimes', 'numeric', 'min:0'],
            'diminution_credit_engagement' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $ligne = DB::transaction(function () use ($id, $validated) {
            $ligne = NotificationLigne::with('notification')->lockForUpdate()->findOrFail($id);
            BudgetHierarchyService::assertNotificationLigneUpdateValid($ligne, $validated);
            $ligne->update($validated);
            return $ligne;
        });

        NotificationLigne::clearBudgetCache();
        return response()->json($ligne->fresh('mouvements'));
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeDirector($request);

        DB::transaction(function () use ($id) {
            $notification = Notification::with('lignes')->lockForUpdate()->findOrFail($id);
            BudgetHierarchyService::assertNotificationDeletableAndRelink($notification);

            foreach ($notification->lignes as $ligne) {
                $ligne->mouvements()->delete();
                $ligne->delete();
            }
            $notification->mouvements()->delete();
            $notification->delete();
        });

        NotificationLigne::clearBudgetCache();
        return response()->json(['message' => 'Notification supprimée avec succès']);
    }

    public function getReportExerciceStatus(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));
        $domaine = $request->query('domaine');

        $invReportLigne = NotificationLigne::where('reports', '>', 0)
            ->where(function ($q) {
                $q->where('domaine', 'INVESTISSEMENT')
                  ->orWhereNull('domaine')
                  ->orWhereHas('notification', function ($nq) {
                      $nq->where('domaine', 'INVESTISSEMENT')->orWhereNull('domaine');
                  });
            })
            ->whereHas('notification', function ($q) use ($exercice) {
                $q->where('exercice', $exercice);
            })
            ->with('notification')
            ->first();

        $fncReportLigne = NotificationLigne::where('reports', '>', 0)
            ->where(function ($q) {
                $q->where('domaine', 'FONCTIONNEMENT')
                  ->orWhereHas('notification', function ($nq) {
                      $nq->where('domaine', 'FONCTIONNEMENT');
                  });
            })
            ->whereHas('notification', function ($q) use ($exercice) {
                $q->where('exercice', $exercice);
            })
            ->with('notification')
            ->first();

        $chosen = null;
        if ($domaine === 'FONCTIONNEMENT') {
            $chosen = $fncReportLigne;
        } elseif ($domaine === 'INVESTISSEMENT') {
            $chosen = $invReportLigne;
        } else {
            $chosen = $invReportLigne ?: $fncReportLigne;
        }

        return response()->json([
            'has_report' => (bool)$chosen,
            'exercice' => intval($exercice),
            'report_montant' => $chosen ? floatval($chosen->reports) : 0,
            'notification_numero' => $chosen?->notification?->numero,
            'notification_date' => $chosen?->notification?->date_notification,
            'ligne_budgetaire' => $chosen ? "{$chosen->article}/{$chosen->paragraphe}/{$chosen->ligne_budgetaire}" : null,
            'libelle' => $chosen?->libelle,
            'investissement' => [
                'has_report' => (bool)$invReportLigne,
                'report_montant' => $invReportLigne ? floatval($invReportLigne->reports) : 0,
                'notification_numero' => $invReportLigne?->notification?->numero,
                'notification_date' => $invReportLigne?->notification?->date_notification,
                'ligne_budgetaire' => $invReportLigne ? "{$invReportLigne->article}/{$invReportLigne->paragraphe}/{$invReportLigne->ligne_budgetaire}" : null,
                'libelle' => $invReportLigne?->libelle,
            ],
            'fonctionnement' => [
                'has_report' => (bool)$fncReportLigne,
                'report_montant' => $fncReportLigne ? floatval($fncReportLigne->reports) : 0,
                'notification_numero' => $fncReportLigne?->notification?->numero,
                'notification_date' => $fncReportLigne?->notification?->date_notification,
                'ligne_budgetaire' => $fncReportLigne ? "{$fncReportLigne->article}/{$fncReportLigne->paragraphe}/{$fncReportLigne->ligne_budgetaire}" : null,
                'libelle' => $fncReportLigne?->libelle,
            ],
        ]);
    }

    public function getDashboardStats(Request $request)
    {
        $exercice = $request->query('exercice', date('Y'));
        NotificationLigne::clearBudgetCache();
        $snapshot = BudgetHierarchyService::computeExerciceSnapshot($exercice);
        $recap = $this->getRecapitulatifExercice($request)->getData(true);

        $lignes = $recap['lignes'] ?? [];

        foreach ($lignes as &$ligne) {
            $gKey = BudgetHierarchyService::imputationKey($ligne['domaine'] ?? '', $ligne['article'] ?? '', $ligne['paragraphe'] ?? '', $ligne['ligne_budgetaire'] ?? '');
            if (isset($snapshot['groups'][$gKey])) {
                $group = $snapshot['groups'][$gKey];
                $ligne['total_credits'] = $group['total_net'];
                $ligne['total_brut'] = $group['total_credits'];
                $ligne['credits_engages'] = $group['total_engaged'];
                $ligne['credits_disponibles'] = $group['available_total'];
            }
            foreach (['ordonnance_reports', 'ordonnance_credits_consolides', 'ordonnance_credits_neufs', 'ordonnance_ras', 'ordonnance_rap', 'total_ordonnance', 'paiement_reports', 'paiement_credits_consolides', 'paiement_credits_neufs', 'paiement_ras', 'paiement_rap', 'total_paiements'] as $champ) {
                $ligne[$champ] = 0.0;
            }
        }
        unset($ligne);

        $indexParImputation = [];
        foreach ($lignes as $index => $ligne) {
            $gKey = BudgetHierarchyService::imputationKey($ligne['domaine'] ?? '', $ligne['article'] ?? '', $ligne['paragraphe'] ?? '', $ligne['ligne_budgetaire'] ?? '');
            $indexParImputation[$gKey] = $index;
        }

        $ordonnancements = Ordonnancement::with(['ordres', 'consultation.budget', 'consultation.notificationLigne', 'marche.notificationLigne', 'liquidation', 'notificationLigne'])
            ->where('exercice', (string) $exercice)
            ->get();

        foreach ($ordonnancements as $ordonnancement) {
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            if ($amounts['total_ordonnance'] <= 0 && $amounts['total_paiements'] <= 0) {
                continue;
            }

            $ligneId = $ordonnancement->notification_ligne_id
                ?: $ordonnancement->notificationLigne?->id
                ?: $ordonnancement->consultation?->notification_ligne_id
                ?: $ordonnancement->marche?->notification_ligne_id;

            $gKey = ($ligneId && isset($snapshot['ligne_id_to_group'][$ligneId])) ? $snapshot['ligne_id_to_group'][$ligneId] : null;

            if ($gKey === null) {
                $dom = $ordonnancement->budget_type ?: ($ordonnancement->domaine ?: ($ordonnancement->consultation?->type_budget ?: $ordonnancement->marche?->type_budget));
                $art = $ordonnancement->article ?: ($ordonnancement->art ?: ($ordonnancement->consultation?->budget?->art ?: $ordonnancement->marche?->article_budget));
                $par = $ordonnancement->paragraphe ?: ($ordonnancement->par ?: ($ordonnancement->consultation?->budget?->par ?: $ordonnancement->marche?->paragraphe_budget));
                $lig = $ordonnancement->ligne ?: ($ordonnancement->ligne_budgetaire ?: ($ordonnancement->lig ?: ($ordonnancement->consultation?->budget?->lig ?: $ordonnancement->marche?->ligne_budget)));
                if ($art && $par && $lig) {
                    $candidate = BudgetHierarchyService::imputationKey($dom, $art, $par, $lig);
                    if (isset($indexParImputation[$candidate])) {
                        $gKey = $candidate;
                    }
                }
            }

            $index = $gKey !== null ? ($indexParImputation[$gKey] ?? null) : null;
            if ($index === null) {
                continue;
            }

            foreach (['reports', 'credits_consolides', 'credits_neufs', 'ras', 'rap'] as $bucket) {
                $lignes[$index]['ordonnance_' . $bucket] = round($lignes[$index]['ordonnance_' . $bucket] + $amounts['ordonnance'][$bucket], 2);
                $lignes[$index]['paiement_' . $bucket] = round($lignes[$index]['paiement_' . $bucket] + $amounts['paiement'][$bucket], 2);
            }

            $lignes[$index]['total_ordonnance'] = round($lignes[$index]['total_ordonnance'] + $amounts['total_ordonnance'], 2);
            $lignes[$index]['total_paiements'] = round(
                min($lignes[$index]['total_paiements'] + $amounts['total_paiements'], $lignes[$index]['total_ordonnance']),
                2
            );
        }

        $calcPct = function ($part, $total) {
            $p = (float) ($part ?? 0);
            $t = (float) ($total ?? 0);
            if ($t <= 0 || $p <= 0) return 0;
            $raw = ($p / $t) * 100;
            if ($raw > 0 && $raw < 0.01) {
                return round($raw, 4);
            }
            return round($raw, 2);
        };

        foreach ($lignes as &$ligne) {
            $ligne['taux_engagement'] = $calcPct($ligne['credits_engages'], $ligne['total_credits']);
            $ligne['taux_ordonnancement'] = $calcPct($ligne['total_ordonnance'], $ligne['credits_engages']);
            $ligne['taux_ordonnancement_notifie'] = $calcPct($ligne['total_ordonnance'], $ligne['total_credits']);
            $ligne['taux_paiement_ordonnancement'] = $calcPct($ligne['total_paiements'], $ligne['total_ordonnance']);
            $ligne['taux_paiement_engagement'] = $calcPct($ligne['total_paiements'], $ligne['credits_engages']);
            $ligne['taux_paiement_notifie'] = $calcPct($ligne['total_paiements'], $ligne['total_credits']);
        }
        unset($ligne);

        $totalNotifie = $snapshot['total_net'];
        $totalEngage = $snapshot['total_engaged'];
        $totalDisponible = $snapshot['available_total'];
        $totalOrdonnance = $snapshot['total_ordonnanced'];
        $totalPaiement = min($snapshot['total_paid'], $totalOrdonnance);
        $tauxConsommation = $calcPct($totalEngage, $totalNotifie);

        return response()->json([
            'total_notifie' => $totalNotifie,
            'total_engage' => $totalEngage,
            'total_disponible' => $totalDisponible,
            'total_liquide' => $totalOrdonnance,
            'total_ordonnance' => $totalOrdonnance,
            'total_paiement' => $totalPaiement,
            'taux_consommation' => $tauxConsommation,
            'taux_ordonnancement' => $calcPct($totalOrdonnance, $totalEngage),
            'taux_ordonnancement_notifie' => $calcPct($totalOrdonnance, $totalNotifie),
            'taux_paiement_ordonnancement' => $calcPct($totalPaiement, $totalOrdonnance),
            'taux_paiement_engagement' => $calcPct($totalPaiement, $totalEngage),
            'taux_paiement_notifie' => $calcPct($totalPaiement, $totalNotifie),
            'lignes' => $lignes,
        ]);
    }
}
