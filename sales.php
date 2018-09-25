<?php

// Этот файл отображает страницу sales 
// Вывод каждой продаваемой позиции
// Этот сценарий создан в конце главы 8

// Перед выполнением кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Включение файла заголовка
$page_title = 'Товары со скидкой';
include('./includes/header.html');

// Выполняется подключение к базе данных
require(MYSQL);

// Вызов хранимой процедуры
$r = mysqli_query($dbc, 'CALL select_sale_items(true)');

if (mysqli_num_rows($r) > 0) {
	include('./views/list_sales.html');
} else {
	include('./views/noproducts.html');
}

// Включение HTML-футера:
include('./includes/footer.html');
?>