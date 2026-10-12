import React from 'react';
import { FileText, CheckCircle } from 'lucide-react';

export default function PreparationHeader({ formData }) {
  const isValidee = ['Preparation_Validee', 'Commission_Ouverture', 'attribue'].includes(formData.statut);
  
  return (
    <div className="bg-white border border-slate-200 rounded-3xl p-8 mb-8 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
      <div>
        <div className="flex items-center gap-4 mb-3">
          <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center shadow-inner">
            <FileText size={24} />
          </div>
          <h1 className="text-3xl font-black text-slate-800">PRÉPARATION DE L'APPEL D'OFFRES</h1>
        </div>
        {formData.num_aoo && (
          <p className="text-lg text-slate-500 font-bold ml-16">
            AOO N° <span className="text-blue-600">{formData.num_aoo}</span>
          </p>
        )}
      </div>
      
      <div className="flex-shrink-0">
        {isValidee ? (
          <span className="bg-emerald-100 text-emerald-800 text-sm font-black px-6 py-3 rounded-full border-2 border-emerald-200 shadow-sm flex items-center gap-2">
            <CheckCircle size={18} /> PRÉPARATION VALIDÉE
          </span>
        ) : (
          <span className="bg-amber-100 text-amber-800 text-sm font-black px-6 py-3 rounded-full border-2 border-amber-200 shadow-sm">
            BROUILLON
          </span>
        )}
      </div>
    </div>
  );
}
