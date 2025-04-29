<?php
session_start();
include_once 'config/database.php';

// Se já estiver logado, redireciona para index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';
$usuario = [
    'nome' => '',
    'email' => ''
];

// Processa o formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação dos dados
    $regras = [
        'nome' => ['nome' => 'Nome', 'obrigatorio' => true, 'min' => 3, 'max' => 100],
        'email' => ['nome' => 'Email', 'obrigatorio' => true, 'tipo' => 'email'],
        'senha' => ['nome' => 'Senha', 'obrigatorio' => true, 'min' => 6],
        'confirmar_senha' => ['nome' => 'Confirmar Senha', 'obrigatorio' => true]
    ];
    
    $usuario = [
        'nome' => $_POST['nome'] ?? '',
        'email' => $_POST['email'] ?? '',
        'senha' => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? ''
    ];
    
    $erros = validarDados($usuario, $regras);
    
    // Validação adicional para senha
    if ($usuario['senha'] !== $usuario['confirmar_senha']) {
        $erros['confirmar_senha'] = 'As senhas não conferem';
    }
    
    // Verificar se email já existe
    if (empty($erros['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$usuario['email']]);
        if ($stmt->fetch()) {
            $erros['email'] = 'Este email já está cadastrado';
        }
    }
    
    if (empty($erros)) {
        try {
            // Hash da senha
            $senha_hash = password_hash($usuario['senha'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario['nome'],
                $usuario['email'],
                $senha_hash
            ]);
            
            $sucesso = 'Cadastro realizado com sucesso! Você já pode fazer login.';
            
            // Limpa os dados do formulário
            $usuario = ['nome' => '', 'email' => ''];
            
        } catch (PDOException $e) {
            $erro = 'Erro ao realizar cadastro: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de E-commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .form-input:focus {
            @apply ring-2 ring-blue-500 outline-none;
        }
        .login-animation {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="login-animation bg-white rounded-lg shadow-xl overflow-hidden max-w-md w-full mx-4">
        <div class="bg-blue-600 py-4 px-6">
            <h1 class="text-white text-2xl font-bold">Sistema de E-commerce</h1>
            <p class="text-blue-100">Cadastro de Usuário</p>
        </div>
        
        <div class="p-6">
            <?php if ($erro): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($erro); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($sucesso); ?></p>
                    <a href="login.php" class="inline-block mt-2 text-green-600 hover:text-green-800 font-medium">
                        Fazer login →
                    </a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" 
                           class="form-input w-full px-4 py-2 border <?php echo isset($erros['nome']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" 
                           required>
                    <?php if (isset($erros['nome'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['nome']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                           class="form-input w-full px-4 py-2 border <?php echo isset($erros['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" 
                           required>
                    <?php if (isset($erros['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['email']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" id="senha" name="senha" 
                           class="form-input w-full px-4 py-2 border <?php echo isset($erros['senha']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" 
                           required>
                    <?php if (isset($erros['senha'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['senha']; ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-sm text-gray-500">Mínimo de 6 caracteres</p>
                </div>
                
                <div>
                    <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" 
                           class="form-input w-full px-4 py-2 border <?php echo isset($erros['confirmar_senha']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" 
                           required>
                    <?php if (isset($erros['confirmar_senha'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['confirmar_senha']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300">
                        Cadastrar
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Já tem uma conta? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Faça login
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                // Efeito de foco
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('label').classList.add('text-blue-600');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('label').classList.remove('text-blue-600');
                    
                    // Validação básica
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-500');
                    } else {
                        this.classList.remove('border-red-500');
                    }
                });
            });
            
            // Validação de senha
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            
            confirmarSenha.addEventListener('input', function() {
                if (this.value !== senha.value) {
                    this.classList.add('border-red-500');
                } else {
                    this.classList.remove('border-red-500');
                }
            });
        });
    </script>
</body>
</html>