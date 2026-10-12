/**
 * Convert numbers to French words with Moroccan Dirhams formatting
 * Ex: 1822867.20 -> "Un million huit cent vingt-deux mille huit cent soixante-sept dirhams et vingt centimes"
 */

const UNITS = [
  '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
  'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'
];

const TENS = [
  '', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingts', 'quatre-vingt-dix'
];

function convertBelowThousand(n) {
  if (n === 0) return '';
  if (n < 20) return UNITS[n];

  if (n < 100) {
    const ten = Math.floor(n / 10);
    const unit = n % 10;

    if (ten === 7) {
      if (unit === 1) return 'soixante-et-onze';
      return `soixante-${UNITS[10 + unit]}`;
    }
    if (ten === 8) {
      if (unit === 0) return 'quatre-vingts';
      return `quatre-vingt-${UNITS[unit]}`;
    }
    if (ten === 9) {
      return `quatre-vingt-${UNITS[10 + unit]}`;
    }

    if (unit === 0) return TENS[ten];
    if (unit === 1) return `${TENS[ten]}-et-un`;
    return `${TENS[ten]}-${UNITS[unit]}`;
  }

  const hundred = Math.floor(n / 100);
  const remainder = n % 100;
  let hundredStr = '';

  if (hundred === 1) {
    hundredStr = 'cent';
  } else {
    hundredStr = remainder === 0 ? `${UNITS[hundred]} cents` : `${UNITS[hundred]} cent`;
  }

  if (remainder === 0) return hundredStr;
  return `${hundredStr} ${convertBelowThousand(remainder)}`;
}

export function numberToFrenchWords(number) {
  const n = Math.abs(Number(number) || 0);
  const integerPart = Math.floor(n);
  const decimalPart = Math.round((n - integerPart) * 100);

  if (integerPart === 0 && decimalPart === 0) {
    return 'zéro dirham';
  }

  const parts = [];

  // Milliards
  const billions = Math.floor(integerPart / 1000000000);
  let rem = integerPart % 1000000000;

  if (billions > 0) {
    parts.push(billions === 1 ? 'un milliard' : `${convertBelowThousand(billions)} milliards`);
  }

  // Millions
  const millions = Math.floor(rem / 1000000);
  rem = rem % 1000000;

  if (millions > 0) {
    parts.push(millions === 1 ? 'un million' : `${convertBelowThousand(millions)} millions`);
  }

  // Milliers
  const thousands = Math.floor(rem / 1000);
  const remainder = rem % 1000;

  if (thousands > 0) {
    parts.push(thousands === 1 ? 'mille' : `${convertBelowThousand(thousands)} mille`);
  }

  if (remainder > 0) {
    parts.push(convertBelowThousand(remainder));
  }

  let words = parts.join(' ').trim();
  if (!words) words = 'zéro';

  const dirhamLabel = integerPart > 1 ? 'dirhams' : 'dirham';
  let result = `${words} ${dirhamLabel}`;

  if (decimalPart > 0) {
    const centimesWords = convertBelowThousand(decimalPart);
    const centimesLabel = decimalPart > 1 ? 'centimes' : 'centime';
    result += ` et ${centimesWords} ${centimesLabel}`;
  }

  return result.charAt(0).toUpperCase() + result.slice(1);
}

export default numberToFrenchWords;
