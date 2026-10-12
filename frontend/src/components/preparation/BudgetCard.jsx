import React from 'react';
import SectionCard from './SectionCard';
import { Coins, CheckCircle, AlertCircle, Copy, BookmarkCheck, ArrowRight } from 'lucide-react';

export default function BudgetCard({ 
  formData, 
  handleLotsDetailsChange, 
  lignesBudgetaires = [], 
  isReadOnly, 
  status,
  setFormData 
}) {
  const lots = (formData.lots_details && formData.lots_details.length > 0)
    ? formData.lots_details
    : [{
        num_lot: 'LOT 1',
        objet_lot: formData.objet || '',
        estimation: formData.budget || '',
        art: formData.art || '',
        par: formData.par || '',
        lig: formData.lig || '',
        imputation: formData.imputation || '',
        notification_ligne_id: formData.notification_ligne_id || null,
      }];

  const isLotComplete = (lot) => {
    return Boolean(
      (lot.art && String(lot.art).trim() !== '' &&
       lot.par && String(lot.par).trim() !== '' &&
       lot.lig && String(lot.lig).trim() !== '') ||
      (lot.imputation && String(lot.imputation).trim() !== '') ||
      lot.notification_ligne_id
    );
  };

  const handleSelectLigne = (lotIndex, ligneId) => {
    if (!handleLotsDetailsChange) return;
    if (!ligneId) {
      handleLotsDetailsChange(lotIndex, 'notification_ligne_id', null);
      return;
    }
    const selected = (lignesBudgetaires || []).find(l => String(l.id) === String(ligneId));
    if (selected) {
      handleLotsDetailsChange(lotIndex, 'notification_ligne_id', selected.id);
      handleLotsDetailsChange(lotIndex, 'art', selected.article || '');
      handleLotsDetailsChange(lotIndex, 'par', selected.paragraphe || '');
      handleLotsDetailsChange(lotIndex, 'lig', selected.ligne_budgetaire || '');
      if (selected.intitule_ligne || selected.libelle) {
        handleLotsDetailsChange(lotIndex, 'imputation', selected.intitule_ligne || selected.libelle || '');
      }
    }
  };

  const handleDuplicateToAll = (sourceIndex) => {
    if (!setFormData) return;
    const sourceLot = lots[sourceIndex];
    if (!sourceLot) return;

    setFormData(prev => {
      const updatedLots = (prev.lots_details || []).map(lot => ({
        ...lot,
        art: sourceLot.art || '',
        par: sourceLot.par || '',
        lig: sourceLot.lig || '',
        imputation: sourceLot.imputation || '',
        notification_ligne_id: sourceLot.notification_ligne_id || null,
      }));

      return {
        ...prev,
        lots_details: updatedLots,
        art: sourceLot.art || prev.art,
        par: sourceLot.par || prev.par,
        lig: sourceLot.lig || prev.lig,
        imputation: sourceLot.imputation || prev.imputation,
        notification_ligne_id: sourceLot.notification_ligne_id || prev.notification_ligne_id,
      };
    });
  };

  return (
    <SectionCard title="Imputation Budgétaire par Lot" status={status} number="03">
      <div className="space-y-6">
        {lots.map((lot, index) => {
          const lotTtc = (parseFloat(lot.estimation) || 0) * 1.2;
          const complete = isLotComplete(lot);
          const lotNumber = lot.num_lot || `LOT ${index + 1}`;

          return (
            <div 
              key={index}
              className={`rounded-2xl border transition-all p-6 ${
                complete 
                  ? 'bg-slate-50/70 border-slate-200 shadow-sm' 
                  : 'bg-amber-50/30 border-amber-200'
              }`}
            >
              {/* En-tête de l'imputation du Lot */}
              <div className="flex flex-wrap items-center justify-between gap-4 pb-4 mb-5 border-b border-slate-200">
                <div className="flex items-center gap-3">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shadow-sm ${
                    complete ? 'bg-indigo-600 text-white' : 'bg-amber-500 text-white'
                  }`}>
                    {index + 1}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-extrabold text-slate-800 text-base">{lotNumber}</span>
                      {lot.objet_lot && (
                        <span className="text-slate-500 text-sm font-medium truncate max-w-xs md:max-w-md">
                          • {lot.objet_lot}
                        </span>
                      )}
                    </div>
                    <span className="text-xs text-slate-400 font-semibold">
                      Estimation : {lotTtc.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MAD TTC
                    </span>
                  </div>
                </div>

                <div className="flex items-center gap-3 ml-auto">
                  {complete ? (
                    <span className="text-emerald-700 bg-emerald-100/80 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5 text-xs shadow-sm">
                      <CheckCircle size={14} className="text-emerald-600" /> Imputation complète
                    </span>
                  ) : (
                    <span className="text-amber-700 bg-amber-100/80 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5 text-xs">
                      <AlertCircle size={14} className="text-amber-600" /> Imputation requise
                    </span>
                  )}

                  {!isReadOnly && lots.length > 1 && (
                    <button
                      type="button"
                      onClick={() => handleDuplicateToAll(index)}
                      className="px-3 py-1.5 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-indigo-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm"
                      title="Copier cette imputation à tous les autres lots"
                    >
                      <Copy size={13} /> Appliquer à tous les lots
                    </button>
                  )}
                </div>
              </div>

              {/* Sélection rapide Ligne Budgétaire (si lignes disponibles) */}
              {lignesBudgetaires && lignesBudgetaires.length > 0 && !isReadOnly && (
                <div className="mb-4">
                  <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                    Sélection rapide depuis les lignes de notification
                  </label>
                  <select
                    value={lot.notification_ligne_id || ''}
                    onChange={(e) => handleSelectLigne(index, e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 focus:border-indigo-500 outline-none"
                  >
                    <option value="">-- Saisie manuelle ou sélectionner une ligne --</option>
                    {lignesBudgetaires.map((nl) => (
                      <option key={nl.id} value={nl.id}>
                        Art. {nl.article} / Par. {nl.paragraphe} / Lig. {nl.ligne_budgetaire} {nl.intitule_ligne ? `— ${nl.intitule_ligne}` : (nl.libelle ? `— ${nl.libelle}` : '')}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {/* Formulaire Imputation Budgétaire */}
              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label className="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">
                    Article <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={lot.art || ''}
                    onChange={(e) => handleLotsDetailsChange && handleLotsDetailsChange(index, 'art', e.target.value)}
                    disabled={isReadOnly}
                    placeholder="Ex: 10"
                    className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 text-sm disabled:opacity-70 shadow-sm"
                  />
                </div>

                <div>
                  <label className="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">
                    Paragraphe <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={lot.par || ''}
                    onChange={(e) => handleLotsDetailsChange && handleLotsDetailsChange(index, 'par', e.target.value)}
                    disabled={isReadOnly}
                    placeholder="Ex: 213"
                    className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 text-sm disabled:opacity-70 shadow-sm"
                  />
                </div>

                <div>
                  <label className="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">
                    Ligne <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={lot.lig || ''}
                    onChange={(e) => handleLotsDetailsChange && handleLotsDetailsChange(index, 'lig', e.target.value)}
                    disabled={isReadOnly}
                    placeholder="Ex: 10"
                    className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-mono font-bold text-slate-800 text-sm disabled:opacity-70 shadow-sm"
                  />
                </div>

                <div>
                  <label className="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">
                    Imputation (Optionnel)
                  </label>
                  <input
                    type="text"
                    value={lot.imputation || ''}
                    onChange={(e) => handleLotsDetailsChange && handleLotsDetailsChange(index, 'imputation', e.target.value)}
                    disabled={isReadOnly}
                    placeholder="Libellé / code..."
                    className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all font-medium text-slate-800 text-sm disabled:opacity-70 shadow-sm"
                  />
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </SectionCard>
  );
}
