import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Plus, Trash2, Save, AlertCircle } from 'lucide-react';

export default function NouvelleNotification() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    numero: '',
    exercice: new Date().getFullYear(),
    date_notification: new Date().toISOString().split('T')[0],
    montant: '',
    objet: '',
    reference: '',
    observations: '',
    lignes: [{
      article: '',
      paragraphe: '',
      ligne_budgetaire: '',
      libelle: '',
      reports: 0,
      diminution_report: 0,
      credits_neufs: 0,
      diminution_credit_neuf: 0,
      credits_engagements: 0
    }]
  });

  const handleLineChange = (index, field, value) => {
    const newLignes = [...formData.lignes];
    newLignes[index][field] = value;
    setFormData({ ...formData, lignes: newLignes });
  };

  const addLine = () => {
    setFormData({
      ...formData,
      lignes: [...formData.lignes, {
        article: '',
        paragraphe: '',
        ligne_budgetaire: '',
        libelle: '',
        reports: 0,
        diminution_report: 0,
        credits_neufs: 0,
        diminution_credit_neuf: 0,
        credits_engagements: 0
      }]
    });
  };

  const removeLine = (index) => {
    if (formData.lignes.length > 1) {
      const newLignes = formData.lignes.filter((_, i) => i !== index);
      setFormData({ ...formData, lignes: newLignes });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await api.post('/notifications', formData);
      navigate('/notifications');
    } catch (err) {
      console.error(err);
      setError(err.response?.data?.message || 'Erreur lors de la création de la notification.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-5 sm:px-10 lg:px-14">
      <div className="max-w-6xl mx-auto">
        <header className="mb-8 flex items-center gap-4">
          <Link to="/notifications" className="p-2 bg-white border border-slate-200 rounded-full hover:bg-slate-100 transition"><ArrowLeft size={20} /></Link>
          <h1 className="text-3xl font-bold text-[#1e3a8a]">Nouvelle Notification Budgétaire</h1>
        </header>

        {error && <div className="mb-6 p-4 bg-red-50 text-red-700 rounded-xl flex items-center gap-2 border border-red-200"><AlertCircle size={20}/> {error}</div>}

        <form onSubmit={handleSubmit} className="space-y-8">
          <section className="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
            <h2 className="text-xl font-bold text-slate-800 mb-6 border-b pb-3">Informations Générales</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-2">N° Notification *</label>
                <input required type="text" value={formData.numero} onChange={e => setFormData({...formData, numero: e.target.value})} className="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Ex: N-01/2026" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-2">Date *</label>
                <input required type="date" value={formData.date_notification} onChange={e => setFormData({...formData, date_notification: e.target.value, exercice: new Date(e.target.value).getFullYear()})} className="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-2">Montant Global</label>
                <input type="number" step="0.01" value={formData.montant} onChange={e => setFormData({...formData, montant: e.target.value})} className="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </section>

          <section className="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
            <div className="flex justify-between items-center mb-6 border-b pb-3">
              <h2 className="text-xl font-bold text-slate-800">Lignes Budgétaires</h2>
              <button type="button" onClick={addLine} className="text-sm font-bold bg-emerald-100 text-emerald-800 px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-emerald-200 transition"><Plus size={16} /> Ajouter une ligne</button>
            </div>
            
            <div className="space-y-6">
              {formData.lignes.map((ligne, index) => {
                const total = Number(ligne.reports) + Number(ligne.credits_neufs) + Number(ligne.credits_engagements);
                return (
                  <div key={index} className="p-6 bg-slate-50 border border-slate-200 rounded-xl relative">
                    {formData.lignes.length > 1 && (
                      <button type="button" onClick={() => removeLine(index)} className="absolute top-4 right-4 text-red-500 hover:bg-red-100 p-2 rounded-lg transition"><Trash2 size={18} /></button>
                    )}
                    <h3 className="font-bold text-[#1e3a8a] mb-4">Ligne #{index + 1}</h3>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                      <div><label className="text-xs font-semibold text-slate-600">Article *</label><input required type="text" value={ligne.article} onChange={e => handleLineChange(index, 'article', e.target.value)} className="w-full p-2 text-sm border rounded-lg" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Paragraphe *</label><input required type="text" value={ligne.paragraphe} onChange={e => handleLineChange(index, 'paragraphe', e.target.value)} className="w-full p-2 text-sm border rounded-lg" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Ligne *</label><input required type="text" value={ligne.ligne_budgetaire} onChange={e => handleLineChange(index, 'ligne_budgetaire', e.target.value)} className="w-full p-2 text-sm border rounded-lg" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Libellé *</label><input required type="text" value={ligne.libelle} onChange={e => handleLineChange(index, 'libelle', e.target.value)} className="w-full p-2 text-sm border rounded-lg" /></div>
                    </div>
                    
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end bg-white p-4 rounded-xl border border-slate-200">
                      <div><label className="text-xs font-semibold text-slate-600">Reports (1)</label><input type="number" step="0.01" value={ligne.reports} onChange={e => handleLineChange(index, 'reports', e.target.value)} className="w-full p-2 text-sm border rounded-lg text-right" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Dim. report</label><input type="number" step="0.01" value={ligne.diminution_report} onChange={e => handleLineChange(index, 'diminution_report', e.target.value)} className="w-full p-2 text-sm border rounded-lg text-right" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Crédits neufs (3)</label><input type="number" step="0.01" value={ligne.credits_neufs} onChange={e => handleLineChange(index, 'credits_neufs', e.target.value)} className="w-full p-2 text-sm border rounded-lg text-right" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Dim. crédit neuf</label><input type="number" step="0.01" value={ligne.diminution_credit_neuf} onChange={e => handleLineChange(index, 'diminution_credit_neuf', e.target.value)} className="w-full p-2 text-sm border rounded-lg text-right" /></div>
                      <div><label className="text-xs font-semibold text-slate-600">Crédits eng. (5)</label><input type="number" step="0.01" value={ligne.credits_engagements} onChange={e => handleLineChange(index, 'credits_engagements', e.target.value)} className="w-full p-2 text-sm border rounded-lg text-right" /></div>
                      <div className="bg-indigo-50 border border-indigo-100 p-2 rounded-lg">
                        <label className="text-xs font-bold text-indigo-700">Total (1+3+5)</label>
                        <div className="text-sm font-bold text-indigo-900 text-right mt-1">{new Intl.NumberFormat('fr-FR').format(total).replace('MAD', 'dh')}</div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </section>

          <div className="flex justify-end gap-4">
            <Link to="/notifications" className="px-6 py-3 bg-white border border-slate-300 font-bold text-slate-700 rounded-xl hover:bg-slate-50 transition">Annuler</Link>
            <button type="submit" disabled={loading} className="px-8 py-3 bg-[#1e40af] text-white font-bold rounded-xl shadow-sm hover:bg-[#1e3a8a] transition flex items-center gap-2">
              {loading ? <span className="animate-spin text-white">⌛</span> : <Save size={20} />} Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
