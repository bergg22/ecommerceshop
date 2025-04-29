<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';
$mensagem_tipo = '';
$conteudo = '';
$nome_arquivo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
    
    try {
        $stmt = $pdo->prepare("CALL exportar_pedidos_diarios(?)");
        $stmt->execute([$data]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nome_arquivo = $resultado['nome_arquivo'];
            $conteudo = $resultado['conteudo'];
            $mensagem = "Arquivo gerado com sucesso: $nome_arquivo";
            $mensagem_tipo = 'sucesso';
        } else {
            $mensagem = "Nenhum dado foi encontrado para a data selecionada.";
            $mensagem_tipo = 'alerta';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao gerar arquivo: " . $e->getMessage();
        $mensagem_tipo = 'erro';
    }
}

include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Exportação de Pedidos</h1>
        <a href="../index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md flex items-center transition-colors duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Voltar
        </a>
    </div>
    
    <?php if ($mensagem): ?>
        <div class="<?php 
            if ($mensagem_tipo === 'sucesso') echo 'bg-green-100 border-green-500 text-green-700';
            elseif ($mensagem_tipo === 'erro') echo 'bg-red-100 border-red-500 text-red-700';
            else echo 'bg-yellow-100 border-yellow-500 text-yellow-700';
        ?> border-l-4 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($mensagem); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Exportar Pedidos para TXT</h2>
                <p class="text-gray-600 mb-4">Selecione uma data para exportar os pedidos realizados nesta data.</p>
                
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="data" class="block text-sm font-medium text-gray-700 mb-1">Data dos Pedidos</label>
                        <input type="date" id="data" name="data" value="<?php echo date('Y-m-d'); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">Selecione a data desejada.</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Gerar Arquivo TXT
                    </button>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Instruções</h2>
                <div class="text-gray-600 space-y-3">
                    <p>O arquivo TXT gerado contém:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>ID do pedido</li>
                        <li>Data e hora do pedido</li>
                        <li>Status do pedido</li>
                        <li>Valor total</li>
                        <li>Forma de pagamento</li>
                        <li>Dados do cliente</li>
                        <li>Quantidade de itens</li>
                        <li>Total de produtos</li>
                    </ul>
                    <p class="mt-2">Dados separados por ponto e vírgula (;).</p>
                </div>
            </div>
        </div>
        
        <div class="md:col-span-2">
            <?php if ($conteudo): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full flex flex-col">
                    <div class="bg-gray-100 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">
                            Pré-visualização: <?php echo htmlspecialchars($nome_arquivo); ?>
                        </h3>
                        <div class="flex space-x-2">
                            <button type="button" onclick="copiarConteudo()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1 px-3 rounded-md transition-colors duration-300 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copiar
                            </button>
                            <button type="button" onclick="baixarArquivo('<?php echo htmlspecialchars($nome_arquivo); ?>')" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-1 px-3 rounded-md transition-colors duration-300 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Baixar
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-auto p-4">
                        <pre id="conteudo-arquivo" class="text-sm text-gray-700 whitespace-pre overflow-x-auto"><?php echo htmlspecialchars($conteudo); ?></pre>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6 h-full flex flex-col items-center justify-center text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mb-4">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Nenhum arquivo gerado</h3>
                    <p class="text-gray-500 mb-4">Selecione uma data e clique em "Gerar Arquivo TXT".</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copiarConteudo() {
    const conteudo = document.getElementById('conteudo-arquivo').textContent;
    navigator.clipboard.writeText(conteudo).then(function() {
        alert('Conteúdo copiado!');
    }, function(err) {
        alert('Erro ao copiar: ' + err);
    });
}

function baixarArquivo(nomeArquivo) {
    const conteudo = document.getElementById('conteudo-arquivo').textContent;
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(conteudo));
    element.setAttribute('download', nomeArquivo);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}
</script>

<?php include_once '../includes/footer.php'; ?>
