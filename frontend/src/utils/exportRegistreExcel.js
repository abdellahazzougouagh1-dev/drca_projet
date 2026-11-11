import api from '../api/axios';

const escapeHtml = (value) =>
  String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const sanitizeFilenamePart = (val, fallback = 'tous') =>
  String(val || fallback).replace(/[^a-zA-Z0-9_-]/g, '_');

const triggerBlobDownload = (blob, filename) => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};

const resolveCreanceStyle = (creance) => {
  switch (creance) {
    case 'Retenue à la source':
      return 'background-color:#fef3c7;color:#78350f;font-weight:bold;';
    case 'Reports':
      return 'background-color:#f3e8ff;color:#581c87;font-weight:bold;';
    case 'Crédit Consolidés':
      return 'background-color:#e0e7ff;color:#312e81;font-weight:bold;';
    case 'C.Neufs':
      return 'background-color:#cffafe;color:#164e63;font-weight:bold;';
    default:
      return 'background-color:#dbeafe;color:#1e40af;font-weight:bold;';
  }
};

const resolveStatutStyle = (statut) => {
  const low = String(statut || '').toLowerCase().trim();
  if (low.includes('payé') || low.includes('paye') || low.includes('totalement ordonnanc')) {
    return 'background-color:#d1fae5;color:#047857;font-weight:bold;';
  }
  if (low.includes('transmis') || low.includes('partiellement')) {
    return 'background-color:#dbeafe;color:#1d4ed8;font-weight:bold;';
  }
  if (low.includes('rejet') || low.includes('annul')) {
    return 'background-color:#ffe4e6;color:#be123c;font-weight:bold;';
  }
  return 'background-color:#fef3c7;color:#b45309;font-weight:bold;';
};

