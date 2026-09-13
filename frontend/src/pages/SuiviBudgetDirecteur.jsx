import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, BarChart3, CircleDollarSign, FileCheck2, FileText, Loader2 } from 'lucide-react';
import api from '../api/axios';

const money = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(v) || 0);
const rate = (v) => `${Number(v || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 })} %`;
const pct = (part, total) => total ? Math.round((Number(part) / Number(total)) * 10000) / 100 : 0;

const demoRows = [
  ['415 / 20 / 13', 'Équipements agricoles', 450000, 315000, 252000, 201600],
  ['415 / 30 / 60', 'Matériel technique', 280000, 196000, 147000, 117600],
  ['221 / 10 / 05', 'Prestations de services', 160000, 104000, 78000, 62400],
].map(([imputation, libelle, total_credits, credits_engages, total_ordonnance, total_paiements]) => ({
  domaine: 'DÉMONSTRATION', imputation, libelle, total_credits, credits_engages, total_ordonnance, total_paiements,
  credits_disponibles: total_credits - credits_engages,
  taux_engagement: pct(credits_engages, total_credits),
  taux_ordonnancement: pct(total_ordonnance, credits_engages),
  taux_paiement_ordonnancement: pct(total_paiements, total_ordonnance),
  taux_paiement_engagement: pct(total_paiements, credits_engages),
}));

function summaryOf(rows) {
  const sum = (key) => rows.reduce((total, row) => total + Number(row[key] || 0), 0);
  const total_notifie = sum('total_credits');
  const total_engage = sum('credits_engages');
  const total_disponible = sum('credits_disponibles');
  const total_ordonnance = sum('total_ordonnance');
  const total_paiement = sum('total_paiements');
  return { total_notifie, total_engage, total_disponible, total_ordonnance, total_paiement, taux_consommation: pct(total_engage, total_notifie), taux_ordonnancement: pct(total_ordonnance, total_engage), taux_paiement_ordonnancement: pct(total_paiement, total_ordonnance), taux_paiement_engagement: pct(total_paiement, total_engage) };
}

function PhaseCard({ step, label, value, rateValue, Icon, tone, detail }) {
  return <article className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span className={`absolute inset-y-0 left-0 w-1 ${tone}`} /><div className="flex items-start justify-between gap-3"><div><p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Taux {step}</p><p className="mt-1 text-sm font-bold text-slate-700">{label}</p><p className="mt-2 text-2xl font-black text-slate-950">{money(value)} <span className="text-sm">DH</span></p><p className="mt-2 text-xs font-bold text-emerald-700">Taux : {rate(rateValue)}</p><p className="mt-1 text-xs text-slate-400">{detail}</p></div><span className="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-slate-600"><Icon size={20} /></span></div></article>;
}

