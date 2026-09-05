import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  ArrowLeft,
  Loader2,
  FileText,
  BarChart3,
  Receipt,
  Calendar,
  History,
  TrendingUp,
  Layers,
} from 'lucide-react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from 'recharts';

export default function DetailsNotification() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [notification, setNotification] = useState(null);
  const [recapExercice, setRecapExercice] = useState(null);
  const [activeTab, setActiveTab] = useState('notification'); // 'notification' | 'cumul_exercice'
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchNotificationAndRecap = async () => {
      try {
        const response = await api.get(`/notifications/${id}`);
        setNotification(response.data);

        if (response.data?.exercice) {
          const recapRes = await api.get(
            `/notifications/recapitulatif-exercice?exercice=${response.data.exercice}`
          );
          setRecapExercice(recapRes.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    };
    fetchNotificationAndRecap();
  }, [id]);

  const formatMoney = (value) =>
    new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(Number(value) || 0) + ' DH';

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('fr-FR');
    } catch {
      return '-';
    }
  };

  if (loading)
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <Loader2 className="animate-spin text-[#1e3a8a]" size={48} />
      </div>
    );
  if (!notification)
    return (
      <div className="p-10 text-center text-red-500 font-bold">
        Notification introuvable
      </div>
    );

  const totalGlobal =
    notification.lignes?.reduce((acc, l) => acc + Number(l.total_credits), 0) || 0;
  const totalEngage =
    notification.lignes?.reduce((acc, l) => acc + Number(l.credits_engages), 0) || 0;
  const totalDispo =
    notification.lignes?.reduce((acc, l) => acc + Number(l.credits_disponibles), 0) || 0;

  const totalLiquide = 0;
  const totalOrdonnance = 0;

  const chartData = [
    { name: 'Total crédits', value: totalGlobal, color: '#1e40af' },
    { name: 'Engagé', value: totalEngage, color: '#059669' },
    { name: 'Liquidé', value: totalLiquide, color: '#d97706' },
    { name: 'Ordonnancé', value: totalOrdonnance, color: '#dc2626' },
    { name: 'Disponible', value: totalDispo, color: '#2563eb' },
  ];

  const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white p-3 border border-slate-200 shadow-lg rounded-xl">
          <p className="font-bold text-slate-800 mb-1">{payload[0].payload.name}</p>
          <p className="font-mono text-[#1e3a8a] font-bold">
            {formatMoney(payload[0].value)}
          </p>
        </div>
      );
    }
    return null;
  };

  const allMouvements =
    notification.mouvements && notification.mouvements.length > 0
      ? notification.mouvements
      : notification.lignes?.flatMap((l) => l.mouvements || []) || [];

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-4 sm:px-8 lg:px-12">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <header className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate('/notifications')}
              className="p-2.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition shadow-xs text-slate-700"
            >
              <ArrowLeft size={20} />
            </button>
            <div>
              <div className="flex items-center gap-3">
                <h1 className="text-3xl font-black text-[#1e3a8a]">
                  Notification Budgétaire {notification.numero}
                </h1>
                <span
                  className={`px-3 py-1 rounded-full text-xs font-bold ${notification.type_budget === 'REPORT'
                      ? 'bg-purple-100 text-purple-800 border border-purple-200'
                      : notification.type_budget === 'REPORT + NOTIFIER'
                        ? 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                        : 'bg-blue-100 text-blue-800 border border-blue-200'
                    }`}
                >
                  {notification.type_budget || 'NOTIFIER'}
                </span>
                {notification.domaine && (
                  <span className="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {notification.domaine}
                  </span>
                )}
              </div>
              <p className="text-slate-500 text-sm mt-1 flex items-center gap-3">
                <span className="flex items-center gap-1">
                  <Calendar size={15} /> Exercice {notification.exercice}
                </span>
                <span>•</span>
                <span>
                  Date : {formatDate(notification.date_notification)}
                </span>
                {notification.objet && (
                  <>
                    <span>•</span>
                    <span className="italic text-slate-600">{notification.objet}</span>
                  </>
                )}
              </p>
            </div>
          </div>

          {/* Onglets de vue */}
          <div className="flex bg-white p-1 rounded-xl border border-slate-200 shadow-2xs text-xs font-bold">
            <button
              onClick={() => setActiveTab('notification')}
              className={`px-4 py-2 rounded-lg transition ${activeTab === 'notification'
                  ? 'bg-[#1e40af] text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
                }`}
            >
              Détails de cette Notification
            </button>
            <button
              onClick={() => setActiveTab('cumul_exercice')}
              className={`px-4 py-2 rounded-lg transition ${activeTab === 'cumul_exercice'
                  ? 'bg-[#1e40af] text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
                }`}
            >
              Cumul Exercice {notification.exercice}
            </button>
          </div>
        </header>

        {activeTab === 'notification' ? (
          <>
            {/* Cartes KPI */}
            <section className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <div className="bg-white border border-slate-200 shadow-2xs rounded-2xl p-6 border-l-4 border-l-blue-700">
                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                  Total Crédits Notifiés (1+3+5)
                </h3>
                <p className="text-3xl font-black text-[#1e3a8a] mt-2 font-mono">
                  {formatMoney(totalGlobal)}
                </p>
              </div>
              <div className="bg-white border border-slate-200 shadow-2xs rounded-2xl p-6 border-l-4 border-l-emerald-600">
                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                  Crédits Engagés (Consommés)
                </h3>
                <p className="text-3xl font-black text-emerald-700 mt-2 font-mono">
                  {formatMoney(totalEngage)}
                </p>
              </div>
              <div className="bg-white border border-slate-200 shadow-2xs rounded-2xl p-6 border-l-4 border-l-indigo-600">
                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                  Crédits Disponibles Nets
                </h3>
                <p className="text-3xl font-black text-indigo-700 mt-2 font-mono">
                  {formatMoney(totalDispo)}
                </p>
              </div>
            </section>

            {/* TABLEAU RÉCAPITULATIF DE LA NOTIFICATION (FORMAT EXCEL) */}
            <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
              <div className="flex justify-between items-center mb-5 border-b pb-4">
                <div>
                  <h2 className="text-xl font-bold text-[#1e3a8a] flex items-center gap-2">
                    <Receipt className="text-emerald-600" size={22} /> Tableau Récapitulatif Budgétaire
                  </h2>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Ventilation comptable : Total = (1) Reports + (3) Crédits Neufs + (5) Crédits d&apos;Engagement
                  </p>
                </div>
              </div>

              <div className="overflow-x-auto border border-slate-200 rounded-xl">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-[#1e3a8a] text-white text-xs font-bold">
                      <th className="p-3.5 border-r border-blue-800">Imputation Budgétaire</th>
                      <th className="p-3.5 border-r border-blue-800">Libellé</th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Reports (1)
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Dim. report (2)
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Crédits neufs (3)
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Dim. crédit neuf (4)
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Crédits d&apos;eng. (5)
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Dim. eng. (6)
                      </th>
                      <th className="p-3.5 text-right bg-blue-950 font-black">
                        Total crédits (1+3+5)
                      </th>
                      <th className="p-3.5 text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 text-xs">
                    {notification.lignes?.map((ligne) => (
                      <tr key={ligne.id} className="hover:bg-slate-50 transition">
                        <td className="p-3.5 font-mono font-bold text-[#1e3a8a] border-r border-slate-200 whitespace-nowrap">
                          {ligne.article}/{ligne.paragraphe}/{ligne.ligne_budgetaire}
                        </td>
                        <td className="p-3.5 font-medium border-r border-slate-200 text-slate-700">
                          {ligne.libelle}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-slate-800">
                          {formatMoney(ligne.reports)}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                          {formatMoney(ligne.diminution_report)}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-slate-800 font-semibold">
                          {formatMoney(ligne.credits_neufs)}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                          {formatMoney(ligne.diminution_credit_neuf)}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-slate-800 font-semibold">
                          {formatMoney(ligne.credits_engagements)}
                        </td>
                        <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                          {formatMoney(ligne.diminution_credit_engagement)}
                        </td>
                        <td className="p-3.5 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/50">
                          {formatMoney(ligne.total_credits)}
                        </td>
                        <td className="p-3.5 text-center whitespace-nowrap">
                          <button
                            onClick={() =>
                              navigate('/consultations/nouvelle', {
                                state: { ligneBudgetaireId: ligne.id },
                              })
                            }
                            className="px-3 py-1.5 bg-[#1e3a8a] text-white text-xs font-bold rounded-lg hover:bg-blue-800 transition shadow-xs"
                          >
                            Créer consultation
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>

            {/* JOURNAL DES MOUVEMENTS */}
            {allMouvements.length > 0 && (
              <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
                <h2 className="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                  <History className="text-[#1e40af]" size={22} /> Historique des Mouvements de Crédits
                </h2>
                <div className="overflow-x-auto border border-slate-200 rounded-xl">
                  <table className="w-full text-left border-collapse text-xs">
                    <thead>
                      <tr className="bg-slate-100 text-slate-700 font-bold border-b">
                        <th className="p-3">Nature</th>
                        <th className="p-3">Type de Crédit / Mouvement</th>
                        <th className="p-3">N° Notification</th>
                        <th className="p-3">Date</th>
                        <th className="p-3">Motif de diminution</th>
                        <th className="p-3 text-right">Montant</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {allMouvements.map((mvt, i) => (
                        <tr key={mvt.id || i} className="hover:bg-slate-50">
                          <td className="p-3">
                            <span
                              className={`px-2.5 py-1 rounded-md text-[11px] font-bold ${mvt.nature === 'DIMINUTION' ||
                                  mvt.type_credit.startsWith('DIMINUTION')
                                  ? 'bg-amber-100 text-amber-800'
                                  : 'bg-emerald-100 text-emerald-800'
                                }`}
                            >
                              {mvt.nature === 'DIMINUTION' ||
                                mvt.type_credit.startsWith('DIMINUTION')
                                ? 'DIMINUTION'
                                : 'ALIMENTATION'}
                            </span>
                          </td>
                          <td className="p-3 font-semibold text-slate-800">
                            {mvt.type_credit === 'NEUF_CC' && 'Crédit Consolidé (CC)'}
                            {mvt.type_credit === 'NEUF_CPN' && 'Crédit de Paiement Neuf (CPN)'}
                            {mvt.type_credit === 'ENGAGEMENT' && "Crédit d'engagement"}
                            {mvt.type_credit === 'REPORT' && 'Report'}
                            {mvt.type_credit === 'DIMINUTION_REPORT' && 'Diminution du report'}
                            {mvt.type_credit === 'DIMINUTION_ENGAGEMENT' && "Diminution de l'engagement"}
                            {mvt.type_credit === 'DIMINUTION_PAIEMENT' && 'Diminution du paiement'}
                            {!['NEUF_CC', 'NEUF_CPN', 'ENGAGEMENT', 'REPORT', 'DIMINUTION_REPORT', 'DIMINUTION_ENGAGEMENT', 'DIMINUTION_PAIEMENT'].includes(mvt.type_credit) && mvt.type_credit}
                          </td>
                          <td className="p-3 font-mono text-slate-600">
                            {mvt.numero_notification || '-'}
                          </td>
                          <td className="p-3 text-slate-600">
                            {formatDate(mvt.date_mouvement)}
                          </td>
                          <td className="p-3 text-slate-700 italic">
                            {mvt.motif || '-'}
                          </td>
                          <td
                            className={`p-3 text-right font-mono font-bold text-sm ${mvt.nature === 'DIMINUTION' ||
                                mvt.type_credit.startsWith('DIMINUTION')
                                ? 'text-amber-700'
                                : 'text-emerald-700'
                              }`}
                          >
                            {mvt.nature === 'DIMINUTION' ||
                              mvt.type_credit.startsWith('DIMINUTION')
                              ? '-'
                              : '+'}
                            {formatMoney(mvt.montant)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {/* Situation Budgétaire Chart */}
            <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
              <h2 className="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                <BarChart3 className="text-blue-500" /> Situation Budgétaire
              </h2>
              <div className="w-full min-h-[300px]">
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart
                    layout="vertical"
                    data={chartData}
                    margin={{ top: 10, right: 30, left: 40, bottom: 10 }}
                  >
                    <XAxis type="number" hide />
                    <YAxis
                      dataKey="name"
                      type="category"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: '#475569', fontWeight: 600 }}
                    />
                    <Tooltip cursor={{ fill: '#f8fafc' }} content={<CustomTooltip />} />
                    <Bar
                      dataKey="value"
                      radius={[0, 4, 4, 0]}
                      barSize={32}
                      label={{
                        position: 'right',
                        fill: '#475569',
                        fontSize: 12,
                        formatter: (val) => formatMoney(val),
                      }}
                    >
                      {chartData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </section>
          </>
        ) : (
          /* SECTION CUMUL EXERCICE COMPLET (INVESTISSEMENT & FONCTIONNEMENT) */
          (() => {
            const calculateSectionTotals = (rows = []) => {
              return rows.reduce(
                (acc, row) => ({
                  reports: acc.reports + Number(row.reports || 0),
                  diminution_report: acc.diminution_report + Number(row.diminution_report || 0),
                  credits_neufs: acc.credits_neufs + Number(row.credits_neufs || 0),
                  diminution_credit_neuf: acc.diminution_credit_neuf + Number(row.diminution_credit_neuf || 0),
                  credits_engagements: acc.credits_engagements + Number(row.credits_engagements || 0),
                  diminution_credit_engagement:
                    acc.diminution_credit_engagement + Number(row.diminution_credit_engagement || 0),
                  total_credits: acc.total_credits + Number(row.total_credits || 0),
                }),
                {
                  reports: 0,
                  diminution_report: 0,
                  credits_neufs: 0,
                  diminution_credit_neuf: 0,
                  credits_engagements: 0,
                  diminution_credit_engagement: 0,
                  total_credits: 0,
                }
              );
            };

            const allLignes = recapExercice?.lignes || [];
            const lignesInvestissement = allLignes.filter(
              (row) => (row.domaine || '').toUpperCase() === 'INVESTISSEMENT' || !row.domaine
            );
            const lignesFonctionnement = allLignes.filter(
              (row) => (row.domaine || '').toUpperCase() === 'FONCTIONNEMENT'
            );

            const totalsInvestissement = calculateSectionTotals(lignesInvestissement);
            const totalsFonctionnement = calculateSectionTotals(lignesFonctionnement);

            const renderRecapSection = (title, domaineBadge, themeColor, rows, totals, totalsLabel) => (
              <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8 space-y-4">
                <div className="flex justify-between items-center border-b pb-4">
                  <div>
                    <div className="flex items-center gap-2.5">
                      <span
                        className={`px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider ${themeColor === 'blue'
                            ? 'bg-blue-100 text-blue-800 border border-blue-200'
                            : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                          }`}
                      >
                        {domaineBadge}
                      </span>
                      <h2 className="text-xl font-black text-slate-800">{title}</h2>
                    </div>
                    <p className="text-xs text-slate-500 mt-1">
                      Consolidation des notifications {domaineBadge.toLowerCase()} de l&apos;exercice {notification.exercice} ({rows.length} ligne{rows.length > 1 ? 's' : ''}).
                    </p>
                  </div>
                </div>

                {rows.length === 0 ? (
                  <p className="py-10 text-center text-slate-400 text-xs italic bg-slate-50 rounded-xl border border-slate-100">
                    Aucune ligne budgétaire pour le budget {domaineBadge.toLowerCase()} sur l&apos;exercice {notification.exercice}.
                  </p>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-[#1e3a8a] text-white font-bold">
                          <th className="p-3.5 border-r border-blue-800">Imputation Budgétaire</th>
                          <th className="p-3.5 border-r border-blue-800">Libellé</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(1) Reports</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(2) Dim. report</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(3) Crédits neufs</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(4) Dim. crédit neuf</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(5) Crédits d&apos;eng.</th>
                          <th className="p-3.5 text-right border-r border-blue-800">(6) Dim. eng.</th>
                          <th className="p-3.5 text-right bg-blue-950 font-black">
                            Total Crédits (1+3+5)
                          </th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-200">
                        {/* Ligne 1 : TOTAL RÉCAPITULATIF */}
                        <tr className="bg-slate-100 font-bold text-xs text-slate-900 border-b-2 border-slate-300">
                          <td colSpan="2" className="p-3.5 border-r border-slate-300 uppercase font-black">
                            {totalsLabel} {notification.exercice}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {Number(totals.reports) > 0 ? formatMoney(totals.reports) : ''}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatMoney(totals.diminution_report)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {formatMoney(totals.credits_neufs)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatMoney(totals.diminution_credit_neuf)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {formatMoney(totals.credits_engagements)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatMoney(totals.diminution_credit_engagement)}
                          </td>
                          <td className="p-3.5 text-right font-mono font-black text-white bg-[#1e40af]">
                            {formatMoney(totals.total_credits)}
                          </td>
                        </tr>

                        {rows.map((row) => (
                          <tr key={row.imputation} className="hover:bg-slate-50 transition font-medium">
                            <td className="p-3.5 font-mono font-bold text-[#1e3a8a] border-r border-slate-200 whitespace-nowrap">
                              {row.imputation}
                            </td>
                            <td className="p-3.5 border-r border-slate-200 text-slate-700">
                              {row.libelle}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200">
                              {Number(row.reports) > 0 ? formatMoney(row.reports) : ''}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_report)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatMoney(row.credits_neufs)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_credit_neuf)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatMoney(row.credits_engagements)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_credit_engagement)}
                            </td>
                            <td className="p-3.5 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/50">
                              {formatMoney(row.total_credits)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </section>
            );

            return (
              <>
                {renderRecapSection(
                  `Récapitulatif Cumulé - Investissement ${notification.exercice}`,
                  'INVESTISSEMENT',
                  'blue',
                  lignesInvestissement,
                  totalsInvestissement,
                  'Total Investissement'
                )}

                {renderRecapSection(
                  `Récapitulatif Cumulé - Fonctionnement ${notification.exercice}`,
                  'FONCTIONNEMENT',
                  'emerald',
                  lignesFonctionnement,
                  totalsFonctionnement,
                  'Total Fonctionnement'
                )}
              </>
            );
          })()
        )}

        {/* Consultations / Marchés liés */}
        <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <h2 className="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <FileText className="text-blue-500" /> Consultations & Marchés Liés
          </h2>
          {notification.lignes?.every(
            (l) => l.consultations?.length === 0 && l.marches?.length === 0
          ) ? (
            <p className="text-slate-500 text-center py-6 bg-slate-50 rounded-xl">
              Aucune consultation ou marché n&apos;est encore lié à cette notification.
            </p>
          ) : (
            <div className="space-y-6">
              {notification.lignes
                ?.filter((l) => l.consultations?.length > 0 || l.marches?.length > 0)
                .map((ligne) => (
                  <div
                    key={`cons-${ligne.id}`}
                    className="border border-slate-100 rounded-xl p-4 bg-slate-50/50"
                  >
                    <h4 className="font-bold text-[#1e3a8a] mb-3">
                      Ligne : {ligne.article}/{ligne.paragraphe}/{ligne.ligne_budgetaire} -{' '}
                      {ligne.libelle}
                    </h4>
                    <ul className="space-y-2">
                      {ligne.consultations?.map((c) => (
                        <li
                          key={c.id}
                          className="flex justify-between items-center bg-white p-3 rounded-lg border border-slate-200"
                        >
                          <span className="font-semibold text-slate-800">
                            {c.numero_consultation} -{' '}
                            <span className="text-slate-500 text-sm">
                              {c.objet_consultation}
                            </span>
                          </span>
                          <Link
                            to={`/consultations/${c.id}`}
                            className="text-sm font-bold text-[#1e40af] hover:underline"
                          >
                            Voir
                          </Link>
                        </li>
                      ))}
                      {ligne.marches?.map((m) => (
                        <li
                          key={m.id}
                          className="flex justify-between items-center bg-white p-3 rounded-lg border border-slate-200"
                        >
                          <span className="font-semibold text-slate-800">
                            {m.num_marche} (Marché) -{' '}
                            <span className="text-slate-500 text-sm">{m.objet_marche}</span>
                          </span>
                          <Link
                            to={`/marches/${m.id}`}
                            className="text-sm font-bold text-[#1e40af] hover:underline"
                          >
                            Voir
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
