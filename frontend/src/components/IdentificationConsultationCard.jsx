import React from 'react';
import { Building2, Lock } from 'lucide-react';

const formatCurrency = (val) => {
  if (val === null || val === undefined || val === '') return '—';
  const num = parseFloat(String(val).replace(',', '.'));
  if (isNaN(num)) return '—';
  return num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH';
};

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return '—';
  if (typeof dateStr === 'string') {
    const match = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
      return `${match[3]}/${match[2]}/${match[1]}`;
    }
  }
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return String(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  } catch (e) {
    return String(dateStr);
  }
};

const formatFullDateTime = (dateStr, heureStr) => {
  if (!dateStr && !heureStr) return '—';
  const formattedDate = dateStr ? formatDateDisplay(dateStr) : '';
  if (heureStr) {
    return `${formattedDate} à ${heureStr}`;
  }
  return formattedDate || '—';
};

export default function IdentificationConsultationCard({ formData }) {
  // 1. Calcul de l'estimation de l'administration et des seuils réglementaires
  const getEstimationValue = () => {
    if (Array.isArray(formData.lots_details) && formData.lots_details.length > 0) {
      let sumItemsHT = 0;
      let hasItems = false;
      formData.lots_details.forEach(lot => {
        if (Array.isArray(lot.items) && lot.items.length > 0) {
          lot.items.forEach(item => {
            const q = parseFloat(String(item.quantite ?? '').replace(',', '.')) || 0;
            const pu = parseFloat(String(item.prix_unitaire_ht ?? '').replace(',', '.')) || 0;
            if (q > 0 && pu > 0) {
              sumItemsHT += q * pu;
              hasItems = true;
            }
          });
        }
      });
      if (hasItems && sumItemsHT > 0) {
        return sumItemsHT * 1.20;
      }

      const sumEstimation = formData.lots_details.reduce((acc, lot) => {
        const est = parseFloat(String(lot.estimation ?? '').replace(',', '.')) || 0;
        return acc + est;
      }, 0);
      if (sumEstimation > 0) {
        return sumEstimation;
      }
    }

    if (formData.budget) {
      const b = parseFloat(String(formData.budget).replace(',', '.'));
      if (!isNaN(b) && b > 0) {
        return b;
      }
    }

    return null;
  };

  const montantEstimation = getEstimationValue();
  const montantBasse = montantEstimation !== null ? montantEstimation * 0.75 : null;
  const montantExcessive = montantEstimation !== null ? montantEstimation * 1.20 : null;

  // 2. Préparation et structuration des publications dans les journaux
  const getJournauxList = () => {
    const list = [];
    if (Array.isArray(formData.publications_journaux) && formData.publications_journaux.length > 0) {
      formData.publications_journaux.forEach((j) => {
        if (j.nom_journal || j.numero_journal || j.edition || j.date_publication || j.reference_publication) {
          list.push({
            nom_journal: j.nom_journal || '',
            numero_journal: j.numero_journal || '',
            date_publication: j.date_publication || '',
            edition: j.edition || '',
            langue: j.langue || 'Français',
            reference_publication: j.reference_publication || ''
          });
        }
      });
    }

    // Fallback rétrocompatible aux anciens champs si publications_journaux est vide
    if (list.length === 0) {
      if (formData.journal_fr) {
        list.push({
          nom_journal: formData.journal_fr,
          numero_journal: '',
          date_publication: formData.date_publication_fr || '',
          edition: '',
          langue: 'Français',
          reference_publication: formData.reference_publication_fr || ''
        });
      }
      if (formData.journal_ar) {
        list.push({
          nom_journal: formData.journal_ar,
          numero_journal: '',
          date_publication: formData.date_publication_ar || '',
          edition: '',
          langue: 'Arabe',
          reference_publication: formData.reference_publication_ar || ''
        });
      }
    }

    return list;
  };

  const journaux = getJournauxList();

  // 3. Vérification de l'existence d'un prix de référence réel
  const hasPrixReference = Boolean(
    formData.prix_reference !== null &&
    formData.prix_reference !== undefined &&
    String(formData.prix_reference).trim() !== '' &&
    !isNaN(parseFloat(String(formData.prix_reference).replace(',', '.'))) &&
    parseFloat(String(formData.prix_reference).replace(',', '.')) > 0
  );

  return (
    <div className="bg-white rounded-2xl border-2 border-slate-300 shadow-md overflow-hidden mb-8 print:shadow-none print:border-slate-800 print:mb-4">
      {/* En-tête administratif DRCA RSK */}
      <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-950 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b-2 border-blue-950 print:bg-blue-900">
        <div>
          <div className="flex items-center gap-2">
            <Building2 className="text-amber-400" size={20} />
            <span className="text-lg font-black tracking-wider uppercase text-amber-300 font-mono">
              DRCA RSK
            </span>
          </div>
          <h2 className="text-xl sm:text-2xl font-black tracking-wide text-white mt-0.5 uppercase">
            Identification de la consultation
          </h2>
        </div>

        <div className="flex items-center gap-2 self-start sm:self-center">
          <span className="inline-flex items-center gap-1.5 bg-blue-950/70 text-blue-100 text-xs font-bold px-3 py-1.5 rounded-full border border-blue-700/60 shadow-inner">
            <Lock size={13} className="text-amber-400" />
            Informations issues de la préparation (Lecture seule)
          </span>
        </div>
      </div>

      {/* Tableau administratif de synthèse multi-colonnes */}
      <div className="overflow-x-auto">
        <table className="w-full text-left border-collapse table-fixed min-w-[700px]">
          <colgroup>
            <col className="w-[28%]" />
            <col className="w-[22%]" />
            <col className="w-[28%]" />
            <col className="w-[22%]" />
          </colgroup>
          <tbody className="divide-y divide-slate-200 text-sm">

            {/* 1. Référence */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Référence :
              </th>
              <td colSpan={3} className="px-6 py-3.5 font-mono font-black text-slate-900 text-base">
                {formData.num_aoo || formData.reference || 'Non renseignée'}
              </td>
            </tr>

            {/* 2. Mode de passation */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Mode de passation :
              </th>
              <td colSpan={3} className="px-6 py-3.5 font-bold text-slate-800 uppercase tracking-wide">
                {formData.mode_passation || 'D’APPEL D’OFFRES OUVERT SIMPLIFIÉ SUR OFFRES DE PRIX'}
              </td>
            </tr>

            {/* 3. Objet de la consultation */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Objet de la consultation :
              </th>
              <td colSpan={3} className="px-6 py-3.5 font-semibold text-slate-900 text-sm leading-relaxed">
                {formData.objet || '—'}
              </td>
            </tr>

            {/* 4. Date et heure limite de remise des plis (Ligne séparée) */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Date et heure limite de remise des plis :
              </th>
              <td colSpan={3} className="px-6 py-3.5 font-bold text-slate-900 font-mono text-sm">
                {formatFullDateTime(formData.date_ouverture, formData.heure_ouverture)}
              </td>
            </tr>

            {/* 5. Lieu (Ligne séparée distincte) */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Lieu :
              </th>
              <td colSpan={3} className="px-6 py-3.5 text-slate-800 text-sm leading-relaxed font-medium">
                {formData.lieu_ouverture || 'Siège de la direction régionale du conseil agricole Rabat-Salé-Kénitra sis à angle avenue Mohamed V et Rue Sebta Kenitra'}
              </td>
            </tr>

            {/* 6. Publication dans les journaux */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200 align-top">
                Publication dans les journaux :
              </th>
              <td colSpan={3} className="px-6 py-3.5 text-slate-800">
                {journaux.length > 0 ? (
                  <div className="space-y-3">
                    {journaux.map((j, idx) => {
                      const parts = [];
                      if (j.nom_journal) parts.push(`Journal ${j.nom_journal}`);
                      if (j.numero_journal) parts.push(`N° ${j.numero_journal}`);
                      const datePart = j.edition || (j.date_publication ? formatDateDisplay(j.date_publication) : '');
                      if (datePart) parts.push(`du ${datePart}`);
                      const titreJournal = parts.join(' ') || (j.nom_journal ? `Journal ${j.nom_journal}` : `Publication #${idx + 1}`);

                      return (
                        <div key={idx} className="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                          <div className="font-bold text-slate-900 flex items-center gap-2">
                            <span className="text-blue-600 font-black">•</span>
                            <span>{titreJournal}</span>
                          </div>

                          <div className="pl-4 text-xs space-y-0.5 text-slate-600">
                            {j.reference_publication && (
                              <div>
                                <span className="font-semibold text-slate-700">Référence :</span>{' '}
                                <span className="font-mono">{j.reference_publication}</span>
                              </div>
                            )}
                            {j.langue && (
                              <div>
                                <span className="font-semibold text-slate-700">Langue :</span>{' '}
                                <span>{j.langue}</span>
                              </div>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <span className="text-slate-400 italic">Aucune publication dans les journaux enregistrée</span>
                )}
              </td>
            </tr>

            {/* 7. Publication sur le portail des MP */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Publication sur le portail des MP :
              </th>
              <td colSpan={3} className="px-6 py-3.5 font-bold text-slate-800">
                <span className="font-mono text-slate-900">
                  {formData.date_publication_portail
                    ? formatDateDisplay(formData.date_publication_portail)
                    : (formData.date_publication_fr ? formatDateDisplay(formData.date_publication_fr) : '—')}
                </span>
                {formData.ref_publication_portail && (
                  <div className="text-xs font-normal text-slate-600 mt-1">
                    <span className="font-semibold text-slate-700">Référence portail :</span>{' '}
                    <span className="font-mono">{formData.ref_publication_portail}</span>
                  </div>
                )}
              </td>
            </tr>

            {/* 8. GRILLE FINANCIÈRE : Estimation (gauche) & Offre anormalement Basse / Excessive (droite) */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              {/* Colonne 1 : Libellé Estimation */}
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Estimation de l'administration :
              </th>
              {/* Colonne 2 : Montant Estimation */}
              <td className="px-6 py-3.5 font-mono font-black text-slate-900 text-base border-r border-slate-200">
                {montantEstimation !== null ? formatCurrency(montantEstimation) : '—'}
              </td>
              {/* Colonne 3 : Libellé Offre anormalement Basse */}
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Offre anormalement Basse :
              </th>
              {/* Colonne 4 : Montant Offre anormalement Basse */}
              <td className="px-6 py-3.5 font-mono font-bold text-amber-700 text-base">
                {montantBasse !== null ? formatCurrency(montantBasse) : '—'}
              </td>
            </tr>

            {/* Deuxième ligne financière : Offre Excessive (à droite, sous l'offre anormalement basse) */}
            <tr className="hover:bg-slate-50/50 transition-colors">
              {/* Colonnes 1 et 2 vides à gauche */}
              <td colSpan={2} className="px-6 py-3.5 bg-slate-50/30 border-r border-slate-200"></td>
              {/* Colonne 3 : Libellé Offre Excessive */}
              <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                Offre Excessive :
              </th>
              {/* Colonne 4 : Montant Offre Excessive */}
              <td className="px-6 py-3.5 font-mono font-bold text-red-700 text-base">
                {montantExcessive !== null ? formatCurrency(montantExcessive) : '—'}
              </td>
            </tr>

            {/* 9. Prix de référence (Affiché UNIQUEMENT si une valeur existe) */}
            {hasPrixReference && (
              <tr className="hover:bg-slate-50/50 transition-colors">
                <th className="px-6 py-3.5 bg-slate-100/80 font-bold text-slate-700 uppercase text-xs tracking-wider border-r border-slate-200">
                  Prix de référence :
                </th>
                <td colSpan={3} className="px-6 py-3.5 font-mono font-black text-emerald-700 text-base">
                  {formatCurrency(formData.prix_reference)}
                </td>
              </tr>
            )}

          </tbody>
        </table>
      </div>
    </div>
  );
}
