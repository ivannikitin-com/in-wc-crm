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
	 * Лог-файл
	 */
	const LOGFILE = 'pickpoint.log';
	
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
        $this->settings['pickpoint-shopOrganization'] = isset( $_POST['pickpoint-shopOrganization'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopOrganization'] ) ) : '';
        $this->settings['pickpoint-shopPhone'] = isset( $_POST['pickpoint-shopPhone'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopPhone'] ) ) : '';
        $this->settings['pickpoint-shopManagerName'] = isset( $_POST['pickpoint-shopManagerName'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopManagerName'] ) ) : '';
        $this->settings['pickpoint-shopComment'] = isset( $_POST['pickpoint-shopComment'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopComment'] ) ) : '';
        
        
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
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),
            'shippingMethods' => apply_filters( 'inwccrm_pickpoint_header_shipping_methods', $this->shippingMethods ),
            'pageLength' => apply_filters( 'inwccrm_pickpoint_datatable_page_length', 10 ),			
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
            'limit'     => apply_filters( 'inwccrm_pickpoint_datatable_order_limit', self::ORDER_LIMIT ),
            'orderby'   => 'date',
            'order'     => 'DESC',
            'return'    => 'objects',
            'status'    => $this->getParam( 'pickpoint-order-status', 'wc-processing' ),     
        );

        $dateFrom = ( isset( $_POST['dateFrom'] ) ) ? trim( sanitize_text_field( $_POST['dateFrom'] ) ) : '';
        $dateTo = ( isset( $_POST['dateTo'] ) ) ? trim( sanitize_text_field( $_POST['dateTo'] ) ) : '';

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
                'id' => apply_filters( 'inwccrm_pickpoint_datatable_id', $order->get_order_number(), $order ),
                'date' => apply_filters( 'inwccrm_pickpoint_datatable_date', $order->get_date_created()->date_i18n('d.m.Y'), $order ),
                'customer' => apply_filters( 'inwccrm_pickpoint_datatable_customer', $order->get_formatted_billing_full_name(), $order ),
                'total' => apply_filters( 'inwccrm_pickpoint_datatable_total', $order->calculate_totals(), $order ),
                'payment_method' => apply_filters( 'inwccrm_pickpoint_datatable_payment_method', $order->get_payment_method_title(), $order ),
                'shipping_method' => apply_filters( 'inwccrm_pickpoint_datatable_shipping_method', $order->get_shipping_method(), $order ),
                'shipping_cost' => apply_filters( 'inwccrm_pickpoint_datatable_shipping_cost', $order->get_shipping_total(), $order )
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
        
        // Расшифровываем ответ
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
            'limit'     => apply_filters( 'inwccrm_pickpoint_datatable_order_limit', self::ORDER_LIMIT ),
            'return'    => 'objects',
            'post__in'  => $ids      
        );
        $orders = wc_get_orders( $args );

        // Подготаовливаем JSON данные
        $sendings = array();
        foreach( $orders as $order )
        {
            $sendings[] = $this->createShipment( $order );
        }
        $sendingsStr = implode( ',', $sendings );

        // Пакет для отправки
        $orderData = <<<END_OF_PACKET
        {
            "SessionId": "{$this->sessionId}",
            "Sendings": [{$sendingsStr}]
        }
END_OF_PACKET;

        // Формируем запрос
        $response = null;
        $responseStr = '';	
        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => $orderData,
        );

        // Запрос CreateShipment
        Plugin::get()->log( '--- CreateShipment: Server Request:', self::LOGFILE ); 
		Plugin::get()->log( $args, self::LOGFILE );
        
		$response = wp_remote_post( $url . '/CreateShipment', $args );       
        
		Plugin::get()->log( '--- CreateShipment: Server Responce:',self::LOGFILE ); 
		Plugin::get()->log( $response, self::LOGFILE );

		$responseObj =null;
        try
        {
            $responseObj = json_decode( $response['body'] );
            Plugin::get()->log( '--- responseObj: ', self::LOGFILE );
            Plugin::get()->log( $responseObj, self::LOGFILE );
            if ( $responseObj )
            {
				
				// Записываем успешные отправления
				Plugin::get()->log( '--- CreatedSendings: ', self::LOGFILE );
				Plugin::get()->log( $responseObj->CreatedSendings, self::LOGFILE );
				
				$responseStr .=  __( 'Отправление создано', IN_WC_CRM ) . ': ';
				
				Plugin::get()->log( '--- Sendings: ', self::LOGFILE );
				foreach($responseObj->CreatedSendings as $sending )
				{
					Plugin::get()->log( $sending, self::LOGFILE );
					$currentOrder = new \WC_Order( $sending->SenderCode );
					$currentOrder->add_order_note( 
						__( 'Pikpoint', IN_WC_CRM ) . ': ' . 
						__( 'Отправление создано', IN_WC_CRM ) . ': ' . 
						$sending->InvoiceNumber
					);
					$currentOrder->add_meta_data( __( 'Pikpoint InvoiceNumber', IN_WC_CRM ),  $sending->InvoiceNumber );
					$responseStr .= $sending->SenderCode . ',';
				}
				$responseStr .= PHP_EOL;
				
				// Записываем неуспешные отправления
				Plugin::get()->log( '--- RejectedSendings: ', self::LOGFILE );
				Plugin::get()->log( $responseObj->RejectedSendings, self::LOGFILE );
				
				$responseStr .=  __( 'Ошибки: ', IN_WC_CRM ) . ': ';
				
				Plugin::get()->log( '--- Sendings: ', self::LOGFILE );
				foreach($responseObj->RejectedSendings as $sending )
				{
					Plugin::get()->log( $sending, self::LOGFILE );
					$currentOrder = new \WC_Order( $sending->SenderCode );
					$currentOrder->add_order_note( 
						__( 'Pikpoint', IN_WC_CRM ) . ': ' . 
						__( 'Ошибка', IN_WC_CRM ) . ': ' . 
						$sending->ErrorMessage
					);					
					
					$responseStr .= $error->ErrorMessage .  ' ' . $error->SenderCode . PHP_EOL;
				}				
				
				$responseStr .= PHP_EOL;
				
            }
			else
			{
				Plugin::get()->log( __( 'Пустой ответ', IN_WC_CRM ), self::LOGFILE );
				$responseStr .= __( 'Пустой ответ', IN_WC_CRM );
			}
        }
        catch (\Exception $error)
        {
            $responseStr .= __( 'Ошибка получения и записи данных', IN_WC_CRM ) . ': ' . var_export( $error, true );
                
			Plugin::get()->log( __( 'Ошибка получения и записи данных', IN_WC_CRM ), self::LOGFILE ); 
			Plugin::get()->log( $error, self::LOGFILE );
            
        }
		
		echo $responseStr;
		wp_die();
    }

    /**
     * Метод формирует данные для отправки
     */
    private function createShipment( $order )
    {
        if ( empty( $this->sessionId ) ) return false;

        // Данные
        $requestId = apply_filters( 'inwccrm_pickpoint_requestId', sha1( microtime() . __CLASS__ ), $order );   //<Идентификатор запроса, используемый для ответа. Указывайте уникальное число (50 символов)>
        $ikn =  apply_filters( 'inwccrm_pickpoint_ikn', $this->getParam( 'pickpoint-api-ikn', '' ) ) ; //<ИКН – номер договора (10 символов)>
        if ( empty( $ikn ) ) return false;

        // Пользователь
        $clientName = apply_filters( 'inwccrm_pickpoint_clientName', 
            ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(), 
            $order );

        $mobilePhone = preg_replace('/[\s\-\(\)\.]/', '', $order->get_billing_phone() );
        $mobilePhone = apply_filters( 'inwccrm_pickpoint_mobilePhone', $mobilePhone, $order );
        $email = apply_filters( 'inwccrm_pickpoint_email', $order->get_billing_email(), $order );

        // Заказ
        $orderId = apply_filters( 'inwccrm_pickpoint_orderId', $order->get_order_number(), $order );
        $orderTitleRus = apply_filters( 'inwccrm_pickpoint_orderTitleRus', esc_html__( 'Заказ №', IN_WC_CRM ) . $orderId, $order );
        $orderTitleEn = apply_filters( 'inwccrm_pickpoint_orderTitleEn', esc_html__( 'Order #', IN_WC_CRM ) . $orderId, $order );
        $sum = apply_filters( 'inwccrm_pickpoint_sum', $order->get_total(), $order );

        // Доставка TODO: Сделать чтение налогов из WC
        $DeliveryVat = apply_filters( 'inwccrm_pickpoint_DeliveryVat', 0, $order );
        $DeliveryFee = apply_filters( 'inwccrm_pickpoint_DeliveryFee', 0, $order );
        $InsuareValue = apply_filters( 'inwccrm_pickpoint_InsuareValue', 0, $order );
        $DeliveryMode = apply_filters( 'inwccrm_pickpoint_DeliveryMode', 1, $order );
        $GettingType = apply_filters( 'inwccrm_pickpoint_GettingType', '101', $order );

        // Постомат
        preg_match('/.*([\d]{4}-[\d]{3}).*/', $order->get_shipping_address_1(), $output_array);
        $postamatNumber = apply_filters( 'inwccrm_pickpoint_postamatNumber', ( isset($output_array[1] ) ) ? $output_array[1] : '', $order );   
        $postageType = apply_filters( 'inwccrm_pickpoint_postageType', ( $order->get_payment_method() == 'cod' ) ? '10003' : '10001', $order );

        $senderCityName = apply_filters( 'inwccrm_pickpoint_senderCityName', $order->get_shipping_city(), $order );
        $senderRegionName = apply_filters( 'inwccrm_pickpoint_senderRegionName', $order->get_shipping_state(), $order );

        // Магазин
        $shopName = apply_filters( 'inwccrm_pickpoint_shopName', get_option( 'blogname' ) );
        $shopManagerName = apply_filters( 'inwccrm_pickpoint_shopManagerName', $this->getParam( 'pickpoint-shopManagerName', '' ) );
        $shopOrganization = apply_filters( 'inwccrm_pickpoint_shopOrganization', $this->getParam( 'pickpoint-shopOrganization', '' ) );
        $shopPhone = preg_replace('/[\s\-\(\)\.]/', '', $this->getParam( 'pickpoint-shopPhone', '' ) );
        $shopPhone = apply_filters( 'inwccrm_pickpoint_shopPhone', $shopPhone  );
        $shopComment = apply_filters( 'inwccrm_pickpoint_shopComment', $this->getParam( 'pickpoint-shopComment', '' ) );
        
        // The main address pieces: https://wordpress.stackexchange.com/questions/319346/woocommerce-get-physical-store-address
        $store_address     = apply_filters( 'inwccrm_pickpoint_store_address', get_option( 'woocommerce_store_address' ) );
        $store_address_2   = apply_filters( 'inwccrm_pickpoint_store_address_2', get_option( 'woocommerce_store_address_2' ) );
        $store_city        = apply_filters( 'inwccrm_pickpoint_store_city', get_option( 'woocommerce_store_city' ) );
        $store_postcode    = apply_filters( 'inwccrm_pickpoint_store_postcode', get_option( 'woocommerce_store_postcode' ) );

        // The country/state
        $store_raw_country = apply_filters( 'inwccrm_pickpoint_store_raw_country', get_option( 'woocommerce_default_country' ) );

        // Split the country/state
        $split_country = explode( ":", $store_raw_country );
        if (! isset( $split_country[1]) ) $split_country[1] = '';

        // Country and state separated:
        $store_country = apply_filters( 'inwccrm_pickpoint_store_country', $split_country[0] );
        $store_state   = apply_filters( 'inwccrm_pickpoint_store_state', $split_country[1] );

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
            $Upi = $product->get_id();
            $SubEnclose = <<<SUBENCLOSE
                {
                    "ProductCode": "{$ProductCode}",
                    "GoodsCode": "",
                    "Name": "{$Name}",
                    "Price": "{$Price}",
                    "Quantity": "{$Quantity}",
                    "Vat": null,
                    "Description": "{$Description}",
                    "Upi": "{$Upi}"
                } 
SUBENCLOSE;
            array_push( $SubEncloses, $SubEnclose );
        }
        $SubEnclosesStr = apply_filters( 'inwccrm_pickpoint_json_SubEncloses', implode(',', $SubEncloses), $order );

        $places = array();
        $place = <<<PLACES
            {
                "BarCode": "",
                "CellStorageType": "0",
                "SubEncloses": [
                    {$SubEnclosesStr}
                ]
            }
PLACES;

        array_push( $places, $place );
        $placesStr = apply_filters( 'inwccrm_pickpoint_json_places', implode(',', $places), $order );

        $data  = <<<DATA
            {
                "EDTN": "{$requestId}",
                "IKN": "{$ikn}",
                "ClientName": "{$clientName}",
                "TittleRus": "{$orderTitleRus}",
                "TittleEng": "{$orderTitleEn}",
                "Invoice": {
                  "SenderCode": "{$orderId}",
                  "Description": "{$shopName}",
                  "RecipientName": "$clientName",
                  "PostamatNumber": "{$postamatNumber}",
                  "MobilePhone": "{$mobilePhone}",
                  "Email": "{$email}",
                  "PostageType": "{$postageType}",
                  "GettingType": "{$GettingType}",
                  "PayType": "1",
                  "Sum": "{$sum}",
                  "PrepaymentSum": "0",
                  "DeliveryVat": "{$DeliveryVat}",
                  "DeliveryFee": "{$DeliveryFee}",
                  "InsuareValue": "{$InsuareValue}",
                  "DeliveryMode": "{$DeliveryMode}",
                  "SenderCity": {
                    "CityName": "{$senderCityName}",
                    "RegionName": "{$senderRegionName}"
                  },
                  "ClientReturnAddress": {
                    "CityName": "{$store_city}",
                    "RegionName": "{$store_state}",
                    "Address": "{$store_address} {$store_address_2}",
                    "FIO": "{$shopManagerName}",
                    "PostCode": "{$store_postcode}",
                    "Organisation": "{$shopOrganization}",
                    "PhoneNumber": "{$shopPhone}",
                    "Comment": "{$shopComment}"
                  },
                  "UnclaimedReturnAddress": {
                    "CityName": "{$store_city}",
                    "RegionName": "{$store_state}",
                    "Address": "{$store_address} {$store_address_2}",
                    "FIO": "{$shopManagerName}",
                    "PostCode": "{$store_postcode}",
                    "Organisation": "{$shopOrganization}",
                    "PhoneNumber": "{$shopPhone}",
                    "Comment": "{$shopComment}"
                  },
                  "Places": [
                    {$placesStr}
                  ]
                }
            }
DATA;
        
        $data = apply_filters( 'inwccrm_pickpoint_json_shipment', $data, $order);
        return $data;
    }
}