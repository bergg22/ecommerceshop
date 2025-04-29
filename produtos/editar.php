<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php?msg=erro&texto=ID do produto não informado');
    exit;
}

$id = (int)$_GET['id'];

$stmt_categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

$erros = [];
$produto = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        header('Location: listar.php?msg=erro&texto=Produto não encontrado');
        exit;
    }
} catch (PDOException $e) {
    header('Location: listar.php?msg=erro&texto=Erro ao buscar produto: ' . $e->getMessage());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regras = [
        'nome' => ['nome' => 'Nome', 'obrigatorio' => true, 'min' => 3, 'max' => 100],
        'descricao' => ['nome' => 'Descrição', 'obrigatorio' => false],
        'preco' => ['nome' => 'Preço', 'obrigatorio' => true, 'tipo' => 'numero'],
        'estoque' => ['nome' => 'Estoque', 'obrigatorio' => true, 'tipo' => 'numero'],
        'categoria_id' => ['nome' => 'Categoria', 'obrigatorio' => false]
    ];
    
    $produto = [
        'id' => $id,
        'nome' => $_POST['nome'] ?? '',
        'descricao' => $_POST['descricao'] ?? '',
        'preco' => $_POST['preco'] ?? '',
        'estoque' => $_POST['estoque'] ?? '',
        'categoria_id' => $_POST['categoria_id'] ?? '',
        'destaque' => isset($_POST['destaque']),
        'ativo' => isset($_POST['ativo'])
    ];
    
    if (!empty($produto['preco'])) {
        $produto['preco'] = str_replace('.', '', $produto['preco']);
        $produto['preco'] = str_replace(',', '.', $produto['preco']);
    }
    
    $erros = validarDados($produto, $regras);
    
    if (empty($erros)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE produtos 
                SET nome = ?, descricao = ?, preco = ?, estoque = ?, 
                    categoria_id = ?, destaque = ?, ativo = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $produto['nome'],
                $produto['descricao'],
                $produto['preco'],
                $produto['estoque'],
                !empty($produto['categoria_id']) ? $produto['categoria_id'] : null,
                $produto['destaque'] ? 1 : 0,
                $produto['ativo'] ? 1 : 0,
                $id
            ]);
            
            header('Location: listar.php?msg=sucesso&texto=Produto atualizado com sucesso');
            exit;
        } catch (PDOException $e) {
            $erros['geral'] = 'Erro ao atualizar produto: ' . $e->getMessage();
        }
    }
} else {
    if (!empty($produto['preco']) && is_numeric($produto['preco'])) {
        $produto['preco_formatado'] = number_format($produto['preco'], 2, ',', '.');
    } else {
        $produto['preco_formatado'] = $produto['preco'];
    }
}

include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Produto</h1>
        <a href="listar.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md flex items-center transition-colors duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Voltar
        </a>
    </div>
    
    <?php if (isset($erros['geral'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $erros['geral']; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" 
                           class="w-full px-4 py-2 border <?php echo isset($erros['nome']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                           required>
                    <?php if (isset($erros['nome'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['nome']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="w-full px-4 py-2 border <?php echo isset($erros['categoria_id']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo $produto['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['categoria_id'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['categoria_id']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="preco" class="block text-sm font-medium text-gray-700 mb-1">Preço (R$) *</label>
                    <input type="text" id="preco" name="preco" value="<?php echo htmlspecialchars($produto['preco_formatado'] ?? number_format($produto['preco'], 2, ',', '.')); ?>" 
                           class="w-full px-4 py-2 border <?php echo isset($erros['preco']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                           placeholder="0,00" required>
                    <?php if (isset($erros['preco'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['preco']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="estoque" class="block text-sm font-medium text-gray-700 mb-1">Estoque *</label>
                    <input type="number" id="estoque" name="estoque" value="<?php echo htmlspecialchars($produto['estoque']); ?>" 
                           class="w-full px-4 py-2 border <?php echo isset($erros['estoque']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                           min="0" required>
                    <?php if (isset($erros['estoque'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['estoque']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="md:col-span-2">
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4" 
                              class="w-full px-4 py-2 border <?php echo isset($erros['descricao']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    <?php if (isset($erros['descricao'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $erros['descricao']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="destaque" name="destaque" 
                               <?php echo $produto['destaque'] ? 'checked' : ''; ?> 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="destaque" class="ml-2 block text-sm text-gray-700">Produto em destaque</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="ativo" name="ativo" 
                               <?php echo $produto['ativo'] ? 'checked' : ''; ?> 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="ativo" class="ml-2 block text-sm text-gray-700">Produto ativo</label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end pt-6 border-t border-gray-200">
                <a href="listar.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md mr-2 transition-colors duration-300">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const precoInput = document.getElementById('preco');
        
        precoInput.addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/\D/g, '');
            if (value.length > 0) {
                value = (parseFloat(value) / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
            e.target.value = value;
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>
