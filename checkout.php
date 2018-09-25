<?php

// Этот файл реализует первый этап процесса оформления заказа
// Принимается и проверяется информация о доставке
// Этот сценарий начал создаваться в главе 10

// Перед выполнением кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Проверка идентификатора сеанса пользователя, используемого для выборки содержимого корзины
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if (isset($_COOKIE['SESSION']) && (strlen($_COOKIE['SESSION']) === 32)) {
		$uid = $_COOKIE['SESSION'];
		// Использование существующего идентификатора пользователя
		session_id($uid);
		// Начало сеанса
		session_start();
	} else { // Перенаправление пользователя
		$location = 'http://' . BASE_URL . 'cart.php';
		header("Location: $location");
		exit();
	}
} else { // Запрос POST
	session_start();
	$uid = session_id();
}
// Идентификатор сеанса (session_id) был назначен при первом доступе к этому сценарию через GET, поэтому возможна выборка этого значения (с помощью вызова функции s e s s io n _ id ()  без аргументов) для дальнейшего использования в сценарии.

// Создание фактического сеанса для процесса оформления заказа...

// Выполняется подключение к базе данных
require(MYSQL);

// Верификация формы оформления заказа...

// Для хранения ошибок:
$shipping_errors = array();

// Проверка передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Проверка наличия волшебных кавычек
	if (get_magic_quotes_gpc()) {
		$_POST['first_name'] = stripslashes($_POST['first_name']);		
		$_POST['last_name'] = stripslashes($_POST['last_name']);
		// Повтор для других затрагиваемых переменных
	}

	// Проверка имени
	// В  качестве  простой  формальности  можно добавить  функцию  isset() к каждому условному выражению, выполняющему верификацию: (isset($_POST['var']) preg_match( . . . .
	
	if (preg_match ('/^[A-Z \'.-]{2,20}$/i', $_POST['first_name'])) {
		$fn = addslashes($_POST['first_name']);
	} else {
		$shipping_errors['first_name'] = 'Пожалуйста, введите ваше имя!';
	}
	
	// Проверка фамилии
	if (preg_match ('/^[A-Z \'.-]{2,40}$/i', $_POST['last_name'])) {
		$ln  = addslashes($_POST['last_name']);
	} else {
		$shipping_errors['last_name'] = 'Пожалуйста, укажите фамилию!';
	}

	// Проверка адреса
	if (preg_match ('/^[A-Z0-9 \',.#-]{2,80}$/i', $_POST['address1'])) {
		$a1  = addslashes($_POST['address1']);
	} else {
		$shipping_errors['address1'] = 'Пожалуйста, введите ваше адрес!';
	}

	// Проверка дополнительного адреса
	if (empty($_POST['address2'])) {
		$a2 = NULL;
	} elseif (preg_match ('/^[A-Z0-9 \',.#-]{2,80}$/i', $_POST['address2'])) {
		$a2 = addslashes($_POST['address2']);
	} else {
		$shipping_errors['address2'] = 'Пожалуйста, введите дополнительный адрес!';
	}
	
	// Проверка названия города
	if (preg_match ('/^[A-Z \'.-]{2,60}$/i', $_POST['city'])) {
		$c = addslashes($_POST['city']);
	} else {
		$shipping_errors['city'] = 'Пожалуйста, введите название города!';
	}
	
	// Проверка штата
	if (preg_match ('/^[A-Z]{2}$/', $_POST['state'])) {
		$s = $_POST['state'];
	} else {
		$shipping_errors['state'] = 'Пожалуйста, введите название штата!';
	}
	
	// Проверка почтового индекса
	if (preg_match ('/^(\d{5}$)|(^\d{5}-\d{4})$/', $_POST['zip'])) {
		$z = $_POST['zip'];
	} else {
		$shipping_errors['zip'] = 'Пожалуйста, введите ваш почтовый индекс!';
	}
	
	// Проверка телефонного номера
	// Удаление пробелов, тире и скобок
	$phone = str_replace(array(' ', '-', '(', ')'), '', $_POST['phone']);
	if (preg_match ('/^[0-9]{10}$/', $phone)) {
		$p  = $phone;
	} else {
		$shipping_errors['phone'] = 'Пожалуйста, укажите ваш телефонный номер!';
	}
	
	// Проверка адреса электронной почты
	if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$e = $_POST['email'];
		$_SESSION['email'] = $_POST['email'];
	} else {
		$shipping_errors['email'] = 'Пожалуйста, введите корректный адрес электронной почты!';
	}
	
	// Проверка совпадения адресов доставки и платежа
	if (isset($_POST['use']) && ($_POST['use'] === 'Y')) {
		$_SESSION['shipping_for_billing'] = true;
		$_SESSION['cc_first_name']  = $_POST['first_name'];
		$_SESSION['cc_last_name']  = $_POST['last_name'];
		$_SESSION['cc_address']  = $_POST['address1'] . ' ' . $_POST['address2'];
		$_SESSION['cc_city'] = $_POST['city'];
		$_SESSION['cc_state'] = $_POST['state'];
		$_SESSION['cc_zip'] = $_POST['zip'];
	}
	
	if (empty($shipping_errors)) { // если все OK...
		
		// Добавление пользователя в базу данных...
		
		// Вызов хранимой процедуры
		$r = mysqli_query($dbc, "CALL add_customer('$e', '$fn', '$ln', '$a1', '$a2', '$c', '$s', $z, $p, @cid)");
		// переменные в MySQL, которые не относятся к хранимым процедурам (которые OUT), начинаются с символа @
		
		// Проверка работоспособности сценария
		if ($r) {

			// Выборка идентификатора покупателя
			$r = mysqli_query($dbc, 'SELECT @cid');
			if (mysqli_num_rows($r) == 1) {

				list($_SESSION['customer_id']) = mysqli_fetch_array($r);
					
				// Перенаправление на следующую страницу
				$location = 'https://' . BASE_URL . 'billing.php';
				header("Location: $location");
				exit();

			}

		}
		
		// Регистрация ошибки, отправка сообщения!

		trigger_error('Ваш заказ не может быть обработан из-за системной ошибки. Приносим извинения за доставленные неудобства.');

	} // Условная конструкция IF, используемая для обнаружения ошибок.

} // Завершение условной конструкции REQUEST_METHOD === POST
							
// Включение файла заголовка
$page_title = 'Кофе - оформление заказа - информация о доставке';
include('./includes/checkout_header.html');

// Получение содержимого корзины
$r = mysqli_query($dbc, "CALL get_shopping_cart_contents('$uid')");

if (mysqli_num_rows($r) > 0) { // отображаемые товары
	include('./views/checkout.html');
} else { // Очистка корзины!
	include('./views/emptycart.html');
}

// Завершение страницы:
include('./includes/footer.html');
?>