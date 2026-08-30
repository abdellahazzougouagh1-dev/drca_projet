import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  Search, 
  FileText, 
  Filter,
  CheckCircle2,
  Clock,
  AlertTriangle,
  ChevronRight,
  Calculator,
  LayoutDashboard
} from 'lucide-react';
import api from '../api/axios';

const ListeLiquidations = () => {
  const [marches, setMarches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL');

  useEffect(() => {
    fetchMarches();
  }, []);

  const fetchMarches = async () => {
    try {
      setLoading(true);
      const res = await api.get('/liquidations/marches');
      setMarches(res.data.data);
    } catch (err) {
      console.error('Erreur chargement marchés:', err);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (marche) => {
    if (marche.reste_a_liquider === 0) {
      return <span className="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold flex items-center gap-1"><CheckCircle2 size={12}/> Totalement liquidé</span>;
    }
    if (marche.total_liquide > 0) {
      return <span className="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold flex items-center gap-1"><Clock size={12}/> Partiellement liquidé</span>;
    }
    return <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold flex items-center gap-1"><AlertTriangle size={12}/> À liquider</span>;
  };

  const filteredMarches = marches.filter(m => {
    const searchMatch = (
      m.num_marche?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      m.titulaire?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      m.objet_marche?.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    if (statusFilter === 'ALL') return searchMatch;
    if (statusFilter === 'COMPLETE' && m.reste_a_liquider === 0) return searchMatch;
    if (statusFilter === 'PARTIAL' && m.total_liquide > 0 && m.reste_a_liquider > 0) return searchMatch;
    if (statusFilter === 'PENDING' && m.total_liquide === 0) return searchMatch;
    
    return false;
  });

  return (
    <div className="min-h-screen bg-slate-50 font-sans flex flex-col p-6">
        <main className="flex-1 w-full max-w-7xl mx-auto">
          <div className="mb-4">
             <Link to="/dashboard" className="text-sm font-semibold text-indigo-600 hover:underline">
               ← Retour au tableau de bord
             </Link>
          </div>
          <div className="space-y-6">
            
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
              <div>
                <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                  <Calculator className="text-indigo-600" size={32} />
                  Liquidations
                </h1>
                <p className="text-slate-500 mt-2">Gérez les services faits, factures et décomptes des marchés engagés.</p>
              </div>
            </div>

            <div className="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-4 items-center justify-between">
              <div className="relative w-full md:w-96">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                <input
                  type="text"
                  placeholder="Rechercher (N° Marché, Titulaire, Objet)..."
                  className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              <div className="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0 hide-scrollbar">
                <Filter className="text-slate-400 ml-2" size={18} />
                <span className="text-sm text-slate-500 font-medium whitespace-nowrap">Filtrer par statut:</span>
                <select
                  className="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                >
                  <option value="ALL">Tous les marchés</option>
                  <option value="PENDING">À liquider</option>
                  <option value="PARTIAL">Partiellement liquidés</option>
                  <option value="COMPLETE">Totalement liquidés</option>
                </select>
              </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                      <th className="p-4 font-semibold">N° Marché</th>
                      <th className="p-4 font-semibold">Objet</th>
                      <th className="p-4 font-semibold">Titulaire</th>
                      <th className="p-4 font-semibold text-right">Montant TTC</th>
                      <th className="p-4 font-semibold text-right">Déjà Liquidé</th>
                      <th className="p-4 font-semibold text-right">Reste à Liquider</th>
                      <th className="p-4 font-semibold text-center">Statut</th>
                      <th className="p-4 font-semibold text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {loading ? (
                      <tr>
                        <td colSpan="8" className="p-8 text-center text-slate-500">
                          <div className="flex justify-center items-center gap-3">
                            <div className="w-5 h-5 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                            Chargement des marchés...
                          </div>
                        </td>
                      </tr>
                    ) : filteredMarches.length === 0 ? (
                      <tr>
                        <td colSpan="8" className="p-12 text-center text-slate-500 flex flex-col items-center">
                          <LayoutDashboard size={48} className="text-slate-300 mb-4" />
                          <p className="text-lg font-medium text-slate-600">Aucun marché disponible pour la liquidation.</p>
                          <p className="text-sm mt-1">Seuls les marchés avec un engagement validé apparaissent ici.</p>
                        </td>
                      </tr>
                    ) : (
                      filteredMarches.map((marche) => (
                        <tr key={marche.id} className="hover:bg-slate-50 transition-colors group">
                          <td className="p-4">
                            <span className="font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded-lg text-sm border border-indigo-100">{marche.num_marche}</span>
                          </td>
                          <td className="p-4">
                            <p className="text-sm font-medium text-slate-800 line-clamp-2" title={marche.objet_marche}>
                              {marche.objet_marche}
                            </p>
                          </td>
                          <td className="p-4">
                            <div className="flex items-center gap-2">
                              <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs">
                                {marche.titulaire?.substring(0,2).toUpperCase()}
                              </div>
                              <span className="text-sm font-semibold text-slate-700">{marche.titulaire}</span>
                            </div>
                          </td>
                          <td className="p-4 text-right">
                            <span className="text-sm font-bold text-slate-800">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(marche.montant).replace('MAD', 'dh')}</span>
                          </td>
                          <td className="p-4 text-right">
                            <span className="text-sm font-semibold text-emerald-600">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(marche.total_liquide).replace('MAD', 'dh')}</span>
                          </td>
                          <td className="p-4 text-right">
                            <span className="text-sm font-bold text-amber-600">{new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(marche.reste_a_liquider).replace('MAD', 'dh')}</span>
                          </td>
                          <td className="p-4 text-center">
                            {getStatusBadge(marche)}
                          </td>
                          <td className="p-4 text-center">
                            <Link
                              to={`/liquidations/marches/${marche.id}`}
                              className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-xl text-sm font-bold transition-all shadow-sm"
                            >
                              Gérer <ChevronRight size={16} />
                            </Link>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </main>
    </div>
  );
};

export default ListeLiquidations;
