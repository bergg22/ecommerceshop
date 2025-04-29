<?php
// Configurações do banco de dados
$host = 'localhost';
$db   = 'ecommerce_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Erro de conexão com o banco de dados: ' . $e->getMessage());
}

// Função auxiliar para validar dados
function validarDados($dados, $regras) {
    $erros = [];
    
    foreach ($regras as $campo => $regra) {
        // Verifica se o campo existe nos dados
        if (!isset($dados[$campo]) && $regra['obrigatorio']) {
            $erros[$campo] = "O campo {$regra['nome']} é obrigatório";
            continue;
        }
        
        $valor = $dados[$campo] ?? '';
        
        // Verifica se o campo está vazio
        if ($regra['obrigatorio'] && trim($valor) === '') {
            $erros[$campo] = "O campo {$regra['nome']} é obrigatório";
            continue;
        }
        
        // Verifica o tipo
        if (isset($regra['tipo'])) {
            switch ($regra['tipo']) {
                case 'numero':
                    if (!empty($valor) && !is_numeric($valor)) {
                        $erros[$campo] = "O campo {$regra['nome']} deve ser um número";
                    }
                    break;
                case 'email':
                    if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                        $erros[$campo] = "O campo {$regra['nome']} deve ser um email válido";
                    }
                    break;
                case 'data':
                    if (!empty($valor)) {
                        $data = \DateTime::createFromFormat('Y-m-d', $valor);
                        if (!$data || $data->format('Y-m-d') !== $valor) {
                            $erros[$campo] = "O campo {$regra['nome']} deve ser uma data válida (AAAA-MM-DD)";
                        }
                    }
                    break;
            }
        }
        
        // Verifica o tamanho mínimo
        if (isset($regra['min']) && strlen($valor) < $regra['min']) {
            $erros[$campo] = "O campo {$regra['nome']} deve ter pelo menos {$regra['min']} caracteres";
        }
        
        // Verifica o tamanho máximo
        if (isset($regra['max']) && strlen($valor) > $regra['max']) {
            $erros[$campo] = "O campo {$regra['nome']} deve ter no máximo {$regra['max']} caracteres";
        }
    }
    
    return $erros;
}

// Função para escapar saída HTML
function esc($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}