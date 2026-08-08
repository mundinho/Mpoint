# Endpoints — Distribuição de Prémios, Tentativas e Actividade

Todos os endpoints abaixo estão sob o prefixo `/api`. Os marcados como **Bearer: sim** exigem `Authorization: Bearer {token}` (login admin), header `ngrok-skip-browser-warning: true` em ambiente de dev via túnel.

---

## 1. Modo de distribuição da campanha

### `PUT /api/campanha/{campanha}/distribuicao/manual`
**Bearer:** sim
Define/substitui a distribuição no modo manual. Bloqueado (422) se já existirem participações no ciclo.
**Body:**
```json
{
  "premios": [
    { "numero": 17, "nome": "Smartphone", "data_programada": "2026-08-10 12:00:00" },
    { "numero": 58, "nome": "Voucher", "data_programada": null }
  ]
}
```
**Resposta 200:** objecto `campanha` actualizado (`modo_distribuicao: "manual"`).
**Resposta 422:** número repetido, fora do intervalo, ou participações já existentes.

### `PUT /api/campanha/{campanha}/distribuicao/aleatorio`
**Bearer:** sim
Define a configuração aleatória; o backend sorteia os números uma única vez e fixa a distribuição (não muda em requests seguintes).
**Body:**
```json
{
  "linhas": [
    { "nome": "Smartphone", "quantidade": 3, "logica_aleatoriedade": "aleatorio", "data_programada": "2026-08-10 12:00:00" },
    { "nome": "Voucher", "quantidade": 5, "logica_aleatoriedade": "aleatorio", "data_programada": null }
  ]
}
```
**Resposta 200:** objecto `campanha` actualizado (`modo_distribuicao: "aleatorio"`).
**Resposta 422:** soma de `quantidade` maior que `total_quadrados`, ou participações já existentes.

---

## 2. Reconstrução da tela de gestão da campanha

### `GET /api/campanha/ativa`
**Bearer:** não
Devolve tudo o necessário para reconstruir a tela após refresh.

**Resposta 200 — modo `aleatorio`:**
```json
{
  "id": 1,
  "nome": "Campanha Agosto",
  "total_quadrados": 1000,
  "total_premios": 10,
  "estado": "ativa",
  "modo_distribuicao": "aleatorio",
  "data_inicio": "...",
  "data_fim": null,
  "otp_validade_minutos": 5,
  "distribuicao_aleatoria": [
    { "nome": "Smartphone", "quantidade": 3, "logica_aleatoriedade": "aleatorio", "data_programada": "2026-08-10T12:00:00.000000Z" }
  ],
  "premios": []
}
```

**Resposta 200 — modo `manual`:**
```json
{
  "id": 1,
  "modo_distribuicao": "manual",
  "...": "...",
  "distribuicao_aleatoria": [],
  "premios": [
    {
      "id": 8,
      "numero": 17,
      "nome": "Smartphone",
      "data_programada": "2026-08-10T12:00:00.000000Z",
      "entregue": false
    }
  ]
}
```

**Resposta 200 — sem campanha activa:** `{}`

`modo_distribuicao`: `"manual" | "aleatorio"`.

---

## 3. Resumo dos prémios

### `GET /api/admin/premios/resumo`
**Bearer:** sim
Agrupa os prémios da campanha activa por `nome` e devolve as quantidades totais, já atribuídas (número já aberto por um participante) e remanescentes.
**Resposta 200:**
```json
[
  { "id": 8, "nome": "Smartphone", "quantidade_total": 3, "quantidade_atribuida": 1, "quantidade_remanescente": 2 }
]
```

### `PUT /api/premios/{numero}`
**Bearer:** sim
Actualiza o prémio associado a um número (usado sobretudo para marcar entrega: `{ "entregue": true }`).
**Body (todos os campos opcionais):** `{ "nome": "...", "data_programada": "...", "entregue": true }`
**Resposta 200:** objecto do prémio actualizado.

---

## 4. Tentativas dos participantes

### `GET /api/admin/participantes`
**Bearer:** sim
**Resposta 200:**
```json
[
  {
    "id": 5,
    "nome": "João",
    "telefone": "258845916612",
    "estado": "validado",
    "numero": 17,
    "resultado": "vencedor",
    "premio": "Smartphone",
    "participou_em": "...",
    "tentativas_usadas": 1,
    "tentativas_disponiveis": 1
  }
]
```
`resultado`: `"pendente" | "vencedor" | "nao_vencedor"` (campo pode ser `null` se ainda não participou).
Por padrão, todo participante começa com `tentativas_disponiveis: 1`.

### `POST /api/admin/participantes/conceder-tentativa`
**Bearer:** sim
Concede manualmente +1 tentativa a um participante específico no ciclo activo.
**Body:**
```json
{ "usuario_id": 5 }
```
**Resposta 200:**
```json
{ "id": 12, "usuario_id": 5, "campanha_id": 1, "tentativas_disponiveis": 2, "tentativas_usadas": 1 }
```

---

## 5. Sorteio

### `POST /api/sorteio/abrir`
**Bearer:** não
**Body:** `{ "usuario_id": 5, "numero": 17 }`
**Resposta 201:**
```json
{
  "id": 42,
  "campanha_id": 1,
  "usuario_id": 5,
  "quadrado_id": 17,
  "numero": 17,
  "resultado": "vencedor",
  "premio_id": 8,
  "created_at": "...",
  "updated_at": "..."
}
```
`resultado` possíveis: `"pendente" | "vencedor" | "nao_vencedor"`.
**Resposta 422:** `{"message": "Sem tentativas disponíveis neste ciclo."}` quando `tentativas_usadas >= tentativas_disponiveis`.

---

## 6. Actividade recente do dashboard

### `GET /api/admin/dashboard/atividade`
**Bearer:** sim
Devolve as últimas actividades do ciclo activo, mais recente primeiro.
**Resposta 200:**
```json
[
  {
    "tipo": "vencedor",
    "usuario_id": 5,
    "nome": "João",
    "numero": 17,
    "premio": "Smartphone",
    "data_hora": "2026-08-07T10:15:00.000000Z"
  }
]
```
`tipo`: `"registo" | "validacao" | "participacao" | "vencedor" | "nao_vencedor" | "premio_entregue"`.

---

## Notas de implementação (backend)

- Não existe mais o conceito de "categoria de prémio" nem de resultado "tentar novamente" — todo número com prémio associado é sempre `vencedor`.
- No modo aleatório, a distribuição é sorteada uma vez em `configurarDistribuicaoAleatoria` e persistida via `premio_id` nos `quadrado` — não é re-sorteada em cada leitura.
- Alterar a distribuição (manual ou aleatória) é bloqueado assim que existir qualquer participação no ciclo.
- `logica_aleatoriedade` é guardado tal como enviado pelo frontend (hoje só `"aleatorio"`), sem efeito no backend além de ser devolvido em `GET /campanha/ativa`.
