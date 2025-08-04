# Robô de Cobrança - Apresentação

## Visão Geral

O Robô de Cobrança é uma solução automatizada para contato com clientes inadimplentes, projetada para:
- Confirmar a identidade do cliente
- Informar sobre dívidas pendentes
- Oferecer opções de pagamento
- Gerenciar o processo de forma cordial e profissional

## Funcionalidades Principais

### 1. Verificação de Identidade
- Confirma se está falando com o cliente correto
- Repete a pergunta uma vez se não entender a resposta
- Oferece opção de falar com outra pessoa se necessário

### 2. Informação sobre Dívida
- Informa o valor da dívida pendente
- Pergunta se o cliente pode realizar o pagamento
- Repete a pergunta uma vez se não entender a resposta

### 3. Opções de Pagamento
- Oferece opções de parcelamento se o cliente não puder pagar à vista
- Fornece informações de contato para mais detalhes
- Informa sobre a suspensão do serviço em caso de não pagamento

### 4. Tratamento de Respostas
- Utiliza o componente `repeater` para limitar repetições a apenas uma vez
- Direciona para atendimento humano quando necessário
- Mantém um fluxo de conversação natural e cordial

## Fluxo de Conversação

```
Início
  ↓
Obter Dados do Cliente
  ↓
Confirmar Identidade
  ↓
Informar sobre Dívida
  ↓
Perguntar sobre Pagamento
  ↓
Se Sim → Agradecer e Finalizar
  ↓
Se Não → Oferecer Parcelamento
  ↓
Se Sim → Informar Opções e Finalizar
  ↓
Se Não → Informar Suspensão e Finalizar
```

## Componentes Utilizados

### 1. Start
- Inicia o fluxo do robô
- Define o status inicial da conversa

### 2. Integration
- Obtém dados do cliente (nome e valor da dívida)
- Utiliza o método `getDadosCliente` para buscar informações

### 3. Play
- Reproduz mensagens para o cliente
- Utiliza estilo "natural" para uma conversa mais fluida

### 4. Decision
- Processa respostas do cliente
- Direciona o fluxo com base nas palavras-chave reconhecidas

### 5. Repeater
- Limita repetições de perguntas a apenas uma vez
- Melhora a eficiência do fluxo de conversação

### 6. End
- Finaliza a conversa de forma cordial
- Fornece informações de contato quando necessário

## Benefícios

### Para a Empresa
- **Redução de Custos**: Automatiza o processo de cobrança
- **Escalabilidade**: Atende múltiplos clientes simultaneamente
- **Consistência**: Mantém o mesmo padrão de atendimento
- **Eficiência**: Prioriza casos que precisam de intervenção humana

### Para o Cliente
- **Conveniência**: Atendimento disponível 24/7
- **Clareza**: Informações precisas sobre a dívida
- **Opções**: Alternativas de pagamento apresentadas de forma cordial
- **Transparência**: Informações sobre consequências da não regularização

## Exemplo de Interação

**Robô**: "Bom dia! Estou falando com João Silva?"

**Cliente**: "Sim, sou eu."

**Robô**: "Olá João Silva, tudo bem? Gostaria de informar que você possui uma dívida pendente no valor de R$ 1.250,00 reais. Você poderia realizar o pagamento hoje?"

**Cliente**: "Não posso pagar agora."

**Robô**: "Entendo sua situação, mas preciso informar que, caso o pagamento não seja realizado, seu serviço será suspenso em 48 horas. Gostaria de discutir opções de parcelamento?"

**Cliente**: "Sim, me fale mais."

**Robô**: "Ótimo! Podemos parcelar sua dívida em até 6 vezes. Para mais informações, entre em contato com nosso atendimento através do número 0800-123-4567. Obrigado pela atenção e tenha um ótimo dia!"

## Conclusão

O Robô de Cobrança representa uma solução moderna e eficiente para o processo de cobrança, combinando tecnologia e cordialidade para alcançar resultados positivos tanto para a empresa quanto para o cliente. Sua capacidade de adaptação a diferentes respostas e o tratamento profissional das situações o tornam uma ferramenta valiosa para a gestão de inadimplência. 