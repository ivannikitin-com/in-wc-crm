<?php
/**
 * Отрисовка страницы списка заказов
 * Выполняется в контексте метода PickPoint::renderAdminPageContent()
 */
@include 'header.php';


// Запрос списка заказов
$query = new WC_Order_Query( array(
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'objects',
) );
$orders = $query->get_orders();

var_dump($orders);