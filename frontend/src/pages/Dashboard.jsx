import React, { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import api from '../api/axios';
import { Users, FileText, DollarSign, Loader2, Briefcase, ClipboardList, Archive, Home } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

const KPICard = ({ title, value, icon: Icon, color }) => (
  <div className="bg-white rounded-2xl p-6 shadow-md border border-slate-100 flex items-center gap-4">
    <div className={`p-4 rounded-2xl ${color} text-white shadow-sm`}>
      <Icon size={24} />
    </div>
    <div>
      <p className="text-slate-500 text-xs font-semibold uppercase tracking-wider">{title}</p>
      <h3 className="text-3xl font-extrabold text-blue-900 mt-2">{value}</h3>
    </div>
  </div>
);

export default function Dashboard() {
  const [stats, setStats] = useState({
    totalFournisseurs: 0,
    totalConsultations: 0,
    budgetGlobalTTC: 0,
  });
  const [chartData, setChartData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const location = useLocation();

  useEffect(() => {
    let mounted = true;

    const fetchData = async () => {
      try {
        setLoading(true);
        const [fournisseursRes, consultationsRes, budgetsRes] = await Promise.all([
          api.get('/fournisseurs'),
          api.get('/consultations'),
          api.get('/budgets'),
        ]);

        const totalTTC = budgetsRes.data.reduce((acc, curr) => acc + parseFloat(curr.montant_ttc || 0), 0);

        const newChartData = consultationsRes.data.map((consultation) => {
          const budget = budgetsRes.data.find((b) => b.consultation_id === consultation.id);
          return {
            name: consultation.numero_consultation,
            budgetTTC: budget ? parseFloat(budget.montant_ttc || 0) : 0,
            objet: consultation.objet_consultation,
          };
        });

        if (!mounted) return;

        setStats({
          totalFournisseurs: fournisseursRes.data.length,
          totalConsultations: consultationsRes.data.length,
          budgetGlobalTTC: totalTTC,
        });
        setChartData(newChartData);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
        if (!mounted) return;
        setError('Impossible de charger les données du tableau de bord.');
        setLoading(false);
      }
    };

    fetchData();

    return () => { mounted = false; };
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 text-slate-800">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="animate-spin text-slate-500" size={48} />
          <p className="text-slate-600 font-medium">Chargement des données...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 text-slate-800 px-6">
        <div className="bg-white border border-red-100 p-8 rounded-3xl shadow-md w-full max-w-md text-center">
          <p className="text-red-600 font-semibold">{error}</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-6 px-6 py-3 bg-red-600 text-white rounded-2xl hover:bg-red-700 transition-colors"
          >
            Réessayer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-slate-50">
      {/* Sidebar */}
      <aside className="w-64 bg-[#1e3a8a] text-white flex flex-col h-screen sticky top-0 shadow-xl">
        <div className="px-6 py-6 border-b border-blue-800">
          <Link to="/" className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">ON</div>
            <div>
              <p className="text-sm font-semibold">ERP ONCA</p>
              <p className="text-xs text-white/80">Profil Directeur</p>
            </div>
          </Link>
        </div>

        <nav className="flex-1 mt-4 overflow-y-auto">
          <ul className="space-y-1">
            <li>
              <Link to="/" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Home size={18} />
                <span>Tableau de Bord</span>
              </Link>
            </li>
            <li>
              <Link to="/fournisseurs" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/fournisseurs' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Users size={18} />
                <span>Fournisseurs</span>
              </Link>
            </li>
            <li>
              <Link to="/commission-membres" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/commission-membres' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <ClipboardList size={18} />
                <span>Commission</span>
              </Link>
            </li>
            <li>
              <Link to="/consultations" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/consultations' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <FileText size={18} />
                <span>Consultations</span>
              </Link>
            </li>
            <li>
              <Link to="/aoos" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/aoos' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Archive size={18} />
                <span>Appels d'Offres</span>
              </Link>
            </li>
            <li>
              <Link to="/aoos/nouveau" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/aoos/nouveau' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <FileText size={18} />
                <span>Nouvel AOO</span>
              </Link>
            </li>
            <li>
              <Link to="/marches" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/marches' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <Briefcase size={18} />
                <span>Marchés</span>
              </Link>
            </li>
            <li>
              <Link to="/marches/nouveau" className={`flex items-center space-x-3 px-6 py-4 text-slate-100 font-medium transition-all duration-200 rounded-xl mx-2 my-1 ${
                location.pathname === '/marches/nouveau' ? 'bg-blue-900/80 text-white border-l-4 border-cyan-400 font-bold' : 'hover:bg-blue-800/50'
              }`}>
                <DollarSign size={18} />
                <span>Nouveau Marché</span>
              </Link>
            </li>
          </ul>
        </nav>

        <div className="px-6 py-4 border-t border-blue-800 mb-6">
          <button
            onClick={() => { localStorage.removeItem('token'); window.location.href = '/login'; }}
            className="w-full text-left px-4 py-3 mx-3 bg-white/10 hover:bg-white/20 rounded-lg transition-colors text-sm"
          >
            Se déconnecter
          </button>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 p-10 overflow-y-auto">
        <div className="max-w-7xl mx-auto">
          <header className="mb-8">
            <h1 className="text-4xl font-extrabold tracking-tight text-blue-900">Tableau de Bord</h1>
            <p className="text-slate-600 mt-2 text-lg">Bienvenue sur le portail ERP ONCA - Profil Directeur.</p>
          </header>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <KPICard
              title="Total Fournisseurs"
              value={stats.totalFournisseurs}
              icon={Users}
              color="bg-cyan-500"
            />
            <KPICard
              title="Total Consultations"
              value={stats.totalConsultations}
              icon={FileText}
              color="bg-blue-500"
            />
            <KPICard
              title="Budget Global TTC"
              value={new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(stats.budgetGlobalTTC)}
              icon={DollarSign}
              color="bg-amber-500"
            />
          </div>

          <section className="bg-white rounded-3xl p-8 shadow-md border border-slate-100">
            <h2 className="text-lg font-bold text-blue-900 mb-6">Budgets par Consultation</h2>
            <div className="h-96 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={chartData} margin={{ top: 20, right: 30, left: 0, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                  <XAxis dataKey="name" tick={{ fill: '#334155', fontSize: 12 }} stroke="#64748b" />
                  <YAxis tick={{ fill: '#334155', fontSize: 12 }} stroke="#64748b" tickFormatter={(value) => `${(value / 1000).toFixed(0)}k`} />
                  <Tooltip
                    cursor={{ fill: 'rgba(59, 130, 246, 0.06)' }}
                    contentStyle={{
                      backgroundColor: '#ffffff',
                      borderRadius: '8px',
                      border: '1px solid rgba(203, 213, 225, 0.6)',
                      color: '#0f172a',
                    }}
                    labelStyle={{ color: '#0f172a', fontWeight: 'bold', marginBottom: '4px' }}
                    formatter={(value) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(value)}
                  />
                  <Legend wrapperStyle={{ paddingTop: '18px', color: '#475569' }} />
                  <Bar dataKey="budgetTTC" name="Budget TTC (MAD)" fill="#1e3a8a" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </section>
        </div>
      </main>
    </div>
  );
}
