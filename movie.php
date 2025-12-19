<?php
// movie.php - Единая страница для всех фильмов

// Получаем ID фильма
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Если ID не указан, перенаправляем в каталог
if ($movie_id <= 0) {
    header("Location: catalog.html");
    exit();
}

// Функция для получения данных через cURL
function getMovieData($movie_id) {
    $api_url = "http://localhost/movie-stores/api/get-movie.php?id=" . $movie_id;
    
    // Создаем контекст для file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $movie_data = @file_get_contents($api_url, false, $context);
    
    if ($movie_data === FALSE) {
        // Попробуем другой способ - напрямую через БД
        return getMovieDataFromDB($movie_id);
    }
    
    return json_decode($movie_data, true);
}

// Функция для получения данных напрямую из БД
function getMovieDataFromDB($movie_id) {
    require_once 'api/db.php'; // Подключаемся к БД напрямую
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($movie) {
            return ['success' => true, 'movie' => $movie];
        } else {
            return ['success' => false, 'error' => 'Фильм не найден в базе данных'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()];
    }
}

// Получаем данные фильма
$data = getMovieData($movie_id);

if ($data && $data['success']) {
    $movie = $data['movie'];
    $error = null;
} else {
    $movie = null;
    $error = $data['error'] ?? 'Не удалось загрузить данные фильма';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo1.png">
    <title><?php echo $movie ? htmlspecialchars($movie['title']) : 'Фильм'; ?> - CineHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-quantity-control {
            display: none;
        }
        .product-quantity-control.visible {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 10px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 8px;
            border: 1px solid rgba(255, 0, 0, 0.3);
        }
        
        /* Отключаем выделение при быстрых кликах */
        .quantity-btn {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        /* Стиль для заблокированной кнопки */
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .error-container {
            text-align: center;
            padding: 4rem;
            background: rgba(25, 25, 25, 0.9);
            border-radius: 10px;
            border: 1px solid rgba(255, 0, 0, 0.3);
            margin: 2rem auto;
            max-width: 800px;
        }
        
        /* Стили для OMDb информации */
        .omdb-info-block {
            background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
            border: 2px solid #e50914;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            color: white;
        }
        .omdb-header {
            font-weight: bold;
            font-size: 18px;
            color: #e50914;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        .omdb-content {
            display: grid;
            gap: 20px;
        }
        .omdb-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid;
        }
        .omdb-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .omdb-value {
            font-weight: bold;
            font-size: 16px;
            color: #fff;
            line-height: 1.4;
        }
        .omdb-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #333;
            font-size: 11px;
            color: #666;
        }
        
        /* Секция для дополнительной информации из API */
        .additional-info {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(255, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo.png" alt="Movie Stores Logo" class="logo">
        </div>
        <div class="container">
            <nav>
                <ul>
                    <li><a href="index.html">Главная</a></li>
                    <li><a href="catalog.html">Каталог</a></li>
                    <li><a href="contacts.html">Контакты</a></li>
                </ul>
            </nav>
            <div class="header-right">
                <a href="auth.html" class="header-btn login-btn">Вход</a>
                <a href="cart.html" class="header-btn cart-btn">
                    Корзина
                    <span class="cart-badge">0</span>
                </a>
            </div>
        </div>
    </header>

    <main class="product-page">
        <div class="container">
            <?php if ($movie): ?>
                <!-- Отображение фильма -->
                <div class="product-card" data-product-id="movie-<?php echo $movie['id']; ?>">
                    <img src="<?php echo htmlspecialchars($movie['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                         class="product-image"
                         onerror="this.src='images/default-poster.jpg'">
                    <div class="product-info">
                        <h1 class="product-title"><?php echo htmlspecialchars($movie['title']); ?> (<?php echo $movie['year']; ?>)</h1>
                        
                        <?php if (!empty($movie['original_title'])): ?>
                            <p><strong>Оригинальное название:</strong> <?php echo htmlspecialchars($movie['original_title']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['rating'])): ?>
                            <p class="rating">⭐ <span class="rating-value"><?php echo $movie['rating']; ?></span>/10</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['genre'])): ?>
                            <p><strong>Жанр:</strong> <span class="movie-genre"><?php echo htmlspecialchars($movie['genre']); ?></span></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['director'])): ?>
                            <p><strong>Режиссер:</strong> <span class="movie-director"><?php echo htmlspecialchars($movie['director']); ?></span></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['cast'])): ?>
                            <p><strong>В ролях:</strong> <span class="movie-actors"><?php echo htmlspecialchars($movie['cast']); ?></span></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['duration'])): ?>
                            <p><strong>Продолжительность:</strong> <span class="movie-duration"><?php echo htmlspecialchars($movie['duration']); ?></span></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['country'])): ?>
                            <p><strong>Страна:</strong> <span class="movie-country"><?php echo htmlspecialchars($movie['country']); ?></span></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['age_rating'])): ?>
                            <p><strong>Возрастной рейтинг:</strong> <span class="movie-age-rating"><?php echo htmlspecialchars($movie['age_rating']); ?></span></p>
                        <?php endif; ?>
                        
                        <div class="description">
                            <h3>Описание:</h3>
                            <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                        </div>
                        
                        <!-- Секция для дополнительной информации из OMDb API -->
                        
                        <div class="price-section">
                            <?php 
                            $hasDiscount = !empty($movie['discount_price']) && $movie['discount_price'] < $movie['price'];
                            if ($hasDiscount): ?>
                                <div class="price">
                                    <span style="text-decoration: line-through; color: #888; margin-right: 15px; font-size: 1.2rem;">
                                        <?php echo $movie['price']; ?> руб.
                                    </span>
                                    <strong style="color: #ff0000; font-size: 1.8rem;">
                                        <?php echo $movie['discount_price']; ?> руб.
                                    </strong>
                                </div>
                            <?php else: ?>
                                <div class="price" style="font-size: 1.5rem;">
                                    Цена: <strong><?php echo $movie['price']; ?> руб.</strong>
                                </div>
                            <?php endif; ?>
                            
                            <button class="buy-btn" data-product-id="movie-<?php echo $movie['id']; ?>" style="margin-top: 15px;">
                                Добавить в корзину
                            </button>

                            <div class="product-quantity-control" style="display: none;">> 
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Сообщение об ошибке -->
                <div class="error-container">
                    <h2 style="color: #ff0000; margin-bottom: 1rem;">Ошибка</h2>
                    <p style="color: #888; margin-bottom: 2rem; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($error ?? 'Фильм с ID ' . $movie_id . ' не найден'); ?>
                    </p>
                    
                    <div style="margin-top: 2rem;">
                        <a href="catalog.html" class="btn" style="padding: 12px 30px; font-size: 1.1rem; margin-right: 15px;">Вернуться в каталог</a>
                        <a href="movie.php?id=1" class="btn" style="padding: 12px 30px; font-size: 1.1rem; background: #666;">Проверить фильм ID=1</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Movie Stores. Все права защищены.</p>
        </div>
    </footer>

    <!-- Подключаем скрипты -->
    <script src="cart-system.js"></script>
    <script src="auth-v2.js"></script>
    <script src="omdb-api.js"></script> <!-- Добавляем скрипт OMDb API -->
    
    <?php if ($movie): ?>
    <script>
    // Переменные для управления состоянием
    let isProcessing = false;
    let quantityHandlersSetup = false;
    
    // Упрощенная инициализация для movie.php
    document.addEventListener('DOMContentLoaded', function() {
        // Даем время на загрузку cartSystem
        setTimeout(() => {
            if (window.cartSystem) {
                // Обновляем кнопки товаров
                window.cartSystem.updateProductButtons();
            
                // Настраиваем обработчики для кнопок на этой странице
                setupPageSpecificHandlers();
            }
        }, 500); // Увеличьте задержку для надежности
    });
    // Функция для настройки обработчиков конкретно этой страницы
    function setupPageSpecificHandlers() {
        const buyBtn = document.querySelector('.buy-btn');
        const minusBtn = document.querySelector('.minus-btn');
        const plusBtn = document.querySelector('.plus-btn');
    
        if (buyBtn) {
            buyBtn.addEventListener('click', function(e) {
                e.preventDefault();
            
                // Используем cartSystem для добавления
                if (window.cartSystem) {
                    const productCard = document.querySelector('.product-card');
                    const productId = productCard.dataset.productId;
                    const productTitle = document.querySelector('.product-title').textContent;
                    const priceElement = document.querySelector('.price strong') || document.querySelector('.price');
                    const priceText = priceElement.textContent;
                    const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                    const image = document.querySelector('.product-image').src;
                
                    window.cartSystem.addToCart({
                        id: productId,
                        title: productTitle,
                        price: price,
                        image: image,
                        quantity: 1
                    });
                }
            });
        }
    }
    
    // Настройка обработчиков для кнопок количества
    function setupQuantityControls() {
        if (quantityHandlersSetup) return;
        quantityHandlersSetup = true;
        
        console.log('Настройка обработчиков количества');
        
        const buyBtn = document.querySelector('.buy-btn');
        const minusBtn = document.querySelector('.minus-btn');
        const plusBtn = document.querySelector('.plus-btn');
        const productId = document.querySelector('.product-card')?.dataset.productId;
        
        if (!productId) {
            console.error('Не найден productId');
            return;
        }
        
        // Обработчик для кнопки "Добавить в корзину"
        if (buyBtn) {
            buyBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                
                if (isProcessing) return;
                isProcessing = true;
                
                console.log('Добавление товара в корзину:', productId);
                
                // Используем cartSystem если он доступен
                if (window.cartSystem) {
                    const title = document.querySelector('.product-title').textContent;
                    const priceElement = document.querySelector('.price strong') || document.querySelector('.price');
                    const priceText = priceElement.textContent;
                    const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                    const image = document.querySelector('.product-image').src;
                    
                    await cartSystem.addToCart(productId, {
                        id: productId,
                        title: title,
                        price: price,
                        image: image,
                        quantity: 1
                    });
                    
                    // После добавления получаем обновленное количество
                    const quantity = getItemQuantityFromCart(productId);
                    updateQuantityDisplay(productId, quantity);
                } else {
                    // Fallback на localStorage если cartSystem не доступен
                    addToCartLocalStorage(productId);
                }
                
                setTimeout(() => { isProcessing = false; }, 300);
            });
        }
        
        // Обработчик для кнопки "-" (минус)
        if (minusBtn) {
            minusBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (isProcessing) return;
                isProcessing = true;
                
                console.log('Уменьшение количества:', productId);
                
                if (window.cartSystem) {
                    await cartSystem.updateQuantity(productId, -1);
                    const quantity = getItemQuantityFromCart(productId);
                    updateQuantityDisplay(productId, quantity);
                } else {
                    updateQuantityLocalStorage(productId, -1);
                }
                
                setTimeout(() => { isProcessing = false; }, 300);
            });
        }
        
        // Обработчик для кнопки "+" (плюс)
        if (plusBtn) {
            plusBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (isProcessing) return;
                isProcessing = true;
                
                console.log('Увеличение количества:', productId);
                
                if (window.cartSystem) {
                    await cartSystem.updateQuantity(productId, 1);
                    const quantity = getItemQuantityFromCart(productId);
                    updateQuantityDisplay(productId, quantity);
                } else {
                    updateQuantityLocalStorage(productId, 1);
                }
                
                setTimeout(() => { isProcessing = false; }, 300);
            });
        }
    }
    
    // Функции для работы с localStorage напрямую (если cartSystem недоступен)
    function addToCartLocalStorage(productId) {
        const userData = localStorage.getItem('currentUser');
        let cartKey = 'guest_cart';
        
        if (userData && userData !== 'null') {
            try {
                const user = JSON.parse(userData);
                if (user.username) {
                    cartKey = `cart_${user.username}`;
                }
            } catch(e) {
                console.error('Error parsing user:', e);
            }
        }
        
        const cartData = localStorage.getItem(cartKey);
        let cart = [];
        
        if (cartData) {
            try {
                cart = JSON.parse(cartData);
            } catch(e) {
                console.error('Error parsing cart:', e);
            }
        }
        
        // Получаем данные товара
        const title = document.querySelector('.product-title').textContent;
        const priceElement = document.querySelector('.price strong') || document.querySelector('.price');
        const priceText = priceElement.textContent;
        const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
        const image = document.querySelector('.product-image').src;
        
        // Проверяем, есть ли уже товар в корзине
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                title: title,
                price: price,
                image: image,
                quantity: 1
            });
        }
        
        // Сохраняем обратно в localStorage
        localStorage.setItem(cartKey, JSON.stringify(cart));
        
        // Обновляем отображение
        const quantity = existingItem ? existingItem.quantity : 1;
        updateQuantityDisplay(productId, quantity);
        updateCartBadgeFromStorage();
        
        console.log('Товар добавлен в localStorage корзину');
    }
    
    function updateQuantityLocalStorage(productId, change) {
        const userData = localStorage.getItem('currentUser');
        let cartKey = 'guest_cart';
        
        if (userData && userData !== 'null') {
            try {
                const user = JSON.parse(userData);
                if (user.username) {
                    cartKey = `cart_${user.username}`;
                }
            } catch(e) {
                console.error('Error parsing user:', e);
            }
        }
        
        const cartData = localStorage.getItem(cartKey);
        if (!cartData) return;
        
        try {
            let cart = JSON.parse(cartData);
            const itemIndex = cart.findIndex(item => item.id === productId);
            
            if (itemIndex !== -1) {
                cart[itemIndex].quantity += change;
                
                // Если количество стало 0 или меньше - удаляем товар
                if (cart[itemIndex].quantity <= 0) {
                    cart.splice(itemIndex, 1);
                    updateQuantityDisplay(productId, 0);
                } else {
                    updateQuantityDisplay(productId, cart[itemIndex].quantity);
                }
                
                // Сохраняем обновленную корзину
                localStorage.setItem(cartKey, JSON.stringify(cart));
                updateCartBadgeFromStorage();
            }
        } catch(e) {
            console.error('Error updating quantity in localStorage:', e);
        }
    }
    
    // Обновление бейджа корзины из cartSystem
    function updateCartBadge() {
        if (!window.cartSystem) {
            updateCartBadgeFromStorage();
            return;
        }
        
        const totalItems = cartSystem.getTotalItems ? cartSystem.getTotalItems() : 
                          cartSystem.cart.reduce((total, item) => total + (item.quantity || 1), 0);
        
        const cartBadges = document.querySelectorAll('.cart-badge');
        cartBadges.forEach(badge => {
            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }
    
    // Обновление бейджа корзины из localStorage
    function updateCartBadgeFromStorage() {
        const userData = localStorage.getItem('currentUser');
        let cartKey = 'guest_cart';
        
        if (userData && userData !== 'null') {
            try {
                const user = JSON.parse(userData);
                if (user.username) {
                    cartKey = `cart_${user.username}`;
                }
            } catch(e) {
                console.error('Error parsing user:', e);
            }
        }
        
        const cartData = localStorage.getItem(cartKey);
        let totalItems = 0;
        
        if (cartData) {
            try {
                const cart = JSON.parse(cartData);
                totalItems = cart.reduce((total, item) => total + (item.quantity || 1), 0);
            } catch(e) {
                console.error('Error parsing cart:', e);
            }
        }
        
        const cartBadges = document.querySelectorAll('.cart-badge');
        cartBadges.forEach(badge => {
            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }
    
    // Делегирование событий на уровне документа
    document.addEventListener('click', function(e) {
        if ((e.target.classList.contains('minus-btn') || e.target.classList.contains('plus-btn')) && isProcessing) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Игнорируем клик, обработка уже идет');
        }
    });
</script>
    <?php endif; ?>
</body>
</html>