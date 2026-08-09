// Espelha Backend/app/Support/Telefone.php::normalizar — mesmas regras dos dois
// lados, para o frontend rejeitar antes de enviar o que o backend rejeitaria
// de qualquer forma, mas continuar a aceitar o número com ou sem prefixo.

export function normalizeMozPhone(value) {
  let digits = String(value || '').replace(/\D/g, '')

  if (digits.startsWith('00258')) {
    digits = digits.slice(2)
  }

  if (digits.length === 9 && digits.startsWith('8')) {
    digits = `258${digits}`
  }

  return digits
}

export function isValidMozPhone(value) {
  return /^258[8][2-7][0-9]{7}$/.test(normalizeMozPhone(value))
}
