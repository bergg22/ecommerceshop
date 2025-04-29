<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'nome_asc';

$query = "FROM produtos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          WHERE 1=1";

if (!empty($busca)) {
    $query .= " AND (p.nome LIKE :busca OR p.descricao LIKE :busca)";
}

if ($categoria > 0) {
    $query .= " AND p.categoria_id = :categoria";
}

$stmt_count = $pdo->prepare("SELECT COUNT(*) " . $query);

if (!empty($busca)) {
    $busca_param = "%{$busca}%";
    $stmt_count->bindParam(':busca', $busca_param, PDO::PARAM_STR);
}

if ($categoria > 0) {
    $stmt_count->bindParam(':categoria', $categoria, PDO::PARAM_INT);
}

$stmt_count->execute();
$total_produtos = $stmt_count->fetchColumn();
$total_paginas = ceil($total_produtos / $itens_por_pagina);

switch ($ordenacao) {
    case 'preco_asc':
        $order_by = "p.preco ASC";
        break;
    case 'preco_desc':
        $order_by = "p.preco DESC";
        break;
    case 'nome_desc':
        $order_by = "p.nome DESC";
        break;
    case 'estoque_asc':
        $order_by = "p.estoque ASC";
        break;
    case 'estoque_desc':
        $order_by = "p.estoque DESC";
        break;
    default:
        $order_by = "p.nome ASC";
}

$stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome " . $query . " ORDER BY {$order_by} LIMIT :offset, :limit");

if (!empty($busca)) {
    $busca_param = "%{$busca}%";
    $stmt->bindParam(':busca', $busca_param, PDO::PARAM_STR);
}

if ($categoria > 0) {
    $stmt->bindParam(':categoria', $categoria, PDO::PARAM_INT);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['excluir']) && !empty($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    
    try {

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM itens_pedido WHERE produto_id = ?");
        $stmt_check->execute([$id_excluir]);
        $tem_pedidos = $stmt_check->fetchColumn() > 0;
        
        if ($tem_pedidos) {
            $mensagem = [
                'tipo' => 'erro',
                'texto' => 'Não é possível excluir o produto pois ele está associado a pedidos.'
            ];
        } else {

            $stmt_delete = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt_delete->execute([$id_excluir]);
            
            $mensagem = [
                'tipo' => 'sucesso',
                'texto' => 'Produto excluído com sucesso.'
            ];
        }
    } catch (PDOException $e) {
        $mensagem = [
            'tipo' => 'erro',
            'texto' => 'Erro ao excluir produto: ' . $e->getMessage()
        ];
    }
    

    header("Location: listar.php?" . http_build_query([
        'busca' => $busca, 
        'categoria' => $categoria,
        'ordenacao' => $ordenacao,
        'pagina' => $pagina_atual,
        'msg' => $mensagem['tipo'],
        'texto' => $mensagem['texto']
    ]));
    exit;
}


$msg_tipo = isset($_GET['msg']) ? $_GET['msg'] : '';
$msg_texto = isset($_GET['texto']) ? $_GET['texto'] : '';


$resultado_inicio = $offset + 1;
$resultado_fim = min($offset + $itens_por_pagina, $total_produtos);

