import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api/axios';

import CommissionOuvertureStep from '../components/CommissionOuvertureStep';
import PreparationStep from '../components/preparation/PreparationStep';
import AttributionStep from '../components/AttributionStep';
import { Save, CheckCircle, AlertCircle, Loader2, ArrowLeft, ArrowRight, Download, Users, FileText, Building2, PlayCircle, BarChart2, CheckSquare, FileSpreadsheet, Plus, X, Trash2 } from 'lucide-react';

const TVA_RATE = 0.20;

const parseNum = (val) => {
  const n = parseFloat(String(val ?? '').replace(',', '.'));
  return Number.isFinite(n) ? n : 0;
};

const computeLineMontantHT = (item) => parseNum(item?.quantite) * parseNum(item?.prix_unitaire_ht);

const computeLotTotals = (items = []) => {
  const totalHT = items.reduce((acc, item) => acc + computeLineMontantHT(item), 0);
  const totalTVA = totalHT * TVA_RATE;
  const totalTTC = totalHT + totalTVA;
  return { totalHT, totalTVA, totalTTC };
};

const createEmptyLotItem = () => ({
  id: null,
  designation: '',
  unite: '',
  quantite: '',
  prix_unitaire_ht: '',
});

const createEmptyLot = (index) => ({
  num_lot: `LOT ${index + 1}`,
  objet_lot: '',
  estimation: '',
  cautionnement_provisoire: '',
  art: '',
  par: '',
  lig: '',
  imputation: '',
  notification_ligne_id: null,
  items: [],
});

const mapLotItemsFromApi = (items = []) => (
  items.map(item => ({
    id: item.id,
    designation: item.designation || '',
    unite: item.unite || '',
    quantite: item.quantite ?? '',
    prix_unitaire_ht: item.prix_unitaire_ht ?? '',
  }))
);

const applyEstimationCascade = (lotsDetails, nombreLots) => {
  const nbLots = Math.max(1, parseInt(nombreLots, 10) || 1);
  const sourceLots = Array.isArray(lotsDetails) ? lotsDetails : [];
  const updatedLots = [];

  for (let index = 0; index < nbLots; index += 1) {
    const lot = sourceLots[index] || createEmptyLot(index);
    const { totalTTC } = computeLotTotals(lot.items || []);
    updatedLots.push({
      ...lot,
      num_lot: lot.num_lot || `LOT ${index + 1}`,
      cautionnement_provisoire: lot.cautionnement_provisoire ?? '',
      items: lot.items || [],
      estimation: totalTTC > 0 ? totalTTC.toFixed(2) : (lot.estimation || ''),
    });
  }

  const budgetSum = updatedLots.reduce((acc, lot) => acc + parseNum(lot.estimation), 0);

  return {
    lots_details: updatedLots,
    budget: budgetSum > 0 ? budgetSum.toFixed(2) : '',
  };
};

