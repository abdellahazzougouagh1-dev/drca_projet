// Même calcul que la fiche PDF : les deux montants doivent être renseignés.
export function creditDisponible(credit, depenses) {
  if (credit === null || credit === undefined || credit === '' ||
      depenses === null || depenses === undefined || depenses === '') return '';
  return (Number(credit) - Number(depenses)).toFixed(2);
}
