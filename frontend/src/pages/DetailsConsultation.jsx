import React, { useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Save, Plus, Trash2, CheckCircle2, AlertCircle, FileText, Calculator, Download, Users, X, Edit, Award, FileCheck, Calendar, Briefcase, PenTool, Clock, BarChart2, Activity, PlayCircle, StopCircle, AlertTriangle, FileSignature, CheckSquare, PackageCheck, Banknote, Landmark, Archive, DownloadCloud } from 'lucide-react';

const DOCUMENTS_BC = [
  // Phase 1: Consultation / Estimation
  { phaseCode: '01', id: 'estimation_administrative', label: 'Estimation de l\'administration' },
  { phaseCode: '01', id: 'bordereau_prix', label: 'Bordereau des prix' },
  { phaseCode: '02', id: 'lettre_consultation', label: 'Lettres de Consultations' },
  
  // Phase 2: Ouverture des plis (Commission)
  { phaseCode: '03', id: 'decision_ouverture', label: "Décision d'ouverture des plis" },
  { phaseCode: '03', id: 'pv_ouverture', label: "PV d'ouverture des plis" },
  { phaseCode: '03', id: 'devis_contradictoires', label: "Devis Contradictoires" },

  // Phase 3: Engagement
  { phaseCode: '04', id: 'bon_commande', label: 'Bon de Commande' },
  { phaseCode: '04', id: 'etat_engagement', label: "Etat d'engagement" },

  // Phase 5: Réception & Règlement
  { phaseCode: '05', id: 'decision_reception', label: 'Décision de réception' },
  { phaseCode: '05', id: 'pv_reception', label: 'PV De réception' },
  { phaseCode: '05', id: 'facture', label: 'Facture' },
  { phaseCode: '05', id: 'exoneration_tva', label: 'Exonération de la TVA' },
];

const DOCUMENTS_CONVENTION = [
  // Phase 1: Consultation
  { phaseCode: '01', id: 'rapport_presentation', label: 'Rapport de présentation', endpoint: 'rapport-presentation' },
  { phaseCode: '01', id: 'reglement_consultation', label: 'Règlement de la consultation', endpoint: 'reglement-consultation' },
  { phaseCode: '01', id: 'cps', label: 'Cahier des Prescriptions Spéciales (CPS)', endpoint: 'cps' },
  
  // Phase 2: Ouverture des plis
  { phaseCode: '03', id: 'pv_ouverture_marche', label: "P.V d'ouverture de plis", endpoint: 'pv-ouverture' },
  { phaseCode: '03', id: 'lettre_commission', label: 'Lettre aux membres de la commission', endpoint: 'lettre-commission' },

  // Phase 3: Engagement
  { phaseCode: '04', id: 'acte_engagement', label: "Acte d'engagement", endpoint: 'acte-engagement' },
  { phaseCode: '04', id: 'marche_recu', label: 'Marché + Recu d\'enregistrement', endpoint: 'marche-recu' },
  { phaseCode: '04', id: 'os_notification', label: 'OS de notification de l\'approbation', endpoint: 'os-approbation' },
  { phaseCode: '04', id: 'caution_definitive', label: 'Caution bancaire définitive', endpoint: 'caution-definitive' },
  { phaseCode: '04', id: 'attestation_assurance', label: 'Attestation d\'assurance', endpoint: 'attestation-assurance' },

  // Phase 4: Suivi
  { phaseCode: '07', id: 'os_commencement', label: 'Ordre de service (Commencement/Arrêt)', endpoint: 'os-commencement' },
  { phaseCode: '07', id: 'attachement_provisoire', label: 'Attachement provisoire', endpoint: 'attachement-provisoire' },
  { phaseCode: '07', id: 'decompte_provisoire', label: 'Décompte provisoire', endpoint: 'decompte-provisoire' },

  // Phase 5: Réception & Règlement
  { phaseCode: '05', id: 'pv_reception_provisoire', label: 'PV de la réception provisoire', endpoint: 'pv-reception-provisoire' },
  { phaseCode: '05', id: 'facture_marche', label: 'Facture', endpoint: 'facture' },
  { phaseCode: '05', id: 'attestation_reception', label: 'Attestation de réception', endpoint: 'attestation-reception' },
];

const PhaseDocuments = ({ title, phaseCode, mode, timeline, stepKeywords, disabled, downloadDocument }) => {
  // 1. Chercher dans le nouveau Workflow (si actif)
  const step = timeline && timeline.length ? timeline.find(s => stepKeywords.some(kw => s.code.includes(kw))) : null;
  
  // Si le workflow est géré dynamiquement et a des documents configurés
  if (step && step.documents && step.documents.length > 0) {
    return (
      <div className="mt-8 p-6 bg-slate-50 border border-slate-200 rounded-2xl">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2 uppercase tracking-wider">
            <FileText size={16} className="text-blue-600"/> Documents : {step.libelle}
          </h3>
          <span className={`text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider ${
            step.statut === 'valide' ? 'bg-emerald-100 text-emerald-700' :
            step.statut === 'en_cours' ? 'bg-amber-100 text-amber-700' :
            step.statut === 'bloque' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-500'
          }`}>
            {step.statut === 'non_commence' ? 'À VENIR' : step.statut.replace('_', ' ')}
          </span>
        </div>
        <div className="flex flex-wrap gap-3">
          {step.documents.map(doc => (
            <button
              key={doc.code}
              disabled={disabled || step.statut === 'non_commence'}
              onClick={() => downloadDocument({ id: doc.code, endpoint: doc.code })}
              className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <DownloadCloud size={16} className={(disabled || step.statut === 'non_commence') ? "text-slate-400" : "text-blue-500"}/> 
              Générer {doc.libelle}
            </button>
          ))}
        </div>
      </div>
    );
  }

  // 2. FALLBACK SÉCURITÉ : Utiliser l'ancienne logique statique
  const isConvention = mode && !mode.includes('BC') && !mode.includes('Commande');
  const list = isConvention ? DOCUMENTS_CONVENTION : DOCUMENTS_BC;
  const docs = list.filter(d => d.phaseCode === phaseCode);
  
  if (docs.length === 0) return null;
  
  return (
    <div className="mt-8 p-6 bg-slate-50 border border-slate-200 rounded-2xl border-dashed">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2 uppercase tracking-wider">
          <FileText size={16} className="text-gray-500"/> Documents ({title})
        </h3>
        <span className="text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider bg-gray-100 text-gray-500">Mode Classique</span>
      </div>
      <div className="flex flex-wrap gap-3">
        {docs.map(doc => (
          <button
            key={doc.id}
            disabled={disabled}
            onClick={() => downloadDocument(doc)}
            className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-gray-50 hover:border-gray-300 hover:text-gray-900 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-slate-200 disabled:hover:text-slate-700"
            title={doc.label}
          >
            <DownloadCloud size={16} className={disabled ? "text-slate-400" : "text-gray-500"}/> {doc.label}
          </button>
        ))}
      </div>
    </div>
  );
};

