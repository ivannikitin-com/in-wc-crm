<?php
// Методы доставки
$shippingMethods = array_merge(
    array('_all' => __( 'Все методы', IN_WC_CRM )), 
    $this->getShippingMethods()
);

// Методы оплаты
$paymentMethods = array_merge(
    array('_all' => __( 'Все методы', IN_WC_CRM )), 
    $this->getPaymentMethods()
);


// Статусы заказов
$orderStatuses = array_merge(
    array('_all' => __( 'Все статусы', IN_WC_CRM )), 
    $this->getOrderStatuses()
);
$defaultStatus = apply_filters( 'inwccrm_orderlist_default_status', 'wc-processing' );

?>
<!-- Элементы управления выборкой заказов -->
<section id="orderListControls" style="text-align: center">
  <style>
    #orderListControls .datePicker { width: 85px }
  </style>
  <?php do_action( 'inwccrm_orderlist_controls_before' ) ?>
  <span>
      <label for="dateFrom"><?php esc_html_e( 'Начальная дата', IN_WC_CRM ); ?></label>
      <input id="dateFrom" type="text" class="datePicker">

      <label for="dateTo"><?php esc_html_e( 'Конечная дата', IN_WC_CRM ); ?></label>
      <input id="dateTo" type="text" class="datePicker">
  </span>
  <hr>
  <span>
      <label for="shipping_method"><?php esc_html_e( 'Доставка', IN_WC_CRM ); ?></label>
      <select id="shipping_method">
        <?php foreach ( $shippingMethods as $methodCode => $methodTitle ): ?>
            <option value="<?php echo $methodCode?>"><?php echo $methodTitle ?></option>
        <?php endforeach ?>
      </select>
  </span>
  <span>
      <label for="payment_method"><?php esc_html_e( 'Оплата', IN_WC_CRM ); ?></label>
      <select id="payment_method">
        <?php foreach ( $paymentMethods as $methodCode => $methodTitle ): ?>
            <option value="<?php echo $methodCode?>"><?php echo $methodTitle ?></option>
        <?php endforeach ?>
      </select>
  </span>  
  <span>
      <label for="order_status"><?php esc_html_e( 'Статус заказа', IN_WC_CRM ); ?></label>
      <select id="order_status">
        <?php foreach ( $orderStatuses as $statusCode => $statusTitle ): ?>
            <option value="<?php echo $statusCode?>" <?php selected( $statusCode, $defaultStatus ); ?>><?php echo $statusTitle ?></option>
        <?php endforeach ?>
      </select>
  </span>
  <?php do_action( 'inwccrm_orderlist_controls_after' ) ?>
</section>
<hr>
<!-- Элементы управления действиями с заказами -->
<section id="orderListActions" style="text-align: center">
    <style>
        #orderListActions button { margin: 5px }
    </style>
    <?php do_action( 'inwccrm_orderlist_actions_before' ) ?>
    <button id="btnLoadOrders"><i class="fas fa-search"></i>&nbsp;<?php esc_html_e( 'Найти заказы', IN_WC_CRM ); ?></button>
    <?php do_action( 'inwccrm_orderlist_actions_after' ) ?>
</section>

<!-- Таблица данных -->
<section id="orderListDataTable">
    <style>
    #orderTable tbody tr td { text-align: center }
    #orderListDataTable { position: relative }
    #orderListDataTableSelectedRowsCount { position: absolute; left: 20em; top: 1.6em; display: none }
    #orderListDataTableSelectedRowsCount span { font-weight: bold }
    </style>
    <small><?php esc_html_e( 'Для выделения строки просто щелкните по ней', IN_WC_CRM ); ?></small>
    <div id="orderListDataTableSelectedRowsCount">
        <?php esc_html_e( 'Выделено строк: ', IN_WC_CRM ); ?>
        <span>&nbsp;</span>
    </div>
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