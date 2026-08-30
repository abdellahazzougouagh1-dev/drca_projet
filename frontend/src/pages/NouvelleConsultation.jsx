import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import api from '../api/axios';
import { Save, ArrowLeft, CheckCircle2, AlertCircle, FileText, Calculator } from 'lucide-react';

const NouvelleConsultation = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const initialLigneId = location.state?.ligneBudgetaireId || '';
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const [typeDossier, setTypeDossier] = useState('BC');

  // Form State
  const [formData, setFormData] = useState({
    // Infos Générales
    annee: new Date().getFullYear(),
    date_consultation: new Date().toISOString().split('T')[0],
    objet_consultation: '',
    description_detaillee: '',
    categorie: 'Services',
    type_prestation: '',
    mode_engagement: 'BC',
    type_budget: 'Fonctionnement',
    delai_execution: 30,
    statut_dossier: 'Programmation',
    
    // Infos Budgétaires
    notification_ligne_id: initialLigneId,
    montant_estimatif_ht: 0,
    tva: 20,
  });

  const [montantTTC, setMontantTTC] = useState(0);
  const [lignesBudgetaires, setLignesBudgetaires] = useState([]);
  const [selectedLigne, setSelectedLigne] = useState(null);

  useEffect(() => {
    const fetchLignes = async () => {
      try {
        const response = await api.get('/notification-lignes');
        setLignesBudgetaires(response.data);
        if (initialLigneId) {
          const ligne = response.data.find(l => l.id.toString() === initialLigneId.toString());
          if (ligne) setSelectedLigne(ligne);
        }
      } catch (err) {
        console.error('Erreur chargement lignes budgétaires:', err);
      }
    };
    fetchLignes();
  }, [initialLigneId]);

  const handleLigneChange = (e) => {
    const id = e.target.value;
    setFormData(prev => ({ ...prev, notification_ligne_id: id }));
    if (id) {
      const ligne = lignesBudgetaires.find(l => l.id.toString() === id);
      setSelectedLigne(ligne);
    } else {
      setSelectedLigne(null);
    }
  };

  // Auto-calcul TTC
  useEffect(() => {
    const ht = parseFloat(formData.montant_estimatif_ht) || 0;
    const tva = parseFloat(formData.tva) || 0;
    const ttc = ht * (1 + tva / 100);
    setMontantTTC(ttc);
  }, [formData.montant_estimatif_ht, formData.tva]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleTypeDossierChange = (type) => {
    setTypeDossier(type);
    if (type === 'BC') {
      navigate('/bons-commande');
      return;
    }
    if (type === 'Convention') {
      setFormData(prev => ({ ...prev, mode_engagement: type }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (typeDossier === 'BC') {
      navigate('/bons-commande');
      return;
    }
    if (typeDossier === 'AOO') {
      navigate('/aoos/nouveau');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccess(false);

    try {
      await api.post('/consultations', formData);
      setSuccess(true);
      setTimeout(() => {
        navigate('/dashboard');
      }, 2000);
    } catch (err) {
      console.error(err);
      setError(
        err.response?.data?.message || 'Une erreur est survenue lors de la création de la consultation.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div className="max-w-5xl mx-auto">
        <header className="mb-8 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button 
                onClick={() => navigate('/consultations')}
                className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Nouveau Dossier
              </h1>
            </div>
            <p className="text-gray-500 dark:text-gray-400 ml-11">
              Créer un nouveau dossier (Appel d'Offres, Bon de Commande, Convention).
            </p>
          </div>
        </header>

        {error && (
          <div className="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 text-red-600 dark:text-red-400">
            <AlertCircle size={20} />
            <p className="font-semibold">{error}</p>
          </div>
        )}

        {success && (
          <div className="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 size={20} />
            <p className="font-semibold">Consultation créée avec succès ! Redirection en cours...</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          {/* SÉLECTEUR DE TYPE */}
          <section className="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700">
            <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
              <div className="p-2 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg">
                <FileText size={20} />
              </div>
              <h2 className="text-xl font-bold text-gray-800 dark:text-white">Type de Dossier</h2>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {['AOO', 'BC', 'Convention'].map(type => (
                <div 
                  key={type}
                  onClick={() => handleTypeDossierChange(type)}
                  className={`p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center justify-center text-center gap-2 ${typeDossier === type ? 'border-primary bg-primary/5 text-primary' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'}`}
                >
                  <span className="font-bold">{type === 'AOO' ? "Appel d'Offres" : type === 'BC' ? 'Bon de Commande' : 'Convention'}</span>
                </div>
              ))}
            </div>
          </section>

          {typeDossier === 'AOO' ? (
            <section className="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-lg border border-primary/20 flex flex-col items-center justify-center text-center py-16">
              <FileText size={48} className="text-primary mb-4" />
              <h2 className="text-2xl font-bold text-slate-800 mb-2">Création d'un Appel d'Offres</h2>
              <p className="text-slate-500 mb-8 max-w-md">
                Les Appels d'Offres disposent d'un module avancé (Préparation, Commission, Attribution). Cliquez sur le bouton ci-dessous pour y accéder.
              </p>
            </section>
          ) : (
            <>
              {/* BLOC 1 : Informations Générales */}
              <section className="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700">
                <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                  <div className="p-2 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg">
                    <FileText size={20} />
                  </div>
                  <h2 className="text-xl font-bold text-gray-800 dark:text-white">Informations Générales</h2>
                </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Année</label>
                <input required type="number" name="annee" value={formData.annee} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date de consultation</label>
                <input required type="date" name="date_consultation" value={formData.date_consultation} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Objet de la consultation</label>
                <input required type="text" name="objet_consultation" value={formData.objet_consultation} onChange={handleChange} placeholder="Ex: Achat de matériel informatique..." className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description détaillée</label>
                <textarea rows="3" name="description_detaillee" value={formData.description_detaillee} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Catégorie</label>
                <select name="categorie" value={formData.categorie} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Travaux">Travaux</option>
                  <option value="Fournitures">Fournitures</option>
                  <option value="Services">Services</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Type de prestation</label>
                <input required type="text" name="type_prestation" value={formData.type_prestation} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Mode d'engagement</label>
                <select name="mode_engagement" value={formData.mode_engagement} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="BC">Bon de Commande (BC)</option>
                  <option value="Convention">Convention</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Type de budget</label>
                <select name="type_budget" value={formData.type_budget} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Investissement">Investissement</option>
                  <option value="Fonctionnement">Fonctionnement</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Délai d'exécution (en jours)</label>
                <input required type="number" min="1" name="delai_execution" value={formData.delai_execution} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
            </div>
          </section>

          {/* BLOC 2 : Informations Budgétaires */}
          <section className="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700">
            <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
              <div className="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-lg">
                <Calculator size={20} />
              </div>
              <h2 className="text-xl font-bold text-gray-800 dark:text-white">Informations Budgétaires</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Imputation budgétaire (Notification)</label>
                <select name="notification_ligne_id" value={formData.notification_ligne_id} onChange={handleLigneChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="">-- Sélectionner une ligne budgétaire --</option>
                  {lignesBudgetaires.map(ligne => (
                    <option key={ligne.id} value={ligne.id}>
                      {ligne.notification?.numero} - {ligne.article}/{ligne.paragraphe}/{ligne.ligne_budgetaire} - {ligne.libelle}
                    </option>
                  ))}
                </select>
              </div>

              {selectedLigne && (
                <div className="md:col-span-2 bg-blue-50 border border-blue-100 rounded-xl p-5 grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                  <div>
                    <span className="block text-xs font-bold text-blue-500 uppercase tracking-wide">Crédit Total</span>
                    <span className="text-lg font-bold text-blue-900">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(selectedLigne.total_credits).replace('MAD', 'dh')}</span>
                  </div>
                  <div>
                    <span className="block text-xs font-bold text-emerald-500 uppercase tracking-wide">Crédit Engagé</span>
                    <span className="text-lg font-bold text-emerald-900">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(selectedLigne.credits_engages).replace('MAD', 'dh')}</span>
                  </div>
                  <div>
                    <span className="block text-xs font-bold text-indigo-500 uppercase tracking-wide">Crédit Disponible</span>
                    <span className="text-lg font-bold text-indigo-900">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(selectedLigne.credits_disponibles).replace('MAD', 'dh')}</span>
                  </div>
                </div>
              )}

              <div className="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl col-span-1 md:col-span-2 mt-4 border border-gray-100 dark:border-gray-700">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Montant HT (dh)</label>
                    <input required type="number" step="0.01" min="0" name="montant_estimatif_ht" value={formData.montant_estimatif_ht} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-lg font-mono dark:text-white" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">TVA (%)</label>
                    <select name="tva" value={formData.tva} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-lg font-mono dark:text-white">
                      <option value="20">20%</option>
                      <option value="14">14%</option>
                      <option value="10">10%</option>
                      <option value="7">7%</option>
                      <option value="0">0%</option>
                    </select>
                  </div>
                  <div className="flex flex-col justify-end">
                    <label className="block text-sm font-bold text-primary mb-2">Montant TTC Calculé</label>
                    <div className="w-full px-4 py-3 rounded-xl bg-primary/10 border border-primary/20 text-primary text-xl font-bold font-mono flex items-center justify-between">
                      <span>{new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(montantTTC).replace('MAD', 'dh')}</span>
                      <span className="text-sm">dh</span>
                    </div>
                  </div>
                </div>
              </div>
              </div>
            </section>
            </>
          )}

          <div className="flex justify-end pt-4">
            <button
              type="submit"
              disabled={loading || success}
              className="px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:shadow-xl hover:bg-primary-dark hover:-translate-y-1 transition-all duration-300 flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            >
              {loading ? (
                <>
                  <Loader2 size={24} className="animate-spin" />
                  {typeDossier === 'AOO' ? 'Redirection...' : 'Création en cours...'}
                </>
              ) : (
                <>
                  <Save size={24} />
                  {typeDossier === 'AOO' ? 'Continuer vers le module AOO' : 'Enregistrer le dossier'}
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default NouvelleConsultation;
