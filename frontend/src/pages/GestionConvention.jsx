import React from 'react';
import { Link } from 'react-router-dom';
import { Handshake, ArrowLeft, Clock, Sparkles, Landmark } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

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
          <Button asChild variant="ghost" className="mb-4 text-sm font-bold text-blue-700 hover:underline">
            <Link to="/dashboard">
            <ArrowLeft size={16} /> Retour au Tableau de bord
            </Link>
          </Button>

          <Card className="relative space-y-6 overflow-hidden">
            <div className="absolute top-0 right-0 w-40 h-40 bg-purple-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <CardHeader className="space-y-6 p-10">
              <div className="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-indigo-100 text-indigo-700 shadow-inner">
                <Handshake size={42} />
              </div>

              <div className="space-y-3">
              <Badge className="gap-1.5 bg-indigo-100 text-indigo-800 hover:bg-indigo-100">
                <Clock size={14} className="text-indigo-600" /> Module en développement
              </Badge>
              <h1 className="text-3xl font-extrabold text-[#1e3a8a]">Convention</h1>
              <p className="mx-auto max-w-lg text-base leading-relaxed text-slate-600">
                Le module <strong>Convention</strong> est en cours de développement. Cette fonctionnalité sera disponible prochainement dans une version ultérieure.
              </p>
              </div>
            </CardHeader>

            <CardContent className="flex items-center justify-center gap-4 border-t border-slate-100 px-10 pb-10 pt-4 text-xs font-semibold text-slate-500">
              <span className="flex items-center gap-1.5"><Sparkles size={14} className="text-indigo-600" /> Architecture prête</span>
              <span>•</span>
              <span>Gestion partenariale à venir</span>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}
