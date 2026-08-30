import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import api from '../api/axios';
import { Save, CheckCircle, AlertCircle, Loader2, ArrowLeft, ArrowRight, Download, Briefcase, FileSignature, CheckSquare, Package } from 'lucide-react';

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

const TVA_RATE = 1.20;
const round2 = (value) => Math.round(parseNum(value) * 100) / 100;

const htFromTtc = (ttc) => round2(parseNum(ttc) / TVA_RATE);
const ttcFromHt = (ht) => round2(parseNum(ht) * TVA_RATE);

const buildBordereauFromLot = (lot, savedItems = []) => {
  const articles = Array.isArray(lot?.items) ? lot.items : [];
  return articles.map((item, index) => {
    const saved = savedItems.find(s => String(s.lot_item_id) === String(item.id));
    const puHt = saved?.prix_unitaire_attributaire;
    const prixTtc = puHt !== '' && puHt !== null && puHt !== undefined
      ? ttcFromHt(puHt)
      : '';

    return {
      lot_item_id: item.id,
      numero: item.numero ?? index + 1,
      designation: item.designation || '',
      unite: item.unite || '',
      quantite: item.quantite ?? '',
      prix_unitaire_ttc: prixTtc,
    };
  });
};

const computeBordereauLine = (item) => {
  const quantite = parseNum(item.quantite);
  const puTtc = parseNum(item.prix_unitaire_ttc);
  const puHt = htFromTtc(puTtc);
  const montantHt = round2(quantite * puHt);
  const montantTtc = round2(quantite * puTtc);

  return { puHt, montantHt, montantTtc };
};

const computeBordereauTotals = (items) => {
  const totalTtc = round2(items.reduce((sum, item) => sum + computeBordereauLine(item).montantTtc, 0));
  const totalHt = round2(totalTtc / TVA_RATE);
  const tva = round2(totalTtc - totalHt);

  return { totalHt, tva, totalTtc };
};

