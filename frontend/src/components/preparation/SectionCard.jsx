import React, { useState } from 'react';
import { CheckCircle, AlertTriangle, Lock, ChevronDown, ChevronUp, Edit3 } from 'lucide-react';

export default function SectionCard({ title, status, children, defaultOpen = false, number }) {
  const [isOpen, setIsOpen] = useState(defaultOpen);

  const getStatusBadge = () => {
    switch(status) {
      case 'complet':
        return <span className="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200 flex items-center gap-1"><CheckCircle size={14} /> COMPLET</span>;
      case 'incomplet':
        return <span className="bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1 rounded-full border border-amber-200 flex items-center gap-1"><AlertTriangle size={14} /> INCOMPLET</span>;
      case 'bloque':
        return <span className="bg-slate-100 text-slate-500 text-xs font-bold px-3 py-1 rounded-full border border-slate-300 flex items-center gap-1"><Lock size={14} /> BLOQUÉ</span>;
      case 'en_cours':
        return <span className="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-200 flex items-center gap-1"><Edit3 size={14} /> EN COURS</span>;
      default:
        return <span className="bg-slate-50 text-slate-500 text-xs font-bold px-3 py-1 rounded-full border border-slate-200 flex items-center gap-1">○ NON RENSEIGNÉ</span>;
    }
  };

  return (
    <div className="bg-white border border-slate-200 rounded-3xl shadow-sm mb-6 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-slate-300">
      <div 
        className="p-5 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50/50 transition-colors"
        onClick={() => setIsOpen(!isOpen)}
      >
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-3">
            <span className="flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-black text-sm">
              {number}
            </span>
            <h3 className="font-black text-lg text-slate-800">{title}</h3>
          </div>
          {getStatusBadge()}
        </div>
        <button className="text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-2 text-sm font-bold">
          {isOpen ? (
             <>Fermer <ChevronUp size={20} /></>
          ) : (
             <>Ouvrir <ChevronDown size={20} /></>
          )}
        </button>
      </div>
      
      {isOpen && (
        <div className="p-6 bg-white">
          {children}
        </div>
      )}
    </div>
  );
}
