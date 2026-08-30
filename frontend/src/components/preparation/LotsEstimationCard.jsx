import React, { useState } from 'react';
import SectionCard from './SectionCard';
import { PackageSearch, Plus, Trash2, Eye } from 'lucide-react';

export default function LotsEstimationCard({ 
  formData, 
  status, 
  isReadOnly, 
  handleLotsDetailsChange, 
  handleLotItemChange, 
  addLotItem, 
  removeLotItem, 
  addLot, 
  removeLot,
  calculerTotalEstimation,
  calculerTotalEstimationTva,
  calculerTotalEstimationTtc
}) {
  const [expandedLots, setExpandedLots] = useState({});

  const toggleLot = (index) => {
    setExpandedLots(prev => ({ ...prev, [index]: !prev[index] }));
  };

  return (
    <SectionCard title="Lots & Estimation Budgétaire" status={status} number="02">
      <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200">
        <div className="flex justify-between items-center mb-6">
          <h4 className="font-extrabold text-slate-800 flex items-center gap-2">
            <PackageSearch className="text-blue-600" size={20} /> Détail des Lots
          </h4>
          {!isReadOnly && (
            <button
              type="button"
              onClick={addLot}
              className="bg-white border border-slate-200 text-blue-600 font-bold px-4 py-2 rounded-xl hover:bg-blue-50 transition-colors flex items-center justify-center gap-2 text-sm shadow-sm"
            >
              <Plus size={16} /> Ajouter un lot
            </button>
          )}
        </div>

        <div className="space-y-4">
          {formData.lots_details?.map((lot, index) => {
            const isExpanded = expandedLots[index];
            const lotTtc = (parseFloat(lot.estimation) || 0) * 1.2;

            return (
              <div key={index} className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                {/* En-tête du Lot */}
                <div 
                  className="p-4 border-b border-slate-100 flex flex-wrap lg:flex-nowrap items-center gap-4 cursor-pointer hover:bg-slate-50 transition-colors"
                  onClick={(e) => {
                    // Ne pas déclencher si on clique sur un input ou un bouton supprimer
                    if(e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                      toggleLot(index);
                    }
                  }}
                >
                  <div className="w-24">
                    <input
                      type="text"
                      value={lot.num_lot}
                      onChange={(e) => handleLotsDetailsChange(index, 'num_lot', e.target.value)}
                      disabled={isReadOnly}
                      className="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 font-bold text-slate-800"
                      placeholder="N° Lot"
                    />
                  </div>
                  
                  <div className="flex-1 min-w-[200px]">
                    <input
                      type="text"
                      value={lot.objet_lot || ''}
                      onChange={(e) => handleLotsDetailsChange(index, 'objet_lot', e.target.value)}
                      disabled={isReadOnly}
                      className="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 font-medium text-slate-700"
                      placeholder="Intitulé du lot..."
                    />
                  </div>

                  <div className="w-36">
                    <span className="block text-xs font-bold text-slate-500 mb-0.5">Cautionnement (MAD)</span>
                    <input
                      type="number"
                      step="0.01"
                      value={lot.cautionnement_provisoire ?? ''}
                      onChange={(e) => handleLotsDetailsChange(index, 'cautionnement_provisoire', e.target.value)}
                      disabled={isReadOnly}
                      className="w-full px-2.5 py-1.5 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 font-semibold text-slate-800 text-right"
                      placeholder="0.00"
                    />
                  </div>

                  <div className="w-36 text-right">
                    <span className="block text-xs font-bold text-slate-400">Total TTC</span>
                    <span className="font-bold text-slate-800">{lotTtc.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} MAD</span>
                  </div>

                  <div className="flex items-center gap-2 ml-auto">
                    <button 
                      type="button" 
                      onClick={() => toggleLot(index)}
                      className="flex items-center gap-1 text-sm font-bold text-blue-600 hover:text-blue-800 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors"
                    >
                      <Eye size={16} /> {isExpanded ? 'Masquer détail' : 'Voir détail'}
                    </button>
                    {!isReadOnly && (
                      <button
                        type="button"
                        onClick={() => removeLot(index)}
                        className="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-colors"
                        title="Supprimer ce lot"
                      >
                        <Trash2 size={16} />
                      </button>
                    )}
                  </div>
                </div>

                {/* Détail Estimatif (Accordéon) */}
                {isExpanded && (
                  <div className="p-4 bg-slate-50/50">
                    <div className="flex justify-between items-center mb-3">
                      <h5 className="font-bold text-sm text-slate-700">Détail estimatif</h5>
                      {!isReadOnly && (
                        <button
                          type="button"
                          onClick={() => addLotItem(index)}
                          className="text-xs font-bold bg-white border border-slate-200 text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center gap-1 shadow-sm"
                        >
                          <Plus size={14} /> Ajouter une ligne
                        </button>
                      )}
                    </div>
                    
                    <div className="overflow-x-auto rounded-xl border border-slate-200">
                      <table className="w-full text-left border-collapse text-sm">
                        <thead>
                          <tr className="bg-slate-100 text-slate-700 uppercase font-black border-b border-slate-200">
                            <th className="px-3 py-2">Désignation</th>
                            <th className="px-3 py-2 w-24">Qté</th>
                            <th className="px-3 py-2 w-24">Unité</th>
                            <th className="px-3 py-2 w-32">PU HT</th>
                            <th className="px-3 py-2 w-32">Total HT</th>
                            {!isReadOnly && <th className="px-3 py-2 w-12 text-center"></th>}
                          </tr>
                        </thead>
                        <tbody>
                          {lot.items?.length === 0 ? (
                            <tr>
                              <td colSpan={isReadOnly ? 5 : 6} className="px-3 py-4 text-center text-slate-500 font-medium bg-white">
                                Aucune ligne pour ce lot.
                              </td>
                            </tr>
                          ) : (
                            lot.items?.map((item, iIndex) => (
                              <tr key={iIndex} className="border-b border-slate-100 bg-white hover:bg-slate-50">
                                <td className="px-3 py-2">
                                  <input
                                    type="text"
                                    value={item.designation || ''}
                                    onChange={(e) => handleLotItemChange(index, iIndex, 'designation', e.target.value)}
                                    disabled={isReadOnly}
                                    className="w-full px-2 py-1.5 rounded-md border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 text-slate-700"
                                    placeholder="Désignation..."
                                  />
                                </td>
                                <td className="px-3 py-2">
                                  <input
                                    type="number"
                                    value={item.quantite || ''}
                                    onChange={(e) => handleLotItemChange(index, iIndex, 'quantite', e.target.value)}
                                    disabled={isReadOnly}
                                    min="0"
                                    step="0.01"
                                    className="w-full px-2 py-1.5 rounded-md border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 text-slate-700 text-right"
                                  />
                                </td>
                                <td className="px-3 py-2">
                                  <input
                                    type="text"
                                    value={item.unite || ''}
                                    onChange={(e) => handleLotItemChange(index, iIndex, 'unite', e.target.value)}
                                    disabled={isReadOnly}
                                    className="w-full px-2 py-1.5 rounded-md border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 text-slate-700"
                                    placeholder="Ex: U, forfait..."
                                  />
                                </td>
                                <td className="px-3 py-2">
                                  <input
                                    type="number"
                                    value={item.prix_unitaire_ht || ''}
                                    onChange={(e) => handleLotItemChange(index, iIndex, 'prix_unitaire_ht', e.target.value)}
                                    disabled={isReadOnly}
                                    min="0"
                                    step="0.01"
                                    className="w-full px-2 py-1.5 rounded-md border border-slate-200 bg-slate-50 focus:bg-white outline-none focus:border-blue-500 text-slate-700 text-right"
                                  />
                                </td>
                                <td className="px-3 py-2 font-bold text-slate-800 text-right bg-slate-50/50">
                                  {parseFloat(item.montant_ht || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}
                                </td>
                                {!isReadOnly && (
                                  <td className="px-3 py-2 text-center">
                                    <button
                                      type="button"
                                      onClick={() => removeLotItem(index, iIndex)}
                                      className="text-red-400 hover:text-red-600 transition-colors p-1 hover:bg-red-50 rounded"
                                    >
                                      <Trash2 size={14} />
                                    </button>
                                  </td>
                                )}
                              </tr>
                            ))
                          )}
                        </tbody>
                        <tfoot className="bg-slate-50 font-bold border-t border-slate-200">
                          <tr>
                            <td colSpan="4" className="px-3 py-2 text-right text-slate-600">TOTAL HT DU LOT</td>
                            <td className="px-3 py-2 text-right text-slate-800">
                              {(parseFloat(lot.estimation) || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}
                            </td>
                            {!isReadOnly && <td></td>}
                          </tr>
                          <tr>
                            <td colSpan="4" className="px-3 py-2 text-right text-slate-600">TVA (20%)</td>
                            <td className="px-3 py-2 text-right text-slate-800">
                              {((parseFloat(lot.estimation) || 0) * 0.2).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}
                            </td>
                            {!isReadOnly && <td></td>}
                          </tr>
                          <tr className="bg-slate-100">
                            <td colSpan="4" className="px-3 py-2 text-right text-slate-800">TOTAL TTC DU LOT</td>
                            <td className="px-3 py-2 text-right text-blue-700">
                              {lotTtc.toLocaleString('fr-FR', { minimumFractionDigits: 2 })}
                            </td>
                            {!isReadOnly && <td></td>}
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>

        {/* ESTIMATION GLOBALE */}
        <div className="mt-8 bg-blue-50 rounded-2xl p-6 border border-blue-100">
          <h4 className="font-extrabold text-blue-800 mb-4 text-center">ESTIMATION GLOBALE DE L'AOO</h4>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white p-4 rounded-xl text-center shadow-sm">
              <span className="block text-sm font-bold text-slate-500 mb-1">Montant Global HT</span>
              <span className="text-xl font-black text-slate-800">{calculerTotalEstimation().toLocaleString('fr-FR', { minimumFractionDigits: 2 })} MAD</span>
            </div>
            <div className="bg-white p-4 rounded-xl text-center shadow-sm">
              <span className="block text-sm font-bold text-slate-500 mb-1">TVA globale (20%)</span>
              <span className="text-xl font-black text-slate-800">{calculerTotalEstimationTva().toLocaleString('fr-FR', { minimumFractionDigits: 2 })} MAD</span>
            </div>
            <div className="bg-blue-600 p-4 rounded-xl text-center shadow-sm shadow-blue-500/20 text-white transform hover:scale-105 transition-transform">
              <span className="block text-sm font-bold text-blue-100 mb-1">Montant Global TTC</span>
              <span className="text-2xl font-black">{calculerTotalEstimationTtc().toLocaleString('fr-FR', { minimumFractionDigits: 2 })} MAD</span>
            </div>
          </div>
        </div>
      </div>
    </SectionCard>
  );
}
