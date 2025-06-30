<?php
// Подключаем конфигурацию
require_once __DIR__ . '/../../config.php'; // Путь должен указывать на файл вне веб-директории

// Простая авторизация
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== ADMIN_LOGIN || 
    !password_verify($_SERVER['PHP_AUTH_PW'], ADMIN_PASSWORD_HASH)) {
    
    header('WWW-Authenticate: Basic realm="Log AluLux"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<p style="font-family:sans-serif; padding:20px;">Доступ запрещён</p>';
    exit;
}

// --- Чтение файла ---
$logFile = __DIR__ . '/logs/orders.csv';

echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Лог заказов | AluLuxe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css"> <!-- Общий CSS сайта -->
</head>
<body class="admin-page"> <!-- Уникальный класс для админки -->
  <h1>Лог заказов AluLuxe</h1>

  <!-- Поиск -->
  <div class="search-box">
    <input type="text" id="globalSearch" placeholder="Поиск по всем полям...">
  </div>

  <!-- Таблица -->
  <table id="orders-table">
    <thead>
      <tr>
        <th data-column="date">Дата</th>
        <th data-column="name">Имя</th>
        <th data-column="phone">Телефон</th>
        <th data-column="email">Email</th>
        <th data-column="address">Адрес</th>
        <th data-column="delivery">Доставка</th>
        <th data-column="total">Сумма</th>
        <th data-column="comment">Комментарий</th>
        <th data-column="products">Товары</th>
      </tr>
    </thead>
    <tbody id="orders-body">
HTML;

if (!file_exists($logFile) || filesize($logFile) === 0) {
    echo '</tbody></table><div class="no-data">Заказов пока нет.</div></body></html>';
    exit;
}

$handle = fopen($logFile, 'r');

if (!$handle) {
    die('<div class="no-data">Не удалось открыть файл заказов</div></body></html>');
}

$headersSkipped = false;

while (($row = fgetcsv($handle, 10000, ";")) !== false) {
    if (!$headersSkipped) {
        $headersSkipped = true;
        continue;
    }

    if (count($row) < 9) continue;

    echo "<tr>";
    foreach ($row as $i => $col) {
        $class = $i === 7 ? 'comment-cell' : '';
        echo "<td class=\"$class\">" . htmlspecialchars($col) . "</td>";
    }
    echo "</tr>";
}

fclose($handle);

echo <<<HTML
    </tbody>
  </table>

  <script>
    // --- Фильтрация по одному полю ---
    const searchInput = document.getElementById('globalSearch');
    const rows = Array.from(document.querySelectorAll('#orders-body tr'));

    function filterTable() {
      const query = searchInput.value.trim().toLowerCase();
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let match = false;
        cells.forEach(cell => {
          if (cell.textContent.toLowerCase().includes(query)) match = true;
        });
        row.style.display = match ? '' : 'none';
      });
    }

    searchInput.addEventListener('input', filterTable);

    // --- Парсинг даты в формате "дд.мм.гггг чч:мм" ---
    function parseCustomDate(str) {
      if (!str || typeof str !== 'string') return new Date(0);
      
      const [datePart, timePart] = str.split(' ');
      const [day, month, year] = (datePart || '01.01.1970').split('.');
      const [hours, minutes] = (timePart || '00:00').split(':');
      
      return new Date(
        parseInt(year),
        parseInt(month) - 1, // Месяцы в JS с 0
        parseInt(day),
        parseInt(hours),
        parseInt(minutes)
      );
    }

    // --- Сортировка по дате при загрузке ---
    function applyInitialSort() {
      const dateHeader = document.querySelector('th[data-column="date"]');
      if (!dateHeader) return;

      // Установим начальную конфигурацию
      sortColumnIndex = 0;
      sortDirection = -1; // Сортировка по убыванию (свежие сверху)
      
      // Добавим класс сортировки
      document.querySelectorAll('th').forEach(h => 
        h.classList.remove('sorted-asc', 'sorted-desc')
      );
      dateHeader.classList.add('sorted-desc');
      
      // Выполним сортировку
      const sortedRows = [...rows].sort((a, b) => {
        const aText = a.children[0]?.textContent.trim();
        const bText = b.children[0]?.textContent.trim();
        const aDate = parseCustomDate(aText);
        const bDate = parseCustomDate(bText);
        return (aDate - bDate) * sortDirection;
      });

      // Обновим таблицу
      const tbody = document.getElementById('orders-body');
      if (tbody) {
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
      }
    }

    // --- Сортировка по клику на заголовке ---
    const headers = document.querySelectorAll('th[data-column]');
    let sortColumnIndex = null;
    let sortDirection = -1;

    headers.forEach((header, index) => {
      header.classList.add('sortable');
      header.addEventListener('click', () => {
        if (sortColumnIndex === index) {
          sortDirection *= -1;
        } else {
          sortColumnIndex = index;
          sortDirection = -1;
        }

        // Удаляем классы сортировки
        document.querySelectorAll('th').forEach(h => {
          h.classList.remove('sorted-asc', 'sorted-desc');
        });

        // Добавляем нужный класс
        header.classList.add(sortDirection === 1 ? 'sorted-asc' : 'sorted-desc');

        // Сортировка
        const sortedRows = [...rows].sort((a, b) => {
          const aCell = a.children[sortColumnIndex];
          const bCell = b.children[sortColumnIndex];

          const aText = aCell ? aCell.textContent.trim() : '';
          const bText = bCell ? bCell.textContent.trim() : '';

          // Если это столбец даты
          if (header.dataset.column === 'date' || sortColumnIndex === 0) {
            const aDate = parseCustomDate(aText);
            const bDate = parseCustomDate(bText);
            return (aDate - bDate) * sortDirection;
          }

          // Если это числовое поле
          const aNum = parseFloat(aText.replace(/[^\\d.-]/g, ''));
          const bNum = parseFloat(bText.replace(/[^\\d.-]/g, ''));
          if (!isNaN(aNum) && !isNaN(bNum)) {
            return (aNum - bNum) * sortDirection;
          }

          // Иначе сравниваем как строки
          return aText.localeCompare(bText, undefined, { numeric: true }) * sortDirection;
        });

        const tbody = document.getElementById('orders-body');
        if (tbody) {
          tbody.innerHTML = '';
          sortedRows.forEach(row => tbody.appendChild(row));
        }
      });
    });

    // Вызовем начальную сортировку после загрузки страницы
    window.addEventListener('DOMContentLoaded', applyInitialSort);
  </script>
</body>
</html>
HTML;