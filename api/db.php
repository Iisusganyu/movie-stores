<?php
// api/db.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'movie_store_db';
$username = 'root';
$password = '';  // Пустая строка для XAMPP по умолчанию

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    // Проверяем существование таблиц (опционально, для отладки)
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Найдены таблицы: " . implode(', ', $tables));
    
    // Если нет таблицы movies, создаем её (опционально)
    if (!in_array('movies', $tables)) {
        $pdo->exec("
            CREATE TABLE movies (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                original_title VARCHAR(255),
                year YEAR,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                discount_price DECIMAL(10,2),
                image_url VARCHAR(500),
                genre VARCHAR(100),
                director VARCHAR(100),
                duration VARCHAR(20),
                rating DECIMAL(3,1),
                trailer VARCHAR(500),
                cast TEXT,
                country VARCHAR(100),
                age_rating VARCHAR(10),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        error_log("Таблица movies создана автоматически");
        
        // Добавляем тестовые данные
        $pdo->exec("
            INSERT INTO movies (title, year, description, price, genre, director, duration, rating, cast, country) 
            VALUES ('Голодные игры', 2012, 'Будущее. Деспотичное государство ежегодно устраивает показательные игры на выживание...', 499.00, 'Фантастика, Боевик, Приключения, Триллер', 'Гэри Росс', '142 мин.', 7.3, 'Дженнифер Лоуренс, Джош Хатчерсон, Лиам Хемсворт, Элизабет Бэнкс', 'США')
        ");
    }
    
} catch(PDOException $e) {
    // Для отладки выводим больше информации
    error_log('Ошибка подключения к БД: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'details' => [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username
        ]
    ]);
    exit();
}
?>