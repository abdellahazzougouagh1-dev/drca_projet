@php
    // Le marché adapte ses données au modèle unique de fiche d'engagement des BC.
    // Ainsi, la présentation reste identique et seules les données métier changent.
    $notificationLigne = $marche->notificationLigne ?? ($marche->aoo?->notificationLigne);
    $article = $notificationLigne?->article ?? $marche->article_budget;
    $paragraphe = $notificationLigne?->paragraphe ?? $marche->paragraphe_budget;
    $ligne = $notificationLigne?->ligne_budgetaire ?? $marche->ligne_budget;
    $beneficiaire = $marche->fournisseur?->raison_sociale ?: $marche->titulaire;
    $reference = $marche->reference_engagement ?: $marche->num_marche;
    $numeroEngagement = $marche->num_engagement ?: ($marche->id . '/' . ($marche->exercice ?: date('Y')) . '/FE/DRCA-RSK');
    $dateEngagement = $marche->date_engagement ?: $marche->date_signature;

    $consultation = (object) [
        'type_budget' => $marche->type_budget ?: 'Fonctionnement',
        'annee' => $marche->exercice ?: date('Y'),
        'numero_bc' => $numeroEngagement,
        'objet_consultation' => $marche->objet_marche ?: ($marche->aoo?->objet ?? ''),
        'fournisseur' => (object) ['raison_sociale' => $beneficiaire],
        'budget' => (object) ['art' => $article, 'par' => $paragraphe, 'lig' => $ligne],
    ];

    $documentData = [
        'numero_engagement' => $numeroEngagement,
        'date_document' => $dateEngagement?->format('Y-m-d'),
        'forme_engagement' => $marche->forme_engagement ?: 'Marché',
        'objet' => $consultation->objet_consultation,
        'societe' => $beneficiaire,
        'montant_ttc' => $marche->montant,
        'montant_depense_neuf' => $marche->montant,
        'art' => $article,
        'par' => $paragraphe,
        'lig' => $ligne,
        'credit_budget_cp' => $notificationLigne
            ? (float) ($notificationLigne->reports ?? 0) + (float) ($notificationLigne->credits_neufs ?? 0)
            : $marche->credit_budget_cp,
        'credit_budget_ce' => $notificationLigne?->credits_engagements ?? $marche->credit_budget_ce,
        'depenses_engagees_cp' => $notificationLigne?->credits_engages ?? $marche->depenses_engagees_cp,
        'depenses_engagees_ce' => $marche->depenses_engagees_ce,
        'engagement_propose_cp' => $marche->engagement_propose_cp ?: $marche->montant_total_engagement,
        'pieces_jointes' => $marche->pieces_jointes ?: ('Marché N° ' . $reference),
    ];

    $doc = [
        'titulaire_nom' => $beneficiaire,
        'total_ttc' => $marche->montant,
        'forme_engagement' => $marche->forme_engagement ?: 'Marché',
        'pieces_jointes' => $documentData['pieces_jointes'],
    ];
@endphp

@include('documents.fiche_engagement', compact('consultation', 'documentData', 'doc'))
