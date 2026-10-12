import React, { useState, useEffect } from 'react';
import api from '../api/axios';
import { CheckCircle, Clock, XCircle, AlertCircle, Loader2, Download, ArrowRightCircle } from 'lucide-react';

const EngagementTimeline = ({ engagementId }) => {
  const [timelineData, setTimelineData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [transitioning, setTransitioning] = useState(false);

  useEffect(() => {
    if (engagementId) {
      fetchTimeline();
    }
  }, [engagementId]);

  const fetchTimeline = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/engagements/workflow/${engagementId}/timeline`);
      setTimelineData(res.data);
      setError(null);
    } catch (err) {
      setError("Erreur lors de la récupération de la timeline.");
    } finally {
      setLoading(false);
    }
  };

  const handleTransition = async (statut, commentaire = null) => {
    try {
      setTransitioning(true);
      await api.post('/engagements/workflow/transition', {
        engagement_id: engagementId,
        statut: statut,
        commentaire: commentaire
      });
      // Recharger la timeline après validation
      await fetchTimeline();
    } catch (err) {
      alert("Erreur lors de la validation de l'étape.");
    } finally {
      setTransitioning(false);
    }
  };

  const downloadDocument = async (docCode) => {
    // Cette fonction sera branchée à ton futur Endpoint de génération de PDF
    alert(`Génération du document [${docCode}] en cours de développement...`);
  };

  // On garde l'affichage si on a déjà les données pendant un rechargement léger
  if (loading && !timelineData) { 
    return (
      <div className="flex flex-col items-center justify-center p-12 text-blue-600 bg-slate-50 rounded-3xl border border-slate-100">
        <Loader2 className="animate-spin mb-3" size={32} />
        <span className="font-bold tracking-wide">Chargement de la timeline...</span>
      </div>
    );
  }

  if (error || !timelineData) {
    return (
      <div className="p-6 bg-red-50 text-red-600 rounded-2xl flex items-center gap-3 border border-red-100">
        <AlertCircle size={24} />
        <span className="font-bold">{error || "Données introuvables."}</span>
      </div>
    );
  }

  const { engagement, timeline } = timelineData;

  const getStatusIcon = (statut) => {
    switch (statut) {
      case 'valide': return <CheckCircle className="text-white" size={20} />;
      case 'en_cours': return <Clock className="text-white" size={20} />;
      case 'bloque': return <XCircle className="text-white" size={20} />;
      default: return <div className="w-3 h-3 bg-slate-300 rounded-full"></div>;
    }
  };

  const getStatusColorClass = (statut) => {
    switch (statut) {
      case 'valide': return 'bg-emerald-500 border-emerald-500 shadow-md shadow-emerald-500/20';
      case 'en_cours': return 'bg-amber-500 border-amber-500 ring-4 ring-amber-100 animate-pulse shadow-md shadow-amber-500/20';
      case 'bloque': return 'bg-red-500 border-red-500 shadow-md shadow-red-500/20';
      default: return 'bg-slate-100 border-slate-200';
    }
  };

  return (
    <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 max-w-4xl mx-auto">
      <div className="mb-10 border-b border-slate-100 pb-5">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="text-2xl font-extrabold text-slate-800">Suivi d'Avancement</h2>
            <p className="text-slate-500 text-sm mt-1 font-medium">Dossier Réf: <span className="font-mono text-slate-700">{engagement.numero || 'N/A'}</span></p>
          </div>
          <div className="text-right">
            <span className="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-widest border border-slate-200">
              Objet: {engagement.objet || 'Non défini'}
            </span>
          </div>
        </div>
      </div>

      <div className="relative border-l-2 border-slate-100 ml-6 space-y-10 pb-4">
        {timeline.map((etape, index) => {
          const isActive = etape.statut === 'en_cours';

          return (
            <div key={etape.id_theorique} className="relative pl-10 group">
              {/* Cercle de statut */}
              <div className={`absolute -left-[21px] top-1 flex items-center justify-center w-10 h-10 rounded-full border-4 border-white transition-all duration-300 ${getStatusColorClass(etape.statut)}`}>
                {getStatusIcon(etape.statut)}
              </div>

              {/* Contenu de l'étape */}
              <div className={`p-6 rounded-2xl border transition-all duration-300 ${isActive ? 'bg-amber-50/30 border-amber-200 shadow-sm' : 'bg-slate-50/50 border-slate-100 hover:border-slate-200'}`}>
                <div className="flex justify-between items-start mb-2">
                  <h3 className={`font-bold text-lg ${isActive ? 'text-amber-900' : 'text-slate-800'}`}>
                    Étape {etape.ordre} : {etape.libelle}
                  </h3>
                  <span className={`text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider ${
                    etape.statut === 'valide' ? 'bg-emerald-100 text-emerald-700' :
                    etape.statut === 'en_cours' ? 'bg-amber-100 text-amber-700' :
                    etape.statut === 'bloque' ? 'bg-red-100 text-red-700' :
                    'bg-slate-200 text-slate-500'
                  }`}>
                    {etape.statut === 'non_commence' ? 'À VENIR' : etape.statut.replace('_', ' ')}
                  </span>
                </div>

                {etape.date_realisation && (
                  <p className="text-sm text-slate-500 mb-4 font-medium flex items-center gap-1.5">
                    <CheckCircle size={14} className="text-emerald-500"/>
                    Validée le {new Date(etape.date_realisation).toLocaleDateString('fr-FR')}
                  </p>
                )}

                {/* Section des documents et bouton d'action si l'étape est active ou valide */}
                {(isActive || etape.statut === 'valide') && (
                  <div className="mt-5 pt-4 border-t border-slate-200/60 flex flex-col gap-5">
                    
                    {/* Documents */}
                    {etape.documents && etape.documents.length > 0 && (
                      <div>
                        <p className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Documents Requis</p>
                        <div className="flex flex-wrap gap-3">
                          {etape.documents.map((doc) => (
                            <button
                              key={doc.code}
                              onClick={() => downloadDocument(doc.code)}
                              className="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 hover:shadow-sm transition-all focus:ring-2 focus:ring-blue-100"
                            >
                              <Download size={16} className={isActive ? "text-amber-500" : "text-slate-400"} />
                              Générer {doc.libelle}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}

                    {/* Action de Validation - Uniquement pour l'étape en cours */}
                    {isActive && (
                      <div className="bg-white p-4 rounded-xl border border-amber-200 shadow-sm flex items-center justify-between">
                        <div>
                          <p className="text-sm font-bold text-slate-800">Terminer cette étape</p>
                          <p className="text-xs text-slate-500 mt-1">Générez tous vos documents avant de valider.</p>
                        </div>
                        <button
                          onClick={() => handleTransition('valide')}
                          disabled={transitioning}
                          className="px-6 py-2.5 bg-emerald-600 text-white font-bold text-sm rounded-xl hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {transitioning ? <Loader2 size={16} className="animate-spin" /> : <ArrowRightCircle size={18} />}
                          {transitioning ? "Validation..." : "Valider l'étape actuelle"}
                        </button>
                      </div>
                    )}

                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default EngagementTimeline;
