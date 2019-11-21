<?php
/**
 * Вывод страницы расширения
 * Контекст выполнения -- метод LogisticsRegistry::renderAdminPageContent()
 */
?>
<div>Отладка</div>

<?php 
$result = $this->wc->getActiveOrders();
var_dump($result);

?>