const DetailsConsultation = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [activeTab, setActiveTab] = useState('programmation');
  
  const [consultation, setConsultation] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [consultationsList, setConsultationsList] = useState([]);
  const [consultationsLoading, setConsultationsLoading] = useState(true);
  const [consultationsError, setConsultationsError] = useState(null);
  
  // -- Estimation State
  const [prestations, setPrestations] = useState([]);
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [saveError, setSaveError] = useState(null);
  const [programmationForm, setProgrammationForm] = useState({
    objet_consultation: '',
    objet_consultation_ar: '',
    mode_engagement: '',
    date_consultation: '',
    lieu_consultation: '',
    budget_previsionnel: '',
    notes_programmation: '',
    cautionnement_provisoire: '',
    lieu_reunion_ar: ''
  });
  const [savingProgrammation, setSavingProgrammation] = useState(false);
  const [programmationSuccess, setProgrammationSuccess] = useState(false);
  const [programmationError, setProgrammationError] = useState(null);

  // -- Module 04: Fournisseurs State
  const [offres, setOffres] = useState([]);
  const [allFournisseurs, setAllFournisseurs] = useState([]);
  const [isSelectionModalOpen, setIsSelectionModalOpen] = useState(false);
  const [selectedFournisseurIds, setSelectedFournisseurIds] = useState([]);
  
  const [isDevisModalOpen, setIsDevisModalOpen] = useState(false);
  const [currentOffre, setCurrentOffre] = useState(null);
  const [devisForm, setDevisForm] = useState({ montant_propose: '', statut_reponse: 'En attente' });

  // -- Module 05: Commission & Attribution State
  const [commissionForm, setCommissionForm] = useState({
    date_reunion: '', heure_reunion: '', lieu_reunion: '',
    president_commission: '', membres_commission: '', observations_commission: ''
  });
  const [savingCommission, setSavingCommission] = useState(false);
  const [commissionSuccess, setCommissionSuccess] = useState(false);

  const [ouverturePlisForm, setOuverturePlisForm] = useState([]);
  const [savingOuverture, setSavingOuverture] = useState(false);
  const [ouvertureSuccess, setOuvertureSuccess] = useState(false);

  // -- Module 06: Engagement State
  const [engagementData, setEngagementData] = useState(null);
  const [engagementForm, setEngagementForm] = useState({
    date_engagement: '', delai_execution: '', date_notification: '', date_commencement: '',
  });
  const [savingEngagement, setSavingEngagement] = useState(false);
  const [engagementSuccess, setEngagementSuccess] = useState(false);
  const [engagementError, setEngagementError] = useState(null);

  // -- Module 07: Suivi d'exécution State
  const [suiviData, setSuiviData] = useState(null);
  const [suiviForm, setSuiviForm] = useState({
    date_debut: '', date_fin_previsionnelle: '',
    avancement_pourcentage: 0, statut_execution: 'Non démarré', observations: ''
  });
  const [savingSuivi, setSavingSuivi] = useState(false);
  const [suiviSuccess, setSuiviSuccess] = useState(false);

  const [evenementForm, setEvenementForm] = useState({
    type_evenement: 'Note', date_evenement: new Date().toISOString().split('T')[0], description: ''
  });
  const [savingEvenement, setSavingEvenement] = useState(false);
  const [evenementSuccess, setEvenementSuccess] = useState(false);

  // -- Module 08: Reception State
  const [receptions, setReceptions] = useState([]);
  const [receptionForm, setReceptionForm] = useState({
    type_reception: 'Provisoire',
    date_reunion: '',
    commission_reception: '',
    conformite: 'Oui',
    reserves_observations: ''
  });
  const [savingReception, setSavingReception] = useState(false);
  const [receptionSuccess, setReceptionSuccess] = useState(false);

  // -- Module 09: Liquidation Financière State
  const [liquidationData, setLiquidationData] = useState(null);
  const [liquidationForm, setLiquidationForm] = useState({
    montant_a_payer: '', reference_facture: '', date_facture: '',
    ordre_imputation: '', ordre_paiement: '', ordre_virement: ''
  });
  const [savingLiquidation, setSavingLiquidation] = useState(false);
  const [liquidationSuccess, setLiquidationSuccess] = useState(false);
  const [liquidationError, setLiquidationError] = useState(null);

  // --- NOUVEAU : WORKFLOW ENGINE STATE ---
  const [workflowTimeline, setWorkflowTimeline] = useState([]);
  const [workflowLoading, setWorkflowLoading] = useState(false);

  const fetchWorkflowTimeline = async () => {
    try {
      setWorkflowLoading(true);
      const res = await api.get(`/engagements/workflow/${id}/timeline`);
      setWorkflowTimeline(res.data.timeline);
    } catch (err) {
      console.warn("Workflow non initialisé pour ce dossier.");
    } finally {
      setWorkflowLoading(false);
    }
  };

  useEffect(() => {
    fetchWorkflowTimeline();
  }, [id]);

  const hasWorkflowStep = (keywords) => {
    if (!workflowTimeline || !workflowTimeline.length) return true; // Fallback par défaut
    return workflowTimeline.some(step => keywords.some(kw => step.code.includes(kw)));
  };

  const stepDefinitions = [
    { key: 'programmation', label: 'Programmation', icon: Calendar },
    { key: 'estimation', label: 'Estimation', icon: Calculator },
    { key: 'consultation', label: 'Fournisseurs', icon: Users },
    { key: 'commission', label: 'Commission', icon: Award },
    { key: 'engagement', label: 'Engagement', icon: Briefcase }
  ];

  const visibleSteps = stepDefinitions;
  const currentStepIndex = visibleSteps.findIndex(step => step.key === activeTab);
  const prevStep = currentStepIndex > 0 ? visibleSteps[currentStepIndex - 1] : null;
  const nextStep = currentStepIndex >= 0 && currentStepIndex < visibleSteps.length - 1 ? visibleSteps[currentStepIndex + 1] : null;

  const getConsultationStatusBadge = (status) => {
    const base = 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide';
    switch (status) {
      case 'Programmation': return <span className={`${base} bg-blue-100 text-blue-700`}>Programmation</span>;
      case 'En cours': return <span className={`${base} bg-amber-100 text-amber-700`}>En cours</span>;
      case 'Validé': return <span className={`${base} bg-emerald-100 text-emerald-700`}>Validé</span>;
      case 'Clôturé': return <span className={`${base} bg-slate-100 text-slate-600`}>Clôturé</span>;
      default: return <span className={`${base} bg-slate-100 text-slate-600`}>{status}</span>;
    }
  };

  const fetchConsultationAndOffres = async () => {
    try {
      setLoading(true);
      const [resConsult, resOffres] = await Promise.all([
        api.get(`/consultations/${id}`),
        api.get(`/consultations/${id}/comparatif`)
      ]);
      
      const consultData = resConsult.data;
      setConsultation(consultData);
      setOffres(resOffres.data);
      
      if (consultData.prestations && consultData.prestations.length > 0) {
        setPrestations(consultData.prestations.map(p => ({ ...p, tempId: p.id })));
      } else {
        setPrestations([{ tempId: Date.now(), designation: '', unite: 'Forfait', quantite: 1, prix_unitaire_ht: 0, tva: 20 }]);
      }

      setCommissionForm({
        date_reunion: consultData.date_reunion || '',
        heure_reunion: consultData.heure_reunion ? consultData.heure_reunion.substring(0,5) : '',
        lieu_reunion: consultData.lieu_reunion || '',
        president_commission: consultData.president_commission || '',
        membres_commission: Array.isArray(consultData.membres_commission) ? consultData.membres_commission.join(', ') : (consultData.membres_commission || ''),
        observations_commission: consultData.observations_commission || ''
      });

      setProgrammationForm({
        objet_consultation: consultData.objet_consultation || '',
        objet_consultation_ar: consultData.objet_consultation_ar || '',
        mode_engagement: consultData.mode_engagement || '',
        date_consultation: consultData.date_consultation || '',
        lieu_consultation: consultData.lieu_consultation || consultData.lieu || '',
        budget_previsionnel: consultData.budget?.montant_ttc || '',
        notes_programmation: consultData.notes_programmation || '',
        cautionnement_provisoire: consultData.cautionnement_provisoire || '',
        lieu_reunion_ar: consultData.lieu_reunion_ar || ''
      });

      setOuverturePlisForm(resOffres.data.map(o => ({
        id: o.id,
        montant_apres_verification: o.montant_apres_verification || o.montant_propose || '',
        retenu: o.retenu ? true : false,
        observations_offre: o.observations_offre || ''
      })));

      setLoading(false);
    } catch (err) {
      console.error(err);
      setError('Impossible de charger les détails de la consultation.');
      setLoading(false);
    }
  };

  const fetchEngagementData = async () => {
    try {
      const res = await api.get(`/consultations/${id}/engagement/data`);
      setEngagementData(res.data);
      setEngagementError(null);
    } catch (err) {
      if (err.response?.status === 404) {
        setEngagementError("Aucun prestataire n'a été retenu. Veuillez d'abord valider l'attribution dans l'onglet Commission.");
      } else {
        setEngagementError("Erreur lors de la récupération des données d'engagement.");
      }
      setEngagementData(null);
    }
  };

  const fetchSuiviData = async () => {
    try {
      const res = await api.get(`/consultations/${id}/suivi`);
      setSuiviData(res.data.suivi);
      setSuiviForm({
        date_debut: res.data.suivi.date_debut || '',
        date_fin_previsionnelle: res.data.suivi.date_fin_previsionnelle || '',
        avancement_pourcentage: res.data.suivi.avancement_pourcentage || 0,
        statut_execution: res.data.suivi.statut_execution || 'Non démarré',
        observations: res.data.suivi.observations || ''
      });
    } catch (err) {
      console.error(err);
    }
  };

  const fetchReceptions = async () => {
    try {
      const res = await api.get(`/consultations/${id}/receptions`);
      setReceptions(res.data.receptions);
    } catch (err) {
      console.error(err);
    }
  };

  const fetchLiquidationData = async () => {
    try {
      const res = await api.get(`/consultations/${id}/liquidation`);
      setLiquidationData(res.data.liquidation);
      if (res.data.liquidation) {
        setLiquidationForm({
          montant_a_payer: res.data.liquidation.montant_a_payer || '',
          reference_facture: res.data.liquidation.reference_facture || '',
          date_facture: res.data.liquidation.date_facture || '',
          ordre_imputation: res.data.liquidation.ordre_imputation || '',
          ordre_paiement: res.data.liquidation.ordre_paiement || '',
          ordre_virement: res.data.liquidation.ordre_virement || ''
        });
      }
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchConsultationAndOffres();
  }, [id]);

  useEffect(() => {
    const loadConsultationsList = async () => {
      try {
        setConsultationsLoading(true);
        const res = await api.get('/consultations');
        const sortedData = Array.isArray(res.data) ? res.data.sort((a, b) => b.id - a.id) : [];
        setConsultationsList(sortedData);
      } catch (err) {
        console.error(err);
        setConsultationsError('Impossible de charger le menu des consultations.');
      } finally {
        setConsultationsLoading(false);
      }
    };

    loadConsultationsList();
  }, []);

  useEffect(() => {
    if (activeTab === 'engagement') fetchEngagementData();
    if (activeTab === 'suivi') fetchSuiviData();
    if (activeTab === 'reception') fetchReceptions();
    if (activeTab === 'liquidation') {
        fetchEngagementData(); // Pour le récap du montant engagé
        fetchLiquidationData();
    }
  }, [activeTab]);

  // --- LOGIQUE ESTIMATION ---

  const handleAddLine = () => setPrestations([...prestations, { tempId: Date.now(), designation: '', unite: 'Forfait', quantite: 1, prix_unitaire_ht: 0, tva: 20 }]);
  const handleRemoveLine = (tempId) => setPrestations(prestations.filter(p => p.tempId !== tempId));
  const handleChangeLine = (tempId, field, value) => setPrestations(prestations.map(p => p.tempId === tempId ? { ...p, [field]: value } : p));

  const calculateLineHT = (p) => (parseFloat(p.quantite) || 0) * (parseFloat(p.prix_unitaire_ht) || 0);
  const calculateLineTTC = (p) => calculateLineHT(p) * (1 + (parseFloat(p.tva) || 0) / 100);

  const totalHT = prestations.reduce((sum, p) => sum + calculateLineHT(p), 0);
  const totalTVA = prestations.reduce((sum, p) => sum + (calculateLineHT(p) * (parseFloat(p.tva) || 0) / 100), 0);
  const totalTTC = totalHT + totalTVA;

  const handleSaveEstimation = async () => {
    setSaving(true); setSaveError(null); setSaveSuccess(false);
    try {
      const payload = {
        prestations: prestations.map(p => ({
          designation: p.designation, unite: p.unite,
          quantite: parseFloat(p.quantite) || 0,
          prix_unitaire_ht: parseFloat(p.prix_unitaire_ht) || 0,
          tva: parseFloat(p.tva) || 0,
        }))
      };
      await api.post(`/consultations/${id}/prestations`, payload);
      setSaveSuccess(true);
      setConsultation(prev => ({ ...prev, prestations: payload.prestations }));
      setTimeout(() => setSaveSuccess(false), 5000);
      return true;
    } catch (err) {
      setSaveError(err.response?.data?.message || 'Erreur lors de l\'enregistrement des prestations.');
      return false;
    } finally {
      setSaving(false);
    }
  };

  const saveProgrammation = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingProgrammation(true); setProgrammationSuccess(false); setProgrammationError(null);
    try {
      const payload = {
        ...programmationForm
      };
      await api.put(`/consultations/${id}`, payload);
      setProgrammationSuccess(true);
      setConsultation(prev => ({ ...prev, ...payload }));
      setTimeout(() => setProgrammationSuccess(false), 5000);
      return true;
    } catch (err) {
      setProgrammationError(err.response?.data?.message || 'Erreur lors de la sauvegarde de la programmation.');
      return false;
    } finally {
      setSavingProgrammation(false);
    }
  };

  const handleContinue = async () => {
    if (!nextStep) return;

    let canProceed = true;

    switch (activeTab) {
      case 'programmation':
        canProceed = await saveProgrammation();
        break;
      case 'estimation':
        canProceed = await handleSaveEstimation();
        break;
      case 'consultation':
        canProceed = true;
        break;
      case 'commission':
        canProceed = await saveCommission();
        break;
      case 'engagement':
        canProceed = await saveEngagement();
        break;
      case 'suivi':
        canProceed = await saveSuivi();
        break;
      case 'reception':
        canProceed = await saveReception();
        break;
      case 'liquidation':
        canProceed = await saveLiquidation();
        break;
      default:
        canProceed = true;
    }

    if (canProceed) {
      setActiveTab(nextStep.key);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const handleDownloadPdf = async () => {
    try {
      const response = await api.get(`/consultations/${id}/pdf-estimation`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `Dossier_Estimation_${consultation.numero_consultation}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
    } catch (err) {
      alert('Erreur lors de la génération du PDF.');
    }
  };

  // --- LOGIQUE FOURNISSEURS ---

  const openSelectionModal = async () => {
    try {
      const res = await api.get('/fournisseurs');
      setAllFournisseurs(res.data);
      setSelectedFournisseurIds(offres.map(o => o.fournisseur_id));
      setIsSelectionModalOpen(true);
    } catch (err) {
      alert("Erreur lors du chargement des fournisseurs.");
    }
  };

  const toggleFournisseurSelection = (fid) => setSelectedFournisseurIds(prev => prev.includes(fid) ? prev.filter(id => id !== fid) : [...prev, fid]);

  const submitSelection = async () => {
    try {
      await api.post(`/consultations/${id}/fournisseurs`, { fournisseur_ids: selectedFournisseurIds });
      setIsSelectionModalOpen(false);
      await fetchConsultationAndOffres();
    } catch (err) {
      alert("Erreur lors de l'association des fournisseurs.");
    }
  };

  const openDevisModal = (offre) => {
    setCurrentOffre(offre);
    setDevisForm({ montant_propose: offre.montant_propose || '', statut_reponse: offre.statut_reponse || 'Reçu' });
    setIsDevisModalOpen(true);
  };

  const submitDevis = async (e) => {
    e.preventDefault();
    try {
      await api.put(`/consultations/${id}/fournisseurs/${currentOffre.fournisseur_id}/devis`, {
        montant_propose: devisForm.montant_propose ? parseFloat(devisForm.montant_propose) : null,
        statut_reponse: devisForm.statut_reponse
      });
      setIsDevisModalOpen(false);
      await fetchConsultationAndOffres();
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
    }
  };

  // --- LOGIQUE COMMISSION ---

  const saveCommission = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingCommission(true); setCommissionSuccess(false);
    try {
      const payload = { ...commissionForm, membres_commission: commissionForm.membres_commission ? commissionForm.membres_commission.split(',').map(m => m.trim()) : [] };
      await api.post(`/consultations/${id}/commission`, payload);
      setCommissionSuccess(true);
      setTimeout(() => setCommissionSuccess(false), 4000);
      return true;
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
      return false;
    } finally {
      setSavingCommission(false);
    }
  };

  const handleChangeOuverture = (offreId, field, value) => {
    setOuverturePlisForm(prev => prev.map(o => o.id === offreId ? { ...o, [field]: value } : o));
    if (field === 'retenu' && value === true) {
      setOuverturePlisForm(prev => prev.map(o => o.id === offreId ? { ...o, retenu: true } : { ...o, retenu: false }));
    }
  };

  const saveOuverturePlis = async () => {
    setSavingOuverture(true); setOuvertureSuccess(false);
    try {
      const payload = { offres: ouverturePlisForm.map(o => ({ id: o.id, montant_apres_verification: o.montant_apres_verification ? parseFloat(o.montant_apres_verification) : null, retenu: o.retenu, observations_offre: o.observations_offre })) };
      await api.post(`/consultations/${id}/ouverture-plis`, payload);
      setOuvertureSuccess(true);
      await fetchConsultationAndOffres();
      setTimeout(() => setOuvertureSuccess(false), 4000);
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
    } finally {
      setSavingOuverture(false);
    }
  };

  // --- LOGIQUE ENGAGEMENT ---

  const saveEngagement = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingEngagement(true); setEngagementSuccess(false); setEngagementError(null);
    try {
      const payload = { fournisseur_id: engagementData.fournisseur.id, montant_engagement: engagementData.montant_valide, type_engagement: engagementData.type_engagement, ...engagementForm };
      await api.post(`/consultations/${id}/engagement`, payload);
      setEngagementSuccess(true);
      setConsultation(prev => ({ ...prev, statut_dossier: 'ENGAGÉ' }));
      setTimeout(() => setEngagementSuccess(false), 5000);
      return true;
    } catch (err) {
      setEngagementError(err.response?.data?.message || "Erreur inattendue.");
      return false;
    } finally {
      setSavingEngagement(false);
    }
  };

  // --- LOGIQUE SUIVI EXÉCUTION ---

  const saveSuivi = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingSuivi(true); setSuiviSuccess(false);
    try {
      await api.put(`/consultations/${id}/suivi`, suiviForm);
      setSuiviSuccess(true);
      await fetchSuiviData();
      setTimeout(() => setSuiviSuccess(false), 4000);
      return true;
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
      return false;
    } finally {
      setSavingSuivi(false);
    }
  };

  const submitEvenement = async (e) => {
    e.preventDefault();
    setSavingEvenement(true); setEvenementSuccess(false);
    try {
      await api.post(`/consultations/${id}/suivi/evenements`, evenementForm);
      setEvenementSuccess(true);
      setEvenementForm({...evenementForm, description: ''}); // reset description
      await fetchSuiviData();
      setTimeout(() => setEvenementSuccess(false), 4000);
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
    } finally {
      setSavingEvenement(false);
    }
  };

  // --- LOGIQUE RÉCEPTION ---
  const saveReception = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingReception(true); setReceptionSuccess(false);
    try {
      const payload = { 
        ...receptionForm, 
        commission_reception: receptionForm.commission_reception ? receptionForm.commission_reception.split(',').map(m => m.trim()) : [] 
      };
      const res = await api.post(`/consultations/${id}/receptions`, payload);
      setReceptionSuccess(true);
      if (res.data.statut_dossier) setConsultation(prev => ({...prev, statut_dossier: res.data.statut_dossier}));
      await fetchReceptions();
      setTimeout(() => setReceptionSuccess(false), 4000);
      setReceptionForm({ type_reception: 'Provisoire', date_reunion: '', commission_reception: '', conformite: 'Oui', reserves_observations: '' });
      return true;
    } catch (err) {
      alert("Erreur: " + (err.response?.data?.message || err.message));
      return false;
    } finally {
      setSavingReception(false);
    }
  };

  // --- LOGIQUE LIQUIDATION FINANCIÈRE ---
  const saveLiquidation = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setSavingLiquidation(true); setLiquidationSuccess(false); setLiquidationError(null);
    try {
      const res = await api.post(`/consultations/${id}/liquidation`, liquidationForm);
      setLiquidationSuccess(true);
      if (res.data.statut_dossier) setConsultation(prev => ({...prev, statut_dossier: res.data.statut_dossier}));
      await fetchLiquidationData();
      setTimeout(() => setLiquidationSuccess(false), 5000);
      return true;
    } catch (err) {
      setLiquidationError(err.response?.data?.message || err.message);
      return false;
    } finally {
      setSavingLiquidation(false);
    }
  };

  // --- LOGIQUE DOCUMENTS & ARCHIVES ---
  const [downloadingZip, setDownloadingZip] = useState(false);
  const downloadDocument = async (doc) => {
    try {
      const isConvention = consultation?.mode_engagement && !consultation.mode_engagement.includes('BC') && !consultation.mode_engagement.includes('Commande');
      const endpoint = isConvention && doc.endpoint
        ? `/marche/${doc.endpoint}/${id}` 
        : `/consultations/${id}/documents/${doc.id}`;
        
      const response = await api.get(endpoint, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${doc.id}_${consultation.numero_consultation}.pdf`);
    } catch (err) {
      alert("Erreur lors de la génération de l'archive ZIP.");
    } finally {
      setDownloadingZip(false);
    }
  };

  const generateAndDownloadDocument = async (typeDocument) => {
    try {
      const genRes = await api.post(`/consultations/${id}/documents/${typeDocument}/generate`, {});
      const generatedDocId = genRes.data.document.id;
      
      const dlRes = await api.get(`/consultations/documents/${generatedDocId}/download`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([dlRes.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${typeDocument}_${consultation?.numero_consultation}.docx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      alert("Erreur lors de la génération: " + (err.response?.data?.error || err.message));
    }
  };

  const downloadArchiveZip = async () => {
    try {
      setDownloadingZip(true);
      const response = await api.get(`/consultations/${id}/documents/archive-zip`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `Dossier_Administratif_${consultation.numero_consultation}.zip`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      alert("Erreur lors de la génération de l'archive ZIP.");
    } finally {
      setDownloadingZip(false);
    }
  };

  const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0).replace('MAD', 'dh');

  const offresRecues = offres.filter(o => o.statut_reponse === 'Reçu' && o.montant_propose > 0);
  const moinsDisant = offresRecues.length > 0 ? offresRecues.reduce((prev, curr) => (parseFloat(prev.montant_propose) < parseFloat(curr.montant_propose) ? prev : curr)) : null;

  if (loading) return <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900"><Loader2 className="animate-spin text-primary" size={48} /></div>;
  if (error || !consultation) return <div className="min-h-screen flex items-center justify-center"><div className="bg-red-50 p-6 text-red-600 font-bold">{error}</div></div>;

  return (
    <div className="p-10 bg-gray-50 dark:bg-gray-900 min-h-screen pb-24">
      <div className="max-w-none mx-auto xl:grid xl:grid-cols-[320px_1fr] xl:gap-10">
        <aside className="mb-10 xl:mb-0">
          <div className="sticky top-8 space-y-6">
            <section className="rounded-3xl border border-slate-200 bg-white shadow-sm p-6">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Consultations</p>
                  <h2 className="mt-3 text-2xl font-extrabold text-slate-900">Workflow unifié</h2>
                </div>
                <div className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                  <Calendar size={20} />
                </div>
              </div>
              <p className="mt-4 text-sm text-slate-500">Sélectionnez une consultation et poursuivez le suivi dans une seule interface.</p>
              <Link to="/consultations/nouvelle" className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                <Plus size={18} /> Nouvelle Consultation
              </Link>
            </section>

            <section className="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
              <div className="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 className="text-sm font-bold uppercase tracking-wider text-slate-600">Consultations</h3>
              </div>
              <div className="divide-y divide-slate-200">
                {consultationsLoading ? (
                  <div className="p-4 text-sm text-slate-500">Chargement...</div>
                ) : consultationsError ? (
                  <div className="p-4 text-sm text-red-600">{consultationsError}</div>
                ) : consultationsList.length === 0 ? (
                  <div className="p-4 text-sm text-slate-500">Aucune consultation disponible.</div>
                ) : consultationsList.map(item => (
                  <button
                    key={item.id}
                    onClick={() => navigate(`/consultations/${item.id}`)}
                    className={`w-full text-left px-6 py-4 transition-colors ${item.id === Number(id) ? 'bg-blue-50 text-blue-900' : 'hover:bg-slate-50'}`}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <div className="text-sm font-semibold">{item.numero_consultation || `Consultation #${item.id}`}</div>
                      {item.id === Number(id) && <span className="text-[11px] font-bold uppercase tracking-[0.25em] text-blue-700">Active</span>}
                    </div>
                    <p className="mt-2 text-sm text-slate-600 line-clamp-2">{item.objet_consultation || 'Sans objet'}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{item.mode_engagement || 'Mode inconnu'}</span>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{item.annee || 'N/A'}</span>
                      </div>
                      <div className="mt-3">
                        {getConsultationStatusBadge(item.statut_dossier)}
                      </div>
                    </button>
                ))}
              </div>
            </section>
          </div>
        </aside>

        <main className="space-y-10">
        
        {/* HEADER */}
        <header className="flex items-center justify-between mb-8">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button onClick={() => navigate('/consultations')} className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500">
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Détails de la Consultation</h1>
              <span className={`px-3 py-1 rounded-full text-xs font-bold ml-4 border ${
                consultation.statut_dossier === 'ENGAGÉ' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-700 border-gray-200'
              }`}>
                {consultation.statut_dossier}
              </span>
            </div>
            <p className="text-gray-500 dark:text-gray-400 ml-11">
              Réf : <span className="font-mono text-primary font-bold">{consultation.numero_consultation}</span>
            </p>
          </div>
          <div className="flex gap-4">
            {(consultation?.prestations?.length > 0 || saveSuccess) && (
              <button onClick={handleDownloadPdf} className="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all flex items-center gap-2">
                <Download size={20} className="text-blue-500" /> Documents Officiels
              </button>
            )}
          </div>
        </header>

        <div className="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-center justify-between gap-4 mb-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Progression</p>
              <h2 className="mt-2 text-lg font-bold text-slate-900">Parcours de la consultation</h2>
            </div>
            <div className="text-sm font-semibold text-slate-600">{currentStepIndex + 1} / {visibleSteps.length}</div>
          </div>
          <div className="flex w-full items-start justify-between gap-4 py-4">
            {visibleSteps.map((step, index) => (
              <div key={step.key} className="flex-1 min-w-0 text-center px-1">
                <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border text-sm font-bold ${step.key === activeTab ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : index < currentStepIndex ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-100 text-slate-500'}`}>
                  {index + 1}
                </div>
                <div className="mx-auto max-w-[110px] px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600 leading-tight sm:text-[11px]">
                  {step.label}
                </div>
                <div className={`mx-auto mt-2 h-1 w-10 rounded-full ${index < currentStepIndex ? 'bg-blue-500' : 'bg-slate-200'}`}></div>
              </div>
            ))}
          </div>
        </div>

        {/* TAB 1: PROGRAMMATION */}
        {activeTab === 'programmation' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {programmationSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Programmation enregistrée avec succès.</p></div>}
            {programmationError && <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600"><AlertCircle size={20} /><p className="font-medium">{programmationError}</p></div>}

            <section className="w-full bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
              <div className="p-6 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                <div className="p-2 bg-blue-100 text-blue-600 rounded-lg"><Calendar size={20} /></div>
                <div>
                  <h2 className="text-xl font-bold text-gray-800">Programmation</h2>
                  <p className="text-sm text-gray-500">Remplissez les informations générales avant de lancer le cycle.</p>
                </div>
              </div>
              <form onSubmit={saveProgrammation} className="p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Réf. Consultation</label>
                    <input type="text" value={consultation.numero_consultation || ''} disabled className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-500 font-mono" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Année</label>
                    <input type="text" value={consultation.annee || ''} disabled className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-500 font-mono" />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Objet de la consultation</label>
                    <input type="text" value={programmationForm.objet_consultation} onChange={e => setProgrammationForm({...programmationForm, objet_consultation: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl" placeholder="Objet de la consultation" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Mode d'engagement</label>
                    <input type="text" value={programmationForm.mode_engagement} onChange={e => setProgrammationForm({...programmationForm, mode_engagement: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl" placeholder="Ex: BC, Convention, etc." />
                  </div>
                </div>

                <div className="mb-6">
                  <label className="block text-sm font-semibold text-gray-700 mb-1">Objet de la consultation (Arabe)</label>
                  <input type="text" dir="rtl" value={programmationForm.objet_consultation_ar} onChange={e => setProgrammationForm({...programmationForm, objet_consultation_ar: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl font-arabic" placeholder="موضوع الاستشارة" />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Date de consultation</label>
                    <input type="date" value={programmationForm.date_consultation} onChange={e => setProgrammationForm({...programmationForm, date_consultation: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Budget prévisionnel (TTC)</label>
                    <input type="number" step="0.01" value={programmationForm.budget_previsionnel} onChange={e => setProgrammationForm({...programmationForm, budget_previsionnel: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl font-mono" placeholder="Montant prévisionnel" />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Lieu</label>
                    <input type="text" value={programmationForm.lieu_consultation} onChange={e => setProgrammationForm({...programmationForm, lieu_consultation: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl" placeholder="Lieu de consultation" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Lieu (Arabe)</label>
                    <input type="text" dir="rtl" value={programmationForm.lieu_reunion_ar} onChange={e => setProgrammationForm({...programmationForm, lieu_reunion_ar: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl font-arabic" placeholder="مكان فتح الأظرفة" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Cautionnement Prov.</label>
                    <input type="number" step="0.01" value={programmationForm.cautionnement_provisoire} onChange={e => setProgrammationForm({...programmationForm, cautionnement_provisoire: e.target.value})} className="w-full p-3 border border-gray-200 rounded-xl font-mono" placeholder="Ex: 5000.00" />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">Notes de programmation</label>
                  <textarea rows="4" value={programmationForm.notes_programmation} onChange={e => setProgrammationForm({...programmationForm, notes_programmation: e.target.value})} className="w-full p-3 border border-gray-200 rounded-2xl" placeholder="Objectifs, contraintes, équipe, observations..." />
                </div>

                <div className="flex justify-end">
                  <button type="submit" disabled={savingProgrammation} className="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 flex items-center gap-2 disabled:opacity-50">
                    {savingProgrammation ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />} Enregistrer
                  </button>
                </div>
              </form>
            </section>

            <PhaseDocuments title="Programmation" phaseCode="01" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['programmation', 'preparation', 'creation']} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 1: ESTIMATION */}
        {activeTab === 'estimation' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {saveSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Estimation enregistrée avec succès !</p></div>}
            {saveError && <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600"><AlertCircle size={20} /><p className="font-medium">{saveError}</p></div>}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div className="lg:col-span-1 space-y-8">
                <section className="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100">
                  <div className="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100"><div className="p-2 bg-blue-100 text-blue-600 rounded-lg"><FileText size={20} /></div><h2 className="text-lg font-bold text-gray-800 dark:text-white">Infos Générales</h2></div>
                  <div className="space-y-3 text-sm">
                    <div className="flex justify-between"><span className="text-gray-500">Objet</span><span className="font-medium text-right max-w-[200px] truncate" title={consultation.objet_consultation}>{consultation.objet_consultation}</span></div>
                    <div className="flex justify-between"><span className="text-gray-500">Date</span><span className="font-medium">{consultation.date_consultation}</span></div>
                    <div className="flex justify-between"><span className="text-gray-500">Mode</span><span className="font-medium">{consultation.mode_engagement}</span></div>
                  </div>
                </section>
                <section className="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100">
                  <div className="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100"><div className="p-2 bg-amber-100 text-amber-600 rounded-lg"><Calculator size={20} /></div><h2 className="text-lg font-bold text-gray-800 dark:text-white">Budget (Enveloppe)</h2></div>
                  {consultation.budget ? (
                    <div className="space-y-3 text-sm">
                      <div className="flex justify-between"><span className="text-gray-500">Imputation</span><span className="font-medium font-mono">{consultation.budget.code_imputation}</span></div>
                      <div className="flex justify-between"><span className="text-gray-500">Montant TTC</span><span className="font-bold text-primary">{formatCurrency(consultation.budget.montant_ttc)} dh</span></div>
                    </div>
                  ) : <p className="text-gray-500 italic">Aucun budget défini.</p>}
                </section>
              </div>

              <div className="lg:col-span-2">
                <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden h-full flex flex-col">
                  <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 className="text-xl font-bold text-gray-800 dark:text-white">Estimation Administrative</h2>
                    <div className="flex gap-2">
                      <button onClick={handleAddLine} className="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg shadow-sm hover:bg-gray-50 text-sm flex items-center gap-2"><Plus size={16} /> Ligne</button>
                      <button onClick={handleSaveEstimation} disabled={saving || prestations.length === 0} className="px-4 py-2 bg-primary text-white font-bold rounded-lg shadow-md hover:bg-primary-dark transition-all flex items-center gap-2 disabled:opacity-50">
                        {saving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Enregistrer
                      </button>
                    </div>
                  </div>
                  <div className="overflow-x-auto flex-1">
                    <table className="w-full text-left border-collapse">
                      <thead><tr className="bg-gray-50 border-b border-gray-100"><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Désignation</th><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Unité</th><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Qté</th><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Prix U. (HT)</th><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">TVA</th><th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">TTC</th><th className="px-4 py-3 text-center"></th></tr></thead>
                      <tbody className="divide-y divide-gray-100">
                        {prestations.map(p => (
                          <tr key={p.tempId} className="hover:bg-gray-50/50">
                            <td className="p-2"><input type="text" value={p.designation} onChange={(e) => handleChangeLine(p.tempId, 'designation', e.target.value)} className="w-full p-2 text-sm border border-gray-200 rounded" required /></td>
                            <td className="p-2 w-20"><input type="text" value={p.unite} onChange={(e) => handleChangeLine(p.tempId, 'unite', e.target.value)} className="w-full p-2 text-sm border border-gray-200 rounded" /></td>
                            <td className="p-2 w-20"><input type="number" min="0.01" step="0.01" value={p.quantite} onChange={(e) => handleChangeLine(p.tempId, 'quantite', e.target.value)} className="w-full p-2 text-sm border border-gray-200 rounded" required /></td>
                            <td className="p-2 w-28"><input type="number" min="0" step="0.01" value={p.prix_unitaire_ht} onChange={(e) => handleChangeLine(p.tempId, 'prix_unitaire_ht', e.target.value)} className="w-full p-2 text-sm border border-gray-200 rounded font-mono text-right" required /></td>
                            <td className="p-2 w-20"><select value={p.tva} onChange={(e) => handleChangeLine(p.tempId, 'tva', e.target.value)} className="w-full p-2 text-sm border border-gray-200 rounded"><option value="20">20%</option><option value="14">14%</option><option value="10">10%</option><option value="7">7%</option><option value="0">0%</option></select></td>
                            <td className="p-2 whitespace-nowrap text-right"><span className="font-mono font-medium text-gray-800">{formatCurrency(calculateLineTTC(p))}</span></td>
                            <td className="p-2 text-center"><button type="button" onClick={() => handleRemoveLine(p.tempId)} className="p-1.5 text-gray-400 hover:text-red-600"><Trash2 size={16} /></button></td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  <div className="bg-gray-50 p-4 border-t border-gray-100 flex justify-end">
                    <div className="flex items-center gap-6 text-sm">
                      <div><span className="text-gray-500 mr-2">Total HT:</span><span className="font-mono">{formatCurrency(totalHT)}</span></div>
                      <div><span className="text-gray-500 mr-2">TVA:</span><span className="font-mono">{formatCurrency(totalTVA)}</span></div>
                      <div className="text-lg font-bold"><span className="text-gray-800 mr-2">TTC:</span><span className="text-primary">{formatCurrency(totalTTC)} dh</span></div>
                    </div>
                  </div>
                </section>
              </div>
            </div>
            <div className="mt-8 p-6 bg-slate-50 border border-slate-200 rounded-2xl">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2 uppercase tracking-wider">
                  <FileText size={16} className="text-blue-600"/> Génération des Documents Officiels
                </h3>
              </div>
              <div className="flex flex-wrap gap-3">
                <button onClick={() => generateAndDownloadDocument('estimation')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> Estimation administrative
                </button>
                <button onClick={() => generateAndDownloadDocument('rc')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> RC - Règlement de Consultation
                </button>
                <button onClick={() => generateAndDownloadDocument('bordereau')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> Bordereau des prix
                </button>
                <button onClick={() => generateAndDownloadDocument('avis_fr')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> Avis de publication (FR)
                </button>
                <button onClick={() => generateAndDownloadDocument('avis_ar')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> Avis de publication (AR)
                </button>
                <button onClick={() => generateAndDownloadDocument('lettre_ecartement')} className="px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all flex items-center gap-2">
                  <FileText size={16} className="text-blue-500"/> Lettre d'écartement
                </button>
              </div>
            </div>
          </div>
        )}

        {/* TAB 2: CONSULTATION */}
        {activeTab === 'consultation' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
              <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-indigo-100 text-indigo-600 rounded-lg"><Users size={20} /></div>
                  <h2 className="text-xl font-bold text-gray-800">Suivi des Consultations (Devis)</h2>
                </div>
                <button onClick={openSelectionModal} className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2 text-sm">
                  <Plus size={16} /> Sélectionner Fournisseurs
                </button>
              </div>
              <div className="p-6">
                {offres.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">Aucun fournisseur n'a été consulté.</div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                      <thead>
                        <tr className="bg-gray-50 border-b border-gray-100">
                          <th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Fournisseur</th>
                          <th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Statut</th>
                          <th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right">Montant Proposé (dh)</th>
                          <th className="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Action</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {offres.map(o => (
                          <tr key={o.id} className={`hover:bg-gray-50`}>
                            <td className="px-4 py-3 font-bold text-gray-800">{o.fournisseur?.raison_sociale}</td>
                            <td className="px-4 py-3">
                              <span className={`px-2 py-1 rounded text-xs font-bold uppercase ${
                                o.statut_reponse === 'Reçu' ? 'bg-emerald-100 text-emerald-700' :
                                o.statut_reponse === 'Refusé' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'
                              }`}>{o.statut_reponse}</span>
                            </td>
                            <td className={`px-4 py-3 text-right font-mono font-bold text-gray-800`}>
                              {o.montant_propose ? formatCurrency(o.montant_propose) : '-'}
                            </td>
                            <td className="px-4 py-3 text-center">
                              <button onClick={() => openDevisModal(o)} className="px-3 py-1.5 bg-white border border-gray-200 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center justify-center gap-2 mx-auto"><Edit size={14} /> Devis</button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </section>
            <PhaseDocuments title="Consultation" phaseCode="02" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['consultation', 'preparation', 'creation']} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 3: COMMISSION */}
        {activeTab === 'commission' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {commissionSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Infos enregistrées.</p></div>}
            {ouvertureSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Attribution validée.</p></div>}
            {/* Formulaires simplifiés pour l'exemple de tab switcher */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex items-center gap-3 bg-gray-50"><div className="p-2 bg-blue-100 text-blue-600 rounded-lg"><Calendar size={20} /></div><h2 className="text-xl font-bold">Commission d'Évaluation</h2></div>
                <form onSubmit={saveCommission} className="p-6 space-y-5">
                  <div className="grid grid-cols-2 gap-4">
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Date</label><input type="date" value={commissionForm.date_reunion} onChange={e => setCommissionForm({...commissionForm, date_reunion: e.target.value})} className="w-full p-2 border rounded-lg" /></div>
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Heure</label><input type="time" value={commissionForm.heure_reunion} onChange={e => setCommissionForm({...commissionForm, heure_reunion: e.target.value})} className="w-full p-2 border rounded-lg" /></div>
                  </div>
                  <div><label className="block text-sm font-semibold text-gray-700 mb-1">Président</label><input type="text" value={commissionForm.president_commission} onChange={e => setCommissionForm({...commissionForm, president_commission: e.target.value})} className="w-full p-2 border rounded-lg" /></div>
                  <div className="flex justify-end pt-2"><button type="submit" disabled={savingCommission} className="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700">{savingCommission ? 'En cours...' : 'Enregistrer'}</button></div>
                </form>
              </section>

              <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex items-center gap-3 bg-gray-50"><div className="p-2 bg-emerald-100 text-emerald-600 rounded-lg"><FileCheck size={20} /></div><h2 className="text-xl font-bold">Ouverture & Attribution</h2></div>
                <div className="p-6">
                  {ouverturePlisForm.length > 0 ? (
                    <div className="space-y-4">
                      {ouverturePlisForm.map(formRow => (
                        <div key={formRow.id} className={`p-4 border rounded-2xl ${formRow.retenu ? 'border-emerald-400 bg-emerald-50/30' : 'border-gray-200'}`}>
                          <div className="flex items-center justify-between mb-3"><h3 className="font-bold text-gray-900">{offres.find(o=>o.id===formRow.id)?.fournisseur?.raison_sociale}</h3>
                          <label className="flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 border rounded-lg"><input type="checkbox" checked={formRow.retenu} onChange={e => handleChangeOuverture(formRow.id, 'retenu', e.target.checked)} className="w-5 h-5 text-emerald-600 rounded" /><span className="font-bold text-sm">Retenir</span></label></div>
                          <input type="number" step="0.01" value={formRow.montant_apres_verification} onChange={e => handleChangeOuverture(formRow.id, 'montant_apres_verification', e.target.value)} className="w-full p-2 text-sm border rounded-lg" placeholder="Montant final..." />
                        </div>
                      ))}
                      <div className="flex justify-end pt-4"><button onClick={saveOuverturePlis} disabled={savingOuverture} className="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700">Valider l'Attribution</button></div>
                    </div>
                  ) : <div className="text-gray-500 italic text-center py-4">Aucune offre.</div>}
                </div>
              </section>
            </div>
            <PhaseDocuments title="Commission & Attribution" phaseCode="03" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['ouverture', 'commission']} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 4: ENGAGEMENT */}
        {activeTab === 'engagement' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-4xl mx-auto">
            {engagementSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Engagement validé avec succès !</p></div>}
            {engagementError && <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600"><AlertCircle size={20} /><p className="font-medium">{engagementError}</p></div>}
            {engagementData && (
              <>
                <div className="bg-slate-50 border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row items-center gap-6 justify-between">
                  <div className="flex items-center gap-4"><div className="p-4 bg-slate-200 text-slate-700 rounded-2xl"><Briefcase size={28} /></div><div><p className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Fournisseur Retenu</p><h3 className="text-xl font-extrabold text-slate-900">{engagementData.fournisseur.raison_sociale}</h3></div></div>
                  <div className="flex gap-8"><div className="text-right"><p className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Type Engagement</p><span className="px-3 py-1 bg-white border border-slate-300 rounded-lg font-bold">{engagementData.type_engagement}</span></div><div className="text-right"><p className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Montant Validé</p><span className="text-2xl font-black text-emerald-600">{formatCurrency(engagementData.montant_valide)} <span className="text-sm font-bold text-emerald-600/70">dh</span></span></div></div>
                </div>
                <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                  <div className="p-6 border-b border-gray-100 bg-gray-50"><h2 className="text-xl font-bold text-gray-800 flex items-center gap-3"><PenTool size={20} className="text-blue-600" /> Formalisation de l'Engagement</h2></div>
                  <form onSubmit={saveEngagement} className="p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div><label className="block text-sm font-semibold text-gray-700 mb-1">Numéro d'Engagement</label><input type="text" value="Généré automatiquement (Ex: ENG-2026-...)" disabled className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-500 font-mono italic" /></div>
                      <div><label className="block text-sm font-semibold text-gray-700 mb-1">Date d'engagement</label><input type="date" required value={engagementForm.date_engagement} onChange={e => setEngagementForm({...engagementForm, date_engagement: e.target.value})} className="w-full p-3 border rounded-xl" /></div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div><label className="block text-sm font-semibold text-gray-700 mb-1">Date notification</label><input type="date" value={engagementForm.date_notification} onChange={e => setEngagementForm({...engagementForm, date_notification: e.target.value})} className="w-full p-3 border rounded-xl" /></div>
                      <div><label className="block text-sm font-semibold text-gray-700 mb-1">Date commencement</label><input type="date" value={engagementForm.date_commencement} onChange={e => setEngagementForm({...engagementForm, date_commencement: e.target.value})} className="w-full p-3 border rounded-xl" /></div>
                      <div><label className="block text-sm font-semibold text-gray-700 mb-1">Délai d'exécution</label><div className="relative"><input type="number" min="1" required value={engagementForm.delai_execution} onChange={e => setEngagementForm({...engagementForm, delai_execution: e.target.value})} className="w-full p-3 pr-16 border rounded-xl font-mono" /><span className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">jours</span></div></div>
                    </div>
                    <div className="pt-6 border-t border-gray-100 flex justify-end"><button type="submit" disabled={savingEngagement || consultation.statut_dossier === 'ENGAGÉ'} className="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 flex items-center gap-3 disabled:opacity-50">{savingEngagement ? <Loader2 size={20} className="animate-spin"/> : <PenTool size={20}/>} {consultation.statut_dossier === 'ENGAGÉ' ? "Déjà engagé" : "Valider l'Engagement"}</button></div>
                  </form>
                </section>
              </>
            )}
            <PhaseDocuments title="Engagement" phaseCode="04" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['engagement', 'attribution', 'signature']} disabled={false} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 5: SUIVI D'EXECUTION (MODULE 07) */}
        {activeTab === 'suivi' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {suiviSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Mise à jour du suivi effectuée avec succès !</p></div>}
            {evenementSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Événement ajouté à l'historique !</p></div>}
            
            {suiviData && (
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {/* COLONNE GAUCHE : ETAT GLOBAL */}
                <div className="lg:col-span-1 space-y-8">
                  <section className="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100">
                    <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100"><div className="p-2 bg-indigo-100 text-indigo-600 rounded-lg"><Activity size={20} /></div><h2 className="text-lg font-bold text-gray-800">État d'Avancement</h2></div>
                    
                    <div className="space-y-6">
                      {/* Progress Bar */}
                      <div>
                        <div className="flex justify-between items-end mb-2">
                          <span className="text-sm font-bold text-gray-500 uppercase tracking-wider">Réalisation</span>
                          <span className="text-3xl font-black text-indigo-600">{suiviData.avancement_pourcentage}%</span>
                        </div>
                        <div className="w-full bg-gray-100 rounded-full h-4 overflow-hidden shadow-inner">
                          <div className={`h-4 rounded-full transition-all duration-1000 ease-out ${
                            suiviData.avancement_pourcentage === 100 ? 'bg-emerald-500' :
                            suiviData.avancement_pourcentage > 50 ? 'bg-indigo-500' :
                            suiviData.avancement_pourcentage > 0 ? 'bg-amber-400' : 'bg-gray-300'
                          }`} style={{ width: `${suiviData.avancement_pourcentage}%` }}></div>
                        </div>
                      </div>

                      <div className="pt-4 border-t border-gray-100">
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border ${
                          suiviData.statut_execution === 'Terminé' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          suiviData.statut_execution === 'En cours' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          suiviData.statut_execution === 'Bloqué' ? 'bg-red-50 text-red-700 border-red-200' :
                          'bg-gray-100 text-gray-700 border-gray-200'
                        }`}>
                          {suiviData.statut_execution === 'En cours' && <PlayCircle size={14}/>}
                          {suiviData.statut_execution === 'Bloqué' && <StopCircle size={14}/>}
                          {suiviData.statut_execution === 'Terminé' && <CheckCircle2 size={14}/>}
                          {suiviData.statut_execution}
                        </span>
                      </div>
                    </div>
                  </section>

                  <section className="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100">
                    <h3 className="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider">Mettre à jour le suivi</h3>
                    <form onSubmit={saveSuivi} className="space-y-4">
                      <div>
                        <label className="block text-xs font-semibold text-gray-500 mb-1">Pourcentage d'avancement</label>
                        <div className="flex items-center gap-3">
                          <input type="range" min="0" max="100" step="5" value={suiviForm.avancement_pourcentage} onChange={e => setSuiviForm({...suiviForm, avancement_pourcentage: parseInt(e.target.value)})} className="w-full accent-indigo-600" />
                          <span className="font-mono font-bold w-12 text-right">{suiviForm.avancement_pourcentage}%</span>
                        </div>
                      </div>
                      <div>
                        <label className="block text-xs font-semibold text-gray-500 mb-1">Statut global</label>
                        <select value={suiviForm.statut_execution} onChange={e => setSuiviForm({...suiviForm, statut_execution: e.target.value})} className="w-full p-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
                          <option value="Non démarré">Non démarré</option>
                          <option value="En cours">En cours</option>
                          <option value="Bloqué">Bloqué</option>
                          <option value="Terminé">Terminé</option>
                        </select>
                      </div>
                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs font-semibold text-gray-500 mb-1">Date Début</label>
                          <input type="date" value={suiviForm.date_debut} onChange={e => setSuiviForm({...suiviForm, date_debut: e.target.value})} className="w-full p-2 text-sm border border-gray-200 rounded-lg" />
                        </div>
                        <div>
                          <label className="block text-xs font-semibold text-gray-500 mb-1">Fin Prév.</label>
                          <input type="date" value={suiviForm.date_fin_previsionnelle} onChange={e => setSuiviForm({...suiviForm, date_fin_previsionnelle: e.target.value})} className="w-full p-2 text-sm border border-gray-200 rounded-lg" />
                        </div>
                      </div>
                      <div>
                        <label className="block text-xs font-semibold text-gray-500 mb-1">Observations générales</label>
                        <textarea value={suiviForm.observations} onChange={e => setSuiviForm({...suiviForm, observations: e.target.value})} rows="2" className="w-full p-2 text-sm border border-gray-200 rounded-lg"></textarea>
                      </div>
                      <button type="submit" disabled={savingSuivi} className="w-full py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 flex justify-center items-center gap-2">
                        {savingSuivi ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Actualiser
                      </button>
                    </form>
                  </section>
                </div>

                {/* COLONNE DROITE : TIMELINE & EVENEMENTS */}
                <div className="lg:col-span-2 space-y-8">
                  <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                      <h2 className="text-xl font-bold text-gray-800 flex items-center gap-3"><Clock size={20} className="text-blue-600" /> Historique et Événements</h2>
                    </div>
                    
                    {/* Formulaire ajout événement */}
                    <div className="p-6 bg-blue-50/50 border-b border-gray-100">
                      <form onSubmit={submitEvenement} className="flex flex-col sm:flex-row gap-4 items-end">
                        <div className="w-full sm:w-1/4">
                          <label className="block text-xs font-semibold text-gray-700 mb-1">Date</label>
                          <input type="date" required value={evenementForm.date_evenement} onChange={e => setEvenementForm({...evenementForm, date_evenement: e.target.value})} className="w-full p-2.5 border border-gray-200 rounded-xl text-sm" />
                        </div>
                        <div className="w-full sm:w-1/4">
                          <label className="block text-xs font-semibold text-gray-700 mb-1">Type</label>
                          <select value={evenementForm.type_evenement} onChange={e => setEvenementForm({...evenementForm, type_evenement: e.target.value})} className="w-full p-2.5 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Note">📝 Note</option>
                            <option value="Jalon">🏁 Jalon Validé</option>
                            <option value="Incident">⚠️ Incident / Blocage</option>
                          </select>
                        </div>
                        <div className="w-full sm:w-2/4 flex gap-3">
                          <div className="flex-1">
                            <label className="block text-xs font-semibold text-gray-700 mb-1">Description</label>
                            <input type="text" required value={evenementForm.description} onChange={e => setEvenementForm({...evenementForm, description: e.target.value})} placeholder="Saisir les détails de l'événement..." className="w-full p-2.5 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500" />
                          </div>
                          <button type="submit" disabled={savingEvenement} className="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 flex-shrink-0 flex items-center gap-2">
                            {savingEvenement ? <Loader2 size={16} className="animate-spin"/> : <Plus size={16}/>} Ajouter
                          </button>
                        </div>
                      </form>
                    </div>

                    {/* Timeline */}
                    <div className="p-6">
                      {(!suiviData.evenements || suiviData.evenements.length === 0) ? (
                        <div className="text-center py-12 text-gray-400 italic flex flex-col items-center">
                          <Activity size={32} className="mb-3 opacity-50" />
                          Aucun événement n'a été enregistré pour le moment.
                        </div>
                      ) : (
                        <div className="relative border-l-2 border-gray-100 ml-4 space-y-8 pb-4">
                          {suiviData.evenements.map(ev => (
                            <div key={ev.id} className="relative pl-8">
                              {/* Icon bullet */}
                              <div className={`absolute -left-[17px] top-1 p-1.5 rounded-full border-4 border-white ${
                                ev.type_evenement === 'Incident' ? 'bg-red-500 text-white' :
                                ev.type_evenement === 'Jalon' ? 'bg-emerald-500 text-white' :
                                'bg-blue-500 text-white'
                              }`}>
                                {ev.type_evenement === 'Incident' && <AlertTriangle size={14}/>}
                                {ev.type_evenement === 'Jalon' && <CheckCircle2 size={14}/>}
                                {ev.type_evenement === 'Note' && <FileSignature size={14}/>}
                              </div>
                              
                              <div className="bg-gray-50 border border-gray-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
                                <div className="flex justify-between items-start mb-2">
                                  <span className={`text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
                                    ev.type_evenement === 'Incident' ? 'text-red-700 bg-red-100' :
                                    ev.type_evenement === 'Jalon' ? 'text-emerald-700 bg-emerald-100' :
                                    'text-blue-700 bg-blue-100'
                                  }`}>{ev.type_evenement}</span>
                                  <span className="text-xs font-mono text-gray-500 font-bold bg-white px-2 py-1 border border-gray-200 rounded-lg">{ev.date_evenement}</span>
                                </div>
                                <p className="text-gray-800 text-sm leading-relaxed">{ev.description}</p>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  </section>
                </div>
              </div>
            )}
            <PhaseDocuments title="Suivi d'Exécution" phaseCode="07" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['execution', 'suivi', 'travaux']} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 6: RÉCEPTION FINALE (MODULE 08) */}
        {activeTab === 'reception' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {receptionSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Réception enregistrée avec succès !</p></div>}
            
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              
              {/* Formulaire Réception */}
              <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex items-center gap-3 bg-gray-50"><div className="p-2 bg-teal-100 text-teal-600 rounded-lg"><PackageCheck size={20} /></div><h2 className="text-xl font-bold">Déclarer une Réception</h2></div>
                <form onSubmit={saveReception} className="p-6 space-y-6">
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-semibold text-gray-700 mb-2">Type de réception</label>
                      <div className="flex bg-gray-100 p-1 rounded-xl">
                        <button type="button" onClick={() => setReceptionForm({...receptionForm, type_reception: 'Provisoire'})} className={`flex-1 py-2 text-sm font-bold rounded-lg transition-colors ${receptionForm.type_reception === 'Provisoire' ? 'bg-white text-teal-700 shadow' : 'text-gray-500 hover:text-gray-700'}`}>Provisoire</button>
                        <button type="button" onClick={() => setReceptionForm({...receptionForm, type_reception: 'Définitive'})} className={`flex-1 py-2 text-sm font-bold rounded-lg transition-colors ${receptionForm.type_reception === 'Définitive' ? 'bg-white text-teal-700 shadow' : 'text-gray-500 hover:text-gray-700'}`}>Définitive</button>
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-semibold text-gray-700 mb-2">Date de réunion</label>
                      <input type="date" required value={receptionForm.date_reunion} onChange={e => setReceptionForm({...receptionForm, date_reunion: e.target.value})} className="w-full p-2.5 border rounded-xl" />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">Conformité des prestations</label>
                    <div className="flex gap-3">
                      <label className={`flex-1 flex justify-center items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all ${receptionForm.conformite === 'Oui' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-bold' : 'border-gray-200 text-gray-500 hover:bg-gray-50'}`}>
                        <input type="radio" name="conformite" value="Oui" checked={receptionForm.conformite === 'Oui'} onChange={e => setReceptionForm({...receptionForm, conformite: e.target.value})} className="hidden" />
                        <CheckCircle2 size={18} /> Conforme
                      </label>
                      <label className={`flex-1 flex justify-center items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all ${receptionForm.conformite === 'Avec réserves' ? 'border-amber-500 bg-amber-50 text-amber-700 font-bold' : 'border-gray-200 text-gray-500 hover:bg-gray-50'}`}>
                        <input type="radio" name="conformite" value="Avec réserves" checked={receptionForm.conformite === 'Avec réserves'} onChange={e => setReceptionForm({...receptionForm, conformite: e.target.value})} className="hidden" />
                        <AlertTriangle size={18} /> Avec Réserves
                      </label>
                      <label className={`flex-1 flex justify-center items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all ${receptionForm.conformite === 'Non' ? 'border-red-500 bg-red-50 text-red-700 font-bold' : 'border-gray-200 text-gray-500 hover:bg-gray-50'}`}>
                        <input type="radio" name="conformite" value="Non" checked={receptionForm.conformite === 'Non'} onChange={e => setReceptionForm({...receptionForm, conformite: e.target.value})} className="hidden" />
                        <X size={18} /> Non Conforme
                      </label>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Commission de réception</label>
                    <input type="text" value={receptionForm.commission_reception} onChange={e => setReceptionForm({...receptionForm, commission_reception: e.target.value})} placeholder="Ex: Ali, Fatima, Youssef (séparés par des virgules)" className="w-full p-2.5 border rounded-xl" />
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">Observations / Réserves</label>
                    <textarea rows="3" value={receptionForm.reserves_observations} onChange={e => setReceptionForm({...receptionForm, reserves_observations: e.target.value})} className="w-full p-2.5 border rounded-xl" placeholder={receptionForm.conformite === 'Avec réserves' ? "Veuillez détailler les réserves..." : "Observations générales..."}></textarea>
                  </div>

                  <div className="pt-2">
                    <button type="submit" disabled={savingReception} className="w-full py-3 bg-teal-600 text-white font-bold rounded-xl shadow-lg hover:bg-teal-700 flex justify-center items-center gap-3">
                      {savingReception ? <Loader2 size={20} className="animate-spin"/> : <CheckSquare size={20}/>} Valider la Réception
                    </button>
                  </div>

                </form>
              </section>

              {/* Historique des réceptions */}
              <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden h-fit">
                <div className="p-6 border-b border-gray-100 flex items-center gap-3 bg-gray-50"><div className="p-2 bg-gray-200 text-gray-600 rounded-lg"><Clock size={20} /></div><h2 className="text-xl font-bold">Historique des Réceptions</h2></div>
                <div className="p-6">
                  {receptions.length === 0 ? (
                    <div className="text-center py-8 text-gray-400 italic">Aucune réception enregistrée.</div>
                  ) : (
                    <div className="space-y-4">
                      {receptions.map(r => (
                        <div key={r.id} className="p-4 border border-gray-100 rounded-2xl bg-gray-50 shadow-sm relative overflow-hidden">
                          {/* Banner de statut de conformité */}
                          <div className={`absolute top-0 left-0 w-1.5 h-full ${
                            r.conformite === 'Oui' ? 'bg-emerald-500' :
                            r.conformite === 'Avec réserves' ? 'bg-amber-500' : 'bg-red-500'
                          }`}></div>
                          
                          <div className="pl-3">
                            <div className="flex justify-between items-start mb-2">
                              <div>
                                <span className="font-black text-gray-900 text-lg">Réception {r.type_reception}</span>
                                <span className={`ml-3 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide border ${
                                  r.conformite === 'Oui' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' :
                                  r.conformite === 'Avec réserves' ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-red-100 text-red-700 border-red-200'
                                }`}>{r.conformite}</span>
                              </div>
                              <span className="text-sm font-mono text-gray-500 font-bold">{r.date_reunion}</span>
                            </div>
                            
                            {r.commission_reception && r.commission_reception.length > 0 && (
                              <p className="text-sm text-gray-600 mb-2"><strong>Commission :</strong> {r.commission_reception.join(', ')}</p>
                            )}
                            
                            {r.reserves_observations && (
                              <div className="mt-3 p-3 bg-white border border-gray-100 rounded-xl">
                                <p className="text-sm text-gray-700 italic">"{r.reserves_observations}"</p>
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </section>

            </div>
            <PhaseDocuments title="Réception Finale" phaseCode="05" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['reception', 'cloture']} disabled={false} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 7: LIQUIDATION FINANCIERE (MODULE 09) */}
        {activeTab === 'liquidation' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-4xl mx-auto">
            {liquidationSuccess && <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-600"><CheckCircle2 size={20} /><p className="font-medium">Liquidation financière validée avec succès !</p></div>}
            {liquidationError && <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600"><AlertCircle size={20} /><p className="font-medium">{liquidationError}</p></div>}
            
            {/* Recap Engagement */}
            {engagementData ? (
              <div className="bg-slate-50 border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row items-center gap-6 justify-between">
                <div className="flex items-center gap-4"><div className="p-4 bg-slate-200 text-slate-700 rounded-2xl"><Landmark size={28} /></div><div><p className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Montant Engagé Init.</p><h3 className="text-xl font-extrabold text-slate-900">{formatCurrency(engagementData.montant_valide)} dh</h3></div></div>
                <div className="text-right">
                  <p className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Prestataire</p>
                  <span className="font-bold text-slate-700">{engagementData.fournisseur.raison_sociale}</span>
                </div>
              </div>
            ) : (
              <div className="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3 text-amber-600"><AlertTriangle size={20} /><p className="font-medium">Aucun engagement trouvé. Impossible de procéder à la liquidation.</p></div>
            )}

            {/* Formulaire Liquidation */}
            {engagementData && (
              <section className="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                <div className="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                  <h2 className="text-xl font-bold text-gray-800 flex items-center gap-3"><Banknote size={20} className="text-indigo-600" /> Saisie de la Liquidation</h2>
                  {liquidationData && <span className="px-3 py-1 bg-emerald-100 text-emerald-700 font-bold rounded-full text-xs">Déjà liquidé</span>}
                </div>
                <form onSubmit={saveLiquidation} className="p-6 space-y-6">
                  
                  <div className="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                    <label className="block text-sm font-semibold text-indigo-900 mb-2">Montant final à payer (dh)</label>
                    <input type="number" step="0.01" min="0" required value={liquidationForm.montant_a_payer} onChange={e => setLiquidationForm({...liquidationForm, montant_a_payer: e.target.value})} className="w-full p-4 border border-indigo-200 rounded-xl font-mono text-xl font-bold text-indigo-700 bg-white shadow-inner focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="0.00" />
                    <p className="text-xs text-indigo-500 mt-2 flex items-center gap-1"><AlertCircle size={12}/> Ce montant ne doit pas dépasser le montant engagé.</p>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Référence Facture</label><input type="text" value={liquidationForm.reference_facture} onChange={e => setLiquidationForm({...liquidationForm, reference_facture: e.target.value})} className="w-full p-3 border rounded-xl" placeholder="Ex: FAC-2026-042" /></div>
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Date Facture</label><input type="date" value={liquidationForm.date_facture} onChange={e => setLiquidationForm({...liquidationForm, date_facture: e.target.value})} className="w-full p-3 border rounded-xl" /></div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Ordre d'Imputation</label><input type="text" value={liquidationForm.ordre_imputation} onChange={e => setLiquidationForm({...liquidationForm, ordre_imputation: e.target.value})} className="w-full p-3 border rounded-xl font-mono text-sm" placeholder="O.I n°..." /></div>
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Ordre de Paiement</label><input type="text" value={liquidationForm.ordre_paiement} onChange={e => setLiquidationForm({...liquidationForm, ordre_paiement: e.target.value})} className="w-full p-3 border rounded-xl font-mono text-sm" placeholder="O.P n°..." /></div>
                    <div><label className="block text-sm font-semibold text-gray-700 mb-1">Ordre de Virement</label><input type="text" value={liquidationForm.ordre_virement} onChange={e => setLiquidationForm({...liquidationForm, ordre_virement: e.target.value})} className="w-full p-3 border rounded-xl font-mono text-sm" placeholder="O.V n°..." /></div>
                  </div>

                  <div className="pt-6 border-t border-gray-100 flex justify-end">
                    <button type="submit" disabled={savingLiquidation || consultation.statut_dossier === 'PAYÉ'} className="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 flex items-center gap-3 disabled:opacity-50">
                      {savingLiquidation ? <Loader2 size={20} className="animate-spin"/> : <Banknote size={20}/>} {consultation.statut_dossier === 'PAYÉ' ? "Mettre à jour le paiement" : "Valider le Paiement Final"}
                    </button>
                  </div>

                </form>
              </section>
            )}
            <PhaseDocuments title="Liquidation Financière" phaseCode="06" mode={consultation.mode_engagement} timeline={workflowTimeline} stepKeywords={['liquidation', 'reception_liquidation']} disabled={consultation.statut_dossier !== 'PAYÉ' && consultation.statut_dossier !== 'CLÔTURÉ' && consultation.statut_dossier !== 'LIQUIDÉ'} downloadDocument={downloadDocument} />
          </div>
        )}

        {/* TAB 8: DOSSIER DOCUMENTAIRE & ARCHIVE (MODULES 10 & 11) */}
        {activeTab === 'archive' && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-5xl mx-auto">
            
            {/* Section Haute : Téléchargement Global ZIP */}
            <section className="bg-gradient-to-r from-slate-800 to-slate-900 rounded-3xl shadow-xl border border-slate-700 p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
              <div className="absolute -right-10 -top-10 text-white/5"><Archive size={200} /></div>
              <div className="relative z-10">
                <h2 className="text-2xl font-black mb-2 flex items-center gap-3"><Archive className="text-blue-400" size={28} /> Archivage Numérique Global (Coffre-fort final)</h2>
                <p className="text-slate-300 font-medium max-w-xl">Téléchargez en un clic l'intégralité du dossier administratif de la consultation <strong>{consultation?.numero_consultation}</strong>, organisé en sous-dossiers.</p>
                {consultation?.statut_dossier !== 'PAYÉ' && consultation?.statut_dossier !== 'CLÔTURÉ' && consultation?.statut_dossier !== 'LIQUIDÉ' && (
                   <div className="mt-4 inline-flex items-center gap-2 px-3 py-1 bg-amber-500/20 text-amber-300 rounded-lg text-sm border border-amber-500/30">
                     <AlertTriangle size={14} /> Le dossier n'est pas encore clôturé financièrement.
                   </div>
                )}
              </div>
              <div className="relative z-10 w-full md:w-auto shrink-0">
                <button 
                  onClick={downloadArchiveZip} 
                  disabled={downloadingZip || (consultation?.statut_dossier !== 'PAYÉ' && consultation?.statut_dossier !== 'CLÔTURÉ' && consultation?.statut_dossier !== 'LIQUIDÉ')} 
                  className="w-full md:w-auto px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl shadow-lg shadow-blue-900/50 flex items-center justify-center gap-3 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {downloadingZip ? <Loader2 size={24} className="animate-spin"/> : <DownloadCloud size={24}/>}
                  {downloadingZip ? 'Compression ZIP...' : 'Télécharger le Dossier ZIP'}
                </button>
              </div>
            </section>
          </div>
        )}

          <div className="mt-10 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                {prevStep ? (
                  <button onClick={() => { setActiveTab(prevStep.key); window.scrollTo({ top: 0, behavior: 'smooth' }); }} className="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-slate-400 hover:bg-slate-50 transition">
                    ← Précédent
                  </button>
                ) : (
                  <div className="text-sm text-slate-500">Début du cycle</div>
                )}
              </div>

              <div className="space-y-1 text-right">
                {nextStep ? (
                  <>
                    <p className="text-xs uppercase tracking-[0.24em] text-slate-400">Étape suivante</p>
                    <button onClick={handleContinue} disabled={saving || savingProgrammation || savingCommission || savingEngagement || savingSuivi || savingReception || savingLiquidation} className="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                      Suivant : {nextStep.label} →
                    </button>
                  </>
                ) : (
                  <div className="text-sm font-semibold text-slate-700">Vous avez atteint la fin du cycle.</div>
                )}
              </div>
            </div>
          </div>
        </main>
      </div>

      {/* MODALS FOURNISSEURS / DEVIS (Inchangés, cachés en fin de fichier) */}
      {isSelectionModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
            <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-3xl">
              <h2 className="text-xl font-bold text-gray-900">Sélectionner les prestataires</h2>
              <button onClick={() => setIsSelectionModalOpen(false)} className="text-gray-400"><X size={24} /></button>
            </div>
            <div className="p-6 overflow-y-auto flex-1">
              <div className="space-y-2">
                {allFournisseurs.map(f => (
                  <label key={f.id} className="flex items-center gap-4 p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" checked={selectedFournisseurIds.includes(f.id)} onChange={() => toggleFournisseurSelection(f.id)} className="w-5 h-5 text-primary rounded" />
                    <div><p className="font-bold">{f.raison_sociale}</p><p className="text-xs text-gray-500">ICE: {f.ice}</p></div>
                  </label>
                ))}
              </div>
            </div>
            <div className="p-6 border-t border-gray-100 flex justify-end gap-3">
              <button onClick={() => setIsSelectionModalOpen(false)} className="px-5 py-2.5 bg-white border rounded-xl">Annuler</button>
              <button onClick={submitSelection} className="px-5 py-2.5 bg-primary text-white rounded-xl">Valider</button>
            </div>
          </div>
        </div>
      )}

      {isDevisModalOpen && currentOffre && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-md">
            <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-3xl"><h2 className="text-lg font-bold">Saisie du Devis</h2><button onClick={() => setIsDevisModalOpen(false)}><X size={24} /></button></div>
            <form onSubmit={submitDevis} className="p-6 space-y-4">
              <div><label className="block text-sm font-semibold mb-1">Statut de la réponse</label>
                <select value={devisForm.statut_reponse} onChange={e => setDevisForm({...devisForm, statut_reponse: e.target.value})} className="w-full p-2.5 border rounded-xl"><option value="En attente">En attente</option><option value="Reçu">Reçu</option><option value="Hors délai">Hors délai</option><option value="Refusé">Refusé</option></select>
              </div>
              <div><label className="block text-sm font-semibold mb-1">Montant Proposé (dh TTC)</label>
                <input type="number" step="0.01" value={devisForm.montant_propose} onChange={e => setDevisForm({...devisForm, montant_propose: e.target.value})} className="w-full p-2.5 border rounded-xl font-mono" />
              </div>
              <div className="pt-4 flex justify-end gap-3"><button type="button" onClick={() => setIsDevisModalOpen(false)} className="px-4 py-2 border rounded-lg">Annuler</button><button type="submit" className="px-4 py-2 bg-primary text-white rounded-lg">Enregistrer</button></div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};

export default DetailsConsultation;
