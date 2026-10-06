<?php

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Nome, e-mail e senha são obrigatórios']);
    exit;
}

if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Nome, e-mail e senha são obrigatórios']);
    exit;
}

if (!is_string($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['error' => 'Digite um e-mail válido']);
    exit;
}

if (!is_string($data['password']) || strlen($data['password']) < 8) {
    http_response_code(422);
    echo json_encode(['error' => 'A senha deve ter pelo menos 8 caracteres']);
    exit;
}

$stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$data['email']]);

if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'E-mail já cadastrado']);
    exit;
}

try {
    $stmt = db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->execute([
        $data['name'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['error' => 'E-mail já cadastrado']);
        exit;
    }

    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao cadastrar']);
    exit;
}

http_response_code(201);
echo json_encode(['message' => 'Cadastro realizado com sucesso']);
