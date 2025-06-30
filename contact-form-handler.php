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
$address  = trim($_POST['address'] ?? '');
$delivery = trim($_POST['delivery'] ?? '');
$comment  = trim($_POST['comment'] ?? '');
$deliveryCost = trim($_POST['delivery_cost'] ?? '0');
$total    = trim($_POST['order_total'] ?? '0');
$cartJson = trim($_POST['cart_json'] ?? '');

// Парсинг корзины
$cartItems = json_decode($cartJson, true);
$cartListForEmail = '';
if (is_array($cartItems)) {
    foreach ($cartItems as $item) {
        $color = !empty($item['color']) ? ($item['color'] === 'custom' ? 'на заказ' : 'RAL ' . htmlspecialchars($item['color'])) : 'без цвета';
        $cartListForEmail .= "
          <tr>
            <td>{$item['name']}</td>
            <td>{$item['quantity']}</td>
            <td>" . number_format($item['price'], 0, '', ' ') . " руб.</td>
            <td>" . number_format($item['price'] * $item['quantity'], 0, '', ' ') . " руб.</td>
            <td>{$color}</td>
          </tr>";
    }
}

// Простая серверная проверка
$errors = [];
if ($name === '') $errors['name'] = 'Имя обязательно';
if ($phone === '') $errors['phone'] = 'Телефон обязателен';
$cleanPhone = preg_replace('/\D/', '', $phone);
if (strlen($cleanPhone) < 10) $errors['phone'] = 'Некорректный номер телефона';
if ($address === '') $errors['address'] = 'Адрес обязателен';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Некорректный email';
}
if (!empty($errors)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'errors' => $errors]);
    } else {
        echo implode('<br>', array_values($errors));
    }
    exit;
}

// Расшифровка способа доставки
$deliveryMethods = [
    'moscow' => 'Доставка по Москве – 500 руб.',
    'mo_10km' => 'Доставка до 10 км – 800 руб.',
    'mo_30km' => 'Доставка до 30 км – 1500 руб.',
    'sdek' => 'СДЭК – расчет при звонке'
];
$deliveryName = $deliveryMethods[$delivery] ?? 'Не указан';