export default function SuiviBudgetDirecteur() {
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(String(currentYear));
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => {
    let active = true;
    api.get(`/dashboard/budget?exercice=${year}`).then((r) => active && setData(r.data)).catch(() => active && setError('Impossible de charger les données réelles. Les données de démonstration restent visibles.'));
    return () => { active = false; };
  }, [year]);
  if (!data && !error) return <div className="grid min-h-screen place-items-center bg-slate-50 text-slate-500"><span className="flex items-center gap-2"><Loader2 className="animate-spin" /> Chargement du suivi budgétaire…</span></div>;

  const realRows = data?.lignes || [];
  const isDemo = !realRows.some((r) => Number(r.credits_engages || 0) || Number(r.total_ordonnance || 0) || Number(r.total_paiements || 0));
  const rows = isDemo ? demoRows : realRows;
  const summary = isDemo ? summaryOf(rows) : data;
  const columns = ['Ligne budgétaire', 'Notifié', 'Total engagements', 'Taux Eng.', 'Disponible', 'Total ordonnancement', 'Taux Ord./Eng.', 'Total paiements', 'Taux Pai./Ord.', 'Taux Pai./Eng.'];

  return <main className="min-h-screen bg-slate-50 p-4 text-slate-900 sm:p-7"><div className="mx-auto max-w-[1800px]">
    <Link to="/directeur" className="inline-flex items-center gap-2 text-sm font-bold text-blue-700 hover:underline"><ArrowLeft size={16} /> Retour au tableau de bord</Link>
    <div className="mt-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-end"><div><p className="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Espace Directeur</p><h1 className="mt-1 text-3xl font-black tracking-tight">Suivi budgétaire des phases</h1><p className="mt-2 text-sm text-slate-500"></p></div><label className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600">Exercice <select value={year} onChange={(e) => setYear(e.target.value)} className="ml-2 bg-transparent outline-none"><option value={currentYear}>{currentYear}</option><option value={currentYear - 1}>{currentYear - 1}</option><option value={currentYear - 2}>{currentYear - 2}</option></select></label></div>
    {error && <p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">{error}</p>}
    <section className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4"><PhaseCard step="1" label="Crédits notifiés" value={summary?.total_notifie} rateValue={100} detail="Base de calcul" Icon={BarChart3} tone="bg-blue-600" /><PhaseCard step="2" label="Engagement" value={summary?.total_engage} rateValue={summary?.taux_consommation} detail="Engagé / notifié" Icon={FileCheck2} tone="bg-emerald-600" /><PhaseCard step="3" label="Ordonnancement" value={summary?.total_ordonnance} rateValue={summary?.taux_ordonnancement} detail="Ordonnancé / engagé" Icon={FileText} tone="bg-violet-600" /><PhaseCard step="4" label="Paiement" value={summary?.total_paiement} rateValue={summary?.taux_paiement_ordonnancement} detail={`Payé / ordonnancé · ${rate(summary?.taux_paiement_engagement)} de l'engagé`} Icon={CircleDollarSign} tone="bg-amber-600" /></section>
    <section className="mt-6 overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><div className="border-b border-slate-200 px-5 py-4"><h2 className="font-black">Synthèse par ligne budgétaire</h2><p className="mt-1 text-xs text-slate-500">Uniquement les montants totaux et les taux de passage entre les phases.</p></div><div className="overflow-x-auto"><table className="min-w-[1450px] w-full border-collapse text-xs"><thead className="bg-emerald-700 text-left font-extrabold uppercase tracking-wide text-white"><tr>{columns.map((c) => <th key={c} className="border-r border-emerald-600 px-4 py-4 text-right first:text-left">{c}</th>)}</tr></thead><tbody className="divide-y divide-slate-100">{rows.map((r) => <tr key={`${r.domaine}-${r.imputation}`} className="hover:bg-emerald-50/40"><td className="px-4 py-3 font-bold text-slate-700">{r.imputation}<span className="ml-2 font-normal text-slate-400">{r.libelle}</span></td><td className="px-4 py-3 text-right">{money(r.total_credits)}</td><td className="px-4 py-3 text-right font-bold text-emerald-700">{money(r.credits_engages)}</td><td className="px-4 py-3 text-right">{rate(r.taux_engagement)}</td><td className="px-4 py-3 text-right">{money(r.credits_disponibles)}</td><td className="px-4 py-3 text-right font-bold text-violet-700">{money(r.total_ordonnance)}</td><td className="px-4 py-3 text-right">{rate(r.taux_ordonnancement)}</td><td className="px-4 py-3 text-right font-bold text-amber-700">{money(r.total_paiements)}</td><td className="px-4 py-3 text-right">{rate(r.taux_paiement_ordonnancement)}</td><td className="px-4 py-3 text-right">{rate(r.taux_paiement_engagement)}</td></tr>)}</tbody><tfoot className="border-t-2 border-emerald-700 bg-emerald-50 font-black text-emerald-950"><tr><td className="px-4 py-4">TOTAL GLOBAL</td><td className="px-4 py-4 text-right">{money(summary?.total_notifie)}</td><td className="px-4 py-4 text-right">{money(summary?.total_engage)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_consommation)}</td><td className="px-4 py-4 text-right">{money(summary?.total_disponible)}</td><td className="px-4 py-4 text-right">{money(summary?.total_ordonnance)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_ordonnancement)}</td><td className="px-4 py-4 text-right">{money(summary?.total_paiement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_paiement_ordonnancement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_paiement_engagement)}</td></tr></tfoot></table></div></section>
  </div></main>;
}
