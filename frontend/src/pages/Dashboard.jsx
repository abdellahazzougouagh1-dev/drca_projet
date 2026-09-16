import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  BarChart3, Bell, Building2, CalendarDays, CheckCircle2, ChevronRight,
  CircleDollarSign, ClipboardList, Download, Eye, FileCheck2, FileText,
  Gauge, Landmark, Loader2, Search, SlidersHorizontal, TrendingUp,
  WalletCards, X, ArrowUpRight, Clock3, FolderKanban,
  CreditCard, Settings, LogOut, Menu, ShieldCheck, Receipt, Plus,
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

function Progress({ value, color = 'bg-blue-600' }) {
  return <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div className={`h-full rounded-full ${color}`} style={{ width: `${Math.min(100, value)}%` }} /></div>;
}

function Kpi({ label, value, icon: Icon, tone = 'blue', detail }) {
  const tones = { blue: 'bg-blue-50 text-blue-600', emerald: 'bg-emerald-50 text-emerald-600', violet: 'bg-violet-50 text-violet-600', amber: 'bg-amber-50 text-amber-600', rose: 'bg-rose-50 text-rose-600' };
  return <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div className="flex justify-between gap-2"><div><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold tracking-tight text-slate-900">{value}</p></div><span className={`grid h-10 w-10 place-items-center rounded-xl ${tones[tone]}`}><Icon size={20} /></span></div><p className="mt-4 flex items-center gap-1 text-xs text-slate-400"><ArrowUpRight size={14} className="text-emerald-600" /><span className="font-bold text-emerald-600">À jour</span> · {detail}</p></article>;
}

