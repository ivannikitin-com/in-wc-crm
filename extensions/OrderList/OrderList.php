<?php
/**
 * Расширение выводит списко пользователей
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Shipping as WC_Shipping;
use \WC_Payment_Gateways as WC_Payment_Gateways;

class OrderList extends BaseAdminPage
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) 
            return;
		
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'wp_ajax_inwccrm_get_order_list', array( $this, 'getOrderList' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'Заказы', IN_WC_CRM );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Работа со списоком заказов', IN_WC_CRM );
    }
    
    /**
     * Отрисовывает содержимое страницы
     */
    protected function renderAdminPageContent()
    {
        @include 'views/orderlist.php';
    }

    /**
     * Формирует и возвращает массив колонок таблицы заказов
     * @return mixed
     */
    private function getColumns()
    {
        return apply_filters( 'inwccrm_orderlist_columns', array(
          'id'              => __( '№ Заказа', IN_WC_CRM ),
          'date'            => __( 'Дата', IN_WC_CRM ),
          'customer'        => __( 'ФИО', IN_WC_CRM ),
          'total'           => __( 'Сумма', IN_WC_CRM ),
          'payment_method'  => __( 'Оплата', IN_WC_CRM ),
          'shipping_method' => __( 'Доставка', IN_WC_CRM ),
          'shipping_cost'   => __( 'Стоимость доставки', IN_WC_CRM )
        ) );
    }

    /**
     * Возвращает значение поля заказа
     * @param string $column  Колонка
     * @param mixed $order Заказ
     * @return mixed
     */
    private function getOrderColumn($column, $order)
    {
        switch ( $column )
        {
            case 'id':
                $data = $order->get_order_number();
                break;

            case 'date':
                $data = $order->get_date_created()->date_i18n('d.m.Y');
                break;

            case 'customer':
                $data = $order->get_formatted_billing_full_name();
                break;

            case 'total':
                $data = $order->calculate_totals();
                break;
                
            case 'payment_method':
                $data = $order->get_payment_method_title();
                break;

            case 'shipping_method':
                $data = $order->get_shipping_method();
                break;

            case 'shipping_cost':
                $data = $order->get_shipping_total();
                break;                

            default: 
                $data = '';
        }
        return apply_filters( 'inwccrm_orderlist_column_data', $data, $column, $order );
    }


    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        // jQuery
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style( 'jquery-ui-theme', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );

        // Select 2
        wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all' );
        wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style( 'select2css' );
        wp_enqueue_script( 'select2' ); 

        /* Font Awesome */
        wp_enqueue_style( 'fa-5', Plugin::get()->url . 'asserts/fontawesome-free-5.11.2-web/css/all.css' );

        // DataTables
        $dataTables = 'DataTables';
        wp_register_script( 
            $dataTables, 
            Plugin::get()->url . 'asserts/DataTables/datatables.min.js', 
            array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' ),
            '1.10.20', 
            true );
        wp_enqueue_script( $dataTables ); 
        wp_enqueue_style( $dataTables, Plugin::get()->url . 'asserts/DataTables/datatables.min.css' );         

        // Скрипты расширения
        $scriptID = IN_WC_CRM . '-orderlist';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/OrderList/js/orderlist.js', 
            array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker', $dataTables ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );
        
        // Параметры и строки для скриптов
        $objectName = 'IN_WC_CRM_OrderList';
        $data = array(
            'viewOrderTitle' => __( 'Просмотреть заказ', IN_WC_CRM ),
            'pageLength' => apply_filters( 'inwccrm_orderlist_page_length', 10 ),
            'columns' => $this->getColumns(),
        );
        wp_localize_script( $scriptID, $objectName, $data );
    }

    /**
     * Возвращает массив id => title методов доставки
     * @return mixed
     */
    private function getShippingMethods()
    {
        $shippingMethods = array();
        $shipping = new WC_Shipping();        
        foreach( $shipping->get_shipping_methods() as $key => $method )
        {
            $shippingMethods[$key] = $method->method_title;
        }
        return apply_filters( 'inwccrm_orderlist_shipping_methods', $shippingMethods );
    }

    /**
     * Возвращает массив id => title методов оплаты
     * @return mixed
     */
    private function getPaymentMethods()
    {
        $paymentMethods = array();
        $gateways = new WC_Payment_Gateways();
        foreach( $gateways->get_available_payment_gateways() as $key => $gateway )
        {
            if ( $gateway->enabled != 'yes') continue;
            $paymentMethods[$key] = $gateway->method_title;
        }
        return apply_filters( 'inwccrm_orderlist_payment_methods', $paymentMethods );
    }    

    /**
     * Возвращает массив id => title статусов заказов
     * @return mixed
     */
    private function getOrderStatuses()
    {
        return apply_filters( 'inwccrm_orderlist_order_statuses', wc_get_order_statuses() );
    }

    /**
     * Обрабатывает AJAX запрос данных
     */
    public function getOrderList()
    {
        // Параметры запроса
        $orderStatus = ( isset( $_POST['order_status'] ) ) ? trim( sanitize_text_field( $_POST['order_status'] ) ) : '';

        $shippingMehods = array();
        $shippingMehodIds = ( isset( $_POST['shipping_method'] ) ) ? (array) $_POST['shipping_method'] : array();
        $allShippingMethods = $this->getShippingMethods();
        foreach ($shippingMehodIds as $shippingId) {
            $shippingMehods[] = $allShippingMethods[ $shippingId ];
        }

        $paymentMethod = ( isset( $_POST['payment_method'] ) ) ? trim( sanitize_text_field( $_POST['payment_method'] ) ) : '';
        $dateFrom = ( isset( $_POST['dateFrom'] ) ) ? trim( sanitize_text_field( $_POST['dateFrom'] ) ) : '';
        $dateTo = ( isset( $_POST['dateTo'] ) ) ? trim( sanitize_text_field( $_POST['dateTo'] ) ) : '';

        // Параметры запроса
        // https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
        $args = array(
            'limit'     => apply_filters( 'inwccrm_orderlist_datatable_order_limit', 100 ),
            'orderby'   => 'date',
            'order'     => 'DESC',
            'return'    => 'objects'
        );
        
        // Статус заказов
        if ( ! empty( $orderStatus ) )
        {
            $args['status'] = $orderStatus;
        }

        // Метод оплаты
        if ( ! empty( $paymentMethod ) )
        {
            $paymentMethods = $this->getPaymentMethods();
            $args['payment_method_title'] = $paymentMethods[ $paymentMethod ];
        }        

        // Даты запроса
        if ( $dateFrom && $dateTo )
        {
            $args['date_created'] = $dateFrom . '...' . $dateTo;
        }
        else
        {
            if ( empty( $dateFrom )  && ! empty( $dateTo ) )
            {
                $args['date_created'] = '<=' . $dateTo;
            }
            if ( ! empty( $dateFrom )  && empty( $dateTo ) )
            {
                $args['date_created'] = '>=' . $dateFrom;
            }
        } 
        
        // Фильтр параметров запроса
        $args = apply_filters( 'inwccrm_orderlist_query_args', $args, $_POST );

        // Запрос заказов
        $result = array();

        $orderColumns = $this->getColumns();
        $orders = wc_get_orders( $args );
        foreach ($orders as $order)
        {
            if ( ! empty( $shippingMehods ) )
            {
                // Фильтруем по методам доставки
                if ( ! in_array( strtolower($order->get_shipping_method()), array_map('strtolower', $shippingMehods )))
                    continue;
            }

            // Произвольная фильтрация списка заказов. Если true запись попадает на вывод
            if ( ! apply_filters( 'inwccrm_orderlist_custom_filter', true, $order, $_POST ) ) continue;

            $orderData = array();
            foreach ( $orderColumns as $column => $columnTitle )
            {
                $orderData[$column] = $this->getOrderColumn( $column, $order );
            }

            $result[] = $orderData;
        }
        
        // Передача результатов
        echo json_encode( $result );
        wp_die();
    }
}