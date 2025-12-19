<?php
// Включаем отображение ошибок ВО ВСЕХ файлах API
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Заголовки для JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => true,
    'message' => 'Movie Stores API v1.0',
    'status' => 'operational',
    'data' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'database' => 'movie_store_db',
        'tables_count' => 0,
        'movies_count' => 0,
        'users_count' => 0
    ]
];

try {
    // Подключение к базе
    $host = 'localhost';
    $dbname = 'movie_store_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем количество таблиц
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $response['data']['tables_count'] = count($tables);
    $response['data']['tables'] = $tables;
    
    // Количество фильмов
    $moviesCount = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
    $response['data']['movies_count'] = $moviesCount;
    
    // Количество пользователей
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $response['data']['users_count'] = $usersCount;
    
} catch(PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error';
    $response['error'] = $e->getMessage();
}

// Выводим JSON с красивым форматированием
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>