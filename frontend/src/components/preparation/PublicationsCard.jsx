import React from 'react';
import SectionCard from './SectionCard';
import { Save, Plus, Trash2, Globe, Newspaper, Calendar, CheckCircle2, AlertTriangle, FileText } from 'lucide-react';

export default function PublicationsCard({
  formData,
  handleChange,
  handleJournauxChange,
  addJournal,
  removeJournal,
  status,
  handleSavePublications,
  saving,
  isReadOnly = false
}) {
  const journaux = Array.isArray(formData.publications_journaux) && formData.publications_journaux.length > 0
    ? formData.publications_journaux
    : [];

  const hasCompleteJournaux = journaux.length > 0 && journaux.some(j => j.nom_journal && j.numero_journal && j.date_publication);
  const hasCompletePortail = Boolean(formData.date_publication_portail);

  const isGlobalComplete = (hasCompleteJournaux || (formData.journal_fr && formData.date_publication_fr)) && hasCompletePortail;

  return (
    <SectionCard title="Informations de Publication" status={status} number="08">
      <div className="space-y-8">

        {/* ======================================================== */}
        {/* A) PUBLICATION DANS LES JOURNAUX                        */}
        {/* ======================================================== */}
        <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <Newspaper size={20} />
              </div>
              <div>
                <h3 className="font-extrabold text-lg text-slate-800">
                  A) PUBLICATION DANS LES JOURNAUX
                </h3>
                <p className="text-xs text-slate-500 font-medium">
                  Enregistrez un ou plusieurs journaux d'annonces légales (Français / Arabe)
                </p>
              </div>
            </div>

            {!isReadOnly && (
              <button
                type="button"
                onClick={addJournal}
                className="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-sm rounded-xl transition-all border border-blue-200 shadow-sm"
              >
                <Plus size={16} /> Ajouter un journal
              </button>
            )}
          </div>

          {journaux.length === 0 ? (
            <div className="my-6 p-8 text-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
              <Newspaper className="mx-auto text-slate-400 mb-2" size={32} />
              <p className="text-sm font-semibold text-slate-600">Aucun journal enregistré pour le moment.</p>
              <p className="text-xs text-slate-400 mt-1">Cliquez sur « Ajouter un journal » pour renseigner les parutions dans la presse.</p>
              {!isReadOnly && (
                <button
                  type="button"
                  onClick={addJournal}
                  className="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-bold text-xs rounded-xl hover:bg-blue-700 transition-colors shadow-sm"
                >
                  <Plus size={14} /> Ajouter un premier journal
                </button>
              )}
            </div>
          ) : (
            <div className="mt-6 space-y-4">
              {journaux.map((journal, index) => {
                const isArabic = journal.langue === 'Arabe';
                return (
                  <div
                    key={index}
                    className="p-5 bg-slate-50/70 border border-slate-200 hover:border-slate-300 rounded-2xl transition-all relative group"
                  >
                    <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/80">
                      <div className="flex items-center gap-2">
                        <span className="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-black">
                          {index + 1}
                        </span>
                        <span className="font-bold text-sm text-slate-700">
                          {journal.nom_journal ? `Journal : ${journal.nom_journal}` : `Publication #${index + 1}`}
                        </span>
                        {journal.langue && (
                          <span className={`text-[11px] font-extrabold px-2 py-0.5 rounded-full uppercase ${
                            isArabic ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'
                          }`}>
                            {journal.langue}
                          </span>
                        )}
                      </div>

                      {!isReadOnly && journaux.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeJournal(index)}
                          className="text-slate-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors"
                          title="Supprimer ce journal"
                        >
                          <Trash2 size={16} />
                        </button>
                      )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      {/* Nom du journal */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Nom du journal <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={journal.nom_journal || ''}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'nom_journal', e.target.value)}
                          placeholder="Ex: Le Matin, الصحراء المغربية..."
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-medium text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
                        />
                      </div>

                      {/* Numéro du journal */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Numéro du journal <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={journal.numero_journal || ''}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'numero_journal', e.target.value)}
                          placeholder="Ex: 18490, 8432..."
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono font-medium text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
                        />
                      </div>

                      {/* Date de publication */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Date de publication <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="date"
                          value={journal.date_publication || ''}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'date_publication', e.target.value)}
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 text-sm disabled:bg-slate-100"
                        />
                      </div>

                      {/* Édition / période de publication */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Édition / période de publication
                        </label>
                        <input
                          type="text"
                          value={journal.edition || ''}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'edition', e.target.value)}
                          placeholder="Ex: Mardi-Mercredi 26-27 mai 2026"
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-medium text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
                        />
                      </div>

                      {/* Langue */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Langue
                        </label>
                        <select
                          value={journal.langue || 'Français'}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'langue', e.target.value)}
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-medium text-slate-800 text-sm disabled:bg-slate-100"
                        >
                          <option value="Français">Français</option>
                          <option value="Arabe">Arabe (العربية)</option>
                        </select>
                      </div>

                      {/* Référence de publication */}
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                          Référence de publication
                        </label>
                        <input
                          type="text"
                          value={journal.reference_publication || ''}
                          disabled={isReadOnly}
                          onChange={(e) => handleJournauxChange(index, 'reference_publication', e.target.value)}
                          placeholder="Ex: REF-18490/2026"
                          className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
                        />
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* ======================================================== */}
        {/* B) PUBLICATION SUR LE PORTAIL DES MARCHÉS PUBLICS       */}
        {/* ======================================================== */}
        <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
          <div className="flex items-center gap-3 pb-5 border-b border-slate-100 mb-6">
            <div className="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
              <Globe size={20} />
            </div>
            <div>
              <h3 className="font-extrabold text-lg text-slate-800">
                B) PUBLICATION SUR LE PORTAIL DES MARCHÉS PUBLICS
              </h3>
              <p className="text-xs text-slate-500 font-medium">
                Renseignez les détails de la publication dématérialisée sur le portail officiel (www.marchespublics.gov.ma)
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Date de publication sur le portail */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-2">
                Date de publication sur le portail <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                name="date_publication_portail"
                value={formData.date_publication_portail || formData.date_publication_fr || ''}
                disabled={isReadOnly}
                onChange={handleChange}
                className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 transition-all font-mono text-slate-800 text-sm disabled:bg-slate-100"
              />
            </div>

            {/* Référence de publication sur le portail */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-2">
                Référence de publication sur le portail
              </label>
              <input
                type="text"
                name="ref_publication_portail"
                value={formData.ref_publication_portail || ''}
                disabled={isReadOnly}
                onChange={handleChange}
                placeholder="Ex: PMP-2026/029"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 transition-all font-mono text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
              />
            </div>

            {/* Date de mise en ligne si nécessaire */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-2">
                Date de mise en ligne (si différente)
              </label>
              <input
                type="date"
                name="date_mise_en_ligne_portail"
                value={formData.date_mise_en_ligne_portail || ''}
                disabled={isReadOnly}
                onChange={handleChange}
                className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 transition-all font-mono text-slate-800 text-sm disabled:bg-slate-100"
              />
            </div>
          </div>
        </div>

        {/* Bouton d'enregistrement */}
        {!isReadOnly && (
          <div className="pt-2 flex justify-center">
            <button
              type="button"
              onClick={handleSavePublications}
              disabled={saving}
              className="px-8 py-3.5 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 disabled:opacity-75"
            >
              <Save size={18} />
              {saving ? 'Enregistrement...' : 'Enregistrer les informations de publication'}
            </button>
          </div>
        )}

      </div>
    </SectionCard>
  );
}
