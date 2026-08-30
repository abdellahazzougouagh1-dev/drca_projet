<?php

namespace App\Services;

use App\Models\Aoo;
use App\Models\Marche;
use App\Models\DocumentGenere;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class AooDocumentGenerationService
{
    public static function generateMarcheFromCps(Marche $marche): string
    {
        if (!$marche->chemin_cps || !Storage::exists($marche->chemin_cps)) {
            throw new \Exception("Aucun fichier CPS importé pour ce marché.");
        }

        $templatePath = storage_path('app/' . $marche->chemin_cps);
        $templateProcessor = new TemplateProcessor($templatePath);

        // Données du marché
        $templateProcessor->setValue('num_marche', $marche->num_marche ?? '');
        $templateProcessor->setValue('objet_marche', $marche->objet_marche ?? '');
        $templateProcessor->setValue('montant_ttc', number_format($marche->montant_ttc, 2, ',', ' ') ?? '');
        
        if ($marche->aoo) {
            $templateProcessor->setValue('num_aoo', $marche->aoo->num_aoo ?? '');
        }

        // Données du fournisseur (attributaire)
        if ($marche->fournisseur) {
            $fournisseur = $marche->fournisseur;
            $templateProcessor->setValue('titulaire_nom', $fournisseur->nom ?? '');
            $templateProcessor->setValue('titulaire_adresse', $fournisseur->adresse ?? '');
            $templateProcessor->setValue('titulaire_ice', $fournisseur->ice ?? '');
            $templateProcessor->setValue('titulaire_rc', $fournisseur->rc ?? '');
            $templateProcessor->setValue('titulaire_patente', $fournisseur->patente ?? '');
            $templateProcessor->setValue('titulaire_cnss', $fournisseur->cnss ?? '');
            $templateProcessor->setValue('titulaire_rib', $fournisseur->rib ?? '');
            $templateProcessor->setValue('titulaire_banque', $fournisseur->banque ?? '');
            $templateProcessor->setValue('titulaire_capital', $fournisseur->capital ? number_format($fournisseur->capital, 2, ',', ' ') : '');
            $templateProcessor->setValue('titulaire_ville_rc', $fournisseur->ville_rc ?? '');
            $templateProcessor->setValue('titulaire_email', $fournisseur->email ?? '');
            $templateProcessor->setValue('titulaire_telephone', $fournisseur->telephone ?? '');
        }

        $outputDir = 'documents_generes/marches/' . $marche->id;
        $absoluteOutputDir = storage_path('app/' . $outputDir);
        if (!is_dir($absoluteOutputDir)) {
            mkdir($absoluteOutputDir, 0777, true);
        }

        $fileName = 'Marche_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $marche->num_marche) . '_' . time() . '.docx';
        $outputPath = storage_path('app/' . $outputDir . '/' . $fileName);

        $templateProcessor->saveAs($outputPath);

        return $outputPath;
    }
    public static function generate(Aoo $aoo, string $typeDocument, array $extraData = []): DocumentGenere
    {
        $templatePath = storage_path('app/templates/' . $typeDocument . '.docx');

        if (!file_exists($templatePath)) {
            throw new \Exception("Le modèle pour {$typeDocument} est introuvable dans {$templatePath}");
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Balises Globales
        self::fillGlobalData($templateProcessor, $aoo);

        // Mapping Spécifique
        if ($typeDocument === 'estimation' || $typeDocument === 'bordereau') {
            self::fillEstimationData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'rc') {
            // Seules les balises globales sont requises pour l'instant
        } elseif ($typeDocument === 'avis_fr') {
            self::fillAvisFrData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'avis_ar') {
            self::fillAvisArData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'pv_ouverture') {
            self::fillPvOuvertureData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'lettre_decartement') {
            self::fillLettreEcartementData($templateProcessor, $aoo, $extraData);
        } elseif ($typeDocument === 'rapport_presentation') {
            self::fillRapportPresentationData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'resultats_ao') {
            self::fillResultatsAoData($templateProcessor, $aoo);
        } elseif ($typeDocument === 'decision_nomination') {
            self::fillDecisionNominationData($templateProcessor, $aoo);
        }

        $outputDir = 'documents_generes/aoo/' . $aoo->id;
        $absoluteOutputDir = storage_path('app/' . $outputDir);
        if (!is_dir($absoluteOutputDir)) {
            mkdir($absoluteOutputDir, 0777, true);
        }

        $fileName = $typeDocument . '_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $aoo->num_aoo) . '_' . time() . '.docx';
        $outputPath = storage_path('app/' . $outputDir . '/' . $fileName);

        $templateProcessor->saveAs($outputPath);

        $document = new DocumentGenere([
            'aoo_id' => $aoo->id,
            'type_document' => $typeDocument,
            'nom_fichier' => $fileName,
            'chemin_fichier' => $outputDir . '/' . $fileName,
            'chemin_pdf' => '', // Avoid DB strict mode error if column exists and not nullable
            'version' => DocumentGenere::where('aoo_id', $aoo->id)->where('type_document', $typeDocument)->count() + 1,
            'statut' => 'généré',
            'utilisateur_id' => auth()->id() ?? 1
        ]);

        $document->save();

        return $document;
    }

    private static function fillGlobalData(TemplateProcessor $processor, Aoo $aoo)
    {
        $processor->setValue('numero_ao', $aoo->num_aoo ?? '');
        $processor->setValue('objet_ao', $aoo->objet ?? '');
        $processor->setValue('objet_ao_ar', $aoo->objet_ar ?? '');
        $processor->setValue('date_preparation', $aoo->date_preparation ? \Carbon\Carbon::parse($aoo->date_preparation)->format('d/m/Y') : '');
        
        $processor->setValue('date_ouverture', $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '');
        $processor->setValue('heure_ouverture', $aoo->heure_ouverture ? \Carbon\Carbon::parse($aoo->heure_ouverture)->format('H:i') : '');
        $processor->setValue('lieu_ouverture', $aoo->lieu_ouverture ?? '');
        $processor->setValue('lieu_ouverture_ar', $aoo->lieu_ouverture_ar ?? '');
        
        // Celles spécifiques à AOO
        $processor->setValue('journal_fr', $aoo->journal_fr ?? '');
        $processor->setValue('reference_publication_fr', $aoo->reference_publication_fr ?? '');
        $processor->setValue('date_publication_fr', $aoo->date_publication_fr ? \Carbon\Carbon::parse($aoo->date_publication_fr)->format('d/m/Y') : '');
        
        $journalAr = (!empty($aoo->journal_ar) && !preg_match('/^\?+$/', trim($aoo->journal_ar))) ? $aoo->journal_ar : 'الصحراء المغربية';
        $processor->setValue('journal_ar', $journalAr);
        $processor->setValue('reference_publication_ar', $aoo->reference_publication_ar ?? '');
        $processor->setValue('date_publication_ar', $aoo->date_publication_ar ? \Carbon\Carbon::parse($aoo->date_publication_ar)->format('d/m/Y') : '');

        $processor->setValue('articles_rc', $aoo->articles_rc ?? '');
    }

    private static function fillEstimationData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['lots.items']);
        
        $totalHt = 0;
        $totalTva = 0;

        // Flatten all items across all lots for estimation
        $allItems = collect();
        foreach($aoo->lots as $lot) {
            foreach($lot->items as $item) {
                $allItems->push($item);
            }
        }

        if ($allItems->count() > 0) {
            try {
                $processor->cloneRow('numero_prix', $allItems->count());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Balise numero_prix introuvable pour AOO {$aoo->id}. Erreur: " . $e->getMessage());
                return;
            }

            $i = 1;
            foreach ($allItems as $item) {
                $processor->setValue("numero_prix#{$i}", $i);
                $processor->setValue("designation#{$i}", $item->designation);
                $processor->setValue("unite#{$i}", $item->unite);
                $processor->setValue("quantite#{$i}", $item->quantite);
                
                $prixUnitaire = (float) $item->prix_unitaire_ht;
                $quantite = (float) $item->quantite;
                $processor->setValue("prix_unitaire_ht#{$i}", number_format($prixUnitaire, 2, ',', ' '));
                
                $montantHt = $quantite * $prixUnitaire;
                $processor->setValue("montant_ht#{$i}", number_format($montantHt, 2, ',', ' '));
                
                $totalHt += $montantHt;
                // Get TVA from lot or item depending on structure
                $tvaRate = 20; // Default 20%
                if (isset($item->tva)) {
                    $tvaRate = (float) $item->tva;
                } elseif (isset($item->lot->tva)) {
                    $tvaRate = (float) $item->lot->tva;
                }
                $totalTva += $montantHt * ($tvaRate / 100);
                
                $i++;
            }
        }

        $totalTtc = $totalHt + $totalTva;
        $processor->setValue('total_ht', number_format($totalHt, 2, ',', ' '));
        $processor->setValue('total_tva', number_format($totalTva, 2, ',', ' '));
        $processor->setValue('total_ttc', number_format($totalTtc, 2, ',', ' '));
        // Use MontantEnLettres if it exists in the codebase
        if (class_exists(\App\Utils\MontantEnLettres::class)) {
            $processor->setValue('montant_lettres', \App\Utils\MontantEnLettres::convert($totalTtc));
        } else {
            $processor->setValue('montant_lettres', '');
        }
    }

    private static function fillAvisFrData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['lots']);
        
        if ($aoo->lots->count() > 0) {
            try {
                $processor->cloneBlock('LOT_BLOCK', $aoo->lots->count(), true, true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Balise LOT_BLOCK introuvable dans avis_fr pour AOO {$aoo->id}. Erreur: " . $e->getMessage());
            }

            $i = 1;
            foreach ($aoo->lots as $lot) {
                $processor->setValue("lot_numero#{$i}", $lot->num_lot ?? "Lot {$i}");
                $processor->setValue("lot_objet#{$i}", $lot->objet_lot ?? $aoo->objet);
                $estimation = (float) $lot->estimation;
                $processor->setValue("lot_estimation#{$i}", number_format($estimation, 2, ',', ' '));
                
                $cautionnement = (float) $lot->cautionnement_provisoire;
                $processor->setValue("lot_cautionnement#{$i}", number_format($cautionnement, 2, ',', ' '));
                $i++;
            }
        }
    }

    private static function fillAvisArData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['lots']);
        
        if ($aoo->lots->count() > 0) {
            try {
                $processor->cloneBlock('LOT_BLOCK_AR', $aoo->lots->count(), true, true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Balise LOT_BLOCK_AR introuvable dans avis_ar pour AOO {$aoo->id}. Erreur: " . $e->getMessage());
            }

            $i = 1;
            foreach ($aoo->lots as $lot) {
                $processor->setValue("lot_numero_ar#{$i}", $lot->num_lot ?? "حصة {$i}");
                $processor->setValue("lot_objet_ar#{$i}", $lot->objet_lot_ar ?? $aoo->objet_ar);
                $estimation = (float) $lot->estimation;
                $processor->setValue("lot_estimation_ar#{$i}", number_format($estimation, 2, ',', ' '));
                
                $cautionnement = (float) $lot->cautionnement_provisoire;
                $processor->setValue("lot_cautionnement_ar#{$i}", number_format($cautionnement, 2, ',', ' '));
                $i++;
            }
        }
    }

    private static function fillPvOuvertureData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['concurrents.fournisseur']);
        $processor->setValue('heure_levee', $aoo->heure_levee ? \Carbon\Carbon::parse($aoo->heure_levee)->format('H:i') : '.........');
        $processor->setValue('num_decision_nomination', $aoo->num_decision_nomination ?? '.........');
        
        // Date de la lettre (souvent égale à date_ouverture)
        $dateLettre = $aoo->date_lettre ? \Carbon\Carbon::parse($aoo->date_lettre)->format('d/m/Y') : ($aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : date('d/m/Y'));
        $processor->setValue('date_lettre', $dateLettre);

        // 1. Commission Membres
        $membresRaw = is_array($aoo->membres_commission) ? $aoo->membres_commission : [];
        if (empty($membresRaw)) {
            $membresRaw = [
                ['role' => 'Président', 'nom_prenom' => $aoo->president_commission ?: '....................', 'fonction' => '....................', 'organisme' => '....................'],
                ['role' => 'Membre', 'nom_prenom' => $aoo->rapporteur_commission ?: '....................', 'fonction' => '....................', 'organisme' => '....................'],
            ];
        }

        try {
            $processor->cloneRow('commission_nom', count($membresRaw));
            foreach ($membresRaw as $idx => $m) {
                $i = $idx + 1;
                $role = $m['role'] ?? ($idx === 0 ? 'Président' : 'Membre ' . $idx);
                $qualite = (stripos($role, 'président') !== false) ? 'Président' : 'Membre';
                $processor->setValue("commission_nom#{$i}", $m['nom_prenom'] ?? '');
                $processor->setValue("commission_fonction#{$i}", $m['fonction'] ?? '');
                $processor->setValue("commission_qualite#{$i}", $qualite);
            }
        } catch (\Exception $e) {}

        // Balises de signatures (hors tableau)
        $processor->setValue('sign_president', $membresRaw[0]['nom_prenom'] ?? '....................');
        $processor->setValue('sign_membre1', $membresRaw[1]['nom_prenom'] ?? '....................');
        $processor->setValue('sign_membre2', $membresRaw[2]['nom_prenom'] ?? '....................');
        
        $processor->setValue('ref_lettre', $aoo->reference_publication_fr ?: ($aoo->num_aoo ?? '.........'));
        
        // Extraire la ville depuis le lieu_ouverture (ex: "Siège de la DRCA-RSK Kénitra" -> "Kénitra")
        // Ou utiliser la valeur de base si elle est courte.
        $lieu = $aoo->lieu_ouverture ?? 'Kénitra';
        $ville = (stripos($lieu, 'Kénitra') !== false) ? 'Kénitra' : (strlen($lieu) < 15 ? $lieu : 'Kénitra');
        $processor->setValue('lieu_signature', $ville);
        
        $processor->setValue('date_signature', $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : date('d/m/Y'));

        // 2. Participants & 3. Offres & Attributaire
        $concurrents = $aoo->concurrents;
        
        $lowestTtc = null;
        $retainedCandidate = null;

        if ($concurrents->count() > 0) {
            try { $processor->cloneRow('participant_n', $concurrents->count()); } catch (\Exception $e) {}
            try { $processor->cloneRow('offre_n', $concurrents->count()); } catch (\Exception $e) {}

            $i = 1;
            foreach ($concurrents as $c) {
                $fournisseur = $c->fournisseur;
                $nom = $fournisseur->raison_sociale ?? $fournisseur->societe ?? $c->nom_soumissionnaire ?? 'Candidat';
                
                // Tableau Participants
                $processor->setValue("participant_n#{$i}", $i);
                $processor->setValue("participant_nom#{$i}", $nom);
                $processor->setValue("participant_adresse#{$i}", $fournisseur->adresse ?? '');
                $processor->setValue("participant_ville#{$i}", $fournisseur->ville ?? '');
                
                // Tableau Offres
                $ttc = (float) ($c->montant_ttc ?? $c->montant_acte_engagement ?? $c->montant_engagement ?? 0);
                $ttcFormat = $ttc > 0 ? number_format($ttc, 2, ',', ' ') : '-';
                
                $processor->setValue("offre_n#{$i}", $i);
                $processor->setValue("offre_nom#{$i}", $nom);
                $processor->setValue("offre_montant#{$i}", $ttcFormat);
                $processor->setValue("offre_montant_apres#{$i}", $ttcFormat); // Approximation avant/après
                
                // Recherche du gagnant
                if ($c->classement == 1 || ($ttc > 0 && ($lowestTtc === null || $ttc < $lowestTtc))) {
                    $lowestTtc = $ttc;
                    $retainedCandidate = [
                        'nom' => $nom,
                        'montant_ttc_format' => $ttcFormat,
                        'montant_lettres' => $ttc > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($ttc) : '-',
                    ];
                }
                
                $i++;
            }
        } else {
            // Fallbacks si aucun concurrent
            try {
                $processor->cloneRow('participant_n', 1);
                $processor->setValue("participant_n#1", '1');
                $processor->setValue("participant_nom#1", 'Aucun');
                $processor->setValue("participant_adresse#1", '-');
                $processor->setValue("participant_ville#1", '-');
            } catch (\Exception $e) {}
            
            try {
                $processor->cloneRow('offre_n', 1);
                $processor->setValue("offre_n#1", '1');
                $processor->setValue("offre_nom#1", 'Aucun');
                $processor->setValue("offre_montant#1", '-');
                $processor->setValue("offre_montant_apres#1", '-');
            } catch (\Exception $e) {}
        }

        // Attributaire (Gagnant)
        if ($retainedCandidate) {
            $processor->setValue('attributaire_nom', $retainedCandidate['nom']);
            $processor->setValue('attributaire_montant', $retainedCandidate['montant_ttc_format']);
            $processor->setValue('attributaire_lettres', $retainedCandidate['montant_lettres']);
        } else {
            $processor->setValue('attributaire_nom', '..........................');
            $processor->setValue('attributaire_montant', '..........');
            $processor->setValue('attributaire_lettres', '..........');
        }
    }

    private static function fillLettreEcartementData(TemplateProcessor $processor, Aoo $aoo, array $extraData)
    {
        $processor->setValue('nom_soumissionnaire', $extraData['nom_soumissionnaire'] ?? '__________');
        $processor->setValue('motif_ecartement', $extraData['motif_ecartement'] ?? '__________');
        $processor->setValue('adresse_soumissionnaire', $extraData['adresse_soumissionnaire'] ?? '__________');
    }

    private static function fillRapportPresentationData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['concurrents.fournisseur', 'lots.attributaire.fournisseur']);
        
        $processor->setValue('date_signature', date('d/m/Y'));
        
        // Trouver l'attributaire (le mieux classé globalement, ou par lot si multilots)
        $attributaire = $aoo->concurrents->where('classement', 1)->first();
        if ($attributaire) {
            $processor->setValue('attributaire_nom', $attributaire->fournisseur->raison_sociale ?? $attributaire->fournisseur->societe ?? $attributaire->nom_soumissionnaire ?? '...........');
            $ttc = (float) ($attributaire->montant_ttc ?? $attributaire->montant_acte_engagement ?? 0);
            $processor->setValue('attributaire_montant', $ttc > 0 ? number_format($ttc, 2, ',', ' ') : '...........');
            $processor->setValue('attributaire_montant_lettres', $ttc > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($ttc) : '...........');
        } else {
            $processor->setValue('attributaire_nom', '...........');
            $processor->setValue('attributaire_montant', '...........');
            $processor->setValue('attributaire_montant_lettres', '...........');
        }
        
        // Si tableau multilot
        if ($aoo->lots->count() > 0) {
            try { $processor->cloneRow('lot_rp', $aoo->lots->count()); } catch (\Exception $e) {}

            $i = 1;
            foreach ($aoo->lots as $lot) {
                $processor->setValue("lot_rp#{$i}", $lot->num_lot ?? "Lot {$i}");
                $attributaireLot = $lot->attributaire->fournisseur->raison_sociale ?? $lot->attributaire->nom_soumissionnaire ?? 'Aucun attributaire';
                $processor->setValue("attributaire_rp#{$i}", $attributaireLot);
                
                $montant = 0;
                if ($lot->attributaire) {
                    $montant = $lot->attributaire->montant_apres_verification ?? $lot->attributaire->montant_acte_engagement;
                }
                $processor->setValue("montant_rp#{$i}", number_format((float)$montant, 2, ',', ' '));
                $i++;
            }
        }
    }

    private static function fillResultatsAoData(TemplateProcessor $processor, Aoo $aoo)
    {
        $aoo->loadMissing(['concurrents.fournisseur']);
        $processor->setValue('date_signature', date('d/m/Y'));
        
        $concurrents = $aoo->concurrents;
        if ($concurrents->count() > 0) {
            try { $processor->cloneRow('concurrent_n', $concurrents->count()); } catch (\Exception $e) {}
            
            $i = 1;
            foreach ($concurrents as $c) {
                $processor->setValue("concurrent_n#{$i}", $i);
                $nom = $c->fournisseur->raison_sociale ?? $c->fournisseur->societe ?? $c->nom_soumissionnaire ?? '...........';
                $processor->setValue("concurrent_nom#{$i}", $nom);
                
                $ttc = (float) ($c->montant_ttc ?? $c->montant_acte_engagement ?? 0);
                $processor->setValue("concurrent_montant#{$i}", $ttc > 0 ? number_format($ttc, 2, ',', ' ') : '-');
                
                // Statut basé sur le classement ou tech_conforme
                if ($c->classement == 1) {
                    $statut = 'Retenu';
                } elseif ($c->admin_conforme === false) {
                    $statut = 'Écarté (Admin)';
                } elseif ($c->tech_conforme === false) {
                    $statut = 'Écarté (Tech)';
                } else {
                    $statut = 'Admis';
                }
                $processor->setValue("concurrent_statut#{$i}", $statut);
                $processor->setValue("concurrent_motif#{$i}", $c->motif_ecartement ?? '-');
                $i++;
            }
        } else {
            try { $processor->cloneRow('concurrent_n', 1); } catch (\Exception $e) {}
            $processor->setValue("concurrent_n#1", '-');
            $processor->setValue("concurrent_nom#1", '-');
            $processor->setValue("concurrent_montant#1", '-');
            $processor->setValue("concurrent_statut#1", '-');
            $processor->setValue("concurrent_motif#1", '-');
        }
    }

    private static function fillDecisionNominationData(TemplateProcessor $processor, Aoo $aoo)
    {
        $numeroDecision = $aoo->num_decision_nomination ?: $aoo->num_aoo;
        $dateDecision = $aoo->date_decision_nomination
            ? \Carbon\Carbon::parse($aoo->date_decision_nomination)->format('d/m/Y')
            : ($aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : date('d/m/Y'));
        $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : date('d/m/Y');

        $processor->setValue('numero_decision', $numeroDecision);
        $processor->setValue('num_decision_nomination', $numeroDecision);
        $processor->setValue('num_decision', $numeroDecision);
        $processor->setValue('date_decision', $dateDecision);
        $processor->setValue('date_decision_nomination', $dateDecision);
        $processor->setValue('date_ouverture', $dateOuverture);
        $processor->setValue('numero_ao', $aoo->num_aoo ?? '...........');
        $processor->setValue('num_aoo', $aoo->num_aoo ?? '...........');
        $processor->setValue('objet_ao', $aoo->objet ?? '...........');
        $processor->setValue('objet', $aoo->objet ?? '...........');

        $membresRaw = is_array($aoo->membres_commission) ? $aoo->membres_commission : [];
        if (empty($membresRaw)) {
            $membresRaw = [
                ['role' => 'Président', 'nom_prenom' => $aoo->president_commission ?: 'Directeur Régional', 'fonction' => 'Directeur Régional', 'organisme' => 'DRCA-RSK'],
                ['role' => 'Membre', 'nom_prenom' => $aoo->rapporteur_commission ?: 'Chef de Service Marchés', 'fonction' => 'Chef de Service Marchés', 'organisme' => 'DRCA-RSK'],
                ['role' => 'Membre', 'nom_prenom' => 'Mme. Salma BENANI', 'fonction' => 'Administrateur', 'organisme' => 'DRCA-RSK'],
                ['role' => 'Membre', 'nom_prenom' => 'M. Omar CHRAIBI', 'fonction' => 'Représentant Trésorerie', 'organisme' => 'Ministère des Finances'],
            ];
        }

        try {
            $processor->cloneRow('commission_nom', count($membresRaw));
            foreach ($membresRaw as $idx => $m) {
                $i = $idx + 1;
                $role = $m['role'] ?? ($idx === 0 ? 'Président' : 'Membre ' . $idx);
                $qualite = (stripos($role, 'président') !== false) ? 'Président' : 'Membre';
                $processor->setValue("commission_nom#{$i}", $m['nom_prenom'] ?? '');
                $processor->setValue("commission_fonction#{$i}", $m['fonction'] ?? '');
                $processor->setValue("commission_organisme#{$i}", $m['organisme'] ?? '');
                $processor->setValue("commission_qualite#{$i}", $qualite);
                $processor->setValue("commission_role#{$i}", $role);
            }
        } catch (\Exception $e) {}
    }
}
