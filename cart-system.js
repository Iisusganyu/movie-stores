// cart-system.js - ПЕРЕРАБОТАННАЯ ВЕРСИЯ УДАЛЕНИЯ

class CartSystem {
    constructor() {
        this.currentUser = null;
        this.cartKeyPrefix = 'cart_';
        this.promoKeyPrefix = 'promo_';
        this.guestCartKey = 'guest_cart';
        this.guestPromoKey = 'guest_promo';
        this.cart = [];
        this.activePromo = null;
        this.initialized = false;
    }

    // Инициализация системы
    async init() {
        try {
            console.log('CartSystem: Начало инициализации');

            // Загружаем пользователя
            await this.getCurrentUser();
            console.log('CartSystem: Пользователь загружен', this.currentUser?.username);

            // Загружаем корзину ТОЛЬКО из localStorage
            await this.loadCartFromLocalStorage();
            console.log('CartSystem: Корзина из localStorage', this.cart.length, 'товаров');

            // Если пользователь авторизован, синхронизируем с БД (но НЕ перезаписываем)
            if (this.currentUser?.id) {
                await this.syncWithDatabase();
            }

            // Инициализируем промокоды
            this.initializePromoCodes();

            // Обновляем UI
            this.updateCartDisplay();
            this.updateProductButtons();

            // Настраиваем обработчики
            this.setupCartButtons();
            this.setupPromoCodeHandlers();

            this.initialized = true;
            console.log('CartSystem: Инициализация завершена успешно');

        } catch (error) {
            console.error('CartSystem: Ошибка инициализации:', error);
        }
    }

    // Загрузка корзины из localStorage
    async loadCartFromLocalStorage() {
        try {
            const cartKey = this.getCartKey();
            const cartData = localStorage.getItem(cartKey);

            if (cartData) {
                this.cart = JSON.parse(cartData);
                console.log('CartSystem: Корзина загружена из localStorage:', cartKey);
                console.log('CartSystem: Товары:', this.cart);
            } else {
                this.cart = [];
                console.log('CartSystem: Новая корзина создана для:', cartKey);
            }

            // Загружаем промокод
            const promoKey = this.getPromoKey();
            const promoData = localStorage.getItem(promoKey);
            if (promoData) {
                this.activePromo = JSON.parse(promoData);
            }

        } catch (e) {
            console.error('CartSystem: Ошибка загрузки корзины из localStorage:', e);
            this.cart = [];
        }
    }

    // Синхронизация с базой данных (НЕ загрузка, а только отправка изменений)
    async syncWithDatabase() {
        if (!this.currentUser?.id) return;

        try {
            console.log('CartSystem: Синхронизация с БД для user_id:', this.currentUser.id);

            // Отправляем текущую корзину в БД
            if (this.cart.length === 0) {
                // Если корзина пуста - очищаем в БД
                await this.clearCartInDatabase();
            } else {
                // Обновляем каждый товар в БД
                for (const item of this.cart) {
                    const movieId = this.extractMovieId(item.id);
                    if (movieId) {
                        await this.syncItemToDatabase(item.id, movieId, 1);
                    }
                }
            }

        } catch (e) {
            console.error('CartSystem: Ошибка синхронизации с БД:', e);
        }
    }

    setupCartButtons() {
        document.addEventListener('click', async (e) => {
            const addBtn = e.target.closest('.buy-btn');
            if (addBtn) {
                e.preventDefault();
                const product = this.getProductData(addBtn);
                if (!product) return;
                await this.addToCart(product);
                return;
            }

            const removeBtn = e.target.closest('.cart-item-remove');
            if (removeBtn) {
                e.preventDefault();
                e.stopPropagation();
                const productId = removeBtn.dataset.productId;
                if (productId) {
                    console.log('CartSystem: Удаление товара:', productId);
                    await this.removeFromCart(productId);
                }
            }
        });
    }

