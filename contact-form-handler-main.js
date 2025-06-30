document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('contact-form');

  if (!form) return;

  const nameInput = form.querySelector('#name');
  const phoneInput = form.querySelector('#phone');
  const emailInput = form.querySelector('#email');
  const messageInput = form.querySelector('#message');
  const errorMessages = form.querySelectorAll('.input-error');

  // Маска телефона
  const phoneMask = IMask(phoneInput, {
    mask: '+{7} (000) 000-00-00'
  });

  // Проверка email
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Проверка полей
  function validateForm() {
    let isValid = true;

    errorMessages.forEach(msg => msg.hidden = true);

    if (!nameInput.value.trim()) {
      showError(nameInput, 'Пожалуйста, введите имя');
      isValid = false;
    }

    if (!phoneInput.value.trim() || phoneMask.unmaskedValue.length !== 11) {
      showError(phoneInput, 'Введите корректный номер телефона');
      isValid = false;
    }

    if (emailInput.value && !isValidEmail(emailInput.value)) {
      showError(emailInput, 'Введите корректный email');
      isValid = false;
    }

    return isValid;
  }

  function showError(inputEl, message) {
    const group = inputEl.closest('.form-group');
    const error = group.querySelector('.input-error');
    if (error) {
      error.textContent = message;
      error.hidden = false;
    }
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (!validateForm()) return;

    const formData = new FormData(form);

    try {
      const response = await fetch('contact-form-handler-main.php', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        form.reset();
        alert('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.');
      } else {
        alert('Произошла ошибка при отправке. Пожалуйста, попробуйте позже.');
      }
    } catch (error) {
      alert('Не удалось отправить заявку. Проверьте подключение к интернету.');
      console.error(error);
    }
  });
});
