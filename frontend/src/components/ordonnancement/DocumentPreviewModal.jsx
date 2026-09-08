import React, { useState } from 'react';
import { X, Printer, Download, Eye, FileText, CheckCircle2, Building, ShieldCheck } from 'lucide-react';
import api from '../../api/axios';
import { numberToFrenchWords } from '../../utils/numberToWords';

export default function DocumentPreviewModal({ isOpen, onClose, ordonnancement, ordre, docType = 'op' }) {
  if (!isOpen || !ordonnancement) return null;

  const isRas = docType === 'op_ras' || docType === 'op_ras_is' || docType === 'op_ras_tva' || (docType === 'op' && (ordre?.type_mouvement?.toLowerCase().includes('retenue') || ordre?.creance?.toLowerCase().includes('retenue')));

  const currentOrdre = ordre || (
    docType === 'op_ras_tva'
      ? ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('tva') || o.creance?.toLowerCase().includes('tva')) || ordonnancement.ordres?.[0]
      : docType === 'op_ras_is'
        ? ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('ias') || o.type_mouvement?.toLowerCase().includes('is') || o.creance?.toLowerCase().includes('ias')) || ordonnancement.ordres?.[0]
        : docType === 'op_ras'
          ? ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('ias') || o.type_mouvement?.toLowerCase().includes('tva') || o.type_mouvement?.toLowerCase().includes('retenue')) || ordonnancement.ordres?.[0]
          : docType === 'op' 
            ? ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('fournisseur')) || ordonnancement.ordres?.[0]
            : docType === 'ov'
              ? ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('tva')) || ordonnancement.ordres?.[1] || ordonnancement.ordres?.[0]
              : ordonnancement.ordres?.find(o => o.type_mouvement?.toLowerCase().includes('ias')) || ordonnancement.ordres?.[2] || ordonnancement.ordres?.[0]
  );

  const montant = currentOrdre ? Number(currentOrdre.montant || 0) : Number(ordonnancement.net_a_payer || 0);
  const beneficiaire = isRas 
    ? 'Receveur de l’administration fiscale' 
    : (currentOrdre?.beneficiaire || ordonnancement.beneficiaire_nom || ordonnancement.fournisseur?.raison_sociale || 'Bénéficiaire');
  
  const rib = currentOrdre?.rib_compte || ordonnancement.fournisseur?.rib || '310 810 100 002 470 105 200 152';
  const modePaiement = isRas ? (currentOrdre?.mode_paiement || 'TELEPAIEMENT') : (currentOrdre?.mode_paiement || 'VIREMENT');

  const [downloading, setDownloading] = useState(false);

  const downloadPdf = async () => {
    try {
      setDownloading(true);
      const endpoint = `/ordonnancements/${ordonnancement.id}/documents/${docType}${currentOrdre ? `/${currentOrdre.id}` : ''}`;
      const res = await api.get(endpoint, { responseType: 'blob' });
      const mimeType = res.data.type || res.headers['content-type'] || 'application/pdf';
      const fileUrl = window.URL.createObjectURL(new Blob([res.data], { type: mimeType }));
      const link = document.createElement('a');
      link.href = fileUrl;
      const docLabel = docType === 'etat_liquidation' ? 'Etat_Liquidation' : (docType === 'op_ras_is' ? 'OP_RAS_IS' : (docType === 'op_ras_tva' ? 'OP_RAS_TVA' : (docType === 'op_ras' ? 'OP_RAS' : docType.toUpperCase())));
      const safeNum = (ordonnancement.num_ordonnancement || ordonnancement.id).replace(/[^a-zA-Z0-9_\-]/g, '_');
      link.setAttribute('download', `${docLabel}_${safeNum}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(fileUrl);
    } catch (err) {
      console.error('Erreur téléchargement PDF:', err);
      alert('Erreur lors du téléchargement du document PDF.');
    } finally {
      setDownloading(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const formatDH = (val) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(val || 0) + ' DH';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-md p-2 md:p-4 overflow-y-auto">
      <div className="bg-white w-full max-w-6xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col h-[94vh] animate-in fade-in zoom-in duration-200">
        
        {/* Modal Header */}
        <div className="px-6 py-4 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex items-center justify-between shadow-md shrink-0">
          <div className="flex items-center gap-3">
            <div className="p-2.5 bg-white/10 rounded-xl backdrop-blur-sm border border-white/10 shadow-inner">
              <FileText className="w-5 h-5 text-cyan-300" />
            </div>
            <div>
              <h2 className="text-base font-extrabold tracking-tight">
                {docType === 'op_ras_is' && 'ORDRE DE PAIEMENT - RETENUE À LA SOURCE (RAS / IS) - APERÇU'}
                {docType === 'op_ras_tva' && 'ORDRE DE PAIEMENT - RETENUE À LA SOURCE (RAS / TVA) - APERÇU'}
                {docType === 'op_ras' && 'ORDRE DE PAIEMENT - RETENUE À LA SOURCE (RAS) - APERÇU'}
                {docType === 'op' && !isRas && 'ORDRE DE PAIEMENT (OP) - APERÇU'}
                {docType === 'op' && isRas && 'ORDRE DE PAIEMENT - RETENUE À LA SOURCE (RAS) - APERÇU'}
                {docType === 'ov' && 'ORDRE DE VIREMENT (OV) - APERÇU'}
                {docType === 'oi' && 'ORDRE D\'IMPUTATION (OI) - APERÇU'}
                {docType === 'etat_liquidation' && 'ÉTAT DE LIQUIDATION - APERÇU'}
              </h2>
              <p className="text-xs text-cyan-100 font-mono">
                {ordonnancement.num_ordonnancement} • {ordonnancement.reference}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={downloadPdf}
              disabled={downloading}
              className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer"
              title="Télécharger PDF"
            >
              <Download size={15} className={downloading ? 'animate-bounce' : ''} />
              {downloading ? 'Téléchargement...' : 'Télécharger PDF'}
            </button>
            <button
              onClick={handlePrint}
              className="inline-flex items-center gap-1.5 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl transition cursor-pointer"
              title="Imprimer"
            >
              <Printer size={15} />
              Imprimer
            </button>
            <button
              onClick={onClose}
              className="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition ml-2 cursor-pointer"
            >
              <X size={20} />
            </button>
          </div>
        </div>

        {/* Modal Body - Official Document Rendering Full Width */}
        <div className="p-6 md:p-10 overflow-y-auto bg-white flex-1 text-slate-900 font-sans text-sm">
          <div className="w-full max-w-5xl mx-auto">
            
            {/* Top Org Header with Official Enlarged Logos */}
            <div className="flex justify-between items-center border-b-2 border-black pb-4 mb-6">
              <div className="flex items-center gap-3">
                <img 
                  src="/images/logo-onca.png" 
                  alt="Logo ONCA" 
                  className="h-16 md:h-20 w-auto object-contain"
                  onError={(e) => { e.currentTarget.style.display = 'none'; }}
                />
              </div>
              <div className="text-center px-4">
                <h3 className="font-black text-slate-900 text-sm md:text-base uppercase tracking-wider">
                  Direction Régionale du Conseil Agricole
                </h3>
                <div className="text-xs md:text-sm font-bold text-slate-800 tracking-wide mt-0.5">
                  Rabat - Salé - Kénitra
                </div>
              </div>
              <div className="flex items-center gap-3 justify-end">
                <img 
                  src="/images/sceau-maroc.png" 
                  alt="Royaume du Maroc" 
                  className="h-16 md:h-20 w-auto object-contain"
                  onError={(e) => { e.currentTarget.style.display = 'none'; }}
                />
              </div>
            </div>

            {/* 1. ORDRE DE PAIEMENT - RETENUE À LA SOURCE (RAS / IS & RAS / TVA) */}
            {(docType === 'op_ras' || docType === 'op_ras_is' || docType === 'op_ras_tva' || (docType === 'op' && isRas)) && (() => {
              const isTva = (
                docType === 'op_ras_tva' ||
                (docType !== 'op_ras_is' && (currentOrdre?.type_mouvement?.toLowerCase().includes('tva') || currentOrdre?.creance?.toLowerCase().includes('tva')))
              );
              const natureRas = isTva ? 'RAS / TVA' : 'RAS / IS';
              const montantRas = Number(montant || (isTva ? (ordonnancement.retenue_tva || 2730) : (ordonnancement.retenue_ias || 4420)));
              const montantTtc = Number(ordonnancement.liquidation?.montant_brut_ttc || ordonnancement.montant_brut || (montantRas * 5));
              const montantHt = Number(ordonnancement.liquidation?.montant_brut_ht || (montantTtc / 1.2));
              const dateOpStr = ordonnancement.date_ordonnancement 
                ? new Date(ordonnancement.date_ordonnancement).toLocaleDateString('fr-FR')
                : '23/12/2024';
              const numOpClean = (currentOrdre?.num_ordre || ordonnancement.num_op || (isTva ? '49' : '40')).replace(/[^0-9]/g, '') || (isTva ? '49' : '40');
              const suffixeOp = `/DRCA-RSK/${ordonnancement.exercice || '2024'}`;
              const numMarche = ordonnancement.reference || (isTva ? 'Marché N°06/2024/DRCA-RSK' : 'Marché N°08/2024/DRCA-RSK');
              const objetDepense = ordonnancement.intitule_depense || ordonnancement.liquidation?.objet_liquidation || (isTva ? 'l’organisation de voyages d’agriculteurs et de techniciens, en lot unique' : 'L\'organisation de journées de sensibilisation des agriculteurs');
              const factureRef = ordonnancement.liquidation?.num_facture 
                ? `Facture N° ${ordonnancement.liquidation.num_facture} du ${dateOpStr}` 
                : (isTva ? 'Facture N° 04-2024 du 23-12-2024' : 'Facture N° 04/2024-II-S du 12/12/2024');
              const oiRef = isTva ? `OI N°39/${ordonnancement.exercice || '2024'}/DRCA-RSK` : `OI N°26/${ordonnancement.exercice || '2024'}/DRCA-RSK`;

              return (
                <div className="space-y-4 text-xs">
                  {/* Budget & Nature RAS Box */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-64 text-[11px]">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/2 p-1 border-r border-black bg-white">Budget</td>
                          <td className="w-1/2 p-1">{ordonnancement.budget_type || 'Investissement'}</td>
                        </tr>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-white">Déclaration</td>
                          <td className="p-1">Retenue à la source</td>
                        </tr>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-white">Exercice</td>
                          <td className="p-1">{ordonnancement.exercice || '2024'}</td>
                        </tr>
                        <tr>
                          <td className="p-1 border-r border-black bg-white">Nature de la RAS</td>
                          <td className="p-1">{natureRas}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Document Title */}
                  <div className="text-center my-3">
                    <h1 className="text-base font-black text-black tracking-wide uppercase underline decoration-2 underline-offset-4">
                      ORDRE DE PAIEMENT (OP)
                    </h1>
                  </div>

                  {/* OP N° and DATE */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-64 text-xs">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/3 p-1.5 border-r border-black bg-white">OP N°</td>
                          <td className="w-1/4 p-1.5 border-r border-black text-sm">{numOpClean}</td>
                          <td className="p-1.5">{suffixeOp}</td>
                        </tr>
                        <tr>
                          <td colSpan={2} className="p-1.5 border-r border-black bg-white">DATE</td>
                          <td className="p-1.5">{dateOpStr}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Renseignements sur la dépense */}
                  <div className="border-2 border-black overflow-hidden">
                    <div className="bg-[#e5e0d8] border-b-2 border-black text-center font-bold py-1 uppercase text-black text-xs">
                      RENSEIGNEMENTS SUR LA DEPENSE
                    </div>
                    <table className="w-full border-collapse">
                      <tbody className="divide-y divide-black font-medium">
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-white text-black">BENEFICIAIRE</td>
                          <td colSpan={3} className="p-2 font-bold text-black text-xs">Receveur de l’administration fiscale</td>
                        </tr>
                        {isTva ? (
                          <tr>
                            <td className="p-2 font-bold border-r border-black bg-white text-black leading-tight">
                              OPERATEUR<br />CHARGE DE<br />TELEPAIEMENT
                            </td>
                            <td className="w-2/5 p-2 font-bold text-black uppercase text-[11px] border-r border-black">
                              OFFICE NATIONAL DU CONSEIL AGRICOLE
                            </td>
                            <td colSpan={2} className="p-2 font-mono font-bold text-black text-center text-xs">
                              310 810 100 002 470 105 200 152
                            </td>
                          </tr>
                        ) : (
                          <tr>
                            <td className="p-2 font-bold border-r border-black bg-white text-black leading-tight">
                              Société soumise à<br />la retenue à la source
                            </td>
                            <td colSpan={3} className="p-2">
                              <div className="font-bold text-black uppercase text-xs">{ordonnancement.fournisseur?.raison_sociale || ordonnancement.beneficiaire_nom}</div>
                              <div className="text-[10px] text-slate-700 mt-0.5">{ordonnancement.fournisseur?.adresse || 'APPT N°1 Imm 10 Bloc G Al Majd Partie 04 Beni Mellal'}</div>
                            </td>
                          </tr>
                        )}
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">MODE DE PAIEMENT</td>
                          <td colSpan={3} className="p-2 font-bold text-black uppercase">TELEPAIEMENT</td>
                        </tr>
                        <tr>
                          <td rowSpan={2} className="p-2 font-bold border-r border-black bg-white text-black text-center align-middle">OBJET</td>
                          <td className="w-2/5 p-2 font-bold border-r border-black text-center bg-white text-black">
                            Retenue a la source concernants
                          </td>
                          <td colSpan={2} className="p-2 font-bold text-center text-black">
                            {numMarche}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black text-center bg-white text-black">
                            Pour objet de :
                          </td>
                          <td colSpan={2} className="p-2 font-bold text-black text-justify text-[11px] leading-snug">
                            {objetDepense}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Référence</td>
                          <td colSpan={3} className="p-2 font-bold text-black">{numMarche}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Montant / Pièces jointes / Mode de paiement */}
                  <div className="border-2 border-black overflow-hidden">
                    <table className="w-full border-collapse">
                      <thead>
                        <tr className="bg-white border-b-2 border-black font-bold text-black text-center text-[11px]">
                          <th className="w-1/4 p-2 border-r border-black">MONTANT (DH)</th>
                          <th className="w-1/2 p-2 border-r border-black">PIECES JOINTES:</th>
                          <th className="w-1/4 p-2">MODE DE PAIEMENT</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-black font-medium">
                        <tr>
                          <td rowSpan={isTva ? 3 : 4} className="p-3 text-center align-middle font-black text-base text-black border-r border-black">
                            {formatDH(montantRas)}
                          </td>
                          <td className="p-2 border-r border-black text-black">
                            <strong className="mr-2">1</strong> {factureRef}
                          </td>
                          <td rowSpan={isTva ? 3 : 4} className="p-3 text-center align-middle font-bold text-sm text-black">
                            VIREMENT
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 border-r border-black text-black">
                            <strong className="mr-2">2</strong> {oiRef}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 border-r border-black text-black">
                            <strong className="mr-2">3</strong> Etat de liquidation
                          </td>
                        </tr>
                        {!isTva && (
                          <tr>
                            <td className="p-2 border-r border-black text-black">
                              <strong className="mr-2">4</strong> Autorisation de TELEPAIEMENT
                            </td>
                          </tr>
                        )}
                        <tr className="border-t-2 border-black">
                          <td className="p-2 text-center font-bold bg-white border-r border-black text-black leading-tight">
                            SOMME A PAYER<br /><span className="text-[10px] font-normal">(en lettres)</span>
                          </td>
                          <td colSpan={2} className="p-2 font-bold text-black uppercase text-xs">
                            {numberToFrenchWords(montantRas)}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Imputation Comptable */}
                  <div className="border-2 border-black overflow-hidden text-center">
                    <div className="bg-[#e5e0d8] border-b-2 border-black font-bold py-1 uppercase text-black text-xs">
                      IMPUTATION COMPTABLE
                    </div>
                    <table className="w-full border-collapse text-xs font-bold">
                      <thead>
                        <tr className="bg-[#e5e0d8] border-b border-black text-[11px]">
                          <th className="p-1.5 border-r border-black">Chap</th>
                          <th className="p-1.5 border-r border-black">Art</th>
                          <th className="p-1.5 border-r border-black">Parag</th>
                          <th className="p-1.5 border-r border-black">Ligne</th>
                          <th className="p-1.5 border-r border-black w-1/4">Montant HT</th>
                          <th className="p-1.5 w-1/4">Montant de la RAS</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-black">
                        <tr>
                          <td className="p-2 border-r border-black">{ordonnancement.chapitre || ''}</td>
                          <td className="p-2 border-r border-black">{ordonnancement.article || '415'}</td>
                          <td className="p-2 border-r border-black">{ordonnancement.paragraphe || '20'}</td>
                          <td className="p-2 border-r border-black">{ordonnancement.ligne || (isTva ? '14' : '12')}</td>
                          <td className="p-2 border-r border-black font-mono">{formatDH(montantHt)}</td>
                          <td className="p-2 font-mono">{formatDH(montantRas)}</td>
                        </tr>
                        <tr className="bg-[#e5e0d8]">
                          <td colSpan={4} className="p-1 text-center font-bold text-[11px] border-r border-black">
                            INTITULE DE LA RUBRIQUE
                          </td>
                          <td colSpan={2} className="p-1 bg-white"></td>
                        </tr>
                        <tr>
                          <td colSpan={4} className="p-2 text-left text-[11px] font-normal border-r border-black">
                            {ordonnancement.intitule_depense || (isTva ? 'Frais de voyage des agriculteurs et techniciens' : 'Frais d\'organisation et de participation aux journées de sensibilisation...')}
                          </td>
                          <td colSpan={2} className="p-2 text-center">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Cadre de visas et signatures */}
                  <div className="border-2 border-black overflow-hidden mt-4">
                    <div className="grid grid-cols-2 text-center font-bold text-xs">
                      <div className="bg-[#e5e0d8] border-r-2 border-black py-1.5">VISA DU SOUS-ORDONNATEUR</div>
                      <div className="bg-[#e5e0d8] py-1.5">VISA DU FONDE DE POUVOIRS</div>
                    </div>
                    <div className="grid grid-cols-2 h-24">
                      <div className="border-r-2 border-black"></div>
                      <div></div>
                    </div>
                  </div>

                </div>
              );
            })()}

            {/* 1.bis ORDRE DE PAIEMENT STANDARD (SANS RAS / FOURNISSEUR) */}
            {docType === 'op' && !isRas && (() => {
              const numOpClean = (currentOrdre?.num_ordre || ordonnancement.num_op || '38').replace(/[^0-9]/g, '') || '38';
              const exerciceStr = ordonnancement.exercice || '2024';
              const exerciceOrigine = ordonnancement.marche?.exercice || exerciceStr;
              const suffixeOp = `/DRCA-RSK/${exerciceStr}`;
              const dateOpStr = ordonnancement.date_ordonnancement 
                ? new Date(ordonnancement.date_ordonnancement).toLocaleDateString('fr-FR')
                : '03/12/2024';
              const montantPaiement = Number(montant || ordonnancement.net_a_payer || ordonnancement.montant_brut || 61892.50);
              const montantEngagement = Number(ordonnancement.engagement_montant || ordonnancement.montant_brut || montantPaiement);
              const refMarche = ordonnancement.reference || ordonnancement.marche?.num_marche || `BC N°01/INV/${exerciceStr}/DRCA-RSK`;
              const objetDepense = ordonnancement.intitule_depense || ordonnancement.liquidation?.objet_liquidation || ordonnancement.marche?.objet_marche || 'Acquisition des semences et engrais pour les Ecoles aux champs de la Région de Rabat-Salé-Kénitra';
              const feRef = ordonnancement.marche?.num_engagement 
                ? `Fiche d'engagement N°${ordonnancement.marche.num_engagement} Du ${ordonnancement.marche.date_engagement ? new Date(ordonnancement.marche.date_engagement).toLocaleDateString('fr-FR') : dateOpStr}`
                : `Fiche d'engagement N°12/${exerciceStr}/FE/DRCA-RSK Du ${dateOpStr}`;
              const docRefMarche = refMarche ? `${refMarche} du ${dateOpStr}` : `Bon de commande N°01/INV/${exerciceStr}/DRCA-RSK du ${dateOpStr}`;
              const dateReception = ordonnancement.liquidation?.date_reception 
                ? new Date(ordonnancement.liquidation.date_reception).toLocaleDateString('fr-FR') 
                : dateOpStr;
              const factureRef = ordonnancement.liquidation?.num_facture 
                ? `Facture N°${ordonnancement.liquidation.num_facture} du ${ordonnancement.liquidation.date_facture ? new Date(ordonnancement.liquidation.date_facture).toLocaleDateString('fr-FR') : dateOpStr}`
                : (ordonnancement.liquidation?.num_decompte ? `Décompte N°${ordonnancement.liquidation.num_decompte}` : `Facture N°12/CADG/${exerciceStr} du ${dateOpStr}`);
              const oiRef = ordonnancement.num_oi ? `OI N°${ordonnancement.num_oi}/DRCA-RSK/${exerciceStr}` : `OI N°28/DRCA-RSK/${exerciceStr}`;
              const art = ordonnancement.article || ordonnancement.notificationLigne?.article || '415';
              const par = ordonnancement.paragraphe || ordonnancement.notificationLigne?.paragraphe || '20';
              const lig = ordonnancement.ligne || ordonnancement.notificationLigne?.ligne_budgetaire || '13';
              const intituleRubrique = ordonnancement.intitule_rubrique || ordonnancement.notificationLigne?.intitule || 'Essais de démonstration et achat des intrants pour FFS';

              return (
                <div className="space-y-4 text-xs text-black">
                  {/* Tableau Budget */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-64 text-[11px]">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/2 p-1 border-r border-black bg-white">Budget</td>
                          <td className="w-1/2 p-1">{ordonnancement.budget_type || 'Investissement'}</td>
                        </tr>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-white">Crédit</td>
                          <td className="p-1">{ordonnancement.type_credit || 'C.Neufs'}</td>
                        </tr>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-white">Exercice</td>
                          <td className="p-1">{exerciceStr}</td>
                        </tr>
                        <tr>
                          <td className="p-1 border-r border-black bg-white">Exercice origine</td>
                          <td className="p-1">{exerciceOrigine}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Document Title */}
                  <div className="text-center my-2">
                    <h1 className="text-base font-black text-black tracking-wide uppercase underline decoration-2 underline-offset-4">
                      ORDRE DE PAIEMENT (OP)
                    </h1>
                  </div>

                  {/* OP N° and DATE */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-64 text-xs">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/3 p-1.5 border-r border-black bg-white">OP N°</td>
                          <td className="w-1/4 p-1.5 border-r border-black text-sm">{numOpClean}</td>
                          <td className="p-1.5">{suffixeOp}</td>
                        </tr>
                        <tr>
                          <td colSpan={2} className="p-1.5 border-r border-black bg-white">DATE</td>
                          <td className="p-1.5">{dateOpStr}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Renseignements sur la dépense */}
                  <div className="border-2 border-black overflow-hidden">
                    <div className="bg-[#e5e0d8] border-b-2 border-black text-center font-bold py-1 uppercase text-black text-xs">
                      RENSEIGNEMENTS SUR LA DEPENSE
                    </div>
                    <table className="w-full border-collapse">
                      <tbody className="divide-y divide-black font-medium">
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-white text-black">BENEFICIAIRE</td>
                          <td className="p-2">
                            <div className="font-bold text-black uppercase text-xs">{beneficiaire}</div>
                            {(ordonnancement.fournisseur?.adresse || ordonnancement.marche?.fournisseur?.adresse) && (
                              <div className="text-[10px] text-slate-700 mt-0.5">
                                {ordonnancement.fournisseur?.adresse || ordonnancement.marche?.fournisseur?.adresse}
                              </div>
                            )}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">RIB N°</td>
                          <td className="p-2 font-mono font-bold text-black text-xs">
                            {rib}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">OBJET</td>
                          <td className="p-2 font-bold text-black text-justify text-[11px] leading-snug">
                            {objetDepense}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Référence</td>
                          <td className="p-2 font-bold text-black">{refMarche}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Montant / Pièces jointes / Mode de paiement */}
                  <div className="border-2 border-black overflow-hidden">
                    <table className="w-full border-collapse">
                      <thead>
                        <tr className="bg-white border-b-2 border-black font-bold text-black text-center text-[11px]">
                          <th className="w-1/4 p-2 border-r border-black">MONTANT (DH)</th>
                          <th className="w-1/2 p-2 border-r border-black">PIECES JOINTES:</th>
                          <th className="w-1/4 p-2">MODE DE PAIEMENT</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-black font-medium">
                        <tr>
                          <td rowSpan={10} className="p-3 text-center align-middle font-black text-base text-black border-r border-black">
                            {formatDH(montantPaiement)}
                          </td>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">1</strong> Avis d'achat
                          </td>
                          <td rowSpan={10} className="p-3 text-center align-middle font-bold text-sm text-black uppercase">
                            {modePaiement || 'VIREMENT'}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">2</strong> PV D'examen des devis
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">3</strong> Lettre de confirmation
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">4</strong> Devis
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">5</strong> Bon de commande dématérialisé
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">6</strong> {feRef}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">7</strong> {docRefMarche}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">8</strong> PV de réception définitive du {dateReception}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">9</strong> {factureRef}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-1.5 border-r border-black text-black">
                            <strong className="mr-2">10</strong> {oiRef}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Somme à payer en lettres */}
                  <div className="border-2 border-black overflow-hidden">
                    <table className="w-full border-collapse">
                      <tbody>
                        <tr>
                          <td className="w-1/4 p-2 text-center font-bold bg-white border-r border-black text-black leading-tight text-[11px]">
                            SOMME A PAYER<br /><span className="text-[10px] font-normal">(en lettres)</span>
                          </td>
                          <td className="p-2 font-bold text-black uppercase text-xs">
                            # {numberToFrenchWords(montantPaiement)} #
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Imputation Comptable */}
                  <div className="border-2 border-black overflow-hidden text-center">
                    <div className="bg-[#e5e0d8] border-b-2 border-black font-bold py-1 uppercase text-black text-xs">
                      IMPUTATION COMPTABLE
                    </div>
                    <table className="w-full border-collapse text-xs font-bold">
                      <thead>
                        <tr className="bg-[#e5e0d8] border-b border-black text-[11px]">
                          <th className="p-1.5 border-r border-black">Chap</th>
                          <th className="p-1.5 border-r border-black">Art</th>
                          <th className="p-1.5 border-r border-black">Parag</th>
                          <th className="p-1.5 border-r border-black">Ligne</th>
                          <th className="p-1.5 border-r border-black w-1/4">ENGAGEMENT (DH)</th>
                          <th className="p-1.5 w-1/4">PAIEMENT (DH)</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-black">
                        <tr>
                          <td className="p-2 border-r border-black">-</td>
                          <td className="p-2 border-r border-black">{art}</td>
                          <td className="p-2 border-r border-black">{par}</td>
                          <td className="p-2 border-r border-black">{lig}</td>
                          <td className="p-2 border-r border-black font-mono">{formatDH(montantEngagement)}</td>
                          <td className="p-2 font-mono">{formatDH(montantPaiement)}</td>
                        </tr>
                        <tr className="bg-[#e5e0d8]">
                          <td colSpan={4} className="p-1 text-center font-bold text-[11px] border-r border-black">
                            INTITULE DE LA RUBRIQUE
                          </td>
                          <td colSpan={2} className="p-1 text-center font-bold text-[11px]">
                            PRESTATION DE MEME NATURE
                          </td>
                        </tr>
                        <tr>
                          <td colSpan={4} className="p-2 text-left text-[11px] font-normal border-r border-black">
                            {intituleRubrique}
                          </td>
                          <td colSpan={2} className="p-2 text-center">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Cadre de visas et signatures */}
                  <div className="border-2 border-black overflow-hidden mt-4">
                    <div className="grid grid-cols-2 text-center font-bold text-xs">
                      <div className="bg-[#e5e0d8] border-r-2 border-black py-1.5">VISA DU SOUS-ORDONNATEUR</div>
                      <div className="bg-[#e5e0d8] py-1.5">VISA DU FONDE DE POUVOIRS</div>
                    </div>
                    <div className="grid grid-cols-2 h-24">
                      <div className="border-r-2 border-black"></div>
                      <div></div>
                    </div>
                  </div>

                </div>
              );
            })()}

            {/* 2. ORDRE DE VIREMENT (OV) */}
            {docType === 'ov' && (() => {
              const numOvClean = (currentOrdre?.num_ordre || '42').replace(/[^0-9]/g, '') || '42';
              const numOpClean = (ordonnancement.num_op || '49').replace(/[^0-9]/g, '') || '49';
              const exerciceStr = ordonnancement.exercice || '2024';
              const dateOvStr = ordonnancement.date_ordonnancement
                ? new Date(ordonnancement.date_ordonnancement).toLocaleDateString('fr-FR')
                : '23/12/2024';
              const montantOv = Number(montant || ordonnancement.retenue_tva || 2730);
              const beneficiaireOv = currentOrdre?.beneficiaire || 'OFFICE NATIONAL DU CONSEIL AGRICOLE';
              const ribOv = currentOrdre?.rib_compte || '310 810 100 002 470 105 200 152';
              const refMarche = ordonnancement.reference || 'Marché N°06/2024/DRCA-RSK';

              return (
                <div className="space-y-6 text-xs text-black">
                  {/* Reference & Date Line */}
                  <div className="flex justify-between items-center font-bold text-xs pt-1 border-b-2 border-black pb-2">
                    <div>
                      /Référence: OV N° &nbsp;&nbsp;&nbsp;&nbsp;<strong className="text-sm">{numOvClean}</strong>&nbsp;&nbsp;&nbsp;&nbsp; /DRCA-RSK/{exerciceStr}
                    </div>
                    <div>
                      Date: &nbsp;&nbsp;&nbsp;&nbsp;<span className="font-bold">{dateOvStr}</span>
                    </div>
                  </div>

                  {/* Title & Recipient Block */}
                  <div className="text-center space-y-1.5 pt-2">
                    <h1 className="text-base font-black uppercase tracking-wider">
                      ORDRE DE VIREMENT DE FOND
                    </h1>
                    <div className="text-sm font-bold">A</div>
                    <div className="text-xs font-bold uppercase leading-tight">
                      MONSIEUR LE TRESORIER GENERAL DU ROYAUME<br />
                      CHEF DE L'AGENCE BANCAIRE DE KENITRA
                    </div>
                  </div>

                  {/* Intro text */}
                  <p className="text-xs leading-relaxed text-justify">
                    Par le débit de notre compte courant N° <strong className="font-mono">310330100602470154760181</strong> , ONCA DR RABAT-SALE-KENITRA {(ordonnancement.budget_type || 'INVESTISSEMENT').toUpperCase()},ouvert dans vos livres, veuillez virer :
                  </p>

                  {/* Details List */}
                  <div className="space-y-2.5 pl-2 text-xs">
                    <div className="flex items-baseline">
                      <span className="w-44 text-slate-700">La somme de</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-black text-sm text-black">{formatDH(montantOv)}</span>
                    </div>
                    <div className="flex items-baseline">
                      <span className="w-44 text-slate-700">En lettres</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-bold uppercase text-black text-[11px]">
                        {numberToFrenchWords(montantOv)}
                      </span>
                    </div>
                    <div className="flex items-baseline">
                      <span className="w-44 text-slate-700">Au profit de</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-bold uppercase text-black">{beneficiaireOv}</span>
                    </div>
                    <div className="flex items-baseline">
                      <span className="w-44 text-slate-700">Titulaire du compte</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-mono font-bold text-black text-xs tracking-wider">
                        {ribOv}
                      </span>
                    </div>
                    <div className="flex items-baseline">
                      <span className="w-44 text-slate-700">Pour fin de règlement de</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-bold text-black">{refMarche}</span>
                    </div>
                    <div className="flex items-baseline pt-2">
                      <span className="w-44 font-bold text-black">OP N°</span>
                      <span className="w-6 font-bold">:</span>
                      <span className="font-bold text-black">
                        <strong>{numOpClean}</strong> &nbsp;&nbsp;&nbsp;&nbsp; /DRCA-RSK/{exerciceStr}
                      </span>
                    </div>
                  </div>

                  {/* Closing phrase */}
                  <p className="text-xs leading-relaxed text-justify pt-2">
                    Dans l’attente de votre avis de débit, veuillez agréer, Monsieur le Trésorier Général Du Royaume, l’expression de nos salutations distinguées.
                  </p>

                  {/* Official Signatures Grid with exact green headers */}
                  <div className="border-2 border-black overflow-hidden mt-8">
                    <div className="grid grid-cols-2 text-center font-bold text-[10px] leading-tight">
                      <div className="bg-[#a3e635] text-black border-r-2 border-black p-2.5 uppercase">
                        LE DIRECTEUR REGIONAL DE L’OFFICE NATIONALE DU CONSEIL AGRICOLE DU Rabat-Salé-Kénitra
                      </div>
                      <div className="bg-[#a3e635] text-black p-2.5 uppercase">
                        LE FONDE DE POUVOIRS DU TRESORIER PAYEUR DE L’ONCA AUPRES DE LA DIRECTION REGIONALE DU Rabat-Salé-Kénitra
                      </div>
                    </div>
                    <div className="grid grid-cols-2 h-28 bg-white">
                      <div className="border-r-2 border-black"></div>
                      <div></div>
                    </div>
                  </div>

                </div>
              );
            })()}

            {/* 3. ORDRE D'IMPUTATION (OI) */}
            {docType === 'oi' && (() => {
              const numOiClean = (currentOrdre?.num_ordre || '41').replace(/[^0-9]/g, '') || '41';
              const exerciceStr = ordonnancement.exercice || '2024';
              const suffixeOi = `/DRCA-RSK/${exerciceStr}`;
              const dateOiStr = ordonnancement.date_ordonnancement
                ? new Date(ordonnancement.date_ordonnancement).toLocaleDateString('fr-FR')
                : '23/12/2024';
              const montantOi = Number(montant || ordonnancement.retenue_tva || ordonnancement.retenue_ias || 3666);
              const refMarche = ordonnancement.reference || 'Convention N°04/2024/DRCA-RSK';
              const objetDepense = ordonnancement.intitule_depense || ordonnancement.liquidation?.objet_liquidation || 'Prestation de transport des agriculteurs pratiquant le semis direct pour assurer leurs participation aux ateliers provinciaux organisés par la DRCA de RSK';
              
              let formeEngagement = 'Convention';
              if (refMarche.toLowerCase().includes('marché')) formeEngagement = 'Marché';
              else if (refMarche.toLowerCase().includes('bc') || refMarche.toLowerCase().includes('bon')) formeEngagement = 'Bon de commande';

              return (
                <div className="space-y-4 text-xs text-black">
                  {/* Top Right Budget Box */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-60 text-[11px]">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/2 p-1 border-r border-black bg-white">BUDGET</td>
                          <td className="w-1/2 p-1">{ordonnancement.budget_type || 'Investissement'}</td>
                        </tr>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-white">EXERCICE :</td>
                          <td className="p-1">{exerciceStr}</td>
                        </tr>
                        <tr>
                          <td className="p-1 border-r border-black bg-white">ANNEE D'ORIGINE:</td>
                          <td className="p-1">{exerciceStr}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Title Banner */}
                  <div className="bg-[#e5e0d8] border-2 border-black py-2 text-center">
                    <h1 className="text-base font-black tracking-wider uppercase m-0 text-black">
                      ORDRE D'IMPUTATION (OI)
                    </h1>
                  </div>

                  {/* N° OI and DATE */}
                  <div className="flex justify-end">
                    <table className="border-collapse border-2 border-black font-bold text-center w-60 text-xs">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="w-1/4 p-1.5 border-r border-black bg-white">N°:</td>
                          <td className="w-1/4 p-1.5 border-r border-black text-sm">{numOiClean}</td>
                          <td className="p-1.5">{suffixeOi}</td>
                        </tr>
                        <tr>
                          <td colSpan={2} className="p-1.5 border-r border-black bg-white">DATE</td>
                          <td className="p-1.5">{dateOiStr}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Renseignements sur l'Imputation */}
                  <div className="border-2 border-black overflow-hidden">
                    <div className="bg-[#e5e0d8] border-b-2 border-black text-center font-bold py-1 uppercase text-black text-xs">
                      RENSEIGNEMENTS SUR L'IMPUTATION
                    </div>
                    <table className="w-full border-collapse font-medium">
                      <tbody className="divide-y divide-black">
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-white text-black">Bénéficiaire</td>
                          <td colSpan={3} className="p-2 font-bold text-black uppercase">DIRECTION GENERALE DES IMPOTS</td>
                        </tr>
                        <tr>
                          <td rowSpan={2} className="p-2 font-bold border-r border-black bg-white text-black text-center align-middle">Objet</td>
                          <td className="w-2/5 p-2 font-bold border-r border-black text-center bg-white text-black">
                            Retenue à la source concernants :
                          </td>
                          <td colSpan={2} className="p-2 font-bold text-center text-black">
                            {refMarche}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black text-center bg-white text-black">
                            Pour objet de :
                          </td>
                          <td colSpan={2} className="p-2 font-bold text-black text-justify text-[11px] leading-snug">
                            {objetDepense}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Forme d'engagement</td>
                          <td colSpan={3} className="p-2 font-bold text-black">{formeEngagement}</td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Refrerence (N°)</td>
                          <td colSpan={3} className="p-2 font-bold text-black">{refMarche}</td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Montant (Dh)</td>
                          <td colSpan={3} className="p-2 font-black text-sm text-right pr-6 font-mono text-black">
                            {formatDH(montantOi)}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Montant en lettre</td>
                          <td colSpan={3} className="p-2 font-bold text-black uppercase text-xs">
                            {numberToFrenchWords(montantOi)}
                          </td>
                        </tr>
                        <tr>
                          <td className="p-2 font-bold border-r border-black bg-white text-black">Mode de règlement</td>
                          <td colSpan={3} className="p-2 font-bold text-black">Virement</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Tableau Imputation Comptable */}
                  <div className="border-2 border-black overflow-hidden text-center">
                    <div className="bg-[#e5e0d8] border-b-2 border-black font-bold py-1 uppercase text-black text-xs">
                      IMPUTATION COMPTABLE
                    </div>
                    <table className="w-full border-collapse text-xs font-bold">
                      <thead>
                        <tr className="bg-[#e5e0d8] border-b border-black">
                          <th colSpan={4} className="p-1 border-r border-black">COMPTABILITE GENERALE</th>
                          <th colSpan={3} className="p-1"></th>
                        </tr>
                        <tr className="border-b border-black">
                          <th colSpan={2} className="p-1 border-r border-black">Débit</th>
                          <th colSpan={2} className="p-1 border-r border-black">Crédit</th>
                          <th className="p-1 border-r border-black">ART</th>
                          <th className="p-1 border-r border-black">PARA</th>
                          <th className="p-1">LIGNE</th>
                        </tr>
                        <tr className="border-b-2 border-black text-[10px]">
                          <th className="p-1 border-r border-black w-1/4">N° Compte</th>
                          <th className="p-1 border-r border-black w-1/8">Montant (Dh)</th>
                          <th className="p-1 border-r border-black w-1/4">N° Compte</th>
                          <th className="p-1 border-r border-black w-1/8">Montant (Dh)</th>
                          <th colSpan={3} className="bg-[#e5e0d8]"></th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-black">
                        <tr>
                          <td className="p-2 border-r border-black text-[10px]">
                            <div className="font-mono font-bold">310330100602470154760181</div>
                            <div className="text-slate-600 mt-0.5">T.P KENITRA</div>
                          </td>
                          <td className="p-2 border-r border-black align-middle">-</td>
                          <td className="p-2 border-r border-black align-middle uppercase text-[11px]">TELEPAIEMENT</td>
                          <td className="p-2 border-r border-black align-middle font-mono">{formatDH(montantOi)}</td>
                          <td className="p-2 border-r border-black align-middle">{ordonnancement.article || '415'}</td>
                          <td className="p-2 border-r border-black align-middle">{ordonnancement.paragraphe || '20'}</td>
                          <td className="p-2 align-middle">{ordonnancement.ligne || '14'}</td>
                        </tr>
                        <tr>
                          <td colSpan={4} className="h-12 bg-white border-r border-black"></td>
                          <td colSpan={3} className="p-2 text-center text-[10px] font-normal align-middle leading-tight">
                            {ordonnancement.intitule_depense || 'Frais de voyage des agriculteurs et techniciens'}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Visa du Sous-Ordonnateur */}
                  <div className="border-2 border-black overflow-hidden mt-4">
                    <div className="bg-[#e5e0d8] border-b-2 border-black text-center font-bold py-1.5 text-xs text-black uppercase">
                      VISA DU SOUS-ORDONNATEUR
                    </div>
                    <div className="h-28 bg-white"></div>
                  </div>

                </div>
              );
            })()}

            {/* 4. ÉTAT DE LIQUIDATION */}
            {docType === 'etat_liquidation' && (() => {
              const montantTtc = Number(ordonnancement.liquidation?.montant_brut_ttc || ordonnancement.liquidation?.montant_ttc || ordonnancement.montant_brut || 0);
              const montantHt = Number(ordonnancement.liquidation?.montant_brut_ht || ordonnancement.liquidation?.montant_ht || (montantTtc / 1.2));
              const montantTva = Number(ordonnancement.liquidation?.montant_tva || (montantTtc - montantHt));
              const montantRas = Number(ordonnancement.retenue_tva || ordonnancement.ras_total || 0);
              const tauxRas = (montantTva > 0 && montantRas > 0) ? `${Math.round((montantRas / montantTva) * 100)}%` : (montantRas > 0 ? '75%' : '0%');
              const netAVerser = Number(ordonnancement.net_a_payer || (montantTtc - montantRas));

              const dateLiq = ordonnancement.liquidation?.date_decompte
                ? new Date(ordonnancement.liquidation.date_decompte).toLocaleDateString('fr-FR')
                : (ordonnancement.date_ordonnancement ? new Date(ordonnancement.date_ordonnancement).toLocaleDateString('fr-FR') : new Date().toLocaleDateString('fr-FR'));

              const factureRef = ordonnancement.liquidation?.num_facture
                ? `Facture N°${ordonnancement.liquidation.num_facture} du ${dateLiq}`
                : (ordonnancement.liquidation?.num_decompte
                  ? `Décompte N°${ordonnancement.liquidation.num_decompte} du ${dateLiq}`
                  : `Facture N°${ordonnancement.liquidation?.num_liquidation || '010/' + (ordonnancement.exercice || '2024')} du ${dateLiq}`);

              return (
                <div className="space-y-4">
                  {/* Budget / Exercice header box */}
                  <div className="flex justify-end mb-2">
                    <table className="border-collapse border border-black text-xs font-bold w-64 text-center">
                      <tbody>
                        <tr className="border-b border-black">
                          <td className="p-1 border-r border-black bg-slate-50 w-1/2">BUDGET</td>
                          <td className="p-1 w-1/2">{ordonnancement.budget_type || 'Investissement'}</td>
                        </tr>
                        <tr>
                          <td className="p-1 border-r border-black bg-slate-50">EXERCICE :</td>
                          <td className="p-1">{ordonnancement.exercice || '2024'}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Main Banner Title */}
                  <div className="bg-[#e5e0d8] border border-black py-2 text-center">
                    <h1 className="text-base font-black tracking-wider uppercase m-0 text-slate-900">
                      ETAT DE LIQUIDATION
                    </h1>
                  </div>

                  {/* Date Banner */}
                  <div className="bg-[#e5e0d8] border border-black py-1 text-center text-xs font-bold text-slate-900">
                    DATE : {dateLiq}
                  </div>

                  {/* Table Renseignements */}
                  <div className="border border-black overflow-hidden text-xs">
                    <div className="bg-[#e5e0d8] border-b border-black text-center font-bold py-1.5 uppercase text-slate-900">
                      RENSEIGNEMENTS SUR L'ETAT DE LIQUIDATION
                    </div>
                    <table className="w-full border-collapse">
                      <tbody className="divide-y divide-black">
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Marché N°</td>
                          <td className="p-2 font-bold text-slate-900">{ordonnancement.reference || '-'}</td>
                        </tr>
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Objet</td>
                          <td className="p-2 font-bold text-slate-900 text-justify">
                            {ordonnancement.intitule_depense || ordonnancement.liquidation?.objet_liquidation || '-'}
                          </td>
                        </tr>
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Facture</td>
                          <td className="p-2 font-bold text-slate-900">{factureRef}</td>
                        </tr>
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Au profit de</td>
                          <td className="p-2 font-bold text-slate-900">{beneficiaire}</td>
                        </tr>
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Montant (Dh)</td>
                          <td className="p-2 font-bold text-center text-slate-900">{formatDH(montantTtc)}</td>
                        </tr>
                        <tr>
                          <td className="w-1/4 p-2 font-bold border-r border-black bg-slate-50 text-slate-800">Montant en lettre</td>
                          <td className="p-2 font-bold text-center uppercase text-slate-900 italic text-[11px] leading-relaxed">
                            {numberToFrenchWords(montantTtc).toUpperCase()}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Table Retenue à la source / TVA */}
                  <div className="border border-black overflow-hidden text-[11px] text-center">
                    <div className="bg-[#e5e0d8] border-b border-black text-center font-bold py-1.5 text-slate-900">
                      Etat descriptif de la Retenue à la source/ TVA
                    </div>
                    <table className="w-full border-collapse">
                      <thead>
                        <tr className="bg-[#e5e0d8] border-b border-black text-[10px] font-bold text-slate-900 leading-tight">
                          <th className="p-2 border-r border-black">Montant TTC</th>
                          <th className="p-2 border-r border-black">Montant HT</th>
                          <th className="p-2 border-r border-black">MONTANT DE<br />LA TVA</th>
                          <th className="p-2 border-r border-black">Taux de la Retenue<br />à la source</th>
                          <th className="p-2 border-r border-black">Montant de la<br />retenue à la<br />source en DH</th>
                          <th className="p-2 border-r border-black">MONTANT DE<br />LA RAS ARRONDI AU<br />DH SUPERIEUR</th>
                          <th className="p-2">MT A VERSER AU<br />PRESTATAIRE</th>
                        </tr>
                      </thead>
                      <tbody className="font-bold text-slate-900">
                        <tr>
                          <td className="p-2.5 border-r border-black">{formatDH(montantTtc)}</td>
                          <td className="p-2.5 border-r border-black">{formatDH(montantHt)}</td>
                          <td className="p-2.5 border-r border-black">{formatDH(montantTva)}</td>
                          <td className="p-2.5 border-r border-black">{tauxRas}</td>
                          <td className="p-2.5 border-r border-black">{formatDH(montantRas)}</td>
                          <td className="p-2.5 border-r border-black">{formatDH(Math.ceil(montantRas))}</td>
                          <td className="p-2.5">{formatDH(netAVerser)}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Visa du Sous-Ordonnateur */}
                  <div className="border border-black overflow-hidden mt-4">
                    <div className="bg-[#e5e0d8] border-b border-black text-center font-bold py-1.5 text-xs text-slate-900 uppercase">
                      VISA DU SOUS-ORDONNATEUR
                    </div>
                    <div className="h-28 bg-white"></div>
                  </div>
                </div>
              );
            })()}

            {/* Signature Block (Double Signatures Box for OI) */}
            {docType === 'oi' && (
              <div className="mt-12 pt-4 grid grid-cols-2 gap-4">
                <div className="border-2 border-emerald-700 bg-emerald-50/60 rounded-lg p-3 text-center text-[11px] font-bold text-emerald-950 h-28 flex flex-col justify-between">
                  <span>LE DIRECTEUR RÉGIONAL DE L'OFFICE NATIONAL DU CONSEIL AGRICOLE DE RABAT-SALÉ-KÉNITRA</span>
                  <span className="text-[10px] font-normal text-slate-400 italic">Signature & Cachet</span>
                </div>
                <div className="border-2 border-emerald-700 bg-emerald-50/60 rounded-lg p-3 text-center text-[11px] font-bold text-emerald-950 h-28 flex flex-col justify-between">
                  <span>LE FONDÉ DE POUVOIRS DU TRÉSORIER PAYEUR DE L'ONCA AUPRÈS DE LA DRCA-RSK</span>
                  <span className="text-[10px] font-normal text-slate-400 italic">Signature & Cachet</span>
                </div>
              </div>
            )}

          </div>
        </div>

        {/* Modal Footer */}
        <div className="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
          <div>Document officiel généré pour l'ERP budgétaire DRCA-RSK</div>
          <button
            onClick={onClose}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg font-semibold transition"
          >
            Fermer
          </button>
        </div>

      </div>
    </div>
  );
}
