<?php
/**
 * Отрисовка страницы списка заказов
 * Выполняется в контексте метода PickPoint::renderAdminPageContent()
 */
@include 'header.php';

?>
<section id="pickpointControls" style="text-align: center">
  <span>
      <label for="dateFrom"><?php esc_html_e( 'Начальная дата', IN_WC_CRM ); ?></label>
      <input id="dateFrom" type="text" class="datePickers">

      <label for="dateTo"><?php esc_html_e( 'Конечная дата', IN_WC_CRM ); ?></label>
      <input id="dateTo" type="text" class="datePickers">
  </span>
  <br>
  <span>
      <label for="shipping_method"><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></label>
      <select id="shipping_method">
        <option value=""><?php esc_html_e( 'Все методы', IN_WC_CRM ); ?></option>
      </select>
  </span>

  <button id="btnLoadOrders"><?php esc_html_e( 'Найти заказы', IN_WC_CRM ); ?></button>
  <button id="btnSendOrders"><?php esc_html_e( 'Передать в PickPoint', IN_WC_CRM ); ?></button>

</section>

<hr>

<section id="pickpointDataTable">
<style>
  #orderTable tbody tr td { text-align: center }
</style>
<small><?php esc_html_e( 'Для выделения строки просто щелкните по ней', IN_WC_CRM ); ?></small>
<table id="orderTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e( '№ Заказа', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Дата', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'ФИО', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Сумма', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Оплата', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Стоимость доставки', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Действия', IN_WC_CRM ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th><?php esc_html_e( '№ Заказа', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Дата', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'ФИО', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Сумма', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Оплата', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Стоимость доставки', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Действия', IN_WC_CRM ); ?></th>
            </tr>
        </tfoot>
    </table>
</section>

<div id="loadBanner" style="
  display: none;
  position: absolute;
  top: 0;
  left: 48%;
  margin: auto;
  padding: 10px;
  background-color: #ffc;
  border-radius: 8px;
  border: 1px solid grey">
<?php esc_html_e( 'Загрузка и обработка данных', IN_WC_CRM ); ?>
</div>