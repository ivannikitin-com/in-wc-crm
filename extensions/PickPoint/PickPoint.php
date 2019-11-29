<?php
/**
 * Класс расширения PickPoint
 * Подробную информацию см. в файле info.md
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;

class PickPoint extends BaseAdminPage
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'wp_ajax_get_orders', array( $this, 'get_orders' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'PickPoint передача реестров заказов для отправки', IN_WC_CRM );
    }

    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function getAdminPageMenuTitle()
    {
        return __( 'PickPoint', IN_WC_CRM );
    }

    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function enqueueScripts()
    {
        // jQuery
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style( 'jquery-ui-theme', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );

        /* Font Awesome */
        wp_enqueue_style( 'fa-5', Plugin::get()->url . 'asserts/fontawesome-free-5.11.2-web/css/all.css' );

        // DataTables
        $dataTables = IN_WC_CRM . '-datatables';
        wp_register_script( 
            $dataTables, 
            Plugin::get()->url . 'extensions/PickPoint/DataTables/datatables.min.js', 
            array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $dataTables ); 
        wp_enqueue_style( $dataTables, Plugin::get()->url . 'extensions/PickPoint/DataTables/datatables.min.css' );         

        // Скрипты расширения
        $scriptID = IN_WC_CRM . '-pickpoint';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/PickPoint/js/pickpoint.js', 
            array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker', $dataTables ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );    
        
        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_Pickpoint';
        $data = array(
            'viewOrderTitle' => __( 'Просмотр и редактирование заказа', IN_WC_CRM ),
            'orderStatuses' => wc_get_order_statuses()
        );
        wp_localize_script( $scriptID, $objectName, $data );

    }

    /**
     * Отрисовывает содержимое страницы
     */
    protected function renderAdminPageContent()
    {
        // Какую страницу показываем
        $view = ( isset( $_GET[ 'view' ] ) ) ? $_GET[ 'view' ] : 'order-list';
        switch ( $view )
        {
            case 'order-list' : 
                @include 'views/order-list.php';
                break;

            default :
                @include 'views/order-list.php';
        }
    }

    /**
     * Обрабатывает AJAX запрос данных
     */
    public function get_orders()
    {
        $result = array();

        // Параметры запроса
        // https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
        $args = array(
            'limit'     => 100,
            'orderby'   => 'date',
            'order'     => 'DESC',
            'return'    => 'objects'            
        );

        $status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( $_POST['status'] ) : '';
        if ( ! empty( $status ) )
        {
            $args['status'] = $status;
        }

        $dateFrom = ( isset( $_POST['dateFrom'] ) ) ? sanitize_text_field( $_POST['dateFrom'] ) : '';
        $dateTo = ( isset( $_POST['dateTo'] ) ) ? sanitize_text_field( $_POST['dateTo'] ) : '';


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
        
        // Запрос заказов
        $orders = wc_get_orders( $args );


        foreach ($orders as $order)
        {
            $result[] = array(
                'checkbox' => '',
                'id' => $order->get_order_number(),
                'date' => $order->get_date_created()->date_i18n('d.m.Y'),
                'customer' => $order->get_formatted_billing_full_name(),
                'total' => $order->calculate_totals(),
                'payment' => $order->get_payment_method_title(),
                'shipping' => $order->get_shipping_method(),
                'stock' => 'Склад'
            );

        }

        echo json_encode( $result );
        wp_die();
    }
}