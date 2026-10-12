import React from 'react';
import { Link } from 'react-router-dom';
import { Handshake, ArrowLeft, Clock, Sparkles, Landmark } from 'lucide-react';

export default function GestionConvention() {
  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 lg:flex">
      {/* Sidebar matching app theme */}
      <aside className="w-full bg-[#1e3a8a] text-white lg:sticky lg:top-0 lg:h-screen lg:w-[380px] lg:flex-none lg:overflow-y-auto">
        <div className="px-8 pb-6 pt-12">
          <Link to="/dashboard" className="flex items-center gap-4">
            <div className="grid h-14 w-14 place-items-center rounded-full border-2 border-cyan-300 font-serif text-xl font-bold text-cyan-200 text-center leading-tight">
              <span>DR<br/><span className="text-[10px]">RSK</span></span>
            </div>
            <div>
              <p className="font-serif text-2xl font-bold tracking-wide">DRCA - RSK</p>
              <p className="mt-1 text-sm tracking-wider text-blue-200">MARCHÉS PUBLICS</p>
            </div>
          </Link>
        </div>

        <nav className="px-5 pb-8">
          <Link to="/dashboard" className="mb-4 flex items-center gap-4 rounded-xl px-4 py-4 hover:bg-white/5 transition-colors">
            <span className="grid h-11 w-11 place-items-center rounded-full bg-cyan-300 text-[#1e3a8a]"><Landmark size={20} /></span>
            <span>
              <span className="block font-serif text-xl font-semibold">Tableau de bord</span>
              <span className="text-sm text-blue-200">Vue d'ensemble du dossier</span>
            </span>
          </Link>
        </nav>
      </aside>

      {/* Main Content */}
      <main className="min-w-0 flex-1 px-5 py-10 sm:px-10 lg:px-14 lg:py-14 flex items-center justify-center">
        <div className="max-w-2xl w-full text-center space-y-8">
          <Link to="/dashboard" className="inline-flex items-center gap-2 text-sm font-bold text-blue-700 hover:underline mb-4">
            <ArrowLeft size={16} /> Retour au Tableau de bord
          </Link>

          <div className="bg-white rounded-3xl p-10 border border-slate-200 shadow-xl space-y-6 relative overflow-hidden">
            <div className="absolute top-0 right-0 w-40 h-40 bg-purple-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div className="grid h-20 w-20 place-items-center rounded-3xl bg-indigo-100 text-indigo-700 mx-auto shadow-inner">
              <Handshake size={42} />
            </div>

            <div className="space-y-3">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-200">
                <Clock size={14} className="text-indigo-600" /> Module en développement
              </span>
              <h1 className="text-3xl font-extrabold text-[#1e3a8a]">Convention</h1>
              <p className="text-base text-slate-600 leading-relaxed max-w-lg mx-auto">
                Le module <strong>Convention</strong> est en cours de développement. Cette fonctionnalité sera disponible prochainement dans une version ultérieure.
              </p>
            </div>

            <div className="pt-4 border-t border-slate-100 flex items-center justify-center gap-4 text-xs font-semibold text-slate-500">
              <span className="flex items-center gap-1.5"><Sparkles size={14} className="text-indigo-600" /> Architecture prête</span>
              <span>•</span>
              <span>Gestion partenariale à venir</span>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
