<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM movies ORDER BY title");
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'movies' => $movies]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка при загрузке фильмов']);
    }
}
?>