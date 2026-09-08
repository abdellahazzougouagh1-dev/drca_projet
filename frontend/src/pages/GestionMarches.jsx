import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import api from '../api/axios';
import { Save, CheckCircle, AlertCircle, Loader2, ArrowLeft, ArrowRight, Download, Briefcase, FileSignature, Package, HandCoins, ReceiptText, Banknote, Edit3, Eye, FileText } from 'lucide-react';
import { EngagementFormModal } from '../components/EngagementFormModal';
import { EngagementPreviewModal } from '../components/EngagementPreviewModal';

const toText = (value, fallback = '') => {
  if (value === null || value === undefined || value === '') return fallback;
  if (typeof value === 'string' || typeof value === 'number') return String(value);
  if (typeof value === 'object' && value.num_lot) return String(value.num_lot);
  return fallback;

};

const buildDefaultNumMarche = (numAoo, lotLabel) => {
  const lot = toText(lotLabel, 'LOT');
  if (!numAoo) return '';
  return `M-${String(numAoo).replace(/\//g, '-')}-${lot}`;
};

const getLotDropdownLabel = (lot, parentAooObjet) => {
  const objetLot = (lot?.objet_lot ?? '').trim();
  const objetParent = (parentAooObjet ?? '').trim();
  const displayObjet = (!objetLot || objetLot === 'Sans objet')
    ? (objetParent || 'Sans objet')
    : objetLot;
  return `${lot.num_lot} — ${displayObjet}`;
};

const resolveFournisseurQualite = (aoo, titulaire, fournisseurId) => {
  if (!aoo) return '';

  const normalize = (value) => String(value || '').trim().toLowerCase();
  const targetName = normalize(titulaire);
  const targetId = fournisseurId ? String(fournisseurId).trim() : '';

  const allFournisseurs = [];
  (aoo.lots || []).forEach(lot => {
    if (lot.attributaire) allFournisseurs.push(lot.attributaire);
    (lot.decisions || []).forEach(decision => {
      if (decision.fournisseur) allFournisseurs.push(decision.fournisseur);
    });
  });
  (aoo.concurrents || []).forEach(concurrent => {
    if (concurrent.fournisseur) allFournisseurs.push(concurrent.fournisseur);
  });

  const match = allFournisseurs.find(fournisseur => {
    if (!fournisseur) return false;
    const idMatches = targetId && String(fournisseur.id) === targetId;
    const nameMatches = targetName && normalize(fournisseur.raison_sociale) === targetName;
    return idMatches || nameMatches;
  });

  if (!match) return '';
  return toText(match.qualite_representant, toText(match.fonction_responsable, ''));
};

