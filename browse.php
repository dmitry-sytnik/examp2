<?php

// Этот файл выводит списки товаров, относящиеся к определенной категории
// Этот сценарий начал разрабатываться в главе 8

// Перед выполнением кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Верификация требуемых значений
$type = $sp_cat = $category = false;
if (isset($_GET['type'], $_GET['category'], $_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
	
	// Создание ассоциаций
	$category = $_GET['category'];
	$sp_cat = $_GET['id'];
	
	// Верификация типа
	if ($_GET['type'] === 'goodies') {
		
		$type = 'goodies';
		
	} elseif ($_GET['type'] === 'coffee') {
		
		$type = 'coffee';	
		
	}

}

// В случае каких-либо проблем отображается страница ошибки
if (!$type || !$sp_cat || !$category) {
	$page_title = 'Ошибка!';
	include('./includes/header.html');
	include('./views/error.html');
	include('./includes/footer.html');
	exit();
}

// Создание заголовка страницы
$page_title = ucfirst($type) . ' Купить::' . $category;

// Включение заголовка файла
include('./includes/header.html');

// Выполняется подключение к базе данных
require(MYSQL);

// Вызов хранимой процедуры
$r = mysqli_query($dbc, "CALL select_products('$type', $sp_cat)");

// Применяется для отладки
//if (!$r) echo mysqli_error($dbc);

// Если записи возвращены, включить файл представления
if (mysqli_num_rows($r) > 0) {
	if ($type === 'goodies') {
		// Три версии файла
		 include('./views/list_goodies.html');
		// include('./views/list_goodies2.html');
		// include('./views/list_goodies3.html');
	} elseif ($type === 'coffee') {
		 include('./views/list_coffees.html');
		// include('./views/list_coffees2.html');

		// Очистка результатов выполнения хранимой процедуры
		mysqli_next_result($dbc);

		// Добавлено в главе 13...
		// Обработка и отображение обзоров...
		/*
		include('./includes/handle_review.php');
		
		$r = mysqli_query($dbc, "CALL select_reviews('$type', $sp_cat)");
		include('./views/review.html');
		*/
	}
} else { // включение страницы "noproducts"
	include('./views/noproducts.html');
}

// Включение файла футера
include('./includes/footer.html');
?>