const BASE_URL =
  'https://funky-meerkat-heavily.ngrok-free.app/api'

async function request(endpoint, options = {}) {
  const response = await fetch(`${BASE_URL}${endpoint}`, {
    headers: {
      'Content-Type': 'application/json',
      'ngrok-skip-browser-warning': 'true',
      ...options.headers
    },
    ...options
  })

  let data = null

  try {
    data = await response.json()
  } catch {
    data = null
  }

  if (!response.ok) {
    throw new Error(
      data?.message ||
      data?.erro ||
      data?.error ||
      'Ocorreu um erro ao comunicar com o servidor.'
    )
  }

  return data
}

export function registerParticipant(data) {
  return request('/participantes/registar', {
    method: 'POST',
    body: JSON.stringify({
      nome: data.name,
      telefone: data.phone
    })
  })
}

export function resendOtp(usuarioId) {
  return request('/otp/reenviar', {
    method: 'POST',
    body: JSON.stringify({
      usuario_id: usuarioId
    })
  })
}

export function validateOtp(usuarioId, codigo) {
  return request('/otp/validar', {
    method: 'POST',
    body: JSON.stringify({
      usuario_id: usuarioId,
      codigo
    })
  })
}

export function getSquares() {
  return request('/quadrados')
}

export function openNumber(usuarioId, numero) {
  return request('/sorteio/abrir', {
    method: 'POST',
    body: JSON.stringify({
      usuario_id: usuarioId,
      numero
    })
  })
}

export function getResult(usuarioId) {
  return request(`/participacoes/${usuarioId}/resultado`)
}

export function getPrizes() {
  return request('/premios')
}

export function getActiveCampaign() {
  return request('/campanha/ativa')
}

export function resetCampaign(token) {
  return request('/campanha/reset', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}


export function requestAdminLogin(telefone) {
  return request('/admin/login/solicitar', {
    method: 'POST',
    body: JSON.stringify({
      telefone
    })
  })
}

export function validateAdminLogin(telefone, codigo) {
  return request('/admin/login/validar', {
    method: 'POST',
    body: JSON.stringify({
      telefone,
      codigo
    })
  })
}

export function getAdminMe(token) {
  return request('/admin/me', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function adminLogout(token) {
  return request('/admin/logout', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getActiveCampaignAdmin(token) {
  return request('/campanha/ativa', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function updateCampaign(id, data, token) {
  return request(`/campanha/${id}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function activateCampaign(id, token) {
  return request(`/campanha/${id}/activar`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function pauseCampaign(id, token) {
  return request(`/campanha/${id}/pausar`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function closeCampaignApi(id, token) {
  return request(`/campanha/${id}/encerrar`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function createPrize(data, token) {
  return request('/premios', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function updatePrize(numero, data, token) {
  return request(`/premios/${numero}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function deletePrize(numero, token) {
  return request(`/premios/${numero}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function setCampaignPrizes(prizes, token) {
  return request('/campanha/premios', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({
      premios: prizes
    })
  })
}

export function getUsers(token) {
  return request('/usuarios', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getUser(id, token) {
  return request(`/usuarios/${id}`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function createUser(data, token) {
  return request('/usuarios', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({
      nome: data.nome,
      telefone: data.telefone
    })
  })
}

export function updateUser(id, data, token) {
  return request(`/usuarios/${id}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function deleteUser(id, token) {
  return request(`/usuarios/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getDashboardStatistics(token) {
  return request('/admin/dashboard/estatisticas', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getAdminParticipants(token) {
  return request('/admin/participantes', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getAdminWinners(token) {
  return request('/admin/vencedores', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function markPrizeDelivered(numero, token) {
  return request(`/premios/${numero}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({
      entregue: true
    })
  })
}


