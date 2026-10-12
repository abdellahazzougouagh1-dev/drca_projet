<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowEngineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. TYPES D'ENGAGEMENTS
        $types = [
            ['code' => 'bc', 'libelle' => 'Bon de Commande', 'description' => 'Flux pour les bons de commande simples.'],
            ['code' => 'convention', 'libelle' => 'Convention', 'description' => 'Flux pour les conventions.'],
            ['code' => 'aoo', 'libelle' => "Appel d'Offres", 'description' => "Flux pour les appels d'offres (AOO)."],
        ];

        foreach ($types as $type) {
            DB::table('type_engagements')->updateOrInsert(['code' => $type['code']], $type);
        }

        $typeBcId = DB::table('type_engagements')->where('code', 'bc')->value('id');
        $typeConventionId = DB::table('type_engagements')->where('code', 'convention')->value('id');
        $typeAooId = DB::table('type_engagements')->where('code', 'aoo')->value('id');

        // 2. CONFIGURATION DU WORKFLOW PAR TYPE (Étapes et Modèles de Documents)
        $workflows = [
            // A. POUR LE TYPE 'bc' (Bon de Commande)
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'estimation',
                'libelle' => 'Estimation Administrative',
                'ordre' => 1,
                'documents' => [
                    ['code' => 'bc_estimation', 'libelle' => "Estimation de l'administration", 'chemin_blade' => 'documents.estimation_administrative'],
                    ['code' => 'bc_bordereau_prix', 'libelle' => 'Bordereau des prix', 'chemin_blade' => 'documents.bordereau_prix'],
                ]
            ],
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'fournisseurs',
                'libelle' => 'Sélection des Fournisseurs',
                'ordre' => 2,
                'documents' => [
                    ['code' => 'bc_lettre_consultation', 'libelle' => 'Lettres de Consultations', 'chemin_blade' => 'documents.lettre_consultation'],
                ]
            ],
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'commission',
                'libelle' => "Commission d'évaluation",
                'ordre' => 3,
                'documents' => [
                    ['code' => 'bc_decision_ouverture', 'libelle' => "Décision d'ouverture des plis", 'chemin_blade' => 'documents.decision_ouverture'],
                    ['code' => 'bc_pv_ouverture', 'libelle' => "PV d'ouverture des plis", 'chemin_blade' => 'documents.pv_ouverture'],
                    ['code' => 'bc_devis_contradictoires', 'libelle' => 'Devis Contradictoires', 'chemin_blade' => 'documents.devis_contradictoires'],
                ]
            ],
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'engagement',
                'libelle' => 'Engagement du Budget',
                'ordre' => 4,
                'documents' => [
                    ['code' => 'bc_bon_commande', 'libelle' => 'Bon de Commande', 'chemin_blade' => 'documents.bon_commande'],
                    ['code' => 'bc_etat_engagement', 'libelle' => "État d'engagement", 'chemin_blade' => 'documents.etat_engagement'],
                ]
            ],
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'reception',
                'libelle' => 'Réception Finale',
                'ordre' => 5,
                'documents' => [
                    ['code' => 'bc_decision_reception', 'libelle' => 'Décision de réception', 'chemin_blade' => 'documents.decision_reception'],
                    ['code' => 'bc_pv_reception', 'libelle' => 'PV De réception', 'chemin_blade' => 'documents.pv_reception'],
                ]
            ],
            [
                'type_engagement_id' => $typeBcId,
                'code' => 'liquidation',
                'libelle' => 'Liquidation & Paiement',
                'ordre' => 6,
                'documents' => [
                    ['code' => 'bc_facture', 'libelle' => 'Facture', 'chemin_blade' => 'documents.facture'],
                    ['code' => 'bc_exoneration_tva', 'libelle' => 'Exonération de la TVA', 'chemin_blade' => 'documents.exoneration_tva'],
                ]
            ],

            // B. POUR LE TYPE 'convention' (Convention)
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'estimation',
                'libelle' => 'Estimation Administrative',
                'ordre' => 1,
                'documents' => [
                    ['code' => 'conv_rapport_presentation', 'libelle' => 'Rapport de présentation', 'chemin_blade' => 'documents.rapport_presentation'],
                    ['code' => 'conv_reglement_consultation', 'libelle' => 'Règlement de la consultation', 'chemin_blade' => 'documents.reglement_consultation'],
                    ['code' => 'conv_cps', 'libelle' => 'Cahier des Prescriptions Spéciales (CPS)', 'chemin_blade' => 'documents.cps'],
                ]
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'fournisseurs',
                'libelle' => 'Sélection des Fournisseurs',
                'ordre' => 2,
                'documents' => []
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'commission',
                'libelle' => "Commission d'évaluation",
                'ordre' => 3,
                'documents' => [
                    ['code' => 'conv_pv_ouverture_marche', 'libelle' => "P.V d'ouverture de plis", 'chemin_blade' => 'documents.pv_ouverture_marche'],
                    ['code' => 'conv_lettre_commission', 'libelle' => 'Lettre aux membres de la commission', 'chemin_blade' => 'documents.lettre_commission'],
                ]
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'engagement',
                'libelle' => 'Engagement du Budget',
                'ordre' => 4,
                'documents' => [
                    ['code' => 'conv_acte_engagement', 'libelle' => "Acte d'engagement", 'chemin_blade' => 'documents.acte_engagement'],
                    ['code' => 'conv_marche_recu', 'libelle' => "Marché + Recu d'enregistrement", 'chemin_blade' => 'documents.marche_recu'],
                    ['code' => 'conv_os_notification', 'libelle' => "OS de notification de l'approbation", 'chemin_blade' => 'documents.os_notification'],
                    ['code' => 'conv_caution_definitive', 'libelle' => 'Caution bancaire définitive', 'chemin_blade' => 'documents.caution_definitive'],
                    ['code' => 'conv_attestation_assurance', 'libelle' => "Attestation d'assurance", 'chemin_blade' => 'documents.attestation_assurance'],
                ]
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'suivi_execution',
                'libelle' => "Suivi d'Exécution",
                'ordre' => 5,
                'documents' => [
                    ['code' => 'conv_os_commencement', 'libelle' => 'Ordre de service (Commencement/Arrêt)', 'chemin_blade' => 'documents.os_commencement'],
                    ['code' => 'conv_attachement_provisoire', 'libelle' => 'Attachement provisoire', 'chemin_blade' => 'documents.attachement_provisoire'],
                    ['code' => 'conv_decompte_provisoire', 'libelle' => 'Décompte provisoire', 'chemin_blade' => 'documents.decompte_provisoire'],
                ]
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'reception',
                'libelle' => 'Réception Finale',
                'ordre' => 6,
                'documents' => [
                    ['code' => 'conv_pv_reception_provisoire', 'libelle' => 'PV de la réception provisoire', 'chemin_blade' => 'documents.pv_reception_provisoire'],
                    ['code' => 'conv_attestation_reception', 'libelle' => 'Attestation de réception', 'chemin_blade' => 'documents.attestation_reception'],
                ]
            ],
            [
                'type_engagement_id' => $typeConventionId,
                'code' => 'liquidation',
                'libelle' => 'Liquidation & Paiement',
                'ordre' => 7,
                'documents' => [
                    ['code' => 'conv_facture', 'libelle' => 'Facture', 'chemin_blade' => 'documents.facture'],
                ]
            ],

            // C. POUR LE TYPE 'aoo' (Appel d'Offres) - Reste inchangé
            [
                'type_engagement_id' => $typeAooId,
                'code' => 'preparation_publication',
                'libelle' => 'Préparation et Publication',
                'ordre' => 1,
                'documents' => [
                    ['code' => 'aoo_rapport_presentation', 'libelle' => 'Rapport de présentation', 'chemin_blade' => 'documents.rapport_presentation'],
                    ['code' => 'aoo_reglement_consultation', 'libelle' => 'Règlement de la consultation', 'chemin_blade' => 'documents.reglement_consultation'],
                    ['code' => 'aoo_cps', 'libelle' => 'Cahier des Prescriptions Spéciales (CPS)', 'chemin_blade' => 'documents.cps'],
                ]
            ],
            [
                'type_engagement_id' => $typeAooId,
                'code' => 'commission_ouverture',
                'libelle' => 'Commission et Ouverture des Plis',
                'ordre' => 2,
                'documents' => [
                    ['code' => 'aoo_pv_ouverture', 'libelle' => "P.V d'ouverture de plis", 'chemin_blade' => 'documents.pv_ouverture_aoo'],
                    ['code' => 'aoo_lettre_commission', 'libelle' => 'Lettre aux membres de la commission', 'chemin_blade' => 'documents.lettre_commission'],
                    ['code' => 'aoo_liste_presence', 'libelle' => 'Liste de présence', 'chemin_blade' => 'documents.liste_presence'],
                ]
            ],
            [
                'type_engagement_id' => $typeAooId,
                'code' => 'attribution_notification',
                'libelle' => 'Attribution et Notification',
                'ordre' => 3,
                'documents' => [
                    ['code' => 'aoo_acte_engagement', 'libelle' => "Acte d'engagement", 'chemin_blade' => 'documents.acte_engagement'],
                    ['code' => 'aoo_marche_approuve', 'libelle' => 'Marché approuvé', 'chemin_blade' => 'documents.marche_approuve'],
                    ['code' => 'aoo_os_notification', 'libelle' => "OS de notification de l'approbation", 'chemin_blade' => 'documents.os_notification'],
                ]
            ],
            [
                'type_engagement_id' => $typeAooId,
                'code' => 'suivi_travaux',
                'libelle' => 'Suivi et Exécution des Travaux',
                'ordre' => 4,
                'documents' => [
                    ['code' => 'aoo_os_commencement', 'libelle' => 'Ordre de service (Commencement/Arrêt)', 'chemin_blade' => 'documents.os_commencement'],
                    ['code' => 'aoo_attachement_provisoire', 'libelle' => 'Attachement provisoire', 'chemin_blade' => 'documents.attachement_provisoire'],
                    ['code' => 'aoo_decompte_provisoire', 'libelle' => 'Décompte provisoire', 'chemin_blade' => 'documents.decompte_provisoire'],
                ]
            ],
            [
                'type_engagement_id' => $typeAooId,
                'code' => 'reception_liquidation_aoo',
                'libelle' => 'Réception Finale et Liquidation',
                'ordre' => 5,
                'documents' => [
                    ['code' => 'aoo_pv_reception_provisoire', 'libelle' => 'PV de la réception provisoire', 'chemin_blade' => 'documents.pv_reception_provisoire'],
                    ['code' => 'aoo_facture', 'libelle' => 'Facture', 'chemin_blade' => 'documents.facture_aoo'],
                    ['code' => 'aoo_main_levee', 'libelle' => 'Main levée de la caution', 'chemin_blade' => 'documents.main_levee_caution'],
                ]
            ],
        ];

        // Suppression des anciennes données avant de populer
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('document_modeles')->truncate();
        DB::table('etape_workflows')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        foreach ($workflows as $workflowData) {
            $documents = $workflowData['documents'];
            unset($workflowData['documents']);
            
            $etapeId = DB::table('etape_workflows')->insertGetId($workflowData);
            
            foreach ($documents as $doc) {
                $doc['etape_workflow_id'] = $etapeId;
                DB::table('document_modeles')->insert($doc);
            }
        }
    }
}
