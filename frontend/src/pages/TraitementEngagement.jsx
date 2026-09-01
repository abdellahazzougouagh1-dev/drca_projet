import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  Save,
  CheckCircle,
  AlertCircle,
  Loader2,
  ArrowLeft,
  Download,
  Eye,
  Briefcase,
  FileSignature,
  ReceiptText,
  Banknote,
  Upload,
  ArrowRight,
  Building2,
  Landmark,
  FileText,
  Award,
  CheckCircle2,
  Calendar,
  MapPin,
  Mail,
  Phone,
  ShieldCheck
} from 'lucide-react';

const formatMoney = (amount) => {
  if (!amount && amount !== 0) return '-';
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'MAD',
  }).format(amount).replace('MAD', 'dh');
};

const getLotText = (lot) => {
  if (!lot) return '-';
  if (typeof lot === 'string') return lot;
  return lot.objet_lot ? `${lot.num_lot} - ${lot.objet_lot}` : lot.num_lot || 'Lot Unique';
};

const TraitementEngagement = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  const [formData, setFormData] = useState({
    num_marche: '',
    objet_marche: '',
    titulaire: '',
    lot: '',
    montant: '',
    montant_ht: '',
    taux_tva: '20',
    delai_execution: '',
    date_debut_prevue: '',
    date_fin_prevue: '',
    observations: '',
    ice: '',
    if: '',
    rc: '',
    forme_juridique: '',
    capital: '',
    patente: '',
    cnss: '',
    adresse: '',
    ville: '',
    telephone: '',
    fax: '',
    email: '',
    representant: '',
    banque: '',
    agence_bancaire: '',
    rib: '',
    date_signature: '',
    num_decision: '',
    date_approbation: '',
    date_notification_marche: '',
    os_numero: '',
    os_date_signature: '',
    os_date_effet: '',
    os_arret_numero: '',
    os_arret_date_signature: '',
    os_arret_date_effet: '',
    os_arret_motif: '',
    os_reprise_numero: '',
    os_reprise_date_signature: '',
    os_reprise_date_effet: '',
    num_engagement: '',
    reference_engagement: '',
    forme_engagement: 'Marché',
    type_budget: 'Investissement',
    exercice: '',
    date_engagement: '',
    article_budget: '415',
    paragraphe_budget: '20',
    ligne_budget: '16',
    credit_budget_cp: '',
    credit_budget_ce: '',
    depenses_engagees_cp: '',
    depenses_engagees_ce: '',
    disponible_cp: '',
    disponible_ce: '',
    engagement_propose_cp: '',
    engagement_propose_ce: '',
    pieces_jointes: '',
  });

  const [isUploadingCps, setIsUploadingCps] = useState(false);
  const [cpsUploaded, setCpsUploaded] = useState(false);

  const fetchMarche = useCallback(async () => {
    if (!id || id === 'nouveau') return;
    try {
      setLoading(true);
      const response = await api.get(`/marches/${id}`);
      const data = response.data;
      const f = data.fournisseur || {};
      const nl = data.notification_ligne || data.aoo?.notification_ligne || {};
      const montantTtc = data.montant ?? '';
      const tauxTva = data.taux_tva ?? '20';
      const montantHt = data.montant_ht || (montantTtc ? (Number(montantTtc) / (1 + (Number(tauxTva) / 100))).toFixed(2) : '');
      const currentYear = data.exercice || (data.num_marche && data.num_marche.match(/\/(\d{4})\//) ? data.num_marche.match(/\/(\d{4})\//)[1] : new Date().getFullYear().toString());

      const creditCp = data.credit_budget_cp !== null && data.credit_budget_cp !== undefined ? data.credit_budget_cp : (nl.total_credits ?? '');
      const depensesCp = data.depenses_engagees_cp !== null && data.depenses_engagees_cp !== undefined ? data.depenses_engagees_cp : (nl.credits_engages ?? '');
      const dispoCp = data.disponible_cp !== null && data.disponible_cp !== undefined ? data.disponible_cp : (creditCp !== '' && depensesCp !== '' ? (Number(creditCp) - Number(depensesCp)).toFixed(2) : '');
      const montantEngageTotal = montantTtc ? (Number(montantTtc) * 1.01).toFixed(2) : '';

      const marcheRef = data.num_marche ? (data.num_marche.toLowerCase().startsWith('marché') ? data.num_marche : `Marché N° ${data.num_marche}`) : `Marché N° 06/${currentYear}/DRCA-RSK`;
      const cleanRefEngagement = data.reference_engagement && !data.reference_engagement.startsWith('BC N°') ? data.reference_engagement : marcheRef;
      const cleanFormeEngagement = data.forme_engagement && data.forme_engagement !== 'Bon de Commande' ? data.forme_engagement : 'Marché';

      setFormData(prev => ({
        ...prev,
        ...data,
        titulaire: data.titulaire || f.raison_sociale || '',
        ice: f.ice || data.ice || '',
        if: f.if || data.if || '',
        rc: f.rc || data.rc || '',
        forme_juridique: f.forme_juridique || '',
        capital: f.capital || '',
        patente: f.patente || data.patente || '',
        cnss: f.cnss || data.cnss || '',
        adresse: f.adresse || data.adresse || '',
        ville: f.ville || data.ville || '',
        telephone: f.telephone || data.telephone || '',
        fax: f.fax || data.fax || '',
        email: f.email || data.email || '',
        representant: f.representant || data.qualite_gerant || '',
        banque: f.banque || data.banque || '',
        agence_bancaire: f.agence_bancaire || data.agence || '',
        rib: f.rib || data.rib || '',
        montant: montantTtc,
        montant_ht: montantHt,
        taux_tva: tauxTva,
        delai_execution: data.delai_execution ?? '',
        date_debut_prevue: data.date_debut_prevue ?? '',
        date_fin_prevue: data.date_fin_prevue ?? '',
        observations: data.observations ?? '',
        num_engagement: data.num_engagement || (data.id ? `${data.id}/${currentYear}/FE/DRCA-RSK` : ''),
        reference_engagement: cleanRefEngagement,
        forme_engagement: cleanFormeEngagement,
        type_budget: data.type_budget || 'Investissement',
        exercice: currentYear,
        date_engagement: data.date_engagement || data.date_signature || '',
        article_budget: data.article_budget || (nl.article || '415'),
        paragraphe_budget: data.paragraphe_budget || (nl.paragraphe || '20'),
        ligne_budget: data.ligne_budget || (nl.ligne_budgetaire || '16'),
        credit_budget_cp: creditCp,
        credit_budget_ce: data.credit_budget_ce ?? '',
        depenses_engagees_cp: depensesCp,
        depenses_engagees_ce: data.depenses_engagees_ce ?? '',
        disponible_cp: dispoCp,
        disponible_ce: data.disponible_ce ?? '',
        engagement_propose_cp: data.engagement_propose_cp ?? montantEngageTotal,
        engagement_propose_ce: data.engagement_propose_ce ?? '',
        pieces_jointes: data.pieces_jointes && !data.pieces_jointes.startsWith('BC N°') ? data.pieces_jointes : cleanRefEngagement,
      }));

      if (data.chemin_cps) {
        setCpsUploaded(true);
      }
    } catch (err) {
      console.error(err);
      setErrorMessage('Erreur lors du chargement des données.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchMarche();
  }, [fetchMarche]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => {
      const next = {
        ...prev,
        [name]: type === 'checkbox' ? checked : value
      };

      // Live HT / TTC / TVA synchronization
      if (name === 'montant_ht') {
        const ht = parseFloat(value) || 0;
        const tva = parseFloat(next.taux_tva) || 20;
        next.montant = ht > 0 ? (ht * (1 + tva / 100)).toFixed(2) : '';
        const totalEng = ht > 0 ? (Number(next.montant) * 1.01).toFixed(2) : '';
        next.engagement_propose_cp = totalEng;
      } else if (name === 'montant') {
        const ttc = parseFloat(value) || 0;
        const tva = parseFloat(next.taux_tva) || 20;
        next.montant_ht = ttc > 0 ? (ttc / (1 + tva / 100)).toFixed(2) : '';
        const totalEng = ttc > 0 ? (ttc * 1.01).toFixed(2) : '';
        next.engagement_propose_cp = totalEng;
      } else if (name === 'taux_tva') {
        const ht = parseFloat(next.montant_ht) || 0;
        const tva = parseFloat(value) || 20;
        if (ht > 0) {
          next.montant = (ht * (1 + tva / 100)).toFixed(2);
          next.engagement_propose_cp = (Number(next.montant) * 1.01).toFixed(2);
        }
      }

      // Live budget disponible calculation
      if (name === 'credit_budget_cp' || name === 'depenses_engagees_cp') {
        const c = parseFloat(name === 'credit_budget_cp' ? value : next.credit_budget_cp);
        const d = parseFloat(name === 'depenses_engagees_cp' ? value : next.depenses_engagees_cp);
        if (!isNaN(c) && !isNaN(d)) {
          next.disponible_cp = (c - d).toFixed(2);
        }
      }
      if (name === 'credit_budget_ce' || name === 'depenses_engagees_ce') {
        const c = parseFloat(name === 'credit_budget_ce' ? value : next.credit_budget_ce);
        const d = parseFloat(name === 'depenses_engagees_ce' ? value : next.depenses_engagees_ce);
        if (!isNaN(c) && !isNaN(d)) {
          next.disponible_ce = (c - d).toFixed(2);
        }
      }

      return next;
    });
  };

  const handleUploadCps = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('fichier_cps', file);

    setIsUploadingCps(true);
    try {
      await api.post(`/marches/${id}/upload-cps`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      setCpsUploaded(true);
      setSuccessMessage('Fichier CPS importé avec succès.');
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error(err);
      setErrorMessage("Erreur lors de l'importation du CPS.");
      setTimeout(() => setErrorMessage(''), 3000);
    } finally {
      setIsUploadingCps(false);
      e.target.value = null;
    }
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      setErrorMessage('');
      setSuccessMessage('');

      const payload = {
        ...formData,
        taux_tva: formData.taux_tva ? Number(formData.taux_tva) : 20,
        delai_execution: formData.delai_execution ? Number(formData.delai_execution) : null,
        credit_budget_cp: formData.credit_budget_cp !== '' ? Number(formData.credit_budget_cp) : null,
        credit_budget_ce: formData.credit_budget_ce !== '' ? Number(formData.credit_budget_ce) : null,
        depenses_engagees_cp: formData.depenses_engagees_cp !== '' ? Number(formData.depenses_engagees_cp) : null,
        depenses_engagees_ce: formData.depenses_engagees_ce !== '' ? Number(formData.depenses_engagees_ce) : null,
        disponible_cp: formData.disponible_cp !== '' ? Number(formData.disponible_cp) : null,
        disponible_ce: formData.disponible_ce !== '' ? Number(formData.disponible_ce) : null,
        engagement_propose_cp: formData.engagement_propose_cp !== '' ? Number(formData.engagement_propose_cp) : null,
        engagement_propose_ce: formData.engagement_propose_ce !== '' ? Number(formData.engagement_propose_ce) : null,
        lot: typeof formData.lot === 'object' ? (formData.lot?.num_lot || 'Lot Unique') : formData.lot,
        statut: 'engagement_validee',
        fournisseur_data: {
          raison_sociale: formData.titulaire,
          ice: formData.ice,
          if: formData.if,
          rc: formData.rc,
          forme_juridique: formData.forme_juridique,
          capital: formData.capital,
          patente: formData.patente,
          cnss: formData.cnss,
          adresse: formData.adresse,
          ville: formData.ville,
          telephone: formData.telephone,
          fax: formData.fax,
          email: formData.email,
          representant: formData.representant,
          banque: formData.banque,
          agence_bancaire: formData.agence_bancaire,
          rib: formData.rib,
        }
      };

      // Chronological validations
      if (payload.date_approbation && payload.date_notification_marche) {
        if (new Date(payload.date_notification_marche) < new Date(payload.date_approbation)) {
          throw new Error("La date de notification ne peut pas être antérieure à la date d'approbation.");
        }
      }
      if (payload.date_notification_marche && payload.os_date_effet) {
        if (new Date(payload.os_date_effet) < new Date(payload.date_notification_marche)) {
          throw new Error("La date d'effet de l'OS ne peut pas être antérieure à la date de notification.");
        }
      }
      if (payload.os_date_effet && payload.os_arret_date_effet) {
        if (new Date(payload.os_arret_date_effet) < new Date(payload.os_date_effet)) {
          throw new Error("La date d'effet d'arrêt ne peut pas être antérieure à la date de commencement.");
        }
      }
      if (payload.os_arret_date_effet && payload.os_reprise_date_effet) {
        if (new Date(payload.os_reprise_date_effet) < new Date(payload.os_arret_date_effet)) {
          throw new Error("La date d'effet de reprise ne peut pas être antérieure à la date d'arrêt.");
        }
      }

      await api.put(`/marches/${id}`, payload);
      setSuccessMessage('Engagement et informations du titulaire enregistrés avec succès.');
      fetchMarche();

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      let detailedError = 'Erreur lors de l\'enregistrement des données.';
      if (err.response) {
        if (err.response.status === 422 && err.response.data.errors) {
          const errors = Object.values(err.response.data.errors).flat().join(' ');
          detailedError = `Erreur 422 : ${errors}`;
        } else {
          detailedError = `Erreur ${err.response.status} : ${err.response.data.message || ''}`;
        }
      } else if (err.message) {
        detailedError = err.message;
      }
      setErrorMessage(detailedError);
      console.error('Erreur API:', err.response?.data || err);
    } finally {
      setSaving(false);
    }
  };

  const downloadDocument = async (documentType) => {
    if (!id || id === 'nouveau') return;

    let missingFields = [];

    switch (documentType) {
      case 'acte-engagement':
      case 'cps':
      case 'contrat-marche':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.titulaire) missingFields.push("Titulaire");
        if (!formData.montant) missingFields.push("Montant du marché");
        break;
      case 'decision-approbation':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'designation-agent-suivi':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'os-commencement':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'os-arret-reprise':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.os_arret_numero && !formData.os_reprise_numero) {
          missingFields.push("N° OS d'arrêt ou N° OS de reprise");
        }
        break;
      case 'bordereau-prix':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
    }

    if (missingFields.length > 0) {
      setErrorMessage(`Veuillez remplir et enregistrer les champs suivants avant de télécharger : ${missingFields.join(', ')}`);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    try {
      let endpoint = '';
      if (documentType === 'acte-engagement') {
        endpoint = `/marches/${id}/generate/acte`;
      } else if (documentType === 'decision-approbation') {
        endpoint = `/marches/${id}/generate/notification`;
      } else if (documentType === 'os-commencement') {
        endpoint = `/marches/${id}/generate/os`;
      } else if (documentType === 'os-arret-reprise') {
        endpoint = `/marches/${id}/generate/os-arret-reprise`;
      } else if (documentType === 'bordereau-prix') {
        endpoint = `/marches/${id}/generate/bordereau`;
      } else if (documentType === 'cps') {
        endpoint = `/marches/${id}/generate/cps`;
      } else if (documentType === 'contrat-marche') {
        endpoint = `/marches/${id}/generate/contrat-marche`;
      } else if (documentType === 'designation-agent-suivi') {
        endpoint = `/marches/${id}/generate/designation-agent`;
      } else {
        endpoint = `/marches/${id}/documents/${documentType}`;
      }

      const filename = `${documentType.replace('-', '_')}_${formData.num_marche || id}.pdf`;

      const response = await api.get(endpoint, {
        responseType: 'blob',
      });

      let fileExtension = 'pdf';
      let mimeType = 'application/pdf';

      const actualFilename = `${documentType.replace('-', '_')}_${formData.num_marche || id}.${fileExtension}`;

      const url = window.URL.createObjectURL(new Blob([response.data], { type: mimeType }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', actualFilename);
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
  };

  const previewDocument = async (documentType) => {
    if (!id || id === 'nouveau') return;

    let missingFields = [];
    switch (documentType) {
      case 'acte-engagement':
      case 'cps':
      case 'contrat-marche':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.titulaire) missingFields.push("Titulaire");
        if (!formData.montant) missingFields.push("Montant du marché");
        break;
      case 'decision-approbation':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'designation-agent-suivi':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'os-commencement':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        break;
      case 'os-arret-reprise':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.os_arret_numero && !formData.os_reprise_numero) {
          missingFields.push("N° OS d'arrêt ou N° OS de reprise");
        }
        break;
    }

    if (missingFields.length > 0) {
      setErrorMessage(`Veuillez remplir et enregistrer les champs suivants avant d'afficher l'aperçu : ${missingFields.join(', ')}`);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    try {
      let endpoint = '';
      if (documentType === 'acte-engagement') {
        endpoint = `/marches/${id}/generate/acte`;
      } else if (documentType === 'decision-approbation') {
        endpoint = `/marches/${id}/generate/notification`;
      } else if (documentType === 'os-commencement') {
        endpoint = `/marches/${id}/generate/os`;
      } else if (documentType === 'os-arret-reprise') {
        endpoint = `/marches/${id}/generate/os-arret-reprise`;
      } else if (documentType === 'contrat-marche') {
        endpoint = `/marches/${id}/generate/contrat-marche`;
      } else if (documentType === 'designation-agent-suivi') {
        endpoint = `/marches/${id}/generate/designation-agent`;
      } else {
        endpoint = `/marches/${id}/documents/${documentType}`;
      }

      const response = await api.get(endpoint, {
        responseType: 'blob',
      });

      const fileUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      window.open(fileUrl, '_blank');
    } catch (err) {
      let msg = "Erreur lors de l'ouverture de l'aperçu. Vérifiez que tous les champs requis sont remplis et sauvegardés.";
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
  };

  const inputClass = "w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 hover:border-slate-300 transition-all duration-300 text-sm shadow-sm placeholder:text-slate-400";

  if (loading) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-50">
        <Loader2 size={48} className="animate-spin text-blue-600" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] pb-24">

      {/* HEADER */}
      <header className="bg-white/95 border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur">
        <div className="w-full px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
          <div className="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <button
                onClick={() => navigate('/engagements')}
                className="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-all"
              >
                <ArrowLeft size={20} />
              </button>
              <div>
                <h1 className="text-2xl font-extrabold text-slate-800 tracking-tight">Traitement de l'Engagement</h1>
                <p className="text-sm font-medium text-slate-500 flex items-center gap-2 mt-1">
                  <span className="text-blue-600 font-bold">{formData.num_marche || 'Nouveau Dossier'}</span>
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <button
                onClick={handleSave}
                disabled={saving}
                className="px-6 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-md transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
              >
                {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                Enregistrer l'Engagement
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="w-full px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">

        {/* MESSAGES */}
        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700 font-medium">
            <AlertCircle size={20} /> {errorMessage}
          </div>
        )}
        {successMessage && (
          <div className="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700 font-medium">
            <CheckCircle size={20} /> {successMessage}
          </div>
        )}

        {/* RÉSUMÉ DU DOSSIER */}
        <div className="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 rounded-[1.5rem] p-6 shadow-xl shadow-blue-900/20 mb-6 relative overflow-hidden border border-white/10 space-y-4">
          <div className="absolute top-0 right-0 p-4 opacity-[0.03] pointer-events-none transform translate-x-5 -translate-y-5">
            <Briefcase size={120} />
          </div>
          <div className="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.1),_transparent_40%)] pointer-events-none" />

          <div className="flex items-center justify-between relative z-10">
            <h3 className="text-lg font-black text-white flex items-center gap-2">
              <div className="p-2 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10 shadow-inner">
                <Briefcase size={18} className="text-blue-200" />
              </div>
              Registre des Ordres de service & Engagement
            </h3>
            <div className="flex items-center gap-2">
              <span className="px-3 py-1 bg-blue-500/20 text-blue-200 border border-blue-400/30 rounded-lg text-xs font-bold font-mono">
                Nature: {formData.forme_engagement || 'Marché'}
              </span>
              <span className="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-lg text-xs font-bold font-mono">
                Budget: {formData.type_budget || 'Investissement'}
              </span>
            </div>
          </div>

          {/* Ligne 1 : Attributaire, Code Ste, N° Marché / CV, AOO /consult, date OA */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 relative z-10">
            <div className="lg:col-span-2 bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">Attributaire & Code Ste</p>
              <div className="flex flex-col">
                <span className="text-sm font-bold text-white truncate max-w-full" title={formData.fournisseur?.raison_sociale || formData.titulaire}>
                  {formData.fournisseur?.raison_sociale || formData.titulaire || '-'}
                </span>
                <span className="text-[10px] text-blue-200/80 font-mono mt-0.5">
                  Code Ste: {formData.code_ste || formData.fournisseur_id || '-'} {formData.fournisseur?.ice ? `| ICE: ${formData.fournisseur.ice}` : ''}
                </span>
              </div>
            </div>
            <div className="bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">N° de Marché / CV</p>
              <p className="text-sm font-bold text-white font-mono">{formData.num_marche || '-'}</p>
              <p className="text-[10px] text-blue-300 mt-0.5 truncate">{getLotText(formData.lot)}</p>
            </div>
            <div className="bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">AOO /consult & date OA</p>
              <p className="text-xs font-bold text-white font-mono">{formData.aoo?.num_aoo || formData.num_aoo || '-'}</p>
              <p className="text-[10px] text-blue-300 mt-0.5">
                date OA: {formData.date_oa || formData.aoo?.date_ouverture || '-'}
              </p>
            </div>
          </div>

          {/* Ligne 2 : Dépense, Intérêt moratoire 1%, Montant Total Engagé, Crédit Disponible */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 relative z-10">
            <div className="bg-white/10 rounded-xl p-3 border border-white/10 backdrop-blur-sm">
              <p className="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Montant de la Dépense (DH)</p>
              <p className="text-base font-black text-white mt-1">{formatMoney(formData.montant)}</p>
            </div>
            <div className="bg-amber-500/20 rounded-xl p-3 border border-amber-400/30 backdrop-blur-sm">
              <p className="text-[10px] font-bold text-amber-300 uppercase tracking-wider">Intérêt Moratoire (1%)</p>
              <p className="text-base font-black text-amber-200 mt-1">
                {formData.montant ? formatMoney(Number(formData.montant) * 0.01) : '-'}
              </p>
            </div>
            <div className="bg-gradient-to-br from-emerald-600/60 to-emerald-800/40 rounded-xl p-3 border border-emerald-400/30 backdrop-blur-sm shadow-md">
              <p className="text-[10px] font-bold text-emerald-200 uppercase tracking-wider">Montant Total Engagement</p>
              <p className="text-lg font-black text-white mt-0.5 drop-shadow">
                {formData.montant ? formatMoney(Number(formData.montant) * 1.01) : '-'}
              </p>
            </div>
            <div className="bg-white/10 rounded-xl p-3 border border-white/10 backdrop-blur-sm">
              <p className="text-[10px] font-bold text-cyan-300 uppercase tracking-wider">Crédit Disponible (CP)</p>
              <p className="text-base font-black text-white mt-1">
                {formData.disponible_cp ? formatMoney(formData.disponible_cp) : '-'}
              </p>
            </div>
          </div>
        </div>

        <div className="space-y-8">

          {/* FORMULAIRES DE SAISIE */}
          <div className="space-y-6">

            {/* 1. INFORMATIONS DE L'ATTRIBUTAIRE */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm relative overflow-hidden">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100 mb-6">
                <div>
                  <h3 className="text-lg font-black text-slate-900 flex items-center gap-2">
                    <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                      1
                    </div>
                    Données de la Société (Attributaire)
                  </h3>
                  <p className="text-xs text-slate-500 mt-1">
                    Entreprise attributaire : <strong className="uppercase text-slate-800">{formData.titulaire || 'En attente'}</strong> (Code Ste: {formData.code_ste || formData.fournisseur_id || '-'})
                  </p>
                </div>
              </div>

              <div className="space-y-6 relative z-10">

                {/* SOUS-BLOC 1 : IDENTIFICATION SOCIETE */}
                <div className="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                  <h4 className="text-xs font-bold text-slate-700 mb-3 uppercase tracking-wider flex items-center gap-2">
                    <Building2 size={16} className="text-slate-500" /> Identification Juridique & Fiscale
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div className="space-y-1 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">Nom de la societe <span className="text-red-500">*</span></label>
                      <input type="text" name="titulaire" value={formData.titulaire || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">N° (Code Ste)</label>
                      <input type="text" name="code_ste" value={formData.code_ste || formData.fournisseur_id || ''} onChange={handleChange} className={`${inputClass} font-mono`} placeholder="Ex: 14" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">Responsable (Gérant)</label>
                      <input type="text" name="representant" value={formData.representant || ''} onChange={handleChange} className={inputClass} placeholder="Ex: OULACHGAR NOUREDDINE" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">Qualité</label>
                      <input type="text" name="qualite_gerant" value={formData.qualite_gerant || formData.qualite_representant || 'Gérant'} onChange={handleChange} className={inputClass} placeholder="Ex: Gérant" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">ICE <span className="text-red-500">*</span></label>
                      <input type="text" name="ice" value={formData.ice || ''} onChange={handleChange} required className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">IF (Identifiant Fiscal)</label>
                      <input type="text" name="if" value={formData.if || ''} onChange={handleChange} className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">N° registre de commerce (RC)</label>
                      <input type="text" name="rc" value={formData.rc || ''} onChange={handleChange} className={`${inputClass} font-mono`} placeholder="Ex: 56139" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">N° Pat (Patente)</label>
                      <input type="text" name="patente" value={formData.patente || ''} onChange={handleChange} className={`${inputClass} font-mono`} placeholder="Ex: 17201039" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">N° CNSS</label>
                      <input type="text" name="cnss" value={formData.cnss || ''} onChange={handleChange} className={`${inputClass} font-mono`} placeholder="Ex: 4170153" />
                    </div>
                    <div className="space-y-1 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">Adresse <span className="text-red-500">*</span></label>
                      <input type="text" name="adresse" value={formData.adresse || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">ville <span className="text-red-500">*</span></label>
                      <input type="text" name="ville" value={formData.ville || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                  </div>
                </div>

                {/* SOUS-BLOC 2 : INFORMATIONS BANCAIRES */}
                <div className="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                  <h4 className="text-xs font-bold text-slate-700 mb-3 uppercase tracking-wider flex items-center gap-2">
                    <Landmark size={16} className="text-slate-500" /> Coordonnées Bancaires
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">Banque</label>
                      <input type="text" name="banque" value={formData.banque || ''} onChange={handleChange} className={inputClass} placeholder="Ex: CREDIT AGRICOLE, ATTIJARIWAFA BANK" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">Agence</label>
                      <input type="text" name="agence_bancaire" value={formData.agence_bancaire || ''} onChange={handleChange} className={inputClass} placeholder="Ex: Agence Moulay Ali Cherif" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">compte N° (RIB 24 chiffres)</label>
                      <input type="text" name="rib" value={formData.rib || ''} onChange={handleChange} maxLength={24} className={`${inputClass} font-mono tracking-wider`} placeholder="Ex: 225480078501359651010167" />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-bold text-slate-700">Nom à la Banque (Titulaire du compte)</label>
                      <input type="text" name="titulaire_compte" value={formData.titulaire_compte || formData.titulaire || ''} onChange={handleChange} className={inputClass} placeholder="Ex: AYDO ENG" />
                    </div>
                  </div>
                </div>

              </div>
            </div>

            {/* 2. FICHE D'ENGAGEMENT */}
            <div className="bg-white rounded-3xl border border-blue-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                  2
                </div>
                Fiche d'Engagement Budgétaire
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">N° Fiche d'Engagement</label>
                  <input
                    type="text"
                    name="num_engagement"
                    value={formData.num_engagement || ''}
                    onChange={handleChange}
                    placeholder="Ex: 22/2024/FE/DRCA-RSK"
                    className={`${inputClass} font-mono font-bold text-blue-900`}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date visa / engagement</label>
                  <input
                    type="date"
                    name="date_engagement"
                    value={formData.date_engagement || ''}
                    onChange={handleChange}
                    className={inputClass}
                  />
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-bold text-slate-700 mb-2">Pièces jointes mentionnées sur la fiche</label>
                  <input
                    type="text"
                    name="pieces_jointes"
                    value={formData.pieces_jointes || ''}
                    onChange={handleChange}
                    placeholder="Ex: Marché N° M-29-2026-DRCA-RSK"
                    className={inputClass}
                  />
                </div>
              </div>
            </div>

            {/* 3. NOTIFICATION DE L'APPROBATION DU MARCHÉ */}
            <div className="bg-white rounded-3xl border border-emerald-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                  3
                </div>
                Notification de l'approbation du Marché
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">N° d'or. de service (Notification)</label>
                  <input
                    type="text"
                    name="num_decision"
                    value={formData.num_decision || ''}
                    onChange={handleChange}
                    className={`${inputClass} font-mono font-bold text-emerald-900`}
                    placeholder="Ex: 01/2024/M06"
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-emerald-900 mb-2">Date de notification de l'approbation *</label>
                  <input
                    type="date"
                    name="date_notification_marche"
                    value={formData.date_notification_marche || ''}
                    onChange={handleChange}
                    required
                    className={`${inputClass} border-emerald-300 focus:ring-emerald-100 focus:border-emerald-500`}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Caution definitive (DH)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="caution_definitive"
                    value={formData.caution_definitive || formData.montant_caution_definitive || ''}
                    onChange={handleChange}
                    className={inputClass}
                    placeholder="Ex: 15214.59 (Calculé auto à 3% si vide)"
                  />
                </div>
              </div>
            </div>

            {/* 4. ORDRE DE SERVICE DE COMMENCEMENT */}
            <div className="bg-white rounded-3xl border border-amber-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                  4
                </div>
                Ordre de Service de Commencement de l'exécution
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">N° d'or. de service *</label>
                  <input type="text" name="os_numero" value={formData.os_numero || ''} onChange={handleChange} className={`${inputClass} font-mono font-bold text-amber-900`} placeholder="Ex: 03/2024/M10" />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date (Date signature OS) *</label>
                  <input type="date" name="os_date_signature" value={formData.os_date_signature || ''} onChange={handleChange} className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Démarrage)</label>
                  <input type="date" name="os_date_effet" value={formData.os_date_effet || ''} onChange={handleChange} className={inputClass} placeholder="Laisser vide si à la réception" />
                </div>
              </div>
            </div>

            {/* 5. OS ARRÊT & REPRISE */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs">
                  5
                </div>
                Ajournement & Reprise (Événements)
              </h3>

              <div className="mb-6 p-4 border border-red-100 bg-red-50 rounded-2xl">
                <h4 className="font-bold text-red-900 mb-3 text-sm flex items-center gap-1.5">
                  <AlertCircle size={16} className="text-red-600" /> Ordre de Service d'Arrêt (Ajournement)
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">N° OS d'Arrêt</label>
                    <input type="text" name="os_arret_numero" value={formData.os_arret_numero || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 02/2024/M03" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date signature OS Arrêt</label>
                    <input type="date" name="os_arret_date_signature" value={formData.os_arret_date_signature || ''} onChange={handleChange} className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Arrêt)</label>
                    <input type="date" name="os_arret_date_effet" value={formData.os_arret_date_effet || ''} onChange={handleChange} className={inputClass} placeholder="Laisser vide si à la réception" />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Motif d'arrêt</label>
                  <input type="text" name="os_arret_motif" value={formData.os_arret_motif || ''} onChange={handleChange} className={inputClass} placeholder="Ex: Attente des autorisations ou conditions climatiques" />
                </div>
              </div>

              <div className="p-4 border border-emerald-100 bg-emerald-50 rounded-2xl">
                <h4 className="font-bold text-emerald-900 mb-3 text-sm flex items-center gap-1.5">
                  <CheckCircle size={16} className="text-emerald-600" /> Ordre de Service de Reprise
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">N° OS de Reprise</label>
                    <input type="text" name="os_reprise_numero" value={formData.os_reprise_numero || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 03/2024/M03" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date signature OS Reprise</label>
                    <input type="date" name="os_reprise_date_signature" value={formData.os_reprise_date_signature || ''} onChange={handleChange} className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Reprise)</label>
                    <input type="date" name="os_reprise_date_effet" value={formData.os_reprise_date_effet || ''} onChange={handleChange} className={inputClass} placeholder="Laisser vide si à la réception" />
                  </div>
                </div>
              </div>
            </div>

          </div>

          {/* SECTION DU BAS : DOCUMENTS OFFICIELS À GÉNÉRER */}
          <div className="mt-12 pt-8 border-t border-slate-200 space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <h3 className="text-xl font-black text-slate-900 flex items-center gap-2.5">
                  <div className="p-2 bg-blue-50 text-blue-600 rounded-xl">
                    <Download size={22} />
                  </div>
                  Documents Officiels de la Phase d'Engagement
                </h3>
                <p className="text-xs text-slate-500 mt-1">
                  Visualisez et téléchargez directement les documents officiels conformes aux modèles réglementaires.
                </p>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-xl">
                  5 Documents Disponibles
                </span>
              </div>
            </div>

            {/* GRILLE HORIZONTALE DES 5 DOCUMENTS */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">

              {/* DOCUMENT 1 : FICHE D'ENGAGEMENT */}
              <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:-translate-y-1 hover:shadow-lg hover:shadow-blue-900/5 transition-all duration-300 flex flex-col justify-between group">
                <div>
                  <div className="flex items-center gap-2.5 mb-3 border-b border-slate-100 pb-2.5">
                    <div className="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
                      <FileSignature size={18} />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-slate-900 text-xs">Fiche d'Engagement</h4>
                      <p className="text-[10px] text-slate-400 font-mono">Modèle officiel</p>
                    </div>
                  </div>
                  <p className="text-[11px] text-slate-600 mb-3 line-clamp-2">
                    Fiche d'engagement comptable avec ventilation et moratoire 1%.
                  </p>
                </div>
                <div className="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-50">
                  <button
                    type="button"
                    onClick={() => previewDocument('acte-engagement')}
                    className="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1"
                  >
                    <Eye size={13} className="text-blue-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('acte-engagement')}
                    className="py-2 px-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center justify-center gap-1"
                  >
                    <Download size={13} /> PDF
                  </button>
                </div>
              </div>

              {/* DOCUMENT 2 : NOTIFICATION D'APPROBATION */}
              <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-900/5 transition-all duration-300 flex flex-col justify-between group">
                <div>
                  <div className="flex items-center gap-2.5 mb-3 border-b border-slate-100 pb-2.5">
                    <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                      <CheckCircle size={18} />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-slate-900 text-xs">Notification Approbation</h4>
                      <p className="text-[10px] text-slate-400 font-mono">Lettre officielle</p>
                    </div>
                  </div>
                  <p className="text-[11px] text-slate-600 mb-3 line-clamp-2">
                    Notification d'approbation et demande de constitution de caution.
                  </p>
                </div>
                <div className="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-50">
                  <button
                    type="button"
                    onClick={() => previewDocument('decision-approbation')}
                    className="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1"
                  >
                    <Eye size={13} className="text-emerald-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('decision-approbation')}
                    className="py-2 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center justify-center gap-1"
                  >
                    <Download size={13} /> PDF
                  </button>
                </div>
              </div>

              {/* DOCUMENT 3 : OS DE COMMENCEMENT */}
              <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:-translate-y-1 hover:shadow-lg hover:shadow-amber-900/5 transition-all duration-300 flex flex-col justify-between group">
                <div>
                  <div className="flex items-center gap-2.5 mb-3 border-b border-slate-100 pb-2.5">
                    <div className="p-2 bg-amber-50 text-amber-600 rounded-lg group-hover:bg-amber-600 group-hover:text-white transition-colors">
                      <ReceiptText size={18} />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-slate-900 text-xs">OS Commencement</h4>
                      <p className="text-[10px] text-slate-400 font-mono">Ordre de service</p>
                    </div>
                  </div>
                  <p className="text-[11px] text-slate-600 mb-3 line-clamp-2">
                    Ordre de démarrage des prestations avec date d'effet et accusé.
                  </p>
                </div>
                <div className="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-50">
                  <button
                    type="button"
                    onClick={() => previewDocument('os-commencement')}
                    className="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1"
                  >
                    <Eye size={13} className="text-amber-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('os-commencement')}
                    className="py-2 px-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center justify-center gap-1"
                  >
                    <Download size={13} /> PDF
                  </button>
                </div>
              </div>

              {/* DOCUMENT 4 : OS ARRÊT ET REPRISE */}
              <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:-translate-y-1 hover:shadow-lg hover:shadow-red-900/5 transition-all duration-300 flex flex-col justify-between group">
                <div>
                  <div className="flex items-center gap-2.5 mb-3 border-b border-slate-100 pb-2.5">
                    <div className="p-2 bg-red-50 text-red-600 rounded-lg group-hover:bg-red-600 group-hover:text-white transition-colors">
                      <AlertCircle size={18} />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-slate-900 text-xs">OS Arrêt & Reprise</h4>
                      <p className="text-[10px] text-slate-400 font-mono">Ajournement / Reprise</p>
                    </div>
                  </div>
                  <p className="text-[11px] text-slate-600 mb-3 line-clamp-2">
                    Ordres officiels d'interruption temporaire et de reprise des travaux.
                  </p>
                </div>
                <div className="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-50">
                  <button
                    type="button"
                    onClick={() => previewDocument('os-arret-reprise')}
                    className="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1"
                  >
                    <Eye size={13} className="text-red-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('os-arret-reprise')}
                    className="py-2 px-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center justify-center gap-1"
                  >
                    <Download size={13} /> PDF
                  </button>
                </div>
              </div>

              {/* DOCUMENT 5 : CPS / MARCHÉ DÉFINITIF */}
              <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:-translate-y-1 hover:shadow-lg hover:shadow-indigo-900/5 transition-all duration-300 flex flex-col justify-between group">
                <div>
                  <div className="flex items-center gap-2.5 mb-3 border-b border-slate-100 pb-2.5">
                    <div className="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                      <FileSignature size={18} />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-slate-900 text-xs">Marché Définitif</h4>
                      <p className="text-[10px] text-slate-400 font-mono">Contrat contractuel</p>
                    </div>
                  </div>
                  <div className="mb-2">
                    <label className={`w-full py-1.5 px-2 font-bold text-[10px] rounded-lg border transition-all flex items-center justify-center gap-1.5 cursor-pointer ${cpsUploaded ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'}`}>
                      {isUploadingCps ? <Loader2 size={12} className="animate-spin" /> : cpsUploaded ? <CheckCircle size={12} className="text-emerald-500" /> : <Upload size={12} />}
                      {isUploadingCps ? 'Import...' : cpsUploaded ? 'CPS Importé' : 'Importer CPS'}
                      <input type="file" accept=".pdf,.doc,.docx" className="hidden" onChange={handleUploadCps} disabled={isUploadingCps} />
                    </label>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-50">
                  <button
                    type="button"
                    onClick={() => previewDocument('contrat-marche')}
                    className="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1"
                  >
                    <Eye size={13} className="text-indigo-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('contrat-marche')}
                    className="py-2 px-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center justify-center gap-1"
                  >
                    <Download size={13} /> {formData.chemin_cps ? 'Word' : 'PDF'}
                  </button>
                </div>
              </div>

            </div>

            {/* BANNIÈRE ETAPE SUIVANTE : LIQUIDATION */}
            {id && id !== 'nouveau' && (
              <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white rounded-2xl p-4 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4 border border-white/10">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-white/10 rounded-xl backdrop-blur-sm">
                    <ReceiptText size={20} className="text-blue-200" />
                  </div>
                  <div>
                    <h4 className="font-extrabold text-white text-sm">Étape Suivante : Liquidation & Décomptes</h4>
                    <p className="text-xs text-blue-200">
                      L'engagement est validé ? Accédez directement au traitement des décomptes et constats de service fait.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => navigate(`/liquidations/marches/${id}`)}
                  className="py-2.5 px-4 bg-white text-blue-950 hover:bg-blue-50 font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2 whitespace-nowrap group"
                >
                  <span>Passer à la Liquidation</span>
                  <ArrowRight size={15} className="group-hover:translate-x-1 transition-transform" />
                </button>
              </div>
            )}

            <div className="p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl">
              <p className="text-xs text-blue-800 leading-relaxed font-medium">
                💡 <strong>Rappel :</strong> Assurez-vous d'avoir cliqué sur <span className="font-bold">"Enregistrer l'Engagement"</span> après vos modifications avant de télécharger les documents, afin qu'ils incluent les dernières valeurs saisies.
              </p>
            </div>
          </div>

        </div>

      </main>
    </div>
  );
};

export default TraitementEngagement;
