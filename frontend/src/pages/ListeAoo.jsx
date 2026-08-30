import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Search, Plus, Eye, Edit2, Trash2, Calendar, DollarSign, FileText, AlertCircle, Loader2 } from 'lucide-react';

const ListeAoo = () => {
  const navigate = useNavigate();
  const [aoos, setAoos] = useState([]);
  const [filteredAoos, setFilteredAoos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    fetchAoos();
  }, []);

  useEffect(() => {
    filterAoos();
  }, [searchTerm, aoos]);

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

  const filterAoos = () => {
    if (!searchTerm.trim()) {
      setFilteredAoos(aoos);
      return;
    }

    const term = searchTerm.toLowerCase();
    const filtered = aoos.filter(aoo =>
      aoo.num_aoo?.toLowerCase().includes(term) ||
      aoo.objet?.toLowerCase().includes(term) ||
      aoo.statut?.toLowerCase().includes(term) ||
      aoo.etat_avancement?.toLowerCase().includes(term)
    );

    setFilteredAoos(filtered);
  };

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
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-4xl font-extrabold text-slate-800 mb-2">Appels d'Offres</h1>
          <p className="text-slate-600">Gestion et suivi des appels d'offres</p>
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
              placeholder="Rechercher par N° AOO, objet, statut..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-white"
            />
          </div>
          <button
            onClick={() => navigate('/aoos/nouveau')}
            className="px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 flex items-center gap-2 transition-all shadow-md"
          >
            <Plus size={20} />
            Nouvel AOO
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
            {filteredAoos.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-gradient-to-r from-blue-50 to-slate-100 border-b border-slate-200">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">N° AOO</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Objet</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Budget</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Statut</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">État</th>
                      <th className="px-6 py-4 text-left text-sm font-bold text-slate-700">Ouverture</th>
                      <th className="px-6 py-4 text-center text-sm font-bold text-slate-700">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredAoos.map((aoo, idx) => (
                      <tr key={aoo.id} className={idx % 2 === 0 ? 'bg-white' : 'bg-slate-50 hover:bg-slate-100'}>
                        <td className="px-6 py-4">
                          <span className="font-bold text-blue-600">{aoo.num_aoo}</span>
                        </td>
                        <td className="px-6 py-4">
                          <div className="text-sm text-slate-800 line-clamp-2">{aoo.objet}</div>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-1 text-slate-800">
                            <DollarSign size={16} className="text-emerald-600" />
                            <span className="font-semibold">{formatMoney(aoo.budget)}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <span className={`px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap inline-block ${getStatusBadgeColor(aoo.statut)}`}>
                            {aoo.statut?.replace(/_/g, ' ').toUpperCase()}
                          </span>
                        </td>
                        <td className="px-6 py-4">
                          <span className="text-sm text-slate-600">{aoo.etat_avancement}</span>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-1 text-slate-600">
                            <Calendar size={16} className="text-amber-600" />
                            <span className="text-sm">{formatDate(aoo.date_ouverture)}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-center">
                          <div className="flex gap-2 justify-center">
                            <button
                              onClick={() => navigate(`/aoos/${aoo.id}`)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                              title="Voir"
                            >
                              <Eye size={18} />
                            </button>
                            <button
                              onClick={() => navigate(`/aoos/${aoo.id}`)}
                              className="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                              title="Modifier"
                            >
                              <Edit2 size={18} />
                            </button>
                            <button
                              onClick={() => handleDelete(aoo.id)}
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
                <p className="text-slate-500 text-lg">Aucun AOO trouvé</p>
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
            Affichage : <strong>{filteredAoos.length}</strong> / <strong>{aoos.length}</strong> AOO
          </div>
        )}
      </div>
    </div>
  );
};

export default ListeAoo;
