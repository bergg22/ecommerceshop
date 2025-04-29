</main>

<footer class="bg-gray-800 text-white py-6 mt-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <p>&copy; <?php echo date('Y'); ?> Sistema de E-commerce. Todos os direitos reservados.</p>
            </div>
            <div class="flex space-x-4">
                <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-help-circle">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (currentPath === href || currentPath.startsWith(href.replace('/listar.php', ''))) {
                link.classList.add('active');
            }
        });

        const menuButton = document.querySelector('.md\\:hidden');
        if (menuButton) {
            const mobileMenu = document.createElement('div');
            mobileMenu.classList.add('mobile-menu', 'fixed', 'inset-0', 'bg-white', 'z-50', 'flex', 'flex-col', 'p-4', 'hidden');
            mobileMenu.innerHTML = `
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h2 class="text-xl font-bold">Menu</h2>
                    <button class="close-menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <a href="/ecommerce/index.php" class="py-3 px-4 border-b">Dashboard</a>
                <a href="/ecommerce/produtos/listar.php" class="py-3 px-4 border-b">Produtos</a>
                <a href="/ecommerce/pedidos/listar.php" class="py-3 px-4 border-b">Pedidos</a>
                <a href="/ecommerce/relatorios/mais_vendidos.php" class="py-3 px-4 border-b">Produtos Mais Vendidos</a>
                <a href="/ecommerce/exportacao/exportar.php" class="py-3 px-4 border-b">Exportar Pedidos</a>
                <a href="/ecommerce/logout.php" class="mt-auto py-3 px-4 bg-red-500 text-white text-center rounded-md">Sair</a>
            `;
            
            document.body.appendChild(mobileMenu);
            
            menuButton.addEventListener('click', function() {
                mobileMenu.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
            
            mobileMenu.querySelector('.close-menu').addEventListener('click', function() {
                mobileMenu.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
</script>
</body>
</html>
