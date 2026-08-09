import { normalizeMozPhone } from '../utils/telefone'

const BASE_URL = import.meta.env.VITE_API_URL || '/api'

async function request(endpoint, options = {}) {
const response = await fetch(`${BASE_URL}${endpoint}`, {
  ...options,

  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'ngrok-skip-browser-warning': 'true',
    ...options.headers
  }
})

  let data = null

  try {
    data = await response.json()
  } catch {
    data = null
  }

  if (!response.ok) {
    const error = new Error(
      data?.message ||
      data?.erro ||
      data?.error ||
      'Ocorreu um erro ao comunicar com o servidor.'
    )

    error.status = response.status

    throw error
  }

  return data
}

export function registerParticipant(data) {
  return request('/participantes/registar', {
    method: 'POST',
    body: JSON.stringify({
      nome: data.name,
      telefone: normalizeMozPhone(data.phone)
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

export function getAdminCampaigns(token) {
  return request('/admin/campanhas', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getAdminCampaign(id, token) {
  return request(`/admin/campanhas/${id}`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

function formatMozPhone(telefone) {
  return normalizeMozPhone(telefone)
}

export function requestAdminLogin(telefone) {
  return request('/admin/login/solicitar', {
    method: 'POST',
    body: JSON.stringify({
      telefone: formatMozPhone(telefone)
    })
  })
}

export function validateAdminLogin(telefone, codigo) {
  return request('/admin/login/validar', {
    method: 'POST',
    body: JSON.stringify({
      telefone: formatMozPhone(telefone),
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

export function getCampaigns(token) {
  return request('/admin/campanhas', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getCampaign(id, token) {
  return request(`/admin/campanhas/${id}`, {
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

export function getDashboardStatistics(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/estatisticas`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getCampaignReports(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/relatorios`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getAdminParticipants(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/participantes`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getAdminWinners(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/vencedores`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getPrizeSummary(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/premios/resumo`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function markPrizeDelivered(campaignId, numero, token) {
  return request(`/admin/campanhas/${campaignId}/premios/${numero}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({
      entregue: true
    })
  })
}




export function configureRandomDistribution(campaignId, linhas, token) {
  return request(
    `/campanha/${campaignId}/distribuicao/aleatorio`,
    {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify({
        linhas
      })
    }
  )
}

export function configureManualDistribution(campaignId, premios, token) {
  return request(
    `/campanha/${campaignId}/distribuicao/manual`,
    {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify({
        premios
      })
    }
  )
}

export function grantExtraAttempt(userId, token) {
  return request('/admin/participantes/conceder-tentativa', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({
      usuario_id: userId
    })
  })
}

export function getRecentActivity(campaignId, token) {
  return request(`/admin/campanhas/${campaignId}/atividade`, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

