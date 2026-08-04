import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, AlertCircle, Eye } from 'lucide-react';

const ListeConsultations = () => {
  const navigate = useNavigate();
  const [consultations, setConsultations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchConsultations = async () => {
      try {
        setLoading(true);
        const res = await api.get('/consultations');
        // Trier par ID décroissant pour avoir les plus récentes en premier
        const sortedData = res.data.sort((a, b) => b.id - a.id);
        setConsultations(sortedData);
        setLoading(false);
      } catch (err) {
        console.error(err);
        setError('Impossible de charger la liste des consultations.');
        setLoading(false);
      }
    };

    fetchConsultations();
  }, []);

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(value);
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
    <div className="p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div className="max-w-7xl mx-auto">
        <header className="mb-8 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button 
                onClick={() => navigate('/dashboard')}
                className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-500"
              >
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
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
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Mode</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Budget TTC</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 dark:divide-gray-700">
                {consultations.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                      Aucune consultation trouvée.
                    </td>
                  </tr>
                ) : (
                  consultations.map((consultation) => (
                    <tr key={consultation.id} className="hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors">
                      <td className="px-6 py-5 whitespace-nowrap">
                        <span className="font-mono font-semibold text-primary dark:text-blue-400">
                          {consultation.numero_consultation}
                        </span>
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-300">
                        {consultation.annee}
                      </td>
                      <td className="px-6 py-5">
                        <p className="text-sm font-medium text-slate-900 dark:text-white line-clamp-2" title={consultation.objet_consultation}>
                          {consultation.objet_consultation}
                        </p>
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap">
                        <span className="text-sm font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg inline-block">
                          {consultation.mode_engagement}
                        </span>
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap font-mono font-bold text-slate-900 dark:text-slate-100">
                        {consultation.budget ? formatCurrency(consultation.budget.montant_ttc) : 'N/A'}
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap">
                        {getStatusBadge(consultation.statut_dossier)}
                      </td>
                      <td className="px-6 py-5 whitespace-nowrap text-right">
                        <Link 
                          to={`/consultations/${consultation.id}`}
                          className="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                          title="Voir détails"
                        >
                          <Eye size={18} />
                        </Link>
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
