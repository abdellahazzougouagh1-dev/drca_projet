<?php

namespace Tests\Feature\Api;

use App\Models\Budget;
use App\Models\Consultation;
use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BonCommandeDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_bon_commande_documents_can_be_generated(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $consultation = Consultation::factory()->create([
            'mode_engagement' => 'BC',
            'numero_consultation' => 'CONS-2026-0001',
            'objet_consultation' => 'Achat de fournitures de bureau',
            'type_prestation' => 'Fournitures',
            'statut_dossier' => 'Programmation',
        ]);

        Budget::factory()->create([
            'consultation_id' => $consultation->id,
            'art' => '10',
            'par' => '20',
            'lig' => '30',
            'code_imputation' => '102030',
            'montant_estimatif_ht' => 1000,
            'tva' => 20,
        ]);

        Prestation::create([
            'consultation_id' => $consultation->id,
            'designation' => 'Papier A4',
            'unite' => 'Ramette',
            'quantite' => 10,
            'prix_unitaire_ht' => 100,
            'tva' => 20,
        ]);

        $documents = [
            'avis_achat',
            'decision_commission_ouverture',
            'pv_ouverture_attribution',
            'bon_commande',
            'fiche_engagement',
            'ordre_commande',
            'accuse_reception',
            'decision_commission_reception',
            'pv_reception',
            'ordre_imputation_paiement_virement',
        ];

        foreach ($documents as $document) {
            $this->get("/api/consultations/{$consultation->id}/documents/{$document}")
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        $this->post("/api/consultations/{$consultation->id}/documents/avis_achat", [
            'document_data' => [
                'numero_avis' => '5',
                'numero_bc' => '05/2024/DRCA-RSK',
                'objet' => 'Achat de materiel de valorisation des produits agricoles',
                'date_limite' => '2024-12-16',
                'heure_limite' => '10:00',
            ],
        ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
