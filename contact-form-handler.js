// Функция валидации формы контактов
function validateContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return false;

  let isValid = true;
  
  // Проверка имени
  const nameInput = form.elements['name'];
  if (!nameInput.value.trim()) {
    nameInput.classList.add('invalid');
    form.querySelector('#name + .input-error').textContent = 'Пожалуйста, введите ваше имя';
    form.querySelector('#name + .input-error').hidden = false;
    isValid = false;
  } else {
    nameInput.classList.remove('invalid');
    form.querySelector('#name + .input-error').hidden = true;
  }

  // Проверка телефона
  const phoneInput = form.elements['phone'];
  const phoneRegex = /^\+?[78]\d{10}$/; // Проверка на формат +7XXXXXXXXXX или 8XXXXXXXXXX
  if (!phoneInput.value.trim()) {
    phoneInput.classList.add('invalid');
    form.querySelector('#phone + .input-error').textContent = 'Пожалуйста, введите ваш телефон';
    form.querySelector('#phone + .input-error').hidden = false;
    isValid = false;
  } else if (!phoneRegex.test(phoneInput.value.replace(/\D/g, ''))) {
    phoneInput.classList.add('invalid');
    form.querySelector('#phone + .input-error').textContent = 'Введите номер в формате +7XXXXXXXXXX или 8XXXXXXXXXX';
    form.querySelector('#phone + .input-error').hidden = false;
    isValid = false;
  } else {
    phoneInput.classList.remove('invalid');
    form.querySelector('#phone + .input-error').hidden = true;
  }

  // Проверка email если указан
  const emailInput = form.elements['email'];
  if (emailInput.value.trim()) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
      emailInput.classList.add('invalid');
      form.querySelector('#email + .input-error').textContent = 'Введите корректный email';
      form.querySelector('#email + .input-error').hidden = false;
      isValid = false;
    } else {
      emailInput.classList.remove('invalid');
      form.querySelector('#email + .input-error').hidden = true;
    }
  }

  // Проверка сообщения
  const messageInput = form.elements['message'];
  if (!messageInput.value.trim()) {
    messageInput.classList.add('invalid');
    form.querySelector('#message + .input-error').textContent = 'Пожалуйста, введите сообщение';
    form.querySelector('#message + .input-error').hidden = false;
    isValid = false;
  } else {
    messageInput.classList.remove('invalid');
    form.querySelector('#message + .input-error').hidden = true;
  }

  return isValid;
}

// Обработчик отправки формы контактов
function handleContactFormSubmit(event) {
  event.preventDefault();
  
  if (!validateContactForm()) {
    return;
  }

  const form = event.target;
  const formData = new FormData(form);

  // Получаем корзину из localStorage
  const cart = JSON.parse(localStorage.getItem('cart')) || [];

  // Добавляем цвет к каждому товару
  const cartWithColors = cart.map(item => ({
    ...item,
    color: item.color || 'не указан'
  }));

  // Добавляем JSON-представление корзины в форму
  formData.append('cart_json', JSON.stringify(cartWithColors));

  // Отправка данных на сервер
  fetch('contact-form-handler-main.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Ошибка отправки формы');
    }
    return response.text();
  })
  .then(data => {
      form.reset();

      // Показываем сообщение об успехе
      const successMessage = document.createElement('div');
      successMessage.className = 'success-message';
      successMessage.style.cssText = `
        background-color: #dff0d8;
        color: #3c763d;
        padding: 10px;
        margin-top: 15px;
        text-align: center;
      `;
      successMessage.textContent = 'Ваше сообщение успешно отправлено! Мы свяжемся с вами в ближайшее время.';
      
      // Вставляем сообщение после формы
      form.parentNode.insertBefore(successMessage, form.nextSibling);
      
      // Удаляем сообщение через 5 секунд
      setTimeout(() => {
        successMessage.remove();
      }, 5000);
    })
    .catch(error => {
      console.error('Ошибка:', error);

      // Показываем сообщение об ошибке
      const errorMessage = document.createElement('div');
      errorMessage.className = 'error-message';
      errorMessage.style.cssText = `
        background-color: #f2dede;
        color: #a94442;
        padding: 10px;
        margin-top: 15px;
        text-align: center;
      `;
      errorMessage.textContent = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте снова.';
      form.parentNode.insertBefore(errorMessage, form.nextSibling);

      // Скрываем сообщение через 5 секунд
      setTimeout(() => {
        errorMessage.remove();
      }, 5000);
    });
}

// Инициализация формы контактов
document.addEventListener('DOMContentLoaded', () => {
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', handleContactFormSubmit);
  }
});