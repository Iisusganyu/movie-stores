<?php
// api/get-movie.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Подключаем db.php из той же папки
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $movie_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($movie) {
            // Преобразуем числовые значения
            $movie['price'] = (float)$movie['price'];
            $movie['discount_price'] = $movie['discount_price'] ? (float)$movie['discount_price'] : null;
            $movie['rating'] = $movie['rating'] ? (float)$movie['rating'] : null;
            
            echo json_encode([
                'success' => true, 
                'movie' => $movie
            ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Фильм с ID ' . $movie_id . ' не найден в базе данных'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch(PDOException $e) {
        error_log('Ошибка при загрузке фильма: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Ошибка базы данных: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Не указан параметр id. Используйте: get-movie.php?id=1'
    ], JSON_UNESCAPED_UNICODE);
}
?>