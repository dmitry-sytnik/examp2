<?php

// С помощью этого сценария администратор может просматривать каждый заказ
// Сценарий создан в главе 11

// Чтобы управлять отображением сообщений об ошибках, перед выполнением любого кода PHP требуется подключение файла конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и подключение заголовка
$page_title = 'Просмотр всех заказов';
include('./includes/header.html');
// Этот файл заголовка открывает сеанс

// Выполняется подключение к базе данных:
require(MYSQL);

echo '<h3>Просмотр заказов</h3>
<table border="0" width="100%" cellspacing="4" cellpadding="4" id="orders">
<thead>
  <tr>
    <th align="center">Код заказа</th>
    <th align="center">Итоговая сумма</th>
    <th align="right">Имя заказчика</th>
    <th align="right">Город</th>
    <th align="center">Штат</th>
    <th align="center">Почтовый индекс</th>
    <th align="center">Осталось поставить</th>
  </tr>
</thead>
<tbody>';

// Создание запроса
$q = 'SELECT o.id, FORMAT(total/100, 2) AS total, c.id AS cid, CONCAT(last_name, ", ", first_name) AS name, city, state, zip, COUNT(oc.id) AS items 
FROM orders AS o 
LEFT OUTER JOIN order_contents AS oc 
ON (oc.order_id=o.id AND oc.ship_date IS NULL) 
JOIN customers AS c 
ON (o.customer_id = c.id) 
JOIN transactions AS t 
ON (t.order_id=o.id AND t.response_code=1) 
GROUP BY o.id DESC';

$r = mysqli_query($dbc, $q);
while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
	echo '<tr>
    <td align="center"><a href="view_order.php?oid=' . $row['id'] . '">' . $row['id'] . '</a></td>
    <td align="center">$' . $row['total'] .'</td>
    <td align="right"><a href="view_customer.php?cid=' . $row['cid'] . '">' . htmlspecialchars( $row['name']) .'</a></td>
    <td align="right">' . htmlspecialchars($row['city']) . '</td>
    <td align="center">' . $row['state'] .'</td>
    <td align="center">' . $row['zip'] .'</td>
    <td align="center">' . $row['items'] .'</td>
  </tr>';
  // Сценарий view_customer.php не приводится в книге, но при необходимости вы сможете легко написать его самостоятельно
}

echo '</tbody></table>';

?>

<!-- скрипты пропускаю -->

<?php
// Включение файла футера, завершающего шаблон
include('./includes/footer.html');
?>