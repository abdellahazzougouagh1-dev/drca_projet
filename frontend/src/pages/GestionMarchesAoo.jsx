import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Save, FileText, CheckCircle, Clock, AlertCircle, Loader2, ArrowLeft, Download, Users, Briefcase, FileSignature, Building2, Calendar, FileDown } from 'lucide-react';

const GestionMarchesAoo = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('aoo');

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  const [formData, setFormData] = useState({
    num_aoo: '',
    objet: '',
    date_ouverture: '',
    heure_ouverture: '',
    nombre_lots: 1,
    journal_francais: '',
    journal_arabe: '',
    budget_type: '',
    code_budget: '',
    art: '',
    par: '',
    lig: '',
    caution_provisoire: '',
    estimation_administrative: '',
    aoo_president: '',
    aoo_membre1: '',
    aoo_membre2: '',
    num_marche: '',
    titulaire: '',
    montant_max: '',
    delai_execution: '',
    date_reception: '',
    membre_reception_1: ''
  });

  useEffect(() => {
    if (id && id !== 'nouveau') {
      fetchDossier();
    }
  }, [id]);

  const fetchDossier = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/marches-aoo/${id}`);
      // Fill null values with empty strings for controlled inputs
      const data = response.data;
      const sanitizedData = {};
      Object.keys(data).forEach(key => {
        sanitizedData[key] = data[key] !== null ? data[key] : '';
      });
      setFormData(sanitizedData);
    } catch (err) {
      setErrorMessage('Erreur lors du chargement du dossier AOO.');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setSuccessMessage('');
    setErrorMessage('');

    try {
      const payload = { ...formData };
      if (id && id !== 'nouveau') {
        payload.id = id;
      }

      const response = await api.post('/marches-aoo', payload);
      setSuccessMessage('Dossier enregistré avec succès.');

      // Si c'est un nouveau dossier, on redirige vers l'URL avec l'ID
      if (!id || id === 'nouveau') {
        setTimeout(() => {
          navigate(`/marches-aoo/${response.data.data.id}`);
        }, 1500);
      }
    } catch (err) {
      setErrorMessage('Erreur lors de l\'enregistrement. Veuillez vérifier les champs.');
    } finally {
      setSaving(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-screen bg-slate-50 text-blue-600">
        <Loader2 className="animate-spin mb-4" size={48} />
        <h2 className="text-xl font-bold">Chargement du dossier...</h2>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50/50 pb-24">
      {/* HEADER */}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="py-4 flex items-center justify-between">
            <div className="flex items-center gap-4">
              <button onClick={() => navigate('/dashboard')} className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all">
                <ArrowLeft size={20} />
              </button>
              <div>
                <h1 className="text-2xl font-bold text-slate-800 flex items-center gap-3">
                  <Building2 className="text-primary" />
                  {id && id !== 'nouveau' ? `Dossier AOO : ${formData.num_aoo || 'En cours'}` : 'Nouveau Dossier AOO'}
                </h1>
                <p className="text-slate-500 text-sm mt-1">Gestion centralisée des Appels d'Offres & Marchés (Processus ONCA)</p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              {successMessage && (
                <div className="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl font-bold text-sm flex items-center gap-2 border border-emerald-100 animate-fade-in-down">
                  <CheckCircle size={18} /> {successMessage}
                </div>
              )}
              {errorMessage && (
                <div className="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold text-sm flex items-center gap-2 border border-red-100 animate-fade-in-down">
                  <AlertCircle size={18} /> {errorMessage}
                </div>
              )}
              <button
                onClick={handleSubmit}
                disabled={saving}
                className="px-6 py-2.5 bg-primary text-white font-bold rounded-xl shadow-md shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
              >
                {saving ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                {saving ? 'Enregistrement...' : 'Enregistrer le Dossier'}
              </button>
            </div>
          </div>

          {/* TABS NAVIGATION */}
          <div className="flex space-x-1 pb-4 overflow-x-auto">
            <button onClick={() => setActiveTab('aoo')} className={`whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === 'aoo' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
              <FileText size={18} /> 1. Ouverture & Publication (AOO)
            </button>
            <button onClick={() => setActiveTab('marche')} className={`whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === 'marche' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
              <Briefcase size={18} /> 2. Attribution (Marché)
            </button>
            <button onClick={() => setActiveTab('reception')} className={`whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === 'reception' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
              <Users size={18} /> 3. Commission de Réception
            </button>
            <button onClick={() => setActiveTab('documents')} className={`whitespace-nowrap px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 ${activeTab === 'documents' ? 'bg-emerald-600 text-white shadow-md' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'}`}>
              <FileDown size={18} /> 4. Édition des Documents (PDF)
            </button>
          </div>
        </div>
      </header>

      {/* FORM CONTENT */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <form onSubmit={handleSubmit} className="bg-white rounded-3xl p-8 shadow-sm border border-slate-200">

          {/* TAB 1 : AOO */}
          <div className={activeTab === 'aoo' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-slate-800">Informations de l'Appel d'Offres</h2>
              <p className="text-slate-500 text-sm mt-1">Saisissez les détails de la publication et de l'ouverture des plis.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="col-span-1 lg:col-span-3">
                <label className="block text-sm font-bold text-slate-700 mb-2">Objet de l'Appel d'Offres *</label>
                <textarea name="objet" value={formData.objet} onChange={handleChange} required rows={3} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all resize-none bg-slate-50 focus:bg-white"></textarea>
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro AOO *</label>
                <input type="text" name="num_aoo" value={formData.num_aoo} onChange={handleChange} required placeholder="Ex: 01/2026/ONCA" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date d'ouverture *</label>
                <input type="date" name="date_ouverture" value={formData.date_ouverture} onChange={handleChange} required className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Heure d'ouverture *</label>
                <input type="time" name="heure_ouverture" value={formData.heure_ouverture} onChange={handleChange} required className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Estimation Administrative (Dhs) *</label>
                <input type="number" step="0.01" name="estimation_administrative" value={formData.estimation_administrative} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Caution Provisoire (Dhs) *</label>
                <input type="number" step="0.01" name="caution_provisoire" value={formData.caution_provisoire} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Nombre de lots</label>
                <input type="number" name="nombre_lots" value={formData.nombre_lots} onChange={handleChange} min="1" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>

              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Journal Français</label>
                <input type="text" name="journal_francais" value={formData.journal_francais} onChange={handleChange} placeholder="Nom du journal" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Journal Arabe</label>
                <input type="text" name="journal_arabe" value={formData.journal_arabe} onChange={handleChange} placeholder="Nom du journal" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>

              {/* Ligne budgétaire */}
              <div className="col-span-1 lg:col-span-3 mt-4">
                <h3 className="text-sm font-bold text-slate-800 mb-3 uppercase tracking-widest bg-slate-100 inline-block px-3 py-1 rounded-md">Imputation Budgétaire</h3>
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Type Budget</label>
                    <input type="text" name="budget_type" value={formData.budget_type} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Code</label>
                    <input type="text" name="code_budget" value={formData.code_budget} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Art</label>
                    <input type="text" name="art" value={formData.art} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Par</label>
                    <input type="text" name="par" value={formData.par} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Lig</label>
                    <input type="text" name="lig" value={formData.lig} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                </div>
              </div>

              {/* Commission */}
              <div className="col-span-1 lg:col-span-3 mt-4">
                <h3 className="text-sm font-bold text-slate-800 mb-3 uppercase tracking-widest bg-slate-100 inline-block px-3 py-1 rounded-md">Commission d'Ouverture</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Président</label>
                    <input type="text" name="aoo_president" value={formData.aoo_president} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Membre 1</label>
                    <input type="text" name="aoo_membre1" value={formData.aoo_membre1} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1">Membre 2</label>
                    <input type="text" name="aoo_membre2" value={formData.aoo_membre2} onChange={handleChange} className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-primary" />
                  </div>
                </div>
              </div>

            </div>
          </div>

          {/* TAB 2 : MARCHE */}
          <div className={activeTab === 'marche' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4 flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600"><FileSignature size={20} /></div>
              <div>
                <h2 className="text-xl font-extrabold text-slate-800">Informations de l'Attribution (Marché)</h2>
                <p className="text-slate-500 text-sm mt-1">Détails de l'entreprise retenue après commission.</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Numéro du Marché</label>
                <input type="text" name="num_marche" value={formData.num_marche} onChange={handleChange} placeholder="Ex: 12/2026/ONCA" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Entreprise Titulaire</label>
                <input type="text" name="titulaire" value={formData.titulaire} onChange={handleChange} placeholder="Raison sociale du fournisseur retenu" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Montant Maximum (Dhs TTC)</label>
                <input type="number" step="0.01" name="montant_max" value={formData.montant_max} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Délai d'exécution</label>
                <input type="text" name="delai_execution" value={formData.delai_execution} onChange={handleChange} placeholder="Ex: 12 mois ou 90 jours" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
            </div>
          </div>

          {/* TAB 3 : RECEPTION */}
          <div className={activeTab === 'reception' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4 flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600"><Users size={20} /></div>
              <div>
                <h2 className="text-xl font-extrabold text-slate-800">Commission de Réception</h2>
                <p className="text-slate-500 text-sm mt-1">Saisie des membres et de la date de réception.</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Date de Réception</label>
                <input type="date" name="date_reception" value={formData.date_reception} onChange={handleChange} className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
              <div>
                <label className="block text-sm font-bold text-slate-700 mb-2">Membre de Réception (Principal)</label>
                <input type="text" name="membre_reception_1" value={formData.membre_reception_1} onChange={handleChange} placeholder="Nom et qualité" className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all bg-slate-50 focus:bg-white" />
              </div>
            </div>
          </div>

          {/* TAB 4 : DOCUMENTS PDF */}
          <div className={activeTab === 'documents' ? 'block animate-fade-in' : 'hidden'}>
            <div className="mb-8 border-b border-slate-100 pb-4">
              <h2 className="text-xl font-extrabold text-emerald-800">Documents à Générer</h2>
              <p className="text-slate-500 text-sm mt-1">Vous pourrez imprimer ici tous les modèles spécifiques au flux AOO basés sur les données saisies.</p>
            </div>

            {!id || id === 'nouveau' ? (
              <div className="p-8 bg-amber-50 rounded-2xl border border-amber-100 text-center">
                <AlertCircle className="mx-auto text-amber-500 mb-3" size={32} />
                <h3 className="text-lg font-bold text-amber-800 mb-1">Dossier non enregistré</h3>
                <p className="text-amber-700">Vous devez d'abord enregistrer les informations du dossier (Bouton en haut à droite) avant de pouvoir générer les documents PDF.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="p-6 bg-slate-50 border border-slate-200 rounded-2xl">
                  <h3 className="font-bold text-slate-800 mb-4 border-b pb-2">Phase 1 : Publication</h3>
                  <div className="space-y-3">
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> Rapport de présentation</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> Règlement de consultation</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> Avis d'Appel d'Offres (Ar/Fr)</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                  </div>
                </div>

                <div className="p-6 bg-slate-50 border border-slate-200 rounded-2xl">
                  <h3 className="font-bold text-slate-800 mb-4 border-b pb-2">Phase 2 : Commission & Attribution</h3>
                  <div className="space-y-3">
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> PV d'ouverture des plis</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> Acte d'engagement</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                    <button type="button" className="w-full px-4 py-3 bg-white text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all flex items-center justify-between group">
                      <span className="flex items-center gap-2"><FileText size={16} className="text-emerald-500" /> OS de Notification</span>
                      <Download size={16} className="opacity-0 group-hover:opacity-100" />
                    </button>
                  </div>
                </div>

                {/* Note : Ces boutons sont des placeholders pour l'instant (UI Uniquement) */}
              </div>
            )}
          </div>

        </form>
      </main>
    </div>
  );
};

export default GestionMarchesAoo;
