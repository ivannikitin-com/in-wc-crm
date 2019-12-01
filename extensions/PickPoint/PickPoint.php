<?php
/**
 * Класс расширения PickPoint
 * Подробную информацию см. в файле info.md
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;

class PickPoint extends BaseAdminPage
{
    /**
     * Методы доставки
     * @var mixed
     */
    private $shippingMethods;


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
        add_action( 'wp_ajax_get_orders', array( $this, 'get_orders' ) );
        add_action( 'wp_ajax_send_orders', array( $this, 'send_orders' ) );

        // Методы доставки
        $this->shippingMethods = $this->getShippingMethods();

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
     * Возвращает true если этому расширению требуются настройки
     * @return bool
     */
    public function hasSettings()
    {
        return true;
    }

    /**
     * Показывает секцию настроек
     */
    public function showSettings()
    {
        @include( Plugin::get()->path . 'extensions/PickPoint/views/settings.php' );
    }

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['pickpoint-api-endpoint'] = isset( $_POST['pickpoint-api-endpoint'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-endpoint'] ) ) : '';
        $this->settings['pickpoint-api-login'] = isset( $_POST['pickpoint-api-login'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-login'] ) )  : '';
        $this->settings['pickpoint-api-password'] = isset( $_POST['pickpoint-api-password'] ) ? sanitize_text_field( $_POST['pickpoint-api-password'] ) : '';
        $this->settings['pickpoint-api-ikn'] = isset( $_POST['pickpoint-api-ikn'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-ikn'] ) ) : '';
        return parent::saveSettings();
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

        /* Font Awesome */
        wp_enqueue_style( 'fa-5', Plugin::get()->url . 'asserts/fontawesome-free-5.11.2-web/css/all.css' );

        // DataTables
        $dataTables = IN_WC_CRM . '-datatables';
        wp_register_script( 
            $dataTables, 
            Plugin::get()->url . 'asserts/DataTables/datatables.min.js', 
            array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $dataTables ); 
        wp_enqueue_style( $dataTables, Plugin::get()->url . 'asserts/DataTables/datatables.min.css' );         

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
            'orderStatuses' => wc_get_order_statuses(),
            'shippingMethods' => $this->shippingMethods,
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

        // Корректировка массива по данным плагина Advanced Shipping methods
        unset($shippingMethods['advanced_shipping']);
		// TODO: Сделать это или настройкой или как-то читать из плагина Advanced Shipping
        $shippingMethods['advanced_shipping_pickpoint'] = __( 'Пункты выдачи заказов', IN_WC_CRM );

        return $shippingMethods;
    }

    /**
     * @const Максимальное число заказов, выбираемых из БД
     */
    const ORDER_LIMIT = 250;


    /**
     * Обрабатывает AJAX запрос данных
     */
    public function get_orders()
    {
        $result = array();

        // Параметры запроса
        // https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
        $args = array(
            'limit'     => self::ORDER_LIMIT,
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

        // Требуемый метод доставки
        $shipping_method = ( isset( $_POST['shipping_method'] ) ) ? sanitize_text_field( $_POST['shipping_method'] ) : '';

        foreach ($orders as $order)
        {
            if ( ! empty( $shipping_method ) )
            {
                // Фильтруем по методам доставки
                if ( $order->get_shipping_method() != $this->shippingMethods[ $shipping_method ] ) 
                    continue;
            }

            $result[] = array(
                'id' => $order->get_order_number(),
                'date' => $order->get_date_created()->date_i18n('d.m.Y'),
                'customer' => $order->get_formatted_billing_full_name(),
                'total' => $order->calculate_totals(),
                'payment_method' => $order->get_payment_method_title(),
                'shipping_method' => $order->get_shipping_method(),
                'stock' => 'Склад'
            );

        }

        echo json_encode( $result );
        wp_die();
    }

    /**
     * Обрабатывает AJAX запрос на отправку данных
     */
    public function send_orders()
    {
        // Требуемый метод доставки
        $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';

        if ( empty( $idsString ) )
        {
            esc_html_e( 'Данные для отправки не выбраны. Щелкайте по строкам таблицы для их выделения', IN_WC_CRM );
            wp_die();            
        }

        // Параметры удаленного сервера
        $url = $this->getParam('pickpoint-api-endpoint', '');
        $login = $this->getParam('pickpoint-api-login', '');
        $password = $this->getParam('pickpoint-api-password', '');
        if ( empty( $url ) || empty( $login ) || empty( $password ) )
        {
            esc_html_e( 'Для корректной работы необходимо указать настройки расширения Pickpoint!', IN_WC_CRM );
            wp_die();              
        }

        // Логин на удаленный сервер
        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => '{ "Login" : "' . $login . '", "Password" : "' . $password . '" }',
        );
        $response = wp_remote_post( $url . '/login', $args );

        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            $error_message = $response->get_error_message();
            echo $error_message . PHP_EOL . 
                'URL: ' . $url . '/login' . PHP_EOL .
                'Login: ' . $login . PHP_EOL .
                'Passsword: ' . $password;
            wp_die();
        }     
        
        
        try
        {
            $responseObj = json_decode( $response['body'] );
            if ( $responseObj->ErrorMessage )
            {
             echo $responseObj->ErrorMessage;
             wp_die();               
            }
        }
        catch (\Exception $error)
        {
            var_export($response);
            wp_die();
        }


        // Продолжение следует
        var_export($responseObj);
        wp_die();

        $ids = explode(',', $idsString);
        $args = array(
            'limit'     => self::ORDER_LIMIT,
            'return'    => 'objects',
            'post__in'  => $ids      
        );
        $orders = wc_get_orders( $args );

        foreach( $orders as $order )
        {

        }
    }    
}