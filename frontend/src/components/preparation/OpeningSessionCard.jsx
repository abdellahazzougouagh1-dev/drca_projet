import React from 'react';
import SectionCard from './SectionCard';

export default function OpeningSessionCard({ formData, handleChange, isReadOnly, status }) {
  return (
    <SectionCard title="Séance d'Ouverture" status={status} number="05">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label className="block text-sm font-bold text-slate-700 mb-2">Date d'ouverture *</label>
          <input
            type="date"
            name="date_ouverture"
            value={formData.date_ouverture || ''}
            onChange={handleChange}
            disabled={isReadOnly}
            className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 disabled:opacity-70"
          />
        </div>
        <div>
          <label className="block text-sm font-bold text-slate-700 mb-2">Heure d'ouverture *</label>
          <input
            type="time"
            name="heure_ouverture"
            value={formData.heure_ouverture || ''}
            onChange={handleChange}
            disabled={isReadOnly}
            className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 disabled:opacity-70"
          />
        </div>
        <div>
          <label className="block text-sm font-bold text-slate-700 mb-2">Lieu d'ouverture *</label>
          <input
            type="text"
            name="lieu_ouverture"
            value={formData.lieu_ouverture || ''}
            onChange={handleChange}
            disabled={isReadOnly}
            placeholder="Ex: Salle de réunion DRCA..."
            className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-medium text-slate-800 disabled:opacity-70"
          />
        </div>
      </div>
    </SectionCard>
  );
}
