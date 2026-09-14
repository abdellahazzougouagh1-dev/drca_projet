import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, BarChart3, CircleDollarSign, FileCheck2, FileText, Loader2 } from 'lucide-react';
import api from '../api/axios';

const money = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(v) || 0) + ' DH';
const pct = (part, total) => {
  const p = Number(part || 0);
  const t = Number(total || 0);
  if (!t || p <= 0) return 0;
  const raw = (p / t) * 100;
  if (raw > 0 && raw < 0.01) {
    return Number(raw.toFixed(4));
  }
  return Math.round(raw * 100) / 100;
};

const rate = (v) => {
  const num = Number(v || 0);
  if (num === 0) return '0 %';
  if (num > 0 && num < 0.01) {
    return `${num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 4 })} %`;
  }
  return `${num.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} %`;
};

function summaryOf(rows) {
  const sum = (key) => (rows || []).reduce((total, row) => total + Number(row[key] || 0), 0);
  const total_notifie = sum('total_credits');
  const total_engage = sum('credits_engages');
  const total_disponible = sum('credits_disponibles');
  const total_ordonnance = sum('total_ordonnance');
  const total_paiement = sum('total_paiements');
  return {
    total_notifie,
    total_engage,
    total_disponible,
    total_ordonnance,
    total_paiement,
    taux_consommation: pct(total_engage, total_notifie),
    taux_ordonnancement: pct(total_ordonnance, total_engage),
    taux_ordonnancement_notifie: pct(total_ordonnance, total_notifie),
    taux_paiement_ordonnancement: pct(total_paiement, total_ordonnance),
    taux_paiement_engagement: pct(total_paiement, total_engage),
    taux_paiement_notifie: pct(total_paiement, total_notifie),
  };
}

function PhaseCard({ step, label, value, rateValue, secondaryRate, Icon, tone, detail }) {
  return (
    <article className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <span className={`absolute inset-y-0 left-0 w-1 ${tone}`} />
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Taux {step}</p>
          <p className="mt-1 text-sm font-bold text-slate-700">{label}</p>
          <p className="mt-2 text-2xl font-black text-slate-950">{money(value)}</p>
          <div className="mt-2 space-y-0.5">
            <p className="text-xs font-bold text-emerald-700">{rateValue}</p>
            {secondaryRate && <p className="text-xs font-semibold text-slate-600">{secondaryRate}</p>}
          </div>
          {detail && <p className="mt-1.5 text-xs text-slate-400">{detail}</p>}
        </div>
        <span className="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-slate-600">
          <Icon size={20} />
        </span>
      </div>
    </article>
  );
}

