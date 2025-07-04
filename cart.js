// cart.js - Функции для работы с корзиной товаров
// Обновлено: 21.05.2025

// === Словарь цветов по RAL ===
function getColorHex(code) {
    const colors = {
        '9016': '#F6F6F6', // RAL 9016 — Белый транспортный
        '7035': '#D7D7D7', // RAL 7035 — Серый светлый  
        '6005': '#2F4F2F', // RAL 6005 — Зелёный мох
        '5012': '#3E5F8A', // RAL 5012 — Синий светлый
        '3000': '#AF2B1E', // RAL 3000 — Красный огненный
        'custom': '#f0f0f0' // Для заказных цветов
    };
    return colors[code] || '#cccccc'; // Серый по умолчанию
}

// === Получение названия цвета ===
function getColorName(code) {
    const names = {
        '9016': 'Белый транспортный',
        '7035': 'Серый светлый',
        '6005': 'Зелёный мох', 
        '5012': 'Синий светлый',
        '3000': 'Красный огненный',
        'custom': 'На заказ'
    };
    return names[code] || 'Стандартный';
}

function getCartFromStorage() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

function saveCartToStorage(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartIcon() {
    const cart = getCartFromStorage();
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const countEl = document.querySelector('.cart-item-count');
    if (countEl) {
        countEl.textContent = count;
        countEl.classList.add('updated');
        setTimeout(() => countEl.classList.remove('updated'), 300);
    }
}

function addToCart(product) {
    const cart = getCartFromStorage();
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    saveCartToStorage(cart);
    updateCartIcon();
}

function setupAddToCartButtons() {
    document.querySelectorAll('.btn-tocart').forEach(button => {
        button.addEventListener('click', () => {
            const product = {
                id: button.dataset.productId,
                name: button.dataset.productName,
                price: parseInt(button.dataset.productPrice, 10),
                image: button.dataset.productImage,
                color: button.dataset.productColor // <-- Добавляем цвет
            };
            addToCart(product);
        });
    });
}

function initCartPage() {
    const container = document.getElementById('cart-container');
    const cart = getCartFromStorage();
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = '<p>Ваша корзина пуста.</p>';
        return;
    }

    let total = 0;
    const itemsHtml = cart.map(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        // Цвет: если не указан, то показываем стандартный
        const color = item.color || '9016';
        const isCustom = color === 'custom';
        const bgColor = getColorHex(color);
        const colorName = getColorName(color);

        // HTML для отображения цвета и текста
        const colorDisplay = isCustom 
            ? `
              <div class="cart-item-color-label" style="margin-right: 8px; background: linear-gradient(45deg, #ffffff 25%, #e0e0e0 25%, #e0e0e0 50%, #ffffff 50%, #ffffff 75%, #e0e0e0 75%); padding: 2px 8px; border: 1px dashed #999; border-radius: 3px; font-size: 0.8rem;">
                ${colorName}
              </div>
            `
            : `
              <div class="cart-item-color" style="background-color: ${bgColor}; border: 1px solid #ddd;" title="${colorName} (RAL ${color})"></div>
              <div class="cart-item-color-name">
                ${colorName}
              </div>
            `;

        return `
          <div class="cart-item" data-id="${item.id}">
            <div class="cart-item-image">
              <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="cart-item-details">
              <div class="cart-item-name">${item.name}</div>
              <div class="cart-item-color-wrapper" style="display: flex; align-items: center; margin-top: 4px;">
                ${colorDisplay}
              </div>
              <div class="cart-item-price-single">${item.price.toLocaleString()} руб. / шт.</div>
            </div>
            <div class="cart-item-quantity">
              <button class="quantity-btn" data-id="${item.id}" data-action="decrease">−</button>
              <input type="number" value="${item.quantity}" min="1" readonly>
              <button class="quantity-btn" data-id="${item.id}" data-action="increase">+</button>
              <button class="remove-btn" data-id="${item.id}" title="Удалить">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="cart-item-total-price">${itemTotal.toLocaleString()} руб.</div>
          </div>
        `;
    }).join('');

    const summaryHtml = `
      <div id="cart-summary">
        <div class="cart-total">Итого: <span>${total.toLocaleString()} руб.</span></div>
        <div class="cart-actions">
          <a href="index.html#catalog" class="btn btn-secondary">&larr; Продолжить покупки</a>
          <a href="checkout.html" class="btn btn-primary btn-checkout">Оформить заказ &rarr;</a>
        </div>
      </div>
    `;

    container.innerHTML = itemsHtml + summaryHtml;
    setupQuantityButtons();
    setupRemoveButtons();
} // Конец функции initCartPage()

function setupQuantityButtons() {
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const id = this.dataset.id;
            const cart = getCartFromStorage();
            const item = cart.find(i => i.id === id);
            
            if (!item) return;
            
            if (action === 'increase') {
                item.quantity++;
            } else if (action === 'decrease') {
                item.quantity = Math.max(1, item.quantity - 1);
            }
            
            saveCartToStorage(cart);
            updateCartIcon();
            initCartPage();
        });
    });
}

function setupRemoveButtons() {
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            let cart = getCartFromStorage();
            cart = cart.filter(item => item.id !== btn.dataset.id);
            saveCartToStorage(cart);
            initCartPage();
            updateCartIcon();
        });
    });
}

function initCheckoutPage() {
    const cart = getCartFromStorage();
    const table = document.getElementById('checkout-order-table');
    const totalEl = document.getElementById('checkout-grand-total-amount');

    if (!table || !totalEl || cart.length === 0) return;

    let total = 0;
    let rows = `
      <thead>
        <tr>
          <th>Товар</th>
          <th>Кол-во</th>
          <th>Цена / шт.</th>
          <th>Сумма</th>
        </tr>
      </thead>
      <tbody>
    `;

    cart.forEach(item => {
        const itemTotal = item.quantity * item.price;
        total += itemTotal;
        rows += `
          <tr>
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.price.toLocaleString()} руб.</td>
            <td>${itemTotal.toLocaleString()} руб.</td>
          </tr>
        `;
    });

    rows += '</tbody>';
    table.innerHTML = rows;
    totalEl.textContent = `${total.toLocaleString()} руб.`;
}

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    setupAddToCartButtons();
    updateCartIcon();
    if (document.getElementById('cart-container')) initCartPage();
    if (document.getElementById('checkout-order-table')) initCheckoutPage();
});