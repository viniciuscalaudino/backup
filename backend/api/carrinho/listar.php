<?php

require_once __DIR__ . '/../../config/database.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['error' => 'Faça login para usar o carrinho']);
	exit;
}

$stmt = db()->prepare(
	'SELECT c.id AS cart_id, ci.id, ci.product_id, ci.quantity, '
	. 'p.name, p.price, p.stock, (ci.quantity * p.price) AS subtotal '
	. 'FROM carts c '
	. 'JOIN cart_items ci ON ci.cart_id = c.id '
	. 'JOIN products p ON p.id = ci.product_id '
	. 'WHERE c.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$total = round(array_sum(array_column($items, 'subtotal')), 2);

echo json_encode([
	'items' => $items,
	'total' => $total,
]);