const resolveExercice = (aoo, numMarche = '') => {
  const src = numMarche || aoo?.num_aoo || '';
  const match = String(src).match(/\/(\d{4})\//);
  if (match) return match[1];
  if (aoo?.date_ouverture) return String(new Date(aoo.date_ouverture).getFullYear());
  return String(new Date().getFullYear());
};

const parseNum = (value) => {
  const n = parseFloat(value);
  return Number.isFinite(n) ? n : 0;
};

const formatMoney = (value) => parseNum(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const round2 = (value) => Math.round(parseNum(value) * 100) / 100;

const buildBordereauFromLot = (lot, savedItems = []) => {
  const articles = Array.isArray(lot?.items) ? lot.items : [];
  return articles.map((item, index) => {
    const saved = savedItems.find(s => String(s.lot_item_id) === String(item.id));
    const puHt = saved?.prix_unitaire_attributaire;
    const itemTva = parseFloat(saved?.taux_tva ?? item.taux_tva ?? 20);

    const prixTtc = puHt !== '' && puHt !== null && puHt !== undefined
      ? round2(parseNum(puHt) * (1 + itemTva / 100))
      : '';

    return {
      lot_item_id: item.id,
      numero: item.numero ?? index + 1,
      designation: item.designation || '',
      unite: item.unite || '',
      quantite: item.quantite ?? '',
      prix_unitaire_ttc: prixTtc,
      taux_tva: itemTva,
    };
  });
};

const computeBordereauLine = (item) => {
  const quantite = parseNum(item.quantite);
  const puTtc = parseNum(item.prix_unitaire_ttc);
  const tauxTva = parseNum(item.taux_tva) || 20;

  const puHt = round2(puTtc / (1 + tauxTva / 100));
  const montantHt = round2(quantite * puHt);
  const montantTtc = round2(quantite * puTtc);

  return { puHt, montantHt, montantTtc };
};

const computeBordereauTotals = (items) => {
  let totalHt = 0;
  let totalTtc = 0;

  items.forEach(item => {
    const line = computeBordereauLine(item);
    totalHt += line.montantHt;
    totalTtc += line.montantTtc;
  });

  return {
    totalHt: round2(totalHt),
    tva: round2(totalTtc - totalHt),
    totalTtc: round2(totalTtc)
  };
};

const GestionMarches = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [activeTab, setActiveTab] = useState('consultation');

  const wizardStepOrder = ['consultation', 'engagement', 'liquidation', 'ordonnancement', 'cloture'];
  const wizardLabels = {
    consultation: 'Consultation',
    engagement: 'Engagement',
    liquidation: 'Liquidation',
    ordonnancement: 'Ordonnancement',
    cloture: 'Clôture'
  };
  const wizardNextLabels = {
    consultation: 'Engagement',
    engagement: 'Liquidation',
    liquidation: 'Ordonnancement',
    ordonnancement: 'Clôture'
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
  const [prefilling, setPrefilling] = useState(false);
  const [saving, setSaving] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [downloadingBordereau, setDownloadingBordereau] = useState(false);

  const [aoosList, setAoosList] = useState([]);
  const [selectedAoo, setSelectedAoo] = useState(null);

  const [formData, setFormData] = useState({
    aoo_id: '',
    lot_id: '',
    fournisseur_id: '',
    num_marche: '',
    lot: '',
    titulaire: '',
    objet_marche: '',
    qualite_gerant: '',
    exercice: String(new Date().getFullYear()),
    type_budget: 'Investissement',
    code_budget: '',
    intitule_budget: '',
    montant: '',
    taux_tva: '20',
    delai_execution: '',
    date_signature: '',
    date_approbation: '',
    date_notification_marche: '',
    os_numero: '',
    os_date_signature: '',
    os_date_effet: '',
    num_decision: '',
    date_decision: '',
    date_reunion_commission: '',
    heure_reunion_commission: '',
    lieu_reunion_commission: 'au siège de la DRCA-RSK',
    statut: 'en_creation',
    agent_suivi: '',
    date_reception_finale: '',
    commission_reception: [],
    num_engagement: '',
    reference_engagement: '',
    forme_engagement: 'Marché',
    date_engagement: '',
    article_budget: '',
    paragraphe_budget: '',
    ligne_budget: '',
    credit_budget_cp: '',
    credit_budget_ce: '',
    depenses_engagees_cp: '',
    depenses_engagees_ce: '',
    disponible_cp: '',
    disponible_ce: '',
    engagement_propose_cp: '',
    engagement_propose_ce: '',
    pieces_jointes: '',
    fournisseur_data: null
  });

  const [nouveauMembre, setNouveauMembre] = useState('');
  const [bordereauItems, setBordereauItems] = useState([]);
  const [commissionMembres, setCommissionMembres] = useState([]);
  const [selectedCommissionMembres, setSelectedCommissionMembres] = useState([]);

  const [activeFormModalDoc, setActiveFormModalDoc] = useState(null);
  const [activePreviewModalDoc, setActivePreviewModalDoc] = useState(null);

  const [workflow, setWorkflow] = useState({ progress_percent: 0, current_phase: 'consultation' });

  const loadWorkflowState = useCallback(async () => {
    if (!id || id === 'nouveau') return;
    try {
      const response = await api.get(`/marches/${id}/workflow`);
      if (response.data && response.data.data && response.data.data.workflow) {
        setWorkflow(response.data.data.workflow);
      }
    } catch (err) {
      console.error("Erreur chargement workflow", err);
    }
  }, [id]);

  useEffect(() => {
    if (workflow?.current_phase && wizardStepOrder.includes(workflow.current_phase)) {
      setActiveTab(workflow.current_phase);
    }
  }, [workflow?.current_phase]);

  const syncBordereauForLot = useCallback((lot, savedItems = []) => {
    setBordereauItems(buildBordereauFromLot(lot, savedItems));
  }, []);

  const fetchAoos = async () => {
    try {
      const response = await api.get('/aoos');
      setAoosList(response.data);
    } catch (err) {
      console.error("Erreur chargement AOOs", err);
    }
  };

  const fetchCommissionMembres = async () => {
    try {
      const response = await api.get('/commission-membres');
      setCommissionMembres(response.data);
    } catch (err) {
      console.error("Erreur chargement membres commission", err);
    }
  };

  const downloadArchive = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/marches/${id}/archive-zip`, { responseType: 'blob' });
      const blob = new Blob([response.data], { type: 'application/zip' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `marche_${id}_documents.zip`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setErrorMessage('Erreur lors du téléchargement de l\'archive : ' + (err.response?.data?.error || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleDownloadBordereau = async () => {
    if (!id || id === 'nouveau') return;
    try {
      setDownloadingBordereau(true);
      setErrorMessage('');
      const response = await api.get(`/marches/${id}/generate/bordereau`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      const link = document.createElement('a');
      link.href = url;
      const safeNumMarche = String(formData.num_marche || id).replace(/\//g, '_');
      link.setAttribute('download', `Bordereau_${safeNumMarche}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setErrorMessage("Erreur lors de la génération du Bordereau.");
    } finally {
      setDownloadingBordereau(false);
    }
  };

  const resolveLotAttributaire = useCallback((aoo, targetLot) => {
    const marches = Array.isArray(aoo.marches) ? aoo.marches : [];
    const concurrents = Array.isArray(aoo.concurrents) ? aoo.concurrents : [];

    let titulaire = '';
    let montant = targetLot.montant_attribue_ttc !== null && targetLot.montant_attribue_ttc !== undefined ? targetLot.montant_attribue_ttc : '';
    let lotLabel = toText(targetLot.num_lot, `LOT ${targetLot.id}`);
    let lotId = String(targetLot.id);
    let fournisseurId = '';
    let qualiteGerant = '';
    let objetMarche = toText(targetLot.objet_lot, toText(aoo.objet, ''));
    let delaiExecution = targetLot.delai_execution_jours || '';

    const existingMarche = marches.find(m => String(m.lot_id) === String(targetLot.id));
    if (existingMarche) {
      titulaire = existingMarche.titulaire || '';
      montant = existingMarche.montant ?? montant;
      lotLabel = toText(existingMarche.lot, lotLabel) || lotLabel;
      fournisseurId = existingMarche.fournisseur_id ? String(existingMarche.fournisseur_id) : '';
      objetMarche = existingMarche.objet_marche || objetMarche;
      qualiteGerant = existingMarche.qualite_gerant || '';
      delaiExecution = existingMarche.delai_execution || delaiExecution;
    }

    let fournisseurData = null;
    const attributaire = targetLot.attributaire;
    if (attributaire) {
      titulaire = titulaire || attributaire.raison_sociale || '';
      fournisseurId = fournisseurId || String(attributaire.id);
      qualiteGerant = qualiteGerant || toText(attributaire.qualite_representant, '') || 'Gérant';
      fournisseurData = attributaire;
    }

    const attributaireId = targetLot.attributaire_fournisseur_id || fournisseurId;
    const retenueDecision = (targetLot.decisions || []).find(
      d => (d.statut === 'Retenu' || d.statut === 'adjudicataire') && (!attributaireId || String(d.fournisseur_id) === String(attributaireId))
    ) || (targetLot.decisions || []).find(d => d.statut === 'Retenu' || d.statut === 'adjudicataire');

    if (retenueDecision) {
      montant = montant !== '' && montant !== null ? montant : (retenueDecision.montant_propose ?? '');
      fournisseurId = fournisseurId || String(retenueDecision.fournisseur_id);
      titulaire = titulaire || retenueDecision.fournisseur?.raison_sociale || '';
      qualiteGerant = qualiteGerant || toText(retenueDecision.fournisseur?.qualite_representant, '') || 'Gérant';
      fournisseurData = retenueDecision.fournisseur || fournisseurData;
    }

    if ((!titulaire || montant === '') && (aoo.lots?.length || 0) <= 1) {
      const retenu = concurrents.find(c => c.statut_analyse === 'retenu' || c.statut_analyse === 'retenu_provisoire');
      if (retenu) {
        titulaire = titulaire || retenu.fournisseur?.raison_sociale || retenu.nom_soumissionnaire || '';
        fournisseurId = fournisseurId || String(retenu.fournisseur_id || '');
        qualiteGerant = qualiteGerant || toText(retenu.fournisseur?.qualite_representant, '') || 'Gérant';
        if (montant === '') {
          montant = retenu.montant_engagement ?? '';
        }
        fournisseurData = retenu.fournisseur || fournisseurData;
      }
    }

    const fournisseurQualite = resolveFournisseurQualite(aoo, titulaire, fournisseurId);
    if (fournisseurQualite) {
      qualiteGerant = fournisseurQualite;
    }

    // Ensure we use the exact existing Marche's fournisseur data if it exists and is loaded
    if (existingMarche && existingMarche.fournisseur) {
      fournisseurData = existingMarche.fournisseur;
    }

    return {
      lot_id: lotId,
      lot: lotLabel,
      titulaire,
      montant: montant !== null && montant !== '' ? String(montant) : '',
      fournisseur_id: fournisseurId,
      qualite_gerant: qualiteGerant,
      objet_marche: objetMarche,
      taux_tva: '20',
      delai_execution: delaiExecution !== undefined ? String(delaiExecution) : '',
      fournisseur_data: fournisseurData
    };
  }, []);

  const prefillFromAoo = useCallback(async (aooId, lotIdParam = null) => {
    try {
      setPrefilling(true);
      const response = await api.get(`/aoos/${aooId}`);
      const aoo = response.data;
      setSelectedAoo(aoo);

      const lots = Array.isArray(aoo.lots) ? aoo.lots : [];
      let targetLot = lotIdParam
        ? lots.find(l => String(l.id) === String(lotIdParam))
        : null;

      if (!targetLot) {
        targetLot = lots.find(l => l.attributaire_fournisseur_id)
          || (lots.length === 1 ? lots[0] : null);
      }

      let patch = {
        aoo_id: String(aooId),
        lot_id: '',
        fournisseur_id: '',
        lot: '',
        titulaire: '',
        montant: '',
        taux_tva: '20',
        delai_execution: '',
        qualite_gerant: '',
        objet_marche: '',
        fournisseur_data: null,
      };

      if (targetLot) {
        const lotPatch = resolveLotAttributaire(aoo, targetLot);
        patch = { ...patch, ...lotPatch };
        if (aoo.num_aoo && lotPatch.lot) {
          patch.num_marche = buildDefaultNumMarche(aoo.num_aoo, lotPatch.lot);
        }
        patch.exercice = resolveExercice(aoo, patch.num_marche);
        syncBordereauForLot(targetLot);
      } else {
        setBordereauItems([]);
      }

      setFormData(prev => ({
        ...prev,
        ...patch,
        num_marche: patch.num_marche || prev.num_marche,
        exercice: patch.exercice || prev.exercice,
        type_budget: prev.type_budget || 'Investissement',
      }));
    } catch (err) {
      setErrorMessage("Impossible de charger les données de l'AOO pour pré-remplir le marché.");
    } finally {
      setPrefilling(false);
    }
  }, [resolveLotAttributaire, syncBordereauForLot]);

  useEffect(() => {
    fetchAoos();
    fetchCommissionMembres();
    if (id && id !== 'nouveau') {
      fetchMarche();
      loadWorkflowState();
    }
  }, [id, loadWorkflowState]);

  useEffect(() => {
    if (id !== 'nouveau') return;
    const aooId = searchParams.get('aoo_id');
    if (!aooId) return;
    prefillFromAoo(aooId, searchParams.get('lot_id'));
  }, [id, searchParams, prefillFromAoo]);

  const fetchMarche = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/marches/${id}`);
      const data = response.data;
      const sanitizedData = {};
      Object.keys(data).forEach(key => {
        sanitizedData[key] = data[key] !== null ? data[key] : '';
      });
      sanitizedData.commission_reception = Array.isArray(sanitizedData.commission_reception) ? sanitizedData.commission_reception : [];
      sanitizedData.fournisseur_data = data.fournisseur || null;
      setFormData(sanitizedData);

      if (data.aoo_id) {
        const aooResponse = await api.get(`/aoos/${data.aoo_id}`);
        setSelectedAoo(aooResponse.data);
        const lot = (aooResponse.data.lots || []).find(l => String(l.id) === String(data.lot_id));
        const savedItems = (data.bordereau_items || []).map(row => ({
          lot_item_id: row.lot_item_id,
          prix_unitaire_attributaire: row.prix_unitaire_attributaire,
        }));
        if (lot) {
          syncBordereauForLot(lot, savedItems);
        }
      }
    } catch (err) {
      setErrorMessage('Erreur lors du chargement du Marché.');
    } finally {
      setLoading(false);
    }
  };

  const handleAooChange = async (e) => {
    const aooId = e.target.value;
    setFormData(prev => ({
      ...prev,
      aoo_id: aooId,
      lot_id: '',
      lot: '',
      titulaire: '',
      montant: '',
      taux_tva: '20',
      delai_execution: '',
      fournisseur_id: '',
      qualite_gerant: '',
      objet_marche: '',
      fournisseur_data: null,
    }));
    setBordereauItems([]);

    if (!aooId) {
      setSelectedAoo(null);
      return;
    }

    try {
      setPrefilling(true);
      const response = await api.get(`/aoos/${aooId}`);
      const aoo = response.data;
      setSelectedAoo(aoo);

      const lots = Array.isArray(aoo.lots) ? aoo.lots : [];
      if (lots.length === 1) {
        const patch = resolveLotAttributaire(aoo, lots[0]);
        setFormData(prev => ({
          ...prev,
          aoo_id: aooId,
          ...patch,
          num_marche: prev.num_marche || buildDefaultNumMarche(aoo.num_aoo, patch.lot),
        }));
        syncBordereauForLot(lots[0]);
      }
    } catch (err) {
      setErrorMessage("Impossible de charger les lots de l'AOO sélectionné.");
      setSelectedAoo(null);
    } finally {
      setPrefilling(false);
    }
  };

  const handleLotChange = (e) => {
    const lotId = e.target.value;
    if (!selectedAoo || !lotId) {
      setFormData(prev => ({
        ...prev,
        lot_id: '',
        lot: '',
        titulaire: '',
        montant: '',
        taux_tva: '20',
        delai_execution: '',
        fournisseur_id: '',
        qualite_gerant: '',
        objet_marche: '',
        fournisseur_data: null,
      }));
      setBordereauItems([]);
      return;
    }

    const lot = (selectedAoo.lots || []).find(l => String(l.id) === String(lotId));
    if (!lot) return;

    const patch = resolveLotAttributaire(selectedAoo, lot);
    setFormData(prev => ({
      ...prev,
      aoo_id: String(selectedAoo.id),
      ...patch,
      num_marche: prev.num_marche || buildDefaultNumMarche(selectedAoo.num_aoo, patch.lot),
    }));
    syncBordereauForLot(lot);
  };

  const handleBordereauItemChange = (index, field, value) => {
    setBordereauItems(prev => prev.map((item, i) => (
      i === index ? { ...item, [field]: value } : item
    )));
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => {
      const next = { ...prev, [name]: value };
      if (name === 'num_marche') {
        const match = String(value).match(/\/(\d{4})\//);
        if (match) next.exercice = match[1];
      }
      if (name === 'credit_budget_cp' || name === 'depenses_engagees_cp') {
        const credit = parseNum(name === 'credit_budget_cp' ? value : next.credit_budget_cp);
        const expenses = parseNum(name === 'depenses_engagees_cp' ? value : next.depenses_engagees_cp);
        next.disponible_cp = (credit - expenses).toFixed(2);
      }
      if (name === 'credit_budget_ce' || name === 'depenses_engagees_ce') {
        const credit = parseNum(name === 'credit_budget_ce' ? value : next.credit_budget_ce);
        const expenses = parseNum(name === 'depenses_engagees_ce' ? value : next.depenses_engagees_ce);
        next.disponible_ce = (credit - expenses).toFixed(2);
      }
      if (name === 'montant') {
        next.engagement_propose_cp = (parseNum(value) * 1.01).toFixed(2);
      }
      return next;
    });
  };

  const handleAddMembre = () => {
    if (nouveauMembre.trim() === '') return;
    setFormData(prev => ({
      ...prev,
      commission_reception: [...prev.commission_reception, nouveauMembre.trim()]
    }));
    setNouveauMembre('');
  };

  const handleRemoveMembre = (index) => {
    setFormData(prev => ({
      ...prev,
      commission_reception: prev.commission_reception.filter((_, i) => i !== index)
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');

    if (!formData.aoo_id) {
      setErrorMessage("Veuillez sélectionner un Appel d'Offres (AOO) d'origine.");
      setSaving(false);
      return;
    }

    if (!formData.lot_id) {
      setErrorMessage('Veuillez sélectionner un lot concerné.');
      setSaving(false);
      return;
    }

    if (formData.montant === '' || formData.montant === null) {
      setErrorMessage('Le montant du marché est requis. Sélectionnez un lot avec attributaire.');
      setSaving(false);
      return;
    }

    const selectedLotForBordereau = (selectedAoo?.lots || []).find(l => String(l.id) === String(formData.lot_id));
    const lotArticles = selectedLotForBordereau?.items || [];
    if (lotArticles.length > 0) {
      if (bordereauItems.length !== lotArticles.length) {
        setErrorMessage('Le bordereau doit contenir une ligne pour chaque article du lot AOO.');
        setSaving(false);
        return;
      }
      const missingPrice = bordereauItems.some(item => item.prix_unitaire_ttc === '' || item.prix_unitaire_ttc === null);
      if (missingPrice) {
        setErrorMessage('Veuillez saisir le prix unitaire TTC pour chaque article du bordereau.');
        setSaving(false);
        return;
      }
      const { totalTtc } = computeBordereauTotals(bordereauItems);
      const montantAttribution = parseNum(formData.montant);
      if (Math.abs(totalTtc - montantAttribution) > 0.02) {
        setErrorMessage(`Le Total TTC du bordereau (${formatMoney(totalTtc)} dh) doit être égal au montant d'attribution (${formatMoney(montantAttribution)} dh).`);
        setSaving(false);
        return;
      }
    }

    try {
      const lots = selectedAoo?.lots || [];
      const selectedLot = lots.find(l => String(l.id) === String(formData.lot_id));
      const lotLabel = toText(formData.lot, toText(selectedLot?.num_lot, `LOT ${formData.lot_id || ''}`));

      const payload = {
        aoo_id: Number(formData.aoo_id),
        lot_id: formData.lot_id ? Number(formData.lot_id) : null,
        fournisseur_id: formData.fournisseur_id ? Number(formData.fournisseur_id) : null,
        num_marche: toText(formData.num_marche, '').includes('[object Object]')
          ? buildDefaultNumMarche(selectedAoo?.num_aoo, lotLabel)
          : toText(formData.num_marche, buildDefaultNumMarche(selectedAoo?.num_aoo, lotLabel)),
        lot: lotLabel,
        titulaire: toText(formData.titulaire, ''),
        objet_marche: toText(formData.objet_marche, '') || null,
        qualite_gerant: toText(formData.qualite_gerant, '') || null,
        exercice: toText(formData.exercice, '') || null,
        type_budget: toText(formData.type_budget, 'Investissement') || 'Investissement',
        code_budget: toText(formData.code_budget, '') || null,
        intitule_budget: toText(formData.intitule_budget, '') || null,
        montant: formData.montant === '' ? null : Number(formData.montant),
        taux_tva: formData.taux_tva ? Number(formData.taux_tva) : 20,
        delai_execution: formData.delai_execution ? Number(formData.delai_execution) : null,
        date_signature: formData.date_signature || null,
        date_approbation: formData.date_approbation || null,
        date_notification_marche: formData.date_notification_marche || null,
        os_numero: formData.os_numero || null,
        os_date_signature: formData.os_date_signature || null,
        os_date_effet: formData.os_date_effet || null,
        num_decision: formData.num_decision || null,
        date_decision: formData.date_decision || null,
        date_reunion_commission: formData.date_reunion_commission || null,
        heure_reunion_commission: formData.heure_reunion_commission || null,
        lieu_reunion_commission: formData.lieu_reunion_commission || null,
        statut: formData.statut || 'en_creation',
        agent_suivi: formData.agent_suivi || null,
        date_reception_finale: formData.date_reception_finale || null,
        commission_reception: Array.isArray(formData.commission_reception) ? formData.commission_reception : [],
        num_engagement: formData.num_engagement || null,
        reference_engagement: formData.reference_engagement || null,
        forme_engagement: formData.forme_engagement || 'Marché',
        date_engagement: formData.date_engagement || null,
        article_budget: formData.article_budget || null,
        paragraphe_budget: formData.paragraphe_budget || null,
        ligne_budget: formData.ligne_budget || null,
        credit_budget_cp: formData.credit_budget_cp === '' ? null : Number(formData.credit_budget_cp),
        credit_budget_ce: formData.credit_budget_ce === '' ? null : Number(formData.credit_budget_ce),
        depenses_engagees_cp: formData.depenses_engagees_cp === '' ? null : Number(formData.depenses_engagees_cp),
        depenses_engagees_ce: formData.depenses_engagees_ce === '' ? null : Number(formData.depenses_engagees_ce),
        disponible_cp: formData.disponible_cp === '' ? null : Number(formData.disponible_cp),
        disponible_ce: formData.disponible_ce === '' ? null : Number(formData.disponible_ce),
        engagement_propose_cp: formData.engagement_propose_cp === '' ? null : Number(formData.engagement_propose_cp),
        engagement_propose_ce: formData.engagement_propose_ce === '' ? null : Number(formData.engagement_propose_ce),
        pieces_jointes: formData.pieces_jointes || null,
        bordereau_items: bordereauItems.length > 0
          ? bordereauItems.map(item => {
            const { puHt } = computeBordereauLine(item);
            return {
              lot_item_id: item.lot_item_id,
              prix_unitaire_ht: puHt,
              taux_tva: parseNum(item.taux_tva) || 20,
            };
          })
          : [],
      };

      if (id && id !== 'nouveau') {
        payload.id = Number(id);
      }

      const response = await api.post('/marches', payload);
      setSuccessMessage('Marché enregistré avec succès.');

      if (!id || id === 'nouveau') {
        setTimeout(() => {
          navigate(`/marches/${response.data.data.id}`);
        }, 1500);
      }
    } catch (err) {
      const validationErrors = err.response?.data?.errors;
      if (validationErrors) {
        setErrorMessage(Object.values(validationErrors).flat().join(' '));
      } else {
        setErrorMessage(err.response?.data?.message || "Erreur lors de l'enregistrement. Veuillez vérifier les champs.");
      }
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  const downloadDocument = async (documentType) => {
    if (!id || id === 'nouveau') return;

    if (documentType === 'rapport-engagement') {
      try {
        const response = await api.get(`/marches/${id}/generate/rapport-engagement`, { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `Rapport_Engagement_${formData.num_marche}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        setErrorMessage("Enregistrez d'abord la fiche d'engagement avant de générer le rapport.");
      }
    } else if (documentType === 'acte-engagement' || documentType === 'decision-approbation' || documentType === 'os-commencement') {
      try {
        let endpoint = '';
        let filename = '';
        if (documentType === 'acte-engagement') {
          endpoint = `/marches/${id}/generate/acte`;
          filename = `Acte_Engagement_${formData.num_marche}.pdf`;
        } else if (documentType === 'decision-approbation') {
          endpoint = `/marches/${id}/generate/notification`;
          filename = `Notification_Approbation_${formData.num_marche}.pdf`;
        } else if (documentType === 'os-commencement') {
          endpoint = `/marches/${id}/generate/os`;
          filename = `OS_Commencement_${formData.num_marche}.pdf`;
        }

        const response = await api.get(endpoint, {
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        let msg = 'Erreur lors de la génération du document. Vérifiez que tous les champs requis sont remplis et sauvegardés.';
        if (err.response && err.response.data && err.response.data.message) {
          msg = err.response.data.message;
        } else if (err.response && err.response.data instanceof Blob) {
          const text = await err.response.data.text();
          try {
            const json = JSON.parse(text);
            if (json.message) msg = json.message;
          } catch (e) { }
        }
        setErrorMessage(msg);
      }
    } else if (documentType === 'decision-nomination') {
      try {
        const payload = {
          num_decision: formData.num_decision,
          date_decision: formData.date_decision,
          date_reunion_commission: formData.date_reunion_commission,
          heure_reunion_commission: formData.heure_reunion_commission,
          lieu_reunion_commission: formData.lieu_reunion_commission,
          membres_commission_ids: selectedCommissionMembres,
        };
        const response = await api.post(`/marches/${id}/generer-decision`, payload, {
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Decision_Nomination_${formData.num_marche}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        const errorMessage = err.response?.data?.error || 'Erreur lors de la génération de la Décision de Nomination. Vérifiez que tous les champs requis sont remplis.';
        setErrorMessage(errorMessage);
      }
    } else {
      try {
        const response = await api.get(`/marches/${id}/documents/${documentType}`, {
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
        link.download = `${documentType}_${formData.num_marche}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Erreur téléchargement document marché:', error);
        setErrorMessage(`Impossible de télécharger le document: ${error.message}`);
      }
    }
  };

  const availableLots = selectedAoo?.lots || [];
  const bordereauTotals = computeBordereauTotals(bordereauItems);
  const montantAttribution = round2(formData.montant);
  const hasBordereauPrices = bordereauItems.some(i => i.prix_unitaire_ttc !== '' && i.prix_unitaire_ttc !== null);
  const bordereauMatch = !hasBordereauPrices || Math.abs(bordereauTotals.totalTtc - montantAttribution) <= 0.02;

  if (loading || prefilling) {
    return (
      <div className="flex flex-col items-center justify-center h-screen bg-slate-50 text-blue-600">
        <Loader2 className="animate-spin mb-4" size={48} />
        <h2 className="text-xl font-bold">{prefilling ? 'Chargement des données AOO...' : 'Chargement du Marché...'}</h2>
      </div>
    );
  }

  const tabClass = (tabName) => `whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === tabName ? 'bg-[#1e3a8a] text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`;
  const inputClass = 'w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-[#1e3a8a] focus:ring-2 focus:ring-[#1e3a8a]/10 transition-all';

  const parentAoo = selectedAoo;

  const markPhase = async (phaseName, validee) => {
    try {
      setSaving(true);
      const action = validee ? 'valider' : 'cours';
      await api.post(`/marches/${id}/phases/${phaseName}/${action}`);
      await fetchMarche();
      await loadWorkflowState();
      setSuccessMessage(`Phase ${phaseName} ${validee ? 'validée' : 'mise à jour'} avec succès.`);
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      setErrorMessage(err.response?.data?.message || `Erreur lors de la mise à jour de la phase ${phaseName}.`);
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const saveMarche = async (updatedData = null) => {
    try {
      const payload = updatedData || formData;
      await api.put(`/marches/${id}`, payload);
      setSuccessMessage('Marché mis à jour avec succès');
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error(err);
    }
  };

  const renderActionBanner = (buttonText, documents) => (
    <div className="mt-8 p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col xl:flex-row items-center justify-between gap-6 shadow-sm">
      <button
        type="submit"
        disabled={saving}
        className="w-full xl:w-auto px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
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

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] pb-24">
      <header className="bg-white/95 border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur">
        <div className="w-full px-4 sm:px-6 lg:px-8">
          <div className="py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex min-w-0 items-start sm:items-center gap-3 sm:gap-4">
              <button onClick={() => navigate('/dashboard')} className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all">
                <ArrowLeft size={20} />
              </button>
              <div>
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-2 sm:gap-3">
                  <Briefcase className="text-[#1e3a8a]" />
                  {id && id !== 'nouveau' ? `Marché : ${formData.num_marche || 'En cours'}` : 'Nouveau Marché'}
                </h1>
                <p className="text-slate-500 text-sm mt-1">Workflow institutionnel du cycle d’un marché public marocain</p>
              </div>
            </div>

            <div className="flex w-full sm:w-auto flex-wrap items-center gap-2 sm:justify-end">
              {id && id !== 'nouveau' && (
                <button
                  type="button"
                  onClick={handleDownloadBordereau}
                  disabled={downloadingBordereau}
                  className="px-4 py-2 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 flex items-center gap-2 shadow-md transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                >
                  {downloadingBordereau ? <Loader2 size={16} className="animate-spin" /> : <Download size={16} />}
                  {downloadingBordereau ? 'Génération...' : 'Générer Bordereau PDF'}
                </button>
              )}
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

          <div className="mb-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
              <div>
                <p className="text-sm font-semibold text-slate-600">Avancement du marché</p>
                <p className="text-xl font-bold text-slate-800">{workflow.progress_percent}%</p>
              </div>
              <div className="text-right">
                <p className="text-sm text-slate-500">Phase active</p>
                <p className="text-sm font-semibold text-[#1e3a8a]">{wizardLabels[workflow.current_phase] || 'Consultation'}</p>
              </div>
            </div>
            <div className="h-2 rounded-full bg-slate-200 overflow-hidden">
              <div className="h-full rounded-full bg-gradient-to-r from-[#1e3a8a] via-[#0ea5e9] to-[#34d399] transition-all" style={{ width: `${workflow.progress_percent}%` }} />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 pb-4">
            <button onClick={() => setActiveTab('consultation')} className={tabClass('consultation')}>
              <FileSignature size={18} /> 1. Consultation
            </button>
            <button onClick={() => setActiveTab('engagement')} className={tabClass('engagement')}>
              <HandCoins size={18} /> 2. Engagement
            </button>
            <button onClick={() => setActiveTab('liquidation')} className={tabClass('liquidation')}>
              <ReceiptText size={18} /> 3. Liquidation
            </button>
            <button onClick={() => setActiveTab('ordonnancement')} className={tabClass('ordonnancement')}>
              <Banknote size={18} /> 4. Ordonnancement
            </button>
          </div>
        </div>
      </header>

      <main className="w-full px-3 sm:px-6 lg:px-8 mt-8 sm:mt-16">
        <form onSubmit={handleSubmit} className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-10 shadow-sm border border-slate-200">

          <div className={activeTab === 'consultation' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Préparation et consultation</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
              <div className="col-span-1 md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">AOO Parent (Source) *</label>
                <select
                  name="aoo_id"
                  value={formData.aoo_id}
                  onChange={handleAooChange}
                  required
                  className={`${inputClass} disabled:opacity-60`}
                >
                  <option value="">Sélectionnez l'AOO d'origine...</option>
                  {aoosList.map(aoo => (
                    <option key={aoo.id} value={aoo.id}>{aoo.num_aoo} - {aoo.objet?.substring(0, 50)}...</option>
                  ))}
                </select>
                {searchParams.get('aoo_id') && formData.aoo_id && (
                  <p className="text-xs text-emerald-600 mt-1">Pré-rempli depuis l'attribution de l'AOO (Phase 4).</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro du Marché *</label>
                <input type="text" name="num_marche" value={formData.num_marche} onChange={handleChange} required placeholder="Ex: 12/2026/ONCA" className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Lot Concerné *</label>
                <select
                  name="lot_id"
                  value={formData.lot_id}
                  onChange={handleLotChange}
                  required
                  disabled={!formData.aoo_id || availableLots.length === 0}
                  className={`${inputClass} disabled:opacity-60`}
                >
                  <option value="">
                    {!formData.aoo_id
                      ? "Sélectionnez d'abord un AOO..."
                      : availableLots.length === 0
                        ? 'Aucun lot disponible pour cet AOO'
                        : 'Sélectionnez le lot concerné...'}
                  </option>
                  {availableLots.map(lot => (
                    <option key={lot.id} value={lot.id}>
                      {getLotDropdownLabel(lot, selectedAoo?.objet)}
                    </option>
                  ))}
                </select>
              </div>

              <div className="col-span-1 md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Objet du Marché</label>
                <textarea
                  name="objet_marche"
                  value={formData.objet_marche}
                  onChange={handleChange}
                  rows={4}
                  placeholder="Objet détaillé du marché (peut différer de l'objet du lot)..."
                  className={`${inputClass} resize-none`}
                />
              </div>

              <div className="col-span-1 md:col-span-2 mt-2 mb-2 border-t border-slate-100 pt-6">
                <h3 className="text-sm font-extrabold text-slate-700 mb-4">Informations budgétaires (Page de Garde)</h3>
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Exercice</label>
                <input
                  type="text"
                  name="exercice"
                  value={formData.exercice}
                  onChange={handleChange}
                  placeholder="Ex: 2026"
                  className={inputClass}
                />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Type de budget</label>
                <input
                  type="text"
                  name="type_budget"
                  value={formData.type_budget}
                  onChange={handleChange}
                  placeholder="Investissement"
                  className={inputClass}
                />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Code budget</label>
                <input
                  type="text"
                  name="code_budget"
                  value={formData.code_budget}
                  onChange={handleChange}
                  placeholder="Ex: 415 20 12"
                  className={inputClass}
                />
              </div>
              <div className="col-span-1 md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Intitulé budget</label>
                <input
                  type="text"
                  name="intitule_budget"
                  value={formData.intitule_budget}
                  onChange={handleChange}
                  placeholder="Ex: Frais d'organisation et de participation aux journées..."
                  className={inputClass}
                />
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Titulaire (Entreprise retenue) *</label>
                <input type="text" name="titulaire" value={formData.titulaire} onChange={handleChange} required readOnly className={`${inputClass} bg-slate-100 text-slate-700 cursor-not-allowed`} />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Qualité du Gérant / Responsable</label>
                <input type="text" name="qualite_gerant" value={formData.qualite_gerant} onChange={handleChange} readOnly placeholder="Rempli automatiquement depuis le fournisseur" className={`${inputClass} bg-slate-100 text-slate-700 cursor-not-allowed`} />
              </div>
            </div>

            {/* Titulaire Info Block */}
            {formData.fournisseur_data && (
              <div className="mt-6 mb-6 p-5 bg-indigo-50 border border-indigo-100 rounded-2xl">
                <h4 className="text-sm font-bold text-indigo-800 mb-3 uppercase tracking-wider flex items-center gap-2">
                  <Briefcase size={16} /> Informations Administratives & Bancaires du Titulaire
                </h4>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                  <div>
                    <span className="block text-indigo-400 text-xs font-semibold mb-1">ICE</span>
                    <span className="font-bold text-indigo-950">{formData.fournisseur_data.ice || '-'}</span>
                  </div>
                  <div>
                    <span className="block text-indigo-400 text-xs font-semibold mb-1">Registre de Commerce</span>
                    <span className="font-bold text-indigo-950">{formData.fournisseur_data.rc || '-'} {formData.fournisseur_data.ville_rc ? `(${formData.fournisseur_data.ville_rc})` : ''}</span>
                  </div>
                  <div>
                    <span className="block text-indigo-400 text-xs font-semibold mb-1">Identifiant Fiscal (IF)</span>
                    <span className="font-bold text-indigo-950">{formData.fournisseur_data.if || formData.fournisseur_data.identifiant_fiscal || '-'}</span>
                  </div>
                  <div>
                    <span className="block text-indigo-400 text-xs font-semibold mb-1">Banque</span>
                    <span className="font-bold text-indigo-950">{formData.fournisseur_data.banque || '-'}</span>
                  </div>
                  <div className="col-span-2 md:col-span-4">
                    <span className="block text-indigo-400 text-xs font-semibold mb-1">RIB (Relevé d'Identité Bancaire)</span>
                    <span className="font-mono text-base tracking-widest font-bold text-indigo-950">{formData.fournisseur_data.rib || 'Non renseigné'}</span>
                  </div>
                </div>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Montant du Marché (dh) *</label>
                <input type="number" step="0.01" name="montant" value={formData.montant} onChange={handleChange} required readOnly className={`${inputClass} bg-slate-100 text-slate-700 cursor-not-allowed`} />
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Délai d'exécution (jours) *</label>
                <input type="number" name="delai_execution" value={formData.delai_execution} onChange={handleChange} required className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de signature</label>
                <input type="date" name="date_signature" value={formData.date_signature} onChange={handleChange} className={inputClass} />
              </div>
            </div>

            {bordereauItems.length > 0 && (
              <div className="mt-10 border-t border-slate-100 pt-8">
                <div className="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                  <div>
                    <h3 className="text-lg font-extrabold text-slate-800">Saisie du Bordereau des Prix (Titulaire)</h3>
                    <p className="text-sm text-slate-500 mt-1">Saisissez les prix unitaires TTC proposés par l'entreprise. Le HT et la TVA (20 %) sont calculés automatiquement.</p>
                  </div>
                  {hasBordereauPrices && !bordereauMatch && formData.montant !== '' && (
                    <div className="px-4 py-2 bg-amber-50 text-amber-700 rounded-xl border border-amber-200 text-sm font-medium flex items-center gap-2">
                      <AlertCircle size={16} /> Total TTC ({formatMoney(bordereauTotals.totalTtc)} dh) ≠ montant d'attribution ({formatMoney(montantAttribution)} dh)
                    </div>
                  )}
                  {hasBordereauPrices && bordereauMatch && (
                    <div className="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 text-sm font-medium flex items-center gap-2">
                      <CheckCircle size={16} /> Bordereau cohérent avec le montant du lot
                    </div>
                  )}
                </div>

                <div className="overflow-x-auto rounded-2xl border border-slate-200">
                  <table className="min-w-full text-sm">
                    <thead className="bg-slate-100 text-slate-700">
                      <tr>
                        <th className="px-3 py-3 text-left font-bold">N°</th>
                        <th className="px-3 py-3 text-left font-bold min-w-[220px]">Désignation</th>
                        <th className="px-3 py-3 text-center font-bold">Unité</th>
                        <th className="px-3 py-3 text-right font-bold">Quantité</th>
                        <th className="px-3 py-3 text-right font-bold min-w-[170px]">Prix Unitaire TTC (Entreprise)</th>
                        <th className="px-3 py-3 text-right font-bold w-24">Taux TVA (%)</th>
                        <th className="px-3 py-3 text-right font-bold">P.U HT (calculé)</th>
                        <th className="px-3 py-3 text-right font-bold">Montant HT</th>
                      </tr>
                    </thead>
                    <tbody>
                      {bordereauItems.map((item, index) => {
                        const line = computeBordereauLine(item);
                        return (
                          <tr key={item.lot_item_id || index} className="border-t border-slate-100">
                            <td className="px-3 py-2">{item.numero}</td>
                            <td className="px-3 py-2">{item.designation}</td>
                            <td className="px-3 py-2 text-center">{item.unite || '—'}</td>
                            <td className="px-3 py-2 text-right font-mono">{formatMoney(item.quantite)}</td>
                            <td className="px-3 py-2">
                              <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={item.prix_unitaire_ttc}
                                onChange={(e) => handleBordereauItemChange(index, 'prix_unitaire_ttc', e.target.value)}
                                className="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white text-right font-mono"
                                placeholder="0,00"
                              />
                            </td>
                            <td className="px-3 py-2">
                              <input
                                type="number"
                                min="0"
                                max="100"
                                step="0.1"
                                value={item.taux_tva ?? 20}
                                onChange={(e) => handleBordereauItemChange(index, 'taux_tva', e.target.value)}
                                className="w-full px-2 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white text-center font-mono"
                              />
                            </td>
                            <td className="px-3 py-2 text-right font-mono text-slate-600">{formatMoney(line.puHt)}</td>
                            <td className="px-3 py-2 text-right font-mono font-bold">{formatMoney(line.montantHt)}</td>
                          </tr>
                        );
                      })}
                    </tbody>
                    <tfoot className="bg-slate-50 border-t border-slate-200">
                      <tr>
                        <td colSpan="6" className="px-3 py-3 text-right font-bold text-slate-700">Total HT</td>
                        <td colSpan="2" className="px-3 py-3 text-right font-mono font-bold">{formatMoney(bordereauTotals.totalHt)} dh</td>
                      </tr>
                      <tr>
                        <td colSpan="6" className="px-3 py-3 text-right font-bold text-slate-700">TVA Totale</td>
                        <td colSpan="2" className="px-3 py-3 text-right font-mono font-bold">{formatMoney(bordereauTotals.tva)} dh</td>
                      </tr>
                      <tr>
                        <td colSpan="6" className="px-3 py-3 text-right font-bold text-slate-800">Total TTC</td>
                        <td colSpan="2" className={`px-3 py-3 text-right font-mono font-extrabold ${bordereauMatch ? 'text-emerald-700' : 'text-red-600'}`}>
                          {formatMoney(bordereauTotals.totalTtc)} dh
                        </td>
                      </tr>
                      <tr>
                        <td colSpan="7" className="px-3 py-3 text-sm text-slate-500">
                          Montant d'attribution du lot : <strong>{formatMoney(montantAttribution)} dh TTC</strong>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            )}

            {formData.lot_id && bordereauItems.length === 0 && (
              <div className="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-sm text-amber-800">
                Aucun article trouvé dans le détail estimatif de ce lot. Ajoutez les lignes dans la Phase 1 (Préparation) de l'AOO parent avant de saisir le bordereau.
              </div>
            )}

            {renderActionBanner("Enregistrer le Marché", [
              { key: 'page-garde', label: 'Imprimer Page de Garde' },
              { key: 'premiere-derniere-page', label: 'Générer 1er & Dernier Page' },
              { key: 'bordereau-prix', label: 'Imprimer Bordereau des Prix (Brdr C.E)' },
            ])}
            <div className="mt-8 flex flex-wrap justify-between gap-3">
              <button type="button" onClick={() => markPhase('consultation', true)} className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl shadow hover:bg-emerald-700 transition-all">
                <CheckCircle size={18} /> Valider la phase Consultation
              </button>
              {nextStep && (
                <button type="button" onClick={handleNextStep} className="inline-flex items-center gap-2 px-6 py-3 bg-[#1e3a8a] text-white rounded-2xl shadow-xl hover:bg-[#16316f] transition-all">
                  <span>Suivant : {wizardLabels[nextStep]}</span>
                  <ArrowRight size={18} />
                </button>
              )}
            </div>
          </div>

          <div className={activeTab === 'engagement' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4 flex flex-wrap items-center justify-between gap-4">
              <div>
                <h2 className="text-xl font-extrabold text-slate-800 flex items-center gap-2">
                  <FileSignature className="text-blue-600" size={24} />
                  Phase 2 : Engagement & Notification du Marché
                </h2>
                <p className="text-sm text-slate-500 mt-1">
                  Gestion et génération automatique des 4 documents contractuels de l'engagement (chaînés depuis la Consultation & l'Attribution).
                </p>
              </div>

              <div className="flex items-center gap-2 bg-blue-50 border border-blue-200 px-4 py-2 rounded-2xl text-xs font-bold text-blue-900 shadow-sm">
                <Package size={16} />
                <span>Titulaire : <strong>{formData.titulaire || 'En attente d\'attribution'}</strong></span>
                <span className="mx-1">•</span>
                <span>TTC : <strong>{formatMoney(formData.montant)} dh</strong></span>
              </div>
            </div>

            <div className="mb-8 rounded-3xl border border-blue-200 bg-blue-50/40 p-6 shadow-sm">
              <div className="mb-5 flex items-center justify-between gap-3 border-b border-blue-100 pb-3">
                <div>
                  <h3 className="text-lg font-extrabold text-slate-900">Fiche d'Engagement Budgétaire</h3>
                  <p className="mt-1 text-xs text-slate-500">Les données enregistrées alimentent automatiquement le registre des engagements.</p>
                </div>
                <button
                  type="button"
                  onClick={() => downloadDocument('rapport-engagement')}
                  className="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800"
                >
                  <Download size={15} /> Rapport d'engagement
                </button>
              </div>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                {[
                  ['num_engagement', "N° Fiche d'Engagement", 'text'],
                  ['date_engagement', 'Date de visa / engagement', 'date'],
                  ['reference_engagement', 'Référence du marché', 'text'],
                  ['article_budget', 'Article (ART)', 'text'],
                  ['paragraphe_budget', 'Paragraphe (PAR)', 'text'],
                  ['ligne_budget', 'Ligne (LIG)', 'text'],
                  ['credit_budget_cp', 'Crédit ouvert CP', 'number'],
                  ['credit_budget_ce', 'Crédit ouvert CE', 'number'],
                  ['depenses_engagees_cp', 'Dépenses antérieures CP', 'number'],
                  ['depenses_engagees_ce', 'Dépenses antérieures CE', 'number'],
                  ['disponible_cp', 'Disponible CP', 'number'],
                  ['disponible_ce', 'Disponible CE', 'number'],
                  ['engagement_propose_cp', 'Montant à engager neuf', 'number'],
                  ['engagement_propose_ce', 'Montant à engager CE', 'number'],
                ].map(([name, label, type]) => (
                  <label key={name} className="text-xs font-bold text-slate-700">
                    {label}
                    <input
                      name={name}
                      type={type}
                      value={formData[name] ?? ''}
                      onChange={handleChange}
                      readOnly={name.startsWith('disponible_') || name === 'engagement_propose_cp'}
                      className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 read-only:bg-slate-100"
                    />
                  </label>
                ))}
              </div>
            </div>

            {/* GRID DES 4 CARTES OFFICIELLES DE L'ENGAGEMENT */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

              {/* CARTE 1 : ACTE D'ENGAGEMENT */}
              <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                  <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                        <FileText size={20} />
                      </div>
                      <div>
                        <h3 className="font-extrabold text-slate-900 text-base">Acte d'Engagement</h3>
                        <p className="text-[11px] text-slate-500 font-mono">Pièce N° 2 Officielle</p>
                      </div>
                    </div>
                    <span className="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-extrabold text-[11px]">
                      Généré
                    </span>
                  </div>

                  <div className="mt-4 space-y-2 text-xs">
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">N° AOO / Consultation :</span>
                      <span className="font-bold text-slate-800 font-mono">{parentAoo?.num_aoo || '06/2026/DRCA-RSK'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Titulaire engagé :</span>
                      <span className="font-bold text-slate-900">{formData.titulaire || '—'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Montant Total TTC :</span>
                      <span className="font-extrabold text-blue-900 font-mono">{formatMoney(formData.montant)} dh</span>
                    </div>
                  </div>
                </div>

                <div className="pt-2 flex flex-wrap gap-1.5">
                  <button
                    type="button"
                    onClick={() => setActiveFormModalDoc('acte-engagement')}
                    className="py-2 px-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[11px] rounded-xl border border-blue-200 transition-all flex items-center gap-1"
                  >
                    <Edit3 size={13} /> Formulaire
                  </button>
                  <button
                    type="button"
                    onClick={() => setActivePreviewModalDoc('acte-engagement')}
                    className="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] rounded-xl transition-all flex items-center gap-1"
                  >
                    <Eye size={13} /> Prévisualiser
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('acte-engagement')}
                    className="flex-1 py-2 px-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-500/30 hover:shadow-lg hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-1.5 group"
                  >
                    <Download size={14} className="group-hover:animate-bounce" /> Télécharger
                  </button>
                </div>
              </div>

              {/* CARTE 2 : MARCHÉ DÉFINITIF */}
              <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                  <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                        <Briefcase size={20} />
                      </div>
                      <div>
                        <h3 className="font-extrabold text-slate-900 text-base">Marché & CPS</h3>
                        <p className="text-[11px] text-slate-500 font-mono">Marché Définitif Rédigé</p>
                      </div>
                    </div>
                    <span className="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-extrabold text-[11px]">
                      {formData.num_marche ? 'Validé' : 'En préparation'}
                    </span>
                  </div>

                  <div className="mt-4 space-y-2 text-xs">
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">N° Marché Officiel :</span>
                      <span className="font-bold text-indigo-900 font-mono">{formData.num_marche || 'Non attribué'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Référence CPS :</span>
                      <span className="font-bold text-slate-800 font-mono">CPS-{parentAoo?.num_aoo || '06/2026'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Délai d'exécution :</span>
                      <span className="font-bold text-slate-900">{parentAoo?.delai_execution || 12} Mois</span>
                    </div>
                  </div>
                </div>

                <div className="pt-2 flex flex-wrap gap-1.5">
                  <button
                    type="button"
                    onClick={() => setActiveFormModalDoc('marche-definitif')}
                    className="py-2 px-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] rounded-xl border border-indigo-200 transition-all flex items-center gap-1"
                  >
                    <Edit3 size={13} /> Formulaire
                  </button>
                  <button
                    type="button"
                    onClick={() => setActivePreviewModalDoc('marche-definitif')}
                    className="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] rounded-xl transition-all flex items-center gap-1"
                  >
                    <Eye size={13} /> Prévisualiser
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('marche-definitif')}
                    className="flex-1 py-2 px-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-1.5 group"
                  >
                    <Download size={14} className="group-hover:animate-bounce" /> Télécharger
                  </button>
                </div>
              </div>

              {/* CARTE 3 : APPROBATION DU MARCHÉ */}
              <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                  <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                        <CheckCircle size={20} />
                      </div>
                      <div>
                        <h3 className="font-extrabold text-slate-900 text-base">Approbation du Marché</h3>
                        <p className="text-[11px] text-slate-500 font-mono">Notification & Caution 3%</p>
                      </div>
                    </div>
                    <span className="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-extrabold text-[11px]">
                      {formData.date_approbation ? 'Approuvé' : 'Brouillon'}
                    </span>
                  </div>

                  <div className="mt-4 space-y-2 text-xs">
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">N° Décision :</span>
                      <span className="font-bold text-slate-800 font-mono">{formData.num_decision || '01/2026/M06'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Date d'approbation :</span>
                      <span className="font-bold text-slate-900">{formData.date_approbation || new Date().toLocaleDateString('fr-FR')}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Caution Définitive (3%) :</span>
                      <span className="font-extrabold text-emerald-800 font-mono">{formatMoney(parseFloat(formData.montant || 0) * 0.03)} dh</span>
                    </div>
                  </div>
                </div>

                <div className="pt-2 flex flex-wrap gap-1.5">
                  <button
                    type="button"
                    onClick={() => setActiveFormModalDoc('decision-approbation')}
                    className="py-2 px-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[11px] rounded-xl border border-emerald-200 transition-all flex items-center gap-1"
                  >
                    <Edit3 size={13} /> Formulaire
                  </button>
                  <button
                    type="button"
                    onClick={() => setActivePreviewModalDoc('decision-approbation')}
                    className="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] rounded-xl transition-all flex items-center gap-1"
                  >
                    <Eye size={13} /> Prévisualiser
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('decision-approbation')}
                    className="flex-1 py-2 px-3 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-500/30 hover:shadow-lg hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-1.5 group"
                  >
                    <Download size={14} className="group-hover:animate-bounce" /> Télécharger
                  </button>
                </div>
              </div>

              {/* CARTE 4 : ORDRE DE SERVICE DE COMMENCEMENT */}
              <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                  <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                        <ReceiptText size={20} />
                      </div>
                      <div>
                        <h3 className="font-extrabold text-slate-900 text-base">OS de Commencement</h3>
                        <p className="text-[11px] text-slate-500 font-mono">Démarrage des prestations</p>
                      </div>
                    </div>
                    <span className="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-extrabold text-[11px]">
                      {formData.os_numero ? 'Notifié' : 'En attente'}
                    </span>
                  </div>

                  <div className="mt-4 space-y-2 text-xs">
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">N° OS :</span>
                      <span className="font-bold text-amber-900 font-mono">{formData.os_numero || '03/2026/M10'}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Date d'Effet :</span>
                      <span className="font-bold text-slate-900">{formData.os_date_effet || new Date().toLocaleDateString('fr-FR')}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-50">
                      <span className="text-slate-500 font-medium">Accusé de réception :</span>
                      <span className="font-bold text-slate-800">{formData.titulaire || '—'}</span>
                    </div>
                  </div>
                </div>
                <div className="pt-2 flex flex-wrap gap-1.5">
                  <button
                    type="button"
                    onClick={() => setActiveFormModalDoc('os-commencement')}
                    className="py-2 px-2.5 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-[11px] rounded-xl border border-amber-200 transition-all flex items-center gap-1"
                  >
                    <Edit3 size={13} /> Formulaire
                  </button>
                  <button
                    type="button"
                    onClick={() => setActivePreviewModalDoc('os-commencement')}
                    className="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] rounded-xl transition-all flex items-center gap-1"
                  >
                    <Eye size={13} /> Prévisualiser
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('os-commencement')}
                    className="flex-1 py-2 px-3 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-600 hover:to-amber-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-500/30 hover:shadow-lg hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-1.5 group"
                  >
                    <Download size={14} className="group-hover:animate-bounce" /> Télécharger
                  </button>
                </div>
              </div>

            </div>

            {/* FORMULAIRE DE SAISIE & EDITION RAPIDE DE L'ENGAGEMENT */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm mb-8 space-y-4">
              <h3 className="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <FileSignature size={18} className="text-blue-600" />
                Mise à jour des paramètres d'engagement du marché
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">N° Décision Approbation</label>
                  <input
                    type="text"
                    name="num_decision"
                    value={formData.num_decision || ''}
                    onChange={handleChange}
                    placeholder="Ex: 01/2026/M06"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white font-mono text-xs outline-none focus:border-blue-600"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Date Approbation</label>
                  <input
                    type="date"
                    name="date_approbation"
                    value={formData.date_approbation || ''}
                    onChange={handleChange}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs outline-none focus:border-blue-600"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">N° OS Commencement</label>
                  <input
                    type="text"
                    name="os_numero"
                    value={formData.os_numero || ''}
                    onChange={handleChange}
                    placeholder="Ex: 03/2026/M10"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white font-mono text-xs outline-none focus:border-blue-600"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Date d'effet OS</label>
                  <input
                    type="date"
                    name="os_date_effet"
                    value={formData.os_date_effet || ''}
                    onChange={handleChange}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs outline-none focus:border-blue-600"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Agent chargé du suivi</label>
                  <input
                    type="text"
                    name="agent_suivi"
                    value={formData.agent_suivi || ''}
                    onChange={handleChange}
                    placeholder="Nom du responsable ONCA..."
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs outline-none focus:border-blue-600"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Exercice budgétaire</label>
                  <input
                    type="text"
                    name="exercice"
                    value={formData.exercice || ''}
                    onChange={handleChange}
                    placeholder="2026"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs outline-none focus:border-blue-600"
                  />
                </div>
              </div>
            </div>

            <div className="mt-8 flex flex-wrap justify-between gap-3">
              <button type="button" onClick={() => markPhase('engagement', true)} className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white font-bold rounded-2xl shadow hover:bg-emerald-700 transition-all">
                <CheckCircle size={18} /> Valider la phase Engagement
              </button>
              {nextStep && (
                <button type="button" onClick={handleNextStep} className="inline-flex items-center gap-2 px-6 py-3 bg-[#1e3a8a] text-white font-bold rounded-2xl shadow-xl hover:bg-[#16316f] transition-all">
                  <span>Suivant : {wizardLabels[nextStep]}</span>
                  <ArrowRight size={18} />
                </button>
              )}
            </div>
          </div>

          <div className={activeTab === 'liquidation' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Liquidation et exécution</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mb-8">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de notification du marché</label>
                <input type="date" name="date_notification_marche" value={formData.date_notification_marche} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro de l'Ordre de Service</label>
                <input type="text" name="os_numero" value={formData.os_numero} onChange={handleChange} placeholder="Ex: OS/01/2026" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de signature de l'OS</label>
                <input type="date" name="os_date_signature" value={formData.os_date_signature} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet / Commencement effectif</label>
                <input type="date" name="os_date_effet" value={formData.os_date_effet} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
            </div>

            <div className="max-w-4xl mb-8">
              <label className="block text-sm font-bold text-slate-700 mb-2">Agent chargé du suivi</label>
              <input type="text" name="agent_suivi" value={formData.agent_suivi} onChange={handleChange} placeholder="Nom de l'agent ONCA responsable..." className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-amber-500 outline-none bg-slate-50 focus:bg-white" />
            </div>

            {renderActionBanner("Mettre à jour le Suivi", [
              { key: 'os-commencement', label: 'Générer OS Commencement' },
              { key: 'designation-agent-suivi', label: 'Désignation agent suivi' },
              { key: 'os-arret', label: 'OS Arrêt' },
              { key: 'os-reprise', label: 'OS Reprise' }
            ])}
            <div className="mt-8 flex flex-wrap justify-between gap-3">
              <button type="button" onClick={() => markPhase('liquidation', true)} className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl shadow hover:bg-emerald-700 transition-all">
                <CheckCircle size={18} /> Valider la phase Liquidation
              </button>
              {nextStep && (
                <button type="button" onClick={handleNextStep} className="inline-flex items-center gap-2 px-6 py-3 bg-[#1e3a8a] text-white rounded-2xl shadow-xl hover:bg-[#16316f] transition-all">
                  <span>Suivant : {wizardLabels[nextStep]}</span>
                  <ArrowRight size={18} />
                </button>
              )}
            </div>
          </div>

          <div className={activeTab === 'ordonnancement' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Ordonnancement et paiement final</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mb-8">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro de la décision</label>
                <input type="text" name="num_decision" value={formData.num_decision} onChange={handleChange} placeholder="Ex: 38/DR/2025" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de la décision</label>
                <input type="date" name="date_decision" value={formData.date_decision} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de réunion de la commission</label>
                <input type="date" name="date_reunion_commission" value={formData.date_reunion_commission} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Heure de réunion</label>
                <input type="time" name="heure_reunion_commission" value={formData.heure_reunion_commission} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Lieu de réunion</label>
                <input type="text" name="lieu_reunion_commission" value={formData.lieu_reunion_commission} onChange={handleChange} placeholder="Ex: au siège de la DRCA-RSK" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mb-8">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de réception finale</label>
                <input type="date" name="date_reception_finale" value={formData.date_reception_finale} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none bg-slate-50 focus:bg-white" />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Membres de la commission de réception</label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
                  {(Array.isArray(commissionMembres) ? commissionMembres : []).map((membre) => (
                    <label key={membre.id} className="flex items-center p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                      <input
                        type="checkbox"
                        checked={(Array.isArray(selectedCommissionMembres) ? selectedCommissionMembres : []).includes(membre.id)}
                        onChange={(e) => {
                          const currentSelected = Array.isArray(selectedCommissionMembres) ? selectedCommissionMembres : [];
                          if (e.target.checked) {
                            setSelectedCommissionMembres([...currentSelected, membre.id]);
                          } else {
                            setSelectedCommissionMembres(currentSelected.filter(id => id !== membre.id));
                          }
                        }}
                        className="mr-3 w-4 h-4 text-blue-600"
                      />
                      <div>
                        <div className="text-sm font-medium text-slate-700">{membre.nom_prenom}</div>
                        <div className="text-xs text-slate-500">{membre.fonction}</div>
                      </div>
                    </label>
                  ))}
                </div>
                {selectedCommissionMembres.length > 0 && (
                  <div className="text-sm text-blue-700 font-medium">
                    {selectedCommissionMembres.length} membre(s) sélectionné(s)
                  </div>
                )}
              </div>
            </div>

            <div className="bg-slate-900 text-white p-6 rounded-2xl shadow-lg border border-slate-800">
              <div className="flex justify-between items-start mb-6 border-b border-slate-700 pb-4">
                <h2 className="text-lg font-bold flex items-center gap-2">
                  <FileText className="text-indigo-400" size={20} /> Documents d'Ordonnancement / Clôture
                </h2>
                {formData.fournisseur_data && (
                  <div className="text-right bg-slate-800 p-3 rounded-xl border border-slate-700">
                    <span className="block text-xs text-slate-400 mb-1 uppercase font-bold">Banque & RIB de {formData.titulaire}</span>
                    <div className="flex flex-col">
                      <span className="font-bold text-slate-200 text-sm">{formData.fournisseur_data.banque || 'Banque non renseignée'}</span>
                      <span className="font-mono font-bold text-indigo-300 text-sm tracking-wider">{formData.fournisseur_data.rib || 'RIB non renseigné'}</span>
                    </div>
                  </div>
                )}
              </div>

              <div className="space-y-3">
                <button type="button" onClick={async () => {
                  try {
                    const res = await api.get(`/marches/${id}/cloture/documents/mainlevee`, { responseType: 'blob' });
                    const mimeType = res.data.type || res.headers['content-type'] || '';
                    const extension = mimeType.includes('pdf') ? 'pdf' : 'docx';
                    const url = window.URL.createObjectURL(new Blob([res.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `mainlevee_${formData.num_marche}.${extension}`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                  } catch (err) {
                    alert('Erreur lors de la génération. Avez-vous enregistré les données de clôture ? (Avez-vous ajouté mainlevee.docx ?)');
                  }
                }} className="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-bold flex items-center justify-between transition-colors">
                  <span className="flex items-center gap-2"><FileDown size={18} className="text-emerald-400" /> Mainlevée</span>
                  <span className="text-xs bg-slate-700 px-2 py-1 rounded text-slate-300">Générer</span>
                </button>

                <button type="button" onClick={async () => {
                  try {
                    const res = await api.get(`/marches/${id}/cloture/documents/certificat_reference`, { responseType: 'blob' });
                    const mimeType = res.data.type || res.headers['content-type'] || '';
                    const extension = mimeType.includes('pdf') ? 'pdf' : 'docx';
                    const url = window.URL.createObjectURL(new Blob([res.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `certificat_reference_${formData.num_marche}.${extension}`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                  } catch (err) {
                    alert('Erreur lors de la génération. Avez-vous enregistré les données de clôture ? (Avez-vous ajouté certificat_reference.docx ?)');
                  }
                }} className="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-bold flex items-center justify-between transition-colors">
                  <span className="flex items-center gap-2"><FileDown size={18} className="text-amber-400" /> Certificat de Référence</span>
                  <span className="text-xs bg-slate-700 px-2 py-1 rounded text-slate-300">Générer</span>
                </button>
              </div>
            </div>
            <div className="mt-8 flex flex-wrap justify-between gap-3">
              <button type="button" onClick={() => markPhase('ordonnancement', true)} className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl shadow hover:bg-emerald-700 transition-all">
                <CheckCircle size={18} /> Valider la phase Ordonnancement
              </button>

              {id !== 'nouveau' && ['ordonnancement_validee', 'cloture_en_cours', 'cloture_validee'].includes(formData.statut) && (
                <button type="button" onClick={() => navigate(`/marches/${id}/cloture`)} className="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white font-bold rounded-2xl shadow-xl hover:bg-slate-800 transition-all">
                  <span>Phase Clôture & Documents finaux</span>
                  <ArrowRight size={18} />
                </button>
              )}
            </div>

            {/* Export Archive Button */}
            <div className="mt-8 relative group">
              <button
                type="button"
                onClick={downloadArchive}
                disabled={id === 'nouveau'}
                className="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Package size={20} />
                <span>Exporter les documents disponibles (.zip)</span>
              </button>
              {id === 'nouveau' && (
                <div className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-max max-w-xs px-3 py-2 bg-slate-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-normal">
                  Le dossier complet est disponible uniquement après la finalisation de toutes les étapes et la génération de tous les documents.
                </div>
              )}
            </div>
          </div>
        </form>

        {/* Engagement Form Modal */}
        <EngagementFormModal
          isOpen={!!activeFormModalDoc}
          onClose={() => setActiveFormModalDoc(null)}
          docType={activeFormModalDoc}
          docTitle={
            activeFormModalDoc === 'acte-engagement' ? 'Acte d\'Engagement' :
              activeFormModalDoc === 'marche-definitif' ? 'Marché Définitif & CPS' :
                activeFormModalDoc === 'decision-approbation' ? 'Approbation du Marché' :
                  activeFormModalDoc === 'os-commencement' ? 'Ordre de Service de Commencement' : ''
          }
          initialData={formData}
          onSave={(updated) => {
            setFormData((prev) => ({ ...prev, ...updated }));
            saveMarche();
            setActiveFormModalDoc(null);
          }}
          onPreview={(type, updated) => {
            setFormData((prev) => ({ ...prev, ...updated }));
            saveMarche();
            setActiveFormModalDoc(null);
            setActivePreviewModalDoc(type);
          }}
        />

        {/* Engagement Preview Modal */}
        <EngagementPreviewModal
          isOpen={!!activePreviewModalDoc}
          onClose={() => setActivePreviewModalDoc(null)}
          docType={activePreviewModalDoc}
          docTitle={
            activePreviewModalDoc === 'acte-engagement' ? 'Acte d\'Engagement' :
              activePreviewModalDoc === 'marche-definitif' ? 'Marché Définitif & CPS' :
                activePreviewModalDoc === 'decision-approbation' ? 'Approbation du Marché' :
                  activePreviewModalDoc === 'os-commencement' ? 'Ordre de Service de Commencement' : ''
          }
          formData={formData}
          onEdit={(type) => {
            setActivePreviewModalDoc(null);
            setActiveFormModalDoc(type);
          }}
          onDownload={(type) => {
            downloadDocument(type);
          }}
        />
      </main>
    </div>
  );
};

export default GestionMarches;
