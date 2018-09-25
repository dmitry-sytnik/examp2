<?php

// Этот файл реализует начальную страницу 
// Этот сценарий начал разрабатываться в главе 8

// Перед выполнением произвольного кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Включение файла заголовка
$page_title = 'Кофе - не хотите ли чашечку?';
include('./includes/header.html');

// Выполняется подключение к базе данных
require(MYSQL);

// Вызов хранимой процедуры
$r = mysqli_query($dbc, "CALL select_sale_items(false)");

// Включение представления
include('./views/home.html');

// Включение HTML-футера:
include('./includes/footer.html');
?>