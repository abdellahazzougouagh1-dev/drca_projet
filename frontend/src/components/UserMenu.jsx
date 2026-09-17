import React, { useState, useEffect, useRef, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import useClickOutside from '../hooks/useClickOutside';
import {
  User,
  Shield,
  KeyRound,
  HelpCircle,
  LogOut,
  ChevronUp,
  Bell,
  Check,
  X,
  Mail,
  Building2,
  Phone,
  Lock,
  Loader2,
  AlertCircle,
  CheckCircle2,
  Sparkles,
} from 'lucide-react';
import api from '../api/axios';

/**
 * Génère un dégradé de couleur harmonieux et déterministe basé sur le nom
 */
const getAvatarGradient = (name = '') => {
  const gradients = [
    'from-blue-600 via-indigo-600 to-violet-700',
    'from-emerald-500 via-teal-600 to-cyan-700',
    'from-purple-600 via-fuchsia-600 to-pink-600',
    'from-amber-500 via-orange-600 to-rose-600',
    'from-cyan-600 via-blue-600 to-indigo-700',
  ];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return gradients[Math.abs(hash) % gradients.length];
};

/**
 * Extrait les initiales (2 lettres max)
 */
const getInitials = (name = '') => {
  if (!name || typeof name !== 'string') return 'DR';
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return 'DR';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

export default function UserMenu({ className = '' }) {
  const { currentUser, logout, setCurrentUser } = useAuth();
  const navigate = useNavigate();

  // Menu Open State
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef(null);

  // Sub-modals states
  const [activeModal, setActiveModal] = useState(null); // 'profile' | 'password' | 'support' | null

  // User Status: 'online' | 'away' | 'offline'
  const [status, setStatus] = useState(() => {
    return localStorage.getItem('user_status') || 'online';
  });

  // Notifications Count (mock/dynamic)
  const [unreadCount] = useState(3);

  // Password Modal form
  const [passwordForm, setPasswordForm] = useState({ current: '', new: '', confirm: '' });
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  // Close when clicking outside
  useClickOutside(containerRef, () => setIsOpen(false), isOpen);

  // Keyboard shortcut Ctrl+K / Cmd+K and Escape
  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        setIsOpen((prev) => !prev);
      } else if (e.key === 'Escape') {
        if (activeModal) {
          setActiveModal(null);
        } else {
          setIsOpen(false);
        }
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeModal]);

  // Status change handler
  const handleStatusChange = (newStatus) => {
    setStatus(newStatus);
    localStorage.setItem('user_status', newStatus);
  };

  // Password update submit
  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    setPasswordError('');
    setPasswordSuccess('');

    if (passwordForm.new !== passwordForm.confirm) {
      setPasswordError('Le nouveau mot de passe et sa confirmation ne correspondent pas.');
      return;
    }
    if (passwordForm.new.length < 6) {
      setPasswordError('Le nouveau mot de passe doit comporter au moins 6 caractères.');
      return;
    }

    try {
      setPasswordLoading(true);
      await api.post('/change-password', {
        current_password: passwordForm.current,
        new_password: passwordForm.new,
      });
      setPasswordLoading(false);
      setPasswordSuccess('Mot de passe modifié avec succès !');
      setPasswordForm({ current: '', new: '', confirm: '' });
      setTimeout(() => setActiveModal(null), 1500);
    } catch (err) {
      setPasswordLoading(false);
      setPasswordError(
        err.response?.data?.message ||
        'Erreur lors du changement de mot de passe.'
      );
    }
  };

  // User display infos
  const userName = currentUser?.name || 'Directeur Régional';
  const userEmail = currentUser?.email || 'directeur@onca.ma';
  const userRole =
    currentUser?.role === 'directeur'
      ? 'Directeur Régional'
      : currentUser?.role
        ? currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1)
        : 'Accès décisionnel';
  const userInitials = useMemo(() => getInitials(userName), [userName]);
  const avatarGradient = useMemo(() => getAvatarGradient(userName), [userName]);

  // Status configuration
  const statusConfig = {
    online: { label: 'En ligne', color: 'bg-emerald-500', ring: 'ring-emerald-400/30' },
    away: { label: 'Absent', color: 'bg-amber-500', ring: 'ring-amber-400/30' },
    offline: { label: 'Hors ligne', color: 'bg-slate-400', ring: 'ring-slate-300/30' },
  };

  return (
    <>
      {/* Conteneur principal UserMenu en bas de sidebar */}
      <div ref={containerRef} className={`relative w-full ${className}`}>
        {/* BOUTON PRINCIPAL */}
        <button
          type="button"
          onClick={() => setIsOpen((prev) => !prev)}
          aria-expanded={isOpen}
          aria-haspopup="true"
          aria-label="Menu de profil utilisateur"
          className={`group flex w-full items-center justify-between gap-3 rounded-2xl border p-2.5 sm:p-3 text-left transition-all duration-300 cursor-pointer select-none ${isOpen
              ? 'border-blue-500/80 bg-slate-800/90 shadow-lg shadow-blue-950/40 ring-2 ring-blue-500/20'
              : 'border-slate-700/70 bg-slate-800/60 hover:border-slate-600 hover:bg-slate-800/90 shadow-sm'
            }`}
        >
          <div className="flex items-center gap-3 min-w-0 flex-1">
            {/* Avatar avec statut */}
            <div className="relative shrink-0">
              <div
                className={`grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br ${avatarGradient} text-sm font-extrabold text-white shadow-md shadow-slate-900/50 transition-transform group-hover:scale-105`}
              >
                {userInitials}
              </div>
              {/* Point de statut */}
              <span
                title={statusConfig[status].label}
                className={`absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-[#101b33] ${statusConfig[status].color} ring-2 ${statusConfig[status].ring}`}
              />
            </div>

            {/* Informations Nom + Rôle */}
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-1.5">
                <p
                  className="truncate text-sm font-bold text-white transition-colors group-hover:text-blue-300"
                  title={userName}
                >
                  {userName}
                </p>
              </div>
              <p className="truncate text-xs font-medium text-slate-400 capitalize">
                {userRole}
              </p>
            </div>
          </div>

          {/* Badge de notifications + Chevron interactif */}
          <div className="flex items-center gap-2 shrink-0">
            {unreadCount > 0 && (
              <span
                title={`${unreadCount} notifications en attente`}
                className="relative flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-[10px] font-black text-white shadow-sm"
              >
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-60" />
                {unreadCount}
              </span>
            )}
            <div
              className={`text-slate-400 transition-transform duration-300 group-hover:text-white ${isOpen ? 'rotate-180 text-blue-400' : ''
                }`}
            >
              <ChevronUp size={16} />
            </div>
          </div>
        </button>

        {/* MENU DÉROULANT (S'OUVRE VERS LE HAUT) */}
        {isOpen && (
          <div
            role="menu"
            aria-orientation="vertical"
            className="absolute bottom-full left-0 right-0 z-50 mb-2 origin-bottom overflow-hidden rounded-2xl border border-slate-700/80 bg-[#172033]/98 p-1.5 text-slate-200 shadow-2xl backdrop-blur-xl animate-scaleUp transition-all duration-200"
          >
            {/* EN-TÊTE DU MENU */}
            <div className="rounded-xl bg-slate-800/80 p-3 border border-slate-700/40 mb-1.5">
              <div className="flex items-center gap-3">
                <div
                  className={`grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br ${avatarGradient} text-sm font-black text-white shrink-0 shadow-sm`}
                >
                  {userInitials}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="flex items-center justify-between gap-1">
                    <p className="truncate text-sm font-bold text-white" title={userName}>
                      {userName}
                    </p>
                    <span className="rounded-md bg-blue-500/20 px-1.5 py-0.5 text-[10px] font-bold text-blue-300 border border-blue-500/30">
                      {statusConfig[status].label}
                    </span>
                  </div>
                  <p className="truncate text-xs text-slate-400" title={userEmail}>
                    {userEmail}
                  </p>
                </div>
              </div>

              {/* Sélecteur de statut rapide */}
              <div className="mt-3 flex items-center justify-between gap-1 border-t border-slate-700/60 pt-2 text-[11px]">
                <span className="text-slate-400 font-medium">Statut :</span>
                <div className="flex items-center gap-1">
                  <button
                    type="button"
                    onClick={() => handleStatusChange('online')}
                    className={`flex items-center gap-1 rounded-md px-1.5 py-0.5 font-semibold transition ${status === 'online'
                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                        : 'text-slate-400 hover:text-slate-200'
                      }`}
                  >
                    <span className="h-2 w-2 rounded-full bg-emerald-500" /> En ligne
                  </button>
                  <button
                    type="button"
                    onClick={() => handleStatusChange('away')}
                    className={`flex items-center gap-1 rounded-md px-1.5 py-0.5 font-semibold transition ${status === 'away'
                        ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40'
                        : 'text-slate-400 hover:text-slate-200'
                      }`}
                  >
                    <span className="h-2 w-2 rounded-full bg-amber-500" /> Absent
                  </button>
                  <button
                    type="button"
                    onClick={() => handleStatusChange('offline')}
                    className={`flex items-center gap-1 rounded-md px-1.5 py-0.5 font-semibold transition ${status === 'offline'
                        ? 'bg-slate-500/20 text-slate-300 border border-slate-500/40'
                        : 'text-slate-400 hover:text-slate-200'
                      }`}
                  >
                    <span className="h-2 w-2 rounded-full bg-slate-400" /> Hors ligne
                  </button>
                </div>
              </div>
            </div>

            {/* SECTION 1 : MON COMPTE */}
            <div className="px-1 py-1">
              <p className="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Mon Compte
              </p>
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  setActiveModal('profile');
                }}
                className="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-300 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer"
              >
                <User size={15} className="text-blue-400 group-hover:text-white" />
                <span>Mon Profil</span>
              </button>
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  setActiveModal('password');
                }}
                className="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-300 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer"
              >
                <KeyRound size={15} className="text-amber-400 group-hover:text-white" />
                <span>Modifier le mot de passe</span>
              </button>
            </div>

            {/* SÉPARATEUR */}
            <div className="my-1 border-t border-slate-700/60" />

            {/* SECTION 3 : SUPPORT */}
            <div className="px-1 py-1">
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  setActiveModal('support');
                }}
                className="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-300 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer"
              >
                <HelpCircle size={15} className="text-emerald-400" />
                <span>Aide & Support</span>
              </button>
            </div>

            {/* SÉPARATEUR */}
            <div className="my-1 border-t border-slate-700/60" />

            {/* SECTION 4 : PIED (DÉCONNEXION) */}
            <div className="p-1">
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  logout();
                }}
                className="flex w-full items-center gap-2.5 rounded-xl bg-rose-500/10 px-2.5 py-2 text-xs font-bold text-rose-400 hover:bg-rose-600 hover:text-white transition-all cursor-pointer border border-rose-500/20"
              >
                <LogOut size={15} className="shrink-0" />
                <span>Se déconnecter</span>
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ========================================================================= */}
      {/* MODALE : MON PROFIL */}
      {/* ========================================================================= */}
      {activeModal === 'profile' && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-fadeIn">
          <div className="relative w-full max-w-md overflow-hidden rounded-3xl bg-[#1e252b] border border-slate-700 shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-slate-700/80 px-6 py-5 bg-[#2d3741]/60">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30">
                  <User size={20} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white">Mon Profil</h3>
                  <p className="text-xs text-slate-400">Informations de votre compte</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-700/60 hover:text-white"
              >
                <X size={18} />
              </button>
            </div>

            {/* Body */}
            <div className="p-6 space-y-4">
              <div className="flex items-center gap-4 p-4 rounded-2xl bg-[#2d3741]/40 border border-slate-700/50">
                <div
                  className={`grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br ${avatarGradient} text-xl font-black text-white shadow-lg`}
                >
                  {userInitials}
                </div>
                <div className="min-w-0 flex-1">
                  <h4 className="text-lg font-bold text-white truncate">{userName}</h4>
                  <span className="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                    {userRole}
                  </span>
                </div>
              </div>

              <div className="space-y-3 pt-2">
                <div className="flex items-center gap-3 p-3 rounded-xl bg-[#2d3741]/30 border border-slate-700/40 text-xs">
                  <Mail size={16} className="text-blue-400 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <span className="text-slate-400 block text-[10px]">Email</span>
                    <span className="text-white font-semibold truncate block">{userEmail}</span>
                  </div>
                </div>

                <div className="flex items-center gap-3 p-3 rounded-xl bg-[#2d3741]/30 border border-slate-700/40 text-xs">
                  <Building2 size={16} className="text-emerald-400 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <span className="text-slate-400 block text-[10px]">Structure / Organisme</span>
                    <span className="text-white font-semibold">
                      Direction Régionale du Conseil Agricole (DRCA-RSK)
                    </span>
                  </div>
                </div>

                <div className="flex items-center gap-3 p-3 rounded-xl bg-[#2d3741]/30 border border-slate-700/40 text-xs">
                  <Shield size={16} className="text-purple-400 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <span className="text-slate-400 block text-[10px]">Niveau d'accréditation</span>
                    <span className="text-white font-semibold">
                      {currentUser?.role === 'directeur' ? 'Superviseur Décisionnel' : 'Opérateur Financier'}
                    </span>
                  </div>
                </div>
              </div>

              <div className="pt-4 flex justify-end">
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  className="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition"
                >
                  Fermer
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODALE : MODIFIER LE MOT DE PASSE */}
      {/* ========================================================================= */}
      {activeModal === 'password' && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-fadeIn">
          <div className="relative w-full max-w-md overflow-hidden rounded-3xl bg-[#1e252b] border border-slate-700 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-700/80 px-6 py-5 bg-[#2d3741]/60">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-600/20 text-amber-400 border border-amber-500/30">
                  <KeyRound size={20} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white">Sécurité du compte</h3>
                  <p className="text-xs text-slate-400">Modifier votre mot de passe</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-700/60 hover:text-white"
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handlePasswordSubmit} className="p-6 space-y-4">
              {passwordError && (
                <div className="flex items-start gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-400 font-medium">
                  <AlertCircle size={16} className="shrink-0 mt-0.5" />
                  <span>{passwordError}</span>
                </div>
              )}

              {passwordSuccess && (
                <div className="flex items-start gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-400 font-medium">
                  <CheckCircle2 size={16} className="shrink-0 mt-0.5" />
                  <span>{passwordSuccess}</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Mot de passe actuel
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <Lock size={15} />
                  </span>
                  <input
                    type="password"
                    required
                    value={passwordForm.current}
                    onChange={(e) => setPasswordForm({ ...passwordForm, current: e.target.value })}
                    placeholder="••••••••"
                    className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Nouveau mot de passe
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <Lock size={15} />
                  </span>
                  <input
                    type="password"
                    required
                    value={passwordForm.new}
                    onChange={(e) => setPasswordForm({ ...passwordForm, new: e.target.value })}
                    placeholder="Au moins 6 caractères"
                    className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Confirmer le nouveau mot de passe
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <Lock size={15} />
                  </span>
                  <input
                    type="password"
                    required
                    value={passwordForm.confirm}
                    onChange={(e) => setPasswordForm({ ...passwordForm, confirm: e.target.value })}
                    placeholder="••••••••"
                    className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-3 pt-3">
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  disabled={passwordLoading}
                  className="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={passwordLoading}
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-500 transition disabled:opacity-50"
                >
                  {passwordLoading ? (
                    <>
                      <Loader2 size={15} className="animate-spin" />
                      <span>Enregistrement...</span>
                    </>
                  ) : (
                    <span>Mettre à jour</span>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODALE : AIDE & SUPPORT */}
      {/* ========================================================================= */}
      {activeModal === 'support' && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-fadeIn">
          <div className="relative w-full max-w-md overflow-hidden rounded-3xl bg-[#1e252b] border border-slate-700 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-700/80 px-6 py-5 bg-[#2d3741]/60">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30">
                  <HelpCircle size={20} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white">Assistance & Support</h3>
                  <p className="text-xs text-slate-400">Plateforme financière DRCA-RSK</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-700/60 hover:text-white"
              >
                <X size={18} />
              </button>
            </div>

            <div className="p-6 space-y-4 text-xs text-slate-300">
              <div className="p-4 rounded-2xl bg-blue-900/20 border border-blue-700/40 text-blue-200 space-y-2">
                <div className="flex items-center gap-2 font-bold text-sm text-blue-300">
                  <Sparkles size={16} /> Raccourcis Rapides
                </div>
                <p>
                  • Appuyez sur <kbd className="px-1.5 py-0.5 rounded bg-blue-950 border border-blue-700 text-white font-mono">Ctrl + K</kbd> à tout moment pour ouvrir/fermer votre menu utilisateur.
                </p>
                <p>
                  • Appuyez sur <kbd className="px-1.5 py-0.5 rounded bg-blue-950 border border-blue-700 text-white font-mono">Échap</kbd> pour fermer n'importe quelle boîte de dialogue.
                </p>
              </div>

              <div className="space-y-2">
                <p className="font-semibold text-white">Contact du support technique :</p>
                <div className="p-3 rounded-xl bg-[#2d3741]/40 border border-slate-700/50 space-y-1">
                  <p>📧 Email : <span className="font-mono text-blue-400">support.drca@onca.ma</span></p>
                  <p>📞 Ligne interne : <span className="font-mono text-emerald-400">+212 (0) 537 XX XX XX</span></p>
                  <p>📍 Siège : DRCA Rabat-Salé-Kénitra</p>
                </div>
              </div>

              <div className="pt-2 flex justify-end">
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  className="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition"
                >
                  Compris
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
