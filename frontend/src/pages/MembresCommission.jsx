import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, Search, Edit, Trash2, X, AlertCircle, UserCircle, Home, Users, FileText, Briefcase, ClipboardList, Archive } from 'lucide-react';

const MembresCommission = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [membres, setMembres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchFilter, setSearchFilter] = useState('nom_prenom');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState(null);

  const initialFormState = { nom_prenom: '', fonction: '' };
  const [formData, setFormData] = useState(initialFormState);

  const fetchMembres = async () => {
    try {
      setLoading(true);
      const params = {};
      if (searchTerm) {
        params[searchFilter] = searchTerm;
      }
      const res = await api.get('/commission-membres', { params });
      setMembres(res.data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      fetchMembres();
    }, 400);
    return () => clearTimeout(delayDebounceFn);
  }, [searchTerm, searchFilter]);

  const openModal = (membre = null) => {
    setFormError(null);
    if (membre) {
      setEditingId(membre.id);
      setFormData({
        nom_prenom: membre.nom_prenom || '',
        fonction: membre.fonction || '',
      });
    } else {
      setEditingId(null);
      setFormData(initialFormState);
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setFormData(initialFormState);
    setEditingId(null);
    setFormError(null);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setFormError(null);

    try {
      if (editingId) {
        await api.put(`/commission-membres/${editingId}`, formData);
      } else {
        await api.post('/commission-membres', formData);
      }
      fetchMembres();
      closeModal();
    } catch (err) {
      setFormError(err.response?.status === 422
        ? 'Veuillez renseigner le nom et la fonction.'
        : 'Une erreur est survenue lors de l\'enregistrement.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (membreId, nom) => {
    if (!window.confirm(`Supprimer le membre "${nom}" ?`)) return;
    try {
      await api.delete(`/commission-membres/${membreId}`);
      fetchMembres();
    } catch (err) {
      alert('Erreur lors de la suppression.');
    }
  };

  return (
    <div className="flex min-h-screen bg-slate-50">
      <aside className="w-64 bg-[#1e3a8a] text-white flex flex-col h-screen sticky top-0 shadow-xl">
        <div className="px-6 py-6 border-b border-blue-800">
          <Link to="/" className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">ON</div>
            <div>
              <p className="text-sm font-semibold">ERP ONCA</p>
              <p className="text-xs text-white/80">Profil Directeur</p>
            </div>
          </Link>
        </div>

        <nav className="flex-1 mt-4 overflow-y-auto">
          <ul className="space-y-1">
            <li>
              <Link to="/" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Home size={18} />
                <span>Tableau de Bord</span>
              </Link>
            </li>
            <li>
              <Link to="/fournisseurs" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/fournisseurs' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Users size={18} />
                <span>Fournisseurs</span>
              </Link>
            </li>
            <li>
              <Link to="/commission-membres" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/commission-membres' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <ClipboardList size={18} />
                <span>Commission</span>
              </Link>
            </li>
            <li>
              <Link to="/consultations" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/consultations' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <FileText size={18} />
                <span>Consultations</span>
              </Link>
            </li>
            <li>
              <Link to="/aoos/nouveau" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/aoos/nouveau' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Archive size={18} />
                <span>Appels d'Offres</span>
              </Link>
            </li>
            <li>
              <Link to="/marches/nouveau" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/marches/nouveau' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Briefcase size={18} />
                <span>Marchés</span>
              </Link>
            </li>
          </ul>
        </nav>

        <div className="px-6 py-4 border-t border-blue-800 mb-6">
          <button
            onClick={() => { localStorage.removeItem('token'); window.location.href = '/login'; }}
            className="w-full text-left px-4 py-3 mx-3 bg-white/10 hover:bg-white/20 rounded-lg transition-colors text-sm"
          >
            Se déconnecter
          </button>
        </div>
      </aside>

      <main className="flex-1 p-10 overflow-y-auto">
        <div className="max-w-7xl mx-auto">
          <header className="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 className="text-4xl font-extrabold tracking-tight text-blue-900">Membres de la Commission</h1>
              <p className="text-slate-600 mt-2 text-lg">Annuaire réutilisable pour les commissions d'appel d'offres.</p>
            </div>
            <button
              onClick={() => openModal()}
              className="px-5 py-3 bg-[#1e3a8a] text-white hover:bg-blue-900 font-medium rounded-xl transition-all shadow-sm flex items-center space-x-2 w-fit"
            >
              <Plus size={18} />
              <span>Ajouter un membre</span>
            </button>
          </header>

          <div className="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row items-center space-x-4 mb-6">
            <div className="flex items-center gap-2 w-full md:w-auto">
              <Search className="text-slate-400" size={20} />
              <span className="text-sm font-medium text-slate-700">Rechercher par :</span>
            </div>
            <select
              value={searchFilter}
              onChange={(e) => setSearchFilter(e.target.value)}
              className="w-full md:w-48 px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-700"
            >
              <option value="nom_prenom">Nom & Prénom</option>
              <option value="fonction">Fonction</option>
            </select>
            <input
              type="text"
              placeholder="Saisissez votre recherche..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-700"
            />
          </div>

          <div className="bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-100">
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Nom & Prénom</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Fonction</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr>
                      <td colSpan="3" className="px-6 py-12 text-center">
                        <Loader2 className="animate-spin text-blue-500 mx-auto" size={32} />
                      </td>
                    </tr>
                  ) : membres.length === 0 ? (
                    <tr>
                      <td colSpan="3" className="px-6 py-12 text-center text-slate-600">
                        Aucun membre enregistré.
                      </td>
                    </tr>
                  ) : (
                    membres.map((membre) => (
                      <tr key={membre.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                              <UserCircle size={18} />
                            </div>
                            <span className="font-bold text-slate-900">{membre.nom_prenom}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-slate-700">{membre.fonction}</td>
                        <td className="px-6 py-4 text-right">
                          <div className="flex items-center justify-end gap-2 transition-colors">
                            <button onClick={() => openModal(membre)} className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Modifier">
                              <Edit size={18} />
                            </button>
                            <button onClick={() => handleDelete(membre.id, membre.nom_prenom)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Supprimer">
                              <Trash2 size={18} />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
              <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                {editingId ? 'Modifier le membre' : 'Nouveau membre'}
              </h2>
              <button onClick={closeModal} className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {formError && (
                <div className="p-3 bg-red-50 text-red-600 rounded-lg text-sm flex items-center gap-2 border border-red-100">
                  <AlertCircle size={16} /> {formError}
                </div>
              )}
              <div>
                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nom & Prénom *</label>
                <input
                  type="text"
                  name="nom_prenom"
                  value={formData.nom_prenom}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
              <div>
                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Fonction *</label>
                <input
                  type="text"
                  name="fonction"
                  value={formData.fonction}
                  onChange={handleChange}
                  required
                  placeholder="Ex: Ingénieur, Directeur..."
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
              <div className="flex justify-end gap-3 pt-4">
                <button type="button" onClick={closeModal} className="px-5 py-2.5 text-gray-600 font-bold rounded-xl hover:bg-gray-100">
                  Annuler
                </button>
                <button type="submit" disabled={saving} className="px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary-dark disabled:opacity-70 flex items-center gap-2">
                  {saving && <Loader2 size={16} className="animate-spin" />}
                  {editingId ? 'Mettre à jour' : 'Enregistrer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default MembresCommission;