    // Получение текущего пользователя
    getCurrentUser() {
        const userData = localStorage.getItem('currentUser');
        if (userData && userData !== 'null') {
            try {
                this.currentUser = JSON.parse(userData);
                console.log('CartSystem: Текущий пользователь:', this.currentUser?.username);
            } catch (e) {
                this.currentUser = null;
                console.error('CartSystem: Ошибка парсинга пользователя:', e);
            }
        } else {
            this.currentUser = null;
            console.log('CartSystem: Пользователь не авторизован (гость)');
        }
    }

    // Получение ключа для корзины
    getCartKey() {
        if (this.currentUser?.username) {
            return `${this.cartKeyPrefix}${this.currentUser.username}`;
        }
        return this.guestCartKey;
    }

    // Получение ключа для промокода
    getPromoKey() {
        if (this.currentUser?.username) {
            return `${this.promoKeyPrefix}${this.currentUser.username}`;
        }
        return this.guestPromoKey;
    }

    // Получение изображения по movie_id
    getDefaultImageByMovieId(movieId) {
        const num = parseInt(movieId);
        const id = isNaN(num) ? this.extractMovieId(movieId) : num;

        if (id && id >= 1 && id <= 18) {
            return `images/poster${id}.jpg`;
        }
        return 'images/poster1.jpg';
    }

    // Сохранение корзины
    async saveCart() {
        try {
            const cartKey = this.getCartKey();
            const promoKey = this.getPromoKey();

            console.log('CartSystem: Сохранение корзины. Ключ:', cartKey, 'Товаров:', this.cart.length);

            // Сохраняем корзину в localStorage
            localStorage.setItem(cartKey, JSON.stringify(this.cart));

            // Сохраняем промокод
            if (this.activePromo) {
                localStorage.setItem(promoKey, JSON.stringify(this.activePromo));
            } else {
                localStorage.removeItem(promoKey);
            }

            console.log('CartSystem: Корзина сохранена в localStorage:', this.cart);

            // Обновляем UI
            this.updateCartDisplay();
            this.updateProductButtons();

            // Синхронизируем с БД если пользователь авторизован
            if (this.currentUser?.id) {
                await this.syncWithDatabase();
            }

        } catch (error) {
            console.error('CartSystem: Ошибка сохранения корзины:', error);
        }
    }

    // Извлечение movie_id из product_id
    extractMovieId(productId) {
        if (!productId) return null;

        // Убираем префикс "movie-"
        const cleanId = productId.replace(/^movie-/, '');

        // Если это число
        if (!isNaN(cleanId) && parseInt(cleanId) >= 1 && parseInt(cleanId) <= 18) {
            return parseInt(cleanId);
        }

        // Маппинг строковых идентификаторов
        const mapping = {
            'hunger-games': 1, 'menu': 2, 'three-daughters': 3, 'devil-wears-prada': 4,
            'scream': 5, 'sloane': 6, 'interstellar': 7, 'bohemian-rhapsody': 8,
            'cruella': 9, 'house-of-gucci': 10, 'eternity': 11, 'eternals': 11,
            'agatha-all': 12, 'divergent': 13, 'world-war-z': 14, '7-sisters': 15,
            'doctor-strange': 16, 'terrifier-3': 17, 'five-nights': 18
        };

        const lowerId = cleanId.toLowerCase().trim();
        if (mapping[lowerId] !== undefined) {
            return mapping[lowerId];
        }

        return null;
    }

