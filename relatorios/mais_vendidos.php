<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '30d';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;

$data_inicio = null;
switch ($periodo) {
    case '7d':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30d':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90d':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        break;
    case '365d':
        $data_inicio = date('Y-m-d', strtotime('-365 days'));
        break;
    case 'custom':
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
        break;
    default:
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
}

$query = "
    SELECT 
        p.id,
        p.nome,
        p.preco,
        c.nome AS categoria_nome,
        SUM(ip.quantidade) AS total_vendido,
        COUNT(DISTINCT ped.id) AS num_pedidos,
        SUM(ip.subtotal) AS valor_total
    FROM 
        produtos p
    JOIN 
        itens_pedido ip ON p.id = ip.produto_id
    JOIN 
        pedidos ped ON ip.pedido_id = ped.id
    LEFT JOIN 
        categorias c ON p.categoria_id = c.id
    WHERE 
        ped.status != 'cancelado'
";

if ($periodo === 'custom') {
    $query .= " AND DATE(ped.data_pedido) BETWEEN :data_inicio AND :data_fim";
} else {
    $query .= " AND DATE(ped.data_pedido) >= :data_inicio";
}

if ($categoria > 0) {
    $query .= " AND p.categoria_id = :categoria";
}

$query .= "
    GROUP BY 
        p.id, p.nome, p.preco, c.nome
    ORDER BY 
        total_vendido DESC, valor_total DESC
    LIMIT :limite
";

$stmt = $pdo->prepare($query);

if ($periodo === 'custom') {
    $stmt->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
    $stmt->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
} else {
    $stmt->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
}

if ($categoria > 0) {
    $stmt->bindParam(':categoria', $categoria, PDO::PARAM_INT);
}

$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_quantidade = 0;
$total_valor = 0;
foreach ($produtos as $produto) {
    $total_quantidade += $produto['total_vendido'];
    $total_valor += $produto['valor_total'];
}