const GestionAoo = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('preparation');

  const wizardStepOrder = ['preparation', 'commission', 'engagement', 'registre'];
  const wizardNextLabels = {
    preparation: 'Ouverture des plis et analyse',
    commission: 'Engagement',
    engagement: 'Registre',
  };

  const getNextStep = (current) => {
    const currentIndex = wizardStepOrder.indexOf(current);
    return currentIndex >= 0 && currentIndex < wizardStepOrder.length - 1
      ? wizardStepOrder[currentIndex + 1]
      : null;
  };

  const nextStep = getNextStep(activeTab);

  const handleNextStep = () => {
    const next = getNextStep(activeTab);
    if (next) {
      setActiveTab(next);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [generatingDoc, setGeneratingDoc] = useState(null);
  const [preparationValidee, setPreparationValidee] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [fournisseurs, setFournisseurs] = useState([]);
  const [lignesBudgetaires, setLignesBudgetaires] = useState([]);
  const [membresCommissionCatalog, setMembresCommissionCatalog] = useState([]);
  const [registreBudget, setRegistreBudget] = useState('Investissement');

  const [formData, setFormData] = useState({
    num_aoo: '',
    objet: '',
    objet_ar: '',
    journal_fr: '',
    journal_ar: '',
    reference_publication_fr: '',
    date_publication_fr: '',
    reference_publication_ar: '',
    date_publication_ar: '',
    publications_journaux: [
      { nom_journal: '', numero_journal: '', date_publication: '', edition: '', langue: 'Français', reference_publication: '' }
    ],
    date_publication_portail: '',
    ref_publication_portail: '',
    date_mise_en_ligne_portail: '',
    mode_passation: "D’APPEL D’OFFRES OUVERT SIMPLIFIÉ SUR OFFRES DE PRIX",
    prix_reference: '',
    date_ouverture: '',
    heure_ouverture: '',
    nombre_lots: 1,
    budget: '',
    type_budget: 'Investissement',
    art: '',
    par: '',
    lig: '',
    statut: 'en_preparation',
    membres_commission: [],
    concurrents: [],
    etat_avancement: '',
    num_decision_nomination: '',
    date_lettre: '',
    lieu_ouverture: 'Siège de la direction régionale du conseil agricole Rabat-Salé-Kénitra sis à angle avenue Mohamed V et Rue Sebta Kenitra',
    num_aoo_interne: '',
    ref_courrier_analyse: '',
    signataire_titre_analyse: '',
    numero_engagement: '',
    date_engagement: '',
    reference_engagement: '',
    credit_ouvert_cp: '',
    credit_ouvert_ce: '',
    depenses_anterieures_cp: '',
    depenses_anterieures_ce: '',
    depenses_credits_engagement: '',
    depenses_rap: '',
    montant_depense_neuf: '',
    interets_moratoires: '',
    montant_engager_neuf: '',
    pieces_jointes: '',
    lots: [],
    lots_details: [createEmptyLot(0)],
  });

  const [nouveauMembre, setNouveauMembre] = useState({
    membre_commission_id: '',
    qualite: 'Membre'
  });

  const [isMembreQuickModalOpen, setIsMembreQuickModalOpen] = useState(false);
  const [membreQuickForm, setMembreQuickForm] = useState({ nom_prenom: '', fonction: '' });
  const [membreQuickSaving, setMembreQuickSaving] = useState(false);
  const [membreQuickError, setMembreQuickError] = useState(null);
  const [showValidationModal, setShowValidationModal] = useState(false);
  const [showTransitionModal, setShowTransitionModal] = useState(false);
  const [transitionErrors, setTransitionErrors] = useState([]);
  const [validationErrors, setValidationErrors] = useState([]);

  // State for multi-lot attribution (Phase 4)
  const [attributions, setAttributions] = useState({});

  // State for concurrent lot decisions (multi-lot Phase 3)
  const [lotDecisionsState, setLotDecisionsState] = useState({});

  const [activePreparationLotTab, setActivePreparationLotTab] = useState(0);

  useEffect(() => {
    fetchFournisseurs();
    fetchLignesBudgetaires();
    fetchMembresCommission();
    if (id && id !== 'nouveau') {
      fetchDossier();
    }
  }, [id]);

  useEffect(() => {
    if (activeTab !== 'analyse' || fournisseurs.length === 0) return;
    setFormData(prev => {
      let changed = false;
      const concurrents = prev.concurrents.map(c => {
        const updated = prefillGerantNomForConcurrent(c);
        if (updated !== c) changed = true;
        return updated;
      });
      return changed ? { ...prev, concurrents } : prev;
    });
  }, [activeTab, fournisseurs]);

  const updateLotsWithCascade = (updater) => {
    setFormData(prev => {
      const currentLots = [...(prev.lots_details || [])];
      const nextLots = typeof updater === 'function' ? updater(currentLots) : updater;
      const cascaded = applyEstimationCascade(nextLots, prev.nombre_lots);
      return { ...prev, ...cascaded };
    });
  };

  const getFournisseurName = (concurrent) => (
    concurrent?.fournisseur?.raison_sociale || concurrent?.nom_soumissionnaire || 'Soumissionnaire Sans Nom'
  );

  const getGerantFromFournisseur = (fournisseurOrId) => {
    const fournisseur = typeof fournisseurOrId === 'object' && fournisseurOrId !== null
      ? fournisseurOrId
      : fournisseurs.find(f => String(f.id) === String(fournisseurOrId));
    return fournisseur?.gerant_nom || fournisseur?.representant || fournisseur?.gerant || fournisseur?.nom_gerant || '';
  };

  const prefillGerantNomForConcurrent = (concurrent) => {
    if (concurrent.gerant_nom && String(concurrent.gerant_nom).trim() !== '') {
      return concurrent;
    }
    const gerant = getGerantFromFournisseur(concurrent.fournisseur) || getGerantFromFournisseur(concurrent.fournisseur_id);
    if (!gerant) return concurrent;
    return { ...concurrent, gerant_nom: gerant };
  };

  const getAnalyseLots = () => {
    const nbLots = parseInt(formData.nombre_lots, 10) || 1;
    const dbLots = Array.isArray(formData.lots) ? formData.lots : [];
    const detailLots = Array.isArray(formData.lots_details) ? formData.lots_details : [];

    if (dbLots.length > 1) return dbLots;
    if (nbLots > 1 && detailLots.length > 0) {
      return detailLots.slice(0, nbLots).map((lot, index) => ({
        id: lot.id ?? `detail_${index}`,
        num_lot: lot.num_lot || `LOT ${index + 1}`,
        objet_lot: lot.objet_lot || '',
        estimation: lot.estimation ?? '',
      }));
    }
    if (dbLots.length > 0) return dbLots;
    return [];
  };

  const isRealLotId = (lotId) => {
    // Les IDs réels en base de données ne commencent pas par "detail_"
    return lotId && !String(lotId).startsWith('detail_');
  };

  const isMultiLots = () => {
    const nbLots = parseInt(formData.nombre_lots, 10) || 1;
    return nbLots > 1 || getAnalyseLots().length > 1;
  };

  const hasEmptyConcurrentRows = () => (
    formData.concurrents.some(c => !c.fournisseur_id || String(c.fournisseur_id).trim() === '')
  );

  const getValidConcurrents = () => (
    formData.concurrents.filter(c => c.fournisseur_id && String(c.fournisseur_id).trim() !== '')
  );

  const hasDuplicateFournisseurs = () => {
    const ids = getValidConcurrents().map(c => String(c.fournisseur_id));
    return new Set(ids).size !== ids.length;
  };

  const buildConcurrentsPayload = () => (
    getValidConcurrents().map(c => ({
      ...c,
      fournisseur_id: parseInt(c.fournisseur_id, 10),
      montant_engagement: c.montant_engagement === '' ? null : c.montant_engagement,
    }))
  );

  const validateConcurrentsBeforeSave = () => {
    if (hasEmptyConcurrentRows()) {
      setErrorMessage('Veuillez sélectionner un fournisseur pour chaque ligne ou retirer les lignes vides.');
      return false;
    }
    if (hasDuplicateFournisseurs()) {
      setErrorMessage('Chaque fournisseur ne peut apparaître qu\'une seule fois dans le tableau.');
      return false;
    }
    if (getValidConcurrents().length === 0) {
      setErrorMessage('Ajoutez au moins un concurrent avec un fournisseur sélectionné.');
      return false;
    }
    return true;
  };

  const findConcurrentIndex = (concurrent) => (
    formData.concurrents.findIndex(c =>
      (c.id && concurrent.id && c.id === concurrent.id)
      || (c.fournisseur_id && String(c.fournisseur_id) === String(concurrent.fournisseur_id))
    )
  );

  const getRetenusForLot = (lotId) => {
    if (isMultiLots()) {
      const retenusIds = Object.values(lotDecisionsState)
        .filter(d => String(d.lot_id) === String(lotId) && d.statut === 'Retenu')
        .map(d => String(d.fournisseur_id));
      return getValidConcurrents().filter(c => retenusIds.includes(String(c.fournisseur_id)));
    }
    return getValidConcurrents().filter(c => c.statut_analyse === 'retenu');
  };

  const fetchFournisseurs = async () => {
    try {
      const response = await api.get('/fournisseurs');
      setFournisseurs(response.data);
    } catch (err) {
      setErrorMessage('Erreur lors du chargement des fournisseurs.');
    }
  };

  const fetchLignesBudgetaires = async () => {
    try {
      const response = await api.get('/notification-lignes');
      setLignesBudgetaires(response.data);
    } catch (err) {
      console.error('Erreur chargement lignes budgetaires', err);
    }
  };

  const fetchMembresCommission = async () => {
    try {
      const response = await api.get('/commission-membres');
      setMembresCommissionCatalog(response.data);
      return response.data;
    } catch (err) {
      setErrorMessage('Erreur lors du chargement des membres de la commission.');
      return null;
    }
  };

  const openMembreQuickModal = () => {
    setMembreQuickForm({ nom_prenom: '', fonction: '' });
    setMembreQuickError(null);
    setIsMembreQuickModalOpen(true);
  };

  const closeMembreQuickModal = () => {
    setIsMembreQuickModalOpen(false);
    setMembreQuickForm({ nom_prenom: '', fonction: '' });
    setMembreQuickError(null);
  };

  const handleMembreQuickSubmit = async (e) => {
    e.preventDefault();
    setMembreQuickSaving(true);
    setMembreQuickError(null);

    try {
      const response = await api.post('/commission-membres', membreQuickForm);
      const createdMembre = response.data;
      await fetchMembresCommission();
      setNouveauMembre(prev => ({ ...prev, membre_commission_id: String(createdMembre.id) }));
      closeMembreQuickModal();
    } catch (err) {
      setMembreQuickError(err.response?.status === 422
        ? 'Veuillez renseigner le nom et la fonction.'
        : 'Une erreur est survenue lors de l\'enregistrement.');
    } finally {
      setMembreQuickSaving(false);
    }
  };

  const getSelectedCatalogMembre = () => (
    membresCommissionCatalog.find(m => String(m.id) === String(nouveauMembre.membre_commission_id))
  );

  const fetchDossier = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/aoos/${id}`);
      const data = response.data;
      const sanitizedData = {};
      Object.keys(data).forEach(key => {
        sanitizedData[key] = data[key] !== null ? data[key] : '';
      });

      let rawMembres = sanitizedData.membres_commission;
      if (typeof rawMembres === 'string') {
        try { rawMembres = JSON.parse(rawMembres); } catch (e) { rawMembres = []; }
      }
      if (!Array.isArray(rawMembres)) rawMembres = [];
      sanitizedData.membres_commission = rawMembres.map(m => {
        if (typeof m === 'number' || typeof m === 'string') {
          return {
            membre_commission_id: m,
            nom_prenom: '',
            fonction: '',
            qualite: 'Membre'
          };
        }
        return {
          membre_commission_id: m.membre_commission_id || m.id || null,
          nom_prenom: m.nom_prenom || `${m.nom || ''} ${m.prenom || ''}`.trim() || '',
          fonction: m.fonction || m.role || '',
          qualite: m.qualite || 'Membre'
        };
      });

      sanitizedData.concurrents = (Array.isArray(sanitizedData.concurrents) ? sanitizedData.concurrents : [])
        .map(prefillGerantNomForConcurrent);
      sanitizedData.lots = Array.isArray(sanitizedData.lots) ? sanitizedData.lots : [];
      sanitizedData.lots_details = Array.isArray(sanitizedData.lots_details) ? sanitizedData.lots_details : [];
      if (sanitizedData.lots.length > 0) {
        sanitizedData.lots_details = sanitizedData.lots.map((lot, index) => ({
          id: lot.id,
          num_lot: lot.num_lot,
          objet_lot: lot.objet_lot || '',
          estimation: lot.estimation || '',
          cautionnement_provisoire: lot.cautionnement_provisoire ?? '',
          art: lot.art || (index === 0 ? sanitizedData.art : '') || '',
          par: lot.par || (index === 0 ? sanitizedData.par : '') || '',
          lig: lot.lig || (index === 0 ? sanitizedData.lig : '') || '',
          imputation: lot.imputation || (index === 0 ? sanitizedData.imputation : '') || '',
          notification_ligne_id: lot.notification_ligne_id || (index === 0 ? sanitizedData.notification_ligne_id : '') || null,
          items: mapLotItemsFromApi(lot.items),
        }));
        if (sanitizedData.lots.length > 1) {
          sanitizedData.nombre_lots = Math.max(
            parseInt(sanitizedData.nombre_lots, 10) || 1,
            sanitizedData.lots.length
          );
        }
      } else {
        const nbLots = Math.max(1, parseInt(sanitizedData.nombre_lots, 10) || 1);
        sanitizedData.lots_details = Array.from({ length: nbLots }, (_, index) => ({
          ...createEmptyLot(index),
          art: index === 0 ? (sanitizedData.art || '') : '',
          par: index === 0 ? (sanitizedData.par || '') : '',
          lig: index === 0 ? (sanitizedData.lig || '') : '',
          imputation: index === 0 ? (sanitizedData.imputation || '') : '',
          notification_ligne_id: index === 0 ? (sanitizedData.notification_ligne_id || null) : null,
        }));
      }
      let rawJournaux = sanitizedData.publications_journaux;
      if (typeof rawJournaux === 'string') {
        try { rawJournaux = JSON.parse(rawJournaux); } catch (e) { rawJournaux = []; }
      }
      if (!Array.isArray(rawJournaux) || rawJournaux.length === 0) {
        rawJournaux = [];
        if (sanitizedData.journal_fr || sanitizedData.date_publication_fr) {
          rawJournaux.push({
            nom_journal: sanitizedData.journal_fr || '',
            numero_journal: '',
            date_publication: sanitizedData.date_publication_fr || '',
            edition: '',
            langue: 'Français',
            reference_publication: sanitizedData.reference_publication_fr || '',
          });
        }
        if (sanitizedData.journal_ar || sanitizedData.date_publication_ar) {
          rawJournaux.push({
            nom_journal: sanitizedData.journal_ar || '',
            numero_journal: '',
            date_publication: sanitizedData.date_publication_ar || '',
            edition: '',
            langue: 'Arabe',
            reference_publication: sanitizedData.reference_publication_ar || '',
          });
        }
        if (rawJournaux.length === 0) {
          rawJournaux = [{ nom_journal: '', numero_journal: '', date_publication: '', edition: '', langue: 'Français', reference_publication: '' }];
        }
      }
      sanitizedData.publications_journaux = rawJournaux;
      if (!sanitizedData.mode_passation) {
        sanitizedData.mode_passation = "D’APPEL D’OFFRES OUVERT SIMPLIFIÉ SUR OFFRES DE PRIX";
      }

      const cascaded = applyEstimationCascade(sanitizedData.lots_details, sanitizedData.nombre_lots);
      sanitizedData.lots_details = cascaded.lots_details;
      sanitizedData.budget = cascaded.budget || sanitizedData.budget || '';
      setFormData(sanitizedData);

      const existingDecisions = {};
      sanitizedData.lots.forEach(lot => {
        (lot.decisions || []).forEach(decision => {
          const key = `${decision.fournisseur_id}_${lot.id}`;
          existingDecisions[key] = {
            fournisseur_id: decision.fournisseur_id,
            lot_id: lot.id,
            postule: true,
            montant_propose: decision.montant_propose || '',
            statut: decision.statut || null,
            motif_ecartement: decision.motif_ecartement || ''
          };
        });
      });
      setLotDecisionsState(existingDecisions);

      const existingAttributions = {};
      sanitizedData.lots.forEach(lot => {
        if (lot.attributaire_fournisseur_id) {
          existingAttributions[lot.id] = String(lot.attributaire_fournisseur_id);
        }
      });
      setAttributions(existingAttributions);
    } catch (err) {
      setErrorMessage('Erreur lors du chargement de l\'AOO.');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => {
      const next = { ...prev, [name]: value };
      if (name === 'montant_depense_neuf') {
        const montant = parseNum(value);
        next.interets_moratoires = (montant * 0.01).toFixed(2);
        next.montant_engager_neuf = (montant * 1.01).toFixed(2);
      }
      return next;
    });
  };

  const handleNombreLotsChange = (e) => {
    const val = Math.max(1, parseInt(e.target.value, 10) || 1);
    setActivePreparationLotTab(0);
    setFormData(prev => {
      const newLots = [...(prev.lots_details || [])];
      while (newLots.length < val) {
        newLots.push(createEmptyLot(newLots.length));
      }
      const slicedLots = val > 1 ? newLots.slice(0, val) : newLots.slice(0, 1);
      const cascaded = applyEstimationCascade(slicedLots, val);
      return {
        ...prev,
        nombre_lots: val,
        ...cascaded,
      };
    });
  };


  const calculerTotalEstimation = () => {
    return (formData.lots_details || []).reduce((acc, lot) => {
      const lotTotal = parseFloat(lot.estimation) || 0;
      return acc + lotTotal;
    }, 0);
  };
  const calculerTotalEstimationTva = () => calculerTotalEstimation() * 0.20;
  const calculerTotalEstimationTtc = () => calculerTotalEstimation() * 1.20;

  const handleLotChange = (index, field, value) => {
    updateLotsWithCascade((lots) => {
      const newLots = [...lots];
      if (!newLots[index]) {
        newLots[index] = createEmptyLot(index);
      }
      newLots[index] = { ...newLots[index], [field]: value };
      return newLots;
    });
  };

  const handleAddLotItem = (lotIndex) => {
    updateLotsWithCascade((lots) => {
      const newLots = [...lots];
      if (!newLots[lotIndex]) {
        newLots[lotIndex] = createEmptyLot(lotIndex);
      }
      newLots[lotIndex] = {
        ...newLots[lotIndex],
        items: [...(newLots[lotIndex].items || []), createEmptyLotItem()],
      };
      return newLots;
    });
  };

  const handleRemoveLotItem = (lotIndex, itemIndex) => {
    updateLotsWithCascade((lots) => {
      const newLots = [...lots];
      if (!newLots[lotIndex]) return lots;
      newLots[lotIndex] = {
        ...newLots[lotIndex],
        items: (newLots[lotIndex].items || []).filter((_, i) => i !== itemIndex),
      };
      return newLots;
    });
  };

  const handleLotItemChange = (lotIndex, itemIndex, field, value) => {
    updateLotsWithCascade((lots) => {
      const newLots = [...lots];
      if (!newLots[lotIndex]) {
        newLots[lotIndex] = createEmptyLot(lotIndex);
      }
      const items = [...(newLots[lotIndex].items || [])];
      if (!items[itemIndex]) {
        items[itemIndex] = createEmptyLotItem();
      }
      items[itemIndex] = { ...items[itemIndex], [field]: value };
      newLots[lotIndex] = { ...newLots[lotIndex], items };
      return newLots;
    });
  };

  const handleJournauxChange = (index, field, value) => {
    setFormData(prev => {
      const currentList = Array.isArray(prev.publications_journaux) ? [...prev.publications_journaux] : [];
      if (!currentList[index]) {
        currentList[index] = { nom_journal: '', numero_journal: '', date_publication: '', edition: '', langue: 'Français', reference_publication: '' };
      }
      currentList[index] = { ...currentList[index], [field]: value };

      const frJournal = currentList.find(j => j.langue !== 'Arabe');
      const arJournal = currentList.find(j => j.langue === 'Arabe');

      return {
        ...prev,
        publications_journaux: currentList,
        journal_fr: frJournal ? frJournal.nom_journal : prev.journal_fr,
        reference_publication_fr: frJournal ? frJournal.reference_publication : prev.reference_publication_fr,
        date_publication_fr: frJournal ? frJournal.date_publication : prev.date_publication_fr,
        journal_ar: arJournal ? arJournal.nom_journal : prev.journal_ar,
        reference_publication_ar: arJournal ? arJournal.reference_publication : prev.reference_publication_ar,
        date_publication_ar: arJournal ? arJournal.date_publication : prev.date_publication_ar,
      };
    });
  };

  const handleAddJournal = () => {
    setFormData(prev => {
      const currentList = Array.isArray(prev.publications_journaux) ? [...prev.publications_journaux] : [];
      currentList.push({ nom_journal: '', numero_journal: '', date_publication: '', edition: '', langue: 'Français', reference_publication: '' });
      return { ...prev, publications_journaux: currentList };
    });
  };

  const handleRemoveJournal = (index) => {
    setFormData(prev => {
      const currentList = Array.isArray(prev.publications_journaux) ? [...prev.publications_journaux] : [];
      currentList.splice(index, 1);
      return { ...prev, publications_journaux: currentList };
    });
  };

  const formatCurrency = (value) => (
    Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  );

  const renderLotEstimationTable = (lotIndex) => {
    const lot = formData.lots_details?.[lotIndex] || createEmptyLot(lotIndex);
    const items = lot.items || [];
    const { totalHT, totalTVA, totalTTC } = computeLotTotals(items);

    return (
      <div className="space-y-4">
        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
          <table className="w-full text-sm text-left">
            <thead className="text-xs uppercase bg-slate-100 text-slate-600 border-b border-slate-200">
              <tr>
                <th className="px-3 py-3 w-12 text-center font-bold">N°</th>
                <th className="px-3 py-3 font-bold">Désignation</th>
                <th className="px-3 py-3 w-28 font-bold">Unité</th>
                <th className="px-3 py-3 w-24 font-bold">Quantité</th>
                <th className="px-3 py-3 w-28 font-bold">PU HT</th>
                <th className="px-3 py-3 w-32 font-bold text-right">Montant HT</th>
                <th className="px-3 py-3 w-12"></th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan="7" className="px-4 py-8 text-center text-slate-400 text-sm">
                    Aucune ligne. Cliquez sur « Ajouter une ligne » pour commencer le détail estimatif.
                  </td>
                </tr>
              ) : (
                items.map((item, itemIndex) => (
                  <tr key={item.id || `item_${lotIndex}_${itemIndex}`} className="border-b border-slate-100 hover:bg-slate-50/60">
                    <td className="px-3 py-2 text-center font-semibold text-slate-500">{itemIndex + 1}</td>
                    <td className="px-3 py-2">
                      <input
                        type="text"
                        value={item.designation || ''}
                        onChange={(e) => handleLotItemChange(lotIndex, itemIndex, 'designation', e.target.value)}
                        placeholder="Description de la prestation"
                        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-blue-500 bg-white"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="text"
                        value={item.unite || ''}
                        onChange={(e) => handleLotItemChange(lotIndex, itemIndex, 'unite', e.target.value)}
                        placeholder="Jour, U..."
                        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-blue-500 bg-white"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        value={item.quantite ?? ''}
                        onChange={(e) => handleLotItemChange(lotIndex, itemIndex, 'quantite', e.target.value)}
                        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-blue-500 bg-white"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        value={item.prix_unitaire_ht ?? ''}
                        onChange={(e) => handleLotItemChange(lotIndex, itemIndex, 'prix_unitaire_ht', e.target.value)}
                        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-blue-500 bg-white text-right"
                      />
                    </td>
                    <td className="px-3 py-2 text-right font-mono font-medium text-slate-700">
                      {formatCurrency(computeLineMontantHT(item))}
                      <button
                        type="button"
                        onClick={() => handleRemoveLotItem(lotIndex, itemIndex)}
                        className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        title="Supprimer la ligne"
                      >
                        <Trash2 size={16} />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <button
            type="button"
            onClick={() => handleAddLotItem(lotIndex)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus size={16} /> Ajouter une ligne
          </button>

          <div className="bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-sm space-y-1 min-w-64">
            <div className="flex justify-between gap-4">
              <span className="text-slate-600">Total HT</span>
              <span className="font-mono font-semibold">{formatCurrency(totalHT)} DH</span>
            </div>
            <div className="flex justify-between gap-4">
              <span className="text-slate-600">TVA (20%)</span>
              <span className="font-mono font-semibold">{formatCurrency(totalTVA)} DH</span>
            </div>
            <div className="flex justify-between gap-4 pt-1 border-t border-slate-300">
              <span className="font-bold text-slate-800">Estimation TTC du lot</span>
              <span className="font-mono font-bold text-blue-700">{formatCurrency(totalTTC)} DH</span>
            </div>
          </div>
        </div>
      </div>
    );
  };

  const handleAddMembre = async (customMember = null) => {
    let nomPrenom = customMember?.nom_prenom?.trim() || '';
    let fonction = customMember?.fonction?.trim() || '';
    let qualite = customMember?.qualite || nouveauMembre.qualite || 'Membre';
    let catalogId = customMember?.membre_commission_id || nouveauMembre.membre_commission_id;

    if (!customMember && catalogId) {
      const catalogMembre = (membresCommissionCatalog || []).find(m => String(m.id) === String(catalogId));
      if (catalogMembre) {
        nomPrenom = (catalogMembre.nom_prenom || `${catalogMembre.nom || ''} ${catalogMembre.prenom || ''}`).trim();
        fonction = catalogMembre.fonction || catalogMembre.role || '';
      }
    }

    if (!nomPrenom) {
      setErrorMessage('Veuillez renseigner le nom du membre ou en sélectionner un dans la liste.');
      return false;
    }

    const dejaAjoute = (formData.membres_commission || []).some(
      m => (catalogId && String(m.membre_commission_id) === String(catalogId))
        || (m.nom_prenom && m.nom_prenom.toLowerCase().trim() === nomPrenom.toLowerCase().trim())
    );
    if (dejaAjoute) {
      setErrorMessage('Ce membre fait déjà partie de la commission pour cet AOO.');
      return false;
    }

    // Auto-save to catalog in backend if custom new member
    if (!catalogId && nomPrenom) {
      try {
        const res = await api.post('/commission-membres', {
          nom_prenom: nomPrenom,
          fonction: fonction || 'Membre de commission'
        });
        if (res.data?.id) {
          catalogId = res.data.id;
        }
      } catch (err) {
        console.error('Erreur enregistrement membre en base:', err);
      }

      try {
        const updatedCatalog = await fetchMembresCommission();
        if (updatedCatalog) {
          setMembresCommissionCatalog(updatedCatalog);
        }
      } catch (err) {
        console.error('Erreur chargement catalogue:', err);
      }
    }

    setFormData(prev => ({
      ...prev,
      membres_commission: [...(prev.membres_commission || []), {
        membre_commission_id: catalogId ? String(catalogId) : null,
        nom_prenom: nomPrenom,
        fonction: fonction || 'Membre de commission',
        qualite: qualite,
      }]
    }));

    if (catalogId) {
      setNouveauMembre({ membre_commission_id: String(catalogId), qualite: qualite });
    } else {
      setNouveauMembre({ membre_commission_id: '', qualite: 'Membre' });
    }

    setErrorMessage('');
    setSuccessMessage(`Membre "${nomPrenom}" ajouté avec succès à la commission.`);
    setTimeout(() => setSuccessMessage(''), 3000);
    return true;
  };

  const handleUpdateMembreQualite = (index, newQualite) => {
    setFormData(prev => {
      const updated = [...(prev.membres_commission || [])];
      if (updated[index]) {
        updated[index] = { ...updated[index], qualite: newQualite };
      }
      return { ...prev, membres_commission: updated };
    });
  };

  const handleRemoveMembre = (index) => {
    setFormData(prev => ({
      ...prev,
      membres_commission: prev.membres_commission.filter((_, i) => i !== index)
    }));
  };

  const handleValidatePreparationRequest = () => {
    const errors = [];

    // Etape 1
    if (!formData.objet) errors.push('Objet de l\'AOO en français manquant');
    if (!formData.objet_ar) errors.push('Objet de l\'AOO en arabe manquant');
    const nLots = parseInt(formData.nombre_lots, 10);
    if (isNaN(nLots) || nLots < 1) errors.push('Le nombre de lots doit être un entier > 0');

    // Etape 2
    if (!isNaN(nLots) && nLots > 0) {
      for (let i = 0; i < nLots; i++) {
        const lot = (formData.lots_details || [])[i];
        if (!lot) {
          errors.push(`Lot ${i + 1} non configuré`);
          continue;
        }
        if (nLots > 1 && !lot.objet_lot) errors.push(`Intitulé du Lot ${i + 1} manquant`);
        const items = lot.items || [];
        if (items.length === 0) errors.push(nLots > 1 ? `Lot ${i + 1} : aucun détail estimatif` : `Détail estimatif vide`);
        else {
          for (let j = 0; j < items.length; j++) {
            if (!items[j].designation) errors.push(nLots > 1 ? `Lot ${i + 1} - Ligne ${j + 1} : désignation manquante` : `Ligne ${j + 1} : désignation manquante`);
            if (parseNum(items[j].quantite) <= 0) errors.push(nLots > 1 ? `Lot ${i + 1} - Ligne ${j + 1} : quantité invalide` : `Ligne ${j + 1} : quantité invalide`);
            if (parseNum(items[j].prix_unitaire_ht) <= 0) errors.push(nLots > 1 ? `Lot ${i + 1} - Ligne ${j + 1} : prix unitaire invalide` : `Ligne ${j + 1} : prix unitaire invalide`);
          }
        }
      }
    }

    // Etape 3 (Imputation par lot)
    const lotsForValidation = formData.lots_details || [];
    if (lotsForValidation.length === 0) {
      if (!formData.notification_ligne_id && (!formData.art || !formData.par || !formData.lig) && !formData.imputation) {
        errors.push("Imputation budgétaire incomplète (Article, Paragraphe et Ligne requis)");
      }
    } else {
      lotsForValidation.forEach((l, idx) => {
        const isComplete = (l.art && l.par && l.lig) || l.imputation || l.notification_ligne_id;
        if (!isComplete) {
          errors.push(`Imputation budgétaire incomplète pour le ${l.num_lot || `Lot ${idx + 1}`} (Article, Paragraphe et Ligne requis)`);
        }
      });
    }

    // Etape 4
    if (!formData.membres_commission || formData.membres_commission.length === 0) errors.push('La commission doit comporter au moins un membre');

    // Etape 5
    if (!formData.date_ouverture) errors.push('Date d\'ouverture des plis manquante');
    if (!formData.heure_ouverture) errors.push('Heure d\'ouverture manquante');
    if (!formData.lieu_ouverture) errors.push('Lieu de la séance manquant');

    setValidationErrors(errors);
    if (errors.length === 0) {
      setShowValidationModal(true);
    } else {
      setErrorMessage("Veuillez compléter les éléments requis avant de valider la préparation :\n" + errors.map(e => `• ${e}`).join('\n'));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const handleConfirmValidation = async () => {
    try {
      setSaving(true);
      const payload = { ...formData, statut: 'Preparation_Validee' };
      const apiMethod = id === 'nouveau' ? api.post : api.put;
      const apiUrl = id === 'nouveau' ? '/aoos' : `/aoos/${id}`;
      const res = await apiMethod(apiUrl, payload);

      setFormData(prev => ({ ...prev, statut: 'Preparation_Validee' }));
      setShowValidationModal(false);
      setSuccessMessage('Préparation validée avec succès.');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (err) {
      setErrorMessage(err.response?.data?.message || 'Erreur lors de la validation.');
    } finally {
      setSaving(false);
    }
  };

  const handleUnlockPreparation = async () => {
    if (!window.confirm("Voulez-vous vraiment déverrouiller la préparation ? Le statut repassera en Brouillon.")) return;
    try {
      setSaving(true);
      await api.put(`/aoos/${id}`, { ...formData, statut: 'Brouillon' });
      setFormData(prev => ({ ...prev, statut: 'Brouillon' }));
      setSuccessMessage('Préparation déverrouillée. Vous pouvez la modifier.');
    } catch (err) {
      setErrorMessage('Erreur lors du déverrouillage.');
    } finally {
      setSaving(false);
    }
  };


  const handleSavePublications = async () => {
    try {
      setSaving(true);
      const apiMethod = (id && id !== 'nouveau') ? api.put : api.post;
      const apiUrl = (id && id !== 'nouveau') ? `/aoos/${id}` : '/aoos';
      const res = await apiMethod(apiUrl, formData);
      const savedId = res.data?.data?.id || res.data?.id || id;
      if (id === 'nouveau' && savedId) {
        navigate(`/aoos/${savedId}`, { replace: true });
      }
      setSuccessMessage('Informations de publication enregistrées avec succès.');
    } catch (err) {
      setErrorMessage(err.response?.data?.message || 'Erreur lors de l\'enregistrement des publications.');
    } finally {
      setSaving(false);
    }
  };

  const handlePasserCommission = async () => {
    try {
      setSaving(true);
      setErrorMessage('');
      setSuccessMessage('');
      await api.post(`/aoos/${id}/passer-commission`);
      setFormData(prev => ({ ...prev, statut: 'Commission_Ouverture' }));
      setShowTransitionModal(false);
      setSuccessMessage('Préparation complète. Passage à l\'Ouverture des plis et analyse réussi.');
      setActiveTab('commission');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (err) {
      if (err.response && err.response.data && err.response.data.errors) {
        setTransitionErrors(err.response.data.errors);
        setShowTransitionModal(true);
      } else {
        setErrorMessage(err.response?.data?.error || err.response?.data?.message || 'Impossible de passer à l\'Ouverture des plis et analyse.');
      }
    } finally {
      setSaving(false);
    }
  };

  const confirmPasserCommission = async () => {
    await handlePasserCommission();
  };

  const handlePasserAttribution = () => {
    navigate('/engagements');
  };

  const handleGenerateDocument = async (type, format, action = 'download') => {
    let currentId = (id && id !== 'nouveau') ? id : (formData.id || null);
    if (!currentId) {
      try {
        setSaving(true);
        const payload = { ...formData };
        if (formData.id) payload.id = formData.id;
        const response = await api.post('/aoos', payload);
        const newId = response.data?.data?.id || response.data?.id;
        if (newId) {
          currentId = newId;
          setFormData(prev => ({ ...prev, id: newId }));
          navigate(`/aoos/${newId}`, { replace: true });
        }
      } catch (err) {
        const errorDetails = err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(' • ')
          : (err.response?.data?.message || 'Erreur lors de l\'enregistrement automatique de l\'AOO.');
        setErrorMessage(`Impossible de générer le document : ${errorDetails}`);
        setSaving(false);
        return;
      } finally {
        setSaving(false);
      }
    }
    setGeneratingDoc(`${type}-${format}-${action}`);
    try {
      const url = `/aoos/${currentId}/documents-generes/${type}${format === 'pdf' ? '?format=pdf' : ''}`;
      const response = await api.get(url, { responseType: 'blob' });

      const blob = new Blob([response.data], { type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
      const blobUrl = window.URL.createObjectURL(blob);

      if (action === 'preview') {
        window.open(blobUrl, '_blank');
        setSuccessMessage(`Aperçu du document généré avec succès.`);
      } else {
        let fileName = `Document_${type}_${format === 'pdf' ? 'pdf' : 'docx'}`;
        const fileNameHeader = response.headers['content-disposition'];
        if (fileNameHeader) {
          const match = fileNameHeader.match(/filename="?([^"]+)"?/);
          if (match && match[1]) {
            fileName = match[1];
          }
        }
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
        setSuccessMessage(`Document ${format.toUpperCase()} généré avec succès.`);
      }
    } catch (err) {
      console.error(err);
      setErrorMessage("Impossible de générer le document. Vérifiez que le template existe côté serveur.");
    } finally {
      setGeneratingDoc(null);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Strict Validation Etape 1 & 2
    if (activeTab === 'preparation') {
      const nLots = parseInt(formData.nombre_lots, 10);
      if (!formData.objet || !formData.objet_ar || isNaN(nLots) || nLots < 1) {
        let msg = "Veuillez renseigner l'objet (FR et AR) et un nombre de lots valide (>0).";
        if (!formData.objet) msg = "Veuillez renseigner l'objet de l'AOO en français.";
        else if (!formData.objet_ar) msg = "Veuillez renseigner l'objet de l'AOO en arabe.";
        else if (isNaN(nLots) || nLots < 1) msg = "Le nombre de lots doit être un entier supérieur à 0.";

        setErrorMessage(msg);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }

      // Validation Etape 5 (Séance d'ouverture)
      if (!formData.date_ouverture) {
        setErrorMessage("Veuillez renseigner la date d'ouverture des plis.");
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }
      if (!formData.heure_ouverture) {
        setErrorMessage("Veuillez renseigner l'heure d'ouverture.");
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }
      if (!formData.lieu_ouverture || formData.lieu_ouverture.trim() === '') {
        setErrorMessage("Veuillez renseigner le lieu de la séance.");
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }

      // Validation Etape 3 (Imputation par lot)
      const lotsForSubmit = formData.lots_details || [];
      if (lotsForSubmit.length === 0) {
        if (!formData.notification_ligne_id && (!formData.art || !formData.par || !formData.lig) && !formData.imputation) {
          setErrorMessage("Veuillez renseigner l'imputation budgétaire (Article, Paragraphe, Ligne).");
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }
      } else {
        for (let i = 0; i < lotsForSubmit.length; i++) {
          const l = lotsForSubmit[i];
          const isComplete = (l.art && l.par && l.lig) || l.imputation || l.notification_ligne_id;
          if (!isComplete) {
            setErrorMessage(`Veuillez renseigner l'imputation budgétaire pour le ${l.num_lot || `Lot ${i + 1}`} (Article, Paragraphe, Ligne).`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }
        }
      }

      // Validation Etape 2 (Lignes budgétaires)
      const lotsDetails = formData.lots_details || [];
      const numExpectedLots = nLots;

      for (let i = 0; i < numExpectedLots; i++) {
        const lot = lotsDetails[i];
        if (!lot) continue;

        if (numExpectedLots > 1 && !lot.objet_lot) {
          setErrorMessage(`L'intitulé du Lot ${i + 1} est obligatoire.`);
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }

        const items = lot.items || [];
        if (items.length === 0) {
          setErrorMessage(`Le ${numExpectedLots > 1 ? 'Lot ' + (i + 1) : 'détail estimatif'} doit contenir au moins une ligne.`);
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }

        for (let j = 0; j < items.length; j++) {
          const item = items[j];
          if (!item.designation) {
            setErrorMessage(`Ligne ${j + 1} (${numExpectedLots > 1 ? 'Lot ' + (i + 1) : 'Détail'}) : La désignation est obligatoire.`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }
          if (item.quantite === undefined || item.quantite === null || parseFloat(item.quantite) <= 0) {
            setErrorMessage(`Ligne ${j + 1} (${numExpectedLots > 1 ? 'Lot ' + (i + 1) : 'Détail'}) : La quantité doit être supérieure à 0.`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }
          if (item.prix_unitaire_ht === undefined || item.prix_unitaire_ht === null || parseFloat(item.prix_unitaire_ht) < 0) {
            setErrorMessage(`Ligne ${j + 1} (${numExpectedLots > 1 ? 'Lot ' + (i + 1) : 'Détail'}) : Le prix unitaire HT doit être supérieur ou égal à 0.`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }
        }
      }
    }

    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');

    try {
      const payload = { ...formData };
      if (id && id !== 'nouveau') {
        payload.id = id;
      }

      const response = await api.post('/aoos', payload);
      setSuccessMessage('Estimation enregistrée avec succès.');

      if (!id || id === 'nouveau') {
        setTimeout(() => {
          navigate(`/aoos/${response.data.data.id}`);
        }, 1500);
      } else {
        fetchDossier();
      }
    } catch (err) {
      setErrorMessage('Erreur lors de l\'enregistrement. Veuillez vérifier les champs.');
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  const handleAddConcurrent = () => {
    setFormData(prev => ({
      ...prev,
      concurrents: [
        ...prev.concurrents,
        {
          fournisseur_id: '',
          nom_soumissionnaire: '',
          dh: false, cp: false, rc: false, cps: false,
          m_hum: false,
          montant_engagement: '',
          observations: '',
          ref_courrier: prev.ref_courrier_analyse || '',
          signataire_titre: prev.signataire_titre_analyse || '',
          gerant_nom: '',
          statut_analyse: null,
          motif_ecartement: ''
        }
      ]
    }));
  };

  const handleRemoveConcurrent = (index) => {
    setFormData(prev => ({
      ...prev,
      concurrents: prev.concurrents.filter((_, i) => i !== index)
    }));
  };

  const handleConcurrentChange = (index, field, value) => {
    setFormData(prev => {
      const updated = [...prev.concurrents];
      updated[index][field] = value;
      if (field === 'fournisseur_id') {
        const fournisseur = fournisseurs.find(f => String(f.id) === String(value));
        updated[index].nom_soumissionnaire = fournisseur?.raison_sociale || '';
        updated[index].fournisseur = fournisseur || null;
        if (!updated[index].gerant_nom || String(updated[index].gerant_nom).trim() === '') {
          updated[index].gerant_nom = getGerantFromFournisseur(fournisseur);
        }
      }
      return { ...prev, concurrents: updated };
    });
  };

  const handleGlobalAnalyseChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => {
      const updatedConcurrents = prev.concurrents.map(c => ({
        ...c,
        [name === 'ref_courrier_analyse' ? 'ref_courrier' : 'signataire_titre']: value
      }));
      return { ...prev, [name]: value, concurrents: updatedConcurrents };
    });
  };

  const saveOuverturePlis = async () => {
    if (!id || id === 'nouveau') {
      setErrorMessage("Veuillez d'abord enregistrer l'AOO (Phase 1) avant de sauvegarder l'ouverture des plis.");
      return;
    }
    if (!validateConcurrentsBeforeSave()) return;

    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');
    try {
      const response = await api.post(`/aoos/${id}/ouverture-plis`, {
        concurrents: buildConcurrentsPayload()
      });
      if (response.data?.concurrents) {
        setFormData(prev => ({ ...prev, concurrents: response.data.concurrents }));
      }
      setSuccessMessage("Ouverture des plis enregistrée avec succès.");
    } catch (err) {
      setErrorMessage(err.response?.data?.error || err.response?.data?.message || "Erreur lors de l'enregistrement de l'ouverture des plis.");
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  const saveAnalyse = async () => {
    if (!id || id === 'nouveau') return;
    if (!validateConcurrentsBeforeSave()) return;

    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');
    try {
      await api.post('/aoos', { ...formData, id });
      await api.post(`/aoos/${id}/ouverture-plis`, { concurrents: buildConcurrentsPayload() });
      setSuccessMessage("Analyse technique et financière enregistrée avec succès.");
    } catch (err) {
      setErrorMessage(err.response?.data?.error || err.response?.data?.message || "Erreur lors de l'enregistrement de l'analyse.");
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  // --- Multi-lot Analysis Save ---
  const saveAnalyseMultiLots = async (decisions) => {
    if (!id || id === 'nouveau') return;
    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');
    try {
      await api.post(`/aoos/${id}/analyse-multi-lots`, { decisions });
      setSuccessMessage("Décisions multi-lots enregistrées avec succès.");
    } catch (err) {
      setErrorMessage(err.response?.data?.error || err.response?.data?.message || "Erreur lors de l'enregistrement des décisions.");
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  // --- Multi-lot Attribution ---
  const handleAttribution = (lotId, fournisseurId) => {
    setAttributions(prev => ({ ...prev, [lotId]: fournisseurId }));
  };

  const saveAttribuerLots = async () => {
    if (!id || id === 'nouveau') return;
    const payload = Object.entries(attributions)
      .filter(([, cId]) => cId)
      .map(([lotId, fournisseurId]) => ({ lot_id: parseInt(lotId), fournisseur_id: parseInt(fournisseurId) }));
    if (payload.length === 0) {
      setErrorMessage('Veuillez sélectionner un attributaire pour chaque lot.');
      return;
    }
    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');
    try {
      const res = await api.post(`/aoos/${id}/attribuer-lots`, { attributions: payload });
      setSuccessMessage(res.data.message);
      setFormData(prev => ({ ...prev, statut: 'attribue' }));
    } catch (err) {
      setErrorMessage(err.response?.data?.error || "Erreur lors de l'attribution.");
    } finally {
      setSaving(false);
    }
  };

  const handleLotDecisionChange = (fournisseurId, lotId, field, value) => {
    const key = `${fournisseurId}_${lotId}`;
    setLotDecisionsState(prev => {
      if (field === 'postule' && !value) {
        const next = { ...prev };
        delete next[key];
        return next;
      }

      const existing = prev[key] || { fournisseur_id: fournisseurId, lot_id: lotId };
      const updated = { ...existing, [field]: value };

      if (field === 'postule' && value) {
        updated.montant_propose = '';
        updated.statut = null;
        updated.motif_ecartement = '';
      }

      return { ...prev, [key]: updated };
    });
  };

  const isPostulingForLot = (fournisseurId, lotId) => {
    const key = `${fournisseurId}_${lotId}`;
    return !!(lotDecisionsState[key]?.postule);
  };

  const getDecisionForLot = (fournisseurId, lotId) => {
    const key = `${fournisseurId}_${lotId}`;
    return lotDecisionsState[key] || {};
  };

  const parseMontant = (val) => {
    if (val === '' || val === null || val === undefined) return null;
    const n = parseFloat(String(val).replace(',', '.'));
    return Number.isFinite(n) ? n : null;
  };

  const formatMontant = (val) => (
    val === null
      ? '-'
      : val.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  );

  const formatMontantDH = (val) => `${formatMontant(val)} DH`;

  const montantsStrictementEgaux = (a, b) => (
    a !== null && b !== null && Math.round(a * 100) === Math.round(b * 100)
  );

  const validateMultiLotMontantsAgainstEngagement = () => {
    for (const concurrent of getValidConcurrents()) {
      const fournisseurId = concurrent.fournisseur_id;
      const montantPhase2 = parseMontant(concurrent.montant_engagement);

      const lotsCoches = Object.values(lotDecisionsState).filter(
        d => String(d.fournisseur_id) === String(fournisseurId) && d.postule
      );

      if (lotsCoches.length === 0) continue;

      const sommeLots = lotsCoches.reduce(
        (acc, d) => acc + (parseMontant(d.montant_propose) || 0),
        0
      );

      if (!montantsStrictementEgaux(sommeLots, montantPhase2)) {
        setErrorMessage(
          `Attention : Pour ${getFournisseurName(concurrent)}, la somme des montants des lots (${formatMontantDH(sommeLots)}) ne correspond pas au montant de l'acte d'engagement global (${formatMontantDH(montantPhase2)}). Veuillez ventiler correctement le montant entre les différents lots.`
        );
        return false;
      }
    }
    return true;
  };

  const handleSaveAllMultiLotDecisions = () => {
    const decisions = Object.values(lotDecisionsState)
      .filter(d => d.postule)
      .map(d => ({
        fournisseur_id: d.fournisseur_id,
        lot_id: d.lot_id,
        montant_propose: d.montant_propose || null,
        statut: d.statut || null,
        motif_ecartement: d.motif_ecartement || null,
      }));

    const hasIncompleteDecision = decisions.some(d => !d.fournisseur_id || !d.lot_id || !d.montant_propose || !d.statut);
    if (hasIncompleteDecision) {
      setErrorMessage('Chaque lot coche doit avoir un montant et un resultat analyse.');
      return;
    }

    if (!validateMultiLotMontantsAgainstEngagement()) return;

    saveAnalyseMultiLots(decisions);
  };

  const downloadDocument = async (documentType) => {
    if (!id || id === 'nouveau') return;

    try {
      const response = await api.get(`/aoos/${id}/documents/${documentType}`, {
        responseType: 'blob',
        headers: {
          Accept: 'application/pdf',
        },
        validateStatus: () => true,
      });

      // Vérifier le status HTTP
      if (response.status !== 200) {
        // Essayer de parser le message d'erreur JSON
        try {
          const text = await response.data.text();
          const errorData = JSON.parse(text);
          throw new Error(errorData.message || errorData.error || 'Erreur lors du téléchargement');
        } catch (e) {
          throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
      }

      // Vérifier que c'est un PDF valide
      const contentType = response.headers['content-type'] || '';
      if (!contentType.includes('application/pdf')) {
        throw new Error('La réponse n\'est pas un PDF valide');
      }

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${documentType}_${id}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement document AOO:', error);
      alert(`Impossible de télécharger le document: ${error.message}`);
    }
  };

  const downloadLettreNotification = async (fournisseurId, lotId) => {
    if (!id || id === 'nouveau' || !fournisseurId || !lotId) return;

    // Vérifier que c'est un ID réel (pas un ID temporaire)
    if (!isRealLotId(lotId)) {
      alert('Ce lot n\'a pas encore été sauvegardé en base de données. Veuillez d\'abord enregistrer l\'AOO.');
      return;
    }

    try {
      const response = await api.get(`/aoos/${id}/lettres-notification/${fournisseurId}/${lotId}`, {
        responseType: 'blob',
        headers: {
          Accept: 'application/pdf',
        },
        validateStatus: () => true,
      });

      // Vérifier le status HTTP
      if (response.status !== 200) {
        // Essayer de parser le message d'erreur JSON
        try {
          const text = await response.data.text();
          const errorData = JSON.parse(text);
          throw new Error(errorData.message || errorData.error || 'Erreur lors du téléchargement');
        } catch (e) {
          throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
      }

      // Vérifier que c'est un PDF valide
      const contentType = response.headers['content-type'] || '';
      if (!contentType.includes('application/pdf')) {
        throw new Error('La réponse n\'est pas un PDF valide');
      }

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `lettre_notification_${id}_${fournisseurId}_${lotId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement lettre notification:', error);
      alert(`Impossible de télécharger la lettre de notification: ${error.message}`);
    }
  };

  const getMarcheNouveauUrl = (lotId = null) => {
    const params = new URLSearchParams({ aoo_id: String(id) });
    const resolvedLotId = lotId
      || (isMultiLots()
        ? (formData.lots.find(l => attributions[l.id] || l.attributaire_fournisseur_id) || formData.lots[0])?.id
        : formData.lots[0]?.id);
    if (resolvedLotId) {
      params.set('lot_id', String(resolvedLotId));
    }
    return `/marches/nouveau?${params.toString()}`;
  };

  const cloturerAoo = async () => {
    if (!id || id === 'nouveau') return;
    setSaving(true);
    try {
      const response = await api.post(`/aoos/${id}/cloturer`);
      setSuccessMessage('AOO clôturé avec succès. Redirection vers les marchés...');
      const marcheId = response.data.marche_id;
      setTimeout(() => {
        navigate('/engagements');
      }, 1500);
    } catch (err) {
      setErrorMessage(err.response?.data?.error || "Erreur lors de la clôture de l'AOO.");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-screen bg-slate-50 text-blue-600">
        <Loader2 className="animate-spin mb-4" size={48} />
        <h2 className="text-xl font-bold">Chargement de l'AOO...</h2>
      </div>
    );
  }

  const preparationLotCount = Math.max(1, parseInt(formData.nombre_lots, 10) || 1);
  const currentPreparationLotTab = Math.min(activePreparationLotTab, preparationLotCount - 1);

  const renderActionBanner = (buttonText, documents) => (
    <div className="mt-8 p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col xl:flex-row items-center justify-between gap-6 shadow-sm">
      <button
        type="submit"
        disabled={saving}
        className="w-full xl:w-auto px-8 py-3 bg-primary text-white font-bold rounded-xl shadow-md shadow-primary/20 hover:bg-primary-dark transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
      >
        {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
        {saving ? 'Enregistrement...' : buttonText}
      </button>

      <div className="flex flex-wrap items-center justify-center xl:justify-end gap-3 w-full xl:w-auto">
        {id && id !== 'nouveau' ? (
          documents.map((doc, idx) => (
            <button
              key={idx}
              type="button"
              onClick={() => downloadDocument(doc.key)}
              className="px-5 py-2.5 text-sm font-bold text-emerald-700 bg-white border border-emerald-200 shadow-sm rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all flex items-center gap-2"
            >
              <Download size={16} className="text-emerald-500" /> {doc.label}
            </button>
          ))
        ) : (
          <div className="flex items-center gap-2 text-amber-600 bg-amber-50 px-4 py-2 rounded-xl border border-amber-200 text-sm font-medium">
            <AlertCircle size={16} /> Enregistrez d'abord pour générer les documents
          </div>
        )}
      </div>
    </div>
  );

  const registreRows = [
    ...(formData.registre_engagement ? [{ row: formData.registre_engagement, marche: null }] : []),
    ...(formData.marches || [])
      .filter((marche) => marche.registre_engagement)
      .map((marche) => ({ row: marche.registre_engagement, marche })),
  ];
  const registreRowsFiltres = registreRows.filter(({ row }) => row.budget === registreBudget);

  return (
    <div className="min-h-screen bg-slate-50/50 pb-24">
      <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div className="w-full px-4 sm:px-6 lg:px-8">
          <div className="py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex min-w-0 items-start sm:items-center gap-3 sm:gap-4">
              <button onClick={() => navigate('/dashboard')} className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all">
                <ArrowLeft size={20} />
              </button>
              <div>
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-2 sm:gap-3">
                  <Building2 className="text-primary" />
                  {id && id !== 'nouveau' ? `Appel d'Offres : ${formData.num_aoo || 'En cours'}` : 'Nouvel Appel d\'Offres'}
                </h1>
                <p className="text-slate-500 text-sm mt-1">Gestion du cycle de vie de l'Appel d'Offres (Module 1)</p>
              </div>
            </div>

            <div className="flex w-full sm:w-auto flex-wrap items-center gap-2 sm:justify-end">
              {successMessage && (
                <div className="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl font-bold text-sm flex items-center gap-2 border border-emerald-100 animate-fade-in-down">
                  <CheckCircle size={18} /> {successMessage}
                </div>
              )}
              {errorMessage && (
                <div className="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold text-sm flex items-center gap-2 border border-red-100 animate-fade-in-down">
                  <AlertCircle size={18} /> {errorMessage}
                </div>
              )}
            </div>
          </div>
          {/* TABS NAVIGATION */}
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2 sm:gap-4 pb-4">
            <button onClick={() => setActiveTab('preparation')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'preparation'
              ? 'bg-primary text-white shadow-lg'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}>
              <FileText size={24} /> 1. Préparation
            </button>
            <button onClick={() => {
              setActiveTab('commission');
            }} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'commission'
              ? 'bg-primary text-white shadow-lg'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}>
              <Users size={24} /> 2. Ouverture des plis et analyse
            </button>
            <button onClick={() => setActiveTab('engagement')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'engagement'
              ? 'bg-primary text-white shadow-lg'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}>
              <CheckSquare size={24} /> 3. Engagement
            </button>
            <button onClick={() => setActiveTab('registre')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'registre'
              ? 'bg-primary text-white shadow-lg'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}>
              <FileSpreadsheet size={24} /> 4. Registre d'engagement
            </button>
          </div>
        </div>
      </header>

      <main className="w-full px-3 sm:px-6 lg:px-8 mt-8 sm:mt-16">
        <form onSubmit={handleSubmit} className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 shadow-sm border border-slate-200">

          {/* TAB 1 : PREPARATION */}
          <div className={activeTab === 'preparation' ? 'block animate-fade-in' : 'hidden'}>
            <PreparationStep
              formData={formData}
              handleChange={handleChange}
              handleLotsDetailsChange={handleLotChange}
              handleLotItemChange={handleLotItemChange}
              addLotItem={handleAddLotItem}
              removeLotItem={handleRemoveLotItem}
              addLot={() => {
                const lotsDetails = formData.lots_details ? [...formData.lots_details] : [];
                lotsDetails.push(createEmptyLot(lotsDetails.length));
                setFormData({ ...formData, lots_details: lotsDetails, nombre_lots: lotsDetails.length });
              }}
              removeLot={(index) => {
                const lotsDetails = formData.lots_details ? [...formData.lots_details] : [];
                lotsDetails.splice(index, 1);
                setFormData({ ...formData, lots_details: lotsDetails, nombre_lots: lotsDetails.length });
              }}
              handleJournauxChange={handleJournauxChange}
              addJournal={handleAddJournal}
              removeJournal={handleRemoveJournal}
              calculerTotalEstimation={calculerTotalEstimation}
              calculerTotalEstimationTva={calculerTotalEstimationTva}
              calculerTotalEstimationTtc={calculerTotalEstimationTtc}
              nouveauMembre={nouveauMembre}
              setNouveauMembre={setNouveauMembre}
              membresCommissionCatalog={membresCommissionCatalog}
              ajouterMembre={handleAddMembre}
              retirerMembre={handleRemoveMembre}
              handleUpdateMembreQualite={handleUpdateMembreQualite}
              handleSave={handleSubmit}
              handleValidatePreparation={handleValidatePreparationRequest}
              handleUnlockPreparation={handleUnlockPreparation}
              handleGenerateDocument={handleGenerateDocument}
              handleSavePublications={handleSavePublications}
              handlePasserCommission={handlePasserCommission}
              saving={saving}
              id={id}
              generatingDoc={generatingDoc}
              lignesBudgetaires={lignesBudgetaires}
              setFormData={setFormData}
            />
          </div>

          {/* TAB 2 : COMMISSION & OUVERTURE */}
          <div className={activeTab === 'commission' ? 'block animate-fade-in' : 'hidden'}>
            <CommissionOuvertureStep
              formData={formData}
              handleChange={handleChange}
              handleCompanyChange={(index, field, value) => {
                const current = Array.isArray(formData.concurrents) ? formData.concurrents : [];
                const newConcurrents = [...current];
                newConcurrents[index] = { ...newConcurrents[index], [field]: value };
                setFormData(prev => ({ ...prev, concurrents: newConcurrents }));
              }}
              importPreview={Array.isArray(formData.concurrents) ? formData.concurrents : []}
              setImportPreview={(val) => {
                setFormData(prev => {
                  const currentConcurrents = Array.isArray(prev.concurrents) ? prev.concurrents : [];
                  const nextConcurrents = typeof val === 'function' ? val(currentConcurrents) : (Array.isArray(val) ? val : []);
                  return { ...prev, concurrents: nextConcurrents };
                });
              }}
              fournisseurs={fournisseurs}
              refreshFournisseurs={fetchFournisseurs}
              id={id}
              setSuccessMessage={setSuccessMessage}
              setErrorMessage={setErrorMessage}
              validateCommission={async () => {
                await fetchDossier();
              }}
              downloadDocument={downloadDocument}
              handlePasserAttribution={handlePasserAttribution}
            />
          </div>

          <div className={activeTab === 'engagement' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Fiche d'Engagement Budgétaire</h2>
              <p className="mt-1 text-sm text-slate-500">Les champs sont enregistrés avec cet appel d'offres et seront disponibles lors de la création du marché.</p>
            </div>
            <div className="mb-5 max-w-sm">
              <label className="block text-xs font-bold text-slate-700">
                Type de budget
                <select
                  name="type_budget"
                  value={formData.type_budget || 'Investissement'}
                  onChange={handleChange}
                  className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                  <option value="Investissement">Investissement</option>
                  <option value="Fonctionnement">Fonctionnement</option>
                </select>
              </label>
            </div>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
              {[
                ['numero_engagement', "N° Fiche d'Engagement", 'text'],
                ['date_engagement', 'Date de visa / engagement', 'date'],
                ['credit_ouvert_cp', 'Crédit ouvert CP', 'number'],
                ['credit_ouvert_ce', 'Crédit ouvert CE', 'number'],
                ['depenses_anterieures_cp', 'Dépenses antérieures CP', 'number'],
                ['depenses_anterieures_ce', 'Dépenses antérieures CE', 'number'],
                ['depenses_credits_engagement', "Dépenses sur crédits d'engagement", 'number'],
                ['depenses_rap', 'Dépenses sur reste à payer', 'number'],
                ['montant_depense_neuf', 'Montant de la dépense neuve', 'number'],
                ['interets_moratoires', 'Intérêts moratoires 1 %', 'number'],
                ['montant_engager_neuf', 'Montant à engager neuf', 'number'],
              ].filter(([name]) => {
                if (String(formData.type_budget || '').toLowerCase() !== 'fonctionnement') return true;
                return !['credit_ouvert_ce', 'depenses_anterieures_ce', 'depenses_credits_engagement'].includes(name);
              }).map(([name, label, type]) => (
                <label key={name} className="block text-xs font-bold text-slate-700">
                  {label}
                  <input
                    name={name}
                    type={type}
                    value={formData[name] ?? ''}
                    onChange={handleChange}
                    readOnly={name === 'interets_moratoires' || name === 'montant_engager_neuf'}
                    className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 read-only:bg-slate-100"
                  />
                </label>
              ))}
            </div>
            <div className="mt-8 flex justify-end">
              <button type="submit" disabled={saving} className="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-bold text-white shadow-md disabled:opacity-60">
                {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />} Enregistrer la fiche
              </button>
            </div>
          </div>

          <div className={activeTab === 'registre' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 flex items-center justify-between border-b border-slate-100 pb-4">
              <div>
                <h2 className="flex items-center gap-2 text-xl font-extrabold text-slate-800">
                  <FileSpreadsheet className="text-blue-700" /> Registre d'engagement
                </h2>
                <p className="mt-1 text-sm text-slate-500">Registre identique à celui des bons de commande, filtré sur cet appel d'offres.</p>
              </div>
            </div>
            <div className="mb-5 flex flex-wrap gap-2">
              {['Investissement', 'Fonctionnement'].map((budgetType) => (
                <button
                  key={budgetType}
                  type="button"
                  onClick={() => setRegistreBudget(budgetType)}
                  className={`rounded-xl px-5 py-2.5 text-sm font-bold transition-colors ${registreBudget === budgetType
                    ? 'bg-blue-700 text-white shadow-sm'
                    : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-blue-700'
                    }`}
                >
                  Registre {budgetType}
                </button>
              ))}
            </div>
            {registreRowsFiltres.length === 0 ? (
              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-10 text-center text-slate-500">
                Aucune ligne d'engagement enregistrée dans le registre {registreBudget.toLowerCase()}.
              </div>
            ) : (
              <div className="overflow-x-auto rounded-2xl border border-slate-200">
                <table className="min-w-[1500px] w-full border-collapse text-xs text-left">
                  <thead className="bg-slate-100 font-extrabold text-slate-700">
                    <tr>
                      <th className="px-4 py-3">N° ordre</th>
                      <th className="px-4 py-3">N° fiche</th>
                      <th className="px-4 py-3">Date</th>
                      <th className="px-4 py-3">Référence marché</th>
                      <th className="px-4 py-3">Budget</th>
                      <th className="px-4 py-3">ART</th>
                      <th className="px-4 py-3">PAR</th>
                      <th className="px-4 py-3">LIG</th>
                      <th className="px-4 py-3">Crédit ouvert CP</th>
                      <th className="px-4 py-3">Dépense neuve</th>
                      <th className="px-4 py-3">Intérêts 1%</th>
                      <th className="px-4 py-3">À engager neuf</th>
                      <th className="px-4 py-3">Bénéficiaire</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {registreRowsFiltres.map(({ row, marche }) => {
                      const money = value => value === null || value === undefined || value === '' ? '-' : Number(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                      return (
                        <tr key={row.id} className="hover:bg-blue-50/40">
                          <td className="px-4 py-3">{row.numero_ordre || '-'}</td>
                          <td className="px-4 py-3 font-bold text-blue-700">{row.numero_rubrique || '-'}</td>
                          <td className="px-4 py-3">{row.date_engagement ? new Date(row.date_engagement).toLocaleDateString('fr-FR') : '-'}</td>
                          <td className="px-4 py-3 font-bold">{row.reference || marche?.num_marche || '-'}</td>
                          <td className="px-4 py-3">{row.budget || '-'}</td>
                          <td className="px-4 py-3">{row.art || '-'}</td>
                          <td className="px-4 py-3">{row.par || '-'}</td>
                          <td className="px-4 py-3">{row.lig || '-'}</td>
                          <td className="px-4 py-3 text-right">{money(row.credit_ouvert_cp)}</td>
                          <td className="px-4 py-3 text-right">{money(row.montant_depense_neuf)}</td>
                          <td className="px-4 py-3 text-right">{money(row.interets_moratoires)}</td>
                          <td className="px-4 py-3 text-right">{money(row.montant_engager_neuf)}</td>
                          <td className="px-4 py-3">{row.beneficiaire || marche?.titulaire || '-'}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>



        </form>

        {/* Modal Transition Étape 9 */}
        {showTransitionModal && (
          <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
              <div className="p-6 border-b border-slate-100 flex items-center justify-between bg-emerald-50 text-emerald-800">
                <h3 className="text-xl font-black flex items-center gap-2">
                  <CheckCircle size={24} /> Préparation Complète
                </h3>
                <button onClick={() => setShowTransitionModal(false)} className="text-emerald-600 hover:text-emerald-900 transition-colors">
                  <X size={24} />
                </button>
              </div>

              <div className="p-6 overflow-y-auto">
                <p className="text-slate-600 font-medium mb-6">
                  Vous êtes sur le point de clôturer définitivement la phase de Préparation. Le système va procéder à l'ultime vérification des données.
                </p>

                {transitionErrors.length > 0 && (
                  <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl">
                    <div className="font-bold flex items-center gap-2 mb-2"><AlertTriangle size={18} /> Impossible de passer à l'Ouverture des plis et analyse</div>
                    <ul className="list-disc ml-5 space-y-1 text-sm font-medium">
                      {transitionErrors.map((err, i) => (
                        <li key={i}>{err}</li>
                      ))}
                    </ul>
                  </div>
                )}

                <p className="text-center font-bold text-slate-800 text-lg">
                  Voulez-vous passer à l'Ouverture des plis et analyse ?
                </p>
              </div>

              <div className="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button onClick={() => setShowTransitionModal(false)} className="px-6 py-2.5 text-slate-600 font-bold hover:bg-slate-200 rounded-xl transition-colors">
                  Annuler
                </button>
                <button
                  onClick={confirmPasserCommission}
                  disabled={saving}
                  className="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-all flex items-center gap-2 shadow-md shadow-emerald-500/20"
                >
                  {saving ? 'Vérification...' : <>Continuer</>}
                </button>
              </div>
            </div>
          </div>
        )}

      </main>

      {/* MODAL DE VALIDATION */}
      {showValidationModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-200">
            <div className="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0">
              <h3 className="font-extrabold text-slate-800 text-lg flex items-center gap-2">
                <CheckCircle className="text-emerald-600" /> Résumé de la Préparation
              </h3>
              <button onClick={() => setShowValidationModal(false)} className="text-slate-400 hover:text-slate-600">
                <X size={24} />
              </button>
            </div>

            <div className="p-6">
              {(() => {

                const totalGlobalTTC_modal = formData.lots_details?.reduce((acc, lot) => {
                  const { totalTTC } = computeLotTotals(lot.items || []);
                  return acc + totalTTC;
                }, 0) || 0;
                const totalGlobalHT_modal = formData.lots_details?.reduce((acc, lot) => {
                  const { totalHT } = computeLotTotals(lot.items || []);
                  return acc + totalHT;
                }, 0) || 0;
                const totalGlobalTVA_modal = formData.lots_details?.reduce((acc, lot) => {
                  const { totalTVA } = computeLotTotals(lot.items || []);
                  return acc + totalTVA;
                }, 0) || 0;

                return (
                  <div className="space-y-6">
                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                      <div className="text-xs text-slate-500 font-bold mb-1">AOO : {formData.num_aoo || 'N/A'}</div>
                      <div className="text-sm font-semibold text-slate-800">FR : {formData.objet}</div>
                      <div className="text-sm font-semibold text-slate-800 text-right mt-1" dir="rtl">AR : {formData.objet_ar}</div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div className="text-xs text-slate-500 font-bold mb-1">Lots</div>
                        <div className="text-lg font-black text-slate-800">{formData.nombre_lots}</div>
                      </div>
                      <div className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div className="text-xs text-slate-500 font-bold mb-1">Membres Commission</div>
                        <div className="text-lg font-black text-slate-800">{formData.membres_commission?.length || 0}</div>
                      </div>
                    </div>

                    <div className="bg-blue-50 p-4 rounded-xl border border-blue-100">
                      <div className="flex justify-between items-center mb-1">
                        <span className="text-sm text-blue-800 font-medium">Estimation HT</span>
                        <span className="font-mono font-bold text-blue-900">{formatCurrency(totalGlobalHT_modal)} MAD</span>
                      </div>
                      <div className="flex justify-between items-center mb-1">
                        <span className="text-sm text-blue-800 font-medium">TVA (20%)</span>
                        <span className="font-mono font-bold text-blue-900">{formatCurrency(totalGlobalTVA_modal)} MAD</span>
                      </div>
                      <div className="flex justify-between items-center pt-2 mt-2 border-t border-blue-200">
                        <span className="text-base text-blue-900 font-black">Estimation TTC</span>
                        <span className="font-mono font-black text-blue-900 text-lg">{formatCurrency(totalGlobalTTC_modal)} MAD</span>
                      </div>
                    </div>

                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                      <div className="text-sm font-bold text-slate-700 mb-2">Séance d'ouverture :</div>
                      <div className="grid grid-cols-2 gap-2 text-sm text-slate-600">
                        <div><span className="font-semibold text-slate-500">Date:</span> {formData.date_ouverture}</div>
                        <div><span className="font-semibold text-slate-500">Heure:</span> {formData.heure_ouverture}</div>
                        <div className="col-span-2"><span className="font-semibold text-slate-500">Lieu:</span> {formData.lieu_ouverture}</div>
                      </div>
                    </div>

                    <div className="mt-8 text-center">
                      <p className="text-emerald-700 font-bold mb-4">
                        Toutes les informations obligatoires sont complètes.<br />Voulez-vous valider définitivement la Préparation ?
                      </p>
                      <div className="flex items-center justify-center gap-4">
                        <button
                          type="button"
                          onClick={() => setShowValidationModal(false)}
                          className="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 transition-colors"
                        >
                          Annuler
                        </button>
                        <button
                          type="button"
                          onClick={handleConfirmValidation}
                          disabled={saving}
                          className="px-8 py-2.5 bg-emerald-600 text-white font-black rounded-xl hover:bg-emerald-700 transition-colors shadow-md flex items-center gap-2"
                        >
                          <CheckCircle size={18} />
                          {saving ? 'Validation...' : 'Confirmer la validation'}
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })()}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default GestionAoo;
