<?php

namespace Tests\Feature\Api;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function validConventionPayload(array $overrides = []): array
    {
        return array_merge([
            'numero_consultation' => 'CONV-2026-101',
            'numero_decision' => 'DEC-2026-101',
            'numero_bc' => 'CONV-2026-101',
            'annee' => 2026,
            'date_consultation' => '2026-04-01',
            'date_limite_devis' => '2026-04-15',
            'heure_limite_devis' => '10:00',
            'lieu_execution' => 'Kénitra',
            'objet_consultation' => 'Convention appui conseil agricole',
            'categorie' => 'Services',
            'intitule' => 'Formation et accompagnement',
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'delai_execution' => 45,
            'statut_dossier' => 'Programmation',
            'art' => '415',
            'par' => '30',
            'lig' => '60',
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 15000,
            'tva' => 20,
        ], $overrides);
    }

    public function test_convention_and_contrat_store_reject_empty_or_whitespace_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/consultations', [
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'numero_consultation' => '   ',
            'numero_decision' => '',
            'numero_bc' => '',
            'objet_consultation' => '   ',
            'intitule' => '   ',
            'art' => '',
            'par' => '',
            'lig' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'numero_consultation',
                'numero_decision',
                'numero_bc',
                'date_limite_devis',
                'heure_limite_devis',
                'lieu_execution',
                'objet_consultation',
                'categorie',
                'intitule',
                'art',
                'par',
                'lig',
                'exercice_budgetaire',
            ]);

        $this->postJson('/api/consultations', $this->validConventionPayload([
            'numero_consultation' => 'CONV-2026-102',
            'numero_bc' => 'CONV-2026-102',
            'mode_engagement' => 'Contrat',
            'type_budget' => 'Fonctionnement',
        ]))->assertCreated();
    }

    public function test_engagement_update_enforces_ce_only_for_investissement(): void
    {
        $this->actingAsAdmin();

        $conv = Consultation::factory()->create([
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'numero_consultation' => 'CONV-2026-103',
            'numero_bc' => 'CONV-2026-103',
        ]);

        $this->putJson("/api/consultations/{$conv->id}", [
            'numero_engagement' => 'ENG-103/2026',
            'credit_ouvert_cp' => 100000,
            'depenses_anterieures_cp' => 0,
            'montant_depense_neuf' => 20000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'credit_ouvert_ce',
                'depenses_anterieures_ce',
                'depenses_credits_engagement',
            ]);

        $contrat = Consultation::factory()->create([
            'mode_engagement' => 'Contrat',
            'type_budget' => 'Fonctionnement',
            'numero_consultation' => 'CONV-2026-104',
            'numero_bc' => 'CONV-2026-104',
        ]);

        $this->putJson("/api/consultations/{$contrat->id}", [
            'numero_engagement' => 'ENG-104/2026',
            'credit_ouvert_cp' => 50000,
            'depenses_anterieures_cp' => 1000,
            'montant_depense_neuf' => 5000,
        ])->assertOk();
    }

    public function test_commission_reception_and_prestations_endpoints_validate_required_payloads(): void
    {
        $this->actingAsAdmin();

        $conv = Consultation::factory()->create([
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'numero_consultation' => 'CONV-2026-105',
            'numero_bc' => 'CONV-2026-105',
        ]);

        $this->postJson("/api/consultations/{$conv->id}/commission", [
            'date_reunion' => '2026-04-20',
            'heure_reunion' => '10:00',
            'lieu_reunion' => 'Siège DRCA',
            'membres_commission' => [
                ['nom' => 'Membre 1', 'qualite' => 'Membre'],
                ['nom' => 'Membre 2', 'qualite' => 'Membre'],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['membres_commission', 'president_commission']);

        $this->postJson("/api/consultations/{$conv->id}/reception-commission", [
            'is_pv_reception' => true,
            'numero_bc' => 'CONV-2026-105',
            'numero_decision' => 'DEC-REC-105',
            'type_reception' => 'partielle',
            'date_decision' => '2026-05-01',
            'date_reunion' => '2026-05-05',
            'heure_reunion' => '10:00',
            'membres_commission' => [
                ['nom' => 'M1', 'qualite' => 'Président'],
                ['nom' => 'M2', 'qualite' => 'Membre'],
                ['nom' => 'M3', 'qualite' => 'Membre'],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['heure_fin', 'periode_du', 'periode_au', 'prestations_receptionnees']);

        $this->postJson("/api/consultations/{$conv->id}/prestations", [
            'prestations' => [],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prestations']);

        $this->postJson('/api/commission-membres', [
            'nom_prenom' => '   ',
            'fonction' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nom_prenom', 'fonction']);
    }
}

