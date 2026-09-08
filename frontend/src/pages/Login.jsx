import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../api/axios';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');

    try {
      const response = await api.post('/login', { email, password });
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      api.defaults.headers.common.Authorization = `Bearer ${response.data.token}`;
      navigate(response.data.user?.role === 'directeur' ? '/directeur' : '/consultations');
    } catch (err) {
      setError(err.response?.data?.message || 'Email ou mot de passe incorrect');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#1e252b] px-6 py-10">
      <div className="w-full max-w-xl p-10 bg-[#2d3741]/90 rounded-3xl shadow-2xl border border-slate-700/50">
        <div className="text-center">
          <img
            src="http://127.0.0.1:8000/images/logo-onca.png"
            alt="Logo ONCA"
            className="h-28 w-auto mx-auto mb-4 object-contain bg-white p-2 rounded-xl"
          />
          <h1 className="text-white text-xl font-bold text-center tracking-wide mb-1">
            OFFICE NATIONAL DU CONSEIL AGRICOLE
          </h1>
          <p className="text-slate-400 text-sm text-center mb-8">
            Portail d'authentification
          </p>
        </div>

        {error && (
          <div className="mb-6 rounded-2xl bg-red-600/10 border border-red-500/20 px-5 py-4 text-red-200 text-sm text-center">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">
              Email
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-6"
              placeholder="admin@onca.ma"
            />
          </div>

          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">
              Mot de passe
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-6"
              placeholder="admin123"
            />
          </div>

          <button
            type="submit"
            className="w-full bg-[#002f5d] hover:bg-[#003f7c] text-white font-bold py-4 text-xl rounded-xl transition-all shadow-lg transform hover:-translate-y-0.5"
          >
            Se connecter
          </button>

          <div className="flex flex-col sm:flex-row sm:justify-between gap-3 text-sm text-slate-500 text-center mt-3">
            <button type="button" className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors">
              Mot de passe oublié ?
            </button>
            <Link to="/register" className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors">
              Créer un compte
            </Link>
          </div>
        </form>

        <div className="mt-8 text-center">
          <p className="text-slate-400 text-xs">
            © ONCA 2026 - Tous droits réservés | Direction des Systèmes d'Information
          </p>
          <p className="text-slate-500 text-xs mt-2 space-x-2">
            <span className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors cursor-pointer">Mentions Légales</span>
            <span className="text-slate-500">|</span>
            <span className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors cursor-pointer">Contact</span>
            <span className="text-slate-500">|</span>
            <span className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors cursor-pointer">Aide</span>
          </p>
        </div>
      </div>
    </div>
  );
}
