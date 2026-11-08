import React, { useState, useEffect, useRef, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import useClickOutside from '../hooks/useClickOutside';
import {
  KeyRound,
  LogOut,
  ChevronUp,
  X,
  Lock,
  Loader2,
  AlertCircle,
  CheckCircle2,
  Mail,
  Send,
  Eye,
  EyeOff,
  ShieldCheck,
  RefreshCw,
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
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();

  // Menu Open State
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef(null);

  // Sub-modal state: 'password' | null
  const [activeModal, setActiveModal] = useState(null);

  // Notifications Count (mock/dynamic)
  const [unreadCount] = useState(3);

  // Mode de changement : 'email' (code SMTP recommandé) ou 'current' (mot de passe actuel)
  const [pwdMode, setPwdMode] = useState('email');
  const [codeSent, setCodeSent] = useState(false);
  const [sendingCode, setSendingCode] = useState(false);
  const [codeCooldown, setCodeCooldown] = useState(0);
  const [showPassword, setShowPassword] = useState(false);

  // Password Modal form
  const [passwordForm, setPasswordForm] = useState({ current: '', new: '', confirm: '', code: '' });
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  // Compteur de renvoi du code
  useEffect(() => {
    if (codeCooldown > 0) {
      const timer = setTimeout(() => setCodeCooldown(codeCooldown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [codeCooldown]);

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

  // Envoi du code de vérification par email SMTP
  const handleSendCode = async () => {
    setPasswordError('');
    setPasswordSuccess('');
    setSendingCode(true);
    try {
      const res = await api.post('/user/send-password-code');
      setCodeSent(true);
      setCodeCooldown(60);
      setPasswordSuccess(res.data.message || 'Code envoyé avec succès par email !');
    } catch (err) {
      setPasswordError(
        err.response?.data?.message ||
        'Erreur lors de l’envoi de l’email. Vérifiez la configuration SMTP dans backend/.env.'
      );
    } finally {
      setSendingCode(false);
    }
  };

  // Soumission du formulaire
  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    setPasswordError('');
    setPasswordSuccess('');

    if (passwordForm.new !== passwordForm.confirm) {
      setPasswordError('Le nouveau mot de passe et sa confirmation ne correspondent pas.');
      return;
    }
    if (passwordForm.new.length < 8) {
      setPasswordError('Le nouveau mot de passe doit comporter au moins 8 caractères.');
      return;
    }

    if (pwdMode === 'email') {
      if (!passwordForm.code || passwordForm.code.trim().length !== 6) {
        setPasswordError('Veuillez saisir le code de vérification à 6 chiffres reçu par email.');
        return;
      }
    } else {
      if (!passwordForm.current) {
        setPasswordError('Veuillez saisir votre mot de passe actuel.');
        return;
      }
    }

    try {
      setPasswordLoading(true);
      const payload = {
        new_password: passwordForm.new,
        ...(pwdMode === 'email' ? { code: passwordForm.code.trim() } : { current_password: passwordForm.current }),
      };

      const res = await api.post('/user/change-password', payload);
      setPasswordLoading(false);
      setPasswordSuccess(res.data.message || 'Mot de passe modifié avec succès !');
      setPasswordForm({ current: '', new: '', confirm: '', code: '' });
      setTimeout(() => {
        setActiveModal(null);
        setPasswordSuccess('');
      }, 2000);
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
              ? 'border-[#087F5B] bg-[#172B46] shadow-lg shadow-[#101B2D]/40 ring-2 ring-[#087F5B]/30'
              : 'border-[#172B46] bg-[#172B46]/60 hover:border-[#087F5B]/50 hover:bg-[#172B46] shadow-xs'
            }`}
        >
          <div className="flex items-center gap-3 min-w-0 flex-1">
            {/* Avatar */}
            <div className="relative shrink-0">
              <div
                className={`grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br ${avatarGradient} text-sm font-extrabold text-white shadow-md shadow-slate-900/50 transition-transform group-hover:scale-105`}
              >
                {userInitials}
              </div>
            </div>

            {/* Informations Nom + Rôle */}
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-1.5">
                <p
                  className="truncate text-sm font-bold text-white transition-colors group-hover:text-emerald-300"
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
            className="absolute bottom-full left-0 right-0 z-50 mb-2 origin-bottom max-h-[calc(100vh-100px)] overflow-y-auto rounded-2xl border border-slate-700/80 bg-[#172033]/98 p-1.5 text-slate-200 shadow-2xl backdrop-blur-xl animate-scaleUp transition-all duration-200"
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
                  <p className="truncate text-sm font-bold text-white" title={userName}>
                    {userName}
                  </p>
                  <p className="truncate text-xs text-slate-400" title={userEmail}>
                    {userEmail}
                  </p>
                </div>
              </div>
            </div>

            {/* SECTION : MON COMPTE */}
            <div className="px-1 py-1">
              <p className="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Mon Compte
              </p>
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  setActiveModal('password');
                }}
                className="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-300 hover:bg-[#087F5B] hover:text-white transition-colors cursor-pointer"
              >
                <KeyRound size={15} className="text-amber-400 group-hover:text-white" />
                <span>Modifier le mot de passe</span>
              </button>
            </div>

            {/* SÉPARATEUR */}
            <div className="my-1 border-t border-slate-700/60" />

            {/* SECTION : DÉCONNEXION */}
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
      {/* MODALE : MODIFIER LE MOT DE PASSE (AVEC CODE SMTP & ANCIEN MOT DE PASSE) */}
      {/* ========================================================================= */}
      {activeModal === 'password' && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fadeIn">
          <div className="relative w-full max-w-lg overflow-hidden rounded-3xl bg-[#1e252b] border border-slate-700 shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-slate-700/80 px-6 py-5 bg-[#2d3741]/60">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30">
                  <KeyRound size={20} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white">Sécurité du compte</h3>
                  <p className="text-xs text-slate-400">Modifier votre mot de passe ({userEmail})</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => {
                  setActiveModal(null);
                  setPasswordError('');
                  setPasswordSuccess('');
                }}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-700/60 hover:text-white cursor-pointer"
              >
                <X size={18} />
              </button>
            </div>

            {/* Onglets de sélection du mode */}
            <div className="px-6 pt-5">
              <div className="grid grid-cols-2 gap-2 p-1 bg-[#151c22] rounded-2xl border border-slate-700/60">
                <button
                  type="button"
                  onClick={() => {
                    setPwdMode('email');
                    setPasswordError('');
                  }}
                  className={`flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    pwdMode === 'email'
                      ? 'bg-[#087F5B] text-white shadow-md shadow-[#087F5B]/30'
                      : 'text-slate-400 hover:text-white hover:bg-slate-800/40'
                  }`}
                >
                  <Mail size={14} />
                  <span>Code par Email (SMTP)</span>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setPwdMode('current');
                    setPasswordError('');
                  }}
                  className={`flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    pwdMode === 'current'
                      ? 'bg-[#087F5B] text-white shadow-md shadow-[#087F5B]/30'
                      : 'text-slate-400 hover:text-white hover:bg-slate-800/40'
                  }`}
                >
                  <Lock size={14} />
                  <span>Mot de passe actuel</span>
                </button>
              </div>
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

              {/* MODE EMAIL (SMTP) */}
              {pwdMode === 'email' && (
                <div className="space-y-4">
                  <div className="rounded-2xl border border-slate-700 bg-[#2d3741]/40 p-3.5">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                      <div>
                        <p className="text-xs font-bold text-white flex items-center gap-1.5">
                          <ShieldCheck size={14} className="text-emerald-400" />
                          Vérification d’identité par Email
                        </p>
                        <p className="text-[11px] text-slate-400 mt-1">
                          Code envoyé à : <span className="text-white font-medium underline">{userEmail}</span>
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={handleSendCode}
                        disabled={sendingCode || codeCooldown > 0}
                        className="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600/20 text-emerald-300 border border-emerald-500/40 hover:bg-emerald-600 hover:text-white text-xs font-bold transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                      >
                        {sendingCode ? (
                          <>
                            <Loader2 size={13} className="animate-spin" />
                            <span>Envoi...</span>
                          </>
                        ) : codeCooldown > 0 ? (
                          <>
                            <RefreshCw size={13} className="animate-spin" />
                            <span>Renvoyer ({codeCooldown}s)</span>
                          </>
                        ) : (
                          <>
                            <Send size={13} />
                            <span>{codeSent ? 'Renvoyer le code' : 'Envoyer le code'}</span>
                          </>
                        )}
                      </button>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                      Code de vérification (6 chiffres reçus par email)
                    </label>
                    <div className="relative">
                      <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <Mail size={15} />
                      </span>
                      <input
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]{6}"
                        maxLength={6}
                        required
                        value={passwordForm.code}
                        onChange={(e) => setPasswordForm({ ...passwordForm, code: e.target.value.replace(/\D/g, '') })}
                        placeholder="Ex: 849201"
                        className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-3 text-sm text-center tracking-[0.3em] font-mono font-bold text-emerald-300 focus:border-[#087F5B] focus:outline-none focus:ring-1 focus:ring-[#087F5B]"
                      />
                    </div>
                  </div>
                </div>
              )}

              {/* MODE MOT DE PASSE ACTUEL */}
              {pwdMode === 'current' && (
                <div>
                  <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                    Mot de passe actuel
                  </label>
                  <div className="relative">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                      <Lock size={15} />
                    </span>
                    <input
                      type={showPassword ? 'text' : 'password'}
                      required
                      value={passwordForm.current}
                      onChange={(e) => setPasswordForm({ ...passwordForm, current: e.target.value })}
                      placeholder="••••••••"
                      className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-10 text-sm text-white focus:border-[#087F5B] focus:outline-none focus:ring-1 focus:ring-[#087F5B]"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white cursor-pointer"
                    >
                      {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                    </button>
                  </div>
                </div>
              )}

              {/* NOUVEAU MOT DE PASSE */}
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Nouveau mot de passe <span className="text-slate-400 font-normal">(8 caractères minimum)</span>
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <Lock size={15} />
                  </span>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    minLength={8}
                    value={passwordForm.new}
                    onChange={(e) => setPasswordForm({ ...passwordForm, new: e.target.value })}
                    placeholder="Nouveau mot de passe sécurisé"
                    className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-10 text-sm text-white focus:border-[#087F5B] focus:outline-none focus:ring-1 focus:ring-[#087F5B]"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white cursor-pointer"
                  >
                    {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                  </button>
                </div>
              </div>

              {/* CONFIRMER LE NOUVEAU MOT DE PASSE */}
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Confirmer le nouveau mot de passe
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <Lock size={15} />
                  </span>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    minLength={8}
                    value={passwordForm.confirm}
                    onChange={(e) => setPasswordForm({ ...passwordForm, confirm: e.target.value })}
                    placeholder="Retapez le nouveau mot de passe"
                    className="w-full rounded-xl border border-slate-700 bg-[#2d3741] py-2.5 pl-9 pr-10 text-sm text-white focus:border-[#087F5B] focus:outline-none focus:ring-1 focus:ring-[#087F5B]"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-3 pt-3">
                <button
                  type="button"
                  onClick={() => {
                    setActiveModal(null);
                    setPasswordError('');
                    setPasswordSuccess('');
                  }}
                  disabled={passwordLoading}
                  className="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white cursor-pointer"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={passwordLoading}
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#087F5B] hover:bg-[#06694A] px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-[#087F5B]/30 transition disabled:opacity-50 cursor-pointer"
                >
                  {passwordLoading ? (
                    <>
                      <Loader2 size={15} className="animate-spin" />
                      <span>Validation en cours...</span>
                    </>
                  ) : (
                    <span>Valider et modifier le mot de passe</span>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