// HTML-письмо администратору
$adminSubject = "Новый заказ с сайта AluLuxe";
$adminEmail = "der-ok@mail.ru";
$adminHeaders = "MIME-Version: 1.0\r\n";
$adminHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
$adminHeaders .= "From: AluLuxe <no-reply@AluLuxe.ru>\r\n";
$adminMessage = '
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .email-wrapper { max-width: 700px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 30px; }
  .email-header { border-bottom: 2px solid #8b7755; padding-bottom: 20px; margin-bottom: 30px; }
  .email-header h2 { color: #8b7755; font-size: 24px; margin: 0; }
  .order-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .order-table th, .order-table td { padding: 12px 15px; border: 1px solid #eee; text-align: left; }
  .order-table th { background: #fafafa; color: #333; font-weight: bold; }
  .summary-row { font-weight: bold; border-top: 2px solid #eee; }
  .summary-row td { padding-top: 15px; padding-bottom: 15px; }
  .info-box { background: #f5f5f5; padding: 15px; border-left: 4px solid #8b7755; border-radius: 6px; margin-top: 30px; }
  .footer-note { font-size: 0.8rem; color: #666; margin-top: 40px; border-top: 1px solid #eee; padding-top: 15px; }
</style>
<div class="email-wrapper">
  <div class="email-header">
    <h2>Новый заказ с сайта AluLuxe</h2>
    <p style="margin: 5px 0 0;">Получено: ' . date('d.m.Y H:i') . '</p>
  </div>
  <table class="order-table">
    <thead>
      <tr>
        <th>Товар</th>
        <th>Кол-во</th>
        <th>Цена</th>
        <th>Сумма</th>
        <th>Цвет</th>
      </tr>
    </thead>
    <tbody>' . $cartListForEmail . '</tbody>
    <tfoot>
      <tr class="summary-row">
        <td colspan="5">' . htmlspecialchars($deliveryName) . '</td>
      </tr>
      <tr class="summary-row">
        <td colspan="5"><strong>Общая сумма:</strong> ' . number_format($total, 0, '', ' ') . ' руб.</td>
      </tr>
    </tfoot>
  </table>
  <div class="info-box">
    <strong>Имя:</strong> ' . htmlspecialchars($name) . '<br>
    <strong>Телефон:</strong> ' . htmlspecialchars($phone) . '<br>
    <strong>Email:</strong> ' . htmlspecialchars($email) . '<br>
    <strong>Адрес:</strong> ' . htmlspecialchars($address) . '<br>
    <strong>Комментарий:</strong> ' . nl2br(htmlspecialchars($comment)) . '
  </div>
  <div class="footer-note">
    Это сообщение отправлено через сайт AluLuxe.<br>
    Пожалуйста, свяжитесь с клиентом для подтверждения заказа.
  </div>
</div>
';

$sentToAdmin = mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);

// Если указан email клиента — отправляем ему копию
$sentToClient = true;
if (!empty($email)) {
    $clientSubject = "Ваш заказ принят – AluLuxe";
    $clientHeaders = "MIME-Version: 1.0\r\n";
    $clientHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $clientHeaders .= "From: AluLuxe <no-reply@AluLuxe.ru>\r\n";

    $clientMessage = '
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
  <tr>
    <td align="center">
      <table width="100%" max-width="600" border="0" cellspacing="0" cellpadding="20" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin: auto;">
        <tr>
          <td align="center">
            <h2 style="color: #8b7755; font-size: 24px; margin: 0;">Ваш заказ принят!</h2>
            <p style="margin-top: 10px; color: #333;">Здравствуйте, ' . htmlspecialchars($name) . '!<br><br>
              Благодарим вас за заказ. Мы получили ваше сообщение и свяжемся с вами в ближайшее время.</p>
          </td>
        </tr>
        <!-- Заказ -->
        <tr>
          <td style="padding-top: 20px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="10" style="border-collapse: collapse;">
              <thead>
                <tr style="background-color: #fafafa;">
                  <th style="text-align:left; color:#333; font-weight:bold; padding:10px 15px; border-bottom:1px solid #eee;">Товар</th>
                  <th style="text-align:center; color:#333; font-weight:bold; padding:10px 15px; border-bottom:1px solid #eee;">Кол-во</th>
                  <th style="text-align:right; color:#333; font-weight:bold; padding:10px 15px; border-bottom:1px solid #eee;">Сумма</th>
                  <th style="text-align:right; color:#333; font-weight:bold; padding:10px 15px; border-bottom:1px solid #eee;">Цвет</th>
                </tr>
              </thead>
              <tbody>';

if (is_array($cartItems)) {
    foreach ($cartItems as $item) {
        $sum = number_format($item['price'] * $item['quantity'], 0, '', ' ');
        $color = !empty($item['color']) ? ($item['color'] === 'custom' ? 'на заказ' : 'RAL ' . htmlspecialchars($item['color'])) : 'без цвета';
        $clientMessage .= "
          <tr>
            <td style=\"padding: 10px 15px; border-bottom:1px solid #eee; font-weight:bold;\">{$item['name']}</td>
            <td style=\"padding: 10px 15px; border-bottom:1px solid #eee; text-align:center;\">{$item['quantity']} шт.</td>
            <td style=\"padding: 10px 15px; border-bottom:1px solid #eee; text-align:right;\">$sum руб.</td>
            <td style=\"padding: 10px 15px; border-bottom:1px solid #eee; text-align:right;\">$color</td>
          </tr>
        ";
    }
}

$clientMessage .= '
              <tr>
                <td colspan="4" style="padding: 20px 15px 0; font-size:16px; font-weight:bold; text-align:right;">
                  ' . htmlspecialchars($deliveryName) . '
                </td>
              </tr>
              <tr>
                <td colspan="4" style="padding: 10px 15px; font-size:16px; font-weight:bold; text-align:right; border-top:2px solid #eee; border-bottom:2px solid #eee;">
                  Итого: ' . number_format($total, 0, '', ' ') . ' руб.
                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
      <tr>
        <td style="padding-top: 30px; text-align: center;">
          <p style="margin: 0 0 20px; color: #555;">С уважением,<br><strong>Команда AluLuxe</strong></p>
          <div style="font-size:0.9rem; color:#777; border-top:1px solid #eee; padding-top:15px; margin-top:25px;">
            Это автоматическое сообщение. Пожалуйста, не отвечайте на него.
          </div>
        </td>
      </tr>
    </table>
  </td>
</tr>
</table>
';
    $sentToClient = mail($email, $clientSubject, $clientMessage, $clientHeaders);
}

// --- Логирование заказа в CSV ---
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/orders.csv';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$flatItems = [];
if (is_array($cartItems)) {
    foreach ($cartItems as $item) {
        $color = !empty($item['color']) ? ($item['color'] === 'custom' ? 'на заказ' : 'RAL ' . $item['color']) : 'без цвета';
        $flatItems[] = "{$item['name']} × {$item['quantity']} (Цвет: {$color})";
    }
}
$productsStr = implode(' | ', $flatItems);

$fp = fopen($logFile, 'a');
if ($fp) {
    if (filesize($logFile) === 0) {
        fputcsv($fp, ['Дата','Имя','Телефон','Email','Адрес','Доставка','Сумма','Комментарий','Товары'], ';', '"', '"');
    }
    fputcsv($fp, [
        date('d.m.Y H:i'),
        $name,
        $phone,
        $email,
        $address,
        $deliveryName,
        number_format($total, 0, '', ' '),
        $comment ?: '-',
        $productsStr
    ], ';', '"', '"');
    fclose($fp);
}

$content = file_get_contents($logFile);
file_put_contents($logFile, "\xEF\xBB\xBF" . $content);

// Редирект
if ($sentToAdmin && $sentToClient) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
    } else {
        echo '
        <script>
          localStorage.removeItem("cart");
          window.location.href = "order-success.html";
        </script>
        ';
        exit;
    }
} else if (!$sentToClient) {
    echo json_encode(['success' => false, 'errors' => ['email' => 'Ошибка отправки письма клиенту']]);
} else {
    echo json_encode(['success' => false, 'errors' => ['general' => 'Не удалось отправить форму']]);
}
?>