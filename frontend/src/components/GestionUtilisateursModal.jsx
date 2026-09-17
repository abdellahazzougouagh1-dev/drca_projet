import { useEffect, useMemo, useState } from 'react';
import {
  Users, CheckCircle2, XCircle, Loader2, X, RefreshCw,
  Search, ShieldAlert, ShieldCheck, UserCheck, UserX, UserCheck2,
  Calendar, Mail, User
} from 'lucide-react';
import api from '../api/axios';

export default function GestionUtilisateursModal({ isOpen, onClose }) {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState(null);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL'); // 'ALL' | 'ACTIVE' | 'INACTIVE'

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

  const activeCount = useMemo(() => users.filter((u) => Boolean(u.is_active)).length, [users]);
  const inactiveCount = useMemo(() => users.filter((u) => !Boolean(u.is_active)).length, [users]);

  const filteredUsers = useMemo(() => {
    return users.filter((u) => {
      const matchesSearch =
        (u.name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
        (u.email || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
        (u.role || '').toLowerCase().includes(searchQuery.toLowerCase());

      if (!matchesSearch) return false;

      if (statusFilter === 'ACTIVE') return Boolean(u.is_active);
      if (statusFilter === 'INACTIVE') return !Boolean(u.is_active);
      return true;
    });
  }, [users, searchQuery, statusFilter]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full h-full max-w-[99vw] max-h-[96vh] overflow-hidden flex flex-col transition-all">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4 bg-slate-50/80 shrink-0">
          <div className="flex items-center gap-3.5">
            <div className="p-2.5 bg-blue-600 text-white rounded-xl shadow-md shadow-blue-500/20">
              <Users size={24} />
            </div>
            <div>
              <div className="flex items-center gap-2.5">
                <h2 className="text-xl font-black text-slate-900 tracking-tight">Gestion des Utilisateurs</h2>
                <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                  Plein écran
                </span>
              </div>
              <p className="text-xs text-slate-500 font-medium mt-0.5">
                Validez ou désactivez les accès aux modules de la plateforme régionale.
              </p>
            </div>
          </div>

          {/* Quick Metrics */}
          <div className="flex items-center gap-3">
            <div className="hidden sm:flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs">
              <span className="text-xs text-slate-500 font-medium">Total:</span>
              <span className="text-xs font-bold text-slate-800">{users.length}</span>
            </div>
            <div className="hidden sm:flex items-center gap-2 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
              <span className="h-2 w-2 rounded-full bg-emerald-500" />
              <span className="text-xs font-bold text-emerald-800">{activeCount} Actif(s)</span>
            </div>
            {inactiveCount > 0 && (
              <div className="flex items-center gap-2 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200">
                <span className="h-2 w-2 rounded-full bg-amber-500 animate-pulse" />
                <span className="text-xs font-bold text-amber-800">{inactiveCount} En attente / Désactivé(s)</span>
              </div>
            )}

            <button
              onClick={fetchUsers}
              className="p-2 text-slate-500 hover:text-blue-600 hover:bg-slate-200/70 rounded-xl transition-colors"
              title="Actualiser la liste"
            >
              <RefreshCw size={18} className={loading ? 'animate-spin text-blue-600' : ''} />
            </button>
            <button
              onClick={onClose}
              className="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 rounded-xl transition-colors"
              title="Fermer"
            >
              <X size={22} />
            </button>
          </div>
        </div>

        {/* Action & Filter Toolbar */}
        <div className="px-6 py-3 border-b border-slate-200 bg-white flex flex-wrap items-center justify-between gap-3 shrink-0">
          <div className="relative flex-1 min-w-[240px] max-w-md">
            <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              placeholder="Rechercher par nom, email ou rôle..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                <X size={14} />
              </button>
            )}
          </div>

          <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200">
            <button
              type="button"
              onClick={() => setStatusFilter('ALL')}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                statusFilter === 'ALL'
                  ? 'bg-white text-slate-900 shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Tous ({users.length})
            </button>
            <button
              type="button"
              onClick={() => setStatusFilter('INACTIVE')}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 ${
                statusFilter === 'INACTIVE'
                  ? 'bg-amber-600 text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <ShieldAlert size={13} />
              En attente / Désactivés ({inactiveCount})
            </button>
            <button
              type="button"
              onClick={() => setStatusFilter('ACTIVE')}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 ${
                statusFilter === 'ACTIVE'
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <ShieldCheck size={13} />
              Actifs ({activeCount})
            </button>
          </div>
        </div>

        {/* Content Table */}
        <div className="p-6 overflow-y-auto flex-1 bg-slate-50/40">
          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
              {error}
            </div>
          )}

          {loading ? (
            <div className="py-20 flex flex-col items-center justify-center text-slate-400 gap-3">
              <Loader2 size={32} className="animate-spin text-blue-600" />
              <p className="text-sm font-semibold">Chargement des utilisateurs en cours...</p>
            </div>
          ) : filteredUsers.length === 0 ? (
            <div className="py-20 flex flex-col items-center justify-center text-center">
              <div className="p-4 bg-slate-100 rounded-full text-slate-400 mb-3">
                <Users size={32} />
              </div>
              <p className="text-base font-bold text-slate-700">Aucun utilisateur trouvé</p>
              <p className="text-xs text-slate-400 mt-1 max-w-sm">
                {searchQuery || statusFilter !== 'ALL'
                  ? "Aucun résultat ne correspond aux filtres appliqués."
                  : "Aucun compte utilisateur n'est encore enregistré dans le système."}
              </p>
            </div>
          ) : (
            <div className="overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-xs">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                  <tr>
                    <th className="px-6 py-3.5">Utilisateur</th>
                    <th className="px-6 py-3.5">Email</th>
                    <th className="px-6 py-3.5">Rôle</th>
                    <th className="px-6 py-3.5">Date d'inscription</th>
                    <th className="px-6 py-3.5 text-center">État du compte</th>
                    <th className="px-6 py-3.5 text-right">Décision Directeur</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {filteredUsers.map((u) => {
                    const isActive = Boolean(u.is_active);
                    const initials = (u.name || 'U')
                      .split(' ')
                      .map((n) => n[0])
                      .slice(0, 2)
                      .join('')
                      .toUpperCase();

                    return (
                      <tr
                        key={u.id}
                        className={`hover:bg-slate-50/80 transition-colors ${
                          !isActive ? 'bg-amber-50/20' : ''
                        }`}
                      >
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div
                              className={`h-9 w-9 rounded-xl flex items-center justify-center text-xs font-bold shadow-xs ${
                                isActive
                                  ? 'bg-blue-100 text-blue-700'
                                  : 'bg-amber-100 text-amber-800'
                              }`}
                            >
                              {initials}
                            </div>
                            <div>
                              <p className="font-bold text-slate-900 text-sm">{u.name || 'Utilisateur'}</p>
                              <p className="text-[11px] text-slate-400">ID #{u.id}</p>
                            </div>
                          </div>
                        </td>

                        <td className="px-6 py-4">
                          <div className="flex items-center gap-1.5 text-slate-600 font-mono text-xs">
                            <Mail size={13} className="text-slate-400 shrink-0" />
                            <span>{u.email}</span>
                          </div>
                        </td>

                        <td className="px-6 py-4">
                          <span
                            className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold capitalize ${
                              u.role === 'directeur'
                                ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                : 'bg-slate-100 text-slate-700 border border-slate-200'
                            }`}
                          >
                            <User size={12} />
                            {u.role || 'Gestionnaire'}
                          </span>
                        </td>

                        <td className="px-6 py-4 text-slate-500">
                          <div className="flex items-center gap-1.5 text-xs">
                            <Calendar size={13} className="text-slate-400" />
                            <span>
                              {u.created_at
                                ? new Date(u.created_at).toLocaleDateString('fr-FR', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                  })
                                : '—'}
                            </span>
                          </div>
                        </td>

                        <td className="px-6 py-4 text-center">
                          <span
                            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ${
                              isActive
                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                : 'bg-red-100 text-red-800 border border-red-200'
                            }`}
                          >
                            {isActive ? (
                              <>
                                <CheckCircle2 size={14} className="text-emerald-600" />
                                <span>Actif / Validé</span>
                              </>
                            ) : (
                              <>
                                <XCircle size={14} className="text-red-600" />
                                <span>Désactivé / En attente</span>
                              </>
                            )}
                          </span>
                        </td>

                        <td className="px-6 py-4 text-right">
                          <button
                            type="button"
                            disabled={updatingId === u.id}
                            onClick={() => handleToggle(u.id)}
                            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1.5 cursor-pointer ${
                              isActive
                                ? 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white hover:shadow-md'
                                : 'bg-emerald-600 text-white hover:bg-emerald-700 hover:shadow-md hover:shadow-emerald-600/20'
                            }`}
                          >
                            {updatingId === u.id ? (
                              <Loader2 size={14} className="animate-spin" />
                            ) : isActive ? (
                              <>
                                <UserX size={14} />
                                <span>Désactiver l'accès</span>
                              </>
                            ) : (
                              <>
                                <UserCheck size={14} />
                                <span>Accepter & Activer</span>
                              </>
                            )}
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

        {/* Modal Footer */}
        <div className="px-6 py-3.5 border-t border-slate-200 bg-white flex items-center justify-between text-xs text-slate-500 shrink-0">
          <div>
            Affichage de <span className="font-bold text-slate-800">{filteredUsers.length}</span> sur <span className="font-bold text-slate-800">{users.length}</span> utilisateur(s)
          </div>
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs transition shadow-xs"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
}