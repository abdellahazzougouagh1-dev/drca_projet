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
      const montantTtc = data.montant ?? '';
      const tauxTva = data.taux_tva ?? '20';
      const montantHt = data.montant_ht || (montantTtc ? (Number(montantTtc) / (1 + (Number(tauxTva) / 100))).toFixed(2) : '');

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
      } else if (name === 'montant') {
        const ttc = parseFloat(value) || 0;
        const tva = parseFloat(next.taux_tva) || 20;
        next.montant_ht = ttc > 0 ? (ttc / (1 + tva / 100)).toFixed(2) : '';
      } else if (name === 'taux_tva') {
        const ht = parseFloat(next.montant_ht) || 0;
        const tva = parseFloat(value) || 20;
        if (ht > 0) {
          next.montant = (ht * (1 + tva / 100)).toFixed(2);
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
        if (!formData.num_decision) missingFields.push("N° Décision d'approbation");
        if (!formData.date_approbation) missingFields.push("Date d'approbation");
        break;
      case 'designation-agent-suivi':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.num_decision) missingFields.push("N° Décision d'approbation");
        if (!formData.date_approbation) missingFields.push("Date d'approbation");
        break;
      case 'os-commencement':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.date_notification_marche) missingFields.push("Date de notification");
        if (!formData.os_numero) missingFields.push("N° OS Commencement");
        if (!formData.os_date_effet) missingFields.push("Date d'effet (OS de commencement)");
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
        if (!formData.num_decision) missingFields.push("N° Décision d'approbation");
        if (!formData.date_approbation) missingFields.push("Date d'approbation");
        break;
      case 'designation-agent-suivi':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.num_decision) missingFields.push("N° Décision d'approbation");
        if (!formData.date_approbation) missingFields.push("Date d'approbation");
        break;
      case 'os-commencement':
        if (!formData.num_marche) missingFields.push("Numéro du marché");
        if (!formData.date_notification_marche) missingFields.push("Date de notification");
        if (!formData.os_numero) missingFields.push("N° OS Commencement");
        if (!formData.os_date_effet) missingFields.push("Date d'effet (OS de commencement)");
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
        <div className="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 rounded-[1.5rem] p-5 shadow-xl shadow-blue-900/20 mb-6 relative overflow-hidden border border-white/10">
          <div className="absolute top-0 right-0 p-4 opacity-[0.03] pointer-events-none transform translate-x-5 -translate-y-5">
            <Briefcase size={120} />
          </div>
          <div className="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.1),_transparent_40%)] pointer-events-none" />

          <h3 className="text-lg font-black text-white mb-4 flex items-center gap-2 relative z-10">
            <div className="p-2 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10 shadow-inner">
              <Briefcase size={18} className="text-blue-200" />
            </div>
            Résumé du Dossier
          </h3>

          <div className="grid grid-cols-1 lg:grid-cols-5 gap-4 relative z-10">
            <div className="lg:col-span-2 bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">Titulaire</p>
              <div className="flex flex-col">
                <span className="text-sm font-bold text-white truncate max-w-full" title={formData.fournisseur?.raison_sociale || formData.titulaire}>{formData.fournisseur?.raison_sociale || formData.titulaire || '-'}</span>
                {formData.fournisseur?.ice && (
                  <span className="text-[10px] text-blue-200/80 font-mono mt-0.5">
                    ICE: {formData.fournisseur.ice} {formData.fournisseur.if ? `| IF: ${formData.fournisseur.if}` : ''}
                  </span>
                )}
              </div>
            </div>
            <div className="bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">Lot</p>
              <p className="text-sm font-bold text-white line-clamp-1">{getLotText(formData.lot)}</p>
            </div>
            <div className="lg:col-span-2 bg-gradient-to-br from-emerald-900/40 to-emerald-800/20 rounded-xl p-3 border border-emerald-500/20 backdrop-blur-sm flex flex-col justify-center">
              <p className="text-[10px] font-bold text-emerald-400/80 uppercase tracking-widest mb-1">Montant d'Attribution TTC</p>
              <div className="flex items-center gap-2">
                <div className="p-1.5 bg-emerald-500/20 rounded-lg shadow-inner border border-emerald-500/30">
                  <Banknote size={16} className="text-emerald-400" />
                </div>
                <span className="text-xl font-black text-white tracking-tight drop-shadow-md">{formatMoney(formData.montant)}</span>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">

          {/* COLONNE GAUCHE: FORMULAIRES */}
          <div className="xl:col-span-2 space-y-8">

            {/* 1. INFORMATIONS DE L'ATTRIBUTION (EN 1ÈRE POSITION) */}
            <div className="bg-white rounded-3xl border border-blue-200 p-6 shadow-md relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-blue-600/5 rounded-full blur-2xl pointer-events-none" />

              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-blue-100 mb-6">
                <div>
                  <h3 className="text-xl font-extrabold text-blue-950 flex items-center gap-2">
                    <Award size={24} className="text-blue-600" />
                    Attribution du Marché
                  </h3>
                  <p className="text-sm text-blue-700 mt-1">
                    Entreprise retenue par la commission : <strong className="uppercase">{formData.titulaire || 'En attente'}</strong> (Offre : {formatMoney(formData.montant)})
                  </p>
                </div>
              </div>

              <div className="space-y-8 relative z-10">

                {/* SOUS-BLOC 1 : INFORMATIONS DU TITULAIRE */}
                <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                  <h4 className="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider flex items-center gap-2">
                    <Building2 size={18} className="text-slate-500" /> Informations du Titulaire
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">Raison Sociale <span className="text-red-500">*</span></label>
                      <input type="text" name="titulaire" value={formData.titulaire || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">ICE <span className="text-red-500">*</span></label>
                      <input type="text" name="ice" value={formData.ice || ''} onChange={handleChange} required className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Identifiant Fiscal (IF)</label>
                      <input type="text" name="if" value={formData.if || ''} onChange={handleChange} className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Registre Commerce (RC)</label>
                      <input type="text" name="rc" value={formData.rc || ''} onChange={handleChange} className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Forme Juridique</label>
                      <input type="text" name="forme_juridique" value={formData.forme_juridique || ''} onChange={handleChange} className={inputClass} placeholder="Ex: SARL, SA, SARL AU" />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Capital Social (DH)</label>
                      <input type="text" name="capital" value={formData.capital || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 100 000" />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">N° Taxe Pro. (Patente)</label>
                      <input type="text" name="patente" value={formData.patente || ''} onChange={handleChange} className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1.5 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">N° d'Affiliation CNSS</label>
                      <input type="text" name="cnss" value={formData.cnss || ''} onChange={handleChange} className={`${inputClass} font-mono`} />
                    </div>
                    <div className="space-y-1.5 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">Adresse <span className="text-red-500">*</span></label>
                      <input type="text" name="adresse" value={formData.adresse || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Ville <span className="text-red-500">*</span></label>
                      <input type="text" name="ville" value={formData.ville || ''} onChange={handleChange} required className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Téléphone</label>
                      <input type="text" name="telephone" value={formData.telephone || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Fax</label>
                      <input type="text" name="fax" value={formData.fax || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Email</label>
                      <input type="email" name="email" value={formData.email || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">Représentant (Gérant)</label>
                      <input type="text" name="representant" value={formData.representant || ''} onChange={handleChange} className={inputClass} />
                    </div>
                  </div>
                </div>

                {/* SOUS-BLOC 2 : INFORMATIONS BANCAIRES */}
                <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                  <h4 className="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider flex items-center gap-2">
                    <Landmark size={18} className="text-slate-500" /> Informations Bancaires
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Banque</label>
                      <input type="text" name="banque" value={formData.banque || ''} onChange={handleChange} className={inputClass} placeholder="Ex: Attijariwafa bank, BCP" />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Agence Bancaire</label>
                      <input type="text" name="agence_bancaire" value={formData.agence_bancaire || ''} onChange={handleChange} className={inputClass} placeholder="Ex: Agence Rabat Hassan" />
                    </div>
                    <div className="space-y-1.5 md:col-span-2">
                      <label className="text-xs font-bold text-slate-700">RIB (24 chiffres)</label>
                      <input type="text" name="rib" value={formData.rib || ''} onChange={handleChange} maxLength={24} className={`${inputClass} font-mono tracking-wider`} placeholder="Ex: 011780000012345678901234" />
                    </div>
                  </div>
                </div>

                {/* SOUS-BLOC 3 : INFORMATIONS DU MARCHÉ ATTRIBUÉ */}
                <div className="bg-blue-50/60 rounded-2xl p-6 border border-blue-200">
                  <h4 className="text-sm font-bold text-blue-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                    <Briefcase size={18} className="text-blue-600" /> Informations du Marché Attribué
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Montant HT (DH)</label>
                      <input type="number" step="0.01" name="montant_ht" value={formData.montant_ht || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">TVA (%)</label>
                      <input type="number" step="0.01" name="taux_tva" value={formData.taux_tva || '20'} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Montant TTC (DH) <span className="text-red-500">*</span></label>
                      <input type="number" step="0.01" name="montant" value={formData.montant || ''} onChange={handleChange} required className={`${inputClass} font-bold text-blue-900`} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Délai d'exécution (Jours) <span className="text-red-500">*</span></label>
                      <input type="number" name="delai_execution" value={formData.delai_execution || ''} onChange={handleChange} required className={inputClass} placeholder="Ex: 60" />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Date début prévue</label>
                      <input type="date" name="date_debut_prevue" value={formData.date_debut_prevue || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-slate-700">Date fin prévue</label>
                      <input type="date" name="date_fin_prevue" value={formData.date_fin_prevue || ''} onChange={handleChange} className={inputClass} />
                    </div>
                    <div className="space-y-1.5 md:col-span-3">
                      <label className="text-xs font-bold text-slate-700">Observations</label>
                      <textarea name="observations" value={formData.observations || ''} onChange={handleChange} rows={2} className={inputClass} placeholder="Remarques et conditions particulières d'attribution..." />
                    </div>
                  </div>
                </div>

              </div>
            </div>

            {/* PARAMÈTRES GÉNÉRAUX & DATES CONTRACTUELLES */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <FileSignature size={18} className="text-blue-600" />
                Paramètres du marché & Dates
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Délai d'exécution (jours) *</label>
                  <input type="number" name="delai_execution" value={formData.delai_execution || ''} onChange={handleChange} required className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date de signature</label>
                  <input type="date" name="date_signature" value={formData.date_signature || ''} onChange={handleChange} className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">N° Décision d'approbation</label>
                  <input type="text" name="num_decision" value={formData.num_decision || ''} onChange={handleChange} className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date d'approbation</label>
                  <input type="date" name="date_approbation" value={formData.date_approbation || ''} onChange={handleChange} className={inputClass} />
                </div>
              </div>
            </div>

            {/* NOTIFICATION & OS */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <ReceiptText size={18} className="text-amber-600" />
                Notification & Ordre de Service
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="md:col-span-2 bg-amber-50 p-4 rounded-xl border border-amber-100">
                  <label className="block text-sm font-bold text-amber-900 mb-2">Date de notification de l'approbation du marché *</label>
                  <input type="date" name="date_notification_marche" value={formData.date_notification_marche || ''} onChange={handleChange} className={`${inputClass} border-amber-200 focus:ring-amber-100 focus:border-amber-500`} />
                  <p className="text-xs text-amber-700 mt-1">Obligatoire pour générer l'OS de commencement.</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">N° OS Commencement</label>
                  <input type="text" name="os_numero" value={formData.os_numero || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 01/2026/M10" />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date signature OS</label>
                  <input type="date" name="os_date_signature" value={formData.os_date_signature || ''} onChange={handleChange} className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Démarrage)</label>
                  <input type="date" name="os_date_effet" value={formData.os_date_effet || ''} onChange={handleChange} className={inputClass} />
                </div>
              </div>
            </div>

            {/* OS ARRÊT & REPRISE */}
            <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
              <h3 className="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <AlertCircle size={18} className="text-red-500" />
                Ajournement & Reprise
              </h3>

              <div className="mb-6 p-4 border border-red-100 bg-red-50 rounded-2xl">
                <h4 className="font-bold text-red-900 mb-3 text-sm">Ordre de Service d'Arrêt (Ajournement)</h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">N° OS d'Arrêt</label>
                    <input type="text" name="os_arret_numero" value={formData.os_arret_numero || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 02/2026/M10" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date signature OS Arrêt</label>
                    <input type="date" name="os_arret_date_signature" value={formData.os_arret_date_signature || ''} onChange={handleChange} className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Arrêt)</label>
                    <input type="date" name="os_arret_date_effet" value={formData.os_arret_date_effet || ''} onChange={handleChange} className={inputClass} />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Motif d'arrêt</label>
                  <input type="text" name="os_arret_motif" value={formData.os_arret_motif || ''} onChange={handleChange} className={inputClass} placeholder="Ex: Conditions climatiques défavorables" />
                </div>
              </div>

              <div className="p-4 border border-emerald-100 bg-emerald-50 rounded-2xl">
                <h4 className="font-bold text-emerald-900 mb-3 text-sm">Ordre de Service de Reprise</h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">N° OS de Reprise</label>
                    <input type="text" name="os_reprise_numero" value={formData.os_reprise_numero || ''} onChange={handleChange} className={inputClass} placeholder="Ex: 03/2026/M10" />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date signature OS Reprise</label>
                    <input type="date" name="os_reprise_date_signature" value={formData.os_reprise_date_signature || ''} onChange={handleChange} className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-slate-700 mb-2">Date d'effet (Reprise)</label>
                    <input type="date" name="os_reprise_date_effet" value={formData.os_reprise_date_effet || ''} onChange={handleChange} className={inputClass} />
                  </div>
                </div>
              </div>
            </div>

          </div>

          {/* COLONNE DROITE: GÉNÉRATION DE DOCUMENTS */}
          <div className="space-y-4">
            <h3 className="text-base font-extrabold text-slate-800 px-2 flex items-center gap-2">
              <Download size={18} className="text-blue-600" />
              Documents à générer
            </h3>

            {/* CARTE 1 : ACTE D'ENGAGEMENT */}
            <div className="bg-white rounded-[1.5rem] border border-slate-200 p-5 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 group relative overflow-hidden">
              <div className="flex items-center gap-3 mb-3 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-blue-50 text-blue-600 rounded-xl"><FileSignature size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">Fiche d'Engagement</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Modèle officiel</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => previewDocument('acte-engagement')}
                  className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                >
                  <Eye size={15} className="text-blue-600" /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('acte-engagement')}
                  className="py-2.5 px-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* CARTE 2 : NOTIFICATION */}
            <div className="bg-white rounded-[1.5rem] border border-slate-200 p-5 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300 group relative overflow-hidden">
              <div className="flex items-center gap-3 mb-3 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl"><CheckCircle size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">Notification d'Approbation</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Document de probation</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => previewDocument('decision-approbation')}
                  className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                >
                  <Eye size={15} className="text-emerald-600" /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('decision-approbation')}
                  className="py-2.5 px-3 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* CARTE 3 : OS DE COMMENCEMENT */}
            <div className="bg-white rounded-[1.5rem] border border-slate-200 p-5 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-900/5 transition-all duration-300 group relative overflow-hidden">
              <div className="flex items-center gap-3 mb-3 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-amber-50 text-amber-600 rounded-xl"><ReceiptText size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">OS de Commencement</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Ordre de service officiel</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => previewDocument('os-commencement')}
                  className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                >
                  <Eye size={15} className="text-amber-600" /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('os-commencement')}
                  className="py-2.5 px-3 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-600 hover:to-amber-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* CARTE 4 : OS ARRÊT ET REPRISE */}
            <div className="bg-white rounded-[1.5rem] border border-slate-200 p-5 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:shadow-red-900/5 transition-all duration-300 group relative overflow-hidden">
              <div className="flex items-center gap-3 mb-3 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-red-50 text-red-600 rounded-xl"><AlertCircle size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">OS d'Arrêt & Reprise</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Ajournement et Reprise</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => previewDocument('os-arret-reprise')}
                  className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                >
                  <Eye size={15} className="text-red-600" /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('os-arret-reprise')}
                  className="py-2.5 px-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* CARTE 6 & 7 : CPS / MARCHE FINAL */}
            <div className="bg-white rounded-[1.5rem] border border-blue-200 p-5 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-900/10 transition-all duration-300 group relative overflow-hidden ring-1 ring-blue-50">
              <div className="flex items-center gap-3 mb-4 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl"><FileSignature size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">CPS / Marché Définitif</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Le CPS se transforme en marché</p>
                </div>
              </div>

              <div className="space-y-3">
                {/* Bouton Import CPS */}
                <label className={`w-full py-2.5 px-4 font-extrabold text-xs rounded-xl border transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer ${cpsUploaded
                  ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 group/upload'
                  }`}>
                  {isUploadingCps ? (
                    <Loader2 size={16} className="animate-spin text-slate-500" />
                  ) : cpsUploaded ? (
                    <CheckCircle size={16} className="text-emerald-500" />
                  ) : (
                    <Upload size={16} className="text-slate-500 group-hover/upload:-translate-y-0.5 transition-transform" />
                  )}
                  {isUploadingCps ? 'Importation...' : cpsUploaded ? '1. CPS Importé' : '1. Importer le CPS'}

                  <input
                    type="file"
                    accept=".pdf,.doc,.docx"
                    className="hidden"
                    onChange={handleUploadCps}
                    disabled={isUploadingCps}
                  />
                </label>

                {/* Boutons Aperçu & Générer Marché */}
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => previewDocument('contrat-marche')}
                    className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                  >
                    <Eye size={15} className="text-indigo-600" /> Aperçu
                  </button>
                  <button
                    type="button"
                    onClick={() => downloadDocument('contrat-marche')}
                    className="py-2.5 px-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                  >
                    <Download size={15} /> Télécharger
                  </button>
                </div>
              </div>
            </div>

            {/* CARTE 8 : DESIGNATION AGENT SUIVI */}
            <div className="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all">
              <div className="flex items-center gap-3 mb-3 border-b border-slate-100 pb-3">
                <div className="p-2.5 bg-teal-50 text-teal-600 rounded-xl"><FileSignature size={20} /></div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-sm">Agent de Suivi</h3>
                  <p className="text-[11px] text-slate-500 font-mono">Décision de nomination</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => previewDocument('designation-agent-suivi')}
                  className="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                >
                  <Eye size={15} className="text-teal-600" /> Aperçu
                </button>
                <button
                  type="button"
                  onClick={() => downloadDocument('designation-agent-suivi')}
                  className="py-2.5 px-3 bg-gradient-to-r from-teal-600 to-teal-500 hover:from-teal-700 hover:to-teal-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
                >
                  <Download size={15} /> Télécharger
                </button>
              </div>
            </div>

            {/* CARTE PASSAGE LIQUIDATION */}
            {id && id !== 'nouveau' && (
              <div className="bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-900 text-white rounded-3xl p-5 shadow-lg shadow-indigo-950/20 border border-indigo-500/20">
                <div className="flex items-center gap-3 mb-3">
                  <div className="p-2.5 bg-white/15 rounded-xl backdrop-blur-sm">
                    <ReceiptText size={22} className="text-white" />
                  </div>
                  <div>
                    <h3 className="font-extrabold text-white text-sm">Étape Suivante</h3>
                    <p className="text-[11px] text-indigo-200 font-medium">Liquidation & Décomptes</p>
                  </div>
                </div>
                <p className="text-xs text-indigo-100 mb-4 leading-relaxed">
                  L'engagement est validé ? Accédez directement au traitement de la liquidation, aux services faits et aux décomptes de ce marché.
                </p>
                <button
                  type="button"
                  onClick={() => navigate(`/liquidations/marches/${id}`)}
                  className="w-full py-3 px-4 bg-white text-indigo-900 hover:bg-indigo-50 font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2 group"
                >
                  <span>Passer à la Liquidation</span>
                  <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                </button>
              </div>
            )}

            <div className="p-4 bg-blue-50 border border-blue-100 rounded-2xl">
              <p className="text-xs text-blue-800 leading-relaxed font-medium">
                <strong>Astuce :</strong> Assurez-vous d'avoir cliqué sur <span className="font-bold">"Enregistrer l'Engagement"</span> après vos modifications avant de télécharger les documents, afin qu'ils incluent les dernières valeurs saisies.
              </p>
            </div>

          </div>
        </div>

      </main>
    </div>
  );
};

export default TraitementEngagement;
