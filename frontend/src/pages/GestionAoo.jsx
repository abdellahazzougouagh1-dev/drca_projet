import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Save, CheckCircle, AlertCircle, Loader2, ArrowLeft, ArrowRight, Download, Users, FileText, Building2, PlayCircle, BarChart2, CheckSquare, Plus, X, Trash2 } from 'lucide-react';

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
      items: lot.items || [],
      estimation: totalTTC > 0 ? totalTTC.toFixed(2) : '',
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

  const wizardStepOrder = ['preparation', 'commission', 'analyse', 'attribution'];
  const wizardNextLabels = {
    preparation: 'Commission & Ouverture',
    commission: 'Analyse',
    analyse: 'Attribution',
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
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [fournisseurs, setFournisseurs] = useState([]);
  const [membresCommissionCatalog, setMembresCommissionCatalog] = useState([]);

  const [formData, setFormData] = useState({
    num_aoo: '',
    objet: '',
    journal_fr: '',
    journal_ar: '',
    date_ouverture: '',
    heure_ouverture: '',
    nombre_lots: 1,
    budget: '',
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

  // State for multi-lot attribution (Phase 4)
  const [attributions, setAttributions] = useState({});

  // State for concurrent lot decisions (multi-lot Phase 3)
  const [lotDecisionsState, setLotDecisionsState] = useState({});

  const [activePreparationLotTab, setActivePreparationLotTab] = useState(0);

  useEffect(() => {
    fetchFournisseurs();
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
      sanitizedData.membres_commission = Array.isArray(sanitizedData.membres_commission) ? sanitizedData.membres_commission : [];
      sanitizedData.concurrents = (Array.isArray(sanitizedData.concurrents) ? sanitizedData.concurrents : [])
        .map(prefillGerantNomForConcurrent);
      sanitizedData.lots = Array.isArray(sanitizedData.lots) ? sanitizedData.lots : [];
      sanitizedData.lots_details = Array.isArray(sanitizedData.lots_details) ? sanitizedData.lots_details : [];
      if (sanitizedData.lots.length > 0) {
        sanitizedData.lots_details = sanitizedData.lots.map(lot => ({
          id: lot.id,
          num_lot: lot.num_lot,
          objet_lot: lot.objet_lot || '',
          estimation: lot.estimation || '',
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
        sanitizedData.lots_details = Array.from({ length: nbLots }, (_, index) => createEmptyLot(index));
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
    setFormData(prev => ({ ...prev, [name]: value }));
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
                    </td>
                    <td className="px-3 py-2 text-center">
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

  const handleAddMembre = () => {
    const catalogMembre = getSelectedCatalogMembre();
    if (!catalogMembre) {
      setErrorMessage('Veuillez sélectionner un membre dans la liste.');
      return;
    }

    const dejaAjoute = formData.membres_commission.some(
      m => String(m.membre_commission_id) === String(catalogMembre.id)
        || m.nom_prenom === catalogMembre.nom_prenom
    );
    if (dejaAjoute) {
      setErrorMessage('Ce membre fait déjà partie de la commission pour cet AOO.');
      return;
    }

    setFormData(prev => ({
      ...prev,
      membres_commission: [...prev.membres_commission, {
        membre_commission_id: catalogMembre.id,
        nom_prenom: catalogMembre.nom_prenom,
        fonction: catalogMembre.fonction,
        qualite: nouveauMembre.qualite,
      }]
    }));
    setNouveauMembre({ membre_commission_id: '', qualite: 'Membre' });
    setErrorMessage('');
  };

  const handleRemoveMembre = (index) => {
    setFormData(prev => ({
      ...prev,
      membres_commission: prev.membres_commission.filter((_, i) => i !== index)
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');

    try {
      const payload = { ...formData };
      if (id && id !== 'nouveau') {
        payload.id = id;
      }

      const response = await api.post('/aoos', payload);
      setSuccessMessage('AOO enregistré avec succès.');

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

  const downloadDocument = (documentType) => {
    if (!id || id === 'nouveau') return;
    window.open(`http://127.0.0.1:8000/api/aoos/${id}/documents/${documentType}`, '_blank');
  };

  const downloadLettreNotification = (fournisseurId, lotId) => {
    if (!id || id === 'nouveau' || !fournisseurId || !lotId) return;
    window.open(`http://127.0.0.1:8000/api/aoos/${id}/lettres-notification/${fournisseurId}/${lotId}`, '_blank');
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
        if (marcheId) {
          navigate(`/marches/${marcheId}`);
        } else {
          navigate(getMarcheNouveauUrl());
        }
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
                  <Building2 className="text-primary" />
                  {id && id !== 'nouveau' ? `Appel d'Offres : ${formData.num_aoo || 'En cours'}` : 'Nouvel Appel d\'Offres'}
                </h1>
                <p className="text-slate-500 text-sm mt-1">Gestion du cycle de vie de l'Appel d'Offres (Module 1)</p>
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

          {/* TABS NAVIGATION - STEPPER ENHANCED */}
          <div className="grid grid-cols-1 sm:grid-cols-4 gap-8 pb-4">
            <button onClick={() => setActiveTab('preparation')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${
              activeTab === 'preparation'
                ? 'bg-primary text-white shadow-lg'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            }`}>
              <FileText size={24} /> 1. Préparation
            </button>
            <button onClick={() => setActiveTab('commission')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${
              activeTab === 'commission'
                ? 'bg-primary text-white shadow-lg'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            }`}>
              <Users size={24} /> 2. Commission & Ouverture
            </button>
            <button onClick={() => setActiveTab('analyse')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${
              activeTab === 'analyse'
                ? 'bg-primary text-white shadow-lg'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            }`}>
              <BarChart2 size={24} /> 3. Analyse
            </button>
            <button onClick={() => setActiveTab('attribution')} className={`min-w-0 flex-1 px-6 py-5 rounded-2xl font-bold text-base transition-all flex items-center justify-center gap-3 ${
              activeTab === 'attribution'
                ? 'bg-primary text-white shadow-lg'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            }`}>
              <CheckSquare size={24} /> 4. Attribution
            </button>
          </div>
        </div>
      </header>

      <main className="w-full px-4 sm:px-6 lg:px-8 mt-16">
        <form onSubmit={handleSubmit} className="bg-white rounded-3xl p-8 shadow-sm border border-slate-200">

          {/* TAB 1 : PREPARATION */}
          <div className={activeTab === 'preparation' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Préparation de l'Appel d'Offres</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="col-span-1 lg:col-span-3">
                <label className="block text-sm font-bold text-slate-700 mb-2">Objet de l'Appel d'Offres *</label>
                <textarea name="objet" value={formData.objet} onChange={handleChange} required rows={3} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"></textarea>
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro AOO *</label>
                <input type="text" name="num_aoo" value={formData.num_aoo} onChange={handleChange} required placeholder="Ex: 01/2026/ONCA" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">
                  Estimation Globale (Budget) *
                  <span className="ml-2 text-xs font-normal text-slate-500">(calculée automatiquement depuis le détail estimatif)</span>
                </label>
                <input
                  type="number"
                  step="0.01"
                  name="budget"
                  value={formData.budget}
                  readOnly
                  className="w-full px-4 py-3 rounded-lg border border-slate-200 outline-none bg-slate-100 text-slate-700 cursor-not-allowed"
                />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Nombre de lots</label>
                <input type="number" name="nombre_lots" value={formData.nombre_lots} onChange={handleNombreLotsChange} min="1" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
              </div>
              <div className="col-span-1 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Journal d'annonce (Français)</label>
                  <input type="text" name="journal_fr" value={formData.journal_fr} onChange={handleChange} placeholder="Ex: Le Matin" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Journal d'annonce (Arabe)</label>
                  <input type="text" name="journal_ar" value={formData.journal_ar} onChange={handleChange} placeholder="Ex: Assahra Al Maghribia" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all text-right" dir="auto" />
                </div>
              </div>

              {parseInt(formData.nombre_lots, 10) > 1 ? (
                <div className="col-span-1 lg:col-span-3 mt-6 p-5 border border-slate-200 rounded-2xl bg-slate-50 shadow-sm">
                  <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-5">
                    <h3 className="text-lg font-bold text-slate-800">Détail Estimatif par Lot</h3>
                    <p className="text-xs text-slate-500">Saisissez les lignes de calcul pour chaque lot. L'estimation TTC (TVA 20%) est calculée automatiquement.</p>
                  </div>

                  <div className="flex flex-wrap gap-2 mb-5">
                    {Array.from({ length: parseInt(formData.nombre_lots, 10) || 1 }).map((_, index) => {
                      const lot = formData.lots_details?.[index];
                      const isActive = activePreparationLotTab === index;
                      return (
                        <button
                          key={index}
                          type="button"
                          onClick={() => setActivePreparationLotTab(index)}
                          className={`px-4 py-2 rounded-xl text-sm font-bold transition-all border ${
                            isActive
                              ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                              : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300 hover:text-blue-700'
                          }`}
                        >
                          {lot?.num_lot || `LOT ${index + 1}`}
                          {lot?.estimation ? ` — ${formatCurrency(lot.estimation)} DH` : ''}
                        </button>
                      );
                    })}
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                      <label className="block text-sm font-bold text-slate-700 mb-2">
                        Objet du {formData.lots_details?.[currentPreparationLotTab]?.num_lot || `Lot ${currentPreparationLotTab + 1}`} *
                      </label>
                      <input
                        type="text"
                        required
                        placeholder={`Objet du Lot ${currentPreparationLotTab + 1}`}
                        value={formData.lots_details?.[currentPreparationLotTab]?.objet_lot || ''}
                        onChange={(e) => handleLotChange(currentPreparationLotTab, 'objet_lot', e.target.value)}
                        className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-bold text-slate-700 mb-2">Estimation du Lot (TTC)</label>
                      <input
                        type="text"
                        readOnly
                        value={formData.lots_details?.[currentPreparationLotTab]?.estimation
                          ? `${formatCurrency(formData.lots_details[currentPreparationLotTab].estimation)} DH`
                          : ''}
                        placeholder="Calculée depuis le tableau"
                        className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-700 cursor-not-allowed"
                      />
                    </div>
                  </div>

                  {renderLotEstimationTable(currentPreparationLotTab)}
                </div>
              ) : (
                <div className="col-span-1 lg:col-span-3 mt-6 p-5 border border-slate-200 rounded-2xl bg-slate-50 shadow-sm">
                  <div className="mb-5">
                    <h3 className="text-lg font-bold text-slate-800">Détail Estimatif</h3>
                    <p className="text-xs text-slate-500 mt-1">Saisissez les lignes de calcul. L'estimation globale TTC (TVA 20%) sera mise à jour automatiquement.</p>
                  </div>
                  {renderLotEstimationTable(0)}
                </div>
              )}

              <div className="col-span-1 lg:col-span-3 mt-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <h3 className="text-sm font-bold text-slate-800 mb-4">Imputation Budgétaire</h3>
                <div className="grid grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Article (Art)</label>
                    <input type="text" name="art" value={formData.art} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Paragraphe (Par)</label>
                    <input type="text" name="par" value={formData.par} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Ligne (Lig)</label>
                    <input type="text" name="lig" value={formData.lig} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white outline-none focus:border-primary" />
                  </div>
                </div>
              </div>
            </div>

            {renderActionBanner("Valider la Préparation", [
              { key: 'decision-lancement', label: 'Décision de lancement' },
              { key: 'estimation', label: 'Estimation' }
            ])}
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-2xl shadow-xl hover:bg-primary-dark transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}
          </div>

          {/* TAB 2 : COMMISSION & OUVERTURE */}
          <div className={activeTab === 'commission' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Commission & Ouverture des plis</h2>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              {/* Colonne Gauche : Commission */}
              <div>
                <h3 className="text-lg font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Membres de la Commission</h3>

                <div className="mb-4">
                  <label className="block text-sm font-bold text-slate-700 mb-2">Décision de nomination N°</label>
                  <input type="text" name="num_decision_nomination" value={formData.num_decision_nomination} onChange={handleChange} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                </div>

                <div className="mb-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                  <label className="block text-sm font-bold text-slate-700 mb-3">Ajouter un membre à la commission</label>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-500 mb-1">Membre *</label>
                      <div className="flex gap-2">
                        <select
                          value={nouveauMembre.membre_commission_id}
                          onChange={(e) => setNouveauMembre({ ...nouveauMembre, membre_commission_id: e.target.value })}
                          className="flex-1 min-w-0 px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-slate-50"
                        >
                          <option value="">Sélectionner un membre...</option>
                          {membresCommissionCatalog.map(membre => (
                            <option key={membre.id} value={membre.id}>{membre.nom_prenom}</option>
                          ))}
                        </select>
                        <button
                          type="button"
                          onClick={openMembreQuickModal}
                          title="Ajouter un nouveau membre au référentiel"
                          className="shrink-0 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center"
                        >
                          <Plus size={18} />
                        </button>
                      </div>
                      {membresCommissionCatalog.length === 0 && (
                        <p className="text-xs text-amber-600 mt-1">
                          Aucun membre en base.{' '}
                          <button type="button" onClick={() => navigate('/commission-membres')} className="underline font-semibold">
                            Créer un membre
                          </button>
                        </p>
                      )}
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-500 mb-1">Fonction</label>
                      <input
                        type="text"
                        readOnly
                        value={getSelectedCatalogMembre()?.fonction || ''}
                        placeholder="Sélectionnez un membre"
                        className="w-full px-4 py-2 rounded-lg border border-slate-200 bg-slate-100 text-slate-700 cursor-not-allowed outline-none"
                      />
                    </div>
                  </div>
                  <div className="flex gap-3">
                    <select value={nouveauMembre.qualite} onChange={(e) => setNouveauMembre({ ...nouveauMembre, qualite: e.target.value })} className="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-slate-50 outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10 transition-all">
                      <option value="Président">Président</option>
                      <option value="Membre">Membre</option>
                      <option value="Secrétaire">Secrétaire</option>
                    </select>
                    <button type="button" onClick={handleAddMembre} className="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors">Ajouter</button>
                  </div>
                </div>

                {formData.membres_commission.length > 0 && (
                  <ul className="space-y-3">
                    {formData.membres_commission.map((membre, index) => (
                      <li key={index} className="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                          <div className="font-bold text-slate-800">{membre.nom_prenom} <span className="text-xs font-semibold px-2 py-1 bg-blue-100 text-blue-700 rounded-full ml-2">{membre.qualite}</span></div>
                          <div className="text-sm text-slate-500 mt-1">{membre.fonction}</div>
                        </div>
                        <button type="button" onClick={() => handleRemoveMembre(index)} className="text-red-500 hover:text-red-700 p-2 bg-red-50 rounded-lg">Retirer</button>
                      </li>
                    ))}
                  </ul>
                )}
              </div>

              {/* Colonne Droite : Ouverture */}
              <div>
                <h3 className="text-lg font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Informations de la séance</h3>

                <div className="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">N° AOO Interne</label>
                    <input type="text" name="num_aoo_interne" value={formData.num_aoo_interne} onChange={handleChange} placeholder="Ex: 01" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date de la lettre</label>
                    <input type="date" name="date_lettre" value={formData.date_lettre} onChange={handleChange} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date d'ouverture réélle</label>
                    <input type="date" name="date_ouverture" value={formData.date_ouverture} onChange={handleChange} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Heure d'ouverture</label>
                    <input type="time" name="heure_ouverture" value={formData.heure_ouverture} onChange={handleChange} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                  </div>
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-bold text-slate-700 mb-2">Lieu d'ouverture des plis</label>
                  <textarea name="lieu_ouverture" value={formData.lieu_ouverture} onChange={handleChange} rows="3" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"></textarea>
                </div>
              </div>
            </div>

            {renderActionBanner("Valider Commission", [
              { key: 'decision-nomination', label: 'Décision de nomination' },
              { key: 'lettre-commission', label: 'Lettre membres commission' },
              { key: 'lettre-controleur-etat', label: 'Lettre Contrôleur d\'État' },
              { key: 'convocation-membres', label: 'Convocation membres' },
              { key: 'liste-presence', label: 'Liste de présence' }
            ])}
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-2xl shadow-xl hover:bg-primary-dark transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}

            <hr className="my-10 border-slate-200" />

            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Ouverture des Plis</h2>
            </div>

            <div className="mb-6 p-5 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">N° AOO</p>
                <p className="font-bold text-slate-800">{formData.num_aoo || '-'}</p>
              </div>
              <div className="lg:col-span-2">
                <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Objet</p>
                <p className="font-bold text-slate-800 line-clamp-1" title={formData.objet}>{formData.objet || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Estimation (MO)</p>
                <p className="font-bold text-slate-800">{formData.budget ? `${formData.budget} DH` : '-'}</p>
              </div>
              <div className="lg:col-span-4 grid grid-cols-2 gap-4 pt-2 border-t border-slate-200 mt-2">
                <div><span className="text-xs font-bold text-slate-500 mr-2">Journal FR:</span> <span className="text-sm font-medium">{formData.journal_fr || '-'}</span></div>
                <div><span className="text-xs font-bold text-slate-500 mr-2">Journal AR:</span> <span className="text-sm font-medium">{formData.journal_ar || '-'}</span></div>
              </div>
            </div>

            <div className="overflow-x-auto rounded-xl border border-slate-200">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-600 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-bold">Nom du Soumissionnaire</th>
                    <th className="px-4 py-3 font-bold text-center">Dossier Administratif<br /><span className="text-[10px] font-normal">D/H | C.P | RC | CPS</span></th>
                    <th className="px-4 py-3 font-bold text-center">Dossier Technique<br /><span className="text-[10px] font-normal">M.Hum et tech</span></th>
                    <th className="px-4 py-3 font-bold">Montant Acte Engagement</th>
                    <th className="px-4 py-3 font-bold">Observations</th>
                    <th className="px-4 py-3"></th>
                  </tr>
                </thead>
                <tbody>
                  {formData.concurrents.length > 0 ? (
                    formData.concurrents.map((c, index) => (
                      <tr key={index} className={`border-b border-slate-100 hover:bg-slate-50/50 ${!c.fournisseur_id ? 'bg-amber-50/50' : ''}`}>
                        <td className="p-3">
                          <select
                            value={c.fournisseur_id || ''}
                            onChange={(e) => handleConcurrentChange(index, 'fournisseur_id', e.target.value)}
                            className={`w-full px-3 py-2 border rounded-lg text-sm outline-none focus:border-primary bg-white ${!c.fournisseur_id ? 'border-amber-300' : 'border-slate-200'}`}
                          >
                            <option value="">Selectionner un fournisseur...</option>
                            {fournisseurs.map(fournisseur => (
                              <option key={fournisseur.id} value={fournisseur.id}>
                                {fournisseur.raison_sociale}
                              </option>
                            ))}
                          </select>
                        </td>
                        <td className="p-3 text-center">
                          <div className="flex items-center justify-center gap-2">
                            <input type="checkbox" checked={c.dh} onChange={(e) => handleConcurrentChange(index, 'dh', e.target.checked)} title="D/H" className="w-4 h-4 text-primary rounded border-slate-300" />
                            <input type="checkbox" checked={c.cp} onChange={(e) => handleConcurrentChange(index, 'cp', e.target.checked)} title="C.P" className="w-4 h-4 text-primary rounded border-slate-300" />
                            <input type="checkbox" checked={c.rc} onChange={(e) => handleConcurrentChange(index, 'rc', e.target.checked)} title="RC" className="w-4 h-4 text-primary rounded border-slate-300" />
                            <input type="checkbox" checked={c.cps} onChange={(e) => handleConcurrentChange(index, 'cps', e.target.checked)} title="CPS" className="w-4 h-4 text-primary rounded border-slate-300" />
                          </div>
                        </td>
                        <td className="p-3 text-center">
                          <input type="checkbox" checked={c.m_hum} onChange={(e) => handleConcurrentChange(index, 'm_hum', e.target.checked)} title="M.Hum et tech" className="w-4 h-4 text-primary rounded border-slate-300" />
                        </td>
                        <td className="p-3">
                          <input type="number" step="0.01" value={c.montant_engagement} onChange={(e) => handleConcurrentChange(index, 'montant_engagement', e.target.value)} className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-primary" placeholder="0.00" />
                        </td>
                        <td className="p-3">
                          <input type="text" value={c.observations} onChange={(e) => handleConcurrentChange(index, 'observations', e.target.value)} className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-primary" placeholder="..." />
                        </td>
                        <td className="p-3 text-center">
                          <button type="button" onClick={() => handleRemoveConcurrent(index)} className="p-2 bg-red-50 text-red-500 hover:text-red-700 hover:bg-red-100 rounded-lg transition-colors">
                            Retirer
                          </button>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr><td colSpan="6" className="p-4 text-center text-sm text-slate-500">Aucun concurrent ajouté.</td></tr>
                  )}
                </tbody>
              </table>
            </div>

            <div className="mt-4">
              <button type="button" onClick={handleAddConcurrent} className="px-5 py-2.5 bg-slate-800 text-white font-bold text-sm rounded-xl shadow-md hover:bg-slate-900 transition-all">
                + Ajouter un concurrent
              </button>
            </div>

            <div className="mt-8 p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col xl:flex-row items-center justify-between gap-6 shadow-sm">
              <button
                type="button"
                onClick={saveOuverturePlis}
                disabled={saving || hasEmptyConcurrentRows() || getValidConcurrents().length === 0}
                title={hasEmptyConcurrentRows() ? 'Sélectionnez un fournisseur pour chaque ligne ou retirez les lignes vides' : ''}
                className="w-full xl:w-auto px-8 py-3 bg-purple-600 text-white font-bold rounded-xl shadow-md shadow-purple-600/20 hover:bg-purple-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
              >
                {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                Enregistrer l'Ouverture des Plis
              </button>

              <div className="flex flex-wrap items-center justify-center xl:justify-end gap-3 w-full xl:w-auto">
                <button type="button" onClick={() => downloadDocument('tableau-ouverture')} className="px-5 py-2.5 text-sm font-bold text-emerald-700 bg-white border border-emerald-200 shadow-sm rounded-xl hover:bg-emerald-50 hover:border-emerald-300 flex items-center gap-2"><Download size={16} /> Tableau d'Ouverture</button>
                <button type="button" onClick={() => downloadDocument('pv-ouverture')} className="px-5 py-2.5 text-sm font-bold text-blue-700 bg-white border border-blue-200 shadow-sm rounded-xl hover:bg-blue-50 hover:border-blue-300 flex items-center gap-2"><Download size={16} /> PV Ouverture Plis</button>
              </div>
            </div>

          </div>

          {/* TAB 4 : ANALYSE */}
          <div className={activeTab === 'analyse' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Analyse Technique et Financière</h2>
            </div>

            <div className="mb-6 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
              <h3 className="text-lg font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Informations Générales de la Lettre</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Réf Courrier Globale</label>
                  <input type="text" name="ref_courrier_analyse" value={formData.ref_courrier_analyse || ''} onChange={handleGlobalAnalyseChange} placeholder="Ex: 123/ONCA/2026" className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                  <p className="text-xs text-slate-500 mt-1">S'applique par défaut à tous les concurrents ci-dessous.</p>
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Signataire</label>
                  <select name="signataire_titre_analyse" value={formData.signataire_titre_analyse || ''} onChange={handleGlobalAnalyseChange} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all">
                    <option value="">Sélectionnez un signataire...</option>
                    <option value="Le Président de la commission d'appel d'offre">Le Président de la commission d'appel d'offre</option>
                    <option value="Le Directeur Régional de l'ONCA">Le Directeur Régional de l'ONCA</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="mb-8">
              <h3 className="text-lg font-bold text-slate-700 mb-4">Décisions de la Commission par Concurrent</h3>

              {getValidConcurrents().length === 0 ? (
                <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center text-slate-500 text-sm">
                  Aucun concurrent valide n'a été enregistré dans la phase d'ouverture des plis.
                </div>
              ) : (
                <div className="space-y-4">
                  {getValidConcurrents().map((c, index) => (
                    <div key={c.id || c.fournisseur_id || index} className="p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                      <h4 className="font-bold text-slate-800 text-lg mb-3">{getFournisseurName(c)}</h4>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                          <label className="block text-xs font-bold text-slate-500 mb-1">Réf Courrier</label>
                          <input type="text" value={c.ref_courrier || ''} onChange={(e) => handleConcurrentChange(findConcurrentIndex(c), 'ref_courrier', e.target.value)} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:border-amber-500 outline-none text-sm" />
                        </div>
                        <div>
                          <label className="block text-xs font-bold text-slate-500 mb-1">Nom du Gérant</label>
                          <input type="text" value={c.gerant_nom || ''} onChange={(e) => handleConcurrentChange(findConcurrentIndex(c), 'gerant_nom', e.target.value)} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:border-amber-500 outline-none text-sm" />
                        </div>
                      </div>

                      {!isMultiLots() ? (
                        <div className="flex flex-col lg:flex-row gap-4">
                          <div className="w-full lg:w-1/4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <label className="block text-xs font-bold text-slate-500 mb-1">Montant (Phase 2)</label>
                            <p className="font-bold text-slate-800 text-lg">
                              {c.montant_engagement ? `${Number(c.montant_engagement).toLocaleString('fr-FR')} DH` : 'Non renseigné'}
                            </p>
                            <p className="text-xs text-slate-500 mt-1">Montant repris automatiquement de l'ouverture des plis.</p>
                          </div>
                          <div className="w-full lg:w-1/3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <label className="block text-sm font-bold text-slate-700 mb-3">Résultat de l'analyse</label>
                            <div className="flex flex-col gap-3">
                              <label className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${c.statut_analyse === 'retenu' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:bg-slate-100'}`}>
                                <input type="radio" name={`statut_${c.fournisseur_id}`} value="retenu" checked={c.statut_analyse === 'retenu'} onChange={() => handleConcurrentChange(findConcurrentIndex(c), 'statut_analyse', 'retenu')} className="w-4 h-4 text-emerald-600" />
                                <span className={`font-bold text-sm ${c.statut_analyse === 'retenu' ? 'text-emerald-700' : 'text-slate-600'}`}>Retenu (Moins-disant)</span>
                              </label>
                              <label className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${c.statut_analyse === 'ecarte' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:bg-slate-100'}`}>
                                <input type="radio" name={`statut_${c.fournisseur_id}`} value="ecarte" checked={c.statut_analyse === 'ecarte'} onChange={() => handleConcurrentChange(findConcurrentIndex(c), 'statut_analyse', 'ecarte')} className="w-4 h-4 text-red-600" />
                                <span className={`font-bold text-sm ${c.statut_analyse === 'ecarte' ? 'text-red-700' : 'text-slate-600'}`}>Écarté</span>
                              </label>
                            </div>
                          </div>
                          {c.statut_analyse === 'ecarte' && (
                            <div className="flex-1">
                              <label className="block text-sm font-bold text-red-700 mb-2">Motif de l'écartement</label>
                              <textarea value={c.motif_ecartement || ''} onChange={(e) => handleConcurrentChange(findConcurrentIndex(c), 'motif_ecartement', e.target.value)} rows={3} className="w-full px-4 py-3 rounded-xl border border-red-200 focus:border-red-500 outline-none bg-red-50 resize-none text-sm"></textarea>
                            </div>
                          )}
                          {(c.statut_analyse === 'retenu' || c.statut_analyse === 'ecarte') && getAnalyseLots()[0]?.id && (
                            <div className="flex items-end">
                              <button
                                type="button"
                                onClick={() => downloadLettreNotification(c.fournisseur_id, getAnalyseLots()[0].id)}
                                className="px-4 py-2.5 text-sm font-bold text-violet-700 bg-white border border-violet-200 rounded-xl hover:bg-violet-50 flex items-center gap-2"
                              >
                                <Download size={16} /> Lettre PDF
                              </button>
                            </div>
                          )}
                        </div>
                      ) : (
                        <div className="mt-2">
                          <p className="text-xs font-bold text-amber-700 bg-amber-50 px-3 py-2 rounded-lg mb-3">Cochez les lots pour lesquels ce concurrent postule :</p>
                          <div className="space-y-3">
                            {getAnalyseLots().map((lot) => {
                              const fournisseurId = c.fournisseur_id;
                              const dec = getDecisionForLot(fournisseurId, lot.id);
                              const postule = isPostulingForLot(fournisseurId, lot.id);
                              return (
                                <div key={lot.id} className="p-3 border border-slate-200 rounded-xl bg-slate-50">
                                  <label className="flex items-center gap-3 cursor-pointer mb-2">
                                    <input type="checkbox" checked={postule} onChange={(e) => handleLotDecisionChange(fournisseurId, lot.id, 'postule', e.target.checked)} className="w-4 h-4 text-blue-600" />
                                    <span className="font-bold text-sm text-slate-800">{lot.num_lot} — {lot.objet_lot}</span>
                                    {lot.estimation && <span className="text-xs text-slate-500">(Est. {Number(lot.estimation).toLocaleString('fr-FR')} DH)</span>}
                                  </label>
                                  {postule && (
                                    <div className="mt-2 grid grid-cols-1 md:grid-cols-3 gap-3 pl-7">
                                      <div>
                                        <label className="block text-xs font-bold text-slate-500 mb-1">Montant proposé (Dhs)</label>
                                        <input type="number" step="0.01" value={dec.montant_propose || ''} onChange={(e) => handleLotDecisionChange(fournisseurId, lot.id, 'montant_propose', e.target.value)} className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none bg-white" />
                                      </div>
                                      <div className="flex flex-col gap-2 justify-end">
                                        <label className={`flex items-center gap-2 p-2 rounded-lg border cursor-pointer ${dec.statut === 'Retenu' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200'}`}>
                                          <input type="radio" name={`lot_statut_${fournisseurId}_${lot.id}`} checked={dec.statut === 'Retenu'} onChange={() => handleLotDecisionChange(fournisseurId, lot.id, 'statut', 'Retenu')} className="w-4 h-4 text-emerald-600" />
                                          <span className="text-xs font-bold text-emerald-700">Retenu</span>
                                        </label>
                                        <label className={`flex items-center gap-2 p-2 rounded-lg border cursor-pointer ${dec.statut === 'Ecarte' ? 'border-red-500 bg-red-50' : 'border-slate-200'}`}>
                                          <input type="radio" name={`lot_statut_${fournisseurId}_${lot.id}`} checked={dec.statut === 'Ecarte'} onChange={() => handleLotDecisionChange(fournisseurId, lot.id, 'statut', 'Ecarte')} className="w-4 h-4 text-red-600" />
                                          <span className="text-xs font-bold text-red-700">Écarté</span>
                                        </label>
                                      </div>
                                      {dec.statut === 'Ecarte' && (
                                        <div>
                                          <label className="block text-xs font-bold text-red-600 mb-1">Motif</label>
                                          <input type="text" value={dec.motif_ecartement || ''} onChange={(e) => handleLotDecisionChange(fournisseurId, lot.id, 'motif_ecartement', e.target.value)} className="w-full px-3 py-2 border border-red-200 rounded-lg text-sm bg-red-50 outline-none" />
                                        </div>
                                      )}
                                      {(dec.statut === 'Retenu' || dec.statut === 'Ecarte') && (
                                        <div className="md:col-span-3 pl-0 md:pl-7">
                                          <button
                                            type="button"
                                            onClick={() => downloadLettreNotification(fournisseurId, lot.id)}
                                            className="px-4 py-2 text-xs font-bold text-violet-700 bg-white border border-violet-200 rounded-lg hover:bg-violet-50 flex items-center gap-2"
                                          >
                                            <Download size={14} /> Lettre PDF ({lot.num_lot})
                                          </button>
                                        </div>
                                      )}
                                    </div>
                                  )}
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="max-w-4xl mb-4">
              <label className="block text-sm font-bold text-slate-700 mb-2">État d'avancement</label>
              <textarea name="etat_avancement" value={formData.etat_avancement || ''} onChange={handleChange} rows={4} className="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all resize-none"></textarea>
            </div>

            <div className="mt-8 p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col xl:flex-row items-center justify-between gap-6 shadow-sm">
              <button type="button"
                onClick={isMultiLots() ? handleSaveAllMultiLotDecisions : saveAnalyse}
                disabled={saving || getValidConcurrents().length === 0}
                className="w-full xl:w-auto px-8 py-3 bg-amber-500 text-white font-bold rounded-xl shadow-md hover:bg-amber-600 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                Valider l'Analyse
              </button>
              <div className="flex flex-wrap gap-3">
                <button type="button" onClick={() => downloadDocument('lettres-analyse')} className="px-5 py-2.5 text-sm font-bold text-emerald-700 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 flex items-center gap-2">
                  <Download size={16} /> Lettres de Notification
                </button>
                <button type="button" onClick={() => downloadDocument('resultat-aoo')} className="px-5 py-2.5 text-sm font-bold text-blue-700 bg-white border border-blue-200 rounded-xl hover:bg-blue-50 flex items-center gap-2">
                  <Download size={16} /> Résultat AOO
                </button>
              </div>
            </div>
            {nextStep && (
              <div className="mt-8 flex justify-end">
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-2xl shadow-xl hover:bg-primary-dark transition-all"
                >
                  <span>Suivant : {wizardNextLabels[activeTab]}</span>
                  <ArrowRight size={18} />
                </button>
              </div>
            )}
          </div>

          {/* TAB 5 : ATTRIBUTION */}
          <div className={activeTab === 'attribution' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <h2 className="text-xl font-extrabold text-slate-800">Attribution de l'AOO</h2>
                <p className="text-slate-500 text-sm mt-1">Clôture de l'AOO avant de passer à la création des Marchés.</p>
              </div>
              {id && id !== 'nouveau' && (
                <button
                  type="button"
                  onClick={() => downloadDocument('fiche-suivi')}
                  className="px-5 py-2.5 text-sm font-bold text-violet-700 bg-white border border-violet-200 shadow-sm rounded-xl hover:bg-violet-50 hover:border-violet-300 flex items-center gap-2 shrink-0"
                >
                  <Download size={16} /> Télécharger la Fiche de Suivi (PDF)
                </button>
              )}
            </div>

            {formData.statut === 'attribue' ? (
              <div className="p-6 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-4">
                <CheckCircle className="text-emerald-500 mt-1" size={24} />
                <div>
                  <h3 className="font-bold text-emerald-800 text-lg">AOO Clôturé et Attribué</h3>
                  <p className="text-emerald-700 mt-1">Cet appel d'offres a été clôturé avec succès.</p>
                  <button type="button" onClick={() => navigate(getMarcheNouveauUrl())} className="mt-4 px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700">Aller aux Marchés</button>
                </div>
              </div>
            ) : (
              <div className="max-w-5xl">
                <p className="text-sm text-slate-600 bg-blue-50 border border-blue-200 px-4 py-3 rounded-xl mb-6">
                  {isMultiLots()
                    ? 'Sélectionnez l\'attributaire retenu pour chaque lot. Seuls les concurrents marqués Retenu pour ce lot (Phase 3) sont proposés.'
                    : 'Récapitulatif de l\'attribution. Seuls les concurrents retenus lors de l\'analyse sont éligibles.'}
                </p>

                <div className="overflow-x-auto rounded-xl border border-slate-200 mb-6">
                  <table className="w-full text-sm text-left">
                    <thead className="text-xs text-slate-600 bg-slate-50 border-b border-slate-200">
                      <tr>
                        <th className="px-4 py-3 font-bold">Lot</th>
                        <th className="px-4 py-3 font-bold">Objet</th>
                        <th className="px-4 py-3 font-bold">Estimation</th>
                        <th className="px-4 py-3 font-bold">Attributaire (Retenu)</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(isMultiLots() ? getAnalyseLots() : [{
                        id: formData.lots[0]?.id || 'unique',
                        num_lot: formData.lots[0]?.num_lot || 'LOT 1',
                        objet_lot: formData.lots[0]?.objet_lot || formData.objet,
                        estimation: formData.lots[0]?.estimation || formData.budget,
                      }]).map((lot) => {
                        const retenusConc = getRetenusForLot(lot.id);
                        return (
                          <tr key={lot.id} className="border-b border-slate-100 hover:bg-slate-50/50">
                            <td className="px-4 py-3 font-bold text-blue-600">{lot.num_lot}</td>
                            <td className="px-4 py-3 text-slate-800">{lot.objet_lot || '-'}</td>
                            <td className="px-4 py-3 text-slate-600">
                              {lot.estimation ? `${Number(lot.estimation).toLocaleString('fr-FR')} DH` : '-'}
                            </td>
                            <td className="px-4 py-3">
                              {isMultiLots() ? (
                                <select
                                  value={attributions[lot.id] || ''}
                                  onChange={(e) => handleAttribution(lot.id, e.target.value)}
                                  className="w-full min-w-48 px-3 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white"
                                >
                                  <option value="">-- Sélectionner --</option>
                                  {retenusConc.length > 0 ? retenusConc.map(c => (
                                    <option key={c.fournisseur_id} value={c.fournisseur_id}>
                                      {getFournisseurName(c)}
                                      {lotDecisionsState[`${c.fournisseur_id}_${lot.id}`]?.montant_propose
                                        ? ` — ${Number(lotDecisionsState[`${c.fournisseur_id}_${lot.id}`].montant_propose).toLocaleString('fr-FR')} DH`
                                        : ''}
                                    </option>
                                  )) : (
                                    <option disabled>Aucun retenu pour ce lot (Phase 3)</option>
                                  )}
                                </select>
                              ) : (
                                <div className="space-y-2">
                                  {retenusConc.length > 0 ? (
                                    retenusConc.map(c => (
                                      <div key={c.fournisseur_id} className="flex items-center justify-between p-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                        <span className="font-medium text-emerald-800">{getFournisseurName(c)}</span>
                                        <span className="text-sm text-emerald-700">
                                          {c.montant_engagement ? `${Number(c.montant_engagement).toLocaleString('fr-FR')} DH` : '-'}
                                        </span>
                                      </div>
                                    ))
                                  ) : (
                                    <span className="text-amber-600 text-xs">Aucun concurrent retenu — validez d'abord la Phase 3.</span>
                                  )}
                                </div>
                              )}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>

                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col items-center justify-center shadow-sm">
                  {isMultiLots() ? (
                    <button type="button" onClick={saveAttribuerLots} disabled={saving || Object.keys(attributions).length === 0}
                      className="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 flex items-center gap-2 disabled:opacity-70">
                      {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                      Confirmer l'Attribution et Générer les Marchés
                    </button>
                  ) : (
                    <button type="button" onClick={cloturerAoo} disabled={saving || getRetenusForLot('unique').length === 0}
                      className="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 flex items-center gap-2 disabled:opacity-70">
                      {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                      {saving ? 'Clôture en cours...' : "Clôturer et Attribuer l'AOO"}
                    </button>
                  )}
                </div>
              </div>
            )}
          </div>

        </form>
      </main>

      {isMembreQuickModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-200">
            <div className="flex items-center justify-between p-6 border-b border-slate-100">
              <h2 className="text-xl font-bold text-slate-800">Ajouter un nouveau membre au référentiel</h2>
              <button type="button" onClick={closeMembreQuickModal} className="p-2 hover:bg-slate-100 rounded-lg text-slate-500">
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleMembreQuickSubmit} className="p-6 space-y-4">
              {membreQuickError && (
                <div className="p-3 bg-red-50 text-red-600 rounded-lg text-sm flex items-center gap-2 border border-red-100">
                  <AlertCircle size={16} /> {membreQuickError}
                </div>
              )}
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Nom & Prénom *</label>
                <input
                  type="text"
                  name="nom_prenom"
                  value={membreQuickForm.nom_prenom}
                  onChange={(e) => setMembreQuickForm(prev => ({ ...prev, nom_prenom: e.target.value }))}
                  required
                  className="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10 transition-all"
                />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Fonction *</label>
                <input
                  type="text"
                  name="fonction"
                  value={membreQuickForm.fonction}
                  onChange={(e) => setMembreQuickForm(prev => ({ ...prev, fonction: e.target.value }))}
                  required
                  placeholder="Ex: Ingénieur, Directeur..."
                  className="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10 transition-all"
                />
              </div>
              <div className="flex justify-end gap-3 pt-4">
                <button type="button" onClick={closeMembreQuickModal} className="px-5 py-2.5 text-slate-600 font-bold rounded-xl hover:bg-slate-100">
                  Annuler
                </button>
                <button type="submit" disabled={membreQuickSaving} className="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 disabled:opacity-70 flex items-center gap-2">
                  {membreQuickSaving && <Loader2 size={16} className="animate-spin" />}
                  Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default GestionAoo;
