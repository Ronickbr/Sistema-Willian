# Sistema de Controle de Fretes - Banco de Dados

## Estrutura do Banco de Dados

Este diretório contém os scripts de inicialização e configuração do banco de dados MySQL para o Sistema de Controle de Fretes.

### Arquivos

- **init.sql**: Script principal de inicialização do banco de dados com todas as tabelas e dados iniciais

### Tabelas do Sistema

#### 1. usuarios
Tabela para gerenciamento de usuários do sistema.

**Campos principais:**
- `id`: Chave primária
- `email`: Email único do usuário
- `password_hash`: Hash da senha
- `nome`: Nome completo
- `tipo`: Tipo de usuário (operacional, administrador)
- `created_at`: Data de criação

#### 2. transportadoras
Tabela para cadastro de transportadoras e suas tabelas de preços.

**Campos principais:**
- `id`: Chave primária
- `nome`: Nome da transportadora
- `cnpj`, `telefone`, `email`, `endereco`: Dados de contato
- `peso_ate_50kg`, `peso_ate_100kg`, `peso_ate_150kg`, `peso_ate_200kg`, `peso_ate_300kg`: Preços por faixa de peso
- `frete_por_tonelada`: Preço por tonelada
- `frete_minimo`: Valor mínimo de frete
- `pedagio`: Valor do pedágio
- `frete_valor_percentual`: Percentual sobre valor da mercadoria
- `fator_peso_cubico`: Fator para cálculo de peso cúbico
- `ativo`: Status da transportadora

#### 3. pedidos
Tabela para controle de pedidos de frete.

**Campos principais:**
- `id`: Chave primária
- `numero_pedido`: Número único do pedido
- `numero_picking`: Número do picking
- `cliente_nome`: Nome do cliente
- `origem`, `destino`: Origem e destino do frete
- `peso`: Peso total da carga
- `valor_mercadoria`: Valor da mercadoria
- `observacoes`: Observações do pedido
- `status`: Status do pedido (pendente, em_transito, entregue, cancelado)
- `cotacao_id`: Referência para cotação aprovada
- `usuario_id`: Usuário responsável

#### 4. medidas
Tabela para armazenar as dimensões dos volumes dos pedidos.

**Campos principais:**
- `id`: Chave primária
- `pedido_id`: Referência ao pedido
- `comprimento`, `altura`, `largura`: Dimensões em centímetros
- `quantidade_volumes`: Quantidade de volumes
- `cubagem_m3`: Cubagem calculada automaticamente

#### 5. cotacoes
Tabela para armazenar cotações de frete.

**Campos principais:**
- `id`: Chave primária
- `pedido_id`: Referência ao pedido
- `transportadora_id`: Referência à transportadora
- `numero_nota_fiscal`: Número da nota fiscal
- `valor_nota_fiscal`: Valor da nota fiscal
- `peso_nota_fiscal`: Peso da nota fiscal
- `valor_frete`: Valor do frete cotado
- `valor_frete_calculado`: Valor calculado pelo sistema
- `cubagem_total`: Cubagem total da carga
- `prazo_entrega`: Prazo de entrega em dias
- `observacoes`: Observações da cotação
- `status`: Status da cotação (pendente, aprovada, rejeitada)
- `data_cotacao`: Data da cotação

#### 6. faturas
Tabela para controle de faturas e conferência de valores.

**Campos principais:**
- `id`: Chave primária
- `cotacao_id`: Referência à cotação
- `numero_nota_fiscal`: Número da nota fiscal
- `valor_frete_faturado`: Valor efetivamente faturado
- `valor_frete_cotado`: Valor originalmente cotado
- `diferenca`: Diferença calculada automaticamente
- `status`: Status da conferência
- `arquivo_original`: Nome do arquivo importado

### Índices

O banco possui índices otimizados para:
- Consultas por email de usuários
- Busca por números de pedido e picking
- Filtros por status e datas
- Relacionamentos entre tabelas

### Dados Iniciais

O script inclui:
- Usuário administrador padrão (admin@sistema.com)
- 3 transportadoras de exemplo
- 2 pedidos de exemplo com medidas
- 3 cotações de exemplo

### Como Usar

1. Execute o script `init.sql` em um banco MySQL
2. O banco `sistema_fretes` será criado com todas as tabelas
3. Os dados iniciais serão inseridos automaticamente

### Observações

- Todas as tabelas utilizam `AUTO_INCREMENT` para chaves primárias
- Campos monetários utilizam `DECIMAL(12,2)` para precisão
- Campos de peso e dimensões utilizam `DECIMAL(10,2)`
- Timestamps são automaticamente gerenciados pelo MySQL
- Foreign keys garantem integridade referencial