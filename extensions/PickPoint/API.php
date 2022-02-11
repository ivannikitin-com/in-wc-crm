<?php
/**
 * API PickPoint
 */
namespace IN_WC_CRM\Extensions\PickPoint;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;

require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'pickpoint-sending.log';

    /**
     * Параметры удаленного сервера и подключения
     */
    private $url;
    private $login;
    private $password;
    private $ikn;
    private $shopManagerName;
    private $shopOrganization;
    private $shopPhone;
    private $shopComment;

    /**
     * ID сессии
     */
    private $sessionId;

    /**
     * Конструктор
     * @param string    $url                    URL удаленного сервера
     * @param string    $login                  Логин
     * @param string    $password               Пароль
     * @param string    $ikn                    Номер договора
     * @param string    $shopManagerName        Менеджер компании
     * @param string    $shopOrganization       Название организации
     * @param string    $shopPhone              Телефон организации
     * @param string    $shopComment            Комментарий организации
     */
    public function __construct( $url, $login, $password, $ikn, $shopManagerName, $shopOrganization, $shopPhone, $shopComment )
    {
        $this->url = $url;
        $this->login = $login;
        $this->password = $password;
        $this->ikn = $ikn;
        $this->shopManagerName = $shopManagerName;
        $this->shopOrganization = $shopOrganization;
        $this->shopPhone = $shopPhone;
        $this->shopComment = $shopComment;

        // Убираем из URL финальный слеш
        if ( substr( $this->url, -1 ) == '/' )
            $this->url = substr( $this->url, 0, strlen( $this->url ) -1 );

    }

    /**
     * Авторизуется на сервере и возвращает SessionID
     */
    private function getSessionId()
    {
        if ( empty( $this->url) || empty( $this->login ) || empty( $this->password ) || empty( $this->ikn ) )
        {         
            throw new NoСredentialsException( __( 'Не указаны данные подключения к серверу!', IN_WC_CRM ) );
        }

        // Логин на удаленный сервер
        $credentials = json_encode( array(
            'Login' => $this->login,
            'Password' => $this->password,
        ) );

        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => $credentials
        );

        Plugin::get()->log( __( 'Авторизация на сервере', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $args, self::LOGFILE );

        $response = wp_remote_post( $this->url . '/login', $args );

        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            Plugin::get()->log( __( 'Ошибка подключения к серверу: ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );             
            throw new LoginException( __( 'Ошибка подключения к серверу: ', IN_WC_CRM ) . $response->get_error_message() );
        }
        
        $responseObj = json_decode( $response['body'] );
        if ( isset( $responseObj->ErrorMessage ) )
        {
            Plugin::get()->log( __( 'Ошибка авторизации на сервере: ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );              
            throw new LoginException( __( 'Ошибка авторизации на сервере: ', IN_WC_CRM ) . $responseObj->ErrorMessage );
        }
        
        if ( ! isset( $responseObj->SessionId ) )
        {
            Plugin::get()->log( __( 'SessionID при авторизации не получен! ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );            
            throw new LoginException( __( 'SessionID при авторизации не получен! ', IN_WC_CRM ) );
        }

        return $responseObj->SessionId;             
    }

    /**
     * Отправляет данные на сервер
     * @param mixed $orders Массив заказов
     */
    public function send( $orders )
    {
        if ( empty( $this->sessionId) ) 
        {
            $this->sessionId = $this->getSessionId();
        }

        // Отправления
        $sendings = array();
        foreach( $orders as $order )
        {
            $sendings[] = $this->createShipment( $order );
        }
        
        // Данные для отправки
        $sendData = json_encode( array(
            'SessionId' => $this->sessionId,
            'Sendings'  => $sendings
        ));

        Plugin::get()->log( __( 'Данные для отправки', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $sendData, self::LOGFILE );

        // Формируем запрос
        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => $sendData,
        );

        // Отправляем запрос
        $response = wp_remote_post( $this->url . '/CreateShipment', $args );

        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            throw new SendException( __( 'Ошибка отправки данных: ', IN_WC_CRM ) . $response->get_error_message() );
        }
        
        // Расшифровываем ответ
        $responseObj = json_decode( $response['body'] );
        if ( ! $responseObj )
        {        
            throw new EmptyResponseException( __( 'Пустой ответ сервера!', IN_WC_CRM ) );
        }

        Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $responseObj, self::LOGFILE );        

        // Возвращаем результат
        return array(
            'created'  => $responseObj->CreatedSendings,
            'rejected' => $responseObj->RejectedSendings
        );
    }

    // Проверяет статусы отправлений
    public function getStates()
    {
        if ( empty( $this->sessionId) ) 
        {
            $this->sessionId = $this->getSessionId();
        }

        // Данные для отправки
        $sendData = json_encode( array(
            'SessionId' => $this->sessionId,
        ));

        Plugin::get()->log( __( 'Данные для отправки', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $sendData, self::LOGFILE );

        // Формируем запрос
        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => $sendData,
        );

        // Отправляем запрос
        $response = wp_remote_get( $this->url . '/getstates', $args );

        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            throw new SendException( __( 'Ошибка отправки данных: ', IN_WC_CRM ) . $response->get_error_message() );
        }
        
        // Расшифровываем ответ
        $responseObj = json_decode( $response['body'] );
        if ( ! $responseObj )
        {        
            throw new EmptyResponseException( __( 'Пустой ответ сервера!', IN_WC_CRM ) );
        }

        Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $responseObj, self::LOGFILE );        

        // Возвращаем результат
        return  $responseObj;  
    }


    /**
     * Создает объект отправления
     */
    private function createShipment( $order )
    {
        // Данные
        $requestId = apply_filters( 'inwccrm_pickpoint_requestId', sha1( microtime() . __CLASS__ ), $order );   //<Идентификатор запроса, используемый для ответа. Указывайте уникальное число (50 символов)>
        $ikn =  apply_filters( 'inwccrm_pickpoint_ikn', $this->ikn ) ; //<ИКН – номер договора (10 символов)>
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
        $DeliveryVat = apply_filters( 'inwccrm_pickpoint_DeliveryVat', null, $order );
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
        $shopManagerName = apply_filters( 'inwccrm_pickpoint_shopManagerName', $this->shopManagerName );
        $shopOrganization = apply_filters( 'inwccrm_pickpoint_shopOrganization', $this->shopOrganization );
        $shopPhone = preg_replace('/[\s\-\(\)\.]/', '', $this->shopPhone );
        $shopPhone = apply_filters( 'inwccrm_pickpoint_shopPhone', $shopPhone  );
        $shopComment = apply_filters( 'inwccrm_pickpoint_shopComment', $this->shopComment );
        
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
			if ( !$product ) continue;
			
            $SubEnclose = array(
                'ProductCode'   => $product->get_sku(),
                'GoodsCode'     => '',
                'Name'          => $item->get_name(),
                'Price'         => $item->get_subtotal(),
                'Quantity'      => $item->get_quantity(),
                'Vat'           => null,
                'Description'   => '',
                'Upi'           => $product->get_id()
            );
            array_push( $SubEncloses, $SubEnclose );
        }
        
        $places = array();
        $place = array(
            'BarCode'           => '',
            'CellStorageType'   => 0,
            'SubEncloses'       => $SubEncloses
        );
        array_push( $places, $place );

        $data = array(
            'EDTN'          => $requestId,
            'IKN'           => $ikn,
            'ClientName'    => $clientName,
            'TittleRus'     => $orderTitleRus,
            'TittleEng'     => $orderTitleEn,
            'Invoice'       => array(
                'SenderCode'    => $orderId,
                'Description'   => $shopName,
                'RecipientName' => $clientName,
                'PostamatNumber' => $postamatNumber,
                'MobilePhone'   => $mobilePhone,
                'Email'         => $email,
                'PostageType'   => $postageType,
                'GettingType'   => $GettingType,
                'PayType'       => 1,
                'Sum'           => $sum,
                'PrepaymentSum' => 0,
                'DeliveryVat'   => $DeliveryVat,
                'DeliveryFee'   => $DeliveryFee,
                'InsuareValue'  => $InsuareValue,
                'DeliveryMode'  => $DeliveryMode,
                'SenderCity'    => array(
                    'CityName'      => $senderCityName,
                    'RegionName'    => $senderRegionName
                ),
                'ClientReturnAddress'   => array(
                    'CityName'      => $store_city,
                    'RegionName'    => $store_state,
                    'Address'       => $store_address . ' ' . $store_address_2,
                    'FIO'           => $shopManagerName,
                    'PostCode'      => $store_postcode,
                    'Organisation'  => $shopOrganization,
                    'PhoneNumber'   => $shopPhone,
                    'Comment'       => $shopComment
                ),
                'UnclaimedReturnAddress' => array(
                    'CityName'      => $store_city,
                    'RegionName'    => $store_state,
                    'Address'       => $store_address . ' ' . $store_address_2,
                    'FIO'           => $shopManagerName,
                    'PostCode'      => $store_postcode,
                    'Organisation'  => $shopOrganization,
                    'PhoneNumber'   => $shopPhone,
                    'Comment'       => $shopComment                    
                ),
                'Places' => $places
            )
        );

        return $data;
    }

}