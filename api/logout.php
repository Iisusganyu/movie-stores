<?php
// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Заголовки для JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Для выхода нам не нужна база данных, просто сообщаем клиенту
// что нужно очистить localStorage/sessionStorage

$response = [
    'success' => true,
    'message' => 'Выход выполнен успешно',
    'actions' => [
        'clear_local_storage' => [
            'currentUser',
            'guest_cart',
            'guest_promo'
        ],
        'clear_session_storage' => [
            'rememberedUser'
        ],
        'redirect' => 'index.html'
    ],
    'timestamp' => date('Y-m-d H:i:s')
];

// Если используется сессия на сервере (пока не используем, но оставим на будущее)
session_start();
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    session_destroy();
    
    $response['session_cleared'] = true;
}

// Отправляем ответ
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>