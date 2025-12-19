<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Вход выполнен',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверное имя пользователя или пароль']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
    }
}
?>