<?php

require_once __DIR__. '/../config/database.php';

$allowedOrigins = [
    'http://localhost:8080',
    'http://127.0.0.1:8080',
    'http://0.0.0.0:8080'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

in_array($origin, $allowedOrigins) ?
    header("Access-Control-Allow-Origin: $origin") : null;
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET,  POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


$uri = strtok($_SERVER['REQUEST_URI'], '?');

$productId = null;
if (preg_match('#^/api/produtos/(\d+)/?$#', $uri, $matches)) {
    $productId = (int)$matches[1];
    $uri = '/api/produtos';
}

match ($uri) {
    '/api/categorias' => require __DIR__ . '/../api/categorias/listar.php',
    '/api/autorizacao/cad' => require __DIR__ . '/../api/auth/cadastro.php',
    '/api/autorizacao/sessao' => require __DIR__ . '/../api/auth/sessao.php',
    '/api/pedidos' => require __DIR__ . '/../api/pedidos/criar.php',
    '/api/carrinho' => require __DIR__ . '/../api/carrinho/httpHandler.php',
    '/api/produtos' => require __DIR__ . '/../api/produtos/handler.php',
    default => notFound(),
};

function notFound(): void
{
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada']);
}
