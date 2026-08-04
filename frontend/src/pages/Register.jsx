import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const navigate = useNavigate();

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');

    try {
      const response = await api.post('/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });

      console.log('Register response:', response);

      localStorage.removeItem('token');
      delete api.defaults.headers.common.Authorization;
      navigate('/login', {
        state: { message: 'Compte créé avec succès. Veuillez vous connecter.' },
      });
    } catch (err) {
      console.error('Register error:', err.response || err);
      const apiErrors = err.response?.data?.errors || {};
      setFieldErrors(apiErrors);
      const firstError = err.response?.data?.message || Object.values(apiErrors || {})[0];
      setError(firstError || 'Erreur lors de l\'inscription');
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
          <p className="text-slate-400 text-sm text-center mb-8">Créer un compte</p>
        </div>

        {error && (
          <div className="mb-6 rounded-2xl bg-red-600/10 border border-red-500/20 px-5 py-4 text-red-200 text-sm text-center">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">Nom complet</label>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-2"
              placeholder="Admin ONCA"
            />
            {fieldErrors.name && (
              <p className="text-red-300 text-sm mt-2">{fieldErrors.name[0]}</p>
            )}
          </div>

          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-2"
              placeholder="admin@onca.ma"
            />
            {fieldErrors.email && (
              <p className="text-red-300 text-sm mt-2">{fieldErrors.email[0]}</p>
            )}
          </div>

          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">Mot de passe</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-2"
              placeholder="********"
            />
            {fieldErrors.password && (
              <p className="text-red-300 text-sm mt-2">{fieldErrors.password[0]}</p>
            )}
          </div>

          <div>
            <label className="text-slate-300 font-medium text-sm mb-2 block">Confirmer le mot de passe</label>
            <input
              type="password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              className="w-full p-4 text-lg bg-[#3a4652] border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all mb-2"
              placeholder="********"
            />
            {fieldErrors.password_confirmation && (
              <p className="text-red-300 text-sm mt-2">{fieldErrors.password_confirmation[0]}</p>
            )}
          </div>

          <button
            type="submit"
            className="w-full bg-[#002f5d] hover:bg-[#003f7c] text-white font-bold py-4 text-xl rounded-xl transition-all shadow-lg transform hover:-translate-y-0.5"
          >
            Créer un compte
          </button>

          <div className="flex flex-col sm:flex-row sm:justify-between gap-3 text-sm text-slate-500 text-center mt-3">
            <button type="button" onClick={() => navigate('/login')} className="underline decoration-slate-500 decoration-2 hover:text-white transition-colors">
              J'ai déjà un compte
            </button>
          </div>
        </form>

        <div className="mt-8 text-center">
          <p className="text-slate-400 text-xs">© ONCA 2026 - Tous droits réservés | Direction des Systèmes d'Information</p>
        </div>
      </div>
    </div>
  );
}
