import { useState, useEffect, useMemo, useCallback } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../api/axios';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Save,
  AlertCircle,
  TrendingUp,
  TrendingDown,
  Building2,
  FolderSync,
  Receipt,
  CheckCircle2,
  Layers,
  HelpCircle,
  Info,
  Calendar,
  Sparkles,
  Lock,
} from 'lucide-react';

export default function NouvelleNotification() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // 1. CHOIX DU TYPE DE BUDGET AU PREMIER NIVEAU : 'REPORT' ou 'NOTIFIER'
  const [typeBudget, setTypeBudget] = useState('NOTIFIER'); // 'REPORT' | 'NOTIFIER'

  // Informations Générales (Exercice sélectionnable)
  const [generalInfo, setGeneralInfo] = useState({
    numero: '',
    exercice: new Date().getFullYear(),
    date_notification: new Date().toISOString().split('T')[0],
    objet: '',
    reference: '',
    observations: '',
  });

  // Lignes existantes sur cet exercice
  const [existingLines, setExistingLines] = useState([]);

  // Statut global du REPORT pour l'exercice sélectionné (Un seul REPORT par exercice au total)
  const [exerciseReportStatus, setExerciseReportStatus] = useState({
    has_report: false,
    report_montant: 0,
    notification_numero: null,
    ligne_budgetaire: null,
    libelle: null,
  });

  // Lignes Budgétaires
  const [lignes, setLignes] = useState([
    {
      id: 'ligne-1',
      type_ligne: 'NOTIFIER', // 'NOTIFIER' | 'BOTH'
      article: '',
      paragraphe: '',
      ligne_budgetaire: '',
      libelle: '',

      // Si REPORT ou BOTH (1)
      report_amount: '',

      // Domaine & Nature
      domaine: 'INVESTISSEMENT', // 'FONCTIONNEMENT' | 'INVESTISSEMENT'
      nature_notification: 'TOUT', // 'ALIMENTATION' | 'DIMINUTION' | 'TOUT'

      credits_engagement: [
        {
          id: 'eng-1-1',
          montant: '',
          date_mouvement: new Date().toISOString().split('T')[0],
        },
      ],
      credits_neufs: [
        {
          id: 'neuf-1-1',
          type_credit_neuf: 'CC', // 'CC' | 'CPN'
          montant: '',
          date_mouvement: new Date().toISOString().split('T')[0],
        },
      ],
      diminutions: [
        {
          id: 'dim-1-1',
          type_diminution: 'REPORT', // 'REPORT' | 'PAIEMENT' | 'ENGAGEMENT'
          motif: '',
          montant: '',
          date_mouvement: new Date().toISOString().split('T')[0],
        },
      ],
    },
  ]);

  const [recapViewTab, setRecapViewTab] = useState('cette_notification');
  const [recapExerciceData, setRecapExerciceData] = useState(null);

  const parseNum = (val) => {
    if (val === null || val === undefined || val === '') return 0;
    const clean = String(val).replace(/\s/g, '').replace(',', '.');
    const n = Number(clean);
    return isNaN(n) ? 0 : n;
  };

  const formatMoney = (val) => {
    const num = parseNum(val);
    return (
      new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(num) + ' DH'
    );
  };

  const fetchExistingLines = useCallback(async () => {
    try {
      const res = await api.get(`/notifications/lignes-existantes?exercice=${generalInfo.exercice}`);
      setExistingLines(res.data || []);
    } catch (err) {
      console.error('Erreur chargement lignes existantes:', err);
    }
  }, [generalInfo.exercice]);

  const fetchRecapExercice = useCallback(async () => {
    try {
      const res = await api.get(`/notifications/recapitulatif-exercice?exercice=${generalInfo.exercice}`);
      setRecapExerciceData(res.data || null);
    } catch (err) {
      console.error('Erreur chargement récapitulatif exercice:', err);
    }
  }, [generalInfo.exercice]);

  const fetchReportStatus = useCallback(async () => {
    try {
      const res = await api.get(`/notifications/report-status?exercice=${generalInfo.exercice}`);
      setExerciseReportStatus(res.data || { has_report: false });
    } catch (err) {
      console.error('Erreur chargement statut report exercice:', err);
    }
  }, [generalInfo.exercice]);

  useEffect(() => {
    fetchExistingLines();
    fetchRecapExercice();
    fetchReportStatus();
  }, [fetchExistingLines, fetchRecapExercice, fetchReportStatus]);

  const checkLineHasReport = (art, par, lig) => {
    if (!art || !par || !lig) return null;
    const match = existingLines.find(
      (el) =>
        el.article?.trim() === String(art).trim() &&
        el.paragraphe?.trim() === String(par).trim() &&
        el.ligne_budgetaire?.trim() === String(lig).trim()
    );
    return match && match.has_report ? match : null;
  };

  const updateLigne = (ligneId, field, value) => {
    setLignes((prev) =>
      prev.map((l) => {
        if (l.id !== ligneId) return l;
        const updated = { ...l, [field]: value };

        if (['article', 'paragraphe', 'ligne_budgetaire'].includes(field)) {
          const art = field === 'article' ? value : l.article;
          const par = field === 'paragraphe' ? value : l.paragraphe;
          const lig = field === 'ligne_budgetaire' ? value : l.ligne_budgetaire;

          const match = existingLines.find(
            (el) =>
              el.article?.trim() === String(art || '').trim() &&
              el.paragraphe?.trim() === String(par || '').trim() &&
              el.ligne_budgetaire?.trim() === String(lig || '').trim()
          );

          if (match) {
            updated.libelle = match.libelle || l.libelle;
            if (match.domaine) updated.domaine = match.domaine;
          }
        }

        return updated;
      })
    );
  };

  const handleSelectExistingLine = (ligneId, existingLineKey) => {
    const match = existingLines.find((el) => el.key === existingLineKey);
    if (!match) return;

    setLignes((prev) =>
      prev.map((l) => {
        if (l.id !== ligneId) return l;
        return {
          ...l,
          article: match.article,
          paragraphe: match.paragraphe,
          ligne_budgetaire: match.ligne_budgetaire,
          libelle: match.libelle,
          domaine: match.domaine || l.domaine,
        };
      })
    );
  };

  const addLigneWithType = (type = 'NOTIFIER') => {
    const newLigneId = 'ligne-' + Date.now();
    setLignes([
      ...lignes,
      {
        id: newLigneId,
        type_ligne: type,
        article: '',
        paragraphe: '',
        ligne_budgetaire: '',
        libelle: '',
        report_amount: '',
        domaine: 'INVESTISSEMENT',
        nature_notification: 'TOUT',
        credits_engagement: [
          {
            id: 'eng-' + Date.now() + '-1',
            montant: '',
            date_mouvement: generalInfo.date_notification || new Date().toISOString().split('T')[0],
          },
        ],
        credits_neufs: [
          {
            id: 'neuf-' + Date.now() + '-1',
            type_credit_neuf: 'CC',
            montant: '',
            date_mouvement: generalInfo.date_notification || new Date().toISOString().split('T')[0],
          },
        ],
        diminutions: [
          {
            id: 'dim-' + Date.now() + '-1',
            type_diminution: 'REPORT',
            motif: '',
            montant: '',
            date_mouvement: generalInfo.date_notification || new Date().toISOString().split('T')[0],
          },
        ],
      },
    ]);
  };

  const removeLigne = (ligneId) => {
    if (lignes.length > 1) {
      setLignes(lignes.filter((l) => l.id !== ligneId));
    }
  };

  // Sous-mouvements
  const addCreditEngagement = (ligneId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_engagement: [
              ...l.credits_engagement,
              {
                id: 'eng-' + Date.now(),
                montant: '',
                date_mouvement:
                  generalInfo.date_notification || new Date().toISOString().split('T')[0],
              },
            ],
          }
          : l
      )
    );
  };

  const removeCreditEngagement = (ligneId, mvtId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_engagement: l.credits_engagement.filter((m) => m.id !== mvtId),
          }
          : l
      )
    );
  };

  const updateCreditEngagement = (ligneId, mvtId, field, value) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_engagement: l.credits_engagement.map((m) =>
              m.id === mvtId ? { ...m, [field]: value } : m
            ),
          }
          : l
      )
    );
  };

  const addCreditNeuf = (ligneId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_neufs: [
              ...l.credits_neufs,
              {
                id: 'neuf-' + Date.now(),
                type_credit_neuf: 'CC',
                montant: '',
                date_mouvement:
                  generalInfo.date_notification || new Date().toISOString().split('T')[0],
              },
            ],
          }
          : l
      )
    );
  };

  const removeCreditNeuf = (ligneId, mvtId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_neufs: l.credits_neufs.filter((m) => m.id !== mvtId),
          }
          : l
      )
    );
  };

  const updateCreditNeuf = (ligneId, mvtId, field, value) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            credits_neufs: l.credits_neufs.map((m) =>
              m.id === mvtId ? { ...m, [field]: value } : m
            ),
          }
          : l
      )
    );
  };

  const addDiminution = (ligneId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            diminutions: [
              ...l.diminutions,
              {
                id: 'dim-' + Date.now(),
                type_diminution: 'REPORT',
                motif: '',
                montant: '',
                date_mouvement:
                  generalInfo.date_notification || new Date().toISOString().split('T')[0],
              },
            ],
          }
          : l
      )
    );
  };

  const removeDiminution = (ligneId, mvtId) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            diminutions: l.diminutions.filter((m) => m.id !== mvtId),
          }
          : l
      )
    );
  };

  const updateDiminution = (ligneId, mvtId, field, value) => {
    setLignes((prev) =>
      prev.map((l) =>
        l.id === ligneId
          ? {
            ...l,
            diminutions: l.diminutions.map((m) =>
              m.id === mvtId ? { ...m, [field]: value } : m
            ),
          }
          : l
      )
    );
  };

  // Calculs automatiques en temps réel combinant TOUS LES CHAMPS (Report, Crédits Neufs, Engagement, Diminutions)
  const notificationRecap = useMemo(() => {
    const lignesRecap = lignes.map((l) => {
      let col1_Reports = 0;
      let col2_DimReport = 0;
      let col3_CreditsNeufs = 0;
      let col4_DimCreditNeuf = 0;
      let col5_CreditsEngagement = 0;
      let col6_DimEngagement = 0;

      // 1. (1) Reports : toujours calculé si un montant de report est saisi
      if (l.report_amount) {
        col1_Reports = parseNum(l.report_amount);
      }

      // 2. (3) Crédits Neufs (CC / CPN) : toujours calculés indépendamment
      col3_CreditsNeufs = (l.credits_neufs || []).reduce(
        (sum, item) => sum + parseNum(item.montant),
        0
      );

      // 3. (5) Crédits d'engagement : toujours calculés indépendamment
      col5_CreditsEngagement = (l.credits_engagement || []).reduce(
        (sum, item) => sum + parseNum(item.montant),
        0
      );

      // 4. (2, 4, 6) Diminutions : toujours calculées indépendamment
      (l.diminutions || []).forEach((item) => {
        const mnt = parseNum(item.montant);
        if (item.type_diminution === 'REPORT') col2_DimReport += mnt;
        else if (item.type_diminution === 'PAIEMENT') col4_DimCreditNeuf += mnt;
        else if (item.type_diminution === 'ENGAGEMENT') col6_DimEngagement += mnt;
      });

      // FORMULE DU TABLEAU EXCEL : Total = (1) + (3) + (5)
      const colTotalCredits = col1_Reports + col3_CreditsNeufs + col5_CreditsEngagement;

      // Crédits disponibles nets = (1 - 2) + (3 - 4) + (5 - 6)
      const creditsDisponibles =
        col1_Reports -
        col2_DimReport +
        (col3_CreditsNeufs - col4_DimCreditNeuf) +
        (col5_CreditsEngagement - col6_DimEngagement);

      return {
        id: l.id,
        article: l.article,
        paragraphe: l.paragraphe,
        ligne_budgetaire: l.ligne_budgetaire,
        libelle: l.libelle,
        type_ligne: l.type_ligne,
        domaine: l.domaine,
        reports: col1_Reports,
        dimReport: col2_DimReport,
        creditsNeufs: col3_CreditsNeufs,
        dimCreditNeuf: col4_DimCreditNeuf,
        creditsEngagement: col5_CreditsEngagement,
        dimEngagement: col6_DimEngagement,
        totalCredits: colTotalCredits,
        creditsDisponibles: creditsDisponibles,
      };
    });

    const totaux = {
      reports: lignesRecap.reduce((s, r) => s + r.reports, 0),
      dimReport: lignesRecap.reduce((s, r) => s + r.dimReport, 0),
      creditsNeufs: lignesRecap.reduce((s, r) => s + r.creditsNeufs, 0),
      dimCreditNeuf: lignesRecap.reduce((s, r) => s + r.dimCreditNeuf, 0),
      creditsEngagement: lignesRecap.reduce((s, r) => s + r.creditsEngagement, 0),
      dimEngagement: lignesRecap.reduce((s, r) => s + r.dimEngagement, 0),
      totalCredits: lignesRecap.reduce((s, r) => s + r.totalCredits, 0),
      creditsDisponibles: lignesRecap.reduce((s, r) => s + r.creditsDisponibles, 0),
    };

    return { lignes: lignesRecap, totaux };
  }, [lignes]);

  // Soumission
  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      let extractedNumero = generalInfo.numero?.trim() || '';
      let extractedDate = generalInfo.date_notification || new Date().toISOString().split('T')[0];

      if (!extractedNumero) {
        throw new Error("Veuillez renseigner le N° de Notification en haut.");
      }

      const payloadLignes = [];
      let hasAnyReport = false;
      let hasAnyNotifier = false;
      let reportLinesCount = 0;

      // RÈGLE MÉTIER STRICTE : 1 seul REPORT au total par EXERCICE
      for (const l of lignes) {
        if (Number(l.report_amount) > 0) {
          reportLinesCount++;
        }
      }

      if (reportLinesCount > 1) {
        throw new Error(
          `⚠️ Un seul crédit de REPORT peut être saisi au total pour l'exercice ${generalInfo.exercice}. Vous avez saisi un report sur ${reportLinesCount} lignes distinctes.`
        );
      }

      if (reportLinesCount > 0 && exerciseReportStatus?.has_report) {
        throw new Error(
          `⚠️ Un REPORT existe déjà pour l'exercice ${generalInfo.exercice} (Notification : ${exerciseReportStatus.notification_numero || 'existante'}, Ligne : ${exerciseReportStatus.ligne_budgetaire || '-'}, Montant : ${formatMoney(exerciseReportStatus.report_montant)}). Le REPORT ne peut être saisi qu'une seule fois par exercice, quelle que soit la ligne budgétaire ou la notification.`
        );
      }

      for (let i = 0; i < lignes.length; i++) {
        const l = lignes[i];
        const ligneNum = i + 1;

        const art = (l.article || '').trim();
        const par = (l.paragraphe || '').trim();
        const lig = (l.ligne_budgetaire || '').trim();
        const lib = (l.libelle || '').trim();

        if (!art || !par || !lig || !lib) {
          throw new Error(`Ligne #${ligneNum} : L'Article, le Paragraphe, la Ligne et l'Intitulé sont obligatoires.`);
        }

        const mouvementsPayload = [];
        let ligneReports = 0;
        let ligneDimReport = 0;
        let ligneCreditsNeufs = 0;
        let ligneDimCreditNeuf = 0;
        let ligneCreditsEngagement = 0;
        let ligneDimEngagement = 0;

        // 1. INCLUSION DU REPORT (si présent)
        const mntReport = Number(l.report_amount);
        if (mntReport && mntReport > 0) {
          if (exerciseReportStatus?.has_report) {
            throw new Error(
              `⚠️ Un REPORT existe déjà pour l'exercice ${generalInfo.exercice} (Notification : ${exerciseReportStatus.notification_numero || 'existante'}, Montant : ${formatMoney(exerciseReportStatus.report_montant)}). Le REPORT ne peut être saisi qu'une seule fois par exercice, quelle que soit la ligne budgétaire ou la notification.`
            );
          }

          mouvementsPayload.push({
            type_budget: 'REPORT',
            nature: 'REPORT',
            type_credit: 'REPORT',
            numero_notification: extractedNumero,
            date_mouvement: extractedDate,
            montant: mntReport,
          });

          ligneReports = mntReport;
          hasAnyReport = true;
        }

        // 2. INCLUSION DES CRÉDITS D'ENGAGEMENT (si présents)
        for (const eng of l.credits_engagement || []) {
          if (eng.montant && Number(eng.montant) > 0) {
            mouvementsPayload.push({
              type_budget: 'NOTIFIER',
              domaine: l.domaine || 'INVESTISSEMENT',
              nature: 'ALIMENTATION',
              type_credit: 'ENGAGEMENT',
              numero_notification: extractedNumero,
              date_mouvement: eng.date_mouvement || extractedDate,
              montant: Number(eng.montant),
            });
            ligneCreditsEngagement += Number(eng.montant);
            hasAnyNotifier = true;
          }
        }

        // 3. INCLUSION DES CRÉDITS NEUFS CC / CPN (si présents)
        for (const neuf of l.credits_neufs || []) {
          if (neuf.montant && Number(neuf.montant) > 0) {
            mouvementsPayload.push({
              type_budget: 'NOTIFIER',
              domaine: l.domaine || 'INVESTISSEMENT',
              nature: 'ALIMENTATION',
              type_credit: neuf.type_credit_neuf === 'CC' ? 'NEUF_CC' : 'NEUF_CPN',
              numero_notification: extractedNumero,
              date_mouvement: neuf.date_mouvement || extractedDate,
              montant: Number(neuf.montant),
            });
            ligneCreditsNeufs += Number(neuf.montant);
            hasAnyNotifier = true;
          }
        }

        // 4. INCLUSION DES DIMINUTIONS (si présentes)
        for (const dim of l.diminutions || []) {
          if (dim.montant && Number(dim.montant) > 0) {
            if (!dim.motif?.trim()) {
              throw new Error(`Ligne #${ligneNum} : Le motif est obligatoire pour chaque diminution.`);
            }
            let typeCreditCode = 'DIMINUTION_REPORT';
            if (dim.type_diminution === 'REPORT') ligneDimReport += Number(dim.montant);
            if (dim.type_diminution === 'ENGAGEMENT') {
              typeCreditCode = 'DIMINUTION_ENGAGEMENT';
              ligneDimEngagement += Number(dim.montant);
            }
            if (dim.type_diminution === 'PAIEMENT') {
              typeCreditCode = 'DIMINUTION_PAIEMENT';
              ligneDimCreditNeuf += Number(dim.montant);
            }

            mouvementsPayload.push({
              type_budget: 'NOTIFIER',
              domaine: l.domaine || 'INVESTISSEMENT',
              nature: 'DIMINUTION',
              type_credit: typeCreditCode,
              numero_notification: extractedNumero,
              date_mouvement: dim.date_mouvement || extractedDate,
              montant: Number(dim.montant),
              motif: dim.motif,
            });
            hasAnyNotifier = true;
          }
        }

        if (mouvementsPayload.length === 0) {
          throw new Error(`Ligne #${ligneNum} : Veuillez saisir au moins un montant de report ou un crédit/diminution supérieur à 0.`);
        }

        let ligneComputedType = 'NOTIFIER';
        if (ligneReports > 0 && (ligneCreditsNeufs > 0 || ligneCreditsEngagement > 0 || ligneDimReport > 0 || ligneDimCreditNeuf > 0 || ligneDimEngagement > 0)) {
          ligneComputedType = 'REPORT + NOTIFIER';
        } else if (ligneReports > 0) {
          ligneComputedType = 'REPORT';
        }

        payloadLignes.push({
          article: art,
          paragraphe: par,
          ligne_budgetaire: lig,
          libelle: lib,
          type_budget: ligneComputedType,
          domaine: l.domaine || null,
          reports: ligneReports,
          diminution_report: ligneDimReport,
          credits_neufs: ligneCreditsNeufs,
          diminution_credit_neuf: ligneDimCreditNeuf,
          credits_engagements: ligneCreditsEngagement,
          diminution_credit_engagement: ligneDimEngagement,
          mouvements: mouvementsPayload,
        });
      }

      let globalTypeBudget = 'NOTIFIER';
      if (hasAnyReport && hasAnyNotifier) {
        globalTypeBudget = 'REPORT + NOTIFIER';
      } else if (hasAnyReport) {
        globalTypeBudget = 'REPORT';
      }

      const payload = {
        numero: extractedNumero,
        type_budget: globalTypeBudget,
        domaine: payloadLignes.find((l) => l.domaine)?.domaine || 'INVESTISSEMENT',
        exercice: Number(generalInfo.exercice),
        date_notification: extractedDate,
        objet: generalInfo.objet,
        reference: generalInfo.reference,
        observations: generalInfo.observations,
        montant: notificationRecap?.totaux?.totalCredits || 0,
        lignes: payloadLignes,
      };

      await api.post('/notifications', payload);
      navigate('/notifications');
    } catch (err) {
      console.error('Erreur enregistrement notification:', err);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      setError(
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        "Erreur lors de l'enregistrement de la notification budgétaire."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-4 sm:px-8 lg:px-12">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <header className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <Link
              to="/notifications"
              className="p-2.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition shadow-xs text-slate-700"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-3xl font-black text-[#1e3a8a] tracking-tight">
                Nouvelle Notification Budgétaire
              </h1>
              <p className="text-sm text-slate-500 mt-0.5">
                Exercice {generalInfo.exercice} • Saisie et mouvements budgétaires
              </p>
            </div>
          </div>
          <div className="flex items-center bg-white border border-slate-200 rounded-xl px-3.5 py-2 shadow-2xs">
            <Calendar size={16} className="text-[#1e40af] mr-2" />
            <label className="text-xs font-bold text-slate-600 mr-2 uppercase">Exercice :</label>
            <select
              value={generalInfo.exercice}
              onChange={(e) =>
                setGeneralInfo({ ...generalInfo, exercice: Number(e.target.value) })
              }
              className="text-xs font-bold font-mono text-[#1e3a8a] bg-transparent focus:outline-hidden"
            >
              {[2027, 2026, 2025, 2024, 2023].map((an) => (
                <option key={an} value={an}>
                  {an}
                </option>
              ))}
            </select>
          </div>
        </header>

        {error && (
          <div className="mb-6 p-4 bg-red-50 text-red-700 rounded-xl flex items-center gap-3 border border-red-200 shadow-sm animate-shake">
            <AlertCircle size={22} className="flex-shrink-0 text-red-600" />
            <span className="font-medium text-sm">{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          {/* SECTION 1 : INFORMATIONS GÉNÉRALES DE LA NOTIFICATION */}
          <div className="bg-white p-7 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <div className="flex items-center justify-between border-b pb-3">
              <h2 className="text-base font-bold text-[#1e3a8a] flex items-center gap-2">
                <Receipt size={18} /> Informations Générales de la Notification
              </h2>
              <span className="text-xs text-slate-400 font-mono">Exercice {generalInfo.exercice}</span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                  N° DE NOTIFICATION *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Ex: NOTIF-2026/01"
                  value={generalInfo.numero}
                  onChange={(e) =>
                    setGeneralInfo({ ...generalInfo, numero: e.target.value })
                  }
                  className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-mono font-bold text-[#1e3a8a]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                  DATE DE NOTIFICATION *
                </label>
                <input
                  type="date"
                  required
                  value={generalInfo.date_notification}
                  onChange={(e) =>
                    setGeneralInfo({ ...generalInfo, date_notification: e.target.value })
                  }
                  className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-semibold text-slate-800"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                  OBJET / RÉFÉRENCE
                </label>
                <input
                  type="text"
                  placeholder="Ex: Notification initiale du budget d'investissement"
                  value={generalInfo.objet}
                  onChange={(e) => setGeneralInfo({ ...generalInfo, objet: e.target.value })}
                  className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs"
                />
              </div>
            </div>
          </div>

          {/* LIGNES BUDGÉTAIRES DE LA NOTIFICATION */}
          <section className="space-y-6">
            {lignes.map((l, index) => {
              const otherLineHasReport = lignes.some((ol) => ol.id !== l.id && Number(ol.report_amount) > 0);

              return (
                <div
                  key={l.id}
                  className="bg-white p-7 rounded-2xl shadow-sm border border-slate-200 relative space-y-6"
                >
                  {/* Header de la ligne avec sélection du type pour cette ligne */}
                  <div className="flex flex-col md:flex-row md:items-center justify-between border-b pb-4 gap-3">
                    <div className="flex items-center gap-3">
                      <span className="bg-[#1e40af] text-white text-xs font-mono font-bold px-2.5 py-1 rounded-lg">
                        Ligne #{index + 1}
                      </span>
                      <h3 className="text-base font-bold text-slate-800">
                        Identification de la ligne budgétaire
                      </h3>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                      {/* Choix du type pour cette ligne : NOTIFIER ou REPORT + NOTIFIER (1 seul report au total par exercice) */}
                      <div className="flex bg-slate-100 p-1 rounded-xl text-xs font-bold items-center gap-1">
                        <button
                          type="button"
                          onClick={() => updateLigne(l.id, 'type_ligne', 'NOTIFIER')}
                          className={`px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 ${l.type_ligne === 'NOTIFIER'
                            ? 'bg-[#1e40af] text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                          <Receipt size={14} /> NOTIFIER
                        </button>

                        {exerciseReportStatus.has_report ? (
                          <div
                            title={`⚠️ Un REPORT (${formatMoney(exerciseReportStatus.report_montant)}) existe déjà pour l'exercice ${generalInfo.exercice} (Notification : ${exerciseReportStatus.notification_numero || '-'}, Ligne : ${exerciseReportStatus.ligne_budgetaire || '-'}). Le REPORT ne peut être saisi qu'une seule fois par exercice, toutes lignes et notifications confondues.`}
                            className="px-3 py-1.5 rounded-lg text-slate-400 bg-slate-200/70 cursor-not-allowed flex items-center gap-1.5 select-none"
                          >
                            <Lock size={13} className="text-slate-500" />
                            <span className="text-slate-500 font-semibold">REPORT + NOTIFIER</span>
                            <span className="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-bold">
                              Report déjà saisi en {generalInfo.exercice}
                            </span>
                          </div>
                        ) : otherLineHasReport ? (
                          <div
                            title="Un crédit de report est déjà renseigné sur une autre ligne de cette notification. Un seul report est autorisé par exercice."
                            className="px-3 py-1.5 rounded-lg text-slate-400 bg-slate-200/70 cursor-not-allowed flex items-center gap-1.5 select-none"
                          >
                            <Lock size={13} className="text-slate-500" />
                            <span className="text-slate-500 font-semibold">REPORT + NOTIFIER</span>
                            <span className="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-bold">
                              Report sur autre ligne
                            </span>
                          </div>
                        ) : (
                          <button
                            type="button"
                            onClick={() => updateLigne(l.id, 'type_ligne', 'BOTH')}
                            className={`px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 ${l.type_ligne === 'BOTH'
                              ? 'bg-indigo-700 text-white shadow-xs'
                              : 'text-slate-600 hover:text-slate-900'
                              }`}
                          >
                            <Sparkles size={14} /> REPORT + NOTIFIER
                          </button>
                        )}
                      </div>

                      {lignes.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeLigne(l.id)}
                          className="p-2 text-red-500 hover:bg-red-50 rounded-xl transition flex items-center gap-1 text-xs font-bold"
                          title="Supprimer cette ligne"
                        >
                          <Trash2 size={16} /> Supprimer
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Sélection rapide depuis les lignes existantes */}
                  {existingLines.length > 0 && (
                    <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-3">
                      <Sparkles size={18} className="text-[#1e40af] flex-shrink-0" />
                      <span className="text-xs font-bold text-slate-700 whitespace-nowrap">
                        Sélection rapide (lignes en {generalInfo.exercice}) :
                      </span>
                      <select
                        onChange={(e) => handleSelectExistingLine(l.id, e.target.value)}
                        defaultValue=""
                        className="w-full p-2 text-xs border rounded-lg bg-white font-medium text-slate-800 focus:ring-1 focus:ring-blue-500"
                      >
                        <option value="" disabled>
                          -- Choisir une ligne déjà existante en {generalInfo.exercice} ou saisir manuellement --
                        </option>
                        {existingLines.map((el) => (
                          <option key={el.key} value={el.key}>
                            {el.article}/{el.paragraphe}/{el.ligne_budgetaire} - {el.libelle}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}

                  {/* Imputation budgétaire : Article, Paragraphe, Ligne, Libellé */}
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                        ARTICLE *
                      </label>
                      <input
                        required
                        type="text"
                        value={l.article}
                        onChange={(e) => updateLigne(l.id, 'article', e.target.value)}
                        className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-mono"
                        placeholder="Ex: 23"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                        PARAGRAPHE *
                      </label>
                      <input
                        required
                        type="text"
                        value={l.paragraphe}
                        onChange={(e) => updateLigne(l.id, 'paragraphe', e.target.value)}
                        className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-mono"
                        placeholder="Ex: 10"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                        LIGNE *
                      </label>
                      <input
                        required
                        type="text"
                        value={l.ligne_budgetaire}
                        onChange={(e) => updateLigne(l.id, 'ligne_budgetaire', e.target.value)}
                        className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-mono"
                        placeholder="Ex: 04"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                        INTITULÉ / LIBELLÉ *
                      </label>
                      <input
                        required
                        type="text"
                        value={l.libelle}
                        onChange={(e) => updateLigne(l.id, 'libelle', e.target.value)}
                        className="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs font-medium"
                        placeholder="Ex: Acquisition de matériel"
                      />
                    </div>
                  </div>

                  {/* CAS 1 : SI TYPE = REPORT ou BOTH (INTERFACE ÉPURÉE CONFORME À LA CAPTURE) */}
                  {(l.type_ligne === 'REPORT' || l.type_ligne === 'BOTH') && (
                    <div className="border border-purple-200 bg-purple-50/40 rounded-xl p-5 space-y-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5 font-bold text-sm text-purple-950">
                          <FolderSync size={18} className="text-purple-700" />
                          <span>1. Crédit de REPORT (Exercices antérieurs)</span>
                        </div>
                        <span className="text-[11px] font-bold text-purple-800 bg-purple-100 px-2.5 py-1 rounded-md">
                          Alimente Colonne (1)
                        </span>
                      </div>

                      {exerciseReportStatus.has_report ? (
                        <div className="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs font-medium flex items-center gap-2">
                          <Info size={18} className="text-amber-600 flex-shrink-0" />
                          <span>
                            ⚠️ Un crédit de report de <strong>{formatMoney(exerciseReportStatus.report_montant)}</strong> existe déjà pour l&apos;exercice {generalInfo.exercice} (Notification : <strong>{exerciseReportStatus.notification_numero || 'existante'}</strong>, Ligne : <strong>{exerciseReportStatus.ligne_budgetaire || '-'}</strong>). Le REPORT ne peut être saisi qu&apos;une seule fois par exercice, toutes lignes et notifications confondues.
                          </span>
                        </div>
                      ) : otherLineHasReport ? (
                        <div className="p-3.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-medium flex items-center gap-2">
                          <Info size={18} className="text-slate-500 flex-shrink-0" />
                          <span>
                            Un montant de report est déjà renseigné sur une autre ligne de cette notification. Un seul report est autorisé par exercice.
                          </span>
                        </div>
                      ) : (
                        <div className="max-w-sm pt-2">
                          <label className="block text-xs font-bold text-purple-900 mb-1.5 uppercase">
                            MONTANT DU REPORT (DH) *
                          </label>
                          <div className="relative">
                            <input
                              required={l.type_ligne === 'REPORT'}
                              type="number"
                              step="0.01"
                              min="0.01"
                              placeholder="Ex: 50 000"
                              value={l.report_amount}
                              onChange={(e) => updateLigne(l.id, 'report_amount', e.target.value)}
                              className="w-full p-2.5 pr-12 border border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm font-bold text-purple-950 bg-white"
                            />
                            <span className="absolute right-4 top-2.5 text-xs font-bold text-slate-400">
                              DH
                            </span>
                          </div>
                        </div>
                      )}
                    </div>
                  )}

                  {/* CAS 2 : SI TYPE = NOTIFIER ou BOTH (DOMAINE, NATURE, CRÉDITS NEUFS / ENGAGEMENT / DIMINUTIONS) */}
                  {(l.type_ligne === 'NOTIFIER' || l.type_ligne === 'BOTH') && (
                    <div className="border border-blue-200 bg-blue-50/20 rounded-xl p-5 space-y-5">
                      {/* Domaine */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase">
                          Domaine du Budget *
                        </label>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                          <label
                            onClick={() => updateLigne(l.id, 'domaine', 'FONCTIONNEMENT')}
                            className={`p-3 rounded-xl border cursor-pointer flex items-center justify-between transition ${l.domaine === 'FONCTIONNEMENT'
                              ? 'border-[#1e40af] bg-blue-50 text-[#1e3a8a] font-bold ring-1 ring-blue-500/20'
                              : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'
                              }`}
                          >
                            <span className="text-xs">FONCTIONNEMENT</span>
                            <span className="text-[10px] text-slate-500">Dépenses courantes</span>
                          </label>
                          <label
                            onClick={() => updateLigne(l.id, 'domaine', 'INVESTISSEMENT')}
                            className={`p-3 rounded-xl border cursor-pointer flex items-center justify-between transition ${l.domaine === 'INVESTISSEMENT'
                              ? 'border-[#1e40af] bg-blue-50 text-[#1e3a8a] font-bold ring-1 ring-blue-500/20'
                              : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'
                              }`}
                          >
                            <span className="text-xs">INVESTISSEMENT</span>
                            <span className="text-[10px] text-slate-500">Équipements & Projets</span>
                          </label>
                        </div>
                      </div>

                      {/* ALIMENTATION (Crédits d'engagement (5) & Crédits Neufs (3)) */}
                      <div className="space-y-5">
                        {/* Crédits d'engagement */}
                        <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                          <div className="flex justify-between items-center">
                            <h4 className="text-xs font-bold text-[#1e3a8a] uppercase tracking-wide">
                              Crédits d&apos;engagement (5)
                            </h4>
                            <button
                              type="button"
                              onClick={() => addCreditEngagement(l.id)}
                              className="text-xs font-bold bg-emerald-100 text-emerald-800 px-3 py-1.5 rounded-lg flex items-center gap-1.5 hover:bg-emerald-200 transition"
                            >
                              <Plus size={14} /> + Ajouter crédit d&apos;engagement
                            </button>
                          </div>

                          {l.credits_engagement.map((eng) => (
                            <div
                              key={eng.id}
                              className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-3 rounded-xl border border-slate-200 shadow-2xs"
                            >
                              <div className="md:col-span-7">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Montant Crédit d&apos;Engagement (DH) *
                                </label>
                                <input
                                  type="number"
                                  step="0.01"
                                  min="0"
                                  placeholder="100 000"
                                  value={eng.montant}
                                  onChange={(e) =>
                                    updateCreditEngagement(
                                      l.id,
                                      eng.id,
                                      'montant',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg text-right font-bold text-emerald-700 focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-4">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Date *
                                </label>
                                <input
                                  type="date"
                                  value={eng.date_mouvement}
                                  onChange={(e) =>
                                    updateCreditEngagement(
                                      l.id,
                                      eng.id,
                                      'date_mouvement',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-1 flex justify-end">
                                <button
                                  type="button"
                                  onClick={() => removeCreditEngagement(l.id, eng.id)}
                                  className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                >
                                  <Trash2 size={16} />
                                </button>
                              </div>
                            </div>
                          ))}
                        </div>

                        {/* Crédits Neufs */}
                        <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                          <div className="flex justify-between items-center">
                            <h4 className="text-xs font-bold text-[#1e3a8a] uppercase tracking-wide">
                              Crédits Neufs (3) (CC / CPN)
                            </h4>
                            <button
                              type="button"
                              onClick={() => addCreditNeuf(l.id)}
                              className="text-xs font-bold bg-emerald-100 text-emerald-800 px-3 py-1.5 rounded-lg flex items-center gap-1.5 hover:bg-emerald-200 transition"
                            >
                              <Plus size={14} /> + Ajouter crédit neuf
                            </button>
                          </div>

                          {l.credits_neufs.map((neuf) => (
                            <div
                              key={neuf.id}
                              className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-3 rounded-xl border border-slate-200 shadow-2xs"
                            >
                              <div className="md:col-span-3">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Type *
                                </label>
                                <div className="flex gap-1.5">
                                  <button
                                    type="button"
                                    onClick={() =>
                                      updateCreditNeuf(l.id, neuf.id, 'type_credit_neuf', 'CC')
                                    }
                                    className={`flex-1 py-1 px-1.5 text-[11px] font-bold rounded-lg border transition ${neuf.type_credit_neuf === 'CC'
                                      ? 'bg-[#1e40af] text-white border-[#1e40af]'
                                      : 'bg-slate-50 text-slate-700 border-slate-200'
                                      }`}
                                  >
                                    CC
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() =>
                                      updateCreditNeuf(l.id, neuf.id, 'type_credit_neuf', 'CPN')
                                    }
                                    className={`flex-1 py-1 px-1.5 text-[11px] font-bold rounded-lg border transition ${neuf.type_credit_neuf === 'CPN'
                                      ? 'bg-[#1e40af] text-white border-[#1e40af]'
                                      : 'bg-slate-50 text-slate-700 border-slate-200'
                                      }`}
                                  >
                                    CPN
                                  </button>
                                </div>
                              </div>
                              <div className="md:col-span-5">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Montant Crédit Neuf (DH) *
                                </label>
                                <input
                                  type="number"
                                  step="0.01"
                                  min="0"
                                  placeholder="200 000"
                                  value={neuf.montant}
                                  onChange={(e) =>
                                    updateCreditNeuf(
                                      l.id,
                                      neuf.id,
                                      'montant',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg text-right font-bold text-indigo-700 focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-3">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Date *
                                </label>
                                <input
                                  type="date"
                                  value={neuf.date_mouvement}
                                  onChange={(e) =>
                                    updateCreditNeuf(
                                      l.id,
                                      neuf.id,
                                      'date_mouvement',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-1 flex justify-end">
                                <button
                                  type="button"
                                  onClick={() => removeCreditNeuf(l.id, neuf.id)}
                                  className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                >
                                  <Trash2 size={16} />
                                </button>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>

                      {/* DIMINUTIONS (2, 4, 6) */}
                      <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div className="flex justify-between items-center mb-2">
                          <h4 className="text-xs font-bold text-amber-900 uppercase tracking-wide">
                            Mouvements de Diminution (Motif obligatoire) (2, 4, 6)
                          </h4>
                          <button
                            type="button"
                            onClick={() => addDiminution(l.id)}
                            className="text-xs font-bold bg-amber-100 text-amber-800 px-3 py-1.5 rounded-lg flex items-center gap-1.5 hover:bg-amber-200 transition"
                          >
                            <Plus size={14} /> + Ajouter diminution
                          </button>
                        </div>

                        {l.diminutions.map((dim) => (
                          <div
                            key={dim.id}
                            className="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs space-y-2"
                          >
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                              <div className="md:col-span-4">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Catégorie *
                                </label>
                                <select
                                  value={dim.type_diminution}
                                  onChange={(e) =>
                                    updateDiminution(
                                      l.id,
                                      dim.id,
                                      'type_diminution',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg font-semibold text-slate-800 focus:ring-1 focus:ring-blue-500"
                                >
                                  <option value="REPORT">1. DIMINUTION DU REPORT (2)</option>
                                  <option value="PAIEMENT">2. DIMINUTION DU PAIEMENT / CRÉDIT NEUF (4)</option>
                                  <option value="ENGAGEMENT">3. DIMINUTION DE L&apos;ENGAGEMENT (6)</option>
                                </select>
                              </div>
                              <div className="md:col-span-4">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Montant (DH) *
                                </label>
                                <input
                                  type="number"
                                  step="0.01"
                                  min="0.01"
                                  placeholder="Montant"
                                  value={dim.montant}
                                  onChange={(e) =>
                                    updateDiminution(l.id, dim.id, 'montant', e.target.value)
                                  }
                                  className="w-full p-2 text-xs border rounded-lg text-right font-bold text-amber-700 focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-3">
                                <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                  Date
                                </label>
                                <input
                                  type="date"
                                  value={dim.date_mouvement}
                                  onChange={(e) =>
                                    updateDiminution(
                                      l.id,
                                      dim.id,
                                      'date_mouvement',
                                      e.target.value
                                    )
                                  }
                                  className="w-full p-2 text-xs border rounded-lg focus:ring-1 focus:ring-blue-500"
                                />
                              </div>
                              <div className="md:col-span-1 flex justify-end">
                                <button
                                  type="button"
                                  onClick={() => removeDiminution(l.id, dim.id)}
                                  className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                >
                                  <Trash2 size={16} />
                                </button>
                              </div>
                            </div>
                            <div>
                              <label className="block text-[10px] font-bold text-slate-600 mb-1 uppercase">
                                Motif de la Diminution * (Obligatoire)
                              </label>
                              <input
                                required
                                type="text"
                                placeholder="Indiquez le motif précis de la réduction..."
                                value={dim.motif}
                                onChange={(e) =>
                                  updateDiminution(l.id, dim.id, 'motif', e.target.value)
                                }
                                className="w-full p-2 text-xs border rounded-lg focus:ring-1 focus:ring-blue-500 bg-slate-50/50"
                              />
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              );
            })}

            <div className="flex justify-center pt-2">
              <button
                type="button"
                onClick={() => addLigneWithType('NOTIFIER')}
                className="text-xs font-bold bg-[#1e40af] text-white px-5 py-3 rounded-xl flex items-center gap-2 hover:bg-[#1e3a8a] transition shadow-xs"
              >
                <Plus size={16} /> + Ajouter une Ligne Budgétaire
              </button>
            </div>
          </section>

          {/* SECTION 3 : TABLEAU RÉCAPITULATIF (FORMAT EXCEL) */}
          <section className="bg-white p-7 rounded-2xl shadow-sm border border-slate-200">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-5 border-b pb-4 gap-3">
              <div>
                <h2 className="text-xl font-black text-[#1e3a8a] flex items-center gap-2">
                  <Receipt className="text-emerald-600" size={22} /> Tableau Récapitulatif Budgétaire
                </h2>
                <p className="text-xs text-slate-500 mt-0.5">
                  Formule réglementaire : Total = (1) Reports + (3) Crédits Neufs + (5) Crédits d&apos;Engagement
                </p>
              </div>

              <div className="flex bg-slate-100 p-1 rounded-xl text-xs font-bold">
                <button
                  type="button"
                  onClick={() => setRecapViewTab('cette_notification')}
                  className={`px-3.5 py-1.5 rounded-lg transition ${recapViewTab === 'cette_notification'
                    ? 'bg-white text-[#1e40af] shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                    }`}
                >
                  Cette Notification ({lignes.length} ligne{lignes.length > 1 ? 's' : ''})
                </button>
                <button
                  type="button"
                  onClick={() => setRecapViewTab('cumul_exercice')}
                  className={`px-3.5 py-1.5 rounded-lg transition ${recapViewTab === 'cumul_exercice'
                    ? 'bg-white text-[#1e40af] shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                    }`}
                >
                  Cumul Exercice {generalInfo.exercice}
                </button>
              </div>
            </div>

            {/* VUE 1 : CETTE NOTIFICATION */}
            {recapViewTab === 'cette_notification' && (
              <div className="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-[#1e3a8a] text-white text-xs font-bold">
                      <th className="p-3.5 border-r border-blue-800">Ligne Budgétaire</th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Reports {generalInfo.exercice - 1}/{generalInfo.exercice}
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Diminution / report
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">Crédits neufs</th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Diminution / Crédit neuf
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Crédits d&apos;engagement
                      </th>
                      <th className="p-3.5 text-right border-r border-blue-800">
                        Diminution engagement
                      </th>
                      <th className="p-3.5 text-right bg-blue-950 font-black">
                        Total crédits {generalInfo.exercice}
                      </th>
                    </tr>
                    <tr className="bg-blue-900 text-blue-200 text-[11px] font-mono text-center">
                      <th className="p-1 border-r border-blue-800 text-left pl-3">Réf.</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(1)</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(2)</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(3)</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(4)</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(5)</th>
                      <th className="p-1 border-r border-blue-800 text-right pr-3">(6)</th>
                      <th className="p-1 bg-blue-950 text-right pr-3 font-bold text-white">
                        (1) + (3) + (5)
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 text-xs">
                    {notificationRecap.lignes.map((row, idx) => (
                      <tr key={row.id} className="hover:bg-slate-50 transition font-medium">
                        <td className="p-3.5 border-r border-slate-200">
                          <span className="font-mono font-bold text-[#1e3a8a]">
                            {row.article || '••'}/{row.paragraphe || '••'}/{row.ligne_budgetaire || '••'}
                          </span>
                          <span className="text-slate-600 block text-[11px] truncate max-w-xs mt-0.5">
                            {row.libelle || `Ligne #${idx + 1}`}
                          </span>
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.reports > 0 ? 'text-purple-900 font-bold bg-purple-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.reports)}
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.dimReport > 0 ? 'text-amber-800 font-bold bg-amber-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.dimReport)}
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.creditsNeufs > 0 ? 'text-indigo-900 font-bold bg-indigo-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.creditsNeufs)}
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.dimCreditNeuf > 0 ? 'text-amber-800 font-bold bg-amber-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.dimCreditNeuf)}
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.creditsEngagement > 0 ? 'text-emerald-900 font-bold bg-emerald-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.creditsEngagement)}
                        </td>
                        <td className={`p-3.5 text-right font-mono border-r border-slate-200 ${row.dimEngagement > 0 ? 'text-amber-800 font-bold bg-amber-50/50' : 'text-slate-400'}`}>
                          {formatMoney(row.dimEngagement)}
                        </td>
                        <td className="p-3.5 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/80 text-sm">
                          {formatMoney(row.totalCredits)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr className="bg-slate-100 font-bold text-xs text-slate-800">
                      <td className="p-3.5 border-r border-slate-200 uppercase">
                        Total Notification
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200">
                        {formatMoney(notificationRecap.totaux.reports)}
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                        {formatMoney(notificationRecap.totaux.dimReport)}
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200">
                        {formatMoney(notificationRecap.totaux.creditsNeufs)}
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                        {formatMoney(notificationRecap.totaux.dimCreditNeuf)}
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200">
                        {formatMoney(notificationRecap.totaux.creditsEngagement)}
                      </td>
                      <td className="p-3.5 text-right font-mono border-r border-slate-200 text-amber-700">
                        {formatMoney(notificationRecap.totaux.dimEngagement)}
                      </td>
                      <td className="p-3.5 text-right font-mono font-black text-white bg-[#1e40af] text-sm">
                        {formatMoney(notificationRecap.totaux.totalCredits)}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            )}

            {/* VUE 2 : CUMUL EXERCICE */}
            {recapViewTab === 'cumul_exercice' && (
              <div className="space-y-3">
                <div className="p-3 bg-blue-50/80 border border-blue-200 rounded-xl text-xs text-blue-900 flex items-center gap-2">
                  <Info size={16} className="text-[#1e40af] flex-shrink-0" />
                  <span>
                    Ce tableau consolide l&apos;ensemble des notifications déjà enregistrées sur l&apos;exercice{' '}
                    <strong>{generalInfo.exercice}</strong>.
                  </span>
                </div>

                {recapExerciceData?.lignes?.length === 0 ? (
                  <p className="p-8 text-center text-slate-500 text-xs italic bg-slate-50 rounded-xl border">
                    Aucune notification n&apos;est encore enregistrée pour l&apos;exercice {generalInfo.exercice}.
                  </p>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-[#1e3a8a] text-white font-bold">
                          <th className="p-3 border-r border-blue-800">Imputation Budgétaire</th>
                          <th className="p-3 border-r border-blue-800">Libellé</th>
                          <th className="p-3 text-right border-r border-blue-800">(1) Reports</th>
                          <th className="p-3 text-right border-r border-blue-800">(2) Dim. report</th>
                          <th className="p-3 text-right border-r border-blue-800">(3) Crédits neufs</th>
                          <th className="p-3 text-right border-r border-blue-800">(4) Dim. crédit neuf</th>
                          <th className="p-3 text-right border-r border-blue-800">(5) Crédits d&apos;eng.</th>
                          <th className="p-3 text-right border-r border-blue-800">(6) Dim. eng.</th>
                          <th className="p-3 text-right bg-blue-950 font-black">
                            Total Crédits (1+3+5)
                          </th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-200">
                        {recapExerciceData?.lignes?.map((row) => (
                          <tr key={row.imputation} className="hover:bg-slate-50 transition font-medium">
                            <td className="p-3 font-mono font-bold text-[#1e3a8a] border-r border-slate-200 whitespace-nowrap">
                              {row.imputation}
                            </td>
                            <td className="p-3 border-r border-slate-200 text-slate-700">
                              {row.libelle}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200">
                              {formatMoney(row.reports)}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_report)}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatMoney(row.credits_neufs)}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_credit_neuf)}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200 font-semibold">
                              {formatMoney(row.credits_engagements)}
                            </td>
                            <td className="p-3 text-right font-mono border-r border-slate-200 text-amber-700">
                              {formatMoney(row.diminution_credit_engagement)}
                            </td>
                            <td className="p-3 text-right font-mono font-black text-[#1e3a8a] bg-blue-50/50">
                              {formatMoney(row.total_credits)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}
          </section>

          {/* ACTIONS */}
          <div className="flex justify-end gap-4 pt-4">
            <Link
              to="/notifications"
              className="px-6 py-3 bg-white border border-slate-300 font-bold text-slate-700 rounded-xl hover:bg-slate-100 transition shadow-xs text-sm"
            >
              Annuler
            </Link>
            <button
              type="submit"
              disabled={loading}
              className="px-8 py-3 bg-[#1e40af] text-white font-bold rounded-xl shadow-sm hover:bg-[#1e3a8a] transition flex items-center gap-2 text-sm disabled:opacity-50"
            >
              {loading ? (
                <span className="animate-spin text-white">⌛</span>
              ) : (
                <Save size={18} />
              )}{' '}
              Enregistrer la Notification
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
