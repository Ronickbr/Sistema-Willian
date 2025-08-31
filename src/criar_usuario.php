<?php
/**
 * Página de Criação de Usuários
 * Acesso restrito a administradores
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

// Redirecionar se não estiver logado ou não for administrador
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit();
}

if (!isAdmin()) {
    header('Location: /index.php?error=acesso_negado');
    exit();
}

// Variáveis para mensagens
$success_message = '';
$error_message = '';

// Buscar todos os usuários
$usuarios = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, nome, email, tipo, created_at FROM usuarios ORDER BY nome");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Erro ao buscar usuários: ' . $e->getMessage();
}

// Processar formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'criar';
    
    // Ação de deletar usuário
    if ($action === 'deletar_usuario') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                $conn = getDBConnection();
                
                // Verificar se o usuário existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                
                if ($stmt->fetch()) {
                    // Verificar se não é o próprio usuário logado
                    if ($user_id != $_SESSION['user_id']) {
                        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                        $result = $stmt->execute([$user_id]);
                        
                        if ($result) {
                            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
        }
        exit();
    }
    
    // Ação de editar usuário
    if ($action === 'editar_usuario') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo = $_POST['tipo'] ?? 'operacional';
        $nova_senha = $_POST['nova_senha'] ?? '';
        
        // Validações
        $errors = [];
        
        if (empty($nome)) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($email)) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        if (!in_array($tipo, ['operacional', 'administrador'])) {
            $errors[] = 'Tipo de usuário inválido';
        }
        
        if (!empty($nova_senha) && strlen($nova_senha) < 6) {
            $errors[] = 'Nova senha deve ter pelo menos 6 caracteres';
        }
        
        // Verificar se email já existe (exceto para o próprio usuário)
        if (empty($errors)) {
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'Email já está em uso por outro usuário';
                }
            } catch (Exception $e) {
                $errors[] = 'Erro ao verificar email: ' . $e->getMessage();
            }
        }
        
        // Atualizar usuário se não houver erros
        if (empty($errors)) {
            try {
                $conn = getDBConnection();
                
                if (!empty($nova_senha)) {
                    $password_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ?, password_hash = ? WHERE id = ?");
                    $result = $stmt->execute([$nome, $email, $tipo, $password_hash, $user_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ? WHERE id = ?");
                    $result = $stmt->execute([$nome, $email, $tipo, $user_id]);
                }
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
        }
        exit();
    }
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'operacional';
    
    // Validações
    $errors = [];
    
    if (empty($nome)) {
        $errors[] = 'Nome é obrigatório';
    }
    
    if (empty($email)) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($senha)) {
        $errors[] = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if ($senha !== $confirmar_senha) {
        $errors[] = 'Senhas não coincidem';
    }
    
    if (!in_array($tipo, ['operacional', 'administrador'])) {
        $errors[] = 'Tipo de usuário inválido';
    }
    
    // Verificar se email já existe
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Email já está em uso';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao verificar email: ' . $e->getMessage();
        }
    }
    
    // Inserir usuário se não houver erros
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $password_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, password_hash, tipo) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$nome, $email, $password_hash, $tipo]);
            
            if ($result) {
                // Se for uma requisição AJAX, retornar JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
                    exit();
                }
                $success_message = 'Usuário criado com sucesso!';
                // Limpar campos do formulário
                $nome = $email = $senha = $confirmar_senha = '';
                $tipo = 'operacional';
            } else {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário']);
                    exit();
                }
                $error_message = 'Erro ao criar usuário';
            }
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()]);
                exit();
            }
            $error_message = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
            exit();
        }
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Usuário - Sistema de Controle de Fretes</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/modern-theme.css" rel="stylesheet">
</head>
<body class="modern-body">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Conteúdo Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 modern-main">
                <div class="modern-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-4 pb-3 mb-4">
                    <div>
                        <h1 class="h2 text-gradient mb-1">Gerenciar Usuários</h1>
                        <p class="text-muted mb-0">Visualizar, criar, editar e excluir usuários do sistema</p>
                    </div>
                    <div class="user-info">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Bem-vindo!</div>
                                <small class="text-muted"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário'; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensagens -->
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Botão para Criar Novo Usuário -->
                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="modern-btn" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                        <i class="fas fa-user-plus"></i> Novo Usuário
                    </button>
                </div>
                
                <!-- Tabela de Usuários -->
                <div class="modern-card fade-in">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Usuários Cadastrados
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usuarios)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum usuário encontrado</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table modern-table">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Data de Criação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar-small me-2">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $usuario['tipo'] === 'administrador' ? 'bg-danger' : 'bg-primary'; ?>">
                                                        <?php echo ucfirst($usuario['tipo']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn-modern-sm btn-primary" title="Editar" 
                                                            onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn-modern-sm btn-danger" title="Excluir" 
                                                                onclick="deletarUsuario(<?php echo $usuario['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Modal para Criar/Editar Usuário -->
                <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalUsuarioLabel">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Novo Usuário
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formUsuario" novalidate>
                                    <input type="hidden" id="action" name="action" value="criar">
                                    <input type="hidden" id="user_id" name="user_id" value="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">Nome Completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Nome é obrigatório
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Email válido é obrigatório
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="senha" class="form-label">Senha *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="senha" name="senha" 
                                                       minlength="6" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleSenha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                Senha deve ter pelo menos 6 caracteres
                                            </div>
                                            <small class="form-text text-muted">Mínimo de 6 caracteres</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                                       minlength="6" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmarSenha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                Senhas devem coincidir
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tipo" class="form-label">Tipo de Usuário *</label>
                                            <select class="form-select" id="tipo" name="tipo" required>
                                                <option value="operacional" <?php echo (isset($tipo) && $tipo === 'operacional') ? 'selected' : ''; ?>>
                                                    Operacional
                                                </option>
                                                <option value="administrador" <?php echo (isset($tipo) && $tipo === 'administrador') ? 'selected' : ''; ?>>
                                                    Administrador
                                                </option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Selecione um tipo de usuário
                                            </div>
                                            <small class="form-text text-muted">
                                                Administradores têm acesso completo ao sistema
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3" id="novaSenhaGroup" style="display: none;">
                                            <label for="nova_senha" class="form-label">Nova Senha</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                                       minlength="6">
                                                <button class="btn btn-outline-secondary" type="button" id="toggleNovaSenha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Deixe em branco para manter a senha atual</small>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </button>
                                <button type="submit" form="formUsuario" class="btn btn-primary" id="btnSalvarUsuario">
                                    <i class="fas fa-user-plus me-2"></i>
                                    <span id="textoBtnSalvar">Criar Usuário</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Personalizado -->
    <script src="/assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação fade-in
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Toggle para mostrar/ocultar senha
            const toggleSenha = document.getElementById('toggleSenha');
            const senhaInput = document.getElementById('senha');
            
            if (toggleSenha && senhaInput) {
                toggleSenha.addEventListener('click', function() {
                    const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    senhaInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
            
            // Toggle para confirmar senha
            const toggleConfirmarSenha = document.getElementById('toggleConfirmarSenha');
            const confirmarSenhaInput = document.getElementById('confirmar_senha');
            
            if (toggleConfirmarSenha && confirmarSenhaInput) {
                toggleConfirmarSenha.addEventListener('click', function() {
                    const type = confirmarSenhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmarSenhaInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
            
            // Toggle para nova senha
            const toggleNovaSenha = document.getElementById('toggleNovaSenha');
            const novaSenhaInput = document.getElementById('nova_senha');
            
            if (toggleNovaSenha && novaSenhaInput) {
                toggleNovaSenha.addEventListener('click', function() {
                    const type = novaSenhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    novaSenhaInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
            
            // Validação do formulário
            const form = document.getElementById('formUsuario');
            
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                if (!form.checkValidity()) {
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
                
                const action = document.getElementById('action').value;
                
                // Verificar se senhas coincidem (apenas para criação)
                if (action === 'criar') {
                    const senha = document.getElementById('senha').value;
                    const confirmarSenha = document.getElementById('confirmar_senha').value;
                    
                    if (senha !== confirmarSenha) {
                        event.stopPropagation();
                        
                        const confirmarSenhaInput = document.getElementById('confirmar_senha');
                        confirmarSenhaInput.setCustomValidity('Senhas não coincidem');
                        confirmarSenhaInput.classList.add('is-invalid');
                        form.classList.add('was-validated');
                        return;
                    }
                }
                
                // Enviar formulário via AJAX
                const formData = new FormData(form);
                const btnSalvar = document.getElementById('btnSalvarUsuario');
                const textoOriginal = btnSalvar.innerHTML;
                
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
                
                fetch('criar_usuario.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar solicitação');
                })
                .finally(() => {
                    btnSalvar.disabled = false;
                    btnSalvar.innerHTML = textoOriginal;
                });
            });
            
            // Validação em tempo real para confirmação de senha
            if (confirmarSenhaInput && senhaInput) {
                function validatePasswordMatch() {
                    const senha = senhaInput.value;
                    const confirmarSenha = confirmarSenhaInput.value;
                    
                    if (confirmarSenha && senha !== confirmarSenha) {
                        confirmarSenhaInput.setCustomValidity('Senhas não coincidem');
                        confirmarSenhaInput.classList.add('is-invalid');
                    } else {
                        confirmarSenhaInput.setCustomValidity('');
                        confirmarSenhaInput.classList.remove('is-invalid');
                    }
                }
                
                senhaInput.addEventListener('input', validatePasswordMatch);
                confirmarSenhaInput.addEventListener('input', validatePasswordMatch);
            }
            
            // Reset do modal quando fechado
            const modalUsuario = document.getElementById('modalUsuario');
            modalUsuario.addEventListener('hidden.bs.modal', function() {
                resetarModal();
            });
        });
        
        // Função para resetar o modal
        function resetarModal() {
            const form = document.getElementById('formUsuario');
            form.reset();
            form.classList.remove('was-validated');
            
            document.getElementById('action').value = 'criar';
            document.getElementById('user_id').value = '';
            document.getElementById('modalUsuarioLabel').innerHTML = '<i class="fas fa-user-plus me-2"></i>Novo Usuário';
            document.getElementById('textoBtnSalvar').textContent = 'Criar Usuário';
            
            // Mostrar campos de senha para criação
            document.getElementById('senha').parentElement.parentElement.style.display = 'block';
            document.getElementById('confirmar_senha').parentElement.parentElement.style.display = 'block';
            document.getElementById('novaSenhaGroup').style.display = 'none';
            
            // Tornar senhas obrigatórias
            document.getElementById('senha').required = true;
            document.getElementById('confirmar_senha').required = true;
        }
        
        // Função para editar usuário
        function editarUsuario(userId) {
            fetch('get_usuario.php?id=' + userId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const usuario = data.usuario;
                    
                    // Preencher formulário
                    document.getElementById('action').value = 'editar_usuario';
                    document.getElementById('user_id').value = usuario.id;
                    document.getElementById('nome').value = usuario.nome;
                    document.getElementById('email').value = usuario.email;
                    document.getElementById('tipo').value = usuario.tipo;
                    
                    // Alterar título e botão
                    document.getElementById('modalUsuarioLabel').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Usuário';
                    document.getElementById('textoBtnSalvar').textContent = 'Salvar Alterações';
                    
                    // Ocultar campos de senha obrigatória e mostrar nova senha
                    document.getElementById('senha').parentElement.parentElement.style.display = 'none';
                    document.getElementById('confirmar_senha').parentElement.parentElement.style.display = 'none';
                    document.getElementById('novaSenhaGroup').style.display = 'block';
                    
                    // Remover obrigatoriedade das senhas
                    document.getElementById('senha').required = false;
                    document.getElementById('confirmar_senha').required = false;
                    
                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
                    modal.show();
                } else {
                    alert('Erro ao carregar dados do usuário: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados do usuário');
            });
        }
        
        // Função para deletar usuário
        function deletarUsuario(userId) {
            // Usar setTimeout para garantir que o confirm seja processado corretamente
            setTimeout(function() {
                const confirmacao = confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.');
                
                if (confirmacao === true) {
                    const formData = new FormData();
                    formData.append('action', 'deletar_usuario');
                    formData.append('user_id', userId);
                    
                    fetch('criar_usuario.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao excluir usuário');
                    });
                }
            }, 100);
        }
    </script>
</body>
</html>