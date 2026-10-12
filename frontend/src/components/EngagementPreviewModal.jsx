import React from 'react';
import { FileText, Download, Printer, Edit3, X, CheckCircle } from 'lucide-react';

export const EngagementPreviewModal = ({
  isOpen,
  onClose,
  docType,
  docTitle,
  formData,
  onEdit,
  onDownload,
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm grid place-items-center p-4 overflow-y-auto animate-fade-in">
      <div className="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Modal Header */}
        <div className="px-6 py-4 bg-[#1e3a8a] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <FileText size={22} className="text-cyan-300" />
            <div>
              <h3 className="font-extrabold text-base">Prévisualisation : {docTitle}</h3>
              <p className="text-[11px] text-cyan-100 font-mono">Document officiel de la Phase Engagement</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-all"
          >
            <X size={20} />
          </button>
        </div>

        {/* Live Preview Document Area */}
        <div className="p-8 max-h-[65vh] overflow-y-auto bg-slate-100/70 font-sans text-xs leading-relaxed text-slate-900">
          
          <div className="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 space-y-6 max-w-3xl mx-auto">
            
            {/* Document Header */}
            <div className="flex items-start justify-between border-b border-slate-200 pb-4">
              <div className="text-left font-bold text-slate-700 space-y-0.5">
                <p className="text-[11px] uppercase tracking-wider text-slate-500">Royaume du Maroc</p>
                <p className="text-xs">Office National du Conseil Agricole</p>
                <p className="text-[10px] text-slate-500">DRCA Rabat-Salé-Kénitra</p>
              </div>
              <div className="text-right font-mono text-xs">
                <p className="font-bold text-blue-900">{docTitle}</p>
                <p className="text-[11px] text-slate-500">Réf: {formData.num_aoo || formData.num_marche || '06/2026/DRCA-RSK'}</p>
                <p className="text-[11px] text-slate-500">Kénitra, le {formData.date_approbation || formData.os_date_effet || new Date().toLocaleDateString('fr-FR')}</p>
              </div>
            </div>

            {/* Document Specific Preview Rendering */}
            {docType === 'acte-engagement' && (
              <div className="space-y-4">
                <div className="flex justify-between items-start border border-black p-2 rounded-lg bg-slate-50">
                  <div>
                    <span className="font-bold text-xs">N° : </span>
                    <span className="font-mono font-bold text-xs">{formData.num_engagement || `${formData.id || '22'}/${formData.exercice || new Date().getFullYear()}/FE/DRCA-RSK`}</span>
                  </div>
                  <div className="border border-black px-3 py-1 text-right text-[11px] font-bold">
                    <div>Budget : {formData.type_budget || 'Investissement'}</div>
                    <div>Exercice : {formData.exercice || new Date().getFullYear()}</div>
                  </div>
                </div>

                <div className="text-center font-extrabold text-base text-slate-900 tracking-wide">
                  FICHE D'ENGAGEMENT
                </div>

                <div className="border border-black divide-y divide-black text-xs">
                  <div className="grid grid-cols-3 p-2">
                    <span className="font-bold">Référence de l'engagement :</span>
                    <span className="col-span-2 font-mono font-bold text-center">{formData.reference_engagement || (formData.num_marche ? `BC N° ${formData.num_marche}` : `BC N° 06/INV/${formData.exercice || new Date().getFullYear()}/DRCA-RSK`)}</span>
                  </div>
                  <div className="grid grid-cols-3 p-2">
                    <span className="font-bold">Objet :</span>
                    <span className="col-span-2 font-bold uppercase text-center">{formData.objet_marche}</span>
                  </div>
                  <div className="grid grid-cols-3 p-2">
                    <span className="font-bold">Forme :</span>
                    <span className="col-span-2 font-bold text-center">{formData.forme_engagement || 'Marché'}</span>
                  </div>
                  <div className="grid grid-cols-3 p-2">
                    <span className="font-bold">Montant en dirhams :</span>
                    <span className="col-span-2 font-bold text-center text-sm">{Number(formData.montant || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</span>
                  </div>
                </div>

                <div className="border border-black text-xs text-center">
                  <div className="bg-slate-100 p-1.5 font-bold border-b border-black">Rubrique Budgétaire</div>
                  <div className="grid grid-cols-3 divide-x divide-black border-b border-black p-1.5 font-mono font-bold">
                    <div>ARTICLE : {formData.article_budget || '415'}</div>
                    <div>PARAGRAPHE : {formData.paragraphe_budget || '20'}</div>
                    <div>LIGNE : {formData.ligne_budget || '16'}</div>
                  </div>
                  <div className="grid grid-cols-4 divide-x divide-black p-2 text-[11px]">
                    <div>
                      <p className="font-bold text-slate-500">Crédit CP</p>
                      <p className="font-mono font-bold mt-1">{formData.credit_budget_cp ? Number(formData.credit_budget_cp).toLocaleString('fr-FR') : '-'}</p>
                    </div>
                    <div>
                      <p className="font-bold text-slate-500">Dépenses CP</p>
                      <p className="font-mono font-bold mt-1">{formData.depenses_engagees_cp ? Number(formData.depenses_engagees_cp).toLocaleString('fr-FR') : '-'}</p>
                    </div>
                    <div>
                      <p className="font-bold text-emerald-700">Disponible CP</p>
                      <p className="font-mono font-bold mt-1 text-emerald-800">{formData.disponible_cp ? Number(formData.disponible_cp).toLocaleString('fr-FR') : '-'}</p>
                    </div>
                    <div>
                      <p className="font-bold text-blue-700">Engagement CP</p>
                      <p className="font-mono font-bold mt-1 text-blue-900">{Number((Number(formData.montant || 0) * 1.01)).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</p>
                    </div>
                  </div>
                </div>

                <div className="border border-black divide-y divide-black text-xs">
                  <div className="grid grid-cols-3 p-2">
                    <span className="font-bold">Bénéficiaire :</span>
                    <span className="col-span-2 font-bold uppercase text-center">{formData.titulaire}</span>
                  </div>
                  <div className="grid grid-cols-3 p-2">
                    <span>Montant de la dépense :</span>
                    <span className="col-span-2 font-bold text-center">{Number(formData.montant || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</span>
                  </div>
                  <div className="grid grid-cols-3 p-2">
                    <span>Intérêt moratoire (1%) :</span>
                    <span className="col-span-2 font-bold text-center text-amber-700">{Number((Number(formData.montant || 0) * 0.01)).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</span>
                  </div>
                  <div className="grid grid-cols-3 p-2 bg-slate-50">
                    <span className="font-extrabold">Montant Total Engagement :</span>
                    <span className="col-span-2 font-extrabold text-center text-sm text-blue-900">{Number((Number(formData.montant || 0) * 1.01)).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</span>
                  </div>
                </div>

                <div className="border border-black p-3 text-center">
                  <p className="font-bold uppercase tracking-wider text-xs mb-8">VISA DU SOUS-ORDONNATEUR</p>
                  <p className="text-right text-xs font-mono">Date : {formData.date_engagement || formData.date_signature || new Date().toLocaleDateString('fr-FR')}</p>
                </div>
              </div>
            )}

            {docType === 'marche-definitif' && (
              <div className="space-y-4">
                <div className="text-center space-y-1">
                  <div className="text-xl font-extrabold text-indigo-900">MARCHÉ N° {formData.num_marche || '07/2026/DRCA-RSK'}</div>
                  <div className="text-xs font-bold text-slate-600">Issu de l'Appel d'Offres Ouvert N° {formData.num_aoo}</div>
                  <div className="p-3 bg-indigo-50/60 rounded-xl font-bold text-indigo-950 uppercase text-xs border border-indigo-100">
                    {formData.objet_marche}
                  </div>
                </div>

                <div className="space-y-2 border-t border-b border-slate-200 py-3 text-justify">
                  <p><strong>PREAMBULE :</strong> Entre l'Office National du Conseil Agricole représenté par le Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra (Maître d'ouvrage) d'une part, et <strong>{formData.titulaire}</strong> (Titulaire) d'autre part, il a été convenu l'exécution des prestations ci-dessus.</p>
                  <p><strong>Délai d'exécution :</strong> {formData.delai_execution || 12} Mois à compter de l'OS de commencement.</p>
                  <p><strong>Montant du Marché :</strong> {parseFloat(formData.montant || 0).toLocaleString('fr-FR')} dh TTC.</p>
                </div>
              </div>
            )}

            {docType === 'decision-approbation' && (
              <div className="space-y-4">
                <div className="text-center font-extrabold text-base text-slate-900 uppercase underline">
                  NOTIFICATION DE L'APPROBATION DU MARCHÉ
                </div>
                <div className="p-3 bg-emerald-50/50 border border-emerald-200 rounded-xl space-y-1">
                  <p><strong>Marché Numéro :</strong> {formData.num_marche}</p>
                  <p><strong>Attributaire / Titulaire :</strong> {formData.titulaire}</p>
                  <p><strong>Objet :</strong> {formData.objet_marche}</p>
                </div>
                <p className="text-justify">
                  J'ai l'honneur de vous informer que le Marché Numéro <strong>{formData.num_marche}</strong> a été approuvé par l'autorité compétente le <strong>{formData.date_approbation || new Date().toLocaleDateString('fr-FR')}</strong> sous la décision N° <strong>{formData.num_decision || '01/2026/M06'}</strong>.
                </p>
                <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-emerald-900">
                  Observation : Le montant de la caution définitive à présenter (3%) est de : {(parseFloat(formData.montant || 0) * 0.03).toLocaleString('fr-FR')} DH.
                </div>
              </div>
            )}

            {docType === 'os-commencement' && (
              <div className="space-y-4">
                <div className="text-center font-extrabold text-base text-slate-900 uppercase underline">
                  ORDRE DE SERVICE DE COMMENCEMENT DE L'EXÉCUTION
                </div>
                <div className="p-3 bg-amber-50/50 border border-amber-200 rounded-xl space-y-1">
                  <p><strong>N° OS :</strong> {formData.os_numero || '03/2026/M10'}</p>
                  <p><strong>Marché N° :</strong> {formData.num_marche}</p>
                  <p><strong>Titulaire :</strong> {formData.titulaire}</p>
                </div>
                <div className="p-4 bg-amber-100/70 border border-amber-300 rounded-xl font-bold text-amber-950 text-center">
                  Le commencement de l'exécution du dit marché prendra effet à compter du : {formData.os_date_effet || new Date().toLocaleDateString('fr-FR')}
                </div>
              </div>
            )}

            {/* Signature Box Preview */}
            <div className="border-t border-slate-200 pt-4 flex justify-between font-bold text-xs">
              <div>Le Maître d'Ouvrage</div>
              <div>Le Titulaire du Marché</div>
            </div>

          </div>

        </div>

        {/* Modal Footer Actions */}
        <div className="px-6 py-4 bg-slate-100 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
          <button
            type="button"
            onClick={() => onEdit(docType)}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl transition-all flex items-center gap-1.5 text-xs"
          >
            <Edit3 size={15} /> Modifier les informations
          </button>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => onDownload(docType)}
              className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 text-xs"
            >
              <Download size={15} /> Générer & Télécharger PDF
            </button>
            <button
              type="button"
              onClick={() => onDownload(docType)}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-all flex items-center gap-1.5 text-xs"
            >
              <Printer size={15} /> Imprimer
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
