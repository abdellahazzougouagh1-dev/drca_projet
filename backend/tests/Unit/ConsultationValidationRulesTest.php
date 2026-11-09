<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\CommissionMembreController;
use App\Http\Controllers\Api\ConsultationController;
use App\Models\Consultation;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConsultationValidationRulesTest extends TestCase
{
    private function validConventionPayload(array $overrides = []): array
    {
        return array_merge([
            'numero_consultation' => 'CONV-2026-001',
            'numero_decision' => 'DEC-2026-001',
            'numero_bc' => 'CONV-2026-001',
            'annee' => 2026,
            'date_consultation' => '2026-03-01',
            'date_limite_devis' => '2026-03-15',
            'heure_limite_devis' => '10:00',
            'lieu_execution' => 'Kénitra',
            'objet_consultation' => 'Convention de partenariat agricole',
            'categorie' => 'Services',
            'intitule' => 'Appui technique aux agriculteurs',
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'delai_execution' => 30,
            'statut_dossier' => 'Programmation',
            'art' => '415',
            'par' => '30',
            'lig' => '60',
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 10000,
            'tva' => 20,
        ], $overrides);
    }

    public function test_store_rules_reject_empty_and_whitespace_fields_for_convention_contrat_and_bc(): void
    {
        foreach (['Convention', 'Contrat', 'BC'] as $mode) {
            $emptyValidator = Validator::make(
                ['mode_engagement' => $mode],
                ConsultationController::storeRules(['mode_engagement' => $mode], false)
            );

            $this->assertTrue($emptyValidator->fails());
            $this->assertArrayHasKey('numero_consultation', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('numero_decision', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('numero_bc', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('date_limite_devis', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('heure_limite_devis', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('lieu_execution', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('objet_consultation', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('categorie', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('intitule', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('art', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('par', $emptyValidator->errors()->toArray());
            $this->assertArrayHasKey('lig', $emptyValidator->errors()->toArray());

            $whitespacePayload = $this->validConventionPayload([
                'mode_engagement' => $mode,
                'type_budget' => $mode === 'Contrat' ? 'Fonctionnement' : 'Investissement',
                'numero_consultation' => '   ',
                'numero_decision' => "\t\n",
                'numero_bc' => '   ',
                'objet_consultation' => '   ',
                'intitule' => '   ',
                'lieu_execution' => '   ',
                'art' => ' ',
                'par' => ' ',
                'lig' => ' ',
            ]);
            $wsValidator = Validator::make(
                $whitespacePayload,
                ConsultationController::storeRules($whitespacePayload, false)
            );
            $this->assertTrue($wsValidator->fails());
            $this->assertArrayHasKey('numero_consultation', $wsValidator->errors()->toArray());
            $this->assertArrayHasKey('numero_decision', $wsValidator->errors()->toArray());
            $this->assertArrayHasKey('numero_bc', $wsValidator->errors()->toArray());
            $this->assertArrayHasKey('objet_consultation', $wsValidator->errors()->toArray());
            $this->assertArrayHasKey('intitule', $wsValidator->errors()->toArray());
            $this->assertArrayHasKey('art', $wsValidator->errors()->toArray());
        }
    }

    public function test_store_rules_reject_pre_2026_dates_and_invalid_deadline_or_delay_for_convention(): void
    {
        $payload = $this->validConventionPayload([
            'annee' => 2025,
            'exercice_budgetaire' => 2025,
            'date_consultation' => '2025-12-31',
            'date_limite_devis' => '2025-12-01',
            'delai_execution' => 0,
        ]);

        $validator = Validator::make($payload, ConsultationController::storeRules($payload, false));

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('annee', $errors);
        $this->assertArrayHasKey('exercice_budgetaire', $errors);
        $this->assertArrayHasKey('date_consultation', $errors);
        $this->assertArrayHasKey('date_limite_devis', $errors);
        $this->assertArrayHasKey('delai_execution', $errors);
    }

    public function test_store_rules_accept_valid_convention_contrat_and_ao_payloads(): void
    {
        $convPayload = $this->validConventionPayload();
        $this->assertTrue(
            Validator::make($convPayload, ConsultationController::storeRules($convPayload, false))->passes()
        );

        $contratPayload = $this->validConventionPayload([
            'numero_consultation' => 'CONV-2026-002',
            'numero_bc' => 'CONV-2026-002',
            'mode_engagement' => 'Contrat',
            'type_budget' => 'Fonctionnement',
        ]);
        $this->assertTrue(
            Validator::make($contratPayload, ConsultationController::storeRules($contratPayload, false))->passes()
        );

        $aoPayload = [
            'annee' => 2024,
            'date_consultation' => '2024-06-15',
            'objet_consultation' => 'Appel d offres travaux',
            'categorie' => 'Travaux',
            'type_prestation' => 'Amenagement agricole',
            'mode_engagement' => 'AO',
            'type_budget' => 'Investissement',
            'delai_execution' => 60,
            'statut_dossier' => 'Programmation',
            'montant_estimatif_ht' => 50000,
            'tva' => 20,
        ];
        $this->assertTrue(
            Validator::make($aoPayload, ConsultationController::storeRules($aoPayload, false))->passes()
        );
    }

    public function test_engagement_update_rules_distinguish_investissement_and_fonctionnement(): void
    {
        $invConsultation = new Consultation([
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
        ]);

        $missingCePayload = [
            'numero_engagement' => 'ENG-001/2026',
            'credit_ouvert_cp' => 100000,
            'depenses_anterieures_cp' => 1000,
            'montant_depense_neuf' => 0,
        ];
        $invValidator = Validator::make(
            $missingCePayload,
            ConsultationController::updateRules($missingCePayload, $invConsultation, false)
        );

        $this->assertTrue($invValidator->fails());
        $invErrors = $invValidator->errors()->toArray();
        $this->assertArrayHasKey('credit_ouvert_ce', $invErrors);
        $this->assertArrayHasKey('depenses_anterieures_ce', $invErrors);
        $this->assertArrayHasKey('depenses_credits_engagement', $invErrors);
        $this->assertArrayHasKey('montant_depense_neuf', $invErrors);

        $contratConsultation = new Consultation([
            'mode_engagement' => 'Contrat',
            'type_budget' => 'Fonctionnement',
        ]);
        $validFonctionnementPayload = [
            'numero_engagement' => 'ENG-002/2026',
            'credit_ouvert_cp' => 100000,
            'depenses_anterieures_cp' => 2500,
            'credit_ouvert_ce' => null,
            'depenses_anterieures_ce' => null,
            'depenses_credits_engagement' => null,
            'montant_depense_neuf' => 12000,
        ];
        $contratValidator = Validator::make(
            $validFonctionnementPayload,
            ConsultationController::updateRules($validFonctionnementPayload, $contratConsultation, false)
        );
        $this->assertTrue($contratValidator->passes());
    }

    public function test_prestations_rules_validate_lines(): void
    {
        $invalid = Validator::make([
            'prestations' => [
                ['designation' => '   ', 'unite' => '', 'quantite' => 0, 'prix_unitaire_ht' => -5, 'tva' => 120],
            ],
        ], ConsultationController::prestationsRules());

        $this->assertTrue($invalid->fails());
        $errors = $invalid->errors()->toArray();
        $this->assertArrayHasKey('prestations.0.designation', $errors);
        $this->assertArrayHasKey('prestations.0.unite', $errors);
        $this->assertArrayHasKey('prestations.0.quantite', $errors);
        $this->assertArrayHasKey('prestations.0.prix_unitaire_ht', $errors);
        $this->assertArrayHasKey('prestations.0.tva', $errors);

        $valid = Validator::make([
            'prestations' => [
                ['numero_prix' => '1', 'designation' => 'Papier A4', 'unite' => 'Ramette', 'quantite' => 5, 'prix_unitaire_ht' => 45, 'tva' => 20],
            ],
        ], ConsultationController::prestationsRules());
        $this->assertTrue($valid->passes());
    }

    public function test_commission_payload_validation_requires_president_and_three_members(): void
    {
        $tooFew = ConsultationController::validateCommissionPayload([
            'date_reunion' => '2026-04-01',
            'heure_reunion' => '10:00',
            'lieu_reunion' => 'Siège DRCA-RSK',
            'membres_commission' => [
                ['nom' => 'Membre 1', 'qualite' => 'Président'],
                ['nom' => 'Membre 2', 'qualite' => 'Membre'],
            ],
        ]);
        $this->assertTrue($tooFew->fails());
        $this->assertArrayHasKey('membres_commission', $tooFew->errors()->toArray());

        $noPresident = ConsultationController::validateCommissionPayload([
            'date_reunion' => '2026-04-01',
            'heure_reunion' => '10:00',
            'lieu_reunion' => 'Siège DRCA-RSK',
            'membres_commission' => [
                ['nom' => 'Membre 1', 'qualite' => 'Membre'],
                ['nom' => 'Membre 2', 'qualite' => 'Membre'],
                ['nom' => 'Membre 3', 'qualite' => 'Rapporteur'],
            ],
        ]);
        $this->assertTrue($noPresident->fails());
        $this->assertArrayHasKey('president_commission', $noPresident->errors()->toArray());

        $valid = ConsultationController::validateCommissionPayload([
            'date_reunion' => '2026-04-01',
            'heure_reunion' => '10:00',
            'lieu_reunion' => 'Siège DRCA-RSK',
            'membres_commission' => [
                ['nom' => 'Ahmed Alami', 'qualite' => 'Président'],
                ['nom' => 'Fatima Bennani', 'qualite' => 'Membre'],
                ['nom' => 'Karim Idrissi', 'qualite' => 'Rapporteur'],
            ],
        ]);
        $this->assertTrue($valid->passes());
    }

    public function test_reception_commission_payload_validation_handles_decision_vs_pv(): void
    {
        // Décision de commission de réception (provisoire) without PV fields succeeds
        $decisionProvisoire = ConsultationController::validateReceptionCommissionPayload([
            'is_pv_reception' => false,
            'numero_bc' => 'CONV-2026-001',
            'numero_decision' => 'DEC-REC-2026-001',
            'type_reception' => 'provisoire',
            'date_decision' => '2026-05-01',
            'date_reunion' => '2026-05-05',
            'heure_reunion' => '10:00',
            'membres_commission' => [
                ['nom' => 'M1', 'qualite' => 'Président'],
                ['nom' => 'M2', 'qualite' => 'Membre'],
                ['nom' => 'M3', 'qualite' => 'Membre'],
            ],
        ]);
        $this->assertTrue($decisionProvisoire->passes());

        // PV de réception (partielle) requires heure_fin, periode_du, periode_au >= periode_du, and checked prestations
        $invalidPv = ConsultationController::validateReceptionCommissionPayload([
            'is_pv_reception' => true,
            'numero_bc' => 'CONV-2026-001',
            'numero_decision' => 'DEC-REC-2026-001',
            'type_reception' => 'partielle',
            'date_decision' => '2026-05-01',
            'date_reunion' => '2026-05-05',
            'heure_reunion' => '10:00',
            'heure_fin' => '',
            'periode_du' => '2026-05-10',
            'periode_au' => '2026-05-02',
            'prestations_receptionnees' => [
                ['receptionne' => false, 'quantite_receptionnee' => 0],
            ],
            'membres_commission' => [
                ['nom' => 'M1', 'qualite' => 'Président'],
                ['nom' => 'M2', 'qualite' => 'Membre'],
                ['nom' => 'M3', 'qualite' => 'Membre'],
            ],
        ]);
        $this->assertTrue($invalidPv->fails());
        $pvErrors = $invalidPv->errors()->toArray();
        $this->assertArrayHasKey('heure_fin', $pvErrors);
        $this->assertArrayHasKey('periode_au', $pvErrors);
        $this->assertArrayHasKey('prestations_receptionnees', $pvErrors);
    }

    public function test_commission_membre_rules_reject_whitespace(): void
    {
        $invalid = Validator::make([
            'nom_prenom' => '   ',
            'fonction' => '',
        ], CommissionMembreController::storeRules());

        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('nom_prenom', $invalid->errors()->toArray());
        $this->assertArrayHasKey('fonction', $invalid->errors()->toArray());

        $valid = Validator::make([
            'nom_prenom' => 'Hassan Mansouri',
            'fonction' => 'Chef SAF',
        ], CommissionMembreController::storeRules());
        $this->assertTrue($valid->passes());
    }
}
