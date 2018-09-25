<?php

// С помощью этого сценария администратор может добавлять сувениры на склад
// Этот сценарий создан в главе 11

// Чтобы управлять отображением сообщений об ошибках, перед выполнением произвольного кода PHP требуется подключение файла конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и включение заголовка
$page_title = 'Добавление сувенира';
include('./includes/header.html');
// Файл заголовка начинает сеанс

// Выполняется подключение к базе данных
require(MYSQL);

// Для хранения ошибок
$add_product_errors = array();

// Проверка для передачи данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {	

	// Проверка категории
	if (!isset($_POST['category']) || !filter_var($_POST['category'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$add_product_errors['category'] = 'Пожалуйста, выберите категорию!';
	}

	// Проверка цены
	// Значение цены не должно быть пустым, иметь тип данных с плавающей точкой (десятичный тип данных), а также быть большим или равным нулю
	if (empty($_POST['price']) || !filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) || ($_POST['price'] <= 0)) {
		$add_product_errors['price'] = 'Пожалуйста, введите корректную цену!';
	}

	// Проверка наличия на складе
	// Значение, соответствующее количеству товаров на складе, не должно быть пустым, а также должно быть целым числом, большим или равным 1. Можно изменить значение переменной min_range, выбрав его равным 0, чтобы разрешить администратору добавлять товар в случае [количества равным нулю - Дм.С.].
	if (empty($_POST['stock']) || !filter_var($_POST['stock'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$add_product_errors['stock'] = 'Пожалуйста, укажите количество товара на складе!';
	}

	// Следующие два поля не должны быть пустыми
	// Проверка имени
	if (empty($_POST['name'])) {
		$add_product_errors['name'] = 'Пожалуйста, введите название товара!';
	}

	// Проверка описания
	if (empty($_POST['description'])) {
		$add_product_errors['description'] = 'Пожалуйста, введите описание!';
	}
	
	// Верификация выгружаемых изображений. Здесь максимальный размер изображения не может превышать 512 Кбайт. Также здесь используется вложенный массив $_FILES['image']['tmp_name']. 
	
	if (is_uploaded_file($_FILES['image']['tmp_name']) && ($_FILES['image']['error'] === UPLOAD_ERR_OK)) {
		
		$file = $_FILES['image'];
		
		$size = ROUND($file['size']/1024);

		// Верификация размера файла
		if ($size > 512) {
			$add_product_errors['image'] = 'Размер загруженного файла слишком велик.';
		} 

		// Верификация типа файла...

		// Допустимые типы
		// Сами придумываем массивы и перечисляем в них нужные нам значения.
		$allowed_mime = array ('image/gif', 'image/pjpeg', 'image/jpeg', 'image/JPG', 'image/X-PNG', 'image/PNG', 'image/png', 'image/x-png');
		$allowed_extensions = array ('.jpg', '.gif', '.png', 'jpeg');

		// Проверка файла
		// Открываем структуру php, владеющую информацией про файлы.
		$fileinfo = finfo_open(FILEINFO_MIME_TYPE);
		// Узнаём о типе нашего файла.
		$file_type = finfo_file($fileinfo, $file['tmp_name']);
		// Закрываем структуру php, владеющую информацией про файлы.
		finfo_close($fileinfo);
		// Узнаём о расширении нашего файла, взяв последние 4 символа в имени файла.
		$ext = substr($file['name'], -4);
		// Если наш тип данного файлй не присутствует в придуманном нами массиве типов ИЛИ наше расширение не находится в придуманном нами массиве расширений, то добавляем сообщение об ошибке в массив сообщений об ошибках.
		 if ( !in_array($file_type, $allowed_mime) || !in_array($ext, $allowed_extensions) ) {
			$add_product_errors['image'] = 'Тип загруженного файла некорректен.';
		} 

		// Если нет проблем (если в массиве $add_product_errors нет ключа 'image'), переместить файл
		if (!array_key_exists('image', $add_product_errors)) {

			// Создание нового имени файла
			$new_name = sha1($file['name'] . uniqid('',true));
			//  В результате генерируется случайное название файла, состоящее из 40 символов

			// Добавление расширения к $new_name
			// Берем наше расширение $ext, обрезаем, начиная с первого символа [0], берем один символ [1]. Если получившееся значение не является точкой, то прибавляем к нашему расширению точку вначале, иначе прописываем наше расширение как есть.
			$new_name .= ((substr($ext, 0, 1) != '.') ? ".{$ext}" : $ext);
			
			// Перемещение файла в подходящую папку (к названию которого добавлено _tmp -- не понял, возможно, ошибка автора)
			$dest =  "../products/$new_name";
			
			// Встроенная функция move_uploaded_file перемещает загруженный на сервер файл (переданный по протоколу HTTP POST) в новое место. Возвращает TRUE в случае успеха и FALSE в случае какой-либо неудачи.
			// Таким образом, если файл был перемещён, то...
			if (move_uploaded_file($file['tmp_name'], $dest)) {
				
				// Сохранение данных в сеансе для дальнейшего использования
				$_SESSION['image']['new_name'] = $new_name;
				$_SESSION['image']['file_name'] = $file['name'];
				
				//
				echo '<h4>Файл был загружен!</h4>';
				
			} else {
				trigger_error('Файл не может быть перемещен.');
				unlink ($file['tmp_name']);	// Удаляет файл			
			}

		} // завершение блока IF для array_key_exists()
			
	} elseif (!isset($_SESSION['image'])) { // отсутствует текущий или ранее загруженный файл
		switch ($_FILES['image']['error']) {
			case 1:
			case 2:
				$add_product_errors['image'] = 'Размер загруженного файла слишком велик.';
				break;
			case 3:
				$add_product_errors['image'] = 'Файл был загружен частично.';
				break;
			case 6:
			case 7:
			case 8:
				$add_product_errors['image'] = 'Файл не может быть загружен из-за системной ошибки.';
				break;
			case 4:
			default: 
				$add_product_errors['image'] = 'Отсутствует загруженный файл.';
				break;
		} // завершение блока SWITCH

	} // завершение конструкции $_FILES IF-ELSEIF-ELSE

	if (empty($add_product_errors)) { // если все OK

		// Добавление товара в базу данных
		$q = 'INSERT INTO non_coffee_products (non_coffee_category_id, name, description, image, price, stock) VALUES (?, ?, ?, ?, ?, ?)';

		// Подготовка инструкции
		$stmt = mysqli_prepare($dbc, $q);

		// Для выполнения отладки
		// if (!$stmt) echo mysqli_stmt_error($stmt);

		// Привязка переменных
		mysqli_stmt_bind_param($stmt, 'isssii', $_POST['category'], $name, $desc, $_SESSION['image']['new_name'], $price, $_POST['stock']);
		// 'isssii' означает соответственно для всех переменных (вопросиков) int,строка,строка,строка,int,int 
		
		// Создание дополнительных ассоциаций переменных
		$name = strip_tags($_POST['name']);
		$desc = strip_tags($_POST['description']);
		$price = $_POST['price']*100;

		// Вызов запроса
		mysqli_stmt_execute($stmt);
		
		if (mysqli_stmt_affected_rows($stmt) === 1) { // если выполняется без сбоев
			
			// Печать сообщения
			echo '<h4>Товар был добавлен!</h4>';

			// Очистка $_POST
			$_POST = array();
			
			// Очистка $_FILES
			$_FILES = array();
			
			// Очистка $file и $_SESSION['image'] встроенной функцией PHP unset
			unset($file, $_SESSION['image']);
			
			// Очистка выполняется в связи с тем, что повторно отображаемая форма не должна включать предыдущие значения.
					
		} else { // если возник сбой при выполнении
			trigger_error('Товар не может быть добавлен из-за системной ошибки. Приносим извинения за доставленные неудобства.');
			unlink ($dest); 
			// Выгруженный файл удаляется функцией unlink, чтобы предотвратить чрезмерное загромождение папки products.
		}
		
	} // Завершение блока if (empty($add_product_errors)) { // если все OK
	
} else { // Иначе, если это был не POST, то очистка сеанса в запросе GET
	unset($_SESSION['image']);	
	// Очистка значения переменной сеанса выполняется в случае запутанных действий администратора (если администратор  выгрузил  файл,  но не полностью заполнил  поля  формы,  а затем в силу каких-либо причин щелкнул на ссылке, находящейся в заголовке, чтобы вернуться к странице и повторно начать ввод данных в поля формы).
} // завершение раздела IF POST, используемого для передачи данных

// Требуется подключение к сценарию form functions, определяющего функцию create_form_input()
require('../includes/form_functions.inc.php');
?><h3>Добавление сувенирного товара</h3>

<form enctype="multipart/form-data" action="add_other_products.php" method="post" accept-charset="utf-8">

	<input type="hidden" name="MAX_FILE_SIZE" value="524288" />
	
	<fieldset><legend>Заполните поля формы, чтобы добавить сувенирный товар в каталог. Все поля формы обязательны для заполнения.</legend>

	<div class="field"><label for="category"><strong>Категория</strong></label>
	<br /><select name="category"
		<?php // Если у нас есть такое значение - $add_product_errors['category'], то оформляем всё классом css как ошибку
		if (array_key_exists('category', $add_product_errors)) echo ' class="error"'; ?>
		>
		<option>Выбор категории</option>
		<?php // выборка всех категорий и добавление в раскрывающееся меню
		$q = 'SELECT id, category FROM non_coffee_categories ORDER BY category ASC';		
		$r = mysqli_query($dbc, $q);
			while ($row = mysqli_fetch_array ($r, MYSQLI_NUM)) {
				// числовой массив выбирает из получившихся столбцов первое row[0], а потом второе row[1] значение
				echo "<option value=\"$row[0]\"";
				// Проверка связанности: ставим selected, если значение уже выбрано
				if (isset($_POST['category']) && ($_POST['category'] == $row[0]) ) echo ' selected="selected"';
				echo '>' . htmlspecialchars($row[1]) . '</option>';
			}
		?>
		</select><?php if (array_key_exists('category', $add_product_errors)) echo ' <span class="error">' . $add_product_errors['category'] . '</span>'; 
		// В данном случае меню выбора "Выбор категории" генерируется без использования функции create_form input(), поскольку параметры меню требуют запроса к базе данных
		?></div>
		
		<div class="field"><label for="name"><strong>Название</strong></label><br /><?php create_form_input('name', 'text', $add_product_errors); ?></div>

		<div class="field"><label for="price"><strong>Цена</strong></label><br /><?php create_form_input('price', 'text', $add_product_errors); ?> <small>Без символа валюты.</small></div>

		<div class="field"><label for="stock"><strong>Начальное количество на складе</strong></label><br /><?php create_form_input('stock', 'text', $add_product_errors); ?></div>
		
		<div class="field"><label for="description"><strong>Описание</strong></label><br /><?php create_form_input('description', 'textarea', $add_product_errors); ?></div>

		<div class="field"><label for="image"><strong>Изображение</strong></label><br /><?php
		
		// Проверка наличия ошибок
		// Если у нас есть такое значение - $add_product_errors['image'], то оформляем всё классом css как ошибку и пишем это значение прямо в html.
		if (array_key_exists('image', $add_product_errors)) {
			
			echo '<span class="error">' . $add_product_errors['image'] . '</span><br /><input type="file" name="image" class="error" />';
			
		} else { // ошибки отсутствуют

			echo '<input type="file" name="image" />';

			// Если файл существует (после предыдущей передачи формы и при наличии других ошибок),
			// информация о файле хранится в сеансе, а также создается заметка о существовании этого файла		
			if (isset($_SESSION['image'])) {
				echo "<br />Currently '{$_SESSION['image']['file_name']}'";
			}

		} // завершение этого блока идентификации ошибок IF-ELSE
		?></div>

	<br clear="all" />	
		
	<div class="field"><input type="submit" value="Добавить товар" class="button" /></div>
	
	</fieldset>

</form> 

<?php // включение HTML-футера
include('./includes/footer.html');
?>