$stmt_categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Relatório de Produtos Mais Vendidos</h1>
            <p class="text-gray-600">
                <?php if ($periodo === 'custom'): ?>
                    Período: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                <?php else: ?>
                    <?php 
                    $periodo_texto = '';
                    switch ($periodo) {
                        case '7d': $periodo_texto = 'últimos 7 dias'; break;
                        case '30d': $periodo_texto = 'últimos 30 dias'; break;
                        case '90d': $periodo_texto = 'últimos 90 dias'; break;
                        case '365d': $periodo_texto = 'último ano'; break;
                    }
                    ?>
                    Período: <?php echo $periodo_texto; ?>
                <?php endif; ?>
                
                <?php if ($categoria > 0): ?>
                    | Categoria: <?php 
                    foreach ($categorias as $cat) {
                        if ($cat['id'] == $categoria) {
                            echo $cat['nome'];
                            break;
                        }
                    }
                    ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex space-x-2">
            <button type="button" onclick="imprimirRelatorio()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md flex items-center transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimir
            </button>
            <button type="button" onclick="exportarCSV()" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md flex items-center transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Exportar CSV
            </button>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <select id="periodo" name="periodo" onchange="toggleCustomDates()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="7d" <?php echo $periodo === '7d' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                    <option value="30d" <?php echo $periodo === '30d' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                    <option value="90d" <?php echo $periodo === '90d' ? 'selected' : ''; ?>>Últimos 90 dias</option>
                    <option value="365d" <?php echo $periodo === '365d' ? 'selected' : ''; ?>>Último ano</option>
                    <option value="custom" <?php echo $periodo === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                </select>
            </div>
            
            <div id="data-inicio-container" class="<?php echo $periodo !== 'custom' ? 'hidden' : ''; ?>">
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data inicial</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($data_inicio) ? $data_inicio : ''; ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            
            <div id="data-fim-container" class="<?php echo $periodo !== 'custom' ? 'hidden' : ''; ?>">
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data final</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($data_fim) ? $data_fim : date('Y-m-d'); ?>" 
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
                <label for="limite" class="block text-sm font-medium text-gray-700 mb-1">Limite de resultados</label>
                <div class="flex space-x-2">
                    <select id="limite" name="limite" class="flex-grow px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="10" <?php echo $limite === 10 ? 'selected' : ''; ?>>10 produtos</option>
                        <option value="25" <?php echo $limite === 25 ? 'selected' : ''; ?>>25 produtos</option>
                        <option value="50" <?php echo $limite === 50 ? 'selected' : ''; ?>>50 produtos</option>
                        <option value="100" <?php echo $limite === 100 ? 'selected' : ''; ?>>100 produtos</option>
                    </select>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300">
                        Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md">
        <?php if (count($produtos) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="tabela-relatorio">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade Vendida</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pedidos</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Unitário</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% do Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($produtos as $index => $produto): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?> hover:bg-gray-100">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($produto['nome']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo number_format($produto['total_vendido'], 0, ',', '.'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <?php echo number_format($produto['num_pedidos'], 0, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    R$ <?php echo number_format($produto['valor_total'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php 
                                    $percentual = ($total_valor > 0) ? ($produto['valor_total'] / $total_valor) * 100 : 0;
                                    ?>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-900"><?php echo number_format($percentual, 1, ',', '.'); ?>%</span>
                                        <div class="ml-2 w-16 bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percentual; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Total
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">
                                <?php echo number_format($total_quantidade, 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                R$ <?php echo number_format($total_valor, 2, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">
                                100%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">Nenhum resultado encontrado</h3>
                <p class="text-gray-500">Não há dados de vendas para o período e filtros selecionados.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($produtos) > 0): ?>
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Gráfico de Vendas</h2>
        <div class="bg-white rounded-lg shadow-md p-6">
            <canvas id="graficoVendas" height="300"></canvas>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleCustomDates() {
        const periodo = document.getElementById('periodo').value;
        const dataInicioContainer = document.getElementById('data-inicio-container');
        const dataFimContainer = document.getElementById('data-fim-container');
        
        if (periodo === 'custom') {
            dataInicioContainer.classList.remove('hidden');
            dataFimContainer.classList.remove('hidden');
        } else {
            dataInicioContainer.classList.add('hidden');
            dataFimContainer.classList.add('hidden');
        }
    }
    
    function imprimirRelatorio() {
        window.print();
    }
    
    function exportarCSV() {

        const table = document.getElementById('tabela-relatorio');
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(';'));
        }
        
        const csvString = csv.join('\n');
        const filename = 'produtos_mais_vendidos_<?php echo date('Y-m-d'); ?>.csv';
        
        const link = document.createElement('a');
        link.style.display = 'none';
        link.setAttribute('target', '_blank');
        link.setAttribute('href', 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(csvString));
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    <?php if (count($produtos) > 0): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('graficoVendas').getContext('2d');
        
        const produtos = <?php echo json_encode(array_map(function($p) { return $p['nome']; }, $produtos)); ?>;
        const quantidades = <?php echo json_encode(array_map(function($p) { return $p['total_vendido']; }, $produtos)); ?>;
        const valores = <?php echo json_encode(array_map(function($p) { return $p['valor_total']; }, $produtos)); ?>;
        
        const max_itens = 10;
        const produtosLimitados = produtos.slice(0, max_itens);
        const quantidadesLimitadas = quantidades.slice(0, max_itens);
        const valoresLimitados = valores.slice(0, max_itens);
        
        const graficoVendas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: produtosLimitados,
                datasets: [
                    {
                        label: 'Quantidade Vendida',
                        data: quantidadesLimitadas,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Valor Total (R$)',
                        data: valoresLimitados,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Quantidade'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Valor (R$)'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Produtos Mais Vendidos'
                    }
                }
            }
        });
    });
    <?php endif; ?>
</script>

<?php include_once '../includes/footer.php'; ?>