include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gerenciamento de Produtos</h1>
        <a href="cadastrar.php" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md flex items-center transition-colors duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Novo Produto
        </a>
    </div>
    
    <?php if (!empty($msg_tipo)): ?>
        <div class="<?php echo $msg_tipo === 'sucesso' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($msg_texto); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Buscar produtos</label>
                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome ou descrição..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            
            <div>
                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select id="categoria" name="categoria" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="0">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="ordenacao" class="block text-sm font-medium text-gray-700 mb-1">Ordenar por</label>
                <select id="ordenacao" name="ordenacao" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="nome_asc" <?php echo $ordenacao === 'nome_asc' ? 'selected' : ''; ?>>Nome (A-Z)</option>
                    <option value="nome_desc" <?php echo $ordenacao === 'nome_desc' ? 'selected' : ''; ?>>Nome (Z-A)</option>
                    <option value="preco_asc" <?php echo $ordenacao === 'preco_asc' ? 'selected' : ''; ?>>Preço (menor para maior)</option>
                    <option value="preco_desc" <?php echo $ordenacao === 'preco_desc' ? 'selected' : ''; ?>>Preço (maior para menor)</option>
                    <option value="estoque_asc" <?php echo $ordenacao === 'estoque_asc' ? 'selected' : ''; ?>>Estoque (menor para maior)</option>
                    <option value="estoque_desc" <?php echo $ordenacao === 'estoque_desc' ? 'selected' : ''; ?>>Estoque (maior para menor)</option>
                </select>
            </div>
            
            <div class="self-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300">
                    Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (count($produtos) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($produtos as $produto): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if (!empty($produto['imagem'])): ?>
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500">
                                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($produto['nome']); ?></div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars(substr($produto['descricao'], 0, 60)) . (strlen($produto['descricao']) > 60 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($produto['estoque']); ?> un</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($produto['estoque'] > 0): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Disponível
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Esgotado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="editar.php?id=<?php echo $produto['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                            </svg>
                                        </a>
                                        <a href="#" onclick="confirmarExclusao(<?php echo $produto['id']; ?>)" class="text-red-600 hover:text-red-900" title="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Mostrando <span class="font-medium"><?php echo $resultado_inicio; ?></span> a 
                    <span class="font-medium"><?php echo $resultado_fim; ?></span> de 
                    <span class="font-medium"><?php echo $total_produtos; ?></span> produtos
                </div>
                
                <?php if ($total_paginas > 1): ?>
                    <div class="flex items-center space-x-1">
                        <?php if ($pagina_atual > 1): ?>
                            <a href="?<?php echo http_build_query(['busca' => $busca, 'categoria' => $categoria, 'ordenacao' => $ordenacao, 'pagina' => $pagina_atual - 1]); ?>" 
                               class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $range = 2; 
                        $start_page = max(1, $pagina_atual - $range);
                        $end_page = min($total_paginas, $pagina_atual + $range);
                        
                        if ($start_page > 1) {
                            echo '<span class="px-3 py-1 text-sm text-gray-700">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <a href="?<?php echo http_build_query(['busca' => $busca, 'categoria' => $categoria, 'ordenacao' => $ordenacao, 'pagina' => $i]); ?>" 
                               class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium <?php echo $i === $pagina_atual ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; 
                        
                        if ($end_page < $total_paginas) {
                            echo '<span class="px-3 py-1 text-sm text-gray-700">...</span>';
                        }
                        ?>
                        
                        <?php if ($pagina_atual < $total_paginas): ?>
                            <a href="?<?php echo http_build_query(['busca' => $busca, 'categoria' => $categoria, 'ordenacao' => $ordenacao, 'pagina' => $pagina_atual + 1]); ?>" 
                               class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Próxima
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">Nenhum produto encontrado</h3>
                <p class="text-gray-500">Altere os filtros de busca ou adicione um novo produto.</p>
                <a href="cadastrar.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Adicionar Produto
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modal-exclusao" class="hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Confirmar exclusão</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Tem certeza que deseja excluir este produto? Esta ação não poderá ser desfeita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmar-exclusao" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Excluir
                </a>
                <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id) {
        document.getElementById('modal-exclusao').classList.remove('hidden');
        document.getElementById('confirmar-exclusao').href = 'listar.php?excluir=' + id + '&busca=<?php echo urlencode($busca); ?>&categoria=<?php echo $categoria; ?>&ordenacao=<?php echo $ordenacao; ?>&pagina=<?php echo $pagina_atual; ?>';
    }
    
    function fecharModal() {
        document.getElementById('modal-exclusao').classList.add('hidden');
    }
</script>

<?php include_once '../includes/footer.php'; ?>