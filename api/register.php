<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
        exit();
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'error' => 'Имя должно быть минимум 3 символа']);
        exit();
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'error' => 'Пароль должен быть минимум 8 символов']);
        exit();
    }
    
    try {
        // Проверка существующего пользователя
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Пользователь уже существует']);
            exit();
        }
        
        // Хэширование пароля
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Добавление пользователя
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Регистрация успешна',
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
    }
}
?>