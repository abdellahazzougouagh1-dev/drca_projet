<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConsultationController extends Controller
{
    public static function isAoMode(?string $modeEngagement): bool
    {
        $normalized = mb_strtoupper(trim((string) $modeEngagement));
        return in_array($normalized, ['AO', 'AOO', "APPEL D'OFFRES", "APPEL D'OFFRE"], true);
    }

    private static function requiredStringRule(int $max = 255): array
    {
        return ['required', 'string', "max:{$max}", 'not_regex:/\A\s*\z/u'];
    }

    private static function sometimesRequiredStringRule(int $max = 255): array
    {
        return ['sometimes', 'required', 'string', "max:{$max}", 'not_regex:/\A\s*\z/u'];
    }

    private static function engagementFieldKeys(): array
    {
        return [
            'numero_engagement',
            'credit_ouvert_cp',
            'credit_ouvert_ce',
            'depenses_anterieures_ce',
            'depenses_anterieures_cp',
            'depenses_credits_engagement',
            'depenses_credits_consolides',
            'depenses_rap',
            'montant_depense_neuf',
            'interets_moratoires',
            'montant_engager_neuf',
        ];
    }

    public static function engagementUpdateRules(array $input, ?Consultation $consultation = null, bool $withDatabaseRules = true): array
    {
        $hasEngagementPayload = false;
        foreach (self::engagementFieldKeys() as $key) {
            if (array_key_exists($key, $input)) {
                $hasEngagementPayload = true;
                break;
            }
        }

        if (!$hasEngagementPayload) {
            return [];
        }

        $effectiveTypeBudget = $input['type_budget'] ?? $consultation?->type_budget ?? 'Investissement';
        $isFonctionnement = strtolower(trim((string) $effectiveTypeBudget)) === 'fonctionnement';

        $numeroEngagementRule = ['required', 'string', 'max:255', 'not_regex:/\A\s*\z/u'];
        if ($withDatabaseRules) {
            $numeroEngagementRule[] = Rule::unique('consultations', 'numero_engagement')->ignore($consultation?->id);
        }

        return [
            'numero_engagement' => $numeroEngagementRule,
            'credit_ouvert_cp' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_anterieures_cp' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'montant_depense_neuf' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'credit_ouvert_ce' => $isFonctionnement
                ? ['nullable', 'numeric', 'min:0', 'max:9999999999999.99']
                : ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_anterieures_ce' => $isFonctionnement
                ? ['nullable', 'numeric', 'min:0', 'max:9999999999999.99']
                : ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_credits_engagement' => $isFonctionnement
                ? ['nullable', 'numeric', 'min:0', 'max:9999999999999.99']
                : ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_credits_consolides' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_rap' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'interets_moratoires' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'montant_engager_neuf' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];
    }

    public static function storeRules(array $input, bool $withDatabaseRules = true): array
    {
        $isAo = self::isAoMode($input['mode_engagement'] ?? null);

        $numeroConsultationRules = $isAo
            ? ['nullable', 'string', 'max:255']
            : self::requiredStringRule(255);
        $numeroBcRules = $isAo
            ? ['nullable', 'string', 'max:255']
            : self::requiredStringRule(255);

        if ($withDatabaseRules) {
            $numeroConsultationRules[] = Rule::unique('consultations', 'numero_consultation');
            $numeroBcRules[] = Rule::unique('consultations', 'numero_bc');
        }

        return [
            // Infos Générales
            'numero_consultation' => $numeroConsultationRules,
            'numero_decision' => $isAo ? ['nullable', 'string', 'max:255'] : self::requiredStringRule(255),
            'numero_bc' => $numeroBcRules,
            'annee' => $isAo ? ['required', 'integer', 'min:2000', 'max:2099'] : ['required', 'integer', 'min:2026', 'max:2099'],
            'date_consultation' => $isAo ? ['required', 'date'] : ['required', 'date', 'after_or_equal:2026-01-01'],
            'date_limite_devis' => $isAo
                ? ['nullable', 'date']
                : ['required', 'date', 'after_or_equal:2026-01-01', 'after_or_equal:date_consultation'],
            'heure_limite_devis' => $isAo ? ['nullable', 'string', 'max:50'] : self::requiredStringRule(50),
            'lieu_execution' => $isAo ? ['nullable', 'string', 'max:255'] : self::requiredStringRule(255),
            'objet_consultation' => self::requiredStringRule(65000),
            'description_detaillee' => ['nullable', 'string'],
            'categorie' => self::requiredStringRule(255),
            'intitule' => $isAo ? ['nullable', 'string', 'max:65000'] : self::requiredStringRule(65000),
            'type_prestation' => $isAo ? self::requiredStringRule(65000) : ['nullable', 'string', 'max:65000'],
            'mode_engagement' => ['required', Rule::in(['BC', 'Convention', 'Contrat', 'AO', 'Bon de commande', "Appel d'offres", "Appel d'offre"])],
            'type_budget' => ['required', Rule::in(['Investissement', 'Fonctionnement'])],
            'delai_execution' => ['required', 'integer', 'min:1'],
            'statut_dossier' => self::requiredStringRule(255),
            'fournisseur_id' => $withDatabaseRules ? ['nullable', 'integer', 'exists:fournisseurs,id'] : ['nullable', 'integer'],
            // Infos Budgétaires
            'notification_ligne_id' => $withDatabaseRules ? ['nullable', 'integer', 'exists:notification_lignes,id'] : ['nullable', 'integer'],
            'art' => $isAo ? ['nullable', 'string', 'max:255'] : self::requiredStringRule(255),
            'par' => $isAo ? ['nullable', 'string', 'max:255'] : self::requiredStringRule(255),
            'lig' => $isAo ? ['nullable', 'string', 'max:255'] : self::requiredStringRule(255),
            'code_imputation' => ['nullable', 'string', 'max:255'],
            'exercice_budgetaire' => $isAo
                ? ['nullable', 'integer', 'min:2000', 'max:2099']
                : ['required', 'integer', 'min:2026', 'max:2099'],
            'montant_estimatif_ht' => ['required', 'numeric', 'min:0'],
            'tva' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public static function updateRules(array $input, ?Consultation $consultation = null, bool $withDatabaseRules = true): array
    {
        $effectiveMode = $input['mode_engagement'] ?? $consultation?->mode_engagement ?? 'BC';
        $isAo = self::isAoMode($effectiveMode);
        $rawDateCons = $input['date_consultation'] ?? ($consultation?->date_consultation ? substr((string) $consultation->date_consultation, 0, 10) : null);

        $numeroConsultationRules = $isAo
            ? ['sometimes', 'required', 'string', 'max:255']
            : self::sometimesRequiredStringRule(255);
        $numeroBcRules = $isAo
            ? ['nullable', 'string', 'max:255']
            : self::sometimesRequiredStringRule(255);

        if ($withDatabaseRules) {
            $numeroConsultationRules[] = Rule::unique('consultations', 'numero_consultation')->ignore($consultation?->id);
            $numeroBcRules[] = Rule::unique('consultations', 'numero_bc')->ignore($consultation?->id);
        }

        $dateLimiteRules = $isAo
            ? ['nullable', 'date']
            : ['sometimes', 'required', 'date', 'after_or_equal:2026-01-01'];
        if (!$isAo && !empty($rawDateCons) && strtotime((string) $rawDateCons) !== false) {
            $dateLimiteRules[] = 'after_or_equal:' . substr((string) $rawDateCons, 0, 10);
        }

        $rules = [
            'numero_consultation' => $numeroConsultationRules,
            'numero_decision' => $isAo ? ['nullable', 'string', 'max:255'] : self::sometimesRequiredStringRule(255),
            'numero_bc' => $numeroBcRules,
            'annee' => $isAo ? ['sometimes', 'required', 'integer', 'min:2000', 'max:2099'] : ['sometimes', 'required', 'integer', 'min:2026', 'max:2099'],
            'objet_consultation' => self::sometimesRequiredStringRule(65000),
            'objet_consultation_ar' => ['nullable', 'string', 'max:65000'],
            'categorie' => self::sometimesRequiredStringRule(255),
            'intitule' => $isAo ? ['nullable', 'string', 'max:65000'] : self::sometimesRequiredStringRule(65000),
            'type_prestation' => $isAo ? ['nullable', 'string', 'max:65000'] : ['sometimes', 'required', 'string', 'max:65000', 'not_regex:/\A\s*\z/u'],
            'mode_engagement' => ['sometimes', 'required', Rule::in(['BC', 'Convention', 'Contrat', 'AO', 'Bon de commande', "Appel d'offres", "Appel d'offre"])],
            'type_budget' => ['sometimes', 'required', Rule::in(['Investissement', 'Fonctionnement'])],
            'statut_dossier' => self::sometimesRequiredStringRule(255),
            'date_consultation' => $isAo ? ['sometimes', 'required', 'date'] : ['sometimes', 'required', 'date', 'after_or_equal:2026-01-01'],
            'date_limite_devis' => $dateLimiteRules,
            'heure_limite_devis' => $isAo ? ['nullable', 'string', 'max:50'] : self::sometimesRequiredStringRule(50),
            'lieu_execution' => $isAo ? ['nullable', 'string', 'max:255'] : self::sometimesRequiredStringRule(255),
            'delai_execution' => $isAo ? ['nullable', 'integer', 'min:1'] : ['sometimes', 'required', 'integer', 'min:1'],
            'lieu_consultation' => ['nullable', 'string', 'max:255'],
            'lieu_reunion_ar' => ['nullable', 'string', 'max:255'],
            'cautionnement_provisoire' => ['nullable', 'numeric', 'min:0'],
            'budget_previsionnel' => ['nullable', 'numeric', 'min:0'],
            'notes_programmation' => ['nullable', 'string'],
            'fournisseur_id' => $withDatabaseRules ? ['nullable', 'integer', 'exists:fournisseurs,id'] : ['nullable', 'integer'],
            'reference_2' => ['nullable', 'string', 'max:255'],
            's_lig' => ['nullable', 'string', 'max:255'],
            'art' => $isAo ? ['nullable', 'string', 'max:255'] : self::sometimesRequiredStringRule(255),
            'par' => $isAo ? ['nullable', 'string', 'max:255'] : self::sometimesRequiredStringRule(255),
            'lig' => $isAo ? ['nullable', 'string', 'max:255'] : self::sometimesRequiredStringRule(255),
            'code_imputation' => ['nullable', 'string', 'max:255'],
            'exercice_budgetaire' => $isAo
                ? ['nullable', 'integer', 'min:2000', 'max:2099']
                : ['sometimes', 'required', 'integer', 'min:2026', 'max:2099'],
            'numero_engagement' => $withDatabaseRules
                ? ['nullable', 'string', 'max:255', Rule::unique('consultations', 'numero_engagement')->ignore($consultation?->id)]
                : ['nullable', 'string', 'max:255'],
            'credit_ouvert_cp' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'credit_ouvert_ce' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_anterieures_ce' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_anterieures_cp' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_credits_engagement' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_credits_consolides' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'depenses_rap' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'montant_depense_neuf' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'interets_moratoires' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'montant_engager_neuf' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];

        return array_merge($rules, self::engagementUpdateRules($input, $consultation, $withDatabaseRules));
    }

    public static function prestationsRules(): array
    {
        return [
            'prestations' => ['required', 'array', 'min:1'],
            'prestations.*.numero_prix' => ['nullable', 'string', 'max:50'],
            'prestations.*.designation' => self::requiredStringRule(255),
            'prestations.*.specification' => ['nullable', 'string'],
            'prestations.*.unite' => self::requiredStringRule(50),
            'prestations.*.quantite' => ['required', 'numeric', 'gt:0'],
            'prestations.*.prix_unitaire_ht' => ['required', 'numeric', 'min:0'],
            'prestations.*.tva' => ['required', 'numeric', 'min:0', 'max:100'],
            'prestations.*.garantie_exigee' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function commissionRules(): array
    {
        return [
            'date_reunion' => ['required', 'date'],
            'heure_reunion' => ['required', 'date_format:H:i'],
            'lieu_reunion' => self::requiredStringRule(255),
            'president_commission' => ['nullable', 'string', 'max:255'],
            'membres_commission' => ['required', 'array', 'min:1'],
            'observations_commission' => ['nullable', 'string'],
        ];
    }

    public static function validateCommissionPayload(array $input): \Illuminate\Validation\Validator
    {
        $validator = Validator::make($input, self::commissionRules());

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($input) {
            $membres = $input['membres_commission'] ?? [];
            $presidentInput = trim((string) ($input['president_commission'] ?? ''));

            if (!is_array($membres)) {
                return;
            }

            $validMembers = [];
            $hasPresidentInList = false;

            foreach ($membres as $item) {
                if (is_array($item)) {
                    $name = trim((string) ($item['nom'] ?? ($item['nom_prenom'] ?? '')));
                    $qualite = trim((string) ($item['qualite'] ?? ($item['fonction'] ?? '')));
                } elseif (is_string($item) || is_numeric($item)) {
                    $name = trim((string) $item);
                    $qualite = '';
                } else {
                    $name = '';
                    $qualite = '';
                }

                if ($name === '') {
                    $v->errors()->add('membres_commission', 'Chaque membre de la commission doit avoir un nom valide.');
                    return;
                }

                if ($qualite !== '' && preg_match('/pr[eé]sident/iu', $qualite) === 1) {
                    $hasPresidentInList = true;
                }

                $validMembers[] = mb_strtolower($name);
            }

            if (!$hasPresidentInList && $presidentInput === '') {
                $v->errors()->add('president_commission', 'Le président de la commission est obligatoire.');
            }

            $totalMembers = count($validMembers);
            if ($presidentInput !== '' && !in_array(mb_strtolower($presidentInput), $validMembers, true) && !$hasPresidentInList) {
                $totalMembers += 1;
            }

            if ($totalMembers < 3) {
                $v->errors()->add('membres_commission', 'Au moins 3 membres sont obligatoires pour la commission.');
            }
        });

        return $validator;
    }

    public static function receptionCommissionRules(array $input): array
    {
        $typeReception = strtolower(trim((string) ($input['type_reception'] ?? '')));
        $isDefinitive = $typeReception === 'définitive';
        $hasPvFields = !empty($input['is_pv_reception']) || array_key_exists('heure_fin', $input);
        $isProvisoireOuPartielle = in_array($typeReception, ['provisoire', 'partielle'], true);

        return [
            'is_pv_reception' => ['nullable', 'boolean'],
            'numero_bc' => self::requiredStringRule(255),
            'numero_decision' => self::requiredStringRule(255),
            'type_reception' => ['required', Rule::in(['définitive', 'provisoire', 'partielle'])],
            'date_reception_definitive' => $isDefinitive ? ['required', 'date'] : ['nullable', 'date'],
            'periode_du' => ($hasPvFields && $isProvisoireOuPartielle) ? ['required', 'date'] : ['nullable', 'date'],
            'periode_au' => ($hasPvFields && $isProvisoireOuPartielle)
                ? ['required', 'date', 'after_or_equal:periode_du']
                : ['nullable', 'date', 'after_or_equal:periode_du'],
            'prestations_receptionnees' => ($hasPvFields && $isProvisoireOuPartielle) ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'date_decision' => ['required', 'date'],
            'date_reunion' => ['required', 'date'],
            'heure_reunion' => self::requiredStringRule(50),
            'heure_fin' => $hasPvFields ? self::requiredStringRule(50) : ['nullable', 'string', 'max:50'],
            'lieu_reunion' => ['nullable', 'string', 'max:255'],
            'membres_commission' => ['required', 'array', 'min:3'],
        ];
    }

    public static function validateReceptionCommissionPayload(array $input): \Illuminate\Validation\Validator
    {
        $validator = Validator::make($input, self::receptionCommissionRules($input));

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($input) {
            $membres = $input['membres_commission'] ?? [];
            if (is_array($membres)) {
                foreach ($membres as $item) {
                    $name = is_array($item)
                        ? trim((string) ($item['nom'] ?? ($item['nom_prenom'] ?? '')))
                        : trim((string) $item);
                    if ($name === '') {
                        $v->errors()->add('membres_commission', 'Chaque membre de la commission de réception doit avoir un nom valide.');
                        break;
                    }
                }
            }

            $typeReception = strtolower(trim((string) ($input['type_reception'] ?? '')));
            $hasPvFields = !empty($input['is_pv_reception']) || array_key_exists('heure_fin', $input);
            if ($hasPvFields && in_array($typeReception, ['provisoire', 'partielle'], true)) {
                $prestations = $input['prestations_receptionnees'] ?? [];
                if (is_array($prestations)) {
                    $checkedCount = 0;
                    foreach ($prestations as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        if (!empty($item['receptionne'])) {
                            $checkedCount++;
                            $qte = $item['quantite_receptionnee'] ?? null;
                            if ($qte === null || $qte === '' || !is_numeric($qte) || (float) $qte < 0) {
                                $v->errors()->add('prestations_receptionnees', 'La quantité réceptionnée doit être un nombre valide (>= 0).');
                                break;
                            }
                        }
                    }
                    if ($checkedCount === 0) {
                        $v->errors()->add('prestations_receptionnees', 'Veuillez cocher au moins une prestation réceptionnée.');
                    }
                }
            }
        });

        return $validator;
    }

    public function index(Request $request)
    {
        $query = Consultation::with(['fournisseur', 'budget', 'notificationLigne', 'prestations', 'receptionCommission', 'registreEngagement']);

        if ($request->filled('mode_engagement')) {
            $mode = trim($request->query('mode_engagement'));
            if (in_array(strtoupper($mode), ['AO', 'AOO', 'MARCHE', 'MARCHÉ', 'APPEL', "APPEL D'OFFRES"], true)) {
                $query->whereIn('mode_engagement', ['AO', "Appel d'offres", "Appel d'Offres", "Appel d'offre"]);
            } elseif (in_array(strtoupper($mode), ['CONV', 'CONVENTION', 'CONTRAT'], true)) {
                $query->where(function ($q) {
                    $q->whereIn('mode_engagement', ['Convention', 'Contrat'])
                      ->orWhere('numero_consultation', 'like', 'CONV%')
                      ->orWhere('numero_bc', 'like', 'CONV%');
                });
            } elseif (in_array(strtoupper($mode), ['BC', 'BON', 'BON_DE_COMMANDE', 'BON DE COMMANDE'], true)) {
                $query->where(function ($q) {
                    $q->whereIn('mode_engagement', ['BC', 'Bon de commande'])
                      ->where('numero_consultation', 'not like', 'CONV%');
                });
            } else {
                $query->where('mode_engagement', $mode);
            }
        }

        return response()->json($query->get());
    }

    public function findByBonCommande(string $numeroBc)
    {
        $consultation = Consultation::with(['fournisseur', 'budget', 'notificationLigne', 'prestations', 'engagement', 'registreEngagement', 'receptionCommission'])
            ->where('numero_bc', $numeroBc)
            ->orWhere('numero_consultation', $numeroBc)
            ->first();

        if (!$consultation) {
            return response()->json(['message' => 'Aucun bon de commande ne correspond à ce numéro.'], 404);
        }

        $ligne = $consultation->notificationLigne;
        if (in_array(strtoupper((string) $consultation->type_budget), ['INVESTISSEMENT', 'FONCTIONNEMENT'], true)) {
            $consultation->refreshCreditsOuvertsDepuisNotifications();
        } elseif ($ligne) {
            $consultation->credit_ouvert_cp = max(0, (float) ($ligne->credits_neufs ?? 0) - (float) ($ligne->diminution_credit_neuf ?? 0));
            $consultation->credit_ouvert_ce = max(0, (float) ($ligne->credits_engagements ?? 0) - (float) ($ligne->diminution_credit_engagement ?? 0));
        }

        return response()->json($consultation);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::storeRules($request->all(), true));

        try {
            $consultation = DB::transaction(function () use ($validated, $request) {
                $isAo = self::isAoMode($validated['mode_engagement'] ?? null);
                $annee = $validated['annee'];
                $numero_consultation = $isAo
                    ? (!empty($validated['numero_consultation'])
                        ? trim($validated['numero_consultation'])
                        : 'CONS-' . $annee . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT))
                    : trim($validated['numero_consultation']);

                $notifLigneId = $validated['notification_ligne_id'] ?? null;
                $artInput = trim((string) ($validated['art'] ?? $request->art ?? ''));
                $parInput = trim((string) ($validated['par'] ?? $request->par ?? ''));
                $ligInput = trim((string) ($validated['lig'] ?? $request->lig ?? ''));
                if (!$notifLigneId && $artInput !== '' && $parInput !== '' && $ligInput !== '') {
                    $matchedLigne = \App\Models\NotificationLigne::where('article', $artInput)
                        ->where('paragraphe', $parInput)
                        ->where('ligne_budgetaire', $ligInput)
                        ->first();
                    if ($matchedLigne) {
                        $notifLigneId = $matchedLigne->id;
                    }
                }

                $intitule = $validated['intitule'] ?? ($validated['type_prestation'] ?? null);
                $typePrestation = $validated['type_prestation'] ?? $intitule ?? $validated['objet_consultation'];

                // Création de la Consultation
                $consultation = Consultation::create([
                    'numero_consultation' => $numero_consultation,
                    'numero_decision' => isset($validated['numero_decision']) ? trim((string) $validated['numero_decision']) : null,
                    'annee' => $validated['annee'],
                    'date_consultation' => $validated['date_consultation'],
                    'objet_consultation' => trim($validated['objet_consultation']),
                    'description_detaillee' => $validated['description_detaillee'] ?? null,
                    'categorie' => trim($validated['categorie']),
                    'type_prestation' => trim((string) $typePrestation),
                    'intitule' => $intitule !== null ? trim((string) $intitule) : null,
                    'mode_engagement' => $validated['mode_engagement'],
                    'type_budget' => $validated['type_budget'],
                    'delai_execution' => $validated['delai_execution'],
                    'statut_dossier' => trim($validated['statut_dossier']),
                    'fournisseur_id' => $validated['fournisseur_id'] ?? null,
                    'numero_bc' => isset($validated['numero_bc']) ? trim((string) $validated['numero_bc']) : null,
                    'notification_ligne_id' => $notifLigneId,
                    'date_limite_devis' => $isAo
                        ? ($validated['date_limite_devis'] ?? date('Y-m-d', strtotime($validated['date_consultation'] . ' +2 days')))
                        : $validated['date_limite_devis'],
                    'heure_limite_devis' => $isAo
                        ? ($validated['heure_limite_devis'] ?? '10:00')
                        : trim((string) $validated['heure_limite_devis']),
                    'lieu_execution' => $isAo
                        ? ($validated['lieu_execution'] ?? 'REGION DE RABAT SALE KENITRA')
                        : trim((string) $validated['lieu_execution']),
                ]);

                // Récupération de la ligne budgétaire pour auto-remplir le budget
                $ligne = $notifLigneId ? \App\Models\NotificationLigne::with('notification')->find($notifLigneId) : null;
                $artVal = $ligne ? $ligne->article : $artInput;
                $parVal = $ligne ? $ligne->paragraphe : $parInput;
                $ligVal = $ligne ? $ligne->ligne_budgetaire : $ligInput;
                $codeImputation = $ligne
                    ? "{$ligne->article}/{$ligne->paragraphe}/{$ligne->ligne_budgetaire}"
                    : ($validated['code_imputation'] ?? ($artVal . $parVal . $ligVal));

                // Création du Budget associé
                Budget::create([
                    'consultation_id' => $consultation->id,
                    'art' => $artVal,
                    'par' => $parVal,
                    'lig' => $ligVal,
                    'code_imputation' => $codeImputation,
                    'exercice_budgetaire' => $ligne?->notification?->exercice ?? ($validated['exercice_budgetaire'] ?? $validated['annee']),
                    'montant_estimatif_ht' => $validated['montant_estimatif_ht'],
                    'tva' => $validated['tva'],
                ]);

                return $consultation->load(['budget', 'notificationLigne', 'prestations', 'fournisseur', 'receptionCommission', 'registreEngagement']);
            });

            return response()->json($consultation, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création de la consultation.', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Consultation $consultation)
    {
        $consultation->load(['fournisseur', 'budget', 'notificationLigne', 'prestations', 'engagement', 'registreEngagement', 'offres', 'receptionCommission']);

        $ligne = $consultation->notificationLigne;
        if (in_array(strtoupper((string) $consultation->type_budget), ['INVESTISSEMENT', 'FONCTIONNEMENT'], true)) {
            $consultation->refreshCreditsOuvertsDepuisNotifications();
        } elseif ($ligne) {
            $consultation->credit_ouvert_cp = max(0, (float) ($ligne->credits_neufs ?? 0) - (float) ($ligne->diminution_credit_neuf ?? 0));
            $consultation->credit_ouvert_ce = max(0, (float) ($ligne->credits_engagements ?? 0) - (float) ($ligne->diminution_credit_engagement ?? 0));
        }

        return response()->json($consultation);
    }

    public function destroy(Consultation $consultation)
    {
        try {
            DB::transaction(function () use ($consultation) {
                $consultation->budget()?->delete();
                $consultation->prestations()?->delete();
                $consultation->offres()?->delete();
                $consultation->engagement()?->delete();
                $consultation->suiviExecution()?->delete();
                $consultation->receptions()?->delete();
                $consultation->receptionCommission()?->delete();
                $consultation->liquidation()?->delete();

                $consultation->delete();
            });

            return response()->json([
                'message' => 'Consultation supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de la consultation.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Consultation $consultation)
    {
        $validated = $request->validate(self::updateRules($request->all(), $consultation, true));

        $consultation->update($validated);

        if ($request->hasAny(['art', 'par', 'lig', 'code_imputation', 'exercice_budgetaire'])) {
            $budgetData = array_filter([
                'art' => $validated['art'] ?? $request->art,
                'par' => $validated['par'] ?? $request->par,
                'lig' => $validated['lig'] ?? $request->lig,
                'code_imputation' => $validated['code_imputation'] ?? $request->code_imputation,
                'exercice_budgetaire' => $validated['exercice_budgetaire'] ?? $request->exercice_budgetaire,
            ], fn ($v) => $v !== null);

            if (!isset($budgetData['code_imputation']) && (isset($budgetData['art']) || isset($budgetData['par']) || isset($budgetData['lig']))) {
                $art = $budgetData['art'] ?? ($consultation->budget?->art ?? '');
                $par = $budgetData['par'] ?? ($consultation->budget?->par ?? '');
                $lig = $budgetData['lig'] ?? ($consultation->budget?->lig ?? '');
                $budgetData['code_imputation'] = "{$art}{$par}{$lig}";
            }

            if ($consultation->budget) {
                $consultation->budget->update($budgetData);
            } else {
                $consultation->budget()->create(array_merge([
                    'art' => $budgetData['art'] ?? '',
                    'par' => $budgetData['par'] ?? '',
                    'lig' => $budgetData['lig'] ?? '',
                    'code_imputation' => $budgetData['code_imputation'] ?? '',
                    'exercice_budgetaire' => $budgetData['exercice_budgetaire'] ?? ($consultation->annee ?: date('Y')),
                    'montant_estimatif_ht' => 0,
                    'tva' => 20,
                ], $budgetData));
            }
        }

        if (!empty($validated['numero_engagement'])) {
            $consultation->load(['budget', 'fournisseur', 'engagement', 'notificationLigne']);
            $registre = $consultation->registreEngagement;
            $notificationLigne = $consultation->notificationLigne;
            $creditConsolide = $notificationLigne
                ? (float) ($notificationLigne->reports ?? 0) + (float) ($notificationLigne->credits_neufs ?? 0)
                : null;

            $isConvention = in_array($consultation->mode_engagement, ['Convention', 'Contrat'])
                || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV');
            $typeBudget = $request->type_budget ?: ($consultation->type_budget ?: 'Investissement');
            $isContrat = $isConvention && strtolower((string) $typeBudget) === 'fonctionnement';

            $beneficiaire = $consultation->fournisseur?->raison_sociale
                ?: ($request->beneficiaire
                ?: ($request->societe
                ?: ($request->titulaire_nom
                ?: ($consultation->engagement?->fournisseur?->raison_sociale ?? ''))));

            $ref = $consultation->numero_bc ?: $consultation->numero_consultation;
            if ($isConvention && $ref) {
                $ref = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string) $ref));
                $ref = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $ref));
            }

            $consultation->registreEngagement()->updateOrCreate(
                ['consultation_id' => $consultation->id],
                [
                    'numero_ordre' => $registre?->numero_ordre
                        ?? ((int) \App\Models\RegistreEngagement::max('numero_ordre') + 1),
                    'engagement_id' => $consultation->engagement?->id,
                    'date_engagement' => $request->date_consultation,
                    'numero_rubrique' => $consultation->numero_engagement,
                    'mode_engagement' => $isConvention ? ($isContrat ? 'Contrat' : 'Convention') : ($consultation->mode_engagement ?: 'Bon de commande'),
                    'reference' => $ref,
                    'reference_2' => $consultation->reference_2,
                    'budget' => $typeBudget,
                    'code' => $consultation->budget?->code_imputation,
                    'art' => $consultation->budget?->art,
                    'par' => $consultation->budget?->par,
                    'lig' => $consultation->budget?->lig,
                    's_lig' => $consultation->s_lig,
                    'intitule' => $consultation->intitule,
                    'credit_ouvert_cp' => $consultation->credit_ouvert_cp,
                    'credit_ouvert_ce' => $isContrat ? null : $consultation->credit_ouvert_ce,
                    'credit_consolide' => $creditConsolide,
                    'depenses_anterieures_ce' => $isContrat ? null : $consultation->depenses_anterieures_ce,
                    'depenses_anterieures_cp' => $consultation->depenses_anterieures_cp,
                    'depenses_credits_engagement' => $isContrat ? null : $consultation->depenses_credits_engagement,
                    'depenses_credits_consolides' => $consultation->depenses_credits_consolides,
                    'depenses_rap' => $consultation->depenses_rap,
                    'montant_depense_neuf' => $consultation->montant_depense_neuf,
                    'interets_moratoires' => $consultation->interets_moratoires,
                    'montant_engager_neuf' => $consultation->montant_engager_neuf,
                    'objet' => $consultation->objet_consultation,
                    'beneficiaire' => $beneficiaire,
                ]
            );
        }

        return response()->json($consultation->load(['fournisseur', 'budget', 'notificationLigne', 'prestations', 'engagement', 'registreEngagement', 'offres', 'receptionCommission']));
    }

    public function syncPrestations(Request $request, Consultation $consultation)
    {
        $validated = $request->validate(self::prestationsRules());

        try {
            $prestations = array_map(static function (array $prestation): array {
                return [
                    'numero_prix' => isset($prestation['numero_prix']) ? trim((string) $prestation['numero_prix']) : null,
                    'designation' => trim((string) $prestation['designation']),
                    'specification' => isset($prestation['specification']) ? trim((string) $prestation['specification']) : null,
                    'unite' => trim((string) $prestation['unite']),
                    'quantite' => (float) $prestation['quantite'],
                    'prix_unitaire_ht' => (float) $prestation['prix_unitaire_ht'],
                    'tva' => (float) $prestation['tva'],
                    'garantie_exigee' => isset($prestation['garantie_exigee']) ? trim((string) $prestation['garantie_exigee']) : null,
                ];
            }, $validated['prestations']);

            // Keep the delete and inserts as separate statements so PostgreSQL
            // exposes the original constraint error instead of only 25P02.
            try {
                $consultation->prestations()->delete();
            } catch (\Throwable $e) {
                Log::error('Échec de la suppression des prestations', [
                    'consultation_id' => $consultation->id,
                    'exception' => $this->exceptionDetails($e),
                ]);
                throw $e;
            }

            foreach ($prestations as $prestationData) {
                try {
                    $consultation->prestations()->create($prestationData);
                } catch (\Throwable $e) {
                    Log::error('Échec de la création d’une prestation', [
                        'consultation_id' => $consultation->id,
                        'data' => $prestationData,
                        'exception' => $this->exceptionDetails($e),
                    ]);
                    throw $e;
                }
            }

            return response()->json([
                'message' => 'Prestations enregistrées avec succès.',
                'consultation' => $consultation->load('prestations')
            ]);
        } catch (\Throwable $e) {
            Log::error('Échec de l’enregistrement des prestations', [
                'consultation_id' => $consultation->id,
                'prestations_count' => count($validated['prestations']),
                'exception' => $this->exceptionDetails($e),
            ]);

            return response()->json([
                'error' => 'Erreur lors de l\'enregistrement des prestations.',
                'message' => app()->hasDebugModeEnabled()
                    ? $this->exceptionDetails($e)['root_cause']
                    : 'Une erreur base de données est survenue.',
            ], 500);
        }
    }

    private function exceptionDetails(\Throwable $exception): array
    {
        $previous = $exception->getPrevious();
        $previousDetails = $previous ? $this->exceptionDetails($previous) : null;

        return [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'previous' => $previousDetails,
            'root_cause' => $previousDetails['root_cause'] ?? $exception->getMessage(),
        ];
    }

    public function generatePdf(Consultation $consultation)
    {
        $consultation->load(['prestations']);

        $totalHT = $consultation->prestations->sum('montant_ht');
        
        $totalTVA = $consultation->prestations->reduce(function ($carry, $prestation) {
            return $carry + ($prestation->montant_ht * ($prestation->tva / 100));
        }, 0);

        $totalTTC = $totalHT + $totalTVA;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.dossier_estimation', [
            'consultation' => $consultation,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
        ]);

        return $pdf->download("Dossier_Estimation_{$consultation->numero_consultation}.pdf");
    }

    // --- MODULE 04: CONSULTATION DES FOURNISSEURS ---

    public function selectFournisseurs(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'fournisseur_ids' => 'required|array',
            'fournisseur_ids.*' => 'exists:fournisseurs,id',
            'date_envoi' => 'nullable|date',
        ]);

        foreach ($validated['fournisseur_ids'] as $fournisseurId) {
            // Utilisation de firstOrCreate pour éviter les doublons si on relance
            $consultation->offres()->firstOrCreate(
                ['fournisseur_id' => $fournisseurId],
                ['date_envoi' => $validated['date_envoi'] ?? now(), 'statut_reponse' => 'En attente']
            );
        }

        return response()->json([
            'message' => 'Fournisseurs associés avec succès.',
            'offres' => $consultation->offres()->with('fournisseur')->get()
        ]);
    }

    public function updateDevis(Request $request, Consultation $consultation, $fournisseurId)
    {
        $validated = $request->validate([
            'montant_propose' => 'nullable|numeric|min:0',
            'statut_reponse' => 'required|in:En attente,Reçu,Hors délai,Refusé',
        ]);

        $offre = $consultation->offres()->where('fournisseur_id', $fournisseurId)->firstOrFail();
        
        $offre->update([
            'montant_propose' => $validated['montant_propose'] ?? $offre->montant_propose,
            'statut_reponse' => $validated['statut_reponse'],
        ]);

        return response()->json([
            'message' => 'Devis mis à jour.',
            'offre' => $offre->load('fournisseur')
        ]);
    }

    public function getComparatif(Consultation $consultation)
    {
        $offres = $consultation->offres()->with('fournisseur')->get();
        return response()->json($offres);
    }

    // --- MODULE 05: COMMISSION ET OUVERTURE DES PLIS ---

    public function saveCommission(Request $request, Consultation $consultation)
    {
        $validator = self::validateCommissionPayload($request->all());
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        if (empty($validated['president_commission']) && is_array($validated['membres_commission'] ?? null)) {
            foreach ($validated['membres_commission'] as $item) {
                if (is_array($item) && preg_match('/pr[eé]sident/iu', (string) ($item['qualite'] ?? '')) === 1) {
                    $validated['president_commission'] = trim((string) ($item['nom'] ?? ($item['nom_prenom'] ?? '')));
                    break;
                }
            }
        }

        $consultation->update($validated);

        return response()->json([
            'message' => 'Informations de la commission enregistrées.',
            'consultation' => $consultation
        ]);
    }

    public function saveOuverturePlis(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'offres' => 'required|array',
            'offres.*.id' => [
                'required',
                Rule::exists('offres', 'id')->where(
                    fn ($query) => $query->where('consultation_id', $consultation->id)
                ),
            ],
            'offres.*.montant_apres_verification' => 'nullable|numeric|min:0',
            'offres.*.retenu' => 'required|boolean',
            'offres.*.observations_offre' => 'nullable|string',
        ]);

        $retenues = collect($validated['offres'])->where('retenu', true);
        if ($retenues->count() > 1) {
            throw ValidationException::withMessages([
                'offres' => ['Une seule offre peut être retenue pour une consultation.'],
            ]);
        }

        foreach ($validated['offres'] as $offreData) {
            $offre = $consultation->offres()->where('id', $offreData['id'])->first();
            if ($offre) {
                $statut = $offre->statut_reponse;
                if ($offreData['retenu'] && in_array($statut, ['Hors délai', 'Refusé'], true)) {
                    throw ValidationException::withMessages([
                        'offres' => ['Une offre hors délai ou refusée ne peut pas être retenue.'],
                    ]);
                }

                if ($offreData['retenu']
                    && ($offreData['montant_apres_verification'] ?? $offre->montant_apres_verification) === null
                ) {
                    throw ValidationException::withMessages([
                        'offres' => ['Le montant de l’offre retenue est obligatoire.'],
                    ]);
                }

                $offre->update([
                    'montant_apres_verification' => $offreData['montant_apres_verification'] ?? $offre->montant_apres_verification,
                    'retenu' => $offreData['retenu'],
                    'observations_offre' => $offreData['observations_offre'] ?? $offre->observations_offre,
                ]);
            }
        }

        return response()->json([
            'message' => 'Résultats de l\'ouverture des plis enregistrés.',
            'offres' => $consultation->offres()->with('fournisseur')->get()
        ]);
    }

    public function saveReceptionCommission(Request $request, Consultation $consultation)
    {
        $validator = self::validateReceptionCommissionPayload($request->all());
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $commissionData = \Illuminate\Support\Arr::except($validated, ['is_pv_reception']);

        $commission = $consultation->receptionCommission()->updateOrCreate(
            ['consultation_id' => $consultation->id],
            $commissionData
        );

        // Auto-synchronize / create LiquidationFinanciere for this Bon de Commande
        $consultation->load(['prestations', 'engagement', 'registreEngagement', 'liquidation']);
        $totalPrestationsTTC = 0;
        if ($consultation->prestations && $consultation->prestations->count() > 0) {
            $totalPrestationsTTC = (float) $consultation->prestations->reduce(function ($sum, $p) {
                $ht = (float) ($p->montant_ht ?: (($p->quantite ?: 1) * ($p->prix_unitaire_ht ?: 0)));
                $tvaRate = (float) ($p->tva ?: 20);
                return $sum + ($ht * (1 + ($tvaRate / 100)));
            }, 0);
        }

        $montantEngagement = (float) (
            $consultation->montant_engager_neuf ?:
            ($consultation->montant_depense_neuf ?:
            ($consultation->engagement?->montant_engagement ?:
            ($consultation->registreEngagement?->montant_engager_neuf ?:
            ($consultation->registreEngagement?->montant_engage ?: $totalPrestationsTTC))))
        );

        $montantLiq = $montantEngagement > 0 ? $montantEngagement : $totalPrestationsTTC;
        if ($montantLiq > 0) {
            $consultation->liquidation()->updateOrCreate(
                ['consultation_id' => $consultation->id],
                [
                    'montant_a_payer' => $montantLiq,
                    'reference_facture' => $validated['numero_bc'] ?? ($consultation->numero_bc ?? $consultation->numero_consultation),
                    'date_facture' => $validated['date_reception_definitive'] ?? ($validated['date_decision'] ?? now()->format('Y-m-d')),
                ]
            );
        }

        if (!in_array(strtoupper((string) $consultation->statut_dossier), ['PAYÉ', 'PAYE', 'CLÔTURÉ', 'CLOTURE'])) {
            $consultation->update(['statut_dossier' => 'LIQUIDÉ']);
        }

        return response()->json([
            'message' => 'Commission de réception et liquidation enregistrées avec succès.',
            'reception_commission' => $commission,
            'consultation' => $consultation->load(['receptionCommission', 'budget', 'prestations', 'fournisseur', 'offres', 'liquidation'])
        ]);
    }
}
