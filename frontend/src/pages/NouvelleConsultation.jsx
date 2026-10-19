import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Save, ArrowLeft, CheckCircle2, AlertCircle, FileText, Calculator, Loader2, BookOpen } from 'lucide-react';
import LigneBudgetaireSelector from '../components/LigneBudgetaireSelector';
import TopNavButtons from '../components/TopNavButtons';

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
    type_budget: 'Investissement',
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

  // Gestion du choix depuis la nomenclature officielle
  const handleNomenclatureSelect = (ligne) => {
    setFormData((prev) => {
      const art = ligne.article || prev.art || '415';
      const par = ligne.paragraphe || prev.par || '';
      const lig = ligne.ligne || prev.lig || '';
      const code = ligne.code_imputation || `${art}${par}${lig}`;
      const intitule = ligne.intitule || prev.intitule;

      return {
        ...prev,
        type_budget: ligne.type_budget || prev.type_budget,
        art: art,
        par: par,
        lig: lig,
        code_imputation: code,
        intitule: intitule,
        type_prestation: intitule,
      };
    });

    setFieldErrors((prev) => ({
      ...prev,
      art: null,
      par: null,
      lig: null,
      intitule: null,
      type_budget: null,
    }));
  };

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
      ...(formData.mode_engagement === 'BC' || formData.mode_engagement === 'Convention' ? [{ name: 'numero_bc', label: formData.mode_engagement === 'Convention' ? 'Numéro de convention' : 'Numéro de bon de commande' }] : []),
      { name: 'date_limite_devis', label: 'Date limite de réception des devis' },
      { name: 'heure_limite_devis', label: 'Heure limite' },
      { name: 'lieu_execution', label: "Lieu d'exécution" },
      { name: 'annee', label: 'Année' },
      { name: 'date_consultation', label: 'Date de consultation' },
      { name: 'objet_consultation', label: 'Objet de la consultation' },
      { name: 'intitule', label: 'Intitulé (Nature de la prestation)' },
      { name: 'delai_execution', label: "Délai d'exécution" },
      ...(formData.mode_engagement !== 'AO' ? [
        { name: 'art', label: 'Article (ART)' },
        { name: 'par', label: 'Paragraphe (PAR)' },
        { name: 'lig', label: 'Ligne (LIG)' },
        { name: 'exercice_budgetaire', label: 'Exercice budgétaire' },
      ] : []),
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
        } else if (formData.mode_engagement === 'Convention') {
          navigate('/convention', { state: { autoSelectId: created?.id, createdConsultation: created } });
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

  const getInputClass = (fieldName) => `w-full px-4 py-3 rounded-xl bg-white border ${fieldErrors[fieldName]
      ? 'border-red-500 bg-red-50/20 text-red-900 focus:ring-red-200'
      : 'border-slate-200 text-slate-800 focus:border-[#087F5B] focus:ring-2 focus:ring-[#087F5B]/20'
    } outline-none transition-all`;

  return (
    <div className="p-4 sm:p-6 lg:p-8 bg-[#F5F7F5] min-h-screen text-[#172033]">
      <div className="w-full max-w-[1500px] mx-auto space-y-6">
        <header className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => {
                if (window.history.length > 1) {
                  navigate(-1);
                } else {
                  navigate('/bons-commande');
                }
              }}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer"
              title="Retour à la plateforme"
            >
              <ArrowLeft size={16} />
              <span>Retour</span>
            </button>
            <div className="h-6 w-px bg-slate-200 hidden sm:block" />
            <div>
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-bold text-[#087F5B] bg-[#E7F5EF] px-2 py-0.5 rounded-full uppercase tracking-wider">DRCA · RSK</span>
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nouvel engagement</span>
              </div>
              <h1 className="mt-1 text-2xl sm:text-3xl font-extrabold text-[#172033] tracking-tight">
                Nouvelle Consultation
              </h1>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <TopNavButtons current="dashboard" />
          </div>
        </header>

        {error && (
          <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600 font-semibold shadow-xs">
            <AlertCircle size={20} />
            <p>{error}</p>
          </div>
        )}

        {success && (
          <div className="p-4 bg-[#E7F5EF] border border-[#087F5B]/30 rounded-xl flex items-center gap-3 text-[#087F5B] font-semibold shadow-xs">
            <CheckCircle2 size={20} />
            <p>Consultation créée avec succès ! Redirection en cours...</p>
          </div>
        )}

        <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 px-4 py-2 rounded-xl w-fit shadow-xs">
          <span className="text-red-600 font-extrabold text-sm">*</span> : Les champs obligatoires
        </div>

        <form onSubmit={handleSubmit} className="space-y-6" noValidate>

          {/* BLOC 1 : Informations Générales */}
          <section className="bg-white p-5 sm:p-7 rounded-2xl shadow-sm border border-slate-200">
            <div className="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
              <div className="p-2 bg-[#E7F5EF] text-[#087F5B] rounded-xl">
                <FileText size={20} />
              </div>
              <h2 className="text-lg font-bold text-[#172033]">Informations Générales</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-2">
                  Mode d'engagement <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <select name="mode_engagement" value={formData.mode_engagement} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-[#087F5B]/20 focus:border-[#087F5B] outline-none transition-all text-slate-800 font-semibold cursor-pointer">
                  <option value="BC">Bon de commande</option>
                  <option value="Convention">Convention</option>
                  <option value="AO">Appel d'offres</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Numéro de consultation <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="numero_consultation" value={formData.numero_consultation} onChange={handleChange} placeholder="Ex: 04/2026/DRCA-RSK" className={getInputClass('numero_consultation')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Numéro de la décision de la commission d'ouverture <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="numero_decision" value={formData.numero_decision} onChange={handleChange} placeholder="Ex: 06/2026/DRCA-RSK" className={getInputClass('numero_decision')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {formData.mode_engagement === 'AO' ? 'Numéro de marché' : formData.mode_engagement === 'Convention' ? 'Numéro de convention' : 'Numéro de bon de commande'}{' '}
                  {(formData.mode_engagement === 'BC' || formData.mode_engagement === 'Convention') && <span className="text-red-600 font-bold ml-0.5">*</span>}
                </label>
                <input
                  type="text"
                  name="numero_bc"
                  value={formData.numero_bc}
                  onChange={handleChange}
                  placeholder={formData.mode_engagement === 'AO' ? 'Ex: M-05/2026/DRCA-RSK' : formData.mode_engagement === 'Convention' ? 'Ex: CONV-05/2026/DRCA-RSK' : 'Ex: BC CONS-2026-0001'}
                  className={getInputClass('numero_bc')}
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date limite de réception des devis <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="date" name="date_limite_devis" min="2026-01-01" max="2099-12-31" value={formData.date_limite_devis} onChange={handleChange} className={getInputClass('date_limite_devis')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Heure limite <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="heure_limite_devis" value={formData.heure_limite_devis} onChange={handleChange} placeholder="Ex: 10:00" className={getInputClass('heure_limite_devis')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Lieu d'exécution <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="lieu_execution" value={formData.lieu_execution} onChange={handleChange} placeholder="Ex: REGION DE RABAT SALE KENITRA" className={getInputClass('lieu_execution')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Année <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="number" name="annee" min="2026" max="2099" onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }} value={formData.annee} onChange={handleChange} className={getInputClass('annee')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de consultation <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="date" name="date_consultation" min="2026-01-01" max="2099-12-31" value={formData.date_consultation} onChange={handleChange} className={getInputClass('date_consultation')} />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Objet de la consultation <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="objet_consultation" value={formData.objet_consultation} onChange={handleChange} placeholder="Ex: Achat de matériel informatique..." className={getInputClass('objet_consultation')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Catégorie <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <select name="categorie" value={formData.categorie} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Travaux">Travaux</option>
                  <option value="Fournitures">Fournitures</option>
                  <option value="Services">Services</option>
                </select>
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Intitulé (Nature de la prestation) <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="text" name="intitule" value={formData.intitule} onChange={(e) => {
                  const val = e.target.value;
                  setFormData((prev) => ({ ...prev, intitule: val, type_prestation: val }));
                  if (fieldErrors.intitule) {
                    setFieldErrors((prev) => ({ ...prev, intitule: null }));
                  }
                }} placeholder="Ex: Prestation de même nature / Achat de matériel technique..." className={getInputClass('intitule')} />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Type de budget <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <select name="type_budget" value={formData.type_budget} onChange={handleChange} className="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white">
                  <option value="Investissement">Investissement</option>
                  <option value="Fonctionnement">Fonctionnement</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Délai d'exécution (en jours) <span className="text-red-600 font-bold ml-0.5">*</span>
                </label>
                <input type="number" min="1" name="delai_execution" value={formData.delai_execution} onChange={handleChange} className={getInputClass('delai_execution')} />
              </div>
            </div>
          </section>

          {/* L'imputation des AO est renseignée au niveau des lots. */}
          {formData.mode_engagement !== 'AO' && (
            <section className="bg-white dark:bg-gray-800 p-6 sm:p-8 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 space-y-6">
              <div className="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div className="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-lg">
                  <Calculator size={20} />
                </div>
                <div>
                  <h2 className="text-xl font-bold text-gray-800 dark:text-white">Imputation Budgétaires</h2>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    Sélectionnez une ligne dans la nomenclature officielle ou renseignez manuellement les codes.
                  </p>
                </div>
              </div>

              {/* Sélecteur intelligent depuis la nomenclature */}
              <div className="bg-slate-50/80 dark:bg-gray-900/50 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700">
                <LigneBudgetaireSelector
                  selectedCode={formData.code_imputation}
                  selectedArt={formData.art}
                  selectedPar={formData.par}
                  selectedLig={formData.lig}
                  typeBudget={formData.type_budget}
                  onSelect={handleNomenclatureSelect}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Article (ART) <span className="text-red-600 font-bold ml-0.5">*</span>
                  </label>
                  <input type="text" name="art" value={formData.art} onChange={handleChange} placeholder="Ex: 415" className={getInputClass('art')} />
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Paragraphe (PAR) <span className="text-red-600 font-bold ml-0.5">*</span>
                  </label>
                  <input type="text" name="par" value={formData.par} onChange={handleChange} placeholder="Ex: 10" className={getInputClass('par')} />
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Ligne (LIG) <span className="text-red-600 font-bold ml-0.5">*</span>
                  </label>
                  <input type="text" name="lig" value={formData.lig} onChange={handleChange} placeholder="Ex: 10" className={getInputClass('lig')} />
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Code d'imputation</label>
                  <input readOnly type="text" name="code_imputation" value={formData.code_imputation} placeholder="Ex: 4151010" className="w-full px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-800/70 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-mono font-bold outline-none cursor-not-allowed transition-all" />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Exercice budgétaire <span className="text-red-600 font-bold ml-0.5">*</span>
                  </label>
                  <input type="number" name="exercice_budgetaire" min="2026" max="2099" onInput={(e) => { if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4); }} value={formData.exercice_budgetaire} onChange={handleChange} className={getInputClass('exercice_budgetaire')} />
                </div>
              </div>
            </section>
          )}

          <div className="flex items-center justify-end gap-3 pt-4">
            <button
              type="button"
              onClick={() => {
                if (window.history.length > 1) {
                  navigate(-1);
                } else {
                  navigate('/bons-commande');
                }
              }}
              className="px-6 py-3 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl shadow-xs transition cursor-pointer"
            >
              Annuler / Retour
            </button>
            <button
              type="submit"
              disabled={loading || success}
              className="px-7 py-3 bg-[#087F5B] hover:bg-[#06694A] text-white font-bold rounded-xl shadow-md shadow-[#087F5B]/25 transition transform active:scale-98 flex items-center gap-2.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none cursor-pointer"
            >
              {loading ? (
                <>
                  <Loader2 size={18} className="animate-spin" />
                  <span>Création en cours...</span>
                </>
              ) : (
                <>
                  <Save size={18} />
                  <span>Enregistrer la consultation</span>
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
