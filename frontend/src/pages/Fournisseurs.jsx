import React, { useEffect, useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, Search, Edit, Trash2, X, AlertCircle, Building2, Home, Users, FileText, DollarSign, Briefcase, ClipboardList, Archive, Eye, MapPin, Phone, Mail, Briefcase as BriefcaseIcon, User, Zap } from 'lucide-react';

const Fournisseurs = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [fournisseurs, setFournisseurs] = useState([]);
  const [loading, setLoading] = useState(true);

  // Search state
  const [searchTerm, setSearchTerm] = useState('');
  const [searchFilter, setSearchFilter] = useState('raison_sociale'); // raison_sociale, ice, domaine_activite

  // Modal state for editing
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState(null);

  // Modal state for viewing details
  const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
  const [selectedFournisseur, setSelectedFournisseur] = useState(null);

  const initialFormState = {
    raison_sociale: '', ice: '', if: '', rc: '', patente: '',
    adresse: '', ville: '', telephone: '', email: '',
    representant: '', domaine_activite: '',
    qualite_representant: '', cnss: '', banque: '',
    agence_bancaire: '', rib: '', titulaire_compte: ''
  };
  const [formData, setFormData] = useState(initialFormState);

  const fetchFournisseurs = async () => {
    try {
      setLoading(true);
      const params = {};
      if (searchTerm) {
        params[searchFilter] = searchTerm;
      }
      const res = await api.get('/fournisseurs', { params });
      setFournisseurs(res.data);
      setLoading(false);
    } catch (err) {
      console.error(err);
      setLoading(false);
    }
  };

  useEffect(() => {
    // Implement debounce for search
    const delayDebounceFn = setTimeout(() => {
      fetchFournisseurs();
    }, 500);
    return () => clearTimeout(delayDebounceFn);
  }, [searchTerm, searchFilter]);

  const openModal = (fournisseur = null) => {
    setFormError(null);
    if (fournisseur) {
      setEditingId(fournisseur.id);
      setFormData({
        raison_sociale: fournisseur.raison_sociale || '',
        ice: fournisseur.ice || '',
        if: fournisseur.if || '',
        rc: fournisseur.rc || '',
        patente: fournisseur.patente || '',
        adresse: fournisseur.adresse || '',
        ville: fournisseur.ville || '',
        telephone: fournisseur.telephone || '',
        email: fournisseur.email || '',
        representant: fournisseur.representant || '',
        domaine_activite: fournisseur.domaine_activite || '',
        qualite_representant: fournisseur.qualite_representant || '',
        cnss: fournisseur.cnss || '',
        banque: fournisseur.banque || '',
        agence_bancaire: fournisseur.agence_bancaire || '',
        rib: fournisseur.rib || '',
        titulaire_compte: fournisseur.titulaire_compte || '',
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

  const openDetailModal = (fournisseur) => {
    setSelectedFournisseur(fournisseur);
    setIsDetailModalOpen(true);
  };

  const closeDetailModal = () => {
    setIsDetailModalOpen(false);
    setSelectedFournisseur(null);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    
    // Filtre spécifique pour le RIB : uniquement des chiffres, max 24
    if (name === 'rib') {
      const numericValue = value.replace(/\D/g, '');
      if (numericValue.length <= 24) {
        setFormData(prev => ({ ...prev, [name]: numericValue }));
      }
      return;
    }

    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setFormError(null);

    try {
      if (editingId) {
        await api.put(`/fournisseurs/${editingId}`, formData);
      } else {
        await api.post('/fournisseurs', formData);
      }
      fetchFournisseurs();
      closeModal();
    } catch (err) {
      if (err.response?.status === 422) {
        const errors = err.response.data.errors;
        if (errors.ice) {
          setFormError('Cet ICE est déjà utilisé par un autre fournisseur.');
        } else {
          setFormError('Veuillez vérifier les champs obligatoires.');
        }
      } else {
        setFormError('Une erreur est survenue lors de l\'enregistrement.');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id, raison_sociale) => {
    if (window.confirm(`Êtes-vous sûr de vouloir supprimer le fournisseur "${raison_sociale}" ?`)) {
      try {
        await api.delete(`/fournisseurs/${id}`);
        fetchFournisseurs();
      } catch (err) {
        alert("Erreur lors de la suppression. Ce fournisseur est peut-être lié à une consultation.");
      }
    }
  };

  return (
    <div className="flex min-h-screen bg-slate-50">
      {/* Sidebar */}
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

      {/* Main Content */}
      <main className="flex-1 p-10 overflow-y-auto">
        <div className="max-w-7xl mx-auto">

          {/* HEADER */}
          <header className="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 className="text-4xl font-extrabold tracking-tight text-blue-900">Annuaire des Fournisseurs</h1>
              <p className="text-slate-600 mt-2 text-lg">Gérez votre base de données de fournisseurs et prestataires.</p>
            </div>
            <button
              onClick={() => openModal()}
              className="px-5 py-3 bg-[#1e3a8a] text-white hover:bg-blue-900 font-medium rounded-xl transition-all shadow-sm flex items-center space-x-2 w-fit"
            >
              <Plus size={18} />
              <span>Ajouter un fournisseur</span>
            </button>
          </header>

          {/* SEARCH BAR */}
          <div className="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row items-center space-x-4 mb-6">
            <div className="flex items-center gap-2 w-full md:w-auto">
              <Search className="text-slate-400" size={20} />
              <span className="text-base font-medium text-slate-700">Rechercher par :</span>
            </div>
            <select
              value={searchFilter}
              onChange={(e) => setSearchFilter(e.target.value)}
              className="w-full md:w-48 px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base text-slate-700"
            >
              <option value="raison_sociale">Raison Sociale</option>
              <option value="ice">ICE</option>
              <option value="domaine_activite">Domaine d'activité</option>
            </select>
            <input
              type="text"
              placeholder="Saisissez votre recherche..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base text-slate-700"
            />
          </div>

          {/* TABLE */}
          <div className="bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50">
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Raison Sociale</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">ICE</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Ville</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Téléphone</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700">Domaine</th>
                    <th className="px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-700 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {loading ? (
                    <tr>
                      <td colSpan="6" className="px-6 py-12 text-center">
                        <Loader2 className="animate-spin text-blue-500 mx-auto" size={32} />
                      </td>
                    </tr>
                  ) : fournisseurs.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="px-6 py-12 text-center text-slate-600">
                        Aucun fournisseur trouvé.
                      </td>
                    </tr>
                  ) : (
                    fournisseurs.map((fournisseur) => (
                      <tr key={fournisseur.id} className="hover:bg-slate-50/80 transition-colors group">
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                              <Building2 size={18} />
                            </div>
                            <div>
                              <p className="text-slate-900 font-bold">{fournisseur.raison_sociale}</p>
                              <p className="text-xs text-slate-500">{fournisseur.email}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap font-mono font-medium text-slate-700">
                          {fournisseur.ice}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-slate-700">
                          {fournisseur.ville}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-slate-700 font-mono">
                          {fournisseur.telephone}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">
                            {fournisseur.domaine_activite}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right">
                          <div className="flex items-center justify-end gap-2 opacity-100 transition-opacity">
                            <button
                              onClick={() => openDetailModal(fournisseur)}
                              className="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                              title="Voir les détails"
                            >
                              <Eye size={18} />
                            </button>
                            <button
                              onClick={() => openModal(fournisseur)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                              title="Modifier"
                            >
                              <Edit size={18} />
                            </button>
                            <button
                              onClick={() => handleDelete(fournisseur.id, fournisseur.raison_sociale)}
                              className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                              title="Supprimer"
                            >
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

          {/* MODAL */}
          {isModalOpen && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
              <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">

                <div className="px-8 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                  <h2 className="text-2xl font-bold text-slate-900">
                    {editingId ? 'Modifier le fournisseur' : 'Ajouter un fournisseur'}
                  </h2>
                  <button onClick={closeModal} className="p-2 text-slate-400 hover:bg-slate-200 rounded-full transition-colors">
                    <X size={24} />
                  </button>
                </div>

                <div className="p-8 overflow-y-auto flex-1">
                  {formError && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600">
                      <AlertCircle size={20} />
                      <p className="font-medium">{formError}</p>
                    </div>
                  )}

                  <form id="fournisseur-form" onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="space-y-4">
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-1">Raison Sociale <span className="text-red-500">*</span></label>
                          <input required type="text" name="raison_sociale" value={formData.raison_sociale} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">ICE</label>
                            <input type="text" name="ice" value={formData.ice} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Identifiant Fiscal (IF)</label>
                            <input type="text" name="if" value={formData.if} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                          </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Registre Commerce</label>
                            <input type="text" name="rc" value={formData.rc} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Patente</label>
                            <input type="text" name="patente" value={formData.patente} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                          </div>
                        </div>
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-1">N° CNSS</label>
                          <input type="text" name="cnss" value={formData.cnss} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Responsable</label>
                            <input type="text" name="representant" value={formData.representant} onChange={handleChange} placeholder="Ex: Nom Prénom" className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Qualité</label>
                            <input type="text" name="qualite_representant" value={formData.qualite_representant} onChange={handleChange} placeholder="Ex: Gérant, PDG..." className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                          </div>
                        </div>
                      </div>

                      <div className="space-y-4">
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-1">Domaine d'activité</label>
                          <input type="text" name="domaine_activite" value={formData.domaine_activite} onChange={handleChange} placeholder="Ex: Informatique, BTP..." className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                        </div>
                        <div className="grid grid-cols-2 gap-4 col-span-2">
                          <div className="col-span-2">
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Adresse <span className="text-red-500">*</span></label>
                            <input required type="text" name="adresse" value={formData.adresse} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Ville <span className="text-red-500">*</span></label>
                            <input required type="text" name="ville" value={formData.ville} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1">Téléphone</label>
                            <input type="tel" name="telephone" value={formData.telephone} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono" />
                          </div>
                        </div>
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                          <input type="email" name="email" value={formData.email} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                        </div>
                        <div className="border-t border-slate-100 pt-4 mt-2">
                          <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                              <label className="block text-sm font-semibold text-slate-700 mb-1">Banque</label>
                              <input type="text" name="banque" value={formData.banque} onChange={handleChange} placeholder="Ex: Attijariwafa Bank" className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                            </div>
                            <div>
                              <label className="block text-sm font-semibold text-slate-700 mb-1">Agence</label>
                              <input type="text" name="agence_bancaire" value={formData.agence_bancaire} onChange={handleChange} className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900" />
                            </div>
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1 flex justify-between">
                              <span>Compte N° / RIB</span>
                              <span className="text-xs text-slate-500 font-normal">{formData.rib?.length || 0}/24 chiffres</span>
                            </label>
                            <input type="text" name="rib" value={formData.rib} onChange={handleChange} maxLength={24} placeholder="000 000 0000000000000000 00" className="w-full px-4 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 font-mono text-center tracking-widest" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>

                <div className="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-4">
                  <button
                    type="button"
                    onClick={closeModal}
                    className="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    form="fournisseur-form"
                    disabled={saving}
                    className="px-6 py-2.5 bg-[#1e3a8a] text-white font-bold rounded-xl hover:bg-blue-900 transition-all flex items-center gap-2 disabled:opacity-50"
                  >
                    {saving && <Loader2 size={18} className="animate-spin" />}
                    {editingId ? 'Mettre à jour' : 'Ajouter'}
                  </button>
                </div>

              </div>
            </div>
          )}

          {/* DETAIL MODAL */}
          {isDetailModalOpen && selectedFournisseur && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
              <div className="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">

                <div className="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-gradient-to-r from-blue-50 to-blue-100">
                  <div className="flex items-center gap-3">
                    <div className="p-3 bg-blue-600 text-white rounded-lg">
                      <Building2 size={24} />
                    </div>
                    <div>
                      <h2 className="text-2xl font-bold text-slate-900">{selectedFournisseur.raison_sociale}</h2>
                      <p className="text-sm text-slate-600 mt-1">{selectedFournisseur.domaine_activite}</p>
                    </div>
                  </div>
                  <button onClick={closeDetailModal} className="p-2 text-slate-400 hover:bg-slate-200 rounded-full transition-colors">
                    <X size={24} />
                  </button>
                </div>

                <div className="p-8 overflow-y-auto flex-1 space-y-8">
                  
                  {/* Informations Entreprise */}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                      <BriefcaseIcon size={20} className="text-blue-600" />
                      Informations de l'Entreprise
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide">ICE</p>
                        <p className="text-lg font-mono font-bold text-slate-900 mt-1">{selectedFournisseur.ice}</p>
                      </div>
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide">Identifiant Fiscal (IF)</p>
                        <p className="text-lg font-mono font-bold text-slate-900 mt-1">{selectedFournisseur.if}</p>
                      </div>
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide">Registre de Commerce</p>
                        <p className="text-lg font-mono font-bold text-slate-900 mt-1">{selectedFournisseur.rc || '—'}</p>
                      </div>
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide">Patente</p>
                        <p className="text-lg font-mono font-bold text-slate-900 mt-1">{selectedFournisseur.patente || '—'}</p>
                      </div>
                    </div>
                  </div>

                  {/* Coordonnées */}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                      <MapPin size={20} className="text-blue-600" />
                      Coordonnées et Contact
                    </h3>
                    <div className="space-y-4">
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide">Adresse Complète</p>
                        <p className="text-slate-900 mt-2">{selectedFournisseur.adresse}</p>
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="bg-slate-50 p-4 rounded-lg">
                          <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide flex items-center gap-2">
                            <MapPin size={14} /> Ville
                          </p>
                          <p className="text-slate-900 font-semibold mt-2">{selectedFournisseur.ville}</p>
                        </div>
                        <div className="bg-slate-50 p-4 rounded-lg">
                          <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide flex items-center gap-2">
                            <Phone size={14} /> Téléphone
                          </p>
                          <p className="text-slate-900 font-mono font-bold mt-2">{selectedFournisseur.telephone}</p>
                        </div>
                      </div>
                      <div className="bg-slate-50 p-4 rounded-lg">
                        <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide flex items-center gap-2">
                          <Mail size={14} /> Email
                        </p>
                        <p className="text-blue-600 font-medium mt-2 break-all">{selectedFournisseur.email}</p>
                      </div>
                    </div>
                  </div>

                  {/* Représentant */}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                      <User size={20} className="text-blue-600" />
                      Représentant / Gérant
                    </h3>
                    <div className="space-y-4">
                      <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p className="text-xs font-semibold text-blue-700 uppercase tracking-wide">Nom / Raison Sociale</p>
                        <p className="text-lg font-bold text-slate-900 mt-2">{selectedFournisseur.representant || '—'}</p>
                      </div>
                      <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p className="text-xs font-semibold text-blue-700 uppercase tracking-wide">Qualité / Fonction</p>
                        <p className="text-lg font-bold text-slate-900 mt-2">{selectedFournisseur.qualite_representant || '—'}</p>
                      </div>
                    </div>
                  </div>

                  {/* Informations Bancaires */}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                      <Zap size={20} className="text-green-600" />
                      Informations Bancaires
                    </h3>
                    <div className="space-y-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                          <p className="text-xs font-semibold text-green-700 uppercase tracking-wide">Banque</p>
                          <p className="text-slate-900 font-medium mt-2">{selectedFournisseur.banque || '—'}</p>
                        </div>
                        <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                          <p className="text-xs font-semibold text-green-700 uppercase tracking-wide">Agence Bancaire</p>
                          <p className="text-slate-900 font-medium mt-2">{selectedFournisseur.agence_bancaire || '—'}</p>
                        </div>
                      </div>
                      <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p className="text-xs font-semibold text-green-700 uppercase tracking-wide">RIB</p>
                        <p className="text-slate-900 font-mono font-bold mt-2">{selectedFournisseur.rib || '—'}</p>
                      </div>
                      <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p className="text-xs font-semibold text-green-700 uppercase tracking-wide">Titulaire du Compte</p>
                        <p className="text-slate-900 font-medium mt-2">{selectedFournisseur.titulaire_compte || '—'}</p>
                      </div>
                    </div>
                  </div>

                  {/* Informations Sociales */}
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                      <Users size={20} className="text-purple-600" />
                      Informations Sociales
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <p className="text-xs font-semibold text-purple-700 uppercase tracking-wide">N° CNSS</p>
                        <p className="text-slate-900 font-mono font-bold mt-2">{selectedFournisseur.cnss || '—'}</p>
                      </div>
                    </div>
                  </div>

                </div>

                <div className="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-4">
                  <button
                    onClick={closeDetailModal}
                    className="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-2"
                  >
                    <X size={18} />
                    Fermer
                  </button>
                </div>

              </div>
            </div>
          )}

        </div>
      </main>
    </div>
  );
};
export default Fournisseurs;
