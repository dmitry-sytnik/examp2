<?php

// Начальная страница администратора
// Этот сценарий создан в главе 11

// Чтобы управлять отображением сообщений об ошибках, перед выполнением произвольного кода PHP нужно подключить файл конфигурации
require('../includes/config.inc.php');

// Настройка названия страницы и включение заголовка
$page_title = 'Кофе - администрирование';
include('./includes/header.html');
// Файл заголовка начинает сеанс.
?>

<h3>Ссылки</h3>
<ul>
<li><a href="add_specific_coffees.php">Добавить кофе</a></li>
<li><a href="add_other_products.php">Добавить сувениры</a></li>
<li><a href="add_inventory.php">Пополнить запасы</a></li>
</ul>

<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque dapibus, felis at hendrerit commodo, nisl risus gravida quam, vel consequat leo quam suscipit purus. Proin purus justo, ornare vitae luctus sit amet, placerat quis dolor. Cras sit amet erat id quam posuere bibendum vitae non orci. Phasellus lacus sem, egestas sit amet scelerisque sit amet, venenatis ut elit. Maecenas diam nisi, tempor eu vestibulum placerat, varius in massa. Aenean scelerisque neque vel mi porta accumsan. Pellentesque euismod ipsum nec dui blandit at facilisis felis commodo. Suspendisse egestas mi et magna venenatis aliquam. Integer scelerisque ligula et dolor pulvinar dignissim. Ut interdum fringilla dignissim. Mauris eu fringilla felis. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Maecenas in ipsum dui, at gravida elit. Donec tincidunt scelerisque faucibus. Vivamus eget metus lectus. Aliquam erat volutpat.</p>

<?php include('./includes/footer.html'); ?>