import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  BarChart3, Bell, Building2, CalendarDays, CheckCircle2, ChevronRight,
  CircleDollarSign, ClipboardList, Download, Eye, FileCheck2, FileText,
  Gauge, Landmark, Loader2, Search, SlidersHorizontal, TrendingUp,
  WalletCards, X, ArrowUpRight, Clock3, LayoutDashboard, FolderKanban,
  CreditCard, Settings, LogOut, Menu, ShieldCheck, ArrowLeft, Receipt, Plus,
} from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
const money = (value) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(Number(value) || 0).replace('MAD', 'DH');
const date = (value) => value ? new Date(value).toLocaleDateString('fr-FR') : '—';
const percentage = (part, total) => total ? Math.round((part / total) * 100) : 0;

const getDossierPhase = (item) => {
  const state = String(item.statut_dossier || item.statut || item.statut_marche || item.etat_avancement || '').toLowerCase().trim();

  // 1. Paiement
  if (state.includes('payé') || state.includes('paye') || state.includes('paiement') || state.includes('clôtur') || state.includes('clotur')) {
    return {
      label: 'Paiement',
      badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      dotClass: 'bg-emerald-500',
      progress: 100,
    };
  }

  // 2. Ordonnancement
  if ((item.ordonnancements && item.ordonnancements.length > 0) || state.includes('ordonnan') || state.includes('mandat')) {
    return {
      label: 'Ordonnancement',
      badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
      dotClass: 'bg-amber-500',
      progress: 80,
    };
  }

  // 3. Liquidation
  if (item.liquidation || state.includes('liquid') || state.includes('factur') || state.includes('decompte') || state.includes('service fait') || state.includes('reception')) {
    return {
      label: 'Liquidation',
      badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
      dotClass: 'bg-purple-500',
      progress: 60,
    };
  }

  // 4. Engagement
  if (item.engagement || item.registreEngagement || item.numero_engagement || state.includes('engag') || state.includes('approb') || state.includes('visa') || state.includes('os')) {
    return {
      label: 'Engagement',
      badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
      dotClass: 'bg-blue-500',
      progress: 40,
    };
  }

  // 5. Consultation
  return {
    label: 'Consultation',
    badgeClass: 'bg-cyan-50 text-cyan-800 border-cyan-200',
    dotClass: 'bg-cyan-500',
    progress: 20,
  };
};

const phase = (item) => {
  const p = getDossierPhase(item);
  return [p.label, p.progress];
};

const engagementMode = (item) => {
  if (item.type === 'Appel d’offres') return 'Appel d’offres';
  const mode = String(item.mode_engagement || item.categorie || item.type_consultation || '').toLowerCase();
  if (mode.includes('appel') || mode.includes('aoo')) return 'Appel d’offres';
  if (mode.includes('bon') || mode.includes('bc')) return 'Bon de commande';
  return 'Bon de commande';
};

const budgetLine = (item) => {
  const line = item.notification_ligne || item.notificationLigne;
  if (line) return `${line.article || '—'} / ${line.paragraphe || '—'} / ${line.ligne_budgetaire || '—'}`;
  const budget = item.budget;
  if (budget?.art || budget?.par || budget?.lig) return `${budget.art || '—'} / ${budget.par || '—'} / ${budget.lig || '—'}`;
  return 'Non rattachée';
};

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

