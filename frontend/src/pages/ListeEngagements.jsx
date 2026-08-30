import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Search, Plus, Eye, Edit2, Trash2, Calendar, DollarSign, FileText, AlertCircle, Loader2, ReceiptText } from 'lucide-react';

const ListeEngagements = () => {
  const navigate = useNavigate();
  const [marches, setMarches] = useState([]);
  const [filteredMarches, setFilteredMarches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

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
    if (!amount) return '-';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MAD',
    }).format(amount).replace('MAD', 'dh');
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-4xl font-extrabold text-slate-800 mb-2">Engagements</h1>
          <p className="text-slate-600">Tableau de bord unifié des dossiers d'engagement</p>
        </div>

        {/* Messages */}
        {errorMessage && (
          <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
            <AlertCircle size={20} />
            {errorMessage}
          </div>
        )}
        {successMessage && (
          <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-700">
            {successMessage}
          </div>
        )}

        {/* Controls */}
        <div className="mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
          <div className="flex-1 relative">
            <Search size={20} className="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              placeholder="Rechercher par N° marché, titulaire, objet..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-white"
            />
          </div>
          <button
            onClick={() => navigate('/engagements/nouveau')}
            className="px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 flex items-center gap-2 transition-all shadow-md"
          >
            <Plus size={20} />
            Ajouter un dossier
          </button>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="flex justify-center items-center h-64">
            <Loader2 size={24} className="animate-spin text-blue-600" />
          </div>
        )}

        {/* Table */}
        {!loading && (
          <div className="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-200">
            {filteredMarches.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-gradient-to-r from-blue-50 to-slate-100 border-b border-slate-200">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">N° Marché</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Titulaire</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Objet</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Montant</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Statut</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Agent Suivi</th>
                      <th className="px-6 py-4 text-center text-sm font-bold text-slate-700">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredMarches.map((marche, idx) => (
                      <tr key={marche.id} className={idx % 2 === 0 ? 'bg-white' : 'bg-slate-50 hover:bg-slate-100'}>
                        <td className="px-6 py-4">
                          <span className="font-bold text-blue-600">{marche.num_marche}</span>
                        </td>
                        <td className="px-6 py-4">
                          <div className="text-sm font-medium text-slate-800">{marche.titulaire}</div>
                        </td>
                        <td className="px-6 py-4">
                          <div className="text-sm text-slate-600 line-clamp-2">{marche.objet_marche}</div>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-1 text-slate-800">
                            <span className="font-semibold">{formatMoney(marche.montant)}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <span className={`px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap ${getStatusBadgeColor(marche.statut)}`}>
                            {marche.statut?.replace(/_/g, ' ').toUpperCase()}
                          </span>
                        </td>
                        <td className="px-6 py-4">
                          <span className="text-sm text-slate-600">{marche.agent_suivi || '-'}</span>
                        </td>
                        <td className="px-6 py-4 text-center">
                          <div className="flex gap-2 justify-center">
                            <button
                              onClick={() => navigate(`/engagements/${marche.id}`)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                              title="Voir l'Engagement"
                            >
                              <Eye size={18} />
                            </button>
                            <button
                              onClick={() => navigate(`/liquidations/marches/${marche.id}`)}
                              className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                              title="Passer à la Liquidation"
                            >
                              <ReceiptText size={18} />
                            </button>
                            <button
                              onClick={() => navigate(`/engagements/${marche.id}`)}
                              className="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                              title="Modifier"
                            >
                              <Edit2 size={18} />
                            </button>
                            <button
                              onClick={() => handleDelete(marche.id)}
                              className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all"
                              title="Supprimer"
                            >
                              <Trash2 size={18} />
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
                <p className="text-slate-500 text-lg">Aucun dossier disponible pour l'engagement</p>
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
            Affichage : <strong>{filteredMarches.length}</strong> / <strong>{marches.length}</strong> dossiers
          </div>
        )}
      </div>
    </div>
  );
};

export default ListeEngagements;
