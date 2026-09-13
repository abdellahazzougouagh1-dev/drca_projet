import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { Link, useLocation } from 'react-router-dom';
import api from '../api/axios';
import {
  ArrowRight,
  Building2,
  Check,
  ClipboardCheck,
  FileCheck2,
  FileText,
  Landmark,
  Loader2,
  ReceiptText,
  WalletCards,
  BarChart3,
  PieChart as PieChartIcon,
} from 'lucide-react';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Legend } from 'recharts';

const workflowSteps = [
  { number: '1', title: 'Notification', subtitle: 'Gestion des crédits budgétaires', to: '/notifications', icon: Landmark },
  { number: '2', title: 'Consultation', subtitle: "Appel d'offres / Bons de commande / Conventions", to: '/consultations', icon: FileText },
  { number: '3', title: 'Engagement', subtitle: 'Acte et crédits disponibles', to: '/engagements', icon: FileCheck2 },
  { number: '4', title: 'Liquidation', subtitle: 'Exécution et décomptes', to: '/liquidations', icon: ReceiptText },
  { number: '5', title: 'Ordonnancement', subtitle: 'Paiement et clôture', to: '/ordonnancements', icon: WalletCards },
];

const aooModes = [
  { title: 'Appel d’Offres Ouvert National', description: 'Procédure ouverte nationale', mode: 'national' },
  { title: 'Appel d’Offres Ouvert International', description: 'Procédure ouverte internationale', mode: 'international' },
  { title: 'Appel d’Offres Simplifié', description: 'Procédure adaptée simplifiée', mode: 'simplifie' },
];

const formatMoney = (value) => new Intl.NumberFormat('fr-FR', {
  style: 'currency',
  currency: 'MAD',
  maximumFractionDigits: 0,
}).format(value || 0).replace('MAD', 'DH');

