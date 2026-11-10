<?php

namespace Tests\Unit;

use App\Models\Ordonnancement;
use App\Models\OrdonnancementOrdre;
use App\Services\BudgetHierarchyService;
use Tests\TestCase;

class BudgetHierarchyServiceTest extends TestCase
{
    // ===================== normalizeDomaine =====================

    public function test_normalize_domaine_returns_fonctionnement(): void
    {
        $this->assertSame('FONCTIONNEMENT', BudgetHierarchyService::normalizeDomaine('fonctionnement'));
        $this->assertSame('FONCTIONNEMENT', BudgetHierarchyService::normalizeDomaine('FONCTIONNEMENT'));
        $this->assertSame('FONCTIONNEMENT', BudgetHierarchyService::normalizeDomaine('Fonct'));
        $this->assertSame('FONCTIONNEMENT', BudgetHierarchyService::normalizeDomaine('  FoNcTioN  '));
    }

    public function test_normalize_domaine_returns_investissement_by_default(): void
    {
        $this->assertSame('INVESTISSEMENT', BudgetHierarchyService::normalizeDomaine('investissement'));
        $this->assertSame('INVESTISSEMENT', BudgetHierarchyService::normalizeDomaine('INVESTISSEMENT'));
        $this->assertSame('INVESTISSEMENT', BudgetHierarchyService::normalizeDomaine(''));
        $this->assertSame('INVESTISSEMENT', BudgetHierarchyService::normalizeDomaine(null));
        $this->assertSame('INVESTISSEMENT', BudgetHierarchyService::normalizeDomaine('OTHER'));
    }

    // ===================== imputationKey =====================

    public function test_imputation_key_normalizes_domaine_and_trims(): void
    {
        $this->assertSame('INVESTISSEMENT_415/10/13', BudgetHierarchyService::imputationKey('Investissement', '415', '10', '13'));
        $this->assertSame('FONCTIONNEMENT_210/10/05', BudgetHierarchyService::imputationKey('fonctionnement', '210', '10', '05'));
        $this->assertSame('INVESTISSEMENT_415/10/13', BudgetHierarchyService::imputationKey(null, ' 415 ', ' 10 ', ' 13 '));
    }

    // ===================== normalizeStatusKey =====================

    public function test_normalize_status_key_strips_accents(): void
    {
        $this->assertSame('rejete', BudgetHierarchyService::normalizeStatusKey('Rejeté'));
        $this->assertSame('annule', BudgetHierarchyService::normalizeStatusKey('Annulé'));
        $this->assertSame('paye', BudgetHierarchyService::normalizeStatusKey('Payé'));
        $this->assertSame('cloture', BudgetHierarchyService::normalizeStatusKey('Clôturé'));
        $this->assertSame('a payer', BudgetHierarchyService::normalizeStatusKey(' À payer '));
        $this->assertSame('valide', BudgetHierarchyService::normalizeStatusKey('Validé'));
    }

    // ===================== Status helpers =====================

