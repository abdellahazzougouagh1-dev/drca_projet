<?php

namespace Tests\Feature\Api;

use App\Models\Consultation;
use App\Models\Fournisseur;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\Ordonnancement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetHierarchyValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsDirector(): User
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'role' => 'directeur',
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function seedNotificationLine(float $creditsNeufs = 100000.00, float $reports = 0.0): array
    {
        $notif = Notification::create([
            'numero' => 'NOTIF-2026-' . uniqid(),
            'type_budget' => $reports > 0 ? 'REPORT + NOTIFIER' : 'NOTIFIER',
            'domaine' => 'INVESTISSEMENT',
            'exercice' => 2026,
            'date_notification' => '2026-01-15',
            'montant' => $creditsNeufs + $reports,
            'objet' => 'Dotation Test 2026',
        ]);

        $ligne = NotificationLigne::create([
            'notification_id' => $notif->id,
            'article' => '415',
            'paragraphe' => '10',
            'ligne_budgetaire' => '13',
            'libelle' => 'Travaux test',
            'domaine' => 'INVESTISSEMENT',
            'reports' => $reports,
            'diminution_report' => 0,
            'credits_neufs' => $creditsNeufs,
            'diminution_credit_neuf' => 0,
            'credits_engagements' => 0,
            'diminution_credit_engagement' => 0,
            'total_credits' => $creditsNeufs + $reports,
        ]);

        return [$notif, $ligne];
    }

    private function engagementPayload(int $fournisseurId, int $ligneId, string $numeroEng, float $montant): array
    {
        return [
            'numero_engagement' => $numeroEng,
            'credit_ouvert_cp' => 200000,
            'credit_ouvert_ce' => 0,
            'depenses_anterieures_cp' => 0,
            'depenses_anterieures_ce' => 0,
            'depenses_credits_engagement' => 0,
            'montant_depense_neuf' => $montant,
            'montant_engager_neuf' => $montant,
            'fournisseur_id' => $fournisseurId,
            'art' => '415',
            'par' => '10',
            'lig' => '13',
            'notification_ligne_id' => $ligneId,
        ];
    }

    public function test_engagement_cannot_exceed_notified_credits(): void
    {
        $this->actingAsDirector();
        [, $ligne] = $this->seedNotificationLine(100000.00);
        $fournisseur = Fournisseur::create(['raison_sociale' => 'Fournisseur Test SARL', 'ice' => '001122334455667']);

        $consultation = Consultation::factory()->create([
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'annee' => 2026,
            'numero_consultation' => 'BC-2026-T01',
            'numero_bc' => 'BC-2026-T01',
            'notification_ligne_id' => $ligne->id,
        ]);

        // Engaging 150 000 DH against 100 000 DH notified must return 422
        $resFail = $this->putJson("/api/consultations/{$consultation->id}", $this->engagementPayload(
            $fournisseur->id,
            $ligne->id,
            'ENG-T01/2026',
            150000
        ));
        $resFail->assertStatus(422);
        $this->assertStringContainsString('Règle budgétaire', json_encode($resFail->json(), JSON_UNESCAPED_UNICODE));

        // Engaging 80 000 DH succeeds
        $this->putJson("/api/consultations/{$consultation->id}", $this->engagementPayload(
            $fournisseur->id,
            $ligne->id,
            'ENG-T01/2026',
            80000
        ))->assertOk();
    }

    public function test_ordonnancement_and_liquidation_cannot_exceed_engagement(): void
    {
        $this->actingAsDirector();
        [, $ligne] = $this->seedNotificationLine(200000.00);
        $fournisseur = Fournisseur::create(['raison_sociale' => 'Prestataire Test SARL', 'ice' => '009988776655443']);

        $consultation = Consultation::factory()->create([
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'annee' => 2026,
            'numero_consultation' => 'BC-2026-T02',
            'numero_bc' => 'BC-2026-T02',
            'notification_ligne_id' => $ligne->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        // Attempting liquidation without engagement must fail (422)
        $this->postJson("/api/consultations/{$consultation->id}/liquidation", [
            'montant_a_payer' => 50000,
            'reference_facture' => 'FAC-001',
        ])->assertStatus(422);

        // Engage 60 000 DH
        $this->putJson("/api/consultations/{$consultation->id}", $this->engagementPayload(
            $fournisseur->id,
            $ligne->id,
            'ENG-T02/2026',
            60000
        ))->assertOk();

        // Liquidation exceeding 60 000 DH fails
        $this->postJson("/api/consultations/{$consultation->id}/liquidation", [
            'montant_a_payer' => 70000,
            'reference_facture' => 'FAC-002',
        ])->assertStatus(422);

        // Ordonnancement exceeding 60 000 DH fails
        $resOrdFail = $this->postJson('/api/ordonnancements', [
            'consultation_id' => $consultation->id,
            'fournisseur_id' => $fournisseur->id,
            'notification_ligne_id' => $ligne->id,
            'exercice' => '2026',
            'date_ordonnancement' => '2026-04-01',
            'type_procedure' => 'Bon de commande',
            'reference' => 'BC-2026-T02',
            'beneficiaire_nom' => $fournisseur->raison_sociale,
            'budget_type' => 'Investissement',
            'article' => '415',
            'paragraphe' => '10',
            'ligne' => '13',
            'intitule_depense' => 'Prestation test ordonnancement',
            'montant_brut' => 65000,
            'net_a_payer' => 65000,
            'ordres' => [
                [
                    'num_ordre' => 'OP-001',
                    'type_mouvement' => 'Paiement fournisseur',
                    'beneficiaire' => $fournisseur->raison_sociale,
                    'montant' => 65000,
                    'creance' => 'Crédit neuf',
                ],
            ],
        ]);
        $resOrdFail->assertStatus(422)->assertJsonValidationErrors(['ordres']);

        // Ordonnancement within 60 000 DH succeeds
        $this->postJson('/api/ordonnancements', [
            'consultation_id' => $consultation->id,
            'fournisseur_id' => $fournisseur->id,
            'notification_ligne_id' => $ligne->id,
            'exercice' => '2026',
            'date_ordonnancement' => '2026-04-01',
            'type_procedure' => 'Bon de commande',
            'reference' => 'BC-2026-T02',
            'beneficiaire_nom' => $fournisseur->raison_sociale,
            'budget_type' => 'Investissement',
            'article' => '415',
            'paragraphe' => '10',
            'ligne' => '13',
            'intitule_depense' => 'Prestation test ordonnancement',
            'montant_brut' => 50000,
            'net_a_payer' => 50000,
            'ordres' => [
                [
                    'num_ordre' => 'OP-001',
                    'type_mouvement' => 'Paiement fournisseur',
                    'beneficiaire' => $fournisseur->raison_sociale,
                    'montant' => 50000,
                    'creance' => 'Crédit neuf',
                ],
            ],
        ])->assertCreated();
    }

    public function test_paiement_never_exceeds_ordonnancement_and_dashboard_respects_hierarchy(): void
    {
        $this->actingAsDirector();
        [, $ligne] = $this->seedNotificationLine(100000.00);
        $fournisseur = Fournisseur::create(['raison_sociale' => 'Entreprise Alfa SARL', 'ice' => '005544332211009']);

        $consultation = Consultation::factory()->create([
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'annee' => 2026,
            'numero_consultation' => 'BC-2026-T03',
            'numero_bc' => 'BC-2026-T03',
            'notification_ligne_id' => $ligne->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $this->putJson("/api/consultations/{$consultation->id}", $this->engagementPayload(
            $fournisseur->id,
            $ligne->id,
            'ENG-T03/2026',
            80000
        ))->assertOk();

        $ordRes = $this->postJson('/api/ordonnancements', [
            'consultation_id' => $consultation->id,
            'fournisseur_id' => $fournisseur->id,
            'notification_ligne_id' => $ligne->id,
            'exercice' => '2026',
            'date_ordonnancement' => '2026-04-02',
            'type_procedure' => 'Bon de commande',
            'reference' => 'BC-2026-T03',
            'beneficiaire_nom' => $fournisseur->raison_sociale,
            'budget_type' => 'Investissement',
            'article' => '415',
            'paragraphe' => '10',
            'ligne' => '13',
            'intitule_depense' => 'Prestation test paiement',
            'montant_brut' => 40000,
            'net_a_payer' => 40000,
            'statut' => 'À payer',
            'ordres' => [
                [
                    'num_ordre' => 'OP-101',
                    'type_mouvement' => 'Paiement fournisseur',
                    'beneficiaire' => $fournisseur->raison_sociale,
                    'montant' => 15000,
                    'creance' => 'Crédit neuf',
                ],
                [
                    'num_ordre' => 'OP-102',
                    'type_mouvement' => 'Paiement fournisseur',
                    'beneficiaire' => $fournisseur->raison_sociale,
                    'montant' => 25000,
                    'creance' => 'Crédit neuf',
                ],
            ],
        ])->assertCreated();

        $ordId = $ordRes->json('data.id');
        $ord = Ordonnancement::with('ordres')->findOrFail($ordId);
        $firstOrdreId = $ord->ordres->first()->id;

        // Before paying, dashboard paiement must be 0 while ordonnancement = 40000
        $dash1 = $this->getJson('/api/dashboard/budget?exercice=2026')->assertOk()->json();
        $this->assertEquals(100000.0, (float) $dash1['total_notifie']);
        $this->assertEquals(80000.0, (float) $dash1['total_engage']);
        $this->assertEquals(40000.0, (float) $dash1['total_ordonnance']);
        $this->assertEquals(0.0, (float) $dash1['total_paiement']);

        // Mark only first ordre as Payé -> paiement must become 15000 (<= 40000)
        $this->patchJson("/api/ordonnancements/{$ordId}/statut", [
            'statut' => 'Payé',
            'ordre_id' => $firstOrdreId,
            'date_paiement' => '2026-04-05',
        ])->assertOk();

        $dash2 = $this->getJson('/api/dashboard/budget?exercice=2026')->assertOk()->json();
        $this->assertEquals(15000.0, (float) $dash2['total_paiement']);
        $this->assertLessThanOrEqual((float) $dash2['total_ordonnance'], (float) $dash2['total_paiement']);
        $this->assertLessThanOrEqual((float) $dash2['total_engage'], (float) $dash2['total_ordonnance']);
        $this->assertLessThanOrEqual((float) $dash2['total_notifie'], (float) $dash2['total_engage']);
    }

    public function test_notification_reduction_or_deletion_below_engagements_is_blocked(): void
    {
        $this->actingAsDirector();
        [$notif, $ligne] = $this->seedNotificationLine(100000.00);
        $fournisseur = Fournisseur::create(['raison_sociale' => 'Fournisseur Beta SARL', 'ice' => '003322114455667']);

        $consultation = Consultation::factory()->create([
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'annee' => 2026,
            'numero_consultation' => 'BC-2026-T04',
            'numero_bc' => 'BC-2026-T04',
            'notification_ligne_id' => $ligne->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $this->putJson("/api/consultations/{$consultation->id}", $this->engagementPayload(
            $fournisseur->id,
            $ligne->id,
            'ENG-T04/2026',
            75000
        ))->assertOk();

        // Trying to diminish credits below 75000 DH must fail with 422
        $this->putJson("/api/notification-lignes/{$ligne->id}", [
            'credits_neufs' => 50000,
        ])->assertStatus(422);

        // Trying to delete notification with active engagement (without backup line) must fail with 422
        $this->deleteJson("/api/notifications/{$notif->id}")->assertStatus(422);
    }
}
