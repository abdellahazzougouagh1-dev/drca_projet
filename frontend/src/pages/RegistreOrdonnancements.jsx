import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { 
  Search, Filter, Plus, Eye, Printer, Trash2, 
  CheckCircle2, Clock, Send, AlertTriangle, ChevronRight,
  WalletCards, Layers, Landmark, ArrowUpDown, Download,
  Building, RefreshCw, Calculator, FileText, Check,
  ChevronDown, Receipt
} from 'lucide-react';
import api from '../api/axios';
import ModalNouveauOrdonnancement from '../components/ordonnancement/ModalNouveauOrdonnancement';
import DocumentPreviewModal from '../components/ordonnancement/DocumentPreviewModal';

export default function RegistreOrdonnancements() {
  const navigate = useNavigate();
  const [ordonnancements, setOrdonnancements] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  // Filters & Search
  const [exercice, setExercice] = useState('2026');
  const [searchTerm, setSearchTerm] = useState('');
  const [statutFilter, setStatutFilter] = useState('TOUS');
  const [procedureFilter, setProcedureFilter] = useState('TOUS');
  const [showFilters, setShowFilters] = useState(false);

  // Modals
  const [modalCreateOpen, setModalCreateOpen] = useState(false);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [selectedOrdonnancement, setSelectedOrdonnancement] = useState(null);

  useEffect(() => {
    fetchRegistre();
  }, [exercice, statutFilter, procedureFilter]);

  const fetchRegistre = async () => {
    try {
      setLoading(true);
      setError('');
      const params = {};
      if (exercice && exercice !== 'TOUS') params.exercice = exercice;
      if (statutFilter && statutFilter !== 'TOUS') params.statut = statutFilter;
      if (procedureFilter && procedureFilter !== 'TOUS') params.type_procedure = procedureFilter;
      if (searchTerm) params.search = searchTerm;

      const res = await api.get('/ordonnancements', { params });
      setOrdonnancements(res.data.data || []);
    } catch (err) {
      console.error('Erreur chargement registre ordonnancements:', err);
      setError('Erreur lors du chargement du registre des ordonnancements.');
    } finally {
      setLoading(false);
    }
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    fetchRegistre();
  };

  const handleDelete = async (id, numOrd) => {
    if (!window.confirm(`Confirmez-vous la suppression de l'ordonnancement ${numOrd} ?`)) return;
    try {
      await api.delete(`/ordonnancements/${id}`);
      fetchRegistre();
    } catch (err) {
      console.error('Erreur suppression:', err);
      alert('Erreur lors de la suppression de l\'ordonnancement.');
    }
  };

  const formatDH = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0) + ' DH';

  const formatNumber = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0);

  const getStatusBadge = (statut) => {
    switch (statut) {
      case 'Payé':
        return (
          <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <CheckCircle2 size={13} className="text-emerald-600" /> Payé
          </span>
        );
      case 'Transmis au trésorier':
        return (
          <span className="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <Send size={12} className="text-blue-600" /> Transmis
          </span>
        );
      case 'Totalement ordonnancée':
      case 'Totalement ordonnancé':
      case 'Ordonnancé':
        return (
          <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <Check size={13} className="text-emerald-600" /> Totalement ordonnancé
          </span>
        );
      case 'Partiellement ordonnancée':
      case 'Partiellement ordonnancé':
        return (
          <span className="px-2.5 py-1 bg-sky-50 text-sky-700 border border-sky-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <Clock size={13} className="text-sky-600" /> Partiellement ordonnancé
          </span>
        );
      case 'Non ordonnancée':
      case 'Non ordonnancé':
        return (
          <span className="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <Clock size={13} className="text-amber-600" /> Non ordonnancé
          </span>
        );
      case 'Rejeté':
      case 'Annulé':
        return (
          <span className="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <AlertTriangle size={13} className="text-rose-600" /> {statut}
          </span>
        );
      case 'À payer':
      case 'À vérifier':
      default:
        return (
          <span className="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-2xs">
            <Clock size={13} className="text-amber-600" /> {statut || 'À payer'}
          </span>
        );
    }
  };

  // Helper to extract clean integer number for N° OP (e.g. 1, 2, 38, 39, etc.)
  const extractNumOp = (val, fallback) => {
    if (val === null || val === undefined || val === '') return String(fallback || 1);
    const str = String(val).trim();
    const digits = str.replace(/\D+/g, '');
    if (digits) {
      const num = parseInt(digits, 10);
      return isNaN(num) ? str : String(num);
    }
    return str || String(fallback || 1);
  };

  // Flatten ordonnancements into individual payment order rows for Moroccan DRCA layout
  const rows = [];
  let seqIdx = 1;

  ordonnancements.forEach((ord) => {
    const ordresList = ord.ordres || [];

    if (ordresList.length > 0) {
      ordresList.forEach((o, oIdx) => {
        const creanceRaw = o.creance || (
          o.type_mouvement?.includes('TVA') ? 'Retenue à la source' :
          (o.type_mouvement?.includes('IS') || o.type_mouvement?.includes('IAS')) ? 'Retenue à la source' :
          o.type_mouvement?.includes('Reports') ? 'Reports' :
          o.type_mouvement?.includes('Consolid') ? 'Crédit Consolidés' :
          o.type_mouvement?.includes('Neufs') ? 'C.Neufs' : 'Reste à payer'
        );

        const montant = Number(o.montant || 0);

        const isReports = creanceRaw === 'Reports';
        const isCreditConsolides = creanceRaw === 'Crédit Consolidés';
        const isCNeufs = creanceRaw === 'C.Neufs';
        const isRas = creanceRaw === 'Retenue à la source';
        const isRap = creanceRaw === 'Reste à payer' || (!isReports && !isCreditConsolides && !isCNeufs && !isRas);

        const currentRowIndex = seqIdx++;

        rows.push({
          id: ord.id,
          ordre_id: o.id,
          index: currentRowIndex,
          raw: ord,
          rawOrdre: o,
          num_op: extractNumOp(o.num_ordre || ord.num_op, currentRowIndex),
          date_op: o.date_ordre ? new Date(o.date_ordre).toLocaleDateString('fr-FR') : (ord.date_ordonnancement ? new Date(ord.date_ordonnancement).toLocaleDateString('fr-FR') : '-'),
          beneficiaire: o.beneficiaire || ord.beneficiaire_nom || ord.fournisseur?.raison_sociale || 'Bénéficiaire',
          reference: ord.reference || '-',
          budget: ord.budget_type || 'Investissement',
          creance: creanceRaw,
          code: ord.code_imputation || '225320',
          art: ord.article || '415',
          par: ord.paragraphe || '20',
          lig: ord.ligne || '13',
          slig: ord.sous_ligne || '0',
          intitule: ord.intitule_depense || ord.liquidation?.objet_liquidation || '-',
          montant_reports: isReports ? montant : 0,
          montant_credit_consolides: isCreditConsolides ? montant : 0,
          montant_c_neufs: isCNeufs ? montant : 0,
          montant_ras: isRas ? montant : 0,
          montant_rap: isRap ? montant : 0,
          montant_total: montant,
          statut: o.statut || ord.statut || 'À payer',
        });
      });
    } else {
      const brut = Number(ord.montant_brut || ord.net_a_payer || 0);
      const ras = Number(ord.ras_total || ord.retenue_tva || 0);
      const net = Number(ord.net_a_payer || (brut - ras));

      const creanceRaw = ord.creance || 'Reste à payer';
      const isReports = creanceRaw === 'Reports';
      const isCreditConsolides = creanceRaw === 'Crédit Consolidés';
      const isCNeufs = creanceRaw === 'C.Neufs';
      const isRas = creanceRaw === 'Retenue à la source';
      const isRap = creanceRaw === 'Reste à payer';

      const currentRowIndex = seqIdx++;

      rows.push({
        id: ord.id,
        ordre_id: null,
        index: currentRowIndex,
        raw: ord,
        rawOrdre: null,
        num_op: extractNumOp(ord.num_op || ord.num_ordonnancement, currentRowIndex),
        date_op: ord.date_ordonnancement ? new Date(ord.date_ordonnancement).toLocaleDateString('fr-FR') : '-',
        beneficiaire: ord.beneficiaire_nom || ord.fournisseur?.raison_sociale || 'Bénéficiaire',
        reference: ord.reference || '-',
        budget: ord.budget_type || 'Investissement',
        creance: creanceRaw,
        code: ord.code_imputation || '225320',
        art: ord.article || '415',
        par: ord.paragraphe || '20',
        lig: ord.ligne || '13',
        slig: ord.sous_ligne || '0',
        intitule: ord.intitule_depense || ord.liquidation?.objet_liquidation || '-',
        montant_reports: isReports ? brut : Number(ord.paiement_reports || 0),
        montant_credit_consolides: isCreditConsolides ? brut : Number(ord.credit_consolide || 0),
        montant_c_neufs: isCNeufs ? brut : Number(ord.credit_neuf || 0),
        montant_ras: isRas ? brut : ras,
        montant_rap: isRap ? net : (brut - ras),
        montant_total: brut || net,
        statut: ord.statut || 'À payer',
      });
    }
  });

  // Client-side search filtering
  const filteredRows = rows.filter(r => {
    if (!searchTerm) return true;
    const term = searchTerm.toLowerCase();
    return (
      r.num_op?.toLowerCase().includes(term) ||
      r.beneficiaire?.toLowerCase().includes(term) ||
      r.reference?.toLowerCase().includes(term) ||
      r.intitule?.toLowerCase().includes(term) ||
      r.creance?.toLowerCase().includes(term) ||
      r.code?.toLowerCase().includes(term) ||
      r.statut?.toLowerCase().includes(term)
    );
  });

  // Global KPI Calculations
  const totalReportsGlobal = filteredRows.reduce((sum, r) => sum + r.montant_reports, 0);
  const totalCreditConsolidesGlobal = filteredRows.reduce((sum, r) => sum + r.montant_credit_consolides, 0);
  const totalCNeufsGlobal = filteredRows.reduce((sum, r) => sum + r.montant_c_neufs, 0);
  const totalRasGlobal = filteredRows.reduce((sum, r) => sum + r.montant_ras, 0);
  const totalRapGlobal = filteredRows.reduce((sum, r) => sum + r.montant_rap, 0);
  const totalOrdonneGlobal = totalReportsGlobal + totalCreditConsolidesGlobal + totalCNeufsGlobal + totalRasGlobal + totalRapGlobal;

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),linear-gradient(135deg,_#f8fafc_0%,_#eef6ff_100%)] font-sans flex flex-col p-6 sm:p-8 pb-24">
      <main className="flex-1 w-full max-w-[99%] mx-auto space-y-6">
        
        {/* Back Link */}
        <div>
          <Link to="/dashboard" className="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline transition">
            ← Retour au tableau de bord
          </Link>
        </div>

        {/* Page Title & Main Action */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-3.5">
            <div className="p-3 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100 shadow-2xs">
              <WalletCards size={32} />
            </div>
            <div>
              <div className="flex items-center gap-3">
                <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">
                  Registre des ordonnancements
                </h1>
                <span className="px-2.5 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                  {filteredRows.length} ligne(s)
                </span>
              </div>
              <p className="text-slate-500 text-sm mt-0.5 font-medium">
                Suivi officiel des ordres de paiement (OP), imputations et états des créances.
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={() => setModalCreateOpen(true)}
              className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition transform active:scale-98 cursor-pointer"
            >
              <Plus size={18} />
              + Nouvel ordonnancement
            </button>
          </div>
        </div>



        {/* Filter & Search Bar */}
        <div className="bg-white p-4 rounded-3xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-4 items-center justify-between">
          
          <div className="relative w-full md:w-96">
            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Rechercher par N° OP, Référence, Bénéficiaire, Créance..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 hover:border-slate-300 transition-all duration-300 text-sm font-medium text-slate-800 placeholder-slate-400"
            />
          </div>

          <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
            
            {/* Quick Status Chips */}
            <div className="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 text-xs font-semibold">
              {['TOUS', 'À payer', 'Transmis au trésorier', 'Payé', 'Rejeté'].map(st => (
                <button
                  key={st}
                  onClick={() => setStatutFilter(st)}
                  className={`px-3 py-1.5 rounded-lg transition ${
                    statutFilter === st 
                      ? 'bg-white text-blue-700 font-bold shadow-xs' 
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  {st === 'TOUS' ? 'Tous' : st}
                </button>
              ))}
            </div>

            {/* Exercice */}
            <div className="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700">
              <span className="text-slate-500">Exercice :</span>
              <select
                value={exercice}
                onChange={(e) => setExercice(e.target.value)}
                className="bg-transparent border-none text-slate-900 font-bold focus:outline-none cursor-pointer"
              >
                <option value="2026">2026</option>
                <option value="2025">2025</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
                <option value="TOUS">Tous</option>
              </select>
            </div>

            {/* More Filters Toggle */}
            <button
              onClick={() => setShowFilters(!showFilters)}
              className={`inline-flex items-center gap-1.5 px-3 py-2 border rounded-xl text-xs font-semibold transition ${
                showFilters 
                  ? 'bg-blue-50 border-blue-300 text-blue-700' 
                  : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
              }`}
            >
              <Filter size={15} />
              <span>Plus de filtres</span>
            </button>
          </div>
        </div>

        {/* Extended Filter Bar */}
        {showFilters && (
          <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs animate-in fade-in duration-150">
            <div>
              <label className="block text-slate-600 font-bold mb-1">Procédure</label>
              <select
                value={procedureFilter}
                onChange={(e) => setProcedureFilter(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="TOUS">Toutes les procédures</option>
                <option value="Marché">Marché</option>
                <option value="Bon de commande">Bon de commande</option>
                <option value="Convention">Convention</option>
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={() => {
                  setStatutFilter('TOUS');
                  setProcedureFilter('TOUS');
                  setSearchTerm('');
                }}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition"
              >
                Réinitialiser les filtres
              </button>
            </div>
          </div>
        )}

        {/* REGISTRY TABLE - Official Moroccan DRCA Columns */}
        <div className="bg-white rounded-3xl border border-slate-300 shadow-sm overflow-hidden flex flex-col">
          <div className="overflow-x-auto min-h-[420px]">
            <table className="w-full text-left border-collapse text-xs md:text-sm">
              
              {/* Header - Styled in Institutional Green */}
              <thead className="bg-emerald-700 text-white font-extrabold uppercase tracking-wider border-b-2 border-emerald-800 select-none text-[11px] md:text-xs text-center shadow-xs">
                <tr>
                  <th className="px-3 py-3.5 border-r border-emerald-600/70 w-10">N°</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 min-w-[100px]">N° OP</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 min-w-[95px]">Date OP</th>
                  <th className="px-4 py-3.5 border-r border-emerald-600/70 text-left min-w-[180px]">Bénéficiaires</th>
                  <th className="px-4 py-3.5 border-r border-emerald-600/70 text-left min-w-[160px]">Référence</th>
                  <th className="px-3 py-3.5 border-r border-emerald-600/70 min-w-[95px]">Budget</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 min-w-[130px]">Créance</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 min-w-[70px]">Code</th>
                  <th className="px-2.5 py-3.5 border-r border-emerald-600/70 min-w-[50px]">ART</th>
                  <th className="px-2.5 py-3.5 border-r border-emerald-600/70 min-w-[50px]">PAR</th>
                  <th className="px-2.5 py-3.5 border-r border-emerald-600/70 min-w-[50px]">LIG</th>
                  <th className="px-2.5 py-3.5 border-r border-emerald-600/70 min-w-[55px]">S.LIG</th>
                  <th className="px-4 py-3.5 border-r border-emerald-600/70 text-left min-w-[200px]">Intitulé</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 text-right min-w-[130px]">Paiement Reports</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 text-right min-w-[145px]">Paiement Crédit Consolidés</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 text-right min-w-[130px]">Paiement C.Neufs</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 text-right min-w-[150px]">Paiement Retenue à la source</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 text-right min-w-[145px]">Paiement Reste à payer</th>
                  <th className="px-3.5 py-3.5 border-r border-emerald-600/70 min-w-[125px]">Payé/Rejet</th>
                    <th className="px-3 py-3.5 w-20">Actions</th>
                  </tr>
                </thead>

              {/* Body */}
              <tbody className="divide-y divide-slate-200 text-slate-800 text-xs md:text-sm">
                {filteredRows.map((row) => (
                  <tr 
                    key={`${row.id}-${row.ordre_id || 'main'}`} 
                    className="hover:bg-blue-50/50 even:bg-slate-50/50 transition-colors"
                  >
                    <td className="px-3 py-3.5 text-center font-bold text-slate-500 border-r border-slate-200">
                      {row.index}
                    </td>
                    <td className="px-3.5 py-3.5 text-center font-mono font-bold text-blue-900 border-r border-slate-200 whitespace-nowrap">
                      <Link 
                        to={`/ordonnancements/${row.id}`}
                        className="text-blue-700 hover:text-blue-900 hover:underline font-extrabold"
                      >
                        {row.num_op}
                      </Link>
                    </td>
                    <td className="px-3.5 py-3.5 text-center text-slate-700 whitespace-nowrap border-r border-slate-200 font-medium">
                      {row.date_op}
                    </td>
                    <td className="px-4 py-3.5 font-bold text-slate-900 border-r border-slate-200">
                      <Link 
                        to={`/ordonnancements/${row.id}`}
                        className="hover:text-blue-600 hover:underline transition block"
                      >
                        {row.beneficiaire}
                      </Link>
                    </td>
                    <td className="px-4 py-3.5 text-slate-800 font-medium border-r border-slate-200">
                      {row.reference}
                    </td>
                    <td className="px-3 py-3.5 text-center text-slate-700 border-r border-slate-200">
                      <span className="px-2 py-0.5 bg-slate-100 text-slate-800 rounded font-semibold text-[11px]">
                        {row.budget}
                      </span>
                    </td>
                    <td className="px-3.5 py-3.5 text-center border-r border-slate-200">
                      <span className={`px-2 py-0.5 rounded text-[11px] font-bold ${
                        row.creance === 'Retenue à la source' ? 'bg-amber-100 text-amber-900' :
                        row.creance === 'Reports' ? 'bg-purple-100 text-purple-900' :
                        row.creance === 'Crédit Consolidés' ? 'bg-indigo-100 text-indigo-900' :
                        row.creance === 'C.Neufs' ? 'bg-cyan-100 text-cyan-900' : 'bg-blue-100 text-blue-900'
                      }`}>
                        {row.creance}
                      </span>
                    </td>
                    <td className="px-3 py-3.5 text-center font-mono text-slate-600 border-r border-slate-200 text-xs font-semibold">
                      {row.code}
                    </td>
                    <td className="px-2.5 py-3.5 text-center font-mono text-slate-700 border-r border-slate-200 font-bold">
                      {row.art}
                    </td>
                    <td className="px-2.5 py-3.5 text-center font-mono text-slate-700 border-r border-slate-200 font-bold">
                      {row.par}
                    </td>
                    <td className="px-2.5 py-3.5 text-center font-mono text-slate-700 border-r border-slate-200 font-bold">
                      {row.lig}
                    </td>
                    <td className="px-2.5 py-3.5 text-center font-mono text-slate-700 border-r border-slate-200 font-bold">
                      {row.slig}
                    </td>
                    <td className="px-4 py-3.5 text-slate-800 border-r border-slate-200 max-w-xs truncate" title={row.intitule}>
                      {row.intitule}
                    </td>
                    
                    {/* 5 Financial Payment Columns */}
                    <td className="px-3.5 py-3.5 text-right font-mono font-bold text-slate-800 border-r border-slate-200">
                      {row.montant_reports > 0 ? formatNumber(row.montant_reports) : '-'}
                    </td>
                    <td className="px-3.5 py-3.5 text-right font-mono font-bold text-indigo-800 border-r border-slate-200">
                      {row.montant_credit_consolides > 0 ? formatNumber(row.montant_credit_consolides) : '-'}
                    </td>
                    <td className="px-3.5 py-3.5 text-right font-mono font-bold text-cyan-800 border-r border-slate-200">
                      {row.montant_c_neufs > 0 ? formatNumber(row.montant_c_neufs) : '-'}
                    </td>
                    <td className="px-3.5 py-3.5 text-right font-mono font-bold text-amber-800 border-r border-slate-200">
                      {row.montant_ras > 0 ? formatNumber(row.montant_ras) : '-'}
                    </td>
                    <td className="px-3.5 py-3.5 text-right font-mono font-black text-blue-900 border-r border-slate-200">
                      {row.montant_rap > 0 ? formatNumber(row.montant_rap) : '-'}
                    </td>
                    
                    {/* Payé / Rejet */}
                    <td className="px-3.5 py-3.5 text-center whitespace-nowrap border-r border-slate-200">
                      {getStatusBadge(row.statut)}
                    </td>

                    {/* Actions */}
                    <td className="px-3 py-3.5 text-center">
                      <div className="flex items-center justify-center gap-1.5">
                        <Link
                          to={`/ordonnancements/${row.id}`}
                          className="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                          title="Consulter le dossier d'ordonnancement"
                        >
                          <Eye size={16} />
                        </Link>
                        <button
                          onClick={() => {
                            setSelectedOrdonnancement(row.raw);
                            setPreviewOpen(true);
                          }}
                          className="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                          title="Aperçu / Impression"
                        >
                          <Printer size={16} />
                        </button>
                        <button
                          onClick={() => handleDelete(row.id, row.num_op)}
                          className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                          title="Supprimer"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}

                {filteredRows.length === 0 && !loading && (
                  <tr>
                    <td colSpan={20} className="px-6 py-20 text-center text-slate-400 italic">
                      <div className="max-w-sm mx-auto space-y-2">
                        <FileText size={36} className="mx-auto text-slate-300" />
                        <p className="font-semibold text-slate-600 text-sm">Aucun ordonnancement dans cette vue</p>
                        <p className="text-xs text-slate-400">Cliquez sur « + Nouvel ordonnancement » pour ordonnancer une liquidation disponible.</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>

            </table>
          </div>

          {/* Table Footer Pagination Info */}
          <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-500">
            <div>
              Affichage de <strong className="text-slate-800">{filteredRows.length}</strong> ligne(s) d'ordonnancement
            </div>

            <div className="flex items-center gap-1 font-semibold">
              <button className="px-2.5 py-1 border border-slate-200 rounded-lg bg-white text-slate-400 disabled:opacity-40" disabled>
                «
              </button>
              <button className="px-2.5 py-1 border border-slate-200 rounded-lg bg-white text-slate-400 disabled:opacity-40" disabled>
                ‹
              </button>
              <span className="px-3 py-1 bg-blue-600 text-white rounded-lg font-bold">
                1
              </span>
              <button className="px-2.5 py-1 border border-slate-200 rounded-lg bg-white text-slate-400 disabled:opacity-40" disabled>
                ›
              </button>
              <button className="px-2.5 py-1 border border-slate-200 rounded-lg bg-white text-slate-400 disabled:opacity-40" disabled>
                »
              </button>
            </div>
          </div>
        </div>

      </main>

      {/* Creation Modal */}
      <ModalNouveauOrdonnancement
        isOpen={modalCreateOpen}
        onClose={() => setModalCreateOpen(false)}
        onSuccess={(newOrd) => {
          fetchRegistre();
          navigate(`/ordonnancements/${newOrd.id}`);
        }}
      />

      {/* Document Preview Modal */}
      {selectedOrdonnancement && (
        <DocumentPreviewModal
          isOpen={previewOpen}
          onClose={() => {
            setPreviewOpen(false);
            setSelectedOrdonnancement(null);
          }}
          ordonnancement={selectedOrdonnancement}
          docType="op"
        />
      )}

    </div>
  );
}
