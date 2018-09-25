<?php

// Этот сценарий управляет списком желаний
// Сценарий начал создаваться в главе 9

// Перед выполнением любого сценария PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Проверка либо создание пользовательского сеанса
if (isset($_COOKIE['SESSION']) && (strlen($_COOKIE['SESSION']) === 32)) {
	$uid = $_COOKIE['SESSION'];
} else {
	$uid = openssl_random_pseudo_bytes(16);
	$uid = bin2hex($uid);
}

// Отправка куки-файла
setcookie('SESSION', $uid, time()+(60*60*24*30)); // В книге еще было два параметра через запятую - слэш и адрес сайта с www

// Включение файла заголовка
$page_title = 'Кофе - ваш список желаний';
include('./includes/header.html');

// Выполняется подключение к базе данных
require(MYSQL);

// Требуются функции-утилиты
include('./includes/product_functions.inc.php');

// Если URL-ссылка включает значение SKU, производится его разбиение на части
if (isset($_GET['sku'])) {
	list($type, $pid) = parse_sku($_GET['sku']);
}

if (isset($type, $pid, $_GET['action']) && ($_GET['action'] === 'remove') ) { // удаление позиции из списка желаний
	$r = mysqli_query($dbc, "CALL remove_from_wish_list('$uid', '$type', $pid)");

} elseif (isset($type, $pid, $_GET['action'], $_GET['qty']) && ($_GET['action'] === 'move') ) { // перемещение позиции в список желаний из корзины (метод GET['action'] === 'move' с вызовом wishlist.php запускается файлом корзины)

	// Определение количества позиций
	$qty = (filter_var($_GET['qty'], FILTER_VALIDATE_INT, array('min_range' => 1)) !== false) ? $_GET['qty'] : 1;

	// Добавление позиции в список желаний
	$r = mysqli_query($dbc, "CALL add_to_wish_list('$uid', '$type', $pid, $qty)");

	// Для выполнения отладки
	if (!$r) echo mysqli_error($dbc);
	
	// Удаление позиции из корзины
	$r = mysqli_query($dbc, "CALL remove_from_cart('$uid', '$type', $pid)");
	
	// Для выполнения отладки
	if (!$r) echo mysqli_error($dbc);

} elseif (isset($_POST['quantity'])) { // обновление количества позиций в списке желаний
	
	// Циклический просмотр позиций:
	foreach ($_POST['quantity'] as $sku => $qty) {
		
		// Анализ SKU
		list($type, $pid) = parse_sku($sku);
		
		if (isset($type, $pid)) {

			// Определение количества позиций
			$qty = (filter_var($qty, FILTER_VALIDATE_INT, array('min_range' => 0)) !== false) ? $qty : 1;

			// Обновление количества позиций в списке желаний
			$r = mysqli_query($dbc, "CALL update_wish_list('$uid', '$type', $pid, $qty)");

			}

		} // завершение цикла FOREACH
	
}// завершение основного условия IF
		
// Получение содержимого списка желаний
$r = mysqli_query($dbc, "CALL get_wish_list_contents('$uid')");
 
if (mysqli_num_rows($r) > 0) { // отображаемые товары
	include('./views/wishlist.html');
} else { //очистка корзины
	include('./views/emptylist.html');
}

// Завершение страницы
include('./includes/footer.html');
?>