import React from 'react';
import { CheckCircle, AlertTriangle, ChevronRight, Loader2 } from 'lucide-react';

export default function PreparationSummary({ 
  formData, 
  stepsStatus, 
  handlePasserCommission, 
  handleSave,
  saving
}) {
  const isValidee = ['Preparation_Validee', 'Commission_Ouverture', 'attribue'].includes(formData.statut);
  
  // Compter le nombre d'étapes incomplètes parmi les étapes obligatoires pour le passage
  const requiredSteps = ['infos', 'lots', 'budget', 'commission', 'seance', 'validation', 'docs', 'pubs'];
  const incompleteCount = requiredSteps.filter(s => !stepsStatus[s]).length;
  const isReady = incompleteCount === 0;

  return (
    <div className="mt-12 bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
      <div className="bg-slate-800 text-white p-6 text-center">
        <h3 className="text-2xl font-black tracking-wide">RÉSUMÉ FINAL DE LA PRÉPARATION</h3>
      </div>
      
      <div className="p-8">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mx-auto mb-10">
          <div className="space-y-3">
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.infos ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Informations générales</span>
              {stepsStatus.infos ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.lots ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">{formData.nombre_lots || 1} lot(s) estimé(s)</span>
              {stepsStatus.lots ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.budget ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Imputation budgétaire</span>
              {stepsStatus.budget ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.commission ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">{formData.membres_commission?.length || 0} membres de commission</span>
              {stepsStatus.commission ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
          </div>
          
          <div className="space-y-3">
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.seance ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Séance d'ouverture</span>
              {stepsStatus.seance ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.validation ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Validation globale</span>
              {stepsStatus.validation ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.docs ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Documents officiels</span>
              {stepsStatus.docs ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl border ${stepsStatus.pubs ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-200'}`}>
              <span className="font-bold text-slate-700">Publications Journaux & Portail</span>
              {stepsStatus.pubs ? <CheckCircle className="text-emerald-500" size={20} /> : <AlertTriangle className="text-amber-500" size={20} />}
            </div>
          </div>
        </div>

        <div className="flex flex-col items-center border-t border-slate-100 pt-8">
          {!isValidee && (
            <div className="mb-6 flex gap-4">
              <button
                type="button"
                onClick={handleSave}
                className="px-6 py-3 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors shadow-sm"
              >
                Sauvegarder en brouillon
              </button>
            </div>
          )}

          {isReady ? (
            <div className="text-center">
              <div className="inline-flex items-center gap-2 text-emerald-600 font-black mb-4 bg-emerald-50 px-4 py-1.5 rounded-full border border-emerald-200">
                <CheckCircle size={18} /> Préparation complète
              </div>
              <br/>
              <button
                type="button"
                onClick={handlePasserCommission}
                disabled={saving}
                className="px-10 py-5 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-black text-lg rounded-2xl hover:from-emerald-700 hover:to-emerald-600 transition-all shadow-xl hover:shadow-emerald-500/30 flex items-center gap-3 transform hover:-translate-y-1 group disabled:opacity-75 disabled:cursor-wait"
              >
                <span className="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full group-hover:scale-110 transition-transform">
                  {saving ? <Loader2 size={20} className="animate-spin" /> : <CheckCircle size={20} />}
                </span>
                {saving ? 'Passage en cours...' : 'PASSER À L\'OUVERTURE DES PLIS ET ANALYSE'}
                {!saving && <ChevronRight size={24} className="group-hover:translate-x-1 transition-transform" />}
              </button>
            </div>
          ) : (
            <div className="text-center">
              <div className="inline-flex items-center gap-2 text-amber-600 font-bold mb-4 bg-amber-50 px-4 py-1.5 rounded-full border border-amber-200">
                <AlertTriangle size={18} /> {incompleteCount} élément(s) doivent encore être complété(s)
              </div>
              <br/>
              <button
                type="button"
                disabled
                className="px-10 py-5 bg-slate-200 text-slate-400 font-black text-lg rounded-2xl flex items-center gap-3 cursor-not-allowed mx-auto"
              >
                PASSER À L'OUVERTURE DES PLIS ET ANALYSE
                <ChevronRight size={24} />
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
