<?php
/**
 * Отрисовка страницы списка заказов
 * Выполняется в контексте метода PickPoint::renderAdminPageContent()
 */
@include 'header.php';

?>
<section id="pickpointControls">
  <span class="ui-widget">
      <label for="statuses"><?php esc_html_e( 'Статусы заказов', IN_WC_CRM ); ?></label>
      <input id="statuses" size="50">
  </span>

  <span>
      <label for="dateFrom"><?php esc_html_e( 'Начальная дата', IN_WC_CRM ); ?></label>
      <input id="dateFrom" type="text" class="datePickers">

      <label for="dateTo"><?php esc_html_e( 'Конечная дата', IN_WC_CRM ); ?></label>
      <input id="dateTo" type="text" class="datePickers">
  </span>

  <button><?php esc_html_e( 'Найти заказы', IN_WC_CRM ); ?></button>
</section>

<hr>

<section id="pickpointDataTable">
<style>
  #orderTable tbody tr td { text-align: center }
</style>
<table id="orderTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th>№ Заказа</th>
                <th>ФИО</th>
                <th>Сумма</th>
                <th>Платеж</th>
                <th>Доставка</th>
                <th>Склад</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>*</td>
                <td>42</td>
                <td>Пупкин</td>
                <td>6100</td>
                <td>Наличные</td>
                <td>PickPoint</td>
                <td>15</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
            <th>&nbsp;</th>
                <th>№ Заказа</th>
                <th>ФИО</th>
                <th>Сумма</th>
                <th>Платеж</th>
                <th>Доставка</th>
                <th>Склад</th>
            </tr>
        </tfoot>
    </table>
</section>




<?php
// Запрос списка заказов
$query = new WC_Order_Query( array(
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'objects',
) );
$orders = $query->get_orders();

//var_dump($orders);