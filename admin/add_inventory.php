<?php

// С помощью этого файла администратор может пополнять складские запасы
// Этот сценарий создан в главе 11

// Чтобы управлять отображением сообщений об ошибках, перед выполнением любого кода PHP требуется подключение файла конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и включение заголовка
$page_title = 'Пополнение запасов';
include('./includes/header.html');
// Файл заголовка начинает сеанс

// Выполняется подключение к базе данных
require(MYSQL);

// Проверка передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {	

	// Проверка добавленных запасов
	if (isset($_POST['add']) && is_array($_POST['add'])) {
		
		// Выполняется подключение сценария, выполняющего обработку товаров
		//  Сценарий будет использовать функцию parse_sku(), заданную в этом файле
		require('../includes/product_functions.inc.php');
		
		// Определение двух запросов
		$q1 = 'UPDATE specific_coffees SET stock=stock+? WHERE id=?';
		$q2 = 'UPDATE non_coffee_products SET stock=stock+? WHERE id=?';

		// Подготовка инструкций
		$stmt1 = mysqli_prepare($dbc, $q1);
		$stmt2 = mysqli_prepare($dbc, $q2);
		
		// Привязка переменных
		mysqli_stmt_bind_param($stmt1, 'ii', $qty, $id);
		mysqli_stmt_bind_param($stmt2, 'ii', $qty, $id);
		
		// Инициализация переменной, которая будет подсчитывать количество затронутых строк
		$affected = 0;
		
		// Циклический просмотр каждого переданного значения
		foreach ($_POST['add'] as $sku => $qty) {
		
			// Верификация добавленного количества товаров
			// Количество добавленных товаров должно быть целым числом, которое будет больше либо равно 1
			if (filter_var($qty, FILTER_VALIDATE_INT, array('min_range' => 1))) {
				
				// Анализ SKU
				// Наша функция parse_sku разбивает совмещенное значение, например, С23, на С и 23. А встроенной функцией PHP list() эти значения присваиваются переменным type и id. 
				list($type, $id) = parse_sku($sku);
				
				// Идентификация запроса, вызываемого на основании данного типа
				if ($type === 'coffee') {
					// Вызов запроса
					mysqli_stmt_execute($stmt1);
					
					// Добавление затронутых строк
					$affected += mysqli_stmt_affected_rows($stmt1);				

				} elseif ($type === 'goodies') {
					// Выполнение запроса
					mysqli_stmt_execute($stmt2);
					
					// Добавление затронутых строк
					$affected += mysqli_stmt_affected_rows($stmt2);				

				}
				
			} // конец блока IF
				
		} // конец цикла FOREACH
		
		// Печать сообщения
		echo "<h4>$affected позиции были обновлены!</h4>";

	} // завершение конструкции $_POST['add'] IF

} // завершение блока IF SERVER POST, управляюшего передачей данных

?><h3>Пополнение запасов</h3>

<form action="add_inventory.php" method="post" accept-charset="utf-8">

	<fieldset><legend>Дополнительное количество товаров, добавляемых в запасы.</legend>
	
		<table border="0" width="100%" cellspacing="4" cellpadding="4">
		<thead>
			<tr>
		    <th align="right">Товар</th>
		    <th align="right">Обычная цена</th>
		    <th align="right">Кол-во на складе</th>
		    <th align="center">Добавить</th>
		  </tr></thead>
		<tbody>		
		<?php
		
		// Выборка каждого товара
		$q = '(SELECT CONCAT("G", ncp.id) AS sku, ncc.category, ncp.name, FORMAT(ncp.price/100, 2) AS price, ncp.stock FROM non_coffee_products AS ncp 
				INNER JOIN non_coffee_categories AS ncc ON ncc.id=ncp.non_coffee_category_id ORDER BY category, name) 
			UNION				
				(SELECT CONCAT("C", sc.id), gc.category, CONCAT_WS(" - ", s.size, sc.caf_decaf, sc.ground_whole),FORMAT(sc.price/100, 2), sc.stock FROM specific_coffees AS sc INNER JOIN sizes AS s ON s.id=sc.size_id 
				INNER JOIN general_coffees AS gc ON gc.id=sc.general_coffee_id ORDER BY sc.general_coffee_id, sc.size, sc.caf_decaf, sc.ground_whole)';
			// Вот здесь в конце по-хорошему вместо sc.size должно быть s.size согласно указанному выше CONCAT_WS(" - ", s.size. Это так же объясняется тем, что в таблице sizes есть столбец size (т.е. после сокращения в запросе  "sizes AS s" правильно использовать s.size), а в таблице specific_coffees присутствует столбец size_id (т.е. после сокращения "specific_coffees AS sc" правильно будет sс.size_id). В онлайне работает только после исправления этой ошибки (Онлайн тип сервера MariaDB 10.2.12, в оффлайне MySQL 5.6.38).	
		$r = mysqli_query($dbc, $q);
		
		// Отображение элементов формы для каждого товара
		while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
			echo '<tr>
		    <td align="right">' . htmlspecialchars($row['category']) . '::' . htmlspecialchars($row['name']) . '</td>
		    <td align="center">' . $row['price'] .'</td>
		    <td align="center">' . $row['stock'] .'</td>
		    <td align="center"><input type="text" name="add[' . $row['sku'] . ']"  id="add[' . $row['sku'] . ']" size="5" class="small" /></td>
		  </tr>';
		}
		
?>
		
	</tbody></table>
	<div class="field"><input type="submit" value="Добавить в запасы" class="button" /></div>	
	</fieldset>
</form>

<?php
include('./includes/footer.html');
?>