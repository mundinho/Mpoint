# Endpoints — Distribuição de Prémios, Tentativas e Actividade

Todos os endpoints abaixo estão sob o prefixo `/api`. Os marcados como **Bearer: sim** exigem `Authorization: Bearer {token}` (login admin), header `ngrok-skip-browser-warning: true` em ambiente de dev via túnel.

---

## 1. Categorias de prémio (CRUD)

### `GET /api/admin/categorias-premio`
**Bearer:** sim
**Resposta 200:**
```json
[
  { "id": 1, "nome": "Tentar Novamente", "tipo": "tentar_novamente", "created_at": "...", "updated_at": "..." },
  { "id": 2, "nome": "Smartphone", "tipo": "normal", "created_at": "...", "updated_at": "..." }
]
```
`tipo`: `"normal" | "tentar_novamente"`. A categoria `tentar_novamente` é criada automaticamente pelo backend na primeira vez que for necessária (sorteio ou configuração de distribuição aleatória) — o frontend não precisa criá-la.

### `POST /api/admin/categorias-premio`
**Bearer:** sim
**Body:**
```json
{ "nome": "Smartphone" }
```
**Resposta 201:** objecto da categoria criada (sempre `tipo: "normal"`).

### `PUT /api/admin/categorias-premio/{id}`
**Bearer:** sim
**Body:** `{ "nome": "Novo nome" }`
**Resposta 200:** objecto actualizado.
**Resposta 422** se tentar editar a categoria `tentar_novamente`.

### `DELETE /api/admin/categorias-premio/{id}`
**Bearer:** sim
**Resposta 204** (sem corpo).
**Resposta 422** se for a categoria `tentar_novamente`, ou se houver prémios associados.

---

## 2. Modo de distribuição da campanha

### `PUT /api/campanha/{campanha}/distribuicao/manual`
**Bearer:** sim
Define/substitui a distribuição no modo manual. Bloqueado (422) se já existirem participações no ciclo.
**Body:**
```json
{
  "premios": [
    { "numero": 17, "categoria_id": 2, "descricao": "Smartphone", "data_programada": "2026-08-10 12:00:00" },
    { "numero": 58, "categoria_id": 3, "descricao": "Voucher", "data_programada": null }
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
    { "categoria_id": 2, "quantidade": 3, "data_programada": "2026-08-10 12:00:00" },
    { "categoria_id": 4, "quantidade": 5, "data_programada": null }
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
    { "categoria_id": 2, "categoria_nome": "Smartphone", "quantidade": 3, "data_programada": "2026-08-10T12:00:00.000000Z" }
  ],
  "premios": null
}
```

**Resposta 200 — modo `manual`:**
```json
{
  "id": 1,
  "modo_distribuicao": "manual",
  "...": "...",
  "distribuicao_aleatoria": null,
  "premios": [
    {
      "numero": 17,
      "categoria_id": 2,
      "categoria_nome": "Smartphone",
      "descricao": "Smartphone",
      "data_programada": "2026-08-10T12:00:00.000000Z",
      "entregue": false
    }
  ]
}
```

**Resposta 200 — sem campanha activa:** `null`

`modo_distribuicao`: `"manual" | "aleatorio"`.

---

## 4. Tentativas dos participantes

### `GET /api/admin/participantes` (alterado)
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
    "resultado": "tentar_novamente",
    "premio": null,
    "participou_em": "...",
    "tentativas_usadas": 1,
    "tentativas_disponiveis": 2
  }
]
```
`resultado`: `"pendente" | "vencedor" | "nao_vencedor" | "tentar_novamente"` (campo pode ser `null` se ainda não participou).
Por padrão, todo participante começa com `tentativas_disponiveis: 1`.

### `POST /api/admin/participantes/conceder-tentativa` (novo)
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

## 5. Sorteio — resultado "Tentar Novamente" (alterado)

### `POST /api/sorteio/abrir` (comportamento alterado, mesma rota)
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
  "resultado": "tentar_novamente",
  "premio_id": 8,
  "created_at": "...",
  "updated_at": "..."
}
```
`resultado` possíveis: `"pendente" | "vencedor" | "nao_vencedor" | "tentar_novamente"`.
Quando `resultado === "tentar_novamente"`: o backend já concede automaticamente +1 tentativa ao participante (visível em `tentativas_disponiveis` no endpoint de participantes). O frontend deve levar o participante de volta à tela de sorteio em vez do ecrã de resultado final.
**Resposta 422:** `{"message": "Sem tentativas disponíveis neste ciclo."}` quando `tentativas_usadas >= tentativas_disponiveis`.

---

## 6. Actividade recente do dashboard (novo)

### `GET /api/admin/dashboard/atividade`
**Bearer:** sim
Devolve as últimas 50 actividades do ciclo activo, mais recente primeiro.
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
  },
  {
    "tipo": "tentar_novamente",
    "usuario_id": 6,
    "nome": "Maria",
    "numero": 58,
    "premio": null,
    "data_hora": "2026-08-07T10:10:00.000000Z"
  }
]
```
`tipo`: `"registo" | "validacao" | "participacao" | "vencedor" | "tentar_novamente" | "premio_entregue"`.

---

## Resumo — todos os endpoints novos/alterados

| Método | Rota | Bearer | Novo/Alterado |
|---|---|---|---|
| GET | `/api/admin/categorias-premio` | sim | novo |
| POST | `/api/admin/categorias-premio` | sim | novo |
| PUT | `/api/admin/categorias-premio/{id}` | sim | novo |
| DELETE | `/api/admin/categorias-premio/{id}` | sim | novo |
| PUT | `/api/campanha/{campanha}/distribuicao/manual` | sim | novo |
| PUT | `/api/campanha/{campanha}/distribuicao/aleatorio` | sim | novo |
| GET | `/api/campanha/ativa` | não | alterado (payload) |
| GET | `/api/admin/participantes` | sim | alterado (payload) |
| POST | `/api/admin/participantes/conceder-tentativa` | sim | novo |
| POST | `/api/sorteio/abrir` | não | alterado (comportamento) |
| GET | `/api/admin/dashboard/atividade` | sim | novo |

## Notas de implementação (backend)

- A categoria `tentar_novamente` é única por sistema (`firstOrCreate` por `tipo`).
- No modo aleatório, a distribuição é sorteada uma vez em `configurarDistribuicaoAleatoria` e persistida via `premio_id` nos `quadrado` — não é re-sorteada em cada leitura.
- Alterar a distribuição (manual ou aleatória) é bloqueado assim que existir qualquer participação no ciclo (mesma regra já usada em `definirPremios`).
- **Pendente:** rodar `php artisan migrate` no ambiente onde o MySQL estiver disponível — não consegui rodar aqui porque a base local (porta 3307) não está a aceitar conexões. As migrations já estão no repositório, prontas a aplicar.