const generateStyledOrdonnancementFallback = ({ procedure, exercice, statut, search, rows = [], filename }) => {
  const procLabel = procedure && procedure !== 'TOUS' ? procedure : 'Toutes les procédures';
  const exLabel = exercice && exercice !== 'TOUS' ? exercice : 'Tous les exercices';
  const stLabel = statut && statut !== 'TOUS' ? statut : 'Tous';
  const metaParts = [
    `Procédure : ${escapeHtml(procLabel)}`,
    `Exercice : ${escapeHtml(exLabel)}`,
    `Statut : ${escapeHtml(stLabel)}`,
  ];
  if (search) metaParts.push(`Recherche : "${escapeHtml(search)}"`);
  metaParts.push(`Lignes : ${rows.length}`);
  metaParts.push(`Exporté le ${new Date().toLocaleDateString('fr-FR')}`);

  let sumReports = 0;
  let sumConsolides = 0;
  let sumNeufs = 0;
  let sumRas = 0;
  let sumRap = 0;

  const bodyRowsHtml = rows.length
    ? rows
        .map((r, idx) => {
          const mReports = Number(r.paiement_reports ?? r.montant_reports ?? 0);
          const mConsolides = Number(r.paiement_credits_consolides ?? r.montant_credit_consolides ?? 0);
          const mNeufs = Number(r.paiement_c_neufs ?? r.montant_c_neufs ?? 0);
          const mRas = Number(r.paiement_retenue_source ?? r.montant_ras ?? 0);
          const mRap = Number(r.paiement_rap ?? r.montant_rap ?? 0);

          sumReports += mReports;
          sumConsolides += mConsolides;
          sumNeufs += mNeufs;
          sumRas += mRas;
          sumRap += mRap;

          const rowBg = idx % 2 === 0 ? '#ffffff' : '#f0fdf4';
          return `
            <tr style="background-color:${rowBg};">
              <td class="center bold muted">${escapeHtml(r.index ?? idx + 1)}</td>
              <td class="center bold blue">${escapeHtml(r.num_op ?? idx + 1)}</td>
              <td class="center">${escapeHtml(r.date_op || '-')}</td>
              <td class="left bold dark">${escapeHtml(r.beneficiaire || '-')}</td>
              <td class="left">${escapeHtml(r.reference || '-')}</td>
              <td class="center pill-slate">${escapeHtml(r.budget || 'Investissement')}</td>
              <td class="center" style="${resolveCreanceStyle(r.creance)}">${escapeHtml(r.creance || 'Reste à payer')}</td>
              <td class="center bold">${escapeHtml(r.code || '-')}</td>
              <td class="center bold">${escapeHtml(r.art || '-')}</td>
              <td class="center bold">${escapeHtml(r.par || '-')}</td>
              <td class="center bold">${escapeHtml(r.lig || '-')}</td>
              <td class="center bold">${escapeHtml(r.slig ?? '0')}</td>
              <td class="left">${escapeHtml(r.intitule || '-')}</td>
              <td class="right num-dash" style="color:${mReports > 0 ? '#1e293b' : '#94a3b8'};font-weight:${mReports > 0 ? 'bold' : 'normal'};">${mReports}</td>
              <td class="right num-dash" style="color:${mConsolides > 0 ? '#3730a3' : '#94a3b8'};font-weight:${mConsolides > 0 ? 'bold' : 'normal'};">${mConsolides}</td>
              <td class="right num-dash" style="color:${mNeufs > 0 ? '#155e75' : '#94a3b8'};font-weight:${mNeufs > 0 ? 'bold' : 'normal'};">${mNeufs}</td>
              <td class="right num-dash" style="color:${mRas > 0 ? '#92400e' : '#94a3b8'};font-weight:${mRas > 0 ? 'bold' : 'normal'};">${mRas}</td>
              <td class="right num-dash" style="color:${mRap > 0 ? '#1e3a8a' : '#94a3b8'};font-weight:${mRap > 0 ? 'bold' : 'normal'};">${mRap}</td>
              <td class="center" style="${resolveStatutStyle(r.statut)}">${escapeHtml(r.statut || 'À payer')}</td>
            </tr>
          `;
        })
        .join('')
    : `<tr><td colspan="19" class="center muted" style="padding:24px;font-style:italic;">Aucun ordonnancement correspondant à cette recherche.</td></tr>`;

  const grandTotal = sumReports + sumConsolides + sumNeufs + sumRas + sumRap;

  const html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8" />
        <!--[if gte mso 9]>
        <xml>
          <x:ExcelWorkbook>
            <x:ExcelWorksheets>
              <x:ExcelWorksheet>
                <x:Name>Ordonnancements</x:Name>
                <x:WorksheetOptions>
                  <x:DisplayGridlines/>
                  <x:FreezePanes/>
                  <x:FrozenNoSplit/>
                  <x:SplitPositionRow>5</x:SplitPositionRow>
                  <x:SplitPositionColumn>0</x:SplitPositionColumn>
                </x:WorksheetOptions>
              </x:ExcelWorksheet>
            </x:ExcelWorksheets>
          </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
          body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; color: #1e293b; }
          .title-inst { font-size: 9pt; font-weight: bold; color: #475569; }
          .title-main { font-size: 16pt; font-weight: bold; color: #047857; }
          .title-meta { font-size: 10pt; font-style: italic; color: #64748b; }
          table { border-collapse: collapse; width: 100%; }
          th { background-color: #047857; color: #ffffff; font-weight: bold; font-size: 9.5pt; border: 1px solid #059669; padding: 10px 8px; height: 34px; text-transform: uppercase; }
          td { border: 1px solid #e2e8f0; padding: 7px 8px; font-size: 10pt; vertical-align: middle; }
          .left { text-align: left; }
          .center { text-align: center; }
          .right { text-align: right; }
          .bold { font-weight: bold; }
          .dark { color: #0f172a; }
          .muted { color: #64748b; }
          .blue { color: #1d4ed8; }
          .pill-slate { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
          .num-dash { mso-number-format: "#,##0.00;-#,##0.00;\"-\""; }
          .num-dh { mso-number-format: "#,##0.00 \"DH\""; }
          tfoot td { background-color: #ecfdf5; color: #064e3b; font-weight: bold; font-size: 10.5pt; border-top: 2px solid #047857; border-bottom: 3px double #064e3b; padding: 9px 8px; }
        </style>
      </head>
      <body>
        <table>
          <tr><td colspan="19" style="border:none;" class="title-inst">ROYAUME DU MAROC • OFFICE NATIONAL DU CONSEIL AGRICOLE (ONCA) • DRCA RSK</td></tr>
          <tr><td colspan="19" style="border:none;" class="title-main">REGISTRE DES ORDONNANCEMENTS</td></tr>
          <tr><td colspan="19" style="border:none;" class="title-meta">${metaParts.join(' &nbsp;|&nbsp; ')}</td></tr>
          <tr><td colspan="19" style="border:none;height:8px;"></td></tr>
          <thead>
            <tr>
              <th class="center">N°</th>
              <th class="center">N° OP</th>
              <th class="center">DATE OP</th>
              <th class="left">BÉNÉFICIAIRE</th>
              <th class="left">RÉFÉRENCE</th>
              <th class="center">BUDGET</th>
              <th class="center">CRÉANCE</th>
              <th class="center">CODE</th>
              <th class="center">ART</th>
              <th class="center">PAR</th>
              <th class="center">LIG</th>
              <th class="center">S.LIG</th>
              <th class="left">INTITULÉ</th>
              <th class="right">PAIEMENT REPORTS</th>
              <th class="right">PAIEMENT CRÉDITS CONSOLIDÉS</th>
              <th class="right">PAIEMENT C.NEUFS</th>
              <th class="right">PAIEMENT RETENUE À LA SOURCE</th>
              <th class="right">PAIEMENT RESTE À PAYER</th>
              <th class="center">PAYÉ / REJET</th>
            </tr>
          </thead>
          <tbody>
            ${bodyRowsHtml}
          </tbody>
          <tfoot>
            <tr>
              <td colspan="13" class="left">TOTAL (${rows.length} ligne(s))</td>
              <td class="right num-dh">${sumReports}</td>
              <td class="right num-dh">${sumConsolides}</td>
              <td class="right num-dh">${sumNeufs}</td>
              <td class="right num-dh">${sumRas}</td>
              <td class="right num-dh">${sumRap}</td>
              <td class="right num-dh">${grandTotal}</td>
            </tr>
          </tfoot>
        </table>
      </body>
    </html>
  `;

  const blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  triggerBlobDownload(blob, filename.replace(/\.xlsx$/i, '.xls'));
};

const generateStyledGenericFallback = ({ title, meta, rows = [], filename, sheetName = 'Registre' }) => {
  const safeRows = Array.isArray(rows) ? rows : [];
  const ignored = new Set(['id', 'consultation_id', 'marche_id', 'notification_ligne_id', 'created_at', 'updated_at', 'deleted_at']);
  const keys = [...new Set(safeRows.flatMap((r) => Object.keys(r || {}).filter((k) => !ignored.has(k))))];
  const activeKeys = keys.length ? keys : ['information'];
  const effectiveRows = safeRows.length ? safeRows : [{ information: 'Aucune donnée pour cette sélection.' }];

  const headersHtml = activeKeys
    .map((k) => `<th>${escapeHtml(k.replaceAll('_', ' ').toUpperCase())}</th>`)
    .join('');

  const bodyHtml = effectiveRows
    .map(
      (r, idx) => `
      <tr style="background-color:${idx % 2 === 0 ? '#ffffff' : '#f0fdf4'};">
        ${activeKeys
          .map((k) => {
            const v = r?.[k] ?? '';
            const isNum = typeof v === 'number' || (String(k).includes('montant') && !Number.isNaN(Number(v)) && v !== '');
            return `<td class="${isNum ? 'right num-dash' : 'left'}">${escapeHtml(typeof v === 'object' ? JSON.stringify(v) : v)}</td>`;
          })
          .join('')}
      </tr>`
    )
    .join('');

  const html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8" />
        <style>
          body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; color: #1e293b; }
          .title-inst { font-size: 9pt; font-weight: bold; color: #475569; }
          .title-main { font-size: 16pt; font-weight: bold; color: #047857; }
          .title-meta { font-size: 10pt; font-style: italic; color: #64748b; }
          table { border-collapse: collapse; width: 100%; }
          th { background-color: #047857; color: #ffffff; font-weight: bold; font-size: 9.5pt; border: 1px solid #059669; padding: 10px 8px; text-align: center; }
          td { border: 1px solid #e2e8f0; padding: 7px 8px; font-size: 10pt; }
          .left { text-align: left; }
          .right { text-align: right; }
          .num-dash { mso-number-format: "#,##0.00;-#,##0.00;\"-\""; }
        </style>
      </head>
      <body>
        <table>
          <tr><td colspan="${activeKeys.length}" style="border:none;" class="title-inst">ROYAUME DU MAROC • ONCA • DRCA RSK</td></tr>
          <tr><td colspan="${activeKeys.length}" style="border:none;" class="title-main">${escapeHtml(title)}</td></tr>
          <tr><td colspan="${activeKeys.length}" style="border:none;" class="title-meta">${escapeHtml(meta)}</td></tr>
          <tr><td colspan="${activeKeys.length}" style="border:none;height:8px;"></td></tr>
          <thead><tr>${headersHtml}</tr></thead>
          <tbody>${bodyHtml}</tbody>
        </table>
      </body>
    </html>
  `;

  const blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  triggerBlobDownload(blob, filename.replace(/\.xlsx$/i, '.xls'));
};

export async function exportRegistreConsultationsExcel({ procedure, exercice, search = '', rows = [] }) {
  const safeProc = sanitizeFilenamePart(procedure, 'toutes');
  const safeEx = sanitizeFilenamePart(exercice || new Date().getFullYear());
  const filename = `Registre_consultations_${safeProc}_${safeEx}.xlsx`;

  try {
    const response = await api.post(
      '/registres/consultations/export-excel',
      { procedure, exercice, search, rows },
      { responseType: 'blob' }
    );
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    triggerBlobDownload(blob, filename);
  } catch (err) {
    console.warn('Export Excel consultations serveur indisponible, fallback stylé utilisé:', err);
    generateStyledGenericFallback({
      title: 'REGISTRE DES CONSULTATIONS',
      meta: `Procédure : ${procedure || 'toutes'} | Exercice : ${exercice || new Date().getFullYear()} | Exporté le ${new Date().toLocaleDateString('fr-FR')}`,
      rows,
      filename,
      sheetName: 'Consultations',
    });
  }
}

export async function exportRegistreEngagementExcel({
  mode,
  budget,
  exercice,
  rows = [],
  rowsInvestissement = [],
  rowsFonctionnement = [],
}) {
  const safeMode = sanitizeFilenamePart(mode, 'tous');
  const safeEx = sanitizeFilenamePart(exercice || new Date().getFullYear());
  const filename = `Registre_engagement_${safeMode}_${safeEx}.xlsx`;

  try {
    const response = await api.post(
      '/registres/engagement/export-excel',
      { mode, budget, exercice, rows, rowsInvestissement, rowsFonctionnement },
      { responseType: 'blob' }
    );
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    triggerBlobDownload(blob, filename);
  } catch (err) {
    console.warn('Export Excel engagement serveur indisponible, fallback stylé utilisé:', err);
    const fallbackRows = budget === 'all' ? [...rowsInvestissement, ...rowsFonctionnement] : rows;
    generateStyledGenericFallback({
      title: `REGISTRE DES ENGAGEMENTS — ${String(budget || 'COMPLET').toUpperCase()}`,
      meta: `Procédure : ${mode || 'tous'} | Exercice : ${exercice || new Date().getFullYear()} | Exporté le ${new Date().toLocaleDateString('fr-FR')}`,
      rows: fallbackRows,
      filename,
      sheetName: 'Engagements',
    });
  }
}

export async function exportRegistreOrdonnancementExcel({
  procedure = 'TOUS',
  exercice = String(new Date().getFullYear()),
  statut = 'TOUS',
  search = '',
  rows = [],
}) {
  const safeProc = sanitizeFilenamePart(procedure !== 'TOUS' ? procedure : 'tous');
  const safeEx = sanitizeFilenamePart(exercice || new Date().getFullYear());
  const filterSuffix = search || (statut && statut !== 'TOUS') ? '_filtre' : '';
  const filename = `Registre_ordonnancements_${safeProc}_${safeEx}${filterSuffix}.xlsx`;

  try {
    const response = await api.post(
      '/registres/ordonnancement/export-excel',
      { procedure, exercice, statut, search, rows },
      { responseType: 'blob' }
    );
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    triggerBlobDownload(blob, filename);
  } catch (err) {
    console.warn('Export Excel ordonnancement serveur indisponible, fallback stylé utilisé:', err);
    generateStyledOrdonnancementFallback({
      procedure,
      exercice,
      statut,
      search,
      rows,
      filename,
    });
  }
}
