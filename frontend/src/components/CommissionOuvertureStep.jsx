import React, { useState, useMemo, useEffect } from 'react';
import {
  FileText,
  Users,
  Upload,
  Trash2,
  Plus,
  Search,
  CheckCircle2,
  XCircle,
  X,
  Loader2,
  AlertTriangle,
  Lock,
  Unlock,
  Printer,
  FileDown,
  Clock,
  History,
  Info,
  ChevronDown,
  ChevronRight,
  Sparkles,
  ShieldCheck,
  Award,
  Filter,
  DollarSign,
  HelpCircle,
  FileSpreadsheet,
  FileSignature,
  Eye,
  Download
} from 'lucide-react';
import * as XLSX from 'xlsx';
import api from '../api/axios';
import IdentificationConsultationCard from './IdentificationConsultationCard';

const REJECTION_REASONS = [
  'Pièce manquante',
  'Caution absente',
  'Dossier incomplet',
  'Hors délai',
  'Signature absente',
  'Autre'
];

export default function CommissionOuvertureStep({
  formData,
  handleChange,
  handleCompanyChange,
  importPreview = [],
  setImportPreview,
  handleImportFileChange,
  sendBulkFournisseurs,
  clearImport,
  importing,
  importErrors,
  saving,
  validateCommission,
  downloadDocument,
  id,
  setSuccessMessage,
  setErrorMessage,
  fournisseurs = [],
  refreshFournisseurs,
  handlePasserAttribution
}) {
  // --- STATE ---
  const isLocked = Boolean(formData.commission_validee);

  // Fournisseur Modal State
  const [showAddModal, setShowAddModal] = useState(false);
  const [addMode, setAddMode] = useState('existant'); // 'existant' or 'nouveau'
  const [selectedFournisseurId, setSelectedFournisseurId] = useState('');
  const [newFournisseurData, setNewFournisseurData] = useState({
    raison_sociale: '', ice: '', if: '', rc: '', adresse: '', ville: '', email: '', telephone: ''
  });
  const [isSubmittingFournisseur, setIsSubmittingFournisseur] = useState(false);

  // Dynamic Commission Members
  const [commissionMembers, setCommissionMembers] = useState(() => {
    if (Array.isArray(formData.commission_members) && formData.commission_members.length > 0) {
      return formData.commission_members;
    }
    return [
      { id: '1', role: 'Président', nom_prenom: formData.president_commission || '', fonction: 'Directeur Régional', organisme: 'DRCA-RSK' },
      { id: '2', role: 'Membre', nom_prenom: formData.rapporteur_commission || '', fonction: 'Chef de Service Marchés', organisme: 'DRCA-RSK' },
      { id: '3', role: 'Membre', nom_prenom: 'Mme. Salma BENANI', fonction: 'Administrateur', organisme: 'DRCA-RSK' },
      { id: '4', role: 'Membre', nom_prenom: 'M. Omar CHRAIBI', fonction: 'Représentant Trésorerie', organisme: 'Ministère des Finances' },
    ];
  });

  // Keep parent state updated with commission members
  useEffect(() => {
    if (typeof handleChange === 'function') {
      const e = { target: { name: 'commission_members', value: commissionMembers } };
      handleChange(e);
    }
  }, [commissionMembers]);

  // Dynamic References Juridiques
  const [referencesJuridiques, setReferencesJuridiques] = useState(() => {
    if (Array.isArray(formData.references_juridiques) && formData.references_juridiques.length > 0) {
      return formData.references_juridiques;
    }
    return [
      { id: '1', type: 'Loi', numero: '58-12', date: '16 Janvier 2013', objet: 'portant création de l\'Office National du Conseil Agricole (ONCA)', texte_complet: 'Vu la loi 58-12 portant création de l\'Office National du Conseil Agricole (ONCA) promulguée par le Dahir n° 1-12-67 du 16 Janvier 2013 ;' },
      { id: '2', type: 'Décret', numero: 'n°2.22.431', date: '8 mars 2023', objet: 'relatif au marchés publics', texte_complet: 'Vu le décret n°2.22.431 du 15 chaabane 1444 (8 mars 2023), relatif au marchés publics ;' }
    ];
  });

  useEffect(() => {
    if (typeof handleChange === 'function') {
      const e = { target: { name: 'references_juridiques', value: referencesJuridiques } };
      handleChange(e);
    }
  }, [referencesJuridiques]);

  // Import Meta
  const [importMeta, setImportMeta] = useState(() => ({
    count: importPreview.length,
    date: new Date().toLocaleDateString('fr-FR') + ' à ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
    user: 'Agent des Marchés (Directeur)'
  }));

  useEffect(() => {
    if (importPreview.length > 0 && importMeta.count !== importPreview.length) {
      setImportMeta(prev => ({
        ...prev,
        count: importPreview.length,
        date: new Date().toLocaleDateString('fr-FR') + ' à ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      }));
    }
  }, [importPreview.length]);

  // Lock Metadata
  const [lockMeta, setLockMeta] = useState(() => ({
    date: formData.date_validation_commission || '',
    user: formData.user_validation_commission || 'Directeur Régional ONCA'
  }));

  // Toast Notifications
  const [toasts, setToasts] = useState([]);

  const addToast = (type, message) => {
    const id = Date.now();
    setToasts(prev => [...prev, { id, type, message }]);
    setTimeout(() => {
      setToasts(prev => prev.filter(t => t.id !== id));
    }, 4000);
  };

  // Audit History Log
  const [historyLogs, setHistoryLogs] = useState(() => [
    { id: 1, date: new Date().toLocaleDateString('fr-FR'), time: '09:00', user: 'Système', action: 'Création du dossier AOO', badge: 'bg-blue-100 text-blue-800' },
    ...(importPreview.length > 0 ? [{ id: 2, date: importMeta.date.split(' à ')[0], time: importMeta.date.split(' à ')[1] || '10:15', user: importMeta.user, action: `Import Excel de ${importPreview.length} soumissionnaires`, badge: 'bg-indigo-100 text-indigo-800' }] : []),
    ...(isLocked ? [{ id: 3, date: lockMeta.date || new Date().toLocaleDateString('fr-FR'), time: '11:30', user: lockMeta.user, action: 'Clôture et verrouillage définitif de la séance', badge: 'bg-emerald-100 text-emerald-800' }] : [])
  ]);

  const logAction = (actionText, badgeColor = 'bg-blue-100 text-blue-800') => {
    const now = new Date();
    const newLog = {
      id: Date.now(),
      date: now.toLocaleDateString('fr-FR'),
      time: now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
      user: 'Agent Connecté',
      action: actionText,
      badge: badgeColor
    };
    setHistoryLogs(prev => [newLog, ...prev]);
  };

  // Search & Filter State
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all'); // all, admis, rejetes, retenu
  const [showValidationModal, setShowValidationModal] = useState(false);
  const [showPvModal, setShowPvModal] = useState(false);

  // Dynamic ranking and automated calculation of financial & final status
  // --- ANALYSE DES ENTREPRISES & LOGIQUE DE PROGRESSION EXACTE ---
  const analyzedCompanies = useMemo(() => {
    if (!importPreview || importPreview.length === 0) return [];

    const companies = importPreview.map((company, index) => {
      const ht = parseFloat(company.montant_ht || company.montant_propose || 0);
      const tvaRate = parseFloat(company.tva_rate || 20);
      const ttc = company.montant_ttc ? parseFloat(company.montant_ttc) : (ht > 0 ? ht * (1 + tvaRate / 100) : 0);

      // 1. Dossier Admin
      const adminStatut = company.admin_statut || (company.admin_conforme === false ? 'Rejeté' : 'Admis sans réserve');
      const isAdminRejected = adminStatut === 'Rejeté';

      // 2. Offre Tech
      const techStatut = isAdminRejected ? 'Non examiné' : (company.tech_statut || (company.tech_conforme === false ? 'Rejeté' : 'Admis sans réserve'));
      const isTechRejected = isAdminRejected || techStatut === 'Rejeté';

      // 3. Offre Fin
      const finStatut = isTechRejected ? 'Non examiné' : (company.fin_statut || 'Admis sans réserve');
      const isFinRejected = isTechRejected || finStatut === 'Rejeté';

      // 4. Après Vérif
      const verifStatut = isFinRejected ? 'Non examiné' : (company.verif_statut || 'Admis sans réserve');

      // Globalement admis si aucune étape n'est rejetée
      const isGloballyAdmis = !isAdminRejected && !isTechRejected && !isFinRejected && verifStatut !== 'Rejeté' && verifStatut !== 'Non examiné';

      let computedStatut = 'Rejeté';
      if (isGloballyAdmis) {
        computedStatut = 'Admis';
      }

      return {
        ...company,
        __originalIndex: index,
        computedHT: isTechRejected ? 0 : ht,
        computedTTC: isTechRejected ? 0 : ttc,
        computedStatut,
        adminStatut,
        isAdminRejected,
        techStatut,
        isTechRejected,
        finStatut,
        isFinRejected,
        verifStatut,
        isGloballyAdmis,
      };
    });

    const admisCompanies = companies
      .filter(c => c.isGloballyAdmis)
      .sort((a, b) => {
        const montantA = a.montant_rectifie ? parseFloat(a.montant_rectifie) : a.computedTTC;
        const montantB = b.montant_rectifie ? parseFloat(b.montant_rectifie) : b.computedTTC;
        return montantA - montantB;
      });

    const rankMap = new Map();
    admisCompanies.forEach((comp, i) => {
      rankMap.set(comp.__originalIndex, i + 1);
    });

    return companies.map(c => {
      const rank = rankMap.get(c.__originalIndex);
      let finalStatus = c.computedStatut;
      let classementDisplay = '-';

      if (rank !== undefined) {
        classementDisplay = rank === 1 ? '1er (Moins-disant)' : `${rank}ème`;
      }

      return {
        ...c,
        calculatedRank: rank || 999,
        classementDisplay,
        finalStatus
      };
    });
  }, [importPreview]);

  // Filtered Companies for display
  const filteredCompanies = useMemo(() => {
    return analyzedCompanies.filter(c => {
      const name = (c.raison_sociale || '').toLowerCase();
      const ice = (c.ice || '').toLowerCase();
      const search = searchTerm.toLowerCase();

      const matchesSearch = name.includes(search) || ice.includes(search);

      if (!matchesSearch) return false;

      if (filterStatus === 'admis') return c.isGloballyAdmis;
      if (filterStatus === 'rejetes') return !c.isGloballyAdmis;
      if (filterStatus === 'retenus') return c.calculatedRank === 1;
      return true;
    });
  }, [analyzedCompanies, searchTerm, filterStatus]);

  // Handler de mise à jour des statuts avec cascade automatique
  const updateStepStatus = (idx, stepKey, newStatus) => {
    if (isLocked) return;
    const currentCompanies = Array.isArray(importPreview) ? [...importPreview] : [];
    const comp = { ...currentCompanies[idx] };

    if (stepKey === 'admin') {
      comp.admin_statut = newStatus;
      if (newStatus === 'Rejeté') {
        comp.admin_conforme = false;
        comp.tech_statut = 'Non examiné';
        comp.tech_motif = '-';
        comp.tech_conforme = false;
        comp.fin_statut = 'Non examiné';
        comp.fin_motif = '-';
        comp.montant_ht = 0;
        comp.montant_ttc = 0;
        comp.verif_statut = 'Non examiné';
        comp.verif_motif = '-';
        comp.montant_rectifie = 0;
      } else {
        comp.admin_conforme = true;
        if (comp.tech_statut === 'Non examiné' || !comp.tech_statut) comp.tech_statut = 'Admis sans réserve';
        if (comp.fin_statut === 'Non examiné' || !comp.fin_statut) comp.fin_statut = 'Admis sans réserve';
        if (comp.verif_statut === 'Non examiné' || !comp.verif_statut) comp.verif_statut = 'Admis sans réserve';
        if (newStatus === 'Admis sans réserve' && (!comp.admin_motif || comp.admin_motif === '')) comp.admin_motif = '-';
      }
    } else if (stepKey === 'tech') {
      comp.tech_statut = newStatus;
      if (newStatus === 'Rejeté') {
        comp.tech_conforme = false;
        comp.fin_statut = 'Non examiné';
        comp.fin_motif = '-';
        comp.montant_ht = 0;
        comp.montant_ttc = 0;
        comp.verif_statut = 'Non examiné';
        comp.verif_motif = '-';
        comp.montant_rectifie = 0;
      } else {
        comp.tech_conforme = true;
        if (comp.fin_statut === 'Non examiné' || !comp.fin_statut) comp.fin_statut = 'Admis sans réserve';
        if (comp.verif_statut === 'Non examiné' || !comp.verif_statut) comp.verif_statut = 'Admis sans réserve';
        if (newStatus === 'Admis sans réserve' && (!comp.tech_motif || comp.tech_motif === '')) comp.tech_motif = '-';
      }
    } else if (stepKey === 'fin') {
      comp.fin_statut = newStatus;
      if (newStatus === 'Rejeté') {
        comp.verif_statut = 'Non examiné';
        comp.verif_motif = '-';
        comp.montant_rectifie = 0;
      } else {
        if (comp.verif_statut === 'Non examiné' || !comp.verif_statut) comp.verif_statut = 'Admis sans réserve';
        if (newStatus === 'Admis sans réserve' && (!comp.fin_motif || comp.fin_motif === '')) comp.fin_motif = '-';
      }
    } else if (stepKey === 'verif') {
      comp.verif_statut = newStatus;
      if (newStatus === 'Admis sans réserve' && (!comp.verif_motif || comp.verif_motif === '')) comp.verif_motif = '-';
    }

    currentCompanies[idx] = comp;
    if (typeof setImportPreview === 'function') {
      setImportPreview(currentCompanies);
    }
  };

  // Handlers for Members
  const handleAddMember = () => {
    if (isLocked) return;
    const newId = String(Date.now());
    const newMember = {
      id: newId,
      role: 'Membre',
      nom_prenom: '',
      fonction: 'Membre de commission',
      organisme: 'DRCA-RSK'
    };
    setCommissionMembers(prev => [...prev, newMember]);
    logAction('Ajout d\'un membre à la commission', 'bg-blue-100 text-blue-800');
    addToast('info', 'Nouveau membre ajouté');
  };

  const handleRemoveMember = (memberId) => {
    if (isLocked) return;
    setCommissionMembers(prev => prev.filter(m => m.id !== memberId));
    logAction('Suppression d\'un membre de la commission', 'bg-amber-100 text-amber-800');
  };

  const [downloadingTableauPdf, setDownloadingTableauPdf] = useState(false);
  const [previewingTableauPdf, setPreviewingTableauPdf] = useState(false);

  const handlePreviewTableauPdf = async () => {
    if (!id || id === 'nouveau') {
      addToast('error', "Veuillez d'abord enregistrer le dossier AOO.");
      return;
    }

    try {
      setPreviewingTableauPdf(true);
      addToast('info', "Chargement de l'aperçu du tableau...");

      const payload = {
        concurrents: analyzedCompanies
      };

      const response = await api.post(`/aoos/${id}/documents/tableau-examen-offres`, payload, {
        responseType: 'blob'
      });

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      window.open(url, '_blank');
      logAction('Aperçu du tableau d\'examen des offres en PDF', 'bg-blue-100 text-blue-800');
    } catch (err) {
      console.error('Erreur aperçu tableau PDF:', err);
      addToast('error', "Erreur lors de l'ouverture de l'aperçu du tableau.");
    } finally {
      setPreviewingTableauPdf(false);
    }
  };

  const handleDownloadTableauPdf = async () => {
    if (!id || id === 'nouveau') {
      addToast('error', "Veuillez d'abord enregistrer le dossier AOO.");
      return;
    }

    try {
      setDownloadingTableauPdf(true);
      addToast('info', "Génération du tableau officiel en PDF...");

      const payload = {
        concurrents: analyzedCompanies
      };

      const response = await api.post(`/aoos/${id}/documents/tableau-examen-offres`, payload, {
        responseType: 'blob'
      });

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      const cleanNum = (formData.num_aoo || 'AOO').replace(/[\/\s]/g, '_');
      link.setAttribute('download', `Tableau_Examen_Offres_${cleanNum}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      addToast('success', "Tableau PDF téléchargé avec succès !");
      logAction('Téléchargement du tableau d\'examen des offres en PDF', 'bg-indigo-100 text-indigo-800');
    } catch (err) {
      console.error('Erreur génération tableau PDF:', err);
      addToast('error', "Erreur lors de la génération du tableau en PDF.");
    } finally {
      setDownloadingTableauPdf(false);
    }
  };

  const handleGenerateResultatAO = () => {
    if (typeof downloadDocument === 'function') {
      downloadDocument('resultat-aoo');
    }
  };

  // --- IMPORT EXCEL PERSONNALISÉ CONFORME AU MODÈLE DRCA-RSK ---
  const handleExcelImport = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (evt) => {
      try {
        const data = new Uint8Array(evt.target?.result);
        const wb = XLSX.read(data, { type: 'array' });
        const wsName = wb.SheetNames[0];
        const ws = wb.Sheets[wsName];
        const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

        if (!rows || rows.length === 0) {
          addToast('error', 'Le fichier Excel est vide.');
          return;
        }

        // Détection de la ligne de démarrage des données
        let dataStartRow = 0;
        for (let i = 0; i < Math.min(rows.length, 12); i++) {
          const rowText = rows[i].join(' ').toLowerCase();
          if (rowText.includes('dépositaire') || rowText.includes('dossier administratif') || rowText.includes('offre technique') || rowText.includes('offre financière')) {
            if (i + 1 < rows.length && (rows[i + 1].join(' ').toLowerCase().includes('statut') || rows[i + 1].join(' ').toLowerCase().includes('motif'))) {
              dataStartRow = i + 2;
            } else {
              dataStartRow = i + 1;
            }
            break;
          }
        }

        const parseMoney = (val) => {
          if (typeof val === 'number') return val;
          if (!val) return 0;
          const clean = String(val).replace(/\s+/g, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
          const parsed = parseFloat(clean);
          return isNaN(parsed) ? 0 : parsed;
        };

        const normalizeStatus = (rawVal) => {
          if (!rawVal) return 'Admis sans réserve';
          const s = String(rawVal).trim().toLowerCase();
          if (s.includes('avec réserve') || s.includes('avec reserve')) {
            return 'Admis avec réserve';
          }
          if (s.includes('rejet') || s.includes('non conforme') || s.includes('écart') || s === '0' || s === 'faux' || s === 'false') {
            return 'Rejeté';
          }
          if (s.includes('non examiné') || s.includes('non examine')) {
            return 'Non examiné';
          }
          return 'Admis sans réserve';
        };

        const parsedCompanies = [];
        for (let i = dataStartRow; i < rows.length; i++) {
          const row = rows[i];
          if (!row || row.length === 0) continue;

          // Colonne 0 : Nom du dépositaire de l'offre
          const nom = String(row[0] || '').trim();
          if (!nom || nom.toLowerCase().includes('total') || nom.toLowerCase().includes('direction régionale') || nom.toLowerCase().includes('nom du dépositaire')) {
            continue;
          }

          // Step 1: Dossier Admin
          const adminStatut = normalizeStatus(row[1]);
          let adminMotif = String(row[2] || '').trim();
          if (adminStatut === 'Admis sans réserve' && (!adminMotif || adminMotif === '')) adminMotif = '-';
          const isAdminRejected = adminStatut === 'Rejeté';

          // Step 2: Offre Tech
          const techStatut = isAdminRejected ? 'Non examiné' : normalizeStatus(row[3]);
          let techMotif = isAdminRejected ? '-' : String(row[4] || '').trim();
          if (techStatut === 'Admis sans réserve' && (!techMotif || techMotif === '')) techMotif = '-';
          const isTechRejected = isAdminRejected || techStatut === 'Rejeté';

          // Step 3: Offre Fin
          const finStatut = isTechRejected ? 'Non examiné' : normalizeStatus(row[6]);
          let finMotif = isTechRejected ? '-' : String(row[7] || '').trim();
          if (finStatut === 'Admis sans réserve' && (!finMotif || finMotif === '')) finMotif = '-';
          const isFinRejected = isTechRejected || finStatut === 'Rejeté';

          // Step 4: Après Vérif
          const verifStatut = isFinRejected ? 'Non examiné' : normalizeStatus(row[9]);
          let verifMotif = isFinRejected ? '-' : String(row[10] || '').trim();
          if (verifStatut === 'Admis sans réserve' && (!verifMotif || verifMotif === '')) verifMotif = '-';

          const montantHt = isTechRejected ? 0 : (parseMoney(row[5]) || 0);
          const montantRectifie = isFinRejected ? 0 : (parseMoney(row[8]) > 0 ? parseMoney(row[8]) : montantHt);

          const existingF = (fournisseurs || []).find(f => (f.raison_sociale || '').toLowerCase().trim() === nom.toLowerCase());

          parsedCompanies.push({
            id: existingF ? existingF.id : `import_${Date.now()}_${i}`,
            fournisseur_id: existingF ? existingF.id : null,
            raison_sociale: nom,
            ice: existingF ? (existingF.ice || '') : '',
            adresse: existingF ? (existingF.adresse || '') : '',
            ville: existingF ? (existingF.ville || '') : '',
            admin_statut: adminStatut,
            admin_conforme: !isAdminRejected,
            admin_motif: adminMotif,
            admin_motif_rejet: isAdminRejected ? adminMotif : '',
            tech_statut: techStatut,
            tech_conforme: !isTechRejected,
            tech_motif: techMotif,
            tech_motif_rejet: techStatut === 'Rejeté' ? techMotif : '',
            montant_ht: montantHt,
            tva_rate: 20,
            montant_ttc: Math.round(montantHt * 1.2 * 100) / 100,
            fin_statut: finStatut,
            fin_motif: finMotif,
            montant_rectifie: montantRectifie,
            verif_statut: verifStatut,
            verif_motif: verifMotif,
            observations: [adminMotif, techMotif, finMotif, verifMotif].filter(m => m && m !== '-').join(' | '),
          });
        }

        if (parsedCompanies.length === 0) {
          addToast('error', 'Aucune ligne de soumissionnaire détectée dans le fichier.');
          return;
        }

        if (typeof setImportPreview === 'function') {
          setImportPreview(parsedCompanies);
        }

        addToast('success', `${parsedCompanies.length} soumissionnaire(s) importé(s) avec succès !`);
        logAction(`Import Excel : ${parsedCompanies.length} soumissionnaires`, 'bg-emerald-100 text-emerald-800');
      } catch (err) {
        console.error('Erreur import Excel:', err);
        addToast('error', `Erreur lors de la lecture du fichier Excel: ${err.message || err}`);
      }
    };
    reader.readAsArrayBuffer(file);
    e.target.value = '';
  };

  const handleDownloadExcelTemplate = () => {
    const headerRow1 = [
      'Nom du dépositaire de l\'offre',
      'Dossier administratif et technique',
      '',
      'Offre technique',
      '',
      'Offre financière',
      '',
      '',
      'Après vérification',
      '',
      ''
    ];
    const headerRow2 = [
      '',
      'Statut',
      'Motif',
      'Statut',
      'Motif',
      'Montant',
      'Statut',
      'Motif',
      'Montant',
      'Statut',
      'Motif'
    ];
    const sampleRows = [
      ['Société ABC SARL', 'Conforme', '-', 'Acceptée', '-', 12232435, 'Admis', '-', 12232435, 'Admis', '-'],
      ['Société XYZ SARL', 'Conforme', '-', 'Acceptée', '-', 13500000, 'Admis', '-', 13500000, 'Admis', '-'],
      ['Société DEF SARL', 'Non conforme', 'Pièce manquante', '-', '-', 0, 'Rejeté', '-', 0, 'Rejeté', '-'],
    ];

    const ws = XLSX.utils.aoa_to_sheet([
      ['Direction Régionale de l\'Office National du Conseil Agricole Rabat-Salé-Kénitra'],
      [],
      headerRow1,
      headerRow2,
      ...sampleRows
    ]);

    ws['!merges'] = [
      { s: { r: 0, c: 0 }, e: { r: 0, c: 10 } },
      { s: { r: 2, c: 0 }, e: { r: 3, c: 0 } },
      { s: { r: 2, c: 1 }, e: { r: 2, c: 2 } },
      { s: { r: 2, c: 3 }, e: { r: 2, c: 4 } },
      { s: { r: 2, c: 5 }, e: { r: 2, c: 7 } },
      { s: { r: 2, c: 8 }, e: { r: 2, c: 10 } },
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Examen des offres');
    XLSX.writeFile(wb, 'Modele_Examen_Offres_DRCA_RSK.xlsx');
    addToast('info', 'Modèle Excel téléchargé avec succès.');
  };

  const handlePreview = async (docType) => {
    if (!id || id === 'nouveau') {
      addToast('error', "Veuillez d'abord enregistrer le dossier.");
      return;
    }

    try {
      addToast('info', "Ouverture de l'aperçu...");
      const response = await api.get(`/aoos/${id}/documents/${docType}`, {
        responseType: 'blob',
        validateStatus: () => true,
      });

      if (response.status !== 200) {
        try {
          const text = await response.data.text();
          const err = JSON.parse(text);
          throw new Error(err.message || err.error || 'Erreur lors du chargement');
        } catch (e) {
          throw new Error(`Erreur HTTP ${response.status}`);
        }
      }

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      window.open(url, '_blank');
    } catch (err) {
      console.error(err);
      addToast('error', `Impossible d'ouvrir l'aperçu : ${err.message}`);
    }
  };

  const handleAddReference = () => {
    if (isLocked) return;
    const newId = String(Date.now());
    const newRef = {
      id: newId,
      type: 'Décision',
      numero: '',
      date: '',
      objet: '',
      texte_complet: 'Vu la décision...'
    };
    setReferencesJuridiques(prev => [...prev, newRef]);
    logAction('Ajout d\'une référence juridique', 'bg-purple-100 text-purple-800');
    addToast('info', 'Nouvelle référence ajoutée');
  };

  const handleRemoveReference = (refId) => {
    if (isLocked) return;
    setReferencesJuridiques(prev => prev.filter(r => r.id !== refId));
    logAction('Suppression d\'une référence juridique', 'bg-red-100 text-red-800');
  };

  const handleReferenceChange = (id, field, value) => {
    if (isLocked) return;
    setReferencesJuridiques(prev => prev.map(r => r.id === id ? { ...r, [field]: value } : r));
  };

  // Gestion de l'ajout de fournisseur (Existant ou Nouveau)
  const handleOpenAddModal = () => {
    if (isLocked) return;
    setShowAddModal(true);
    setAddMode('existant');
    setSelectedFournisseurId('');
    setNewFournisseurData({ raison_sociale: '', ice: '', if: '', rc: '', adresse: '', ville: '', email: '', telephone: '' });
  };

  const handleCloseAddModal = () => {
    setShowAddModal(false);
  };

  const submitAddFournisseur = async () => {
    if (isLocked) return;
    setIsSubmittingFournisseur(true);

    try {
      let fournisseurReal = null;

      if (addMode === 'existant') {
        if (!selectedFournisseurId) {
          addToast('error', 'Veuillez sélectionner un fournisseur.');
          setIsSubmittingFournisseur(false);
          return;
        }
        fournisseurReal = fournisseurs.find(f => String(f.id) === String(selectedFournisseurId));
        if (!fournisseurReal) {
          addToast('error', 'Fournisseur introuvable.');
          setIsSubmittingFournisseur(false);
          return;
        }
      } else {
        if (!newFournisseurData.raison_sociale || !newFournisseurData.ice) {
          addToast('error', 'Raison sociale et ICE sont obligatoires.');
          setIsSubmittingFournisseur(false);
          return;
        }
        // Appel API pour créer le fournisseur réellement
        const payloadToCreate = {
          raison_sociale: newFournisseurData.raison_sociale,
          ice: newFournisseurData.ice,
          if: newFournisseurData.if || '00000000',
          rc: newFournisseurData.rc || '0000',
          adresse: newFournisseurData.adresse || 'Non renseignée',
          ville: newFournisseurData.ville || 'Non renseignée',
          telephone: newFournisseurData.telephone || '0000000000',
          email: newFournisseurData.email || 'contact@fournisseur.com',
          patente: '00000000',
          cnss: '0000000',
          domaine_activite: 'Non renseigné',
          actif: true
        };
        const response = await api.post('/fournisseurs', payloadToCreate);
        fournisseurReal = response.data;
        if (typeof refreshFournisseurs === 'function') {
          refreshFournisseurs();
        }
      }

      // Ajout au state importPreview
      const newCompany = {
        ...fournisseurReal, // VRAI OBJET COMPLET (if, rc, patente, etc.)
        id: fournisseurReal.id, // VRAI ID !
        fournisseur_id: fournisseurReal.id, // VRAI ID !
        raison_sociale: fournisseurReal.raison_sociale,
        ice: fournisseurReal.ice || '',
        email: fournisseurReal.email || '',
        admin_conforme: true,
        admin_motif_rejet: '',
        tech_conforme: true,
        montant_ht: 0,
        tva_rate: 20,
        montant_ttc: 0,
        observations: '',
      };

      if (typeof setImportPreview === 'function') {
        setImportPreview(prev => [...prev, newCompany]);
      }

      logAction(`Ajout du soumissionnaire: ${fournisseurReal.raison_sociale}`, 'bg-blue-100 text-blue-800');
      addToast('info', `Soumissionnaire ${fournisseurReal.raison_sociale} ajouté.`);
      handleCloseAddModal();
    } catch (err) {
      console.error(err);
      const errorMsg = err.response?.data?.message || err.response?.data?.error || 'Erreur lors de l\'ajout du fournisseur.';
      addToast('error', errorMsg);
    } finally {
      setIsSubmittingFournisseur(false);
    }
  };

  const handleMemberChange = (memberId, field, value) => {
    if (isLocked) return;
    setCommissionMembers(prev => prev.map(m => m.id === memberId ? { ...m, [field]: value } : m));
  };

  // Handle company updates
  const updateCompanyField = (originalIndex, field, value) => {
    if (isLocked) return;
    if (typeof handleCompanyChange === 'function') {
      handleCompanyChange(originalIndex, field, value);
    } else if (typeof setImportPreview === 'function') {
      setImportPreview(prev => {
        const arr = Array.isArray(prev) ? [...prev] : [];
        if (arr[originalIndex]) {
          arr[originalIndex] = { ...arr[originalIndex], [field]: value };
        }
        return arr;
      });
    }
  };

  const confirmLockSession = async () => {
    if (!id || id === 'nouveau') {
      addToast('error', 'Veuillez d\'abord enregistrer l\'AOO dans l\'onglet Préparation avant de valider la séance.');
      setShowValidationModal(false);
      return;
    }

    try {
      setShowValidationModal(false);

      // Save the concurrents from the Commission step to the database
      if (analyzedCompanies && analyzedCompanies.length > 0) {
        const payload = analyzedCompanies.map(c => ({
          fournisseur_id: c.fournisseur_id || c.id,
          nom_soumissionnaire: c.raison_sociale || '',
          ice: c.ice || '',
          adresse: c.adresse || '',
          ville: c.ville || '',
          admin_conforme: c.isAdminConforme,
          admin_motif_rejet: c.admin_motif_rejet || '',
          admin_observations: c.observations || '',
          tech_conforme: c.isTechConforme,
          tech_observations: c.observations || '',
          montant_ht: c.computedHT,
          tva: c.tva_rate || 20,
          montant_ttc: c.computedTTC,
          montant_engagement: c.computedTTC > 0 ? c.computedTTC : null,
          classement: c.calculatedRank,
        }));
        await api.post(`/aoos/${id}/ouverture-plis`, { concurrents: payload });
      }

      await validateCommission();
      const nowStr = new Date().toLocaleDateString('fr-FR') + ' à ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
      setLockMeta({
        date: nowStr,
        user: 'Directeur Régional (Session courante)'
      });
      addToast('success', 'Séance d\'ouverture validée et verrouillée avec succès !');
      logAction('Séance d\'ouverture clôturée et verrouillée', 'bg-emerald-100 text-emerald-800');
    } catch (err) {
      addToast('error', 'Erreur lors de la validation de la séance');
    }
  };

  const handleGenerateDecisionNomination = () => {
    if (!formData.num_decision_nomination) {
      addToast('error', 'Le N° de la décision de nomination est obligatoire');
      return;
    }
    if (!formData.date_decision_nomination) {
      addToast('error', 'La date de la décision de nomination est obligatoire');
      return;
    }
    if (!commissionMembers || commissionMembers.length === 0) {
      addToast('error', 'La commission doit comporter au moins un membre');
      return;
    }
    logAction('Génération de la Décision de nomination', 'bg-blue-100 text-blue-800');
    downloadDocument('decision-nomination');
  };

  // Render Status Badge
  const renderStatusBadge = (status) => {
    switch (status) {
      case 'Retenu provisoirement':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200"><Award size={14} className="text-blue-600" /> Retenu provisoirement</span>;
      case 'Admis':
        return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"><CheckCircle2 size={14} className="text-emerald-600" /> Admis</span>;
      case 'Rejeté (Admin)':
      case 'Écarté (Tech)':
        return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200"><XCircle size={14} className="text-red-600" /> {status}</span>;
      default:
        return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"><Clock size={14} className="text-amber-600" /> En attente</span>;
    }
  };

  // Word export function
  const exportToWord = () => {
    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' " +
      "xmlns:w='urn:schemas-microsoft-com:office:word' " +
      "xmlns='http://www.w3.org/TR/REC-html40'>" +
      "<head><meta charset='utf-8'><title>PV d'ouverture des plis</title><style>" +
      "body { font-family: Arial, sans-serif; margin: 40px; } " +
      "h1 { text-align: center; color: #1e3a8a; } " +
      "table { width: 100%; border-collapse: collapse; margin-top: 20px; } " +
      "th, td { border: 1px solid #cccccc; padding: 10px; text-align: left; } " +
      "th { background-color: #f1f5f9; font-weight: bold; } " +
      "</style></head><body>";
    const footer = "</body></html>";

    const content = `
      <h1>ROYAUME DU MAROC</h1>
      <h3 style="text-align: center;">DIRECTION RÉGIONALE DU CONSEIL AGRICOLE RABAT-SALÉ-KÉNITRA</h3>
      <h2 style="text-align: center; color: #1e3a8a;">PROCÈS-VERBAL D'OUVERTURE DES PLIS</h2>
      <p><strong>N° Appel d'Offres :</strong> ${formData.num_aoo || '—'}</p>
      <p><strong>Objet :</strong> ${formData.objet || '—'}</p>
      <p><strong>Date de séance :</strong> ${formData.date_preparation || '—'} à ${formData.heure_ouverture || '—'}</p>
      <p><strong>Lieu :</strong> ${formData.lieu_ouverture || 'Siège DRCA'}</p>
      
      <h3>1. COMPOSITION DE LA COMMISSION</h3>
      <table>
        <thead>
          <tr><th>Qualité</th><th>Nom & Prénom</th><th>Fonction / Organisme</th></tr>
        </thead>
        <tbody>
          ${commissionMembers.map(m => `<tr><td>${m.role}</td><td>${m.nom_prenom || '—'}</td><td>${m.fonction} (${m.organisme})</td></tr>`).join('')}
        </tbody>
      </table>

      <h3>2. RÉSULTAT ET ANALYSE DES SOUMISSIONNAIRES</h3>
      <table>
        <thead>
          <tr><th>Entreprise</th><th>Dossier Admin.</th><th>Offre Tech.</th><th>Montant TTC</th><th>Classement</th><th>Décision</th></tr>
        </thead>
        <tbody>
          ${analyzedCompanies.map(c => `
            <tr>
              <td>${c.raison_sociale}</td>
              <td>${c.isAdminConforme ? 'Conforme' : 'Non Conforme (' + (c.admin_motif_rejet || 'Motif non précisé') + ')'}</td>
              <td>${c.isTechConforme ? 'Acceptée' : 'Rejetée (' + (c.tech_observations || 'N/A') + ')'}</td>
              <td>${c.computedTTC ? c.computedTTC.toLocaleString('fr-FR') + ' DH' : '—'}</td>
              <td>${c.classementDisplay}</td>
              <td>${c.finalStatus}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <br/><br/>
      <p style="text-align: right;">Fait à ${formData.lieu_ouverture || 'Rabat'}, le ${new Date().toLocaleDateString('fr-FR')}</p>
      <p><strong>Signatures des membres de la commission :</strong></p>
      <br/><br/>
      <div style="display: flex; justify-content: space-between;">
        <span>Le Président</span>
        <span>Le Rapporteur</span>
        <span>Les Membres</span>
      </div>
    `;

    const blob = new Blob([header + content + footer], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `PV_Ouverture_AOO_${formData.num_aoo || 'Dossier'}.doc`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    logAction('Téléchargement du PV au format Word', 'bg-blue-100 text-blue-800');
    addToast('success', 'Export du PV Word généré avec succès');
  };

  return (
    <div className="space-y-8 animate-fade-in relative">
      {/* TOAST CONTAINER */}
      <div className="fixed top-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
        {toasts.map(toast => (
          <div key={toast.id} className={`pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl border text-sm font-semibold transition-all duration-300 transform translate-y-0 ${toast.type === 'success' ? 'bg-emerald-600 text-white border-emerald-500' :
            toast.type === 'warning' ? 'bg-amber-500 text-white border-amber-400' :
              toast.type === 'error' ? 'bg-red-600 text-white border-red-500' :
                'bg-slate-900 text-white border-slate-700'
            }`}>
            {toast.type === 'success' && <CheckCircle2 size={20} />}
            {toast.type === 'warning' && <AlertTriangle size={20} />}
            {toast.type === 'error' && <XCircle size={20} />}
            {toast.type === 'info' && <Sparkles size={20} />}
            <span>{toast.message}</span>
          </div>
        ))}
      </div>

      {/* VERROUILLAGE BANNER IF LOCKED */}
      {isLocked && (
        <div className="rounded-2xl border border-emerald-300 bg-emerald-50/90 p-5 shadow-sm flex items-center justify-between gap-4">
          <div className="flex items-center gap-3 text-emerald-900">
            <div className="grid h-10 w-10 place-items-center rounded-xl bg-emerald-600 text-white">
              <Lock size={20} />
            </div>
            <div>
              <p className="font-bold text-base">Séance d'Ouverture Clôturée et Verrouillée</p>
              <p className="text-xs text-emerald-700 mt-0.5">
                Validée le {lockMeta.date || 'récemment'} par <span className="font-semibold">{lockMeta.user}</span>. Tous les champs sont désormais en lecture seule.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* IDENTIFICATION ADMINISTRATIVE DE LA CONSULTATION (MODÈLE EXCEL) */}
      <IdentificationConsultationCard formData={formData} />

      {/* HEADER BAR */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <h2 className="text-2xl font-extrabold text-[#1e3a8a] flex items-center gap-3">
            <ShieldCheck size={28} className="text-blue-600" />
            Ouverture des Plis
          </h2>
          <p className="text-sm text-slate-600 mt-1">
            Gestion de la séance, ouverture des plis et examen des offres des soumissionnaires.
          </p>
        </div>
      </div>


      {/* 4. TABLEAU MÉTIER D'ANALYSE DES SOUMISSIONNAIRES */}
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
        {/* Controls & Filter bar */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
          <div>
            <h3 className="text-lg font-bold text-slate-800">Enregistrement et examen des offres</h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Enregistrement des offres présentées par les soumissionnaires et constatations de la commission lors de la séance.
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            {/* Search */}
            <div className="relative">
              <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                placeholder="Rechercher entreprise..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-600 w-48 lg:w-64"
              />
            </div>

            {/* Filter Tabs */}
            <div className="flex items-center rounded-xl bg-slate-100 p-1 text-xs font-bold">
              <button
                type="button"
                onClick={() => setFilterStatus('all')}
                className={`px-3 py-1.5 rounded-lg transition-all ${filterStatus === 'all' ? 'bg-white text-blue-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
              >
                Tous ({analyzedCompanies.length})
              </button>
              <button
                type="button"
                onClick={() => setFilterStatus('admis')}
                className={`px-3 py-1.5 rounded-lg transition-all ${filterStatus === 'admis' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
              >
                Admis
              </button>
              <button
                type="button"
                onClick={() => setFilterStatus('rejetes')}
                className={`px-3 py-1.5 rounded-lg transition-all ${filterStatus === 'rejetes' ? 'bg-white text-red-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
              >
                Rejetés
              </button>
              <button
                type="button"
                onClick={() => setFilterStatus('retenus')}
                className={`px-3 py-1.5 rounded-lg transition-all ${filterStatus === 'retenus' ? 'bg-white text-blue-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
              >
                Retenus
              </button>
            </div>

            {/* Action Excel Import */}
            {!isLocked && (
              <div className="flex items-center gap-2">
                <label
                  htmlFor="excel-main-import-input"
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl cursor-pointer shadow-xs transition-all flex items-center gap-2"
                  title="Importer le tableau d'examen des offres"
                >
                  <Upload size={15} /> Importer Excel
                </label>
                <input
                  type="file"
                  id="excel-main-import-input"
                  accept=".xlsx, .xls"
                  className="hidden"
                  onChange={handleExcelImport}
                />
              </div>
            )}
          </div>
        </div>

        {/* Main Table conforme au modèle Excel */}
        <div className="overflow-x-auto rounded-xl border border-slate-400 shadow-xs bg-white">
          <table className="w-full text-xs text-left border-collapse">
            <thead>
              {/* Ligne d'en-tête 1 */}
              <tr className="bg-[#ede8db] text-slate-900 font-bold border-b border-slate-400 text-center">
                <th rowSpan="2" className="border border-slate-400 px-3 py-2.5 text-center align-middle font-bold text-slate-900 min-w-[200px]">
                  Nom du dépositaire de l'offre
                </th>
                <th colSpan="2" className="border border-slate-400 px-3 py-2 text-center font-bold text-slate-900 min-w-[180px]">
                  Dossier administratif et technique
                </th>
                <th colSpan="2" className="border border-slate-400 px-3 py-2 text-center font-bold text-slate-900 min-w-[180px]">
                  Offre technique
                </th>
                <th colSpan="3" className="border border-slate-400 px-3 py-2 text-center font-bold text-slate-900 min-w-[270px]">
                  Offre financière
                </th>
                <th colSpan="3" className="border border-slate-400 px-3 py-2 text-center font-bold text-slate-900 min-w-[270px]">
                  Après vérification
                </th>
              </tr>
              {/* Ligne d'en-tête 2 */}
              <tr className="bg-[#ede8db] text-slate-900 font-bold border-b border-slate-400 text-center text-[11px]">
                {/* Dossier admin */}
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-24">Statut</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-slate-800 font-extrabold w-28">Motif</th>
                {/* Offre tech */}
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-24">Statut</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-slate-800 font-extrabold w-28">Motif</th>
                {/* Offre fin */}
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-28">Montant</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-24">Statut</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-slate-800 font-extrabold w-28">Motif</th>
                {/* Après vérif */}
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-28">Montant</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-emerald-800 font-extrabold w-24">Statut</th>
                <th className="border border-slate-400 px-2 py-1.5 text-center text-slate-800 font-extrabold w-28">Motif</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-300">
              {filteredCompanies.length === 0 ? (
                <tr>
                  <td colSpan="11" className="px-4 py-8 text-center text-slate-500 border border-slate-300">
                    {analyzedCompanies.length === 0 ? (
                      <div className="py-4 space-y-3">
                        <Info size={36} className="mx-auto text-slate-300 mb-1" />
                        <p className="font-bold text-base text-slate-700">Aucun soumissionnaire enregistré</p>
                        <p className="text-xs text-slate-500 max-w-md mx-auto">
                          Importez directement le fichier Excel de la commission pour charger les soumissionnaires et les offres.
                        </p>
                        {!isLocked && (
                          <div className="flex items-center justify-center pt-2">
                            <label
                              htmlFor="fournisseur-import-input"
                              className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl cursor-pointer shadow-sm transition-all flex items-center gap-2"
                            >
                              <Upload size={15} /> Importer le fichier Excel
                            </label>
                            <input
                              type="file"
                              id="fournisseur-import-input"
                              accept=".xlsx, .xls"
                              className="hidden"
                              onChange={handleExcelImport}
                            />
                          </div>
                        )}
                      </div>
                    ) : (
                      <p>Aucun résultat ne correspond à votre filtre.</p>
                    )}
                  </td>
                </tr>
              ) : (
                filteredCompanies.map((company, index) => {
                  const idx = company.__originalIndex !== undefined ? company.__originalIndex : index;

                  const isMotifRequired = (status, motif) => {
                    return (status === 'Admis avec réserve' || status === 'Rejeté') && (!motif || motif.trim() === '' || motif.trim() === '-');
                  };

                  const getSelectClass = (st) => {
                    if (st === 'Admis sans réserve') return 'text-emerald-800 bg-emerald-50 border-emerald-300 font-bold';
                    if (st === 'Admis avec réserve') return 'text-amber-800 bg-amber-50 border-amber-300 font-bold';
                    if (st === 'Rejeté') return 'text-red-800 bg-red-50 border-red-300 font-bold';
                    return 'text-slate-400 bg-slate-100 border-slate-200 italic font-semibold';
                  };

                  return (
                    <tr key={company.id || company.__importKey || idx} className="hover:bg-amber-50/30 border-b border-slate-300">
                      {/* 1. Nom du dépositaire de l'offre */}
                      <td className="border border-slate-300 px-3 py-2">
                        <input
                          type="text"
                          value={company.raison_sociale || ''}
                          onChange={(e) => updateCompanyField(idx, 'raison_sociale', e.target.value)}
                          disabled={isLocked}
                          placeholder="Nom de l'entreprise..."
                          className="w-full font-bold text-slate-900 text-xs bg-transparent outline-none"
                        />
                      </td>

                      {/* 2. Dossier Admin : Statut */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        <select
                          value={company.adminStatut}
                          onChange={(e) => updateStepStatus(idx, 'admin', e.target.value)}
                          disabled={isLocked}
                          className={`w-full px-2 py-1 rounded-lg border text-xs outline-none cursor-pointer transition-all ${getSelectClass(company.adminStatut)}`}
                        >
                          <option value="Admis sans réserve">🟢 Admis sans réserve</option>
                          <option value="Admis avec réserve">🟡 Admis avec réserve</option>
                          <option value="Rejeté">🔴 Rejeté</option>
                        </select>
                      </td>

                      {/* 3. Dossier Admin : Motif */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        <input
                          type="text"
                          value={company.admin_motif || ''}
                          onChange={(e) => updateCompanyField(idx, 'admin_motif', e.target.value)}
                          disabled={isLocked}
                          placeholder={company.adminStatut === 'Admis sans réserve' ? '-' : 'Motif obligatoire *'}
                          className={`w-full px-2 py-1 text-center rounded-lg border text-xs outline-none transition-all ${isMotifRequired(company.adminStatut, company.admin_motif)
                            ? 'border-red-400 bg-red-50/40 text-red-900 font-semibold'
                            : 'border-transparent bg-transparent text-slate-700 focus:border-slate-300 focus:bg-white'
                            }`}
                        />
                      </td>

                      {/* 4. Offre Technique : Statut */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.techStatut === 'Non examiné' ? (
                          <div className="w-full px-2 py-1 text-xs text-slate-400 font-semibold italic bg-slate-50 rounded-lg">
                            Non examiné
                          </div>
                        ) : (
                          <select
                            value={company.techStatut}
                            onChange={(e) => updateStepStatus(idx, 'tech', e.target.value)}
                            disabled={isLocked}
                            className={`w-full px-2 py-1 rounded-lg border text-xs outline-none cursor-pointer transition-all ${getSelectClass(company.techStatut)}`}
                          >
                            <option value="Admis sans réserve">🟢 Admis sans réserve</option>
                            <option value="Admis avec réserve">🟡 Admis avec réserve</option>
                            <option value="Rejeté">🔴 Rejeté</option>
                          </select>
                        )}
                      </td>

                      {/* 5. Offre Technique : Motif */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.techStatut === 'Non examiné' ? (
                          <div className="text-xs text-slate-400 font-mono py-1">-</div>
                        ) : (
                          <input
                            type="text"
                            value={company.tech_motif || ''}
                            onChange={(e) => updateCompanyField(idx, 'tech_motif', e.target.value)}
                            disabled={isLocked}
                            placeholder={company.techStatut === 'Admis sans réserve' ? '-' : 'Motif obligatoire *'}
                            className={`w-full px-2 py-1 text-center rounded-lg border text-xs outline-none transition-all ${isMotifRequired(company.techStatut, company.tech_motif)
                              ? 'border-red-400 bg-red-50/40 text-red-900 font-semibold'
                              : 'border-transparent bg-transparent text-slate-700 focus:border-slate-300 focus:bg-white'
                              }`}
                          />
                        )}
                      </td>

                      {/* 6. Offre Financière : Montant */}
                      <td className="border border-slate-300 px-1.5 py-1 text-right font-mono">
                        {company.finStatut === 'Non examiné' ? (
                          <div className="text-xs text-slate-400 font-mono py-1 text-right">-</div>
                        ) : (
                          <input
                            type="text"
                            value={company.montant_ht ? Number(company.montant_ht).toLocaleString('fr-FR') : ''}
                            onChange={(e) => {
                              const raw = e.target.value.replace(/\s+/g, '').replace(/,/g, '.');
                              const val = parseFloat(raw) || 0;
                              updateCompanyField(idx, 'montant_ht', val);
                              updateCompanyField(idx, 'montant_ttc', Math.round(val * 1.2 * 100) / 100);
                            }}
                            disabled={isLocked}
                            placeholder="0,00"
                            className="w-full text-right px-2 py-1 bg-transparent border-transparent focus:border-slate-300 focus:bg-white rounded-lg outline-none text-xs font-mono font-bold text-slate-900"
                          />
                        )}
                      </td>

                      {/* 7. Offre Financière : Statut */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.finStatut === 'Non examiné' ? (
                          <div className="w-full px-2 py-1 text-xs text-slate-400 font-semibold italic bg-slate-50 rounded-lg">
                            Non examiné
                          </div>
                        ) : (
                          <select
                            value={company.finStatut}
                            onChange={(e) => updateStepStatus(idx, 'fin', e.target.value)}
                            disabled={isLocked}
                            className={`w-full px-2 py-1 rounded-lg border text-xs outline-none cursor-pointer transition-all ${getSelectClass(company.finStatut)}`}
                          >
                            <option value="Admis sans réserve">🟢 Admis sans réserve</option>
                            <option value="Admis avec réserve">🟡 Admis avec réserve</option>
                            <option value="Rejeté">🔴 Rejeté</option>
                          </select>
                        )}
                      </td>

                      {/* 8. Offre Financière : Motif */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.finStatut === 'Non examiné' ? (
                          <div className="text-xs text-slate-400 font-mono py-1">-</div>
                        ) : (
                          <input
                            type="text"
                            value={company.fin_motif || ''}
                            onChange={(e) => updateCompanyField(idx, 'fin_motif', e.target.value)}
                            disabled={isLocked}
                            placeholder={company.finStatut === 'Admis sans réserve' ? '-' : 'Motif obligatoire *'}
                            className={`w-full px-2 py-1 text-center rounded-lg border text-xs outline-none transition-all ${isMotifRequired(company.finStatut, company.fin_motif)
                              ? 'border-red-400 bg-red-50/40 text-red-900 font-semibold'
                              : 'border-transparent bg-transparent text-slate-700 focus:border-slate-300 focus:bg-white'
                              }`}
                          />
                        )}
                      </td>

                      {/* 9. Après Vérification : Montant */}
                      <td className="border border-slate-300 px-1.5 py-1 text-right font-mono">
                        {company.verifStatut === 'Non examiné' ? (
                          <div className="text-xs text-slate-400 font-mono py-1 text-right">-</div>
                        ) : (
                          <input
                            type="text"
                            value={company.montant_rectifie ? Number(company.montant_rectifie).toLocaleString('fr-FR') : (company.montant_ht ? Number(company.montant_ht).toLocaleString('fr-FR') : '')}
                            onChange={(e) => {
                              const raw = e.target.value.replace(/\s+/g, '').replace(/,/g, '.');
                              const val = parseFloat(raw) || 0;
                              updateCompanyField(idx, 'montant_rectifie', val);
                            }}
                            disabled={isLocked}
                            placeholder="0,00"
                            className="w-full text-right px-2 py-1 bg-transparent border-transparent focus:border-slate-300 focus:bg-white rounded-lg outline-none text-xs font-mono font-bold text-slate-900"
                          />
                        )}
                      </td>

                      {/* 10. Après Vérification : Statut */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.verifStatut === 'Non examiné' ? (
                          <div className="w-full px-2 py-1 text-xs text-slate-400 font-semibold italic bg-slate-50 rounded-lg">
                            Non examiné
                          </div>
                        ) : (
                          <select
                            value={company.verifStatut}
                            onChange={(e) => updateStepStatus(idx, 'verif', e.target.value)}
                            disabled={isLocked}
                            className={`w-full px-2 py-1 rounded-lg border text-xs outline-none cursor-pointer transition-all ${getSelectClass(company.verifStatut)}`}
                          >
                            <option value="Admis sans réserve">🟢 Admis sans réserve</option>
                            <option value="Admis avec réserve">🟡 Admis avec réserve</option>
                            <option value="Rejeté">🔴 Rejeté</option>
                          </select>
                        )}
                      </td>

                      {/* 11. Après Vérification : Motif */}
                      <td className="border border-slate-300 px-1.5 py-1 text-center">
                        {company.verifStatut === 'Non examiné' ? (
                          <div className="text-xs text-slate-400 font-mono py-1">-</div>
                        ) : (
                          <input
                            type="text"
                            value={company.verif_motif || ''}
                            onChange={(e) => updateCompanyField(idx, 'verif_motif', e.target.value)}
                            disabled={isLocked}
                            placeholder={company.verifStatut === 'Admis sans réserve' ? '-' : 'Motif obligatoire *'}
                            className={`w-full px-2 py-1 text-center rounded-lg border text-xs outline-none transition-all ${isMotifRequired(company.verifStatut, company.verif_motif)
                              ? 'border-red-400 bg-red-50/40 text-red-900 font-semibold'
                              : 'border-transparent bg-transparent text-slate-700 focus:border-slate-300 focus:bg-white'
                              }`}
                          />
                        )}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Actions : Aperçu & Télécharger le tableau en PDF */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3">
          <p className="text-xs text-slate-500 italic">
            * Reproduction officielle du tableau au format A4 Paysage (toutes les colonnes, motifs et statuts exacts).
          </p>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handlePreviewTableauPdf}
              disabled={previewingTableauPdf || filteredCompanies.length === 0}
              className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl border border-slate-300 shadow-sm transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Aperçu du tableau officiel en PDF"
            >
              {previewingTableauPdf ? <Loader2 size={15} className="animate-spin text-slate-600" /> : <Eye size={15} className="text-slate-600" />}
              Aperçu
            </button>
            <button
              type="button"
              onClick={handleDownloadTableauPdf}
              disabled={downloadingTableauPdf || filteredCompanies.length === 0}
              className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Télécharger la reproduction exacte du tableau en PDF (A4 Paysage)"
            >
              {downloadingTableauPdf ? <Loader2 size={16} className="animate-spin" /> : <FileDown size={16} />}
              Télécharger le tableau en PDF
            </button>
          </div>
        </div>
      </div>

      {/* ACTIONS DE LA SÉANCE & DOCUMENTS */}
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 mt-8">
        <div className="mb-5 border-b border-slate-100 pb-3">
          <h3 className="text-lg font-bold text-slate-800 flex items-center gap-2">
            {!isLocked ? <Lock className="text-blue-600" size={20} /> : <FileDown className="text-emerald-600" size={20} />}
            {!isLocked ? "Actions de la séance" : "Documents de la Commission"}
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            {!isLocked
              ? "Assurez-vous d'avoir saisi toutes les offres avant de valider la séance."
              : "Générez les documents réglementaires à l'issue de la séance d'ouverture des plis."}
          </p>
        </div>

        {!isLocked ? (
          <div className="flex justify-end">
            <button
              type="button"
              onClick={() => {
                if (!id || id === 'nouveau') {
                  addToast('error', 'Veuillez d\'abord enregistrer l\'AOO dans l\'onglet Préparation avant de valider la séance.');
                  return;
                }
                setShowValidationModal(true);
              }}
              disabled={saving || analyzedCompanies.length === 0}
              className="inline-flex items-center gap-3 px-8 py-4 bg-[#1e40af] text-white font-extrabold text-base rounded-2xl shadow-xl hover:bg-[#1e3a8a] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
            >
              <Lock size={22} /> Valider & Verrouiller la séance
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* 1. PV d'ouverture des plis */}
            <div className="flex flex-col justify-between p-6 rounded-3xl border border-blue-100 bg-gradient-to-br from-white to-blue-50 shadow-sm hover:shadow-xl hover:shadow-blue-500/10 transition-all duration-300">
              <div className="flex flex-col items-center text-center">
                <div className="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20 mb-4">
                  <FileText size={26} strokeWidth={2.5} />
                </div>
                <span className="font-extrabold text-blue-950 text-base tracking-tight mb-1">
                  PV d'ouverture des plis
                </span>
                <p className="text-xs text-blue-900/70 mb-4">
                  Procès-verbal officiel de la commission d'ouverture des plis.
                </p>
              </div>
              <div className="flex items-center gap-2 pt-2 border-t border-blue-200/60">
                <button
                  type="button"
                  onClick={() => handlePreview('pv-ouverture')}
                  className="flex-1 py-2.5 px-3 bg-white hover:bg-blue-100 text-blue-900 font-bold text-xs rounded-xl border border-blue-300 shadow-2xs flex items-center justify-center gap-1.5 transition-all"
                >
                  <Eye size={15} /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('pv-ouverture')}
                  className="flex-1 py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-600/20 flex items-center justify-center gap-1.5 transition-all"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* 2. Rapport de prestation */}
            <div className="flex flex-col justify-between p-6 rounded-3xl border border-emerald-100 bg-gradient-to-br from-white to-emerald-50 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 transition-all duration-300">
              <div className="flex flex-col items-center text-center">
                <div className="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-500 text-white shadow-md shadow-emerald-500/20 mb-4">
                  <FileSpreadsheet size={26} strokeWidth={2.5} />
                </div>
                <span className="font-extrabold text-emerald-950 text-base tracking-tight mb-1">
                  Rapport de prestation
                </span>
                <p className="text-xs text-emerald-900/70 mb-4">
                  Rapport de présentation et analyse des offres.
                </p>
              </div>
              <div className="flex items-center gap-2 pt-2 border-t border-emerald-200/60">
                <button
                  type="button"
                  onClick={() => handlePreview('rapport-prestation')}
                  className="flex-1 py-2.5 px-3 bg-white hover:bg-emerald-100 text-emerald-900 font-bold text-xs rounded-xl border border-emerald-300 shadow-2xs flex items-center justify-center gap-1.5 transition-all"
                >
                  <Eye size={15} /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('rapport-prestation')}
                  className="flex-1 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 transition-all"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* 3. Résultats de l'Appel d'Offres */}
            <div className="flex flex-col justify-between p-6 rounded-3xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300">
              <div className="flex flex-col items-center text-center">
                <div className="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-md shadow-indigo-500/20 mb-4">
                  <CheckCircle2 size={26} strokeWidth={2.5} />
                </div>
                <span className="font-extrabold text-indigo-950 text-base tracking-tight mb-1">
                  Résultats de l'AOO
                </span>
                <p className="text-xs text-indigo-900/70 mb-4">
                  Avis et notification des résultats de l'AOO.
                </p>
              </div>
              <div className="flex items-center gap-2 pt-2 border-t border-indigo-200/60">
                <button
                  type="button"
                  onClick={() => handlePreview('resultat-aoo')}
                  className="flex-1 py-2.5 px-3 bg-white hover:bg-indigo-100 text-indigo-900 font-bold text-xs rounded-xl border border-indigo-300 shadow-2xs flex items-center justify-center gap-1.5 transition-all"
                >
                  <Eye size={15} /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={handleGenerateResultatAO}
                  className="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 flex items-center justify-center gap-1.5 transition-all"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* 11. PASSAGE DIRECT VERS L'ENGAGEMENT (DASHBOARD) */}
      <div className="bg-gradient-to-br from-white to-blue-50/70 rounded-3xl p-8 border-2 border-blue-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6 mt-4">
        <div>
          <div className="flex items-center gap-2 text-blue-700 font-extrabold text-xs uppercase tracking-wider mb-1.5">
            <CheckCircle2 size={16} className="text-emerald-500" /> Séance d'ouverture des plis terminée
          </div>
          <h3 className="text-xl font-black text-slate-800">
            Passer à l'Engagement
          </h3>
          <p className="text-sm text-slate-500 mt-1">
            L'ouverture des plis est finalisée. Vous pouvez maintenant basculer directement vers le module d'Engagement pour la suite du traitement budgétaire et contractuel.
          </p>
        </div>

        <button
          type="button"
          onClick={handlePasserAttribution}
          className="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-base rounded-2xl transition-all shadow-lg hover:shadow-blue-500/25 flex items-center justify-center gap-3 transform hover:-translate-y-0.5 group shrink-0"
        >
          <span>ACCÉDER À L'ENGAGEMENT</span>
          <ChevronRight size={20} className="group-hover:translate-x-1 transition-transform" />
        </button>
      </div>


      {/* 12. CONFIRMATION VALIDATION MODAL */}
      {showValidationModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm grid place-items-center p-4 animate-fade-in">
          <div className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-200 text-center space-y-6">
            <div className="grid h-16 w-16 place-items-center rounded-2xl bg-amber-100 text-amber-600 mx-auto">
              <AlertTriangle size={36} />
            </div>
            <div>
              <h3 className="text-xl font-extrabold text-slate-900">Valider et Clôturer la Séance ?</h3>
              <p className="text-xs text-slate-600 mt-2 leading-relaxed">
                Êtes-vous certain de vouloir clôturer cette séance d'ouverture ? <br />
                <span className="font-bold text-red-600">Une fois validée, aucune modification ne sera plus possible</span> et tous les procès-verbaux seront définitivement figés.
              </p>
            </div>

            <div className="flex items-center gap-3 pt-2">
              <button
                type="button"
                onClick={() => setShowValidationModal(false)}
                className="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-all"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={confirmLockSession}
                className="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg transition-all"
              >
                Oui, Valider & Verrouiller
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 11. PV PREVIEW & GENERATION MODAL */}
      {showPvModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm grid place-items-center p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-200 my-8 overflow-hidden">
            {/* Modal Header */}
            <div className="px-8 py-5 bg-[#1e3a8a] text-white flex items-center justify-between">
              <div className="flex items-center gap-3">
                <FileText size={24} className="text-cyan-300" />
                <h3 className="text-lg font-bold">Génération du Procès-Verbal d'Ouverture des Plis</h3>
              </div>
              <button
                type="button"
                onClick={() => setShowPvModal(false)}
                className="text-white/80 hover:text-white p-1"
              >
                ✕
              </button>
            </div>

            {/* PV Document Preview Content */}
            <div id="pv-document-content" className="p-8 space-y-6 text-xs text-slate-900 leading-relaxed font-sans max-h-[60vh] overflow-y-auto bg-slate-50/50">
              <div className="text-center space-y-1 border-b border-slate-200 pb-4">
                <p className="font-extrabold tracking-widest text-slate-700 uppercase">ROYAUME DU MAROC</p>
                <p className="font-bold text-slate-600 text-[11px]">DIRECTION RÉGIONALE DU CONSEIL AGRICOLE RABAT-SALÉ-KÉNITRA</p>
                <h2 className="text-xl font-extrabold text-[#1e3a8a] pt-2">PROCÈS-VERBAL D'OUVERTURE DES PLIS</h2>
                <p className="text-xs text-slate-500 font-mono">Appel d'Offres Ouvert N° {formData.num_aoo || '01/2026/ONCA'}</p>
              </div>

              <div className="grid grid-cols-2 gap-4 bg-white p-4 rounded-xl border border-slate-200">
                <div>
                  <p className="text-slate-500 font-bold uppercase text-[10px]">Objet du marché :</p>
                  <p className="font-bold text-slate-900 mt-0.5">{formData.objet || 'Acquisition de prestations'}</p>
                </div>
                <div>
                  <p className="text-slate-500 font-bold uppercase text-[10px]">Date & Lieu de séance :</p>
                  <p className="font-bold text-slate-900 mt-0.5">{formData.date_preparation || new Date().toLocaleDateString('fr-FR')} à {formData.heure_ouverture || '10:00'} ({formData.lieu_ouverture || 'Siège DRCA'})</p>
                </div>
              </div>

              {/* Commission List */}
              <div className="space-y-2">
                <h4 className="font-extrabold text-slate-800 text-sm border-b border-slate-200 pb-1">1. Membres de la Commission</h4>
                <div className="grid grid-cols-2 gap-2">
                  {commissionMembers.map(m => (
                    <div key={m.id} className="p-2.5 rounded-lg bg-white border border-slate-200">
                      <span className="font-bold text-blue-900">{m.role} : </span>
                      <span>{m.nom_prenom || 'Nom non précisé'} ({m.fonction} - {m.organisme})</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Soumissionnaires Analysis Summary */}
              <div className="space-y-2">
                <h4 className="font-extrabold text-slate-800 text-sm border-b border-slate-200 pb-1">2. Synthèse de l'Évaluation des Soumissionnaires</h4>
                <table className="w-full border-collapse border border-slate-200 text-[11px] bg-white">
                  <thead className="bg-slate-100 font-bold text-slate-700">
                    <tr>
                      <th className="border border-slate-200 p-2">Entreprise</th>
                      <th className="border border-slate-200 p-2">Dossier Admin</th>
                      <th className="border border-slate-200 p-2">Offre Tech</th>
                      <th className="border border-slate-200 p-2">Montant TTC</th>
                      <th className="border border-slate-200 p-2">Classement</th>
                      <th className="border border-slate-200 p-2">Décision</th>
                    </tr>
                  </thead>
                  <tbody>
                    {analyzedCompanies.map(c => (
                      <tr key={c.id || c.raison_sociale}>
                        <td className="border border-slate-200 p-2 font-bold">{c.raison_sociale}</td>
                        <td className="border border-slate-200 p-2">{c.isAdminConforme ? 'Conforme' : 'Non Conforme (' + (c.admin_motif_rejet || 'Rejet') + ')'}</td>
                        <td className="border border-slate-200 p-2">{c.isTechConforme ? 'Acceptée' : 'Rejetée'}</td>
                        <td className="border border-slate-200 p-2 font-mono">{c.computedTTC > 0 ? c.computedTTC.toLocaleString('fr-FR') + ' dh' : '—'}</td>
                        <td className="border border-slate-200 p-2 text-center font-bold">{c.classementDisplay}</td>
                        <td className="border border-slate-200 p-2 font-bold text-blue-900">{c.finalStatus}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Modal Footer Actions */}
            <div className="px-8 py-5 bg-slate-100 border-t border-slate-200 flex items-center justify-between">
              <button
                type="button"
                onClick={() => setShowPvModal(false)}
                className="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-all"
              >
                Fermer
              </button>

              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={exportToWord}
                  className="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2"
                >
                  <FileDown size={16} /> Exporter Word (.doc)
                </button>
                <button
                  type="button"
                  onClick={() => {
                    window.print();
                    logAction('Impression/Export PDF du PV d\'ouverture', 'bg-emerald-100 text-emerald-800');
                    addToast('success', 'Impression lancée');
                  }}
                  className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2"
                >
                  <Printer size={16} /> Imprimer / PDF
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL POUR AJOUTER/SELECTIONNER UN FOURNISSEUR */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div className="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div className="flex items-center justify-between p-6 border-b border-slate-100">
              <h3 className="text-xl font-bold text-slate-800 flex items-center gap-2">
                <Users size={24} className="text-blue-600" />
                Ajouter un soumissionnaire
              </h3>
              <button onClick={handleCloseAddModal} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 rounded-xl transition-colors">
                <X size={20} />
              </button>
            </div>

            <div className="p-6 overflow-y-auto">
              {/* TABS */}
              <div className="flex items-center gap-2 mb-6 p-1 bg-slate-100 rounded-xl">
                <button
                  type="button"
                  onClick={() => setAddMode('existant')}
                  className={`flex-1 py-2 text-sm font-bold rounded-lg transition-all ${addMode === 'existant' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                >
                  Fournisseur Existant
                </button>
                <button
                  type="button"
                  onClick={() => setAddMode('nouveau')}
                  className={`flex-1 py-2 text-sm font-bold rounded-lg transition-all ${addMode === 'nouveau' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                >
                  Nouveau Fournisseur
                </button>
              </div>

              {addMode === 'existant' ? (
                <div className="space-y-4">
                  <p className="text-sm text-slate-600 mb-2">Recherchez et sélectionnez un fournisseur déjà enregistré dans la base de données.</p>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Sélectionnez le fournisseur</label>
                    <select
                      value={selectedFournisseurId}
                      onChange={(e) => setSelectedFournisseurId(e.target.value)}
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition-all text-sm font-medium"
                    >
                      <option value="">-- Choisir un fournisseur --</option>
                      {fournisseurs.map(f => (
                        <option key={f.id} value={f.id}>{f.raison_sociale} {f.ice ? `(ICE: ${f.ice})` : ''}</option>
                      ))}
                    </select>
                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  <p className="text-sm text-slate-600 mb-4">Créez un nouveau fournisseur. Il sera enregistré définitivement dans la base de données.</p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="col-span-1 md:col-span-2">
                      <label className="block text-xs font-bold text-slate-700 mb-1">Raison Sociale <span className="text-red-500">*</span></label>
                      <input type="text" value={newFournisseurData.raison_sociale} onChange={e => setNewFournisseurData({ ...newFournisseurData, raison_sociale: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">ICE <span className="text-red-500">*</span></label>
                      <input type="text" value={newFournisseurData.ice} onChange={e => setNewFournisseurData({ ...newFournisseurData, ice: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm font-mono" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Identifiant Fiscal (IF)</label>
                      <input type="text" value={newFournisseurData.if} onChange={e => setNewFournisseurData({ ...newFournisseurData, if: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm font-mono" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Registre Commerce (RC)</label>
                      <input type="text" value={newFournisseurData.rc} onChange={e => setNewFournisseurData({ ...newFournisseurData, rc: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm font-mono" />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Ville</label>
                      <input type="text" value={newFournisseurData.ville} onChange={e => setNewFournisseurData({ ...newFournisseurData, ville: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                    </div>
                    <div className="col-span-1 md:col-span-2">
                      <label className="block text-xs font-bold text-slate-700 mb-1">Adresse</label>
                      <input type="text" value={newFournisseurData.adresse} onChange={e => setNewFournisseurData({ ...newFournisseurData, adresse: e.target.value })} className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-sm" />
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50">
              <button
                type="button"
                onClick={handleCloseAddModal}
                className="px-5 py-2.5 text-slate-600 hover:bg-slate-200 bg-white border border-slate-300 font-bold text-sm rounded-xl transition-all"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={submitAddFournisseur}
                disabled={isSubmittingFournisseur}
                className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl transition-all shadow-md flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
              >
                {isSubmittingFournisseur ? (
                  <><Loader2 size={18} className="animate-spin" /> Enregistrement...</>
                ) : (
                  <><CheckCircle2 size={18} /> Valider l'ajout</>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
