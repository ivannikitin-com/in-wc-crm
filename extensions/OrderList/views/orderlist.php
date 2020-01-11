<?php



?>
<!-- Элементы управления выборкой заказов -->
<section id="orderListControls" style="text-align: center">
  <span>
      <label for="dateFrom"><?php esc_html_e( 'Начальная дата', IN_WC_CRM ); ?></label>
      <input id="dateFrom" type="text" class="datePickers">

      <label for="dateTo"><?php esc_html_e( 'Конечная дата', IN_WC_CRM ); ?></label>
      <input id="dateTo" type="text" class="datePickers">
  </span>
  <span>
      <label for="shipping_method"><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></label>
      <select id="shipping_method">
        <option value=""><?php esc_html_e( 'Все методы', IN_WC_CRM ); ?></option>
      </select>
  </span>
</section>

<!-- Элементы управления действиями с заказами -->
<section id="orderListActions" style="text-align: center">
    <?php do_action( 'inwccrm_orderlist_actions_before' ) ?>
    <button id="btnLoadOrders"><i class="fas fa-search"></i>&nbsp;<?php esc_html_e( 'Найти заказы', IN_WC_CRM ); ?></button>
    <?php do_action( 'inwccrm_orderlist_actions_after' ) ?>
</section>

<!-- Таблица данных -->
<section id="orderListDataTable">
    <style>
    #orderTable tbody tr td { text-align: center }
    </style>
    <small><?php esc_html_e( 'Для выделения строки просто щелкните по ней', IN_WC_CRM ); ?></small>
    <table id="orderTable" class="display" style="width:100%">
        <thead>
            <?php
                $columns = $this->getColumns();
                $columns['_actions'] = __( 'Действия', IN_WC_CRM );
                ob_start();
                foreach ($columns as $column)
                {
                    echo '<th>', $column, '</th>', PHP_EOL;
                }
                $columnHeader = ob_get_contents();
                ob_end_clean();

                echo $columnHeader;
            ?>        
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <?php echo $columnHeader ?>
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