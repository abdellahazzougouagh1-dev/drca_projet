import React from 'react';
import SectionCard from './SectionCard';
import { Download, FileText, Award } from 'lucide-react';

export default function DocumentsCard({
  status,
  handleGenerateDocument,
  generatingDoc
}) {
  return (
    <SectionCard title="Documents Officiels" status={status} number="07" defaultOpen={true}>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* Estimation Budgétaire */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col hover:border-blue-300 transition-colors">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
              <FileText size={20} />
            </div>
            <h4 className="font-extrabold text-slate-800 leading-tight">Estimation<br />Budgétaire</h4>
          </div>
          <div className="mt-auto space-y-2">
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => handleGenerateDocument('estimation', 'pdf', 'preview')}
                disabled={generatingDoc === 'estimation-pdf-preview'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 font-bold hover:bg-blue-100 transition-colors disabled:opacity-50 text-sm"
                title="Aperçu PDF"
              >
                👁️ Aperçu
              </button>
              <button
                type="button"
                onClick={() => handleGenerateDocument('estimation', 'pdf', 'download')}
                disabled={generatingDoc === 'estimation-pdf-download'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-50 border border-red-200 rounded-xl text-red-700 font-bold hover:bg-red-100 transition-colors disabled:opacity-50 text-sm"
                title="Télécharger PDF"
              >
                <Download size={16} /> PDF
              </button>
            </div>
          </div>
        </div>

        {/* Décision de Nomination */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col hover:border-blue-300 transition-colors">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
              <Award size={20} />
            </div>
            <h4 className="font-extrabold text-slate-800 leading-tight">Décision de<br />Nomination</h4>
          </div>
          <div className="mt-auto space-y-2">
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => handleGenerateDocument('decision-nomination', 'pdf', 'preview')}
                disabled={generatingDoc === 'decision-nomination-pdf-preview'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 font-bold hover:bg-blue-100 transition-colors disabled:opacity-50 text-sm"
                title="Aperçu PDF"
              >
                👁️ Aperçu
              </button>
              <button
                type="button"
                onClick={() => handleGenerateDocument('decision-nomination', 'pdf', 'download')}
                disabled={generatingDoc === 'decision-nomination-pdf-download'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-50 border border-red-200 rounded-xl text-red-700 font-bold hover:bg-red-100 transition-colors disabled:opacity-50 text-sm"
                title="Télécharger PDF"
              >
                <Download size={16} /> PDF
              </button>
            </div>
          </div>
        </div>

        {/* Avis FR */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col hover:border-blue-300 transition-colors">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
              <FileText size={20} />
            </div>
            <h4 className="font-extrabold text-slate-800 leading-tight">Avis de publication<br />(Français)</h4>
          </div>
          <div className="mt-auto space-y-2">
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => handleGenerateDocument('avis_fr', 'pdf', 'preview')}
                disabled={generatingDoc === 'avis_fr-pdf-preview'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 font-bold hover:bg-blue-100 transition-colors disabled:opacity-50 text-sm"
                title="Aperçu PDF"
              >
                👁️ Aperçu
              </button>
              <button
                type="button"
                onClick={() => handleGenerateDocument('avis_fr', 'pdf', 'download')}
                disabled={generatingDoc === 'avis_fr-pdf-download'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-50 border border-red-200 rounded-xl text-red-700 font-bold hover:bg-red-100 transition-colors disabled:opacity-50 text-sm"
                title="Télécharger PDF"
              >
                <Download size={16} /> PDF
              </button>
            </div>
          </div>
        </div>

        {/* Avis AR */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col hover:border-blue-300 transition-colors" dir="rtl">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
              <FileText size={20} />
            </div>
            <h4 className="font-extrabold text-slate-800 leading-tight font-arabic mr-3">إعلان النشر<br />(بالعربية)</h4>
          </div>
          <div className="mt-auto space-y-2">
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => handleGenerateDocument('avis_ar', 'pdf', 'preview')}
                disabled={generatingDoc === 'avis_ar-pdf-preview'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 font-bold hover:bg-blue-100 transition-colors disabled:opacity-50 text-sm font-arabic"
                title="Aperçu PDF"
              >
                👁️ معاينة
              </button>
              <button
                type="button"
                onClick={() => handleGenerateDocument('avis_ar', 'pdf', 'download')}
                disabled={generatingDoc === 'avis_ar-pdf-download'}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-50 border border-red-200 rounded-xl text-red-700 font-bold hover:bg-red-100 transition-colors disabled:opacity-50 text-sm font-arabic"
                title="Télécharger PDF"
              >
                <Download size={16} /> PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </SectionCard>
  );
}
