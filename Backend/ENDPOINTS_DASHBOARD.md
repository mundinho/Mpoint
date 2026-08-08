# Endpoints — Dashboard e Gráficos

Todos os endpoints abaixo estão sob o prefixo `/api` e exigem `Authorization: Bearer {token}` (login admin).

---

## `GET /api/admin/dashboard/estatisticas`

Números "ao vivo" para os cartões de resumo do topo do painel.

**Resposta 200:**
```json
{
  "total_participantes": 128,
  "participantes_validados": 94,
  "participantes_pendentes": 34,
  "total_numeros": 1000,
  "numeros_disponiveis": 970,
  "numeros_abertos": 30,
  "premios_disponiveis": 7,
  "premios_entregues": 3
}
```

**Resposta 422:** `{"message": "Não existe campanha activa."}`

---

## `GET /api/admin/dashboard/relatorios`

Dados agregados prontos a consumir directamente por gráficos (linhas temporais, distribuições e funil de conversão). Todos os blocos são relativos à campanha activa, **excepto** `registos_por_hora` e `sms_por_tipo_e_estado`, que são globais (abrangem todos os ciclos/campanhas, porque `usuarios` e `sms` não estão amarrados a uma campanha específica).

**Resposta 422:** `{"message": "Não existe campanha activa."}`

**Resposta 200:**
```json
{
  "resumo": {
    "total_quadrados": 1000,
    "total_registados": 128,
    "total_validados": 94,
    "total_pendentes_validacao": 34,
    "total_jogaram": 60,
    "total_venceram": 8,
    "total_nao_venceram": 50,
    "total_pendentes_resultado": 2
  },

  "jogadas_por_hora": [
    { "hora": "2026-08-09 09:00:00", "quantidade": 12 },
    { "hora": "2026-08-09 10:00:00", "quantidade": 27 }
  ],

  "vencedores_por_hora": [
    { "hora": "2026-08-09 10:00:00", "quantidade": 3 }
  ],

  "premios_atribuidos_por_hora": [
    { "hora": "2026-08-09 10:00:00", "quantidade": 3 }
  ],

  "registos_por_hora": [
    { "hora": "2026-08-09 09:00:00", "quantidade": 18 },
    { "hora": "2026-08-09 10:00:00", "quantidade": 40 }
  ],

  "resultados": [
    { "resultado": "vencedor", "quantidade": 8 },
    { "resultado": "nao_vencedor", "quantidade": 50 },
    { "resultado": "pendente", "quantidade": 2 }
  ],

  "premios_por_nome": [
    { "nome": "Smartphone", "quantidade": 3 },
    { "nome": "Voucher", "quantidade": 5 }
  ],

  "numeros_por_estado": [
    { "estado": "disponivel", "quantidade": 940 },
    { "estado": "aberto", "quantidade": 60 }
  ],

  "sms_por_tipo_e_estado": [
    { "tipo": "otp", "estado": "enviado", "quantidade": 120 },
    { "tipo": "otp", "estado": "falhado", "quantidade": 2 },
    { "tipo": "vencedor", "estado": "enviado", "quantidade": 8 }
  ],

  "funil": [
    { "etapa": "Registados", "quantidade": 128 },
    { "etapa": "Telefone validado", "quantidade": 94 },
    { "etapa": "Jogaram", "quantidade": 60 },
    { "etapa": "Venceram", "quantidade": 8 }
  ]
}
```

### Notas sobre cada bloco

| Chave | Tipo de gráfico sugerido | Descrição |
|---|---|---|
| `resumo` | cartões/KPIs | Totais simples, um valor por métrica |
| `jogadas_por_hora` | linha ou barras | Nº de participações (`sorteio/abrir`) por hora, baseado em `participacao.created_at` |
| `vencedores_por_hora` | linha ou barras | Igual, filtrado a `resultado = 'vencedor'` |
| `premios_atribuidos_por_hora` | linha ou barras | Participações com `premio_id` preenchido — quando um prémio foi de facto atribuído a alguém (não confundir com "entregue fisicamente", que é o campo `entregue` do prémio) |
| `registos_por_hora` | linha ou barras | Novos registos de `usuarios`, por hora — **global**, não filtrado por campanha |
| `resultados` | pizza/donut | Distribuição de `participacao.resultado` na campanha activa |
| `premios_por_nome` | barras horizontais | Quantos prémios de cada nome foram configurados/atribuídos na campanha activa |
| `numeros_por_estado` | pizza/donut | Progresso da campanha: quadrados `disponivel` vs `aberto` |
| `sms_por_tipo_e_estado` | barras empilhadas | Saúde operacional do envio de SMS (OTP, vencedor, etc.) — **global** |
| `funil` | funil/barras horizontais | Conversão registo → validação → jogou → venceu |

### Notas de implementação

- As séries "por hora" são geradas com `DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')` na base de dados (função privada `AdminDashboardController::porHora()`), reutilizável para qualquer nova métrica temporal.
- As horas devolvidas já estão no fuso de Maputo (`Africa/Maputo`, UTC+2), consistente com a configuração da aplicação — ver `config/app.php` (`timezone`) e `config/database.php` (`timezone` da ligação MySQL).
- Só existem linhas nas séries "por hora" para horas em que aconteceu pelo menos um evento (não há preenchimento de horas vazias com `quantidade: 0` — o frontend deve tratar isso ao desenhar o eixo temporal, se precisar de continuidade).
