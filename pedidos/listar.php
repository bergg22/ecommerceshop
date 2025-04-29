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
$status = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'recentes';

$query = "FROM pedidos p 
          LEFT JOIN clientes c ON p.cliente_id = c.id 
          WHERE 1=1";

if (!empty($busca)) {
    $query .= " AND (c.nome LIKE :busca OR c.email LIKE :busca OR p.id = :busca_id)";
}

if (!empty($status)) {
    $query .= " AND p.status = :status";
}

if (!empty($data_inicio) && !empty($data_fim)) {
    $query .= " AND DATE(p.data_pedido) BETWEEN :data_inicio AND :data_fim";
} else if (!empty($data_inicio)) {
    $query .= " AND DATE(p.data_pedido) >= :data_inicio";
} else if (!empty($data_fim)) {
    $query .= " AND DATE(p.data_pedido) <= :data_fim";
}

$stmt_count = $pdo->prepare("SELECT COUNT(*) " . $query);

if (!empty($busca)) {
    $busca_param = "%{$busca}%";
    $stmt_count->bindParam(':busca', $busca_param, PDO::PARAM_STR);
    $stmt_count->bindParam(':busca_id', $busca, PDO::PARAM_INT);
}

if (!empty($status)) {
    $stmt_count->bindParam(':status', $status, PDO::PARAM_STR);
}

if (!empty($data_inicio) && !empty($data_fim)) {
    $stmt_count->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
    $stmt_count->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
} else if (!empty($data_inicio)) {
    $stmt_count->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
} else if (!empty($data_fim)) {
    $stmt_count->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
}

$stmt_count->execute();
$total_pedidos = $stmt_count->fetchColumn();
$total_paginas = ceil($total_pedidos / $itens_por_pagina);

switch ($ordenacao) {
    case 'antigos':
        $order_by = "p.data_pedido ASC";
        break;
    case 'valor_maior':
        $order_by = "p.valor_total DESC";
        break;
    case 'valor_menor':
        $order_by = "p.valor_total ASC";
        break;
    case 'recentes':
    default:
        $order_by = "p.data_pedido DESC";
}

$stmt = $pdo->prepare("
    SELECT 
        p.id, 
        p.data_pedido, 
        p.status, 
        p.valor_total, 
        p.forma_pagamento,
        c.id as cliente_id, 
        c.nome as cliente_nome, 
        c.email as cliente_email,
        (SELECT COUNT(*) FROM itens_pedido WHERE pedido_id = p.id) as qtd_itens
    " . $query . " 
    ORDER BY {$order_by} 
    LIMIT :offset, :limit
");

if (!empty($busca)) {
    $busca_param = "%{$busca}%";
    $stmt->bindParam(':busca', $busca_param, PDO::PARAM_STR);
    $stmt->bindParam(':busca_id', $busca, PDO::PARAM_INT);
}

if (!empty($status)) {
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
}

if (!empty($data_inicio) && !empty($data_fim)) {
    $stmt->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
    $stmt->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
} else if (!empty($data_inicio)) {
    $stmt->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
} else if (!empty($data_fim)) {
    $stmt->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado_inicio = $offset + 1;
$resultado_fim = min($offset + $itens_por_pagina, $total_pedidos);

if (isset($_GET['excluir']) && !empty($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt_delete_itens = $pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = ?");
        $stmt_delete_itens->execute([$id_excluir]);
        
        $stmt_delete = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmt_delete->execute([$id_excluir]);
        
        $pdo->commit();
        
        $mensagem = [
            'tipo' => 'sucesso',
            'texto' => 'Pedido excluído com sucesso.'
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        $mensagem = [
            'tipo' => 'erro',
            'texto' => 'Erro ao excluir pedido: ' . $e->getMessage()
        ];
    }
    
    header("Location: listar.php?" . http_build_query([
        'busca' => $busca, 
        'status' => $status,
        'data_inicio' => $data_inicio,
        'data_fim' => $data_fim,
        'ordenacao' => $ordenacao,
        'pagina' => $pagina_atual,
        'msg' => $mensagem['tipo'],
        'texto' => $mensagem['texto']
    ]));
    exit;
}

$msg_tipo = isset($_GET['msg']) ? $_GET['msg'] : '';
$msg_texto = isset($_GET['texto']) ? $_GET['texto'] : '';

include_once '../includes/header.php';
?>
