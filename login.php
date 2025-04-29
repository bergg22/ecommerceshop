<?php
session_start();
include_once 'config/database.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            header('Location: index.php');
            exit;
        } else {
            $erro = 'Email ou senha inválidos';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de E-commerce</title>
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
            <p class="text-blue-100">Painel Administrativo</p>
        </div>
        
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Login</h2>
            
            <?php if ($erro): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($erro); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                           class="form-input w-full px-4 py-2 border border-gray-300 rounded-md" 
                           required>
                </div>
                
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" id="senha" name="senha" 
                           class="form-input w-full px-4 py-2 border border-gray-300 rounded-md" 
                           required>
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300">
                        Entrar
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Não tem uma conta? 
                    <a href="cadastro.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Cadastre-se
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('label').classList.add('text-blue-600');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('label').classList.remove('text-blue-600');

                    if (this.value.trim() === '') {
                        this.classList.add('border-red-500');
                    } else {
                        this.classList.remove('border-red-500');
                    }
                });
            });
        });
    </script>
</body>
</html>