import { useEffect, useState } from 'react';
import { Users, CheckCircle, XCircle, Loader2, X, RefreshCw } from 'lucide-react';
import api from '../api/axios';

export default function GestionUtilisateursModal({ isOpen, onClose }) {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState(null);
  const [error, setError] = useState('');

  const fetchUsers = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get('/users');
      setUsers(Array.isArray(res.data) ? res.data : []);
    } catch (err) {
      setError('Impossible de récupérer la liste des utilisateurs.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchUsers();
    }
  }, [isOpen]);

  const handleToggle = async (id) => {
    setUpdatingId(id);
    try {
      const res = await api.patch(`/users/${id}/toggle-status`);
      setUsers((prev) =>
        prev.map((u) => (u.id === id ? { ...u, is_active: res.data.user.is_active } : u))
      );
    } catch (err) {
      alert("Erreur lors de la modification de l'état du compte.");
    } finally {
      setUpdatingId(null);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 text-blue-700 rounded-xl">
              <Users size={22} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">Gestion des Utilisateurs</h2>
              <p className="text-xs text-slate-500 font-medium">
                Total : <span className="font-bold text-slate-800">{users.length}</span> utilisateur(s)
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={fetchUsers}
              className="p-2 text-slate-500 hover:bg-slate-200 rounded-lg transition-colors"
              title="Actualiser"
            >
              <RefreshCw size={18} className={loading ? 'animate-spin' : ''} />
            </button>
            <button
              onClick={onClose}
              className="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-lg transition-colors"
            >
              <X size={20} />
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto flex-1">
          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
              {error}
            </div>
          )}

          {loading ? (
            <div className="py-12 flex flex-col items-center justify-center text-slate-400 gap-3">
              <Loader2 size={28} className="animate-spin text-blue-600" />
              <p className="text-sm font-medium">Chargement des utilisateurs...</p>
            </div>
          ) : users.length === 0 ? (
            <p className="text-center py-10 text-sm text-slate-500 font-semibold">
              Aucun utilisateur trouvé.
            </p>
          ) : (
            <div className="overflow-x-auto border border-slate-200 rounded-xl">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                  <tr>
                    <th className="px-4 py-3">Nom</th>
                    <th className="px-4 py-3">Email</th>
                    <th className="px-4 py-3">Rôle</th>
                    <th className="px-4 py-3 text-center">État du compte</th>
                    <th className="px-4 py-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {users.map((u) => {
                    const isActive = Boolean(u.is_active);
                    return (
                      <tr key={u.id} className="hover:bg-slate-50/60 transition-colors">
                        <td className="px-4 py-3.5 font-bold text-slate-800">{u.name || '-'}</td>
                        <td className="px-4 py-3.5 text-slate-600 font-mono">{u.email}</td>
                        <td className="px-4 py-3.5">
                          <span className="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 font-semibold">
                            {u.role || 'Utilisateur'}
                          </span>
                        </td>
                        <td className="px-4 py-3.5 text-center">
                          <span
                            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold ${
                              isActive
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-red-100 text-red-800'
                            }`}
                          >
                            {isActive ? (
                              <>
                                <CheckCircle size={13} /> Actif
                              </>
                            ) : (
                              <>
                                <XCircle size={13} /> Désactivé
                              </>
                            )}
                          </span>
                        </td>
                        <td className="px-4 py-3.5 text-right">
                          <button
                            type="button"
                            disabled={updatingId === u.id}
                            onClick={() => handleToggle(u.id)}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1 ${
                              isActive
                                ? 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white'
                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white'
                            }`}
                          >
                            {updatingId === u.id && <Loader2 size={12} className="animate-spin" />}
                            {isActive ? 'Désactiver' : 'Activer'}
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}