export default function Dashboard() {
  const location = useLocation();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [stats, setStats] = useState({ fournisseurs: 0, consultations: 0, budget: 0 });
  const [budgetStats, setBudgetStats] = useState({
    total_notifie: 0, total_engage: 0, total_disponible: 0,
    total_liquide: 0, total_ordonnance: 0, taux_consommation: 0
  });
  const [lignesBudgetaires, setLignesBudgetaires] = useState([]);
  const [activeDossier, setActiveDossier] = useState(null);
  const [members, setMembers] = useState([]);
  const [showAooModes, setShowAooModes] = useState(false);
  const [selectedYear, setSelectedYear] = useState('');
  const [selectedLigneDetails, setSelectedLigneDetails] = useState(null);

  useEffect(() => {
    let active = true;

    const loadDashboard = async () => {
      try {
        const queryParams = selectedYear ? `?exercice=${selectedYear}` : '';
        const [fournisseursRes, consultationsRes, budgetsRes, marchesRes, membersRes, dashboardBudgetRes, lignesRes] = await Promise.all([
          api.get('/fournisseurs'),
          api.get('/consultations'),
          api.get('/budgets'),
          api.get('/marches'),
          api.get('/commission-membres'),
          api.get(`/dashboard/budget${queryParams}`),
          api.get(`/notification-lignes${queryParams}`),
        ]);

        if (!active) return;

        setStats({
          fournisseurs: fournisseursRes.data.length,
          consultations: consultationsRes.data.length,
          budget: budgetsRes.data.reduce((total, budget) => total + Number(budget.montant_ttc || 0), 0),
        });
        setBudgetStats(dashboardBudgetRes.data);
        setActiveDossier(marchesRes.data[0] || consultationsRes.data[0] || null);
        setMembers(membersRes.data.slice(0, 5));
        setLignesBudgetaires(lignesRes.data || []);
      } catch (requestError) {
        console.error('Erreur de chargement du tableau de bord :', requestError);
        if (active) setError('Certaines données du tableau de bord ne sont pas disponibles.');
      } finally {
        if (active) setLoading(false);
      }
    };

    loadDashboard();
    return () => { active = false; };
  }, [selectedYear]);

  const dossierNumber = activeDossier?.num_marche || activeDossier?.numero_consultation || 'Aucun dossier sélectionné';
  const dossierSubject = activeDossier?.objet_marche || activeDossier?.objet_consultation || 'Créez ou ouvrez un dossier pour commencer son traitement.';

  return (
    <div
      className="min-h-screen text-slate-900 lg:flex bg-cover bg-center bg-fixed"
      style={{ backgroundImage: "url('/bg.jpg')" }}
    >
      <aside className="w-full bg-[#1e3a8a]/60 backdrop-blur-lg border-r border-white/10 text-white lg:sticky lg:top-0 lg:h-screen lg:w-[380px] lg:flex-none lg:overflow-y-auto shadow-2xl z-20">
        <div className="px-8 pb-6 pt-12 lg:px-8">
          <Link to="/dashboard" className="flex items-center gap-4 group">
            <div className="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30 font-serif text-xl font-bold text-white text-center leading-tight transition-transform group-hover:scale-105">
              <span>DR<br /><span className="text-[10px] text-blue-100 font-sans tracking-widest">RSK</span></span>
            </div>
            <div>
              <p className="font-sans text-2xl font-extrabold tracking-tight text-white">DRCA - RSK</p>
              <p className="text-xs font-semibold tracking-widest text-blue-300 uppercase mt-0.5">Marchés Publics</p>
            </div>
          </Link>
        </div>

        <nav className="px-5 pb-8 lg:pb-8 mt-4">
          <Link to="/dashboard" className={`mb-8 flex items-center gap-4 rounded-2xl px-5 py-4 transition-all duration-300 shadow-xl border ${location.pathname === '/dashboard' ? 'bg-white/15 border-white/20 shadow-black/20 backdrop-blur-md' : 'border-transparent hover:bg-white/5'}`}>
            <span className="grid h-12 w-12 place-items-center rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500 text-white shadow-lg shadow-cyan-500/30"><Landmark size={22} /></span>
            <span>
              <span className="block font-sans text-lg font-bold text-white">Tableau de bord</span>
              <span className="text-xs font-medium text-blue-200/80">Vue d'ensemble du dossier</span>
            </span>
          </Link>

          <div className="relative space-y-3 before:absolute before:bottom-8 before:left-[41px] before:top-8 before:w-0.5 before:bg-gradient-to-b before:from-white/20 before:to-transparent">
            {workflowSteps.map(({ number, title, subtitle, to, icon: Icon }) => {
              const selected = false; // On Dashboard, workflow steps are never active
              return (
                <Link key={title} to={to} className={`relative z-10 flex items-center gap-4 rounded-2xl px-4 py-3 transition-all duration-300 group hover:bg-white/10`}>
                  <span className={`grid h-11 w-11 place-items-center rounded-xl border text-base font-bold transition-all duration-300 shadow-md ${selected ? 'border-cyan-300 bg-cyan-300 text-[#0f172a]' : 'border-white/10 bg-white/5 text-blue-100 group-hover:border-white/30 group-hover:bg-white/20'}`}>
                    {number}
                  </span>
                  <span>
                    <span className="block font-sans text-base font-bold text-white/90 group-hover:text-white transition-colors">{title}</span>
                    <span className="text-[11px] font-medium text-blue-200/60 group-hover:text-blue-200 transition-colors">{subtitle}</span>
                  </span>
                  <Icon className="ml-auto text-white/30 group-hover:text-white/70 transition-colors" size={18} />
                </Link>
              );
            })}
          </div>
        </nav>
      </aside>

      <main className="min-w-0 flex-1 relative">
        <div className="absolute inset-0 bg-blue-100/50 backdrop-blur-sm z-0"></div>
        <div className="relative z-10 px-5 py-10 sm:px-10 lg:px-14 lg:py-14 mx-auto max-w-7xl">
          {error && <p className="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">{error}</p>}

          <section className="mt-8 mb-10">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
              <h2 className="font-serif text-3xl font-bold text-[#1e3a8a] flex items-center gap-2">
                <BarChart3 className="text-blue-500" /> Situation budgétaire globale
              </h2>
              <div className="flex items-center gap-2 bg-white/70 backdrop-blur-md px-4 py-2 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                <span className="text-sm font-semibold text-slate-600">Exercice :</span>
                <select
                  className="bg-transparent text-[#1e3a8a] font-bold outline-none cursor-pointer"
                  value={selectedYear}
                  onChange={(e) => setSelectedYear(e.target.value)}
                >
                  <option value="">Tous les exercices</option>
                  <option value="2026">2026</option>
                  <option value="2025">2025</option>
                  <option value="2024">2024</option>
                  <option value="2023">2023</option>
                </select>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
              {[
                { label: 'Notifiés', value: budgetStats.total_notifie, color: 'from-indigo-500 to-blue-600', shadow: 'shadow-blue-500/20', bg: 'bg-blue-50/50' },
                { label: 'Engagés', value: budgetStats.total_engage, color: 'from-emerald-400 to-teal-500', shadow: 'shadow-teal-500/20', bg: 'bg-teal-50/50' },
                { label: 'Liquidés', value: budgetStats.total_liquide, color: 'from-amber-400 to-orange-500', shadow: 'shadow-orange-500/20', bg: 'bg-orange-50/50' },
                { label: 'Ordonnancés', value: budgetStats.total_ordonnance, color: 'from-rose-400 to-red-500', shadow: 'shadow-red-500/20', bg: 'bg-red-50/50' },
                { label: 'Disponibles', value: budgetStats.total_disponible, color: 'from-cyan-400 to-blue-500', shadow: 'shadow-cyan-500/20', bg: 'bg-cyan-50/50' },
              ].map((item, index) => {
                const percentage = budgetStats.total_notifie > 0
                  ? Math.round((item.value / budgetStats.total_notifie) * 100)
                  : 0;

                return (
                  <div
                    key={index}
                    className={`relative overflow-hidden rounded-3xl backdrop-blur-md border border-white/40 p-6 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl ${item.shadow} ${item.bg}`}
                  >
                    <div className={`absolute top-0 right-0 w-24 h-24 bg-gradient-to-br ${item.color} rounded-full opacity-20 blur-2xl -mr-8 -mt-8 pointer-events-none`}></div>

                    <p className="text-xs uppercase tracking-wider font-bold text-slate-500 mb-2">{item.label}</p>
                    <p
                      className="text-lg xl:text-xl 2xl:text-2xl font-black text-slate-800 tracking-tight mb-4 truncate"
                      title={formatMoney(item.value)}
                    >
                      {formatMoney(item.value)}
                    </p>

                    {index > 0 && (
                      <div className="mt-auto">
                        <div className="flex justify-between text-[10px] font-bold text-slate-400 mb-1">
                          <span>Progression</span>
                          <span>{percentage}%</span>
                        </div>
                        <div className="h-1.5 w-full bg-slate-200/50 rounded-full overflow-hidden">
                          <div
                            className={`h-full bg-gradient-to-r ${item.color} rounded-full transition-all duration-1000 ease-out`}
                            style={{ width: `${percentage}%` }}
                          />
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>

            {/* Charts Section */}
            <div className="grid gap-6 lg:grid-cols-2 mt-8 mb-8">
              {/* Pie Chart: Répartition du Budget */}
              <div className="bg-white/70 backdrop-blur-xl rounded-3xl border border-white/50 shadow-sm p-6 hover:shadow-lg transition-shadow duration-300">
                <h3 className="font-serif text-xl font-bold text-[#1e3a8a] mb-6 flex items-center gap-2">
                  <PieChartIcon className="text-blue-500" size={20} /> Répartition Globale
                </h3>
                <div className="w-full min-h-[300px]">
                  <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                      <Pie
                        data={[
                          { name: 'Disponibles', value: budgetStats.total_disponible },
                          { name: 'Engagés', value: budgetStats.total_engage },
                          { name: 'Liquidés', value: budgetStats.total_liquide },
                        ]}
                        cx="50%"
                        cy="50%"
                        innerRadius={80}
                        outerRadius={110}
                        paddingAngle={5}
                        dataKey="value"
                        stroke="none"
                      >
                        <Cell fill="#3b82f6" /> {/* Blue for Disponibles */}
                        <Cell fill="#14b8a6" /> {/* Teal for Engagés */}
                        <Cell fill="#f59e0b" /> {/* Amber for Liquidés */}
                      </Pie>
                      <Tooltip
                        formatter={(value) => formatMoney(value)}
                        contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)' }}
                      />
                      <Legend verticalAlign="bottom" height={36} iconType="circle" />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
              </div>

              {/* Bar Chart: Consommation par Ligne */}
              <div className="bg-white/70 backdrop-blur-xl rounded-3xl border border-white/50 shadow-sm p-6 hover:shadow-lg transition-shadow duration-300">
                <h3 className="font-serif text-xl font-bold text-[#1e3a8a] mb-6 flex items-center gap-2">
                  <BarChart3 className="text-blue-500" size={20} /> Consommation (Top 5 Lignes)
                </h3>
                <div className="w-full min-h-[300px]">
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart
                      data={lignesBudgetaires.slice(0, 5).map(l => ({
                        name: `${l.article}/${l.paragraphe}/${l.ligne_budgetaire}`,
                        Notifié: Number(l.total_credits || 0),
                        Engagé: Number(l.credits_engages || 0),
                      }))}
                      margin={{ top: 10, right: 10, left: -20, bottom: 5 }}
                    >
                      <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} />
                      <YAxis tickFormatter={(val) => val >= 1000 ? `${(val / 1000).toFixed(0)}k` : val} tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} />
                      <Tooltip
                        formatter={(value) => formatMoney(value)}
                        cursor={{ fill: 'rgba(0,0,0,0.05)' }}
                        contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                      />
                      <Legend iconType="circle" />
                      <Bar dataKey="Notifié" fill="#6366f1" radius={[4, 4, 0, 0]} maxBarSize={40} />
                      <Bar dataKey="Engagé" fill="#14b8a6" radius={[4, 4, 0, 0]} maxBarSize={40} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </div>
            </div>


            {/* Table for individual budget lines tracking */}
            <div className="bg-white/70 backdrop-blur-xl rounded-3xl border border-white/50 shadow-sm overflow-hidden mt-8">
              <div className="p-6 border-b border-slate-200/60 bg-white/40">
                <h3 className="font-serif text-xl font-bold text-[#1e3a8a] flex items-center gap-2">
                  <FileText className="text-blue-500" size={20} /> Suivi détaillé par ligne budgétaire
                </h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="bg-slate-50/80 text-slate-500 font-medium border-b border-slate-200/60">
                    <tr>
                      <th className="px-6 py-4">Ligne (Art / Par / Lig)</th>
                      <th className="px-6 py-4">Libellé</th>
                      <th className="px-6 py-4 text-right">Crédits Notifiés</th>
                      <th className="px-6 py-4 text-right">Engagés</th>
                      <th className="px-6 py-4 text-right">Disponibles</th>
                      <th className="px-6 py-4 text-center">Progression</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100/60">
                    {lignesBudgetaires.length > 0 ? (
                      lignesBudgetaires.map((ligne) => {
                        const notifie = Number(ligne.total_credits || 0);
                        const engage = Number(ligne.credits_engages || 0);
                        const dispo = Number(ligne.credits_disponibles || 0);
                        const percent = notifie > 0 ? Math.round((engage / notifie) * 100) : 0;

                        return (
                          <tr
                            key={ligne.id}
                            className="border-b border-slate-100 transition-colors hover:bg-white/60 cursor-pointer"
                            onClick={() => setSelectedLigneDetails(ligne)}
                          >
                            <td className="px-6 py-4 font-mono text-xs font-semibold text-slate-700">
                              {ligne.article} / {ligne.paragraphe} / {ligne.ligne_budgetaire}
                            </td>
                            <td className="px-6 py-4 text-slate-700 font-medium">
                              {ligne.libelle}
                            </td>
                            <td className="px-6 py-4 text-right text-indigo-700 font-bold font-mono">
                              {formatMoney(notifie)}
                            </td>
                            <td className="px-6 py-4 text-right text-teal-700 font-bold font-mono">
                              {formatMoney(engage)}
                            </td>
                            <td className="px-6 py-4 text-right text-blue-700 font-bold font-mono">
                              {formatMoney(dispo)}
                            </td>
                            <td className="px-6 py-4">
                              <div className="flex items-center justify-center gap-3">
                                <div className="h-1.5 w-16 bg-slate-200 rounded-full overflow-hidden">
                                  <div
                                    className="h-full bg-gradient-to-r from-teal-400 to-teal-500 rounded-full"
                                    style={{ width: `${percent}%` }}
                                  />
                                </div>
                                <span className="text-[10px] font-bold text-slate-500 w-6">{percent}%</span>
                              </div>
                            </td>
                          </tr>
                        );
                      })
                    ) : (
                      <tr>
                        <td colSpan="6" className="px-6 py-8 text-center text-slate-500 italic">
                          Aucune ligne budgétaire trouvée.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <section className="pt-2 border-t border-slate-200">
            <h2 className="font-serif text-3xl font-bold text-[#1e3a8a]">Type de consultation</h2>
            <p className="mt-2 text-lg text-slate-600">Choisissez la catégorie de consultation à gérer :</p>

            <div className="mt-8 grid gap-6 md:grid-cols-3">
              {/* 1. Appel d'offres */}
              <div className="rounded-3xl border-2 border-[#1e40af] bg-white p-7 shadow-sm transition hover:shadow-md flex flex-col justify-between">
                <div>
                  <div className="flex items-center justify-between">
                    <h3 className="text-xl font-bold text-[#1e3a8a]">Appel d’offres</h3>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Opérationnel</span>
                  </div>
                  <p className="mt-3 text-sm text-slate-600">Procédure ouverte, formalisée et documentée (Préparation, Commission, Attribution).</p>
                </div>
                {!showAooModes ? (
                  <button type="button" onClick={() => setShowAooModes(true)} className="mt-6 w-full py-3 bg-[#1e40af] hover:bg-[#1e3a8a] text-white font-bold rounded-xl transition-all text-sm text-center">
                    Choisir la procédure AOO
                  </button>
                ) : (
                  <div className="mt-4 space-y-2">
                    {aooModes.map((mode) => (
                      <Link key={mode.mode} to={`/aoos/nouveau?mode=${mode.mode}`} className="block p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 transition-all text-xs font-bold text-[#1e3a8a]">
                        {mode.title}
                      </Link>
                    ))}
                  </div>
                )}
              </div>

              {/* 2. Bon de Commande */}
              <Link to="/bons-commande" className="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md flex flex-col justify-between group">
                <div>
                  <div className="flex items-center justify-between">
                    <h3 className="text-xl font-bold text-[#1e3a8a]">Bon de Commande</h3>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Prochainement</span>
                  </div>
                  <p className="mt-3 text-sm text-slate-600">Achats sur bons de commande simplifiés selon seuils réglementaires.</p>
                </div>
                <span className="mt-6 inline-flex items-center gap-2 text-sm font-bold text-blue-700 group-hover:underline">
                  Voir le module <ArrowRight size={16} />
                </span>
              </Link>

              {/* 3. Convention */}
              <Link to="/conventions" className="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md flex flex-col justify-between group">
                <div>
                  <div className="flex items-center justify-between">
                    <h3 className="text-xl font-bold text-[#1e3a8a]">Convention</h3>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">Prochainement</span>
                  </div>
                  <p className="mt-3 text-sm text-slate-600">Conventions partenariales, institutionnelles et accords-cadres.</p>
                </div>
                <span className="mt-6 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 group-hover:underline">
                  Voir le module <ArrowRight size={16} />
                </span>
              </Link>
            </div>
          </section>

          <section className="mt-8 border-b border-[#ded8c8]">
            <div className="flex flex-wrap gap-8">
              {['Préparation', 'Commission', 'Analyse'].map((step, index) => (
                <div key={step} className={`flex items-center gap-3 border-b-2 px-2 pb-4 text-lg ${index === 1 ? 'border-[#d8ad20] font-semibold text-[#102c24]' : 'border-transparent text-[#65766d]'}`}>
                  <span className={`grid h-7 w-7 place-items-center rounded-full text-sm ${index === 1 ? 'bg-[#173d30] text-white' : 'bg-[#e4e0d2] text-[#56655d]'}`}>{index + 1}</span>
                  {step}
                </div>
              ))}
            </div>
          </section>

          <section className="mt-8 rounded-2xl border border-[#ded8c8] bg-white p-6 shadow-sm sm:p-10">
            <div className="flex items-center justify-between gap-4">
              <h2 className="font-serif text-3xl font-bold text-[#102c24]">Membres de la commission</h2>
              <Link to="/commission-membres" className="text-sm font-semibold text-[#315f4d] hover:underline">Gérer les membres</Link>
            </div>

            {loading ? (
              <div className="flex h-40 items-center justify-center gap-3 text-[#596b62]"><Loader2 className="animate-spin" /> Chargement…</div>
            ) : members.length > 0 ? (
              <div className="mt-7 overflow-x-auto">
                <table className="w-full min-w-[650px] text-left">
                  <thead className="border-b border-[#ded8c8] text-sm uppercase tracking-wide text-[#596b62]">
                    <tr><th className="pb-4 font-medium">Nom</th><th className="pb-4 font-medium">Fonction</th><th className="pb-4 font-medium">Qualité</th><th className="pb-4 font-medium">Administration</th></tr>
                  </thead>
                  <tbody>
                    {members.map((member) => (
                      <tr key={member.id} className="border-b border-[#eee9dc] last:border-0">
                        <td className="py-5 text-lg font-medium text-[#102c24]">{member.nom_prenom}</td>
                        <td className="py-5 text-lg text-[#314a40]">{member.fonction || '—'}</td>
                        <td className="py-5"><span className="rounded-full bg-[#f4e7b8] px-3 py-1.5 text-sm font-semibold text-[#795b08]">{member.qualite || 'Membre'}</span></td>
                        <td className="py-5 text-lg text-[#314a40]">{member.administration || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="mt-7 rounded-xl bg-[#f7f5ef] p-6 text-[#596b62]">Aucun membre de commission n'est encore enregistré.</div>
            )}
          </section>
          <div className="mt-10 flex justify-end">
            <Link to="/engagements" className="inline-flex items-center gap-2 text-sm font-semibold text-[#315f4d] hover:underline"><Check size={16} /> Suivre les dossiers d'engagement</Link>
          </div>

          {/* Modal Détails Ligne Budgétaire */}
          {selectedLigneDetails && (() => {
            const relatedAoos = [...(selectedLigneDetails.aoos || [])];
            (selectedLigneDetails.marches || []).forEach(m => {
              if (m.aoo && !relatedAoos.find(a => a.id === m.aoo.id)) {
                relatedAoos.push(m.aoo);
              }
            });

            return createPortal(
              <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
                <div className="bg-white rounded-2xl shadow-xl w-full max-w-4xl flex flex-col overflow-hidden max-h-[90vh]">
                  <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h3 className="text-xl font-bold text-[#1e3a8a] flex items-center gap-2">
                      <FileText className="w-5 h-5" />
                      Détails de la ligne : {selectedLigneDetails.article} / {selectedLigneDetails.paragraphe} / {selectedLigneDetails.ligne_budgetaire}
                    </h3>
                    <button
                      onClick={() => setSelectedLigneDetails(null)}
                      className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-full transition-colors"
                    >
                      X
                    </button>
                  </div>

                  <div className="p-6 overflow-y-auto">
                    <div className="mb-6 grid grid-cols-3 gap-4">
                      <div className="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <p className="text-sm font-semibold text-blue-600 mb-1">Notifiés</p>
                        <p className="text-xl font-bold text-[#1e3a8a]">{formatMoney(selectedLigneDetails.total_credits)}</p>
                      </div>
                      <div className="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                        <p className="text-sm font-semibold text-emerald-600 mb-1">Engagés</p>
                        <p className="text-xl font-bold text-emerald-700">{formatMoney(selectedLigneDetails.credits_engages)}</p>
                      </div>
                      <div className="p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                        <p className="text-sm font-semibold text-indigo-600 mb-1">Disponibles</p>
                        <p className="text-xl font-bold text-indigo-700">{formatMoney(selectedLigneDetails.credits_disponibles)}</p>
                      </div>
                    </div>

                    {/* Origine du crédit */}
                    <div className="mb-6 text-sm text-slate-600 bg-slate-50 px-4 py-3 rounded-xl border border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                      <span className="font-semibold text-slate-800">Origine du crédit</span>
                      <span className="font-medium">
                        Reporté : <span className="font-bold text-slate-700">{formatMoney(selectedLigneDetails.reports || 0)}</span> <span className="mx-2 text-slate-300">·</span>
                        Crédit neuf : <span className="font-bold text-slate-700">{formatMoney(selectedLigneDetails.credits_neufs || 0)}</span>
                      </span>
                    </div>

                    <h4 className="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Marchés rattachés</h4>
                    {selectedLigneDetails.marches && selectedLigneDetails.marches.length > 0 ? (
                      <div className="overflow-x-auto mb-8 border rounded-xl">
                        <table className="w-full text-left text-sm">
                          <thead className="bg-slate-50 text-slate-600 border-b">
                            <tr>
                              <th className="px-4 py-3 font-semibold">N° Marché</th>
                              <th className="px-4 py-3 font-semibold">Objet</th>
                              <th className="px-4 py-3 font-semibold">Montant</th>
                              <th className="px-4 py-3 font-semibold">Statut Engagement</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y">
                            {selectedLigneDetails.marches.map(marche => (
                              <tr key={marche.id} className="hover:bg-slate-50">
                                <td className="px-4 py-3 font-medium text-slate-800">{marche.num_marche}</td>
                                <td className="px-4 py-3 text-slate-600">{(marche.objet_marche || '').replace(/equipements/ig, 'équipements')}</td>
                                <td className="px-4 py-3 font-bold text-[#1e3a8a]">{formatMoney(marche.montant)}</td>
                                <td className="px-4 py-3">
                                  <span className={`px-2 py-1 text-xs font-semibold rounded-full ${marche.statut_acte_engagement === 'Annulé' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700'}`}>
                                    {marche.statut_acte_engagement === 'Engage' ? 'Engagé' : (marche.statut_acte_engagement || 'N/A')}
                                  </span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <p className="text-slate-500 italic mb-8 bg-slate-50 p-4 rounded-xl border border-dashed">Aucun marché rattaché à cette ligne.</p>
                    )}

                    <h4 className="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Consultations rattachées (AOO, BC)</h4>
                    {(selectedLigneDetails.consultations?.length > 0 || relatedAoos.length > 0) ? (
                      <div className="overflow-x-auto border rounded-xl">
                        <table className="w-full text-left text-sm">
                          <thead className="bg-slate-50 text-slate-600 border-b">
                            <tr>
                              <th className="px-4 py-3 font-semibold">N° Consultation</th>
                              <th className="px-4 py-3 font-semibold">Objet</th>
                              <th className="px-4 py-3 font-semibold">Catégorie</th>
                              <th className="px-4 py-3 font-semibold">Engagement Estimé / Global</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y">
                            {selectedLigneDetails.consultations?.map(cons => (
                              <tr key={cons.id} className="hover:bg-slate-50">
                                <td className="px-4 py-3 font-medium text-slate-800">{cons.numero_consultation}</td>
                                <td className="px-4 py-3 text-slate-600">{(cons.objet_consultation || '').replace(/equipements/ig, 'équipements')}</td>
                                <td className="px-4 py-3">
                                  <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">{cons.categorie}</span>
                                </td>
                                <td className="px-4 py-3 font-bold text-[#1e3a8a]">
                                  {cons.engagement ? formatMoney(cons.engagement.montant_global) : (cons.estimation_budgetaire ? formatMoney(cons.estimation_budgetaire) + ' · Estimé' : 'N/A')}
                                </td>
                              </tr>
                            ))}
                            {relatedAoos.map(aoo => (
                              <tr key={`aoo-${aoo.id}`} className="hover:bg-slate-50">
                                <td className="px-4 py-3 font-medium text-slate-800">{aoo.num_aoo}</td>
                                <td className="px-4 py-3 text-slate-600">{(aoo.objet || '').replace(/equipements/ig, 'équipements')}</td>
                                <td className="px-4 py-3">
                                  <span className="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-semibold">AOO</span>
                                </td>
                                <td className="px-4 py-3 font-bold text-[#1e3a8a]">
                                  {aoo.budget ? formatMoney(aoo.budget) + ' · Estimé' : 'N/A'}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <p className="text-slate-500 italic bg-slate-50 p-4 rounded-xl border border-dashed">Aucune consultation rattachée à cette ligne.</p>
                    )}
                  </div>
                </div>
              </div>,
              document.body
            )
          })()}
        </div>
      </main>
    </div>
  );
}