export default function Dashboard({ mode = 'aoo' }) {
  const navigate = useNavigate();
  const isDirecteur = mode === 'directeur';
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(String(currentYear));
  const [month, setMonth] = useState('');
  const [tab, setTab] = useState(mode === 'directeur' ? 'notifications' : 'overview');
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
      ]);
      if (!active) return;
      const result = (index, fallback) => responses[index].status === 'fulfilled' ? responses[index].value.data : fallback;
      setStore({ consultations: result(0, []), aoos: result(1, []), notifications: result(2, []), lines: result(3, []), budget: result(4, {}) });
      if (responses.some((item) => item.status === 'rejected')) setError('Certaines sources n’ont pas pu être chargées. Les données disponibles restent affichées.');
      setLoading(false);
    }
    load();
    return () => { active = false; };
  }, [year]);

  const dashboard = useMemo(() => {
    const matchYear = (item) => !year || String(item.annee || item.exercice || new Date(item.date_consultation || item.date_lancement || item.created_at || 0).getFullYear()) === String(year);
    const rawDossiers = [
      ...store.consultations.map((item) => ({ ...item, type: 'Consultation', ref: item.numero_consultation, title: item.objet_consultation, route: `/consultations/${item.id}` })),
      ...store.aoos.map((item) => ({ ...item, type: 'Appel d’offres', ref: item.num_aoo, title: item.objet, route: `/aoos/${item.id}` })),
    ].filter(matchYear);
    const filtered = month ? rawDossiers.filter((item) => new Date(item.created_at || item.date_consultation || item.date_preparation).getMonth() + 1 === Number(month)) : rawDossiers;
    const notified = Number(store.budget.total_notifie || store.lines.reduce((sum, line) => sum + Number(line.total_credits || 0), 0));
    const engaged = Number(store.budget.total_engage || store.lines.reduce((sum, line) => sum + Number(line.credits_engages || 0), 0));
    const ordered = Number(store.budget.total_ordonnance || 0);
    const paid = Number(store.budget.total_paiement || 0);
    const count = (keywords) => filtered.filter((item) => keywords.some((word) => String(item.statut_dossier || item.statut || '').toLowerCase().includes(word))).length;
    const trends = months.map((name, index) => {
      const notifications = store.notifications.filter((item) => new Date(item.date_notification || item.created_at).getMonth() === index);
      return { name, Nombre: notifications.length, Montant: notifications.reduce((sum, item) => sum + Number(item.montant || item.montant_total || 0), 0) };
    });
    return { dossiers: filtered, notified, engaged, ordered, paid, available: Number(store.budget.total_disponible ?? notified - engaged), engagement: percentage(engaged, notified), ordering: percentage(ordered, engaged), payment: percentage(paid, ordered), completed: count(['termin', 'clôtur', 'valid']), active: count(['cours', 'lanc', 'analyse']), planned: count(['programm', 'prépar']), trends };
  }, [store, month]);

  const shownDossiers = dashboard.dossiers.filter((item) => `${item.ref || ''} ${item.title || ''} ${item.type}`.toLowerCase().includes(search.toLowerCase()));
  const exportReport = () => {
    const rows = [['Référence', 'Type', 'Objet', 'Statut', 'Phase', 'Avancement'], ...dashboard.dossiers.map((item) => { const [label, progress] = phase(item); return [item.ref || '', item.type, item.title || '', item.statut_dossier || item.statut || '', label, `${progress}%`]; })];
    const csv = rows.map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(';')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' }); const link = document.createElement('a');
    link.href = URL.createObjectURL(blob); link.download = `rapport-directeur-${year}.csv`; link.click(); URL.revokeObjectURL(link.href);
  };

  if (loading) return <div className="grid min-h-screen place-items-center bg-slate-50 text-slate-600"><span className="flex items-center gap-3"><Loader2 className="animate-spin text-blue-700" /> Chargement du tableau de pilotage…</span></div>;

  const navigation = [
    { label: 'Notifications', icon: Bell, tab: 'notifications', action: () => setTab('notifications') },
    { label: 'Consultations & AOO', icon: FolderKanban, tab: 'dossiers', action: () => setTab('dossiers') },
    { label: 'Analyse budgétaire', icon: CreditCard, tab: 'budget', action: () => setTab('budget') },
  ];
  const moduleNavigation = [
    { label: 'Appels d’offres', icon: FileText, active: true },
    { label: 'Notifications', icon: Bell, route: '/notifications' },
    { label: 'Consultations', icon: ClipboardList, route: '/consultations' },
    { label: 'Engagements', icon: WalletCards, route: '/engagements' },
    { label: 'Liquidations', icon: CircleDollarSign, route: '/liquidations' },
    { label: 'Ordonnancements', icon: CreditCard, route: '/ordonnancements' },
  ];

  return <div className="min-h-screen bg-[#f6f8fc] text-slate-900">
    <aside className="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col border-r border-slate-800 bg-[#101b33] p-5 text-slate-300 lg:flex">
      <Link to={isDirecteur ? '/directeur' : '/dashboard'} className="flex items-center gap-3 border-b border-slate-700/80 pb-6"><span className="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 text-sm font-black text-white shadow-lg shadow-blue-900">DR</span><span><b className="block text-sm text-white">DRCA · RSK</b><small className="text-xs text-slate-400">{isDirecteur ? 'Espace de direction' : 'Appels d’offres'}</small></span></Link>
      {isDirecteur ? <><p className="mt-7 px-3 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500">Pilotage</p><nav className="mt-3 space-y-1">{navigation.map(({ label, icon: Icon, action, tab: itemTab }) => <button key={label} onClick={action} className={`flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold transition ${tab === itemTab ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`}><Icon size={18} />{label}</button>)}</nav></> : <><p className="mt-7 px-3 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500">Cycle d'appel d’offres</p><nav className="mt-3 space-y-1">{moduleNavigation.map(({ label, icon: Icon, route, active: isItemActive }) => isItemActive || !route ? <div key={label} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold bg-blue-600 text-white shadow-lg shadow-blue-950/30 cursor-default select-none"><Icon size={18} /><span>{label}</span></div> : <Link key={route} to={route} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white"><Icon size={18} /><span>{label}</span></Link>)}</nav></>}
      <div className="mt-auto rounded-2xl border border-slate-700 bg-slate-800/60 p-3"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-blue-500 text-xs font-bold text-white">DR</span><span className="min-w-0"><b className="block truncate text-sm text-white">Directeur Régional</b><small className="block truncate text-xs text-slate-400">Accès décisionnel</small></span></div><button onClick={logout} className="mt-3 flex w-full items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-700 hover:text-white"><LogOut size={15} /> Se déconnecter</button></div>
    </aside>
    <div className="lg:pl-72">
      <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur"><div className="mx-auto flex max-w-[1920px] items-center justify-between px-4 py-4 sm:px-7"><Link to={isDirecteur ? '/directeur' : '/dashboard'} className="flex items-center gap-3 lg:hidden"><span className="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-blue-700 to-indigo-700 text-sm font-black text-white">DR</span><span><b className="block text-sm">DRCA · RSK</b><small className="text-xs text-slate-500">{isDirecteur ? 'Espace de direction' : 'Appels d’offres'}</small></span></Link><div className="hidden lg:block"><p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Plateforme sécurisée</p><p className="mt-1 flex items-center gap-2 text-sm font-bold text-slate-700"><ShieldCheck size={16} className="text-emerald-600" /> {isDirecteur ? 'Espace décisionnel Directeur' : 'Cycle des appels d’offres'}</p></div><div className="flex items-center gap-2.5"><Link to="/directeur" className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold rounded-xl shadow-xs transition transform active:scale-98" title="Accéder à l'Espace Directeur"><ShieldCheck size={15} /><span>Directeur</span></Link><Link to="/bons-commande" className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition transform active:scale-98" title="Accéder aux Bons de commande"><Receipt size={15} /><span>Bons de commande</span></Link><button onClick={() => setMobileNavOpen(!mobileNavOpen)} className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"><Menu size={18} /></button></div></div></header>
      {mobileNavOpen && <div className="fixed inset-x-4 top-16 z-50 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">{isDirecteur ? <div className="space-y-1">{navigation.map(({ label, icon: Icon, action }) => <button key={label} onClick={() => { action(); setMobileNavOpen(false); }} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-700"><Icon size={18} />{label}</button>)}</div> : <div className="space-y-1">{moduleNavigation.map(({ label, icon: Icon, route, active: isItemActive }) => isItemActive || !route ? <div key={label} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold bg-blue-600 text-white shadow-sm cursor-default select-none"><Icon size={18} /><span>{label}</span></div> : <Link key={route} to={route} onClick={() => setMobileNavOpen(false)} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-700"><Icon size={18} /><span>{label}</span></Link>)}</div>}<button onClick={logout} className="mt-2 flex w-full items-center gap-3 rounded-xl border-t border-slate-100 px-3 py-3 text-left text-sm font-semibold text-rose-600"><LogOut size={18} /> Se déconnecter</button></div>}
      <main className="mx-auto max-w-[1920px] px-4 py-7 sm:px-7"><div className="flex flex-col justify-between gap-5 xl:flex-row xl:items-end"><div><p className="text-sm font-semibold text-blue-700">DIRECTION RÉGIONALE</p><h1 className="mt-1 text-3xl font-bold tracking-tight">{isDirecteur ? 'Tableau de bord Directeur' : 'Tableau de bord des appels d’offres'}</h1><p className="mt-2 text-sm text-slate-500">{isDirecteur ? 'Vue consolidée des activités, procédures et indicateurs budgétaires.' : 'Vue consolidée du cycle : consultation, engagement, liquidation et ordonnancement.'}</p></div><div className="flex flex-wrap gap-2"><div className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:border-slate-300 transition-colors shadow-xs"><CalendarDays size={16} className="text-blue-600 shrink-0" /><select value={year} onChange={(event) => { if (event.target.value === '__ADD__') { handleAddYear(); } else { setYear(event.target.value); } }} className="bg-transparent font-semibold outline-none cursor-pointer text-slate-800 pr-1">{availableYears.map((y) => <option key={y} value={y}>{y}</option>)}<option value="__ADD__" className="text-blue-600 font-bold bg-blue-50">+ Autre année...</option></select><button type="button" onClick={handleAddYear} title="Ajouter une année" className="grid h-6 w-6 place-items-center rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition-colors cursor-pointer"><Plus size={13} className="stroke-[2.5]" /></button></div><select value={month} onChange={(event) => setMonth(event.target.value)} className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 outline-none"><option value="">Tous les mois</option>{months.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}</select><button className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600"><SlidersHorizontal size={16} /> Filtres</button></div></div>
        {error && <p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{error}</p>}
        {tab === 'overview' && <><section className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5"><Kpi label="Consultations" value={dashboard.dossiers.length} icon={ClipboardList} detail={`${dashboard.completed} terminées`} /><Kpi label="Appels d’offres" value={store.aoos.length} icon={FileText} tone="violet" detail={`${dashboard.active} en cours`} /><Kpi label="Notifications" value={store.notifications.length} icon={Bell} tone="amber" detail={money(dashboard.notified)} /><Kpi label="Engagements" value={money(dashboard.engaged)} icon={FileCheck2} tone="emerald" detail={`${dashboard.engagement}% du notifié`} /><Kpi label="Disponible" value={money(dashboard.available)} icon={WalletCards} tone="rose" detail="Crédits restant à engager" /></section>
          <section className="mt-6 max-w-xl"><div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div className="flex justify-between"><div><h2 className="font-bold">Taux d’engagement</h2><p className="mt-1 text-sm text-slate-500">Engagé / notifié</p></div><Gauge className="text-emerald-600" size={20} /></div><div className="relative mt-4 h-56"><ResponsiveContainer width="100%" height="100%"><PieChart><Pie data={[{ value: dashboard.engagement }, { value: 100 - dashboard.engagement }]} dataKey="value" innerRadius={68} outerRadius={88} startAngle={90} endAngle={-270} stroke="none"><Cell fill="#10b981" /><Cell fill="#e2e8f0" /></Pie></PieChart></ResponsiveContainer><div className="absolute inset-0 grid place-items-center"><b className="text-3xl">{dashboard.engagement}%</b></div></div><p className="rounded-xl bg-emerald-50 px-3 py-2 text-center text-sm font-semibold text-emerald-700">{money(dashboard.engaged)} engagés</p></div></section>
          <section className="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div className="flex flex-wrap items-center justify-between gap-2"><div><h2 className="font-bold">Cycle d’exécution budgétaire</h2><p className="mt-1 text-sm text-slate-500">Montants et taux de passage à chaque étape.</p></div><Link to="/notifications" className="text-sm font-semibold text-blue-700">Voir les notifications <ChevronRight className="inline" size={15} /></Link></div><div className="mt-6 grid gap-3 md:grid-cols-4">{[['Notification', dashboard.notified, 100, Landmark, 'bg-blue-600'], ['Engagement', dashboard.engaged, dashboard.engagement, FileCheck2, 'bg-emerald-500'], ['Ordonnancement', dashboard.ordered, dashboard.ordering, FileText, 'bg-violet-500'], ['Paiement', dashboard.paid, dashboard.payment, CircleDollarSign, 'bg-amber-500']].map(([label, value, rate, Icon, color]) => <div key={label} className="rounded-xl border border-slate-100 bg-slate-50 p-4"><Icon className="text-slate-500" size={19} /><p className="mt-3 text-xs font-semibold uppercase text-slate-500">{label}</p><b className="mt-1 block text-lg">{money(value)}</b><div className="mt-3 flex items-center gap-3"><Progress value={rate} color={color} /><span className="text-xs font-bold">{rate}%</span></div></div>)}</div></section>
        </>}

        {tab === 'notifications' && <section className="mt-6"><div className="grid gap-4 md:grid-cols-3"><Kpi label="Notifications de l’exercice" value={store.notifications.length} icon={Bell} tone="amber" detail={`Exercice ${year}`} /><Kpi label="Montant total notifié" value={money(dashboard.notified)} icon={Landmark} detail="Crédits budgétaires notifiés" /><Kpi label="Lignes budgétaires" value={store.lines.length} icon={ClipboardList} tone="violet" detail="Lignes suivies" /></div><div className="mt-6 space-y-6"><div className="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="flex items-center justify-between border-b border-slate-100 p-5"><div><h2 className="font-bold">Liste des notifications</h2><p className="mt-1 text-sm text-slate-500">Consultez les informations de l'exercice {year}.</p></div><Bell className="text-amber-500" size={20} /></div><div className="overflow-x-auto"><table className="w-full min-w-[750px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-3">Référence</th><th className="px-5 py-3">Date</th><th className="px-5 py-3">Exercice</th><th className="px-5 py-3 text-right">Crédits</th><th className="px-5 py-3 text-right">Diminution</th><th className="px-5 py-3 text-right">Montant</th></tr></thead><tbody className="divide-y divide-slate-100">{store.notifications.map((notification) => { const lignes = notification.lignes || []; const totalReports = lignes.reduce((sum, l) => sum + Number(l.reports || 0), 0); const totalNeufs = lignes.reduce((sum, l) => sum + Number(l.credits_neufs || 0), 0); const totalEngagements = lignes.reduce((sum, l) => sum + Number(l.credits_engagements || 0), 0); const totalCredits = (totalReports + totalNeufs + totalEngagements) || Number(notification.montant || 0); const totalDimReports = lignes.reduce((sum, l) => sum + Number(l.diminution_report || 0), 0); const totalDimNeufs = lignes.reduce((sum, l) => sum + Number(l.diminution_credit_neuf || 0), 0); const totalDimEngagements = lignes.reduce((sum, l) => sum + Number(l.diminution_credit_engagement || 0), 0); const totalDiminution = totalDimReports + totalDimNeufs + totalDimEngagements; return <tr key={notification.id} className="hover:bg-slate-50 transition-colors"><td className="px-5 py-4 font-mono text-xs font-semibold text-blue-700">{notification.numero || notification.reference || `Notification #${notification.id}`}</td><td className="px-5 py-4 text-slate-600">{date(notification.date_notification || notification.created_at)}</td><td className="px-5 py-4 text-slate-600">{notification.exercice || year}</td><td className="px-5 py-4 text-right"><div className="flex flex-col items-end gap-1.5">{(totalReports > 0 || totalNeufs > 0 || totalEngagements > 0) ? <div className="flex flex-col items-end gap-1.5 w-full min-w-[195px] max-w-[215px]">{totalReports > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-sky-50/90 border border-sky-200/90 rounded-lg text-xs shadow-xs hover:bg-sky-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-sky-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-sky-500"></span>Reports</span><span className="font-mono font-bold text-sky-950 text-xs">{money(totalReports)}</span></div>}{totalNeufs > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-emerald-50/90 border border-emerald-200/90 rounded-lg text-xs shadow-xs hover:bg-emerald-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-emerald-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Crédits neufs</span><span className="font-mono font-bold text-emerald-950 text-xs">{money(totalNeufs)}</span></div>}{totalEngagements > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-indigo-50/90 border border-indigo-200/90 rounded-lg text-xs shadow-xs hover:bg-indigo-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-indigo-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>Crédits d'eng.</span><span className="font-mono font-bold text-indigo-950 text-xs">{money(totalEngagements)}</span></div>}</div> : <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold text-slate-400 bg-slate-100/80 border border-slate-200/60">0 DH</span>}</div></td><td className="px-5 py-4 text-right"><div className="flex flex-col items-end gap-1.5">{totalDiminution > 0 ? <div className="flex flex-col items-end gap-1.5 w-full min-w-[195px] max-w-[215px]">{totalDimReports > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Dim. Reports</span><span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimReports)}</span></div>}{totalDimNeufs > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Dim. Neufs</span><span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimNeufs)}</span></div>}{totalDimEngagements > 0 && <div className="w-full flex items-center justify-between gap-2 px-2.5 py-1 bg-rose-50/90 border border-rose-200/90 rounded-lg text-xs shadow-xs hover:bg-rose-100/70 transition-colors"><span className="inline-flex items-center gap-1.5 font-semibold text-rose-800 text-[11px]"><span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Dim. Eng.</span><span className="font-mono font-bold text-rose-950 text-xs">−{money(totalDimEngagements)}</span></div>}</div> : <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold text-slate-400 bg-slate-50 border border-slate-200/60">0 DH</span>}</div></td><td className="px-5 py-4 text-right font-black text-slate-900">{money(notification.montant || totalCredits)}</td></tr>; })}{!store.notifications.length && <tr><td colSpan="6" className="px-5 py-12 text-center text-slate-500">Aucune notification pour cet exercice.</td></tr>}</tbody></table></div></div><div className="w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div className="flex items-center justify-between"><div><h2 className="font-bold">Évolution des notifications</h2><p className="mt-1 text-sm text-slate-500">Nombre de notifications par mois.</p></div><TrendingUp className="text-blue-600" size={20} /></div><div className="mt-5 h-72"><ResponsiveContainer width="100%" height="100%"><AreaChart data={dashboard.trends}><defs><linearGradient id="notificationFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stopColor="#2563eb" stopOpacity=".28" /><stop offset="100%" stopColor="#2563eb" stopOpacity="0" /></linearGradient></defs><CartesianGrid vertical={false} stroke="#e2e8f0" /><XAxis dataKey="name" axisLine={false} tickLine={false} fontSize={11} /><YAxis allowDecimals={false} axisLine={false} tickLine={false} fontSize={11} /><Tooltip contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0' }} /><Area type="monotone" dataKey="Nombre" stroke="#2563eb" strokeWidth={3} fill="url(#notificationFill)" /></AreaChart></ResponsiveContainer></div></div></div></section>}

        {tab === 'dossiers' && <section className="mt-6"><div className="grid gap-4 sm:grid-cols-3"><Kpi label="Terminées" value={dashboard.completed} icon={CheckCircle2} tone="emerald" detail="Dossiers clôturés / validés" /><Kpi label="En cours" value={dashboard.active} icon={Clock3} tone="amber" detail="À suivre activement" /><Kpi label="Programmées" value={dashboard.planned} icon={CalendarDays} detail="À préparer" /></div><div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 className="font-bold">Suivi des procédures</h2><p className="mt-1 text-sm text-slate-500">Cliquez sur un dossier pour visualiser sa timeline.</p></div><label className="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-slate-500"><Search size={16} /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Rechercher…" className="w-40 bg-transparent text-sm outline-none" /></label></div><div className="overflow-x-auto"><table className="w-full min-w-[1000px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-3">Référence</th><th className="px-5 py-3">Mode d’engagement</th><th className="px-5 py-3">Ligne budgétaire</th><th className="px-5 py-3">Objet</th><th className="px-5 py-3">Lancement</th><th className="px-5 py-3 text-center">Phase / Statut</th><th className="px-5 py-3 text-right"></th></tr></thead><tbody className="divide-y divide-slate-100">{shownDossiers.map((item) => { const phaseInfo = getDossierPhase(item); const mode = engagementMode(item); return <tr key={`${item.type}-${item.id}`} className="hover:bg-slate-50 transition-colors"><td className="px-5 py-4"><b className="font-mono text-blue-700">{item.ref || '—'}</b><small className="mt-1 block text-slate-400">{item.type}</small></td><td className="px-5 py-4"><span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${mode === 'Appel d’offres' ? 'bg-violet-50 text-violet-700' : 'bg-cyan-50 text-cyan-700'}`}>{mode}</span></td><td className="px-5 py-4"><span className="whitespace-nowrap font-mono text-xs font-semibold text-slate-600">{budgetLine(item)}</span></td><td className="max-w-sm px-5 py-4"><p className="truncate font-medium">{item.title || 'Sans objet'}</p><small className="text-slate-500">{item.service || item.structure_acheteuse || 'DRCA RSK'}</small></td><td className="px-5 py-4 text-slate-600">{date(item.date_lancement || item.date_consultation || item.created_at)}</td><td className="px-5 py-4 text-center"><span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border ${phaseInfo.badgeClass}`}><span className={`w-1.5 h-1.5 rounded-full ${phaseInfo.dotClass}`}></span>{phaseInfo.label}</span></td><td className="px-5 py-4 text-right"><button onClick={() => setSelected(item)} className="inline-flex items-center gap-1 font-semibold text-blue-700 hover:underline"><Eye size={16} /> Détail</button></td></tr>; })}{!shownDossiers.length && <tr><td colSpan="7" className="px-5 py-12 text-center text-slate-500">Aucun dossier ne correspond aux filtres.</td></tr>}</tbody></table></div></div></section>}

        {tab === 'budget' && <section className="mt-6"><div className="grid gap-4 md:grid-cols-3"><Kpi label="Taux d’engagement" value={`${dashboard.engagement}%`} icon={Gauge} tone="emerald" detail="Engagé / notifié" /><Kpi label="Taux d’ordonnancement" value={`${dashboard.ordering}%`} icon={FileText} tone="violet" detail="Ordonnancé / engagé" /><Kpi label="Taux de paiement" value={`${dashboard.payment}%`} icon={CircleDollarSign} tone="amber" detail="Payé / ordonnancé" /></div><div className="mt-6 grid gap-6 xl:grid-cols-2"><div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold">Notifications par ligne budgétaire</h2><p className="mt-1 text-sm text-slate-500">Comparaison des crédits notifiés et engagés.</p><div className="mt-5 h-80"><ResponsiveContainer width="100%" height="100%"><BarChart data={store.lines.slice(0, 7).map((line) => ({ name: `${line.article}/${line.paragraphe}/${line.ligne_budgetaire}`, Notifié: Number(line.total_credits || 0), Engagé: Number(line.credits_engages || 0) }))}><CartesianGrid vertical={false} stroke="#e2e8f0" /><XAxis dataKey="name" fontSize={10} axisLine={false} tickLine={false} /><YAxis tickFormatter={(value) => `${Math.round(value / 1000)}k`} fontSize={11} axisLine={false} tickLine={false} /><Tooltip formatter={(value) => money(value)} /><Bar dataKey="Notifié" fill="#2563eb" radius={[4, 4, 0, 0]} /><Bar dataKey="Engagé" fill="#10b981" radius={[4, 4, 0, 0]} /></BarChart></ResponsiveContainer></div></div><div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold">Comparaison financière</h2><p className="mt-1 text-sm text-slate-500">Suivi de la consommation budgétaire.</p><div className="mt-7 space-y-6">{[['Notifiés', dashboard.notified, 100, 'bg-blue-600'], ['Engagés', dashboard.engaged, dashboard.engagement, 'bg-emerald-500'], ['Ordonnancés', dashboard.ordered, dashboard.ordering, 'bg-violet-500'], ['Payés', dashboard.paid, dashboard.payment, 'bg-amber-500']].map(([label, value, rate, color]) => <div key={label}><div className="mb-2 flex justify-between text-sm"><span className="font-semibold">{label}</span><b>{money(value)}</b></div><Progress value={rate} color={color} /></div>)}</div></div></div><div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="border-b border-slate-100 p-5"><h2 className="font-bold">Détail par ligne budgétaire</h2></div><div className="overflow-x-auto"><table className="w-full min-w-[800px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-5 py-3">Ligne</th><th className="px-5 py-3">Libellé</th><th className="px-5 py-3 text-right">Notifié</th><th className="px-5 py-3 text-right">Engagé</th><th className="px-5 py-3 text-right">Disponible</th></tr></thead><tbody className="divide-y divide-slate-100">{store.lines.map((line) => <tr key={line.id}><td className="px-5 py-4 font-mono text-xs font-semibold">{line.article}/{line.paragraphe}/{line.ligne_budgetaire}</td><td className="px-5 py-4 text-slate-600">{line.libelle || '—'}</td><td className="px-5 py-4 text-right font-semibold">{money(line.total_credits)}</td><td className="px-5 py-4 text-right font-semibold text-emerald-700">{money(line.credits_engages)}</td><td className="px-5 py-4 text-right font-semibold text-blue-700">{money(line.credits_disponibles)}</td></tr>)}{!store.lines.length && <tr><td colSpan="5" className="px-5 py-12 text-center text-slate-500">Aucune ligne budgétaire pour cet exercice.</td></tr>}</tbody></table></div></div></section>}

        {tab === 'reports' && <section className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]"><div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-3"><span className="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-blue-600"><BarChart3 /></span><div><h2 className="font-bold">Rapport d’activité Directeur</h2><p className="text-sm text-slate-500">Synthèse selon les filtres sélectionnés.</p></div></div><div className="mt-6 grid gap-3 sm:grid-cols-2">{[['Exercice', year], ['Période', month ? months[Number(month) - 1] : 'Année complète'], ['Dossiers', dashboard.dossiers.length], ['Lignes budgétaires', store.lines.length]].map(([label, value]) => <div key={label} className="rounded-xl bg-slate-50 p-4"><p className="text-xs font-semibold uppercase text-slate-500">{label}</p><p className="mt-1 font-bold">{value}</p></div>)}</div><div className="mt-6 flex flex-wrap gap-3"><button onClick={exportReport} className="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800"><Download size={17} /> Exporter Excel / CSV</button><button onClick={() => window.print()} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><FileText size={17} /> Imprimer / PDF</button></div></div><div className="rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-800 p-6 text-white shadow-sm"><Building2 className="text-blue-200" /><h2 className="mt-6 text-xl font-bold">Point d’attention</h2><p className="mt-2 text-sm leading-6 text-blue-100">{dashboard.active} dossier(s) nécessitent un suivi actif. Ouvrez leur timeline pour anticiper les prochaines validations.</p><button onClick={() => setTab('dossiers')} className="mt-6 inline-flex items-center gap-2 text-sm font-bold hover:underline">Ouvrir le suivi <ChevronRight size={16} /></button></div></section>}
      </main>
      {selected && <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/35 p-4 backdrop-blur-sm"><div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"><div className="flex items-start justify-between border-b border-slate-100 p-6"><div><p className="text-xs font-bold uppercase tracking-wider text-blue-700">{selected.type}</p><h2 className="mt-1 text-xl font-bold">{selected.ref}</h2><p className="mt-2 text-sm text-slate-500">{selected.title}</p></div><button onClick={() => setSelected(null)} className="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><X size={19} /></button></div><div className="p-6"><h3 className="font-bold">Timeline du processus</h3><div className="mt-5">{['Programmation', 'Préparation', 'Lancement', 'Réception des offres', 'Ouverture', 'Évaluation', 'Attribution', 'Validation', 'Terminée'].map((step, index) => { const [, progress] = phase(selected); const done = index * 12.5 < progress; const current = !done && (index - 1) * 12.5 < progress; return <div key={step} className="flex gap-4 pb-5 last:pb-0"><div className="flex flex-col items-center"><span className={`grid h-7 w-7 place-items-center rounded-full text-xs ${done ? 'bg-emerald-500 text-white' : current ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-slate-100 text-slate-400'}`}>{done ? <CheckCircle2 size={15} /> : index + 1}</span>{index < 8 && <span className={`h-6 w-px ${done ? 'bg-emerald-300' : 'bg-slate-200'}`} />}</div><div><p className={`text-sm font-semibold ${current ? 'text-blue-700' : ''}`}>{step}{current && <span className="ml-2 text-xs font-normal">Étape actuelle</span>}</p><p className="text-xs text-slate-400">{done ? `Validée · ${date(selected.updated_at || selected.created_at)}` : current ? 'En traitement' : 'À venir'}</p></div></div>; })}</div><div className="mt-6 flex justify-end gap-3"><button onClick={() => setSelected(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">Fermer</button><button onClick={() => navigate(selected.route)} className="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Voir le dossier</button></div></div></div></div>}
    </div></div>;
}
