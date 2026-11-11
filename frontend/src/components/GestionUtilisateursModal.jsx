import { useEffect, useMemo, useState } from 'react';
import {
  Users, CheckCircle2, XCircle, Loader2, X, RefreshCw,
  Search, ShieldAlert, ShieldCheck, UserCheck, UserX,
  Calendar, Mail, User, Trash2, Lock, AlertTriangle
} from 'lucide-react';
import api from '../api/axios';
import { useAuth } from '../context/AuthContext';

export default function GestionUtilisateursModal({ isOpen, onClose }) {
  const { currentUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState(null);
  const [deletingId, setDeletingId] = useState(null);
  const [confirmDeleteUser, setConfirmDeleteUser] = useState(null);
  const [deleteError, setDeleteError] = useState('');
  const [feedbackMessage, setFeedbackMessage] = useState('');
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL'); // 'ALL' | 'ACTIVE' | 'INACTIVE'

  const fetchUsers = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get('/users');
      setUsers(Array.isArray(res.data) ? res.data : []);
    } catch {
      setError('Impossible de récupérer la liste des utilisateurs.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!isOpen) return;
    let cancelled = false;
    api
      .get('/users')
      .then((res) => {
        if (!cancelled) {
          setUsers(Array.isArray(res.data) ? res.data : []);
          setError('');
          setConfirmDeleteUser(null);
          setDeleteError('');
          setFeedbackMessage('');
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Impossible de récupérer la liste des utilisateurs.');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [isOpen]);

  const handleCloseModal = () => {
    setConfirmDeleteUser(null);
    setDeleteError('');
    setFeedbackMessage('');
    setError('');
    onClose?.();
  };

  const handleToggle = async (id) => {
    setUpdatingId(id);
    setError('');
    setFeedbackMessage('');
    try {
      const res = await api.patch(`/users/${id}/toggle-status`);
      setUsers((prev) =>
        prev.map((u) => (u.id === id ? { ...u, is_active: res.data.user.is_active } : u))
      );
    } catch (err) {
      setError(err?.response?.data?.message || "Erreur lors de la modification de l'état du compte.");
    } finally {
      setUpdatingId(null);
    }
  };

  const handleConfirmDelete = async () => {
    if (!confirmDeleteUser) return;

    const targetRole = (confirmDeleteUser.role || '').trim().toLowerCase();
    const isTargetCurrent = currentUser?.id != null && Number(currentUser.id) === Number(confirmDeleteUser.id);
    if (targetRole === 'directeur') {
      setDeleteError('Impossible de supprimer un compte Directeur.');
      return;
    }
    if (isTargetCurrent) {
      setDeleteError('Vous ne pouvez pas supprimer votre propre compte.');
      return;
    }

    setDeletingId(confirmDeleteUser.id);
    setDeleteError('');
    setError('');
    setFeedbackMessage('');

    try {
      await api.delete(`/users/${confirmDeleteUser.id}`);
      const deletedLabel = confirmDeleteUser.name || confirmDeleteUser.email;
      setUsers((prev) => prev.filter((item) => item.id !== confirmDeleteUser.id));
      setFeedbackMessage(`Le compte « ${deletedLabel} » a été supprimé avec succès.`);
      setConfirmDeleteUser(null);
    } catch (err) {
      setDeleteError(
        err?.response?.data?.message || 'Impossible de supprimer cet utilisateur.'
      );
    } finally {
      setDeletingId(null);
    }
  };

  const activeCount = useMemo(() => users.filter((u) => Boolean(u.is_active)).length, [users]);
  const inactiveCount = useMemo(() => users.filter((u) => !u.is_active).length, [users]);

  const filteredUsers = useMemo(() => {
    return users.filter((u) => {
      const matchesSearch =
        (u.name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
        (u.email || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
        (u.role || '').toLowerCase().includes(searchQuery.toLowerCase());

      if (!matchesSearch) return false;

      if (statusFilter === 'ACTIVE') return Boolean(u.is_active);
      if (statusFilter === 'INACTIVE') return !u.is_active;
      return true;
    });
  }, [users, searchQuery, statusFilter]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full h-full max-w-[99vw] max-h-[96vh] overflow-hidden flex flex-col transition-all">
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
              onClick={handleCloseModal}
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

          {feedbackMessage && (
            <div className="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center justify-between gap-2">
              <span className="flex items-center gap-2">
                <CheckCircle2 size={15} className="text-emerald-600 shrink-0" />
                {feedbackMessage}
              </span>
              <button
                type="button"
                onClick={() => setFeedbackMessage('')}
                className="text-emerald-700 hover:text-emerald-900 cursor-pointer"
                aria-label="Fermer le message"
              >
                <X size={14} />
              </button>
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
            <div className="overflow-x-auto border border-slate-200 rounded-2xl bg-white shadow-xs">
              <table className="w-full min-w-[820px] text-left text-xs">
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
                    const isDirector = (u.role || '').trim().toLowerCase() === 'directeur';
                    const isCurrentUser =
                      currentUser?.id != null && Number(currentUser.id) === Number(u.id);
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
                            {u.avatar ? (
                              <img
                                src={u.avatar}
                                alt={u.name || 'Utilisateur'}
                                className="h-9 w-9 rounded-xl object-cover border border-slate-200 shadow-xs shrink-0"
                              />
                            ) : (
                              <div
                                className={`h-9 w-9 rounded-xl flex items-center justify-center text-xs font-bold shadow-xs shrink-0 ${
                                  isActive
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-amber-100 text-amber-800'
                                }`}
                              >
                                {initials}
                              </div>
                            )}
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
                              isDirector
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
                          <div className="inline-flex items-center justify-end gap-2">
                            <button
                              type="button"
                              disabled={updatingId === u.id || deletingId === u.id}
                              onClick={() => handleToggle(u.id)}
                              className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 ${
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

                            {!isDirector && !isCurrentUser ? (
                              <button
                                type="button"
                                disabled={updatingId === u.id || deletingId === u.id}
                                onClick={() => {
                                  setDeleteError('');
                                  setConfirmDeleteUser(u);
                                }}
                                title="Supprimer cet utilisateur"
                                className="px-3 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-600 hover:text-white hover:shadow-md transition-all shadow-xs inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                              >
                                {deletingId === u.id ? (
                                  <Loader2 size={14} className="animate-spin" />
                                ) : (
                                  <Trash2 size={14} />
                                )}
                                <span>Supprimer</span>
                              </button>
                            ) : (
                              <span
                                title={
                                  isDirector
                                    ? 'Le compte Directeur ne peut pas être supprimé'
                                    : 'Vous ne pouvez pas supprimer votre propre compte'
                                }
                                className={`inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border select-none ${
                                  isDirector
                                    ? 'bg-purple-50 text-purple-700 border-purple-200'
                                    : 'bg-slate-100 text-slate-500 border-slate-200'
                                }`}
                              >
                                <Lock size={13} />
                                <span>Protégé</span>
                              </span>
                            )}
                          </div>
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
            onClick={handleCloseModal}
            className="px-5 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs transition shadow-xs"
          >
            Fermer
          </button>
        </div>

        {/* Confirmation de suppression */}
        {confirmDeleteUser && (
          <div
            onClick={(e) => {
              if (e.target === e.currentTarget && deletingId === null) {
                setConfirmDeleteUser(null);
                setDeleteError('');
              }
            }}
            className="absolute inset-0 z-20 flex items-center justify-center bg-slate-950/50 backdrop-blur-[2px] p-4"
          >
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
              <div className="flex items-start gap-3.5">
                <div className="shrink-0 rounded-xl bg-rose-100 p-2.5 text-rose-600">
                  <AlertTriangle size={20} />
                </div>
                <div className="min-w-0 flex-1">
                  <h3 className="text-base font-extrabold text-slate-900">
                    Supprimer cet utilisateur ?
                  </h3>
                  <p className="mt-1.5 text-xs text-slate-600 leading-relaxed">
                    Vous êtes sur le point de supprimer définitivement le compte de{' '}
                    <span className="font-bold text-slate-900">
                      {confirmDeleteUser.name || 'cet utilisateur'}
                    </span>{' '}
                    (<span className="font-mono text-slate-700">{confirmDeleteUser.email}</span>).
                    Cette action est irréversible.
                  </p>
                </div>
              </div>

              {deleteError && (
                <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-semibold text-rose-700">
                  {deleteError}
                </div>
              )}

              <div className="mt-6 flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  disabled={deletingId !== null}
                  onClick={() => {
                    setConfirmDeleteUser(null);
                    setDeleteError('');
                  }}
                  className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 cursor-pointer"
                >
                  Annuler
                </button>
                <button
                  type="button"
                  disabled={deletingId !== null}
                  onClick={handleConfirmDelete}
                  className="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50 cursor-pointer"
                >
                  {deletingId !== null ? (
                    <>
                      <Loader2 size={14} className="animate-spin" />
                      <span>Suppression...</span>
                    </>
                  ) : (
                    <>
                      <Trash2 size={14} />
                      <span>Confirmer la suppression</span>
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}