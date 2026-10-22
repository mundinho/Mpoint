import { normalizeMozPhone } from '../utils/telefone'
import { publishDemoSms } from '../demo'
import { site } from '../site'

const BASE_URL = import.meta.env.VITE_API_URL || '/api'

async function request(endpoint, options = {}) {
const response = await fetch(`${BASE_URL}${endpoint}`, {
  ...options,

  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    // Jogo: identifica a campanha pelo subdomínio (um subdomínio inválido nunca
    // cai noutra campanha — o servidor responde "campanha não encontrada")
    ...(site.mode === 'game' ? { 'X-Campanha': site.slug || 'invalido' } : {}),
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

  publishDemoSms(data)

  if (!response.ok) {
    // Erros de validação: mostrar a primeira mensagem concreta
    const validationMessage = data?.errors
      ? Object.values(data.errors).flat()[0]
      : null

    const error = new Error(
      validationMessage ||
      data?.message ||
      data?.erro ||
      data?.error ||
      'Ocorreu um erro ao comunicar com o servidor.'
    )

    error.status = response.status
    error.code = data?.codigo || null
    error.fields = data?.errors || null

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

export function openNumber(usuarioId, numero, participationToken) {
  return request('/sorteio/abrir', {
    method: 'POST',
    headers: {
      'X-Participacao-Token': participationToken || ''
    },
    body: JSON.stringify({
      usuario_id: usuarioId,
      numero
    })
  })
}

export function getResult(usuarioId, participationToken) {
  return request(`/participacoes/${usuarioId}/resultado`, {
    headers: {
      'X-Participacao-Token': participationToken || ''
    }
  })
}

export function getActiveCampaign() {
  return request('/campanha/ativa')
}

// Cria uma campanha nova (em pausa) com o seu link
export function createCampaign(data, token) {
  return request('/admin/campanhas', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

// Novo ciclo: encerra a campanha e cria outra com a mesma configuração e link
export function restartCampaign(id, token) {
  return request(`/admin/campanhas/${id}/reiniciar`, {
    method: 'POST',
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

export function getPrizeBank(token) {
  return request('/admin/premios-banco', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function createPrizeBankItem(data, token) {
  return request('/admin/premios-banco', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function updatePrizeBankItem(id, data, token) {
  return request(`/admin/premios-banco/${id}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(data)
  })
}

export function deletePrizeBankItem(id, token) {
  return request(`/admin/premios-banco/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`
    }
  })
}

export function getCampaignPrizes(campaignId, token) {
  return request(
    `/admin/campanhas/${campaignId}/premios-campanha`,
    {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  )
}

export function createCampaignPrize(campaignId, data, token) {
  return request(
    `/admin/campanhas/${campaignId}/premios-campanha`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(data)
    }
  )
}

export function updateCampaignPrize(campaignId, prizeId, data, token) {
  return request(
    `/admin/campanhas/${campaignId}/premios-campanha/${prizeId}`,
    {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(data)
    }
  )
}

export function deleteCampaignPrize(campaignId, prizeId, token) {
  return request(
    `/admin/campanhas/${campaignId}/premios-campanha/${prizeId}`,
    {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  )
}

