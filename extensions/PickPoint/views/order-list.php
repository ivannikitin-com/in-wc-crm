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
                <th><?php esc_html_e( '№ Заказа', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'ФИО', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Сумма', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Платеж', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Склад', IN_WC_CRM ); ?></th>
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
                <th>&nbsp;</th>
                <th><?php esc_html_e( '№ Заказа', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'ФИО', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Сумма', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Платеж', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></th>
                <th><?php esc_html_e( 'Склад', IN_WC_CRM ); ?></th>
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
  background-color: #ffc">
<?php esc_html_e( 'Загрузка', IN_WC_CRM ); ?>
</div>