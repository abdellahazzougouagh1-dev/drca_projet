import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Mail, LockKeyhole, Loader2 } from 'lucide-react';
import api from '../api/axios';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [step, setStep] = useState('email');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const inputClass = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:outline-2 focus:outline-emerald-600';

  const submit = async (event, resend = false) => {
    event.preventDefault();
    setError('');
    setMessage('');
    if (step === 'code' && !resend && password !== confirmation) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }
    setBusy(true);
    try {
      const sending = step === 'email' || resend;
      const response = await api.post(sending ? '/forgot-password' : '/reset-password', sending
        ? { email: email.trim() }
        : { email: email.trim(), code, password, password_confirmation: confirmation });
      setMessage(response.data.message);
      setStep(sending ? 'code' : 'done');
      if (!sending) {
        setPassword('');
        setConfirmation('');
        setCode('');
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        delete api.defaults.headers.common.Authorization;
      }
    } catch (err) {
      setError(Object.values(err.response?.data?.errors || {}).flat()[0]
        || err.response?.data?.message || 'Impossible de traiter la demande. Réessayez.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <main className="min-h-screen bg-slate-100 px-4 py-12 flex items-center justify-center text-slate-800">
      <div className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <Link to="/login" className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-emerald-700"><ArrowLeft size={16} /> Retour à la connexion</Link>
        <div className="mt-8 mb-5 w-fit rounded-2xl bg-emerald-50 p-4 text-emerald-700"><LockKeyhole size={26} /></div>
        <h1 className="text-2xl font-bold tracking-tight">Mot de passe oublié</h1>
        <p className="mt-3 mb-6 text-sm leading-6 text-slate-600">{step === 'email'
          ? 'Saisissez l’adresse email de votre compte pour recevoir un code à 6 chiffres.'
          : step === 'code' ? `Saisissez le code reçu sur ${email} et choisissez un nouveau mot de passe.`
            : 'Votre mot de passe a été réinitialisé.'}</p>
        {message && <p role="status" className="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{message}</p>}
        {error && <p role="alert" className="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700">{error}</p>}
        {step === 'done' ? <Link to="/login" className="block rounded-xl bg-emerald-700 p-3 text-center font-semibold text-white">Se connecter</Link> : (
          <form onSubmit={submit} className="space-y-5">
            <fieldset disabled={busy} className="space-y-5 disabled:opacity-60">
              {step === 'email' ? <label className="block text-sm font-semibold">Adresse email
                <input autoComplete="email" type="email" required maxLength={255} value={email} onChange={(e) => setEmail(e.target.value)} className={inputClass} placeholder="vous@onca.ma" />
              </label> : <>
                <label className="block text-sm font-semibold">Code reçu par email
                  <input autoComplete="one-time-code" inputMode="numeric" pattern="[0-9]{6}" required minLength={6} maxLength={6} value={code} onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))} className={`${inputClass} text-center text-xl tracking-[0.4em]`} placeholder="000000" />
                </label>
                <label className="block text-sm font-semibold">Nouveau mot de passe
                  <input autoComplete="new-password" type="password" required minLength={8} maxLength={255} value={password} onChange={(e) => setPassword(e.target.value)} className={inputClass} />
                  <span className="mt-2 block text-xs font-normal text-slate-500">8 caractères minimum.</span>
                </label>
                <label className="block text-sm font-semibold">Confirmer le mot de passe
                  <input autoComplete="new-password" type="password" required minLength={8} maxLength={255} value={confirmation} onChange={(e) => setConfirmation(e.target.value)} className={inputClass} />
                </label>
              </>}
              <button type="submit" className="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 p-3 font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
                {busy ? <Loader2 size={18} className="animate-spin" /> : <Mail size={18} />}
                {busy ? 'Traitement…' : step === 'email' ? 'Recevoir mon code' : 'Modifier mon mot de passe'}
              </button>
              {step === 'code' && <div className="flex justify-between gap-3 text-xs">
                <button type="button" onClick={(event) => submit(event, true)} className="text-emerald-700 underline">Renvoyer le code</button>
                <button type="button" onClick={() => { setStep('email'); setCode(''); setMessage(''); setError(''); }} className="text-slate-600 underline">Changer d’adresse</button>
              </div>}
            </fieldset>
          </form>
        )}
      </div>
    </main>
  );
}
