const columns = [
  [
    "numero_ordre",
    "N° d’ordre général",
    "text"
  ],
  [
    "numero_rubrique",
    "N° d’ordre dans la rubrique",
    "text"
  ],
  [
    "date_engagement",
    "Date d’engagement",
    "date"
  ],
  [
    "mode_engagement",
    "Mode d’engagement",
    "text"
  ],
  [
    "reference",
    "Référence",
    "text"
  ],
  [
    "reference_2",
    "Référence 2",
    "text"
  ],
  [
    "budget",
    "Budget",
    "text"
  ],
  [
    "code",
    "Code",
    "text"
  ],
  [
    "art",
    "ART",
    "text"
  ],
  [
    "par",
    "PAR",
    "text"
  ],
  [
    "lig",
    "LIG",
    "text"
  ],
  [
    "s_lig",
    "S/LIG",
    "text"
  ],
  [
    "intitule",
    "Intitulé",
    "text"
  ],
  [
    "credit_ouvert_cp",
    "Crédit ouvert CP",
    "money"
  ],
  [
    "credit_ouvert_ce",
    "Crédit ouvert CE",
    "money"
  ],
  [
    "depenses_anterieures_ce",
    "Dépenses engagées antérieurement CE",
    "money"
  ],
  [
    "depenses_anterieures_cp",
    "Dépenses engagées antérieurement CP",
    "money"
  ],
  [
    "depenses_credits_engagement",
    "Dépenses sur crédits d’engagement",
    "money"
  ],
  [
    "depenses_credits_consolides",
    "Dépenses sur crédits consolidés",
    "money"
  ],
  [
    "depenses_rap",
    "Dépenses sur reste à payer",
    "money"
  ],
  [
    "montant_depense_neuf",
    "Montant de la dépense neuve",
    "money"
  ],
  [
    "interets_moratoires",
    "Intérêts moratoires 1 %",
    "money"
  ],
  [
    "montant_engager_neuf",
    "Montant à engager neuf",
    "money"
  ],
  [
    "objet",
    "Objet",
    "text"
  ],
  [
    "beneficiaire",
    "Bénéficiaire",
    "text"
  ]
];

// Keep dossier details; financial values come only from the saved engagement form.
export function fonctionnementRow(row, fiche, isMarche = false) {
  const value = (name) => fiche[name] === '' ? null : (fiche[name] ?? null);
  return {
    ...row,
    credit_ouvert_cp: value(isMarche ? 'credit_budget_cp' : 'credit_ouvert_cp'),
    depenses_anterieures_cp: value(isMarche ? 'depenses_engagees_cp' : 'depenses_anterieures_cp'),
    montant_depense_neuf: value('montant_depense_neuf'),
    interets_moratoires: value('interets_moratoires'),
    montant_engager_neuf: value(isMarche ? 'engagement_propose_cp' : 'montant_engager_neuf'),
    credit_ouvert_ce: null,
    depenses_anterieures_ce: null,
    depenses_credits_engagement: null,
    depenses_credits_consolides: null,
    depenses_rap: null,
  };
}

export default function RegistreFonctionnement({ rows, onOpen }) {
  const money = (value) => value === null || value === undefined || value === '' ? '—'
    : Number(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  return (
    <table className="w-full min-w-[2450px] border-collapse text-left text-xs">
      <caption className="bg-slate-50 p-4 text-left text-slate-600">Registre des engagements — Fonctionnement</caption>
      <thead className="bg-emerald-700 text-white"><tr>
        {columns.map(([key, label]) => <th key={key} className={`px-4 py-4 ${key === 'intitule' ? 'min-w-[320px]' : ''}`}>{label}</th>)}
        {onOpen && <th className="px-4 py-4">Action</th>}
      </tr></thead>
      <tbody className="divide-y divide-slate-100">
        {rows.map((row) => <tr key={row.id} className="hover:bg-slate-50">
          {columns.map(([key, , type]) => <td key={key} className={`px-4 py-3 ${type === 'money' ? 'text-right tabular-nums' : 'font-medium'} ${key === 'intitule' ? 'min-w-[320px] max-w-[440px] whitespace-normal break-words leading-relaxed' : ''}`}>
            {type === 'money' ? money(row[key]) : type === 'date' ? (row[key] ? new Date(row[key]).toLocaleDateString('fr-FR') : '—') : (row[key] ?? '—')}
          </td>)}
          {onOpen && <td className="px-4 py-3"><button type="button" onClick={() => onOpen(row)} className="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-slate-700 hover:bg-emerald-50">Ouvrir</button></td>}
        </tr>)}
      </tbody>
    </table>
  );
}
