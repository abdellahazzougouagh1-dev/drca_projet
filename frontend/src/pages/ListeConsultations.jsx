import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, AlertCircle, Eye, Trash2, Edit3, ClipboardList } from 'lucide-react';
import TopNavButtons from '../components/TopNavButtons';

const ListeConsultations = () => {
  const navigate = useNavigate();
  const [consultations, setConsultations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const handleDelete = async (item) => {
    if (!item.id) return;
    const isAoo = item.global_id?.startsWith('aoo_');
    const label = isAoo ? "l'appel d'offres" : "la consultation";
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer ${label} "${item.global_numero || ''}" ?`)) return;
    try {
      if (isAoo) {
        await api.delete(`/aoos/${item.id}`);
      } else {
        await api.delete(`/consultations/${item.id}`);
      }
      setConsultations(prev => prev.filter(c => c.global_id !== item.global_id));
    } catch (err) {
      console.error(err);
      alert(`Erreur lors de la suppression de ${label}.`);
    }
  };

  const getStatusBadge = (status) => {
    const baseClasses = 'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5 border';
    switch (status) {
      case 'Programmation':
        return <span className={`${baseClasses} bg-blue-50 text-blue-700 border-blue-200`}><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Programmation</span>;
      case 'En cours':
        return <span className={`${baseClasses} bg-amber-50 text-amber-800 border-amber-200`}><span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span>En cours</span>;
      case 'Validé':
        return <span className={`${baseClasses} bg-[#E7F5EF] text-[#087F5B] border-[#087F5B]/30`}><span className="w-1.5 h-1.5 rounded-full bg-[#087F5B]"></span>Validé</span>;
      case 'Clôturé':
        return <span className={`${baseClasses} bg-slate-100 text-slate-700 border-slate-300`}><span className="w-1.5 h-1.5 rounded-full bg-slate-500"></span>Clôturé</span>;
      default:
        return <span className={`${baseClasses} bg-slate-100 text-slate-700 border-slate-200`}>{status || 'Non défini'}</span>;
    }
  };

  useEffect(() => {
    const fetchAllDossiers = async () => {
      try {
        setLoading(true);
        const [consRes, aooRes] = await Promise.all([
          api.get('/consultations'),
          api.get('/aoos')
        ]);

        const formattedCons = consRes.data.map(c => {
          const isAo = c.mode_engagement === 'AO' || c.mode_engagement === "Appel d'offres" || c.mode_engagement === "Appel d'Offres" || c.mode_engagement === "Appel d'offre";
          const matchingAoo = aooRes.data.find(a => 
            (a.num_aoo && c.numero_consultation && a.num_aoo.trim().toLowerCase() === c.numero_consultation.trim().toLowerCase()) ||
            (a.id && c.aoo_id && String(a.id) === String(c.aoo_id))
          );

          let targetRoute = `/consultations/${c.id}`;
          if (isAo) {
            targetRoute = matchingAoo ? `/aoos/${matchingAoo.id}` : `/aoos/nouveau?consultation_id=${c.id}`;
          }

          return {
            ...c,
            global_id: `cons_${c.id}`,
            global_type: isAo ? "Appel d'offres" : (c.mode_engagement || 'Bon de commande'),
            global_numero: c.numero_consultation,
            global_objet: c.objet_consultation,
            global_budget: c.budget ? c.budget.montant_ttc : (c.montant_estimatif_ht || null),
            global_statut: c.statut_dossier,
            is_ao: isAo,
            matching_aoo_id: matchingAoo?.id,
            route: targetRoute
          };
        });

        // Filter out AOOs that already match a consultation to avoid visual duplicates in list
        const consAoNumbers = new Set(
          formattedCons
            .filter(c => c.is_ao && c.global_numero)
            .map(c => c.global_numero.trim().toLowerCase())
        );

        const formattedAoos = aooRes.data
          .filter(a => !a.num_aoo || !consAoNumbers.has(a.num_aoo.trim().toLowerCase()))
          .map(a => ({
            ...a,
            global_id: `aoo_${a.id}`,
            global_type: "Appel d'Offres",
            global_numero: a.num_aoo,
            global_objet: a.objet,
            global_budget: a.budget,
            global_statut: a.statut,
            is_ao: true,
            route: `/aoos/${a.id}`
          }));

        const merged = [...formattedCons, ...formattedAoos].sort((a, b) => {
          const dateA = new Date(a.created_at || a.date_consultation || a.date_preparation || 0);
          const dateB = new Date(b.created_at || b.date_consultation || b.date_preparation || 0);
          return dateB - dateA;
        });

        setConsultations(merged);
        setLoading(false);
      } catch (err) {
        console.error(err);
        setError('Impossible de charger la liste des dossiers.');
        setLoading(false);
      }
    };

    fetchAllDossiers();
  }, []);

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(value).replace('MAD', 'dh');
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="animate-spin text-primary" size={48} />
          <p className="text-gray-500 font-medium">Chargement des consultations...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="bg-red-50 border border-red-200 p-6 rounded-xl text-center">
          <AlertCircle className="mx-auto text-red-500 mb-2" size={32} />
          <p className="text-red-600 font-bold">{error}</p>
          <button 
            onClick={() => window.location.reload()}
            className="mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Réessayer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6 lg:p-8 bg-[#F5F7F5] min-h-screen text-[#172033]">
      <div className="w-full max-w-[1700px] mx-auto space-y-6">
        {/* Header bar */}
        <header className="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div className="flex items-start sm:items-center gap-3">
            <button 
              onClick={() => navigate('/dashboard')}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer shrink-0"
              title="Retour au tableau de bord"
            >
              <ArrowLeft size={16} />
              <span>Retour</span>
            </button>
            <div className="h-6 w-px bg-slate-200 hidden sm:block" />
            <div>
              <div className="flex items-center gap-2 flex-wrap">
                <span className="text-[10px] font-bold text-[#087F5B] bg-[#E7F5EF] px-2 py-0.5 rounded-full uppercase tracking-wider">DRCA · RSK</span>
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Marchés & Achats</span>
              </div>
              <h1 className="mt-1 text-2xl sm:text-3xl font-extrabold text-[#172033] tracking-tight">
                Gestion des Consultations
              </h1>
              <p className="mt-1 text-xs sm:text-sm text-slate-500">
                Registre centralisé de toutes les consultations enregistrées.
              </p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <TopNavButtons current="dashboard" />
            <Link 
              to="/consultations/nouvelle"
              className="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 bg-[#087F5B] hover:bg-[#06694A] text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-[#087F5B]/20 transition transform active:scale-98 cursor-pointer"
            >
              <Plus size={18} />
              <span>Nouvelle Consultation</span>
            </Link>
          </div>
        </header>

        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[#101B2D] border-b-2 border-[#087F5B] text-white">
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider">Réf. Consultation</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider">Année</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider">Objet</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider">Mode d'engagement</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider text-right">Budget TTC</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider text-center">Statut</th>
                  <th className="px-6 py-4 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {consultations.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-16 text-center text-slate-500">
                      <div className="flex flex-col items-center justify-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                          <ClipboardList size={24} />
                        </div>
                        <p className="font-semibold text-slate-700">Aucun dossier trouvé</p>
                        <p className="text-xs text-slate-400">Commencez par créer une nouvelle consultation en cliquant sur le bouton ci-dessus.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  consultations.map((consultation) => (
                    <tr key={consultation.global_id} className="hover:bg-[#F2FAF6] transition-colors">
                      <td className="px-6 py-4.5 whitespace-nowrap">
                        <span className="font-mono font-bold text-[#101B2D]">
                          {consultation.global_numero || 'N/A'}
                        </span>
                      </td>
                      <td className="px-6 py-4.5 whitespace-nowrap text-xs font-semibold text-slate-600">
                        {consultation.annee || new Date(consultation.created_at || Date.now()).getFullYear()}
                      </td>
                      <td className="px-6 py-4.5 max-w-md">
                        <p className="text-sm font-semibold text-slate-900 line-clamp-2" title={consultation.global_objet}>
                          {consultation.global_objet || 'Sans objet'}
                        </p>
                      </td>
                      <td className="px-6 py-4.5 whitespace-nowrap">
                        {consultation.global_type === 'AO' || consultation.global_type === "Appel d'offres" || consultation.global_type === "Appel d'Offres" ? (
                          <span className="inline-block rounded-full bg-violet-50 border border-violet-200 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-violet-700">
                            Appel d'offres
                          </span>
                        ) : (
                          <span className="inline-block rounded-full bg-[#E7F5EF] border border-[#087F5B]/25 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-[#087F5B]">
                            Bon de commande
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4.5 whitespace-nowrap font-mono font-bold text-slate-900 text-right tabular-nums">
                        {consultation.global_budget ? formatCurrency(consultation.global_budget) : 'N/A'}
                      </td>
                      <td className="px-6 py-4.5 whitespace-nowrap text-center">
                        {getStatusBadge(consultation.global_statut)}
                      </td>
                      <td className="px-6 py-4.5 whitespace-nowrap text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => {
                              if (consultation.is_ao || consultation.global_id?.startsWith('aoo_')) {
                                navigate(consultation.route, {
                                  state: {
                                    createdConsultation: consultation,
                                    autoSelectId: consultation.id,
                                    consultation_id: consultation.id,
                                  },
                                });
                              } else {
                                navigate(consultation.route);
                              }
                            }}
                            className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-[#E7F5EF] text-[#087F5B] hover:bg-[#d8efe5] transition cursor-pointer"
                            title="Aperçu / Voir détails"
                          >
                            <Eye size={16} />
                          </button>
                          <button
                            onClick={() => {
                              if (consultation.is_ao || consultation.global_id?.startsWith('aoo_')) {
                                navigate(consultation.route, {
                                  state: {
                                    createdConsultation: consultation,
                                    autoSelectId: consultation.id,
                                    consultation_id: consultation.id,
                                  },
                                });
                              } else {
                                navigate(`/bons-commande?consultation_id=${consultation.id}&edit=1`, {
                                  state: { autoSelectId: consultation.id, autoEditId: consultation.id },
                                });
                              }
                            }}
                            className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition cursor-pointer"
                            title="Modifier"
                          >
                            <Edit3 size={16} />
                          </button>
                          <button
                            onClick={() => handleDelete(consultation)}
                            className="inline-flex items-center justify-center h-8.5 w-8.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                            title="Supprimer"
                          >
                            <Trash2 size={16} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ListeConsultations;
