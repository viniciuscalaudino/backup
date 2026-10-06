<?php

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$data['email'] ?? '']);
    $user = $stmt->fetch();

    if (!$user || !password_verify($data['password'] ?? '', $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciais inválidas']);
        exit;
    }

    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
        'message' => 'Login realizado',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
    ]);

} elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    session_start();
    session_destroy();

    echo json_encode(['message' => 'Logout realizado']);
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    session_start();
    echo json_encode(["usuario" => !empty($_SESSION['user_id'])]);
} else {
    http_response_code(405);
    header("Allow: GET, POST, DELETE");
    echo json_encode(['message' => 'Não se pode editar sessões.']);
}
