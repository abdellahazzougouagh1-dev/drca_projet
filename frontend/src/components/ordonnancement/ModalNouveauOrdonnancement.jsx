import React, { useState, useEffect, useMemo } from 'react';
import {
  X, Check, ArrowRight, ArrowLeft, Plus, Edit2, Trash2,
  AlertCircle, CheckCircle2, FileText, Landmark, Wallet, FileCheck,
  Calendar, Building, Receipt, Layers, Info, WalletCards, Eye, Download,
  Loader2, ExternalLink, Handshake
} from 'lucide-react';
import api from '../../api/axios';
import DocumentPreviewModal from './DocumentPreviewModal';
import OrdreDocumentsModal from './OrdreDocumentsModal';
import { resolveOrdreDocument, resolveOrdreDocumentsList } from '../../utils/ordonnancementDocs';

export default function ModalNouveauOrdonnancement({ isOpen, onClose, onSuccess, defaultProcedure = null }) {
  const [step, setStep] = useState(1); // 1: Info, 2: Paiements, 3: Documents, 4: Recap
  const [loadingDispos, setLoadingDispos] = useState(false);
  const [liquidationsDispo, setLiquidationsDispo] = useState([]);
  
  const normalizeProcedure = (p) => {
    if (!p) return 'TOUS';
    const low = String(p).toLowerCase();
    if (low.includes('march') || low.includes('aoo') || low.includes('appel')) return 'Marché';
    if (low.includes('bon') || low.includes('bc')) return 'Bon de commande';
    if (low.includes('conv')) return 'Convention';
    return 'TOUS';
  };

  const [modalProcedureFilter, setModalProcedureFilter] = useState(() => normalizeProcedure(defaultProcedure));
  const [selectedDispoKey, setSelectedDispoKey] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');
  const [createdDossierId, setCreatedDossierId] = useState(null);
  const [decisionAccepted, setDecisionAccepted] = useState(false);

  // Retenues à la source state (NON SYSTÉMATIQUES - par défaut false)
  const [applyTva, setApplyTva] = useState(false);
  const [applyIas, setApplyIas] = useState(false);
  const [tvaMontantInput, setTvaMontantInput] = useState('');
  const [iasMontantInput, setIasMontantInput] = useState('');

  // Document Preview Modal State
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewDocType, setPreviewDocType] = useState('op');
  const [previewOrdre, setPreviewOrdre] = useState(null);

  // Ordre Documents Modal State (Specific to single selected OP)
  const [docsModalOpen, setDocsModalOpen] = useState(false);
  const [docsModalOrdre, setDocsModalOrdre] = useState(null);
  const [downloadingDocKey, setDownloadingDocKey] = useState(null);

  const handleOpenPreview = (type, ordre = null) => {
    setPreviewDocType(type);
    setPreviewOrdre(ordre);
    setPreviewOpen(true);
  };

  const handleDownloadDirect = async (docType, ordre = null) => {
    const docKey = `${docType}-${ordre?.id || 'dossier'}`;
    try {
      setDownloadingDocKey(docKey);
      const ordId = createdDossierId || formData.id;
      if (!ordId) {
        handleOpenPreview(docType, ordre);
        return;
      }
      const endpoint = `/ordonnancements/${ordId}/documents/${docType}${ordre?.id ? `/${ordre.id}` : ''}`;
      const res = await api.get(endpoint, { responseType: 'blob' });
      const mimeType = res.data.type || res.headers['content-type'] || 'application/pdf';
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: mimeType }));
      const link = document.createElement('a');
      link.href = fileUrl;
      const docLabel = docType === 'etat_liquidation'
        ? 'Etat_Liquidation'
        : (docType === 'op_ras_iac' || docType === 'op_ras_is'
          ? 'OP_RAS_IAC'
          : (docType === 'op_ras_tva' ? 'OP_RAS_TVA' : docType.toUpperCase()));
      const safeNum = String(ordre?.num_ordre || formData.num_ordonnancement || ordId).replace(/[^a-zA-Z0-9_\-]/g, '_');
      link.setAttribute('download', `${docLabel}_${safeNum}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(fileUrl);
    } catch (err) {
      console.error('Erreur téléchargement document:', err);
      handleOpenPreview(docType, ordre);
    } finally {
      setDownloadingDocKey(null);
    }
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
      setModalProcedureFilter(normalizeProcedure(defaultProcedure));
      fetchDisponibles();
      setStep(1);
      setErrorMsg('');
      setSuccessMsg('');
      setCreatedDossierId(null);
      setDecisionAccepted(false);
      setApplyTva(false);
      setApplyIas(false);
      setTvaMontantInput('');
      setIasMontantInput('');
      setSelectedDispoKey('');
    }
  }, [isOpen, defaultProcedure]);

  const getLiquidationProcedure = (item) => {
    if (!item) return 'Marché';
    const proc = String(item.type_procedure || '').toLowerCase();
    const ref = String(item.reference || '').toUpperCase();
    const numLiq = String(item.num_liquidation || '').toUpperCase();
    const source = String(item.type_source || '').toLowerCase();

    if (proc.includes('conv') || ref.startsWith('CONV')) {
      return 'Convention';
    }
    if (proc.includes('bon') || proc.includes('bc') || ref.startsWith('BC') || numLiq.includes('LIQ-BC') || source.includes('consultation')) {
      return 'Bon de commande';
    }
    return 'Marché';
  };

  const getLiquidationKey = (item) => {
    if (!item) return '';
    return `${item.num_liquidation || ''}|${item.reference || ''}|${item.liquidation_id || item.consultation_id || ''}`;
  };

  const aooCount = useMemo(() => {
    return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Marché').length;
  }, [liquidationsDispo]);

  const bcCount = useMemo(() => {
    return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Bon de commande').length;
  }, [liquidationsDispo]);

  const convCount = useMemo(() => {
    return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Convention').length;
  }, [liquidationsDispo]);

  const filteredLiquidations = useMemo(() => {
    if (modalProcedureFilter === 'Marché') {
      return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Marché');
    }
    if (modalProcedureFilter === 'Bon de commande') {
      return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Bon de commande');
    }
    if (modalProcedureFilter === 'Convention') {
      return liquidationsDispo.filter((item) => getLiquidationProcedure(item) === 'Convention');
    }
    return liquidationsDispo;
  }, [liquidationsDispo, modalProcedureFilter]);

  const selectedDispoItem = useMemo(() => {
    if (!selectedDispoKey) return null;
    return liquidationsDispo.find((it) => getLiquidationKey(it) === selectedDispoKey) || null;
  }, [liquidationsDispo, selectedDispoKey]);

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
      num_ordre: 'OP N°38',
      type_mouvement: 'Paiement fournisseur',
      mode_paiement: 'Virement',
      beneficiaire: baseData.beneficiaire_nom || 'Bénéficiaire',
      rib_compte: baseData.rib_compte || '',
      banque_agence: baseData.banque_agence || '',
      creance: baseData.creance || 'Crédits nouveaux',
      montant: net,
      statut: 'À payer',
      observations: `Paiement fournisseur du montant net (${formatDH(net)})`
    });

    // 2. Retenue TVA (SEULEMENT si sélectionnée par l'utilisateur et > 0)
    if (isTva && validTva > 0) {
      newOrdres.push({
        num_ordre: 'OV N°39',
        type_mouvement: 'Retenue à la source TVA',
        mode_paiement: 'Virement',
        beneficiaire: 'TRÉSOR PUBLIC',
        rib_compte: '225 330 000 706 918 851 021 328',
        banque_agence: 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
        creance: 'Retenue TVA',
        montant: validTva,
        statut: 'À payer',
        observations: 'Retenue à la source TVA au profit du Trésor Public'
      });
    }

    // 3. Retenue IAC (SEULEMENT si sélectionnée par l'utilisateur et > 0)
    if (isIas && validIas > 0) {
      newOrdres.push({
        num_ordre: `OP N°${38 + newOrdres.length}`,
        type_mouvement: 'Retenue à la source IAC',
        mode_paiement: 'Virement',
        beneficiaire: 'TRÉSOR PUBLIC',
        rib_compte: '225 330 000 706 918 851 021 328',
        banque_agence: 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
        creance: 'Retenue IAC',
        montant: validIas,
        statut: 'À payer',
        observations: 'Retenue à la source IAC au profit du Trésor Public'
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
    const key = e.target.value;
    setSelectedDispoKey(key);
    if (!key) return;

    const item = liquidationsDispo.find((it) => getLiquidationKey(it) === key);
    if (!item) return;

    const brut = Number(item.montant_brut || 0);

    // Par défaut, AUCUNE retenue n'est sélectionnée
    setApplyTva(false);
    setApplyIas(false);
    setTvaMontantInput(item.retenue_tva ? String(item.retenue_tva) : '');
    setIasMontantInput(item.retenue_ias ? String(item.retenue_ias) : '');

    const detectedProc = getLiquidationProcedure(item);

    const base = {
      ...formData,
      liquidation_id: item.liquidation_id,
      marche_id: item.marche_id,
      consultation_id: item.consultation_id,
      fournisseur_id: item.fournisseur_id,
      notification_ligne_id: item.notification_ligne_id,
      type_procedure: item.type_procedure || detectedProc,
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
    const target = formData.ordres[index];
    if (target?.statut === 'Rejeté') {
      alert('Impossible de supprimer un ordre déjà rejeté.');
      return;
    }
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

  // Seuls les ordres actifs (non rejetés et non annulés) comptent dans le montant équilibré
  const activeOrdres = formData.ordres.filter((o) => o.statut !== 'Rejeté' && o.statut !== 'Annulé');
  const totalOrdres = activeOrdres.reduce((sum, o) => sum + Number(o.montant || 0), 0);
  const isBalanced = Math.abs(totalOrdres - Number(formData.montant_brut)) < 0.05;

  const handleSubmit = async (decision = null, specificOrdreId = null) => {
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
      setSuccessMsg('');
      let dossier;
      if (createdDossierId) {
        const current = await api.get(`/ordonnancements/${createdDossierId}`);
        dossier = current.data.data;
      } else {
        const res = await api.post('/ordonnancements', formData);
        dossier = res.data.data;
        setCreatedDossierId(dossier.id);
      }

      if (decision) {
        // Cibler l'ordre spécifique demandé ou le dernier ordre actif avec statut « À payer »
        const targetOrdre = specificOrdreId
          ? (dossier.ordres?.find((o) => o.id === specificOrdreId) || formData.ordres.find((o) => o.id === specificOrdreId))
          : ([...(dossier.ordres || [])].reverse().find((o) => o.statut === 'À payer')
            || [...(formData.ordres || [])].reverse().find((o) => o.statut === 'À payer')
            || dossier.ordres?.[0]
            || formData.ordres[0]);

        const statusResponse = await api.patch(`/ordonnancements/${dossier.id}/statut`, {
          statut: decision,
          ordre_id: targetOrdre?.id || null,
          motif_rejet: decision === 'Rejeté' ? 'Refusé par le fondé de pouvoir.' : null,
          date_paiement: decision === 'Payé' ? new Date().toISOString().split('T')[0] : null,
        });
        dossier = statusResponse.data.data;
      }

      if (decision === 'Rejeté') {
        const nouvelOrdre = [...(dossier.ordres || [])].reverse().find((o) => o.statut === 'À payer');
        setFormData((previous) => ({
          ...previous,
          statut: dossier.statut,
          ordres: dossier.ordres || previous.ordres,
        }));
        setSuccessMsg(`Ordre rejeté. Le nouvel ordre (${nouvelOrdre?.num_ordre || 'OP'}) a été généré avec succès avec le statut « À payer ». Vous pouvez l'accepter ou le refuser.`);
        return;
      }

      if (decision === 'Payé') {
        setDecisionAccepted(true);
        setFormData((previous) => ({
          ...previous,
          statut: dossier.statut,
          ordres: dossier.ordres || previous.ordres,
        }));
        setSuccessMsg('Ordre accepté et validé par le fondé de pouvoir. Vous pouvez maintenant poursuivre avec les documents.');
        return;
      }

      if (onSuccess) onSuccess(dossier);
      onClose();
    } catch (err) {
      console.error('Erreur ordonnancement:', err);
      const firstErr = Object.values(err.response?.data?.errors || {}).flat().find(Boolean);
      setErrorMsg(firstErr || err.response?.data?.message || err.response?.data?.error || 'Erreur lors du traitement de la décision.');
    } finally {
      setSubmitting(false);
    }
  };

  const createFirstOrderAndContinue = async () => {
    if (!formData.reference || !formData.beneficiaire_nom || Number(formData.montant_brut) <= 0) {
      setErrorMsg('Veuillez sélectionner une liquidation et compléter les informations générales obligatoires.');
      return;
    }

    try {
      setSubmitting(true);
      setErrorMsg('');
      setSuccessMsg('');

      if (createdDossierId) {
        await api.put(`/ordonnancements/${createdDossierId}`, formData);
        setStep(2);
        return;
      }

      const res = await api.post('/ordonnancements', formData);
      setCreatedDossierId(res.data.data.id);
      setFormData((previous) => ({
        ...previous,
        ordres: res.data.data.ordres || previous.ordres,
        statut: res.data.data.statut,
      }));
      setStep(2);
    } catch (err) {
      console.error('Erreur création du premier ordre:', err);
      const firstErr = Object.values(err.response?.data?.errors || {}).flat().find(Boolean);
      setErrorMsg(firstErr || err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la création du premier ordre de paiement.');
    } finally {
      setSubmitting(false);
    }
  };

  const finishWorkflow = async () => {
    if (!createdDossierId) {
      setErrorMsg('Aucun ordonnancement créé. Revenez à l’étape précédente.');
      return;
    }

    try {
      setSubmitting(true);
      const res = await api.get(`/ordonnancements/${createdDossierId}`);
      if (onSuccess) onSuccess(res.data.data);
      onClose();
    } catch (err) {
      console.error('Erreur lors du retour au registre:', err);
      setErrorMsg(err.response?.data?.message || 'Impossible de terminer le traitement.');
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
        <div className="bg-white border-b border-slate-200 px-6 md:px-10 py-3.5 shrink-0 shadow-xs">
          <div className="flex items-center justify-between w-full">
            {[
              { num: 1, label: 'Informations générales' },
              { num: 2, label: 'Paiements à effectuer' },
              { num: 3, label: 'Documents' }
            ].map((s, idx) => (
              <React.Fragment key={s.num}>
                <div
                  onClick={() => setStep(s.num)}
                  className={`flex items-center gap-2.5 cursor-pointer transition select-none ${step === s.num
                    ? 'text-blue-600 font-bold'
                    : step > s.num
                      ? 'text-blue-700 font-medium'
                      : 'text-slate-400 hover:text-slate-600'
                    }`}
                >
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs ${step === s.num
                    ? 'bg-blue-600 text-white ring-4 ring-blue-50'
                    : step > s.num
                      ? 'bg-blue-100 text-blue-700 border border-blue-200'
                      : 'bg-slate-100 text-slate-500 border border-slate-200'
                    }`}>
                    {step > s.num ? <Check size={15} /> : s.num}
                  </div>
                  <span className="text-xs font-semibold hidden sm:inline">{s.label}</span>
                </div>
                {idx < 2 && <div className={`flex-1 h-[2px] mx-4 md:mx-8 transition-colors ${step > idx + 1 ? 'bg-blue-600' : 'bg-slate-200'}`} />}
              </React.Fragment>
            ))}
          </div>
        </div>

        {/* Error Alert */}
        {errorMsg && (
          <div className="mx-6 md:mx-10 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2 shrink-0">
            <AlertCircle size={16} className="text-rose-600 shrink-0" />
            <span>{errorMsg}</span>
          </div>
        )}

        {/* Success / Info Alert */}
        {successMsg && (
          <div className="mx-6 md:mx-10 mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-2 shrink-0">
            <CheckCircle2 size={16} className="text-emerald-600 shrink-0" />
            <span>{successMsg}</span>
          </div>
        )}

        {/* Modal Body */}
        <div className="p-6 md:p-10 overflow-y-auto flex-1 bg-slate-50/50">
          <div className="w-full">

            {/* STEP 1: INFORMATIONS GÉNÉRALES */}
            {step === 1 && (
              <div className="space-y-6">

                {/* Liquidation Selector Banner */}
                <div className="p-5 bg-blue-50/80 border border-blue-200 rounded-3xl space-y-3 shadow-xs">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <label className="block text-xs font-bold text-blue-950 uppercase tracking-wider">
                      Sélectionner une liquidation disponible pour ordonnancement *
                    </label>

                    {/* Filtres par procédure (Appel d'offres / Bon de commande / Convention) */}
                    <div className="flex flex-wrap items-center gap-1.5 p-1 bg-white rounded-xl border border-blue-200 shadow-2xs">
                      <button
                        type="button"
                        onClick={() => {
                          setModalProcedureFilter('Marché');
                          setSelectedDispoKey('');
                        }}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer ${
                          modalProcedureFilter === 'Marché'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'text-blue-800 hover:bg-blue-50'
                        }`}
                      >
                        <FileText size={13} />
                        <span>Appels d’offres ({aooCount})</span>
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          setModalProcedureFilter('Bon de commande');
                          setSelectedDispoKey('');
                        }}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer ${
                          modalProcedureFilter === 'Bon de commande'
                            ? 'bg-[#087F5B] text-white shadow-xs'
                            : 'text-[#087F5B] hover:bg-[#E7F5EF]'
                        }`}
                      >
                        <Receipt size={13} />
                        <span>Bons de commande ({bcCount})</span>
                      </button>

                      {convCount > 0 && (
                        <button
                          type="button"
                          onClick={() => {
                            setModalProcedureFilter('Convention');
                            setSelectedDispoKey('');
                          }}
                          className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer ${
                            modalProcedureFilter === 'Convention'
                              ? 'bg-emerald-700 text-white shadow-xs'
                              : 'text-emerald-800 hover:bg-emerald-50'
                          }`}
                        >
                          <Handshake size={13} />
                          <span>Conventions ({convCount})</span>
                        </button>
                      )}

                      <button
                        type="button"
                        onClick={() => {
                          setModalProcedureFilter('TOUS');
                        }}
                        className={`px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer ${
                          modalProcedureFilter === 'TOUS'
                            ? 'bg-slate-800 text-white shadow-xs'
                            : 'text-slate-600 hover:bg-slate-100'
                        }`}
                      >
                        Toutes ({liquidationsDispo.length})
                      </button>
                    </div>
                  </div>

                  <div className="flex gap-3 items-center">
                    <select
                      className="w-full px-4 py-2.5 bg-white border border-blue-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold shadow-xs"
                      value={selectedDispoKey}
                      onChange={handleSelectLiquidation}
                    >
                      <option value="">
                        {modalProcedureFilter === 'Marché'
                          ? `-- Choisir une liquidation Appel d'offres (${filteredLiquidations.length} disponible(s)) --`
                          : modalProcedureFilter === 'Bon de commande'
                          ? `-- Choisir une liquidation Bon de commande (${filteredLiquidations.length} disponible(s)) --`
                          : modalProcedureFilter === 'Convention'
                          ? `-- Choisir une liquidation Convention (${filteredLiquidations.length} disponible(s)) --`
                          : `-- Choisir une liquidation dans le registre (${filteredLiquidations.length} disponible(s)) --`}
                      </option>
                      {filteredLiquidations.map((item) => (
                        <option key={getLiquidationKey(item)} value={getLiquidationKey(item)}>
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

                {/* Informations générales — Pleine largeur */}
                <div className="w-full bg-white p-6 md:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-6">
                  
                  {/* Card Header */}
                  <div className="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                      <Building size={16} className="text-blue-600" />
                      Informations générales de l'ordonnancement
                    </h3>
                    {formData.montant_brut > 0 && (
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-500 font-medium">Montant brut :</span>
                        <span className="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-xl text-xs font-black">
                          {formatDH(formData.montant_brut)}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Ligne 1 : N° Liquidation, Date, Référence */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">N° Liquidation *</label>
                      <input
                        type="text"
                        name="num_liquidation"
                        value={formData.num_liquidation || selectedDispoItem?.num_liquidation || ''}
                        readOnly
                        placeholder="Sélectionnez une liquidation"
                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Date d'ordonnancement *</label>
                      <input
                        type="date"
                        name="date_ordonnancement"
                        value={formData.date_ordonnancement}
                        onChange={handleInputChange}
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Référence (Marché / BC / Convention) *</label>
                      <input
                        type="text"
                        name="reference"
                        value={formData.reference}
                        onChange={handleInputChange}
                        placeholder="ex: M-10-2026-DRCA-RSK"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>

                  {/* Ligne 2 : Bénéficiaire, RIB, Banque */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Bénéficiaire *</label>
                      <input
                        type="text"
                        name="beneficiaire_nom"
                        value={formData.beneficiaire_nom}
                        onChange={handleInputChange}
                        placeholder="Nom ou Raison Sociale"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">RIB / Compte bancaire</label>
                      <input
                        type="text"
                        name="rib_compte"
                        value={formData.rib_compte}
                        onChange={handleInputChange}
                        placeholder="ex: 310 810 100 002 470 105 200 152"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Banque / Agence</label>
                      <input
                        type="text"
                        name="banque_agence"
                        value={formData.banque_agence}
                        onChange={handleInputChange}
                        placeholder="ex: ATTIJARIWAFA BANK - KÉNITRA"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>

                  {/* Ligne 3 : Budget, Procédure, Montant Brut TTC */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Budget *</label>
                      <select
                        name="budget_type"
                        value={formData.budget_type}
                        onChange={handleInputChange}
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      >
                        <option value="Investissement">Investissement</option>
                        <option value="Fonctionnement">Fonctionnement</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Procédure</label>
                      <select
                        name="type_procedure"
                        value={formData.type_procedure}
                        onChange={handleInputChange}
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      >
                        <option value="Bon de commande">Bon de commande</option>
                        <option value="Marché">Marché</option>
                        <option value="Convention">Convention</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Montant Brut TTC *</label>
                      <div className="relative">
                        <input
                          type="number"
                          step="0.01"
                          name="montant_brut"
                          value={formData.montant_brut}
                          onChange={(e) => handleBrutChange(e.target.value)}
                          className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <span className="absolute right-3.5 top-2.5 font-bold text-slate-400 text-xs">DH</span>
                      </div>
                    </div>
                  </div>

                  {/* Ligne 4 : Imputation Budgétaire & Intitulé */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-slate-100">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Imputation budgétaire
                      </label>
                      <div className="grid grid-cols-4 gap-2.5">
                        <div>
                          <span className="text-[10px] text-slate-500 font-bold block mb-1 text-center">ART</span>
                          <input
                            type="text"
                            name="article"
                            value={formData.article}
                            onChange={handleInputChange}
                            className="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs font-bold text-slate-800"
                          />
                        </div>
                        <div>
                          <span className="text-[10px] text-slate-500 font-bold block mb-1 text-center">PAR</span>
                          <input
                            type="text"
                            name="paragraphe"
                            value={formData.paragraphe}
                            onChange={handleInputChange}
                            className="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs font-bold text-slate-800"
                          />
                        </div>
                        <div>
                          <span className="text-[10px] text-slate-500 font-bold block mb-1 text-center">LIG</span>
                          <input
                            type="text"
                            name="ligne"
                            value={formData.ligne}
                            onChange={handleInputChange}
                            className="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs font-bold text-slate-800"
                          />
                        </div>
                        <div>
                          <span className="text-[10px] text-slate-500 font-bold block mb-1 text-center">S/LIG</span>
                          <input
                            type="text"
                            name="sous_ligne"
                            value={formData.sous_ligne}
                            onChange={handleInputChange}
                            className="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs font-bold text-slate-800"
                          />
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">Intitulé / Objet de la dépense *</label>
                      <textarea
                        rows={2}
                        name="intitule_depense"
                        value={formData.intitule_depense}
                        onChange={handleInputChange}
                        placeholder="Description exacte de la dépense..."
                        className="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                      />
                    </div>
                  </div>

                  {/* Section Retenues à la source (Optionnelles) */}
                  <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-slate-200/60">
                      <div className="flex items-center gap-2">
                        <Receipt size={15} className="text-blue-600" />
                        <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                          Retenues à la source (Optionnelles)
                        </span>
                      </div>
                      <span className="text-[11px] text-slate-500 font-medium italic">
                        {(!applyTva && !applyIas) ? 'Aucune retenue (100% Brut)' : 'Retenues appliquées'}
                      </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {/* 1. Case à cocher TVA */}
                      <div className="space-y-2 p-3 bg-white rounded-xl border border-slate-200/80">
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

                        {applyTva && (
                          <div className="pt-2 animate-in fade-in duration-150">
                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                              Montant de la retenue TVA *
                            </label>
                            <div className="relative">
                              <input
                                type="number"
                                step="0.01"
                                value={tvaMontantInput}
                                onChange={(e) => handleTvaAmountChange(e.target.value)}
                                placeholder="0.00"
                                className="w-full px-3 py-2 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700 focus:ring-2 focus:ring-rose-400"
                              />
                              <span className="absolute right-3 top-2 text-xs font-bold text-rose-400">DH</span>
                            </div>
                            <p className="text-[10px] text-slate-500 mt-1">
                              Sera mandatée via un Ordre de Virement (OV).
                            </p>
                          </div>
                        )}
                      </div>

                      {/* 2. Case à cocher IAC */}
                      <div className="space-y-2 p-3 bg-white rounded-xl border border-slate-200/80">
                        <label className="flex items-center gap-2.5 cursor-pointer select-none">
                          <input
                            type="checkbox"
                            checked={applyIas}
                            onChange={(e) => handleToggleIas(e.target.checked)}
                            className="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer"
                          />
                          <span className={`text-xs font-bold ${applyIas ? 'text-blue-700' : 'text-slate-700'}`}>
                            Appliquer une retenue IAC
                          </span>
                        </label>

                        {applyIas && (
                          <div className="pt-2 animate-in fade-in duration-150">
                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                              Montant de la retenue IAC *
                            </label>
                            <div className="relative">
                              <input
                                type="number"
                                step="0.01"
                                value={iasMontantInput}
                                onChange={(e) => handleIasAmountChange(e.target.value)}
                                placeholder="0.00"
                                className="w-full px-3 py-2 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700 focus:ring-2 focus:ring-rose-400"
                              />
                              <span className="absolute right-3 top-2 text-xs font-bold text-rose-400">DH</span>
                            </div>
                            <p className="text-[10px] text-slate-500 mt-1">
                              Sera imputée via un Ordre d'Imputation (OI).
                            </p>
                          </div>
                        )}
                      </div>
                    </div>

                    {(applyTva || applyIas) && (
                      <div className="p-3 bg-blue-50/80 border border-blue-200 rounded-xl flex items-center justify-between text-xs">
                        <span className="font-bold text-blue-950">Net à payer calculé :</span>
                        <strong className="text-base font-black text-blue-700">{formatDH(formData.net_a_payer)}</strong>
                      </div>
                    )}
                  </div>

                </div>

              </div>
            )}

            {/* STEP 2: PAIEMENTS À EFFECTUER / ORDRES MULTIPLES */}
            {step === 2 && (
              <div className="space-y-5">
                {decisionAccepted && <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                  <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-3">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                      <Receipt size={16} className="text-blue-600" />
                      Montants de la liquidation
                    </h3>
                    <div>
                      <label className="block text-xs text-slate-700 font-bold mb-1">Montant TTC (Montant brut) *</label>
                      <div className="relative">
                        <input
                          type="number"
                          step="0.01"
                          value={formData.montant_brut}
                          onChange={(e) => handleBrutChange(e.target.value)}
                          className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <span className="absolute right-3.5 top-2.5 font-bold text-slate-400">DH</span>
                      </div>
                    </div>
                    <div className="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                      <div className="text-xs font-bold text-slate-800 uppercase tracking-wider">Retenues à la source</div>
                      <label className="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-slate-700">
                        <input type="checkbox" checked={applyTva} onChange={(e) => handleToggleTva(e.target.checked)} className="w-4 h-4 rounded text-blue-600" />
                        Appliquer une retenue TVA
                      </label>
                      {applyTva && (
                        <input type="number" step="0.01" value={tvaMontantInput} onChange={(e) => handleTvaAmountChange(e.target.value)} className="w-full px-3 py-1.5 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700" />
                      )}
                      <label className="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-slate-700 border-t border-slate-200 pt-2">
                        <input type="checkbox" checked={applyIas} onChange={(e) => handleToggleIas(e.target.checked)} className="w-4 h-4 rounded text-blue-600" />
                        Appliquer une retenue IAC
                      </label>
                      {applyIas && (
                        <input type="number" step="0.01" value={iasMontantInput} onChange={(e) => handleIasAmountChange(e.target.value)} className="w-full px-3 py-1.5 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-700" />
                      )}
                    </div>
                    <div className="p-4 bg-blue-50 border-2 border-blue-200 rounded-2xl">
                      <div className="text-[11px] font-bold text-blue-950 uppercase">Net à payer au bénéficiaire</div>
                      <div className="text-2xl font-black text-blue-700 mt-1">{formatDH(formData.net_a_payer)}</div>
                    </div>
                  </div>
                  <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
                    <label className="block text-xs font-semibold text-slate-700 mb-1">Intitulé / Objet de la dépense *</label>
                    <textarea
                      rows={5}
                      name="intitule_depense"
                      value={formData.intitule_depense}
                      onChange={handleInputChange}
                      placeholder="Description exacte de la dépense..."
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                    />
                  </div>
                </div>}
                {!decisionAccepted && <>
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
                <div className="border border-slate-200 rounded-2xl overflow-x-auto shadow-xs">
                  <table className="w-full min-w-[700px] text-sm text-left">
                    <thead className="bg-slate-50 text-slate-700 font-extrabold border-b border-slate-200 uppercase tracking-wider text-xs">
                      <tr>
                        <th className="px-4 py-3.5">#</th>
                        <th className="px-5 py-3.5">Type de paiement</th>
                        <th className="px-4 py-3.5">N° OP</th>
                        <th className="px-5 py-3.5">Bénéficiaire</th>
                        <th className="px-5 py-3.5">Créance</th>
                        <th className="px-5 py-3.5 text-right">Montant (DH)</th>
                        <th className="px-4 py-3.5 text-center">Statut</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {formData.ordres.map((ord, idx) => {
                        const isRejete = ord.statut === 'Rejeté';
                        const isPaye = ord.statut === 'Payé';

                        return (
                          <tr key={ord.id || idx} className={`transition ${
                            isRejete
                              ? 'bg-rose-50/25 hover:bg-rose-50/40 text-slate-500'
                              : isPaye
                              ? 'bg-emerald-50/20 hover:bg-emerald-50/40'
                              : 'hover:bg-blue-50/40'
                          }`}>
                            <td className="px-4 py-3.5 font-bold text-slate-400">{idx + 1}</td>
                            <td className="px-5 py-3.5">
                              <div className={`font-semibold ${isRejete ? 'text-slate-500 line-through' : 'text-slate-900'}`}>
                                {ord.type_mouvement}
                              </div>
                            </td>
                            <td className="px-4 py-3.5 font-mono font-bold">
                              <span className={`px-2.5 py-1 border rounded-lg text-xs ${
                                isRejete
                                  ? 'bg-rose-50 text-rose-700 border-rose-200 line-through'
                                  : isPaye
                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                  : 'bg-blue-50 text-blue-700 border-blue-200'
                              }`}>
                                {ord.num_ordre || `OP N°${idx + 1}`}
                              </span>
                            </td>
                            <td className="px-5 py-3.5 text-slate-800 font-medium">
                              <span className={isRejete ? 'text-slate-400' : ''}>{ord.beneficiaire}</span>
                            </td>
                            <td className="px-5 py-3.5 text-slate-600 font-medium">
                              <span className="px-2 py-0.5 bg-slate-100 rounded text-xs font-semibold text-slate-700">
                                {ord.creance}
                              </span>
                            </td>
                            <td className="px-5 py-3.5 text-right font-black">
                              <span className={isRejete ? 'line-through text-slate-400 font-normal' : 'text-blue-700'}>
                                {formatDH(ord.montant)}
                              </span>
                            </td>
                            <td className="px-4 py-3.5 text-center">
                              <span className={`px-3 py-1 border rounded-full text-xs font-bold ${
                                isPaye
                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                  : isRejete
                                  ? 'bg-rose-50 text-rose-700 border-rose-200'
                                  : 'bg-amber-50 text-amber-700 border-amber-200'
                              }`}>
                                {ord.statut || 'À payer'}
                              </span>
                            </td>
                          </tr>
                        );
                      })}
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
                <div className={`p-4 rounded-2xl border flex flex-col sm:flex-row justify-between items-center gap-3 ${isBalanced
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
                        ? `TOTAL DES MOUVEMENTS ACTIFS = MONTANT BRUT (Équilibré)${formData.ordres.some(o => o.statut === 'Rejeté') ? ' — Ordre(s) rejeté(s) exclu(s)' : ''}`
                        : `Attention : La somme des mouvements actifs (${formatDH(totalOrdres)}) diffère du montant brut (${formatDH(formData.montant_brut)})`
                      }
                    </span>
                  </div>
                  <div className="text-sm font-black tracking-wide">
                    Total actif : {formatDH(totalOrdres)}
                  </div>
                </div>

                {/* Décision du fondé de pouvoir */}
                {(() => {
                  const currentActiveOrdre = [...formData.ordres].reverse().find(o => o.statut === 'À payer' && o.type_mouvement === 'Paiement fournisseur')
                    || [...formData.ordres].reverse().find(o => o.statut === 'À payer');
                  const isDossierPaye = formData.statut === 'Payé' || (formData.ordres.length > 0 && formData.ordres.every(o => o.statut === 'Payé' || o.statut === 'Rejeté') && formData.ordres.some(o => o.statut === 'Payé'));
                  const rejectedOrders = formData.ordres.filter(o => o.statut === 'Rejeté');

                  return (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 bg-white border border-blue-200 rounded-2xl shadow-xs">
                      <div>
                        <div className="flex items-center gap-2">
                          <p className="text-xs font-bold text-slate-800">Décision du fondé de pouvoir</p>
                          {rejectedOrders.length > 0 && (
                            <span className="px-2 py-0.5 bg-rose-100 text-rose-700 border border-rose-200 rounded-md text-[10px] font-bold">
                              {rejectedOrders.length} ordre(s) précédent(s) rejeté(s)
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 mt-1">
                          {isDossierPaye ? (
                            <span className="text-emerald-700 font-bold flex items-center gap-1">
                              <CheckCircle2 size={13} className="text-emerald-600 inline" />
                              Dossier validé et accepté par le fondé de pouvoir (Statut : Payé).
                            </span>
                          ) : currentActiveOrdre ? (
                            <span>
                              Ordre en cours d'évaluation : <strong className="text-blue-700 font-mono">{currentActiveOrdre.num_ordre}</strong> ({formatDH(currentActiveOrdre.montant)}).
                              En cas de refus, un nouvel ordre de remplacement sera automatiquement créé.
                            </span>
                          ) : (
                            'Le premier ordre sera créé avec le statut « À payer » avant la décision.'
                          )}
                        </p>
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <button
                          type="button"
                          onClick={() => handleSubmit('Rejeté', currentActiveOrdre?.id)}
                          disabled={submitting || !isBalanced || !currentActiveOrdre || isDossierPaye}
                          className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                          Refuser
                        </button>
                        <button
                          type="button"
                          onClick={() => handleSubmit('Payé', currentActiveOrdre?.id)}
                          disabled={submitting || !isBalanced || !currentActiveOrdre || isDossierPaye}
                          className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                          Accepter
                        </button>
                      </div>
                    </div>
                  );
                })()}
                </>}

              </div>
            )}

            {/* STEP 3: DOCUMENTS DU DOSSIER */}
            {step === 3 && (() => {
              const activeOrdresList = formData.ordres.filter(o => o.statut !== 'Rejeté');
              const rejectedOrdresList = formData.ordres.filter(o => o.statut === 'Rejeté');
              const activeDocs = activeOrdresList.flatMap((ord, oIdx) => {
                const docs = resolveOrdreDocumentsList(ord);
                return docs.map((docInfo, dIdx) => ({ ord, docInfo, key: `${ord.id || oIdx}-${dIdx}` }));
              });

              return (
                <div className="space-y-6">
                  {/* Header Title & Subtitle */}
                  <div className="pb-2 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                      <h3 className="text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <FileText className="w-5 h-5 text-blue-600" />
                        Documents officiels du dossier d'ordonnancement
                      </h3>
                      <p className="text-xs text-slate-500 font-medium mt-0.5">
                        Consultez, prévisualisez et téléchargez les pièces comptables officielles générées pour ce dossier.
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="px-3 py-1 bg-blue-50 text-blue-800 border border-blue-200 rounded-full text-xs font-bold">
                        1 État global + {activeDocs.length} Pièce{activeDocs.length > 1 ? 's' : ''} d'ordre
                      </span>
                    </div>
                  </div>

                  {/* Section 1: Document Général du Dossier (Pleine Largeur) */}
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <span className="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                      <h4 className="text-xs font-black text-slate-800 uppercase tracking-wider">
                        Document général du dossier
                      </h4>
                    </div>

                    <div className="w-full bg-white border-2 border-blue-200/90 hover:border-blue-300 rounded-3xl p-6 md:p-7 shadow-xs hover:shadow-md transition">
                      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {/* Title & Icon */}
                        <div className="flex items-start gap-4">
                          <div className="p-3.5 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl shadow-md shrink-0">
                            <FileText size={28} />
                          </div>
                          <div className="space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className="px-2.5 py-0.5 bg-blue-100 text-blue-800 text-[11px] font-bold rounded-lg uppercase tracking-wider">
                                Document officiel du dossier
                              </span>
                              <span className="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-bold rounded-lg">
                                Synthèse globale
                              </span>
                            </div>
                            <h4 className="text-base font-extrabold text-slate-900">
                              État de liquidation financière
                            </h4>
                            <p className="text-xs text-slate-500 font-medium max-w-xl">
                              Récapitulatif financier et administratif officiel des engagements budgétaires, imputations et montants liquidés de la dépense.
                            </p>
                          </div>
                        </div>

                        {/* Meta infos pills */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 text-xs">
                          <div>
                            <span className="text-[10px] uppercase font-bold text-slate-400 block">N° Liquidation</span>
                            <span className="font-mono font-bold text-slate-800 truncate block">
                              {selectedDispoItem?.num_liquidation || formData.num_liquidation || formData.reference || '—'}
                            </span>
                          </div>
                          <div>
                            <span className="text-[10px] uppercase font-bold text-slate-400 block">Procédure</span>
                            <span className="font-bold text-slate-800 block truncate">{formData.type_procedure || 'Marché'}</span>
                          </div>
                          <div>
                            <span className="text-[10px] uppercase font-bold text-slate-400 block">Montant brut</span>
                            <span className="font-bold text-slate-800 block">{formatDH(formData.montant_brut)}</span>
                          </div>
                          <div>
                            <span className="text-[10px] uppercase font-bold text-emerald-600 block">Net à payer</span>
                            <span className="font-black text-emerald-700 block">{formatDH(formData.net_a_payer || formData.montant_brut)}</span>
                          </div>
                        </div>

                        {/* Action buttons */}
                        <div className="flex items-center gap-2.5 shrink-0">
                          <button
                            type="button"
                            onClick={() => handleOpenPreview('etat_liquidation')}
                            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                          >
                            <Eye size={15} className="text-blue-600" />
                            Aperçu
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDownloadDirect('etat_liquidation')}
                            disabled={downloadingDocKey === 'etat_liquidation-dossier'}
                            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md hover:shadow-lg transition disabled:opacity-50 cursor-pointer"
                          >
                            {downloadingDocKey === 'etat_liquidation-dossier' ? (
                              <>
                                <Loader2 size={15} className="animate-spin" />
                                Téléchargement...
                              </>
                            ) : (
                              <>
                                <Download size={15} />
                                Télécharger PDF
                              </>
                            )}
                          </button>
                        </div>

                      </div>
                    </div>
                  </div>

                  {/* Section 2: Documents associés aux ordres (OP & OV) */}
                  <div className="space-y-3 pt-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                        <h4 className="text-xs font-black text-slate-800 uppercase tracking-wider">
                          Documents associés aux ordres (OP & OV)
                        </h4>
                      </div>
                      <span className="text-xs font-bold text-slate-500">
                        {activeOrdresList.length} ordre{activeOrdresList.length > 1 ? 's' : ''} actif{activeOrdresList.length > 1 ? 's' : ''} ({activeDocs.length} document{activeDocs.length > 1 ? 's' : ''})
                      </span>
                    </div>

                    {/* Grille 2 colonnes parfaitement équilibrée */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                      {activeDocs.map(({ ord, docInfo, key }, idx) => {
                        const isOv = docInfo.docType === 'ov';
                        const isOi = docInfo.docType === 'oi';
                        const isRas = docInfo.docType === 'op_ras_iac' || docInfo.docType === 'op_ras_tva';
                        const downloadKey = `${docInfo.docType}-${ord.id || idx}`;

                        return (
                          <div
                            key={key}
                            className="p-6 bg-white border-2 border-slate-200/90 hover:border-blue-300 rounded-3xl flex flex-col justify-between space-y-5 shadow-xs hover:shadow-md transition"
                          >
                            <div className="space-y-4">
                              {/* Header Card */}
                              <div className="flex items-start gap-3.5">
                                <div className={`p-3 rounded-2xl border shrink-0 ${
                                  isOv
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                    : isOi
                                    ? 'bg-indigo-50 text-indigo-700 border-indigo-100'
                                    : isRas
                                    ? 'bg-amber-50 text-amber-700 border-amber-100'
                                    : 'bg-blue-50 text-blue-700 border-blue-100'
                                }`}>
                                  {isOv ? <Landmark size={22} /> : isOi ? <Layers size={22} /> : <FileCheck size={22} />}
                                </div>
                                <div className="flex-1 min-w-0">
                                  <div className="flex flex-wrap items-center gap-1.5">
                                    <span className="font-mono text-xs font-black text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-lg">
                                      {ord.num_ordre || `OP N°${idx + 1}`}
                                    </span>
                                    <span className={`px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border ${
                                      isOv
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                        : isOi
                                        ? 'bg-indigo-50 text-indigo-800 border-indigo-200'
                                        : 'bg-blue-50 text-blue-800 border-blue-200'
                                    }`}>
                                      {docInfo.docType.toUpperCase()}
                                    </span>
                                    <span className={`px-2 py-0.5 rounded-lg text-[10px] font-bold ${
                                      ord.statut === 'Payé'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : ord.statut === 'Rejeté'
                                        ? 'bg-rose-100 text-rose-800'
                                        : 'bg-amber-100 text-amber-800'
                                    }`}>
                                      {ord.statut || 'À payer'}
                                    </span>
                                  </div>
                                  <h4 className="font-bold text-slate-900 text-sm md:text-base leading-snug mt-1.5">
                                    {docInfo.title}
                                  </h4>
                                  <p className="text-[11px] text-slate-500 font-medium line-clamp-2 mt-0.5">
                                    {docInfo.description}
                                  </p>
                                </div>
                              </div>

                              {/* Details Info Box */}
                              <div className="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/70 space-y-2 text-xs">
                                <div className="flex justify-between items-center text-slate-600 gap-2">
                                  <span className="font-medium shrink-0">Bénéficiaire :</span>
                                  <span className="font-bold text-slate-900 text-right truncate">
                                    {ord.beneficiaire || formData.beneficiaire_nom || '—'}
                                  </span>
                                </div>
                                <div className="flex justify-between items-center text-slate-600 gap-2">
                                  <span className="font-medium shrink-0">Créance :</span>
                                  <span className="font-bold text-slate-800 text-right">{ord.creance || 'Reste à payer'}</span>
                                </div>
                                <div className="flex justify-between items-center text-slate-600 gap-2">
                                  <span className="font-medium shrink-0">Mode de paiement :</span>
                                  <span className="font-semibold text-slate-700 text-right">{ord.mode_paiement || 'Virement bancaire'}</span>
                                </div>
                                {(ord.rib_compte || formData.rib_compte) && (
                                  <div className="flex justify-between items-center text-slate-600 gap-2">
                                    <span className="font-medium shrink-0">Compte / RIB :</span>
                                    <span className="font-mono text-[11px] font-bold text-slate-700 text-right truncate">
                                      {ord.rib_compte || formData.rib_compte}
                                    </span>
                                  </div>
                                )}
                                <div className="flex justify-between items-center pt-2.5 border-t border-slate-200/80">
                                  <span className="font-bold text-slate-700 text-xs">Montant du document :</span>
                                  <span className="font-black text-blue-700 text-base font-mono">
                                    {formatDH(ord.montant)}
                                  </span>
                                </div>
                              </div>
                            </div>

                            {/* Actions Buttons */}
                            <div className="flex items-center gap-2.5 pt-3 border-t border-slate-100">
                              <button
                                type="button"
                                onClick={() => handleOpenPreview(docInfo.docType, ord)}
                                className="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                              >
                                <Eye size={15} className="text-blue-600" />
                                Aperçu
                              </button>
                              <button
                                type="button"
                                onClick={() => handleDownloadDirect(docInfo.docType, ord)}
                                disabled={downloadingDocKey === downloadKey}
                                className="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs hover:shadow transition disabled:opacity-50 cursor-pointer"
                              >
                                {downloadingDocKey === downloadKey ? (
                                  <>
                                    <Loader2 size={15} className="animate-spin" />
                                    Téléchargement...
                                  </>
                                ) : (
                                  <>
                                    <Download size={15} />
                                    Télécharger PDF
                                  </>
                                )}
                              </button>
                            </div>
                          </div>
                        );
                      })}

                      {activeDocs.length === 0 && (
                        <div className="col-span-full p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-slate-400">
                          Aucun ordre de paiement actif n'a été créé.
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Section 3: Historique des ordres rejetés (si applicable) */}
                  {rejectedOrdresList.length > 0 && (
                    <div className="pt-4 border-t border-slate-200/80 space-y-3">
                      <div className="flex items-center gap-2 text-rose-700">
                        <span className="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <h4 className="text-xs font-black uppercase tracking-wider">
                          Historique des ordres précédents rejetés ({rejectedOrdresList.length})
                        </h4>
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {rejectedOrdresList.map((ord, idx) => (
                          <div
                            key={idx}
                            className="p-4 bg-rose-50/30 border border-rose-200/80 rounded-2xl flex items-center justify-between gap-3 opacity-80"
                          >
                            <div>
                              <div className="flex items-center gap-2">
                                <span className="font-mono text-xs font-bold text-rose-700 line-through">
                                  {ord.num_ordre || `OP-${idx + 1}`}
                                </span>
                                <span className="px-2 py-0.5 bg-rose-100 text-rose-800 rounded text-[10px] font-bold">
                                  Rejeté
                                </span>
                              </div>
                              <p className="text-xs text-slate-500 mt-1">
                                Bénéficiaire : <strong className="text-slate-700">{ord.beneficiaire || '—'}</strong> — <span className="line-through">{formatDH(ord.montant)}</span>
                              </p>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleOpenPreview('op', ord)}
                              className="px-3 py-1.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-xl transition cursor-pointer inline-flex items-center gap-1.5 shadow-xs"
                            >
                              <Eye size={13} />
                              Aperçu
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                </div>
              );
            })()}

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
              onClick={() => step === 1 ? createFirstOrderAndContinue() : setStep(step + 1)}
              disabled={submitting}
              className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer"
            >
              {submitting && step === 1 ? 'Création...' : 'Suivant'}
              <ArrowRight size={16} />
            </button>
          ) : (
            <button
              type="button"
              onClick={finishWorkflow}
              disabled={submitting}
              className="inline-flex items-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition disabled:opacity-50 cursor-pointer"
            >
              {submitting ? 'Traitement en cours...' : 'Fin'}
              <Check size={16} />
            </button>
          )}
        </div>

      </div>

      {/* SUB-MODAL : CREATION / MODIFICATION D'UN PAIEMENT */}
      {showOrdreModal && (
        <div className="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto animate-in fade-in duration-150">
          <div className="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden my-auto flex flex-col max-h-[90vh]">
            
            {/* Header */}
            <div className="px-6 py-4 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex justify-between items-center shrink-0">
              <div className="flex items-center gap-2.5">
                <div className="p-2 bg-white/10 rounded-xl">
                  <Wallet className="w-4 h-4 text-blue-200" />
                </div>
                <div>
                  <h4 className="text-sm font-bold tracking-wide">
                    {editingOrdreIndex !== null ? 'Modifier un paiement / ordre' : 'Création / Modification d\'un paiement'}
                  </h4>
                  <p className="text-[11px] text-blue-200">
                    Configuration des détails du virement, mandat ou retenue
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setShowOrdreModal(false)}
                className="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition cursor-pointer"
                title="Fermer"
              >
                <X size={20} />
              </button>
            </div>

            {/* Body */}
            <div className="p-6 md:p-8 space-y-4 overflow-y-auto flex-1 text-xs">
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">N° Ordre / OP *</label>
                  <input
                    type="text"
                    value={ordreForm.num_ordre || ''}
                    onChange={(e) => setOrdreForm({ ...ordreForm, num_ordre: e.target.value })}
                    placeholder="ex: OP-001..."
                    className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">Type de paiement *</label>
                  <select
                    value={ordreForm.type_mouvement}
                    onChange={(e) => setOrdreForm({ ...ordreForm, type_mouvement: e.target.value })}
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
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
                  <label className="block text-slate-700 font-bold mb-1.5">Mode de paiement *</label>
                  <select
                    value={ordreForm.mode_paiement}
                    onChange={(e) => setOrdreForm({ ...ordreForm, mode_paiement: e.target.value })}
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium"
                  >
                    <option value="Virement">Virement</option>
                    <option value="Chèque">Chèque</option>
                    <option value="Ordre d'imputation">Ordre d'imputation</option>
                    <option value="Prélèvement">Prélèvement</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">Bénéficiaire *</label>
                  <input
                    type="text"
                    value={ordreForm.beneficiaire}
                    onChange={(e) => setOrdreForm({ ...ordreForm, beneficiaire: e.target.value })}
                    placeholder="ex: COMPTOIR AGRICOLE... ou TRÉSOR PUBLIC"
                    className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">RIB / Compte *</label>
                  <input
                    type="text"
                    value={ordreForm.rib_compte}
                    onChange={(e) => setOrdreForm({ ...ordreForm, rib_compte: e.target.value })}
                    placeholder="310 810 100 002 470 105 200 152"
                    className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">Créance *</label>
                  <select
                    value={ordreForm.creance}
                    onChange={(e) => setOrdreForm({ ...ordreForm, creance: e.target.value })}
                    className="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="Reste à payer">Reste à payer</option>
                    <option value="C.Neufs">C.Neufs</option>
                    <option value="Reports">Reports</option>
                    <option value="Retenue à la source">Retenue à la source</option>
                    <option value="Crédit Consolidés">Crédit Consolidés</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1.5">Montant (DH) *</label>
                  <div className="relative">
                    <input
                      type="number"
                      step="0.01"
                      value={ordreForm.montant}
                      onChange={(e) => setOrdreForm({ ...ordreForm, montant: e.target.value })}
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <span className="absolute right-3.5 top-2.5 font-bold text-slate-400 text-xs">DH</span>
                  </div>
                </div>
              </div>

            </div>

            {/* Footer */}
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 shrink-0">
              <button
                type="button"
                onClick={() => setShowOrdreModal(false)}
                className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={handleSaveOrdre}
                className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md hover:shadow-lg transition cursor-pointer"
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
          id: createdDossierId || formData.id,
          num_op: previewOrdre?.num_ordre || formData.ordres?.[0]?.num_ordre || 'OP N° 38',
          liquidation: selectedDispoItem,
          retenue_tva: applyTva ? Number(tvaMontantInput) : 0,
          retenue_ias: applyIas ? Number(iasMontantInput) : 0,
          net_a_payer: formData.net_a_payer,
          montant_brut: formData.montant_brut,
        }}
        ordre={previewOrdre}
        docType={previewDocType}
      />

      {/* Specific OP Documents Modal */}
      <OrdreDocumentsModal
        isOpen={docsModalOpen}
        onClose={() => {
          setDocsModalOpen(false);
          setDocsModalOrdre(null);
        }}
        ordonnancement={{
          ...formData,
          id: createdDossierId || formData.id,
          num_op: docsModalOrdre?.num_ordre || formData.ordres?.[0]?.num_ordre || 'OP N° 38',
          liquidation: selectedDispoItem,
          retenue_tva: applyTva ? Number(tvaMontantInput) : 0,
          retenue_ias: applyIas ? Number(iasMontantInput) : 0,
          net_a_payer: formData.net_a_payer,
          montant_brut: formData.montant_brut,
        }}
        ordre={docsModalOrdre}
        onOpenPreview={(type, ord) => handleOpenPreview(type, ord)}
      />

    </div>
  );
}