function CircularProgress({ percentage = 0, color = '#2563eb', trackColor = '#f1f5f9', size = 80, strokeWidth = 7 }) {
  const radius = (size - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const numericVal = Math.max(0, Math.min(100, Number(percentage) || 0));
  const strokeDashoffset = circumference - (numericVal / 100) * circumference;

  return (
    <div className="relative flex items-center justify-center shrink-0" style={{ width: size, height: size }}>
      <svg width={size} height={size} className="transform -rotate-90">
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={trackColor}
          strokeWidth={strokeWidth}
          fill="transparent"
        />
        {numericVal > 0 && (
          <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            stroke={color}
            strokeWidth={strokeWidth}
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            strokeLinecap="round"
            fill="transparent"
            className="transition-all duration-700 ease-out"
          />
        )}
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
        <span className="text-sm font-black text-slate-900 leading-none">{Math.round(numericVal)}%</span>
      </div>
    </div>
  );
}

function PhaseCard({ step, label, value, percentage = 0, rateValue, secondaryRate, Icon, color = '#2563eb', detail }) {
  return (
    <article className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
      <span className="absolute inset-y-0 left-0 w-1" style={{ backgroundColor: color }} />
      <div className="flex items-center justify-between gap-4">
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Taux {step}</span>
            {Icon && (
              <span className="grid h-6 w-6 place-items-center rounded-lg bg-slate-100 text-slate-600">
                <Icon size={13} style={{ color }} />
              </span>
            )}
          </div>
          <p className="mt-1 text-sm font-bold text-slate-800 truncate" title={label}>{label}</p>
          <p className="mt-1.5 text-xl font-black text-slate-950">{money(value)}</p>
          <div className="mt-2 space-y-0.5">
            {rateValue && <p className="text-xs font-bold text-slate-700">{rateValue}</p>}
            {secondaryRate && <p className="text-[11px] font-semibold text-slate-500">{secondaryRate}</p>}
          </div>
          {detail && <p className="mt-1.5 text-[11px] text-slate-400">{detail}</p>}
        </div>

        <CircularProgress percentage={percentage} color={color} size={84} strokeWidth={7} />
      </div>
    </article>
  );
}

function Progress({ value, color = 'bg-blue-600' }) {
  return <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div className={`h-full rounded-full ${color}`} style={{ width: `${Math.min(100, value)}%` }} /></div>;
}

function Kpi({ label, value, icon: Icon, tone = 'blue', detail }) {
  const tones = { blue: 'bg-blue-50 text-blue-600', emerald: 'bg-emerald-50 text-emerald-600', violet: 'bg-violet-50 text-violet-600', amber: 'bg-amber-50 text-amber-600', rose: 'bg-rose-50 text-rose-600' };
  return <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div className="flex justify-between gap-2"><div><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold tracking-tight text-slate-900">{value}</p></div><span className={`grid h-10 w-10 place-items-center rounded-xl ${tones[tone]}`}><Icon size={20} /></span></div><p className="mt-4 flex items-center gap-1 text-xs text-slate-400"><ArrowUpRight size={14} className="text-emerald-600" /><span className="font-bold text-emerald-600">À jour</span> · {detail}</p></article>;
}

export default function DashboardDirecteur() {
  const navigate = useNavigate();
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(String(currentYear));
  const [month, setMonth] = useState('');
  const [domaine, setDomaine] = useState('ALL'); // 'ALL' | 'FONCTIONNEMENT' | 'INVESTISSEMENT'
  const [tab, setTab] = useState('notifications');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState(null);
  const [store, setStore] = useState({ consultations: [], aoos: [], notifications: [], lines: [], budget: {} });
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  const getNotifDomaine = (notif) => {
    if (notif.domaine) return notif.domaine;
    const domaines = (notif.lignes || []).map((l) => l.domaine).filter(Boolean);
    if (domaines.length === 0) return 'INVESTISSEMENT';
    const hasInv = domaines.includes('INVESTISSEMENT');
    const hasFnc = domaines.includes('FONCTIONNEMENT');
    if (hasInv && hasFnc) return 'MIXTE';
    return hasFnc ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
  };

  const getDossierDomaine = (item) => {
    if (item.domaine) return item.domaine;
    const lineDom = item.notification_ligne?.domaine || item.notificationLigne?.domaine || item.budget?.domaine;
    if (lineDom) return lineDom;
    const lineId = item.notification_ligne_id || item.budget_ligne_id;
    if (lineId && store.lines) {
      const found = store.lines.find((l) => l.id === lineId);
      if (found?.domaine) return found.domaine;
    }
    return 'INVESTISSEMENT';
  };

  const [customYears, setCustomYears] = useState([]);

  const handleAddYear = () => {
    const nextSuggested = availableYears.length > 0 ? Math.max(...availableYears) + 1 : currentYear + 1;
    const input = window.prompt(`Ajouter une nouvelle année d'exercice (ex: ${nextSuggested}) :`, String(nextSuggested));
    if (input) {
      const parsed = parseInt(input.trim(), 10);
      if (!isNaN(parsed) && parsed >= 2000 && parsed <= 2099) {
        setCustomYears((prev) => Array.from(new Set([...prev, parsed])));
        setYear(String(parsed));
      } else {
        alert("Veuillez saisir une année valide (ex: 2029).");
      }
    }
  };

  const availableYears = useMemo(() => {
    const current = new Date().getFullYear();
    const yearSet = new Set();
    for (let y = current + 5; y >= 2018; y--) {
      yearSet.add(y);
    }
    customYears.forEach((y) => yearSet.add(Number(y)));
    store.notifications?.forEach((n) => {
      if (n.exercice) yearSet.add(Number(n.exercice));
      if (n.date_notification) yearSet.add(new Date(n.date_notification).getFullYear());
    });
    store.consultations?.forEach((c) => {
      if (c.annee) yearSet.add(Number(c.annee));
      if (c.date_consultation) yearSet.add(new Date(c.date_consultation).getFullYear());
    });
    store.aoos?.forEach((a) => {
      if (a.annee || a.exercice) yearSet.add(Number(a.annee || a.exercice));
      if (a.date_lancement || a.created_at) yearSet.add(new Date(a.date_lancement || a.created_at).getFullYear());
    });
    return Array.from(yearSet).filter((y) => y >= 2000 && y <= 2099).sort((a, b) => b - a);
  }, [store, customYears]);

  useEffect(() => {
    let active = true;
    async function load() {
      setLoading(true); setError('');
      const query = year ? `?exercice=${year}` : '';
      const responses = await Promise.allSettled([
        api.get('/consultations'), api.get('/aoos'), api.get(`/notifications${query}`),
        api.get(`/notification-lignes${query}`), api.get(`/dashboard/budget${query}`),
        api.get(`/ordonnancements${query}`),
      ]);
      if (!active) return;
      const result = (index, fallback) => responses[index].status === 'fulfilled' ? responses[index].value.data : fallback;
      const ordData = result(5, { data: [] });
      setStore({
        consultations: result(0, []),
        aoos: result(1, []),
        notifications: result(2, []),
        lines: result(3, []),
        budget: result(4, {}),
        ordonnancements: Array.isArray(ordData) ? ordData : (ordData.data || [])
      });
      if (responses.some((item) => item.status === 'rejected')) setError('Certaines sources n’ont pas pu être chargées. Les données disponibles restent affichées.');
      setLoading(false);
    }
    load();
    return () => { active = false; };
  }, [year]);

  const realRows = useMemo(() => {
    const map = new Map();
    const getLineKey = (dom, imp) => `${(dom || 'INVESTISSEMENT').toUpperCase()}_${imp}`;

    // 1. Process backend budget lines
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

    // 1b. Process ordonnancements directly from Registre to ensure all 5 column sums are included
    if (store.ordonnancements && Array.isArray(store.ordonnancements) && store.ordonnancements.length > 0) {
      store.ordonnancements.forEach((ord) => {
        const dom = String(ord.budget_type || ord.domaine || 'INVESTISSEMENT').toUpperCase().includes('FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
        const art = ord.article || ord.art || '';
        const par = ord.paragraphe || ord.par || '';
        const lig = ord.ligne || ord.ligne_budgetaire || ord.lig || '';
        const imp = (art && par && lig) ? `${art} / ${par} / ${lig}` : null;

        // Calcul de la somme des colonnes du registre d'ordonnancement
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

    // 2. Process store.lines if any lines are missing or have additional credits
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

    // 3. Process store.notifications (in case there are notifications with lines or total amount)
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

    // If map is empty but notifications exist, create summary row from notifications
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

  const budgetColumns = ['DOMAINE', 'LIGNE BUDGÉTAIRE', 'NOTIFIÉ', 'TOTAL ENGAGEMENTS', 'TAUX ENG.', 'DISPONIBLE', 'TOTAL ORDONNANCEMENT', 'TAUX ORD./ENG.', 'TAUX ORD./NOT.', 'TOTAL PAIEMENTS', 'TAUX PAI./ORD.', 'TAUX PAI./ENG.', 'TAUX PAI./NOT.'];

  const budgetRows = useMemo(() => {
    if (domaine === 'ALL') return realRows;
    return realRows.filter((r) => r.domaine === domaine);
  }, [realRows, domaine]);

  const budgetSummary = useMemo(() => summaryOf(budgetRows), [budgetRows]);
  const invBudgetRows = useMemo(() => realRows.filter((r) => r.domaine === 'INVESTISSEMENT'), [realRows]);
  const fncBudgetRows = useMemo(() => realRows.filter((r) => r.domaine === 'FONCTIONNEMENT'), [realRows]);
  const invSummary = useMemo(() => summaryOf(invBudgetRows), [invBudgetRows]);
  const fncSummary = useMemo(() => summaryOf(fncBudgetRows), [fncBudgetRows]);

  const dashboard = useMemo(() => {
    const matchYear = (item) => !year || String(item.annee || item.exercice || new Date(item.date_consultation || item.date_lancement || item.created_at || 0).getFullYear()) === String(year);
    const rawDossiers = [
      ...store.consultations.map((item) => ({ ...item, type: 'Consultation', ref: item.numero_consultation, title: item.objet_consultation, route: `/consultations/${item.id}` })),
      ...store.aoos.map((item) => ({ ...item, type: 'Appel d’offres', ref: item.num_aoo, title: item.objet, route: `/aoos/${item.id}` })),
    ].filter(matchYear);
    const filteredByMonth = month ? rawDossiers.filter((item) => new Date(item.created_at || item.date_consultation || item.date_preparation).getMonth() + 1 === Number(month)) : rawDossiers;
    const filtered = domaine === 'ALL'
      ? filteredByMonth
      : filteredByMonth.filter((item) => getDossierDomaine(item) === domaine);

    const notified = budgetSummary.total_notifie;
    const engaged = budgetSummary.total_engage;
    const ordered = budgetSummary.total_ordonnance;
    const paid = budgetSummary.total_paiement;

    const count = (keywords) => filtered.filter((item) => keywords.some((word) => String(item.statut_dossier || item.statut || '').toLowerCase().includes(word))).length;
    const trends = months.map((name, index) => {
      const notifications = store.notifications.filter((item) => {
        const notifMonth = new Date(item.date_notification || item.created_at).getMonth() === index;
        if (!notifMonth) return false;
        if (domaine === 'ALL') return true;
        const d = getNotifDomaine(item);
        return d === domaine || d === 'MIXTE';
      });
      return { name, Nombre: notifications.length, Montant: notifications.reduce((sum, item) => sum + Number(item.montant || item.montant_total || 0), 0) };
    });

    return {
      dossiers: filtered,
      notified,
      engaged,
      ordered,
      paid,
      available: notified - engaged,
      engagement: percentage(engaged, notified),
      ordering: percentage(ordered, engaged),
      payment: percentage(paid, ordered),
      completed: count(['termin', 'clôtur', 'valid']),
      active: count(['cours', 'lanc', 'analyse']),
      planned: count(['programm', 'prépar']),
      trends,
    };
  }, [store, month, year, domaine, budgetSummary]);

  const filteredNotifications = useMemo(() => {
    return store.notifications.filter((n) => {
      if (domaine === 'ALL') return true;
      const d = getNotifDomaine(n);
      return d === domaine || d === 'MIXTE';
    });
  }, [store.notifications, domaine]);

  const shownDossiers = dashboard.dossiers.filter((item) => `${item.ref || ''} ${item.title || ''} ${item.type}`.toLowerCase().includes(search.toLowerCase()));
  const exportReport = () => {
    const rows = [['Référence', 'Domaine', 'Type', 'Objet', 'Statut', 'Phase', 'Avancement'], ...dashboard.dossiers.map((item) => { const [label, progress] = phase(item); return [item.ref || '', getDossierDomaine(item), item.type, item.title || '', item.statut_dossier || item.statut || '', label, `${progress}%`]; })];
    const csv = rows.map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(';')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' }); const link = document.createElement('a');
    link.href = URL.createObjectURL(blob); link.download = `rapport-directeur-${year}-${domaine.toLowerCase()}.csv`; link.click(); URL.revokeObjectURL(link.href);
  };

  if (loading) return <div className="grid min-h-screen place-items-center bg-slate-50 text-slate-600"><span className="flex items-center gap-3"><Loader2 className="animate-spin text-blue-700" /> Chargement du tableau de pilotage…</span></div>;

  const navigation = [
    { label: 'Notifications', icon: Bell, tabKey: 'notifications', action: () => setTab('notifications') },
    { label: 'Consultations', icon: FolderKanban, tabKey: 'dossiers', action: () => setTab('dossiers') },
    { label: 'Paiements & taux', icon: CircleDollarSign, tabKey: 'paiements', action: () => setTab('paiements') },
  ];

  return (
    <div className="min-h-screen bg-[#f6f8fc] text-slate-900">
      <aside className="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col border-r border-slate-800 bg-[#101b33] p-5 text-slate-300 lg:flex">
        <Link to="/directeur" className="flex items-center gap-3 border-b border-slate-700/80 pb-6">
          <span className="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 text-sm font-black text-white shadow-lg shadow-blue-900">DR</span>
          <span><b className="block text-sm text-white">DRCA · RSK</b><small className="text-xs text-slate-400">Espace de direction</small></span>
        </Link>
        <p className="mt-7 px-3 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500">Pilotage</p>
        <nav className="mt-3 space-y-1">
          {navigation.map(({ label, icon: Icon, tabKey, action }) => (
            <button
              key={label}
              onClick={action}
              className={`flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold transition ${tab === tabKey ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`}
            >
              <Icon size={18} />{label}
            </button>
          ))}
        </nav>
        <div className="mt-auto rounded-2xl border border-slate-700 bg-slate-800/60 p-3">
          <div className="flex items-center gap-3">
            <span className="grid h-9 w-9 place-items-center rounded-full bg-blue-500 text-xs font-bold text-white">DR</span>
            <span className="min-w-0"><b className="block truncate text-sm text-white">Directeur Régional</b><small className="block truncate text-xs text-slate-400">Accès décisionnel</small></span>
          </div>
          <button onClick={logout} className="mt-3 flex w-full items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-700 hover:text-white">
            <LogOut size={15} /> Se déconnecter
          </button>
        </div>
      </aside>

      <div className="lg:pl-72">
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
          <div className="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-4 sm:px-7">
            <Link to="/directeur" className="flex items-center gap-3 lg:hidden">
              <span className="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-blue-700 to-indigo-700 text-sm font-black text-white">DR</span>
              <span><b className="block text-sm">DRCA · RSK</b><small className="text-xs text-slate-500">Plateforme Directeur</small></span>
            </Link>
            <div className="hidden lg:flex items-center gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Plateforme sécurisée</p>
                <p className="mt-1 flex items-center gap-2 text-sm font-bold text-slate-700"><ShieldCheck size={16} className="text-emerald-600" /> Espace décisionnel Directeur</p>
              </div>
            </div>
            <div className="flex items-center gap-2.5">
              <Link
                to="/bons-commande"
                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition transform active:scale-98"
                title="Accéder aux Bons de commande"
              >
                <Receipt size={15} />
                <span>Bons de commande</span>
              </Link>
              <Link
                to="/dashboard"
                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition transform active:scale-98"
                title="Accéder aux Appels d'offres"
              >
                <FileText size={15} />
                <span>Appels d'offres</span>
              </Link>
              <button onClick={() => setMobileNavOpen(!mobileNavOpen)} className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 lg:hidden">
                <Menu size={18} />
              </button>
            </div>
          </div>
        </header>

        {mobileNavOpen && (
          <div className="fixed inset-x-4 top-16 z-50 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">
            <div className="space-y-1">
              {navigation.map(({ label, icon: Icon, tabKey, action }) => (
                <button
                  key={label}
                  onClick={() => { action(); setMobileNavOpen(false); }}
                  className={`flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold ${tab === tabKey ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50'}`}
                >
                  <Icon size={18} />{label}
                </button>
              ))}
              <Link to="/bons-commande" onClick={() => setMobileNavOpen(false)} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-700">
                <Receipt size={18} /> Bons de commande
              </Link>
              <Link to="/dashboard" onClick={() => setMobileNavOpen(false)} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-700">
                <FileText size={18} /> Appels d'offres
              </Link>
            </div>
            <button onClick={logout} className="mt-2 flex w-full items-center gap-3 rounded-xl border-t border-slate-100 px-3 py-3 text-left text-sm font-semibold text-rose-600">
              <LogOut size={18} /> Se déconnecter
            </button>
          </div>
        )}

        <main className="mx-auto max-w-[1920px] px-4 py-7 sm:px-7">
          <div className="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
              <p className="text-sm font-semibold text-blue-700">DIRECTION RÉGIONALE</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight">Tableau de bord Directeur</h1>
              <p className="mt-2 text-sm text-slate-500">Vue consolidée des activités, procédures et indicateurs budgétaires.</p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <div className="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-xs">
                <button
                  type="button"
                  onClick={() => setDomaine('INVESTISSEMENT')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${domaine === 'INVESTISSEMENT'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                    }`}
                >
                  Investissement
                </button>
                <button
                  type="button"
                  onClick={() => setDomaine('FONCTIONNEMENT')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${domaine === 'FONCTIONNEMENT'
                      ? 'bg-blue-500 text-white shadow-xs'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                    }`}
                >
                  Fonctionnement
                </button>
              </div>
              <div className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:border-slate-300 transition-colors shadow-xs">
                <CalendarDays size={16} className="text-blue-600 shrink-0" />
                <select
                  value={year}
                  onChange={(event) => {
                    if (event.target.value === '__ADD__') {
                      handleAddYear();
                    } else {
                      setYear(event.target.value);
                    }
                  }}
                  className="bg-transparent font-semibold outline-none cursor-pointer text-slate-800 pr-1"
                >
                  {availableYears.map((y) => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                  <option value="__ADD__" className="text-blue-600 font-bold bg-blue-50">+ Autre année...</option>
                </select>
                <button
                  type="button"
                  onClick={handleAddYear}
                  title="Ajouter une année"
                  className="grid h-6 w-6 place-items-center rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition-colors cursor-pointer"
                >
                  <Plus size={13} className="stroke-[2.5]" />
                </button>
              </div>
              <select value={month} onChange={(event) => setMonth(event.target.value)} className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 outline-none">
                <option value="">Tous les mois</option>
                {months.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}
              </select>
            </div>
          </div>

          {error && <p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{error}</p>}

          {tab === 'notifications' && (
            <section className="mt-6">
              <div className="grid gap-4 md:grid-cols-3">
                <Kpi label="Notifications filtrées" value={filteredNotifications.length} icon={Bell} tone="amber" detail={domaine === 'ALL' ? `Exercice ${year}` : `${domaine} · Exercice ${year}`} />
                <Kpi label="Montant total notifié" value={money(dashboard.notified)} icon={Landmark} detail={domaine === 'ALL' ? 'Tous les crédits notifiés' : `Crédits ${domaine.toLowerCase()}`} />
                <Kpi label="Lignes budgétaires" value={(store.lines || []).filter(l => domaine === 'ALL' || (l.domaine || 'INVESTISSEMENT') === domaine).length} icon={ClipboardList} tone="violet" detail={domaine === 'ALL' ? 'Total lignes suivies' : `Lignes ${domaine.toLowerCase()}`} />
              </div>
              <div className="mt-6 space-y-6">
                <div className="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                  <div className="flex items-center justify-between border-b border-slate-100 p-5">
                    <div>
                      <div className="flex items-center gap-2">
                        <h2 className="font-bold text-base text-slate-800">Liste des notifications</h2>
                        <span className={`text-xs px-2.5 py-0.5 rounded-full font-bold ${domaine === 'FONCTIONNEMENT' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                          domaine === 'INVESTISSEMENT' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' :
                            'bg-slate-100 text-slate-700'
                          }`}>
                          {domaine === 'ALL' ? 'Tous les domaines' : domaine}
                        </span>
                      </div>
                      <p className="mt-1 text-sm text-slate-500">Consultez les informations de l'exercice {year}.</p>
                    </div>
                    <Bell className="text-amber-500" size={20} />
                  </div>
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[850px] text-left text-sm">
                      <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                          <th className="px-5 py-3">Référence</th>
                          <th className="px-5 py-3">Domaine</th>
                          <th className="px-5 py-3">Date</th>
                          <th className="px-5 py-3">Exercice</th>
                          <th className="px-5 py-3 text-right">Crédits</th>
                          <th className="px-5 py-3 text-right">Diminution</th>
                          <th className="px-5 py-3 text-right">Montant</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {filteredNotifications.map((notification) => {
                          const lignes = notification.lignes || [];
                          const notifDom = getNotifDomaine(notification);
                          const isFonct = notifDom === 'FONCTIONNEMENT';

                          const totalReports = lignes.reduce((sum, l) => sum + Number(l.reports || 0), 0);
                          const totalNeufs = lignes.reduce((sum, l) => sum + Number(l.credits_neufs || 0), 0);
                          const totalEngagements = lignes.reduce((sum, l) => sum + Number(l.credits_engagements || 0), 0);
                          const totalCredits = (totalReports + totalNeufs + totalEngagements) || Number(notification.montant || 0);

                          const totalDimReports = lignes.reduce((sum, l) => sum + Number(l.diminution_report || 0), 0);
                          const totalDimNeufs = lignes.reduce((sum, l) => sum + Number(l.diminution_credit_neuf || 0), 0);
                          const totalDimEngagements = lignes.reduce((sum, l) => sum + Number(l.diminution_credit_engagement || 0), 0);
                          const totalDiminution = totalDimReports + totalDimNeufs + totalDimEngagements;

                          return (
                            <tr key={notification.id} className="hover:bg-slate-50 transition-colors">
                              <td className="px-5 py-4 font-mono text-xs font-semibold text-blue-700">
                                {notification.numero || notification.reference || `Notification #${notification.id}`}
                              </td>
                              <td className="px-5 py-4">
                                <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border ${notifDom === 'FONCTIONNEMENT'
                                  ? 'bg-blue-50 text-[#1e40af] border-blue-200'
                                  : notifDom === 'INVESTISSEMENT'
                                    ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                    : 'bg-teal-50 text-teal-700 border-teal-200'
                                  }`}>
                                  <span className={`w-1.5 h-1.5 rounded-full ${notifDom === 'FONCTIONNEMENT' ? 'bg-blue-500' : notifDom === 'INVESTISSEMENT' ? 'bg-indigo-500' : 'bg-teal-500'
                                    }`}></span>
                                  {notifDom}
                                </span>
                              </td>
                              <td className="px-5 py-4 text-slate-600">
                                {date(notification.date_notification || notification.created_at)}
                              </td>
                              <td className="px-5 py-4 text-slate-600">
                                {notification.exercice || year}
                              </td>
                              <td className="px-5 py-4 text-right">
                                <div className="flex flex-col items-end gap-1.5">
                                  {(totalReports > 0 || totalNeufs > 0 || totalEngagements > 0) ? (
                                    <div className="flex flex-col items-end gap-1.5 w-full min-w-[205px] max-w-[225px]">
                                      {totalReports > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-sky-50/90 border border-sky-200/90 rounded-lg text-xs shadow-xs hover:bg-sky-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-sky-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            {isFonct ? 'Reste à payer' : 'Reports'}
                                          </span>
                                          <span className="font-mono font-bold text-sky-950 text-xs">{money(totalReports)}</span>
                                        </div>
                                      )}
                                      {totalNeufs > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-emerald-50/90 border border-emerald-200/90 rounded-lg text-xs shadow-xs hover:bg-emerald-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-emerald-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Crédits neufs
                                          </span>
                                          <span className="font-mono font-bold text-emerald-950 text-xs">{money(totalNeufs)}</span>
                                        </div>
                                      )}
                                      {totalEngagements > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-indigo-50/90 border border-indigo-200/90 rounded-lg text-xs shadow-xs hover:bg-indigo-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-indigo-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                            Crédits d'eng.
                                          </span>
                                          <span className="font-mono font-bold text-indigo-950 text-xs">{money(totalEngagements)}</span>
                                        </div>
                                      )}
                                    </div>
                                  ) : (
                                    <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold text-slate-400 bg-slate-100/80 border border-slate-200/60">0 DH</span>
                                  )}
                                </div>
                              </td>
                              <td className="px-5 py-4 text-right">
                                <div className="flex flex-col items-end gap-1.5">
                                  {totalDiminution > 0 ? (
                                    <div className="flex flex-col items-end gap-1.5 w-full min-w-[205px] max-w-[225px]">
                                      {totalDimReports > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            {isFonct ? 'Dim. Reste à payer' : 'Dim. Reports'}
                                          </span>
                                          <span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimReports)}</span>
                                        </div>
                                      )}
                                      {totalDimNeufs > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Dim. Neufs
                                          </span>
                                          <span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimNeufs)}</span>
                                        </div>
                                      )}
                                      {totalDimEngagements > 0 && (
                                        <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors">
                                          <span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]">
                                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Dim. Eng.
                                          </span>
                                          <span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimEngagements)}</span>
                                        </div>
                                      )}
                                    </div>
                                  ) : (
                                    <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold text-slate-400 bg-slate-50 border border-slate-200/60">0 DH</span>
                                  )}
                                </div>
                              </td>
                              <td className="px-5 py-4 text-right font-black text-slate-900">
                                {money(notification.montant || totalCredits)}
                              </td>
                            </tr>
                          );
                        })}
                        {!filteredNotifications.length && (
                          <tr><td colSpan="7" className="px-5 py-12 text-center text-slate-500">Aucune notification {domaine !== 'ALL' ? `pour le domaine ${domaine}` : ''} pour cet exercice.</td></tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
                <div className="w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                  <div className="flex items-center justify-between">
                    <div>
                      <h2 className="font-bold">Évolution des notifications</h2>
                      <p className="mt-1 text-sm text-slate-500">Nombre de notifications par mois {domaine !== 'ALL' ? `(${domaine})` : ''}.</p>
                    </div>
                    <TrendingUp className="text-blue-600" size={20} />
                  </div>
                  <div className="mt-5 h-72">
                    <ResponsiveContainer width="100%" height="100%">
                      <AreaChart data={dashboard.trends}>
                        <defs>
                          <linearGradient id="notificationFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stopColor="#2563eb" stopOpacity=".28" />
                            <stop offset="100%" stopColor="#2563eb" stopOpacity="0" />
                          </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} stroke="#e2e8f0" />
                        <XAxis dataKey="name" axisLine={false} tickLine={false} fontSize={11} />
                        <YAxis allowDecimals={false} axisLine={false} tickLine={false} fontSize={11} />
                        <Tooltip contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0' }} />
                        <Area type="monotone" dataKey="Nombre" stroke="#2563eb" strokeWidth={3} fill="url(#notificationFill)" />
                      </AreaChart>
                    </ResponsiveContainer>
                  </div>
                </div>
              </div>
            </section>
          )}

          {tab === 'dossiers' && (
            <section className="mt-6">
              <div className="grid gap-4 sm:grid-cols-3">
                <Kpi label="Terminées" value={dashboard.completed} icon={CheckCircle2} tone="emerald" detail="Dossiers clôturés / validés" />
                <Kpi label="En cours" value={dashboard.active} icon={Clock3} tone="amber" detail="À suivre activement" />
                <Kpi label="Programmées" value={dashboard.planned} icon={CalendarDays} detail="À préparer" />
              </div>
              <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="font-bold text-base text-slate-800">Suivi des procédures & Consultations</h2>
                      <span className={`text-xs px-2.5 py-0.5 rounded-full font-bold ${domaine === 'FONCTIONNEMENT' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                        domaine === 'INVESTISSEMENT' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' :
                          'bg-slate-100 text-slate-700'
                        }`}>
                        {domaine === 'ALL' ? 'Tous les domaines' : domaine}
                      </span>
                    </div>
                    <p className="mt-1 text-sm text-slate-500">Cliquez sur un dossier pour visualiser sa timeline.</p>
                  </div>
                  <label className="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-slate-500">
                    <Search size={16} />
                    <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Rechercher…" className="w-40 bg-transparent text-sm outline-none" />
                  </label>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full min-w-[1050px] text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                      <tr>
                        <th className="px-5 py-3">Référence</th>
                        <th className="px-5 py-3">Domaine</th>
                        <th className="px-5 py-3">Mode d’engagement</th>
                        <th className="px-5 py-3">Ligne budgétaire</th>
                        <th className="px-5 py-3">Objet</th>
                        <th className="px-5 py-3">Lancement</th>
                        <th className="px-5 py-3 text-center">Phase / Statut</th>
                        <th className="px-5 py-3 text-right"></th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {shownDossiers.map((item) => {
                        const phaseInfo = getDossierPhase(item);
                        const mode = engagementMode(item);
                        const dossierDom = getDossierDomaine(item);
                        return (
                          <tr key={`${item.type}-${item.id}`} className="hover:bg-slate-50 transition-colors">
                            <td className="px-5 py-4">
                              <b className="font-mono text-blue-700">{item.ref || '—'}</b>
                              <small className="mt-1 block text-slate-400">{item.type}</small>
                            </td>
                            <td className="px-5 py-4">
                              <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border ${dossierDom === 'FONCTIONNEMENT'
                                ? 'bg-blue-50 text-[#1e40af] border-blue-200'
                                : 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                }`}>
                                <span className={`w-1.5 h-1.5 rounded-full ${dossierDom === 'FONCTIONNEMENT' ? 'bg-blue-500' : 'bg-indigo-500'}`}></span>
                                {dossierDom}
                              </span>
                            </td>
                            <td className="px-5 py-4">
                              <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${mode === 'Appel d’offres' ? 'bg-violet-50 text-violet-700' : 'bg-cyan-50 text-cyan-700'}`}>{mode}</span>
                            </td>
                            <td className="px-5 py-4"><span className="whitespace-nowrap font-mono text-xs font-semibold text-slate-600">{budgetLine(item)}</span></td>
                            <td className="max-w-sm px-5 py-4">
                              <p className="truncate font-medium">{item.title || 'Sans objet'}</p>
                              <small className="text-slate-500">{item.service || item.structure_acheteuse || 'DRCA RSK'}</small>
                            </td>
                            <td className="px-5 py-4 text-slate-600">{date(item.date_lancement || item.date_consultation || item.created_at)}</td>
                            <td className="px-5 py-4 text-center">
                              <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border ${phaseInfo.badgeClass}`}>
                                <span className={`w-1.5 h-1.5 rounded-full ${phaseInfo.dotClass}`}></span>
                                {phaseInfo.label}
                              </span>
                            </td>
                            <td className="px-5 py-4 text-right">
                              <button onClick={() => setSelected(item)} className="inline-flex items-center gap-1 font-semibold text-blue-700 hover:underline">
                                <Eye size={16} /> Détail
                              </button>
                            </td>
                          </tr>
                        );
                      })}
                      {!shownDossiers.length && (
                        <tr><td colSpan="8" className="px-5 py-12 text-center text-slate-500">Aucun dossier ne correspond aux filtres ({domaine !== 'ALL' ? domaine : 'Tous'}).</td></tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          )}

          {tab === 'paiements' && (
            <section className="mt-6 space-y-6">
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <PhaseCard
                  step="1"
                  label={`Crédits notifiés (${domaine === 'ALL' ? 'Total' : domaine})`}
                  value={budgetSummary?.total_notifie}
                  percentage={Number(budgetSummary?.total_notifie || 0) > 0 ? 100 : 0}
                  rateValue={Number(budgetSummary?.total_notifie || 0) > 0 ? "Taux : 100 %" : "Taux : 0 %"}
                  detail={Number(budgetSummary?.total_notifie || 0) > 0 ? "Base de calcul" : "Aucun crédit notifié"}
                  Icon={BarChart3}
                  color="#2563eb"
                />
                <PhaseCard
                  step="2"
                  label={`Engagement (${domaine === 'ALL' ? 'Total' : domaine})`}
                  value={budgetSummary?.total_engage}
                  percentage={budgetSummary?.taux_consommation ?? pct(budgetSummary?.total_engage, budgetSummary?.total_notifie)}
                  rateValue={`Taux / notifié : ${rate(budgetSummary?.taux_consommation || pct(budgetSummary?.total_engage, budgetSummary?.total_notifie))}`}
                  detail="Engagé / notifié"
                  Icon={FileCheck2}
                  color="#10b981"
                />
                <PhaseCard
                  step="3"
                  label={`Ordonnancement (${domaine === 'ALL' ? 'Total' : domaine})`}
                  value={budgetSummary?.total_ordonnance}
                  percentage={budgetSummary?.taux_ordonnancement ?? pct(budgetSummary?.total_ordonnance, budgetSummary?.total_engage)}
                  rateValue={`Taux / notifié : ${rate(budgetSummary?.taux_ordonnancement_notifie || pct(budgetSummary?.total_ordonnance, budgetSummary?.total_notifie))}`}
                  secondaryRate={`Taux / engagé : ${rate(budgetSummary?.taux_ordonnancement || pct(budgetSummary?.total_ordonnance, budgetSummary?.total_engage))}`}
                  detail="Ordonnancé / notifié & engagé"
                  Icon={FileText}
                  color="#8b5cf6"
                />
                <PhaseCard
                  step="4"
                  label={`Paiement (${domaine === 'ALL' ? 'Total' : domaine})`}
                  value={budgetSummary?.total_paiement}
                  percentage={budgetSummary?.taux_paiement_ordonnancement ?? pct(budgetSummary?.total_paiement, budgetSummary?.total_ordonnance)}
                  rateValue={`Taux / notifié : ${rate(budgetSummary?.taux_paiement_notifie || pct(budgetSummary?.total_paiement, budgetSummary?.total_notifie))}`}
                  secondaryRate={`Taux / ordonnancé : ${rate(budgetSummary?.taux_paiement_ordonnancement || pct(budgetSummary?.total_paiement, budgetSummary?.total_ordonnance))}`}
                  detail={`Payé / notifié · ${rate(budgetSummary?.taux_paiement_engagement || pct(budgetSummary?.total_paiement, budgetSummary?.total_engage))} de l'engagé`}
                  Icon={CircleDollarSign}
                  color="#f59e0b"
                />
              </div>

              {/* Si Domaine === ALL, affichage comparatif synthétique INVESTISSEMENT vs FONCTIONNEMENT */}
              {domaine === 'ALL' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="p-4 bg-indigo-50/50 border border-indigo-200 rounded-2xl">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-xs font-black uppercase text-indigo-900 flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        Domaine : INVESTISSEMENT
                      </span>
                      <span className="text-xs font-bold text-indigo-700 font-mono">{invBudgetRows.length} lignes</span>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                      <div className="bg-white p-2.5 rounded-xl border border-indigo-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Notifié</p>
                        <p className="font-bold text-slate-900 font-mono mt-0.5">{money(invSummary.total_notifie)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-indigo-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Engagé</p>
                        <p className="font-bold text-emerald-700 font-mono mt-0.5">{money(invSummary.total_engage)}</p>
                        <p className="text-[10px] text-emerald-600 font-bold">{rate(invSummary.taux_consommation)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-indigo-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Ordonnancé</p>
                        <p className="font-bold text-violet-700 font-mono mt-0.5">{money(invSummary.total_ordonnance)}</p>
                        <p className="text-[10px] text-violet-600 font-bold">{rate(invSummary.taux_ordonnancement)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-indigo-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Payé</p>
                        <p className="font-bold text-amber-700 font-mono mt-0.5">{money(invSummary.total_paiement)}</p>
                        <p className="text-[10px] text-amber-600 font-bold">{rate(invSummary.taux_paiement_ordonnancement)}</p>
                      </div>
                    </div>
                  </div>

                  <div className="p-4 bg-blue-50/50 border border-blue-200 rounded-2xl">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-xs font-black uppercase text-blue-900 flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        Domaine : FONCTIONNEMENT
                      </span>
                      <span className="text-xs font-bold text-[#1e40af] font-mono">{fncBudgetRows.length} lignes</span>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                      <div className="bg-white p-2.5 rounded-xl border border-blue-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Notifié</p>
                        <p className="font-bold text-slate-900 font-mono mt-0.5">{money(fncSummary.total_notifie)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-blue-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Engagé</p>
                        <p className="font-bold text-emerald-700 font-mono mt-0.5">{money(fncSummary.total_engage)}</p>
                        <p className="text-[10px] text-emerald-600 font-bold">{rate(fncSummary.taux_consommation)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-blue-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Ordonnancé</p>
                        <p className="font-bold text-violet-700 font-mono mt-0.5">{money(fncSummary.total_ordonnance)}</p>
                        <p className="text-[10px] text-violet-600 font-bold">{rate(fncSummary.taux_ordonnancement)}</p>
                      </div>
                      <div className="bg-white p-2.5 rounded-xl border border-blue-100 shadow-2xs">
                        <p className="text-[10px] text-slate-500 font-semibold uppercase">Payé</p>
                        <p className="font-bold text-amber-700 font-mono mt-0.5">{money(fncSummary.total_paiement)}</p>
                        <p className="text-[10px] text-amber-600 font-bold">{rate(fncSummary.taux_paiement_ordonnancement)}</p>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              <div className="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                <div className="border-b border-slate-200 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <h2 className="font-black text-slate-900">Synthèse par ligne budgétaire</h2>
                    <p className="mt-1 text-xs text-slate-500">Montants totaux et taux de passage pour {domaine === 'ALL' ? 'Investissement et Fonctionnement' : `le domaine ${domaine}`}.</p>
                  </div>
                  <span className={`text-xs px-3 py-1 rounded-full font-bold self-start sm:self-auto ${domaine === 'FONCTIONNEMENT' ? 'bg-blue-100 text-blue-800' :
                    domaine === 'INVESTISSEMENT' ? 'bg-indigo-100 text-indigo-800' :
                      'bg-slate-100 text-slate-700'
                    }`}>
                    {domaine === 'ALL' ? 'Tous les domaines' : domaine}
                  </span>
                </div>
                <div className="overflow-x-auto">
                  <table className="min-w-[1650px] w-full border-collapse text-xs">
                    <thead className="bg-emerald-700 text-left font-extrabold uppercase tracking-wide text-white">
                      <tr>
                        {budgetColumns.map((c) => (
                          <th key={c} className="border-r border-emerald-600 px-4 py-4 text-right first:text-left">{c}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {budgetRows.map((r, idx) => (
                        <tr key={`${r.domaine}-${r.imputation}-${idx}`} className="hover:bg-emerald-50/40">
                          <td className="px-4 py-3">
                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border ${r.domaine === 'FONCTIONNEMENT'
                              ? 'bg-blue-50 text-[#1e40af] border-blue-200'
                              : 'bg-indigo-50 text-indigo-700 border-indigo-200'
                              }`}>
                              <span className={`w-1.5 h-1.5 rounded-full ${r.domaine === 'FONCTIONNEMENT' ? 'bg-blue-500' : 'bg-indigo-500'}`}></span>
                              {r.domaine}
                            </span>
                          </td>
                          <td className="px-4 py-3 font-bold text-slate-700">
                            {r.imputation}
                            <span className="ml-2 font-normal text-slate-400">{r.libelle}</span>
                          </td>
                          <td className="px-4 py-3 text-right">{money(r.total_credits)}</td>
                          <td className="px-4 py-3 text-right font-bold text-emerald-700">{money(r.credits_engages)}</td>
                          <td className="px-4 py-3 text-right">{rate(r.taux_engagement)}</td>
                          <td className="px-4 py-3 text-right">{money(r.credits_disponibles)}</td>
                          <td className="px-4 py-3 text-right font-bold text-violet-700">{money(r.total_ordonnance)}</td>
                          <td className="px-4 py-3 text-right">{rate(r.taux_ordonnancement)}</td>
                          <td className="px-4 py-3 text-right font-semibold text-emerald-700">{rate(r.taux_ordonnancement_notifie)}</td>
                          <td className="px-4 py-3 text-right font-bold text-amber-700">{money(r.total_paiements)}</td>
                          <td className="px-4 py-3 text-right">{rate(r.taux_paiement_ordonnancement)}</td>
                          <td className="px-4 py-3 text-right">{rate(r.taux_paiement_engagement)}</td>
                          <td className="px-4 py-3 text-right font-semibold text-emerald-700">{rate(r.taux_paiement_notifie)}</td>
                        </tr>
                      ))}
                      {!budgetRows.length && (
                        <tr>
                          <td colSpan="13" className="px-5 py-12 text-center text-slate-500">
                            Aucune ligne budgétaire pour ce filtre.
                          </td>
                        </tr>
                      )}
                    </tbody>
                    <tfoot className="border-t-2 border-emerald-700 bg-emerald-50 font-black text-emerald-950">
                      <tr>
                        <td className="px-4 py-4" colSpan="2">TOTAL {domaine === 'ALL' ? 'GLOBAL' : domaine}</td>
                        <td className="px-4 py-4 text-right">{money(budgetSummary?.total_notifie)}</td>
                        <td className="px-4 py-4 text-right">{money(budgetSummary?.total_engage)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_consommation)}</td>
                        <td className="px-4 py-4 text-right">{money(budgetSummary?.total_disponible)}</td>
                        <td className="px-4 py-4 text-right">{money(budgetSummary?.total_ordonnance)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_ordonnancement)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_ordonnancement_notifie || pct(budgetSummary?.total_ordonnance, budgetSummary?.total_notifie))}</td>
                        <td className="px-4 py-4 text-right">{money(budgetSummary?.total_paiement)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_paiement_ordonnancement)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_paiement_engagement)}</td>
                        <td className="px-4 py-4 text-right">{rate(budgetSummary?.taux_paiement_notifie || pct(budgetSummary?.total_paiement, budgetSummary?.total_notifie))}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </section>
          )}
        </main>

        {selected && (
          <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/35 p-4 backdrop-blur-sm">
            <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
              <div className="flex items-start justify-between border-b border-slate-100 p-6">
                <div>
                  <p className="text-xs font-bold uppercase tracking-wider text-blue-700">{selected.type}</p>
                  <h2 className="mt-1 text-xl font-bold">{selected.ref}</h2>
                  <p className="mt-2 text-sm text-slate-500">{selected.title}</p>
                </div>
                <button onClick={() => setSelected(null)} className="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><X size={19} /></button>
              </div>
              <div className="p-6">
                <h3 className="font-bold">Timeline du processus</h3>
                <div className="mt-5">
                  {['Programmation', 'Préparation', 'Lancement', 'Réception des offres', 'Ouverture', 'Évaluation', 'Attribution', 'Validation', 'Terminée'].map((step, index) => {
                    const [, progress] = phase(selected);
                    const done = index * 12.5 < progress;
                    const current = !done && (index - 1) * 12.5 < progress;
                    return (
                      <div key={step} className="flex gap-4 pb-5 last:pb-0">
                        <div className="flex flex-col items-center">
                          <span className={`grid h-7 w-7 place-items-center rounded-full text-xs ${done ? 'bg-emerald-500 text-white' : current ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-slate-100 text-slate-400'}`}>
                            {done ? <CheckCircle2 size={15} /> : index + 1}
                          </span>
                          {index < 8 && <span className={`h-6 w-px ${done ? 'bg-emerald-300' : 'bg-slate-200'}`} />}
                        </div>
                        <div>
                          <p className={`text-sm font-semibold ${current ? 'text-blue-700' : ''}`}>{step}{current && <span className="ml-2 text-xs font-normal">Étape actuelle</span>}</p>
                          <p className="text-xs text-slate-400">{done ? `Validée · ${date(selected.updated_at || selected.created_at)}` : current ? 'En traitement' : 'À venir'}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
                <div className="mt-6 flex justify-end gap-3">
                  <button onClick={() => setSelected(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">Fermer</button>
                  <button onClick={() => navigate(selected.route)} className="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Voir le dossier</button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
