import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, AlertCircle, Eye, Trash2, Edit3 } from 'lucide-react';

const ListeConsultations = () => {
  const navigate = useNavigate();
  const [consultations, setConsultations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const handleDelete = async (item) => {
    if (!item.id || item.global_id.startsWith('aoo_')) return;
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer la consultation "${item.global_numero}" ?`)) return;
    try {
      await api.delete(`/consultations/${item.id}`);
      setConsultations(prev => prev.filter(c => c.global_id !== item.global_id));
    } catch (err) {
      alert("Erreur lors de la suppression de la consultation.");
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

        const formattedCons = consRes.data.map(c => ({
          ...c,
          global_id: `cons_${c.id}`,
          global_type: c.mode_engagement || 'Convention',
          global_numero: c.numero_consultation,
          global_objet: c.objet_consultation,
          global_budget: c.budget ? c.budget.montant_ttc : null,
          global_statut: c.statut_dossier,
          route: `/consultations/${c.id}`
        }));

        const formattedAoos = aooRes.data.map(a => ({
          ...a,
          global_id: `aoo_${a.id}`,
          global_type: "Appel d'Offres",
          global_numero: a.num_aoo,
          global_objet: a.objet,
          global_budget: a.budget,
          global_statut: a.statut,
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

  const getStatusBadge = (status) => {
    const baseClasses = 'px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide inline-block';
    switch (status) {
      case 'Programmation':
        return <span className={`${baseClasses} bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300`}>Programmation</span>;
      case 'En cours':
        return <span className={`${baseClasses} bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300`}>En cours</span>;
      case 'Validé':
        return <span className={`${baseClasses} bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300`}>Validé</span>;
      case 'Clôturé':
        return <span className={`${baseClasses} bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300`}>Clôturé</span>;
      default:
        return <span className={`${baseClasses} bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300`}>{status}</span>;
    }
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
    <div className="p-3 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div className="w-full">
        <header className="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button 
                onClick={() => navigate('/dashboard')}
                className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Gestion des Consultations
              </h1>
            </div>
            <p className="text-gray-500 dark:text-gray-400 ml-11">
              Liste complète de toutes les consultations enregistrées.
            </p>
          </div>
          <Link 
            to="/consultations/nouvelle"
            className="px-6 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:shadow-xl hover:bg-primary-dark hover:-translate-y-1 transition-all duration-300 flex items-center gap-2"
          >
            <Plus size={20} />
            Nouvelle Consultation
          </Link>
        </header>

        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 dark:bg-gray-800/50 border-b border-slate-200 dark:border-gray-700">
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Réf. Consultation</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Année</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Objet</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Mode d'engagement</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Budget TTC</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 dark:divide-gray-700">
                {consultations.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                      Aucun dossier trouvé.
                    </td>
                  </tr>
                ) : (
                  consultations.map((consultation) => (
                    <tr key={consultation.global_id} className="hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors">
                      <td className="px-6 py-5 whitespace-nowrap">
                        <span className="font-mono font-semibold text-primary dark:text-blue-400">
                          {consultation.global_numero || 'N/A'}
                        </span>
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-300">
                        {consultation.annee || new Date(consultation.created_at || Date.now()).getFullYear()}
                      </td>
                      <td className="px-6 py-5">
                        <p className="text-sm font-medium text-slate-900 dark:text-white line-clamp-2" title={consultation.global_objet}>
                          {consultation.global_objet || 'N/A'}
                        </p>
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap">
                        {consultation.global_type === 'AO' || consultation.global_type === "Appel d'offres" || consultation.global_type === "Appel d'Offres" ? (
                          <span className="inline-block rounded-full bg-violet-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-violet-800 dark:bg-violet-900/30 dark:text-violet-300">
                            Appel d'offres
                          </span>
                        ) : (
                          <span className="inline-block rounded-full bg-cyan-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">
                            Bon de commande
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap font-mono font-bold text-slate-900 dark:text-slate-100">
                        {consultation.global_budget ? formatCurrency(consultation.global_budget) : 'N/A'}
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap">
                        {getStatusBadge(consultation.global_statut)}
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Link 
                            to={consultation.route}
                            className="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                            title="Voir détails"
                          >
                            <Eye size={18} />
                          </Link>
                          {!consultation.global_id.startsWith('aoo_') && (
                            <button
                              onClick={() => navigate(`/bons-commande?consultation_id=${consultation.id}&edit=1`, {
                                state: { autoSelectId: consultation.id, autoEditId: consultation.id },
                              })}
                              className="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50 transition-colors"
                              title="Modifier la consultation"
                            >
                              <Edit3 size={18} />
                            </button>
                          )}
                          {!consultation.global_id.startsWith('aoo_') && (
                            <button
                              onClick={() => handleDelete(consultation)}
                              className="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                              title="Supprimer la consultation"
                            >
                              <Trash2 size={18} />
                            </button>
                          )}
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
