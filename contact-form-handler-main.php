<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Защита от спам-ботов
if (!empty($_POST['honeypot']) || empty($_POST['name'])) {
    http_response_code(400);
    echo 'Неверный запрос';
    exit;
}

// Получение данных из формы
$name     = trim($_POST['name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$email    = trim($_POST['email'] ?? '');
$message  = trim($_POST['message'] ?? '');

// Простая серверная проверка
$errors = [];

if ($name === '') {
    $errors[] = 'Имя обязательно';
}
if ($phone === '') {
    $errors[] = 'Телефон обязателен';
}

$cleanPhone = preg_replace('/\D/', '', $phone);
if (strlen($cleanPhone) < 10) {
    $errors[] = 'Некорректный формат телефона';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email';
}

if (!empty($errors)) {
    http_response_code(400);
    echo implode('<br>', $errors);
    exit;
}

// Настройки почты для администратора
$adminEmail = "der-ok@mail.ru"; // Ваш email
$adminSubject = "Новое сообщение с сайта AluLux";
$adminHeaders = "MIME-Version: 1.0\r\n";
$adminHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
$adminHeaders .= "From: AluLux <no-reply@alulux.ru>\r\n";

// Письмо администратору
$adminMessage = "
  <style>
    .email-container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
    .email-section {
      background: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .email-section h2 { color: #333; margin-bottom: 20px; }
    .email-section p { margin: 10px 0; line-height: 1.5; }
    .email-section strong { color: #2c3e50; }
    hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
  </style>
  <div class='email-container'>
    <div class='email-section'>
      <h2>Сообщение с сайта AluLux</h2>
      <p><strong>Имя:</strong> $name</p>
      <p><strong>Телефон:</strong> $phone</p>
      <p><strong>Email:</strong> $email</p>
      <p><strong>Сообщение:</strong> " . nl2br(htmlspecialchars($message)) . "</p>
    </div>
  </div>
";

$sentToAdmin = mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);

// Если указан email клиента — отправляем ему подтверждение
$sentToClient = true;

if (!empty($email)) {
    $clientSubject = "Ваш запрос принят – AluLux";
    $clientHeaders = "MIME-Version: 1.0\r\n";
    $clientHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $clientHeaders .= "From: AluLux <no-reply@alulux.ru>\r\n";

    $clientMessage = "
      <style>
        .email-container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
        .email-section {
          background: #ffffff;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-section p { margin: 10px 0; line-height: 1.5; }
        .email-section strong { color: #2c3e50; }
      </style>
      <div class='email-container'>
        <div class='email-section'>
          <p>Здравствуйте, $name!</p>
          <p>Ваше сообщение успешно получено. Мы свяжемся с вами в ближайшее время.</p>
          <p>С уважением,<br><strong>Команда AluLux</strong></p>
        </div>
      </div>
    ";

    $sentToClient = mail($email, $clientSubject, $clientMessage, $clientHeaders);
}



// Возвращаем результат клиенту
if ($sentToAdmin && $sentToClient) {
    http_response_code(200);
    echo 'Форма успешно отправлена!';
} else if (!$sentToClient) {
    http_response_code(500);
    echo 'Ошибка отправки письма клиенту';
} else {
    http_response_code(500);
    echo 'Не удалось отправить форму';
}
?>