    // Синхронизация товара с БД
    async syncItemToDatabase(productId, movieId, quantity) {
        if (!this.currentUser?.id || !movieId) return;

        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update',
                    user_id: this.currentUser.id,
                    movie_id: movieId,
                    quantity: quantity
                })
            });
            await response.json();
        } catch (error) {
            console.error('CartSystem: Ошибка синхронизации товара с БД:', error);
        }
    }

    // Очистка корзины в БД
    async clearCartInDatabase() {
        if (!this.currentUser?.id) return;

        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'clear',
                    user_id: this.currentUser.id
                })
            });
            await response.json();
        } catch (error) {
            console.error('CartSystem: Ошибка очистки корзины в БД:', error);
        }
    }

    initializePromoCodes() {
        this.PROMO_CODES = {
            'OSCAR2025': { discount: 0.2, name: 'Скидка 20% по промокоду OSCAR2025' },
            'MOVIE10': { discount: 0.1, name: 'Скидка 10% по промокоду MOVIE10' }
        };
    }

    // Получение данных о товаре
    getProductData(button) {
        const productCard = button.closest('.product-card') || button.closest('.movie-card');
        if (!productCard) return null;

        const productIdAttr = productCard.dataset.productId;
        const dataProductId = productCard.getAttribute('data-product-id');
        const idAttr = productCard.id;

        let movieId = productIdAttr || dataProductId || idAttr;

        if (!movieId) return null;

        const title = productCard.querySelector('h1, h2, h3')?.textContent.trim() || 'Фильм';
        const priceText = productCard.querySelector('.price')?.textContent || '';
        const price = parseInt(priceText.replace(/\D/g, '')) || 499;
        const image = productCard.querySelector('img')?.src || this.getDefaultImageByMovieId(movieId);

        // Нормализуем productId
        let finalProductId = movieId;
        if (!movieId.startsWith('movie-')) {
            finalProductId = `movie-${movieId}`;
        }

        return {
            id: finalProductId,
            movie_id: parseInt(movieId.replace(/\D/g, '')),
            title,
            price,
            image,
            quantity: 1
        };
    }

    // Добавление товара в корзину
    async addToCart(product) {
        console.log('CartSystem: Добавление товара:', product);

        // Проверяем, есть ли уже этот фильм в корзине
        const existingItem = this.cart.find(item => item.id === product.id);

        if (existingItem) {
            this.showNotification(`${product.title} уже есть в корзине!`, 'info');
            return false;
        }

        // Добавляем товар
        this.cart.push({
            ...product,
            quantity: 1,
            addedAt: new Date().toISOString()
        });

        // Сохраняем
        await this.saveCart();
        this.showNotification(`${product.title} добавлен в корзину!`);
        return true;
    }

    // Удаление товара из корзины - ПЕРЕРАБОТАННЫЙ МЕТОД
    async removeFromCart(productId) {
        console.log('CartSystem: Удаление товара ID:', productId);
        console.log('CartSystem: Корзина до удаления:', this.cart);

        // Находим товар
        const itemIndex = this.cart.findIndex(item => item.id === productId);
        
        if (itemIndex === -1) {
            console.error('CartSystem: Товар не найден:', productId);
            this.showNotification('Товар не найден в корзине', 'error');
            return false;
        }

        // Сохраняем название
        const itemName = this.cart[itemIndex].title;
        
        // Удаляем из массива
        this.cart.splice(itemIndex, 1);
        
        console.log('CartSystem: Корзина после удаления:', this.cart);

        // НЕМЕДЛЕННО сохраняем в localStorage
        const cartKey = this.getCartKey();
        localStorage.setItem(cartKey, JSON.stringify(this.cart));
        console.log('CartSystem: Сохранено в localStorage, ключ:', cartKey);

        // Проверяем, что сохранилось
        const savedCart = localStorage.getItem(cartKey);
        console.log('CartSystem: Проверка сохранения:', savedCart);

        // Удаляем из БД если пользователь авторизован
        if (this.currentUser?.id) {
            const movieId = this.extractMovieId(productId);
            if (movieId) {
                await this.syncItemToDatabase(productId, movieId, 0);
            }
        }

        // Обновляем UI
        this.updateCartDisplay();
        this.updateProductButtons();
        
        this.showNotification(`${itemName} удален из корзины`);
        
        return true;
    }

    // Обновление кнопок на страницах продуктов
    updateProductButtons() {
        console.log('CartSystem: Обновление кнопок продуктов. Товаров:', this.cart.length);

        document.querySelectorAll('.buy-btn').forEach(button => {
            const productCard = button.closest('.product-card') || button.closest('.movie-card');
            if (!productCard) return;

            const productIdAttr = productCard.dataset.productId;
            const dataProductId = productCard.getAttribute('data-product-id');
            const idAttr = productCard.id;

            let movieId = productIdAttr || dataProductId || idAttr;

            if (!movieId) return;

            let productId = movieId;
            if (!movieId.startsWith('movie-')) {
                productId = `movie-${movieId}`;
            }

            const itemInCart = this.cart.find(item => item.id === productId);

            if (itemInCart) {
                button.style.display = 'none';

                let inCartLabel = productCard.querySelector('.in-cart-label');
                if (!inCartLabel) {
                    inCartLabel = document.createElement('div');
                    inCartLabel.className = 'in-cart-label';
                    inCartLabel.innerHTML = '<span style="color: #4CAF50; font-weight: bold; font-size: 1.1rem;">В корзине</span>';
                    inCartLabel.style.cssText = `
                        display: block;
                        padding: 12px 20px;
                        background: rgba(76, 175, 80, 0.1);
                        border: 2px solid #4CAF50;
                        border-radius: 8px;
                        text-align: center;
                        margin-top: 15px;
                        font-size: 1rem;
                    `;

                    if (button.nextSibling) {
                        button.parentNode.insertBefore(inCartLabel, button.nextSibling);
                    } else {
                        button.parentNode.appendChild(inCartLabel);
                    }
                } else {
                    inCartLabel.style.display = 'block';
                }

                const qc = productCard.querySelector('.product-quantity-control');
                if (qc) qc.style.display = 'none';

            } else {
                button.style.display = 'block';

                const inCartLabel = productCard.querySelector('.in-cart-label');
                if (inCartLabel) {
                    inCartLabel.style.display = 'none';
                }

                const qc = productCard.querySelector('.product-quantity-control');
                if (qc) qc.style.display = 'none';
            }
        });
    }

    // Обновление отображения корзины
    updateCartDisplay() {
        this.updateCartBadge();
        this.updateCartPage();
    }

    // Обновление бейджа корзины
    updateCartBadge() {
        const cartBadges = document.querySelectorAll('.cart-badge');
        const totalItems = this.getTotalItems();

        cartBadges.forEach(badge => {
            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }

    // Обновление страницы корзины
    updateCartPage() {
        const cartContainer = document.querySelector('.cart-container');
        const cartItems = document.querySelector('.cart-items');
        const cartEmptyMessage = document.querySelector('.cart-empty-message');
        const cartContent = document.querySelector('.cart-content');

        if (!cartContainer || !cartItems) return;

        // Если корзина пуста
        if (this.cart.length === 0) {
            if (cartEmptyMessage) cartEmptyMessage.style.display = 'block';
            if (cartContent) cartContent.style.display = 'none';
            return;
        }

        // Если есть товары
        if (cartEmptyMessage) cartEmptyMessage.style.display = 'none';
        if (cartContent) cartContent.style.display = 'grid';

        // Генерируем товары
        cartItems.innerHTML = this.cart.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <div class="cart-item-image">
                    <img src="${item.image || this.getDefaultImageByMovieId(item.movie_id)}" 
                         alt="${item.title || 'Фильм'}"
                         style="width: 100px; height: 150px; object-fit: cover; border-radius: 5px;">
                </div>
                <div class="cart-item-info">
                    <h3>${item.title || 'Фильм'}</h3>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-item-remove" data-product-id="${item.id}" 
                            style="padding: 10px 20px; background: #e50914; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: bold;">
                        Удалить
                    </button>
                </div>
                <div class="cart-item-price">
                    <span class="price" style="font-weight: bold; font-size: 1.2rem;">
                        ${this.formatPrice(item.price || 499)}
                    </span>
                </div>
            </div>
        `).join('');

        // Обновляем суммы
        this.updateCartSummary();
    }

    // Обновление итоговой суммы
    updateCartSummary() {
        const totalPriceElement = document.querySelector('.total-price');
        const discountRow = document.querySelector('.summary-row.discount');
        const finalPriceElement = document.querySelector('.final-price');
        const promoMessage = document.getElementById('promoMessage');

        if (!totalPriceElement || !finalPriceElement) return;

        const totalPrice = this.getTotalPrice();
        let discount = 0;
        let finalPrice = totalPrice;

        if (this.activePromo) {
            discount = Math.round(totalPrice * this.activePromo.discount);
            finalPrice = totalPrice - discount;

            if (discountRow) {
                discountRow.style.display = 'flex';
                discountRow.querySelector('span:last-child').textContent = `-${this.formatPrice(discount)}`;
            }

            if (promoMessage) {
                promoMessage.textContent = `Применен промокод: ${this.activePromo.code}`;
                promoMessage.style.color = '#4CAF50';
            }
        } else {
            if (discountRow) discountRow.style.display = 'none';
            if (promoMessage) promoMessage.textContent = '';
        }

        totalPriceElement.textContent = this.formatPrice(totalPrice);
        finalPriceElement.textContent = this.formatPrice(finalPrice);
    }

    // Настройка обработчиков для промокода
    setupPromoCodeHandlers() {
        const applyPromoBtn = document.getElementById('applyPromoBtn');
        const promoCodeInput = document.getElementById('promoCodeInput');
        const removePromoBtn = document.getElementById('removePromoBtn');

        if (applyPromoBtn && promoCodeInput) {
            applyPromoBtn.addEventListener('click', () => {
                const code = promoCodeInput.value.trim();
                if (code) {
                    this.applyPromoCode(code);
                    promoCodeInput.value = '';
                }
            });
        }

        if (promoCodeInput) {
            promoCodeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const code = promoCodeInput.value.trim();
                    if (code) {
                        this.applyPromoCode(code);
                        promoCodeInput.value = '';
                    }
                }
            });
        }

        if (removePromoBtn) {
            removePromoBtn.addEventListener('click', () => {
                this.clearPromoCode();
            });
        }
    }

    // Применение промокода
    applyPromoCode(code) {
        const promoCode = code.toUpperCase().trim();

        if (this.PROMO_CODES[promoCode]) {
            const promoData = {
                code: promoCode,
                discount: this.PROMO_CODES[promoCode].discount,
                name: this.PROMO_CODES[promoCode].name,
                appliedAt: new Date().toISOString()
            };

            this.activePromo = promoData;
            this.saveCart();
            this.showNotification(`Промокод "${promoCode}" применен! Скидка ${promoData.discount * 100}%`);
            return true;
        } else {
            this.showNotification('Неверный промокод', 'error');
            return false;
        }
    }

    // Очистка промокода
    clearPromoCode() {
        this.activePromo = null;
        this.saveCart();
        this.showNotification('Промокод удален');
    }

    // Получение общего количества товаров
    getTotalItems() {
        return this.cart.length;
    }

    // Получение общей суммы
    getTotalPrice() {
        return this.cart.reduce((total, item) => total + (item.price || 0), 0);
    }

    // Форматирование цены
    formatPrice(price) {
        if (!price && price !== 0) return '0 ₽';
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
    }

    // Показ уведомления
    showNotification(message, type = 'success') {
        const existingNotification = document.querySelector('.cart-notification');
        if (existingNotification) existingNotification.remove();

        const notification = document.createElement('div');
        notification.className = `cart-notification ${type}`;
        notification.textContent = message;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#ff4444' : (type === 'info' ? '#2196F3' : '#4CAF50')};
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-family: Arial, sans-serif;
            font-size: 16px;
            font-weight: bold;
        `;

        document.body.appendChild(notification);

        if (!document.querySelector('#cart-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'cart-notification-styles';
            style.textContent = `
                @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
                @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
            `;
            document.head.appendChild(style);
        }

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Инициализация системы корзины
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM загружен, инициализируем CartSystem...');

    // Создаем глобальный экземпляр
    window.cartSystem = new CartSystem();

    // Инициализируем
    window.cartSystem.init().then(() => {
        console.log('CartSystem успешно инициализирован');
    }).catch(error => {
        console.error('Ошибка инициализации CartSystem:', error);
    });
});