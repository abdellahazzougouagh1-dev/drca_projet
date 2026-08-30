import React from 'react';
import SectionCard from './SectionCard';
import { Coins, CheckCircle } from 'lucide-react';

export default function BudgetCard({ formData, handleChange, isReadOnly, status }) {
  return (
    <SectionCard title="Imputation Budgétaire" status={status} number="03">
      <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6">
        <div className="flex items-center gap-3 mb-6 border-b border-slate-200 pb-4">
          <div className="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
            <Coins size={20} />
          </div>
          <h4 className="font-extrabold text-slate-800 text-lg">Détails de l'imputation</h4>
          {status === 'complet' && (
            <span className="ml-auto text-emerald-600 font-bold flex items-center gap-2 text-sm">
              <CheckCircle size={16} /> Imputation complète
            </span>
          )}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
          <div>
            <label className="block text-sm font-bold text-slate-600 mb-2">Article</label>
            <input
              type="text"
              name="art"
              value={formData.art || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              placeholder="Ex: 10"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 disabled:opacity-70"
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-600 mb-2">Paragraphe</label>
            <input
              type="text"
              name="par"
              value={formData.par || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              placeholder="Ex: 20"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 disabled:opacity-70"
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-600 mb-2">Ligne</label>
            <input
              type="text"
              name="lig"
              value={formData.lig || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              placeholder="Ex: 30"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 disabled:opacity-70"
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-600 mb-2">Imputation (Optionnel)</label>
            <input
              type="text"
              name="imputation"
              value={formData.imputation || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              placeholder="..."
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 disabled:opacity-70"
            />
          </div>
        </div>
      </div>
    </SectionCard>
  );
}
