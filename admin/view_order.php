<?php

// С помощью этого сценария администратор может просматривать выбранный заказ
// Администратор также может маркировать доставленные позиции заказа
// Этот сценарий создан в главе 11

// Чтобы контролировать отображение сообщений об ошибках, перед выполнением любого кода PHP нужно подключить файл конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и подключение заголовка
$page_title = 'Просмотр заказа';
include('./includes/header.html');
// Файл заголовка открывает сеанс

// Верификация кода заказа
$order_id = false;
if (isset($_GET['oid']) && (filter_var($_GET['oid'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) { 
	$order_id = $_GET['oid'];
	// значение oid попадает сюда из файла view_orders.php
	$_SESSION['order_id'] = $order_id;
	// помещаем в сессию это значение
} elseif (isset($_SESSION['order_id']) && (filter_var($_SESSION['order_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) 
	// если это значение уже присутствовало в сессии, то ..
{
	$order_id = $_SESSION['order_id'];
	// этой переменной присваиваем значение из значения сессии
}

// Остановить выполнение сценария, если отсутствует идентификатор $order_id
if (!$order_id) {
	echo '<h3>Ошибка!</h3><p>Ошибка при доступе к странице.</p>';
	include('./includes/footer.html');
	exit();
}

// Выполняется подключение к базе данных
require(MYSQL);

// ------------------------
// Обработка платежа!

// Проверка передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {	
	
	// Требуется обработка платежа, запись транзакции, обновление таблицы order_contents, обновление запасов и текстовый отчет о результатах
	
	// Получение сведений о заказе
	$q = "SELECT customer_id, total, transaction_id FROM orders AS o 
	JOIN transactions AS t ON (o.id=t.order_id AND t.type='auth_only' AND t.response_code=1) 
	WHERE o.id=$order_id";
	// Значения auth_only и response_code=1 получаются (после запуска billing.php) только при удачной транзакции. Статус auth_only может быть получен заказом лишь один раз.
	$r = mysqli_query($dbc, $q);

	if (mysqli_num_rows($r) === 1) {
		
		// Получить возвращенные значения
		// Результат запроса помещаем в числовой массив и последовательно присваиваем значения из этого массива переменным, которым захотим.
		list($customer_id, $order_total, $trans_id) = mysqli_fetch_array($r, MYSQLI_NUM);
		
		// Проверить, будет ли сумма по заказу больше нуля
		if ($order_total > 0) {
	
			// Создание запроса к платежному шлюзу
			require('../includes/vendor/anet_php_sdk/AuthorizeNet.php');
			$aim = new AuthorizeNetAIM(API_LOGIN_ID, TRANSACTION_KEY);

			// Захват
			$response = $aim->priorAuthCapture($trans_id, $order_total/100);
			// в книге не было конкатенации с 1 ($trans_id . 1,...). Внимание ! Из-за этого была ошибка в онлайне.
			
			// Добавление символов косой черты к двум текстовым значениям
			$reason = addslashes($response->response_reason_text);
			$full_response = addslashes($response->response);

			// Запись транзакции
			$r = mysqli_query($dbc, "CALL add_transaction($order_id, '{$response->transaction_type}', $order_total, {$response->response_code}, '$reason', {$response->transaction_id}, '$full_response')");				
			
			// Обновляйте заказ и запасы, если достигнут успех (approved)
			if ($response->approved) {
				
				$message = 'Платеж успешно проведен. Заказ будет доставлен.';
				
				// Обновление содержимого заказа
				$q = "UPDATE order_contents SET ship_date=NOW() WHERE order_id=$order_id";
				$r = mysqli_query($dbc, $q);
	
				// Обновление запасов...
				$q = 'UPDATE specific_coffees AS sc, order_contents AS oc SET sc.stock=sc.stock-oc.quantity WHERE sc.id=oc.product_id AND oc.product_type="coffee" AND oc.order_id=' . $order_id;
				$r = mysqli_query($dbc, $q);
				$q = 'UPDATE non_coffee_products AS ncp, order_contents AS oc SET ncp.stock=ncp.stock-oc.quantity WHERE ncp.id=oc.product_id AND oc.product_type="goodies" AND oc.order_id=' . $order_id;
				$r = mysqli_query($dbc, $q);
								
			} else { // Если запрос платежа завершился неудачно, то генерируется сообщение об ошибке:
			
				$error = 'Платеж не может быть обработан, поскольку: ' . $response->response_reason_text;
				// переменная response_reason_text будет содержать значение независимо от того, была успешной транзакция или нет; в ней будет тот или иной текст.
			} // завершение конструкции IF-ELSE, используемой для обработки отклика на платеж
			
		} else { // Если сумма по заказу не больше 0, то

				$error = "Сумма по заказу (\$$order_total) некорректна.";

		} // завершение контрукции IF-ELSE, используемой для обработки $order_total > 0	
		
	} else { // если отсутствует сведения о заказе
		
		$error = 'Не найден соответстующий заказ.';
		
	} // завершение блока IF-ELSE, применяемого для обработки идентификатора транзакции
	
		// Отчет, включающий сообщения и сведения об ошибках
	echo '<h3>Результаты доставки заказа</h3>';
	if (isset($message)) echo "<p>$message</p>";
	if (isset($error)) echo "<p class=\"error\">$error</p>";
			
} // Конец IF SERVER POST

// Приведенный выше код является частью кода обработки платежей
// ------------------------

// Определение запроса
$q = 'SELECT FORMAT(total/100, 2) AS total, FORMAT(shipping/100,2) AS shipping, credit_card_number, DATE_FORMAT(order_date, "%a %b %e, %Y at %h:%i%p") AS od, email, CONCAT(last_name, ", ", first_name) AS name, CONCAT_WS(" ", address1, address2, city, state, zip) AS address, phone, customer_id, CONCAT_WS(" - ", ncc.category, ncp.name) AS item, ncp.stock, quantity, FORMAT(price_per/100,2) AS price_per, DATE_FORMAT(ship_date, "%b %e, %Y") AS sd 
FROM orders AS o 
INNER JOIN customers AS c ON (o.customer_id = c.id) 
INNER JOIN order_contents AS oc ON (oc.order_id = o.id) 
INNER JOIN non_coffee_products AS ncp ON (oc.product_id = ncp.id AND oc.product_type="goodies") 
INNER JOIN non_coffee_categories AS ncc ON (ncc.id = ncp.non_coffee_category_id) 
WHERE o.id=' . $order_id . '
UNION 
SELECT FORMAT(total/100, 2), FORMAT(shipping/100,2), credit_card_number, DATE_FORMAT(order_date, "%a %b %e, %Y at %l:%i%p"), email, CONCAT(last_name, ", ", first_name), CONCAT_WS(" ", address1, address2, city, state, zip), phone, customer_id, CONCAT_WS(" - ", gc.category, s.size, sc.caf_decaf, sc.ground_whole) AS item, sc.stock, quantity, FORMAT(price_per/100,2), DATE_FORMAT(ship_date, "%b %e, %Y") 
FROM orders AS o 
INNER JOIN customers AS c ON (o.customer_id = c.id) 
INNER JOIN order_contents AS oc ON (oc.order_id = o.id) 
INNER JOIN specific_coffees AS sc ON (oc.product_id = sc.id AND oc.product_type="coffee") 
INNER JOIN sizes AS s ON (s.id=sc.size_id) 
INNER JOIN general_coffees AS gc ON (gc.id=sc.general_coffee_id) 
WHERE o.id=' . $order_id;

// И как обычно, нужно быть внимательным к выбранным сокращениям в запросе и наименованиям столбцов в базе данных. Например, в таблице sizes есть столбец size (т.е. после сокращения в запросе  "sizes AS s" правильно использовать s.size), а в таблице specific_coffees присутствует столбец size_id (т.е. после сокращения "specific_coffees AS sc" правильно будет sс.size_id) 

// Вызов запроса
$r = mysqli_query($dbc, $q);
if (mysqli_num_rows($r) > 0) { // отображение сведений о заказе

	echo '<h3>Просмотр заказа</h3>
	<form action="view_order.php" method="post" accept-charset="utf-8">
		<fieldset>';

	//  Чтобы отобразить сначала общую информацию о заказе и покупателе (однократно), выбирается первая возвращенная строка, находящаяся вне области действия какого-либо цикла:
	$row = mysqli_fetch_array($r, MYSQLI_ASSOC);

	// Отображение сведений о заказе и заказчике:
	echo '<p><strong>Код заказа</strong>: ' . $order_id . '<br />
	<strong>Итог</strong>: $' . $row['total'] . '<br />
	<strong>Поставка</strong>: $' . $row['shipping'] . '<br />
	<strong>Дата заказа</strong>: ' . $row['od'] . '<br />
	<strong>Имя заказчика</strong>: ' . htmlspecialchars($row['name']) . '<br />
	<strong>Адрес заказчика</strong>: ' . htmlspecialchars($row['address']) . '<br />
	<strong>Электронный адрес заказчика</strong>: ' . htmlspecialchars($row['email']) . '<br />
	<strong>Телефон заказчика</strong>: ' . htmlspecialchars($row['phone']) . '<br />
	<strong>Номер используемой кредитной карты</strong>: *' . $row['credit_card_number'] . '</p>';

	// Создание таблицы
	echo '<table border="0" width="100%" cellspacing="8" cellpadding="6">
	<thead>
		<tr>
	    <th align="center">Товар</th>
	    <th align="right">Оплаченная сумма</th>
	    <th align="center">Кол-во товаров на складе</th>
	    <th align="center">Кол-во заказанных товаров</th>
	    <th align="center">Доставлено?</th>
	  </tr>
	</thead>
	<tbody>';

	// Создайте переменную-флаг, используемую для отслеживания доставки заказа
	$shipped = true;
	
	// Поскольку одна  строка уже  выбрана, для  обхода  оставшихся  результатов запроса применяется локальный цикл do...while.
	// Вывод каждой позиции
	do {
		
		// Создание строки
		echo '<tr>
		    <td align="left">' . $row['item'] . '</td>
		    <td align="right">' . $row['price_per'] . '</td>
		    <td align="center">' . $row['stock'] . '</td>
		    <td align="center">' . $row['quantity'] . '</td>
		    <td align="center">' . $row['sd'] . '</td>
		</tr>';
		
		if (!$row['sd']) $shipped = false;
						
	} while ($row = mysqli_fetch_array($r));
	// Пока может быть найден следующий массив, цикл do...while повторяется снова.
	
	// Завершение создания таблицы и формы
	echo '</tbody></table>';

	// Отображать кнопку отправки заказа только в том случае, если заказ еще не доставлен
	// (т.е. если $shipped = false)
	if (!$shipped) {
		echo '<div class="field"><p class="error">Обратите внимание, что реальные платежи будут проводиться только после щелчка на этой кнопке!</p><input type="submit" value="Доставить заказ" class="button" /></div>';	
	}
		
	// Завершение создания формы
	echo '</fieldset>
	</form>';

} else { // если после большого заопроса отсутствуют возвращенные записи, то...
	echo '<h3>Error!</h3><p>Ошибка при доступе к этой странице.</p>';
	include('./includes/footer.html');
	exit();	
}

include('./includes/footer.html');
?>