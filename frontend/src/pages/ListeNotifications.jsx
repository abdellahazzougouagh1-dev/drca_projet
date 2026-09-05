import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  ArrowLeft,
  Loader2,
  Plus,
  Eye,
  Landmark,
  AlertCircle,
  Receipt,
  Calendar,
  Layers,
} from 'lucide-react';

const ListeNotifications = () => {
  const navigate = useNavigate();
  const [selectedExercice, setSelectedExercice] = useState(new Date().getFullYear());
  const [notifications, setNotifications] = useState([]);
  const [recapExercice, setRecapExercice] = useState(null);
  const [viewMode, setViewMode] = useState('par_notification'); // 'par_notification' | 'recap_cumule' | 'dossiers'
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchNotificationsAndRecap = useCallback(async () => {
    setLoading(true);
    try {
      const [notifsRes, recapRes] = await Promise.all([
        api.get(`/notifications?exercice=${selectedExercice}`),
        api.get(`/notifications/recapitulatif-exercice?exercice=${selectedExercice}`),
      ]);
      setNotifications(notifsRes.data || []);
      setRecapExercice(recapRes.data || null);
    } catch (err) {
      console.error(err);
      setError('Impossible de charger les données budgétaires.');
    } finally {
      setLoading(false);
    }
  }, [selectedExercice]);

  useEffect(() => {
    fetchNotificationsAndRecap();
  }, [fetchNotificationsAndRecap]);

  const formatCurrency = (value) => {
    return (
      new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(value) || 0) + ' DH'
    );
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('fr-FR');
    } catch {
      return '-';
    }
  };

  const exercicesList = [2027, 2026, 2025, 2024, 2023];

  return (
    <div className="p-8 bg-slate-50 min-h-screen">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <header className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button
                onClick={() => navigate('/dashboard')}
                className="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-500"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-[#1e3a8a] flex items-center gap-2">
                <Landmark /> Notifications Budgétaires
              </h1>
            </div>
            <p className="text-slate-500 ml-11 text-sm">
              Gestion et tableaux récapitulatifs par notification et consolidation de l&apos;exercice.
            </p>
          </div>

          <div className="flex items-center gap-3 flex-wrap">
            {/* Filtre Exercice */}
            <div className="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-2xs">
              <Calendar size={16} className="text-[#1e40af] mr-2" />
              <label className="text-xs font-bold text-slate-600 mr-2">Exercice :</label>
              <select
                value={selectedExercice}
                onChange={(e) => setSelectedExercice(Number(e.target.value))}
                className="text-xs font-bold font-mono text-[#1e3a8a] bg-transparent focus:outline-hidden"
              >
                {exercicesList.map((an) => (
                  <option key={an} value={an}>
                    {an}
                  </option>
                ))}
              </select>
            </div>

            {/* Bouton Nouvelle Notification */}
            <Link
              to="/notifications/nouvelle"
              className="px-5 py-2.5 bg-[#1e40af] text-white font-bold rounded-xl shadow-xs hover:bg-[#1e3a8a] transition flex items-center gap-2 text-sm"
            >
              <Plus size={18} /> Nouvelle Notification
            </Link>
          </div>
        </header>

        {error && (
          <div className="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-2 border border-red-200">
            <AlertCircle size={20} /> {error}
          </div>
        )}

        {/* Commutateur de Mode d'Affichage */}
        <div className="mb-6 flex justify-between items-center flex-wrap gap-4">
          <div className="flex bg-white p-1 rounded-xl border border-slate-200 shadow-2xs text-xs font-bold gap-1 flex-wrap">
            <button
              onClick={() => setViewMode('par_notification')}
              className={`px-4 py-2 rounded-lg transition flex items-center gap-1.5 ${viewMode === 'par_notification'
                  ? 'bg-[#1e40af] text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
                }`}
            >
              <Receipt size={15} /> Tableaux par Notification ({notifications.length})
            </button>
            <button
              onClick={() => setViewMode('recap_cumule')}
              className={`px-4 py-2 rounded-lg transition flex items-center gap-1.5 ${viewMode === 'recap_cumule'
                  ? 'bg-[#1e40af] text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
                }`}
            >
              <Layers size={15} /> Tableau Récapitulatif Cumulé {selectedExercice} ({recapExercice?.lignes?.length || 0} lignes)
            </button>
          </div>
        </div>

        {loading ? (
          <div className="py-20 flex items-center justify-center bg-white rounded-2xl border border-slate-200 shadow-2xs">
            <Loader2 className="animate-spin text-[#1e3a8a]" size={40} />
          </div>
        ) : viewMode === 'par_notification' ? (
          /* VUE 1 (PAR DÉFAUT) : CHAQUE NOTIFICATION A SON PROPRE TABLEAU */
          <div className="space-y-8">
            {notifications.length === 0 ? (
              <div className="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200 shadow-2xs">
                Aucune notification enregistrée pour l&apos;exercice {selectedExercice}.
              </div>
            ) : (
              notifications.map((notif, index) => {
                const notifTotals = (notif.lignes || []).reduce(
                  (acc, l) => ({
                    reports: acc.reports + Number(l.reports || 0),
                    diminution_report: acc.diminution_report + Number(l.diminution_report || 0),
                    credits_neufs: acc.credits_neufs + Number(l.credits_neufs || 0),
                    diminution_credit_neuf: acc.diminution_credit_neuf + Number(l.diminution_credit_neuf || 0),
                    credits_engagements: acc.credits_engagements + Number(l.credits_engagements || 0),
                    diminution_credit_engagement:
                      acc.diminution_credit_engagement + Number(l.diminution_credit_engagement || 0),
                    total_credits: acc.total_credits + Number(l.total_credits || 0),
                    credits_engages: acc.credits_engages + Number(l.credits_engages || 0),
                    credits_disponibles: acc.credits_disponibles + Number(l.credits_disponibles || 0),
                  }),
                  {
                    reports: 0,
                    diminution_report: 0,
                    credits_neufs: 0,
                    diminution_credit_neuf: 0,
                    credits_engagements: 0,
                    diminution_credit_engagement: 0,
                    total_credits: 0,
                    credits_engages: 0,
                    credits_disponibles: 0,
                  }
                );

                return (
                  <div
                    key={notif.id}
                    className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4"
                  >
                    {/* En-tête de la notification */}
                    <div className="flex flex-col md:flex-row md:items-center justify-between border-b pb-4 gap-3">
                      <div className="flex items-center gap-3 flex-wrap">
                        <span className="bg-[#1e40af] text-white text-xs font-mono font-bold px-3 py-1.5 rounded-lg shadow-2xs">
                          Notification #{index + 1} : {notif.numero || `N-${notif.id}`}
                        </span>

                        <span
                          className={`px-2.5 py-1 rounded-lg text-xs font-bold ${notif.type_budget === 'REPORT'
                              ? 'bg-purple-100 text-purple-800 border border-purple-200'
                              : notif.type_budget === 'REPORT + NOTIFIER'
                                ? 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                                : 'bg-blue-100 text-blue-800 border border-blue-200'
                            }`}
                        >
                          {notif.type_budget || 'NOTIFIER'}
                        </span>

                        {notif.domaine && (
                          <span className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {notif.domaine}
                          </span>
                        )}

                        <span className="text-xs text-slate-500 font-medium">
                          Date : <strong className="text-slate-700">{formatDate(notif.date_notification)}</strong>
                        </span>

                        {notif.objet && (
                          <span className="text-xs text-slate-600 italic bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
                            {notif.objet}
                          </span>
                        )}
                      </div>

                      <div className="flex items-center gap-2">
                        <Link
                          to={`/notifications/${notif.id}`}
                          className="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition flex items-center gap-1.5 border border-blue-200"
                          title="Consulter la fiche détaillée"
                        >
                          <Eye size={14} /> Fiche Détails
                        </Link>
                      </div>
                    </div>

                    {/* Tableau Récapitulatif de cette notification */}
                    {!notif.lignes || notif.lignes.length === 0 ? (
                      <p className="py-6 text-center text-slate-400 text-xs italic bg-slate-50 rounded-xl border">
                        Aucune ligne budgétaire associée à cette notification.
                      </p>
                    ) : (
                      <div className="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                        <table className="w-full text-left border-collapse text-xs">
                          <thead>
                            <tr className="bg-[#1e3a8a] text-white font-bold">
                              <th className="p-3 border-r border-blue-800">Imputation Budgétaire</th>
                              <th className="p-3 border-r border-blue-800">Libellé</th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (1) Reports {selectedExercice - 1}/{selectedExercice}
                              </th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (2) Dim. report
                              </th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (3) Crédits neufs
                              </th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (4) Dim. crédit neuf
                              </th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (5) Crédits d&apos;eng.
                              </th>
                              <th className="p-3 text-right border-r border-blue-800">
                                (6) Dim. eng.
                              </th>
                              <th className="p-3 text-right bg-blue-950 font-black">
                                Total Crédits (1+3+5)
                              </th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-slate-200">
                            {/* Ligne 1 : TOTAL NOTIFICATION */}
                            <tr className="bg-slate-100 font-bold text-xs text-slate-900 border-b-2 border-slate-300">
                              <td colSpan="2" className="p-3 border-r border-slate-300 uppercase font-black">
                                Total {notif.numero ? `Notification ${notif.numero}` : 'Notification'}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300">
                                {Number(notifTotals.reports) > 0 ? formatCurrency(notifTotals.reports) : ''}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300 text-amber-700">
                                {formatCurrency(notifTotals.diminution_report)}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300">
                                {formatCurrency(notifTotals.credits_neufs)}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300 text-amber-700">
                                {formatCurrency(notifTotals.diminution_credit_neuf)}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300">
                                {formatCurrency(notifTotals.credits_engagements)}
                              </td>
                              <td className="p-3 text-right font-mono border-r border-slate-300 text-amber-700">
                                {formatCurrency(notifTotals.diminution_credit_engagement)}
                              </td>
                              <td className="p-3 text-right font-mono font-black text-white bg-[#1e40af]">
                                {formatCurrency(notifTotals.total_credits)}
                              </td>
                            </tr>
                            {notif.lignes.map((ligne) => {
                              const imputation = `${ligne.article || '••'}/${ligne.paragraphe || '••'}/${ligne.ligne_budgetaire || '••'}`;
                              return (
                                <tr key={ligne.id} className="hover:bg-slate-50 transition font-medium">
                                  <td className="p-3 font-mono font-bold text-[#1e3a8a] border-r border-slate-200 whitespace-nowrap">
                                    {imputation}
                                  </td>
                                  <td className="p-3 border-r border-slate-200 text-slate-700">
                                    {ligne.libelle}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.reports) > 0
                                        ? 'text-purple-900 font-bold bg-purple-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.reports)}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.diminution_report) > 0
                                        ? 'text-amber-800 font-bold bg-amber-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.diminution_report)}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.credits_neufs) > 0
                                        ? 'text-indigo-900 font-bold bg-indigo-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.credits_neufs)}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.diminution_credit_neuf) > 0
                                        ? 'text-amber-800 font-bold bg-amber-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.diminution_credit_neuf)}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.credits_engagements) > 0
                                        ? 'text-emerald-900 font-bold bg-emerald-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.credits_engagements)}
                                  </td>
                                  <td
                                    className={`p-3 text-right font-mono border-r border-slate-200 ${Number(ligne.diminution_credit_engagement) > 0
                                        ? 'text-amber-800 font-bold bg-amber-50/50'
                                        : 'text-slate-400'
                                      }`}
                                  >
                                    {formatCurrency(ligne.diminution_credit_engagement)}
                                  </td>
                                  <td className="p-3 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/50">
                                    {formatCurrency(ligne.total_credits)}
                                  </td>
                                </tr>
                              );
                            })}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                );
              })
            )}

            {/* SYNTHÈSE GLOBALE DE L'EXERCICE */}
            {recapExercice && (
              <div className="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4 mb-4">
                  <div>
                    <h3 className="text-lg font-black text-white flex items-center gap-2">
                      <Receipt className="text-emerald-400" size={20} /> Synthèse Globale de l&apos;Exercice {selectedExercice}
                    </h3>
                    <p className="text-xs text-slate-400 mt-0.5">
                      Consolidation des {notifications.length} notification{notifications.length > 1 ? 's' : ''} enregistrée{notifications.length > 1 ? 's' : ''}.
                    </p>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="text-right">
                      <span className="text-[11px] text-slate-400 uppercase font-bold block">Total Disponible Net</span>
                      <span className="text-xl font-black text-emerald-400 font-mono">
                        {formatCurrency(recapExercice?.totaux?.total_disponibles)}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                  <div className="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span className="text-slate-400 block text-[11px] uppercase font-bold">(1) Total Reports</span>
                    <span className="text-sm font-bold font-mono text-purple-300">
                      {formatCurrency(recapExercice?.totaux?.total_reports)}
                    </span>
                  </div>
                  <div className="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span className="text-slate-400 block text-[11px] uppercase font-bold">(3) Crédits Neufs</span>
                    <span className="text-sm font-bold font-mono text-blue-300">
                      {formatCurrency(recapExercice?.totaux?.total_credits_neufs)}
                    </span>
                  </div>
                  <div className="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span className="text-slate-400 block text-[11px] uppercase font-bold">(5) Crédits Engagement</span>
                    <span className="text-sm font-bold font-mono text-emerald-300">
                      {formatCurrency(recapExercice?.totaux?.total_credits_engagements)}
                    </span>
                  </div>
                  <div className="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span className="text-slate-400 block text-[11px] uppercase font-bold">Total Crédits (1+3+5)</span>
                    <span className="text-sm font-black font-mono text-amber-300">
                      {formatCurrency(recapExercice?.totaux?.total_credits)}
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>
        ) : viewMode === 'recap_cumule' ? (
          /* VUE 2 : TABLEAUX RÉCAPITULATIFS CUMULÉS DE L'EXERCICE (INVESTISSEMENT & FONCTIONNEMENT) */
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

            const renderTable = (title, domaineBadge, themeColor, rows, totals, totalsLabel) => (
              <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
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
                      Consolidation des notifications {domaineBadge.toLowerCase()} de l&apos;exercice {selectedExercice} ({rows.length} ligne{rows.length > 1 ? 's' : ''}).
                    </p>
                  </div>
                </div>

                {rows.length === 0 ? (
                  <p className="py-10 text-center text-slate-400 text-xs italic bg-slate-50 rounded-xl border border-slate-100">
                    Aucune ligne budgétaire enregistrée pour le budget {domaineBadge.toLowerCase()} sur l&apos;exercice {selectedExercice}.
                  </p>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-[#1e3a8a] text-white font-bold">
                          <th className="p-3.5 border-r border-blue-800">Imputation Budgétaire</th>
                          <th className="p-3.5 border-r border-blue-800">Libellé</th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (1) Reports {selectedExercice - 1}/{selectedExercice}
                          </th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (2) Dim. report
                          </th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (3) Crédits neufs
                          </th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (4) Dim. crédit neuf
                          </th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (5) Crédits d&apos;eng.
                          </th>
                          <th className="p-3.5 text-right border-r border-blue-800">
                            (6) Dim. eng.
                          </th>
                          <th className="p-3.5 text-right bg-blue-950 font-black">
                            Total Crédits (1+3+5)
                          </th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-200">
                        {/* Ligne 1 : TOTAL RÉCAPITULATIF */}
                        <tr className="bg-slate-100 font-bold text-xs text-slate-900 border-b-2 border-slate-300">
                          <td colSpan="2" className="p-3.5 border-r border-slate-300 uppercase font-black">
                            {totalsLabel} {selectedExercice}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {Number(totals.reports) > 0 ? formatCurrency(totals.reports) : ''}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatCurrency(totals.diminution_report)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {formatCurrency(totals.credits_neufs)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatCurrency(totals.diminution_credit_neuf)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300">
                            {formatCurrency(totals.credits_engagements)}
                          </td>
                          <td className="p-3.5 text-right font-mono border-r border-slate-300 text-amber-700">
                            {formatCurrency(totals.diminution_credit_engagement)}
                          </td>
                          <td className="p-3.5 text-right font-mono font-black text-white bg-[#1e40af]">
                            {formatCurrency(totals.total_credits)}
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
                              {Number(row.reports) > 0 ? formatCurrency(row.reports) : ''}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatCurrency(row.diminution_report)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatCurrency(row.credits_neufs)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatCurrency(row.diminution_credit_neuf)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatCurrency(row.credits_engagements)}
                            </td>
                            <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatCurrency(row.diminution_credit_engagement)}
                            </td>
                            <td className="p-3.5 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/50">
                              {formatCurrency(row.total_credits)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            );

            return (
              <div className="space-y-8">
                {renderTable(
                  `Tableau Récapitulatif Cumulé - Investissement ${selectedExercice}`,
                  'INVESTISSEMENT',
                  'blue',
                  lignesInvestissement,
                  totalsInvestissement,
                  'Total Investissement'
                )}

                {renderTable(
                  `Tableau Récapitulatif Cumulé - Fonctionnement ${selectedExercice}`,
                  'FONCTIONNEMENT',
                  'emerald',
                  lignesFonctionnement,
                  totalsFonctionnement,
                  'Total Fonctionnement'
                )}
              </div>
            );
          })()
        ) : (
          /* VUE 3 : LISTE DES DOSSIERS */
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-200">
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Numéro</th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Type & Domaine</th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Exercice</th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Date</th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Lignes Budgétaires</th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase text-right">
                      Total Crédits
                    </th>
                    <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase text-right">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {notifications.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="px-6 py-12 text-center text-slate-500">
                        Aucune notification enregistrée pour l&apos;exercice {selectedExercice}.
                      </td>
                    </tr>
                  ) : (
                    notifications.map((notification) => {
                      const totalCredits =
                        notification.lignes?.reduce((acc, l) => acc + Number(l?.total_credits || 0), 0) ||
                        Number(notification.montant || 0) ||
                        0;
                      return (
                        <tr key={notification.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-6 py-5 font-mono font-bold text-[#1e3a8a]">
                            {notification.numero || '-'}
                          </td>
                          <td className="px-6 py-5">
                            <div className="flex items-center gap-1.5 flex-wrap">
                              <span
                                className={`px-2.5 py-1 rounded-lg text-xs font-bold ${notification.type_budget === 'REPORT'
                                    ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                    : notification.type_budget === 'REPORT + NOTIFIER'
                                      ? 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                                      : 'bg-blue-100 text-blue-800 border border-blue-200'
                                  }`}
                              >
                                {notification.type_budget || 'NOTIFIER'}
                              </span>
                              {notification.domaine && (
                                <span className="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                  {notification.domaine}
                                </span>
                              )}
                            </div>
                          </td>
                          <td className="px-6 py-5 text-slate-700 font-semibold font-mono">
                            {notification.exercice}
                          </td>
                          <td className="px-6 py-5 text-slate-600 text-sm">
                            {formatDate(notification.date_notification)}
                          </td>
                          <td className="px-6 py-5 text-slate-600">
                            <span className="bg-slate-100 text-slate-800 px-2.5 py-1 rounded-md text-xs font-bold">
                              {notification.lignes?.length || 0} ligne(s)
                            </span>
                          </td>
                          <td className="px-6 py-5 font-mono font-bold text-slate-900 text-right">
                            {formatCurrency(totalCredits)}
                          </td>
                          <td className="px-6 py-5 text-right">
                            <Link
                              to={`/notifications/${notification.id}`}
                              className="inline-flex items-center justify-center p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                              title="Voir les détails"
                            >
                              <Eye size={18} />
                            </Link>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ListeNotifications;
