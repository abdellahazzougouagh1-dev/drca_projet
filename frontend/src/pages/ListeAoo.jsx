import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Search, Plus, Eye, Edit2, Trash2, Calendar, DollarSign, FileText, AlertCircle, Loader2, ArrowLeft } from 'lucide-react';
import TopNavButtons from '../components/TopNavButtons';

const ListeAoo = () => {
  const navigate = useNavigate();
  const [aoos, setAoos] = useState([]);
  const [budgetType, setBudgetType] = useState('Investissement');
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    fetchAoos();
  }, []);


  const fetchAoos = async () => {
    try {
      setLoading(true);
      const response = await api.get('/aoos');
      setAoos(response.data);
      setErrorMessage('');
    } catch (err) {
      setErrorMessage('Erreur lors du chargement des AOO.');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const aoosByBudget = useMemo(() => {
    const groups = { Investissement: [], Fonctionnement: [], 'Non renseigné': [] };
    aoos.forEach((aoo) => {
      const value = String(aoo.type_budget || '').trim().toLowerCase();
      const budget = value === 'investissement' ? 'Investissement'
        : value === 'fonctionnement' ? 'Fonctionnement' : 'Non renseigné';
      groups[budget].push(aoo);
    });
    return groups;
  }, [aoos]);
  const budgetAoos = aoosByBudget[budgetType];
  const filteredAoos = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();
    return budgetAoos.filter((aoo) => !term || [aoo.num_aoo, aoo.objet, aoo.statut, aoo.etat_avancement]
      .some((value) => String(value || '').toLowerCase().includes(term)));
  }, [budgetAoos, searchTerm]);

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet AOO ?')) {
      return;
    }

    try {
      await api.delete(`/aoos/${id}`);
      setSuccessMessage('AOO supprimé avec succès.');
      setAoos(aoos.filter(aoo => aoo.id !== id));
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      setErrorMessage('Erreur lors de la suppression de l\'AOO.');
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
    if (!amount) return '-';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MAD',
    }).format(amount).replace('MAD', 'dh');
  };

  return (
    <div className="min-h-screen bg-[#F5F7F5] p-4 sm:p-6 lg:p-8 text-[#172033]">
      <div className="w-full max-w-[1920px] mx-auto space-y-6">
        {/* Header */}
        <header className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div className="flex items-start sm:items-center gap-3">
            <button
              onClick={() => navigate('/dashboard')}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer shrink-0"
              title="Retour au tableau de bord"
            >
              <ArrowLeft size={16} />
              <span>Retour</span>
            </button>
            <div className="h-6 w-px bg-slate-200 hidden sm:block" />
            <div>
              <div className="flex items-center gap-2 flex-wrap">
                <span className="text-[10px] font-bold text-[#087F5B] bg-[#E7F5EF] px-2 py-0.5 rounded-full uppercase tracking-wider">DRCA · RSK</span>
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Marchés Publics</span>
              </div>
              <h1 className="mt-1 text-2xl sm:text-3xl font-extrabold text-[#172033] tracking-tight">
                Appels d'Offres (AOO)
              </h1>
              <p className="mt-1 text-xs sm:text-sm text-slate-500">
                Gestion et suivi des procédures d'appels d'offres ouverts.
              </p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <TopNavButtons current="dashboard" />
            <button
              onClick={() => navigate('/aoos/nouveau')}
              className="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 bg-[#087F5B] hover:bg-[#06694A] text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-[#087F5B]/20 transition transform active:scale-98 cursor-pointer"
            >
              <Plus size={18} />
              <span>Nouvel AOO</span>
            </button>
          </div>
        </header>

        {/* Messages */}
        {errorMessage && (
          <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700 shadow-xs">
            <AlertCircle size={20} />
            <p className="font-semibold text-sm">{errorMessage}</p>
          </div>
        )}
        {successMessage && (
          <div className="p-4 bg-[#E7F5EF] border border-[#087F5B]/30 rounded-xl flex items-center gap-3 text-[#087F5B] shadow-xs">
            <AlertCircle size={20} />
            <p className="font-semibold text-sm">{successMessage}</p>
          </div>
        )}

        {!loading && (
          <div role="group" aria-label="Type de budget des appels d’offres" className="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-xs">
            {Object.entries(aoosByBudget).filter(([budget, items]) => budget !== 'Non renseigné' || items.length > 0 || budgetType === budget).map(([budget, items]) => (
              <button key={budget} type="button" aria-pressed={budgetType === budget} onClick={() => setBudgetType(budget)}
                className={`inline-flex items-center gap-3 rounded-xl border px-4 py-2 text-sm font-semibold transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#087F5B] ${budgetType === budget ? 'border-[#087F5B] bg-[#E7F5EF] text-[#087F5B] shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'}`}>
                {budget}
                <span className={`min-w-6 rounded-md px-2 py-0.5 text-center text-xs font-bold ${budgetType === budget ? 'bg-white text-[#087F5B]' : 'bg-slate-100 text-slate-600'}`}>{items.length}</span>
              </button>
            ))}
          </div>
        )}

        {/* Controls */}
        <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
          <div className="flex-1 relative w-full">
            <Search size={18} className="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              aria-label="Rechercher un appel d’offres dans le budget sélectionné"
              placeholder="Rechercher par N° AOO, objet, statut..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:border-[#087F5B] focus:ring-2 focus:ring-[#087F5B]/20 bg-white text-sm text-slate-800 shadow-xs"
            />
          </div>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="flex justify-center items-center h-64">
            <Loader2 size={28} className="animate-spin text-[#087F5B]" />
          </div>
        )}

        {/* Table */}
        {!loading && (
          <div className="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200">
            {filteredAoos.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="bg-[#101B2D] border-b-2 border-[#087F5B] text-white">
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">N° AOO</th>
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Objet</th>
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Budget</th>
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Statut</th>
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">État</th>
                      <th className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Ouverture</th>
                      <th className="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredAoos.map((aoo) => (
                      <tr key={aoo.id} className="hover:bg-[#F2FAF6] transition-colors">
                        <td className="px-6 py-4">
                          <span className="font-mono font-bold text-[#101B2D] text-sm">{aoo.num_aoo}</span>
                        </td>
                        <td className="px-6 py-4 max-w-md">
                          <p className="text-sm font-semibold text-slate-800 line-clamp-2">{aoo.objet}</p>
                          <small className="text-xs text-slate-400">{aoo.structure_acheteuse || 'DRCA RSK'}</small>
                        </td>
                        <td className="px-6 py-4 font-mono font-bold text-slate-800 text-sm whitespace-nowrap">
                          {formatMoney(aoo.budget)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-block px-3 py-1 rounded-full text-xs font-bold ${getStatusClass(aoo.statut)}`}>
                            {aoo.statut}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${getAvancementClass(aoo.etat_avancement)}`}>
                            {aoo.etat_avancement || '-'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center gap-1.5 text-slate-600 text-xs">
                            <Calendar size={14} className="text-[#087F5B]" />
                            <span>{formatDate(aoo.date_ouverture)}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-center whitespace-nowrap">
                          <div className="flex gap-1.5 justify-center">
                            <button
                              onClick={() => navigate(`/aoos/${aoo.id}`)}
                              className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-[#E7F5EF] text-[#087F5B] hover:bg-[#d8efe5] transition cursor-pointer"
                              title="Voir"
                            >
                              <Eye size={16} />
                            </button>
                            <button
                              onClick={() => navigate(`/aoos/${aoo.id}`)}
                              className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition cursor-pointer"
                              title="Modifier"
                            >
                              <Edit2 size={16} />
                            </button>
                            <button
                              onClick={() => handleDelete(aoo.id)}
                              className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
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
                <p className="text-slate-500 text-lg">{budgetAoos.length === 0 ? `Aucun appel d’offres dans le budget ${budgetType.toLowerCase()}.` : 'Aucun appel d’offres ne correspond à cette recherche.'}</p>
                {searchTerm && (
                  <p className="text-slate-400 text-sm mt-2">Essayez une autre recherche</p>
                )}
              </div>
            )}
          </div>
        )}

        {/* Summary */}
        {!loading && (
          <div className="mt-6 p-4 bg-slate-100 rounded-lg text-sm text-slate-600">
            Affichage : <strong>{filteredAoos.length}</strong> / <strong>{budgetAoos.length}</strong> AOO · {budgetType}
          </div>
        )}
      </div>
    </div>
  );
};

export default ListeAoo;
