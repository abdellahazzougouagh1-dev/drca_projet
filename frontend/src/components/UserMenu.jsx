import { useState, useEffect, useRef, useMemo } from 'react';
import { createPortal } from 'react-dom';
import { useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
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
  User,
  Phone,
  Camera,
  Save,
} from 'lucide-react';
import api from '../api/axios';

const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
const MAX_AVATAR_BYTES = 5 * 1024 * 1024; // 5 Mo

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

/**
 * Redimensionne proprement l'image côté client (max 320px) pour un envoi rapide et léger.
 */
const optimizeAvatarFile = (file) =>
  new Promise((resolve) => {
    const mime = (file?.type || '').toLowerCase();
    if (!file || mime === 'image/gif' || typeof document === 'undefined') {
      resolve(file);
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    const img = new Image();

    img.onload = () => {
      try {
        const maxDim = 320;
        const origW = img.naturalWidth || img.width || maxDim;
        const origH = img.naturalHeight || img.height || maxDim;
        const scale = Math.min(1, maxDim / Math.max(origW, origH));
        const targetW = Math.max(1, Math.round(origW * scale));
        const targetH = Math.max(1, Math.round(origH * scale));

        const canvas = document.createElement('canvas');
        canvas.width = targetW;
        canvas.height = targetH;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
          URL.revokeObjectURL(objectUrl);
          resolve(file);
          return;
        }

        const keepPng = mime === 'image/png' || mime === 'image/webp';
        if (!keepPng) {
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, targetW, targetH);
        }
        ctx.drawImage(img, 0, 0, targetW, targetH);

        const outMime = keepPng ? 'image/png' : 'image/jpeg';
        const outExt = keepPng ? 'png' : 'jpg';
        canvas.toBlob(
          (blob) => {
            URL.revokeObjectURL(objectUrl);
            if (!blob) {
              resolve(file);
              return;
            }
            const optimized = new File([blob], `avatar.${outExt}`, { type: outMime });
            resolve(optimized);
          },
          outMime,
          keepPng ? undefined : 0.86
        );
      } catch {
        URL.revokeObjectURL(objectUrl);
        resolve(file);
      }
    };

    img.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      resolve(file);
    };

    img.src = objectUrl;
  });

const extractApiError = (err, fallback) =>
  Object.values(err.response?.data?.errors || {}).flat()[0] ||
  err.response?.data?.message ||
  fallback;

