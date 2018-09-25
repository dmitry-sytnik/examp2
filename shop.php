<?php

// Этот сценарий выводит категории товаров
// Сценарий начал создаваться в главе 8

// Перед выполнением любого кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Верификация типа товара...
if (isset($_GET['type']) && ($_GET['type'] === 'goodies')) {
	$page_title = 'Сувениры, отсортированные по категориям';
	$type = 'goodies';
} else { // по умолчанию отображаются товары из категории "кофе"
	$page_title = 'Кофейные товары';
	$type = 'coffee';	
}

// Включение файла заголовка
include('./includes/header.html');

// Выполняется подключение к базе данных:
require(MYSQL);

// Вызов хранимой процедуры
$r = mysqli_query($dbc, "CALL select_categories('$type')");

// Для отладки
// if (!$r) echo mysqli_error($dbc);

// Если возвращены записи, включить файл представления
   if (mysqli_num_rows($r) > 0) {
   	include ('./views/list_categories.html');

 } else {// включение страницы ошибки
         include ('./views/error.html');
 }
  
// Включение HTML-футера
include ('./includes/footer.html');

?>