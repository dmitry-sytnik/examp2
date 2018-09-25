<?php

// С помощью этого сценария администратор может добавлять избранные сорта кофе
// Это сценарий создан в главе 11

// Чтобы контролировать создание отчетов об ошибках, перед выполнением произвольного кода PHP нужно подключить файл конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и включение заголовка
$page_title = 'Добавление выбранных сортов кофе';
include('./includes/header.html');
// Файл заголовка начинает сеанс

// Выполнение подключения к базе данных
require(MYSQL);

// Количество возможных сортов кофе, которые добавляются одновременно
// Этим числом будет ограничен цикл for ниже, причём дважды.
$count = 10;

// Проверка передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {	

	// Проверка присутствия категории
	if (isset($_POST['category']) && filter_var($_POST['category'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		
		// Определение запроса
		$q = 'INSERT INTO specific_coffees (general_coffee_id, size_id, caf_decaf, ground_whole, price, stock) VALUES (?, ?, ?, ?, ?, ?)';

		// Подготовка инструкции
		$stmt = mysqli_prepare($dbc, $q);
		
		// Привязка переменных
		mysqli_stmt_bind_param($stmt, 'iissii', $_POST['category'], $size, $caf_decaf, $ground_whole, $price, $stock);
		// Во втором параметре i означает int, число, а s - строку
		
		// Подсчет количества затрагиваемых строк
		$affected = 0;

		// Циклический просмотр каждой порции отправляемых данных
		for ($i = 1; $i <= $count; $i++) {
			
			// Верификация требуемых значений
			if (filter_var($_POST['stock'][$i], FILTER_VALIDATE_INT, array('min_range' => 1))
			&& filter_var($_POST['price'][$i], FILTER_VALIDATE_FLOAT) 
			&& ($_POST['price'][$i] > 0) ) {
				
				// Присваивание значений переменным
				$size = $_POST['size'][$i];
				$caf_decaf = $_POST['caf_decaf'][$i];
				// echo $caf_decaf;
				
				$ground_whole = $_POST['ground_whole'][$i];
				$price = $_POST['price'][$i]*100;
				$stock = $_POST['stock'][$i];
				
				// Выполнение запроса
				// Т.к. выполнение запроса находится внутри цикла по присвоению значений переменным, если значение не присвоено (количеству или прайсу), то и запрос не выполняется, а следовательно, не добавляются данные в таблицу. Именно поэтому совсем пустой запрос (полностью) не добавляет данные в таблицу. Точно так же незаполненные строки (частично) не добавляются в базу данных.
				mysqli_stmt_execute($stmt);
				
				// Добавление количества затрагиваемых строк
				$affected += mysqli_stmt_affected_rows($stmt);
				// т.е. $affected = $affected + mysqli_stmt_affected_rows($stmt);
				
			} // завершение блока IF

		} // завершение блока FOR
		
		// Вывод на печать количества затрагиваемых строк
		echo "<h4>$affected товар(ов) добавлены!</h4>";
				
	} else { // Иначе. Если категория не прошла проверку
		echo '<p class="error">Пожалуйста, выберите категорию.</p>';
	}

} // завершение блока IF POST, применяемого для передачи данных	

?><h3>Добавление выбранных сортов кофе</h3>

<form action="add_specific_coffees.php" method="post" accept-charset="utf-8">

	<fieldset><legend>Введите в поля формы разновидности кофе, которые будут добавлены на сайте.</legend>
	
		<div class="field"><label for="category"><strong>Разновидности кофе</strong></label><br />
		<select name="category"><option>Выбор категории</option>
		<?php // выборка всех категорий и добавление позиций в раскрывающееся меню
		$q = 'SELECT id, category FROM general_coffees ORDER BY category ASC';		
		$r = mysqli_query($dbc, $q);
			while ($row = mysqli_fetch_array ($r, MYSQLI_NUM)) {
				echo '<option value="' . $row[0] . '">' . htmlspecialchars($row[1]) . '</option>';
			}
		?>
		</select></div>
		
		<table border="0" width="100%" cellspacing="5" cellpadding="5">
			<thead>
				<tr>
			    <th align="right">Расфасовка</th>
			    <th align="right">Молотый/В зернах</th>
			    <th align="right">Обычный/Без кофеина</th>
			    <th align="center">Цена</th>
			    <th align="center">Кол-во на складе</th>
			  </tr>
			</thead>
			<tbody>
		<?php 
		
		// Нужны доступные единицы измерения
		$q = 'SELECT id, size FROM sizes ORDER BY id ASC';		
		$r = mysqli_query($dbc, $q);
		$sizes = '';
		while ($row = mysqli_fetch_array ($r, MYSQLI_NUM)) {
			$sizes .= '<option value="' . $row[0] . '">' . htmlspecialchars($row[1]) . '</option>';
		}
		
		// Требуются доступные параметры измельчения кофе	
		$grinds = '<option value="молотый">Молотый</option><option value="в зернах">В зернах</option>';

		// Требуются параметры обычный/без кофеина
		$caf_decaf = '<option value="обычный">Обычный</option><option value="без кофеина">Без кофеина</option>';
		
		// $sizes, $grinds и $caf_decaf пока не выводятся на экран, но сразу подготавливаются в качестве опций выбора. Ниже они выводятся на экран в каждой отдельной строке. Там же значения price и stock не заполнены изначально, потому что их вручную будет заполнять администратор.
		// Значения $caf_decaf и $ground_whole должны точно соответсвовать тому, что прописано в базе данных, т.к. там всего два варианта на выбор (или же NULL). Именно поэтому value="" должно быть не caf или decaf, а "обычный" или "без кофеина", как в базе данных. Параметр value подхватывается глобальной переменной POST, а это значение потом передается в базу данных при запросе.
		
		// Создание набора полей для $count (количество товаров)
		for ($i = 1; $i <= $count; $i++) {
			echo '<tr>
			<td align="right"><select name="size[' . $i . ']">' . $sizes . '</select></td>
			<td align="right"><select name="ground_whole[' . $i . ']">' . $grinds . '</select></td>
			<td align="right"><select name="caf_decaf[' . $i . ']">' . $caf_decaf . '</select></td>
		    <td align="center"><input type="text" name="price[' . $i . ']" class="small" /></td>
		    <td align="center"><input type="text" name="stock[' . $i . ']" class="small" /></td>
			</tr>
		';
				// Здесь прописывается $i просто как число. Потом, после переданной формы, это значение учтётся в POST как $_POST['stock'][4], например. $_POST['stock'][$i] используется выше в форме.
				
		} // завершение цикла FOR
		
		?></tbody>
		</table>
		
		<div class="field"><input type="submit" value="Добавить товары" class="button" /></div>
	
	</fieldset>

</form> 

<?php // Включение HTML-футера
include('./includes/footer.html');
?>