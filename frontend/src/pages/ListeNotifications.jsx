import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, Plus, Eye, Landmark, AlertCircle } from 'lucide-react';

const ListeNotifications = () => {
  const navigate = useNavigate();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchNotifications = async () => {
      try {
        const response = await api.get('/notifications');
        setNotifications(response.data);
      } catch (err) {
        console.error(err);
        setError('Impossible de charger les notifications.');
      } finally {
        setLoading(false);
      }
    };
    fetchNotifications();
  }, []);

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(value).replace('MAD', 'dh');
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <Loader2 className="animate-spin text-blue-600" size={48} />
      </div>
    );
  }

  return (
    <div className="p-8 bg-slate-50 min-h-screen">
      <div className="max-w-7xl mx-auto">
        <header className="mb-8 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <button onClick={() => navigate('/dashboard')} className="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-500">
                <ArrowLeft size={20} />
              </button>
              <h1 className="text-3xl font-extrabold text-[#1e3a8a] flex items-center gap-2">
                <Landmark /> Notifications Budgétaires
              </h1>
            </div>
            <p className="text-slate-500 ml-11">Gestion des enveloppes budgétaires par exercice.</p>
          </div>
          <Link to="/notifications/nouvelle" className="px-6 py-3 bg-[#1e40af] text-white font-bold rounded-xl shadow-sm hover:bg-[#1e3a8a] transition-all flex items-center gap-2">
            <Plus size={20} /> Nouvelle Notification
          </Link>
        </header>

        {error && (
          <div className="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-2">
            <AlertCircle size={20} /> {error}
          </div>
        )}

        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200">
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Numéro</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Exercice</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Date</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Montant Global</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Lignes</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase">Total Crédits</th>
                  <th className="px-6 py-5 text-xs font-bold text-slate-600 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {notifications.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-12 text-center text-slate-500">Aucune notification enregistrée.</td>
                  </tr>
                ) : (
                  notifications.map((notification) => {
                    const totalCredits = notification.lignes?.reduce((acc, l) => acc + Number(l.total_credits), 0) || 0;
                    return (
                      <tr key={notification.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-6 py-5 font-mono font-bold text-[#1e3a8a]">{notification.numero}</td>
                        <td className="px-6 py-5 text-slate-700 font-semibold">{notification.exercice}</td>
                        <td className="px-6 py-5 text-slate-600">
                          {new Date(notification.date_notification).toLocaleDateString('fr-FR')}
                        </td>
                        <td className="px-6 py-5 font-mono text-slate-700">{notification.montant ? formatCurrency(notification.montant) : '-'}</td>
                        <td className="px-6 py-5 text-slate-600">
                          <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-xs font-bold">{notification.lignes?.length || 0} lignes</span>
                        </td>
                        <td className="px-6 py-5 font-mono font-bold text-slate-900">{formatCurrency(totalCredits)}</td>
                        <td className="px-6 py-5 text-right">
                          <Link to={`/notifications/${notification.id}`} className="inline-flex items-center justify-center p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
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
      </div>
    </div>
  );
};

export default ListeNotifications;
