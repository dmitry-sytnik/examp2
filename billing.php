<?php

// Этот файл реализует второй этап процесса оформления заказа
// Он принимает и верифицирует платежную информацию
// Этот сценарий начал разрабатываться в главе 10

// Требуется подключение файла конфигурации перед выполнением произвольного кода PHP
require('./includes/config.inc.php');

// Открытие сеанса
session_start();

// Код сеанса совпадает с кодом пользовательской корзины
$uid = session_id();

// Проверка корректности пользователя
if (!isset($_SESSION['customer_id'])) { // перенаправление пользователя
	$location = 'https://' . BASE_URL . 'checkout.php';
	header("Location: $location");
	exit();
}

// Требуется подключение к базе данных
require(MYSQL);

// Верификация платежной формы...

// Массив, предназначенный для хранения ошибок
$billing_errors = array();

// Проверка передачи формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if (get_magic_quotes_gpc()) {
		$_POST['cc_first_name'] = stripslashes($_POST['cc_first_name']);
		// Повторить для других затрагиваемых переменных
	}
	
	// Проверка имени
	if (preg_match ('/^[A-Z \'.-]{2,20}$/i', $_POST['cc_first_name'])) {
		$cc_first_name = $_POST['cc_first_name'];
	} else {
		$billing_errors['cc_first_name'] = 'Пожалуйста, укажите ваше имя!';
	}

	// Проверка фамилии
	if (preg_match ('/^[A-Z \'.-]{2,40}$/i', $_POST['cc_last_name'])) {
		$cc_last_name  = $_POST['cc_last_name'];
	} else {
		$billing_errors['cc_last_name'] = 'Пожалуйста, введите фамилию!';
	}
	
	// Проверка корректности номера кредитной карты...
	// Удаление пробелов и символов косой черты
	$cc_number = str_replace(array(' ', '-'), '', $_POST['cc_number']);
	
	// Верификация номера кредитной карты для следующих типов карт
	if (!preg_match ('/^4[0-9]{12}(?:[0-9]{3})?$/', $cc_number) // Visa
	&& !preg_match ('/^5[1-5][0-9]{14}$/', $cc_number) // MasterCard
	&& !preg_match ('/^3[47][0-9]{13}$/', $cc_number) // American Express
	&& !preg_match ('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $cc_number) // Discover
	) {
		$billing_errors['cc_number'] = 'Пожалуйста, укажите номер кредитной карты!';
	}
	
	// Проверка даты окончания действия карты
	if ( ($_POST['cc_exp_month'] < 1 || $_POST['cc_exp_month'] > 12)) {
		$billing_errors['cc_exp_month'] = 'Пожалуйста, укажите месяц окончания действия карты!';		
	}

	if ($_POST['cc_exp_year'] < date('Y')) {
		$billing_errors['cc_exp_year'] = 'Пожалуйста, введите год окончания действия карты!';
	}
	
	// Проверка кода CVV. Код может состоять из 3 или 4 цифр.
	if (preg_match ('/^[0-9]{3,4}$/', $_POST['cc_cvv'])) {
		$cc_cvv = $_POST['cc_cvv'];
	} else {
		$billing_errors['cc_cvv'] = 'Пожалуйста, введите код CVV!';
	}
	
	// Проверка адреса
	if (preg_match ('/^[A-Z0-9 \',.#-]{2,160}$/i', $_POST['cc_address'])) {
		$cc_address  = $_POST['cc_address'];
	} else {
		$billing_errors['cc_address'] = 'Пожалуйста, укажите адрес!';
	}
	
	// Проверка города
	if (preg_match ('/^[A-Z \'.-]{2,60}$/i', $_POST['cc_city'])) {
		$cc_city = $_POST['cc_city'];
	} else {
		$billing_errors['cc_city'] = 'Пожалуйста, введите название города!';
	}

	// Проверка штата
	if (preg_match ('/^[A-Z]{2}$/', $_POST['cc_state'])) {
		$cc_state = $_POST['cc_state'];
	} else {
		$billing_errors['cc_state'] = 'Пожалуйста, введите название штата!';
	}

	// Проверка почтового индекса
	if (preg_match ('/^(\d{5}$)|(^\d{5}-\d{4})$/', $_POST['cc_zip'])) {
		$cc_zip = $_POST['cc_zip'];
	} else {
		$billing_errors['cc_zip'] = 'Пожалуйста, введите почтовый индекс!';
	}
	
	if (empty($billing_errors)) { // если все OK...

		// Преобразование даты завершения действия карты в корректный формат
		$cc_exp = sprintf('%02d%d', $_POST['cc_exp_month'], $_POST['cc_exp_year']);
		// Во встроенной функции sprintf() %02d преобразует первое значение в две цифры с дополнительным нулём вначале (если он необходим), %d преобразует второе значение как есть (то есть в 4 цифры года). В итоге мы получаем, например, 022015 или 102015.
	
		// Проверка существующего идентификатора заказа. Уже существующего в сессии.
		if (isset($_SESSION['order_id'])) { // использование информации о существующем заказе
			$order_id = $_SESSION['order_id'];
			$order_total = $_SESSION['order_total'];
		} else { // создание новой записи о заказе

			// Получение последних четырех цифр номера кредитной карты
			$cc_last_four = substr($cc_number, -4);

			// Вызов хранимой процедуры
			$shipping = $_SESSION['shipping'] * 100;
			$r = mysqli_query($dbc, "CALL add_order({$_SESSION['customer_id']}, '$uid', $shipping, $cc_last_four, @total, @oid)");
			// Если данные, предоставленные пользователем, корректны, то сценарий нуждается в хранении информации о заказе в таблице orders. Код, который вызывает хранимую процедуру add_order(), будет вызываться только один раз независимо от того, сколько раз передаются данные платежной формы.

			// Подтверждение работоспособности
			if ($r) {

				// Выборка номера заказа и суммы
				$r = mysqli_query($dbc, 'SELECT @total, @oid');
				if (mysqli_num_rows($r) == 1) {
					list($order_total, $order_id) = mysqli_fetch_array($r);
					
					// Сохранение информации в сеансе
					$_SESSION['order_total'] = $order_total;
					$_SESSION['order_id'] = $order_id;
						/* Если платежная форма была передана второй раз, то условное выражение выше
						if (isset($_SESSION['order_id'])) { 
							$order_id = $_SESSION['order_id'];
							$order_total = $_SESSION['order_total'];
						} else...
						будет равным true (истина). И этот блок else{} уже не будет выполняться.
						*/

					} else { // выборка идентификатора заказа и суммы невозможна
					unset($cc_number, $cc_cvv, $_POST['cc_number'], $_POST['cc_cvv']);
					// unset стирает данные карты и другие важные данные перед вызовом на экран или отправкой в базу данных или на почту сообщения об ошибке, т.к. это сообщение будет хранить в простом текстовом виде все эти данные, что небезопасно.
					trigger_error('Ваш заказ не может быть обработан из-за системной ошибки. Приносим извинения за доставленные неудобства.');
				}
			} else { // сбой при выполнении процедуры add_order()
				unset($cc_number, $cc_cvv, $_POST['cc_number'], $_POST['cc_cvv']);
				trigger_error('Ваш заказ не может быть обработан из-за системной ошибки. Приносим извинения за доставленные неудобства.');
			}
			
		} // завершение условной конструкции isset($_SESSION['order_id']) IF-ELSE
			
		
		// ------------------------
		// Обработка платежа!
		if (isset($order_id, $order_total)) {


				// Создание запроса к платежному шлюзу
				require('includes/vendor/anet_php_sdk/AuthorizeNet.php');
				
                                                                          $aim = new AuthorizeNetAIM(API_LOGIN_ID, TRANSACTION_KEY);
                                
				// Выполняем тестирование?
				$aim->setSandbox(true);
				
				// Выбор суммы (в долларах)
				$aim->amount = $order_total/100;
				
				// Выбор номер счета-фактуры
				$aim->invoice_num = $order_id;

				// Выбор идентификатора заказчика
				$aim->cust_id = $_SESSION['customer_id'];
				
				// Выбор информации, относящейся к карте заказчика
				$aim->card_num = $cc_number;
				$aim->exp_date = $cc_exp;
				$aim->card_code = $cc_cvv;
				
				// Персональные сведения заказчика
				$aim->first_name = $cc_first_name;
				$aim->last_name = $cc_last_name;
				$aim->address = $cc_address;
				$aim->state = $cc_state;
				$aim->city = $cc_city;
				$aim->zip = $cc_zip;
				$aim->email = $_SESSION['email'];
				
				// $aim->addLineItem();
				// $aim->setCustomField('thing', 'value');
				// $aim->phone;
				// $aim->tax
				// $aim->freight
				// $aim->description
				
				// Этап резервирования нужной суммы
				$response = $aim->authorizeOnly();
				
				// Добавление символов косой черты к двум текстовым значениям
				// для того, чтобы не столкнуться с проблемой, если эти текстовые отклики будут иметь одинарные кавычки.
				$reason = addslashes($response->response_reason_text);
				$full_response = addslashes($response->response);
				
				// Запись транзакции
				$r = mysqli_query($dbc, "CALL add_transaction($order_id, '{$response->transaction_type}', $order_total, {$response->response_code}, '$reason', {$response->transaction_id}, '$full_response')");				
				// Если транзакция завершилась успешно, то сохраните код отклика в сеансе
				if ($response->approved) {
					
					// Добавить сведения о транзакции к сеансу
					$_SESSION['response_code'] = $response->response_code;
					
					// Выполните перенаправление пользователя на финальную страницу
					$location = 'https://' . BASE_URL . 'final.php';
					header("Location: $location");
					exit();
					
				} else { // Если транзакция завершилась неудачно, то генерируется соответствующий отклик
				
					switch ($response->response_code) {
						// response_code == 1 означает успешную оплату
						case '2': // отклонено	
							$message = $response->response_reason_text . ' Пожалуйста, исправьте ошибку или воспользуйтесь другой картой.';	
							break;
						case '3': // ошибка	
							$message = $response->response_reason_text . '  Пожалуйста, исправьте ошибку или воспользуйтесь другой картой.';	
							break;
						case '4': // передано для рассмотрения	
							$message = "Транзакция передана для рассмотрения. Мы свяжемся с вами как можно быстрее. Мы приносим вам извинения за причиненные неудобства.";			
							break;
					} // Поскольку перенаправления пользователя не произошло, в каждом из перечисленных случаев снова отображается платежная форма.
									
				} // завершение конструкции ($response->approved) IF-ELSE.	
		
		} // Завершение конструкции isset($order_id, $order_total) IF
		// Приведенный выше код был добавлен как часть кода обработки платежей
		// ------------------------		
		
		
	} // ошибки при выполнении блока IF

} // завершение блока REQUEST_METHOD IF


// Включение файла заголовка
$page_title = 'Coffee - оформление заказа - ваша платежная информация';
include('./includes/checkout_header.html');

// Получение содержимого корзины
$r = mysqli_query($dbc, "CALL get_shopping_cart_contents('$uid')");

if (mysqli_num_rows($r) > 0) { // отображаемые товары
	if (isset($_SESSION['shipping_for_billing']) && ($_SERVER['REQUEST_METHOD'] !== 'POST')) {
		$values = 'SESSION';
	} else {
		$values = 'POST';
	}
	include('./views/billing.html');
} else { // пустая корзина
	include('./views/emptycart.html');
}

// Завершение создания страницы
include('./includes/footer.html');
?>