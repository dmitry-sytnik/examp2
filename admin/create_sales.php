<?php

// С помощью этого сценария администратор может добавлять сведения о скидках на товары
// Этот сценарий создан в главе 11

// Чтобы контролровать отображение сообщений об ошибках, перед выполнением произвольного кода PHP нужно добавить файл конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и включение заголовка
$page_title = 'Создание записей о скидках';
include('./includes/header.html');
// Файл заголовка открывает сеанс

// Выполняется подключение к базе данных
require(MYSQL);

// Проверка передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {	
	
	// Проверка факта присваивания значений переменным
	if (isset($_POST['sale_price'], $_POST['start_date'], $_POST['end_date'])) {
		
		// Требуются функции, предназначенные для обработки товаров
		require('../includes/product_functions.inc.php');
		//  понадобится определенная пользователем функция parse_sku()
		
		// Подготовка запроса для выполнения
		$q = 'INSERT INTO sales (product_type, product_id, price, start_date, end_date) VALUES (?, ?, ?, ?, ?)';
		$stmt = mysqli_prepare($dbc, $q);
		mysqli_stmt_bind_param($stmt, 'siiss', $type, $id, $price, $start_date, $end_date);
		
		// Эта переменная будет подсчитывать (суммировать) количество затронутых строк
		$affected = 0;
		
		// Циклический просмотр каждого поддерживаемого значения
		foreach ($_POST['sale_price'] as $sku => $price) {
			
			// Верификация цены и даты начала действия скидки
			if (filter_var($price, FILTER_VALIDATE_FLOAT) 
				// Новая скидочная цена должна иметь десятичный формат (например, float)
			&& ($price > 0)
			&& (!empty($_POST['start_date'][$sku]))
			&& (preg_match('/^(201)[3-9]\-[0-1]\d\-[0-3]\d$/', $_POST['start_date'][$sku]))
				// Это выражение поддерживает годы с 2013 по 2019, месяцы — с 01 по 19 (если ограничиться 12 месяцами, регулярное выражение будет чрезмерно усложнено), а дни  — от 01 до 39 (дабы  снова  не усложнять  регулярное выражение).
			){
				
				// Разбор идентификатора SKU
				list($type, $id) = parse_sku($sku);
				// Нами определенная функция parse_sku разбивает значение С21, например, на массив со значениями С и 21. Встроенная в PHP функция list присваивает эти значения последовательно перечисленным в ней переменным $type и $id.
				// $type и $id уже фигурируют в подготовленной инструкции.
				
				// Получение дат
				$start_date = $_POST['start_date'][$sku];
				$end_date = (!empty($_POST['end_date'][$sku]) && preg_match('/^(201)[3-9]\-[0-1]\d\-[0-3]\d$/', $_POST['end_date'][$sku])) ? $_POST['end_date'][$sku] : NULL; 
				// Тенарное выражение: если конечная дата не пустая и проходит валидацию, то утверждаем ее из значения POST, иначе - NULL.
				
				// Преобразование цены
				$price = $price*100;
				
				// Выполнение запроса
				mysqli_stmt_execute($stmt);
				$affected += mysqli_stmt_affected_rows($stmt);
				
			} // завершение блока IF, выполняющего верификацию цены/даты
						
		} // завершение цикла FOREACH
		
		// Отображение результатов
		echo "<h4>Создано $affected скидок!</h4>";
		
	} // Если значения переменным $_POST не присвоены, то...
	// ...ничего
} // завершение блока IF, обрабатывающего передачу данных
?>	

<h3>Создание записей о скидках</h3>
<p>Чтобы создать скидку на товар, укажите специальную цену, дату начала и дату завершения действия скидки. <strong>Все даты должны иметь формат ГГГГ-ММ-ДД.</strong> Чтобы создать запись о скидке с открытой датой, не заполняйте поле даты завершения действия скидки. В списке отображаются только те товары, которые в данный момент находятся на складе!</p>
<form action="create_sales.php" method="post" accept-charset="utf-8">

	<fieldset>

<table border="0" width="100%" cellspacing="4 cellpadding="6">
	<thead>
	<tr>
		<th align="center">Товар</th>
		<th align="center">Обычная цена</th>
		<th align="center">Кол-во на складе</th>
		<th align="center">Специальная цена</th>
		<th align="center">Начало действия скидки</th>
		<th align="center">Завершение действия скидки</th>
	</tr>
	</thead>
	<tbody>

<?php // выборка товаров, находящихся на складе
$q = '(SELECT CONCAT("G", ncp.id) AS sku, ncc.category, ncp.name, FORMAT(ncp.price/100, 2) AS price, ncp.stock FROM non_coffee_products AS ncp 
INNER JOIN non_coffee_categories AS ncc ON ncc.id=ncp.non_coffee_category_id 
WHERE ncp.stock > 0 ORDER BY category, name) 
UNION 
(SELECT CONCAT("C", sc.id), gc.category, CONCAT_WS(" - ", s.size, sc.caf_decaf, sc.ground_whole), FORMAT(sc.price/100, 2), sc.stock FROM specific_coffees AS sc 
INNER JOIN sizes AS s ON s.id=sc.size_id 
INNER JOIN general_coffees AS gc ON gc.id=sc.general_coffee_id 
WHERE sc.stock > 0 ORDER BY sc.general_coffee_id, sc.size, sc.caf_decaf, sc.ground_whole)';
// Вот здесь в конце по-хорошему вместо sc.size должно быть s.size согласно указанному выше CONCAT_WS(" - ", s.size. В онлайне (Онлайн тип сервера MariaDB 10.2.12, в оффлайне MySQL 5.6.38), вероятно, вызовет ошибку.	
$r = mysqli_query($dbc, $q);

while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
	echo '<tr>
    <td align="right">' . htmlspecialchars($row['category']) . '::' . htmlspecialchars($row['name']) . '</td>
    <td align="center">' . $row['price'] .'</td>
    <td align="center">' . $row['stock'] .'</td>
    <td align="center"><input type="text" name="sale_price[' . $row['sku'] . ']" class="small" /></td>
	<td align="center"><input type="text" name="start_date[' . $row['sku'] . ']" class="calendar" /></td>
	<td align="center"><input type="text" name="end_date[' . $row['sku'] . ']"  class="calendar" /></td>
  </tr>';
}
?>

	</tbody></table>
	<div class="field"><input type="submit" value="Добавить скидки" class="button" /></div>
	</fieldset>
</form>
<!-- link'и и скрипты пропущены -->

<?php
include('./includes/footer.html');
?>