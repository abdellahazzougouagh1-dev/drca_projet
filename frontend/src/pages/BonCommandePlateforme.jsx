import { useMemo, useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import api from '../api/axios';
import * as XLSX from 'xlsx';
import UserMenu from '../components/UserMenu';
import {
  ArrowLeft,
  ArrowRight,
  CheckCircle2,
  ClipboardList,
  Download,
  FileArchive,
  FileText,
  Loader2,
  Plus,
  Save,
  Trash2,
  Users,
  Eye,
  Calendar,
  AlertCircle,
  Building2,
  Edit3,
  X,
  Check,
  Search,
  FileSpreadsheet,
  Menu,
} from 'lucide-react';

// ---------------------------------------------------------------------------
// Définition des 3 phases du processus Bon de Commande
// ---------------------------------------------------------------------------
const phases = [
  {
    id: 'consultation',
    label: 'Consultation',
    icon: FileText,
    description: 'Publication, décision de commission et ouverture des plis.',
  },
  {
    id: 'engagement',
    label: 'Engagement',
    icon: ClipboardList,
    description: 'Bon de commande et Ordre de commande / Accusé de réception (fusionnés).',
  },
  {
    id: 'liquidation',
    label: 'Liquidation',
    icon: Users,
    description: 'Commission de réception et procès verbal de réception.',
  },
  {
    id: 'Ordonnancement',
    label: 'Ordonnancement',
    icon: FileArchive,
    description: 'Bulletin de décompte, Ordre d\'Imputation, Ordre de Paiement et Ordre de Virement.',
  },
];

// Organisation des 10 documents par phase
const documentsByPhase = {
  consultation: [
    {
      step: 1,
      id: 'avis_achat',
      title: "Avis d'achat",
      data: ['Objet de la commande', 'Structure acheteuse', 'Conditions de participation', 'Date limite des offres'],
    },
    {
      step: 2,
      id: 'decision_commission_ouverture',
      title: "Décision de la commission d'ouverture",
      data: ['Référence de consultation', 'Président, rapporteur et membres', 'Date, heure et lieu'],
    },
    {
      step: 3,
      id: 'pv_ouverture_attribution',
      title: "PV d'ouverture",
      data: ['Membres de commission', 'Concurrents & montants', 'Société retenue', 'Heure & date de séance'],
    },
  ],
  engagement: [
    {
      step: 4,
      id: 'bon_commande',
      title: 'Bon de commande',
      data: ['Coordonnées des parties', 'Prestations, quantités, prix', 'Délais et lieu', 'RIB fournisseur'],
    },
    {
      step: 5,
      id: 'ordre_commande',
      title: 'Ordre de service / Accusé de réception',
      data: ['Référence du bon de commande', "Date d'effet / notification", 'Validation & Accusé de réception'],
    },
    {
      step: 6,
      id: 'fiche_engagement',
      title: "Fiche d'Engagement Budgétaire",
      data: ["N° d'engagement", 'Montant TTC & intérêt moratoire', 'Imputation budgétaire', 'Bénéficiaire'],
    },
  ],
  liquidation: [
    {
      step: 6,
      id: 'decision_commission_reception',
      title: 'Décision commission de réception',
      data: ['Agents de vérification', 'Périmètre de mission', 'Référence du BC', 'Signature direction'],
    },
    {
      step: 7,
      id: 'pv_reception',
      title: 'PV de réception',
      data: ['Service fait', 'Inventaire contradictoire', 'Réserves éventuelles', 'Signatures'],
    },
  ],
  Ordonnancement: [
    {
      step: 8,
      id: 'ordre_imputation',
      title: "Ordre d'Imputation (OI)",
      data: ['N° OI', 'Bénéficiaire & montant', 'Imputation comptable Débit/Crédit', 'Visa sous-ordonnateur'],
    },
    {
      step: 9,
      id: 'ordre_paiement',
      title: 'Ordre de Paiement (OP)',
      data: ['N° OP', 'Bénéficiaire & RIB', 'Pièces jointes', 'Imputation comptable & visa'],
    },
    {
      step: 10,
      id: 'ordre_virement',
      title: 'Ordre de Virement (OV)',
      data: ['N° OV', 'Compte à débiter', 'Bénéficiaire & RIB', 'Signatures Directeur & Fondé de pouvoirs'],
    },
  ],
};

const documentFieldGroups = {
  avis_achat: [
    { name: 'numero_consultation', label: "Numéro d'avis d'achat / Consultation", placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'numero_bc', label: 'Numéro de bon de commande', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Achat de matériel de valorisation des produits agricoles', required: true, fromDb: true },
    { name: 'consistance_lignes', label: 'Bordereaux des prix', type: 'consistance_table', required: true, fromDb: true },
    { name: 'lieu_execution', label: "Lieu d'exécution", placeholder: 'REGION DE RABAT SALE KENITRA', required: true, fromDb: true },
    { name: 'delai_livraison', label: 'Délai de livraison (en jours)', placeholder: '90', type: 'number', required: true, fromDb: true },
    { name: 'date_limite', label: 'Date limite de réception des devis', type: 'date', required: true, fromDb: true },
    { name: 'heure_limite', label: 'Heure limite de réception des devis', type: 'time', required: true, fromDb: true },
    { name: 'date_document', label: 'Date avis_achat', type: 'date', required: true },
  ],
  decision_commission_ouverture: [
    { name: 'numero_consultation', label: 'Numéro consultation', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'numero_decision', label: "Numéro de la décision de la commission d'ouverture", placeholder: '06/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la consultation', placeholder: 'Prestations topographiques...', required: true, fromDb: true },
    { name: 'membres_commission', label: 'Composition de la commission (Président & Membres)', type: 'commission_selector', required: true },
    { name: 'date_reunion', label: "Date séance d'ouverture", type: 'date', required: true },
    { name: 'heure_reunion', label: 'Heure de séance', type: 'time', required: true },
    { name: 'lieu_reunion', label: 'Lieu de séance', placeholder: 'Siège DRCA Rabat-Salé-Kénitra', required: true },
    { name: 'date_document', label: 'Date de la décision', type: 'date', required: true },
  ],
  pv_ouverture_attribution: [
    { name: 'numero_consultation', label: "Numéro d'avis d'achat", placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'date_reunion', label: "Date d'ouverture des devis", type: 'date', required: true },
    { name: 'heure_reunion', label: "Heure d'ouverture", type: 'time', required: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Prestations topographiques...', required: true, fromDb: true },
    { name: 'concurrents', label: 'Synthése des devis reçus', type: 'concurrents_table', required: true },
    { name: 'societes_refusees', label: "Sociétés écartées", type: 'societes_refusees_input', required: true },
    { name: 'attributaire', label: 'Société retenue ', type: 'attributaire_selector', required: true },
    { name: 'montant_apres_verification', label: 'Montant TTC retenu (DH)', type: 'number', placeholder: '39060', required: true },
    { name: 'heure_fin', label: 'Heure de fin de séance', type: 'time', required: true },
    { name: 'date_document', label: 'Date Pv', type: 'date', required: true },
    { name: 'membres_commission', label: 'Membres de la commission', type: 'commission_selector', required: true },
  ],
  bon_commande: [
    { name: 'numero_bc', label: 'Numéro bon de commande', placeholder: '05/INV/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Achat de matériel de démonstration', required: true, fromDb: true },
    { name: 'titulaire_nom', label: 'Titulaire / Raison sociale', placeholder: 'COMPTOIR COMMERCIAL DE DISTRIBUTION ET D\'EXPLOITATION', required: true, fromDb: true },
    { name: 'art', label: 'Article Imputation (ART)', placeholder: '415', required: true, fromDb: true },
    { name: 'par', label: 'Paragraphe Imputation (PAR)', placeholder: '30', required: true, fromDb: true },
    { name: 'lig', label: 'Ligne Imputation (LIG)', placeholder: '60', required: true, fromDb: true },
    { name: 'intitule', label: 'Intitulé / Nature de la prestation', placeholder: 'Achat de matériel technique, de logiciels et de matériel informatique', required: true, fromDb: true },
    { name: 'adresse_societe', label: 'Adresse / Domicile de la société', placeholder: 'Ex: N° 12 Avenue Hassan II, Kénitra', required: true, fromDb: true },
    { name: 'patente', label: 'Patente', placeholder: 'Ex: 34255474', required: true, fromDb: true },
    { name: 'cnss', label: 'CNSS', placeholder: 'Ex: 5614814', required: true, fromDb: true },
    { name: 'ice', label: 'ICE', placeholder: 'Ex: 001964167000010 (15 chiffres)', required: true, fromDb: true },
    { name: 'if', label: 'Identifiant fiscal (IF)', placeholder: 'Ex: 4025112 (5 ou 8 chiffres)', required: true, fromDb: true },
    { name: 'rib', label: 'RIB / Compte bancaire', placeholder: 'Ex: 022010000342002749835925 (24 chiffres)', required: true, fromDb: true },
    { name: 'consistance_lignes', label: 'Borderaux des Prix ', type: 'consistance_table', required: true, fromDb: true },
    { name: 'date_document', label: 'Date Bon de commande', type: 'date', required: true },
  ],

  fiche_engagement: [
    { name: 'numero_engagement', label: "N° Fiche d'Engagement", placeholder: '02/2024/FE/DRCA-RSK', required: true },
    { name: 'date_document', label: "Date de visa / engagement", type: 'date', required: true },
    { name: 'credit_ouvert_cp', label: 'Crédit ouvert CP', type: 'number', required: false },
    { name: 'credit_ouvert_ce', label: 'Crédit ouvert CE', type: 'number', required: false },
    { name: 'depenses_anterieures_ce', label: 'Dépenses engagées antérieurement CE', type: 'number', required: false },
    { name: 'depenses_anterieures_cp', label: 'Dépenses engagées antérieurement CP', type: 'number', required: false },
    { name: 'depenses_credits_engagement', label: "Dépenses sur crédits d'engagements", type: 'number', required: false },
    { name: 'depenses_rap', label: 'Dépenses sur reste à payer', type: 'number', required: false },
    { name: 'montant_depense_neuf', label: 'Montant de la dépense neuf', type: 'number', required: false },
    { name: 'interets_moratoires', label: 'Intérêts moratoires 1 %', type: 'number', required: false, readOnly: true },
    { name: 'montant_engager_neuf', label: 'Montant à engager neuf', type: 'number', required: false, readOnly: true },
  ],
  ordre_commande: [
    {
      name: 'nature_os',
      label: "Nature de l'ordre de service",
      type: 'select',
      options: [
        "Commencement de l'exécution",
        "Reprise de l'exécution",
        "Ajournement de l'exécution"
      ],
      required: true
    },
    { name: 'motif_ajournement', label: "Motif de l'ajournement (si ajournement)", placeholder: 'Planification et disponibilité des agriculteurs', required: true, condition: (form) => form?.nature_os === "Ajournement de l'exécution" },
    { name: 'numero_lettre', label: "Numéro d'ordre de service / lettre", placeholder: '11/2024/DRCA-RSK/OS', required: true },
    { name: 'date_document', label: 'Date du document', type: 'date', required: true },
    { name: 'numero_consultation', label: ' Consultation N°', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'numero_bc', label: 'Bon de commande N°', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'societe', label: 'Nom de la société ', placeholder: 'DESTIN FLOTTE', required: true, fromDb: true },
    { name: 'adresse_societe', label: 'Adresse de Société', placeholder: 'Ex: N° 12 Avenue Hassan II, Kénitra', required: true, fromDb: true },
    { name: 'ville_societe', label: 'Ville', placeholder: 'Rabat', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Prestation de transport des agriculteurs...', required: true, fromDb: true },
  ],
  accuse_reception: [
    { name: 'nature_os', label: "Nature de l'ordre de service", placeholder: "Notification de l'approbation", required: true },
    { name: 'numero_lettre', label: "Numéro d'ordre de service ", placeholder: '11/2024/DRCA-RSK/OS', required: true },
    { name: 'date_document', label: 'Date du document ', type: 'date', required: true },
    { name: 'numero_consultation', label: 'Convention / Consultation N°', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'numero_bc', label: 'Bon de commande N°', placeholder: '04/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'societe', label: 'Nom de la société / Prestataire', placeholder: 'DESTIN FLOTTE', required: true, fromDb: true },
    { name: 'adresse_societe', label: 'Adresse / Domicile de la société', placeholder: 'Ex: N° 12 Avenue Hassan II, Kénitra', required: true, fromDb: true },
    { name: 'ville_societe', label: 'Ville', placeholder: 'Rabat', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Prestation de transport des agriculteurs...', required: true, fromDb: true },
  ],
  decision_commission_reception: [
    { name: 'numero_decision', label: 'Numéro de décision', placeholder: 'Ex: 05/DR/2024', required: true },
    { name: 'type_reception', label: 'Type de réception', type: 'select', options: ['définitive', 'provisoire', 'partielle'], required: true },
    { name: 'date_reception_definitive', label: 'Date de la réception définitive', type: 'date', required: true, condition: (form) => !form?.type_reception || form?.type_reception?.toLowerCase() === 'définitive' },
    { name: 'date_document', label: 'Date de la décision', type: 'date', required: true },
    { name: 'numero_bc', label: 'Numéro Bon de commande', placeholder: '09/INV/2023/DRCA-RSK', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Achat de matériel...', required: true, fromDb: true },
    { name: 'membres_commission', label: 'Membres de la commission de réception', type: 'commission_selector', required: true },
    { name: 'date_reunion', label: 'Date de la séance de réception', type: 'date', required: true },
    { name: 'heure_reunion', label: 'Heure de la séance', type: 'time', required: true },
  ],
  pv_reception: [
    { name: 'numero_bc', label: 'Numéro bon de commande', placeholder: '03/INV/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'numero_decision', label: 'Numéro de décision de réception', placeholder: 'Ex: 05/DR/2024', required: true, fromDb: true },
    { name: 'type_reception', label: 'Type de réception', type: 'select', options: ['définitive', 'provisoire', 'partielle'], required: true, fromDb: true },
    { name: 'date_reception_definitive', label: 'Date de la réception définitive', type: 'date', required: true, condition: (form) => !form?.type_reception || form?.type_reception?.toLowerCase() === 'définitive' },
    { name: 'periode_du', label: 'Période de réception (provisoire/partielle) - Du', type: 'date', required: true, condition: (form) => ['provisoire', 'partielle'].includes(form?.type_reception?.toLowerCase()) },
    { name: 'periode_au', label: 'Période de réception (provisoire/partielle) - Au', type: 'date', required: true, condition: (form) => ['provisoire', 'partielle'].includes(form?.type_reception?.toLowerCase()) },
    { name: 'date_decision', label: 'Date de la décision de reception', type: 'date', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: 'Prestations...', required: true, fromDb: true },
    { name: 'societe', label: 'Nom de titulaire', placeholder: 'BUREAU ALAOUI TOPO', required: true, fromDb: true },
    { name: 'adresse_societe', label: 'Adresse de Titulaire', placeholder: 'APP N 6 Immeuble 01', required: true, fromDb: true },
    { name: 'ville_societe', label: 'Ville', placeholder: 'KHENIFRA', required: true, fromDb: true },
    { name: 'membres_commission', label: 'Membres de la commission de réception', type: 'commission_selector', required: true, fromDb: true },
    { name: 'date_reunion', label: 'Date de séance de réception', type: 'date', required: true, fromDb: true },
    { name: 'heure_reunion', label: 'Heure de séance', type: 'time', required: true },
    { name: 'heure_fin', label: 'Heure de fin de séance', type: 'time', required: true },
    { name: 'date_document', label: 'date de pv de reception', type: 'date', required: true },
    { name: 'prestations_receptionnees', label: 'Détails des prestations (Réception Provisoire / Partielle)', type: 'prestations_partielles_table', required: true, condition: (form) => ['provisoire', 'partielle'].includes(form?.type_reception?.toLowerCase()), fromDb: true },
  ],

  // ── ORDONNANCEMENT ──────────────────────────────────────────────────────────
  ordre_imputation: [
    { name: 'numero_oi', label: "N° Ordre d'Imputation (OI)", placeholder: '38', required: true },
    { name: 'date_document', label: "Date de l'ordre d'imputation", type: 'date', required: true },
    { name: 'annee_origine', label: "Année d'origine", placeholder: String(new Date().getFullYear()), type: 'number', required: true },
    { name: 'credit_type', label: 'Type de crédit', type: 'select', options: ['Crédit Consolidés', 'Crédit Ouvert', 'Report'], required: true },
    { name: 'societe', label: 'Bénéficiaire', placeholder: 'PLANIFICATION INGENIEUR CONSEIL', required: true, fromDb: true },
    { name: 'objet', label: 'Objet de la prestation', placeholder: "Organisation de voyages d'agriculteurs...", required: true, fromDb: true },
    { name: 'forme_engagement', label: "Forme d'engagement", type: 'select', options: ['Bon de commande', 'Marché'], required: true },
    { name: 'reference_bc', label: 'Référence (N° BC / Marché)', placeholder: 'Marché N°06/2024/DRCA-RSK', required: true, fromDb: true },
    { name: 'montant_oi', label: 'Montant (DH)', type: 'number', placeholder: '98700.60', required: true, fromDb: true },
    { name: 'mode_reglement', label: 'Mode de règlement', type: 'select', options: ['Virement', 'Chèque', 'Espèces'], required: true },
    { name: 'compte_debit', label: 'N° Compte Débit', placeholder: '3103301006024701547601 81', required: true },
    { name: 'libelle_debit', label: 'Libellé Débit (ex: T.P KENITRA)', placeholder: 'T.P KENITRA', required: true },
    { name: 'compte_credit', label: 'N° Compte Crédit', placeholder: '021 825 000 027 403 003 150 252', required: true },
    { name: 'art', label: 'ART', placeholder: '415', required: true, fromDb: true },
    { name: 'par', label: 'PAR', placeholder: '20', required: true, fromDb: true },
    { name: 'lig', label: 'LIG (LIGNE)', placeholder: '14', required: true, fromDb: true },
    { name: 'intitule_rubrique', label: 'Intitulé de la rubrique', placeholder: 'Frais de voyage des agriculteurs et techniciens', required: true, fromDb: true },
  ],

  ordre_paiement: [
    { name: 'numero_op', label: "N° Ordre de Paiement (OP)", placeholder: '6', required: true },
    { name: 'date_document', label: "Date de l'ordre de paiement", type: 'date', required: true },
    { name: 'credit_type', label: 'Type de crédit', type: 'select', options: ['Crédit Consolidés', 'Crédit Ouvert', 'Report'], required: true },
    { name: 'exercice_origine', label: "Exercice d'origine", placeholder: String(new Date().getFullYear() - 1), type: 'number', required: true },
    { name: 'societe', label: 'Bénéficiaire', placeholder: 'VERDA DRIP', required: true, fromDb: true },
    { name: 'adresse_societe', label: 'Adresse du bénéficiaire', placeholder: '59 AV MOULAY ABDELAZIZ, RCE MLY ABDELAZIZ, BUR 4, Kénitra', required: true, fromDb: true },
    { name: 'rib', label: 'RIB N°', placeholder: '011 330 000 006 210 000 814 386', required: true, fromDb: true },
    { name: 'banque', label: 'Banque', placeholder: 'BMCE BANK', required: true, fromDb: true },
    { name: 'objet', label: 'Objet (OBJET)', placeholder: 'ACQUISITIONS DES INTRANTS AGRICOLES...', required: true, fromDb: true },
    { name: 'reference_bc', label: 'Référence (N° BC / Marché)', placeholder: 'Marché N°04/2023/DRCA-RSK', required: true, fromDb: true },
    { name: 'montant_op', label: 'Montant à payer (DH)', type: 'number', placeholder: '79573.63', required: true, fromDb: true },
    { name: 'montant_engagement', label: 'Montant engagement total (DH)', type: 'number', placeholder: '159400.48', required: true, fromDb: true },
    { name: 'pieces_jointes_op', label: 'Pièces jointes (liste)', placeholder: 'Facture N°005/2023 Du 02/05/2024\nDécompte provisoire N°2 et dernier\nPV de réception définitif du 03/05/2024\nOrdre de service d\'ajournement\nOrdre de service de reprise', type: 'textarea', required: true },
    { name: 'art', label: 'Chap / ART', placeholder: '415', required: true, fromDb: true },
    { name: 'par', label: 'Parag (PAR)', placeholder: '20', required: true, fromDb: true },
    { name: 'lig', label: 'Ligne (LIG)', placeholder: '13', required: true, fromDb: true },
    { name: 'intitule_rubrique', label: 'Intitulé de la rubrique', placeholder: 'Essais de démonstration et achat des intrants pour FFS', required: true, fromDb: true },
    { name: 'prestation_meme_nature', label: 'Prestation de même nature', placeholder: 'PRESTATION DE MEME NATURE', required: false },
  ],

  ordre_virement: [
    { name: 'numero_ov', label: "N° Ordre de Virement (OV)", placeholder: '41', required: true },
    { name: 'numero_op', label: 'N° OP associé', placeholder: '48', required: true, fromDb: true },
    { name: 'date_document', label: "Date de l'ordre de virement", type: 'date', required: true },
    { name: 'compte_courant', label: 'N° Compte courant à débiter (ONCA)', placeholder: '31033010060247015476 0181', required: true },
    { name: 'libelle_compte', label: 'Intitulé du compte (ONCA DR)', placeholder: 'ONCA DR RABAT-SALE-KENITRA INVESTISSEMENT', required: true },
    { name: 'montant_ov', label: 'La somme de (DH)', type: 'number', placeholder: '98700.60', required: true, fromDb: true },
    { name: 'societe', label: 'Au profit de (Bénéficiaire)', placeholder: 'PLANIFICATION INGENIEUR CONSEIL', required: true, fromDb: true },
    { name: 'rib', label: 'RIB (Titulaire du compte)', placeholder: '021 825 000 027 403 003 150 252', required: true, fromDb: true },
    { name: 'reference_bc', label: 'Pour fin de règlement de (Référence)', placeholder: 'Marché N°06/2024/DRCA-RSK', required: true, fromDb: true },
  ],
};

const emptyLine = () => ({
  designation: '',
  unite: 'Unite',
  quantite: 1,
  prix_unitaire_ht: 0,
  tva: 20,
});

const fieldMaxLengthMap = {
  rib: 24,
  ice: 15,
  if: 8,
  cnss: 10,
  patente: 12,
  art: 4,
  par: 4,
  lig: 4,
};

const allDocuments = Object.values(documentsByPhase).flat();

const BonCommandePlateforme = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const currentYear = new Date().getFullYear();

  const [activePhase, setActivePhase] = useState(() => {
    const searchParams = new URLSearchParams(window.location.search);
    return searchParams.get('phase') || localStorage.getItem('drca_active_phase') || 'consultation';
  });

  const [registreBudget, setRegistreBudget] = useState('Investissement');

  const [selectedDocumentId, setSelectedDocumentId] = useState(() => {
    const searchParams = new URLSearchParams(window.location.search);
    return searchParams.get('doc') || localStorage.getItem('drca_selected_doc_id') || null;
  });

  // Formulaire minimal de création (phase Consultation)
  const [formData, setFormData] = useState({
    numero_consultation: '',
    numero_decision: '',
    numero_bc: '',
    date_limite_devis: '',
    heure_limite_devis: '',
    lieu_execution: '',
    annee: currentYear,
    date_consultation: new Date().toISOString().slice(0, 10),
    objet_consultation: '',
    description_detaillee: '',
    categorie: '',
    type_prestation: '',
    type_budget: '',
    delai_execution: '',
    art: '',
    par: '',
    lig: '',
    code_imputation: '',
    exercice_budgetaire: currentYear,
  });
  const [prestations, setPrestations] = useState([emptyLine()]);
  const [savedConsultation, setSavedConsultation] = useState(null);
  const [saving, setSaving] = useState(false);
  const [downloading, setDownloading] = useState(null);
  const [documentForms, setDocumentForms] = useState({});
  const [formErrors, setFormErrors] = useState({});
  const [savedDocumentIds, setSavedDocumentIds] = useState(new Set()); // documents enregistrés en BD
  const [searchingBonCommande, setSearchingBonCommande] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  // Synchronisation des états actifs dans le localStorage
  useEffect(() => {
    if (savedConsultation?.id) {
      localStorage.setItem('drca_selected_consultation_id', String(savedConsultation.id));
    }
  }, [savedConsultation]);

  useEffect(() => {
    if (savedConsultation?.prestations && Array.isArray(savedConsultation.prestations) && savedConsultation.prestations.length > 0) {
      setPrestations(savedConsultation.prestations.map((p) => ({
        id: p.id,
        numero_prix: p.numero_prix || 1,
        designation: p.designation || '',
        unite: p.unite || p.unite_mesure || 'Unite',
        quantite: p.quantite || 1,
        prix_unitaire_ht: p.prix_unitaire_ht || 0,
        tva: p.tva || 20,
      })));
    }
  }, [savedConsultation]);

  useEffect(() => {
    if (activePhase) {
      localStorage.setItem('drca_active_phase', activePhase);
    }
  }, [activePhase]);

  useEffect(() => {
    if (selectedDocumentId) {
      localStorage.setItem('drca_selected_doc_id', selectedDocumentId);
    } else {
      localStorage.removeItem('drca_selected_doc_id');
    }
  }, [selectedDocumentId]);

  // --- Base de consultations (liste) ---
  const [consultationsList, setConsultationsList] = useState([]);
  const [consultationsLoading, setConsultationsLoading] = useState(false);
  const [consultationsError, setConsultationsError] = useState('');
  const [consultationSearchTerm, setConsultationSearchTerm] = useState('');

  const filteredConsultationsList = useMemo(() => {
    if (!consultationSearchTerm.trim()) return consultationsList;
    const term = consultationSearchTerm.toLowerCase().trim();
    return consultationsList.filter(
      (c) =>
        (c.numero_consultation && c.numero_consultation.toLowerCase().includes(term)) ||
        (c.objet_consultation && c.objet_consultation.toLowerCase().includes(term)) ||
        (c.annee && String(c.annee).includes(term)) ||
        (c.statut_dossier && c.statut_dossier.toLowerCase().includes(term))
    );
  }, [consultationsList, consultationSearchTerm]);

  // --- Référentiel des membres de la commission ---
  const [membresCatalog, setMembresCatalog] = useState([]);
  const [nouveauMembreForm, setNouveauMembreForm] = useState({ membre_id: '', qualite: 'Président' });
  const [isQuickMembreModalOpen, setIsQuickMembreModalOpen] = useState(false);
  const [quickMembreData, setQuickMembreData] = useState({ nom_prenom: '', fonction: '' });
  const [quickMembreSaving, setQuickMembreSaving] = useState(false);
  const [quickMembreError, setQuickMembreError] = useState(null);

  // --- Modal de modification de consultation ---
  const [isEditConsultationModalOpen, setIsEditConsultationModalOpen] = useState(false);
  const [editingConsultationData, setEditingConsultationData] = useState(null);
  const [editingPrestations, setEditingPrestations] = useState([]);
  const [editingSaving, setEditingSaving] = useState(false);
  const [editingError, setEditingError] = useState('');
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  const fetchMembresCatalog = async () => {
    try {
      const res = await api.get('/commission-membres');
      setMembresCatalog(Array.isArray(res.data) ? res.data : []);
    } catch (e) {
      console.error('Erreur lors du chargement des membres de la commission:', e);
    }
  };

  useEffect(() => {
    const fetchConsultations = async () => {
      setConsultationsLoading(true);
      try {
        const res = await api.get('/consultations');
        const sorted = Array.isArray(res.data) ? [...res.data].sort((a, b) => b.id - a.id) : [];
        setConsultationsList(sorted);
        setConsultationsError('');

        // Déterminer la consultation à sélectionner (state router, URL query param, ou localStorage)
        const searchParams = new URLSearchParams(location.search);
        const urlConsultationId = searchParams.get('consultation_id') || searchParams.get('consultationId');
        const targetId = location.state?.autoSelectId || urlConsultationId || localStorage.getItem('drca_selected_consultation_id');
        const targetEditId = location.state?.autoEditId || (searchParams.get('edit') === '1' ? urlConsultationId : null);

        if (targetId) {
          const found = sorted.find((c) => String(c.id) === String(targetId));
          if (found) {
            setSavedConsultation(found);
            localStorage.setItem('drca_selected_consultation_id', String(found.id));
            const retrievedIntitule = found.intitule || found.type_prestation || found.objet_consultation;
          if (retrievedIntitule) {
            handleDocumentFieldChange('bon_commande', 'intitule', retrievedIntitule);
          }
        }

        if (targetEditId) {
          const consultationToEdit = sorted.find((c) => String(c.id) === String(targetEditId));
          if (consultationToEdit) openEditConsultationModal(consultationToEdit);
        }
        }
      } catch (err) {
        setConsultationsError("Impossible de charger la liste des consultations.");
      } finally {
        setConsultationsLoading(false);
      }
    };
    fetchConsultations();
    fetchMembresCatalog();
  }, [location.state, location.search]);

  const handleQuickMembreSubmit = async (e) => {
    e.preventDefault();
    setQuickMembreSaving(true);
    setQuickMembreError(null);
    try {
      const res = await api.post('/commission-membres', quickMembreData);
      await fetchMembresCatalog();
      setNouveauMembreForm((prev) => ({ ...prev, membre_id: String(res.data.id) }));
      setIsQuickMembreModalOpen(false);
      setQuickMembreData({ nom_prenom: '', fonction: '' });
    } catch (err) {
      setQuickMembreError(err.response?.status === 422 ? 'Veuillez remplir le nom et la fonction.' : 'Erreur lors de la création du membre.');
    } finally {
      setQuickMembreSaving(false);
    }
  };

  const openEditConsultationModal = (consultation) => {
    setEditingConsultationData({
      id: consultation.id,
      numero_consultation: consultation.numero_consultation || '',
      objet_consultation: consultation.objet_consultation || '',
      numero_decision: consultation.numero_decision || '',
      numero_bc: consultation.numero_bc || '',
      date_limite_devis: consultation.date_limite_devis || '',
      heure_limite_devis: consultation.heure_limite_devis || '10:00',
      lieu_execution: consultation.lieu_execution || 'REGION DE RABAT SALE KENITRA',
      annee: consultation.annee || new Date().getFullYear(),
      date_consultation: consultation.date_consultation || '',
      categorie: consultation.categorie || 'Services',
      type_prestation: consultation.type_prestation || '',
      intitule: consultation.intitule || consultation.type_prestation || '',
      mode_engagement: consultation.mode_engagement || 'BC',
      type_budget: consultation.type_budget || 'Fonctionnement',
      delai_execution: consultation.delai_execution || 30,
      statut_dossier: consultation.statut_dossier || 'Programmation',
      art: consultation.budget?.art || '415',
      par: consultation.budget?.par || '30',
      lig: consultation.budget?.lig || '60',
      code_imputation: consultation.budget?.code_imputation || '',
      exercice_budgetaire: consultation.budget?.exercice_budgetaire || new Date().getFullYear(),
    });
    const pres = consultation.prestations && consultation.prestations.length > 0
      ? consultation.prestations.map((p) => ({
        id: p.id,
        designation: p.designation || '',
        unite: p.unite || 'Unite',
        quantite: p.quantite || 1,
        prix_unitaire_ht: p.prix_unitaire_ht || 0,
        tva: p.tva || 20,
      }))
      : [emptyLine()];
    setEditingPrestations(pres);
    setEditingError('');
    setIsEditConsultationModalOpen(true);
  };

  const handleEditPrestationChange = (index, field, value) => {
    setEditingPrestations((prev) => prev.map((line, lineIndex) => {
      if (field === 'tva' && index === 0) {
        return { ...line, tva: value };
      }
      return lineIndex === index ? { ...line, [field]: value } : line;
    }));
  };

  const addEditPrestationLine = () => {
    setEditingPrestations((prev) => {
      const firstTva = prev[0]?.tva ?? 20;
      return [...prev, { ...emptyLine(), tva: firstTva }];
    });
  };

  const removeEditPrestationLine = (index) => {
    setEditingPrestations((prev) => prev.filter((_, i) => i !== index));
  };

  const handleSaveEditedConsultation = async (e) => {
    e.preventDefault();
    if (!editingConsultationData?.id) return;
    setEditingSaving(true);
    setEditingError('');

    try {
      const updatePayload = {
        numero_consultation: editingConsultationData.numero_consultation,
        objet_consultation: editingConsultationData.objet_consultation,
        numero_decision: editingConsultationData.numero_decision,
        numero_bc: editingConsultationData.numero_bc,
        date_limite_devis: editingConsultationData.date_limite_devis,
        heure_limite_devis: editingConsultationData.heure_limite_devis,
        lieu_execution: editingConsultationData.lieu_execution,
        annee: Number(editingConsultationData.annee) || new Date().getFullYear(),
        date_consultation: editingConsultationData.date_consultation,
        categorie: editingConsultationData.categorie,
        type_prestation: editingConsultationData.intitule || editingConsultationData.type_prestation,
        intitule: editingConsultationData.intitule || editingConsultationData.type_prestation,
        mode_engagement: editingConsultationData.mode_engagement,
        type_budget: editingConsultationData.type_budget,
        delai_execution: Number(editingConsultationData.delai_execution) || 30,
        statut_dossier: editingConsultationData.statut_dossier,
        art: editingConsultationData.art,
        par: editingConsultationData.par,
        lig: editingConsultationData.lig,
        code_imputation: editingConsultationData.code_imputation,
        exercice_budgetaire: Number(editingConsultationData.exercice_budgetaire) || new Date().getFullYear(),
      };

      await api.put(`/consultations/${editingConsultationData.id}`, updatePayload);

      const cleanPrestations = editingPrestations
        .filter((line) => line.designation.trim())
        .map((line) => ({
          designation: line.designation,
          unite: line.unite || 'Unite',
          quantite: Number(line.quantite) || 0,
          prix_unitaire_ht: Number(line.prix_unitaire_ht) || 0,
          tva: Number(line.tva) || 0,
        }));

      if (cleanPrestations.length > 0) {
        await api.post(`/consultations/${editingConsultationData.id}/prestations`, { prestations: cleanPrestations });
      }

      // Re-fetch la liste
      const listRes = await api.get('/consultations');
      const sorted = Array.isArray(listRes.data) ? [...listRes.data].sort((a, b) => b.id - a.id) : [];
      setConsultationsList(sorted);

      if (savedConsultation?.id === editingConsultationData.id) {
        const updatedConsultation = sorted.find((c) => c.id === editingConsultationData.id);
        if (updatedConsultation) setSavedConsultation(updatedConsultation);
      }

      setIsEditConsultationModalOpen(false);
      setMessage(`Consultation ${editingConsultationData.numero_consultation} modifiée avec succès.`);
      setError('');
    } catch (err) {
      console.error('Erreur lors de la modification de la consultation:', err);
      setEditingError(err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la modification.');
    } finally {
      setEditingSaving(false);
    }
  };

  const handleDeleteConsultation = async (consultation) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer la consultation "${consultation.numero_consultation}" ? Cette action est irréversible.`)) {
      return;
    }

    try {
      await api.delete(`/consultations/${consultation.id}`);

      setConsultationsList((prev) => prev.filter((c) => c.id !== consultation.id));

      if (savedConsultation?.id === consultation.id) {
        setSavedConsultation(null);
        localStorage.removeItem('drca_selected_consultation_id');
      }

      setMessage(`La consultation ${consultation.numero_consultation} a été supprimée avec succès.`);
      setError('');
    } catch (err) {
      console.error('Erreur lors de la suppression de la consultation:', err);
      setError(err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la suppression de la consultation.');
    }
  };

  const addMembreToDocumentCommission = (documentId, fieldName) => {
    if (!nouveauMembreForm.membre_id) return;
    const selectedMembreObj = membresCatalog.find((m) => String(m.id) === String(nouveauMembreForm.membre_id));
    if (!selectedMembreObj) return;

    const currentList = Array.isArray(documentForms[documentId]?.[fieldName])
      ? [...documentForms[documentId][fieldName]]
      : [];

    const newMemberEntry = {
      id: selectedMembreObj.id,
      nom: selectedMembreObj.nom_prenom,
      nom_prenom: selectedMembreObj.nom_prenom,
      fonction: selectedMembreObj.fonction,
      qualite: nouveauMembreForm.qualite || 'Membre',
    };

    if (!currentList.some((m) => String(m.id || m.nom) === String(newMemberEntry.id))) {
      currentList.push(newMemberEntry);
    }

    handleDocumentFieldChange(documentId, fieldName, currentList);
    setNouveauMembreForm({ membre_id: '', qualite: 'Membre' });
  };

  const removeMembreFromDocumentCommission = (documentId, fieldName, indexToRemove) => {
    const currentList = Array.isArray(documentForms[documentId]?.[fieldName])
      ? [...documentForms[documentId][fieldName]]
      : [];
    const updated = currentList.filter((_, idx) => idx !== indexToRemove);
    handleDocumentFieldChange(documentId, fieldName, updated);
  };

  const totalHT = useMemo(() => prestations.reduce((sum, item) => {
    return sum + ((Number(item.quantite) || 0) * (Number(item.prix_unitaire_ht) || 0));
  }, 0), [prestations]);

  const totalTTC = useMemo(() => prestations.reduce((sum, item) => {
    const ht = (Number(item.quantite) || 0) * (Number(item.prix_unitaire_ht) || 0);
    return sum + ht * (1 + ((Number(item.tva) || 0) / 100));
  }, 0), [prestations]);

  const handleFormChange = (event) => {
    const { name, value } = event.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleLineChange = (index, field, value) => {
    setPrestations((prev) => prev.map((line, lineIndex) => (
      lineIndex === index ? { ...line, [field]: value } : line
    )));
  };

  const handleDocumentFieldChange = (documentId, field, value) => {
    let cleanVal = value;
    if (fieldMaxLengthMap[field] && typeof value === 'string') {
      cleanVal = value.replace(/\D/g, '').slice(0, fieldMaxLengthMap[field]);
    }

    setDocumentForms((prev) => {
      const updated = {
        ...prev,
        [documentId]: {
          ...(prev[documentId] || {}),
          [field]: cleanVal,
        },
      };
      if (documentId === 'decision_commission_ouverture' && field === 'membres_commission') {
        updated.pv_ouverture_attribution = {
          ...(prev.pv_ouverture_attribution || {}),
          membres_commission: value,
        };
      }
      if (documentId === 'decision_commission_reception') {
        const fieldMap = {
          numero_bc: 'numero_bc',
          numero_decision: 'numero_decision',
          type_reception: 'type_reception',
          date_reception_definitive: 'date_reception_definitive',
          periode_du: 'periode_du',
          periode_au: 'periode_au',
          prestations_receptionnees: 'prestations_receptionnees',
          date_document: 'date_decision',
          date_reunion: 'date_reunion',
          heure_reunion: 'heure_reunion',
          membres_commission: 'membres_commission',
        };
        if (fieldMap[field]) {
          updated.pv_reception = {
            ...(prev.pv_reception || {}),
            [fieldMap[field]]: value,
          };
        }
      }
      if (documentId === 'pv_reception') {
        const fieldMap = {
          numero_bc: 'numero_bc',
          numero_decision: 'numero_decision',
          type_reception: 'type_reception',
          date_reception_definitive: 'date_reception_definitive',
          periode_du: 'periode_du',
          periode_au: 'periode_au',
          prestations_receptionnees: 'prestations_receptionnees',
          date_decision: 'date_document',
          date_reunion: 'date_reunion',
          heure_reunion: 'heure_reunion',
          membres_commission: 'membres_commission',
        };
        if (fieldMap[field]) {
          updated.decision_commission_reception = {
            ...(prev.decision_commission_reception || {}),
            [fieldMap[field]]: value,
          };
        }
      }
      if (field === 'adresse_societe' || field === 'adresse_1') {
        ['ordre_commande', 'accuse_reception', 'pv_reception', 'bon_commande'].forEach((docKey) => {
          if (docKey !== documentId) {
            updated[docKey] = {
              ...(prev[docKey] || {}),
              adresse_societe: value,
            };
          }
        });
      }
      if (field === 'ville_societe' || field === 'ville_1') {
        ['ordre_commande', 'accuse_reception', 'pv_reception', 'bon_commande'].forEach((docKey) => {
          if (docKey !== documentId) {
            updated[docKey] = {
              ...(prev[docKey] || {}),
              ville_societe: value,
            };
          }
        });
      }
      if (field === 'titulaire_nom' || field === 'societe' || field === 'attributaire') {
        ['ordre_commande', 'accuse_reception', 'pv_reception', 'bon_commande'].forEach((docKey) => {
          if (docKey !== documentId) {
            updated[docKey] = {
              ...(prev[docKey] || {}),
              societe: value,
              titulaire_nom: value,
            };
          }
        });
      }

      return updated;
    });

    if (formErrors[documentId]?.[field]) {
      setFormErrors((prev) => ({
        ...prev,
        [documentId]: {
          ...(prev[documentId] || {}),
          [field]: null,
        },
      }));
    }
  };

  const autoFillDocument = (documentId) => {
    const fields = documentFieldGroups[documentId] || [];
    const defaults = {};

    fields.forEach((field) => {
      if (field.name === 'numero_consultation') defaults.numero_consultation = savedConsultation?.numero_consultation || formData.numero_consultation || '04/2024/DRCA-RSK';
      else if (field.name === 'numero_bc') {
        let rawBc;
        if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
          rawBc = savedConsultation?.reception_commission?.numero_bc || savedConsultation?.receptionCommission?.numero_bc || documentForms.decision_commission_reception?.numero_bc || '';
        } else {
          rawBc = savedConsultation?.numero_bc || formData.numero_bc || savedConsultation?.numero_consultation || '05/2024/DRCA-RSK';
        }
        defaults.numero_bc = rawBc ? String(rawBc).replace(/^BC\s+/i, '') : '';
      }
      else if (field.name === 'objet') defaults.objet = savedConsultation?.objet_consultation || formData.objet_consultation || 'Achat de matériel de valorisation des produits agricoles';
      else if (field.name === 'numero_avis') defaults.numero_avis = savedConsultation?.numero_consultation || formData.numero_consultation || '04/2024/DRCA-RSK';
      else if (field.name === 'numero_decision') {
        if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
          defaults.numero_decision = savedConsultation?.reception_commission?.numero_decision || savedConsultation?.receptionCommission?.numero_decision || documentForms.decision_commission_reception?.numero_decision || '';
        } else {
          defaults.numero_decision = savedConsultation?.numero_decision || formData.numero_decision || `06/${savedConsultation?.annee || new Date().getFullYear()}/DRCA-RSK`;
        }
      }
      else if (field.name === 'type_reception') {
        defaults.type_reception = savedConsultation?.reception_commission?.type_reception || savedConsultation?.receptionCommission?.type_reception || documentForms.decision_commission_reception?.type_reception || 'définitive';
      }
      else if (field.name === 'date_reception_definitive') {
        defaults.date_reception_definitive = savedConsultation?.reception_commission?.date_reception_definitive || savedConsultation?.receptionCommission?.date_reception_definitive || documentForms.decision_commission_reception?.date_reception_definitive || '';
      }
      else if (field.name === 'periode_du') {
        defaults.periode_du = savedConsultation?.reception_commission?.periode_du || savedConsultation?.receptionCommission?.periode_du || documentForms.decision_commission_reception?.periode_du || '';
      }
      else if (field.name === 'periode_au') {
        defaults.periode_au = savedConsultation?.reception_commission?.periode_au || savedConsultation?.receptionCommission?.periode_au || documentForms.decision_commission_reception?.periode_au || '';
      }
      else if (field.name === 'prestations_receptionnees') {
        const savedList = savedConsultation?.reception_commission?.prestations_receptionnees || savedConsultation?.receptionCommission?.prestations_receptionnees || documentForms.decision_commission_reception?.prestations_receptionnees;
        const srcPrestations = (savedConsultation?.prestations && savedConsultation.prestations.length > 0)
          ? savedConsultation.prestations
          : (prestations && prestations.filter((p) => p.designation?.trim()).length > 0 ? prestations.filter((p) => p.designation?.trim()) : []);

        if (Array.isArray(savedList) && savedList.length > 0) {
          defaults.prestations_receptionnees = savedList;
        } else {
          const list = srcPrestations.map((p, idx) => ({
            id: p.id || idx,
            receptionne: true,
            numero_prix: p.numero_prix || (idx + 1),
            designation: p.designation || 'Prestation',
            unite: p.unite || p.unite_mesure || 'Unité',
            quantite: Number(p.quantite) || 1,
            quantite_receptionnee: Number(p.quantite) || 1,
          }));
          defaults.prestations_receptionnees = list;
        }
      }
      else if (field.name === 'numero_engagement') {
        if (documentId === 'fiche_engagement') {
          defaults.numero_engagement = savedConsultation?.numero_engagement || savedConsultation?.numero_bc
            ? `${savedConsultation.numero_engagement || savedConsultation.numero_bc}`
            : `${savedConsultation?.annee || new Date().getFullYear()}/FE/DRCA-RSK`;
        } else {
          defaults.numero_engagement = `05/DR/${savedConsultation?.annee || new Date().getFullYear()}`;
        }
      }
      else if (field.name === 'date_document' && documentId === 'fiche_engagement') defaults.date_document = savedConsultation?.date_consultation || '';
      else if (field.name === 'reference_2') defaults.reference_2 = savedConsultation?.reference_2 || '';
      else if (field.name === 'mode_engagement') defaults.mode_engagement = savedConsultation?.mode_engagement || 'BC';
      else if (field.name === 'budget') defaults.budget = savedConsultation?.budget?.intitule_ligne || `${savedConsultation?.type_budget || ''} ${savedConsultation?.budget?.exercice_budgetaire || savedConsultation?.annee || ''}`.trim();
      else if (field.name === 'code_imputation') defaults.code_imputation = savedConsultation?.budget?.code_imputation || '';
      else if (field.name === 's_lig') defaults.s_lig = savedConsultation?.s_lig || '';
      else if (field.name === 'montant_ttc') {
        const srcPrestations = savedConsultation?.prestations?.length > 0 ? savedConsultation.prestations : prestations.filter(p => p.designation?.trim());
        const ttc = srcPrestations.reduce((sum, p) => {
          const ht = (Number(p.quantite) || 0) * (Number(p.prix_unitaire_ht) || 0);
          return sum + ht * (1 + ((Number(p.tva) || 20) / 100));
        }, 0);
        defaults.montant_ttc = ttc > 0 ? ttc.toFixed(2) : (savedConsultation?.budget?.montant_ttc || '');
      }
      else if (field.name === 'credit_ouvert_cp') defaults.credit_ouvert_cp = savedConsultation?.credit_ouvert_cp || '';
      else if (field.name === 'credit_ouvert_ce') defaults.credit_ouvert_ce = savedConsultation?.credit_ouvert_ce || '';
      else if (field.name === 'depenses_anterieures_ce') defaults.depenses_anterieures_ce = savedConsultation?.depenses_anterieures_ce || '';
      else if (field.name === 'depenses_anterieures_cp') defaults.depenses_anterieures_cp = savedConsultation?.depenses_anterieures_cp || '';
      else if (field.name === 'depenses_credits_engagement') defaults.depenses_credits_engagement = savedConsultation?.depenses_credits_engagement || '';
      else if (field.name === 'depenses_credits_consolides') defaults.depenses_credits_consolides = savedConsultation?.depenses_credits_consolides || '';
      else if (field.name === 'depenses_rap') defaults.depenses_rap = savedConsultation?.depenses_rap || '';
      else if (field.name === 'montant_depense_neuf') defaults.montant_depense_neuf = savedConsultation?.montant_depense_neuf || savedConsultation?.budget?.montant_ttc || '';
      else if (field.name === 'interets_moratoires') defaults.interets_moratoires = savedConsultation?.interets_moratoires || '';
      else if (field.name === 'montant_engager_neuf') defaults.montant_engager_neuf = savedConsultation?.montant_engager_neuf || '';
      else if (field.name === 'credit_budget_cp') defaults.credit_budget_cp = '';
      else if (field.name === 'depenses_engagees_cp') defaults.depenses_engagees_cp = '';
      else if (field.name === 'engagement_propose_cp') {
        const srcPrestations = savedConsultation?.prestations?.length > 0 ? savedConsultation.prestations : prestations.filter(p => p.designation?.trim());
        const ttc = srcPrestations.reduce((sum, p) => {
          const ht = (Number(p.quantite) || 0) * (Number(p.prix_unitaire_ht) || 0);
          return sum + ht * (1 + ((Number(p.tva) || 20) / 100));
        }, 0);
        defaults.engagement_propose_cp = ttc > 0 ? (ttc * 1.01).toFixed(2) : '';
      }
      else if (field.name === 'pieces_jointes' && documentId === 'fiche_engagement') {
        defaults.pieces_jointes = savedConsultation?.numero_bc ? `Bon de commande N° ${savedConsultation.numero_bc}` : '';
      }
      else if (field.name === 'numero_lettre') defaults.numero_lettre = `${new Date().getFullYear()}/DRCA-RSK/SOS`;
      else if (field.name === 'nature_os') defaults.nature_os = "Commencement de l'exécution";
      else if (field.name === 'societe' || field.name === 'participant_1' || field.name === 'titulaire_nom') {
        defaults[field.name] = documentForms.bon_commande?.titulaire_nom || documentForms.pv_ouverture_attribution?.attributaire || savedConsultation?.fournisseur?.raison_sociale || savedConsultation?.engagement?.fournisseur?.raison_sociale || '';
      }
      else if (field.name === 'art') defaults.art = savedConsultation?.budget?.art || formData.art || '';
      else if (field.name === 'par') defaults.par = savedConsultation?.budget?.par || formData.par || '';
      else if (field.name === 'lig') defaults.lig = savedConsultation?.budget?.lig || formData.lig || '';
      else if (field.name === 'intitule') defaults.intitule = savedConsultation?.intitule || savedConsultation?.type_prestation || savedConsultation?.objet_consultation || '';
      else if (field.name === 'patente') defaults.patente = '';
      else if (field.name === 'cnss') defaults.cnss = '';
      else if (field.name === 'ice') defaults.ice = '';
      else if (field.name === 'if' || field.name === 'identifiant_fiscal') defaults[field.name] = '';
      else if (field.name === 'representant') defaults.representant = '';
      else if (field.name === 'adresse_1' || field.name === 'adresse_societe') defaults[field.name] = documentForms.bon_commande?.adresse_societe || '';
      else if (field.name === 'ville_1' || field.name === 'ville_societe') defaults[field.name] = documentForms.bon_commande?.ville_societe || '';
      else if (field.name === 'code_societe' || field.name === 'code_ste') defaults[field.name] = '';
      else if (field.name === 'coordonnees_parties') defaults[field.name] = '';
      else if (field.name === 'montant_reserve' || field.name === 'montant_facture' || field.name === 'montant_apres_verification' || field.name === 'montant_avant_verification' || field.name === 'montant_1') {
        defaults[field.name] = totalTTC || savedConsultation?.montant_estimatif_ht || '';
      }
      else if (field.name === 'rib') defaults.rib = '';
      else if (field.name === 'lieu_execution' || field.name === 'lieu_livraison' || field.name === 'lieu_reunion') {
        defaults[field.name] = savedConsultation?.lieu_execution || formData.lieu_execution || 'REGION DE RABAT SALE KENITRA';
      }
      else if (field.name === 'delai_livraison' || field.name === 'delais_execution') {
        const val = savedConsultation?.delai_execution ?? formData?.delai_execution ?? 30;
        defaults[field.name] = field.type === 'number' ? (parseInt(val, 10) || 30) : `${val} jours`;
      }
      else if (field.name === 'date_limite') {
        let dLimite = savedConsultation?.date_limite_devis || formData?.date_limite_devis;
        if (!dLimite && (savedConsultation?.date_consultation || formData?.date_consultation)) {
          const dCons = new Date(savedConsultation?.date_consultation || formData?.date_consultation);
          if (!isNaN(dCons.getTime())) {
            dCons.setDate(dCons.getDate() + 2);
            const yyyy = dCons.getFullYear();
            const mm = String(dCons.getMonth() + 1).padStart(2, '0');
            const dd = String(dCons.getDate()).padStart(2, '0');
            dLimite = `${yyyy}-${mm}-${dd}`;
          }
        }
        defaults.date_limite = dLimite || '';
      }
      else if (field.name === 'heure_limite') {
        defaults.heure_limite = savedConsultation?.heure_limite_devis || formData.heure_limite_devis || '10:00';
      }
      else if (field.name === 'date_reunion') {
        if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
          defaults.date_reunion = savedConsultation?.reception_commission?.date_reunion || documentForms.decision_commission_reception?.date_reunion || '';
        } else {
          defaults.date_reunion = savedConsultation?.date_limite_devis || '';
        }
      }
      else if ((field.name === 'date_document' || field.name === 'date_decision' || field.name === 'date_os' || field.name === 'date_reception' || field.name === 'date_bon_payer') && documentId !== 'fiche_engagement') {
        if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
          defaults[field.name] = savedConsultation?.reception_commission?.date_decision || documentForms.decision_commission_reception?.date_document || '';
        } else {
          defaults[field.name] = '';
        }
      }
      else if (field.name === 'heure_reunion') defaults.heure_reunion = savedConsultation?.heure_limite_devis || savedConsultation?.heure_reunion || '10:00';
      else if (field.name === 'heure_fin') defaults.heure_fin = '11:30';
      else if (field.name === 'type_pv') defaults.type_pv = 'Réception Provisoire';
      else if (field.name === 'mode_reglement') defaults.mode_reglement = 'Virement bancaire';
      else if (field.name === 'imputation' || field.name === 'imputation_budgetaire') {
        defaults[field.name] = `ART: ${formData.art || '01'} / PAR: ${formData.par || '02'} / LIG: ${formData.lig || '05'}`;
      }
      else if (field.name === 'membres_commission' || field.name === 'agents_commission') {
        if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
          if (Array.isArray(savedConsultation?.reception_commission?.membres_commission) && savedConsultation.reception_commission.membres_commission.length > 0) {
            defaults[field.name] = savedConsultation.reception_commission.membres_commission;
          } else if (Array.isArray(documentForms.decision_commission_reception?.membres_commission) && documentForms.decision_commission_reception.membres_commission.length > 0) {
            defaults[field.name] = documentForms.decision_commission_reception.membres_commission;
          } else {
            defaults[field.name] = [];
          }
        } else if (documentId === 'pv_ouverture_attribution' && Array.isArray(documentForms.decision_commission_ouverture?.membres_commission) && documentForms.decision_commission_ouverture.membres_commission.length > 0) {
          defaults[field.name] = documentForms.decision_commission_ouverture.membres_commission;
        } else {
          defaults[field.name] = [];
        }
      }
      else if (field.name === 'specification') defaults.specification = prestations[0]?.designation || 'Soudeuse à pédale 30 cm';
      else if (field.name === 'conformite') defaults.conformite = 'Conforme sur le plan administratif et technique';
      else if (field.name === 'justification') defaults.justification = 'Offre la moins disante et techniquement conforme';
      else if (field.name === 'visa_controleur') defaults.visa_controleur = 'Visé par le Contrôleur Financier SAF/DRCA';
      else if (field.name === 'nature_engagement') defaults.nature_engagement = 'Bon de commande';
      else if (field.name === 'numero_prix') defaults.numero_prix = '1';
      else if (field.name === 'designation') defaults.designation = prestations[0]?.designation || savedConsultation?.prestations?.[0]?.designation || '';
      else if (field.name === 'unite_mesure') defaults.unite_mesure = prestations[0]?.unite || savedConsultation?.prestations?.[0]?.unite || 'Unite';
      else if (field.name === 'quantite') defaults.quantite = prestations[0]?.quantite || savedConsultation?.prestations?.[0]?.quantite || 1;
      else if (field.name === 'garantie_exigee') defaults.garantie_exigee = '2 ans';
      else if (field.name === 'consistance_lignes') {
        const srcPrestations = savedConsultation?.prestations?.length > 0
          ? savedConsultation.prestations
          : prestations.filter(p => p.designation?.trim());
        defaults.consistance_lignes = srcPrestations.length > 0
          ? srcPrestations.map((p, i) => ({
            numero_prix: p.numero_prix || String(i + 1),
            designation: p.designation || '',
            specification: p.specification || p.designation || '',
            unite_mesure: p.unite || p.unite_mesure || 'Unite',
            quantite: String(p.quantite || 1),
            prix_unitaire_ht: String(p.prix_unitaire_ht || 0),
            tva: String(p.tva || 20),
            garantie_exigee: p.garantie_exigee || '2 ans',
          }))
          : [{ numero_prix: '1', designation: '', specification: '', unite_mesure: 'Unite', quantite: '1', prix_unitaire_ht: '0', tva: '20', garantie_exigee: '' }];
      }
      else if (field.name === 'concurrents') {
        if (savedConsultation?.offres?.length > 0) {
          defaults.concurrents = savedConsultation.offres.map((o) => ({
            nom: o.fournisseur?.raison_sociale || 'Société',
            montant: o.montant_apres_verification || o.montant_propose || 0,
          }));
        } else {
          defaults.concurrents = [
            { nom: 'TOPOGRAPHY CONSULTING', montant: 30240 },
            { nom: 'LANDMAP SURVEY', montant: 36360 },
            { nom: 'BUREAU ALAOUI TOPO', montant: 39060 },
            { nom: 'BAJITOP', montant: 45240 },
            { nom: 'GOLDEN GEO', montant: 47520 },
          ];
        }
      }
      else if (field.name === 'attributaire') {
        const attrNom = savedConsultation?.fournisseur?.raison_sociale || savedConsultation?.engagement?.fournisseur?.raison_sociale || 'BUREAU ALAOUI TOPO';
        defaults.attributaire = attrNom;

        const concs = defaults.concurrents || [
          { nom: 'TOPOGRAPHY CONSULTING', montant: 30240 },
          { nom: 'LANDMAP SURVEY', montant: 36360 },
          { nom: 'BUREAU ALAOUI TOPO', montant: 39060 },
          { nom: 'BAJITOP', montant: 45240 },
          { nom: 'GOLDEN GEO', montant: 47520 },
        ];
        const foundIdx = concs.findIndex((c) => c.nom === attrNom);
        if (foundIdx > 0) {
          defaults.societes_refusees = concs.slice(0, foundIdx).map((c) => ({
            nom: c.nom,
            motif: "Refus d'invitation du maître d'ouvrage",
          }));
        } else {
          defaults.societes_refusees = [];
        }
      }
      else if (field.name === 'societes_refusees') {
        if (!defaults.societes_refusees) {
          defaults.societes_refusees = [];
        }
      }
      else if (field.name === 'annee') defaults.annee = formData.exercice_budgetaire || currentYear;
      else if (field.name === 'observation') defaults.observation = 'Démarrage immédiat des prestations après notification';
      else if (field.name === 'constat_service_fait') defaults.constat_service_fait = 'Prestations exécutées conformément aux clauses du Bon de Commande';
      else if (field.name === 'direction') defaults.direction = 'Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra';
      // ── Champs OI / OP / OV ────────────────────────────────────────────────
      else if (field.name === 'numero_oi') defaults.numero_oi = '';
      else if (field.name === 'numero_op') {
        // Pour ordre_virement, reprend le numéro OP déjà saisi dans ordre_paiement
        defaults.numero_op = documentForms.ordre_paiement?.numero_op || '';
      }
      else if (field.name === 'numero_ov') defaults.numero_ov = '';
      else if (field.name === 'annee_origine') defaults.annee_origine = savedConsultation?.annee || currentYear;
      else if (field.name === 'exercice_origine') defaults.exercice_origine = (savedConsultation?.annee || currentYear) - 1;
      else if (field.name === 'credit_type') defaults.credit_type = 'Crédit Consolidés';
      else if (field.name === 'forme_engagement') defaults.forme_engagement = 'Bon de commande';
      else if (field.name === 'reference_bc') {
        defaults.reference_bc = savedConsultation?.numero_bc
          ? `Bon de commande N° ${savedConsultation.numero_bc}`
          : (savedConsultation?.numero_consultation ? `Bon de commande N° ${savedConsultation.numero_consultation}` : '');
      }
      else if (field.name === 'montant_oi' || field.name === 'montant_op' || field.name === 'montant_ov') {
        // Calcul du montant TTC depuis les prestations
        const srcP = savedConsultation?.prestations?.length > 0 ? savedConsultation.prestations : prestations.filter(p => p.designation?.trim());
        const ttc = srcP.reduce((sum, p) => {
          const ht = (Number(p.quantite) || 0) * (Number(p.prix_unitaire_ht) || 0);
          return sum + ht * (1 + ((Number(p.tva) || 20) / 100));
        }, 0);
        const val = ttc > 0 ? ttc : (savedConsultation?.budget?.montant_ttc || 0);
        defaults[field.name] = val ? Number(val).toFixed(2) : '';
      }
      else if (field.name === 'montant_engagement') {
        const srcP = savedConsultation?.prestations?.length > 0 ? savedConsultation.prestations : prestations.filter(p => p.designation?.trim());
        const ttc = srcP.reduce((sum, p) => {
          const ht = (Number(p.quantite) || 0) * (Number(p.prix_unitaire_ht) || 0);
          return sum + ht * (1 + ((Number(p.tva) || 20) / 100));
        }, 0);
        defaults.montant_engagement = ttc > 0 ? Number(ttc).toFixed(2) : '';
      }
      else if (field.name === 'compte_debit') defaults.compte_debit = '310330100602470154760181';
      else if (field.name === 'libelle_debit') defaults.libelle_debit = 'T.P KENITRA';
      else if (field.name === 'compte_credit') {
        defaults.compte_credit = documentForms.bon_commande?.rib || savedConsultation?.fournisseur?.rib || '';
      }
      else if (field.name === 'compte_courant') defaults.compte_courant = '310330100602470154760181';
      else if (field.name === 'libelle_compte') defaults.libelle_compte = 'ONCA DR RABAT-SALE-KENITRA INVESTISSEMENT';
      else if (field.name === 'banque') {
        defaults.banque = documentForms.bon_commande?.banque || savedConsultation?.fournisseur?.banque || '';
      }
      else if (field.name === 'intitule_rubrique') {
        defaults.intitule_rubrique = savedConsultation?.intitule || savedConsultation?.type_prestation || savedConsultation?.objet_consultation || '';
      }
      else if (field.name === 'prestation_meme_nature') defaults.prestation_meme_nature = 'PRESTATION DE MEME NATURE';
      else if (field.name === 'pieces_jointes_op') {
        defaults.pieces_jointes_op = `Facture N°\nDécompte provisoire N°\nPV de réception définitif\nOrdre de service d'ajournement\nOrdre de service de reprise`;
      }
    });

    setDocumentForms((prev) => ({
      ...prev,
      [documentId]: {
        ...(prev[documentId] || {}),
        ...defaults,
      },
    }));

    setFormErrors((prev) => ({ ...prev, [documentId]: {} }));
    setMessage('Champs pré-remplis automatiquement avec succès !');
    setError('');
  };

  // Auto-remplissage automatique dès qu'une consultation est sélectionnée
  useEffect(() => {
    if (savedConsultation) {
      allDocuments.forEach((doc) => autoFillDocument(doc.id));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [savedConsultation?.id]);

  const validateDocumentForm = (documentId) => {
    const fields = documentFieldGroups[documentId] || [];
    const values = documentForms[documentId] || {};
    const errors = {};
    const missingLabels = [];

    fields.forEach((field) => {
      if (field.condition && !field.condition(values)) {
        return;
      }
      if (field.required) {
        const val = values[field.name];
        if (field.type === 'commission_selector') {
          const commList = (documentId === 'pv_reception' || documentId === 'pv_ouverture_attribution')
            ? (values[field.name] || (documentId === 'pv_reception' ? (documentForms.decision_commission_reception?.membres_commission || savedConsultation?.reception_commission?.membres_commission) : (documentForms.decision_commission_ouverture?.membres_commission)))
            : val;

          if (!Array.isArray(commList) || commList.length < 3) {
            errors[field.name] = 'La commission doit obligatoirement comporter au moins 3 personnes (1 Président(e) et 2 membres).';
            missingLabels.push(`${field.label} (au moins 3 personnes)`);
          }
        } else if (field.type === 'consistance_table') {
          const rawLignes = values[field.name] || documentForms['avis_achat']?.[field.name] || documentForms['bon_commande']?.[field.name];
          const srcPrestations = savedConsultation?.prestations?.length > 0
            ? savedConsultation.prestations
            : prestations.filter(p => p.designation?.trim());

          const initialLignes = srcPrestations.length > 0
            ? srcPrestations.map((p, i) => ({
              numero_prix: p.numero_prix || String(i + 1),
              designation: p.designation || '',
              specification: p.specification || p.designation || '',
              unite_mesure: p.unite || p.unite_mesure || 'Unite',
              quantite: String(p.quantite || 1),
              prix_unitaire_ht: String(p.prix_unitaire_ht || 0),
              tva: String(p.tva || 20),
              garantie_exigee: p.garantie_exigee || '',
            }))
            : [{ numero_prix: '1', designation: '', specification: '', unite_mesure: 'Unite', quantite: '1', prix_unitaire_ht: '0', tva: '20', garantie_exigee: '' }];

          const lignes = Array.isArray(rawLignes) && rawLignes.length > 0 ? rawLignes : initialLignes;

          let hasEmptyCell = false;
          let hasInvalidPrice = false;
          const checkPrice = documentId !== 'avis_achat';

          lignes.forEach((l) => {
            if (
              !l.numero_prix || String(l.numero_prix).trim() === '' ||
              !l.designation || String(l.designation).trim() === '' ||
              !l.unite_mesure || String(l.unite_mesure).trim() === '' ||
              !l.quantite || String(l.quantite).trim() === ''
            ) {
              hasEmptyCell = true;
            }

            if (checkPrice) {
              const pu = parseFloat(l.prix_unitaire_ht);
              if (isNaN(pu) || pu <= 0 || String(l.prix_unitaire_ht).trim() === '') {
                hasInvalidPrice = true;
              }
            }
          });

          if (hasEmptyCell || hasInvalidPrice) {
            errors[field.name] = checkPrice
            missingLabels.push(`${field.label} (Tous les champs et P.U HT > 0)`);
          }
        } else if (val === undefined || val === null || String(val).trim() === '') {
          errors[field.name] = `Le champ « ${field.label} » est obligatoire.`;
          missingLabels.push(field.label);
        }
      }

      // Validation spécifique du format des données de société (RIB, ICE, IF, RC, CNSS)
      const fieldValue = values[field.name];
      if (fieldValue && typeof fieldValue === 'string' && fieldValue.trim() !== '') {
        const cleanVal = fieldValue.replace(/\s/g, '');

        if (field.name === 'rib') {
          if (!/^\d{24}$/.test(cleanVal)) {
            errors[field.name] = `Le RIB doit comporter exactement 24 chiffres (actuellement: ${cleanVal.length}).`;
            if (!missingLabels.includes(field.label)) missingLabels.push(`${field.label} (24 chiffres obligatoires)`);
          }
        }

        if (field.name === 'ice') {
          if (!/^\d{15}$/.test(cleanVal)) {
            errors[field.name] = `L'ICE doit comporter exactement 15 chiffres (actuellement: ${cleanVal.length}).`;
            if (!missingLabels.includes(field.label)) missingLabels.push(`${field.label} (15 chiffres obligatoires)`);
          }
        }

        if (field.name === 'if' || field.name === 'identifiant_fiscal') {
          if (!/^\d{5}$|^\d{8}$/.test(cleanVal)) {
            errors[field.name] = `L'Identifiant Fiscal (IF) doit comporter 5 ou 8 chiffres (actuellement: ${cleanVal.length}).`;
            if (!missingLabels.includes(field.label)) missingLabels.push(`${field.label} (5 ou 8 chiffres)`);
          }
        }
      }
    });

    setFormErrors((prev) => ({ ...prev, [documentId]: errors }));
    return {
      isValid: Object.keys(errors).length === 0,
      missingLabels,
    };
  };

  const buildDocumentData = (documentId) => {
    const values = documentForms[documentId] || {};
    const montantDepenseNeuf = Number(values.montant_depense_neuf) || 0;
    const interetsMoratoires = Number((montantDepenseNeuf * 0.01).toFixed(2));
    const montantEngagerNeuf = Number((montantDepenseNeuf + interetsMoratoires).toFixed(2));
    const data = {
      ...values,
      ...(documentId === 'fiche_engagement' ? {
        montant_ttc: montantDepenseNeuf,
        interets_moratoires: interetsMoratoires,
        montant_engager_neuf: montantEngagerNeuf,
        credit_budget_cp: values.credit_ouvert_cp,
        credit_budget_ce: values.credit_ouvert_ce,
        depenses_engagees_cp: values.depenses_anterieures_cp,
        depenses_engagees_ce: values.depenses_anterieures_ce,
        disponible_cp: (Number(values.credit_ouvert_cp) || 0) - (Number(values.depenses_anterieures_cp) || 0),
        disponible_ce: (Number(values.credit_ouvert_ce) || 0) - (Number(values.depenses_anterieures_ce) || 0),
      } : {}),
      objet: values.objet || formData.objet_consultation,
      numero_consultation: values.numero_consultation || savedConsultation?.numero_consultation,
      adresse_societe: values.adresse_societe || values.adresse_1 || documentForms['bon_commande']?.adresse_societe || savedConsultation?.fournisseur?.adresse || savedConsultation?.engagement?.fournisseur?.adresse || 'APP N 6 Immeuble 01 KHENIFRA',
      ville_societe: values.ville_societe || values.ville_1 || documentForms['bon_commande']?.ville_societe || savedConsultation?.fournisseur?.ville || savedConsultation?.engagement?.fournisseur?.ville || 'Rabat',
    };

    if (documentId === 'pv_ouverture_attribution' && Array.isArray(documentForms.decision_commission_ouverture?.membres_commission) && documentForms.decision_commission_ouverture.membres_commission.length > 0) {
      data.membres_commission = documentForms.decision_commission_ouverture.membres_commission;
    }

    if (documentId === 'pv_reception') {
      if (documentForms.decision_commission_reception?.numero_decision !== undefined) {
        data.numero_decision = documentForms.decision_commission_reception.numero_decision;
      }
      if (documentForms.decision_commission_reception?.type_reception !== undefined) {
        data.type_reception = documentForms.decision_commission_reception.type_reception;
      }
      if (documentForms.decision_commission_reception?.periode_du !== undefined) {
        data.periode_du = documentForms.decision_commission_reception.periode_du;
      }
      if (documentForms.decision_commission_reception?.periode_au !== undefined) {
        data.periode_au = documentForms.decision_commission_reception.periode_au;
      }
      if (documentForms.decision_commission_reception?.prestations_receptionnees !== undefined) {
        data.prestations_receptionnees = documentForms.decision_commission_reception.prestations_receptionnees;
      }
      if (documentForms.decision_commission_reception?.date_document) {
        data.date_decision = documentForms.decision_commission_reception.date_document;
      }
      if (Array.isArray(documentForms.decision_commission_reception?.membres_commission) && documentForms.decision_commission_reception.membres_commission.length > 0) {
        data.membres_commission = documentForms.decision_commission_reception.membres_commission;
      }
      if (documentForms.decision_commission_reception?.date_reunion) {
        data.date_reunion = documentForms.decision_commission_reception.date_reunion;
      }
    }

    // Convertir délai en jours → affichage mois dans le PDF
    if (values.delai_livraison) {
      const jours = parseInt(values.delai_livraison, 10);
      if (!isNaN(jours) && jours > 0) {
        const mois = jours / 30;
        const moisStr = Number.isInteger(mois) ? `${mois} mois` : `${Math.round(mois * 10) / 10} mois`;
        data.delai_livraison = moisStr;
        data.delai_livraison_jours = `${jours} jours`;
      }
    }

    // Aplatir les lignes de consistance de prestation
    if (Array.isArray(values.consistance_lignes) && values.consistance_lignes.length > 0) {
      data.consistance_lignes = values.consistance_lignes;
      // Pour compatibilité avec les champs individuels (premier élément)
      const first = values.consistance_lignes[0];
      if (first) {
        data.numero_prix = data.numero_prix || first.numero_prix;
        data.designation = data.designation || first.designation;
        data.specification = data.specification || first.specification;
        data.unite_mesure = data.unite_mesure || first.unite_mesure;
        data.quantite = data.quantite || first.quantite;
        data.garantie_exigee = data.garantie_exigee || first.garantie_exigee;
      }
    }

    if (values.concurrents) {
      data.concurrents = values.concurrents;
    }
    if (values.societes_refusees) {
      data.societes_refusees = typeof values.societes_refusees === 'string'
        ? values.societes_refusees.split(',').map((s) => s.trim()).filter(Boolean)
        : values.societes_refusees;
    }

    if (values.participant_1 || values.adresse_1 || values.ville_1 || values.montant_1 || values.montant_apres_verification) {
      data.fournisseurs = [{
        nom: values.participant_1 || values.societe || values.attributaire || 'BUREAU ALAOUI TOPO',
        adresse: values.adresse_1 || values.adresse_societe || '',
        ville: values.ville_1 || values.ville_societe || '',
        montant: Number(values.montant_apres_verification || values.montant_1) || totalTTC,
      }];
      data.attributaire = values.attributaire || values.participant_1 || values.societe || 'BUREAU ALAOUI TOPO';
    }

    return data;
  };

  const addLine = () => setPrestations((prev) => [...prev, emptyLine()]);
  const removeLine = (index) => {
    setPrestations((prev) => prev.length === 1 ? prev : prev.filter((_, lineIndex) => lineIndex !== index));
  };

  const saveDossier = async (event) => {
    if (event) event.preventDefault();
    if (savedConsultation) {
      setMessage('Le dossier est déjà enregistré. Vous pouvez générer les documents PDF.');
      return;
    }
    setSaving(true);
    setError('');
    setMessage('');

    try {
      const payload = {
        ...formData,
        mode_engagement: 'BC',
        statut_dossier: 'Programmation',
        montant_estimatif_ht: totalHT,
        tva: prestations[0]?.tva || 20,
      };

      const consultationResponse = await api.post('/consultations', payload);
      const consultation = consultationResponse.data;

      const cleanPrestations = prestations
        .filter((line) => line.designation.trim())
        .map((line) => ({
          designation: line.designation,
          unite: line.unite || 'Unite',
          quantite: Number(line.quantite) || 0,
          prix_unitaire_ht: Number(line.prix_unitaire_ht) || 0,
          tva: Number(line.tva) || 0,
        }));

      if (cleanPrestations.length > 0) {
        await api.post(`/consultations/${consultation.id}/prestations`, { prestations: cleanPrestations });
      }

      setSavedConsultation(consultation);
      setMessage(`Dossier ${consultation.numero_consultation} enregistré. Les PDF sont disponibles.`);
      setActivePhase('consultation');
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.error || "Impossible d'enregistrer le dossier.");
    } finally {
      setSaving(false);
    }
  };

  const getErrorMessage = async (err, defaultMsg) => {
    if (err.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text();
        const json = JSON.parse(text);
        return json.message || json.error || defaultMsg;
      } catch (e) {
        return defaultMsg;
      }
    }
    return err.response?.data?.message || err.response?.data?.error || defaultMsg;
  };

  const selectConsultation = (consultation) => {
    setSavedConsultation(consultation);
    setMessage(`Dossier ${consultation.numero_consultation} chargé avec succès. Vous pouvez maintenant remplir et télécharger tous les documents PDF.`);
    setError('');
    setActivePhase('consultation');
    if (consultation) {
      const retrievedIntitule = consultation.intitule || consultation.type_prestation || consultation.objet_consultation;
      if (retrievedIntitule) {
        handleDocumentFieldChange('bon_commande', 'intitule', retrievedIntitule);
      }
    }
  };

  const findBonCommande = async () => {
    const numeroBc = String(documentForms.fiche_engagement?.numero_bc || '').trim();
    if (!numeroBc) {
      setError('Veuillez saisir le numéro du bon de commande.');
      return;
    }

    setSearchingBonCommande(true);
    setError('');
    try {
      const response = await api.get(`/consultations/by-bon-commande/${encodeURIComponent(numeroBc)}`);
      setSavedConsultation(response.data);
      setConsultationsList((prev) => [response.data, ...prev.filter((item) => item.id !== response.data.id)]);
      setMessage('Bon de commande trouvé. Les informations générales ont été pré-remplies.');
    } catch (err) {
      setError(err.response?.data?.message || 'Bon de commande introuvable.');
    } finally {
      setSearchingBonCommande(false);
    }
  };

  const saveDocumentDataToDatabase = async (documentId, isExplicitSave = false) => {
    if (!savedConsultation?.id) return;

    if (isExplicitSave) {
      const validation = validateDocumentForm(documentId);
      if (!validation.isValid) {
        const missingText = validation.missingLabels?.length > 0
          ? `Veuillez remplir le(s) champ(s) obligatoire(s) suivant(s) : ${validation.missingLabels.join(', ')}.`
          : "Veuillez remplir tous les champs obligatoires du formulaire avant de procéder à l'enregistrement.";
        setError(missingText);
        setMessage('');
        return;
      }
    }

    const values = documentForms[documentId] || {};

    const updatePayload = {};
    if (documentId !== 'decision_commission_reception' && documentId !== 'pv_reception') {
      if (values.numero_bc !== undefined && values.numero_bc !== null) updatePayload.numero_bc = values.numero_bc;
      if (values.numero_decision !== undefined && values.numero_decision !== null) updatePayload.numero_decision = values.numero_decision;
    }
    if (values.date_limite !== undefined && values.date_limite !== null) updatePayload.date_limite_devis = values.date_limite;
    if (values.heure_limite !== undefined && values.heure_limite !== null) updatePayload.heure_limite_devis = values.heure_limite;
    if (values.lieu_execution !== undefined && values.lieu_execution !== null) updatePayload.lieu_execution = values.lieu_execution;
    if (values.objet !== undefined && values.objet !== null) updatePayload.objet_consultation = values.objet;
    if (values.delai_livraison !== undefined && values.delai_livraison !== null) {
      const parsedDelai = parseInt(values.delai_livraison, 10);
      if (!isNaN(parsedDelai) && parsedDelai > 0) updatePayload.delai_execution = parsedDelai;
    }

    if (documentId === 'fiche_engagement') {
      const montantDepenseNeuf = Number(values.montant_depense_neuf) || 0;
      const interetsMoratoires = Number((montantDepenseNeuf * 0.01).toFixed(2));
      const montantEngagerNeuf = Number((montantDepenseNeuf + interetsMoratoires).toFixed(2));
      const financialFields = [
        'numero_engagement', 'reference_2', 's_lig', 'credit_ouvert_cp', 'credit_ouvert_ce',
        'depenses_anterieures_ce', 'depenses_anterieures_cp', 'depenses_credits_engagement',
        'depenses_credits_consolides', 'depenses_rap', 'montant_depense_neuf',
        'interets_moratoires', 'montant_engager_neuf',
      ];
      financialFields.forEach((field) => {
        if (values[field] !== undefined) updatePayload[field] = values[field] === '' ? null : values[field];
      });
      updatePayload.interets_moratoires = interetsMoratoires;
      updatePayload.montant_engager_neuf = montantEngagerNeuf;
      if (values.date_document) updatePayload.date_consultation = values.date_document;
      if (values.s_lig !== undefined) updatePayload.s_lig = values.s_lig;
    }

    // Récupérer et associer l'entreprise retenue (attributaire / titulaire_nom) en BD
    const chosenAttributaire = values.attributaire || values.titulaire_nom || documentForms['pv_ouverture_attribution']?.attributaire || documentForms['bon_commande']?.titulaire_nom;
    if (chosenAttributaire && typeof chosenAttributaire === 'string' && chosenAttributaire.trim() !== '') {
      try {
        const nomTrimmed = chosenAttributaire.trim();
        const fRes = await api.get(`/fournisseurs?raison_sociale=${encodeURIComponent(nomTrimmed)}`);
        let fournisseurId = Array.isArray(fRes.data) && fRes.data.length > 0 ? fRes.data[0]?.id : null;

        if (!fournisseurId) {
          const newFournisseur = await api.post('/fournisseurs', {
            raison_sociale: nomTrimmed,
            ice: '00' + Math.floor(1000000000000 + Math.random() * 9000000000000),
            if: '00000000',
            adresse: 'Siège social',
            ville: 'Rabat',
            telephone: '0500000000',
            email: `contact@${nomTrimmed.toLowerCase().replace(/[^a-z0-9]/g, '') || 'fournisseur'}.ma`,
            domaine_activite: 'Prestations',
          });
          fournisseurId = newFournisseur.data?.id;
        }

        if (fournisseurId) {
          updatePayload.fournisseur_id = fournisseurId;

          const addressToSave = values.adresse_societe || values.adresse_1 || documentForms['bon_commande']?.adresse_societe;
          const villeToSave = values.ville_societe || values.ville_1 || documentForms['bon_commande']?.ville_societe;
          if (addressToSave || villeToSave) {
            try {
              await api.put(`/fournisseurs/${fournisseurId}`, {
                raison_sociale: nomTrimmed,
                ...(addressToSave ? { adresse: addressToSave } : {}),
                ...(villeToSave ? { ville: villeToSave } : {}),
              });
            } catch (err) {
              console.warn('Erreur mise à jour adresse du fournisseur:', err);
            }
          }
        }
      } catch (e) {
        console.warn('Impossible de lier le fournisseur en base de données:', e);
      }
    }

    try {
      if (Object.keys(updatePayload).length > 0) {
        const response = await api.put(`/consultations/${savedConsultation.id}`, updatePayload);
        if (response.data?.consultation) {
          setSavedConsultation(response.data.consultation);
        }
      }

      // Sauvegarde des informations spécifiques de la commission de réception en base de données
      if (documentId === 'decision_commission_reception' || documentId === 'pv_reception') {
        const recData = {
          numero_bc: values.numero_bc !== undefined ? values.numero_bc : (documentForms.decision_commission_reception?.numero_bc || ''),
          numero_decision: values.numero_decision !== undefined ? values.numero_decision : (documentForms.decision_commission_reception?.numero_decision || ''),
          type_reception: values.type_reception || documentForms.decision_commission_reception?.type_reception || 'définitive',
          date_reception_definitive: values.date_reception_definitive || documentForms.decision_commission_reception?.date_reception_definitive || null,
          periode_du: values.periode_du || documentForms.decision_commission_reception?.periode_du || null,
          periode_au: values.periode_au || documentForms.decision_commission_reception?.periode_au || null,
          prestations_receptionnees: values.prestations_receptionnees || documentForms.decision_commission_reception?.prestations_receptionnees || null,
          date_decision: values.date_decision || values.date_document || documentForms.decision_commission_reception?.date_document,
          date_reunion: values.date_reunion || documentForms.decision_commission_reception?.date_reunion,
          heure_reunion: values.heure_reunion || documentForms.decision_commission_reception?.heure_reunion,
          heure_fin: values.heure_fin || documentForms.decision_commission_reception?.heure_fin,
          lieu_reunion: values.lieu_reunion || documentForms.decision_commission_reception?.lieu_reunion,
          membres_commission: values.membres_commission || documentForms.decision_commission_reception?.membres_commission,
        };
        try {
          const recRes = await api.post(`/consultations/${savedConsultation.id}/reception-commission`, recData);
          if (recRes.data?.consultation) {
            setSavedConsultation(recRes.data.consultation);
          }
        } catch (recErr) {
          console.warn('Erreur lors de la sauvegarde de la commission de réception:', recErr);
        }
      }

      // Synchroniser les lignes du tableau "Consistance de la prestation" en base de données
      if (Array.isArray(values.consistance_lignes) && values.consistance_lignes.length > 0) {
        const prestationsPayload = values.consistance_lignes.map((l) => ({
          numero_prix: l.numero_prix || '1',
          designation: l.designation || 'Prestation',
          specification: l.specification || '',
          unite: l.unite_mesure || 'Unite',
          quantite: Number(l.quantite) || 1,
          prix_unitaire_ht: Number(l.prix_unitaire_ht) || 0,
          tva: Number(l.tva) || 20,
          garantie_exigee: l.garantie_exigee || '',
        }));

        const presRes = await api.post(`/consultations/${savedConsultation.id}/prestations`, { prestations: prestationsPayload });
        if (presRes.data?.consultation) {
          setSavedConsultation(presRes.data.consultation);
        }
      }

      if (isExplicitSave) {
        setSavedDocumentIds((prev) => new Set([...prev, documentId]));
        setMessage('Toutes les informations ont été enregistrées avec succès en base de données !');
        setError('');
      }
    } catch (err) {
      console.error('Erreur lors de la sauvegarde des champs en BD:', err);
      if (isExplicitSave) {
        setError(err.response?.data?.message || 'Erreur lors de l\'enregistrement en base de données.');
      }
    }
  };

  const downloadDocument = async (document) => {
    if (!savedConsultation) {
      setError("Veuillez d'abord sélectionner un dossier depuis la liste des consultations.");
      return;
    }

    const validation = validateDocumentForm(document.id);
    if (!validation.isValid) {
      const missingText = validation.missingLabels?.length > 0
        ? `Veuillez remplir le(s) champ(s) obligatoire(s) suivant(s) avant de télécharger le PDF : ${validation.missingLabels.join(', ')}.`
        : "Veuillez remplir tous les champs obligatoires (*) indiqués en rouge avant de télécharger le PDF.";
      setError(missingText);
      return;
    }

    try {
      setDownloading(document.id);
      setError('');
      await saveDocumentDataToDatabase(document.id, false);
      const payload = { document_data: buildDocumentData(document.id) };
      const endpoint = `/consultations/${savedConsultation.id}/documents/${document.id}`;
      const response = await api.post(endpoint, payload, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = window.document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${String(document.step).padStart(2, '0')}_${document.id}.pdf`);
      window.document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      const msg = await getErrorMessage(err, 'Impossible de générer le document PDF.');
      setError(msg);
    } finally {
      setDownloading(null);
    }
  };

  const isLastDocumentOfPhase = (docId, phaseId) => {
    const docs = documentsByPhase[phaseId] || [];
    if (docs.length === 0) return false;
    return docs[docs.length - 1].id === docId;
  };

  const isPhaseFullyFilled = (phaseId) => {
    if (!savedConsultation) return false;
    const docs = documentsByPhase[phaseId] || [];
    if (docs.length === 0) return false;
    const lastDoc = docs[docs.length - 1];
    const lastFormData = documentForms[lastDoc.id];
    return !!(lastFormData && Object.keys(lastFormData).length > 0);
  };

  const downloadArchive = async (phaseParam = null) => {
    if (!savedConsultation) {
      setError("Veuillez d'abord sélectionner un dossier.");
      return;
    }

    const currentPhase = phaseParam || (activePhase !== 'dashboard' ? activePhase : null);

    try {
      setDownloading('archive');
      setError('');

      // Construire les données saisies réelles de chaque document de la phase
      const docsToExport = currentPhase ? (documentsByPhase[currentPhase] || []) : allDocuments;
      const allDocsData = {};
      docsToExport.forEach((doc) => {
        allDocsData[doc.id] = buildDocumentData(doc.id);
      });

      const queryParam = currentPhase ? `?phase=${encodeURIComponent(currentPhase)}` : '';
      const response = await api.post(
        `/consultations/${savedConsultation.id}/documents/archive-zip${queryParam}`,
        { phase: currentPhase, documents_data: allDocsData },
        { responseType: 'blob' }
      );
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = window.document.createElement('a');
      link.href = url;
      const safeNumero = (savedConsultation?.numero_consultation || '').replace(/[\/\\]/g, '_');
      const filename = currentPhase
        ? `Archive_Phase_${currentPhase}_${safeNumero}.zip`
        : `Dossier_Administratif_${safeNumero}.zip`;
      link.setAttribute('download', filename);
      window.document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      const msg = await getErrorMessage(err, "Impossible de générer l'archive ZIP.");
      setError(msg);
    } finally {
      setDownloading(null);
    }
  };

  const selectDocument = (documentId) => {
    setSelectedDocumentId(documentId);
  };

  const phaseIndex = phases.findIndex((p) => p.id === activePhase);
  const goToPhase = (phaseId) => {
    setActivePhase(phaseId);
    setIsSidebarOpen(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  const goNext = () => {
    if (phaseIndex >= 0 && phaseIndex < phases.length - 1) {
      goToPhase(phases[phaseIndex + 1].id);
    }
  };
  const goPrev = () => {
    if (phaseIndex > 0) {
      goToPhase(phases[phaseIndex - 1].id);
    }
  };

  const selectedDocument = selectedDocumentId
    ? allDocuments.find((d) => d.id === selectedDocumentId)
    : null;
  const selectedFields = selectedDocument ? documentFieldGroups[selectedDocument.id] || [] : [];

  const sidebarNav = [
    ...phases.map((p) => ({ id: p.id, label: p.label, icon: p.icon })),
  ];

  return (
    <div className="relative min-h-screen lg:flex" style={{ backgroundColor: "#f1f5f9" }}>
      {isSidebarOpen && (
        <button
          type="button"
          aria-label="Fermer le menu"
          onClick={() => setIsSidebarOpen(false)}
          className="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
        />
      )}
      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-50 flex h-dvh w-72 shrink-0 flex-col bg-[#0f172a] text-white shadow-xl transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:translate-x-0 ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className="px-5 py-5 border-b border-white/10">
          <Link to="/bons-commande" className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-black text-white text-xs shadow-md">ON</div>
            <div>
              <p className="text-sm font-extrabold text-white tracking-wide">ERP ONCA</p>
              <p className="text-xs text-blue-400 font-semibold">Bon de commande</p>
            </div>
          </Link>
        </div>

        <nav className="flex-1 px-3 py-4 overflow-y-auto">
          <ul className="space-y-1.5">
            {sidebarNav.map((item) => {
              const Icon = item.icon;
              const isActive = activePhase === item.id;
              return (
                <li key={item.id}>
                  <button
                    type="button"
                    onClick={() => goToPhase(item.id)}
                    className={`w-full flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 text-left ${isActive ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
                      }`}
                  >
                    <Icon size={18} />
                    <span>{item.label}</span>
                  </button>
                </li>
              );
            })}

          </ul>
        </nav>

        <div className="px-5 py-4 border-t border-white/10">
          <UserMenu variant="dark" />
        </div>
      </aside>

      {/* Main content */}
      <main className="min-w-0 flex-1 w-full p-3 sm:p-5 lg:p-6">
        <div className="w-full space-y-5">
          {/* Header bar */}
          <div className="bg-white border border-slate-200/80 rounded-2xl px-3 py-3 sm:px-6 sm:py-4 flex items-start sm:items-center justify-between gap-3 shadow-sm">
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setIsSidebarOpen(true)}
                className="lg:hidden p-2 -ml-1 rounded-lg text-slate-600 hover:bg-slate-100"
                aria-label="Ouvrir le menu"
              >
                <Menu size={21} />
              </button>
              <button onClick={() => navigate('/bons-commande')} className="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Retour">
                <ArrowLeft size={18} />
              </button>
              <div className="h-5 w-px bg-slate-200" />
              <div>
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="text-xs font-bold text-blue-600 uppercase tracking-widest">Bon de commande</span>
                  {savedConsultation && (
                    <span className="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-extrabold">
                      N° {savedConsultation.numero_consultation}
                    </span>
                  )}
                </div>
                <h1 className="text-xl font-black text-slate-900 tracking-tight leading-tight">
                  {activePhase === 'registre'
                    ? "Registre d'engagement"
                    : `Phase : ${phases.find((p) => p.id === activePhase)?.label}`}
                </h1>
              </div>
            </div>
            {savedConsultation && (
              <div className="hidden sm:flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                <span className="text-xs font-semibold text-slate-600 max-w-xs truncate">{savedConsultation.objet_consultation}</span>
              </div>
            )}
          </div>

          {message && (
            <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold flex items-center gap-3 shadow-sm animate-fadeIn">
              <CheckCircle2 size={20} />
              {message}
            </div>
          )}
          {error && (
            <div className="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 font-semibold flex items-center gap-3 shadow-sm">
              {error}
            </div>
          )}



          {activePhase === 'registre' && !selectedDocument && (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-xl font-extrabold text-slate-900 flex items-center gap-2">
                    <FileSpreadsheet className="text-blue-700" /> Registre des engagements
                  </h2>
                  
                </div>
                <span className="px-3 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold whitespace-nowrap">
                  {consultationsList.filter((consultation) => consultation.registre_engagement && consultation.type_budget === registreBudget).length} ligne(s)
                </span>
              </div>

              <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-wrap gap-2">
                {['Investissement', 'Fonctionnement'].map((budgetType) => (
                  <button
                    key={budgetType}
                    type="button"
                    onClick={() => setRegistreBudget(budgetType)}
                    className={`px-5 py-2.5 rounded-xl text-sm font-bold transition-colors ${registreBudget === budgetType
                      ? 'bg-blue-700 text-white shadow-sm'
                      : 'bg-white border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-700'
                      }`}
                  >
                    Registre {budgetType}
                  </button>
                ))}
              </div>

              {consultationsLoading ? (
                <div className="p-10 flex items-center justify-center gap-3 text-slate-500">
                  <Loader2 size={20} className="animate-spin" /> Chargement du registre...
                </div>
              ) : consultationsList.filter((consultation) => consultation.registre_engagement && consultation.type_budget === registreBudget).length === 0 ? (
                <div className="p-12 text-center text-slate-500">
                  Aucune fiche d’engagement enregistrée dans le registre {registreBudget.toLowerCase()}.
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-[2450px] w-full text-left border-collapse text-xs">
                    <thead>
                      <tr className="bg-slate-100 border-b border-slate-200 text-slate-700 font-extrabold uppercase tracking-wide">
                        <th className="px-4 py-3">N° ordre</th>
                        <th className="px-4 py-3">N° rubrique / fiche d’engagement</th>
                        <th className="px-4 py-3">Date</th>
                        <th className="px-4 py-3">Mode</th>
                        <th className="px-4 py-3">Référence</th>
                        <th className="px-4 py-3">Référence 2</th>
                        <th className="px-4 py-3">Budget</th>
                        <th className="px-4 py-3">Code</th>
                        <th className="px-3 py-3">ART</th>
                        <th className="px-3 py-3">PAR</th>
                        <th className="px-3 py-3">LIG</th>
                        <th className="px-3 py-3">S/LIG</th>
                        <th className="px-4 py-3">Intitulé</th>
                        <th className="px-4 py-3">Crédit ouvert CP</th>
                        <th className="px-4 py-3">Crédit ouvert CE</th>
                        <th className="px-4 py-3">Crédit consolidé (CC)</th>
                        <th className="px-4 py-3">Dépenses antérieures CE</th>
                        <th className="px-4 py-3">Dépenses antérieures CP</th>
                        <th className="px-4 py-3">Dépenses crédits d’engagement</th>
                        <th className="px-4 py-3">Dépenses crédits consolidés</th>
                        <th className="px-4 py-3">Dépenses reste à payer</th>
                        <th className="px-4 py-3">Dépense neuf</th>
                        <th className="px-4 py-3">Intérêts 1%</th>
                        <th className="px-4 py-3">À engager neuf</th>
                        <th className="px-4 py-3">Objet</th>
                        <th className="px-4 py-3">Bénéficiaire</th>
                        <th className="px-4 py-3 text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {consultationsList.filter((consultation) => consultation.registre_engagement && consultation.type_budget === registreBudget).map((consultation) => {
                        const row = consultation.registre_engagement;
                        const formatDate = (value) => value ? new Date(value).toLocaleDateString('fr-FR') : '-';
                        const formatMoney = (value) => value === null || value === undefined || value === ''
                          ? '-'
                          : Number(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        return (
                          <tr key={row.id} className="hover:bg-blue-50/40 transition-colors">
                            <td className="px-4 py-3 whitespace-nowrap font-bold text-slate-700">{row.numero_ordre || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap font-bold text-blue-700">{row.numero_rubrique || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap">{formatDate(row.date_engagement)}</td>
                            <td className="px-4 py-3 whitespace-nowrap font-semibold">{row.mode_engagement || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap font-bold text-blue-700">{row.reference || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap">{row.reference_2 || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap">{row.budget || '-'}</td>
                            <td className="px-4 py-3 whitespace-nowrap">{row.code || '-'}</td>
                            <td className="px-3 py-3">{row.art || '-'}</td>
                            <td className="px-3 py-3">{row.par || '-'}</td>
                            <td className="px-3 py-3">{row.lig || '-'}</td>
                            <td className="px-3 py-3">{row.s_lig || '-'}</td>
                            <td className="px-4 py-3 max-w-[220px] truncate" title={row.intitule || ''}>{row.intitule || '-'}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.credit_ouvert_cp)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.credit_ouvert_ce)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.credit_consolide)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.depenses_anterieures_ce)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.depenses_anterieures_cp)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.depenses_credits_engagement)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.depenses_credits_consolides)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.depenses_rap)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.montant_depense_neuf)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.interets_moratoires)}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">{formatMoney(row.montant_engager_neuf)}</td>
                            <td className="px-4 py-3 max-w-[240px] truncate" title={row.objet || ''}>{row.objet || '-'}</td>
                            <td className="px-4 py-3 max-w-[180px] truncate" title={row.beneficiaire || ''}>{row.beneficiaire || '-'}</td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">
                              <button
                                type="button"
                                onClick={() => {
                                  selectConsultation(consultation);
                                  setActivePhase('engagement');
                                  setSelectedDocumentId('fiche_engagement');
                                }}
                                className="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold inline-flex items-center gap-1.5"
                              >
                                <Eye size={14} /> Voir la fiche
                              </button>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {activePhase === 'consultation' && !selectedDocument && (
            <div>


              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                    <Building2 className="text-blue-700" /> Base des Consultations
                  </h3>
                  <p className="text-sm text-slate-500 mt-1">Choisir une consultation pour ouvrir son workflow complet.</p>
                </div>
                <button
                  type="button"
                  onClick={() => navigate('/consultations/nouvelle')}
                  className="px-5 py-2.5 bg-emerald-700 text-white font-bold rounded-xl hover:bg-emerald-800 inline-flex items-center gap-2"
                >
                  <Plus size={18} /> Nouvelle consultation
                </button>
              </div>

              {consultationsLoading ? (
                <div className="bg-white rounded-2xl border border-slate-200 p-10 flex items-center justify-center gap-3 text-slate-500">
                  <Loader2 size={20} className="animate-spin" /> Chargement des consultations...
                </div>
              ) : consultationsError ? (
                <div className="bg-red-50 border border-red-200 rounded-2xl p-6 text-red-700 font-semibold flex items-center gap-2">
                  <AlertCircle size={20} /> {consultationsError}
                </div>
              ) : consultationsList.length === 0 ? (
                <div className="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-500">
                  Aucune consultation enregistrée. Cliquez sur « Nouvelle consultation » pour en créer une puis générer l'avis d'achat.
                </div>
              ) : (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                  {/* Search and summary header */}
                  <div className="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="relative w-full sm:w-80">
                      <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                      <input
                        type="text"
                        placeholder="Rechercher une consultation..."
                        value={consultationSearchTerm}
                        onChange={(e) => setConsultationSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all"
                      />
                    </div>
                    <div className="text-xs font-semibold text-slate-500">
                      Affichage de <span className="text-slate-900 font-bold">{filteredConsultationsList.length}</span> sur <span className="text-slate-900 font-bold">{consultationsList.length}</span> consultation(s)
                    </div>
                  </div>

                  {/* Table */}
                  <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                      <thead>
                        <tr className="bg-slate-100/80 border-b border-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider">
                          <th className="px-6 py-4">Réf. Consultation</th>
                          <th className="px-4 py-4">Année</th>
                          <th className="px-6 py-4">Objet de la Consultation</th>
                          <th className="px-4 py-4">Mode d'engagement</th>
                          <th className="px-4 py-4">Statut</th>
                          <th className="px-6 py-4 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 text-sm">
                        {filteredConsultationsList.length === 0 ? (
                          <tr>
                            <td colSpan="6" className="px-6 py-10 text-center text-slate-500">
                              Aucune consultation ne correspond à votre recherche.
                            </td>
                          </tr>
                        ) : (
                          filteredConsultationsList.map((consultation) => {
                            const isCurrent = savedConsultation?.id === consultation.id;
                            return (
                              <tr
                                key={consultation.id}
                                className={`transition-colors hover:bg-slate-50/80 ${isCurrent ? 'bg-blue-50/60 font-medium' : ''
                                  }`}
                              >
                                <td className="px-6 py-4 whitespace-nowrap">
                                  <div className="flex items-center gap-2">
                                    <span className="font-mono font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg text-xs">
                                      {consultation.numero_consultation}
                                    </span>
                                    {isCurrent && (
                                      <span className="px-2 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold uppercase tracking-wide rounded-full">
                                        Sélectionnée
                                      </span>
                                    )}
                                  </div>
                                </td>
                                <td className="px-4 py-4 whitespace-nowrap text-slate-600 font-medium">
                                  {consultation.annee}
                                </td>
                                <td className="px-6 py-4">
                                  <p className="text-slate-900 font-semibold max-w-md line-clamp-2" title={consultation.objet_consultation}>
                                    {consultation.objet_consultation}
                                  </p>
                                </td>
                                <td className="px-4 py-4 whitespace-nowrap">
                                  {consultation.mode_engagement === 'AO' || consultation.mode_engagement === "Appel d'offres" || consultation.mode_engagement === "Appel d'offre" ? (
                                    <span className="inline-block rounded-full bg-violet-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-violet-800">
                                      Appel d'offres
                                    </span>
                                  ) : (
                                    <span className="inline-block rounded-full bg-cyan-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-cyan-800">
                                      Bon de commande
                                    </span>
                                  )}
                                </td>
                                <td className="px-4 py-4 whitespace-nowrap">
                                  <span
                                    className={`px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide inline-block ${consultation.statut_dossier === 'Validé'
                                      ? 'bg-emerald-100 text-emerald-800'
                                      : consultation.statut_dossier === 'En cours'
                                        ? 'bg-amber-100 text-amber-800'
                                        : consultation.statut_dossier === 'Clôturé'
                                          ? 'bg-slate-100 text-slate-700'
                                          : 'bg-blue-100 text-blue-800'
                                      }`}
                                  >
                                    {consultation.statut_dossier}
                                  </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right">
                                  <div className="flex items-center justify-end gap-2">
                                    <button
                                      type="button"
                                      onClick={() => selectConsultation(consultation)}
                                      className={`px-3.5 py-2 font-bold text-xs rounded-xl inline-flex items-center gap-1.5 transition-all shadow-sm ${isCurrent
                                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                        : 'bg-blue-700 hover:bg-blue-800 text-white'
                                        }`}
                                    >
                                      {isCurrent ? (
                                        <>
                                          <Check size={14} /> Sélectionnée
                                        </>
                                      ) : (
                                        <>
                                          <FileText size={14} /> Selectionner la Consultation
                                        </>
                                      )}
                                    </button>

                                    <button
                                      type="button"
                                      onClick={() => openEditConsultationModal(consultation)}
                                      className="px-3 py-2 text-amber-700 hover:bg-amber-100/80 bg-amber-50 border border-amber-200 font-bold text-xs rounded-xl inline-flex items-center gap-1 transition-colors"
                                      title="Modifier la consultation"
                                    >
                                      <Edit3 size={14} />
                                      <span>Modifier</span>
                                    </button>

                                    <button
                                      type="button"
                                      onClick={() => handleDeleteConsultation(consultation)}
                                      className="px-3 py-2 text-red-700 hover:bg-red-100/80 bg-red-50 border border-red-200 font-bold text-xs rounded-xl inline-flex items-center gap-1 transition-colors"
                                      title="Supprimer la consultation"
                                    >
                                      <Trash2 size={14} />
                                      <span>Supprimer</span>
                                    </button>
                                  </div>
                                </td>
                              </tr>
                            );
                          })
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              <div className="mt-8 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <button type="button" onClick={goPrev} className="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 shadow-sm transition-all">
                  <ArrowLeft size={18} /> Précédent
                </button>
                <button type="button" onClick={goNext} className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all">
                  Suivant : Documents de consultation <ArrowRight size={18} />
                </button>
              </div>
            </div>
          )}

          {activePhase !== 'dashboard' && activePhase !== 'consultation' && activePhase !== 'registre' && !selectedDocument && (
            <div className="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-6">
              <button type="button" onClick={goPrev} className="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 shadow-sm transition-all">
                <ArrowLeft size={18} /> Précédent
              </button>
              <button type="button" onClick={goNext} className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all">
                Suivant : {phaseIndex < phases.length - 1 ? phases[phaseIndex + 1].label : 'Fin du processus'} <ArrowRight size={18} />
              </button>
            </div>
          )}

          {activePhase !== 'dashboard' && activePhase !== 'registre' && !selectedDocument && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {(documentsByPhase[activePhase] || []).map((doc) => (
                <div key={doc.id} className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-200 flex flex-col">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="text-xs font-extrabold text-blue-700 uppercase tracking-wide">
                        {String(doc.step).padStart(2, '0')} - {phases.find((p) => p.id === activePhase)?.label}
                      </div>
                      <h3 className="text-base font-extrabold text-slate-900 mt-1">{doc.title}</h3>
                      <p className="text-sm text-slate-600 mt-2">{doc.role}</p>
                    </div>
                    <button
                      type="button"
                      onClick={() => selectDocument(doc.id)}
                      className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shrink-0 shadow-sm transition-colors"
                    >
                      Remplir
                    </button>
                  </div>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {doc.data?.map((item) => (
                      <span key={item} className="px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 text-xs font-semibold">
                        {item}
                      </span>
                    ))}
                  </div>
                </div>
              ))}
              {activePhase === 'engagement' && (
                <div className="bg-white rounded-2xl border border-blue-200/80 p-6 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="text-xs font-extrabold text-blue-700 uppercase tracking-wide">
                        07 - ENGAGEMENT
                      </div>
                      <h3 className="text-base font-extrabold text-slate-900 mt-1 flex items-center gap-2">
                        <FileSpreadsheet size={18} className="text-blue-700" />
                        Registre d'engagement
                      </h3>
                    </div>
                    <button
                      type="button"
                      onClick={() => goToPhase('registre')}
                      className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shrink-0 shadow-sm transition-colors"
                    >
                      Ouvrir
                    </button>
                  </div>
                  <div className="mt-3 flex flex-wrap gap-2">
                    <span className="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold">
                      Toutes les fiches enregistrées
                    </span>
                    <span className="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold">
                      Registre complet
                    </span>
                  </div>
                </div>
              )}
            </div>
          )}

          {selectedDocument && (
            <div className="bg-white rounded-3xl p-4 sm:p-6 lg:p-8 shadow-md border border-slate-100">
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 pb-6 border-b border-slate-100">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-extrabold text-emerald-700 uppercase tracking-wide">
                      Phase : {phases.find((p) => p.id === activePhase)?.label}
                    </span>
                    <span className="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-mono">
                      Étape {selectedDocument.step}/7
                    </span>
                  </div>
                  <h2 className="text-2xl font-extrabold text-slate-900 mt-1">{selectedDocument.title}</h2>
                  <p className="text-sm text-slate-600 mt-1">{selectedDocument.role}</p>
                </div>
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={() => setSelectedDocumentId(null)}
                    className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all"
                  >
                    ← Retour aux documents
                  </button>
                </div>
              </div>

              <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-600 mb-5 bg-slate-50 border border-slate-200/60 px-3.5 py-2 rounded-xl w-fit">
                <span className="text-red-600 font-extrabold text-sm">*</span> : Les champs obligatoires
              </div>

              {selectedDocument.id === 'fiche_engagement' && (
                <div className="mb-6 rounded-2xl border border-blue-200 bg-blue-50/60 p-5">
                  <label className="block text-xs font-extrabold uppercase tracking-wide text-blue-900 mb-2">
                    Numéro du bon de commande
                  </label>
                  <div className="flex flex-col sm:flex-row gap-3">
                    <input
                      type="text"
                      value={documentForms.fiche_engagement?.numero_bc ?? savedConsultation?.numero_bc ?? ''}
                      onChange={(event) => handleDocumentFieldChange('fiche_engagement', 'numero_bc', event.target.value)}
                      onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                          event.preventDefault();
                          findBonCommande();
                        }
                      }}
                      placeholder="BC-2026-00125"
                      className="flex-1 px-4 py-3 rounded-lg border border-blue-200 bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                    <button
                      type="button"
                      onClick={findBonCommande}
                      disabled={searchingBonCommande}
                      className="px-5 py-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold inline-flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                      {searchingBonCommande ? <Loader2 size={17} className="animate-spin" /> : <Search size={17} />}
                      Rechercher
                    </button>
                  </div>
                  {savedConsultation?.numero_bc && (
                    <p className="mt-2 text-xs font-semibold text-emerald-700">Bon de commande trouvé et associé.</p>
                  )}
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-4 sm:gap-5">
                {selectedFields.filter((field) => {
                  if (selectedDocument.id !== 'fiche_engagement') return true;
                  if (String(savedConsultation?.type_budget || '').toLowerCase() !== 'fonctionnement') return true;
                  return !['credit_ouvert_ce', 'depenses_anterieures_ce', 'depenses_credits_engagement'].includes(field.name);
                }).map((field) => {
                  if (field.condition && !field.condition(documentForms[selectedDocument.id])) {
                    return null;
                  }

                  if (field.name === 'motif_ajournement') {
                    const currentNature = documentForms[selectedDocument.id]?.nature_os || "Commencement de l'exécution";
                    if (currentNature !== "Ajournement de l'exécution") {
                      return null;
                    }
                  }

                  const hasError = !!formErrors[selectedDocument.id]?.[field.name];

                  if (field.type === 'prestations_partielles_table') {
                    const srcPrestations = (savedConsultation?.prestations && savedConsultation.prestations.length > 0)
                      ? savedConsultation.prestations
                      : (prestations && prestations.filter((p) => p.designation?.trim()).length > 0 ? prestations.filter((p) => p.designation?.trim()) : []);

                    const currentList = Array.isArray(documentForms[selectedDocument.id]?.prestations_receptionnees) && documentForms[selectedDocument.id].prestations_receptionnees.length > 0
                      ? documentForms[selectedDocument.id].prestations_receptionnees
                      : srcPrestations.map((p, idx) => ({
                        id: p.id || idx,
                        receptionne: true,
                        numero_prix: p.numero_prix || (idx + 1),
                        designation: p.designation || 'Prestation',
                        unite: p.unite || p.unite_mesure || 'Unité',
                        quantite: Number(p.quantite) || 1,
                        quantite_receptionnee: Number(p.quantite) || 1,
                      }));

                    const updateItem = (idx, key, val) => {
                      const updated = currentList.map((item, i) => (i === idx ? { ...item, [key]: val } : item));
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                    };

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <div className="flex items-center justify-between mb-3">
                          <div>
                            <label className="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                              📦 {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                            </label>
                            <p className="text-xs text-slate-500 mt-0.5">
                              Cochez les désignations réceptionnées et ajustez la quantité réceptionnée (≤ quantité commandée).
                            </p>
                          </div>
                        </div>

                        <div className="overflow-x-auto">
                          <table className="w-full bg-white border border-slate-200 rounded-xl overflow-hidden text-xs">
                            <thead>
                              <tr className="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                                <th className="py-2.5 px-3 text-center w-16">Réceptionné</th>
                                <th className="py-2.5 px-3 text-center w-28">N° de prix</th>
                                <th className="py-2.5 px-3 text-left">Désignation des prestations</th>
                                <th className="py-2.5 px-3 text-center w-28">Unité de compte</th>
                                <th className="py-2.5 px-3 text-center w-24">Qté commandée</th>
                                <th className="py-2.5 px-3 text-center w-32">Qté réceptionnée</th>
                              </tr>
                            </thead>
                            <tbody>
                              {currentList.map((item, idx) => (
                                <tr key={idx} className={`border-b border-slate-100 transition-all ${item.receptionne ? 'hover:bg-slate-50' : 'bg-slate-50/70 opacity-60'}`}>
                                  <td className="py-2 px-3 text-center">
                                    <input
                                      type="checkbox"
                                      checked={!!item.receptionne}
                                      onChange={(e) => updateItem(idx, 'receptionne', e.target.checked)}
                                      className="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500 cursor-pointer"
                                    />
                                  </td>
                                  <td className="py-2 px-3 text-center font-bold text-slate-700">
                                    <input
                                      type="text"
                                      value={item.numero_prix !== undefined ? item.numero_prix : (idx + 1)}
                                      disabled={!item.receptionne}
                                      onChange={(e) => updateItem(idx, 'numero_prix', e.target.value)}
                                      className="w-full px-2 py-1.5 rounded border border-slate-200 text-center text-xs font-bold text-slate-800 outline-none focus:border-blue-500 bg-white"
                                    />
                                  </td>
                                  <td className="py-2 px-3 font-semibold text-slate-800">
                                    {item.designation}
                                  </td>
                                  <td className="py-2 px-3 text-center font-medium text-slate-600">
                                    {item.unite || 'Unité'}
                                  </td>
                                  <td className="py-2 px-3 text-center font-bold text-slate-700">
                                    {item.quantite}
                                  </td>
                                  <td className="py-2 px-3 text-center">
                                    <input
                                      type="number"
                                      min="0"
                                      max={item.quantite}
                                      disabled={!item.receptionne}
                                      value={item.quantite_receptionnee !== undefined ? item.quantite_receptionnee : item.quantite}
                                      onChange={(e) => {
                                        const val = Math.min(Number(item.quantite) || 0, Math.max(0, Number(e.target.value) || 0));
                                        updateItem(idx, 'quantite_receptionnee', val);
                                      }}
                                      className="w-20 px-2 py-1.5 rounded border border-slate-200 text-center text-xs font-bold text-emerald-700 outline-none focus:border-emerald-500 bg-white"
                                    />
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      </div>
                    );
                  }

                  if (field.type === 'consistance_table') {
                    const rawLignes = documentForms[selectedDocument.id]?.[field.name] || documentForms['avis_achat']?.[field.name] || documentForms['bon_commande']?.[field.name];
                    const srcPrestations = savedConsultation?.prestations?.length > 0
                      ? savedConsultation.prestations
                      : prestations.filter(p => p.designation?.trim());

                    const initialLignes = srcPrestations.length > 0
                      ? srcPrestations.map((p, i) => ({
                        numero_prix: p.numero_prix || String(i + 1),
                        designation: p.designation || '',
                        specification: p.specification || p.designation || '',
                        unite_mesure: p.unite || p.unite_mesure || 'Unite',
                        quantite: String(p.quantite || 1),
                        prix_unitaire_ht: String(p.prix_unitaire_ht || 0),
                        tva: String(p.tva || 20),
                        garantie_exigee: p.garantie_exigee || '',
                      }))
                      : [{ numero_prix: '1', designation: '', specification: '', unite_mesure: 'Unite', quantite: '1', prix_unitaire_ht: '0', tva: '20', garantie_exigee: '' }];

                    const lignes = Array.isArray(rawLignes) && rawLignes.length > 0 ? rawLignes : initialLignes;

                    const updateLigne = (idx, key, val) => {
                      const updated = lignes.map((l, i) => {
                        if (key === 'tva' && idx === 0) {
                          return { ...l, tva: val };
                        }
                        return i === idx ? { ...l, [key]: val } : l;
                      });
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      if (selectedDocument.id !== 'avis_achat') handleDocumentFieldChange('avis_achat', field.name, updated);
                      if (selectedDocument.id !== 'bon_commande') handleDocumentFieldChange('bon_commande', field.name, updated);
                    };
                    const addLigne = () => {
                      const firstTva = lignes[0]?.tva ?? '20';
                      const updated = [
                        ...lignes,
                        { numero_prix: String(lignes.length + 1), designation: '', specification: '', unite_mesure: 'Unite', quantite: '1', prix_unitaire_ht: '0', tva: String(firstTva), garantie_exigee: '' },
                      ];
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      if (selectedDocument.id !== 'avis_achat') handleDocumentFieldChange('avis_achat', field.name, updated);
                      if (selectedDocument.id !== 'bon_commande') handleDocumentFieldChange('bon_commande', field.name, updated);
                    };
                    const removeLigne = (idx) => {
                      if (lignes.length <= 1) return;
                      const updated = lignes.filter((_, i) => i !== idx);
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      if (selectedDocument.id !== 'avis_achat') handleDocumentFieldChange('avis_achat', field.name, updated);
                      if (selectedDocument.id !== 'bon_commande') handleDocumentFieldChange('bon_commande', field.name, updated);
                    };

                    const colStyle = { fontSize: '11px', fontWeight: 700, color: '#475569', textTransform: 'uppercase', letterSpacing: '0.5px', marginBottom: '4px', display: 'block' };

                    const isAvisAchat = selectedDocument.id === 'avis_achat';
                    const showPrices = selectedDocument.id !== 'avis_achat';
                    const gridCols = isAvisAchat
                      ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8'
                      : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-9';

                    const isCellBlank = (v) => v === undefined || v === null || String(v).trim() === '';
                    const getCellInputCls = (v) => `w-full px-2 py-2 rounded-lg border ${hasError && isCellBlank(v)
                      ? 'border-red-500 bg-red-50/40 text-red-900 focus:ring-1 focus:ring-red-300'
                      : 'border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-100'
                      } text-xs font-semibold outline-none transition-all`;

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full">
                        <div className="flex items-center justify-between mb-3">
                          <span className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            📋 {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                          </span>
                          <button
                            type="button"
                            onClick={addLigne}
                            className="px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg inline-flex items-center gap-1 shadow-sm transition-all"
                          >
                            <Plus size={14} /> Ajouter une ligne
                          </button>
                        </div>

                        <div className="space-y-3">
                          {lignes.map((ligne, idx) => (
                            <div key={idx} className="bg-slate-50 border border-slate-200 rounded-xl p-4 relative">
                              <div className="absolute top-3 right-3 flex items-center gap-1">
                                <span className="text-xs font-bold text-slate-400 mr-1">#{idx + 1}</span>
                                {lignes.length > 1 && (
                                  <button
                                    type="button"
                                    onClick={() => removeLigne(idx)}
                                    className="p-1 text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                    title="Supprimer cette ligne"
                                  >
                                    <Trash2 size={14} />
                                  </button>
                                )}
                              </div>
                              <div className={`grid ${gridCols} gap-3 pr-8`}>
                                <div>
                                  <span style={colStyle}>N° Prix</span>
                                  <input type="text" value={ligne.numero_prix} onChange={e => updateLigne(idx, 'numero_prix', e.target.value)} className={getCellInputCls(ligne.numero_prix)} placeholder="1" />
                                </div>
                                <div className="col-span-2">
                                  <span style={colStyle}>Désignation</span>
                                  <input type="text" value={ligne.designation} onChange={e => updateLigne(idx, 'designation', e.target.value)} className={getCellInputCls(ligne.designation)} placeholder="Soudeuse à pédale" />
                                </div>
                                <div>
                                  <span style={colStyle}>Unité</span>
                                  <input type="text" value={ligne.unite_mesure} onChange={e => updateLigne(idx, 'unite_mesure', e.target.value)} className={getCellInputCls(ligne.unite_mesure)} placeholder="Unite, Forfait" />
                                </div>
                                <div>
                                  <span style={colStyle}>Quantité</span>
                                  <input type="number" value={ligne.quantite} onChange={e => updateLigne(idx, 'quantite', e.target.value)} className={getCellInputCls(ligne.quantite)} placeholder="1" min="1" />
                                </div>
                                {isAvisAchat && (
                                  <div>
                                    <span style={colStyle}>TVA (%)</span>
                                    <input
                                      type="number"
                                      value={idx === 0 ? (ligne.tva ?? '20') : (lignes[0]?.tva ?? '20')}
                                      onChange={e => updateLigne(0, 'tva', e.target.value)}
                                      disabled={idx > 0}
                                      className={`${getCellInputCls(ligne.tva)} ${idx > 0 ? 'bg-slate-100/90 text-slate-500 cursor-not-allowed border-slate-200' : ''}`}
                                      placeholder="20"
                                      min="0"
                                      max="100"
                                      title={idx > 0 ? "La TVA est identique pour toutes les lignes (définie par la 1ère ligne)" : ""}
                                    />
                                  </div>
                                )}
                                {showPrices && (
                                  <>
                                    <div>
                                      <span style={colStyle}>P.U HT (DH)</span>
                                      <input type="number" value={ligne.prix_unitaire_ht || ''} onChange={e => updateLigne(idx, 'prix_unitaire_ht', e.target.value)} className={getCellInputCls(ligne.prix_unitaire_ht)} placeholder="0.00" step="0.01" />
                                    </div>
                                    <div>
                                      <span style={colStyle}>Montant HT</span>
                                      <input type="text" value={(Number(ligne.quantite || 0) * Number(ligne.prix_unitaire_ht || 0)).toFixed(2)} readOnly className="w-full px-2 py-2 rounded-lg border border-slate-200 bg-slate-100 font-bold text-blue-700 text-xs cursor-not-allowed outline-none" />
                                    </div>
                                  </>
                                )}
                                <div className="col-span-2">
                                  <span style={colStyle}>Garantie Exigée</span>
                                  <input type="text" value={ligne.garantie_exigee || ''} onChange={e => updateLigne(idx, 'garantie_exigee', e.target.value)} className={getCellInputCls(ligne.garantie_exigee)} placeholder="Facultative (ex: 12 mois...)" />
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    );
                  }

                  if (field.type === 'concurrents_table') {
                    const list = Array.isArray(documentForms[selectedDocument.id]?.[field.name])
                      ? documentForms[selectedDocument.id][field.name]
                      : [
                        { nom: 'TOPOGRAPHY CONSULTING', montant: 30240 },
                        { nom: 'LANDMAP SURVEY', montant: 36360 },
                        { nom: 'BUREAU ALAOUI TOPO', montant: 39060 },
                        { nom: 'BAJITOP', montant: 45240 },
                        { nom: 'GOLDEN GEO', montant: 47520 },
                      ];

                    const currentAttr = documentForms[selectedDocument.id]?.attributaire || '';
                    const syncRefusedWithAttr = (updatedList) => {
                      if (currentAttr) {
                        const foundIndex = updatedList.findIndex((c) => c.nom === currentAttr);
                        if (foundIndex !== -1) {
                          const refusedCompanies = updatedList.slice(0, foundIndex).map((c) => ({
                            nom: c.nom,
                            motif: "Refus d'invitation du maître d'ouvrage",
                          }));
                          handleDocumentFieldChange(selectedDocument.id, 'societes_refusees', refusedCompanies);
                        }
                      }
                    };

                    const updateConcurrent = (idx, key, val) => {
                      const updated = list.map((c, i) => (i === idx ? { ...c, [key]: val } : c));
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      syncRefusedWithAttr(updated);
                    };
                    const addConcurrent = () => {
                      const updated = [
                        ...list,
                        { nom: '', montant: '' },
                      ];
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      syncRefusedWithAttr(updated);
                    };
                    const removeConcurrent = (idx) => {
                      if (list.length <= 1) return;
                      const updated = list.filter((_, i) => i !== idx);
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      syncRefusedWithAttr(updated);
                    };

                    const handleExcelImport = (e) => {
                      const file = e.target.files?.[0];
                      if (!file) return;

                      const reader = new FileReader();

                      reader.onload = (evt) => {
                        try {
                          let rows = [];
                          const fileName = file.name.toLowerCase();

                          if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
                            const data = new Uint8Array(evt.target.result);
                            const wb = XLSX.read(data, { type: 'array' });
                            const wsName = wb.SheetNames[0];
                            const ws = wb.Sheets[wsName];
                            rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
                          } else {
                            let text = '';
                            if (typeof evt.target.result === 'string') {
                              text = evt.target.result;
                            } else {
                              const decoder = new TextDecoder('utf-8', { fatal: false });
                              text = decoder.decode(evt.target.result);
                            }

                            const lines = text.split(/\r\n|\n|\r/);
                            rows = lines
                              .filter((line) => line.trim() !== '')
                              .map((line) => {
                                let delimiter = ';';
                                if (line.includes(';')) delimiter = ';';
                                else if (line.includes('\t')) delimiter = '\t';
                                else if (line.includes(',')) delimiter = ',';
                                return line.split(delimiter).map((c) => c.trim().replace(/^["']|["']$/g, ''));
                              });
                          }

                          if (!rows || rows.length === 0) {
                            alert("Le fichier sélectionné est vide.");
                            return;
                          }

                          let extractedRef = '';
                          let extractedObjet = '';

                          for (let i = 0; i < Math.min(rows.length, 7); i++) {
                            const rowStr = (rows[i] || []).map((c) => String(c).toLowerCase()).join(' ');
                            if (rowStr.includes('référence') || rowStr.includes('reference')) {
                              const valCol = (rows[i] || []).find((c, idx) => idx > 0 && String(c).trim() !== '');
                              if (valCol) extractedRef = String(valCol).trim();
                            }
                            if (rowStr.includes('objet')) {
                              const valCol = (rows[i] || []).find((c, idx) => idx > 0 && String(c).trim() !== '');
                              if (valCol) extractedObjet = String(valCol).trim();
                            }
                          }

                          let headerRowIndex = -1;
                          let colClassement = -1;
                          let colNom = -1;
                          let colMontant = -1;

                          for (let i = 0; i < rows.length; i++) {
                            const row = rows[i];
                            if (!Array.isArray(row)) continue;

                            let hasNameCol = false;
                            let hasAmountCol = false;
                            let cIdx = -1, nIdx = -1, mIdx = -1;

                            row.forEach((cell, colIdx) => {
                              const s = String(cell).toLowerCase().trim();
                              if (s.includes('classement') || s.includes('rang') || s === 'n°' || s === 'no' || s === '#') {
                                cIdx = colIdx;
                              }
                              if (s.includes('entreprise') || s.includes('société') || s.includes('societe') || s.includes('concurrent') || s.includes('dépositaire') || s.includes('fournisseur') || s === 'nom') {
                                nIdx = colIdx;
                                hasNameCol = true;
                              }
                              if (s.includes('total') || s.includes('ttc') || s.includes('ht') || s.includes('montant') || s.includes('prix') || s.includes('offre')) {
                                mIdx = colIdx;
                                hasAmountCol = true;
                              }
                            });

                            if (hasNameCol || (cIdx !== -1 && mIdx !== -1) || (hasAmountCol && nIdx !== -1)) {
                              headerRowIndex = i;
                              colClassement = cIdx;
                              colNom = nIdx;
                              colMontant = mIdx;
                              break;
                            }
                          }

                          const parseMoney = (val) => {
                            if (typeof val === 'number') return val;
                            if (!val) return 0;
                            const clean = String(val).replace(/\s+/g, '').replace(/DH|MAD/gi, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
                            const parsed = parseFloat(clean);
                            return isNaN(parsed) ? 0 : parsed;
                          };

                          const imported = [];
                          const startRow = headerRowIndex !== -1 ? headerRowIndex + 1 : 0;

                          for (let i = startRow; i < rows.length; i++) {
                            const row = rows[i];
                            if (!row || row.length === 0) continue;

                            let rank = '';
                            let nom = '';
                            let montant = 0;

                            if (headerRowIndex !== -1) {
                              if (colClassement !== -1) rank = row[colClassement];
                              if (colNom !== -1) nom = row[colNom];
                              if (colMontant !== -1) montant = parseMoney(row[colMontant]);
                            }

                            if (!nom || String(nom).trim() === '') {
                              if (row.length >= 3 && (typeof row[0] === 'number' || /^\d+$/.test(String(row[0]).trim()))) {
                                rank = row[0];
                                nom = String(row[1] || '').trim();
                                montant = parseMoney(row[2]);
                              } else if (row.length >= 2) {
                                nom = String(row[0] || '').trim();
                                montant = parseMoney(row[1]);
                              }
                            }

                            nom = String(nom || '').trim();
                            if (
                              !nom ||
                              nom.toLowerCase().includes('synthèse') ||
                              nom.toLowerCase().includes('entreprise') ||
                              nom.toLowerCase().includes('nom des concurrents') ||
                              nom.toLowerCase().includes('total') ||
                              nom.toLowerCase().includes('direction régionale') ||
                              nom.toLowerCase() === 'référence' ||
                              nom.toLowerCase() === 'objet'
                            ) {
                              continue;
                            }

                            const parsedRank = parseInt(rank, 10);
                            imported.push({
                              nom: nom,
                              montant: montant || 0,
                              classement: !isNaN(parsedRank) && parsedRank > 0 ? parsedRank : null
                            });
                          }

                          if (imported.length === 0) {
                            alert("Aucun concurrent valide n'a été trouvé dans le fichier. Veuillez vérifier la structure (colonnes: Classement, Entreprise, Montant TTC).");
                            return;
                          }

                          imported.sort((a, b) => {
                            if (a.classement && b.classement) return a.classement - b.classement;
                            if (a.montant > 0 && b.montant > 0) return a.montant - b.montant;
                            return 0;
                          });

                          imported.forEach((item, index) => {
                            if (!item.classement) {
                              item.classement = index + 1;
                            }
                          });

                          handleDocumentFieldChange(selectedDocument.id, field.name, imported);
                          syncRefusedWithAttr(imported);

                          const winner = imported.find((c) => c.classement === 1) || imported[0];
                          if (winner && winner.nom) {
                            handleDocumentFieldChange(selectedDocument.id, 'attributaire', winner.nom);
                            handleDocumentFieldChange(selectedDocument.id, 'montant_apres_verification', winner.montant);
                            handleDocumentFieldChange('bon_commande', 'titulaire_nom', winner.nom);
                            handleDocumentFieldChange('bon_commande', 'societe', winner.nom);
                            handleDocumentFieldChange('ordre_commande', 'societe', winner.nom);
                          }

                          if (extractedRef && !documentForms[selectedDocument.id]?.numero_consultation) {
                            handleDocumentFieldChange(selectedDocument.id, 'numero_consultation', extractedRef);
                          }
                          if (extractedObjet && !documentForms[selectedDocument.id]?.objet) {
                            handleDocumentFieldChange(selectedDocument.id, 'objet', extractedObjet);
                          }

                          setMessage(`${imported.length} concurrent(s) importé(s) avec classement ! Offre retenue : ${winner.nom} (${winner.montant ? winner.montant.toLocaleString('fr-FR') : 0} DH TTC).`);
                        } catch (err) {
                          console.error(err);
                          alert("Erreur lors de la lecture du fichier Excel.");
                        }
                      };

                      if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                        reader.readAsArrayBuffer(file);
                      } else {
                        reader.readAsText(file);
                      }
                      e.target.value = '';
                    };

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
                          <label className="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            🏢 {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                          </label>

                          <div className="flex flex-wrap items-center gap-2">
                            <label className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg inline-flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                              <FileSpreadsheet size={14} /> Importer Excel / CSV
                              <input
                                type="file"
                                accept=".xlsx, .xls, .csv, .tsv, .txt"
                                onChange={handleExcelImport}
                                className="hidden"
                              />
                            </label>

                            <button
                              type="button"
                              onClick={addConcurrent}
                              className="px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg inline-flex items-center gap-1 shadow-sm transition-all"
                            >
                              <Plus size={14} /> Ajouter un concurrent
                            </button>
                          </div>
                        </div>

                        <div className="overflow-x-auto">
                          <table className="w-full bg-white border border-slate-200 rounded-xl overflow-hidden text-xs">
                            <thead>
                              <tr className="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                                <th className="py-2.5 px-3 text-center w-36">Classement</th>
                                <th className="py-2.5 px-3 text-left">Nom des concurrents / Société</th>
                                <th className="py-2.5 px-3 text-right w-48">Montant de l'offre (TTC)</th>
                                <th className="py-2.5 px-3 text-center w-12"></th>
                              </tr>
                            </thead>
                            <tbody>
                              {list.map((c, idx) => {
                                const rank = c.classement || idx + 1;
                                const isWinner = rank === 1;
                                return (
                                  <tr key={idx} className={`border-b border-slate-100 hover:bg-slate-50 transition-all ${isWinner ? 'bg-emerald-50/50' : ''}`}>
                                    <td className="py-2 px-3 text-center font-bold">
                                      <span className={`px-2.5 py-1 rounded-full text-[11px] font-extrabold inline-block ${isWinner
                                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm'
                                        : 'bg-slate-100 text-slate-700 border border-slate-200'
                                        }`}>
                                        {rank === 1 ? '🥇 1er (Moins-disant)' : `${rank}ème`}
                                      </span>
                                    </td>
                                    <td className="py-2 px-3">
                                      <input
                                        type="text"
                                        value={c.nom}
                                        onChange={(e) => updateConcurrent(idx, 'nom', e.target.value)}
                                        placeholder="Ex: CATALYSSIA BUSINESS COMPANY SARL"
                                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                                      />
                                    </td>
                                    <td className="py-2 px-3">
                                      <input
                                        type="number"
                                        value={c.montant}
                                        onChange={(e) => updateConcurrent(idx, 'montant', e.target.value)}
                                        placeholder="45313.4"
                                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold text-slate-800 text-right outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                                      />
                                    </td>
                                    <td className="py-2 px-3 text-center">
                                      {list.length > 1 && (
                                        <button
                                          type="button"
                                          onClick={() => removeConcurrent(idx)}
                                          className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                          title="Supprimer ce concurrent"
                                        >
                                          <Trash2 size={14} />
                                        </button>
                                      )}
                                    </td>
                                  </tr>
                                );
                              })}
                            </tbody>
                          </table>
                        </div>
                      </div>
                    );
                  }

                  if (field.type === 'societes_refusees_input') {
                    const rawVal = documentForms[selectedDocument.id]?.[field.name];
                    const currentList = Array.isArray(rawVal)
                      ? rawVal
                      : typeof rawVal === 'string' && rawVal.trim() !== ''
                        ? rawVal.split(',').map((s) => ({ nom: s.trim(), motif: "Refus d'invitation du maître d'ouvrage" }))
                        : [];

                    const concurrentsList = Array.isArray(documentForms[selectedDocument.id]?.concurrents)
                      ? documentForms[selectedDocument.id].concurrents
                      : [];

                    const standardMotifs = [
                      "Refus d'invitation du maître d'ouvrage",
                      "Offre supérieure au budget estimatif",
                      "Offre financière non conforme / Erreur de calcul",
                      "Dossier administratif ou technique incomplet",
                      "Non-respect des spécifications du cahier des charges",
                      "Abandon ou retrait de l'offre par le concurrent",
                      "Autre motif (à préciser ci-dessous)"
                    ];

                    const syncAutoAttributaire = (updatedRefusedList) => {
                      if (!concurrentsList || concurrentsList.length === 0) return;

                      const refusedNames = updatedRefusedList.map((r) => String(r.nom || '').trim().toLowerCase());
                      const eligible = concurrentsList.find((c) => !refusedNames.includes(String(c.nom || '').trim().toLowerCase()));

                      if (eligible && eligible.nom) {
                        handleDocumentFieldChange(selectedDocument.id, 'attributaire', eligible.nom);
                        if (eligible.montant) {
                          handleDocumentFieldChange(selectedDocument.id, 'montant_apres_verification', eligible.montant);
                        }
                        const rank = eligible.classement || (concurrentsList.findIndex((c) => c.nom === eligible.nom) + 1);
                        handleDocumentFieldChange(
                          selectedDocument.id,
                          'motif_attribution',
                          `Offre la moins disante conforme (classée N°${rank}) après examen des devis reçus`
                        );
                        handleDocumentFieldChange('bon_commande', 'titulaire_nom', eligible.nom);
                        handleDocumentFieldChange('bon_commande', 'societe', eligible.nom);
                        handleDocumentFieldChange('ordre_commande', 'societe', eligible.nom);
                      }
                    };

                    const addRefusedCompany = (companyName = '') => {
                      const defaultMotif = "Refus d'invitation du maître d'ouvrage";
                      let targetName = companyName;
                      if (!targetName && concurrentsList && concurrentsList.length > 0) {
                        const existingRefusedNames = currentList.map((r) => String(r.nom || '').trim().toLowerCase());
                        const firstUnused = concurrentsList.find((c) => !existingRefusedNames.includes(String(c.nom || '').trim().toLowerCase()));
                        if (firstUnused) {
                          targetName = firstUnused.nom;
                        }
                      }
                      const updated = [...currentList, { nom: targetName, motif: defaultMotif }];
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      syncAutoAttributaire(updated);
                    };

                    const updateRefused = (idx, key, value) => {
                      const updated = currentList.map((item, i) => (i === idx ? { ...item, [key]: value } : item));
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      if (key === 'nom') {
                        syncAutoAttributaire(updated);
                      }
                    };

                    const removeRefused = (idx) => {
                      const updated = currentList.filter((_, i) => i !== idx);
                      handleDocumentFieldChange(selectedDocument.id, field.name, updated);
                      syncAutoAttributaire(updated);
                    };

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
                          <label className="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            ❌ {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                          </label>

                          <div className="flex flex-wrap items-center gap-2">
                            <button
                              type="button"
                              onClick={() => addRefusedCompany('')}
                              className="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg inline-flex items-center gap-1 shadow-sm transition-all"
                            >
                              <Plus size={14} /> Ajouter manuellement
                            </button>
                          </div>
                        </div>

                        <div className="space-y-3">
                          {currentList.map((item, idx) => {
                            const takenByOthers = currentList
                              .filter((_, i) => i !== idx)
                              .map((r) => String(r.nom || '').trim().toLowerCase());

                            const availableOptions = concurrentsList.filter((c) => {
                              const normName = String(c.nom || '').trim().toLowerCase();
                              const isSelf = normName === String(item.nom || '').trim().toLowerCase();
                              return isSelf || !takenByOthers.includes(normName);
                            });

                            return (
                              <div key={idx} className="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-white p-3.5 border border-slate-200 rounded-xl shadow-sm">
                                <div className="w-full sm:w-1/3">
                                  <span className="block text-[10px] font-bold text-slate-500 uppercase mb-1">Société écartée</span>
                                  {concurrentsList.length > 0 ? (
                                    <select
                                      value={item.nom}
                                      onChange={(e) => updateRefused(idx, 'nom', e.target.value)}
                                      className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-red-500 focus:bg-white"
                                    >
                                      <option value="">-- Choisir la société --</option>
                                      {availableOptions.map((c, i) => (
                                        <option key={i} value={c.nom}>
                                          {c.nom}
                                        </option>
                                      ))}
                                      {!availableOptions.some((c) => c.nom === item.nom) && item.nom && (
                                        <option value={item.nom}>{item.nom}</option>
                                      )}
                                    </select>
                                  ) : (
                                    <input
                                      type="text"
                                      value={item.nom}
                                      onChange={(e) => updateRefused(idx, 'nom', e.target.value)}
                                      placeholder="Ex: TOPOGRAPHY CONSULTING"
                                      className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-red-500"
                                    />
                                  )}
                                </div>

                                <div className="w-full sm:w-2/3 flex items-center gap-2">
                                  <div className="flex-1 space-y-1">
                                    <span className="block text-[10px] font-bold text-slate-500 uppercase">Motif d'écartement</span>
                                    <input
                                      type="text"
                                      list={`standard-motifs-list-${idx}`}
                                      value={item.motif}
                                      onChange={(e) => updateRefused(idx, 'motif', e.target.value)}
                                      placeholder="Ex: Refus d'invitation du maître d'ouvrage"
                                      className="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-red-500 bg-white"
                                    />
                                    <datalist id={`standard-motifs-list-${idx}`}>
                                      {standardMotifs.map((m, i) => (
                                        <option key={i} value={m} />
                                      ))}
                                    </datalist>
                                  </div>

                                  <button
                                    type="button"
                                    onClick={() => removeRefused(idx)}
                                    className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-all shrink-0 self-end mb-1"
                                    title="Annuler l'écartement de cette société"
                                  >
                                    <Trash2 size={15} />
                                  </button>
                                </div>
                              </div>
                            );
                          })}

                          {currentList.length === 0 && (
                            <div className="p-4 border border-dashed border-slate-300 rounded-xl text-center text-xs text-slate-500 bg-white">
                              Aucune société refusée enregistrée (Néant).
                            </div>
                          )}
                        </div>
                      </div>
                    );
                  }

                  if (field.type === 'attributaire_selector') {
                    const concurrentsList = Array.isArray(documentForms[selectedDocument.id]?.concurrents)
                      ? documentForms[selectedDocument.id].concurrents
                      : [];
                    const selectedAttr = documentForms[selectedDocument.id]?.[field.name] || '';
                    const motifAttr = documentForms[selectedDocument.id]?.motif_attribution || '';

                    const handleSelectAttr = (e) => {
                      const selectedNom = e.target.value;
                      handleDocumentFieldChange(selectedDocument.id, field.name, selectedNom);

                      if (!selectedNom) return;

                      const foundIndex = concurrentsList.findIndex((c) => c.nom === selectedNom);
                      if (foundIndex !== -1) {
                        const found = concurrentsList[foundIndex];
                        if (found && found.montant) {
                          handleDocumentFieldChange(selectedDocument.id, 'montant_apres_verification', found.montant);
                        }

                        const rank = found.classement || (foundIndex + 1);
                        handleDocumentFieldChange(
                          selectedDocument.id,
                          'motif_attribution',
                          `Offre la moins disante conforme (classée N°${rank}) retenue par le maître d'ouvrage`
                        );
                      }

                      handleDocumentFieldChange('bon_commande', 'titulaire_nom', selectedNom);
                      handleDocumentFieldChange('bon_commande', 'societe', selectedNom);
                      handleDocumentFieldChange('ordre_commande', 'societe', selectedNom);
                    };

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                        <div className="flex items-center justify-between">
                          <label className="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            🏆 {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                          </label>
                          {selectedAttr && (
                            <span className="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-[11px] font-extrabold">
                              Société retenue : {selectedAttr}
                            </span>
                          )}
                        </div>

                        <select
                          value={selectedAttr}
                          onChange={handleSelectAttr}
                          className="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                          <option value="">-- Sélectionner la société retenue parmi les concurrents --</option>
                          {concurrentsList.map((c, i) => (
                            <option key={i} value={c.nom}>
                              {c.classement || i + 1}. {c.nom} {c.montant ? `(${Number(c.montant).toLocaleString('fr-FR')} DH TTC)` : ''}
                            </option>
                          ))}
                        </select>

                        <div className="pt-2">
                          <label className="block text-[10px] font-bold text-slate-500 uppercase mb-1 flex items-center gap-1.5">
                            📝 Motif du choix de l'offre retenue
                          </label>
                          <input
                            type="text"
                            value={motifAttr}
                            onChange={(e) => handleDocumentFieldChange(selectedDocument.id, 'motif_attribution', e.target.value)}
                            placeholder="Ex: Offre la moins disante conforme retenue par le maître d'ouvrage"
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                          />
                        </div>

                      </div>
                    );
                  }

                  if (field.type === 'commission_selector') {
                    const isPV = selectedDocument.id === 'pv_ouverture_attribution' || selectedDocument.id === 'pv_reception';
                    const decMembres = selectedDocument.id === 'pv_reception'
                      ? (documentForms.decision_commission_reception?.membres_commission || savedConsultation?.reception_commission?.membres_commission || savedConsultation?.receptionCommission?.membres_commission)
                      : documentForms.decision_commission_ouverture?.membres_commission;
                    const currentCommissionList = isPV && Array.isArray(decMembres) && decMembres.length > 0
                      ? decMembres
                      : (Array.isArray(documentForms[selectedDocument.id]?.[field.name])
                        ? documentForms[selectedDocument.id][field.name]
                        : []);

                    return (
                      <div key={`${selectedDocument.id}-${field.name}`} className="col-span-full bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-3">
                          <label className="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <Users size={16} className="text-blue-700" />
                            {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                          </label>

                        </div>

                        {!isPV && (
                          /* Barre d'ajout d'un membre au document */
                          <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 mb-4">
                            <div className="sm:col-span-6 flex gap-2">
                              <select
                                value={nouveauMembreForm.membre_id}
                                onChange={(e) => setNouveauMembreForm({ ...nouveauMembreForm, membre_id: e.target.value })}
                                className="flex-1 px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                              >
                                <option value="">-- Choisir un membre depuis la base --</option>
                                {membresCatalog.map((m) => (
                                  <option key={m.id} value={m.id}>
                                    {m.nom_prenom} ({m.fonction || 'Membre'})
                                  </option>
                                ))}
                              </select>
                              <button
                                type="button"
                                onClick={() => setIsQuickMembreModalOpen(true)}
                                className="px-3 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shrink-0 inline-flex items-center gap-1 shadow-sm transition-all"
                                title="Créer un nouveau membre dans le répertoire"
                              >
                                <Plus size={16} /> Nouveau
                              </button>
                            </div>

                            <div className="sm:col-span-4">
                              <select
                                value={nouveauMembreForm.qualite}
                                onChange={(e) => setNouveauMembreForm({ ...nouveauMembreForm, qualite: e.target.value })}
                                className="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                              >
                                <option value="Président">Président(e)</option>
                                <option value="Membre">Membre1</option>
                                <option value="Membre">Membre2</option>

                              </select>
                            </div>

                            <div className="sm:col-span-2">
                              <button
                                type="button"
                                disabled={!nouveauMembreForm.membre_id}
                                onClick={() => addMembreToDocumentCommission(selectedDocument.id, field.name)}
                                className="w-full h-full py-2.5 bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white font-bold text-xs rounded-xl inline-flex items-center justify-center gap-1 shadow-sm transition-all"
                              >
                                <Plus size={14} /> Ajouter
                              </button>
                            </div>
                          </div>
                        )}

                        {/* Liste des membres ajoutés */}
                        {currentCommissionList.length > 0 ? (
                          <div className="space-y-2">
                            {currentCommissionList.map((m, idx) => (
                              <div key={idx} className="flex items-center justify-between p-3.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-slate-300 transition-all">
                                <div className="flex items-center gap-3">
                                  <span className={`px-3 py-1 rounded-lg text-xs font-extrabold tracking-wide uppercase ${m.qualite === 'Président' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                                    m.qualite === 'Rapporteur' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                                      'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                    }`}>
                                    {m.qualite}
                                  </span>
                                  <div>
                                    <p className="text-sm font-extrabold text-slate-900">{m.nom || m.nom_prenom}</p>
                                    <p className="text-xs font-medium text-slate-500">{m.fonction || 'Membre de commission'}</p>
                                  </div>
                                </div>
                                {!isPV && (
                                  <button
                                    type="button"
                                    onClick={() => removeMembreFromDocumentCommission(selectedDocument.id, field.name, idx)}
                                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                    title="Retirer ce membre de la commission"
                                  >
                                    <Trash2 size={16} />
                                  </button>
                                )}
                              </div>
                            ))}
                          </div>
                        ) : (
                          <div className="p-4 border border-dashed border-slate-300 rounded-xl text-center text-xs font-semibold text-slate-500 bg-white">
                            Aucun membre sélectionné pour cette commission. Choisissez un membre ci-dessus et cliquez sur « Ajouter ».
                          </div>
                        )}
                      </div>
                    );
                  }

                  const fieldClass = `w-full px-4 py-3 rounded-lg border outline-none transition-all ${hasError
                    ? 'border-red-400 bg-red-50/30 text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-100'
                    : 'border-slate-200 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-50/80 hover:border-slate-300'
                    }`;

                  const maxLen = fieldMaxLengthMap[field.name];
                  const currentVal = (documentForms[selectedDocument.id]?.[field.name] !== undefined &&
                    documentForms[selectedDocument.id]?.[field.name] !== null)
                    ? documentForms[selectedDocument.id][field.name]
                    : '';
                  const displayedVal = selectedDocument.id === 'fiche_engagement' && field.name === 'interets_moratoires'
                    ? ((Number(documentForms.fiche_engagement?.montant_depense_neuf) || 0) * 0.01).toFixed(2)
                    : selectedDocument.id === 'fiche_engagement' && field.name === 'montant_engager_neuf'
                      ? ((Number(documentForms.fiche_engagement?.montant_depense_neuf) || 0) * 1.01).toFixed(2)
                      : currentVal;
                  const currentLen = String(displayedVal || '').length;

                  return (
                    <label key={`${selectedDocument.id}-${field.name}`} className="block">
                      <span className="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>
                          {field.label} {field.required && !field.fromDb && <span className="text-red-600 font-bold ml-0.5">*</span>}
                        </span>
                        {maxLen && (
                          <span className={`text-[10px] font-mono px-2 py-0.5 rounded-full font-bold transition-all ${currentLen === maxLen
                            ? 'bg-amber-100 text-amber-800 border border-amber-300 font-extrabold'
                            : 'text-slate-400 bg-slate-100'
                            }`}>
                            {currentLen} / {maxLen} chiffres max
                          </span>
                        )}
                      </span>

                      {field.type === 'select' ? (
                        <select
                          value={documentForms[selectedDocument.id]?.[field.name] || field.options?.[0] || ''}
                          onChange={(event) => handleDocumentFieldChange(selectedDocument.id, field.name, event.target.value)}
                          disabled={field.readOnly}
                          className={fieldClass}
                        >
                          {(field.options || []).map((opt) => (
                            <option key={opt} value={opt}>
                              {opt}
                            </option>
                          ))}
                        </select>
                      ) : (
                        <input
                          type={field.type || 'text'}
                          maxLength={maxLen || undefined}
                          inputMode={maxLen ? 'numeric' : undefined}
                          value={displayedVal}
                          readOnly={field.readOnly}
                          onChange={(event) => {
                            let val = event.target.value;
                            if (maxLen) {
                              val = val.replace(/\D/g, '').slice(0, maxLen);
                            }
                            handleDocumentFieldChange(selectedDocument.id, field.name, val);
                          }}
                          placeholder={field.placeholder || ''}
                          className={`${fieldClass} ${field.readOnly ? 'bg-slate-100 text-slate-600 cursor-not-allowed' : ''}`}
                        />
                      )}
                    </label>
                  );
                })}
              </div>

              <div className="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100 pt-6">

                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    disabled={!savedConsultation}
                    onClick={() => saveDocumentDataToDatabase(selectedDocument.id, true)}
                    className="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold inline-flex items-center gap-2 disabled:opacity-50 transition-all shadow-md shadow-blue-500/20 hover:shadow-lg hover:shadow-blue-500/30"
                    title="Enregistrer les informations modifiées dans la base de données"
                  >
                    <Save size={18} />
                    Enregistrer
                  </button>
                  <button
                    type="button"
                    disabled={!savedConsultation || downloading === selectedDocument.id}
                    onClick={() => downloadDocument(selectedDocument)}
                    className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold inline-flex items-center gap-2 disabled:opacity-50 transition-all shadow-md shadow-emerald-500/20"
                  >
                    {downloading === selectedDocument.id ? <Loader2 size={18} className="animate-spin" /> : <Download size={18} />}
                    Télécharger le PDF
                  </button>
                </div>

                {/* Bouton Suivant → document suivant dans la phase */}
                {(() => {
                  const phaseDocs = documentsByPhase[activePhase] || [];
                  const currentIdx = phaseDocs.findIndex((d) => d.id === selectedDocument.id);
                  const nextDoc = phaseDocs[currentIdx + 1];
                  if (!nextDoc) return null;
                  const isSaved = savedDocumentIds.has(selectedDocument.id);
                  return (
                    <button
                      type="button"
                      disabled={!isSaved}
                      onClick={() => isSaved && setSelectedDocumentId(nextDoc.id)}
                      className={`px-6 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition-all shadow-md ${isSaved
                        ? 'bg-emerald-600 hover:bg-emerald-700 hover:shadow-lg text-white cursor-pointer'
                        : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                        }`}
                      title={isSaved ? `Aller au document suivant : ${nextDoc.title}` : 'Veuillez d’abord cliquer sur « Enregistrer en BD » pour continuer'}
                    >
                      {nextDoc.title}
                      <ArrowRight size={18} />
                    </button>
                  );
                })()}
              </div>
            </div>
          )}
        </div>
      </main>

      {/* Modal de création rapide d'un membre de la commission */}
      {isQuickMembreModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100">
            <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
              <h3 className="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                <Users size={20} className="text-blue-700" />
                Nouveau membre de commission
              </h3>
              <button
                type="button"
                onClick={() => setIsQuickMembreModalOpen(false)}
                className="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all"
              >
                <X size={18} />
              </button>
            </div>

            {quickMembreError && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold flex items-center gap-2">
                <AlertCircle size={16} /> {quickMembreError}
              </div>
            )}

            <form onSubmit={handleQuickMembreSubmit} className="space-y-4">
 

              <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setIsQuickMembreModalOpen(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={quickMembreSaving}
                  className="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-xl inline-flex items-center gap-1.5 disabled:opacity-50"
                >
                  {quickMembreSaving ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
                  Enregistrer & Sélectionner
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de Modification d'une Consultation */}
      {isEditConsultationModalOpen && editingConsultationData && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 my-8 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2 text-slate-800 font-extrabold text-base">
                <Edit3 className="text-amber-600" size={20} />
                <span>Modifier la consultation : {editingConsultationData.numero_consultation}</span>
              </div>
              <button
                type="button"
                onClick={() => setIsEditConsultationModalOpen(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all"
              >
                <X size={18} />
              </button>
            </div>

            {editingError && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold flex items-center gap-2">
                <AlertCircle size={16} /> {editingError}
              </div>
            )}

            <form onSubmit={handleSaveEditedConsultation} className="space-y-4 max-h-[75vh] overflow-y-auto pr-1">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">N° Consultation</label>
                  <input
                    type="text"
                    required
                    value={editingConsultationData.numero_consultation}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, numero_consultation: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Statut du dossier</label>
                  <select
                    value={editingConsultationData.statut_dossier}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, statut_dossier: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  >
                    <option value="Programmation">Programmation</option>
                    <option value="En cours">En cours</option>
                    <option value="Validé">Validé</option>
                    <option value="Clôturé">Clôturé</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Objet de la consultation</label>
                <textarea
                  required
                  rows={2}
                  value={editingConsultationData.objet_consultation}
                  onChange={(e) => setEditingConsultationData({ ...editingConsultationData, objet_consultation: e.target.value })}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Numéro de décision d'ouverture de commission</label>
                  <input
                    type="text"
                    value={editingConsultationData.numero_decision}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, numero_decision: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">N° Bon de commande</label>
                  <input
                    type="text"
                    value={editingConsultationData.numero_bc}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, numero_bc: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Date limite devis</label>
                  <input
                    type="date"
                    min="2020-01-01"
                    max="2099-12-31"
                    value={editingConsultationData.date_limite_devis}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, date_limite_devis: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Heure limite</label>
                  <input
                    type="text"
                    placeholder="Ex: 10:00"
                    value={editingConsultationData.heure_limite_devis}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, heure_limite_devis: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Délai (jours)</label>
                  <input
                    type="number"
                    value={editingConsultationData.delai_execution}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, delai_execution: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Année</label>
                  <input
                    type="number"
                    min="2020"
                    max="2099"
                    onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }}
                    value={editingConsultationData.annee}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, annee: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Date de consultation</label>
                  <input
                    type="date"
                    min="2020-01-01"
                    max="2099-12-31"
                    value={editingConsultationData.date_consultation}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, date_consultation: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Lieu d'exécution</label>
                <input
                  type="text"
                  value={editingConsultationData.lieu_execution}
                  onChange={(e) => setEditingConsultationData({ ...editingConsultationData, lieu_execution: e.target.value })}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Catégorie</label>
                  <select
                    value={editingConsultationData.categorie}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, categorie: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  >
                    <option value="Travaux">Travaux</option>
                    <option value="Fournitures">Fournitures</option>
                    <option value="Services">Services</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Intitulé (Nature de la prestation)</label>
                  <input
                    type="text"
                    value={editingConsultationData.intitule || editingConsultationData.type_prestation || ''}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, intitule: e.target.value, type_prestation: e.target.value })}
                    placeholder="Ex: Prestation de même nature / Achat de matériel..."
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Mode d'engagement</label>
                  <select
                    value={editingConsultationData.mode_engagement}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, mode_engagement: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  >
                    <option value="BC">BC (Bon de commande)</option>
                    <option value="AO">AO (Appel d'offres)</option>
                    <option value="Convention">Convention</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Type de budget</label>
                  <select
                    value={editingConsultationData.type_budget}
                    onChange={(e) => setEditingConsultationData({ ...editingConsultationData, type_budget: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                  >
                    <option value="Fonctionnement">Fonctionnement</option>
                    <option value="Investissement">Investissement</option>
                  </select>
                </div>
              </div>

              {/* Section Budgétaire */}
              <div className="pt-3 border-t border-slate-100">
                <p className="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3">Imputation Budgétaires</p>
                <div className="grid grid-cols-3 gap-3 mb-3">
                  <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">ART</label>
                    <input
                      type="text"
                      value={editingConsultationData.art}
                      onChange={(e) => {
                        const art = e.target.value;
                        const par = editingConsultationData.par || '';
                        const lig = editingConsultationData.lig || '';
                        setEditingConsultationData({ ...editingConsultationData, art, code_imputation: `${art}${par}${lig}` });
                      }}
                      className="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">PAR</label>
                    <input
                      type="text"
                      value={editingConsultationData.par}
                      onChange={(e) => {
                        const art = editingConsultationData.art || '';
                        const par = e.target.value;
                        const lig = editingConsultationData.lig || '';
                        setEditingConsultationData({ ...editingConsultationData, par, code_imputation: `${art}${par}${lig}` });
                      }}
                      className="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">LIG</label>
                    <input
                      type="text"
                      value={editingConsultationData.lig}
                      onChange={(e) => {
                        const art = editingConsultationData.art || '';
                        const par = editingConsultationData.par || '';
                        const lig = e.target.value;
                        setEditingConsultationData({ ...editingConsultationData, lig, code_imputation: `${art}${par}${lig}` });
                      }}
                      className="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Code d'imputation</label>
                    <input
                      readOnly
                      type="text"
                      value={editingConsultationData.code_imputation}
                      className="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-600 font-mono text-xs font-bold outline-none cursor-not-allowed"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Exercice budgétaire</label>
                    <input
                      type="number"
                      min="2020"
                      max="2099"
                      onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }}
                      value={editingConsultationData.exercice_budgetaire}
                      onChange={(e) => setEditingConsultationData({ ...editingConsultationData, exercice_budgetaire: e.target.value })}
                      className="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold outline-none focus:border-blue-600"
                    />
                  </div>
                </div>
              </div>



              <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setIsEditConsultationModalOpen(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={editingSaving}
                  className="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl inline-flex items-center gap-1.5 disabled:opacity-50"
                >
                  {editingSaving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
                  Enregistrer les modifications
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default BonCommandePlateforme;
