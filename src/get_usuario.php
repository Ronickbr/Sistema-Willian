<?php
/**
 * API para buscar dados de um usuário específico
 * Usado para preencher o modal de edição
 */

// Iniciar sessão
session_start();

// Incluir configurações
require_once 'config/database.php';

// Verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Verificar se o usuário é administrador
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrador';
}

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido']);
    exit();
}

$user_id = intval($_GET['id']);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário inválido']);
    exit();
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, nome, email, tipo FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo json_encode([
            'success' => true,
            'usuario' => $usuario
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar usuário: ' . $e->getMessage()]);
}
?>