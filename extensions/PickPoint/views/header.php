<?php
/**
 * Отрисовка шапки страницы списка заказов
 * Выполняется в контексте метода PickPoint::renderAdminPageContent()
 */
$currentURL = $_REQUEST[ 'REQUEST_URI' ];
$baseURL = preg_replace('/(\&view=[a-z0-9_-]*)\&?/', '', $currentURL)
?>
<header>
    <style></style>
    <ul>
        <li><a href="<?php echo $baseURL?>&view=order-list">Список заказов</a></li>
    </ul>
</header>