import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import {
  LogOut,
  Pencil,
  X,
  Loader2,
  CheckCircle2,
  AlertCircle,
  User,
  Mail,
  Lock,
  Eye,
  EyeOff,
  ShieldCheck,
  ChevronDown,
} from 'lucide-react';

const getUserFromStorage = () => {
  try {
    const raw = localStorage.getItem('user');
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const saveUserToStorage = (user) => {
  localStorage.setItem('user', JSON.stringify(user));
};

/** Génère une couleur HSL déterministe à partir d'une chaîne */
const stringToHsl = (str = '') => {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  const h = Math.abs(hash) % 360;
  return { h, bg: `hsl(${h},65%,52%)`, light: `hsl(${h},65%,92%)`, text: `hsl(${h},50%,28%)` };
};

/** Extrait les initiales (max 2 lettres) */
const getInitials = (name = '') => {
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.slice(0, 2).toUpperCase() || 'U';
};

const UserMenu = ({ variant = 'light' }) => {
  const navigate = useNavigate();
  const [user, setUser] = useState(getUserFromStorage());
  const [open, setOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [showPwd, setShowPwd] = useState(false);
  const [showPwdConfirm, setShowPwdConfirm] = useState(false);
  const menuRef = useRef(null);

  const [form, setForm] = useState({ name: '', email: '' });
  const [passwordForm, setPasswordForm] = useState({ password: '', password_confirmation: '' });

  // ── Modale dédiée au changement de mot de passe ──
  const [pwdOpen, setPwdOpen] = useState(false);
  const [pwdForm, setPwdForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [showCurrentPwd, setShowCurrentPwd] = useState(false);
  const [showNewPwd, setShowNewPwd] = useState(false);
  const [showConfirmPwd, setShowConfirmPwd] = useState(false);
  const [pwdLoading, setPwdLoading] = useState(false);
  const [pwdMessage, setPwdMessage] = useState('');
  const [pwdError, setPwdError] = useState('');

  const colors = stringToHsl(user?.name || 'U');
  const initials = getInitials(user?.name || '');
  const isDark = variant === 'dark';

  useEffect(() => {
    if (!user) {
      api.get('/user').then((res) => {
        setUser(res.data);
        saveUserToStorage(res.data);
      }).catch(() => { });
    }
  }, []);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (menuRef.current && !menuRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete api.defaults.headers.common.Authorization;
    navigate('/login');
  };

  const openEdit = () => {
    setForm({ name: user?.name || '', email: user?.email || '' });
    setPasswordForm({ password: '', password_confirmation: '' });
    setMessage('');
    setError('');
    setOpen(false);
    setEditOpen(true);
  };

  const openPwd = () => {
    setPwdForm({ current_password: '', password: '', password_confirmation: '' });
    setPwdMessage('');
    setPwdError('');
    setOpen(false);
    setPwdOpen(true);
  };

  const handleChangePassword = async (e) => {
    if (e) e.preventDefault();
    if (!user?.id) return;
    setPwdLoading(true);
    setPwdMessage('');
    setPwdError('');
    try {
      const response = await api.post('/user/change-password', pwdForm);
      setPwdMessage(response.data.message || 'Mot de passe modifié avec succès.');
      setPwdForm({ current_password: '', password: '', password_confirmation: '' });
    } catch (err) {
      const apiErrors = err.response?.data?.errors || {};
      const firstError = err.response?.data?.message || Object.values(apiErrors)[0];
      setPwdError(Array.isArray(firstError) ? firstError[0] : (firstError || 'Impossible de modifier le mot de passe.'));
    } finally {
      setPwdLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    if (e) e.preventDefault();
    if (!user?.id) return;
    setLoading(true);
    setMessage('');
    setError('');
    try {
      const payload = {};
      if (form.name.trim()) payload.name = form.name.trim();
      if (form.email.trim()) payload.email = form.email.trim();
      if (passwordForm.password) {
        payload.password = passwordForm.password;
        payload.password_confirmation = passwordForm.password_confirmation;
      }
      const response = await api.put(`/users/${user.id}`, payload);
      const updated = response.data.user;
      setUser(updated);
      saveUserToStorage(updated);
      setPasswordForm({ password: '', password_confirmation: '' });
      setMessage('Profil mis à jour avec succès.');
    } catch (err) {
      const apiErrors = err.response?.data?.errors || {};
      const firstError = err.response?.data?.message || Object.values(apiErrors)[0];
      setError(firstError || 'Impossible de mettre à jour le profil.');
    } finally {
      setLoading(false);
    }
  };

  /* ── Styles inline partagés ── */
  const inputStyle = {
    width: '100%',
    padding: '10px 14px 10px 40px',
    borderRadius: '10px',
    border: '1.5px solid #e2e8f0',
    background: '#f8fafc',
    fontSize: '14px',
    outline: 'none',
    transition: 'all 0.2s',
    color: '#1e293b',
    fontWeight: 500,
  };

  return (
    <>
      {/* ── Trigger Button ── */}
      <div className="relative" ref={menuRef}>
        <button
          type="button"
          onClick={() => setOpen((p) => !p)}
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
            padding: '6px 10px 6px 6px',
            borderRadius: '14px',
            border: isDark ? '1px solid rgba(255,255,255,0.12)' : '1px solid #e2e8f0',
            background: isDark ? 'rgba(255,255,255,0.08)' : '#fff',
            cursor: 'pointer',
            transition: 'all 0.2s',
            width: '100%',
          }}
          onMouseEnter={e => { e.currentTarget.style.background = isDark ? 'rgba(255,255,255,0.14)' : '#f1f5f9'; }}
          onMouseLeave={e => { e.currentTarget.style.background = isDark ? 'rgba(255,255,255,0.08)' : '#fff'; }}
        >
          {/* Avatar */}
          <div
            style={{
              width: '34px',
              height: '34px',
              borderRadius: '10px',
              background: colors.bg,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
              fontSize: '13px',
              fontWeight: 800,
              color: '#fff',
              letterSpacing: '0.5px',
              boxShadow: `0 2px 8px ${colors.bg}60`,
            }}
          >
            {initials}
          </div>

          {/* Name */}
          <div style={{ flex: 1, minWidth: 0, textAlign: 'left' }}>
            <p style={{
              fontSize: '13px',
              fontWeight: 700,
              color: isDark ? '#fff' : '#1e293b',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              maxWidth: '110px',
            }}>
              {user?.name || 'Utilisateur'}
            </p>
            <p style={{
              fontSize: '10px',
              color: isDark ? 'rgba(255,255,255,0.5)' : '#94a3b8',
              fontWeight: 500,
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              maxWidth: '110px',
            }}>
              {user?.email || 'Connecté'}
            </p>
          </div>

          <ChevronDown
            size={14}
            style={{
              color: isDark ? 'rgba(255,255,255,0.5)' : '#94a3b8',
              transform: open ? 'rotate(180deg)' : 'rotate(0deg)',
              transition: 'transform 0.2s',
              flexShrink: 0,
            }}
          />
        </button>

        {/* ── Dropdown Menu ── */}
        {open && (
          <div
            style={{
              position: 'absolute',
              bottom: 'calc(100% + 10px)',
              left: 0,
              right: 0,
              minWidth: '220px',
              background: '#fff',
              borderRadius: '16px',
              boxShadow: '0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(0,0,0,0.08)',
              border: '1px solid #e2e8f0',
              overflow: 'hidden',
              zIndex: 50,
              animation: 'fadeSlideUp 0.18s ease',
            }}
          >
            <style>{`
              @keyframes fadeSlideUp {
                from { opacity: 0; transform: translateY(8px); }
                to   { opacity: 1; transform: translateY(0); }
              }
            `}</style>

            {/* Profile header in dropdown */}
            <div style={{
              padding: '14px 16px',
              background: 'linear-gradient(135deg, #f8faff, #eff4ff)',
              borderBottom: '1px solid #e8edf5',
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
            }}>
              <div style={{
                width: '40px',
                height: '40px',
                borderRadius: '12px',
                background: colors.bg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '15px',
                fontWeight: 800,
                color: '#fff',
                flexShrink: 0,
                boxShadow: `0 4px 12px ${colors.bg}50`,
              }}>
                {initials}
              </div>
              <div style={{ minWidth: 0 }}>
                <p style={{ fontWeight: 800, fontSize: '13px', color: '#1e293b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {user?.name || 'Utilisateur'}
                </p>
                <p style={{ fontSize: '11px', color: '#64748b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {user?.email || ''}
                </p>
              </div>
            </div>

            {/* Actions */}
            <div style={{ padding: '6px' }}>
              <button
                type="button"
                onClick={openEdit}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '10px',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '13px',
                  fontWeight: 600,
                  color: '#334155',
                  transition: 'background 0.15s',
                  textAlign: 'left',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = '#f1f5f9'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
              >
                <div style={{ width: '30px', height: '30px', borderRadius: '8px', background: '#dbeafe', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <Pencil size={14} style={{ color: '#1d4ed8' }} />
                </div>
                Modifier le profil
              </button>

              <div style={{ height: '1px', background: '#f1f5f9', margin: '4px 0' }} />

              <button
                type="button"
                onClick={openPwd}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '10px',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '13px',
                  fontWeight: 600,
                  color: '#334155',
                  transition: 'background 0.15s',
                  textAlign: 'left',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = '#f1f5f9'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
              >
                <div style={{ width: '30px', height: '30px', borderRadius: '8px', background: '#ede9fe', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <Lock size={14} style={{ color: '#7c3aed' }} />
                </div>
                Modifier mot de passe
              </button>

              <div style={{ height: '1px', background: '#f1f5f9', margin: '4px 0' }} />

              <button
                type="button"
                onClick={handleLogout}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '10px',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '13px',
                  fontWeight: 600,
                  color: '#dc2626',
                  transition: 'background 0.15s',
                  textAlign: 'left',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = '#fef2f2'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
              >
                <div style={{ width: '30px', height: '30px', borderRadius: '8px', background: '#fee2e2', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <LogOut size={14} style={{ color: '#dc2626' }} />
                </div>
                Déconnexion
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ── Profile Edit Modal ── */}
      {editOpen && (
        <div
          style={{
            position: 'fixed',
            inset: 0,
            zIndex: 1000,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '16px',
            background: 'rgba(15,23,42,0.65)',
            backdropFilter: 'blur(6px)',
            animation: 'fadeIn 0.2s ease',
          }}
          onClick={(e) => { if (e.target === e.currentTarget) setEditOpen(false); }}
        >
          <style>{`
            @keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
            @keyframes slideUp { from { opacity:0; transform:translateY(24px) scale(0.98) } to { opacity:1; transform:translateY(0) scale(1) } }
            .profile-input:focus { border-color: #3b82f6 !important; background: #fff !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.12) !important; }
          `}</style>

          <div
            style={{
              background: '#fff',
              borderRadius: '24px',
              width: '100%',
              maxWidth: '500px',
              boxShadow: '0 32px 80px rgba(0,0,0,0.25)',
              border: '1px solid #e2e8f0',
              overflow: 'hidden',
              animation: 'slideUp 0.25s ease',
            }}
          >
            {/* Modal Header */}
            <div style={{
              padding: '24px 24px 0',
              background: 'linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%)',
              paddingBottom: '32px',
              position: 'relative',
            }}>
              <button
                type="button"
                onClick={() => setEditOpen(false)}
                style={{
                  position: 'absolute',
                  top: '16px',
                  right: '16px',
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  background: 'rgba(255,255,255,0.15)',
                  border: 'none',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#fff',
                  transition: 'background 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = 'rgba(255,255,255,0.25)'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'rgba(255,255,255,0.15)'; }}
              >
                <X size={16} />
              </button>

              {/* Avatar large */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
                <div style={{
                  width: '64px',
                  height: '64px',
                  borderRadius: '18px',
                  background: colors.bg,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: '24px',
                  fontWeight: 900,
                  color: '#fff',
                  boxShadow: `0 8px 24px ${colors.bg}70`,
                  border: '3px solid rgba(255,255,255,0.3)',
                  flexShrink: 0,
                }}>
                  {initials}
                </div>
                <div>
                  <p style={{ fontSize: '11px', fontWeight: 700, color: 'rgba(255,255,255,0.6)', textTransform: 'uppercase', letterSpacing: '1px', marginBottom: '4px' }}>
                    Mon Profil
                  </p>
                  <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#fff', margin: 0 }}>
                    {user?.name || 'Utilisateur'}
                  </h2>
                  <p style={{ fontSize: '12px', color: 'rgba(255,255,255,0.65)', marginTop: '2px' }}>
                    {user?.email || ''}
                  </p>
                </div>
              </div>
            </div>

            {/* Modal Body */}
            <form onSubmit={handleSubmit} style={{ padding: '24px' }}>

              {/* Alerts */}
              {message && (
                <div style={{
                  display: 'flex', alignItems: 'center', gap: '10px',
                  padding: '12px 14px', borderRadius: '12px',
                  background: '#f0fdf4', border: '1px solid #bbf7d0',
                  color: '#15803d', fontSize: '13px', fontWeight: 600,
                  marginBottom: '16px',
                }}>
                  <CheckCircle2 size={16} style={{ flexShrink: 0 }} />
                  {message}
                </div>
              )}
              {error && (
                <div style={{
                  display: 'flex', alignItems: 'center', gap: '10px',
                  padding: '12px 14px', borderRadius: '12px',
                  background: '#fef2f2', border: '1px solid #fecaca',
                  color: '#dc2626', fontSize: '13px', fontWeight: 600,
                  marginBottom: '16px',
                }}>
                  <AlertCircle size={16} style={{ flexShrink: 0 }} />
                  {error}
                </div>
              )}

              {/* Section 1 — Informations */}
              <div style={{ marginBottom: '20px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '14px' }}>
                  <div style={{ width: '28px', height: '28px', borderRadius: '8px', background: '#dbeafe', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <User size={14} style={{ color: '#1d4ed8' }} />
                  </div>
                  <p style={{ fontSize: '12px', fontWeight: 800, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.8px' }}>
                    Informations personnelles
                  </p>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {/* Nom */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                      Nom complet
                    </label>
                    <div style={{ position: 'relative' }}>
                      <User size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                      <input
                        type="text"
                        value={form.name}
                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                        className="profile-input"
                        style={inputStyle}
                        placeholder="Votre nom complet"
                      />
                    </div>
                  </div>

                  {/* Email */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                      Adresse e-mail
                    </label>
                    <div style={{ position: 'relative' }}>
                      <Mail size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                      <input
                        type="email"
                        value={form.email}
                        onChange={(e) => setForm({ ...form, email: e.target.value })}
                        className="profile-input"
                        style={inputStyle}
                        placeholder="votre@email.com"
                      />
                    </div>
                  </div>
                </div>
              </div>

              {/* Section 2 — Mot de passe */}
              <div style={{
                padding: '16px',
                borderRadius: '14px',
                background: '#f8fafc',
                border: '1px solid #e2e8f0',
                marginBottom: '20px',
              }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '14px' }}>
                  <div style={{ width: '28px', height: '28px', borderRadius: '8px', background: '#ede9fe', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <ShieldCheck size={14} style={{ color: '#7c3aed' }} />
                  </div>
                  <p style={{ fontSize: '12px', fontWeight: 800, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.8px' }}>
                    Changer le mot de passe
                    <span style={{ fontSize: '11px', fontWeight: 500, textTransform: 'none', marginLeft: '6px', color: '#94a3b8' }}>(optionnel)</span>
                  </p>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  {/* Nouveau mot de passe */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                      Nouveau mot de passe
                    </label>
                    <div style={{ position: 'relative' }}>
                      <Lock size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                      <input
                        type={showPwd ? 'text' : 'password'}
                        value={passwordForm.password}
                        onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })}
                        className="profile-input"
                        style={{ ...inputStyle, paddingRight: '40px' }}
                        placeholder="••••••••"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPwd(p => !p)}
                        style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '4px', display: 'flex' }}
                      >
                        {showPwd ? <EyeOff size={15} /> : <Eye size={15} />}
                      </button>
                    </div>
                  </div>

                  {/* Confirmation */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                      Confirmation
                    </label>
                    <div style={{ position: 'relative' }}>
                      <Lock size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                      <input
                        type={showPwdConfirm ? 'text' : 'password'}
                        value={passwordForm.password_confirmation}
                        onChange={(e) => setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })}
                        className="profile-input"
                        style={{
                          ...inputStyle,
                          paddingRight: '40px',
                          borderColor: passwordForm.password && passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation ? '#ef4444' : '#e2e8f0',
                        }}
                        placeholder="••••••••"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPwdConfirm(p => !p)}
                        style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '4px', display: 'flex' }}
                      >
                        {showPwdConfirm ? <EyeOff size={15} /> : <Eye size={15} />}
                      </button>
                    </div>
                    {passwordForm.password && passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation && (
                      <p style={{ fontSize: '11px', color: '#ef4444', fontWeight: 600, marginTop: '4px' }}>
                        Les mots de passe ne correspondent pas.
                      </p>
                    )}
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
                <button
                  type="button"
                  onClick={() => setEditOpen(false)}
                  style={{
                    padding: '10px 20px',
                    borderRadius: '12px',
                    border: '1.5px solid #e2e8f0',
                    background: '#fff',
                    color: '#475569',
                    fontSize: '13px',
                    fontWeight: 700,
                    cursor: 'pointer',
                    transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { e.currentTarget.style.background = '#f1f5f9'; }}
                  onMouseLeave={e => { e.currentTarget.style.background = '#fff'; }}
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  style={{
                    padding: '10px 24px',
                    borderRadius: '12px',
                    border: 'none',
                    background: loading ? '#93c5fd' : 'linear-gradient(135deg, #1d4ed8, #4338ca)',
                    color: '#fff',
                    fontSize: '13px',
                    fontWeight: 700,
                    cursor: loading ? 'not-allowed' : 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    boxShadow: loading ? 'none' : '0 4px 14px rgba(29,78,216,0.35)',
                    transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { if (!loading) e.currentTarget.style.transform = 'translateY(-1px)'; }}
                  onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; }}
                >
                  {loading && <Loader2 size={15} style={{ animation: 'spin 1s linear infinite' }} />}
                  <style>{`@keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }`}</style>
                  {loading ? 'Enregistrement...' : 'Enregistrer les modifications'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ── Change Password Modal ── */}
      {pwdOpen && (
        <div
          style={{
            position: 'fixed',
            inset: 0,
            zIndex: 1000,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '16px',
            background: 'rgba(15,23,42,0.65)',
            backdropFilter: 'blur(6px)',
            animation: 'fadeIn 0.2s ease',
          }}
          onClick={(e) => { if (e.target === e.currentTarget) setPwdOpen(false); }}
        >
          <style>{`
            @keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
            @keyframes slideUp { from { opacity:0; transform:translateY(24px) scale(0.98) } to { opacity:1; transform:translateY(0) scale(1) } }
            .pwd-input:focus { border-color: #7c3aed !important; background: #fff !important; box-shadow: 0 0 0 3px rgba(124,58,237,0.12) !important; }
          `}</style>

          <div
            style={{
              background: '#fff',
              borderRadius: '24px',
              width: '100%',
              maxWidth: '440px',
              boxShadow: '0 32px 80px rgba(0,0,0,0.25)',
              border: '1px solid #e2e8f0',
              overflow: 'hidden',
              animation: 'slideUp 0.25s ease',
            }}
          >
            {/* Header */}
            <div style={{
              padding: '24px 24px 28px',
              background: 'linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%)',
              position: 'relative',
            }}>
              <button
                type="button"
                onClick={() => setPwdOpen(false)}
                style={{
                  position: 'absolute', top: '16px', right: '16px',
                  width: '32px', height: '32px', borderRadius: '8px',
                  background: 'rgba(255,255,255,0.15)', border: 'none',
                  cursor: 'pointer', display: 'flex', alignItems: 'center',
                  justifyContent: 'center', color: '#fff', transition: 'background 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = 'rgba(255,255,255,0.25)'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'rgba(255,255,255,0.15)'; }}
              >
                <X size={16} />
              </button>
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
                <div style={{
                  width: '52px', height: '52px', borderRadius: '16px',
                  background: 'rgba(255,255,255,0.2)', display: 'flex',
                  alignItems: 'center', justifyContent: 'center',
                  border: '2px solid rgba(255,255,255,0.3)', flexShrink: 0,
                }}>
                  <ShieldCheck size={24} style={{ color: '#fff' }} />
                </div>
                <div>
                  <p style={{ fontSize: '11px', fontWeight: 700, color: 'rgba(255,255,255,0.6)', textTransform: 'uppercase', letterSpacing: '1px', marginBottom: '4px' }}>
                    Sécurité du compte
                  </p>
                  <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#fff', margin: 0 }}>
                    Modifier le mot de passe
                  </h2>
                </div>
              </div>
            </div>

            {/* Body */}
            <form onSubmit={handleChangePassword} style={{ padding: '24px' }}>

              {/* Alerts */}
              {pwdMessage && (
                <div style={{
                  display: 'flex', alignItems: 'center', gap: '10px',
                  padding: '12px 14px', borderRadius: '12px',
                  background: '#f0fdf4', border: '1px solid #bbf7d0',
                  color: '#15803d', fontSize: '13px', fontWeight: 600,
                  marginBottom: '16px',
                }}>
                  <CheckCircle2 size={16} style={{ flexShrink: 0 }} />
                  {pwdMessage}
                </div>
              )}
              {pwdError && (
                <div style={{
                  display: 'flex', alignItems: 'center', gap: '10px',
                  padding: '12px 14px', borderRadius: '12px',
                  background: '#fef2f2', border: '1px solid #fecaca',
                  color: '#dc2626', fontSize: '13px', fontWeight: 600,
                  marginBottom: '16px',
                }}>
                  <AlertCircle size={16} style={{ flexShrink: 0 }} />
                  {pwdError}
                </div>
              )}

              <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>

                {/* Mot de passe actuel */}
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                    Mot de passe actuel
                  </label>
                  <div style={{ position: 'relative' }}>
                    <Lock size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    <input
                      type={showCurrentPwd ? 'text' : 'password'}
                      value={pwdForm.current_password}
                      onChange={(e) => setPwdForm({ ...pwdForm, current_password: e.target.value })}
                      className="pwd-input"
                      style={{ ...inputStyle, paddingRight: '40px' }}
                      placeholder="Votre mot de passe actuel"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowCurrentPwd(p => !p)}
                      style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '4px', display: 'flex' }}
                    >
                      {showCurrentPwd ? <EyeOff size={15} /> : <Eye size={15} />}
                    </button>
                  </div>
                </div>

                <div style={{ height: '1px', background: '#f1f5f9', margin: '2px 0' }} />

                {/* Nouveau mot de passe */}
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                    Nouveau mot de passe
                  </label>
                  <div style={{ position: 'relative' }}>
                    <Lock size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    <input
                      type={showNewPwd ? 'text' : 'password'}
                      value={pwdForm.password}
                      onChange={(e) => setPwdForm({ ...pwdForm, password: e.target.value })}
                      className="pwd-input"
                      style={{ ...inputStyle, paddingRight: '40px' }}
                      placeholder="Minimum 8 caractères"
                      required
                      minLength={8}
                    />
                    <button
                      type="button"
                      onClick={() => setShowNewPwd(p => !p)}
                      style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '4px', display: 'flex' }}
                    >
                      {showNewPwd ? <EyeOff size={15} /> : <Eye size={15} />}
                    </button>
                  </div>
                </div>

                {/* Confirmation */}
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#374151', marginBottom: '6px' }}>
                    Confirmer le nouveau mot de passe
                  </label>
                  <div style={{ position: 'relative' }}>
                    <Lock size={15} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
                    <input
                      type={showConfirmPwd ? 'text' : 'password'}
                      value={pwdForm.password_confirmation}
                      onChange={(e) => setPwdForm({ ...pwdForm, password_confirmation: e.target.value })}
                      className="pwd-input"
                      style={{
                        ...inputStyle,
                        paddingRight: '40px',
                        borderColor: pwdForm.password && pwdForm.password_confirmation && pwdForm.password !== pwdForm.password_confirmation ? '#ef4444' : '#e2e8f0',
                      }}
                      placeholder="••••••••"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPwd(p => !p)}
                      style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '4px', display: 'flex' }}
                    >
                      {showConfirmPwd ? <EyeOff size={15} /> : <Eye size={15} />}
                    </button>
                  </div>
                  {pwdForm.password && pwdForm.password_confirmation && pwdForm.password !== pwdForm.password_confirmation && (
                    <p style={{ fontSize: '11px', color: '#ef4444', fontWeight: 600, marginTop: '4px' }}>
                      Les mots de passe ne correspondent pas.
                    </p>
                  )}
                </div>
              </div>

              {/* Actions */}
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '24px' }}>
                <button
                  type="button"
                  onClick={() => setPwdOpen(false)}
                  style={{
                    padding: '10px 20px', borderRadius: '12px',
                    border: '1.5px solid #e2e8f0', background: '#fff',
                    color: '#475569', fontSize: '13px', fontWeight: 700,
                    cursor: 'pointer', transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { e.currentTarget.style.background = '#f1f5f9'; }}
                  onMouseLeave={e => { e.currentTarget.style.background = '#fff'; }}
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={pwdLoading || (pwdForm.password && pwdForm.password_confirmation && pwdForm.password !== pwdForm.password_confirmation)}
                  style={{
                    padding: '10px 24px', borderRadius: '12px', border: 'none',
                    background: pwdLoading ? '#c4b5fd' : 'linear-gradient(135deg, #4c1d95, #6d28d9)',
                    color: '#fff', fontSize: '13px', fontWeight: 700,
                    cursor: pwdLoading ? 'not-allowed' : 'pointer',
                    display: 'inline-flex', alignItems: 'center', gap: '8px',
                    boxShadow: pwdLoading ? 'none' : '0 4px 14px rgba(109,40,217,0.35)',
                    transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { if (!pwdLoading) e.currentTarget.style.transform = 'translateY(-1px)'; }}
                  onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; }}
                >
                  {pwdLoading && <Loader2 size={15} style={{ animation: 'spin 1s linear infinite' }} />}
                  <style>{`@keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }`}</style>
                  {pwdLoading ? 'Modification...' : 'Changer le mot de passe'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
};

export default UserMenu;
