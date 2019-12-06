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
        $this->settings['pickpoint-order-status'] = isset( $_POST['pickpoint-order-status'] ) ? trim(sanitize_text_field( $_POST['pickpoint-order-status'] ) ) : 'wc-processing';
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
            'return'    => 'objects',
            'status'    => $this->getParam( 'pickpoint-order-status', 'wc-processing' ),     
        );

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
     * @var SessionId
     */
    private $sessionId;


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

        // Session ID
        $this->sessionId = $responseObj->SessionId;

        // Запрос выбранных заказов
        $ids = explode(',', $idsString);
        $args = array(
            'limit'     => self::ORDER_LIMIT,
            'return'    => 'objects',
            'post__in'  => $ids      
        );
        $orders = wc_get_orders( $args );

        $response = '';
        foreach( $orders as $order )
        {
            $orderData = $this->createShipment( $order );
            $args = array(
                'timeout'   => 60,
                'blocking'  => true,   
                'headers'   => array('Content-Type' => 'application/json'),
                'body'      => $orderData,
            );
            $response .= wp_remote_post( $url . '/CreateShipment', $args );       

        }
    }

    /**
     * Метод формирует данные для отправки
     */
    private function createShipment( $order )
    {
        if ( empty( $this->sessionId ) ) return false;

        // Данные
        $requestId = sha1( microtime() . __CLASS__ );   //<Идентификатор запроса, используемый для ответа. Указывайте уникальное число (50 символов)>
        $ikn =  $this->getParam( 'pickpoint-api-ikn', '' ); //<ИКН – номер договора (10 символов)>
        if ( empty( $ikn ) ) return false;

        // Пользователь
        $clientName = ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
            $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
            $order->get_billing_last_name() . ' '  . $order->get_billing_first_name();
        $mobilePhone = $order->get_billing_phone();
        $email = $order->get_billing_email();

        // Заказ
        $orderId = $order->get_order_number();
        $orderTitleRus = esc_html__( 'Заказ №', IN_WC_CRM ) . $orderId;
        $orderTitleEn = esc_html__( 'Order #', IN_WC_CRM ) . $orderId;
        $sum = $order->get_total();

        // Постомат
        preg_match('/.*([\d]{4}-[\d]{3}).*/', $order->get_shipping_address_1(), $output_array);

        $postamatNumber = ( isset($output_array[1] ) ) ? $output_array[1] : '';   
        $postageType = ( $order->payment_method() == 'cod' ) ? '10003' : '10001';

        $senderCityName = $order->get_shipping_city();
        $senderRegionName = $order->get_shipping_state();

        // Магазин
        $shopName = get_option( 'blogname' );

        $SubEncloses = array();        
        foreach ($order->get_items() as $item)
        {
            if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) continue;

            $product = wc_get_product( $item->get_product_id() );
            $ProductCode = $product->get_sku();
            $Name = $item->get_name();
            $Price = $item->get_subtotal();
            $Quantity = $item->get_quantity();
            $Description = '';
            $Upi = '';
            $SubEnclose = <<<SUBENCLOSE
                {
                    "ProductCode": "{$ProductCode}",
                    "GoodsCode": "",
                    "Name": "{$Name}",
                    "Price": "{$Price}",
                    "Quantity": "{$Quantity}",
                    "Vat": "< Ставка НДС по товару >",
                    "Description": "",
                    "Upi": "{$ProductCode}"
                } 
SUBENCLOSE;
            array_push( $SubEncloses, $SubEnclose );
        }
        $SubEnclosesStr = implode(',', $SubEncloses);

        $places = array();
        $place = <<<PLACES
            {
                "BarCode": "",
                "GCBarCode": "",
                "CellStorageType": "0",
                "Width": "",
                "Height": "",
                "Depth": "",
                "Weight": "",
                "SubEncloses": [
                    {$SubEnclosesStr}
                ]
            }
PLACES;

        array_push( $places, $place );
        $placesStr = implode(',', $places);

        $data  = <<<DATA
        {
            "SessionId": "{$this->sessionId}",
            "Sendings": [
              {
                "EDTN": "{$requestId}",
                "IKN": "{$ikn}",
                "ClientName": "{$clientName}",
                "TitleRus": "{$orderTitleRus}",
                "TitleEng": "{$orderTitleEn}",
                "Invoice": {
                  "SenderCode": "{$orderId}",
                  "Description": "{$shopName}",
                  "RecipientName": "$clientName",
                  "PostamatNumber": "{$postamatNumber}",
                  "MobilePhone": "{$$mobilePhone}",
                  "Email": "{$email}",
                  "PostageType": "{$postageType}",
                  "GettingType": "<Тип сдачи отправления, (см. таблицу ниже) обязательное поле >",
                  "PayType": "1",
                  "Sum": "{$sum}",
                  "PrepaymentSum": "0",
                  "DeliveryVat": "< Ставка НДС по сервисному сбору >",
                  "DeliveryFee": "< Сумма сервисного сбора с НДС >",
                  "InsuareValue": "<Страховка (число, два знака после запятой)>",
                  "DeliveryMode": "1",
                  "SenderCity": {
                    "CityName": "{$senderCityName}",
                    "RegionName": "{$senderRegionName}"
                  },
                  "ClientReturnAddress": {
                    "CityName": "<Название города (50 символов)>",
                    "RegionName": "<Название региона (50 символов)>",
                    "Address": "<Текстовое описание адреса (150 символов)>",
                    "FIO": "<ФИО контактного лица (150 символов)>",
                    "PostCode": "<Почтовый индекс (20 символов)>",
                    "Organisation": "<Наименование организации (100 символов)>",
                    "PhoneNumber": "<Контактный телефон, обязательное поле (допускаются круглые скобки и тире)>",
                    "Comment": "<Комментарий (255 символов)>"
                  },
                  "UnclaimedReturnAddress": {
                    "CityName": "<Название города (50 символов)>",
                    "RegionName": "<Название региона (50 символов)>",
                    "Address": "<Текстовое описание адреса (150 символов)>",
                    "FIO": "<ФИО контактного лица (150 символов)>",
                    "PostCode": "<Почтовый индекс (20 символов)>",
                    "Organisation": "<Наименование организации (100 символов)>",
                    "PhoneNumber": "<Контактный телефон, обязательное поле (допускаются круглые скобки и тире)>",
                    "Comment": "<Комментарий  (255 символов)>"
                  },
                  "Places": [
                    {$placesStr}
                  ]
                }
              }
            ]
          }
DATA;
        return $data;
    }
}