import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Lock, Mail, KeyRound, AlertCircle, Loader2, X, ShieldCheck,
  FileText, Receipt, ArrowRight, User
} from 'lucide-react';
import api from '../api/axios';

export default function AuthNavigationModal({ isOpen, onClose, target }) {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  if (!isOpen || !target) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await api.post('/login', { email, password });
      const { token, user } = response.data;

      // Vérification du rôle si requis pour la section cible
      if (target.requiredRole && user.role !== target.requiredRole) {
        setError(
          target.requiredRole === 'directeur'
            ? "Accès refusé : Seul le compte Directeur est autorisé à accéder à cet espace."
            : `Accès refusé : Rôle '${target.requiredRole}' requis pour cette section.`
        );
        setLoading(false);
        return;
      }

      // Sauvegarde des identifiants et du token
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      api.defaults.headers.common.Authorization = `Bearer ${token}`;

      // Réinitialiser le formulaire
      setEmail('');
      setPassword('');
      onClose();

      // Redirection vers la page demandée
      navigate(target.route);
    } catch (err) {
      console.error('Erreur authentification navigation:', err);
      const msg =
        err.response?.data?.message ||
        err.response?.data?.errors?.email?.[0] ||
        'Identifiants incorrects. Veuillez réessayer.';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const getTargetIcon = () => {
    if (target.route === '/directeur') return <ShieldCheck className="text-indigo-600" size={26} />;
    if (target.route === '/bons-commande') return <Receipt className="text-blue-600" size={26} />;
    return <FileText className="text-emerald-600" size={26} />;
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden flex flex-col transition-all">
        {/* En-tête */}
        <div className="px-6 pt-6 pb-4 border-b border-slate-100 flex items-start justify-between bg-slate-50/60">
          <div className="flex items-center gap-3.5">
            <div className="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
              {getTargetIcon()}
            </div>
            <div>
              <span className="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">
                Authentification Requise
              </span>
              <h3 className="text-lg font-black text-slate-900 leading-tight">
                {target.title || 'Accès sécurisé'}
              </h3>
            </div>
          </div>
          <button
            onClick={() => {
              setError('');
              setEmail('');
              setPassword('');
              onClose();
            }}
            className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 rounded-xl transition-colors"
          >
            <X size={20} />
          </button>
        </div>

        {/* Formulaire */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <p className="text-xs text-slate-500 font-medium leading-relaxed">
            Veuillez saisir vos identifiants pour confirmer vos accès et accéder à{' '}
            <strong className="text-slate-800">{target.title}</strong>.
          </p>

          {error && (
            <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-xs font-semibold flex items-start gap-2.5">
              <AlertCircle size={16} className="shrink-0 mt-0.5 text-rose-600" />
              <span>{error}</span>
            </div>
          )}

          <div className="space-y-1.5">
            <label className="text-xs font-bold text-slate-700 block">Email ou Identifiant</label>
            <div className="relative">
              <Mail size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="email"
                required
                autoFocus
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder={target.requiredRole === 'directeur' ? 'directeur@onca.ma' : 'admin@onca.ma'}
                className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-bold text-slate-700 block">Mot de passe</label>
            <div className="relative">
              <KeyRound size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
              />
            </div>
          </div>

          <div className="pt-2 flex items-center justify-end gap-2.5">
            <button
              type="button"
              onClick={() => {
                setError('');
                setEmail('');
                setPassword('');
                onClose();
              }}
              className="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition shadow-2xs"
            >
              Annuler
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-md shadow-blue-600/20 flex items-center gap-2 disabled:opacity-60"
            >
              {loading ? (
                <>
                  <Loader2 size={14} className="animate-spin" />
                  <span>Vérification...</span>
                </>
              ) : (
                <>
                  <span>Valider & Accéder</span>
                  <ArrowRight size={14} />
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
