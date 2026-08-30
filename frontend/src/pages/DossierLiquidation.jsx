import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, FileText, CheckCircle2, FileSignature, 
  Banknote, Calendar, Upload, Plus, AlertCircle, Save, Send,
  FileDown, Trash2, Edit, Eye, ReceiptText
} from 'lucide-react';
import api from '../api/axios';

const DossierLiquidation = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('historique'); // historique, saisie, documents
  const [editMode, setEditMode] = useState(false);
  const [currentLiqId, setCurrentLiqId] = useState(null);

  // Form states for new/edit Liquidation
  const [formData, setFormData] = useState(getInitialFormData());

  function getInitialFormData() {
    return {
      num_liquidation: '',
      exercice_budgetaire: new Date().getFullYear().toString(),
      objet_liquidation: '',
      type_execution: 'totale',
      // Service Fait
      reference_service_fait: '',
      date_service_fait: '',
      date_debut_prestations: '',
      date_fin_prestations: '',
      pourcentage_execution: '',
      agent_responsable: '',
      fonction_agent: '',
      service_agent: '',
      // Reception
      type_reception: 'PARTIELLE',
      reference_pv_reception: '',
      date_reception: '',
      num_decision: '',
      date_decision: '',
      date_reunion_commission: '',
      heure_reunion_commission: '',
      commission_reception: [],
      president_commission: '',
      membres_commission: '',
      resultat_reception: '',
      reserves_reception: '',
      date_levee_reserves: '',
      // Facture
      num_facture: '',
      date_facture: '',
      date_reception_facture: '',
      objet_facture: '',
      periode_facture: '',
      echeance_facture: '',
      reference_facture_fournisseur: '',
      // Decompte
      num_decompte: '',
      date_decompte: '',
      type_decompte: 'provisoire',
      periode_du: '',
      periode_au: '',
      montant_brut_ht: 0,
      montant_brut_ttc: 0,
      retenue_garantie: 0,
      penalites_retard: 0,
      avances_a_recuperer: 0,
      autres_retenues: 0,
      autres_deductions: 0,
      // Global
      montant_ht: 0,
      taux_tva: 20,
      montant_tva: 0,
      montant_ttc: 0,
      observations: '',
      lignes: []
    };
  }

  useEffect(() => {
    fetchDossier();
  }, [id]);

  const fetchDossier = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/liquidations/marches/${id}`);
      setData(res.data);
    } catch (err) {
      setError('Erreur lors du chargement du dossier de liquidation.');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleStartNewLiquidation = () => {
    const initData = getInitialFormData();
    const sourceItems = (data?.marche?.bordereau_items && data.marche.bordereau_items.length > 0) 
                         ? data.marche.bordereau_items 
                         : (data?.marche?.lot?.items || []);

    if (sourceItems && sourceItems.length > 0) {
      initData.lignes = sourceItems.map(item => ({
        marche_bordereau_item_id: item.lot_item_id ? item.id : null,
        designation: item.lot_item?.designation || item.lotItem?.designation || item.designation || '',
        unite: item.lot_item?.unite || item.lotItem?.unite || item.unite || '',
        quantite_prevue: item.lot_item?.quantite || item.lotItem?.quantite || item.quantite || 0,
        quantite_executee: 0,
        prix_unitaire_ht: item.prix_unitaire_attributaire || item.prix_unitaire_ht || 0,
        montant_ht: 0,
        taux_tva: item.taux_tva || 20,
        montant_tva: 0,
        montant_ttc: 0,
        observations: ''
      }));
    }
    setFormData(initData);
    setCurrentLiqId(null);
    setEditMode(false);
    setActiveTab('saisie');
  };

  const handleEditLiquidation = (liq) => {
    setFormData({
      ...getInitialFormData(),
      ...liq,
      lignes: liq.lignes || []
    });
    setCurrentLiqId(liq.id);
    setEditMode(true);
    setActiveTab('saisie');
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    let newFormData = { ...formData, [name]: value };
    
    if (name === 'type_execution' && value.toLowerCase() === 'totale' && newFormData.lignes) {
      let totalHT = 0;
      let totalTVA = 0;
      let totalTTC = 0;

      newFormData.lignes = newFormData.lignes.map(ligne => {
        const qte = parseFloat(ligne.quantite_prevue) || 0;
        const pu = parseFloat(ligne.prix_unitaire_ht) || 0;
        const tvaRate = parseFloat(ligne.taux_tva) || 20;
        const ht = qte * pu;
        const tva = (ht * tvaRate) / 100;
        
        totalHT += ht;
        totalTVA += tva;
        totalTTC += (ht + tva);

        return {
          ...ligne,
          quantite_executee: qte,
          montant_ht: ht.toFixed(2),
          montant_tva: tva.toFixed(2),
          montant_ttc: (ht + tva).toFixed(2)
        };
      });

      newFormData.montant_ht = totalHT.toFixed(2);
      newFormData.montant_tva = totalTVA.toFixed(2);
      newFormData.montant_ttc = totalTTC.toFixed(2);
      newFormData.montant_brut_ht = totalHT.toFixed(2);
      newFormData.montant_brut_ttc = totalTTC.toFixed(2);
    }

    // Auto calculate TVA for global fields if needed
    if (name === 'montant_ht' || name === 'taux_tva') {
      const ht = parseFloat(name === 'montant_ht' ? value : newFormData.montant_ht) || 0;
      const tvaRate = parseFloat(name === 'taux_tva' ? value : newFormData.taux_tva) || 0;
      newFormData.montant_tva = ((ht * tvaRate) / 100).toFixed(2);
      newFormData.montant_ttc = (ht + parseFloat(newFormData.montant_tva)).toFixed(2);
      newFormData.montant_brut_ht = newFormData.montant_ht;
      newFormData.montant_brut_ttc = newFormData.montant_ttc;
    }
    setFormData(newFormData);
  };

  const addCommissionMember = () => {
    setFormData({
      ...formData,
      commission_reception: [...(formData.commission_reception || []), { nom: '', fonction: '', qualite: 'Membre' }]
    });
  };

  const removeCommissionMember = (idx) => {
    const newComm = [...(formData.commission_reception || [])];
    newComm.splice(idx, 1);
    setFormData({ ...formData, commission_reception: newComm });
  };

  const handleCommissionChange = (idx, field, value) => {
    const newComm = [...(formData.commission_reception || [])];
    newComm[idx][field] = value;
    setFormData({ ...formData, commission_reception: newComm });
  };

  const handleLigneChange = (index, field, value) => {
    const newLignes = [...formData.lignes];
    const ligne = { ...newLignes[index], [field]: value };

    if (field === 'quantite_executee') {
      const qte = parseFloat(value) || 0;
      const pu = parseFloat(ligne.prix_unitaire_ht) || 0;
      const tvaRate = parseFloat(ligne.taux_tva) || 20;
      const ht = qte * pu;
      const tva = (ht * tvaRate) / 100;
      ligne.montant_ht = ht.toFixed(2);
      ligne.montant_tva = tva.toFixed(2);
      ligne.montant_ttc = (ht + tva).toFixed(2);
    }

    newLignes[index] = ligne;

    // Recalculate totals
    const totalHT = newLignes.reduce((sum, l) => sum + (parseFloat(l.montant_ht) || 0), 0);
    const totalTVA = newLignes.reduce((sum, l) => sum + (parseFloat(l.montant_tva) || 0), 0);
    const totalTTC = newLignes.reduce((sum, l) => sum + (parseFloat(l.montant_ttc) || 0), 0);

    setFormData({
      ...formData,
      lignes: newLignes,
      montant_ht: totalHT.toFixed(2),
      montant_tva: totalTVA.toFixed(2),
      montant_ttc: totalTTC.toFixed(2),
      montant_brut_ht: totalHT.toFixed(2),
      montant_brut_ttc: totalTTC.toFixed(2)
    });
  };

  const handleSaveLiquidation = async (e) => {
    e.preventDefault();
    
    // Règle 4 : CONTRÔLE DU DÉPASSEMENT côté frontend
    // On permet la modification d'une liquidation existante (on ajoute son propre montant au reste autorisé pour le check)
    let resteAutorise = parseFloat(data?.finances?.reste_a_liquider) || 0;
    if (editMode && currentLiqId) {
      const existingLiq = data?.liquidations?.find(l => l.id === currentLiqId);
      if (existingLiq && ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'].includes(existingLiq.statut)) {
          resteAutorise += parseFloat(existingLiq.montant_ttc) || 0;
      }
    }
    
    if (parseFloat(formData.montant_ttc) > resteAutorise + 0.02) {
      alert(`Impossible d'enregistrer : le montant TTC de cette liquidation (${new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(formData.montant_ttc).replace('MAD', 'dh')}) dépasse le Reste à Liquider du marché (${new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(resteAutorise).replace('MAD', 'dh')}).`);
      return;
    }

    try {
      if (editMode && currentLiqId) {
        await api.put(`/liquidations/marches/${id}/${currentLiqId}`, formData);
      } else {
        await api.post(`/liquidations/marches/${id}`, formData);
      }
      setActiveTab('historique');
      fetchDossier();
    } catch (err) {
      if (err.response?.data?.errors?.montant_ttc) {
        alert(err.response.data.errors.montant_ttc[0]);
      } else {
        alert('Erreur lors de l\'enregistrement.');
      }
      console.error(err);
    }
  };

  const handleChangeStatus = async (liquidationId, newStatus) => {
    if (newStatus === 'VALIDÉE') {
      const liq = data?.liquidations?.find(l => l.id === liquidationId);
      const reste = parseFloat(data?.finances?.reste_a_liquider) || 0;
      if (liq && parseFloat(liq.montant_ttc) > reste + 0.02) {
         alert(`Impossible de valider cette liquidation : le montant TTC dépasse le reste à liquider du marché de ${new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(reste).replace('MAD', 'dh')}.`);
         return;
      }
    }

    if (!window.confirm(`Voulez-vous passer cette liquidation au statut : ${newStatus} ?`)) return;
    try {
      await api.patch(`/liquidations/marches/${id}/${liquidationId}/statut`, { statut: newStatus });
      fetchDossier();
    } catch (err) {
      alert(err.response?.data?.error || 'Erreur lors du changement de statut.');
      console.error(err);
    }
  };

  const generateDoc = async (liquidationId, type) => {
    try {
      const res = await api.get(`/liquidations/marches/${id}/${liquidationId}/documents/${type}`, { responseType: 'blob' });
      
      const mimeType = res.data.type || res.headers['content-type'] || '';
      const extension = (mimeType.includes('pdf') || type === 'csf' || type === 'etat_liquidation' || type === 'etat_paiement' || type === 'recapitulatif' || type === 'fiche_transmission' || type === 'pv_reception' || type === 'decompte') ? 'pdf' : 'docx';
      
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${type}_${data.marche.num_marche}.${extension}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      alert('Erreur lors de la génération du document.');
    }
  };

  const previewDoc = async (liquidationId, type) => {
    try {
      const res = await api.get(`/liquidations/marches/${id}/${liquidationId}/documents/${type}?preview=1`, { responseType: 'blob' });
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
      window.open(fileUrl, '_blank');
    } catch (err) {
      alert("Erreur lors de l'ouverture de l'aperçu du document.");
      console.error(err);
    }
  };

  const previewMarcheGlobalDoc = async (type) => {
    try {
      const res = await api.get(`/liquidations/marches/${id}/documents/${type}?preview=1`, { responseType: 'blob' });
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
      window.open(fileUrl, '_blank');
    } catch (err) {
      alert("Erreur lors de l'ouverture de l'aperçu du document.");
      console.error(err);
    }
  };

  const generateMarcheGlobalDoc = async (type) => {
    try {
      const res = await api.get(`/liquidations/marches/${id}/documents/${type}`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${type}_${data?.marche?.num_marche || id}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (err) {
      alert('Erreur lors du téléchargement du document.');
      console.error(err);
    }
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Chargement du dossier...</div>;
  if (error) return <div className="p-8 text-center text-red-500">{error}</div>;
  if (!data) return null;

  const { marche, liquidations, finances } = data;
  const progressPercent = (finances.total_liquide / finances.montant_marche) * 100;

  return (
    <div className="min-h-screen bg-slate-50 font-sans p-6">
      <main className="flex-1 max-w-7xl mx-auto space-y-6">
        
        {/* Header */}
        <div className="flex items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
          <button onClick={() => navigate('/liquidations')} className="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <ArrowLeft size={24} className="text-slate-600" />
          </button>
          <div>
            <h1 className="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
              Dossier de Liquidation <span className="text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg text-lg border border-indigo-100">{marche.num_marche}</span>
            </h1>
            <p className="text-sm text-slate-500 mt-1 line-clamp-1">{marche.objet_marche}</p>
          </div>
        </div>

        {/* Info Marché (Lecture Seule) & Finances */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
             <h2 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><FileText className="text-indigo-600" size={20}/> Informations du Marché (Source : Engagement)</h2>
             <div className="grid grid-cols-2 gap-4 text-sm">
                <div className="col-span-2 p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                  <div>
                    <span className="text-slate-500 block mb-1 text-xs uppercase font-bold">Titulaire</span>
                    <span className="font-semibold text-slate-800">{marche.fournisseur?.raison_sociale}</span>
                  </div>
                  <div className="text-right">
                    <span className="text-slate-500 block mb-1 text-xs uppercase font-bold">ICE / RC</span>
                    <span className="font-semibold text-slate-800 text-xs">
                      {marche.fournisseur?.ice ? `ICE: ${marche.fournisseur.ice}` : 'ICE: N/A'} | {marche.fournisseur?.rc ? `RC: ${marche.fournisseur.rc}` : 'RC: N/A'}
                    </span>
                  </div>
                </div>
                <div className="col-span-2 p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                  <span className="text-indigo-600 block mb-1 text-xs uppercase font-bold">Banque & RIB (pour l'Ordonnancement)</span>
                  <div className="flex justify-between items-center">
                    <span className="font-semibold text-indigo-900">{marche.fournisseur?.banque || 'Non renseignée'}</span>
                    <span className="font-mono font-bold tracking-wider text-indigo-900 text-xs">{marche.fournisseur?.rib || 'Non renseigné'}</span>
                  </div>
                </div>
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100"><span className="text-slate-500 block mb-1 text-xs uppercase font-bold">N° AOO</span><span className="font-semibold text-slate-800">{marche.aoo?.num_aoo}</span></div>
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100"><span className="text-slate-500 block mb-1 text-xs uppercase font-bold">Délai d'exécution</span><span className="font-semibold text-slate-800">{marche.delai_execution} jours</span></div>
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100"><span className="text-slate-500 block mb-1 text-xs uppercase font-bold">Imputation Budgétaire</span><span className="font-semibold text-slate-800 line-clamp-1">{marche.imputation_budgetaire || 'N/A'}</span></div>
             </div>
             
             <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <span className="text-sm font-bold text-slate-700 flex items-center gap-2">
                  <FileDown size={18} className="text-slate-400" /> Documents globaux du marché
                </span>
                <div className="flex items-center gap-2">
                  <button 
                    type="button"
                    onClick={() => previewMarcheGlobalDoc('decision_commission')} 
                    className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-bold flex items-center gap-1.5 shadow-sm transition-all duration-300"
                    title="Aperçu du document"
                  >
                    <Eye size={16} className="text-indigo-600" />
                    <span>Aperçu</span>
                  </button>
                  <button 
                    type="button"
                    onClick={() => generateMarcheGlobalDoc('decision_commission')} 
                    className="px-5 py-2.5 bg-white border-2 border-indigo-100 hover:border-indigo-300 hover:bg-indigo-50 text-indigo-700 rounded-xl text-sm font-extrabold flex items-center gap-2 shadow-sm transition-all duration-300 group"
                  >
                    <FileDown size={16} className="group-hover:-translate-y-0.5 transition-transform" />
                    <span>Télécharger Décision Commission</span>
                  </button>
                </div>
             </div>
          </div>
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h2 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
              <Banknote className="text-indigo-600" size={20} /> Suivi Financier
            </h2>
            <div className="w-full bg-slate-100 rounded-full h-3 mb-4 overflow-hidden">
              <div className="bg-emerald-500 h-3 rounded-full transition-all duration-1000" style={{ width: `${Math.min(progressPercent, 100)}%` }}></div>
            </div>
            <div className="space-y-3">
              <div className="flex justify-between items-center text-sm"><span className="text-slate-500 font-semibold">Marché Initial (TTC)</span><span className="font-bold">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(finances.montant_marche).replace('MAD', 'dh')}</span></div>
              <div className="flex justify-between items-center text-sm"><span className="text-emerald-600 font-semibold">Cumul Validé</span><span className="font-bold text-emerald-700">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(finances.total_liquide).replace('MAD', 'dh')}</span></div>
              <div className="pt-3 border-t border-slate-100 flex justify-between items-center text-sm"><span className="text-amber-600 font-bold">Reste à Liquider</span><span className="font-black text-amber-700 text-lg">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(finances.reste_a_liquider).replace('MAD', 'dh')}</span></div>
              
              {finances.reste_a_liquider <= 0 && (
                <div className="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm font-bold text-center rounded-xl">
                  Le marché est entièrement liquidé.
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 border-b border-slate-200 mb-6">
          <button onClick={() => setActiveTab('historique')} className={`px-4 py-3 text-sm font-bold border-b-2 transition-colors ${activeTab === 'historique' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}>Historique des Liquidations</button>
          
          <button 
            onClick={() => {
              if (activeTab === 'saisie') return;
              if (finances.reste_a_liquider <= 0 && !editMode) {
                alert("Impossible de créer une nouvelle liquidation : le marché est entièrement liquidé.");
                return;
              }
              handleStartNewLiquidation();
            }} 
            className={`px-4 py-3 text-sm font-bold border-b-2 transition-colors ${activeTab === 'saisie' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'} ${finances.reste_a_liquider <= 0 && !editMode ? 'opacity-50 cursor-not-allowed' : ''}`}
            disabled={finances.reste_a_liquider <= 0 && !editMode}
          >
            {editMode ? 'Modifier la Liquidation' : 'Nouvelle Liquidation'}
          </button>
        </div>

        {/* Tab Content: Historique */}
        {activeTab === 'historique' && (
          <div className="space-y-4">
             {liquidations.length === 0 ? (
               <div className="text-center p-12 bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500">
                 <FileSignature size={48} className="mx-auto text-slate-300 mb-4" />
                 <p className="text-lg font-medium">Aucune liquidation enregistrée pour ce marché.</p>
                 <button 
                   onClick={() => {
                     if (finances.reste_a_liquider <= 0) {
                       alert("Impossible de créer une liquidation : le marché est entièrement liquidé.");
                       return;
                     }
                     handleStartNewLiquidation();
                   }} 
                   disabled={finances.reste_a_liquider <= 0}
                   className={`mt-4 px-6 py-2 text-white rounded-xl font-bold transition-colors ${finances.reste_a_liquider <= 0 ? 'bg-slate-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'}`}
                 >
                   Créer le 1er décompte
                 </button>
               </div>
             ) : (
               liquidations.map((liq, idx) => (
                 <div key={liq.id} className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                   <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                     <div>
                       <h3 className="text-xl font-black text-slate-800">Liquidation #{idx+1} <span className="text-sm font-semibold text-slate-500 ml-2">({liq.type_execution.toUpperCase()})</span></h3>
                       <p className="text-sm text-slate-500 flex gap-4 mt-1">
                         <span>Facture: <strong>{liq.num_facture || 'N/A'}</strong></span>
                         <span>Service Fait: <strong>{liq.reference_service_fait || 'N/A'}</strong></span>
                         <span>Décompte: <strong>{liq.num_decompte || 'N/A'}</strong></span>
                       </p>
                     </div>
                     <div className="text-right flex flex-col items-end">
                       <span className={`px-3 py-1 rounded-full text-xs font-bold mb-2 ${
                         liq.statut === 'VALIDÉE' ? 'bg-emerald-100 text-emerald-800' : 
                         liq.statut === 'TRANSMISE À L\'ORDONNANCEMENT' ? 'bg-indigo-100 text-indigo-800' :
                         'bg-amber-100 text-amber-800'
                       }`}>
                         {liq.statut}
                       </span>
                       <p className="text-2xl font-black text-slate-900">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(liq.montant_ttc).replace('MAD', 'dh')}</p>
                     </div>
                   </div>

                   {/* Workflow Actions */}
                   <div className="flex flex-wrap gap-2 py-4 border-y border-slate-100 mb-4 bg-slate-50 -mx-6 px-6">
                     {liq.statut === 'BROUILLON' && (
                       <>
                         <button onClick={() => handleChangeStatus(liq.id, 'À CONTRÔLER')} className="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-xl text-xs font-bold shadow-sm">Soumettre au contrôle</button>
                         <button onClick={() => handleEditLiquidation(liq)} className="px-4 py-2 bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2"><Edit size={14}/> Modifier</button>
                       </>
                     )}
                     {liq.statut === 'À CONTRÔLER' && (
                       <button onClick={() => handleChangeStatus(liq.id, 'VALIDÉE')} className="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-xs font-bold shadow-sm">Valider la liquidation</button>
                     )}
                     {liq.statut === 'VALIDÉE' && (
                       <button onClick={() => handleChangeStatus(liq.id, 'TRANSMISE À L\'ORDONNANCEMENT')} className="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2"><Send size={14}/> Transmettre à l'Ordonnancement</button>
                     )}
                     {liq.statut === 'TRANSMISE À L\'ORDONNANCEMENT' && (
                       <span className="px-4 py-2 bg-slate-200 text-slate-600 rounded-xl text-xs font-bold">Dossier transmis - Verrouillé</span>
                     )}
                   </div>

                    {/* Document Generation */}
                    <div className="pt-2">
                      <h4 className="text-xs font-bold text-slate-500 mb-3 uppercase tracking-wider flex items-center gap-2">
                        <FileText size={15} className="text-indigo-600" />
                        Documents Officiels à Générer
                      </h4>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {/* Carte PV de Réception */}
                        <div className="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                          <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                              <FileSignature size={20} />
                            </div>
                            <div>
                              <h5 className="font-extrabold text-slate-800 text-sm">PV de Réception</h5>
                              <p className="text-[11px] text-slate-400 font-medium">{liq.type_reception ? `Réception ${liq.type_reception.toLowerCase()}` : 'Procès-verbal officiel'}</p>
                            </div>
                          </div>
                          <div className="flex items-center gap-2 self-end sm:self-auto">
                            <button 
                              type="button"
                              onClick={() => previewDoc(liq.id, 'pv_reception')} 
                              className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all"
                              title="Aperçu du PV de Réception"
                            >
                              <Eye size={14} className="text-blue-600"/>
                              <span>Aperçu</span>
                            </button>
                            <button 
                              type="button"
                              onClick={() => generateDoc(liq.id, 'pv_reception')} 
                              className="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all"
                              title="Télécharger le PV de Réception"
                            >
                              <FileDown size={14}/>
                              <span>Télécharger</span>
                            </button>
                          </div>
                        </div>

                        {/* Carte Décompte */}
                        <div className="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                          <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                              <ReceiptText size={20} />
                            </div>
                            <div>
                              <h5 className="font-extrabold text-slate-800 text-sm">Décompte</h5>
                              <p className="text-[11px] text-slate-400 font-medium">{liq.type_decompte ? `Décompte ${liq.type_decompte}` : 'Décompte financier'}</p>
                            </div>
                          </div>
                          <div className="flex items-center gap-2 self-end sm:self-auto">
                            <button 
                              type="button"
                              onClick={() => previewDoc(liq.id, 'decompte')} 
                              className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all"
                              title="Aperçu du Décompte"
                            >
                              <Eye size={14} className="text-indigo-600"/>
                              <span>Aperçu</span>
                            </button>
                            <button 
                              type="button"
                              onClick={() => generateDoc(liq.id, 'decompte')} 
                              className="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all"
                              title="Télécharger le Décompte"
                            >
                              <FileDown size={14}/>
                              <span>Télécharger</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                 </div>
               ))
             )}
          </div>
        )}

        {/* Tab Content: Saisie */}
        {activeTab === 'saisie' && (
          <form onSubmit={handleSaveLiquidation} className="space-y-6">
            <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-8">
              
              {/* Section 1: Infos Générales */}
              <section>
                <h3 className="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">1. Informations de la Liquidation</h3>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Type d'exécution</label>
                    <select name="type_execution" value={formData.type_execution} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                      <option value="totale">Totale</option>
                      <option value="partielle">Partielle</option>
                    </select>
                  </div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">N° Liquidation</label><input type="text" name="num_liquidation" value={formData.num_liquidation} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Exercice</label><input type="text" name="exercice_budgetaire" value={formData.exercice_budgetaire} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Objet</label><input type="text" name="objet_liquidation" value={formData.objet_liquidation} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                </div>
              </section>

              {/* Section 2: Prestations et Lignes */}
              <section>
                <h3 className="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">2. Détail des Prestations (Bordereau)</h3>
                <div className="overflow-x-auto border border-slate-200 rounded-xl">
                  <table className="w-full text-left text-sm">
                    <thead className="bg-slate-50 border-b border-slate-200">
                      <tr>
                        <th className="p-3 font-semibold text-slate-700">Désignation</th>
                        <th className="p-3 font-semibold text-slate-700">Unité</th>
                        <th className="p-3 font-semibold text-slate-700">Qte Prévue</th>
                        <th className="p-3 font-semibold text-indigo-700 bg-indigo-50 w-32">Qte Exécutée</th>
                        <th className="p-3 font-semibold text-slate-700 text-right">PU HT</th>
                        <th className="p-3 font-semibold text-slate-700 text-right">Montant HT</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {formData.lignes.map((ligne, idx) => (
                        <tr key={idx} className="hover:bg-slate-50">
                          <td className="p-3">{ligne.designation}</td>
                          <td className="p-3">{ligne.unite}</td>
                          <td className="p-3 font-medium text-slate-500">{ligne.quantite_prevue}</td>
                          <td className="p-0 bg-indigo-50">
                            <input type="number" step="0.01" min="0" value={ligne.quantite_executee} onChange={(e) => handleLigneChange(idx, 'quantite_executee', e.target.value)} className="w-full p-3 bg-transparent border-none focus:ring-2 focus:ring-indigo-500 font-bold text-indigo-700" />
                          </td>
                          <td className="p-3 text-right">{new Intl.NumberFormat('fr-MA').format(ligne.prix_unitaire_ht).replace('MAD', 'dh')}</td>
                          <td className="p-3 text-right font-bold">{new Intl.NumberFormat('fr-MA').format(ligne.montant_ht).replace('MAD', 'dh')}</td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot className="bg-slate-100 border-t border-slate-200 font-bold">
                      <tr>
                        <td colSpan="5" className="p-3 text-right">Total HT Exécuté :</td>
                        <td className="p-3 text-right text-lg text-slate-900">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(formData.montant_ht).replace('MAD', 'dh')}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </section>

              {/* Section 3: Service Fait */}
              <section>
                <h3 className="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">3. Service Fait & Réception</h3>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Réf. Service Fait</label><input type="text" name="reference_service_fait" value={formData.reference_service_fait} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Date Service Fait</label><input type="date" name="date_service_fait" value={formData.date_service_fait} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Début Prestations</label><input type="date" name="date_debut_prestations" value={formData.date_debut_prestations} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Fin Prestations</label><input type="date" name="date_fin_prestations" value={formData.date_fin_prestations} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Réf. PV Réception</label><input type="text" name="reference_pv_reception" value={formData.reference_pv_reception} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Date Réception</label><input type="date" name="date_reception" value={formData.date_reception} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Résultat Réception</label>
                    <select name="resultat_reception" value={formData.resultat_reception} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                      <option value="">Sélectionner...</option>
                      <option value="Accepte">Accepté</option>
                      <option value="Avec reserves">Avec réserves</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Type de Réception</label>
                    <select name="type_reception" value={formData.type_reception} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                      <option value="PARTIELLE">Partielle</option>
                      <option value="TOTALE">Totale</option>
                      <option value="DEFINITIVE">Définitive</option>
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Date Réunion Commission</label><input type="date" name="date_reunion_commission" value={formData.date_reunion_commission} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Heure Réunion (ex: 10:00)</label><input type="time" name="heure_reunion_commission" value={formData.heure_reunion_commission} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">N° Décision Commission</label><input type="text" name="num_decision" value={formData.num_decision} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Date Décision</label><input type="date" name="date_decision" value={formData.date_decision} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                </div>
                
                <div className="mt-6 border-t border-slate-100 pt-4">
                  <h4 className="text-sm font-bold text-slate-800 mb-3 flex items-center justify-between">
                    Membres de la Commission (Optionnel : Remplacer ceux du marché)
                    <button type="button" onClick={addCommissionMember} className="text-xs px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg flex items-center gap-1 font-bold">
                      <Plus size={14}/> Ajouter
                    </button>
                  </h4>
                  {formData.commission_reception && formData.commission_reception.length > 0 ? (
                    <div className="space-y-2">
                      {formData.commission_reception.map((membre, idx) => (
                        <div key={idx} className="flex gap-2 items-center bg-slate-50 p-2 rounded-lg border border-slate-200">
                          <input type="text" placeholder="Nom et Prénom" value={membre.nom} onChange={(e) => handleCommissionChange(idx, 'nom', e.target.value)} className="flex-1 px-3 py-1.5 text-sm border border-slate-300 rounded-md" />
                          <input type="text" placeholder="Fonction" value={membre.fonction} onChange={(e) => handleCommissionChange(idx, 'fonction', e.target.value)} className="flex-1 px-3 py-1.5 text-sm border border-slate-300 rounded-md" />
                          <select value={membre.qualite} onChange={(e) => handleCommissionChange(idx, 'qualite', e.target.value)} className="w-32 px-3 py-1.5 text-sm border border-slate-300 rounded-md bg-white">
                            <option value="Présidente">Présidente</option>
                            <option value="Président">Président</option>
                            <option value="Membre">Membre</option>
                          </select>
                          <button type="button" onClick={() => removeCommissionMember(idx)} className="p-1.5 text-red-500 hover:bg-red-50 rounded-md"><Trash2 size={16}/></button>
                        </div>
                      ))}
                    </div>
                  ) : (
                     <div className="text-sm text-slate-500 italic p-3 bg-slate-50 rounded-lg text-center">Aucun membre spécifié. Les membres définis lors de l'engagement du marché ou de l'ouverture des plis (AOO) seront utilisés par défaut.</div>
                  )}
                </div>
              </section>

              {/* Section 4: Facture & Décompte */}
              <section>
                <h3 className="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">4. Facture & Décompte</h3>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">N° Facture</label><input type="text" name="num_facture" value={formData.num_facture} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">Date Facture</label><input type="date" name="date_facture" value={formData.date_facture} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div><label className="block text-xs font-bold text-slate-700 mb-1">N° Décompte</label><input type="text" name="num_decompte" value={formData.num_decompte} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" /></div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Type Décompte</label>
                    <select name="type_decompte" value={formData.type_decompte} onChange={handleInputChange} className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                      <option value="provisoire">Provisoire</option>
                      <option value="definitif">Définitif</option>
                      <option value="situation">Situation</option>
                    </select>
                  </div>
                </div>
              </section>

              {/* Section 5: Synthèse Financière (Auto) */}
              <section className="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                <h3 className="text-lg font-black text-slate-900 mb-6 flex items-center gap-2"><Calculator className="text-indigo-600" size={24}/> Synthèse et Net à Payer</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                  
                  <div className="space-y-4">
                    <h4 className="font-bold text-slate-700 border-b border-slate-200 pb-2">Montants Bruts</h4>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">Total Brut HT</label><input type="number" readOnly value={formData.montant_ht} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg font-semibold text-slate-800" /></div>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">TVA (20%)</label><input type="number" readOnly value={formData.montant_tva} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg font-semibold text-slate-800" /></div>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">Total Brut TTC</label><input type="number" readOnly value={formData.montant_ttc} className="w-full px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg font-black text-indigo-900" /></div>
                  </div>

                  <div className="space-y-4">
                    <h4 className="font-bold text-slate-700 border-b border-slate-200 pb-2">Retenues & Déductions</h4>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">Retenue de Garantie</label><input type="number" step="0.01" name="retenue_garantie" value={formData.retenue_garantie} onChange={handleInputChange} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm" /></div>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">Pénalités</label><input type="number" step="0.01" name="penalites_retard" value={formData.penalites_retard} onChange={handleInputChange} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm" /></div>
                    <div><label className="block text-xs text-slate-500 font-bold mb-1">Avances à recupérer</label><input type="number" step="0.01" name="avances_a_recuperer" value={formData.avances_a_recuperer} onChange={handleInputChange} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm" /></div>
                  </div>

                  <div className="lg:col-span-2 flex flex-col justify-end p-6 bg-indigo-600 rounded-2xl text-white shadow-lg">
                    <p className="text-indigo-200 text-sm font-bold uppercase tracking-wider mb-2">Montant Final</p>
                    <h2 className="text-4xl md:text-5xl font-black mb-2">
                      {new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(
                        (parseFloat(formData.montant_ttc) || 0) - 
                        ((parseFloat(formData.retenue_garantie) || 0) + (parseFloat(formData.penalites_retard) || 0) + (parseFloat(formData.avances_a_recuperer) || 0))
                      )}
                    </h2>
                    <p className="text-indigo-100 text-sm">Le montant Net à Payer sera calculé après déduction des retenues sur le montant brut TTC saisi.</p>
                  </div>

                </div>
              </section>

              <div className="flex justify-end gap-3 pt-6 border-t border-slate-100">
                <button type="button" onClick={() => setActiveTab('historique')} className="px-6 py-3 bg-white border border-slate-300 text-slate-700 rounded-xl font-bold hover:bg-slate-50 transition-colors">Annuler</button>
                <button type="submit" className="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-colors shadow-md flex items-center gap-2">
                  <Save size={20} /> Enregistrer la Liquidation
                </button>
              </div>

            </div>
          </form>
        )}

      </main>
    </div>
  );
};

// SVG Icon Component missing in imports
const Calculator = ({size, className}) => (
  <svg xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="16" x2="16" y1="14" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/></svg>
);

export default DossierLiquidation;
