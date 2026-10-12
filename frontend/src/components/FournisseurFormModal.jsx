import React, { useState, useEffect } from 'react';
import { X, Save, Building2, MapPin, User, CreditCard, AlertCircle } from 'lucide-react';
import api from '../api'; // Ajustez le chemin selon votre structure

const FournisseurFormModal = ({ isOpen, onClose, fournisseurToEdit, onSuccess }) => {
  const [formData, setFormData] = useState({
    raison_sociale: '',
    ice: '',
    identifiant_fiscal: '',
    registre_commerce: '',
    patente: '',
    adresse: '',
    ville: '',
    telephone: '',
    email: '',
    domaine_activite: '',
    representant_legal: '',
    qualite_representant: '',
    cnss: '',
    banque: '',
    agence_bancaire: '',
    rib: '',
    titulaire_compte: ''
  });

  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (fournisseurToEdit) {
      setFormData({
        raison_sociale: fournisseurToEdit.raison_sociale || '',
        ice: fournisseurToEdit.ice || '',
        identifiant_fiscal: fournisseurToEdit.identifiant_fiscal || '',
        registre_commerce: fournisseurToEdit.registre_commerce || '',
        patente: fournisseurToEdit.patente || '',
        adresse: fournisseurToEdit.adresse || '',
        ville: fournisseurToEdit.ville || '',
        telephone: fournisseurToEdit.telephone || '',
        email: fournisseurToEdit.email || '',
        domaine_activite: fournisseurToEdit.domaine_activite || '',
        representant_legal: fournisseurToEdit.representant_legal || '',
        qualite_representant: fournisseurToEdit.qualite_representant || '',
        cnss: fournisseurToEdit.cnss || '',
        banque: fournisseurToEdit.banque || '',
        agence_bancaire: fournisseurToEdit.agence_bancaire || '',
        rib: fournisseurToEdit.rib || '',
        titulaire_compte: fournisseurToEdit.titulaire_compte || ''
      });
    } else {
      resetForm();
    }
  }, [fournisseurToEdit, isOpen]);

  const resetForm = () => {
    setFormData({
      raison_sociale: '',
      ice: '',
      identifiant_fiscal: '',
      registre_commerce: '',
      patente: '',
      adresse: '',
      ville: '',
      telephone: '',
      email: '',
      domaine_activite: '',
      representant_legal: '',
      qualite_representant: '',
      cnss: '',
      banque: '',
      agence_bancaire: '',
      rib: '',
      titulaire_compte: ''
    });
    setErrors({});
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    
    // Validation du RIB en temps réel (uniquement des chiffres)
    if (name === 'rib') {
      const numericValue = value.replace(/\D/g, '');
      if (numericValue.length <= 24) {
        setFormData(prev => ({ ...prev, [name]: numericValue }));
      }
      return;
    }

    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error when typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    let newErrors = {};

    if (!formData.raison_sociale.trim()) newErrors.raison_sociale = "La raison sociale est requise";
    if (!formData.ice.trim()) {
      newErrors.ice = "L'ICE est requis";
    } else if (!/^\d{15}$/.test(formData.ice.trim())) {
        // En général l'ICE marocain fait 15 chiffres, on peut relâcher ou durcir si besoin
        // newErrors.ice = "L'ICE doit contenir 15 chiffres";
    }

    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = "Email invalide";
    }

    if (formData.rib && formData.rib.length !== 24) {
      newErrors.rib = "Le RIB doit contenir exactement 24 chiffres";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsSubmitting(true);
    try {
      if (fournisseurToEdit && fournisseurToEdit.id) {
        await api.put(`/fournisseurs/${fournisseurToEdit.id}`, formData);
      } else {
        await api.post('/fournisseurs', formData);
      }
      onSuccess && onSuccess();
      onClose();
      resetForm();
    } catch (error) {
      console.error("Erreur d'enregistrement", error);
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ general: "Une erreur est survenue lors de l'enregistrement." });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  const inputClass = (errorName) => `w-full px-4 py-2.5 rounded-xl border ${errors[errorName] ? 'border-red-500 focus:border-red-500 bg-red-50' : 'border-slate-300 focus:border-blue-500 bg-slate-50 focus:bg-white'} outline-none transition-all`;

  return (
    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-slate-100">
          <div>
            <h2 className="text-xl font-bold text-slate-800">
              {fournisseurToEdit ? 'Modifier le Fournisseur' : 'Nouveau Fournisseur'}
            </h2>
            <p className="text-sm text-slate-500 mt-1">Saisissez les informations légales et coordonnées.</p>
          </div>
          <button onClick={onClose} className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
            <X size={24} />
          </button>
        </div>

        {/* Body */}
        <div className="p-6 overflow-y-auto custom-scrollbar flex-1">
          {errors.general && (
            <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg flex items-center gap-3">
              <AlertCircle size={20} />
              <p>{errors.general}</p>
            </div>
          )}

          <form id="fournisseur-form" onSubmit={handleSubmit} className="space-y-8">
            
            {/* Section A: Informations Générales */}
            <section className="bg-blue-50/50 p-5 rounded-2xl border border-blue-100">
              <div className="flex items-center gap-2 mb-4 text-blue-800 font-bold">
                <Building2 size={20} />
                <h3>A. Informations Générales & Légales</h3>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Raison Sociale <span className="text-red-500">*</span></label>
                  <input type="text" name="raison_sociale" value={formData.raison_sociale} onChange={handleChange} className={inputClass('raison_sociale')} placeholder="Ex: ONCA SARL" />
                  {errors.raison_sociale && <p className="text-red-500 text-xs mt-1">{errors.raison_sociale}</p>}
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">ICE <span className="text-red-500">*</span></label>
                  <input type="text" name="ice" value={formData.ice} onChange={handleChange} className={inputClass('ice')} placeholder="15 chiffres uniques" />
                  {errors.ice && <p className="text-red-500 text-xs mt-1">{errors.ice}</p>}
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Identifiant Fiscal (IF)</label>
                  <input type="text" name="identifiant_fiscal" value={formData.identifiant_fiscal} onChange={handleChange} className={inputClass('identifiant_fiscal')} />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Registre de Commerce (RC)</label>
                  <input type="text" name="registre_commerce" value={formData.registre_commerce} onChange={handleChange} className={inputClass('registre_commerce')} />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Patente</label>
                  <input type="text" name="patente" value={formData.patente} onChange={handleChange} className={inputClass('patente')} />
                </div>
              </div>
            </section>

            {/* Section B: Coordonnées */}
            <section className="p-5 rounded-2xl border border-slate-200">
              <div className="flex items-center gap-2 mb-4 text-slate-800 font-bold">
                <MapPin size={20} className="text-slate-500" />
                <h3>B. Coordonnées</h3>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Adresse</label>
                  <textarea name="adresse" value={formData.adresse} onChange={handleChange} rows="2" className={inputClass('adresse')}></textarea>
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Ville</label>
                  <input type="text" name="ville" value={formData.ville} onChange={handleChange} className={inputClass('ville')} />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Téléphone</label>
                  <input type="tel" name="telephone" value={formData.telephone} onChange={handleChange} className={inputClass('telephone')} />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                  <input type="email" name="email" value={formData.email} onChange={handleChange} className={inputClass('email')} placeholder="contact@societe.com" />
                  {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Domaine d'activité</label>
                  <input type="text" name="domaine_activite" value={formData.domaine_activite} onChange={handleChange} className={inputClass('domaine_activite')} placeholder="Ex: Fournitures informatiques" />
                </div>
              </div>
            </section>

            {/* Section C: Administration & Signature */}
            <section className="bg-amber-50/50 p-5 rounded-2xl border border-amber-100">
              <div className="flex items-center gap-2 mb-4 text-amber-800 font-bold">
                <User size={20} />
                <h3>C. Informations Administratives & Signature</h3>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Nom du Représentant Légal</label>
                  <input type="text" name="representant_legal" value={formData.representant_legal} onChange={handleChange} className={inputClass('representant_legal')} placeholder="Nom et Prénom" />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Qualité du Responsable</label>
                  <input type="text" name="qualite_representant" value={formData.qualite_representant} onChange={handleChange} className={inputClass('qualite_representant')} placeholder="Ex: Gérant, PDG..." />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">N° CNSS</label>
                  <input type="text" name="cnss" value={formData.cnss} onChange={handleChange} className={inputClass('cnss')} />
                </div>
              </div>
            </section>

            {/* Section D: Banque */}
            <section className="bg-emerald-50/50 p-5 rounded-2xl border border-emerald-100">
              <div className="flex items-center gap-2 mb-4 text-emerald-800 font-bold">
                <CreditCard size={20} />
                <h3>D. Informations Bancaires</h3>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>Compte N° / RIB</span>
                    <span className="text-xs text-slate-500 font-normal">{formData.rib.length}/24 chiffres</span>
                  </label>
                  <input 
                    type="text" 
                    name="rib" 
                    value={formData.rib} 
                    onChange={handleChange} 
                    className={`${inputClass('rib')} tracking-widest font-mono text-center text-lg`} 
                    placeholder="000 000 0000000000000000 00" 
                  />
                  {errors.rib && <p className="text-red-500 text-xs mt-1 text-center">{errors.rib}</p>}
                </div>

                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Banque</label>
                  <input type="text" name="banque" value={formData.banque} onChange={handleChange} className={inputClass('banque')} placeholder="Ex: Attijariwafa Bank" />
                </div>
                
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Agence</label>
                  <input type="text" name="agence_bancaire" value={formData.agence_bancaire} onChange={handleChange} className={inputClass('agence_bancaire')} placeholder="Ex: Agadir Centre" />
                </div>
                
                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold text-slate-700 mb-1">Nom du titulaire du compte à la Banque</label>
                  <input type="text" name="titulaire_compte" value={formData.titulaire_compte} onChange={handleChange} className={inputClass('titulaire_compte')} placeholder="Généralement la Raison Sociale" />
                </div>
              </div>
            </section>
            
          </form>
        </div>

        {/* Footer */}
        <div className="p-6 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end gap-3">
          <button 
            type="button" 
            onClick={onClose} 
            className="px-6 py-2.5 rounded-xl font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-colors"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            form="fournisseur-form"
            disabled={isSubmitting}
            className="px-6 py-2.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors shadow-md shadow-blue-600/20 flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
          >
            {isSubmitting ? <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> : <Save size={20} />}
            {isSubmitting ? 'Enregistrement...' : 'Enregistrer le fournisseur'}
          </button>
        </div>

      </div>
    </div>
  );
};

export default FournisseurFormModal;
