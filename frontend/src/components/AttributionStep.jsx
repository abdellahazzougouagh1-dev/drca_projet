import React, { useState, useMemo, useEffect } from 'react';
import {
  Award,
  CheckCircle2,
  Building2,
  FileText,
  AlertTriangle,
  Calendar,
  Mail,
  MapPin,
  ArrowRight,
  ShieldCheck,
  Save,
  Briefcase
} from 'lucide-react';
import api from '../api/axios';

export default function AttributionStep({
  formData,
  importPreview = [],
  cloturerAoo,
  saving,
  navigate,
  getMarcheNouveauUrl,
  downloadDocument
}) {
  const isAttributed = formData.statut === 'attribue' || Boolean(formData.date_attribution);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [isSavingLocal, setIsSavingLocal] = useState(false);
  const [globalError, setGlobalError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  const lots = formData.lots || [];

  // Helper to extract true fournisseur object from various sources
  const findFournisseurById = (fournisseurId, lot) => {
    if (!fournisseurId) return null;
    const fid = Number(fournisseurId);
    if (lot?.attributaire && Number(lot.attributaire.id) === fid) return lot.attributaire;
    const fromDecision = (lot?.decisions || []).find(d => Number(d.fournisseur_id) === fid)?.fournisseur;
    if (fromDecision) return fromDecision;
    const fromConcurrent = (importPreview || []).find(c => Number(c.fournisseur_id) === fid || (c.fournisseur && Number(c.fournisseur.id) === fid));
    if (fromConcurrent?.fournisseur) return fromConcurrent.fournisseur;
    return null;
  };

  // Compute winners per lot
  const winnersByLot = useMemo(() => {
    const winners = {};

    lots.forEach(lot => {
      let resolvedFournisseurId = null;
      let resolvedFournisseur = null;
      let resolvedMontant = lot.montant_attribue_ttc || null;

      // Priorité 1 : Le lot possède déjà un attributaire_fournisseur_id
      if (lot.attributaire_fournisseur_id) {
        resolvedFournisseurId = Number(lot.attributaire_fournisseur_id);
        resolvedFournisseur = findFournisseurById(resolvedFournisseurId, lot) || lot.attributaire || null;
        const matchingDecision = (lot.decisions || []).find(d => Number(d.fournisseur_id) === resolvedFournisseurId);
        if (matchingDecision) {
          resolvedMontant = resolvedMontant || matchingDecision.montant_propose;
        }
      }

      // Priorité 2 : Décisions multi-lots explicites sur ce lot
      if (!resolvedFournisseurId && lot.decisions && lot.decisions.length > 0) {
        const retenu = lot.decisions.find(d => {
          const st = String(d.statut || '').trim().toLowerCase();
          return st === 'retenu' || st === 'adjudicataire';
        });

        if (retenu && retenu.fournisseur_id) {
          resolvedFournisseurId = Number(retenu.fournisseur_id);
          resolvedFournisseur = retenu.fournisseur || findFournisseurById(resolvedFournisseurId, lot);
          resolvedMontant = retenu.montant_propose || resolvedMontant;
        } else {
          // Si aucune décision n'est explicitement 'retenu', filtrer les admis
          const admis = lot.decisions
            .filter(d => String(d.statut || '').trim().toLowerCase() !== 'ecarte' && d.fournisseur_id)
            .sort((a, b) => (parseFloat(a.montant_propose) || 0) - (parseFloat(b.montant_propose) || 0));
          if (admis.length > 0) {
            resolvedFournisseurId = Number(admis[0].fournisseur_id);
            resolvedFournisseur = admis[0].fournisseur || findFournisseurById(resolvedFournisseurId, lot);
            resolvedMontant = admis[0].montant_propose || resolvedMontant;
          }
        }
      }

      // Priorité 3 : Concurrents de la commission (notamment pour lot unique ou sans décision préalable)
      if (!resolvedFournisseurId && importPreview && importPreview.length > 0) {
        // Chercher concurrent avec statut_analyse === 'retenu'
        const retenuConcurrent = importPreview.find(c => {
          const st = String(c.statut_analyse || '').trim().toLowerCase();
          return (st === 'retenu' || st === 'adjudicataire') && (c.fournisseur_id || c.fournisseur?.id);
        });

        if (retenuConcurrent) {
          resolvedFournisseurId = Number(retenuConcurrent.fournisseur_id || retenuConcurrent.fournisseur?.id);
          resolvedFournisseur = retenuConcurrent.fournisseur || findFournisseurById(resolvedFournisseurId, lot);
          resolvedMontant = retenuConcurrent.montant_ttc || retenuConcurrent.montant_engagement || retenuConcurrent.montant_ht || resolvedMontant;
        } else {
          // Chercher les concurrents conformes
          const conformes = [...importPreview].filter(c => {
            const admin = c.admin_conforme === true || c.admin_conforme === 1 || c.admin_conforme === '1';
            const tech = c.tech_conforme === true || c.tech_conforme === 1 || c.tech_conforme === '1';
            const hasFid = Boolean(c.fournisseur_id || c.fournisseur?.id);
            return admin && tech && hasFid;
          }).sort((a, b) => (parseFloat(a.montant_ttc || a.montant_engagement || a.montant_ht || 0) - parseFloat(b.montant_ttc || b.montant_engagement || b.montant_ht || 0)));

          if (conformes.length > 0) {
            resolvedFournisseurId = Number(conformes[0].fournisseur_id || conformes[0].fournisseur?.id);
            resolvedFournisseur = conformes[0].fournisseur || findFournisseurById(resolvedFournisseurId, lot);
            resolvedMontant = conformes[0].montant_ttc || conformes[0].montant_engagement || conformes[0].montant_ht || resolvedMontant;
          }
        }
      }

      if (resolvedFournisseurId) {
        winners[lot.id] = {
          fournisseur_id: resolvedFournisseurId,
          fournisseur: resolvedFournisseur || {},
          montant_propose: resolvedMontant || 0,
        };
      }
    });

    return winners;
  }, [lots, importPreview]);

  const [attributionsData, setAttributionsData] = useState({});

  useEffect(() => {
    const initialData = {};
    lots.forEach(lot => {
      const winner = winnersByLot[lot.id];
      if (winner && winner.fournisseur_id) {
        const fData = (winner.fournisseur && Object.keys(winner.fournisseur).length > 0)
          ? winner.fournisseur
          : ((lot.attributaire && Number(lot.attributaire.id) === winner.fournisseur_id) ? lot.attributaire : {});

        let m_ttc = lot.montant_attribue_ttc || winner.montant_propose || '';
        let tva = lot.tva_taux_attribue || 20;
        let m_ht = lot.montant_attribue_ht || (m_ttc ? (parseFloat(m_ttc) / (1 + (parseFloat(tva) / 100))).toFixed(2) : '');

        initialData[lot.id] = {
          fournisseur_id: winner.fournisseur_id,
          titulaire_data: {
            raison_sociale: fData.raison_sociale || '',
            forme_juridique: fData.forme_juridique || '',
            capital: fData.capital || fData.capital_social || '',
            ice: fData.ice || '',
            if: fData.if || fData.identifiant_fiscal || '',
            rc: fData.rc || '',
            ville_rc: fData.ville_rc || '',
            patente: fData.patente || fData.taxe_professionnelle || '',
            cnss: fData.cnss || '',
            representant: fData.representant || fData.gerant_nom || '',
            qualite_representant: fData.qualite_representant || '',
            telephone: fData.telephone || '',
            email: fData.email || '',
            fax: fData.fax || '',
            adresse: fData.adresse || '',
            domicile_elu: fData.domicile_elu || '',
            ville: fData.ville || '',
            pays: fData.pays || 'Maroc',
            banque: fData.banque || '',
            agence_bancaire: fData.agence_bancaire || '',
            rib: fData.rib || '',
          },
          marche_data: {
            montant_attribue_ttc: m_ttc,
            tva_taux_attribue: tva,
            montant_attribue_ht: m_ht,
            delai_execution_jours: lot.delai_execution_jours || '',
            date_debut_prevue: lot.date_debut_prevue ? lot.date_debut_prevue.substring(0, 10) : '',
            date_fin_prevue: lot.date_fin_prevue ? lot.date_fin_prevue.substring(0, 10) : '',
            observations_attribution: lot.observations_attribution || '',
          },
          errors: []
        };
      }
    });
    setAttributionsData(initialData);
  }, [winnersByLot, lots]);

  const handleTitulaireChange = (lotId, e) => {
    const { name, value } = e.target;
    setAttributionsData(prev => ({
      ...prev,
      [lotId]: {
        ...prev[lotId],
        titulaire_data: {
          ...prev[lotId].titulaire_data,
          [name]: value
        },
        errors: prev[lotId].errors.filter(err => err !== name)
      }
    }));
    setGlobalError('');
  };

  const handleMarcheChange = (lotId, e) => {
    const { name, value } = e.target;
    setAttributionsData(prev => {
      const nextLot = { ...prev[lotId] };
      nextLot.marche_data = { ...nextLot.marche_data, [name]: value };

      // Auto-calc HT / TTC
      if (name === 'montant_attribue_ttc' && value) {
        const tva = parseFloat(nextLot.marche_data.tva_taux_attribue) || 20;
        nextLot.marche_data.montant_attribue_ht = (parseFloat(value) / (1 + (tva / 100))).toFixed(2);
      } else if (name === 'tva_taux_attribue' && nextLot.marche_data.montant_attribue_ttc) {
        const tva = parseFloat(value) || 20;
        nextLot.marche_data.montant_attribue_ht = (parseFloat(nextLot.marche_data.montant_attribue_ttc) / (1 + (tva / 100))).toFixed(2);
      } else if (name === 'montant_attribue_ht' && value) {
        const tva = parseFloat(nextLot.marche_data.tva_taux_attribue) || 20;
        nextLot.marche_data.montant_attribue_ttc = (parseFloat(value) * (1 + (tva / 100))).toFixed(2);
      }

      nextLot.errors = nextLot.errors.filter(err => err !== name);
      return { ...prev, [lotId]: nextLot };
    });
    setGlobalError('');
  };

  const requiredTitulaireFields = ['raison_sociale', 'ice', 'adresse', 'ville'];
  const requiredMarcheFields = ['montant_attribue_ttc', 'delai_execution_jours'];

  const validateAll = () => {
    let hasError = false;
    const nextState = { ...attributionsData };

    Object.keys(nextState).forEach(lotId => {
      const errors = [];
      const lotData = nextState[lotId];

      requiredTitulaireFields.forEach(field => {
        if (!lotData.titulaire_data[field] || String(lotData.titulaire_data[field]).trim() === '') {
          errors.push(field);
        }
      });

      requiredMarcheFields.forEach(field => {
        if (!lotData.marche_data[field] || String(lotData.marche_data[field]).trim() === '') {
          errors.push(field);
        }
      });

      if (errors.length > 0) hasError = true;
      lotData.errors = errors;
    });

    setAttributionsData(nextState);

    if (hasError) {
      setGlobalError("Veuillez remplir tous les champs obligatoires (en rouge) pour tous les lots attribués.");
      return false;
    }
    setGlobalError('');
    return true;
  };

  const handleSaveAttributionInfo = async (isStrict = false) => {
    if (isStrict && !validateAll()) return false;

    const lotKeys = Object.keys(attributionsData);
    if (lotKeys.length === 0) {
      setGlobalError("Aucun lot n'a encore d'attributaire désigné.");
      return false;
    }

    setIsSavingLocal(true);
    setSuccessMessage('');
    setGlobalError('');
    try {
      const payload = lotKeys.map(lotId => ({
        lot_id: parseInt(lotId, 10),
        fournisseur_id: attributionsData[lotId].fournisseur_id,
        titulaire_data: attributionsData[lotId].titulaire_data,
        marche_data: attributionsData[lotId].marche_data
      }));

      await api.post(`/aoos/${formData.id}/attribuer-lots`, { attributions: payload });
      setSuccessMessage('Brouillon d\'attribution enregistré avec succès !');
      setTimeout(() => setSuccessMessage(''), 4000);
      return true;
    } catch (err) {
      console.error(err);
      if (err.response?.status === 422) {
        const errors = err.response.data.errors || err.response.data.error;
        const messages = typeof errors === 'object' ? Object.values(errors).flat().join(' | ') : String(errors);
        setGlobalError(`Erreur: ${messages}`);
      } else {
        setGlobalError("Erreur lors de l'enregistrement: " + (err.response?.data?.message || err.message));
      }
      return false;
    } finally {
      setIsSavingLocal(false);
    }
  };

  const handleConfirmAttribution = async () => {
    if (!validateAll()) {
      setShowConfirmModal(false);
      return;
    }
    try {
      setShowConfirmModal(false);
      const saved = await handleSaveAttributionInfo(true);
      if (saved && cloturerAoo) await cloturerAoo();
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div className="space-y-8 animate-fade-in">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <h2 className="text-2xl font-extrabold text-[#1e3a8a] flex items-center gap-3">
            <Award size={28} className="text-blue-600" />
            Attribution des Marchés
          </h2>
          <p className="text-sm text-slate-600 mt-1">
            Saisie des informations définitives des titulaires et des marchés attribués.
          </p>
        </div>
        {isAttributed && (
          <span className="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-emerald-100 text-emerald-800 border border-emerald-300 font-extrabold text-sm shadow-sm">
            <CheckCircle2 size={18} className="text-emerald-600" /> Marché(s) attribué(s)
          </span>
        )}
      </div>

      {globalError && (
        <div className="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-start gap-3 text-red-700 animate-fade-in shadow-sm">
          <AlertTriangle className="shrink-0 mt-0.5" size={20} />
          <p className="text-sm font-bold leading-relaxed">{globalError}</p>
        </div>
      )}

      {successMessage && (
        <div className="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center gap-3 text-emerald-700 animate-fade-in shadow-sm">
          <CheckCircle2 size={20} />
          <p className="text-sm font-bold">{successMessage}</p>
        </div>
      )}

      {lots.map(lot => {
        const winnerInfo = winnersByLot[lot.id];
        const data = attributionsData[lot.id];

        if (!winnerInfo || !data) {
          return (
            <div key={lot.id} className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm mb-6">
              <h3 className="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <FileText className="text-blue-600" size={22} /> {lot.num_lot} : {lot.objet_lot}
              </h3>
              <div className="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-slate-600 text-sm font-semibold text-center">
                Aucune entreprise n'a été retenue pour ce lot par la commission.
              </div>
            </div>
          );
        }

        return (
          <div key={lot.id} className="bg-white rounded-3xl border border-blue-200 p-6 shadow-md mb-8 relative overflow-hidden">
            <div className="absolute top-0 right-0 w-32 h-32 bg-blue-600/5 rounded-full blur-2xl pointer-events-none"></div>

            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-blue-100 mb-6">
              <div>
                <h3 className="text-xl font-extrabold text-blue-950 flex items-center gap-2">
                  <Briefcase size={22} className="text-blue-600" />
                  {lot.num_lot} : {lot.objet_lot}
                </h3>
                <p className="text-sm text-blue-700 mt-1">
                  Entreprise retenue par la commission : <strong className="uppercase">{winnerInfo.fournisseur?.raison_sociale || 'Inconnue'}</strong> (Offre : {winnerInfo.montant_propose?.toLocaleString('fr-FR')} dh TTC)
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-8 relative z-10">
              {/* BLOC TITULAIRE */}
              <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                <h4 className="text-md font-bold text-slate-800 mb-4 uppercase tracking-wider flex items-center gap-2">
                  <Building2 size={18} className="text-slate-500" /> Informations du Titulaire
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-1.5 md:col-span-2">
                    <label className="text-xs font-bold text-slate-700">Raison Sociale <span className="text-red-500">*</span></label>
                    <input type="text" name="raison_sociale" value={data.titulaire_data.raison_sociale} onChange={(e) => handleTitulaireChange(lot.id, e)} className={`w-full px-3 py-2 bg-white border ${data.errors.includes('raison_sociale') ? 'border-red-500 bg-red-50' : 'border-slate-300'} rounded-xl text-sm`} />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">ICE <span className="text-red-500">*</span></label>
                    <input type="text" name="ice" value={data.titulaire_data.ice} onChange={(e) => handleTitulaireChange(lot.id, e)} className={`w-full px-3 py-2 bg-white border ${data.errors.includes('ice') ? 'border-red-500 bg-red-50' : 'border-slate-300'} rounded-xl text-sm font-mono`} />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">Identifiant Fiscal (IF)</label>
                    <input type="text" name="if" value={data.titulaire_data.if} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">Registre Commerce (RC)</label>
                    <input type="text" name="rc" value={data.titulaire_data.rc} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">Forme Juridique</label>
                    <input type="text" name="forme_juridique" value={data.titulaire_data.forme_juridique} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">Capital Social (DH)</label>
                    <input type="text" name="capital" value={data.titulaire_data.capital} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">N° Taxe Pro. (Patente)</label>
                    <input type="text" name="patente" value={data.titulaire_data.patente} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono" />
                  </div>
                  <div className="space-y-1.5 md:col-span-2">
                    <label className="text-xs font-bold text-slate-700">N° d'Affiliation CNSS</label>
                    <input type="text" name="cnss" value={data.titulaire_data.cnss} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono" />
                  </div>
                  <div className="space-y-1.5 md:col-span-2">
                    <label className="text-xs font-bold text-slate-700">Adresse <span className="text-red-500">*</span></label>
                    <input type="text" name="adresse" value={data.titulaire_data.adresse} onChange={(e) => handleTitulaireChange(lot.id, e)} className={`w-full px-3 py-2 bg-white border ${data.errors.includes('adresse') ? 'border-red-500 bg-red-50' : 'border-slate-300'} rounded-xl text-sm`} />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700">Ville <span className="text-red-500">*</span></label>
                    <input type="text" name="ville" value={data.titulaire_data.ville} onChange={(e) => handleTitulaireChange(lot.id, e)} className={`w-full px-3 py-2 bg-white border ${data.errors.includes('ville') ? 'border-red-500 bg-red-50' : 'border-slate-300'} rounded-xl text-sm`} />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Téléphone</label>
                    <input type="text" name="telephone" value={data.titulaire_data.telephone} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Fax</label>
                    <input type="text" name="fax" value={data.titulaire_data.fax} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value={data.titulaire_data.email} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-xs font-bold text-slate-700 mb-1">Représentant (Gérant)</label>
                    <input type="text" name="representant" value={data.titulaire_data.representant} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div className="md:col-span-2">
                    <h5 className="font-bold text-slate-800 text-sm mt-4 mb-2 border-b border-slate-100 pb-2">Informations Bancaires</h5>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Banque</label>
                    <input type="text" name="banque" value={data.titulaire_data.banque} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Agence Bancaire</label>
                    <input type="text" name="agence_bancaire" value={data.titulaire_data.agence_bancaire} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm" />
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-xs font-bold text-slate-700 mb-1">RIB (24 chiffres)</label>
                    <input type="text" name="rib" value={data.titulaire_data.rib} onChange={(e) => handleTitulaireChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-mono tracking-widest" />
                  </div>
                </div>
              </div>

              {/* BLOC MARCHÉ */}
              <div className="bg-blue-50/50 rounded-2xl p-6 border border-blue-100">
                <h4 className="text-md font-bold text-blue-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                  <FileText size={18} className="text-blue-500" /> Informations du Marché Attribué
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-blue-800">Montant HT</label>
                    <input type="number" step="0.01" name="montant_attribue_ht" value={data.marche_data.montant_attribue_ht} onChange={(e) => handleMarcheChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-sm font-mono text-blue-900" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-blue-800">TVA (%)</label>
                    <input type="number" step="0.1" name="tva_taux_attribue" value={data.marche_data.tva_taux_attribue} onChange={(e) => handleMarcheChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-sm font-mono" />
                  </div>
                  <div className="space-y-1.5 md:col-span-2">
                    <label className="text-xs font-bold text-blue-900">Montant TTC <span className="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="montant_attribue_ttc" value={data.marche_data.montant_attribue_ttc} onChange={(e) => handleMarcheChange(lot.id, e)} className={`w-full px-4 py-3 bg-white border ${data.errors.includes('montant_attribue_ttc') ? 'border-red-500 bg-red-50' : 'border-blue-300'} rounded-xl text-lg font-black text-blue-950 font-mono shadow-sm`} />
                  </div>

                  <div className="space-y-1.5 md:col-span-2 border-t border-blue-100 pt-4 mt-2">
                    <label className="text-xs font-bold text-blue-800">Délai d'exécution (Jours) <span className="text-red-500">*</span></label>
                    <input type="number" name="delai_execution_jours" value={data.marche_data.delai_execution_jours} onChange={(e) => handleMarcheChange(lot.id, e)} className={`w-full px-3 py-2 bg-white border ${data.errors.includes('delai_execution_jours') ? 'border-red-500 bg-red-50' : 'border-blue-200'} rounded-xl text-sm`} />
                  </div>

                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-blue-800">Date début prévue</label>
                    <input type="date" name="date_debut_prevue" value={data.marche_data.date_debut_prevue} onChange={(e) => handleMarcheChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-sm" />
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-blue-800">Date fin prévue</label>
                    <input type="date" name="date_fin_prevue" value={data.marche_data.date_fin_prevue} onChange={(e) => handleMarcheChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-sm" />
                  </div>

                  <div className="space-y-1.5 md:col-span-2">
                    <label className="text-xs font-bold text-blue-800">Observations</label>
                    <textarea name="observations_attribution" rows="2" value={data.marche_data.observations_attribution} onChange={(e) => handleMarcheChange(lot.id, e)} className="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-sm resize-none"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
      })}

      <div className="flex flex-wrap items-center justify-end gap-4 mt-8">
        {successMessage && (
          <div className="px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold rounded-xl flex items-center gap-2">
            <CheckCircle2 size={18} className="text-emerald-600" />
            {successMessage}
          </div>
        )}
        {globalError && (
          <div className="px-4 py-2.5 bg-red-50 border border-red-200 text-red-800 text-sm font-bold rounded-xl flex items-center gap-2">
            <AlertTriangle size={18} className="text-red-600" />
            {globalError}
          </div>
        )}
        <button
          type="button"
          onClick={() => handleSaveAttributionInfo(false)}
          disabled={isSavingLocal || saving}
          className="px-6 py-3 bg-blue-100 hover:bg-blue-200 text-blue-800 font-bold text-sm rounded-xl transition-all flex items-center gap-2"
        >
          <Save size={18} /> {isSavingLocal ? 'Enregistrement...' : 'Enregistrer (Brouillon)'}
        </button>
      </div>

      <div className="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm mt-8">
        {!isAttributed ? (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              <h3 className="text-base font-bold text-slate-800">Validation de l'Attribution Définitive</h3>
              <p className="text-xs text-slate-500 mt-0.5">
                La validation transformera les attributaires en titulaires et clôturera l'Appel d'Offres.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setShowConfirmModal(true)}
              disabled={saving || Object.keys(winnersByLot).length === 0}
              className="px-8 py-4 bg-[#1e40af] hover:bg-[#1e3a8a] text-white font-extrabold rounded-2xl shadow-xl transition-all flex items-center gap-3 disabled:opacity-50 text-base"
            >
              <ShieldCheck size={22} /> Marché(s) définitivement attribué(s)
            </button>
          </div>
        ) : (
          <div className="p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-600 text-white font-bold">
                <CheckCircle2 size={24} />
              </div>
              <div>
                <h4 className="font-extrabold text-base text-emerald-950">Marché(s) Définitivement Attribué(s)</h4>
                <p className="text-xs text-emerald-700 mt-0.5">
                  Toutes les informations ont été validées avec succès.
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={() => navigate('/engagements')}
              className="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-sm rounded-xl shadow-md transition-all flex items-center gap-2"
            >
              Passer à l'Engagement du Marché <ArrowRight size={18} />
            </button>
          </div>
        )}
      </div>

      {showConfirmModal && (
        <div className="fixed inset-0 z-[60] bg-slate-900/60 backdrop-blur-sm grid place-items-center p-4 animate-fade-in">
          <div className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-200 text-center space-y-6">
            <div className="grid h-16 w-16 place-items-center rounded-2xl bg-blue-100 text-blue-700 mx-auto">
              <ShieldCheck size={38} />
            </div>
            <div>
              <h3 className="text-xl font-extrabold text-slate-900">Validation de l'attribution</h3>
              <p className="text-xs text-slate-600 mt-3 leading-relaxed">
                Êtes-vous sûr de vouloir valider ces informations ? Les données du titulaire et du marché seront verrouillées pour l'engagement.
              </p>
            </div>
            <div className="flex items-center gap-3 pt-2">
              <button
                type="button"
                onClick={() => setShowConfirmModal(false)}
                className="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-all"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={handleConfirmAttribution}
                disabled={saving || isSavingLocal}
                className="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg transition-all flex items-center justify-center gap-2"
              >
                {saving || isSavingLocal ? 'Validation...' : 'Oui, Valider'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
