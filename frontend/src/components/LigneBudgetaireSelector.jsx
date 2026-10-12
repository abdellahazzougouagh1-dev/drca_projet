import React, { useState, useEffect, useMemo, useRef } from 'react';
import api from '../api/axios';
import { Search, Bookmark, ChevronDown, Check, X, Sparkles, BookOpen, Layers } from 'lucide-react';

const LigneBudgetaireSelector = ({
  selectedCode,
  selectedArt,
  selectedPar,
  selectedLig,
  typeBudget,
  onSelect,
  className = '',
}) => {
  const [lignes, setLignes] = useState([]);
  const [loading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('ALL'); // 'ALL' | 'Investissement' | 'Fonctionnement'
  const containerRef = useRef(null);

  // Charger les lignes depuis l'API
  useEffect(() => {
    const fetchLignes = async () => {
      setLoading(true);
      try {
        const res = await api.get('/lignes-budgetaires');
        setLignes(res.data || []);
      } catch (err) {
        console.error('Erreur chargement nomenclature budgétaire', err);
      } finally {
        setLoading(false);
      }
    };
    fetchLignes();
  }, []);

  // Fermer la modal/dropdown lors d'un clic extérieur
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Filtrer selon le type de budget et la recherche
  const filteredLignes = useMemo(() => {
    return lignes.filter((item) => {
      // Filtre par type
      if (filterType !== 'ALL') {
        const itemType = (item.type_budget || '').toLowerCase();
        if (!itemType.includes(filterType.toLowerCase())) {
          return false;
        }
      }

      // Filtre par recherche textuelle
      if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase().trim();
        const code = (item.code_imputation || '').toLowerCase();
        const art = (item.article || '').toLowerCase();
        const par = (item.paragraphe || '').toLowerCase();
        const lig = (item.ligne || '').toLowerCase();
        const intitule = (item.intitule || '').toLowerCase();

        return (
          code.includes(query) ||
          intitule.includes(query) ||
          art.includes(query) ||
          par.includes(query) ||
          lig.includes(query) ||
          `${art}${par}${lig}`.includes(query)
        );
      }

      return true;
    });
  }, [lignes, filterType, searchQuery]);

  // Trouver la ligne actuellement sélectionnée
  const currentSelection = useMemo(() => {
    if (!selectedCode && !selectedArt) return null;
    return (
      lignes.find(
        (l) =>
          l.code_imputation === selectedCode ||
          (l.article === selectedArt && l.paragraphe === selectedPar && l.ligne === selectedLig)
      ) || null
    );
  }, [lignes, selectedCode, selectedArt, selectedPar, selectedLig]);

  const handleSelect = (ligne) => {
    if (onSelect) {
      onSelect(ligne);
    }
    setIsOpen(false);
  };

  return (
    <div className={`relative ${className}`} ref={containerRef}>
      <div className="flex items-center justify-between gap-2 mb-2">
        <label className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
          <BookOpen size={14} className="text-blue-600 dark:text-blue-400" />
          <span>Sélectionner depuis la Nomenclature Officielle</span>
        </label>
        <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
          (Remplissage automatique)
        </span>
      </div>

      {/* Bouton déclencheur / Affichage de la sélection actuelle */}
      <div
        onClick={() => setIsOpen(!isOpen)}
        className={`w-full p-3.5 rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-3 shadow-sm ${
          isOpen
            ? 'border-blue-500 ring-2 ring-blue-100 dark:ring-blue-900/30 bg-white dark:bg-gray-800'
            : currentSelection
            ? 'border-emerald-300 bg-emerald-50/40 dark:bg-emerald-950/20 dark:border-emerald-800 hover:border-emerald-400'
            : 'border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-slate-300'
        }`}
      >
        <div className="flex items-center gap-3 overflow-hidden">
          <div
            className={`p-2 rounded-xl flex-shrink-0 ${
              currentSelection
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
            }`}
          >
            <Sparkles size={18} />
          </div>
          <div className="min-w-0">
            {currentSelection ? (
              <div>
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="font-mono font-black text-sm text-slate-900 dark:text-white bg-slate-100 dark:bg-gray-700 px-2 py-0.5 rounded-md">
                    {currentSelection.code_imputation}
                  </span>
                  <span
                    className={`text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border ${
                      currentSelection.type_budget === 'Investissement'
                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800'
                        : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800'
                    }`}
                  >
                    {currentSelection.type_budget}
                  </span>
                  {currentSelection.article && (
                    <span className="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">
                      ART: <b className="text-slate-700 dark:text-slate-200">{currentSelection.article}</b> / PAR: <b className="text-slate-700 dark:text-slate-200">{currentSelection.paragraphe || '—'}</b> / LIG: <b className="text-slate-700 dark:text-slate-200">{currentSelection.ligne || '—'}</b>
                    </span>
                  )}
                </div>
                <p className="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-1">
                  {currentSelection.intitule}
                </p>
              </div>
            ) : (
              <span className="text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <Search size={14} />
                Cliquez pour choisir une ligne budgétaire dans la nomenclature...
              </span>
            )}
          </div>
        </div>

        <div className="flex items-center gap-1 flex-shrink-0 text-slate-400">
          <ChevronDown
            size={18}
            className={`transition-transform duration-200 ${isOpen ? 'rotate-180 text-blue-600' : ''}`}
          />
        </div>
      </div>

      {/* Menu déroulant / Boîte de recherche */}
      {isOpen && (
        <div className="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-gray-700 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150">
          {/* En-tête : Recherche et Filtres */}
          <div className="p-3 bg-slate-50 dark:bg-gray-800/80 border-b border-slate-200 dark:border-gray-700 space-y-2.5">
            <div className="relative">
              <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Rechercher par code (ex: 4151010), article, intitulé (ex: mobilier, informatique...)"
                autoFocus
                className="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs font-semibold text-slate-800 dark:text-white placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/30"
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <X size={14} />
                </button>
              )}
            </div>

            {/* Filtres par type */}
            <div className="flex items-center gap-1.5 flex-wrap">
              <span className="text-[11px] font-bold text-slate-500 dark:text-slate-400 mr-1">Type:</span>
              {[
                { id: 'ALL', label: 'Tous' },
                { id: 'Investissement', label: 'Investissement (INV)' },
                { id: 'Fonctionnement', label: 'Fonctionnement (FONCT)' },
              ].map((btn) => (
                <button
                  key={btn.id}
                  type="button"
                  onClick={() => setFilterType(btn.id)}
                  className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                    filterType === btn.id
                      ? 'bg-blue-600 text-white shadow-sm'
                      : 'bg-slate-200/70 dark:bg-gray-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-gray-600'
                  }`}
                >
                  {btn.label}
                </button>
              ))}
              <span className="ml-auto text-[11px] font-semibold text-slate-400">
                {filteredLignes.length} ligne{filteredLignes.length > 1 ? 's' : ''}
              </span>
            </div>
          </div>

          {/* Liste des résultats */}
          <div className="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-gray-700/50 p-1.5">
            {loading ? (
              <div className="py-8 text-center text-xs font-semibold text-slate-400">
                Chargement de la nomenclature...
              </div>
            ) : filteredLignes.length === 0 ? (
              <div className="py-8 text-center">
                <p className="text-xs font-bold text-slate-600 dark:text-slate-300">
                  Aucune ligne trouvée pour « {searchQuery} »
                </p>
                <p className="text-[11px] text-slate-400 mt-1">
                  Essayez un autre mot-clé ou modifiez le filtre de type.
                </p>
              </div>
            ) : (
              filteredLignes.map((item) => {
                const isSelected =
                  currentSelection?.id === item.id ||
                  currentSelection?.code_imputation === item.code_imputation;
                const isHeader = !item.ligne && (item.niveau === 'rubrique' || item.niveau === 'projet');

                return (
                  <div
                    key={item.id}
                    onClick={() => handleSelect(item)}
                    className={`p-3 rounded-xl transition-all cursor-pointer flex items-start justify-between gap-3 ${
                      isSelected
                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-900 dark:text-blue-100 font-bold'
                        : isHeader
                        ? 'bg-slate-50/70 dark:bg-gray-700/20 hover:bg-blue-50/50'
                        : 'hover:bg-slate-50 dark:hover:bg-gray-700/40'
                    }`}
                  >
                    <div className="space-y-1 min-w-0 flex-1">
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-mono font-black text-xs px-2 py-0.5 rounded bg-slate-200 dark:bg-gray-700 text-slate-800 dark:text-slate-100">
                          {item.code_imputation}
                        </span>
                        <span
                          className={`text-[9px] font-black uppercase px-1.5 py-0.5 rounded border ${
                            item.type_budget === 'Investissement'
                              ? 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300'
                              : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300'
                          }`}
                        >
                          {item.type_budget === 'Investissement' ? 'INV' : 'FONCT'}
                        </span>
                        {item.article && (
                          <span className="text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                            ART: <b>{item.article}</b>
                            {item.paragraphe && <> | PAR: <b>{item.paragraphe}</b></>}
                            {item.ligne && <> | LIG: <b>{item.ligne}</b></>}
                          </span>
                        )}
                        {isHeader && (
                          <span className="text-[9px] font-bold uppercase tracking-wider text-slate-400 bg-slate-200/50 dark:bg-gray-600 px-1.5 py-0.2 rounded">
                            {item.niveau}
                          </span>
                        )}
                      </div>
                      <p className={`text-xs leading-relaxed ${isHeader ? 'font-black text-slate-900 dark:text-white uppercase' : 'text-slate-700 dark:text-slate-200 font-medium'}`}>
                        {item.intitule}
                      </p>
                    </div>

                    {isSelected && (
                      <div className="p-1 rounded-full bg-blue-600 text-white flex-shrink-0 mt-1">
                        <Check size={14} />
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default LigneBudgetaireSelector;
