import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { ArrowLeft, Loader2, FileText, Landmark, BarChart3 } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';

export default function DetailsNotification() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [notification, setNotification] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchNotification = async () => {
      try {
        const response = await api.get(`/notifications/${id}`);
        setNotification(response.data);
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    };
    fetchNotification();
  }, [id]);

  const formatMoney = (value) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(value).replace('MAD', 'dh');

  if (loading) return <div className="min-h-screen flex items-center justify-center bg-slate-50"><Loader2 className="animate-spin text-[#1e3a8a]" size={48} /></div>;
  if (!notification) return <div className="p-10 text-center text-red-500 font-bold">Notification introuvable</div>;

  const totalGlobal = notification.lignes?.reduce((acc, l) => acc + Number(l.total_credits), 0) || 0;
  const totalEngage = notification.lignes?.reduce((acc, l) => acc + Number(l.credits_engages), 0) || 0;
  const totalDispo = notification.lignes?.reduce((acc, l) => acc + Number(l.credits_disponibles), 0) || 0;

  // Valeurs fictives pour Liquidé et Ordonnancé en attendant leur implémentation backend complète
  const totalLiquide = 0;
  const totalOrdonnance = 0;

  const chartData = [
    { name: 'Total crédits', value: totalGlobal, color: '#4f46e5' },
    { name: 'Engagé', value: totalEngage, color: '#059669' },
    { name: 'Liquidé', value: totalLiquide, color: '#d97706' },
    { name: 'Ordonnancé', value: totalOrdonnance, color: '#dc2626' },
    { name: 'Disponible', value: totalDispo, color: '#2563eb' },
  ];
  
  // Custom Tooltip pour formater en dh
  const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white p-3 border border-slate-200 shadow-lg rounded-xl">
          <p className="font-bold text-slate-800 mb-1">{payload[0].payload.name}</p>
          <p className="font-mono text-[#1e3a8a]">{formatMoney(payload[0].value)}</p>
        </div>
      );
    }
    return null;
  };
  return (
    <div className="min-h-screen bg-slate-50 py-10 px-5 sm:px-10 lg:px-14">
      <div className="max-w-7xl mx-auto">
        <header className="mb-8 flex items-center gap-4">
          <button onClick={() => navigate(-1)} className="p-2 bg-white border border-slate-200 rounded-full hover:bg-slate-100 transition"><ArrowLeft size={20} /></button>
          <div>
            <h1 className="text-3xl font-bold text-[#1e3a8a]">Notification Budgétaire {notification.numero}</h1>
            <p className="text-slate-600 mt-1">
              Exercice {notification.exercice} - {new Date(notification.date_notification).toLocaleDateString('fr-FR')} 
              {notification.montant && <span className="ml-4 font-bold text-indigo-700">Montant Global : {formatMoney(notification.montant)}</span>}
            </p>
          </div>
        </header>

        <section className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
            <h3 className="text-sm font-bold text-indigo-700 uppercase tracking-wide">Crédits Notifiés</h3>
            <p className="text-3xl font-bold text-indigo-900 mt-2">{formatMoney(totalGlobal)}</p>
          </div>
          <div className="bg-emerald-50 border border-emerald-100 rounded-2xl p-6">
            <h3 className="text-sm font-bold text-emerald-700 uppercase tracking-wide">Crédits Engagés</h3>
            <p className="text-3xl font-bold text-emerald-900 mt-2">{formatMoney(totalEngage)}</p>
          </div>
          <div className="bg-blue-50 border border-blue-100 rounded-2xl p-6">
            <h3 className="text-sm font-bold text-blue-700 uppercase tracking-wide">Crédits Disponibles</h3>
            <p className="text-3xl font-bold text-blue-900 mt-2">{formatMoney(totalDispo)}</p>
          </div>
        </section>

        <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
          <h2 className="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <BarChart3 className="text-blue-500" /> Situation budgétaire
          </h2>
          <div className="w-full min-h-[300px]">
            <ResponsiveContainer width="100%" height={300}>
              <BarChart
                layout="vertical"
                data={chartData}
                margin={{ top: 10, right: 30, left: 40, bottom: 10 }}
              >
                <XAxis type="number" hide />
                <YAxis dataKey="name" type="category" axisLine={false} tickLine={false} tick={{ fill: '#475569', fontWeight: 600 }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} content={<CustomTooltip />} />
                <Bar dataKey="value" radius={[0, 4, 4, 0]} barSize={32} label={{ position: 'right', fill: '#475569', fontSize: 12, formatter: (val) => formatMoney(val) }}>
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </section>

        <section className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
          <div className="p-6 border-b border-slate-100"><h2 className="text-xl font-bold text-slate-800">Lignes Budgétaires</h2></div>
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase">
                  <th className="px-6 py-4">Imputation</th>
                  <th className="px-6 py-4">Libellé</th>
                  <th className="px-6 py-4 text-right">Crédits Neufs</th>
                  <th className="px-6 py-4 text-right">Total Notifié</th>
                  <th className="px-6 py-4 text-right">Engagé</th>
                  <th className="px-6 py-4 text-right">Disponible</th>
                  <th className="px-6 py-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {notification.lignes?.map((ligne) => (
                  <tr key={ligne.id} className="hover:bg-slate-50 transition">
                    <td className="px-6 py-4 font-mono text-sm text-[#1e3a8a]">{ligne.article}/{ligne.paragraphe}/{ligne.ligne_budgetaire}</td>
                    <td className="px-6 py-4 text-sm font-medium">{ligne.libelle}</td>
                    <td className="px-6 py-4 text-right font-mono text-sm">{formatMoney(ligne.credits_neufs)}</td>
                    <td className="px-6 py-4 text-right font-mono text-sm font-bold text-indigo-700">{formatMoney(ligne.total_credits)}</td>
                    <td className="px-6 py-4 text-right font-mono text-sm font-bold text-emerald-700">{formatMoney(ligne.credits_engages)}</td>
                    <td className="px-6 py-4 text-right font-mono text-sm font-bold text-blue-700">{formatMoney(ligne.credits_disponibles)}</td>
                    <td className="px-6 py-4 text-center">
                      <button
                        onClick={() => navigate('/consultations/nouvelle', { state: { ligneBudgetaireId: ligne.id } })}
                        className="px-4 py-2 bg-[#1e3a8a] text-white text-xs font-bold rounded-lg hover:bg-blue-800 transition shadow-sm"
                      >
                        Créer consultation
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        <section className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <h2 className="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2"><FileText className="text-blue-500" /> Consultations liées</h2>
          {notification.lignes?.every(l => l.consultations?.length === 0 && l.marches?.length === 0) ? (
            <p className="text-slate-500 text-center py-6 bg-slate-50 rounded-xl">Aucune consultation ou marché n'est encore lié à cette notification.</p>
          ) : (
            <div className="space-y-6">
              {notification.lignes?.filter(l => l.consultations?.length > 0 || l.marches?.length > 0).map(ligne => (
                <div key={`cons-${ligne.id}`} className="border border-slate-100 rounded-xl p-4">
                  <h4 className="font-bold text-[#1e3a8a] mb-3">Ligne : {ligne.article}/{ligne.paragraphe}/{ligne.ligne_budgetaire}</h4>
                  <ul className="space-y-2">
                    {ligne.consultations?.map(c => (
                      <li key={c.id} className="flex justify-between items-center bg-slate-50 p-3 rounded-lg">
                        <span className="font-semibold">{c.numero_consultation} - <span className="text-slate-500 text-sm">{c.objet_consultation}</span></span>
                        <Link to={`/consultations/${c.id}`} className="text-sm font-bold text-blue-600 hover:underline">Voir</Link>
                      </li>
                    ))}
                    {ligne.marches?.map(m => (
                      <li key={m.id} className="flex justify-between items-center bg-slate-50 p-3 rounded-lg">
                        <span className="font-semibold">{m.num_marche} (Marché) - <span className="text-slate-500 text-sm">{m.objet_marche}</span></span>
                        <Link to={`/marches/${m.id}`} className="text-sm font-bold text-blue-600 hover:underline">Voir</Link>
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
