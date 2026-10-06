<?php

require_once __DIR__ . '/../../config/database.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['error' => 'Faça login para usar o carrinho']);
	exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$productId = (int) ($data['product_id'] ?? 0);
$quantity = max(1, (int) ($data['quantity'] ?? 1));
$pdo = db();

$productQuery = $pdo->prepare('SELECT * FROM products WHERE id = ? AND active = 1');
$productQuery->execute([$productId]);
$product = $productQuery->fetch();

if (!$product) {
	http_response_code(404);
	echo json_encode(['error' => 'Produto indisponível']);
	exit;
}

if ($quantity > $product['stock']) {
	http_response_code(422);
	echo json_encode(['error' => 'Quantidade maior que o estoque']);
	exit;
}

$currentQuery = $pdo->prepare(
	'SELECT ci.quantity FROM cart_items ci '
	. 'JOIN carts c ON c.id = ci.cart_id '
	. 'WHERE c.user_id = ? AND ci.product_id = ?'
);
$currentQuery->execute([$_SESSION['user_id'], $productId]);
$inCart = (int) $currentQuery->fetchColumn();

if ($inCart + $quantity > $product['stock']) {
	http_response_code(422);
	echo json_encode([
		'error' => 'Você já tem ' . $inCart . ' no carrinho e o estoque é ' . $product['stock'],
	]);
	exit;
}

try {
	$pdo->beginTransaction();

	$cartQuery = $pdo->prepare(
		'INSERT INTO carts (user_id) VALUES (?) '
		. 'ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
	);
	$cartQuery->execute([$_SESSION['user_id']]);
	$cartId = (int) $pdo->lastInsertId();

	$itemQuery = $pdo->prepare(
		'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?) '
		. 'ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)'
	);
	$itemQuery->execute([$cartId, $productId, $quantity, $product['stock']]);

	$pdo->commit();
	echo json_encode(['message' => 'Produto adicionado']);
} catch (PDOException $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}

	error_log($exception->getMessage());
	http_response_code(500);
	echo json_encode(['error' => 'Erro interno ao adicionar o produto']);
}