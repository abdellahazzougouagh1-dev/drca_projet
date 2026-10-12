START TRANSACTION;

/* Users */
INSERT INTO users (name, email, password, is_admin, email_verified_at, remember_token, created_at, updated_at)
VALUES
('Admin ONCA', 'admin@onca.ma', '$2y$12$RWwzzfFz1azs57UGk.sCbO7VfCPoH7gv5fvoiE4Nd3U8ce/Omwkj6', 1, NOW(), NULL, NOW(), NOW()),
('Agent Marchés', 'agent.marches@onca.ma', '$2y$12$qBEbqJGXTMU8YO.z2xUo5eNDo7MzOr4wsVC0UHitQhv48oiwhidXW', 0, NOW(), NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    is_admin = VALUES(is_admin),
    updated_at = NOW();

/* Fournisseurs */
INSERT INTO fournisseurs (
    raison_sociale, ice, `if`, patente, rc, adresse, ville, telephone, email,
    representant, qualite_representant, cnss, banque, agence_bancaire, rib,
    titulaire_compte, domaine_activite, created_at, updated_at
)
VALUES
('Atlas Bureautique SARL', '001122334455667', '12345678', '987654', '45678', '12 Avenue Mohammed V', 'Rabat', '0522123456', 'contact@atlas-bureautique.ma', 'Amina El Fassi', 'Gérante', 'CNSS-778899', 'Attijariwafa bank', 'Rabat Centre', '0078901234567890123456789', 'Atlas Bureautique SARL', 'Matériel informatique', NOW(), NOW()),
('Nexus Informatique SARL', '001122334455668', '12345679', '987655', '45679', '5 Boulevard Hassan II', 'Casablanca', '0522987654', 'contact@nexus-informatique.ma', 'Youssef Benali', 'Gérant', 'CNSS-778900', 'Bank of Africa', 'Casablanca Marina', '0078901234567890133333333', 'Nexus Informatique SARL', 'Solutions IT', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    raison_sociale = VALUES(raison_sociale),
    `if` = VALUES(`if`),
    patente = VALUES(patente),
    rc = VALUES(rc),
    adresse = VALUES(adresse),
    ville = VALUES(ville),
    telephone = VALUES(telephone),
    email = VALUES(email),
    representant = VALUES(representant),
    qualite_representant = VALUES(qualite_representant),
    cnss = VALUES(cnss),
    banque = VALUES(banque),
    agence_bancaire = VALUES(agence_bancaire),
    rib = VALUES(rib),
    titulaire_compte = VALUES(titulaire_compte),
    domaine_activite = VALUES(domaine_activite),
    updated_at = NOW();

/* Consultations */
INSERT INTO consultations (
    numero_consultation, annee, date_consultation, objet_consultation, description_detaillee,
    categorie, type_prestation, mode_engagement, type_budget, delai_execution, statut_dossier,
    fournisseur_id, date_reunion, heure_reunion, lieu_reunion, president_commission,
    membres_commission, observations_commission, created_at, updated_at
)
VALUES
(
    'CONS-2026-001', 2026, '2026-08-01', 'Achat de matériel informatique',
    'Acquisition de postes de travail et accessoires pour les services centraux.',
    'Fournitures', 'Matériel informatique', 'BC', 'Investissement', 15, 'Programmation',
    (SELECT id FROM fournisseurs WHERE ice = '001122334455667'),
    '2026-08-05', '10:00:00', 'Salle de réunion ONCA', 'Mme Amina El Fassi',
    '["Mme Amina El Fassi","M. Youssef Benali","Mme Salma Idrissi"]',
    'Dossier prêt pour validation.', NOW(), NOW()
),
(
    'CONS-2026-002', 2026, '2026-08-03', 'Maintenance du parc informatique',
    'Contrat de maintenance préventive et corrective sur les équipements existants.',
    'Services', 'Maintenance IT', 'Convention', 'Fonctionnement', 30, 'Programmation',
    (SELECT id FROM fournisseurs WHERE ice = '001122334455668'),
    '2026-08-07', '14:30:00', 'Salle des commissions', 'M. Yassine El Mernissi',
    '["M. Yassine El Mernissi","Mme Sara Bennani","M. Rachid Ait Lahcen"]',
    'À soumettre pour contrôle.', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    annee = VALUES(annee),
    date_consultation = VALUES(date_consultation),
    objet_consultation = VALUES(objet_consultation),
    description_detaillee = VALUES(description_detaillee),
    categorie = VALUES(categorie),
    type_prestation = VALUES(type_prestation),
    mode_engagement = VALUES(mode_engagement),
    type_budget = VALUES(type_budget),
    delai_execution = VALUES(delai_execution),
    statut_dossier = VALUES(statut_dossier),
    fournisseur_id = VALUES(fournisseur_id),
    date_reunion = VALUES(date_reunion),
    heure_reunion = VALUES(heure_reunion),
    lieu_reunion = VALUES(lieu_reunion),
    president_commission = VALUES(president_commission),
    membres_commission = VALUES(membres_commission),
    observations_commission = VALUES(observations_commission),
    updated_at = NOW();

/* Offres */
INSERT INTO offres (consultation_id, fournisseur_id, date_envoi, montant_propose, statut_reponse, created_at, updated_at)
VALUES
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-001'),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455667'),
    '2026-08-02', 180000.00, 'Reçu', NOW(), NOW()
),
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-001'),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455668'),
    '2026-08-02', 185500.00, 'Reçu', NOW(), NOW()
),
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-002'),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455668'),
    '2026-08-04', 65000.00, 'En attente', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    date_envoi = VALUES(date_envoi),
    montant_propose = VALUES(montant_propose),
    statut_reponse = VALUES(statut_reponse),
    updated_at = NOW();

