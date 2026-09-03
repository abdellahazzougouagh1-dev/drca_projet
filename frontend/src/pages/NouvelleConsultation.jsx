import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Save, ArrowLeft, CheckCircle2, AlertCircle, FileText, Calculator, Loader2 } from 'lucide-react';

const NouvelleConsultation = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  // Form State
  const defaultYear = new Date().getFullYear() < 2026 ? 2026 : new Date().getFullYear();
  const todayStr = new Date().toISOString().split('T')[0];
  const defaultDateCons = todayStr < '2026-01-01' ? `${defaultYear}-08-22` : todayStr;

  const [formData, setFormData] = useState({
    // Infos Générales
    numero_consultation: '',
    numero_decision: '',
    numero_bc: '',
    date_limite_devis: '',
    heure_limite_devis: '10:00',
    lieu_execution: 'REGION DE RABAT SALE KENITRA',
    annee: defaultYear,
    date_consultation: defaultDateCons,
    objet_consultation: '',
    categorie: 'Services',
    type_prestation: '',
    intitule: '',
    mode_engagement: 'BC',
    type_budget: 'Fonctionnement',
    delai_execution: 30,
    statut_dossier: 'Programmation',

    // Infos Budgétaires
    art: '',
    par: '',
    lig: '',
    code_imputation: '',
    exercice_budgetaire: defaultYear,
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

  // Auto-génération du Code d'imputation (ART + PAR + LIG)
  useEffect(() => {
    const code = `${formData.art || ''}${formData.par || ''}${formData.lig || ''}`;
    setFormData((prev) => ({
      ...prev,
      code_imputation: code,
    }));
  }, [formData.art, formData.par, formData.lig]);

  // Auto-calcul de la date limite de réception des devis / ouverture des plis (Date de consultation + 2 jours)
  useEffect(() => {
    if (formData.date_consultation) {
      const dCons = new Date(formData.date_consultation);
      if (!isNaN(dCons.getTime())) {
        const dLimite = new Date(dCons);
        dLimite.setDate(dLimite.getDate() + 2);
        const yyyy = dLimite.getFullYear();
        const mm = String(dLimite.getMonth() + 1).padStart(2, '0');
        const dd = String(dLimite.getDate()).padStart(2, '0');
        const calculatedDate = `${yyyy}-${mm}-${dd}`;

        setFormData((prev) => ({
          ...prev,
          date_limite_devis: calculatedDate,
        }));
      }
    }
  }, [formData.date_consultation]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    if (fieldErrors[name]) {
      setFieldErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const validateForm = () => {
    const errors = {};
    const fieldsToValidate = [
      { name: 'numero_consultation', label: 'Numéro de consultation' },
      { name: 'numero_decision', label: "Numéro de décision d'ouverture de commission" },
      ...(formData.mode_engagement === 'BC' ? [{ name: 'numero_bc', label: 'Numéro de bon de commande' }] : []),
      { name: 'date_limite_devis', label: 'Date limite de réception des devis' },
      { name: 'heure_limite_devis', label: 'Heure limite' },
      { name: 'lieu_execution', label: "Lieu d'exécution" },
      { name: 'annee', label: 'Année' },
      { name: 'date_consultation', label: 'Date de consultation' },
      { name: 'objet_consultation', label: 'Objet de la consultation' },
      { name: 'intitule', label: 'Intitulé (Nature de la prestation)' },
      { name: 'delai_execution', label: "Délai d'exécution" },
      { name: 'art', label: 'Article (ART)' },
      { name: 'par', label: 'Paragraphe (PAR)' },
      { name: 'lig', label: 'Ligne (LIG)' },
      { name: 'exercice_budgetaire', label: 'Exercice budgétaire' },
    ];

    fieldsToValidate.forEach((field) => {
      const val = formData[field.name];
      if (val === undefined || val === null || String(val).trim() === '') {
        errors[field.name] = `Le champ « ${field.label} » est obligatoire.`;
      }
    });

    // Validation spécifique des dates (Toutes les dates doivent être >= 2026-01-01)
    const minDate = new Date('2026-01-01');

    if (formData.date_consultation) {
      const dConsultation = new Date(formData.date_consultation);
      if (!isNaN(dConsultation.getTime()) && dConsultation < minDate) {
        errors.date_consultation = "La date de consultation doit être supérieure ou égale au 01/01/2026.";
      }
    }

    if (formData.date_limite_devis) {
      const dLimite = new Date(formData.date_limite_devis);
      if (!isNaN(dLimite.getTime()) && dLimite < minDate) {
        errors.date_limite_devis = "La date limite des devis doit être supérieure ou égale au 01/01/2026.";
      }
    }

    if (formData.date_consultation && formData.date_limite_devis) {
      const dConsultation = new Date(formData.date_consultation);
      const dLimite = new Date(formData.date_limite_devis);
      if (!isNaN(dConsultation.getTime()) && !isNaN(dLimite.getTime())) {
        if (dLimite < dConsultation) {
          errors.date_limite_devis = 'La date limite des devis doit être égale ou postérieure à la date de consultation.';
        }
      }
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      setError('Attention : Impossible d\'enregistrer la consultation. Veuillez remplir TOUS les champs obligatoires ci-dessous.');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccess(false);

    try {
      const payload = {
        ...formData,
        type_prestation: formData.intitule || formData.type_prestation || formData.objet_consultation,
      };
      const response = await api.post('/consultations', payload);
      const created = response.data;
      setSuccess(true);
      setTimeout(() => {
        if (formData.mode_engagement === 'AO' || formData.mode_engagement === 'Appel d\'offres' || formData.mode_engagement === 'Appel d\'offre') {
          navigate('/aoos/nouveau', { state: { autoSelectId: created?.id, createdConsultation: created } });
        } else {
          navigate('/bons-commande', { state: { autoSelectId: created?.id } });
        }
      }, 1000);
    } catch (err) {
      console.error(err);
      setError(
        err.response?.data?.message || 'Une erreur est survenue lors de la création de la consultation.'
      );
    } finally {
      setLoading(false);
    }
  };

  const getInputClass = (fieldName) => `w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border ${fieldErrors[fieldName]
      ? 'border-red-500 bg-red-50/20 text-red-900 focus:ring-red-200'
      : 'border-gray-200 dark:border-gray-700 focus:ring-primary'
    } focus:ring-2 focus:border-transparent outline-none transition-all dark:text-white`;

  return (
    <div className="p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div className="max-w-5xl mx-auto">
        <header className="mb-8 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button
                type="button"
                onClick={() => {
                  if (window.history.length > 1) {
                    navigate(-1);
                  } else {
                    navigate('/bons-commande');
                  }
                }}
                className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500"
                title="Retour à la plateforme"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Nouvelle Consultation
              </h1>
            </div>
          </div>
        </header>

        {error && (
          <div className="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 text-red-600 dark:text-red-400 font-semibold">
            <AlertCircle size={20} />
            <p>{error}</p>
          </div>
        )}

        {success && (
          <div className="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3 text-emerald-600 dark:text-emerald-400 font-semibold">
            <CheckCircle2 size={20} />
            <p>Consultation créée avec succès ! Redirection en cours...</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8" noValidate>

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
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Numéro de consultation *</label>
                <input type="text" name="numero_consultation" value={formData.numero_consultation} onChange={handleChange} placeholder="Ex: 04/2026/DRCA-RSK" className={getInputClass('numero_consultation')} />
                {fieldErrors.numero_consultation && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.numero_consultation}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Numéro de  la décision  de  la commission d'ouverture *</label>
                <input type="text" name="numero_decision" value={formData.numero_decision} onChange={handleChange} placeholder="Ex: 06/2026/DRCA-RSK" className={getInputClass('numero_decision')} />
                {fieldErrors.numero_decision && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.numero_decision}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Numéro de bon de commande {formData.mode_engagement === 'BC' ? '*' : '(Optionnel)'}
                </label>
                <input type="text" name="numero_bc" value={formData.numero_bc} onChange={handleChange} placeholder="Ex: BC CONS-2026-0001" className={getInputClass('numero_bc')} />
                {fieldErrors.numero_bc && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.numero_bc}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date limite de réception des devis *</label>
                <input type="date" name="date_limite_devis" min="2026-01-01" max="2099-12-31" value={formData.date_limite_devis} onChange={handleChange} className={getInputClass('date_limite_devis')} />
                {fieldErrors.date_limite_devis && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.date_limite_devis}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Heure limite *</label>
                <input type="text" name="heure_limite_devis" value={formData.heure_limite_devis} onChange={handleChange} placeholder="Ex: 10:00" className={getInputClass('heure_limite_devis')} />
                {fieldErrors.heure_limite_devis && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.heure_limite_devis}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Lieu d'exécution *</label>
                <input type="text" name="lieu_execution" value={formData.lieu_execution} onChange={handleChange} placeholder="Ex: REGION DE RABAT SALE KENITRA" className={getInputClass('lieu_execution')} />
                {fieldErrors.lieu_execution && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.lieu_execution}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Année *</label>
                <input type="number" name="annee" min="2026" max="2099" onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }} value={formData.annee} onChange={handleChange} className={getInputClass('annee')} />
                {fieldErrors.annee && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.annee}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date de consultation *</label>
                <input type="date" name="date_consultation" min="2026-01-01" max="2099-12-31" value={formData.date_consultation} onChange={handleChange} className={getInputClass('date_consultation')} />
                {fieldErrors.date_consultation && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.date_consultation}
                  </span>
                )}
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Objet de la consultation *</label>
                <input type="text" name="objet_consultation" value={formData.objet_consultation} onChange={handleChange} placeholder="Ex: Achat de matériel informatique..." className={getInputClass('objet_consultation')} />
                {fieldErrors.objet_consultation && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.objet_consultation}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Mode d'engagement *</label>
                <select name="mode_engagement" value={formData.mode_engagement} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white font-semibold">
                  <option value="BC">Bon de commande</option>
                  <option value="AO">Appel d'offres</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Catégorie *</label>
                <select name="categorie" value={formData.categorie} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Travaux">Travaux</option>
                  <option value="Fournitures">Fournitures</option>
                  <option value="Services">Services</option>
                </select>
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Intitulé (Nature de la prestation) *</label>
                <input type="text" name="intitule" value={formData.intitule} onChange={(e) => {
                  const val = e.target.value;
                  setFormData((prev) => ({ ...prev, intitule: val, type_prestation: val }));
                  if (fieldErrors.intitule) {
                    setFieldErrors((prev) => ({ ...prev, intitule: null }));
                  }
                }} placeholder="Ex: Prestation de même nature / Achat de matériel technique..." className={getInputClass('intitule')} />
                {fieldErrors.intitule && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.intitule}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Type de budget *</label>
                <select name="type_budget" value={formData.type_budget} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Investissement">Investissement</option>
                  <option value="Fonctionnement">Fonctionnement</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Délai d'exécution (en jours) *</label>
                <input type="number" min="1" name="delai_execution" value={formData.delai_execution} onChange={handleChange} className={getInputClass('delai_execution')} />
                {fieldErrors.delai_execution && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.delai_execution}
                  </span>
                )}
              </div>
            </div>
          </section>

          {/* BLOC 2 : Informations Budgétaires */}
          <section className="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700">
            <div className="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
              <div className="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-lg">
                <Calculator size={20} />
              </div>
              <h2 className="text-xl font-bold text-gray-800 dark:text-white">Imputation Budgétaires</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Article (ART) *</label>
                <input type="text" name="art" value={formData.art} onChange={handleChange} placeholder="Ex: 10" className={getInputClass('art')} />
                {fieldErrors.art && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.art}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Paragraphe (PAR) *</label>
                <input type="text" name="par" value={formData.par} onChange={handleChange} placeholder="Ex: 20" className={getInputClass('par')} />
                {fieldErrors.par && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.par}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ligne (LIG) *</label>
                <input type="text" name="lig" value={formData.lig} onChange={handleChange} placeholder="Ex: 30" className={getInputClass('lig')} />
                {fieldErrors.lig && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.lig}
                  </span>
                )}
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Code d'imputation</label>
                <input readOnly type="text" name="code_imputation" value={formData.code_imputation} placeholder="Ex: 102030" className="w-full px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-800/70 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-mono font-bold outline-none cursor-not-allowed transition-all" />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Exercice budgétaire *</label>
                <input type="number" name="exercice_budgetaire" min="2026" max="2099" onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }} value={formData.exercice_budgetaire} onChange={handleChange} className={getInputClass('exercice_budgetaire')} />
                {fieldErrors.exercice_budgetaire && (
                  <span className="block text-xs font-semibold text-red-600 mt-1 flex items-center gap-1">
                    <AlertCircle size={12} /> {fieldErrors.exercice_budgetaire}
                  </span>
                )}
              </div>
            </div>
          </section>

          <div className="flex items-center justify-end gap-4 pt-4">
            <button
              type="button"
              onClick={() => {
                if (window.history.length > 1) {
                  navigate(-1);
                } else {
                  navigate('/bons-commande');
                }
              }}
              className="px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200"
            >
              Annuler / Retour
            </button>
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