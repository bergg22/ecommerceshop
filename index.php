<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';

// Redireciona para login se não estiver autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6 transform transition-transform duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-14 w-14 rounded-full bg-blue-100 text-blue-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package">
                    <path d="M16.5 9.4 7.55 4.24"></path><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.29 7 12 12 20.71 7"></polyline><line x1="12" y1="22" x2="12" y2="12"></line>
                </svg>
            </div>
            <h2 class="text-xl font-semibold mb-2">Produtos</h2>
            <p class="text-gray-600 mb-4">Gerenciar catálogo de produtos</p>
            <a href="produtos/listar.php" class="block text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors duration-300">
                Acessar
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 transform transition-transform duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-14 w-14 rounded-full bg-green-100 text-green-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart">
                    <circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                </svg>
            </div>
            <h2 class="text-xl font-semibold mb-2">Pedidos</h2>
            <p class="text-gray-600 mb-4">Gerenciar pedidos de clientes</p>
            <a href="pedidos/listar.php" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition-colors duration-300">
                Acessar
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 transform transition-transform duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-14 w-14 rounded-full bg-purple-100 text-purple-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart">
                    <line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line>
                </svg>
            </div>
            <h2 class="text-xl font-semibold mb-2">Relatórios</h2>
            <p class="text-gray-600 mb-4">Visualizar relatórios de vendas</p>
            <a href="relatorios/mais_vendidos.php" class="block text-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded transition-colors duration-300">
                Acessar
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 transform transition-transform duration-300 hover:scale-105">
            <div class="flex items-center justify-center h-14 w-14 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
            </div>
            <h2 class="text-xl font-semibold mb-2">Exportação</h2>
            <p class="text-gray-600 mb-4">Exportar pedidos para TXT</p>
            <a href="exportacao/exportar.php" class="block text-center bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded transition-colors duration-300">
                Acessar
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>