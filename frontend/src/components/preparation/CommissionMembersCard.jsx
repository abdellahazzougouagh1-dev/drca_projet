import React, { useState } from 'react';
import SectionCard from './SectionCard';
import { UserPlus, Trash2, CheckCircle, Shield, Crown, User, Plus, Sparkles, X } from 'lucide-react';

export default function CommissionMembersCard({
  formData,
  status,
  isReadOnly,
  handleChange,
  nouveauMembre,
  setNouveauMembre,
  membresCommissionCatalog,
  ajouterMembre,
  retirerMembre,
  handleUpdateMembreQualite
}) {
  const [showAddForm, setShowAddForm] = useState(false);
  const [formMode, setFormMode] = useState('catalog'); // 'catalog' or 'custom'
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [customForm, setCustomForm] = useState({
    nom_prenom: '',
    fonction: '',
    qualite: 'Membre'
  });

  const handleCustomSubmit = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();

    if (!customForm.nom_prenom.trim()) return;

    setIsSubmitting(true);
    try {
      if (typeof ajouterMembre === 'function') {
        await ajouterMembre({
          nom_prenom: customForm.nom_prenom.trim(),
          fonction: customForm.fonction.trim(),
          qualite: customForm.qualite || 'Membre'
        });
      }
      setCustomForm({ nom_prenom: '', fonction: '', qualite: 'Membre' });
      setFormMode('catalog');
      setShowAddForm(true);
    } catch (err) {
      console.error('Erreur lors de l\'enregistrement du membre:', err);
    } finally {
      setIsSubmitting(false);
    }
  };

  const getMemberDisplayName = (membre) => {
    if (membre.nom_prenom && membre.nom_prenom.trim() !== '') {
      return membre.nom_prenom;
    }
    const infoCatalog = membresCommissionCatalog?.find(m => String(m.id) === String(membre.membre_commission_id));
    if (infoCatalog) {
      return infoCatalog.nom_prenom || `${infoCatalog.nom || ''} ${infoCatalog.prenom || ''}`.trim();
    }
    return 'Membre de commission';
  };

  const getMemberDisplayRole = (membre) => {
    if (membre.fonction && membre.fonction.trim() !== '') {
      return membre.fonction;
    }
    const infoCatalog = membresCommissionCatalog?.find(m => String(m.id) === String(membre.membre_commission_id));
    if (infoCatalog) {
      return infoCatalog.fonction || infoCatalog.role || infoCatalog.departement || '-';
    }
    return membre.role || '-';
  };

  return (
    <SectionCard title="Membres de la Commission" status={status} number="04">
      <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200">
        
        {/* Informations de la décision de nomination */}
        <div className="mb-6 p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
          <h4 className="font-extrabold text-slate-800 text-sm mb-3">
            Informations de la décision de nomination
          </h4>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">
                N° de décision :
              </label>
              <input
                type="text"
                name="num_decision_nomination"
                value={formData.num_decision_nomination || ''}
                disabled={isReadOnly}
                onChange={handleChange}
                placeholder="Ex: 06/2026/DRCA-RSK ou 15/2026"
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono font-medium text-slate-800 text-sm placeholder-slate-400 disabled:bg-slate-100"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">
                Date de décision :
              </label>
              <input
                type="date"
                name="date_decision_nomination"
                value={formData.date_decision_nomination || ''}
                disabled={isReadOnly}
                onChange={handleChange}
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all font-mono text-slate-800 text-sm disabled:bg-slate-100"
              />
            </div>
          </div>
        </div>

        {/* Composition de la commission */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
          <div>
            <h4 className="font-extrabold text-slate-800 flex items-center gap-2 text-base">
              <Shield className="text-blue-600" size={20} /> Membres de la commission
            </h4>
            <p className="text-slate-500 text-xs mt-0.5">
              Désignez le Président et les Membres de la commission pour l'ouverture des plis
            </p>
          </div>

          <div className="flex items-center gap-3">
            <span className="bg-blue-100 text-blue-800 text-xs font-black px-3.5 py-1.5 rounded-full border border-blue-200 shadow-sm">
              {formData.membres_commission?.length || 0} membre(s) enregistré(s)
            </span>

            {!isReadOnly && !showAddForm && (
              <button
                type="button"
                onClick={() => setShowAddForm(true)}
                className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 transition-all shadow-sm hover:shadow-blue-500/20"
              >
                <Plus size={16} /> Ajouter un membre
              </button>
            )}
          </div>
        </div>

        {/* AJOUT MEMBRE : FORMULAIRE OU PANNEAU INTERACTIF */}
        {!isReadOnly && showAddForm && (
          <div className="mb-6 bg-white p-5 rounded-2xl shadow-md border border-blue-100 transition-all animate-fade-in">
            <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <span className="flex items-center justify-center w-7 h-7 bg-blue-100 text-blue-600 rounded-lg">
                  <UserPlus size={16} />
                </span>
                <span className="font-black text-slate-800 text-sm">Ajouter un membre à la commission</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold text-slate-600">
                  <button
                    type="button"
                    onClick={() => setFormMode('catalog')}
                    className={`px-3 py-1 rounded-lg transition-all ${formMode === 'catalog' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-slate-900'}`}
                  >
                    Depuis le catalogue
                  </button>
                  <button
                    type="button"
                    onClick={() => setFormMode('custom')}
                    className={`px-3 py-1 rounded-lg transition-all ${formMode === 'custom' ? 'bg-white text-blue-600 shadow-sm' : 'hover:text-slate-900'}`}
                  >
                    Nouveau membre
                  </button>
                </div>
                <button
                  type="button"
                  onClick={() => setShowAddForm(false)}
                  className="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors"
                >
                  <X size={18} />
                </button>
              </div>
            </div>

            {formMode === 'catalog' ? (
              <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div className="md:col-span-6">
                  <label className="block text-xs font-bold text-slate-600 mb-1">Sélectionner un membre existant</label>
                  <select
                    value={String(nouveauMembre.membre_commission_id || '')}
                    onChange={(e) => setNouveauMembre(prev => ({ ...prev, membre_commission_id: e.target.value }))}
                    className="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all font-medium text-slate-700 text-sm"
                  >
                    <option value="">-- Choisir dans la liste des membres --</option>
                    {membresCommissionCatalog?.map(m => (
                      <option key={m.id} value={String(m.id)}>
                        {m.nom_prenom || `${m.nom || ''} ${m.prenom || ''}`.trim()} {m.fonction ? `(${m.fonction})` : ''}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="md:col-span-4">
                  <label className="block text-xs font-bold text-slate-600 mb-1">Qualité dans la commission</label>
                  <select
                    value={nouveauMembre.qualite || 'Membre'}
                    onChange={(e) => setNouveauMembre(prev => ({ ...prev, qualite: e.target.value }))}
                    className="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 text-sm"
                  >
                    <option value="Président">👑 Président de commission</option>
                    <option value="Membre">👤 Membre de commission</option>
                  </select>
                </div>

                <div className="md:col-span-2 flex items-end">
                  <button
                    type="button"
                    onClick={() => {
                      ajouterMembre();
                      setShowAddForm(false);
                    }}
                    className="w-full bg-blue-600 text-white font-bold px-4 py-2.5 rounded-xl hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 text-sm shadow-sm"
                  >
                    <UserPlus size={16} /> Ajouter
                  </button>
                </div>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div className="md:col-span-4">
                  <label className="block text-xs font-bold text-slate-600 mb-1">Nom et Prénom *</label>
                  <input
                    type="text"
                    required
                    placeholder="Ex: Abdellah Azzougouagh"
                    value={customForm.nom_prenom}
                    onChange={(e) => setCustomForm(prev => ({ ...prev, nom_prenom: e.target.value }))}
                    className="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all font-medium text-slate-700 text-sm"
                  />
                </div>

                <div className="md:col-span-4">
                  <label className="block text-xs font-bold text-slate-600 mb-1">Fonctionnalité / Rôle</label>
                  <input
                    type="text"
                    placeholder="Ex: Chef de service / Ingénieur"
                    value={customForm.fonction}
                    onChange={(e) => setCustomForm(prev => ({ ...prev, fonction: e.target.value }))}
                    className="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all font-medium text-slate-700 text-sm"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-xs font-bold text-slate-600 mb-1">Qualité</label>
                  <select
                    value={customForm.qualite}
                    onChange={(e) => setCustomForm(prev => ({ ...prev, qualite: e.target.value }))}
                    className="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 text-sm"
                  >
                    <option value="Président">👑 Président</option>
                    <option value="Membre">👤 Membre</option>
                  </select>
                </div>

                <div className="md:col-span-2 flex items-end">
                  <button
                    type="button"
                    disabled={isSubmitting}
                    onClick={handleCustomSubmit}
                    className="w-full bg-emerald-600 text-white font-bold px-4 py-2.5 rounded-xl hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2 text-sm shadow-sm disabled:opacity-75 disabled:cursor-wait"
                  >
                    {isSubmitting ? <span className="animate-spin">⏳</span> : <UserPlus size={16} />}
                    {isSubmitting ? 'En cours...' : 'Enregistrer'}
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Tableau des membres */}
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-100 text-slate-700 text-xs uppercase font-black border-b border-slate-200">
                <th className="px-4 py-3.5">Nom et prénom</th>
                <th className="px-4 py-3.5">Fonction</th>
                <th className="px-4 py-3.5">Qualité dans la commission</th>
                <th className="px-4 py-3.5 text-center">Statut</th>
                {!isReadOnly && <th className="px-4 py-3.5 text-center">Action</th>}
              </tr>
            </thead>
            <tbody>
              {!formData.membres_commission || formData.membres_commission.length === 0 ? (
                <tr>
                  <td colSpan={isReadOnly ? 4 : 5} className="px-4 py-10 text-center text-slate-500 font-medium bg-white">
                    <User className="mx-auto text-slate-300 mb-2" size={32} />
                    Aucun membre n'a été ajouté à la commission de cet appel d'offres.
                    {!isReadOnly && (
                      <div className="mt-3">
                        <button
                          type="button"
                          onClick={() => setShowAddForm(true)}
                          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 font-bold text-xs rounded-xl hover:bg-blue-100 border border-blue-200 transition-colors"
                        >
                          <Plus size={14} /> Ajouter le premier membre
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ) : (
                formData.membres_commission.map((membre, index) => {
                  const displayName = getMemberDisplayName(membre);
                  const displayRole = getMemberDisplayRole(membre);
                  const isPresident = membre.qualite === 'Président';

                  return (
                    <tr key={index} className="border-b border-slate-100 hover:bg-slate-50/70 transition-colors">
                      <td className="px-4 py-4">
                        <div className="flex items-center gap-3">
                          <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs ${isPresident ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200'
                            }`}>
                            {isPresident ? <Crown size={18} className="text-amber-600" /> : <User size={18} />}
                          </div>
                          <div>
                            <div className="font-bold text-slate-800 text-sm">{displayName}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-4 text-sm font-medium text-slate-600">
                        {displayRole}
                      </td>
                      <td className="px-4 py-4">
                        {!isReadOnly && typeof handleUpdateMembreQualite === 'function' ? (
                          <select
                            value={membre.qualite || 'Membre'}
                            onChange={(e) => handleUpdateMembreQualite(index, e.target.value)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-black border outline-none cursor-pointer transition-all ${isPresident
                              ? 'bg-amber-50 text-amber-800 border-amber-300'
                              : 'bg-slate-100 text-slate-700 border-slate-200'
                              }`}
                          >
                            <option value="Président">👑 Président</option>
                            <option value="Membre">👤 Membre</option>
                            <option value="Rapporteur">📝 Rapporteur</option>
                          </select>
                        ) : (
                          <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black border ${isPresident
                            ? 'bg-amber-50 text-amber-800 border-amber-200 shadow-sm'
                            : 'bg-slate-100 text-slate-700 border-slate-200'
                            }`}>
                            {isPresident && <Crown size={13} className="text-amber-600" />}
                            {membre.qualite || 'Membre'}
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-4 text-center">
                        <span className="inline-flex items-center gap-1 text-emerald-600 text-xs font-bold bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                          <CheckCircle size={14} /> Validé
                        </span>
                      </td>
                      {!isReadOnly && (
                        <td className="px-4 py-4 text-center">
                          <button
                            type="button"
                            onClick={() => retirerMembre(index)}
                            className="text-red-400 hover:text-red-600 p-2 hover:bg-red-50 rounded-xl transition-colors"
                            title="Retirer de la commission"
                          >
                            <Trash2 size={18} />
                          </button>
                        </td>
                      )}
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </SectionCard>
  );
}