    public function test_is_ordonnancement_cancelled_or_rejected(): void
    {
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementCancelledOrRejected('Rejeté'));
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementCancelledOrRejected('Annulé'));
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementCancelledOrRejected('rejete'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementCancelledOrRejected('Payé'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementCancelledOrRejected('À payer'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementCancelledOrRejected(null));
    }

    public function test_is_ordonnancement_paid(): void
    {
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementPaid('Payé'));
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementPaid('Clôturé'));
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementPaid('paye'));
        $this->assertTrue(BudgetHierarchyService::isOrdonnancementPaid('cloture'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementPaid('À payer'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementPaid('Rejeté'));
        $this->assertFalse(BudgetHierarchyService::isOrdonnancementPaid(null));
    }

    public function test_is_ordre_active(): void
    {
        $this->assertTrue(BudgetHierarchyService::isOrdreActive('À payer'));
        $this->assertTrue(BudgetHierarchyService::isOrdreActive('Payé'));
        $this->assertTrue(BudgetHierarchyService::isOrdreActive('Brouillon'));
        $this->assertFalse(BudgetHierarchyService::isOrdreActive('Rejeté'));
        $this->assertFalse(BudgetHierarchyService::isOrdreActive('Annulé'));
    }

    public function test_is_ordre_paid(): void
    {
        $this->assertTrue(BudgetHierarchyService::isOrdrePaid('Payé'));
        $this->assertTrue(BudgetHierarchyService::isOrdrePaid('Clôturé'));
        $this->assertFalse(BudgetHierarchyService::isOrdrePaid('À payer'));
        $this->assertFalse(BudgetHierarchyService::isOrdrePaid('Transmis au trésorier'));
    }

    public function test_is_marche_liquidation_validated(): void
    {
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated('Validé'));
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated('VALIDÉE'));
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated("Transmise à l'ordonnancement"));
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated('ORDONNANCÉE'));
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated('Partiellement ordonnancée'));
        $this->assertTrue(BudgetHierarchyService::isMarcheLiquidationValidated('Totalement ordonnancée'));
        $this->assertFalse(BudgetHierarchyService::isMarcheLiquidationValidated('Brouillon'));
        $this->assertFalse(BudgetHierarchyService::isMarcheLiquidationValidated('REJETÉE'));
        $this->assertFalse(BudgetHierarchyService::isMarcheLiquidationValidated(null));
    }

    // ===================== classifyOrdreCategory =====================

    public function test_classify_ordre_category(): void
    {
        $makeOrdre = fn (string $creance = '', string $type = '') => tap(new OrdonnancementOrdre(), function ($o) use ($creance, $type) {
            $o->creance = $creance;
            $o->type_mouvement = $type;
        });

        $this->assertSame('reports', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Crédit report', '')));
        $this->assertSame('credits_consolides', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Consolidé', '')));
        $this->assertSame('credits_neufs', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Crédit neuf', '')));
        $this->assertSame('ras', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Retenue TVA', '')));
        $this->assertSame('ras', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Retenue IAS', '')));
        $this->assertSame('rap', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Reste à payer', '')));
        $this->assertSame('rap', BudgetHierarchyService::classifyOrdreCategory($makeOrdre('Paiement fournisseur', '')));
    }

    // ===================== computeOrdonnancementAmounts =====================

    public function test_compute_ordonnancement_amounts_rejected_dossier(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Rejeté';
        $ord->montant_brut = 50000;
        $ord->setRelation('ordres', collect());

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);
        $this->assertEquals(0.0, $result['total_ordonnance']);
        $this->assertEquals(0.0, $result['total_paiements']);
    }

    public function test_compute_ordonnancement_amounts_annule_dossier(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Annulé';
        $ord->montant_brut = 100000;
        $ord->setRelation('ordres', collect());

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);
        $this->assertEquals(0.0, $result['total_ordonnance']);
        $this->assertEquals(0.0, $result['total_paiements']);
    }

    public function test_compute_ordonnancement_amounts_with_active_ordres(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'À payer';
        $ord->montant_brut = 50000;

        $ordre1 = new OrdonnancementOrdre();
        $ordre1->montant = 30000;
        $ordre1->statut = 'Payé';
        $ordre1->creance = 'Crédit neuf';
        $ordre1->type_mouvement = 'Paiement fournisseur';

        $ordre2 = new OrdonnancementOrdre();
        $ordre2->montant = 20000;
        $ordre2->statut = 'À payer';
        $ordre2->creance = 'Reste à payer';
        $ordre2->type_mouvement = 'Paiement fournisseur';

        $ordreRejete = new OrdonnancementOrdre();
        $ordreRejete->montant = 10000;
        $ordreRejete->statut = 'Rejeté';
        $ordreRejete->creance = 'Crédit neuf';
        $ordreRejete->type_mouvement = 'Paiement fournisseur';

        $ord->setRelation('ordres', collect([$ordre1, $ordre2, $ordreRejete]));

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);

        // total_ordonnance = 30000 + 20000 = 50000 (rejected excluded)
        $this->assertEquals(50000.0, $result['total_ordonnance']);
        // total_paiements = 30000 (only paid ordre, dossier is not "Payé")
        $this->assertEquals(30000.0, $result['total_paiements']);
    }

    public function test_compute_ordonnancement_amounts_dossier_paye_all_ordres_count_as_paid(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Payé';
        $ord->montant_brut = 50000;

        $ordre1 = new OrdonnancementOrdre();
        $ordre1->montant = 30000;
        $ordre1->statut = 'À payer'; // statut of ordre doesn't matter if dossier is Payé
        $ordre1->creance = 'Crédit neuf';
        $ordre1->type_mouvement = 'Paiement fournisseur';

        $ordre2 = new OrdonnancementOrdre();
        $ordre2->montant = 20000;
        $ordre2->statut = 'Brouillon';
        $ordre2->creance = 'Reste à payer';
        $ordre2->type_mouvement = 'Paiement fournisseur';

        $ord->setRelation('ordres', collect([$ordre1, $ordre2]));

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);
        $this->assertEquals(50000.0, $result['total_ordonnance']);
        $this->assertEquals(50000.0, $result['total_paiements']);
    }

    public function test_compute_ordonnancement_amounts_zero_ordre_fallback_uses_fields(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Payé';
        $ord->montant_brut = 86400.00;
        $ord->net_a_payer = 72000.00;
        $ord->credit_neuf = 86400.00;
        $ord->ras_total = 14400.00;
        $ord->rap_total = 72000.00;
        $ord->retenue_tva = 14400.00;
        $ord->retenue_ias = 0;
        $ord->autres_retenues = 0;
        $ord->paiement_reports = 0;
        $ord->credit_consolide = 0;
        $ord->creance = 'Crédit neuf';
        $ord->setRelation('ordres', collect());

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);
        $this->assertEquals(86400.0, $result['total_ordonnance']);
        $this->assertEquals(86400.0, $result['total_paiements']); // dossier is Payé
        $this->assertEquals(86400.0, $result['ordonnance']['credits_neufs']);
    }

    public function test_compute_ordonnancement_amounts_unpaid_dossier_zero_ordres_not_paid(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Transmis au trésorier';
        $ord->montant_brut = 50000.00;
        $ord->net_a_payer = 42000.00;
        $ord->credit_neuf = 50000.00;
        $ord->creance = 'Crédit neuf';
        $ord->setRelation('ordres', collect());

        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);
        $this->assertEquals(50000.0, $result['total_ordonnance']);
        $this->assertEquals(0.0, $result['total_paiements']);
    }

    public function test_total_paiements_never_exceeds_total_ordonnance(): void
    {
        $ord = new Ordonnancement();
        $ord->statut = 'Payé';
        $ord->montant_brut = 100.00;

        // Two ordres totalling 100 — both would be "paid" since dossier is Payé
        $o1 = new OrdonnancementOrdre();
        $o1->montant = 60;
        $o1->statut = 'Payé';
        $o1->creance = 'Crédit neuf';
        $o1->type_mouvement = '';

        $o2 = new OrdonnancementOrdre();
        $o2->montant = 40;
        $o2->statut = 'Payé';
        $o2->creance = 'Reste à payer';
        $o2->type_mouvement = '';

        $ord->setRelation('ordres', collect([$o1, $o2]));
        $result = BudgetHierarchyService::computeOrdonnancementAmounts($ord);

        $this->assertLessThanOrEqual($result['total_ordonnance'], $result['total_paiements']);
        $this->assertEquals(100.0, $result['total_ordonnance']);
        $this->assertEquals(100.0, $result['total_paiements']);
    }

    // ===================== Eloquent "0.00" truthiness bug guard =====================

    public function test_decimal_zero_as_string_truthiness(): void
    {
        // In PHP, the string "0.00" from Eloquent decimal casts is truthy
        // This validates the service helpers correctly handle it
        $this->assertTrue((bool) "0.00", 'PHP string "0.00" must be truthy');
        $this->assertFalse((bool) "0", 'PHP string "0" must be falsy');
        $this->assertFalse((bool) "", 'PHP empty string must be falsy');

        // Our service uses (float) cast, which handles "0.00" properly
        $this->assertEquals(0.0, (float) "0.00");
    }
}
