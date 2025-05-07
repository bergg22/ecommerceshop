<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de E-commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dropdown-menu {
            display: none;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .nav-link.active {
            @apply text-blue-700 border-b-2 border-blue-700;
        }
        .page-transition {
            animation: fadeIn 0.4s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag mr-2">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <a href="<?php echo isset($_SESSION['usuario_id']) ? '/ecommerceshop/index.php' : '/ecommerceshop/login.php'; ?>" class="text-xl font-bold text-gray-800">Sistema de E-commerce</a>
                </div>
                
                <?php if (isset($_SESSION['usuario_id'])): ?>
                <nav class="hidden md:flex space-x-6">
                    <a href="/ecommerceshop/index.php" class="nav-link py-4 px-2 text-gray-700 hover:text-blue-700 font-medium transition-colors duration-200">Dashboard</a>
                    <a href="/ecommerceshop/produtos/listar.php" class="nav-link py-4 px-2 text-gray-700 hover:text-blue-700 font-medium transition-colors duration-200">Produtos</a>
                    <a href="/ecommerceshop/pedidos/listar.php" class="nav-link py-4 px-2 text-gray-700 hover:text-blue-700 font-medium transition-colors duration-200">Pedidos</a>
                    <div class="dropdown relative">
                        <button class="nav-link py-4 px-2 text-gray-700 hover:text-blue-700 font-medium transition-colors duration-200 flex items-center">
                            Relatórios
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                            <a href="/ecommerceshop/relatorios/mais_vendidos.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors duration-200">Produtos Mais Vendidos</a>
                            <a href="/ecommerceshop/exportacao/exportar.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors duration-200">Exportar Pedidos</a>
                        </div>
                    </div>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-700">
                        Olá, <span class="font-medium"><?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário'); ?></span>
                    </div>
                    <a href="/ecommerceshop/logout.php" class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors duration-300">
                        Sair
                    </a>
                </div>
                
                <button class="md:hidden flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="flex-grow page-transition">
