import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  Search, Plus, Eye, Edit2, Trash2, Calendar, DollarSign, FileText,
  AlertCircle, Loader2, ReceiptText, ArrowLeft, FileSpreadsheet, CheckSquare
} from 'lucide-react';

const ListeEngagements = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('dossiers');
  const [marches, setMarches] = useState([]);
  const [filteredMarches, setFilteredMarches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [registreBudget, setRegistreBudget] = useState('Investissement');

  useEffect(() => {
    fetchMarches();
  }, []);

  useEffect(() => {
    filterMarches();
  }, [searchTerm, marches]);

  const fetchMarches = async () => {
    try {
      setLoading(true);
      const response = await api.get('/marches');
      setMarches(response.data);
      setErrorMessage('');
    } catch (err) {
      setErrorMessage('Erreur lors du chargement des marchés.');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const filterMarches = () => {
    if (!searchTerm.trim()) {
      setFilteredMarches(marches);
      return;
    }

    const term = searchTerm.toLowerCase();
    const filtered = marches.filter(marche =>
      marche.num_marche?.toLowerCase().includes(term) ||
      marche.titulaire?.toLowerCase().includes(term) ||
      marche.objet_marche?.toLowerCase().includes(term) ||
      marche.statut?.toLowerCase().includes(term)
    );

    setFilteredMarches(filtered);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce marché ?')) {
      return;
    }

    try {
      await api.delete(`/marches/${id}`);
      setSuccessMessage('Marché supprimé avec succès.');
      setMarches(marches.filter(marche => marche.id !== id));
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      setErrorMessage('Erreur lors de la suppression du marché.');
      console.error(err);
    }
  };

  const getStatusBadgeColor = (status) => {
    if (!status) return 'bg-slate-100 text-slate-800 border border-slate-200';
    const s = String(status).toLowerCase();
    
    if (s.includes('validee') || s.includes('clot')) {
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    }
    if (s.includes('cours') || s.includes('creation') || s.includes('approbation')) {
      return 'bg-amber-100 text-amber-800 border border-amber-200';
    }
    if (s.includes('attribue') || s.includes('execution') || s.includes('reception')) {
      return 'bg-blue-100 text-blue-800 border border-blue-200';
    }
    
    return 'bg-slate-100 text-slate-800 border border-slate-200';
  };

  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
  };

  const formatMoney = (amount) => {
    if (!amount && amount !== 0) return '-';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MAD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount).replace('MAD', 'DH');
  };

  const registreRows = marches
    .filter((m) => {
      const bType = m.type_budget || m.registre_engagement?.budget || 'Investissement';
      return bType === registreBudget;
    })
    .filter((m) => {
      if (!searchTerm.trim()) return true;
      const term = searchTerm.toLowerCase();
      return (
        (m.num_marche || '').toLowerCase().includes(term) ||
        (m.titulaire || '').toLowerCase().includes(term) ||
        (m.num_engagement || '').toLowerCase().includes(term)
      );
    });

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate('/dashboard')}
              className="p-2.5 bg-white hover:bg-slate-100 text-slate-600 rounded-2xl shadow-sm border border-slate-200 transition-all"
            >
              <ArrowLeft size={20} />
            </button>
            <div>
              <h1 className="text-3xl font-extrabold text-slate-800 tracking-tight flex items-center gap-3">
                <CheckSquare className="text-blue-600" /> Phase d'Engagement
              </h1>
              <p className="text-slate-500 text-sm mt-1">Tableau de bord unifié des fiches et registres d'engagement (Module 3)</p>
            </div>
          </div>
          <button
            onClick={() => navigate('/engagements/nouveau')}
            className="px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 flex items-center gap-2 transition-all shadow-md self-start sm:self-auto"
          >
            <Plus size={20} />
            Nouveau dossier
          </button>
        </div>

        {/* TABS NAVIGATION */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
          <button
            onClick={() => setActiveTab('dossiers')}
            className={`py-4 px-6 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-3 ${
              activeTab === 'dossiers'
                ? 'bg-blue-600 text-white shadow-lg'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            <FileText size={20} /> 1. Dossiers d'Engagement ({filteredMarches.length})
          </button>
          <button
            onClick={() => setActiveTab('registre')}
            className={`py-4 px-6 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-3 ${
              activeTab === 'registre'
                ? 'bg-blue-600 text-white shadow-lg'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            <FileSpreadsheet size={20} /> 2. Registre d'Engagement ({registreBudget})
          </button>
        </div>

        {/* Messages */}
        {errorMessage && (
          <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700 font-medium">
            <AlertCircle size={20} />
            {errorMessage}
          </div>
        )}
        {successMessage && (
          <div className="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700 font-medium">
            <AlertCircle size={20} />
            {successMessage}
          </div>
        )}

        {/* TAB 1 : LISTE DES DOSSIERS */}
        {activeTab === 'dossiers' && (
          <>
            {/* Search Controls */}
            <div className="mb-6 flex items-center gap-4">
              <div className="flex-1 relative">
                <Search size={20} className="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  placeholder="Rechercher par N° marché, titulaire, objet..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-white shadow-sm"
                />
              </div>
            </div>

            {loading && (
              <div className="flex justify-center items-center h-64 bg-white rounded-2xl border border-slate-200">
                <Loader2 size={32} className="animate-spin text-blue-600" />
              </div>
            )}

            {!loading && (
              <div className="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200">
                {filteredMarches.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left">
                      <thead className="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase">
                        <tr>
                          <th className="px-6 py-4">N° Marché</th>
                          <th className="px-6 py-4">Titulaire</th>
                          <th className="px-6 py-4">Objet</th>
                          <th className="px-6 py-4">Montant TTC</th>
                          <th className="px-6 py-4">Statut</th>
                          <th className="px-6 py-4">Agent Suivi</th>
                          <th className="px-6 py-4 text-center">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 text-sm">
                        {filteredMarches.map((marche, idx) => (
                          <tr key={marche.id} className="hover:bg-blue-50/40 transition-colors">
                            <td className="px-6 py-4 font-mono font-bold text-blue-700">
                              {marche.num_marche}
                            </td>
                            <td className="px-6 py-4 font-medium text-slate-800">
                              {marche.titulaire}
                            </td>
                            <td className="px-6 py-4 text-slate-600 max-w-xs truncate">
                              {marche.objet_marche}
                            </td>
                            <td className="px-6 py-4 font-bold text-slate-900">
                              {formatMoney(marche.montant)}
                            </td>
                            <td className="px-6 py-4">
                              <span className={`px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap ${getStatusBadgeColor(marche.statut)}`}>
                                {marche.statut?.replace(/_/g, ' ').toUpperCase()}
                              </span>
                            </td>
                            <td className="px-6 py-4 text-slate-500">
                              {marche.agent_suivi || '-'}
                            </td>
                            <td className="px-6 py-4 text-center">
                              <div className="flex gap-1.5 justify-center">
                                <button
                                  onClick={() => navigate(`/engagements/${marche.id}`)}
                                  className="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition-all font-bold text-xs flex items-center gap-1 border border-blue-200"
                                  title="Traiter l'Engagement"
                                >
                                  <Eye size={16} /> Traiter
                                </button>
                                <button
                                  onClick={() => navigate(`/liquidations/marches/${marche.id}`)}
                                  className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all"
                                  title="Passer à la Liquidation"
                                >
                                  <ReceiptText size={16} />
                                </button>
                                <button
                                  onClick={() => handleDelete(marche.id)}
                                  className="p-2 text-red-600 hover:bg-red-50 rounded-xl transition-all"
                                  title="Supprimer"
                                >
                                  <Trash2 size={16} />
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="p-12 text-center">
                    <FileText size={48} className="mx-auto text-slate-300 mb-4" />
                    <p className="text-slate-500 text-lg font-medium">Aucun dossier disponible pour l'engagement</p>
                  </div>
                )}
              </div>
            )}
          </>
        )}

        {/* TAB 2 : REGISTRE D'ENGAGEMENT GLOBAL */}
        {activeTab === 'registre' && (
          <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-6">
              <div>
                <h2 className="text-xl font-extrabold text-slate-800 flex items-center gap-2">
                  <FileSpreadsheet className="text-blue-700" /> Registre d'Engagement Général
                </h2>
                <p className="text-slate-500 text-xs mt-1">Registre consolidé des fiches d'engagement pour les marchés et consultations.</p>
              </div>
              <div className="flex flex-wrap gap-2">
                {['Investissement', 'Fonctionnement'].map((budgetType) => (
                  <button
                    key={budgetType}
                    type="button"
                    onClick={() => setRegistreBudget(budgetType)}
                    className={`rounded-xl px-5 py-2.5 text-sm font-bold transition-colors ${
                      registreBudget === budgetType
                        ? 'bg-blue-700 text-white shadow-sm'
                        : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-blue-700'
                    }`}
                  >
                    Registre {budgetType}
                  </button>
                ))}
              </div>
            </div>

            {registreRows.length === 0 ? (
              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-12 text-center text-slate-500 font-medium">
                Aucune ligne enregistrée dans le registre {registreBudget.toLowerCase()}.
              </div>
            ) : (
              <div className="overflow-x-auto rounded-2xl border border-slate-200">
                <table className="min-w-[1500px] w-full border-collapse text-xs text-left">
                  <thead className="bg-slate-100 font-extrabold text-slate-700">
                    <tr>
                      <th className="px-4 py-3">N° ordre</th>
                      <th className="px-4 py-3">N° fiche</th>
                      <th className="px-4 py-3">Date</th>
                      <th className="px-4 py-3">Référence marché</th>
                      <th className="px-4 py-3">Budget</th>
                      <th className="px-4 py-3">ART</th>
                      <th className="px-4 py-3">PAR</th>
                      <th className="px-4 py-3">LIG</th>
                      <th className="px-4 py-3 text-right">Crédit ouvert CP</th>
                      <th className="px-4 py-3 text-right">Dépense neuve</th>
                      <th className="px-4 py-3 text-right">Intérêts 1%</th>
                      <th className="px-4 py-3 text-right">À engager neuf</th>
                      <th className="px-4 py-3">Bénéficiaire</th>
                      <th className="px-4 py-3 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {registreRows.map((m, idx) => {
                      const r = m.registre_engagement || {};
                      const depenseNeuf = m.montant_depense_neuf || m.montant;
                      const interets = m.interets_moratoires || (depenseNeuf ? (Number(depenseNeuf) * 0.01).toFixed(2) : 0);
                      const aEngager = m.engagement_propose_cp || (depenseNeuf ? (Number(depenseNeuf) * 1.01).toFixed(2) : 0);

                      return (
                        <tr key={m.id} className="hover:bg-blue-50/40">
                          <td className="px-4 py-3">{r.numero_ordre || idx + 1}</td>
                          <td className="px-4 py-3 font-bold text-blue-700">{m.num_engagement || r.numero_rubrique || '-'}</td>
                          <td className="px-4 py-3">{formatDate(m.date_engagement || r.date_engagement)}</td>
                          <td className="px-4 py-3 font-bold">{m.num_marche || r.reference || '-'}</td>
                          <td className="px-4 py-3"><span className="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-bold">{m.type_budget || registreBudget}</span></td>
                          <td className="px-4 py-3">{m.article_budget || r.art || '415'}</td>
                          <td className="px-4 py-3">{m.paragraphe_budget || r.par || '20'}</td>
                          <td className="px-4 py-3">{m.ligne_budget || r.lig || '16'}</td>
                          <td className="px-4 py-3 text-right font-medium">{formatMoney(m.credit_budget_cp || r.credit_ouvert_cp)}</td>
                          <td className="px-4 py-3 text-right font-medium">{formatMoney(depenseNeuf)}</td>
                          <td className="px-4 py-3 text-right font-medium text-amber-700">{formatMoney(interets)}</td>
                          <td className="px-4 py-3 text-right font-bold text-blue-700">{formatMoney(aEngager)}</td>
                          <td className="px-4 py-3 font-medium">{m.titulaire || r.beneficiaire || '-'}</td>
                          <td className="px-4 py-3 text-center">
                            <button
                              onClick={() => navigate(`/engagements/${m.id}`)}
                              className="px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-bold text-[11px]"
                            >
                              Ouvrir
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default ListeEngagements;
