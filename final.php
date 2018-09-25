<?php

// Этот файл реализует завершающую страницу, отображающуюся в процессе оформления заказа 
// Этот сценарий начал разрабатываться в главе 10

// Перед выполнением произвольного кода PHP требуется подключение файла конфигурации
require('./includes/config.inc.php');

// Открытие сеанса
session_start();

// Код сеанса совпадает с кодом корзины пользователя
$uid = session_id();

// Проверка корректности доступа к странице:
if (!isset($_SESSION['customer_id'])) { // перенаправление пользователя
	$location = 'https://' . BASE_URL . 'checkout.php';
	header("Location: $location");
	exit();
} elseif (!isset($_SESSION['response_code']) || ($_SESSION['response_code'] != 1)) {
	// response_code == 1 означает успешную оплату
	$location = 'https://' . BASE_URL . 'billing.php';
	header("Location: $location");
	exit();
}

// Выполняется подключение к базе данных
require(MYSQL);

// Очистка корзины
$r = mysqli_query($dbc, "CALL clear_cart('$uid')");

// Отправка сообщения электронной почты
// Добавлено в главе 13
// include('./includes/email_receipt.php');

// Включение файла заголовка
$page_title = 'Кофе - оформление заказа - ваш заказ оформлен';
include('./includes/checkout_header.html');

// Включение представления
include('./views/final.html');

// Очистка сеанса
$_SESSION = array(); // удаление переменных
session_destroy(); // Удаление самого сеанса

// Включение HTML-футера
include('./includes/footer.html');
?>