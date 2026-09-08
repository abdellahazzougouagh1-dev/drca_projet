import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, FileText, Wallet, Landmark, Layers, History, 
  Download, Printer, Eye, CheckCircle2, Clock, AlertTriangle, 
  Send, Check, X, Building, Receipt, Plus, Edit2, Trash2,
  Calendar, ShieldCheck, User, ArrowRight, ExternalLink,
  WalletCards, FileCheck
} from 'lucide-react';
import api from '../api/axios';
import DocumentPreviewModal from '../components/ordonnancement/DocumentPreviewModal';

export default function DossierOrdonnancement() {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState('infos'); // infos, liquidation, paiements, documents, historique

  // Document Preview Modal State
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewDocType, setPreviewDocType] = useState('op');
  const [previewOrdre, setPreviewOrdre] = useState(null);

  // Status Action Modal State
  const [statusModalOpen, setStatusModalOpen] = useState(false);
  const [targetStatut, setTargetStatut] = useState('');
  const [statusComment, setStatusComment] = useState('');
  const [statusDate, setStatusDate] = useState(new Date().toISOString().split('T')[0]);
  const [statusLoading, setStatusLoading] = useState(false);

  // Ordre Add/Edit Modal
  const [ordreModalOpen, setOrdreModalOpen] = useState(false);
  const [editingOrdre, setEditingOrdre] = useState(null);
  const [ordreModalError, setOrdreModalError] = useState('');
  const [ordreForm, setOrdreForm] = useState({
    num_ordre: '',
    type_mouvement: 'Paiement fournisseur',
    mode_paiement: 'Virement',
    beneficiaire: '',
    rib_compte: '',
    banque_agence: '',
    creance: 'Reste à payer',
    montant: 0,
    statut: 'Brouillon',
    date_ordre: new Date().toISOString().split('T')[0],
    reference: '',
    observations: ''
  });

  useEffect(() => {
    fetchDossier();
  }, [id]);

  const fetchDossier = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/ordonnancements/${id}`);
      setData(res.data.data);
    } catch (err) {
      console.error('Erreur chargement dossier ordonnancement:', err);
      setError('Impossible de charger le dossier d\'ordonnancement.');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenPreview = (type, ordre = null) => {
    setPreviewDocType(type);
    setPreviewOrdre(ordre);
    setPreviewOpen(true);
  };

  const handleDownloadDoc = async (type, ordreId = null) => {
    try {
      const endpoint = `/ordonnancements/${id}/documents/${type}${ordreId ? `/${ordreId}` : ''}`;
      const res = await api.get(endpoint, { responseType: 'blob' });
      const mimeType = res.data.type || res.headers['content-type'] || 'application/pdf';
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: mimeType }));
      const link = document.createElement('a');
      link.href = fileUrl;
      const docLabel = type === 'etat_liquidation' ? 'Etat_Liquidation' : type.toUpperCase();
      const safeNum = (data?.num_ordonnancement || id).replace(/[^a-zA-Z0-9_\-]/g, '_');
      link.setAttribute('download', `${docLabel}_${safeNum}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(fileUrl);
    } catch (err) {
      console.error('Erreur téléchargement document:', err);
      alert('Erreur lors du téléchargement du document PDF.');
    }
  };

  const openStatusChange = (newStatut) => {
    setTargetStatut(newStatut);
    setStatusComment('');
    setStatusDate(new Date().toISOString().split('T')[0]);
    setStatusModalOpen(true);
  };

  const confirmStatusChange = async () => {
    try {
      setStatusLoading(true);
      const payload = {
        statut: targetStatut,
        motif_rejet: targetStatut === 'Rejeté' ? statusComment : null,
        date_transmission_tresorier: targetStatut === 'Transmis au trésorier' ? statusDate : null,
        date_paiement: targetStatut === 'Payé' ? statusDate : null,
      };
      await api.patch(`/ordonnancements/${id}/statut`, payload);
      setStatusModalOpen(false);
      fetchDossier();
    } catch (err) {
      console.error('Erreur mise à jour statut:', err);
      alert('Erreur lors du changement de statut');
    } finally {
      setStatusLoading(false);
    }
  };

  const montantLiquidation = Number(data?.liquidation?.montant_brut_ttc || data?.liquidation?.montant_ttc || data?.montant_brut || 0);
  const totalOrdres = (data?.ordres || []).reduce((sum, o) => sum + Number(o.montant || 0), 0);
  const resteAOrdonnancer = Math.max(0, montantLiquidation - totalOrdres);

  // Order CRUD
  const openAddOrdre = () => {
    setEditingOrdre(null);
    const nextSeq = String((data?.ordres?.length || 0) + 1).padStart(3, '0');
    setOrdreForm({
      num_ordre: `OP-${nextSeq}`,
      type_mouvement: 'Paiement fournisseur',
      mode_paiement: 'Virement',
      beneficiaire: data?.beneficiaire_nom || data?.fournisseur?.raison_sociale || '',
      rib_compte: data?.fournisseur?.rib || '',
      banque_agence: data?.fournisseur?.banque || '',
      creance: 'Reste à payer',
      montant: '', // Montant saisi manuellement par l'utilisateur
      statut: 'Brouillon',
      date_ordre: new Date().toISOString().split('T')[0],
      reference: data?.reference || '',
      observations: ''
    });
    setOrdreModalError('');
    setOrdreModalOpen(true);
  };

  const openEditOrdre = (ordre) => {
    setEditingOrdre(ordre);
    setOrdreForm({ 
      ...ordre,
      date_ordre: ordre.date_ordre || new Date().toISOString().split('T')[0],
      reference: ordre.reference || data?.reference || ''
    });
    setOrdreModalError('');
    setOrdreModalOpen(true);
  };

  const saveOrdre = async () => {
    setOrdreModalError('');
    const montant = Number(ordreForm.montant || 0);
    if (montant <= 0) {
      setOrdreModalError('Veuillez renseigner un montant supérieur à 0.');
      return;
    }

    const capacityMax = editingOrdre 
      ? (resteAOrdonnancer + Number(editingOrdre.montant || 0)) 
      : resteAOrdonnancer;

    if (montant > (capacityMax + 0.05)) {
      setOrdreModalError(`Le montant total des ordres dépasse le montant disponible de la liquidation (${formatDH(capacityMax)} restant).`);
      return;
    }

    try {
      if (editingOrdre) {
        await api.put(`/ordonnancements/${id}/ordres/${editingOrdre.id}`, ordreForm);
      } else {
        await api.post(`/ordonnancements/${id}/ordres`, ordreForm);
      }
      setOrdreModalOpen(false);
      fetchDossier();
    } catch (err) {
      console.error('Erreur sauvegarde ordre:', err);
      const msg = err.response?.data?.errors?.montant?.[0] || err.response?.data?.message || 'Erreur lors de l\'enregistrement de l\'ordre.';
      setOrdreModalError(msg);
    }
  };

  const deleteOrdre = async (ordreId) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet ordre ?')) return;
    try {
      await api.delete(`/ordonnancements/${id}/ordres/${ordreId}`);
      fetchDossier();
    } catch (err) {
      console.error('Erreur suppression ordre:', err);
      alert('Erreur lors de la suppression.');
    }
  };

  const formatDH = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0) + ' DH';

  const getStatusBadge = (statut) => {
    switch (statut) {
      case 'Payé':
        return <span className="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold flex items-center gap-1.5"><CheckCircle2 size={13}/> Payé</span>;
      case 'Transmis au trésorier':
        return <span className="px-3 py-1 bg-blue-100 text-blue-800 border border-blue-200 rounded-full text-xs font-bold flex items-center gap-1.5"><Send size={13}/> Transmis au trésorier</span>;
      case 'Totalement ordonnancée':
      case 'Totalement ordonnancé':
      case 'Ordonnancé':
        return <span className="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold flex items-center gap-1.5"><Check size={13}/> Totalement ordonnancé</span>;
      case 'Partiellement ordonnancée':
      case 'Partiellement ordonnancé':
        return <span className="px-3 py-1 bg-sky-100 text-sky-800 border border-sky-200 rounded-full text-xs font-bold flex items-center gap-1.5"><Clock size={13}/> Partiellement ordonnancé</span>;
      case 'Non ordonnancée':
      case 'Non ordonnancé':
        return <span className="px-3 py-1 bg-amber-100 text-amber-800 border border-amber-200 rounded-full text-xs font-bold flex items-center gap-1.5"><Clock size={13}/> Non ordonnancé</span>;
      case 'Rejeté':
      case 'Annulé':
        return <span className="px-3 py-1 bg-rose-100 text-rose-800 border border-rose-200 rounded-full text-xs font-bold flex items-center gap-1.5"><AlertTriangle size={13}/> {statut}</span>;
      case 'À payer':
      case 'À vérifier':
      default:
        return <span className="px-3 py-1 bg-amber-100 text-amber-800 border border-amber-200 rounded-full text-xs font-bold flex items-center gap-1.5"><Clock size={13}/> {statut || 'À payer'}</span>;
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] flex items-center justify-center">
        <div className="text-center space-y-3">
          <div className="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p className="text-slate-600 text-sm font-medium">Chargement du dossier d'ordonnancement...</p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] p-6 flex items-center justify-center">
        <div className="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 text-center max-w-md">
          <AlertTriangle className="w-12 h-12 text-rose-500 mx-auto mb-4" />
          <h2 className="text-lg font-bold text-slate-800">Dossier introuvable</h2>
          <p className="text-slate-500 text-sm mt-1">{error || 'Le dossier demandé n\'existe pas.'}</p>
          <Link
            to="/ordonnancements"
            className="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow transition"
          >
            <ArrowLeft size={16} />
            Retour au registre
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] font-sans flex flex-col pb-24">
      
      {/* STICKY HEADER */}
      <header className="bg-white/95 border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur">
        <div className="w-full px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
          <div className="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <button
                onClick={() => navigate('/ordonnancements')}
                className="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-all"
                title="Retour au registre"
              >
                <ArrowLeft size={20} />
              </button>
              <div>
                <div className="flex items-center gap-3">
                  <h1 className="text-2xl font-extrabold text-slate-800 tracking-tight">
                    Dossier d'Ordonnancement
                  </h1>
                  {getStatusBadge(data.statut)}
                </div>
                <p className="text-sm font-medium text-slate-500 flex items-center gap-2 mt-1">
                  <span className="text-blue-600 font-bold">{data.num_ordonnancement}</span>
                  <span>•</span>
                  <span>Exercice {data.exercice}</span>
                  <span>•</span>
                  <span>Réf: {data.reference}</span>
                </p>
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
              {data.statut === 'À payer' && (
                <button
                  onClick={() => openStatusChange('Transmis au trésorier')}
                  className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2"
                >
                  <Send size={15} />
                  Transmettre au trésorier
                </button>
              )}

              {data.statut === 'Transmis au trésorier' && (
                <button
                  onClick={() => openStatusChange('Payé')}
                  className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2"
                >
                  <CheckCircle2 size={15} />
                  Confirmer Paiement
                </button>
              )}


            </div>
          </div>
        </div>
      </header>

      <main className="w-full px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8 space-y-6">

        {/* RÉSUMÉ DU DOSSIER D'ORDONNANCEMENT (HERO BANNER) */}
        <div className="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 rounded-[1.5rem] p-6 shadow-xl shadow-blue-900/20 relative overflow-hidden border border-white/10 space-y-4">
          <div className="absolute top-0 right-0 p-4 opacity-[0.03] pointer-events-none transform translate-x-5 -translate-y-5">
            <WalletCards size={120} />
          </div>
          <div className="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.1),_transparent_40%)] pointer-events-none" />

          <div className="flex items-center justify-between relative z-10">
            <h3 className="text-lg font-black text-white flex items-center gap-2">
              <div className="p-2 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10 shadow-inner">
                <WalletCards size={18} className="text-blue-200" />
              </div>
              Dossier d'Ordonnancement & Mouvements Financiers
            </h3>
            <div className="flex items-center gap-2">
              <span className="px-3 py-1 bg-blue-500/20 text-blue-200 border border-blue-400/30 rounded-lg text-xs font-bold font-mono">
                Procédure: {data.type_procedure || 'Marché'}
              </span>
              <span className="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-lg text-xs font-bold font-mono">
                Budget: {data.budget_type || 'Investissement'}
              </span>
            </div>
          </div>

          {/* Ligne 1 : Bénéficiaire, Réf, Liquidation */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 relative z-10">
            <div className="lg:col-span-2 bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">Bénéficiaire</p>
              <div className="flex flex-col">
                <span className="text-sm font-bold text-white truncate max-w-full" title={data.beneficiaire_nom || data.fournisseur?.raison_sociale}>
                  {data.beneficiaire_nom || data.fournisseur?.raison_sociale || '-'}
                </span>
                <span className="text-[10px] text-blue-200/80 font-mono mt-0.5">
                  RIB: {data.rib_compte || data.fournisseur?.rib || 'Non renseigné'}
                </span>
              </div>
            </div>
            <div className="bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">N° Ordonnancement & Réf</p>
              <p className="text-sm font-bold text-white font-mono">{data.num_ordonnancement}</p>
              <p className="text-[10px] text-blue-300 mt-0.5 truncate">Réf: {data.reference || '-'}</p>
            </div>
            <div className="bg-black/20 rounded-xl p-3 border border-white/5 backdrop-blur-sm hover:bg-black/30 transition-colors">
              <p className="text-[10px] font-bold text-blue-300/80 uppercase tracking-widest mb-1">Liquidation source & Créance</p>
              <p className="text-xs font-bold text-white font-mono">{data.liquidation?.num_liquidation || data.reference || '-'}</p>
              <p className="text-[10px] text-blue-300 mt-0.5">
                Créance: {data.creance || 'Reste à payer'}
              </p>
            </div>
          </div>

          {/* Ligne 2 : Montants financiers */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 relative z-10">
            <div className="bg-white/10 rounded-xl p-3 border border-white/10 backdrop-blur-sm">
              <p className="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Montant de la Liquidation</p>
              <p className="text-base font-black text-white mt-1">{formatDH(montantLiquidation)}</p>
            </div>
            <div className="bg-gradient-to-br from-blue-600/60 to-blue-800/40 rounded-xl p-3 border border-blue-400/30 backdrop-blur-sm shadow-md">
              <p className="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Total des Ordres ({data.ordres?.length || 0} Ordre(s))</p>
              <p className="text-lg font-black text-white mt-0.5 drop-shadow">{formatDH(totalOrdres)}</p>
            </div>
            <div className={`p-3 rounded-xl border backdrop-blur-sm ${
              resteAOrdonnancer <= 0.01 
                ? 'bg-emerald-500/20 border-emerald-400/30' 
                : 'bg-amber-500/20 border-amber-400/30'
            }`}>
              <p className={`text-[10px] font-bold uppercase tracking-wider ${
                resteAOrdonnancer <= 0.01 ? 'text-emerald-300' : 'text-amber-300'
              }`}>
                {resteAOrdonnancer <= 0.01 ? 'Reste à ordonnancer (Clôturé)' : 'Reste disponible'}
              </p>
              <p className="text-lg font-black text-white mt-0.5 drop-shadow">
                {formatDH(resteAOrdonnancer)}
              </p>
            </div>
          </div>
        </div>



        {/* PERMANENT FINANCIAL CONTROL BANNER (CONTRÔLE DES MONTANTS) */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b border-slate-100">
            <div>
              <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <Receipt className="text-blue-600" size={18} />
                Contrôle des montants de la liquidation
              </h2>
              <p className="text-xs text-slate-500">
                Liquidation liée : <strong className="text-slate-800 font-mono">{data.liquidation?.num_liquidation || data.reference}</strong>
              </p>
            </div>
            <div>
              {resteAOrdonnancer <= 0.01 ? (
                <span className="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold inline-flex items-center gap-1.5">
                  <CheckCircle2 size={14} /> Totalement ordonnancé (100%)
                </span>
              ) : (
                <span className="px-3 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded-full text-xs font-bold inline-flex items-center gap-1.5">
                  <Clock size={14} /> Partiellement ordonnancé ({Math.round((totalOrdres / (montantLiquidation || 1)) * 100)}%)
                </span>
              )}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl">
              <span className="text-xs font-bold text-slate-500 uppercase tracking-wider block">Montant de la liquidation</span>
              <span className="text-xl font-black text-slate-900 mt-1 block">{formatDH(montantLiquidation)}</span>
            </div>

            <div className="p-4 bg-blue-50/70 border border-blue-200 rounded-xl">
              <span className="text-xs font-bold text-blue-700 uppercase tracking-wider block">Total des ordres créés</span>
              <span className="text-xl font-black text-blue-800 mt-1 block">{formatDH(totalOrdres)}</span>
              <span className="text-[11px] text-blue-600 font-medium">({data.ordres?.length || 0} ordre(s) de paiement)</span>
            </div>

            <div className={`p-4 rounded-xl border ${
              resteAOrdonnancer <= 0.01 
                ? 'bg-emerald-50/70 border-emerald-200' 
                : 'bg-amber-50/70 border-amber-200'
            }`}>
              <span className={`text-xs font-bold uppercase tracking-wider block ${
                resteAOrdonnancer <= 0.01 ? 'text-emerald-700' : 'text-amber-700'
              }`}>
                Reste à ordonnancer
              </span>
              <span className={`text-xl font-black mt-1 block ${
                resteAOrdonnancer <= 0.01 ? 'text-emerald-800' : 'text-amber-800'
              }`}>
                {formatDH(resteAOrdonnancer)}
              </span>
              <span className="text-[11px] text-slate-500">
                {resteAOrdonnancer <= 0.01 ? 'Totalité de la liquidation ordonnancée' : 'Montant encore disponible'}
              </span>
            </div>
          </div>
        </div>

        {/* 5 Navigation Tabs */}
        <div className="border-b border-slate-200">
          <nav className="flex space-x-6 overflow-x-auto text-sm font-semibold">
            {[
              { id: 'infos', label: 'Informations générales', icon: FileText },
              { id: 'liquidation', label: 'Liquidation', icon: Receipt },
              { id: 'paiements', label: `Ordres de paiement (${data.ordres?.length || 0})`, icon: Wallet },
              { id: 'documents', label: 'Documents du dossier', icon: Landmark },
            ].map(tab => {
              const Icon = tab.icon;
              const isActive = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`py-3 px-2 border-b-2 flex items-center gap-2 whitespace-nowrap transition ${
                    isActive
                      ? 'border-blue-600 text-blue-600 font-bold'
                      : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
                  }`}
                >
                  <Icon size={16} className={isActive ? 'text-blue-600' : 'text-slate-400'} />
                  {tab.label}
                </button>
              );
            })}
          </nav>
        </div>

        {/* ---------------- TAB CONTENT ---------------- */}

        {/* TAB 1: INFORMATIONS GÉNÉRALES */}
        {activeTab === 'infos' && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {/* Left Col : General Infos Card (2/3) */}
            <div className="lg:col-span-2 space-y-6">
              <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                  <Building size={16} className="text-blue-600" />
                  Informations générales de l'ordonnancement
                </h3>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Référence :</span>
                    <strong className="text-slate-900 text-sm">{data.reference}</strong>
                  </div>
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Bénéficiaire :</span>
                    <strong className="text-slate-900 text-sm">{data.beneficiaire_nom || data.fournisseur?.raison_sociale}</strong>
                  </div>
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Budget :</span>
                    <span className="font-semibold text-slate-800">{data.budget_type}</span>
                  </div>
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Type de procédure :</span>
                    <span className="font-semibold text-slate-800">{data.type_procedure}</span>
                  </div>
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Créance :</span>
                    <span className="font-semibold text-slate-800">{data.creance}</span>
                  </div>
                  <div>
                    <span className="text-slate-500 block mb-0.5 font-medium">Code Imputation :</span>
                    <span className="font-mono font-bold text-slate-800">{data.code_imputation || '225320'}</span>
                  </div>
                </div>

                <div className="pt-2 border-t border-slate-100">
                  <span className="text-slate-500 text-xs block mb-1 font-medium">Imputation budgétaire :</span>
                  <div className="grid grid-cols-4 gap-2 text-center text-xs">
                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                      <span className="text-[10px] text-slate-400 font-bold block">ART</span>
                      <strong className="text-slate-800 text-sm">{data.article || '415'}</strong>
                    </div>
                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                      <span className="text-[10px] text-slate-400 font-bold block">PAR</span>
                      <strong className="text-slate-800 text-sm">{data.paragraphe || '20'}</strong>
                    </div>
                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                      <span className="text-[10px] text-slate-400 font-bold block">LIG</span>
                      <strong className="text-slate-800 text-sm">{data.ligne || '13'}</strong>
                    </div>
                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                      <span className="text-[10px] text-slate-400 font-bold block">S/LIG</span>
                      <strong className="text-slate-800 text-sm">{data.sous_ligne || '0'}</strong>
                    </div>
                  </div>
                </div>

                <div>
                  <span className="text-slate-500 text-xs block mb-1 font-medium">Intitulé / Objet de la dépense :</span>
                  <p className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 leading-relaxed font-medium">
                    {data.intitule_depense || 'Acquisition des semences et engrais pour les Écoles aux champs de la Région de Rabat-Salé-Kénitra'}
                  </p>
                </div>

                {data.observations && (
                  <div>
                    <span className="text-slate-500 text-xs block mb-1 font-medium">Observations :</span>
                    <p className="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 leading-relaxed">
                      {data.observations}
                    </p>
                  </div>
                )}
              </div>
            </div>

            {/* Right Col : Financial Summary Card (1/3) */}
            <div className="space-y-6">
              <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                  <Receipt size={16} className="text-blue-600" />
                  Récapitulatif des montants
                </h3>

                <div className="space-y-3 text-xs">
                  <div className="flex justify-between py-1.5 border-b border-slate-100">
                    <span className="text-slate-600 font-medium">Montant TTC (Brut) :</span>
                    <strong className="text-slate-900 text-sm">{formatDH(data.montant_brut)}</strong>
                  </div>

                  <div className="flex justify-between py-1.5 text-rose-700 border-b border-slate-100">
                    <span>Retenue à la source TVA :</span>
                    <strong className="font-semibold">- {formatDH(data.retenue_tva)}</strong>
                  </div>

                  <div className="flex justify-between py-1.5 text-rose-700 border-b border-slate-100">
                    <span>Retenue à la source IAS :</span>
                    <strong className="font-semibold">- {formatDH(data.retenue_ias)}</strong>
                  </div>

                  {data.autres_retenues > 0 && (
                    <div className="flex justify-between py-1.5 text-rose-700 border-b border-slate-100">
                      <span>Autres retenues :</span>
                      <strong className="font-semibold">- {formatDH(data.autres_retenues)}</strong>
                    </div>
                  )}

                  <div className="p-4 bg-blue-50 border-2 border-blue-200 rounded-2xl mt-4">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-blue-900 block">
                      Net à payer au bénéficiaire
                    </span>
                    <div className="text-2xl font-black text-blue-700 mt-1">
                      {formatDH(data.net_a_payer)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Quick Actions Card */}
              <div className="bg-gradient-to-br from-blue-900 via-blue-800 to-slate-900 text-white p-5 rounded-2xl shadow-sm space-y-3 border border-white/10">
                <h4 className="text-xs font-bold uppercase tracking-wider text-blue-200">
                  Documents Prêts
                </h4>
                <div className="space-y-2 text-xs">
                  <button
                    onClick={() => handleOpenPreview('op')}
                    className="w-full text-left px-3.5 py-2.5 bg-blue-800/80 hover:bg-blue-700 rounded-xl flex justify-between items-center transition cursor-pointer"
                  >
                    <span>Ordre de paiement (OP)</span>
                    <Eye size={15} />
                  </button>
                  <button
                    onClick={() => handleOpenPreview('ov')}
                    className="w-full text-left px-3.5 py-2.5 bg-blue-800/80 hover:bg-blue-700 rounded-xl flex justify-between items-center transition cursor-pointer"
                  >
                    <span>Ordre de virement (OV)</span>
                    <Eye size={15} />
                  </button>
                  <button
                    onClick={() => handleOpenPreview('oi')}
                    className="w-full text-left px-3.5 py-2.5 bg-blue-800/80 hover:bg-blue-700 rounded-xl flex justify-between items-center transition cursor-pointer"
                  >
                    <span>Ordre d'imputation (OI)</span>
                    <Eye size={15} />
                  </button>
                </div>
              </div>

            </div>

          </div>
        )}

        {/* TAB 2: LIQUIDATION LIÉE */}
        {activeTab === 'liquidation' && (
          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
            <div className="flex justify-between items-center pb-3 border-b border-slate-100">
              <div>
                <h3 className="text-sm font-bold text-slate-900">
                  Détails de la liquidation source
                </h3>
                <p className="text-xs text-slate-500">
                  Étape préalable validée d'où provient cet ordonnancement.
                </p>
              </div>
              {data.liquidation && (
                <span className="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold">
                  {data.liquidation.statut || 'ORDONNANCÉE'}
                </span>
              )}
            </div>

            {data.liquidation ? (
              <div className="space-y-6">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                  <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <span className="text-slate-400 block mb-0.5 font-medium">N° Décompte / Liq :</span>
                    <strong className="text-slate-800 font-bold">{data.liquidation.num_liquidation || data.liquidation.num_decompte || 'LIQ-2024-001'}</strong>
                  </div>
                  <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <span className="text-slate-400 block mb-0.5 font-medium">Date Service Fait :</span>
                    <strong className="text-slate-800 font-bold">{data.liquidation.date_service_fait || data.liquidation.date_reception || '-'}</strong>
                  </div>
                  <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <span className="text-slate-400 block mb-0.5 font-medium">Type Décompte :</span>
                    <strong className="text-slate-800 font-bold uppercase">{data.liquidation.type_decompte || 'Provisoire'}</strong>
                  </div>
                </div>

                {data.liquidation.lignes && data.liquidation.lignes.length > 0 && (
                  <div>
                    <h4 className="text-xs font-bold text-slate-800 mb-3 uppercase tracking-wider">Lignes du décompte</h4>
                    <div className="border border-slate-200 rounded-2xl overflow-hidden">
                      <table className="w-full text-sm text-left">
                        <thead className="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 text-xs uppercase tracking-wider">
                          <tr>
                            <th className="px-5 py-3.5">Désignation</th>
                            <th className="px-4 py-3.5 text-center">Unité</th>
                            <th className="px-5 py-3.5 text-right">Qté Exécutée</th>
                            <th className="px-5 py-3.5 text-right">P.U HT</th>
                            <th className="px-5 py-3.5 text-right">Total HT</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {data.liquidation.lignes.map((l, i) => (
                            <tr key={i} className="hover:bg-slate-50 transition">
                              <td className="px-5 py-3.5 font-medium text-slate-900">{l.designation}</td>
                              <td className="px-4 py-3.5 text-center text-slate-600 font-medium">{l.unite}</td>
                              <td className="px-5 py-3.5 text-right font-mono text-slate-700">{l.quantite_executee}</td>
                              <td className="px-5 py-3.5 text-right font-mono text-slate-700">{formatDH(l.prix_unitaire_ht)}</td>
                              <td className="px-5 py-3.5 text-right font-mono font-bold text-slate-900">{formatDH(l.montant_ht)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <div className="p-8 text-center text-slate-400 text-xs italic">
                Aucun décompte de liquidation détaillé rattaché directement. Les informations financières ont été importées depuis l'engagement {data.reference}.
              </div>
            )}
          </div>
        )}

        {/* TAB 3: ORDRES DE PAIEMENT */}
        {activeTab === 'paiements' && (
          <div className="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-slate-100">
              <div>
                <h3 className="text-base font-extrabold text-slate-900 uppercase tracking-wide flex items-center gap-2">
                  <Wallet className="text-blue-600" size={20} />
                  Ordres de paiement
                </h3>
                <p className="text-xs text-slate-500 font-medium mt-0.5">
                  Détail de l'ensemble des ordres émis pour cet ordonnancement ({data.ordres?.length || 0} ordre(s)).
                </p>
              </div>
              <button
                onClick={openAddOrdre}
                className="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer"
              >
                <Plus size={16} />
                + Ajouter un ordre de paiement
              </button>
            </div>

            <div className="border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
              <table className="w-full text-sm text-left">
                <thead className="bg-slate-50 text-slate-700 font-extrabold uppercase tracking-wider text-xs border-b border-slate-200">
                  <tr>
                    <th className="px-5 py-4">N° ordre</th>
                    <th className="px-5 py-4">Type</th>
                    <th className="px-5 py-4">Bénéficiaire</th>
                    <th className="px-5 py-4 text-right">Montant</th>
                    <th className="px-5 py-4 text-center">Date</th>
                    <th className="px-5 py-4 text-center">Statut</th>
                    <th className="px-5 py-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {data.ordres?.map((ord, idx) => (
                    <tr key={ord.id || idx} className="hover:bg-blue-50/40 transition">
                      <td className="px-5 py-4 font-mono font-bold text-blue-900">
                        <span className="px-3 py-1 bg-slate-100 rounded-lg font-bold text-xs border border-slate-200">
                          {ord.num_ordre || `OP-${String(idx + 1).padStart(3, '0')}`}
                        </span>
                      </td>
                      <td className="px-5 py-4 font-semibold text-slate-800">
                        {ord.type_mouvement}
                      </td>
                      <td className="px-5 py-4">
                        <div className="font-bold text-slate-900">{ord.beneficiaire}</div>
                        {ord.rib_compte && (
                          <div className="font-mono text-xs text-slate-400 tracking-wider mt-0.5">{ord.rib_compte}</div>
                        )}
                      </td>
                      <td className="px-5 py-4 text-right font-mono font-black text-blue-700 text-base">
                        {formatDH(ord.montant)}
                      </td>
                      <td className="px-5 py-4 text-center text-slate-600 whitespace-nowrap font-medium">
                        {ord.date_ordre ? new Date(ord.date_ordre).toLocaleDateString('fr-FR') : (data.date_ordonnancement ? new Date(data.date_ordonnancement).toLocaleDateString('fr-FR') : '-')}
                      </td>
                      <td className="px-5 py-4 text-center">
                        <span className={`px-3 py-1 rounded-full text-xs font-bold ${
                          ord.statut === 'Payé' 
                            ? 'bg-emerald-100 text-emerald-800' 
                            : 'bg-amber-100 text-amber-800'
                        }`}>
                          {ord.statut || 'Brouillon'}
                        </span>
                      </td>
                      <td className="px-5 py-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            onClick={() => handleOpenPreview(ord.type_mouvement.includes('TVA') ? 'ov' : ord.type_mouvement.includes('IAS') ? 'oi' : 'op', ord)}
                            className="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition cursor-pointer"
                            title="Aperçu document"
                          >
                            <Eye size={17} />
                          </button>
                          <button
                            onClick={() => openEditOrdre(ord)}
                            className="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition cursor-pointer"
                            title="Modifier"
                          >
                            <Edit2 size={17} />
                          </button>
                          <button
                            onClick={() => deleteOrdre(ord.id)}
                            className="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                            title="Supprimer"
                          >
                            <Trash2 size={17} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {(!data.ordres || data.ordres.length === 0) && (
                    <tr>
                      <td colSpan={7} className="px-6 py-12 text-center text-slate-400 italic">
                        Aucun ordre de paiement créé. Cliquez sur « + Ajouter un ordre de paiement ».
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>

            <div className="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-2 text-sm">
              <span className="font-bold text-slate-700 uppercase text-xs tracking-wider">
                TOTAL DES ORDRES DE PAIEMENT :
              </span>
              <strong className="text-lg font-black text-blue-700">
                {formatDH(totalOrdres)} / {formatDH(montantLiquidation)}
              </strong>
            </div>

          </div>
        )}

        {/* TAB 4: DOCUMENTS DU DOSSIER */}
        {activeTab === 'documents' && (
          <div className="space-y-6">
            <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
              <div>
                <h3 className="text-sm font-bold text-slate-900">
                  Documents du dossier d'ordonnancement
                </h3>
                <p className="text-xs text-slate-500 font-medium">
                  Documents officiels générés avec options d'aperçu, téléchargement PDF et impression.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                
                {/* 1. État de Liquidation */}
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
                      onClick={() => handleOpenPreview('etat_liquidation')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('etat_liquidation')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 2. Ordre de Paiement (OP) */}
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
                      onClick={() => handleOpenPreview('op')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('op')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>



                {/* 3. Ordre de Virement (OV) */}
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
                      onClick={() => handleOpenPreview('ov')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('ov')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 4. Ordre de Paiement RAS / IS */}
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
                      onClick={() => handleOpenPreview('op_ras_is')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('op_ras_is')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 5. Ordre de Paiement RAS / TVA */}
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
                      onClick={() => handleOpenPreview('op_ras_tva')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('op_ras_tva')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

                {/* 6. Ordre d'Imputation (OI) */}
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
                      onClick={() => handleOpenPreview('oi')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
                    >
                      <Eye size={14} className="text-blue-600" /> Aperçu
                    </button>
                    <button
                      onClick={() => handleDownloadDoc('oi')}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow transition cursor-pointer"
                    >
                      <Download size={14} /> Télécharger PDF
                    </button>
                  </div>
                </div>

              </div>
            </div>
          </div>
        )}



      </main>

      {/* DOCUMENT PREVIEW MODAL */}
      <DocumentPreviewModal
        isOpen={previewOpen}
        onClose={() => setPreviewOpen(false)}
        ordonnancement={data}
        ordre={previewOrdre}
        docType={previewDocType}
      />

      {/* STATUS TRANSITION MODAL */}
      {statusModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
          <div className="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6 space-y-4 animate-in fade-in zoom-in duration-150 border border-slate-200">
            <h3 className="text-base font-bold text-slate-900">
              Passer le statut à « {targetStatut} »
            </h3>
            <div className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-700 font-semibold mb-1">Date effective</label>
                <input
                  type="date"
                  value={statusDate}
                  onChange={(e) => setStatusDate(e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-slate-700 font-semibold mb-1">Observations / Motif</label>
                <textarea
                  rows={3}
                  value={statusComment}
                  onChange={(e) => setStatusComment(e.target.value)}
                  placeholder="Détails sur l'opération..."
                  className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <button
                onClick={() => setStatusModalOpen(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer"
              >
                Annuler
              </button>
              <button
                onClick={confirmStatusChange}
                disabled={statusLoading}
                className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer"
              >
                {statusLoading ? 'Validation...' : 'Confirmer'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ORDRE ADD/EDIT MODAL */}
      {ordreModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
          <div className="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
            <div className="px-6 py-4 bg-blue-600 text-white flex justify-between items-center">
              <div>
                <h4 className="text-sm font-bold tracking-wide">
                  {editingOrdre ? `Modifier l'ordre ${ordreForm.num_ordre}` : '+ Ajouter un ordre de paiement'}
                </h4>
                <p className="text-[11px] text-blue-100">
                  Numéro d'ordre de paiement personnalisable (plusieurs mouvements peuvent partager le même N° OP)
                </p>
              </div>
              <button onClick={() => setOrdreModalOpen(false)} className="text-white/80 hover:text-white cursor-pointer">
                <X size={20} />
              </button>
            </div>

            {ordreModalError && (
              <div className="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2">
                <AlertTriangle size={16} className="text-rose-600 shrink-0" />
                <span className="font-semibold">{ordreModalError}</span>
              </div>
            )}

            <div className="p-6 space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-700 font-bold mb-1">N° Ordre / OP *</label>
                  <input
                    type="text"
                    value={ordreForm.num_ordre}
                    onChange={(e) => setOrdreForm({ ...ordreForm, num_ordre: e.target.value })}
                    placeholder="ex: OP-001..."
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Date d'ordre *</label>
                  <input
                    type="date"
                    value={ordreForm.date_ordre}
                    onChange={(e) => setOrdreForm({ ...ordreForm, date_ordre: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Type d'ordre *</label>
                  <select
                    value={ordreForm.type_mouvement}
                    onChange={(e) => setOrdreForm({ ...ordreForm, type_mouvement: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500"
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
                    className="w-full px-3 py-2 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="Virement">Virement bancaire</option>
                    <option value="Chèque">Chèque</option>
                    <option value="Ordre d'imputation">Ordre d'imputation</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-700 font-bold mb-1">Bénéficiaire *</label>
                <input
                  type="text"
                  value={ordreForm.beneficiaire}
                  onChange={(e) => setOrdreForm({ ...ordreForm, beneficiaire: e.target.value })}
                  placeholder="Nom du bénéficiaire..."
                  className="w-full px-3 py-2 border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Créance *</label>
                  <select
                    value={ordreForm.creance}
                    onChange={(e) => setOrdreForm({ ...ordreForm, creance: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="Reste à payer">Reste à payer</option>
                    <option value="C.Neufs">C.Neufs</option>
                    <option value="Reports">Reports</option>
                    <option value="Retenue à la source">Retenue à la source</option>
                    <option value="Crédit Consolidés">Crédit Consolidés</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-700 font-bold mb-1">Référence</label>
                  <input
                    type="text"
                    value={ordreForm.reference}
                    onChange={(e) => setOrdreForm({ ...ordreForm, reference: e.target.value })}
                    placeholder="ex: M-10-2026..."
                    className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-slate-700 font-bold mb-1">Montant (DH) *</label>
                <input
                  type="number"
                  step="0.01"
                  placeholder="Saisir le montant (ex: 8000.00)..."
                  value={ordreForm.montant}
                  onChange={(e) => {
                    setOrdreForm({ ...ordreForm, montant: e.target.value });
                    setOrdreModalError('');
                  }}
                  className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-black text-slate-900 focus:ring-2 focus:ring-blue-500"
                />
              </div>

              {/* Liquidation Financial Context */}
              <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                <div className="flex justify-between text-slate-600">
                  <span>Montant total de la liquidation :</span>
                  <strong className="text-slate-800">{formatDH(montantLiquidation)}</strong>
                </div>
                <div className="flex justify-between text-slate-600">
                  <span>Déjà ordonnancé (autres ordres) :</span>
                  <strong className="text-slate-800">
                    {formatDH(totalOrdres - (editingOrdre ? Number(editingOrdre.montant || 0) : 0))}
                  </strong>
                </div>
                <div className="pt-1.5 border-t border-slate-200 flex justify-between items-center text-blue-900 font-bold">
                  <span>Reste disponible à ordonnancer :</span>
                  <div className="flex items-center gap-2">
                    <span className="text-blue-800 text-sm font-black">
                      {formatDH(editingOrdre ? (resteAOrdonnancer + Number(editingOrdre.montant || 0)) : resteAOrdonnancer)}
                    </span>
                    {resteAOrdonnancer > 0 && !editingOrdre && (
                      <button
                        type="button"
                        onClick={() => setOrdreForm({ ...ordreForm, montant: resteAOrdonnancer })}
                        className="px-2 py-0.5 bg-blue-100 hover:bg-blue-200 text-blue-800 text-[10px] font-bold rounded-lg transition cursor-pointer"
                        title="Remplir le reste disponible"
                      >
                        Tout le reste
                      </button>
                    )}
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-slate-700 font-semibold mb-1">Observations</label>
                <textarea
                  rows={2}
                  value={ordreForm.observations}
                  onChange={(e) => setOrdreForm({ ...ordreForm, observations: e.target.value })}
                  placeholder="Observations facultatives sur cet ordre..."
                  className="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2.5">
              <button
                onClick={() => setOrdreModalOpen(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                Annuler
              </button>
              <button
                onClick={saveOrdre}
                className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer"
              >
                Enregistrer l'ordre
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
