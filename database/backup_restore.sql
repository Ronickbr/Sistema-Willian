-- Script para Backup e Restore do Sistema de Fretes
-- Use este script para fazer backup e restaurar dados do sistema

-- =====================================================
-- BACKUP DOS DADOS (Execute estas consultas para gerar backup)
-- =====================================================

-- Backup da tabela usuarios (sem senhas por segurança)
SELECT 'Backup usuarios:' as info;
SELECT CONCAT(
    'INSERT INTO usuarios (email, nome, tipo, created_at) VALUES (',
    QUOTE(email), ', ',
    QUOTE(nome), ', ',
    QUOTE(tipo), ', ',
    QUOTE(created_at), ');'
) as backup_usuarios
FROM usuarios;

-- Backup da tabela transportadoras
SELECT 'Backup transportadoras:' as info;
SELECT CONCAT(
    'INSERT INTO transportadoras (nome, cnpj, telefone, email, endereco, peso_ate_50kg, peso_ate_100kg, peso_ate_150kg, peso_ate_200kg, peso_ate_300kg, frete_por_tonelada, frete_minimo, pedagio, frete_valor_percentual, fator_peso_cubico, ativo, created_at) VALUES (',
    QUOTE(nome), ', ',
    QUOTE(cnpj), ', ',
    QUOTE(telefone), ', ',
    QUOTE(email), ', ',
    QUOTE(endereco), ', ',
    peso_ate_50kg, ', ',
    peso_ate_100kg, ', ',
    peso_ate_150kg, ', ',
    peso_ate_200kg, ', ',
    peso_ate_300kg, ', ',
    frete_por_tonelada, ', ',
    frete_minimo, ', ',
    pedagio, ', ',
    frete_valor_percentual, ', ',
    fator_peso_cubico, ', ',
    ativo, ', ',
    QUOTE(created_at), ');'
) as backup_transportadoras
FROM transportadoras;

-- Backup da tabela pedidos
SELECT 'Backup pedidos:' as info;
SELECT CONCAT(
    'INSERT INTO pedidos (numero_pedido, numero_picking, cliente_nome, origem, destino, peso, valor_mercadoria, observacoes, status, data_pedido, usuario_id, created_at) VALUES (',
    QUOTE(numero_pedido), ', ',
    QUOTE(numero_picking), ', ',
    QUOTE(cliente_nome), ', ',
    QUOTE(origem), ', ',
    QUOTE(destino), ', ',
    peso, ', ',
    valor_mercadoria, ', ',
    QUOTE(observacoes), ', ',
    QUOTE(status), ', ',
    QUOTE(data_pedido), ', ',
    usuario_id, ', ',
    QUOTE(created_at), ');'
) as backup_pedidos
FROM pedidos;

-- =====================================================
-- LIMPEZA PARA RESTORE (Execute antes de restaurar)
-- =====================================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- Limpar todas as tabelas (CUIDADO: Isso apaga todos os dados!)
-- DELETE FROM faturas;
-- DELETE FROM cotacoes;
-- DELETE FROM medidas;
-- DELETE FROM pedidos;
-- DELETE FROM transportadoras;
-- DELETE FROM usuarios;

-- Resetar AUTO_INCREMENT
-- ALTER TABLE usuarios AUTO_INCREMENT = 1;
-- ALTER TABLE transportadoras AUTO_INCREMENT = 1;
-- ALTER TABLE pedidos AUTO_INCREMENT = 1;
-- ALTER TABLE medidas AUTO_INCREMENT = 1;
-- ALTER TABLE cotacoes AUTO_INCREMENT = 1;
-- ALTER TABLE faturas AUTO_INCREMENT = 1;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- ESTATÍSTICAS DO BANCO
-- =====================================================

SELECT 'Estatísticas do Sistema:' as info;

SELECT 
    'Usuários' as tabela,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN tipo = 'administrador' THEN 1 END) as administradores,
    COUNT(CASE WHEN tipo = 'operacional' THEN 1 END) as operacionais
FROM usuarios

UNION ALL

SELECT 
    'Transportadoras' as tabela,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN ativo = 1 THEN 1 END) as ativas,
    COUNT(CASE WHEN ativo = 0 THEN 1 END) as inativas
FROM transportadoras

UNION ALL

SELECT 
    'Pedidos' as tabela,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
    COUNT(CASE WHEN status = 'entregue' THEN 1 END) as entregues
FROM pedidos

UNION ALL

SELECT 
    'Cotações' as tabela,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
    COUNT(CASE WHEN status = 'aprovada' THEN 1 END) as aprovadas
FROM cotacoes

UNION ALL

SELECT 
    'Medidas' as tabela,
    COUNT(*) as total_registros,
    ROUND(SUM(cubagem_m3), 4) as cubagem_total,
    SUM(quantidade_volumes) as volumes_total
FROM medidas;

-- =====================================================
-- VERIFICAÇÃO DE INTEGRIDADE
-- =====================================================

SELECT 'Verificação de Integridade:' as info;

-- Verificar pedidos sem usuário
SELECT 'Pedidos órfãos (sem usuário):' as verificacao, COUNT(*) as problemas
FROM pedidos p
LEFT JOIN usuarios u ON p.usuario_id = u.id
WHERE p.usuario_id IS NOT NULL AND u.id IS NULL;

-- Verificar cotações sem pedido
SELECT 'Cotações órfãs (sem pedido):' as verificacao, COUNT(*) as problemas
FROM cotacoes c
LEFT JOIN pedidos p ON c.pedido_id = p.id
WHERE c.pedido_id IS NOT NULL AND p.id IS NULL;

-- Verificar cotações sem transportadora
SELECT 'Cotações sem transportadora:' as verificacao, COUNT(*) as problemas
FROM cotacoes c
LEFT JOIN transportadoras t ON c.transportadora_id = t.id
WHERE c.transportadora_id IS NOT NULL AND t.id IS NULL;

-- Verificar medidas sem pedido
SELECT 'Medidas órfãs (sem pedido):' as verificacao, COUNT(*) as problemas
FROM medidas m
LEFT JOIN pedidos p ON m.pedido_id = p.id
WHERE m.pedido_id IS NOT NULL AND p.id IS NULL;