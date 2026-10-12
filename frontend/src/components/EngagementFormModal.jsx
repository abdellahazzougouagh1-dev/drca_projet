import React, { useState, useEffect } from 'react';
import { FileText, Save, AlertTriangle, X, CheckCircle, Info } from 'lucide-react';

export const EngagementFormModal = ({
  isOpen,
  onClose,
  docType,
  docTitle,
  initialData,
  onSave,
  onPreview,
}) => {
  const [formData, setFormData] = useState({});
  const [missingFields, setMissingFields] = useState([]);
  const [showWarning, setShowWarning] = useState(false);

  useEffect(() => {
    if (initialData) {
      setFormData({ ...initialData });
    }
  }, [initialData]);

  if (!isOpen) return null;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const getRequiredFields = () => {
    switch (docType) {
      case 'acte-engagement':
        return [
          { key: 'titulaire', label: 'Nom du titulaire / Société' },
          { key: 'ice', label: 'ICE (Identifiant Commun d\'Entreprise)' },
          { key: 'montant', label: 'Montant Total TTC' },
          { key: 'objet_marche', label: 'Objet du marché' },
        ];
      case 'marche-definitif':
        return [
          { key: 'num_marche', label: 'Numéro du Marché' },
          { key: 'titulaire', label: 'Titulaire du marché' },
          { key: 'montant', label: 'Montant du marché' },
          { key: 'objet_marche', label: 'Objet du marché' },
        ];
      case 'decision-approbation':
        return [
          { key: 'num_decision', label: 'Numéro de la Décision d\'approbation' },
          { key: 'date_approbation', label: 'Date d\'approbation' },
          { key: 'num_marche', label: 'Numéro du marché' },
        ];
      case 'os-commencement':
        return [
          { key: 'os_numero', label: 'Numéro de l\'Ordre de Service (OS)' },
          { key: 'os_date_effet', label: 'Date d\'effet du commencement' },
          { key: 'num_marche', label: 'Numéro du marché' },
        ];
      default:
        return [];
    }
  };

  const validate = () => {
    const req = getRequiredFields();
    const missing = req.filter((f) => !formData[f.key] || String(formData[f.key]).trim() === '');
    setMissingFields(missing);
    return missing.length === 0;
  };

  const handleSaveSubmit = () => {
    const isValid = validate();
    if (!isValid) {
      setShowWarning(true);
      return;
    }
    setShowWarning(false);
    onSave(formData);
  };

  const handlePreviewSubmit = () => {
    const isValid = validate();
    if (!isValid) {
      setShowWarning(true);
      return;
    }
    setShowWarning(false);
    onSave(formData);
    onPreview(docType, formData);
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm grid place-items-center p-4 overflow-y-auto animate-fade-in">
      <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Modal Header */}
        <div className="px-6 py-4 bg-[#1e3a8a] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <FileText size={22} className="text-cyan-300" />
            <div>
              <h3 className="font-extrabold text-base">{docTitle}</h3>
              <p className="text-[11px] text-cyan-100 font-mono">Formulaire de saisie & complétion</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-all"
          >
            <X size={20} />
          </button>
        </div>

        {/* Warning missing fields alert */}
        {showWarning && missingFields.length > 0 && (
          <div className="m-6 mb-0 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3 text-xs text-amber-900">
            <AlertTriangle size={20} className="text-amber-600 shrink-0 mt-0.5" />
            <div>
              <p className="font-extrabold text-amber-900 text-sm">⚠️ Document incomplet</p>
              <p className="mt-1">Veuillez renseigner les informations obligatoires suivantes avant de continuer :</p>
              <ul className="list-disc list-inside mt-1 font-bold text-amber-800 space-y-0.5">
                {missingFields.map((f) => (
                  <li key={f.key}>{f.label}</li>
                ))}
              </ul>
            </div>
          </div>
        )}

        {/* Form Fields */}
        <div className="p-6 space-y-4 max-h-[60vh] overflow-y-auto text-xs">
          
          <div className="p-3 bg-blue-50/60 border border-blue-100 rounded-xl flex items-center gap-2 text-[11px] text-blue-900">
            <Info size={16} className="text-blue-600 shrink-0" />
            <span>
              Les données issues de la <strong>Consultation</strong> sont automatiquement préremplies. Vous pouvez ajuster ou compléter les champs restants.
            </span>
          </div>

          {docType === 'acte-engagement' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">N° AOO / Consultation</label>
                <input
                  type="text"
                  name="num_aoo"
                  value={formData.num_aoo || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-mono font-bold"
                  readOnly
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Titulaire engagé *</label>
                <input
                  type="text"
                  name="titulaire"
                  value={formData.titulaire || ''}
                  onChange={handleChange}
                  placeholder="Raison sociale..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">ICE *</label>
                <input
                  type="text"
                  name="ice"
                  value={formData.ice || ''}
                  onChange={handleChange}
                  placeholder="001234567..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Représentant / Gérant</label>
                <input
                  type="text"
                  name="representant"
                  value={formData.representant || ''}
                  onChange={handleChange}
                  placeholder="Nom & Prénom..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">RC (Registre Commerce)</label>
                <input
                  type="text"
                  name="rc"
                  value={formData.rc || ''}
                  onChange={handleChange}
                  placeholder="N° RC..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">CNSS</label>
                <input
                  type="text"
                  name="cnss"
                  value={formData.cnss || ''}
                  onChange={handleChange}
                  placeholder="N° Affiliation..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Montant Total TTC (DH) *</label>
                <input
                  type="number"
                  step="0.01"
                  name="montant"
                  value={formData.montant || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">RIB (Relevé bancaire)</label>
                <input
                  type="text"
                  name="rib"
                  value={formData.rib || ''}
                  onChange={handleChange}
                  placeholder="24 chiffres..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div className="md:col-span-2">
                <label className="block font-bold text-slate-700 mb-1">Objet du marché *</label>
                <textarea
                  rows="2"
                  name="objet_marche"
                  value={formData.objet_marche || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none resize-none"
                />
              </div>
            </div>
          )}

          {docType === 'marche-definitif' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">Numéro du Marché *</label>
                <input
                  type="text"
                  name="num_marche"
                  value={formData.num_marche || ''}
                  onChange={handleChange}
                  placeholder="Ex: 07/2026/DRCA-RSK"
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Exercice budgétaire</label>
                <input
                  type="text"
                  name="exercice"
                  value={formData.exercice || ''}
                  onChange={handleChange}
                  placeholder="2026"
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Titulaire du marché *</label>
                <input
                  type="text"
                  name="titulaire"
                  value={formData.titulaire || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Montant du Marché TTC (DH) *</label>
                <input
                  type="number"
                  step="0.01"
                  name="montant"
                  value={formData.montant || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Date de signature du marché</label>
                <input
                  type="date"
                  name="date_signature"
                  value={formData.date_signature || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Délai d'exécution (Mois)</label>
                <input
                  type="number"
                  name="delai_execution"
                  value={formData.delai_execution || '12'}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono"
                />
              </div>
              <div className="md:col-span-2">
                <label className="block font-bold text-slate-700 mb-1">Objet du marché *</label>
                <textarea
                  rows="2"
                  name="objet_marche"
                  value={formData.objet_marche || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none resize-none"
                />
              </div>
            </div>
          )}

          {docType === 'decision-approbation' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">N° Décision d'approbation *</label>
                <input
                  type="text"
                  name="num_decision"
                  value={formData.num_decision || ''}
                  onChange={handleChange}
                  placeholder="Ex: 01/2026/M06"
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Date d'approbation *</label>
                <input
                  type="date"
                  name="date_approbation"
                  value={formData.date_approbation || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Numéro du Marché *</label>
                <input
                  type="text"
                  name="num_marche"
                  value={formData.num_marche || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 font-mono font-bold"
                  readOnly
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Caution Définitive (3% DH)</label>
                <input
                  type="number"
                  step="0.01"
                  name="montant_caution_definitive"
                  value={formData.montant_caution_definitive || (parseFloat(formData.montant || 0) * 0.03).toFixed(2)}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold text-emerald-800 bg-emerald-50/50"
                />
              </div>
            </div>
          )}

          {docType === 'os-commencement' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">N° Ordre de Service (OS) *</label>
                <input
                  type="text"
                  name="os_numero"
                  value={formData.os_numero || ''}
                  onChange={handleChange}
                  placeholder="Ex: 03/2026/M10"
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none font-mono font-bold"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Date d'effet du commencement *</label>
                <input
                  type="date"
                  name="os_date_effet"
                  value={formData.os_date_effet || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Date de signature de l'OS</label>
                <input
                  type="date"
                  name="os_date_signature"
                  value={formData.os_date_signature || ''}
                  onChange={handleChange}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
              <div>
                <label className="block font-bold text-slate-700 mb-1">Agent chargé du suivi</label>
                <input
                  type="text"
                  name="agent_suivi"
                  value={formData.agent_suivi || ''}
                  onChange={handleChange}
                  placeholder="Nom du responsable ONCA..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-600 outline-none"
                />
              </div>
            </div>
          )}

        </div>

        {/* Modal Footer Actions */}
        <div className="px-6 py-4 bg-slate-100 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl transition-all"
          >
            Annuler
          </button>
          
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handleSaveSubmit}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-all flex items-center gap-1.5"
            >
              <Save size={15} /> Enregistrer
            </button>
            <button
              type="button"
              onClick={handlePreviewSubmit}
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5"
            >
              <CheckCircle size={15} /> Enregistrer & Prévisualiser
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
