# Endpoints — Distribuição de Prémios, Tentativas e Actividade

Todos os endpoints abaixo estão sob o prefixo `/api`. Os marcados como **Bearer: sim** exigem `Authorization: Bearer {token}` (login admin), header `ngrok-skip-browser-warning: true` em ambiente de dev via túnel.

---

## 1. Gestão de campanhas

### `GET /api/admin/campanhas`
**Bearer:** sim
Lista todas as campanhas (qualquer estado), mais recente primeiro, com contadores para uma tela de selecção/gestão.
**Resposta 200:**
```json
[
  {
    "id": 2,
    "nome": "Campanha Agosto",
    "estado": "encerrada",
    "modo_distribuicao": "aleatorio",
    "data_inicio": "2026-08-08T22:07:36.000000Z",
    "data_fim": null,
    "total_quadrados": 1000,
    "total_premios": 10,
    "quadrados_abertos": 60,
    "premios_configurados": 8,
    "participantes": 60
  },
  {
    "id": 1,
    "nome": null,
    "estado": "encerrada",
    "modo_distribuicao": "manual",
    "data_inicio": "2026-08-09T00:00:00.000000Z",
    "data_fim": null,
    "total_quadrados": 1000,
    "total_premios": 10,
    "quadrados_abertos": 0,
    "premios_configurados": 0,
    "participantes": 0
  }
]
```
`quadrados_abertos`, `premios_configurados` e `participantes` são contadores (não listas) — para o detalhe completo de uma campanha específica, usar o endpoint abaixo.

### `GET /api/admin/campanhas/{campanha}`
**Bearer:** sim
Detalhe completo de **qualquer** campanha por id (activa, pausada ou encerrada) — mesmo formato de `GET /api/campanha/ativa` (secção 3), incluindo `premios` e `distribuicao_aleatoria`. Permite seleccionar e gerir um ciclo específico, não só o activo — é o endpoint a usar para, por exemplo, consultar/editar os prémios de uma campanha já encerrada.
**Resposta 404:** id inexistente.

### `POST /api/campanha/reset`
**Bearer:** sim
Encerra a campanha activa (se existir) e cria uma nova já com 1000 números e 10 prémios genéricos ("Prémio 1"..."Prémio 10") distribuídos aleatoriamente — um ponto de partida rápido, pronto a ajustar depois via distribuição manual/aleatória.
**Resposta 201:** objecto da nova campanha.

### `PUT /api/campanha/{campanha}`
**Bearer:** sim
Actualiza a configuração de uma campanha específica.
**Body (todos os campos opcionais):**
```json
{ "nome": "Campanha Setembro", "data_inicio": "2026-09-01 08:00:00", "data_fim": null, "total_quadrados": 1000, "total_premios": 10, "otp_validade_minutos": 5 }
```
**Resposta 422:** ao alterar `total_quadrados` de uma campanha que já tem participações (os números são recriados do zero, o que apagaria participações existentes).

### `POST /api/campanha/{campanha}/activar`
### `POST /api/campanha/{campanha}/pausar`
### `POST /api/campanha/{campanha}/encerrar`
**Bearer:** sim (as três)
Mudam `estado` para `ativa` | `pausada` | `encerrada` respectivamente. Sem corpo. **Resposta 200:** objecto da campanha actualizado.

---

## 2. Modo de distribuição da campanha

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

## 3. Reconstrução da tela de gestão da campanha

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

## 4. Resumo dos prémios

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

## 5. Tentativas dos participantes

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

## 6. Sorteio

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

## 7. Actividade recente do dashboard

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
- `POST /campanha/reset`, `PUT /campanha/{campanha}` e `activar`/`pausar`/`encerrar` passaram a exigir `Bearer` (antes estavam acessíveis sem autenticação — só `GET /campanha/ativa`, usado pelo ecrã público do participante, continua sem `Bearer`).
- Todos os endpoints de gestão de campanha (secção 1) e distribuição (secção 2) recebem `{campanha}` pela rota, não pela campanha activa — funcionam para qualquer campanha, incluindo ciclos já encerrados.
