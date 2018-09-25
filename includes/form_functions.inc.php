<?php

// С помощью этого сценария создаются функции, требующиеся в разных формах
// Этот сценарий создан в главе 10
// Включение элементов TEXTAREA выполняется в главе 11

// Эта фукнция генерирует теги формы <INPUT> либо <SELECT>
// Она принимает до пяти аргументов:
// - Имя, присвоенное элементу.
// - Тип элемента (text, password, select).
// - Массив ошибок.
// - Местоположение существующих значений (POST, SESSION).
// - Расширения HTML (autocomplete="off", readonly="readonly")
function create_form_input($name, $type, $errors = array(), $values = 'POST', $options = array()) {
	
	// Предполагается, что значений не существует
	$value = false;

	// Получение существующего значения
	if ($values === 'SESSION') {
		
		if (isset($_SESSION[$name])) $value = htmlspecialchars($_SESSION[$name], ENT_QUOTES, 'UTF-8');
		
	} elseif ($values === 'POST') {
		
		if (isset($_POST[$name])) $value = htmlspecialchars($_POST[$name], ENT_QUOTES, 'UTF-8');
		// Убирает символы косой черты, если включены волшебные кавычки
		if ($value && get_magic_quotes_gpc()) $value = stripslashes($value);

	}

	// Условное выражение, позволяющее определить тип создаваемого элемента
	if ( ($type === 'text') || ($type === 'password') ) { // создание поля, предназначенного для ввода текста или пароля
		
		// Начало создания поля ввода
		echo '<input type="' . $type . '" name="' . $name . '" id="' . $name . '"';

		// Добавление значения в поле ввода
		if ($value) echo ' value="' . $value . '"';

		// Проверка наличия произвольных расширений
		if (!empty($options) && is_array($options)) {
			foreach ($options as $k => $v) {
				echo " $k=\"$v\"";
			}
		}
	
		// Проверка наличия ошибок
		if (array_key_exists($name, $errors)) {
			echo 'class="error" /> <span class="error">' . $errors[$name] . '</span>';
		} else {
			echo ' />';		
		}
		
	} elseif ($type === 'select') { // меню выбора
	
		if (($name === 'state') || ($name === 'cc_state')) { // создание списка штатов
			
			$data = array('AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Тexas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming');
			
		} elseif ($name === 'cc_exp_month') { // создание списка месяцев

			$data = array(1 =>  'January', 'February',  'March',  'April',  'May',  'June',  'July',  'August',  'September',  'October', 'November',  'December');
		
		} elseif ($name === 'cc_exp_year') { // Создание списка лет.
			
			$data = array();
			$start = date('Y'); // начало с текущего года
			for ($i = $start; $i <= $start + 5; $i++) { // добавление пяти других лет
				$data[$i] = $i;
			}
			
		} // завершение блока $name IF-ELSEIF
	
		// Начало тега
		echo '<select name="' . $name  . '"';
	
		// Добавление класса error (при необходимости в нем)
		if (array_key_exists($name, $errors)) echo ' class="error"';

		// Закрытие тега
		echo '>';	
		
		// Создание каждого параметра
		foreach ($data as $k => $v) {
			echo "<option value=\"$k\"";
			
			// Выбор существующего значения
			if ($value === $k) echo ' selected="selected"';
			
			echo ">$v</option>\n";
			
		} // конец цикла FOREACH
	
		// Закрывающий тег
		echo '</select>';
		
		// Добавление сообщения об ошибке (при наличии ошибки)
		if (array_key_exists($name, $errors)) {
			echo '<br /><span class="error">' . $errors[$name] . '</span>';
		}
// Включение элементов TEXTAREA выполняется в главе 11
		
		} elseif ($type === 'textarea') { // создание области TEXTAREA

		// Сначала отображается ошибка, если есть 
		if (array_key_exists($name, $errors)) echo ' <span class="error">' . $errors[$name] . '</span><br />';

		// Начало создания текстовой области
		echo '<textarea name="' . $name . '" id="' . $name . '" rows="5" cols="75"';

		// Добавление класса error (при необходимости в нем)
		if (array_key_exists($name, $errors)) {
			echo ' class="error">';
		} else {
			echo '>';		
		}

		// Добавление значения в текстовую область
		if ($value) echo $value;

		// Завершение создания текстовой области
		echo '</textarea>';

	} // завершение основного блока IF-ELSEIF

} // завершение создания функции create_form_input()

// Пропуск закрывающего тега PHP во избежание появления сообщений об ошибках типа 'headers are sent'!