export default function SuiviBudgetDirecteur() {
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(String(currentYear));
  const [domaine, setDomaine] = useState('ALL');
  const [store, setStore] = useState({ budget: {}, lines: [], notifications: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const getNotifDomaine = (notif) => {
    if (notif.domaine) return notif.domaine;
    const domaines = (notif.lignes || []).map((l) => l.domaine).filter(Boolean);
    if (domaines.length === 0) return 'INVESTISSEMENT';
    const hasInv = domaines.includes('INVESTISSEMENT');
    const hasFnc = domaines.includes('FONCTIONNEMENT');
    if (hasInv && hasFnc) return 'MIXTE';
    return hasFnc ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
  };

  useEffect(() => {
    let active = true;
    setLoading(true);
    const query = year ? `?exercice=${year}` : '';
    Promise.allSettled([
      api.get(`/dashboard/budget${query}`),
      api.get(`/notification-lignes${query}`),
      api.get(`/notifications${query}`),
      api.get(`/ordonnancements${query}`),
    ]).then((results) => {
      if (!active) return;
      const budgetData = results[0].status === 'fulfilled' ? results[0].value.data : {};
      const linesData = results[1].status === 'fulfilled' ? results[1].value.data : [];
      const notifsData = results[2].status === 'fulfilled' ? results[2].value.data : [];
      const ordData = results[3].status === 'fulfilled' ? results[3].value.data : [];
      setStore({ 
        budget: budgetData, 
        lines: linesData, 
        notifications: notifsData,
        ordonnancements: Array.isArray(ordData) ? ordData : (ordData.data || [])
      });
      setLoading(false);
    }).catch(() => {
      if (active) {
        setError('Impossible de charger les données réelles.');
        setLoading(false);
      }
    });
    return () => { active = false; };
  }, [year]);

  const realRows = useMemo(() => {
    const map = new Map();
    const getLineKey = (dom, imp) => `${(dom || 'INVESTISSEMENT').toUpperCase()}_${imp}`;

    if (store.budget?.lignes && Array.isArray(store.budget.lignes)) {
      store.budget.lignes.forEach((line) => {
        const lineDom = String(line.domaine || 'INVESTISSEMENT').toUpperCase();
        const imp = line.imputation || `${line.article || ''} / ${line.paragraphe || ''} / ${line.ligne_budgetaire || ''}`;
        const key = getLineKey(lineDom, imp);
        const notif = Number(line.total_credits || line.montant || 0);
        const eng = Number(line.credits_engages || 0);
        const ord = Number(line.total_ordonnance || 0);
        const pai = Number(line.total_paiements || line.total_paiement || 0);
        map.set(key, {
          domaine: lineDom,
          imputation: imp,
          libelle: line.libelle || '',
          total_credits: notif,
          credits_engages: eng,
          credits_disponibles: Number(line.credits_disponibles ?? (notif - eng)),
          total_ordonnance: ord,
          total_paiements: pai,
        });
      });
    }

    // Process ordonnancements directly from Registre to ensure all 5 column sums are included
    if (store.ordonnancements && Array.isArray(store.ordonnancements) && store.ordonnancements.length > 0) {
      store.ordonnancements.forEach((ord) => {
        const dom = String(ord.budget_type || ord.domaine || 'INVESTISSEMENT').toUpperCase().includes('FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
        const art = ord.article || ord.art || '';
        const par = ord.paragraphe || ord.par || '';
        const lig = ord.ligne || ord.ligne_budgetaire || ord.lig || '';
        const imp = (art && par && lig) ? `${art} / ${par} / ${lig}` : null;
        
        let sumOrd = 0;
        let isPaye = ['payé', 'paye', 'clôturé', 'cloture'].includes(String(ord.statut || '').toLowerCase());
        
        if (ord.ordres && Array.isArray(ord.ordres) && ord.ordres.length > 0) {
          sumOrd = ord.ordres.reduce((s, o) => s + Number(o.montant || 0), 0);
        } else {
          const mRep = Number(ord.paiement_reports || (String(ord.creance || '').includes('Report') ? ord.montant_brut : 0));
          const mCons = Number(ord.credit_consolide || (String(ord.creance || '').includes('Consolid') ? ord.montant_brut : 0));
          const mNeuf = Number(ord.credit_neuf || (String(ord.creance || '').includes('Neuf') ? ord.montant_brut : 0));
          const mRas = Number(ord.ras_total || (Number(ord.retenue_tva || 0) + Number(ord.retenue_ias || 0) + Number(ord.autres_retenues || 0)));
          const mRap = Number(ord.rap_total || (String(ord.creance || '').includes('Reste') ? ord.net_a_payer : 0));
          sumOrd = (mRep + mCons + mNeuf + mRas + mRap) || Number(ord.montant_brut || ord.net_a_payer || 0);
        }

        if (imp) {
          const key = getLineKey(dom, imp);
          if (map.has(key)) {
            const row = map.get(key);
            if (row.total_ordonnance === 0 && sumOrd > 0) {
              row.total_ordonnance = sumOrd;
              row.total_paiements = sumOrd;
            }
          }
        }
      });
    }

    if (store.lines && Array.isArray(store.lines)) {
      store.lines.forEach((line) => {
        const lineDom = String(line.domaine || line.notification?.domaine || 'INVESTISSEMENT').toUpperCase();
        const imp = line.imputation || `${line.article || ''} / ${line.paragraphe || ''} / ${line.ligne_budgetaire || ''}`;
        const key = getLineKey(lineDom, imp);
        const reports = Number(line.reports || 0);
        const neufs = Number(line.credits_neufs || 0);
        const engs = Number(line.credits_engagements || 0);
        const dimReports = Number(line.diminution_report || 0);
        const dimNeufs = Number(line.diminution_credit_neuf || 0);
        const dimEngs = Number(line.diminution_credit_engagement || 0);
        const netReports = Math.max(0, reports - dimReports);
        const netNeufs = Math.max(0, neufs - dimNeufs);
        const netEngs = Math.max(0, engs - dimEngs);
        const netCredits = Number(line.total_credits) || (netReports + netNeufs + netEngs);
        const actualEngageNeuf = Number(line.engage_neuf || 0);
        const eng = Number(line.credits_engages || 0) || (reports + engs + actualEngageNeuf);
        const ord = Number(line.total_ordonnance || 0);
        const pai = Number(line.total_paiements || line.total_paiement || ord || 0);

        if (!map.has(key)) {
          map.set(key, {
            domaine: lineDom,
            imputation: imp,
            libelle: line.libelle || '',
            total_credits: netCredits,
            credits_engages: eng,
            credits_disponibles: Math.max(0, netCredits - eng),
            total_ordonnance: ord,
            total_paiements: pai,
          });
        }
      });
    }

    if (store.notifications && Array.isArray(store.notifications)) {
      store.notifications.forEach((notif) => {
        const notifDom = getNotifDomaine(notif);
        if (notif.lignes && notif.lignes.length > 0) {
          notif.lignes.forEach((line) => {
            const lineDom = String(line.domaine || notifDom || 'INVESTISSEMENT').toUpperCase();
            const imp = `${line.article || ''} / ${line.paragraphe || ''} / ${line.ligne_budgetaire || ''}`;
            const key = getLineKey(lineDom, imp);
            if (!map.has(key)) {
              const reports = Number(line.reports || 0);
              const neufs = Number(line.credits_neufs || 0);
              const engs = Number(line.credits_engagements || 0);
              const dimReports = Number(line.diminution_report || 0);
              const dimNeufs = Number(line.diminution_credit_neuf || 0);
              const dimEngs = Number(line.diminution_credit_engagement || 0);
              const netReports = Math.max(0, reports - dimReports);
              const netNeufs = Math.max(0, neufs - dimNeufs);
              const netEngs = Math.max(0, engs - dimEngs);
              const lineCredits = Number(line.total_credits) || (netReports + netNeufs + netEngs);
              const lineEngages = reports + engs;
              map.set(key, {
                domaine: lineDom,
                imputation: imp,
                libelle: line.libelle || '',
                total_credits: lineCredits,
                credits_engages: lineEngages,
                credits_disponibles: Math.max(0, lineCredits - lineEngages),
                total_ordonnance: 0,
                total_paiements: 0,
              });
            }
          });
        }
      });
    }

    if (map.size === 0 && store.notifications && store.notifications.length > 0) {
      store.notifications.forEach((notif) => {
        const notifDom = getNotifDomaine(notif);
        const key = `${notifDom}_Global`;
        const mnt = Number(notif.montant || notif.montant_total || 0);
        if (map.has(key)) {
          const cur = map.get(key);
          cur.total_credits += mnt;
          cur.credits_disponibles += mnt;
        } else {
          map.set(key, {
            domaine: notifDom,
            imputation: `Notification #${notif.numero || notif.reference || notif.id}`,
            libelle: notif.objet || 'Crédits notifiés',
            total_credits: mnt,
            credits_engages: 0,
            credits_disponibles: mnt,
            total_ordonnance: 0,
            total_paiements: 0,
          });
        }
      });
    }

    return Array.from(map.values()).map((row) => ({
      ...row,
      taux_engagement: pct(row.credits_engages, row.total_credits),
      taux_ordonnancement: pct(row.total_ordonnance, row.credits_engages),
      taux_ordonnancement_notifie: pct(row.total_ordonnance, row.total_credits),
      taux_paiement_ordonnancement: pct(row.total_paiements, row.total_ordonnance),
      taux_paiement_engagement: pct(row.total_paiements, row.credits_engages),
      taux_paiement_notifie: pct(row.total_paiements, row.total_credits),
    }));
  }, [store.budget, store.lines, store.notifications]);

  const rows = domaine === 'ALL' ? realRows : realRows.filter((r) => r.domaine === domaine);
  const summary = summaryOf(rows);

  if (loading && realRows.length === 0) return <div className="grid min-h-screen place-items-center bg-slate-50 text-slate-500"><span className="flex items-center gap-2"><Loader2 className="animate-spin" /> Chargement du suivi budgétaire…</span></div>;
  const columns = ['Domaine', 'Ligne budgétaire', 'Notifié', 'Total engagements', 'Taux Eng.', 'Disponible', 'Total ordonnancement', 'Taux Ord./Eng.', 'Taux Ord./Not.', 'Total paiements', 'Taux Pai./Ord.', 'Taux Pai./Eng.', 'Taux Pai./Not.'];

  return <main className="min-h-screen bg-slate-50 p-4 text-slate-900 sm:p-7"><div className="mx-auto max-w-[1800px]">
    <Link to="/directeur" className="inline-flex items-center gap-2 text-sm font-bold text-blue-700 hover:underline"><ArrowLeft size={16} /> Retour au tableau de bord</Link>
    <div className="mt-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
      <div>
        <p className="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Espace Directeur</p>
        <h1 className="mt-1 text-3xl font-black tracking-tight">Suivi budgétaire des phases</h1>
        <p className="mt-2 text-sm text-slate-500">Analyse détaillée des crédits et consommations budgétaires réels par domaine.</p>
      </div>
      <div className="flex flex-wrap items-center gap-2">
        <div className="flex bg-slate-200/80 p-1 rounded-xl text-xs font-bold items-center gap-1 border border-slate-300">
          <button
            type="button"
            onClick={() => setDomaine('ALL')}
            className={`px-3 py-1.5 rounded-lg transition ${domaine === 'ALL' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Tous les domaines
          </button>
          <button
            type="button"
            onClick={() => setDomaine('FONCTIONNEMENT')}
            className={`px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 ${domaine === 'FONCTIONNEMENT' ? 'bg-[#1e40af] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'}`}
          >
            <span className={`w-2 h-2 rounded-full ${domaine === 'FONCTIONNEMENT' ? 'bg-white' : 'bg-blue-500'}`}></span>
            Fonctionnement
          </button>
          <button
            type="button"
            onClick={() => setDomaine('INVESTISSEMENT')}
            className={`px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 ${domaine === 'INVESTISSEMENT' ? 'bg-indigo-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'}`}
          >
            <span className={`w-2 h-2 rounded-full ${domaine === 'INVESTISSEMENT' ? 'bg-white' : 'bg-indigo-400'}`}></span>
            Investissement
          </button>
        </div>
        <label className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:border-slate-300 transition-colors">Exercice <select value={year} onChange={(e) => setYear(e.target.value)} className="ml-2 bg-transparent font-semibold outline-none cursor-pointer">{Array.from({ length: 12 }, (_, i) => currentYear + 2 - i).map((y) => <option key={y} value={y}>{y}</option>)}</select></label>
      </div>
    </div>
    {error && <p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">{error}</p>}
    <section className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <PhaseCard step="1" label={`Crédits notifiés (${domaine === 'ALL' ? 'Total' : domaine})`} value={summary?.total_notifie} rateValue="Taux : 100 %" detail="Base de calcul" Icon={BarChart3} tone="bg-blue-600" />
      <PhaseCard step="2" label={`Engagement (${domaine === 'ALL' ? 'Total' : domaine})`} value={summary?.total_engage} rateValue={`Taux / notifié : ${rate(summary?.taux_consommation || pct(summary?.total_engage, summary?.total_notifie))}`} detail="Engagé / notifié" Icon={FileCheck2} tone="bg-emerald-600" />
      <PhaseCard step="3" label={`Ordonnancement (${domaine === 'ALL' ? 'Total' : domaine})`} value={summary?.total_ordonnance} rateValue={`Taux / notifié : ${rate(summary?.taux_ordonnancement_notifie || pct(summary?.total_ordonnance, summary?.total_notifie))}`} secondaryRate={`Taux / engagé : ${rate(summary?.taux_ordonnancement || pct(summary?.total_ordonnance, summary?.total_engage))}`} detail="Ordonnancé / notifié & engagé" Icon={FileText} tone="bg-violet-600" />
      <PhaseCard step="4" label={`Paiement (${domaine === 'ALL' ? 'Total' : domaine})`} value={summary?.total_paiement} rateValue={`Taux / notifié : ${rate(summary?.taux_paiement_notifie || pct(summary?.total_paiement, summary?.total_notifie))}`} secondaryRate={`Taux / ordonnancé : ${rate(summary?.taux_paiement_ordonnancement || pct(summary?.total_paiement, summary?.total_ordonnance))}`} detail={`Payé / notifié · ${rate(summary?.taux_paiement_engagement || pct(summary?.total_paiement, summary?.total_engage))} de l'engagé`} Icon={CircleDollarSign} tone="bg-amber-600" />
    </section>
    <section className="mt-6 overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><div className="border-b border-slate-200 px-5 py-4 flex items-center justify-between"><h2 className="font-black">Synthèse par ligne budgétaire</h2><span className="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700">{domaine === 'ALL' ? 'Tous les domaines' : domaine}</span></div><div className="overflow-x-auto"><table className="min-w-[1650px] w-full border-collapse text-xs"><thead className="bg-emerald-700 text-left font-extrabold uppercase tracking-wide text-white"><tr>{columns.map((c) => <th key={c} className="border-r border-emerald-600 px-4 py-4 text-right first:text-left">{c}</th>)}</tr></thead><tbody className="divide-y divide-slate-100">{rows.map((r, i) => <tr key={`${r.domaine}-${r.imputation}-${i}`} className="hover:bg-emerald-50/40"><td className="px-4 py-3"><span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold border ${r.domaine === 'FONCTIONNEMENT' ? 'bg-blue-50 text-[#1e40af] border-blue-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'}`}><span className={`w-1.5 h-1.5 rounded-full ${r.domaine === 'FONCTIONNEMENT' ? 'bg-blue-500' : 'bg-indigo-500'}`}></span>{r.domaine}</span></td><td className="px-4 py-3 font-bold text-slate-700">{r.imputation}<span className="ml-2 font-normal text-slate-400">{r.libelle}</span></td><td className="px-4 py-3 text-right">{money(r.total_credits)}</td><td className="px-4 py-3 text-right font-bold text-emerald-700">{money(r.credits_engages)}</td><td className="px-4 py-3 text-right">{rate(r.taux_engagement)}</td><td className="px-4 py-3 text-right">{money(r.credits_disponibles)}</td><td className="px-4 py-3 text-right font-bold text-violet-700">{money(r.total_ordonnance)}</td><td className="px-4 py-3 text-right">{rate(r.taux_ordonnancement)}</td><td className="px-4 py-3 text-right font-semibold text-emerald-700">{rate(r.taux_ordonnancement_notifie)}</td><td className="px-4 py-3 text-right font-bold text-amber-700">{money(r.total_paiements)}</td><td className="px-4 py-3 text-right">{rate(r.taux_paiement_ordonnancement)}</td><td className="px-4 py-3 text-right">{rate(r.taux_paiement_engagement)}</td><td className="px-4 py-3 text-right font-semibold text-emerald-700">{rate(r.taux_paiement_notifie)}</td></tr>)}</tbody><tfoot className="border-t-2 border-emerald-700 bg-emerald-50 font-black text-emerald-950"><tr><td className="px-4 py-4" colSpan="2">TOTAL {domaine === 'ALL' ? 'GLOBAL' : domaine}</td><td className="px-4 py-4 text-right">{money(summary?.total_notifie)}</td><td className="px-4 py-4 text-right">{money(summary?.total_engage)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_consommation)}</td><td className="px-4 py-4 text-right">{money(summary?.total_disponible)}</td><td className="px-4 py-4 text-right">{money(summary?.total_ordonnance)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_ordonnancement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_ordonnancement_notifie || pct(summary?.total_ordonnance, summary?.total_notifie))}</td><td className="px-4 py-4 text-right">{money(summary?.total_paiement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_paiement_ordonnancement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_paiement_engagement)}</td><td className="px-4 py-4 text-right">{rate(summary?.taux_paiement_notifie || pct(summary?.total_paiement, summary?.total_notifie))}</td></tr></tfoot></table></div></section>
  </div></main>;
}
