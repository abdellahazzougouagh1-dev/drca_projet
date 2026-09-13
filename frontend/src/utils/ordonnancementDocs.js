/**
 * Helper to determine the specific document associated with an payment order (OP).
 * Rules:
 * - Retenue à la source TVA -> Ordre de paiement — RAS TVA (docType: op_ras_tva)
 * - Retenue à la source IS / IAS / IAC -> Ordre de paiement — RAS IS/IAC (docType: op_ras_is)
 * - Ordre de virement (OV) -> Ordre de virement (OV) (docType: ov)
 * - Ordre d'imputation (OI) -> Ordre d'imputation (OI) (docType: oi)
 * - Paiement fournisseur / Standard:
 *     - Créance "C.Neufs" / "Crédits nouveaux" -> Ordre de paiement — Crédits nouveaux (docType: op)
 *     - Créance "Reports" -> Ordre de paiement — Reports (docType: op)
 *     - Créance "Crédit Consolidés" -> Ordre de paiement — Crédits Consolidés (docType: op)
 *     - Créance "Reste à payer" -> Ordre de paiement — Reste à payer (docType: op)
 *     - Autre créance -> Ordre de paiement — [Créance] (docType: op)
 */

export function resolveOrdreDocument(ordre) {
  if (!ordre) {
    return {
      docType: 'op',
      title: 'Ordre de paiement (OP)',
      shortTitle: 'Ordre de paiement',
      category: 'Paiement',
      badgeColor: 'blue'
    };
  }

  const typeMouv = String(ordre.type_mouvement || '').toLowerCase();
  const creance = String(ordre.creance || '').toLowerCase();
  const numOrdre = ordre.num_ordre || 'OP';

  // 1. Retenue à la source TVA (Mandatée au Trésor Public via Ordre de Virement OV)
  if (typeMouv.includes('tva') || creance.includes('tva') || (typeMouv.includes('virement') && creance.includes('retenue'))) {
    return {
      docType: 'ov',
      title: 'Ordre de virement (OV) — Retenue à la source TVA',
      shortTitle: `OV Retenue TVA (${numOrdre})`,
      category: 'Retenue TVA',
      badgeColor: 'emerald',
      description: 'Ordre de virement (OV) pour le versement de la retenue à la source TVA au Trésor Public.'
    };
  }

  // 2. Retenue à la source IS / IAS / IAC
  if (typeMouv.includes('ias') || typeMouv.includes('is') || typeMouv.includes('iac') || (typeMouv.includes('retenue') && (creance.includes('ias') || creance.includes('is')))) {
    return {
      docType: 'op_ras_is',
      title: 'Ordre de paiement (OP) — Retenue à la source IS/IAC',
      shortTitle: `OP RAS IS/IAC (${numOrdre})`,
      category: 'Retenue IS/IAC',
      badgeColor: 'amber',
      description: 'Document officiel de retenue à la source IS/IAC pour l’administration fiscale.'
    };
  }

  // 3. Ordre de virement (OV général)
  if (typeMouv.includes('virement') && (typeMouv.includes('ov') || typeMouv.includes('ordre de virement'))) {
    return {
      docType: 'ov',
      title: 'Ordre de virement (OV)',
      shortTitle: `OV (${numOrdre})`,
      category: 'Virement',
      badgeColor: 'blue',
      description: 'Ordre de virement bancaire pour règlement via le compte trésorerie.'
    };
  }

  // 4. Ordre d'imputation (OI)
  if (typeMouv.includes('imputation') || typeMouv.includes('oi') || typeMouv.includes('ordre d\'imputation')) {
    return {
      docType: 'oi',
      title: 'Ordre d\'imputation (OI)',
      shortTitle: `OI (${numOrdre})`,
      category: 'Imputation',
      badgeColor: 'indigo',
      description: 'Ordre d\'imputation budgétaire des crédits.'
    };
  }

  // 5. Paiements Fournisseurs par Créance
  if (creance.includes('neuf') || creance.includes('nouveau')) {
    return {
      docType: 'op',
      title: 'Ordre de paiement — Crédits nouveaux',
      shortTitle: `OP C.Neufs (${numOrdre})`,
      category: 'Crédits nouveaux',
      badgeColor: 'blue',
      description: 'Ordre de paiement fournisseur imputé sur crédits neufs de l’exercice.'
    };
  }

  if (creance.includes('report')) {
    return {
      docType: 'op',
      title: 'Ordre de paiement — Reports',
      shortTitle: `OP Reports (${numOrdre})`,
      category: 'Reports',
      badgeColor: 'sky',
      description: 'Ordre de paiement fournisseur imputé sur crédits de report.'
    };
  }

  if (creance.includes('consolid')) {
    return {
      docType: 'op',
      title: 'Ordre de paiement — Crédits Consolidés',
      shortTitle: `OP C.Consolidés (${numOrdre})`,
      category: 'Crédits Consolidés',
      badgeColor: 'violet',
      description: 'Ordre de paiement fournisseur sur crédits consolidés.'
    };
  }

  if (creance.includes('reste')) {
    return {
      docType: 'op',
      title: 'Ordre de paiement — Reste à payer',
      shortTitle: `OP Reste à payer (${numOrdre})`,
      category: 'Reste à payer',
      badgeColor: 'blue',
      description: 'Ordre de paiement fournisseur principal.'
    };
  }

  return {
    docType: 'op',
    title: `Ordre de paiement — ${ordre.creance || 'Paiement fournisseur'}`,
    shortTitle: `OP (${numOrdre})`,
    category: ordre.creance || 'Paiement',
    badgeColor: 'blue',
    description: 'Ordre de paiement fournisseur officiel.'
  };
}

/**
 * Returns the list of all documents associated with an order.
 * For standard supplier payment, returns both OP and OV.
 */
export function resolveOrdreDocumentsList(ordre) {
  if (!ordre) {
    return [resolveOrdreDocument(ordre)];
  }

  const typeMouv = String(ordre.type_mouvement || '').toLowerCase();
  const numOrdre = ordre.num_ordre || 'OP';

  // Si c'est un paiement fournisseur standard: inclure à la fois l'OP et l'OV
  if (typeMouv.includes('fournisseur') || (!typeMouv.includes('retenue') && !typeMouv.includes('tva') && !typeMouv.includes('ias') && !typeMouv.includes('is') && !typeMouv.includes('imputation'))) {
    const opDoc = resolveOrdreDocument(ordre);
    const ovDoc = {
      docType: 'ov',
      title: 'Ordre de virement (OV) — Paiement fournisseur',
      shortTitle: `OV (${numOrdre})`,
      category: 'Virement',
      badgeColor: 'blue',
      description: 'Ordre de virement bancaire pour règlement du montant au fournisseur.'
    };
    return [opDoc, ovDoc];
  }

  return [resolveOrdreDocument(ordre)];
}
