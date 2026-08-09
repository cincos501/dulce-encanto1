/**
 * Normalize a phone number to the canonical Bolivian format: 591XXXXXXXX.
 *
 * Rules:
 * - Remove spaces, dashes, parentheses, plus sign, and any other non-numeric character.
 * - If it begins with 591, leave it as is.
 * - If it has exactly 8 digits, prepend 591.
 * - Otherwise, return the numeric-only representation.
 */
export function normalizePhone(phone: string): string {
  // Remove all non-numeric characters
  const normalized = phone.replace(/\D/g, '');

  if (!normalized) {
    return '';
  }

  // If it starts with 591, return it
  if (normalized.startsWith('591')) {
    return normalized;
  }

  // If it has exactly 8 digits, prepend 591
  if (normalized.length === 8) {
    return '591' + normalized;
  }

  return normalized;
}
