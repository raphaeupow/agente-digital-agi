# Manual de JSON para Componentes do Sistema Robot

Este manual descreve a estrutura JSON para cada tipo de componente disponível no sistema Robot, incluindo exemplos práticos e boas práticas para implementação.

## Índice

1. [Componentes Disponíveis](#componentes-disponíveis)
   - [Conditional (Condicional)](#1-conditional-condicional)
   - [Repeater (Repetidor)](#2-repeater-repetidor)
   - [Play (Reprodução)](#3-play-reprodução)
   - [Checkpoint (Ponto de Verificação)](#4-checkpoint-ponto-de-verificação)
   - [Start (Início)](#5-start-início)
   - [SetVar (Definir Variável)](#6-setvar-definir-variável)
   - [End (Fim)](#7-end-fim)
   - [Decision (Decisão)](#8-decision-decisão)
   - [Integration (Integração)](#9-integration-integração)
   - [Transfer (Transferência)](#10-transfer-transferência)
2. [Exemplo de Fluxo Completo](#exemplo-de-fluxo-completo)
3. [Boas Práticas](#boas-práticas)
4. [Dicas de Depuração](#dicas-de-depuração)

## Componentes Disponíveis

### 1. Conditional (Condicional)

#### Forma Tradicional (usando operações)

```json
{
  "id": 1,
  "component": "conditional",
  "faultNextId": 2,
  "options": [
    {
      "nextId": 3,
      "operations": [
        {
          "variableIdA": "variavel1",
          "operationBettween": "==",
          "variableIdB": "valor1",
          "operationConnection": "&&"
        },
        {
          "variableIdA": "variavel2",
          "operationBettween": ">",
          "variableIdB": "10",
          "operationConnection": ""
        }
      ]
    }
  ]
}
```

#### Nova Forma (usando equação em string)

```json
{
  "id": 1,
  "component": "conditional",
  "faultNextId": 2,
  "options": [
    {
      "nextId": 3,
      "equation": "variavel1 == 'valor1' && variavel2 > 10"
    },
    {
      "nextId": 4,
      "equation": "variavel3 != 'valor2'"
    }
  ]
}
```

**Descrição**: Avalia condições lógicas e direciona o fluxo com base no resultado.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "conditional")
- `faultNextId`: ID do próximo componente se nenhuma condição for atendida
- `options`: Array de opções (condições) a serem avaliadas
  - `nextId`: ID do próximo componente se esta opção for verdadeira
  - `equation`: (Nova forma) String com a equação a ser avaliada
  - `operations`: (Forma tradicional) Array de operações (condições) que compõem esta opção
    - `variableIdA`: Nome da primeira variável a ser comparada
    - `operationBettween`: Operador de comparação (">", "<", ">=", "<=", "==", "!=")
    - `variableIdB`: Nome da segunda variável ou valor a ser comparado
    - `operationConnection`: Operador lógico ("&&" para AND, "||" para OR, ou vazio se for a última)

**Exemplo Prático**:
```json
{
  "id": 10,
  "component": "conditional",
  "faultNextId": 11,
  "options": [
    {
      "nextId": 12,
      "equation": "saldo >= 100 && tipo_cliente == 'premium'"
    },
    {
      "nextId": 13,
      "equation": "saldo >= 50 && tipo_cliente == 'regular'"
    }
  ]
}
```

### 2. Repeater (Repetidor)

```json
{
  "id": 1,
  "component": "repeater",
  "nextId": 2,
  "totalRepeat": 3,
  "faultNextId": 4
}
```

**Descrição**: Repete uma ação um número específico de vezes.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "repeater")
- `nextId`: ID do próximo componente a ser executado após cada repetição
- `totalRepeat`: Número total de repetições
- `faultNextId`: ID do próximo componente após atingir o número máximo de repetições

**Exemplo Prático**:
```json
{
  "id": 20,
  "component": "repeater",
  "nextId": 21,
  "totalRepeat": 3,
  "faultNextId": 22
}
```

### 3. Play (Reprodução)

```json
{
  "id": 1,
  "component": "play",
  "nextId": 2,
  "text": "Texto a ser reproduzido",
  "style": "estilo_tts",
  "dtmfStop": "1234567890*#"
}
```

**Descrição**: Reproduz um texto usando text-to-speech.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "play")
- `nextId`: ID do próximo componente após a reprodução
- `text`: Texto a ser reproduzido (opcional)
- `style`: Estilo de TTS a ser aplicado (opcional)
- `dtmfStop`: Teclas DTMF que podem interromper a reprodução (opcional)

**Exemplo Prático**:
```json
{
  "id": 30,
  "component": "play",
  "nextId": 31,
  "text": "Seu saldo atual é de [saldo] reais. Para consultar outro serviço, pressione 1. Para encerrar, pressione 2.",
  "style": "natural",
  "dtmfStop": "12"
}
```

### 4. Checkpoint (Ponto de Verificação)

```json
{
  "id": 1,
  "component": "checkpoint",
  "nextId": 2,
  "statusId": 5
}
```

**Descrição**: Define um ponto de verificação no fluxo.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "checkpoint")
- `nextId`: ID do próximo componente
- `statusId`: ID do status a ser definido

**Exemplo Prático**:
```json
{
  "id": 40,
  "component": "checkpoint",
  "nextId": 41,
  "statusId": 5
}
```

### 5. Start (Início)

```json
{
  "id": 1,
  "component": "start",
  "nextId": 2,
  "statusId": 3
}
```

**Descrição**: Inicia o fluxo e define o status inicial.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "start")
- `nextId`: ID do próximo componente
- `statusId`: ID do status inicial

**Exemplo Prático**:
```json
{
  "id": 50,
  "component": "start",
  "nextId": 51,
  "statusId": 1
}
```

### 6. SetVar (Definir Variável)

```json
{
  "id": 1,
  "component": "setVar",
  "nextId": 2,
  "variable": "nome_variavel",
  "value": "valor_variavel"
}
```

**Descrição**: Define o valor de uma variável.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "setVar")
- `nextId`: ID do próximo componente
- `variable`: Nome da variável a ser definida
- `value`: Valor a ser atribuído à variável

**Exemplo Prático**:
```json
{
  "id": 60,
  "component": "setVar",
  "nextId": 61,
  "variable": "tentativas",
  "value": "0"
}
```

### 7. End (Fim)

```json
{
  "id": 1,
  "component": "end"
}
```

**Descrição**: Finaliza o fluxo.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "end")

**Exemplo Prático**:
```json
{
  "id": 70,
  "component": "end"
}
```

### 8. Decision (Decisão)

```json
{
  "id": 1,
  "component": "decision",
  "muteNextId": 2,
  "nextId": 3,
  "timeout": 10,
  "timeMute": 3,
  "timeSilenceBetweenSpeech": 2,
  "timeDTMF": 5,
  "alternatives": [
    {
      "nextId": 4,
      "words": ["sim", "claro", "positivo"]
    },
    {
      "nextId": 5,
      "words": ["não", "negativo", "nunca"]
    }
  ]
}
```

**Descrição**: Captura entrada do usuário e direciona o fluxo com base na resposta.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "decision")
- `muteNextId`: ID do próximo componente se o usuário não responder
- `nextId`: ID do próximo componente se nenhuma alternativa for reconhecida
- `timeout`: Tempo máximo de espera pela resposta (em segundos)
- `timeMute`: Tempo máximo de silêncio antes de considerar que o usuário não respondeu
- `timeSilenceBetweenSpeech`: Tempo de silêncio entre falas
- `timeDTMF`: Tempo de espera por entrada DTMF
- `alternatives`: Array de alternativas possíveis
  - `nextId`: ID do próximo componente se esta alternativa for reconhecida
  - `words`: Array de palavras-chave que correspondem a esta alternativa

**Exemplo Prático**:
```json
{
  "id": 80,
  "component": "decision",
  "muteNextId": 81,
  "nextId": 82,
  "timeout": 15,
  "timeMute": 5,
  "timeSilenceBetweenSpeech": 3,
  "timeDTMF": 10,
  "alternatives": [
    {
      "nextId": 83,
      "words": ["1", "um", "primeiro", "saldo"]
    },
    {
      "nextId": 84,
      "words": ["2", "dois", "segundo", "fatura"]
    },
    {
      "nextId": 85,
      "words": ["3", "três", "terceiro", "atendente"]
    }
  ]
}
```

### 9. Integration (Integração)

```json
{
  "id": 1,
  "component": "integration",
  "nextId": 2,
  "faultNextId": 3,
  "loadingNextId": 4,
  "method": "nomeMetodo",
  "timeout": 30,
  "parameters": {
    "param1": "valor1",
    "param2": "valor2"
  },
  "returns": {
    "campo1": "variavel1",
    "campo2": "variavel2"
  },
  "isAsync": 1
}
```

**Descrição**: Executa uma integração com um sistema externo.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "integration")
- `nextId`: ID do próximo componente após a integração bem-sucedida
- `faultNextId`: ID do próximo componente em caso de falha
- `loadingNextId`: ID do próximo componente durante o carregamento (para integrações assíncronas)
- `method`: Nome do método a ser chamado
- `timeout`: Tempo máximo de espera pela resposta
- `parameters`: Parâmetros a serem passados para o método
- `returns`: Mapeamento de campos de retorno para variáveis
- `isAsync`: Indica se a integração é assíncrona (1) ou síncrona (0)

**Exemplo Prático**:
```json
{
  "id": 90,
  "component": "integration",
  "nextId": 91,
  "faultNextId": 92,
  "loadingNextId": 93,
  "method": "getSaldoCliente",
  "timeout": 20,
  "parameters": {
    "cpf": "[cpf]",
    "tipo_conta": "[tipo_conta]"
  },
  "returns": {
    "saldo": "saldo",
    "limite": "limite",
    "status": "status_conta"
  },
  "isAsync": 0
}
```

### 10. Transfer (Transferência)

```json
{
  "id": 1,
  "component": "transfer",
  "nextId": 2,
  "ramal": "1234",
  "type": "blind"
}
```

**Descrição**: Realiza uma transferência de chamada para um ramal ou número externo.

**Parâmetros**:
- `id`: Identificador único do componente
- `component`: Tipo do componente (deve ser "transfer")
- `nextId`: ID do próximo componente após a transferência
- `ramal`: Número do ramal ou telefone para onde a chamada será transferida
- `type`: Tipo de transferência (opcional, padrão é "blind")
  - `blind`: Transferência direta sem consulta
  - `consultative`: Transferência com consulta ao destinatário

**Exemplo Prático**:
```json
{
  "id": 100,
  "component": "transfer",
  "nextId": 101,
  "ramal": "8000",
  "type": "consultative"
}
```

## Exemplo de Fluxo Completo

```json
[
  {
    "id": 1,
    "component": "start",
    "nextId": 2,
    "statusId": 1
  },
  {
    "id": 2,
    "component": "play",
    "nextId": 3,
    "text": "Bem-vindo ao sistema. Pressione 1 para continuar ou 2 para sair."
  },
  {
    "id": 3,
    "component": "decision",
    "muteNextId": 2,
    "nextId": 4,
    "timeout": 10,
    "timeMute": 3,
    "timeSilenceBetweenSpeech": 2,
    "timeDTMF": 5,
    "alternatives": [
      {
        "nextId": 5,
        "words": ["1", "um", "primeiro"]
      },
      {
        "nextId": 6,
        "words": ["2", "dois", "segundo"]
      }
    ]
  },
  {
    "id": 4,
    "component": "play",
    "nextId": 2,
    "text": "Opção não reconhecida. Tente novamente."
  },
  {
    "id": 5,
    "component": "conditional",
    "faultNextId": 7,
    "options": [
      {
        "nextId": 8,
        "equation": "usuario_tipo == 'premium'"
      }
    ]
  },
  {
    "id": 6,
    "component": "play",
    "nextId": 9,
    "text": "Obrigado por usar nosso sistema. Até logo!"
  },
  {
    "id": 7,
    "component": "play",
    "nextId": 9,
    "text": "Você não tem permissão para acessar este recurso."
  },
  {
    "id": 8,
    "component": "integration",
    "nextId": 9,
    "faultNextId": 7,
    "loadingNextId": 8,
    "method": "getDadosUsuario",
    "timeout": 30,
    "parameters": {
      "id": "[usuario_id]"
    },
    "returns": {
      "nome": "usuario_nome",
      "saldo": "usuario_saldo"
    },
    "isAsync": 1
  },
  {
    "id": 9,
    "component": "end"
  }
]
```

## Boas Práticas

### 1. Nomenclatura de IDs

- Use IDs sequenciais ou com prefixos para facilitar a manutenção
- Exemplo: `menu_1`, `menu_2`, `saldo_1`, `saldo_2`

### 2. Estrutura de Fluxo

- Organize o fluxo em seções lógicas (menu principal, submenus, etc.)
- Use comentários no código para documentar a função de cada seção
- Mantenha o fluxo o mais simples possível, evitando aninhamentos excessivos

### 3. Tratamento de Erros

- Sempre defina `faultNextId` para componentes que podem falhar
- Use componentes `play` para informar ao usuário sobre erros
- Implemente um fluxo de recuperação para situações de erro

### 4. Variáveis

- Use nomes descritivos para variáveis
- Inicialize variáveis no início do fluxo
- Documente o propósito de cada variável

### 5. Integrações

- Defina timeouts adequados para integrações
- Use integrações assíncronas para operações demoradas
- Implemente tratamento de falha para todas as integrações

### 6. Transferências

- Use transferências consultativas para melhor experiência do usuário
- Verifique a disponibilidade do ramal antes de transferir
- Informe ao usuário sobre a transferência antes de realizá-la

## Dicas de Depuração

### 1. Logs

- Use o componente `play` com mensagens de debug para rastrear o fluxo
- Verifique os logs do sistema para identificar erros

### 2. Testes

- Teste cada componente individualmente
- Verifique o fluxo completo em ambiente de desenvolvimento
- Use o modo de teste do sistema para simular diferentes cenários

### 3. Problemas Comuns

- **Condicionais não funcionando**: Verifique se as variáveis estão sendo definidas corretamente
- **Decisões não reconhecendo entradas**: Ajuste os parâmetros de timeout e as palavras-chave
- **Integrações falhando**: Verifique os parâmetros e o timeout
- **Transferências não funcionando**: Verifique se o ramal está correto e disponível

Este manual fornece a estrutura JSON para todos os componentes disponíveis no sistema Robot, permitindo a criação de fluxos complexos e interativos. 