export default function UserMenu({ className = '', profileContext = null }) {
  const { currentUser, setCurrentUser, logout } = useAuth();
  const location = useLocation();

  // Popup state & onglet actif ('profile' | 'password')
  const [isPopupOpen, setIsPopupOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('profile');

  // Référence input fichier caché pour l'avatar
  const fileInputRef = useRef(null);
  const [avatarLoading, setAvatarLoading] = useState(false);
  const [avatarError, setAvatarError] = useState('');
  const [avatarSuccess, setAvatarSuccess] = useState('');

  // Formulaire de modification du profil
  const [profileForm, setProfileForm] = useState({
    name: currentUser?.name || '',
    email: currentUser?.email || '',
    telephone: currentUser?.telephone || '',
  });
  const [profileLoading, setProfileLoading] = useState(false);
  const [profileError, setProfileError] = useState('');
  const [profileSuccess, setProfileSuccess] = useState('');

  // Mode de changement mot de passe : 'email' (code SMTP recommandé) ou 'current' (mot de passe actuel)
  const [pwdMode, setPwdMode] = useState('email');
  const [codeSent, setCodeSent] = useState(false);
  const [sendingCode, setSendingCode] = useState(false);
  const [codeCooldown, setCodeCooldown] = useState(0);
  const [showPassword, setShowPassword] = useState(false);

  // Formulaire mot de passe
  const [passwordForm, setPasswordForm] = useState({ current: '', new: '', confirm: '', code: '' });
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  // Compteur de renvoi du code SMTP
  useEffect(() => {
    if (codeCooldown > 0) {
      const timer = setTimeout(() => setCodeCooldown((prev) => prev - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [codeCooldown]);

  const openPopup = (defaultTab = 'profile') => {
    setActiveTab(defaultTab);
    setProfileForm({
      name: currentUser?.name || '',
      email: currentUser?.email || '',
      telephone: currentUser?.telephone || '',
    });
    setAvatarError('');
    setAvatarSuccess('');
    setProfileError('');
    setProfileSuccess('');
    setPasswordError('');
    setPasswordSuccess('');
    setIsPopupOpen(true);
  };

  const closePopup = () => {
    setIsPopupOpen(false);
    setAvatarError('');
    setAvatarSuccess('');
    setProfileError('');
    setProfileSuccess('');
    setPasswordError('');
    setPasswordSuccess('');
  };

  // Raccourcis clavier Ctrl+K / Cmd+K et Escape
  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isPopupOpen) {
          closePopup();
        } else {
          setActiveTab('profile');
          setProfileForm({
            name: currentUser?.name || '',
            email: currentUser?.email || '',
            telephone: currentUser?.telephone || '',
          });
          setAvatarError('');
          setAvatarSuccess('');
          setProfileError('');
          setProfileSuccess('');
          setPasswordError('');
          setPasswordSuccess('');
          setIsPopupOpen(true);
        }
      } else if (e.key === 'Escape' && isPopupOpen) {
        closePopup();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isPopupOpen, currentUser]);

  // Bloquer le scroll du body quand la popup est ouverte
  useEffect(() => {
    if (!isPopupOpen) return;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [isPopupOpen]);

  // Sélection & envoi d'une image de profil
  const handleAvatarSelected = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;

    setAvatarError('');
    setAvatarSuccess('');

    const fileType = (file.type || '').toLowerCase();
    const hasValidExt = /\.(jpe?g|png|webp|gif)$/i.test(file.name || '');
    if ((fileType && !ALLOWED_IMAGE_TYPES.includes(fileType)) || (!fileType && !hasValidExt)) {
      setAvatarError('Format non supporté. Choisissez une image JPG, PNG, WEBP ou GIF.');
      return;
    }

    if (file.size > MAX_AVATAR_BYTES) {
      setAvatarError('L’image sélectionnée dépasse la limite autorisée de 5 Mo.');
      return;
    }

    try {
      setAvatarLoading(true);
      const preparedFile = await optimizeAvatarFile(file);
      const formData = new FormData();
      formData.append('avatar', preparedFile);

      const res = await api.post('/user/avatar', formData);
      if (res.data?.user) {
        setCurrentUser(res.data.user);
      }
      setAvatarSuccess(res.data?.message || 'Photo de profil mise à jour avec succès !');
    } catch (err) {
      setAvatarError(extractApiError(err, 'Impossible de téléverser la photo de profil.'));
    } finally {
      setAvatarLoading(false);
    }
  };

  // Mise à jour des informations du profil
  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setProfileError('');
    setProfileSuccess('');

    const trimmedName = profileForm.name.trim();
    const trimmedEmail = profileForm.email.trim();
    const trimmedTel = profileForm.telephone.trim();

    if (trimmedName.length < 2) {
      setProfileError('Le nom complet doit contenir au moins 2 caractères.');
      return;
    }
    if (!trimmedEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
      setProfileError('Veuillez saisir une adresse email valide.');
      return;
    }

    try {
      setProfileLoading(true);
      const res = await api.post('/user/profile', {
        name: trimmedName,
        email: trimmedEmail,
        telephone: trimmedTel || null,
      });

      if (res.data?.user) {
        setCurrentUser(res.data.user);
      }
      setProfileSuccess(res.data?.message || 'Votre profil a été mis à jour avec succès !');
    } catch (err) {
      setProfileError(extractApiError(err, 'Erreur lors de la mise à jour du profil.'));
    } finally {
      setProfileLoading(false);
    }
  };

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
        extractApiError(
          err,
          'Erreur lors de l’envoi de l’email. Vérifiez la configuration SMTP dans backend/.env.'
        )
      );
    } finally {
      setSendingCode(false);
    }
  };

  // Soumission du changement de mot de passe
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
    } else if (!passwordForm.current) {
      setPasswordError('Veuillez saisir votre mot de passe actuel.');
      return;
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
    } catch (err) {
      setPasswordLoading(false);
      setPasswordError(extractApiError(err, 'Erreur lors du changement de mot de passe.'));
    }
  };

  const isDirectorSpace = useMemo(() => {
    if (profileContext === 'directeur') return true;
    if (profileContext === 'saf') return false;
    const path = (location?.pathname || '').toLowerCase();
    return path === '/directeur' || path.startsWith('/directeur/');
  }, [profileContext, location?.pathname]);

  const fallbackName = isDirectorSpace ? 'Directeur Régional' : 'Chef de SAF';
  const userName = currentUser?.name?.trim() || fallbackName;
  const userEmail = currentUser?.email || (isDirectorSpace ? 'directeur@onca.ma' : 'saf@onca.ma');
  const userAvatar = currentUser?.avatar || null;
  const userRole = isDirectorSpace
    ? 'Direction Régionale · DRCA-RSK'
    : 'Service administratif et financier';
  const roleBadgeLabel =
    currentUser?.role === 'directeur' || isDirectorSpace
      ? 'Directeur Régional'
      : 'Chef de SAF · Gestionnaire';

  const userInitials = useMemo(() => getInitials(userName), [userName]);
  const avatarGradient = useMemo(() => getAvatarGradient(userName), [userName]);

  const isProfileDirty =
    profileForm.name.trim() !== (currentUser?.name || '').trim() ||
    profileForm.email.trim() !== (currentUser?.email || '').trim() ||
    profileForm.telephone.trim() !== (currentUser?.telephone || '').trim();

  const renderPopup = () => {
    if (!isPopupOpen || typeof document === 'undefined') return null;

    return createPortal(
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-modal-title"
        onClick={(e) => {
          if (e.target === e.currentTarget) {
            closePopup();
          }
        }}
        className="fixed inset-0 z-[120] flex items-center justify-center p-3 sm:p-6 bg-slate-950/65 backdrop-blur-sm animate-fadeIn"
      >
        <div className="relative flex w-full max-w-2xl max-h-[92vh] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white text-slate-900 shadow-2xl animate-scaleUp">
          {/* =============================================================== */}
          {/* EN-TÊTE CLAIR DU PROFIL + PHOTO DE PROFIL                       */}
          {/* =============================================================== */}
          <div className="shrink-0 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-emerald-50/70 px-5 sm:px-7 py-5">
            <div className="flex items-start justify-between gap-4">
              <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5 min-w-0 flex-1">
                {/* Avatar interactif */}
                <div className="relative shrink-0 self-start sm:self-auto">
                  <button
                    type="button"
                    onClick={() => !avatarLoading && fileInputRef.current?.click()}
                    title="Cliquer pour changer la photo de profil"
                    className="group relative flex h-22 w-22 items-center justify-center overflow-hidden rounded-2xl border-2 border-white bg-slate-100 shadow-lg ring-1 ring-slate-200 transition hover:ring-[#087F5B] cursor-pointer"
                  >
                    {userAvatar ? (
                      <img
                        src={userAvatar}
                        alt={userName}
                        className="h-full w-full object-cover"
                      />
                    ) : (
                      <div
                        className={`grid h-full w-full place-items-center bg-gradient-to-br ${avatarGradient} text-2xl font-black text-white`}
                      >
                        {userInitials}
                      </div>
                    )}

                    <div className="absolute inset-0 flex items-center justify-center bg-slate-950/45 text-white opacity-0 transition-opacity group-hover:opacity-100">
                      {avatarLoading ? <Loader2 size={22} className="animate-spin" /> : <Camera size={22} />}
                    </div>
                  </button>

                  <button
                    type="button"
                    onClick={() => !avatarLoading && fileInputRef.current?.click()}
                    disabled={avatarLoading}
                    aria-label="Choisir une photo de profil"
                    className="absolute -bottom-1.5 -right-1.5 flex h-8 w-8 items-center justify-center rounded-xl border-2 border-white bg-[#087F5B] text-white shadow-md transition hover:bg-[#06694A] disabled:opacity-60 cursor-pointer"
                  >
                    {avatarLoading ? <Loader2 size={14} className="animate-spin" /> : <Camera size={14} />}
                  </button>

                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onChange={handleAvatarSelected}
                    className="hidden"
                  />
                </div>

                {/* Infos identité + actions photo */}
                <div className="min-w-0 flex-1">
                  <h2 id="profile-modal-title" className="text-xl font-extrabold text-slate-900 tracking-tight break-words">
                    {userName}
                  </h2>
                  <p className="mt-0.5 text-sm font-semibold text-[#087F5B]">{roleBadgeLabel}</p>
                  <p className="mt-0.5 text-xs font-medium text-slate-500 break-all">{userEmail}</p>

                  <div className="mt-3 flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      disabled={avatarLoading}
                      onClick={() => fileInputRef.current?.click()}
                      className="inline-flex items-center gap-1.5 rounded-xl bg-[#087F5B] px-3 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#06694A] disabled:opacity-50 cursor-pointer"
                    >
                      <Camera size={13} />
                      <span>{userAvatar ? 'Changer la photo' : 'Ajouter une photo'}</span>
                    </button>
                  </div>
                </div>
              </div>

              <button
                type="button"
                onClick={closePopup}
                aria-label="Fermer la fenêtre de profil"
                className="shrink-0 rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-2xs transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-800 cursor-pointer"
              >
                <X size={18} />
              </button>
            </div>

            {(avatarError || avatarSuccess) && (
              <div className="mt-3">
                {avatarError && (
                  <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-medium text-rose-700">
                    <AlertCircle size={15} className="shrink-0 mt-0.5" />
                    <span>{avatarError}</span>
                  </div>
                )}
                {avatarSuccess && (
                  <div className="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-700">
                    <CheckCircle2 size={15} className="shrink-0 mt-0.5" />
                    <span>{avatarSuccess}</span>
                  </div>
                )}
              </div>
            )}

            {/* Onglets principaux */}
            <div className="mt-5 grid grid-cols-2 gap-2 rounded-2xl border border-slate-200 bg-slate-100/90 p-1">
              <button
                type="button"
                onClick={() => {
                  setActiveTab('profile');
                  setPasswordError('');
                  setPasswordSuccess('');
                }}
                className={`flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs sm:text-sm font-bold transition-all cursor-pointer ${
                  activeTab === 'profile'
                    ? 'bg-white text-[#101B2D] shadow-sm ring-1 ring-slate-200/80'
                    : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
              >
                <User size={15} className={activeTab === 'profile' ? 'text-[#087F5B]' : 'text-slate-400'} />
                <span>Modifier le profil</span>
              </button>
              <button
                type="button"
                onClick={() => {
                  setActiveTab('password');
                  setProfileError('');
                  setProfileSuccess('');
                }}
                className={`flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs sm:text-sm font-bold transition-all cursor-pointer ${
                  activeTab === 'password'
                    ? 'bg-white text-[#101B2D] shadow-sm ring-1 ring-slate-200/80'
                    : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
              >
                <KeyRound size={15} className={activeTab === 'password' ? 'text-[#087F5B]' : 'text-slate-400'} />
                <span>Mot de passe</span>
              </button>
            </div>
          </div>

          {/* =============================================================== */}
          {/* CORPS DÉFILABLE DE LA POPUP                                     */}
          {/* =============================================================== */}
          <div className="flex-1 overflow-y-auto px-5 sm:px-7 py-5 bg-white">
            {/* ONGLET 1 : MODIFIER LE PROFIL */}
            {activeTab === 'profile' && (
              <form onSubmit={handleProfileSubmit} className="space-y-4">
                {profileError && (
                  <div className="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-medium text-rose-700">
                    <AlertCircle size={16} className="shrink-0 mt-0.5" />
                    <span>{profileError}</span>
                  </div>
                )}

                {profileSuccess && (
                  <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-medium text-emerald-700">
                    <CheckCircle2 size={16} className="shrink-0 mt-0.5" />
                    <span>{profileSuccess}</span>
                  </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1.5">
                      Nom complet <span className="text-rose-500">*</span>
                    </label>
                    <div className="relative">
                      <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <User size={15} />
                      </span>
                      <input
                        type="text"
                        required
                        minLength={2}
                        maxLength={255}
                        value={profileForm.name}
                        onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })}
                        placeholder="Ex: Hamza Baadi"
                        className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-3.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1.5">
                      Téléphone <span className="font-normal text-slate-400">(optionnel)</span>
                    </label>
                    <div className="relative">
                      <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <Phone size={15} />
                      </span>
                      <input
                        type="tel"
                        maxLength={30}
                        value={profileForm.telephone}
                        onChange={(e) => setProfileForm({ ...profileForm, telephone: e.target.value })}
                        placeholder="Ex: 06 12 34 56 78"
                        className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-3.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1.5">
                    Adresse email <span className="text-rose-500">*</span>
                  </label>
                  <div className="relative">
                    <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                      <Mail size={15} />
                    </span>
                    <input
                      type="email"
                      required
                      maxLength={255}
                      value={profileForm.email}
                      onChange={(e) => setProfileForm({ ...profileForm, email: e.target.value })}
                      placeholder="nom@onca.ma"
                      className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-3.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                    />
                  </div>
                </div>

                <div className="flex items-center justify-end pt-2">
                  <button
                    type="submit"
                    disabled={profileLoading || !isProfileDirty}
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#087F5B] px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-[#087F5B]/20 transition hover:bg-[#06694A] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                  >
                    {profileLoading ? (
                      <>
                        <Loader2 size={16} className="animate-spin" />
                        <span>Enregistrement...</span>
                      </>
                    ) : (
                      <>
                        <Save size={16} />
                        <span>Enregistrer les modifications</span>
                      </>
                    )}
                  </button>
                </div>
              </form>
            )}

            {/* ONGLET 2 : MODIFIER LE MOT DE PASSE */}
            {activeTab === 'password' && (
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-2 rounded-2xl border border-slate-200 bg-slate-100 p-1">
                  <button
                    type="button"
                    onClick={() => {
                      setPwdMode('email');
                      setPasswordError('');
                    }}
                    className={`flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition-all cursor-pointer ${
                      pwdMode === 'email'
                        ? 'bg-[#087F5B] text-white shadow-sm'
                        : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'
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
                    className={`flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition-all cursor-pointer ${
                      pwdMode === 'current'
                        ? 'bg-[#087F5B] text-white shadow-sm'
                        : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'
                    }`}
                  >
                    <Lock size={14} />
                    <span>Mot de passe actuel</span>
                  </button>
                </div>

                <form onSubmit={handlePasswordSubmit} className="space-y-4">
                  {passwordError && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-medium text-rose-700">
                      <AlertCircle size={16} className="shrink-0 mt-0.5" />
                      <span>{passwordError}</span>
                    </div>
                  )}

                  {passwordSuccess && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-medium text-emerald-700">
                      <CheckCircle2 size={16} className="shrink-0 mt-0.5" />
                      <span>{passwordSuccess}</span>
                    </div>
                  )}

                  {pwdMode === 'email' && (
                    <div className="space-y-4">
                      <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                          <div>
                            <p className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                              <ShieldCheck size={15} className="text-[#087F5B]" />
                              Vérification d’identité par Email
                            </p>
                            <p className="text-xs text-slate-500 mt-1">
                              Code envoyé à : <span className="font-bold text-slate-800 underline">{userEmail}</span>
                            </p>
                          </div>
                          <button
                            type="button"
                            onClick={handleSendCode}
                            disabled={sendingCode || codeCooldown > 0}
                            className="inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-[#087F5B] transition hover:bg-[#087F5B] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shrink-0"
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
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Code de vérification (6 chiffres reçus par email)
                        </label>
                        <div className="relative">
                          <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
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
                            className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-3.5 text-center font-mono text-sm font-bold tracking-[0.3em] text-[#087F5B] focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                          />
                        </div>
                      </div>
                    </div>
                  )}

                  {pwdMode === 'current' && (
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">
                        Mot de passe actuel
                      </label>
                      <div className="relative">
                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                          <Lock size={15} />
                        </span>
                        <input
                          type={showPassword ? 'text' : 'password'}
                          required
                          value={passwordForm.current}
                          onChange={(e) => setPasswordForm({ ...passwordForm, current: e.target.value })}
                          placeholder="••••••••"
                          className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-10 text-sm text-slate-900 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword(!showPassword)}
                          className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700 cursor-pointer"
                        >
                          {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                        </button>
                      </div>
                    </div>
                  )}

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">
                        Nouveau mot de passe <span className="font-normal text-slate-400">(8 car. min)</span>
                      </label>
                      <div className="relative">
                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                          <Lock size={15} />
                        </span>
                        <input
                          type={showPassword ? 'text' : 'password'}
                          required
                          minLength={8}
                          value={passwordForm.new}
                          onChange={(e) => setPasswordForm({ ...passwordForm, new: e.target.value })}
                          placeholder="Nouveau mot de passe"
                          className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-10 text-sm text-slate-900 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword(!showPassword)}
                          className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700 cursor-pointer"
                        >
                          {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                        </button>
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">
                        Confirmer le mot de passe
                      </label>
                      <div className="relative">
                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                          <Lock size={15} />
                        </span>
                        <input
                          type={showPassword ? 'text' : 'password'}
                          required
                          minLength={8}
                          value={passwordForm.confirm}
                          onChange={(e) => setPasswordForm({ ...passwordForm, confirm: e.target.value })}
                          placeholder="Retapez le mot de passe"
                          className="w-full rounded-xl border border-slate-300 bg-slate-50/70 py-2.5 pl-10 pr-3.5 text-sm text-slate-900 focus:border-[#087F5B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#087F5B]/20"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center justify-end pt-2">
                    <button
                      type="submit"
                      disabled={passwordLoading}
                      className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#087F5B] px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-[#087F5B]/20 transition hover:bg-[#06694A] disabled:opacity-50 cursor-pointer"
                    >
                      {passwordLoading ? (
                        <>
                          <Loader2 size={16} className="animate-spin" />
                          <span>Validation en cours...</span>
                        </>
                      ) : (
                        <>
                          <KeyRound size={15} />
                          <span>Valider et modifier le mot de passe</span>
                        </>
                      )}
                    </button>
                  </div>
                </form>
              </div>
            )}
          </div>

          {/* =============================================================== */}
          {/* PIED DE POPUP                                                   */}
          {/* =============================================================== */}
          <div className="shrink-0 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 sm:px-7 py-3.5">
            <button
              type="button"
              onClick={() => {
                closePopup();
                logout();
              }}
              className="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-600 hover:text-white cursor-pointer"
            >
              <LogOut size={15} />
              <span>Se déconnecter</span>
            </button>

            <button
              type="button"
              onClick={closePopup}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 cursor-pointer"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>,
      document.body
    );
  };

  return (
    <>
      {/* Conteneur principal UserMenu en bas de sidebar */}
      <div className={`relative w-full ${className}`}>
        <button
          type="button"
          onClick={() => openPopup('profile')}
          aria-expanded={isPopupOpen}
          aria-haspopup="dialog"
          aria-label="Ouvrir le profil utilisateur"
          className={`group flex w-full items-center justify-between gap-2.5 rounded-2xl border p-2.5 sm:p-3 text-left transition-all duration-300 cursor-pointer select-none ${
            isPopupOpen
              ? 'border-[#087F5B] bg-[#172B46] shadow-lg shadow-[#101B2D]/40 ring-2 ring-[#087F5B]/30'
              : 'border-[#172B46] bg-[#172B46]/60 hover:border-[#087F5B]/50 hover:bg-[#172B46] shadow-xs'
          }`}
        >
          <div className="flex items-center gap-2.5 min-w-0 flex-1">
            {/* Avatar */}
            <div className="relative shrink-0">
              {userAvatar ? (
                <img
                  src={userAvatar}
                  alt={userName}
                  className="h-10 w-10 rounded-xl object-cover border border-white/15 shadow-md shadow-slate-900/50 transition-transform group-hover:scale-105"
                />
              ) : (
                <div
                  className={`grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br ${avatarGradient} text-sm font-extrabold text-white shadow-md shadow-slate-900/50 transition-transform group-hover:scale-105`}
                >
                  {userInitials}
                </div>
              )}
            </div>

            {/* Informations Nom + Rôle */}
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-1.5">
                <p
                  className="text-[13px] font-bold leading-tight text-white break-words transition-colors group-hover:text-emerald-300"
                  title={userName}
                >
                  {userName}
                </p>
              </div>
              <p
                className="mt-0.5 text-[11px] font-medium leading-snug text-slate-400 break-words"
                title={userRole}
              >
                {userRole}
              </p>
            </div>
          </div>

          {/* Chevron interactif */}
          <div className="flex items-center shrink-0">
            <div
              className={`text-slate-400 transition-transform duration-300 group-hover:text-white ${
                isPopupOpen ? 'rotate-180 text-emerald-400' : ''
              }`}
            >
              <ChevronUp size={16} />
            </div>
          </div>
        </button>
      </div>

      {renderPopup()}
    </>
  );
}
