import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Save, ArrowLeft, CheckCircle2, AlertCircle, FileText, Calculator } from 'lucide-react';

const NouvelleConsultation = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

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
    art: '',
    par: '',
    lig: '',
    code_imputation: '',
    exercice_budgetaire: new Date().getFullYear(),
    montant_estimatif_ht: 0,
    tva: 20,
  });

  const [montantTTC, setMontantTTC] = useState(0);

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

  const handleSubmit = async (e) => {
    e.preventDefault();
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
                onClick={() => navigate('/dashboard')}
                className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Nouvelle Consultation
              </h1>
            </div>
            <p className="text-gray-500 dark:text-gray-400 ml-11">
              Créer un nouveau dossier de consultation et allouer son budget.
            </p>
          </div>
        </header>

        {error && (
          <div className="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 text-red-600 dark:text-red-400">
            <AlertCircle size={20} />
            <p className="font-medium">{error}</p>
          </div>
        )}

        {success && (
          <div className="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 size={20} />
            <p className="font-medium">Consultation créée avec succès ! Redirection en cours...</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          
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

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Article (ART)</label>
                <input required type="text" name="art" value={formData.art} onChange={handleChange} placeholder="Ex: 10" className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Paragraphe (PAR)</label>
                <input required type="text" name="par" value={formData.par} onChange={handleChange} placeholder="Ex: 20" className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ligne (LIG)</label>
                <input required type="text" name="lig" value={formData.lig} onChange={handleChange} placeholder="Ex: 30" className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Code d'imputation</label>
                <input required type="text" name="code_imputation" value={formData.code_imputation} onChange={handleChange} placeholder="Ex: 102030" className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Exercice budgétaire</label>
                <input required type="number" name="exercice_budgetaire" value={formData.exercice_budgetaire} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white" />
              </div>

              <div className="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl col-span-1 md:col-span-3 mt-4 border border-gray-100 dark:border-gray-700">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Montant HT (MAD)</label>
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
                      <span>{new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(montantTTC)}</span>
                      <span className="text-sm">MAD</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <div className="flex justify-end pt-4">
            <button
              type="submit"
              disabled={loading || success}
              className="px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:shadow-xl hover:bg-primary-dark hover:-translate-y-1 transition-all duration-300 flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            >
              {loading ? (
                <>
                  <Loader2 size={24} className="animate-spin" />
                  Création en cours...
                </>
              ) : (
                <>
                  <Save size={24} />
                  Enregistrer la consultation
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
