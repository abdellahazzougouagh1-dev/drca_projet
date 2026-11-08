import * as XLSX from '@e965/xlsx';

const LABELS = {
  numero_consultation: 'N° consultation',
  date_consultation: 'Date consultation',
  mode_engagement: 'Mode engagement',
  objet_consultation: 'Objet consultation',
  fournisseur_nom: 'Fournisseur',
  type_budget: 'Type budget',
  statut_dossier: 'Statut dossier',
  total_estime: 'Total estimé',
};

const formatLabel = (key) => LABELS[key] || key.replaceAll('_', ' ');

const normalizeValue = (value) => {
  if (value === null || value === undefined) return '';
  if (typeof value === 'object') return JSON.stringify(value);
  return value;
};

const makeSheet = (rows) => {
  const safeRows = Array.isArray(rows) ? rows : [];
  const keys = [...new Set(safeRows.flatMap((row) => Object.keys(row || {})))];
  const data = safeRows.map((row) => keys.reduce((result, key) => {
    result[formatLabel(key)] = normalizeValue(row?.[key]);
    return result;
  }, {}));

  return XLSX.utils.json_to_sheet(data.length ? data : [{ Information: 'Aucune donnée' }]);
};

const appendSheet = (workbook, rows, name) => {
  XLSX.utils.book_append_sheet(workbook, makeSheet(rows), name.slice(0, 31));
};

const downloadWorkbook = (workbook, filename) => {
  XLSX.writeFile(workbook, `${filename}.xlsx`);
};

export function exportRegistreConsultationsExcel({ procedure, exercice, rows = [] }) {
  const workbook = XLSX.utils.book_new();
  appendSheet(workbook, rows, 'Consultations');
  downloadWorkbook(
    workbook,
    `Registre_consultations_${procedure || 'toutes'}_${exercice || new Date().getFullYear()}`
  );
}

export function exportRegistreEngagementExcel({
  mode,
  budget,
  exercice,
  rows = [],
  rowsInvestissement = [],
  rowsFonctionnement = [],
}) {
  const workbook = XLSX.utils.book_new();

  if (budget === 'all') {
    appendSheet(workbook, rowsInvestissement, 'Investissement');
    appendSheet(workbook, rowsFonctionnement, 'Fonctionnement');
  } else {
    appendSheet(workbook, rows, budget || 'Registre');
  }

  downloadWorkbook(
    workbook,
    `Registre_engagement_${mode || 'tous'}_${exercice || new Date().getFullYear()}`
  );
}

export function exportRegistreOrdonnancementExcel({ procedure, exercice, rows = [] }) {
  const workbook = XLSX.utils.book_new();
  appendSheet(workbook, rows, 'Ordonnancements');
  downloadWorkbook(
    workbook,
    `Registre_ordonnancements_${procedure || 'tous'}_${exercice || new Date().getFullYear()}`
  );
}
