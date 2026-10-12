import React from 'react';
import SectionCard from './SectionCard';
import { Lock, Unlock, CheckCircle } from 'lucide-react';

export default function ValidationCard({ 
  formData, 
  status, 
  handleValidatePreparation, 
  handleUnlockPreparation, 
  saving 
}) {
  const isValidee = ['Preparation_Validee', 'Commission_Ouverture', 'attribue'].includes(formData.statut);

  return (
    <SectionCard title="Contrôle et Validation de la Préparation" status={status} number="06" defaultOpen={true}>
      <div className="bg-slate-50 rounded-2xl p-8 border border-slate-200 text-center">
        {isValidee ? (
          <div className="flex flex-col items-center">
            <div className="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mb-6">
              <CheckCircle size={40} className="text-emerald-600" />
            </div>
            <h4 className="text-2xl font-black text-slate-800 mb-2">Préparation Validée</h4>
            <p className="text-slate-600 mb-8 max-w-lg mx-auto font-medium">
              Toutes les informations fondamentales de cet appel d'offres ont été vérifiées et verrouillées. Vous pouvez générer les documents officiels.
            </p>
            <button
              type="button"
              onClick={handleUnlockPreparation}
              disabled={saving}
              className="px-6 py-3 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-100 hover:border-slate-300 transition-all flex items-center gap-2"
            >
              <Unlock size={18} /> Déverrouiller (Retour en brouillon)
            </button>
          </div>
        ) : (
          <div className="flex flex-col items-center">
            <div className="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mb-6">
              <Lock size={40} className="text-amber-600" />
            </div>
            <h4 className="text-2xl font-black text-slate-800 mb-2">Verrouiller la Préparation</h4>
            <p className="text-slate-600 mb-8 max-w-lg mx-auto font-medium">
              Vérifiez que toutes les informations sont correctes. Une fois validée, la préparation sera verrouillée et vous pourrez générer les documents officiels.
            </p>
            <button
              type="button"
              onClick={handleValidatePreparation}
              disabled={saving}
              className="px-10 py-4 bg-emerald-600 text-white font-black text-lg rounded-2xl hover:bg-emerald-700 transition-all shadow-lg hover:shadow-emerald-500/25 flex items-center gap-3 transform hover:-translate-y-1"
            >
              <CheckCircle size={24} /> VALIDER LA PRÉPARATION
            </button>
          </div>
        )}
      </div>
    </SectionCard>
  );
}
