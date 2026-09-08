import React, { useState, useEffect } from 'react';
import { 
  X, Check, ArrowRight, ArrowLeft, Plus, Edit2, Trash2, 
  AlertCircle, CheckCircle2, FileText, Landmark, Wallet, FileCheck,
  Calendar, Building, Receipt, Layers, Info, WalletCards, Eye, Download
} from 'lucide-react';
import api from '../../api/axios';
import DocumentPreviewModal from './DocumentPreviewModal';

export default function ModalNouveauOrdonnancement({ isOpen, onClose, onSuccess }) {
  const [step, setStep] = useState(1); // 1: Info, 2: Paiements, 3: Documents, 4: Recap
  const [loadingDispos, setLoadingDispos] = useState(false);
  const [liquidationsDispo, setLiquidationsDispo] = useState([]);
  const [selectedDispoIndex, setSelectedDispoIndex] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Retenues à la source state (NON SYSTÉMATIQUES - par défaut false)
  const [applyTva, setApplyTva] = useState(false);
  const [applyIas, setApplyIas] = useState(false);
  const [tvaMontantInput, setTvaMontantInput] = useState('');
  const [iasMontantInput, setIasMontantInput] = useState('');

  // Document Preview Modal State
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewDocType, setPreviewDocType] = useState('op');
  const [previewOrdre, setPreviewOrdre] = useState(null);

  const handleOpenPreview = (type, ordre = null) => {
    setPreviewDocType(type);
    setPreviewOrdre(ordre);
    setPreviewOpen(true);
  };

  // Main Form Data
  const [formData, setFormData] = useState({
    exercice: new Date().getFullYear().toString(),
    date_ordonnancement: new Date().toISOString().split('T')[0],
    liquidation_id: null,
    marche_id: null,
    consultation_id: null,
    fournisseur_id: null,
    notification_ligne_id: null,
    type_procedure: 'Marché',
    reference: '',
    beneficiaire_nom: '',
    rib_compte: '',
    banque_agence: '',
    budget_type: 'Investissement',
    creance: 'Reste à payer',
    code_imputation: '225320',
    article: '415',
    paragraphe: '20',
    ligne: '13',
    sous_ligne: '0',
    intitule_depense: '',
    montant_brut: 0,
    retenue_tva: 0,
    retenue_ias: 0,
    autres_retenues: 0,
    net_a_payer: 0,
    credit_consolide: 0,
    credit_neuf: 0,
    statut: 'À payer',
    observations: '',
    ordres: []
  });

  // Sub-form Modal for adding/editing a payment order
  const [showOrdreModal, setShowOrdreModal] = useState(false);
  const [editingOrdreIndex, setEditingOrdreIndex] = useState(null);
  const [ordreForm, setOrdreForm] = useState({
    num_ordre: '',
    type_mouvement: 'Paiement fournisseur',
    mode_paiement: 'Virement',
    beneficiaire: '',
    rib_compte: '',
    banque_agence: '',
    creance: 'Reste à payer',
    montant: 0,
    statut: 'À payer',
    observations: ''
  });

  useEffect(() => {
    if (isOpen) {
      fetchDisponibles();
      setStep(1);
      setErrorMsg('');
      setApplyTva(false);
      setApplyIas(false);
      setTvaMontantInput('');
      setIasMontantInput('');
    }
  }, [isOpen]);

  const fetchDisponibles = async () => {
    try {
      setLoadingDispos(true);
      const res = await api.get('/ordonnancements/liquidations-disponibles');
      setLiquidationsDispo(res.data.data || []);
    } catch (err) {
      console.error('Erreur chargement liquidations disponibles:', err);
      setErrorMsg('Impossible de charger les liquidations disponibles.');
    } finally {
      setLoadingDispos(false);
    }
  };

  // Helper centralisé de calcul et génération des ordres / mouvements
  const updateFinancialsAndOrdres = (baseData, isTva, tvaAmt, isIas, iasAmt) => {
    const brut = Math.max(0, Number(baseData.montant_brut) || 0);
    const validTva = isTva ? Math.max(0, Number(tvaAmt) || 0) : 0;
    const validIas = isIas ? Math.max(0, Number(iasAmt) || 0) : 0;
    const net = Math.max(0, brut - validTva - validIas);

    // Construction dynamique des mouvements (Ordres)
    const newOrdres = [];

    // 1. Paiement bénéficiaire (Net à payer)
    newOrdres.push({
      num_ordre: 'OP-001',
      type_mouvement: 'Paiement fournisseur',
      mode_paiement: 'Virement',
      beneficiaire: baseData.beneficiaire_nom || 'Bénéficiaire',
      rib_compte: baseData.rib_compte || '',
      banque_agence: baseData.banque_agence || '',
      creance: baseData.creance || 'Reste à payer',
      montant: net,
      statut: 'Brouillon',
      observations: `Paiement du net au bénéficiaire (${formatDH(net)})`
    });

    // 2. Retenue TVA (SEULEMENT si sélectionnée par l'utilisateur et > 0)
    if (isTva && validTva > 0) {
      newOrdres.push({
        num_ordre: `OP-${String(newOrdres.length + 1).padStart(3, '0')}`,
        type_mouvement: 'Retenue à la source TVA',
        mode_paiement: 'Virement',
        beneficiaire: 'TRÉSOR PUBLIC',
        rib_compte: '225 330 000 706 918 851 021 328',
        banque_agence: 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
        creance: 'Retenue à la source',
        montant: validTva,
        statut: 'Brouillon',
        observations: 'Retenue à la source TVA au profit du Trésor Public'
      });
    }

    // 3. Retenue IS (SEULEMENT si sélectionnée par l'utilisateur et > 0)
    if (isIas && validIas > 0) {
      newOrdres.push({
        num_ordre: `OP-${String(newOrdres.length + 1).padStart(3, '0')}`,
        type_mouvement: 'Retenue à la source IS',
        mode_paiement: 'Ordre d\'imputation',
        beneficiaire: 'TRÉSOR PUBLIC',
        rib_compte: '225 330 000 706 918 851 021 328',
        banque_agence: 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
        creance: 'Retenue à la source',
        montant: validIas,
        statut: 'Brouillon',
        observations: 'Retenue à la source IS au profit du Trésor Public'
      });
    }

    setFormData({
      ...baseData,
      retenue_tva: validTva,
      retenue_ias: validIas,
      net_a_payer: net,
      ordres: newOrdres
    });
  };

  const handleSelectLiquidation = (e) => {
    const idx = e.target.value;
    setSelectedDispoIndex(idx);
    if (idx === '') return;

    const item = liquidationsDispo[idx];
    if (!item) return;

    const brut = Number(item.montant_brut || 0);

    // Par défaut, AUCUNE retenue n'est sélectionnée
    setApplyTva(false);
    setApplyIas(false);
    setTvaMontantInput(item.retenue_tva ? String(item.retenue_tva) : '');
    setIasMontantInput(item.retenue_ias ? String(item.retenue_ias) : '');

    const base = {
      ...formData,
      liquidation_id: item.liquidation_id,
      marche_id: item.marche_id,
      consultation_id: item.consultation_id,
      fournisseur_id: item.fournisseur_id,
      notification_ligne_id: item.notification_ligne_id,
      type_procedure: item.type_procedure || 'Marché',
      reference: item.reference || '',
      beneficiaire_nom: item.beneficiaire || '',
      rib_compte: item.rib || '',
      banque_agence: item.banque || '',
      budget_type: item.budget_type || 'Investissement',
      creance: item.creance || 'Reste à payer',
      code_imputation: item.code_imputation || '225320',
      article: item.article || '415',
      paragraphe: item.paragraphe || '20',
      ligne: item.ligne || '13',
      sous_ligne: item.sous_ligne || '0',
      intitule_depense: item.intitule_depense || '',
      montant_brut: brut,
      credit_consolide: Number(item.credit_consolide || 0),
      credit_neuf: Number(item.credit_neuf || 0),
    };

    updateFinancialsAndOrdres(base, false, 0, false, 0);
  };

  const handleToggleTva = (checked) => {
    setApplyTva(checked);
    const initialAmt = checked ? (tvaMontantInput || (formData.montant_brut ? (Number(formData.montant_brut) * 0.2 / 1.2).toFixed(2) : '0')) : '0';
    if (checked && !tvaMontantInput) setTvaMontantInput(String(initialAmt));
    updateFinancialsAndOrdres(formData, checked, initialAmt, applyIas, iasMontantInput);
  };

  const handleTvaAmountChange = (val) => {
    setTvaMontantInput(val);
    updateFinancialsAndOrdres(formData, true, val, applyIas, iasMontantInput);
  };

  const handleToggleIas = (checked) => {
    setApplyIas(checked);
    const initialAmt = checked ? (iasMontantInput || '0') : '0';
    if (checked && !iasMontantInput) setIasMontantInput(String(initialAmt));
    updateFinancialsAndOrdres(formData, applyTva, tvaMontantInput, checked, initialAmt);
  };

  const handleIasAmountChange = (val) => {
    setIasMontantInput(val);
    updateFinancialsAndOrdres(formData, applyTva, tvaMontantInput, true, val);
  };

  const handleBrutChange = (val) => {
    const brut = Math.max(0, Number(val) || 0);
    const updated = { ...formData, montant_brut: brut };
    updateFinancialsAndOrdres(updated, applyTva, tvaMontantInput, applyIas, iasMontantInput);
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  // Ordres Management
  const openAddOrdre = () => {
    setEditingOrdreIndex(null);
    setOrdreForm({
      num_ordre: `OP N° ${38 + formData.ordres.length}`,
      type_mouvement: 'Paiement fournisseur',
      mode_paiement: 'Virement',
      beneficiaire: formData.beneficiaire_nom || '',
      rib_compte: '',
      banque_agence: '',
      creance: 'Reste à payer',
      montant: 0,
      statut: 'À payer',
      observations: ''
    });
    setShowOrdreModal(true);
  };

  const openEditOrdre = (index) => {
    setEditingOrdreIndex(index);
    setOrdreForm({ ...formData.ordres[index] });
    setShowOrdreModal(true);
  };

  const handleDeleteOrdre = (index) => {
    const newOrdres = formData.ordres.filter((_, i) => i !== index);
    setFormData({ ...formData, ordres: newOrdres });
  };

  const handleSaveOrdre = () => {
    if (!ordreForm.beneficiaire || Number(ordreForm.montant) <= 0) {
      alert('Veuillez renseigner le bénéficiaire et un montant valide.');
      return;
    }

    let updatedOrdres = [...formData.ordres];
    if (editingOrdreIndex !== null) {
      updatedOrdres[editingOrdreIndex] = { ...ordreForm, montant: Number(ordreForm.montant) };
    } else {
      updatedOrdres.push({ ...ordreForm, montant: Number(ordreForm.montant) });
    }

    setFormData({ ...formData, ordres: updatedOrdres });
    setShowOrdreModal(false);
  };

  const totalOrdres = formData.ordres.reduce((sum, o) => sum + Number(o.montant || 0), 0);
  const isBalanced = Math.abs(totalOrdres - Number(formData.montant_brut)) < 0.05;

  const handleSubmit = async () => {
    if (!formData.reference || !formData.beneficiaire_nom || formData.montant_brut <= 0) {
      setErrorMsg('Veuillez compléter toutes les informations requises à l\'étape 1.');
      setStep(1);
      return;
    }

    if (formData.ordres.length === 0) {
      setErrorMsg('Au moins un ordre de paiement / mouvement doit être défini à l\'étape 2.');
      setStep(2);
      return;
    }

    try {
      setSubmitting(true);
      setErrorMsg('');
      const res = await api.post('/ordonnancements', formData);
      if (onSuccess) onSuccess(res.data.data);
      onClose();
    } catch (err) {
      console.error('Erreur création ordonnancement:', err);
      setErrorMsg(err.response?.data?.message || 'Erreur lors de la création de l\'ordonnancement.');
    } finally {
      setSubmitting(false);
    }
  };

  const formatDH = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0) + ' DH';

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex flex-col h-screen w-screen overflow-hidden animate-in fade-in duration-150">
      <div className="bg-white w-full h-full flex flex-col overflow-hidden">
        
        {/* Header */}
        <div className="px-6 md:px-8 py-4 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex items-center justify-between shrink-0 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-white/10 rounded-xl backdrop-blur-sm border border-white/10 shadow-inner">
              <WalletCards className="w-5 h-5 text-blue-200" />
            </div>
            <div>
              <h2 className="text-base font-extrabold tracking-tight">
                Nouvel ordonnancement
              </h2>
              <p className="text-xs text-blue-200 font-medium">
                Phase d'ordonnancement des dépenses validées
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition cursor-pointer"
            title="Fermer"
          >
            <X size={22} />
          </button>
        </div>

        {/* Wizard Steps Indicator */}
        <div className="bg-white border-b border-slate-200 px-6 md:px-8 py-3.5 shrink-0 shadow-xs">
          <div className="flex items-center justify-between max-w-4xl mx-auto">
            {[
              { num: 1, label: 'Informations générales' },
              { num: 2, label: 'Paiements à effectuer' },
              { num: 3, label: 'Documents' }
            ].map((s, idx) => (
              <React.Fragment key={s.num}>
                <div 
                  onClick={() => setStep(s.num)}
                  className={`flex items-center gap-2.5 cursor-pointer transition select-none ${
                    step === s.num 
                      ? 'text-blue-600 font-bold' 
                      : step > s.num 
                        ? 'text-blue-700 font-medium' 
                        : 'text-slate-400 hover:text-slate-600'
                  }`}
                >
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs ${
                    step === s.num 
                      ? 'bg-blue-600 text-white ring-4 ring-blue-50' 
                      : step > s.num 
                        ? 'bg-blue-100 text-blue-700 border border-blue-200' 
                        : 'bg-slate-100 text-slate-500 border border-slate-200'
                  }`}>
                    {step > s.num ? <Check size={15} /> : s.num}
                  </div>
                  <span className="text-xs font-semibold hidden sm:inline">{s.label}</span>
                </div>
                {idx < 2 && <div className={`flex-1 h-[2px] mx-3 transition-colors ${step > idx + 1 ? 'bg-blue-600' : 'bg-slate-200'}`} />}
              </React.Fragment>
            ))}
          </div>
        </div>

        {/* Error Alert */}
        {errorMsg && (
          <div className="mx-6 md:mx-8 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2 shrink-0">
            <AlertCircle size={16} className="text-rose-600 shrink-0" />
            <span>{errorMsg}</span>
          </div>
        )}

        {/* Modal Body */}
        <div className="p-6 md:p-8 overflow-y-auto flex-1 bg-slate-50/50">
          <div className="max-w-6xl mx-auto w-full">
          
          {/* STEP 1: INFORMATIONS GÉNÉRALES */}
          {step === 1 && (
            <div className="space-y-6">
              
              {/* Liquidation Selector Banner */}
              <div className="p-5 bg-blue-50/80 border border-blue-200 rounded-3xl space-y-2 shadow-xs">
                <label className="block text-xs font-bold text-blue-950 uppercase tracking-wider">
                  Sélectionner une liquidation disponible pour ordonnancement *
                </label>
                <div className="flex gap-3 items-center">
                  <select
                    className="w-full px-4 py-2.5 bg-white border border-blue-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold shadow-xs"
                    value={selectedDispoIndex}
                    onChange={handleSelectLiquidation}
                  >
                    <option value="">-- Choisir une liquidation dans le registre ({liquidationsDispo.length} disponible(s)) --</option>
                    {liquidationsDispo.map((item, i) => (
                      <option key={i} value={i}>
                        {item.num_liquidation} | {item.reference} | {item.beneficiaire} ({formatDH(item.montant_brut)})
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    onClick={fetchDisponibles}
                    className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-sm whitespace-nowrap cursor-pointer"
                  >
                    Actualiser
                  </button>
                </div>
                <p className="text-[11px] text-blue-700 flex items-center gap-1.5 mt-1 font-medium">
                  <Info size={14} />
                  La sélection remplit automatiquement l'ensemble des données administratives et financières.
                </p>
              </div>

              {/* 2-Column Form */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {/* Left Column : Informations générales */}
                <div className="space-y-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                  <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <Building size={16} className="text-blue-600" />
                    Informations générales
                  </h3>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">N° Liquidation *</label>
                      <input
                        type="text"
                        name="num_liquidation"
                        value={formData.num_liquidation || (selectedDispoIndex !== '' ? liquidationsDispo[selectedDispoIndex]?.num_liquidation : '')}
                        readOnly
                        placeholder="Sélectionnez une liquidation"
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Date d'ordonnancement *</label>
                      <input
                        type="date"
                        name="date_ordonnancement"
                        value={formData.date_ordonnancement}
                        onChange={handleInputChange}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">Référence (Marché / BC / Convention) *</label>
                    <input
                      type="text"
                      name="reference"
                      value={formData.reference}
                      onChange={handleInputChange}
                      placeholder="ex: M-10-2026-DRCA-RSK"
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">Bénéficiaire *</label>
                    <input
                      type="text"
                      name="beneficiaire_nom"
                      value={formData.beneficiaire_nom}
                      onChange={handleInputChange}
                      placeholder="Nom ou Raison Sociale"
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Budget *</label>
                      <select
                        name="budget_type"
                        value={formData.budget_type}
                        onChange={handleInputChange}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      >
                        <option value="Investissement">Investissement</option>
                        <option value="Fonctionnement">Fonctionnement</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Procédure</label>
                      <select
                        name="type_procedure"
                        value={formData.type_procedure}
                        onChange={handleInputChange}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      >
                        <option value="Bon de commande">Bon de commande</option>
                        <option value="Marché">Marché</option>
                        <option value="Convention">Convention</option>
                      </select>
                    </div>
                  </div>

                  {/* Imputation Budgétaire */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                      Imputation budgétaire
                    </label>
                    <div className="grid grid-cols-4 gap-2">
                      <div>
                        <span className="text-[10px] text-slate-500 font-bold block mb-0.5 text-center">ART</span>
                        <input
                          type="text"
                          name="article"
                          value={formData.article}
                          onChange={handleInputChange}
                          className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs font-bold text-slate-800"
                        />
                      </div>
                      <div>
                        <span className="text-[10px] text-slate-500 font-bold block mb-0.5 text-center">PAR</span>
                        <input
                          type="text"
                          name="paragraphe"
                          value={formData.paragraphe}
                          onChange={handleInputChange}
                          className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs font-bold text-slate-800"
                        />
                      </div>
                      <div>
                        <span className="text-[10px] text-slate-500 font-bold block mb-0.5 text-center">LIG</span>
                        <input
                          type="text"
                          name="ligne"
                          value={formData.ligne}
                          onChange={handleInputChange}
                          className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs font-bold text-slate-800"
                        />
                      </div>
                      <div>
                        <span className="text-[10px] text-slate-500 font-bold block mb-0.5 text-center">S/LIG</span>
                        <input
                          type="text"
                          name="sous_ligne"
                          value={formData.sous_ligne}
                          onChange={handleInputChange}
                          className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs font-bold text-slate-800"
                        />
                      </div>
                    </div>
                  </div>

                </div>

                {/* Right Column : Montants & Intitulé */}
                <div className="space-y-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-xs flex flex-col justify-between">
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                      <Receipt size={16} className="text-blue-600" />
                      Montants de la liquidation
                    </h3>

                    <div className="space-y-4 mt-3 text-xs">
                      <div>
                        <label className="block text-slate-700 font-bold mb-1">Montant TTC (Montant Brut) *</label>
                        <div className="relative">
                          <input
                            type="number"
                            step="0.01"
                            name="montant_brut"
                            value={formData.montant_brut}
                            onChange={(e) => handleBrutChange(e.target.value)}
                            className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          />
                          <span className="absolute right-3.5 top-2.5 font-bold text-slate-400">DH</span>
                        </div>
                      </div>

                      {/* SECTION RETENUES À LA SOURCE (NON SYSTÉMATIQUES) */}
                      <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                        <div className="flex items-center justify-between pb-1 border-b border-slate-200">
                          <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Retenues à la source
                          </span>
                          <span className="text-[10px] text-slate-500 font-medium italic">
                            Non systématiques (sélectionnez si applicable)
                          </span>
                        </div>

                        {/* 1. Case à cocher TVA */}
                        <div className="space-y-2">
                          <label className="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                              type="checkbox"
                              checked={applyTva}
                              onChange={(e) => handleToggleTva(e.target.checked)}
                              className="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer"
                            />
                            <span className={`text-xs font-bold ${applyTva ? 'text-blue-700' : 'text-slate-700'}`}>
                              Appliquer une retenue TVA
                            </span>
                          </label>

                          {/* Dynamic field TVA if checked */}
                          {applyTva && (
                            <div className="pl-6 pt-1 animate-in fade-in slide-in-from-top-1 duration-150">
                              <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                                Montant de la retenue TVA *
                              </label>
                              <div className="relative max-w-xs">
                                <input
                                  type="number"
                                  step="0.01"
                                  value={tvaMontantInput}
                                  onChange={(e) => handleTvaAmountChange(e.target.value)}
                                  placeholder="0.00"
                                  className="w-full px-3 py-1.5 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700 focus:ring-2 focus:ring-rose-400"
                                />
                                <span className="absolute right-3 top-1.5 text-xs font-bold text-rose-400">DH</span>
                              </div>
                              <p className="text-[10px] text-slate-500 mt-1 font-medium">
                                Sera mandatée au Trésor Public via un Ordre de Virement (OV).
                              </p>
                            </div>
                          )}
                        </div>

                        {/* 2. Case à cocher IS */}
                        <div className="space-y-2 pt-2 border-t border-slate-200/60">
                          <label className="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                              type="checkbox"
                              checked={applyIas}
                              onChange={(e) => handleToggleIas(e.target.checked)}
                              className="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer"
                            />
                            <span className={`text-xs font-bold ${applyIas ? 'text-blue-700' : 'text-slate-700'}`}>
                              Appliquer une retenue IS
                            </span>
                          </label>

                          {/* Dynamic field IS if checked */}
                          {applyIas && (
                            <div className="pl-6 pt-1 animate-in fade-in slide-in-from-top-1 duration-150">
                              <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                                Montant de la retenue IS *
                              </label>
                              <div className="relative max-w-xs">
                                <input
                                  type="number"
                                  step="0.01"
                                  value={iasMontantInput}
                                  onChange={(e) => handleIasAmountChange(e.target.value)}
                                  placeholder="0.00"
                                  className="w-full px-3 py-1.5 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700 focus:ring-2 focus:ring-rose-400"
                                />
                                <span className="absolute right-3 top-1.5 text-xs font-bold text-rose-400">DH</span>
                              </div>
                              <p className="text-[10px] text-slate-500 mt-1 font-medium">
                                Sera imputée au Trésor Public via un Ordre d'Imputation (OI).
                              </p>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Net à payer highlight card */}
                      <div className="p-4 bg-blue-50/90 border-2 border-blue-200 rounded-2xl">
                        <div className="text-[11px] font-bold text-blue-950 uppercase tracking-wider flex items-center justify-between">
                          <span>Net à payer au bénéficiaire</span>
                          {(!applyTva && !applyIas) && (
                            <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">
                              100% Brut (Aucune retenue)
                            </span>
                          )}
                        </div>
                        <div className="text-2xl font-black text-blue-700 mt-1">
                          {formatDH(formData.net_a_payer)}
                        </div>
                        <div className="text-[11px] text-blue-700 mt-1 font-medium">
                          {(!applyTva && !applyIas) 
                            ? 'Net à payer = Montant brut' 
                            : `Formule : Montant brut (${formatDH(formData.montant_brut)})${applyTva ? ` - TVA (${formatDH(formData.retenue_tva)})` : ''}${applyIas ? ` - IS (${formatDH(formData.retenue_ias)})` : ''}`}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Intitulé de la dépense */}
                  <div className="mt-4">
                    <label className="block text-xs font-semibold text-slate-700 mb-1">Intitulé / Objet de la dépense *</label>
                    <textarea
                      rows={3}
                      name="intitule_depense"
                      value={formData.intitule_depense}
                      onChange={handleInputChange}
                      placeholder="Description exacte de la dépense..."
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                    />
                  </div>

                </div>

              </div>

            </div>
          )}

          {/* STEP 2: PAIEMENTS À EFFECTUER / ORDRES MULTIPLES */}
          {step === 2 && (
            <div className="space-y-5">
              <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    Liste des paiements / ordres générés automatiquement
                  </h3>
                  <p className="text-xs text-slate-500 font-medium">
                    Chaque ordonnancement peut générer plusieurs ordres de paiement, virement ou imputation.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={openAddOrdre}
                  className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer"
                >
                  <Plus size={16} />
                  + Ajouter un paiement
                </button>
              </div>

              {/* Table of generated orders */}
              <div className="border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
                <table className="w-full text-sm text-left">
                  <thead className="bg-slate-50 text-slate-700 font-extrabold border-b border-slate-200 uppercase tracking-wider text-xs">
                    <tr>
                      <th className="px-4 py-3.5">#</th>
                      <th className="px-5 py-3.5">Type de paiement</th>
                      <th className="px-5 py-3.5">Bénéficiaire</th>
                      <th className="px-5 py-3.5">Créance</th>
                      <th className="px-5 py-3.5 text-right">Montant (DH)</th>
                      <th className="px-4 py-3.5 text-center">Statut</th>
                      <th className="px-4 py-3.5 text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {formData.ordres.map((ord, idx) => (
                      <tr key={idx} className="hover:bg-blue-50/40 transition">
                        <td className="px-4 py-3.5 font-bold text-slate-400">{idx + 1}</td>
                        <td className="px-5 py-3.5 font-semibold text-slate-900">
                          <div>{ord.type_mouvement}</div>
                          {ord.num_ordre && <span className="text-xs text-slate-400 font-mono">{ord.num_ordre}</span>}
                        </td>
                        <td className="px-5 py-3.5 text-slate-800 font-medium">{ord.beneficiaire}</td>
                        <td className="px-5 py-3.5 text-slate-600 font-medium">{ord.creance}</td>
                        <td className="px-5 py-3.5 text-right font-black text-blue-700">
                          {formatDH(ord.montant)}
                        </td>
                        <td className="px-4 py-3.5 text-center">
                          <span className="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-semibold">
                            {ord.statut || 'À payer'}
                          </span>
                        </td>
                        <td className="px-4 py-3.5 text-center">
                          <div className="flex items-center justify-center gap-2">
                            <button
                              type="button"
                              onClick={() => openEditOrdre(idx)}
                              className="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                              title="Modifier"
                            >
                              <Edit2 size={16} />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDeleteOrdre(idx)}
                              className="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                              title="Supprimer"
                            >
                              <Trash2 size={16} />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                    {formData.ordres.length === 0 && (
                      <tr>
                        <td colSpan={7} className="px-6 py-12 text-center text-slate-400 italic">
                          Aucun ordre de paiement configuré. Cliquez sur "+ Ajouter un paiement".
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {/* Balance Summary Card */}
              <div className={`p-4 rounded-2xl border flex flex-col sm:flex-row justify-between items-center gap-3 ${
                isBalanced 
                  ? 'bg-blue-50/80 border-blue-200 text-blue-950' 
                  : 'bg-amber-50 border-amber-200 text-amber-950'
              }`}>
                <div className="flex items-center gap-2">
                  {isBalanced ? (
                    <CheckCircle2 className="w-5 h-5 text-blue-600" />
                  ) : (
                    <AlertCircle className="w-5 h-5 text-amber-600" />
                  )}
                  <span className="text-xs font-bold">
                    {isBalanced 
                      ? 'TOTAL DES MOUVEMENTS = MONTANT BRUT (Équilibré)' 
                      : `Attention : La somme des mouvements (${formatDH(totalOrdres)}) diffère du montant brut (${formatDH(formData.montant_brut)})`
                    }
                  </span>
                </div>
                <div className="text-sm font-black tracking-wide">
                  Total : {formatDH(totalOrdres)}
                </div>
              </div>

            </div>
          )}

          {/* STEP 3: DOCUMENTS DU DOSSIER */}
          {step === 3 && (
            <div className="space-y-4">
              <div>
                <h3 className="text-sm font-bold text-slate-900">
                  Documents du dossier d'ordonnancement
                </h3>
                <p className="text-xs text-slate-500 font-medium">
                  Documents officiels générés avec options d'aperçu, téléchargement PDF et impression.
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                
                {/* 1. État de liquidation */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-blue-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-blue-100 text-blue-700 rounded-xl">
                      <FileText size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">État de liquidation</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('etat_liquidation')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('etat_liquidation')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 2. Ordre de paiement (OP) */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-blue-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-blue-100 text-blue-700 rounded-xl">
                      <FileCheck size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">Ordre de paiement (OP)</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 2. Ordre de virement (OV) */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-blue-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-blue-100 text-blue-700 rounded-xl">
                      <Landmark size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">Ordre de virement (OV)</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('ov')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('ov')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 3. Ordre de paiement (OP - RAS IS) */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-amber-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-amber-100 text-amber-700 rounded-xl">
                      <FileCheck size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">Ordre de paiement (OP - RAS IS)</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op_ras_is')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op_ras_is')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 4. Ordre de paiement (OP - RAS TVA) */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-emerald-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-emerald-100 text-emerald-700 rounded-xl">
                      <FileCheck size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">Ordre de paiement (OP - RAS TVA)</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op_ras_tva')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('op_ras_tva')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 5. Ordre d'imputation (OI) */}
                <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between space-y-4 hover:border-blue-300 transition">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-blue-100 text-blue-700 rounded-xl">
                      <Layers size={22} />
                    </div>
                    <div>
                      <h4 className="font-bold text-slate-900 text-sm">Ordre d'imputation (OI)</h4>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-slate-200">
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('oi')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      type="button"
                      onClick={() => handleOpenPreview('oi')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

              </div>
            </div>
          )}

          </div>
        </div>

        {/* Modal Footer */}
        <div className="px-6 md:px-8 py-4 bg-white border-t border-slate-200 flex justify-between items-center shrink-0 shadow-lg">
          {step > 1 ? (
            <button
              type="button"
              onClick={() => setStep(step - 1)}
              className="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
            >
              <ArrowLeft size={16} />
              Précédent
            </button>
          ) : (
            <button
              type="button"
              onClick={onClose}
              className="px-5 py-2.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
            >
              Annuler
            </button>
          )}

          {step < 3 ? (
            <button
              type="button"
              onClick={() => setStep(step + 1)}
              className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer"
            >
              Suivant
              <ArrowRight size={16} />
            </button>
          ) : (
            <button
              type="button"
              onClick={handleSubmit}
              disabled={submitting}
              className="inline-flex items-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition disabled:opacity-50 cursor-pointer"
            >
              {submitting ? 'Création en cours...' : 'Créer l\'ordonnancement'}
              <Check size={16} />
            </button>
          )}
        </div>

      </div>

      {/* SUB-MODAL : CREATION / MODIFICATION D'UN PAIEMENT */}
      {showOrdreModal && (
        <div className="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
          <div className="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
            <div className="px-6 py-4 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex justify-between items-center">
              <h4 className="text-xs font-bold tracking-wide uppercase">
                Création / Modification d'un paiement
              </h4>
              <button
                type="button"
                onClick={() => setShowOrdreModal(false)}
                className="text-white/80 hover:text-white cursor-pointer"
              >
                <X size={18} />
              </button>
            </div>

            <div className="p-6 space-y-3.5 text-xs">
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block text-slate-700 font-bold mb-1">N° Ordre / OP *</label>
                  <input
                    type="text"
                    value={ordreForm.num_ordre || ''}
                    onChange={(e) => setOrdreForm({ ...ordreForm, num_ordre: e.target.value })}
                    placeholder="ex: OP-001..."
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Type de paiement *</label>
                  <select
                    value={ordreForm.type_mouvement}
                    onChange={(e) => setOrdreForm({ ...ordreForm, type_mouvement: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                  >
                    <option value="Paiement fournisseur">Paiement fournisseur</option>
                    <option value="Retenue à la source TVA">Retenue à la source TVA</option>
                    <option value="Retenue à la source IAS/IAC">Retenue à la source IAS/IAC</option>
                    <option value="Ordre de virement (OV)">Ordre de virement (OV)</option>
                    <option value="Ordre d'imputation (OI)">Ordre d'imputation (OI)</option>
                    <option value="Autre mouvement">Autre mouvement</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Mode de paiement *</label>
                  <select
                    value={ordreForm.mode_paiement}
                    onChange={(e) => setOrdreForm({ ...ordreForm, mode_paiement: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                  >
                    <option value="Virement">Virement</option>
                    <option value="Chèque">Chèque</option>
                    <option value="Ordre d'imputation">Ordre d'imputation</option>
                    <option value="Prélèvement">Prélèvement</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-700 font-bold mb-1">Bénéficiaire *</label>
                <input
                  type="text"
                  value={ordreForm.beneficiaire}
                  onChange={(e) => setOrdreForm({ ...ordreForm, beneficiaire: e.target.value })}
                  placeholder="ex: COMPTOIR AGRICOLE... ou TRÉSOR PUBLIC"
                  className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-slate-700 font-bold mb-1">RIB / Compte *</label>
                <input
                  type="text"
                  value={ordreForm.rib_compte}
                  onChange={(e) => setOrdreForm({ ...ordreForm, rib_compte: e.target.value })}
                  placeholder="310 810 100 002 470 105 200 152"
                  className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Créance *</label>
                  <select
                    value={ordreForm.creance}
                    onChange={(e) => setOrdreForm({ ...ordreForm, creance: e.target.value })}
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="Reste à payer">Reste à payer</option>
                    <option value="C.Neufs">C.Neufs</option>
                    <option value="Reports">Reports</option>
                    <option value="Retenue à la source">Retenue à la source</option>
                    <option value="Crédit Consolidés">Crédit Consolidés</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Montant (DH) *</label>
                  <input
                    type="number"
                    step="0.01"
                    value={ordreForm.montant}
                    onChange={(e) => setOrdreForm({ ...ordreForm, montant: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-slate-700 font-bold mb-1">Observations</label>
                <textarea
                  rows={2}
                  value={ordreForm.observations}
                  onChange={(e) => setOrdreForm({ ...ordreForm, observations: e.target.value })}
                  placeholder="Observations sur le paiement..."
                  className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2.5">
              <button
                type="button"
                onClick={() => setShowOrdreModal(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition cursor-pointer"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={handleSaveOrdre}
                className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md hover:shadow-lg transition cursor-pointer"
              >
                Enregistrer
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Document Preview Modal */}
      <DocumentPreviewModal
        isOpen={previewOpen}
        onClose={() => {
          setPreviewOpen(false);
          setPreviewOrdre(null);
        }}
        ordonnancement={{
          ...formData,
          num_op: formData.ordres?.[0]?.num_ordre || 'OP N° 38',
          liquidation: selectedDispoIndex !== '' ? liquidationsDispo[selectedDispoIndex] : null,
          retenue_tva: applyTva ? Number(tvaMontantInput) : 0,
          retenue_ias: applyIas ? Number(iasMontantInput) : 0,
          net_a_payer: formData.net_a_payer,
          montant_brut: formData.montant_brut,
        }}
        ordre={previewOrdre}
        docType={previewDocType}
      />

    </div>
  );
}
