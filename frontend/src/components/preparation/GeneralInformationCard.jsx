import React from 'react';
import SectionCard from './SectionCard';

export default function GeneralInformationCard({ formData, handleChange, isReadOnly, status }) {
  return (
    <SectionCard title="Informations Générales" status={status} number="01" defaultOpen={true}>
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <div className="space-y-6">
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">N° AOO *</label>
            <input
              type="text"
              name="num_aoo"
              value={formData.num_aoo || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              placeholder="Ex: 08/2026/DRCA-RSK"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono font-bold text-slate-800 disabled:opacity-70"
            />
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-bold text-slate-700 mb-2">Nombre de lots *</label>
              <input
                type="number"
                name="nombre_lots"
                value={formData.nombre_lots || 1}
                onChange={handleChange}
                disabled={isReadOnly}
                min="1"
                max="50"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 disabled:opacity-70"
              />
            </div>
            <div>
              <label className="block text-sm font-bold text-slate-700 mb-2">Date de préparation</label>
              <input
                type="date"
                name="date_preparation"
                value={formData.date_preparation ? formData.date_preparation.substring(0, 10) : ''}
                onChange={handleChange}
                disabled={isReadOnly}
                className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 disabled:opacity-70"
              />
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">Objet (Français) *</label>
            <textarea
              name="objet"
              value={formData.objet || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              rows="3"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-medium text-slate-700 disabled:opacity-70"
              placeholder="Objet de l'appel d'offres en français..."
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2 font-arabic text-right w-full">الموضوع (عربي) *</label>
            <textarea
              name="objet_ar"
              value={formData.objet_ar || ''}
              onChange={handleChange}
              disabled={isReadOnly}
              dir="rtl"
              rows="3"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-arabic text-slate-700 disabled:opacity-70"
              placeholder="موضوع طلب العروض بالعربية..."
            />
          </div>
        </div>
      </div>
    </SectionCard>
  );
}
