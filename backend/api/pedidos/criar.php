<?php

require_once __DIR__ . '/../../config/database.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	echo json_encode(['error' => 'Método não permitido']);
	exit;
}

if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['error' => 'Login obrigatório']);
	exit;
}

$pdo = db();

try {
	$pdo->beginTransaction();

	$cartQuery = $pdo->prepare('SELECT id FROM carts WHERE user_id = ?');
	$cartQuery->execute([$_SESSION['user_id']]);
	$cart = $cartQuery->fetch();

	if (!$cart) {
		throw new Exception('Carrinho vazio');
	}

	$itemsQuery = $pdo->prepare(
		'SELECT ci.*, p.name, p.price, p.stock, p.active '
		. 'FROM cart_items ci '
		. 'JOIN products p ON p.id = ci.product_id '
		. 'WHERE ci.cart_id = ? FOR UPDATE'
	);
	$itemsQuery->execute([$cart['id']]);
	$items = $itemsQuery->fetchAll();

	if (!$items) {
		throw new Exception('Carrinho vazio');
	}

	$total = 0;

	foreach ($items as $item) {
		if (!$item['active'] || $item['stock'] < $item['quantity']) {
			throw new Exception('Estoque insuficiente para ' . $item['name']);
		}

		$total += $item['price'] * $item['quantity'];
	}

	$total = round($total, 2);

	$orderQuery = $pdo->prepare('INSERT INTO orders (user_id, total) VALUES (?, ?)');
	$orderQuery->execute([$_SESSION['user_id'], $total]);
	$orderId = $pdo->lastInsertId();

	$orderItemQuery = $pdo->prepare(
		'INSERT INTO order_items '
		. '(order_id, product_id, product_name, unit_price, quantity) '
		. 'VALUES (?, ?, ?, ?, ?)'
	);
	$stockQuery = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

	foreach ($items as $item) {
		$orderItemQuery->execute([
			$orderId,
			$item['product_id'],
			$item['name'],
			$item['price'],
			$item['quantity'],
		]);
		$stockQuery->execute([$item['quantity'], $item['product_id']]);
	}

	$deleteItemsQuery = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
	$deleteItemsQuery->execute([$cart['id']]);

	$pdo->commit();
	echo json_encode([
		'message' => 'Pedido criado',
		'order_id' => $orderId,
	]);
} catch (PDOException $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}

	error_log($exception->getMessage());
	http_response_code(500);
	echo json_encode(['error' => 'Erro interno ao criar o pedido']);
} catch (Exception $exception) {
	if ($pdo->inTransaction()){
		$pdo->rollBack();
	}

	http_response_code(422);
	echo json_encode(['error' => $exception->getMessage()]);
}
