import React from 'react';
import PreparationHeader from './PreparationHeader';
import PreparationProgress from './PreparationProgress';
import GeneralInformationCard from './GeneralInformationCard';
import LotsEstimationCard from './LotsEstimationCard';
import BudgetCard from './BudgetCard';
import CommissionMembersCard from './CommissionMembersCard';
import OpeningSessionCard from './OpeningSessionCard';
import ValidationCard from './ValidationCard';
import DocumentsCard from './DocumentsCard';
import PublicationsCard from './PublicationsCard';
import PreparationSummary from './PreparationSummary';

export default function PreparationStep({
  formData,
  handleChange,
  handleLotsDetailsChange,
  handleLotItemChange,
  addLotItem,
  removeLotItem,
  addLot,
  removeLot,
  handleJournauxChange,
  addJournal,
  removeJournal,
  calculerTotalEstimation,
  calculerTotalEstimationTva,
  calculerTotalEstimationTtc,
  nouveauMembre,
  setNouveauMembre,
  membresCommissionCatalog,
  ajouterMembre,
  retirerMembre,
  handleUpdateMembreQualite,
  handleSave,
  handleValidatePreparation,
  handleUnlockPreparation,
  handleGenerateDocument,
  handleSavePublications,
  handlePasserCommission,
  saving,
  id,
  generatingDoc
}) {
  const isReadOnly = ['Preparation_Validee', 'Commission_Ouverture', 'attribue'].includes(formData.statut);

  // Détermination de l'état de chaque section pour les badges et la timeline
  const getSectionStatus = (section) => {
    switch (section) {
      case 'infos':
        return (formData.num_aoo && formData.objet && formData.objet_ar && formData.nombre_lots) ? 'complet' : (formData.num_aoo || formData.objet ? 'incomplet' : 'non_renseigne');
      case 'lots':
        return (formData.lots_details?.length > 0 && formData.budget) ? 'complet' : 'incomplet';
      case 'budget':
        return (formData.art && formData.par && formData.lig) ? 'complet' : (formData.art || formData.par ? 'incomplet' : 'non_renseigne');
      case 'commission':
        return formData.membres_commission?.length > 0 ? 'complet' : 'non_renseigne';
      case 'seance':
        return (formData.date_ouverture && formData.heure_ouverture && formData.lieu_ouverture) ? 'complet' : (formData.date_ouverture || formData.lieu_ouverture ? 'incomplet' : 'non_renseigne');
      case 'validation':
        return isReadOnly ? 'complet' : 'non_renseigne';
      case 'docs':
        return 'complet';
      case 'pubs': {
        const jList = Array.isArray(formData.publications_journaux) ? formData.publications_journaux : [];
        const hasJournaux = jList.length > 0 && jList.some(j => j.nom_journal && j.numero_journal && j.date_publication);
        const hasPortail = Boolean(formData.date_publication_portail || formData.date_publication_fr);
        const legacyFr = formData.journal_fr && formData.date_publication_fr;
        if ((hasJournaux || legacyFr) && hasPortail) return 'complet';
        if (jList.length > 0 || formData.date_publication_portail || formData.journal_fr || formData.journal_ar) return 'incomplet';
        return 'non_renseigne';
      }
      case 'passage':
        return formData.statut === 'Commission_Ouverture' ? 'complet' : 'non_renseigne';
      default:
        return 'non_renseigne';
    }
  };

  const stepsStatus = {
    infos: getSectionStatus('infos') === 'complet',
    lots: getSectionStatus('lots') === 'complet',
    budget: getSectionStatus('budget') === 'complet',
    commission: getSectionStatus('commission') === 'complet',
    seance: getSectionStatus('seance') === 'complet',
    validation: getSectionStatus('validation') === 'complet',
    docs: true, // On relaxe cette contrainte visuelle frontend pour l'affichage du bouton final (le backend bloquera si manquant)
    pubs: getSectionStatus('pubs') === 'complet'
  };

  return (
    <div className="max-w-6xl mx-auto py-8 px-4">
      <PreparationHeader formData={formData} />

      <PreparationProgress stepsStatus={stepsStatus} />

      <div className="space-y-2">
        <GeneralInformationCard
          formData={formData}
          handleChange={handleChange}
          isReadOnly={isReadOnly}
          status={getSectionStatus('infos')}
        />

        <LotsEstimationCard
          formData={formData}
          status={getSectionStatus('lots')}
          isReadOnly={isReadOnly}
          handleLotsDetailsChange={handleLotsDetailsChange}
          handleLotItemChange={handleLotItemChange}
          addLotItem={addLotItem}
          removeLotItem={removeLotItem}
          addLot={addLot}
          removeLot={removeLot}
          calculerTotalEstimation={calculerTotalEstimation}
          calculerTotalEstimationTva={calculerTotalEstimationTva}
          calculerTotalEstimationTtc={calculerTotalEstimationTtc}
        />

        <BudgetCard
          formData={formData}
          handleChange={handleChange}
          isReadOnly={isReadOnly}
          status={getSectionStatus('budget')}
        />

        <CommissionMembersCard
          formData={formData}
          status={getSectionStatus('commission')}
          isReadOnly={isReadOnly}
          handleChange={handleChange}
          handleGenerateDocument={handleGenerateDocument}
          generatingDoc={generatingDoc}
          nouveauMembre={nouveauMembre}
          setNouveauMembre={setNouveauMembre}
          membresCommissionCatalog={membresCommissionCatalog}
          ajouterMembre={ajouterMembre}
          retirerMembre={retirerMembre}
          handleUpdateMembreQualite={handleUpdateMembreQualite}
        />

        <OpeningSessionCard
          formData={formData}
          handleChange={handleChange}
          isReadOnly={isReadOnly}
          status={getSectionStatus('seance')}
        />

        <ValidationCard
          formData={formData}
          status={getSectionStatus('validation')}
          handleValidatePreparation={handleValidatePreparation}
          handleUnlockPreparation={handleUnlockPreparation}
          saving={saving}
        />

        <DocumentsCard
          status={getSectionStatus('docs')}
          id={id}
          formData={formData}
          handleChange={handleChange}
          isReadOnly={isReadOnly}
          handleGenerateDocument={handleGenerateDocument}
          generatingDoc={generatingDoc}
        />

        <PublicationsCard
          formData={formData}
          handleChange={handleChange}
          handleJournauxChange={handleJournauxChange}
          addJournal={addJournal}
          removeJournal={removeJournal}
          status={getSectionStatus('pubs')}
          handleSavePublications={handleSavePublications}
          saving={saving}
          isReadOnly={isReadOnly}
        />
      </div>

      <PreparationSummary
        formData={formData}
        stepsStatus={stepsStatus}
        handlePasserCommission={handlePasserCommission}
        handleSave={handleSave}
        saving={saving}
      />
    </div>
  );
}
