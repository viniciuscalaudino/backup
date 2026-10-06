<?php

require_once __DIR__ . '/../../config/database.php';

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['error' => 'Não autenticado']);
	exit;
}

$itemId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
	'DELETE ci FROM cart_items ci '
	. 'JOIN carts c ON c.id = ci.cart_id '
	. 'WHERE ci.id = ? AND c.user_id = ?'
);
$stmt->execute([$itemId, $_SESSION['user_id']]);

if ($stmt->rowCount() === 0) {
	http_response_code(404);
	echo json_encode(['error' => 'Item não encontrado no carrinho']);
	exit;
}

echo json_encode(['message' => 'Item removido']);