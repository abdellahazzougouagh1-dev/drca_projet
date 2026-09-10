import React, { useState } from 'react';
import { X, FileText, Eye, Download, Printer, CheckCircle2, ShieldCheck, Wallet, Landmark, Layers } from 'lucide-react';
import { resolveOrdreDocumentsList } from '../../utils/ordonnancementDocs';
import api from '../../api/axios';

export default function OrdreDocumentsModal({ 
  isOpen, 
  onClose, 
  ordre, 
  ordonnancement, 
  onOpenPreview 
}) {
  const [downloadingDoc, setDownloadingDoc] = useState(null);

  if (!isOpen || !ordre) return null;

  const docsList = resolveOrdreDocumentsList(ordre);
  const numOrdre = ordre.num_ordre || 'OP';
  const formatDH = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0) + ' DH';

  const handleDownloadPdf = async (docType) => {
    try {
      setDownloadingDoc(docType);
      const ordId = ordonnancement?.id;
      if (!ordId) {
        // If in creation wizard before saving, open preview
        if (onOpenPreview) {
          onOpenPreview(docType, ordre);
        }
        return;
      }
      const endpoint = `/ordonnancements/${ordId}/documents/${docType}${ordre.id ? `/${ordre.id}` : ''}`;
      const res = await api.get(endpoint, { responseType: 'blob' });
      const mimeType = res.data.type || res.headers['content-type'] || 'application/pdf';
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: mimeType }));
      const link = document.createElement('a');
      link.href = fileUrl;
      const cleanNum = String(numOrdre).replace(/[^a-zA-Z0-9_\-]/g, '_');
      link.setAttribute('download', `${docType.toUpperCase()}_${cleanNum}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(fileUrl);
    } catch (err) {
      console.error('Erreur téléchargement document ordre:', err);
      if (onOpenPreview) {
        onOpenPreview(docType, ordre);
      } else {
        alert('Erreur lors du téléchargement du document PDF.');
      }
    } finally {
      setDownloadingDoc(null);
    }
  };

  const handlePrint = (docType) => {
    if (onOpenPreview) {
      onOpenPreview(docType, ordre);
      setTimeout(() => {
        window.print();
      }, 400);
    }
  };

  const getIcon = (type) => {
    if (type === 'ov') return <Landmark className="w-6 h-6 text-blue-600" />;
    if (type === 'oi') return <Layers className="w-6 h-6 text-indigo-600" />;
    return <FileText className="w-6 h-6 text-blue-600" />;
  };

  return (
    <div className="fixed inset-0 z-60 bg-slate-900/60 backdrop-blur-sm flex flex-col h-screen w-screen overflow-hidden animate-in fade-in duration-150">
      <div className="bg-white w-full h-full flex flex-col overflow-hidden">
        
        {/* Header */}
        <div className="px-8 md:px-12 py-5 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex justify-between items-center shrink-0 shadow-md">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-white/10 rounded-2xl backdrop-blur-sm border border-white/10 shadow-inner">
              <FileText className="w-7 h-7 text-cyan-300" />
            </div>
            <div>
              <h3 className="text-xl md:text-2xl font-black tracking-tight flex items-center gap-3">
                Documents — {numOrdre}
              </h3>
              <p className="text-sm text-blue-200 font-medium mt-0.5">
                Documents officiels générés pour cet ordre ({docsList.length} document{docsList.length > 1 ? 's' : ''})
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-2.5 text-white/80 hover:text-white hover:bg-white/10 rounded-2xl transition cursor-pointer"
            title="Fermer"
          >
            <X size={24} />
          </button>
        </div>

        {/* Body (Full Page Scrollable Area) */}
        <div className="p-6 md:p-10 overflow-y-auto flex-1 bg-slate-50/70">
          <div className="w-full space-y-8">
          
            {/* Order Summary Banner - Extra Large */}
            <div className="bg-white p-6 md:p-8 rounded-3xl border border-slate-200 flex flex-wrap items-center justify-between gap-6 shadow-sm">
              <div className="space-y-1">
                <span className="text-slate-400 block font-bold text-xs md:text-sm uppercase tracking-wider">Bénéficiaire</span>
                <strong className="text-slate-900 text-lg md:text-2xl font-black">{ordre.beneficiaire || ordonnancement?.beneficiaire_nom || 'Bénéficiaire'}</strong>
              </div>
              <div className="space-y-1">
                <span className="text-slate-400 block font-bold text-xs md:text-sm uppercase tracking-wider mb-1">Créance</span>
                <span className="px-4 py-1.5 bg-blue-50 text-blue-800 border border-blue-200 rounded-full font-bold text-sm">
                  {ordre.creance || 'Reste à payer'}
                </span>
              </div>
              <div className="text-right space-y-1">
                <span className="text-slate-400 block font-bold text-xs md:text-sm uppercase tracking-wider">Montant de l'ordre</span>
                <strong className="text-blue-700 text-2xl md:text-3xl font-black">{formatDH(ordre.montant)}</strong>
              </div>
            </div>

            {/* 1. ÉTAT DE LIQUIDATION (TOUJOURS PRÉSENT) - Extra Large Cell */}
            <div className="bg-gradient-to-br from-white via-blue-50/60 to-indigo-50/50 p-8 md:p-10 rounded-3xl border-2 border-blue-300 shadow-md space-y-6 hover:border-blue-500 hover:shadow-xl transition">
              <div className="flex items-start gap-5 pb-5 border-b border-blue-100">
                <div className="p-4 bg-blue-600 text-white rounded-2xl shadow-md shrink-0">
                  <FileText className="w-8 h-8" />
                </div>
                <div className="flex-1 min-w-0 space-y-2">
                  <div className="flex items-center gap-3 flex-wrap">
                    <h4 className="text-xl md:text-2xl font-black text-slate-900">
                      État de liquidation
                    </h4>
                    <span className="px-3.5 py-1 bg-blue-600 text-white rounded-full text-xs md:text-sm font-black uppercase tracking-wider shadow-xs">
                      Toujours présent
                    </span>
                  </div>
                  <p className="text-sm md:text-base text-slate-600 font-medium leading-relaxed">
                    Document de référence financière de la liquidation préalable commune à l'ensemble du dossier d'ordonnancement.
                  </p>
                  <div className="flex items-center gap-6 pt-2 text-sm md:text-base text-slate-700 font-semibold">
                    <span>Réf : <strong className="text-slate-900 font-bold">{ordonnancement?.liquidation?.num_liquidation || ordonnancement?.reference || 'Liquidation'}</strong></span>
                    <span>•</span>
                    <span>Montant brut : <strong className="text-blue-700 font-mono font-black text-base md:text-lg">{formatDH(ordonnancement?.montant_brut)}</strong></span>
                  </div>
                </div>
              </div>

              {/* Actions: Aperçu / Télécharger PDF / Imprimer - Extra Large Buttons */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                <button
                  type="button"
                  onClick={() => {
                    if (onOpenPreview) onOpenPreview('etat_liquidation', null);
                  }}
                  className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-white border-2 border-slate-300 hover:bg-blue-50 hover:border-blue-500 text-slate-900 text-sm md:text-base font-bold rounded-2xl transition cursor-pointer shadow-xs hover:shadow-md"
                >
                  <Eye size={20} className="text-blue-600" />
                  Aperçu
                </button>
                
                <button
                  type="button"
                  onClick={() => handleDownloadPdf('etat_liquidation')}
                  disabled={downloadingDoc === 'etat_liquidation'}
                  className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm md:text-base font-bold rounded-2xl shadow-md hover:shadow-lg transition cursor-pointer"
                >
                  <Download size={20} className={downloadingDoc === 'etat_liquidation' ? 'animate-bounce' : ''} />
                  {downloadingDoc === 'etat_liquidation' ? 'Téléchargement...' : 'Télécharger PDF'}
                </button>

                <button
                  type="button"
                  onClick={() => handlePrint('etat_liquidation')}
                  className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm md:text-base font-bold rounded-2xl transition cursor-pointer"
                >
                  <Printer size={20} />
                  Imprimer
                </button>
              </div>
            </div>

            {/* 2. DOCUMENTS ASSOCIÉS À L'ORDRE (OP & OV) - Extra Large Cells */}
            <div className="space-y-5 pt-2">
              <div className="flex items-center gap-3 pb-1">
                <span className="w-3.5 h-3.5 rounded-full bg-emerald-600"></span>
                <h4 className="text-sm md:text-base font-black text-slate-900 uppercase tracking-wider">
                  Documents spécifiques à cet ordre ({numOrdre})
                </h4>
              </div>

              <div className="space-y-5">
              {docsList.map((docInfo, idx) => (
                <div 
                  key={idx} 
                  className="bg-white p-8 md:p-10 rounded-3xl border-2 border-slate-200 shadow-md space-y-6 hover:border-blue-400 hover:shadow-xl transition"
                >
                  <div className="flex items-start gap-5 pb-5 border-b border-slate-100">
                    <div className="p-4 bg-blue-50 rounded-2xl border border-blue-100 shrink-0">
                      {docInfo.docType === 'ov' ? <Landmark className="w-8 h-8 text-blue-600" /> : <FileText className="w-8 h-8 text-blue-600" />}
                    </div>
                    <div className="flex-1 min-w-0 space-y-2">
                      <div className="flex items-center gap-3 flex-wrap">
                        <h4 className="text-xl md:text-2xl font-black text-slate-900">
                          {docInfo.title}
                        </h4>
                        <span className="px-3.5 py-1 bg-blue-100 text-blue-800 border border-blue-200 rounded-full text-xs md:text-sm font-black uppercase tracking-wider">
                          {docInfo.docType.toUpperCase()}
                        </span>
                      </div>
                      <p className="text-sm md:text-base text-slate-600 font-medium leading-relaxed">
                        {docInfo.description}
                      </p>
                      <div className="flex items-center gap-6 pt-2 text-sm md:text-base text-slate-700 font-semibold">
                        <span>N° Ordre : <strong className="text-slate-900 font-mono font-bold">{numOrdre}</strong></span>
                        <span>•</span>
                        <span>Mode : <strong className="text-slate-900 font-bold">{ordre.mode_paiement || 'Virement'}</strong></span>
                        <span>•</span>
                        <span>Montant : <strong className="text-blue-700 font-mono font-black">{formatDH(ordre.montant)}</strong></span>
                      </div>
                    </div>
                  </div>

                  {/* Actions: Aperçu / Télécharger PDF / Imprimer - Extra Large Buttons */}
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                    <button
                      type="button"
                      onClick={() => {
                        if (onOpenPreview) onOpenPreview(docInfo.docType, ordre);
                      }}
                      className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-white border-2 border-slate-300 hover:bg-blue-50 hover:border-blue-500 text-slate-900 text-sm md:text-base font-bold rounded-2xl transition cursor-pointer shadow-xs hover:shadow-md"
                    >
                      <Eye size={20} className="text-blue-600" />
                      Aperçu
                    </button>
                    
                    <button
                      type="button"
                      onClick={() => handleDownloadPdf(docInfo.docType)}
                      disabled={downloadingDoc === docInfo.docType}
                      className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm md:text-base font-bold rounded-2xl shadow-md hover:shadow-lg transition cursor-pointer"
                    >
                      <Download size={20} className={downloadingDoc === docInfo.docType ? 'animate-bounce' : ''} />
                      {downloadingDoc === docInfo.docType ? 'Téléchargement...' : 'Télécharger PDF'}
                    </button>

                    <button
                      type="button"
                      onClick={() => handlePrint(docInfo.docType)}
                      className="inline-flex items-center justify-center gap-2.5 px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm md:text-base font-bold rounded-2xl transition cursor-pointer"
                    >
                      <Printer size={20} />
                      Imprimer
                    </button>
                  </div>
                </div>
              ))}
              </div>
            </div>

          </div>
        </div>

        {/* Footer */}
        <div className="px-8 md:px-12 py-4 bg-white border-t border-slate-200 flex justify-end shrink-0 shadow-sm">
          <button
            type="button"
            onClick={onClose}
            className="px-8 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-2xl text-sm font-bold transition cursor-pointer"
          >
            Fermer
          </button>
        </div>

      </div>
    </div>
  );
}