const GestionMarches = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [activeTab, setActiveTab] = useState('creation');

  const wizardStepOrder = ['creation', 'approbation', 'execution', 'reception'];
  const wizardNextLabels = {
    creation: 'Approbation',
    approbation: 'Exécution & Suivi',
    execution: 'Réception & Clôture',
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
    commission_reception: []
  });

  const [nouveauMembre, setNouveauMembre] = useState('');
  const [bordereauItems, setBordereauItems] = useState([]);
  const [commissionMembres, setCommissionMembres] = useState([]);
  const [selectedCommissionMembres, setSelectedCommissionMembres] = useState([]);

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

  const resolveLotAttributaire = useCallback((aoo, targetLot) => {
    const marches = Array.isArray(aoo.marches) ? aoo.marches : [];
    const concurrents = Array.isArray(aoo.concurrents) ? aoo.concurrents : [];

    let titulaire = '';
    let montant = '';
    let lotLabel = toText(targetLot.num_lot, `LOT ${targetLot.id}`);
    let lotId = String(targetLot.id);
    let fournisseurId = '';
    let qualiteGerant = '';
    let objetMarche = toText(targetLot.objet_lot, toText(aoo.objet, ''));

    const existingMarche = marches.find(m => String(m.lot_id) === String(targetLot.id));
    if (existingMarche) {
      titulaire = existingMarche.titulaire || '';
      montant = existingMarche.montant ?? '';
      lotLabel = toText(existingMarche.lot, lotLabel) || lotLabel;
      fournisseurId = existingMarche.fournisseur_id ? String(existingMarche.fournisseur_id) : '';
      objetMarche = existingMarche.objet_marche || objetMarche;
      qualiteGerant = existingMarche.qualite_gerant || '';
    }

    const attributaire = targetLot.attributaire;
    if (attributaire) {
      titulaire = titulaire || attributaire.raison_sociale || '';
      fournisseurId = fournisseurId || String(attributaire.id);
      qualiteGerant = qualiteGerant || toText(attributaire.qualite_representant, '') || 'Gérant';
    }

    const attributaireId = targetLot.attributaire_fournisseur_id || fournisseurId;
    const retenueDecision = (targetLot.decisions || []).find(
      d => d.statut === 'Retenu' && (!attributaireId || String(d.fournisseur_id) === String(attributaireId))
    ) || (targetLot.decisions || []).find(d => d.statut === 'Retenu');

    if (retenueDecision) {
      montant = montant !== '' && montant !== null ? montant : (retenueDecision.montant_propose ?? '');
      fournisseurId = fournisseurId || String(retenueDecision.fournisseur_id);
      titulaire = titulaire || retenueDecision.fournisseur?.raison_sociale || '';
      qualiteGerant = qualiteGerant || toText(retenueDecision.fournisseur?.qualite_representant, '') || 'Gérant';
    }

    if ((!titulaire || montant === '') && (aoo.lots?.length || 0) <= 1) {
      const retenu = concurrents.find(c => c.statut_analyse === 'retenu');
      if (retenu) {
        titulaire = titulaire || retenu.fournisseur?.raison_sociale || retenu.nom_soumissionnaire || '';
        fournisseurId = fournisseurId || String(retenu.fournisseur_id || '');
        qualiteGerant = qualiteGerant || toText(retenu.fournisseur?.qualite_representant, '') || 'Gérant';
        if (montant === '') {
          montant = retenu.montant_engagement ?? '';
        }
      }
    }

    const fournisseurQualite = resolveFournisseurQualite(aoo, titulaire, fournisseurId);
    if (fournisseurQualite) {
      qualiteGerant = fournisseurQualite;
    }

    return {
      lot_id: lotId,
      lot: lotLabel,
      titulaire,
      montant: montant !== null && montant !== '' ? String(montant) : '',
      fournisseur_id: fournisseurId,
      qualite_gerant: qualiteGerant,
      objet_marche: objetMarche,
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
        qualite_gerant: '',
        objet_marche: '',
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
    }
  }, [id]);

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
      fournisseur_id: '',
      qualite_gerant: '',
      objet_marche: '',
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
        fournisseur_id: '',
        qualite_gerant: '',
        objet_marche: '',
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

  const handleBordereauPriceChange = (index, value) => {
    setBordereauItems(prev => prev.map((item, i) => (
      i === index ? { ...item, prix_unitaire_ttc: value } : item
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
        setErrorMessage(`Le Total TTC du bordereau (${formatMoney(totalTtc)} MAD) doit être égal au montant d'attribution (${formatMoney(montantAttribution)} MAD).`);
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
        bordereau_items: bordereauItems.length > 0
          ? bordereauItems.map(item => ({
              lot_item_id: item.lot_item_id,
              prix_unitaire_ttc: parseNum(item.prix_unitaire_ttc),
            }))
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

    if (documentType === 'os-commencement') {
      try {
        const payload = {
          os_numero: formData.os_numero,
          os_date_signature: formData.os_date_signature,
          os_date_effet: formData.os_date_effet,
          date_notification_marche: formData.date_notification_marche,
        };
        const response = await api.post(`/marches/${id}/generer-os`, payload, {
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `OS_Commencement_${formData.num_marche}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        const errorMessage = err.response?.data?.error || 'Erreur lors de la génération de l\'OS de Commencement. Vérifiez que tous les champs requis sont remplis.';
        setErrorMessage(errorMessage);
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

  const tabClass = (tabName) => `whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === tabName ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`;
  const inputClass = 'w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all';

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
    <div className="min-h-screen bg-slate-50/50 pb-24">
      <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div className="w-full px-4 sm:px-6 lg:px-8">
          <div className="py-4 flex items-center justify-between">
            <div className="flex items-center gap-4">
              <button onClick={() => navigate('/dashboard')} className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all">
                <ArrowLeft size={20} />
              </button>
              <div>
                <h1 className="text-2xl font-bold text-slate-800 flex items-center gap-3">
                  <Briefcase className="text-blue-600" />
                  {id && id !== 'nouveau' ? `Marché : ${formData.num_marche || 'En cours'}` : 'Nouveau Marché'}
                </h1>
                <p className="text-slate-500 text-sm mt-1">Gestion du cycle de vie du Marché (Module 2)</p>
              </div>
            </div>

            <div className="flex items-center gap-3">
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
          <div className="grid grid-cols-1 sm:grid-cols-4 gap-8 pb-4">
            <button onClick={() => setActiveTab('creation')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'creation' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}>
              <FileSignature size={22} /> 1. Création Marché
            </button>
            <button onClick={() => setActiveTab('approbation')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'approbation' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}>
              <CheckSquare size={22} /> 2. Approbation
            </button>
            <button onClick={() => setActiveTab('execution')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'execution' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}>
              <Briefcase size={22} /> 3. Exécution & Suivi
            </button>
            <button onClick={() => setActiveTab('reception')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${activeTab === 'reception' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}>
              <CheckCircle size={22} /> 4. Réception & Clôture
            </button>
          </div>
        </div>
      </header>

      <main className="w-full px-4 sm:px-6 lg:px-8 mt-16">
        <form onSubmit={handleSubmit} className="bg-white rounded-3xl p-10 shadow-sm border border-slate-200">

          {/* TAB 1 : CREATION MARCHE */}
          <div className={activeTab === 'creation' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Liaison et Informations du Marché</h2>
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
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Montant du Marché (MAD) *</label>
                <input type="number" step="0.01" name="montant" value={formData.montant} onChange={handleChange} required readOnly className={`${inputClass} bg-slate-100 text-slate-700 cursor-not-allowed`} />
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
                      <AlertCircle size={16} /> Total TTC ({formatMoney(bordereauTotals.totalTtc)} MAD) ≠ montant d'attribution ({formatMoney(montantAttribution)} MAD)
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
                                onChange={(e) => handleBordereauPriceChange(index, e.target.value)}
                                className="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white text-right font-mono"
                                placeholder="0,00"
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
                        <td colSpan="5" className="px-3 py-3 text-right font-bold text-slate-700">Total HT</td>
                        <td colSpan="2" className="px-3 py-3 text-right font-mono font-bold">{formatMoney(bordereauTotals.totalHt)} MAD</td>
                      </tr>
                      <tr>
                        <td colSpan="5" className="px-3 py-3 text-right font-bold text-slate-700">TVA (20%)</td>
                        <td colSpan="2" className="px-3 py-3 text-right font-mono font-bold">{formatMoney(bordereauTotals.tva)} MAD</td>
                      </tr>
                      <tr>
                        <td colSpan="5" className="px-3 py-3 text-right font-bold text-slate-800">Total TTC</td>
                        <td colSpan="2" className={`px-3 py-3 text-right font-mono font-extrabold ${bordereauMatch ? 'text-emerald-700' : 'text-red-600'}`}>
                          {formatMoney(bordereauTotals.totalTtc)} MAD
                        </td>
                      </tr>
                      <tr>
                        <td colSpan="7" className="px-3 py-3 text-sm text-slate-500">
                          Montant d'attribution du lot : <strong>{formatMoney(montantAttribution)} MAD TTC</strong>
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
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl shadow-xl hover:bg-blue-700 transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}
          </div>

          {/* TAB 2 : APPROBATION */}
          <div className={activeTab === 'approbation' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Approbation du Marché</h2>
              <p className="text-sm text-slate-500 mt-2">
                Génération automatique à partir des données enregistrées en Phase 1 et de l'AOO parent. Aucune saisie requise.
              </p>
            </div>

            <div className="p-6 bg-slate-50 border border-slate-200 rounded-2xl flex flex-wrap items-center justify-center gap-4 shadow-sm">
              {id && id !== 'nouveau' ? (
                <button
                  type="button"
                  onClick={() => downloadDocument('rapport-presentation-marche')}
                  className="px-6 py-3 text-sm font-bold text-emerald-700 bg-white border border-emerald-200 shadow-sm rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all flex items-center gap-2"
                >
                  <Download size={18} className="text-emerald-500" />
                  Imprimer Rapport de Présentation (Rap.Present)
                </button>
              ) : (
                <div className="flex items-center gap-2 text-amber-600 bg-amber-50 px-4 py-3 rounded-xl border border-amber-200 text-sm font-medium">
                  <AlertCircle size={16} /> Enregistrez d'abord le marché en Phase 1 pour générer le document
                </div>
              )}
            </div>
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl shadow-xl hover:bg-blue-700 transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}
          </div>

          {/* TAB 3 : EXECUTION & SUIVI */}
          <div className={activeTab === 'execution' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Exécution et Suivi</h2>
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
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl shadow-xl hover:bg-blue-700 transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}
          </div>

          {/* TAB 4 : RECEPTION & CLOTURE */}
          <div className={activeTab === 'reception' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Réception et Clôture</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mb-8">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro de la décision</label>
                <input type="text" name="num_decision" value={formData.num_decision} onChange={handleChange} placeholder="Ex: 38/DR/2025" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de la décision</label>
                <input type="date" name="date_decision" value={formData.date_decision} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de réunion de la commission</label>
                <input type="date" name="date_reunion_commission" value={formData.date_reunion_commission} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Heure de réunion</label>
                <input type="time" name="heure_reunion_commission" value={formData.heure_reunion_commission} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Lieu de réunion</label>
                <input type="text" name="lieu_reunion_commission" value={formData.lieu_reunion_commission} onChange={handleChange} placeholder="Ex: au siège de la DRCA-RSK" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mb-8">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de réception finale</label>
                <input type="date" name="date_reception_finale" value={formData.date_reception_finale} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 outline-none bg-slate-50 focus:bg-white" />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-bold text-slate-700 mb-2">Membres de la commission de réception</label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
                  {commissionMembres.map((membre) => (
                    <label key={membre.id} className="flex items-center p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                      <input
                        type="checkbox"
                        checked={selectedCommissionMembres.includes(membre.id)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedCommissionMembres([...selectedCommissionMembres, membre.id]);
                          } else {
                            setSelectedCommissionMembres(selectedCommissionMembres.filter(id => id !== membre.id));
                          }
                        }}
                        className="mr-3 w-4 h-4 text-purple-600"
                      />
                      <div>
                        <div className="text-sm font-medium text-slate-700">{membre.nom_prenom}</div>
                        <div className="text-xs text-slate-500">{membre.fonction}</div>
                      </div>
                    </label>
                  ))}
                </div>
                {selectedCommissionMembres.length > 0 && (
                  <div className="text-sm text-purple-700 font-medium">
                    {selectedCommissionMembres.length} membre(s) sélectionné(s)
                  </div>
                )}
              </div>
            </div>

            {renderActionBanner("Enregistrer la Réception & Clôturer", [
              { key: 'decision-nomination', label: 'Générer Décision Nomination' },
              { key: 'pv-reception-provisoire', label: 'PV Réception provisoire' },
              { key: 'pv-reception-definitive', label: 'PV Réception définitive' },
              { key: 'attestation-bonne-execution', label: 'Attestation bonne exécution' }
            ])}

            {/* Export Archive Button */}
            <div className="mt-8 relative group">
              <button
                type="button"
                onClick={downloadArchive}
                disabled={id === 'nouveau'}
                className="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
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
      </main>
    </div>
  );
};

export default GestionMarches;
