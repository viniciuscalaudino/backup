<?php
function metodoNaoPermitido(): void
{
    http_response_code(405);
    header('Allow: GET, POST, DELETE');
    echo json_encode(['error' => 'Método não permitido']);
}

match ($_SERVER['REQUEST_METHOD']) {
    'POST' => require __DIR__ . '/adicionar.php',
    'GET' => require __DIR__ . '/listar.php',
    'DELETE' => require __DIR__ . '/remover.php',
    default => metodoNaoPermitido(),
};