<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\Consultation;
use App\Models\Engagement;

class DemoDashboardSeeder extends Seeder
{
    public function run()
    {
        // Nettoyer pour éviter les doublons lors de tests multiples
        Notification::where('numero', 'like', 'DEMO-%')->delete();
        Consultation::where('numero_consultation', 'like', 'DEMO-%')->delete();
        \App\Models\Aoo::where('num_aoo', 'like', 'DEMO-AOO-%')->delete();
        \App\Models\Marche::where('num_marche', 'like', 'DEMO-MARCHE-%')->delete();

        $annees = [2024, 2025, 2026];
        $lignesPredefinies = [
            ['art' => '10', 'par' => '1', 'lig' => '01', 'lib' => 'Achat de matériel informatique', 'total' => 2500000],
            ['art' => '10', 'par' => '2', 'lig' => '05', 'lib' => 'Frais de déplacement et mission', 'total' => 500000],
            ['art' => '20', 'par' => '1', 'lig' => '10', 'lib' => 'Entretien des bâtiments', 'total' => 1200000],
            ['art' => '30', 'par' => '3', 'lig' => '02', 'lib' => 'Fournitures de bureau', 'total' => 300000],
            ['art' => '40', 'par' => '1', 'lig' => '01', 'lib' => 'Prestations de services externes', 'total' => 4500000],
        ];

        foreach ($annees as $annee) {
            $notification = Notification::create([
                'numero' => 'DEMO-NOTIF-' . $annee,
                'exercice' => $annee,
                'date_notification' => $annee . '-01-15',
                'montant' => array_sum(array_column($lignesPredefinies, 'total')),
                'objet' => 'Notification de démonstration pour l\'exercice ' . $annee,
            ]);

            foreach ($lignesPredefinies as $index => $ldata) {
                // Variation légère du total pour chaque année pour que les stats diffèrent
                $variation = 1 + (($annee - 2024) * 0.1); 
                $totalLigne = $ldata['total'] * $variation;

                $ligne = NotificationLigne::create([
                    'notification_id' => $notification->id,
                    'article' => $ldata['art'],
                    'paragraphe' => $ldata['par'],
                    'ligne_budgetaire' => $ldata['lig'],
                    'libelle' => $ldata['lib'],
                    'reports' => 0,
                    'credits_neufs' => $totalLigne,
                    'credits_engagements' => 0,
                ]);

                // Créer une consultation et un engagement liés pour simuler des "Crédits engagés"
                // On simule une consommation de 30% à 90% selon la ligne
                $tauxConsommation = 0.3 + ($index * 0.15); 
                $montantEngage = $totalLigne * $tauxConsommation;

                if ($montantEngage > 0) {
                    $fournisseur = \App\Models\Fournisseur::first();
                    if (!$fournisseur) {
                        $fournisseur = \App\Models\Fournisseur::create(['nom' => 'Fournisseur DEMO', 'ice' => '1234567890']);
                    }

                    $aoo = \App\Models\Aoo::create([
                        'num_aoo' => 'DEMO-AOO-' . $annee . '-' . $index,
                        'objet' => 'Démonstration AOO pour ' . $ldata['lib'],
                        'notification_ligne_id' => $ligne->id,
                        'statut' => 'Programmé',
                        'budget' => $montantEngage
                    ]);

                    \App\Models\Marche::create([
                        'notification_ligne_id' => $ligne->id,
                        'fournisseur_id' => $fournisseur->id,
                        'aoo_id' => $aoo->id,
                        'num_marche' => 'DEMO-MARCHE-' . $annee . '-' . $index,
                        'objet_marche' => 'Marché de démonstration pour ' . $ldata['lib'],
                        'montant' => $montantEngage,
                        'titulaire' => 'Entreprise DEMO',
                        'statut_acte_engagement' => 'Validé'
                    ]);
                }
            }
        }
    }
}
