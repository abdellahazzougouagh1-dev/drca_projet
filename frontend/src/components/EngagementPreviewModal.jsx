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
                <div className="text-center font-extrabold text-lg text-slate-900 tracking-wide underline">
                  ACTE D'ENGAGEMENT (PIÈCE N° 2)
                </div>
                <div className="p-3 bg-slate-50 rounded-xl space-y-1">
                  <p><strong>Appel d'offres N° :</strong> {formData.num_aoo}</p>
                  <p><strong>Objet :</strong> {formData.objet_marche}</p>
                </div>
                <div className="space-y-2">
                  <p><strong>Je soussigné :</strong> {formData.representant || 'DEKKAKI MOHAMMED'}, Gérant</p>
                  <p><strong>Agissant au nom et pour le compte de :</strong> <span className="font-extrabold text-blue-900">{formData.titulaire}</span></p>
                  <p><strong>Siège social :</strong> {formData.adresse || 'Meknès, Maroc'}</p>
                  <p><strong>ICE :</strong> <span className="font-mono">{formData.ice || '001835798000066'}</span> | <strong>RC :</strong> <span className="font-mono">{formData.rc || '34009'}</span> | <strong>CNSS :</strong> <span className="font-mono">{formData.cnss || '9184342'}</span></p>
                </div>

                <div className="p-4 bg-blue-50/50 border border-blue-200 rounded-xl space-y-1 font-mono">
                  <p className="text-xs font-bold text-blue-900">Engagement financier :</p>
                  <p>Montant Hors TVA : {parseFloat(formData.montant || 0) / 1.20 ? (parseFloat(formData.montant || 0) / 1.20).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) : 0} DH</p>
                  <p>TVA (20%) : {(parseFloat(formData.montant || 0) - (parseFloat(formData.montant || 0) / 1.20)).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</p>
                  <p className="font-extrabold text-sm text-blue-950">Montant Total TTC : {parseFloat(formData.montant || 0).toLocaleString('fr-FR')} dh</p>
                </div>

                <div className="p-3 bg-slate-50 rounded-xl">
                  <p><strong>Compte bancaire (RIB) :</strong> <span className="font-mono">{formData.rib || '127 480 2121155766090007 54'}</span> ({formData.banque || 'Banque Populaire'})</p>
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
