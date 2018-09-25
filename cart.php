<?php

// Сценарий, управляющий корзиной
// Разработка этого сценария началась в главе 9

// Перед выполнением кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Проверка либо создание пользовательского сеанса
if (isset($_COOKIE['SESSION']) && (strlen($_COOKIE['SESSION']) === 32)) {
	$uid = $_COOKIE['SESSION'];
} else {
	$uid = openssl_random_pseudo_bytes(16);
	$uid = bin2hex($uid);
}

// Отправка куки-файла
setcookie('SESSION', $uid, time()+(60*60*24*30)); // В книге еще два параметра: , '/', 'www.examle.com');

// Включение файла заголовка
$page_title = 'Кофе - ваша корзина';
include('./includes/header.html');

// Выполняется подключение к базе данных
require(MYSQL);

// Нужны функции-утилиты
include('./includes/product_functions.inc.php');

// Если в URL-ссылку входит SKU-значение, разбить его на составляющие части
if (isset($_GET['sku'])) {
	list($type, $pid) = parse_sku($_GET['sku']);
}

if (isset($pid, $type, $_GET['action']) && ($_GET['action'] === 'add') ) { // добавление нового товара в корзину
	$r = mysqli_query($dbc, "CALL add_to_cart('$uid', '$type', $pid, 1)");	
	// для отладки
	// if (!$r) echo mysqli_error($dbc);
	
} elseif (isset($type, $pid, $_GET['action']) && ($_GET['action'] === 'remove') ) { // удаление товара из корзины
	// У текущего пользователя должно быть разрешение Delete либо All Privileges
	$r = mysqli_query($dbc, "CALL remove_from_cart('$uid', '$type', $pid)");
	// для отладки
	// if (!$r) echo mysqli_error($dbc);

} elseif (isset($type, $pid, $_GET['action'], $_GET['qty']) && ($_GET['action'] === 'move') ) { // перемещение товара в корзину (из списка желаний)
	// Подсчет количества
	$qty = (filter_var($_GET['qty'], FILTER_VALIDATE_INT, array('min_range' => 1)) !== false) ? $_GET['qty'] : 1;	
	// добавление товара в корзину
	$r = mysqli_query($dbc, "CALL add_to_cart('$uid', '$type', $pid, $qty)");	
	// удаление товара из списка желаний
	$r = mysqli_query($dbc, "CALL remove_from_wish_list('$uid', '$type', $pid)");

} elseif (isset($_POST['quantity'])) { // обновление количества единиц товара в корзине
	// Циклический просмотр всех элементов
	foreach ($_POST['quantity'] as $sku => $qty) {
		// Анализ SKU
		list($type, $pid) = parse_sku($sku);
		// Заметка: list() используется для того, чтобы присвоить списку переменных значения за одну операцию. Эти значения берутся из массива, который возвращается функцией parse_sku($sku). Массив должен быть только индексированным (не ассоциативным).
		if (isset($type, $pid)) {
			// Идентификация количества
			$qty = (filter_var($qty, FILTER_VALIDATE_INT, array('min_range' => 0)) !== false) ? $qty : 1;
			// Обновление количества товаров в корзине
			$r = mysqli_query($dbc, "CALL update_cart('$uid', '$type', $pid, $qty)");
		}			
	} // завершение цикла FOREACH
	
}// завершение основного блока IF
		
// Получение содержимого корзины
$r = mysqli_query($dbc, "CALL get_shopping_cart_contents('$uid')");
// У аргументов процедур должно быть сопоставление utf8
if (mysqli_num_rows($r) > 0) { // отображаемые товары
	include('./views/cart.html');
} else { // очистка корзины
	include('./views/emptycart.html');
}

// Завершение страницы
include('./includes/footer.html');
?>