/* Budgets */
INSERT INTO budgets (
    consultation_id, art, par, lig, code_imputation, exercice_budgetaire,
    montant_estimatif_ht, tva, montant_ttc, created_at, updated_at
)
VALUES
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-001'),
    'Art 12', 'Par 03', 'Lig 01', 'INV-IT-2026-001', 2026,
    180000.00, 20.00, 216000.00, NOW(), NOW()
),
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-002'),
    'Art 18', 'Par 02', 'Lig 05', 'FCT-IT-2026-002', 2026,
    65000.00, 20.00, 78000.00, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    art = VALUES(art),
    par = VALUES(par),
    lig = VALUES(lig),
    code_imputation = VALUES(code_imputation),
    exercice_budgetaire = VALUES(exercice_budgetaire),
    montant_estimatif_ht = VALUES(montant_estimatif_ht),
    tva = VALUES(tva),
    montant_ttc = VALUES(montant_ttc),
    updated_at = NOW();

/* AOOs */
INSERT INTO aoos (
    num_aoo, num_aoo_interne, objet, num_decision_nomination, date_lettre,
    date_ouverture, heure_ouverture, lieu_ouverture, nombre_lots, budget, art, par,
    lig, statut, journal_fr, journal_ar, president_commission, membres_commission,
    etat_avancement, created_at, updated_at
)
VALUES
(
    'AOO-2026-001', 'INT-ONCA-001', 'Achat de 50 ordinateurs portables', 'DEC-2026-014', '2026-08-02',
    '2026-08-10', '10:30:00', 'Salle de commission ONCA', 2, 250000.00, 'Art 5', 'Par 1',
    'Lig 2', 'en_preparation', 'Le Matin', 'الأخبار', 'Mme Amina El Fassi',
    '["Mme Amina El Fassi","M. Youssef Benali","Mme Salma Idrissi"]',
    'Dossier en cours de préparation.', NOW(), NOW()
),
(
    'AOO-2026-002', 'INT-ONCA-002', 'Maintenance annuelle des serveurs', 'DEC-2026-015', '2026-08-04',
    '2026-08-12', '09:00:00', 'Salle informatique ONCA', 1, 140000.00, 'Art 8', 'Par 4',
    'Lig 6', 'en_preparation', 'L’Opinion', 'الصباح', 'M. Yassine El Mernissi',
    '["M. Yassine El Mernissi","Mme Sara Bennani","M. Rachid Ait Lahcen"]',
    'En attente de validation.', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    num_aoo_interne = VALUES(num_aoo_interne),
    objet = VALUES(objet),
    num_decision_nomination = VALUES(num_decision_nomination),
    date_lettre = VALUES(date_lettre),
    date_ouverture = VALUES(date_ouverture),
    heure_ouverture = VALUES(heure_ouverture),
    lieu_ouverture = VALUES(lieu_ouverture),
    nombre_lots = VALUES(nombre_lots),
    budget = VALUES(budget),
    art = VALUES(art),
    par = VALUES(par),
    lig = VALUES(lig),
    statut = VALUES(statut),
    journal_fr = VALUES(journal_fr),
    journal_ar = VALUES(journal_ar),
    president_commission = VALUES(president_commission),
    membres_commission = VALUES(membres_commission),
    etat_avancement = VALUES(etat_avancement),
    updated_at = NOW();

/* Lots */
INSERT INTO lots (aoo_id, num_lot, objet_lot, estimation, attributaire_fournisseur_id, created_at, updated_at)
VALUES
(
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-001'),
    'LOT-1', 'Ordinateurs portables 14 pouces', 150000.00,
    (SELECT id FROM fournisseurs WHERE ice = '001122334455667'), NOW(), NOW()
),
(
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-001'),
    'LOT-2', 'Souris, claviers et sacs de transport', 100000.00,
    (SELECT id FROM fournisseurs WHERE ice = '001122334455668'), NOW(), NOW()
),
(
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-002'),
    'LOT-1', 'Maintenance serveurs et sauvegarde', 140000.00,
    (SELECT id FROM fournisseurs WHERE ice = '001122334455668'), NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    objet_lot = VALUES(objet_lot),
    estimation = VALUES(estimation),
    attributaire_fournisseur_id = VALUES(attributaire_fournisseur_id),
    updated_at = NOW();

/* Marchés */
INSERT INTO marches (
    num_marche, aoo_id, lot_id, fournisseur_id, lot, titulaire, objet_marche, qualite_gerant,
    exercice, type_budget, code_budget, intitule_budget, montant, date_signature,
    date_approbation, date_notification_marche, os_numero, os_date_signature, os_date_effet,
    num_decision, date_decision, date_reunion_commission, heure_reunion_commission,
    lieu_reunion_commission, statut, agent_suivi, date_reception_finale, commission_reception,
    created_at, updated_at
)
VALUES
(
    'MARCHE-2026-001',
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-001'),
    (SELECT id FROM lots WHERE num_lot = 'LOT-1' AND aoo_id = (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-001')),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455667'),
    'LOT-1 - Ordinateurs portables',
    'Atlas Bureautique SARL',
    'Achat de matériel informatique',
    'Gérant',
    '2026', 'Investissement', 'INV-IT-2026-001', 'Acquisition matériel informatique',
    145000.00, '2026-08-15', '2026-08-18', '2026-08-19', 'OS-2026-001', '2026-08-20', '2026-08-21',
    'DEC-2026-014', '2026-08-17', '2026-08-16', '10:30:00', 'Salle de commission ONCA',
    'en_cours', 'M. Yassine El Mernissi', '2026-09-30', '["Mme Amina El Fassi","M. Youssef Benali"]',
    NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    lot_id = VALUES(lot_id),
    fournisseur_id = VALUES(fournisseur_id),
    lot = VALUES(lot),
    titulaire = VALUES(titulaire),
    objet_marche = VALUES(objet_marche),
    qualite_gerant = VALUES(qualite_gerant),
    exercice = VALUES(exercice),
    type_budget = VALUES(type_budget),
    code_budget = VALUES(code_budget),
    intitule_budget = VALUES(intitule_budget),
    montant = VALUES(montant),
    date_signature = VALUES(date_signature),
    date_approbation = VALUES(date_approbation),
    date_notification_marche = VALUES(date_notification_marche),
    os_numero = VALUES(os_numero),
    os_date_signature = VALUES(os_date_signature),
    os_date_effet = VALUES(os_date_effet),
    num_decision = VALUES(num_decision),
    date_decision = VALUES(date_decision),
    date_reunion_commission = VALUES(date_reunion_commission),
    heure_reunion_commission = VALUES(heure_reunion_commission),
    lieu_reunion_commission = VALUES(lieu_reunion_commission),
    statut = VALUES(statut),
    agent_suivi = VALUES(agent_suivi),
    date_reception_finale = VALUES(date_reception_finale),
    commission_reception = VALUES(commission_reception),
    updated_at = NOW();

/* =============================== */
/* Scénario 2 - Données ONCA supplémentaires */
/* =============================== */

INSERT INTO fournisseurs (
    raison_sociale, ice, `if`, patente, rc, adresse, ville, telephone, email,
    representant, qualite_representant, cnss, banque, agence_bancaire, rib,
    titulaire_compte, domaine_activite, created_at, updated_at
)
VALUES
('Maroc Services Pro SARL', '001122334455669', '12345680', '987656', '45680', '18 Rue Ibn Sina', 'Fès', '0535123456', 'contact@maroc-services-pro.ma', 'Hind Kadiri', 'Directrice', 'CNSS-778901', 'CIH Bank', 'Fès Ville Nouvelle', '0078901234567890144444444', 'Maroc Services Pro SARL', 'Services généraux', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    raison_sociale = VALUES(raison_sociale),
    `if` = VALUES(`if`),
    patente = VALUES(patente),
    rc = VALUES(rc),
    adresse = VALUES(adresse),
    ville = VALUES(ville),
    telephone = VALUES(telephone),
    email = VALUES(email),
    representant = VALUES(representant),
    qualite_representant = VALUES(qualite_representant),
    cnss = VALUES(cnss),
    banque = VALUES(banque),
    agence_bancaire = VALUES(agence_bancaire),
    rib = VALUES(rib),
    titulaire_compte = VALUES(titulaire_compte),
    domaine_activite = VALUES(domaine_activite),
    updated_at = NOW();

INSERT INTO consultations (
    numero_consultation, annee, date_consultation, objet_consultation, description_detaillee,
    categorie, type_prestation, mode_engagement, type_budget, delai_execution, statut_dossier,
    fournisseur_id, date_reunion, heure_reunion, lieu_reunion, president_commission,
    membres_commission, observations_commission, created_at, updated_at
)
VALUES
(
    'CONS-2026-003', 2026, '2026-08-06', 'Nettoyage des locaux administratifs',
    'Prestation de nettoyage quotidien pour les bâtiments centraux et annexes.',
    'Services', 'Nettoyage', 'Convention', 'Fonctionnement', 90, 'Programmation',
    (SELECT id FROM fournisseurs WHERE ice = '001122334455669'),
    '2026-08-09', '11:00:00', 'Salle de réunion principale', 'Mme Fatima Zahra',
    '["Mme Fatima Zahra","M. Omar Tazi","Mme Imane Berrada"]',
    'Dossier à présenter au comité.', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    annee = VALUES(annee),
    date_consultation = VALUES(date_consultation),
    objet_consultation = VALUES(objet_consultation),
    description_detaillee = VALUES(description_detaillee),
    categorie = VALUES(categorie),
    type_prestation = VALUES(type_prestation),
    mode_engagement = VALUES(mode_engagement),
    type_budget = VALUES(type_budget),
    delai_execution = VALUES(delai_execution),
    statut_dossier = VALUES(statut_dossier),
    fournisseur_id = VALUES(fournisseur_id),
    date_reunion = VALUES(date_reunion),
    heure_reunion = VALUES(heure_reunion),
    lieu_reunion = VALUES(lieu_reunion),
    president_commission = VALUES(president_commission),
    membres_commission = VALUES(membres_commission),
    observations_commission = VALUES(observations_commission),
    updated_at = NOW();

INSERT INTO offres (consultation_id, fournisseur_id, date_envoi, montant_propose, statut_reponse, created_at, updated_at)
VALUES
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-003'),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455669'),
    '2026-08-07', 52000.00, 'Reçu', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    date_envoi = VALUES(date_envoi),
    montant_propose = VALUES(montant_propose),
    statut_reponse = VALUES(statut_reponse),
    updated_at = NOW();

INSERT INTO budgets (
    consultation_id, art, par, lig, code_imputation, exercice_budgetaire,
    montant_estimatif_ht, tva, montant_ttc, created_at, updated_at
)
VALUES
(
    (SELECT id FROM consultations WHERE numero_consultation = 'CONS-2026-003'),
    'Art 22', 'Par 01', 'Lig 09', 'FCT-SERV-2026-003', 2026,
    52000.00, 20.00, 62400.00, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    art = VALUES(art),
    par = VALUES(par),
    lig = VALUES(lig),
    code_imputation = VALUES(code_imputation),
    exercice_budgetaire = VALUES(exercice_budgetaire),
    montant_estimatif_ht = VALUES(montant_estimatif_ht),
    tva = VALUES(tva),
    montant_ttc = VALUES(montant_ttc),
    updated_at = NOW();

INSERT INTO aoos (
    num_aoo, num_aoo_interne, objet, num_decision_nomination, date_lettre,
    date_ouverture, heure_ouverture, lieu_ouverture, nombre_lots, budget, art, par,
    lig, statut, journal_fr, journal_ar, president_commission, membres_commission,
    etat_avancement, created_at, updated_at
)
VALUES
(
    'AOO-2026-003', 'INT-ONCA-003', 'Prestations de nettoyage des locaux', 'DEC-2026-016', '2026-08-06',
    '2026-08-14', '11:15:00', 'Salle polyvalente ONCA', 1, 52000.00, 'Art 22', 'Par 1',
    'Lig 9', 'en_preparation', 'Al Bayane', 'العلم', 'Mme Fatima Zahra',
    '["Mme Fatima Zahra","M. Omar Tazi","Mme Imane Berrada"]',
    'Préparation du dossier de consultation.', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    num_aoo_interne = VALUES(num_aoo_interne),
    objet = VALUES(objet),
    num_decision_nomination = VALUES(num_decision_nomination),
    date_lettre = VALUES(date_lettre),
    date_ouverture = VALUES(date_ouverture),
    heure_ouverture = VALUES(heure_ouverture),
    lieu_ouverture = VALUES(lieu_ouverture),
    nombre_lots = VALUES(nombre_lots),
    budget = VALUES(budget),
    art = VALUES(art),
    par = VALUES(par),
    lig = VALUES(lig),
    statut = VALUES(statut),
    journal_fr = VALUES(journal_fr),
    journal_ar = VALUES(journal_ar),
    president_commission = VALUES(president_commission),
    membres_commission = VALUES(membres_commission),
    etat_avancement = VALUES(etat_avancement),
    updated_at = NOW();

INSERT INTO lots (aoo_id, num_lot, objet_lot, estimation, attributaire_fournisseur_id, created_at, updated_at)
VALUES
(
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-003'),
    'LOT-1', 'Nettoyage des bureaux et parties communes', 52000.00,
    (SELECT id FROM fournisseurs WHERE ice = '001122334455669'), NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    objet_lot = VALUES(objet_lot),
    estimation = VALUES(estimation),
    attributaire_fournisseur_id = VALUES(attributaire_fournisseur_id),
    updated_at = NOW();

INSERT INTO marches (
    num_marche, aoo_id, lot_id, fournisseur_id, lot, titulaire, objet_marche, qualite_gerant,
    exercice, type_budget, code_budget, intitule_budget, montant, date_signature,
    date_approbation, date_notification_marche, os_numero, os_date_signature, os_date_effet,
    num_decision, date_decision, date_reunion_commission, heure_reunion_commission,
    lieu_reunion_commission, statut, agent_suivi, date_reception_finale, commission_reception,
    created_at, updated_at
)
VALUES
(
    'MARCHE-2026-002',
    (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-003'),
    (SELECT id FROM lots WHERE num_lot = 'LOT-1' AND aoo_id = (SELECT id FROM aoos WHERE num_aoo = 'AOO-2026-003')),
    (SELECT id FROM fournisseurs WHERE ice = '001122334455669'),
    'LOT-1 - Nettoyage bureaux',
    'Maroc Services Pro SARL',
    'Prestations de nettoyage des locaux',
    'Directrice',
    '2026', 'Fonctionnement', 'FCT-SERV-2026-003', 'Entretien et nettoyage',
    52000.00, '2026-08-20', '2026-08-23', '2026-08-24', 'OS-2026-002', '2026-08-25', '2026-08-26',
    'DEC-2026-016', '2026-08-22', '2026-08-21', '11:15:00', 'Salle polyvalente ONCA',
    'attribue', 'Mme Imane Berrada', '2026-10-05', '["Mme Fatima Zahra","M. Omar Tazi"]',
    NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    lot_id = VALUES(lot_id),
    fournisseur_id = VALUES(fournisseur_id),
    lot = VALUES(lot),
    titulaire = VALUES(titulaire),
    objet_marche = VALUES(objet_marche),
    qualite_gerant = VALUES(qualite_gerant),
    exercice = VALUES(exercice),
    type_budget = VALUES(type_budget),
    code_budget = VALUES(code_budget),
    intitule_budget = VALUES(intitule_budget),
    montant = VALUES(montant),
    date_signature = VALUES(date_signature),
    date_approbation = VALUES(date_approbation),
    date_notification_marche = VALUES(date_notification_marche),
    os_numero = VALUES(os_numero),
    os_date_signature = VALUES(os_date_signature),
    os_date_effet = VALUES(os_date_effet),
    num_decision = VALUES(num_decision),
    date_decision = VALUES(date_decision),
    date_reunion_commission = VALUES(date_reunion_commission),
    heure_reunion_commission = VALUES(heure_reunion_commission),
    lieu_reunion_commission = VALUES(lieu_reunion_commission),
    statut = VALUES(statut),
    agent_suivi = VALUES(agent_suivi),
    date_reception_finale = VALUES(date_reception_finale),
    commission_reception = VALUES(commission_reception),
    updated_at = NOW();

COMMIT;