import React from 'react';
import { Check, ArrowRight } from 'lucide-react';

export default function PreparationProgress({ stepsStatus }) {
  const steps = [
    { id: 'infos', label: 'Informations' },
    { id: 'lots', label: 'Lots & Estimation' },
    { id: 'budget', label: 'Budget' },
    { id: 'commission', label: 'Commission' },
    { id: 'seance', label: 'Séance' },
    { id: 'validation', label: 'Validation' },
    { id: 'docs', label: 'Documents' },
    { id: 'pubs', label: 'Publications' },
    { id: 'passage', label: 'Passage' },
  ];

  return (
    <div className="bg-white border border-slate-200 rounded-3xl p-6 mb-8 shadow-sm overflow-x-auto">
      <div className="flex items-center min-w-max px-2">
        {steps.map((step, index) => {
          const isComplete = stepsStatus[step.id];
          const isLast = index === steps.length - 1;
          
          return (
            <React.Fragment key={step.id}>
              <div className="flex flex-col items-center gap-2 relative z-10 w-24">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-colors ${
                  isComplete 
                    ? 'bg-emerald-500 border-emerald-500 text-white shadow-md shadow-emerald-500/20' 
                    : 'bg-white border-slate-200 text-slate-400'
                }`}>
                  {isComplete ? <Check size={18} /> : `0${index + 1}`}
                </div>
                <span className={`text-[10px] font-black uppercase text-center ${isComplete ? 'text-emerald-700' : 'text-slate-400'}`}>
                  {step.label}
                </span>
              </div>
              
              {!isLast && (
                <div className="flex-1 w-12 h-[2px] -mt-5">
                  <div className={`h-full transition-colors ${isComplete ? 'bg-emerald-400' : 'bg-slate-100'}`}></div>
                </div>
              )}
            </React.Fragment>
          );
        })}
      </div>
    </div>
  );
}
