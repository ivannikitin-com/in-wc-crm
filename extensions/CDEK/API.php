<?php
/**
 * API CDEK
 */
namespace IN_WC_CRM\Extensions\CDEK;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;

require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'CDEK-sending.log';
    protected const TOKEN_PATH = "/oauth/token";
    protected const ORDERS_PATH = "/orders";

    /**
     * Параметры удаленного сервера и подключения
     */
    private $url;
    private $apiid;
    private $secretkey;
    private $shopManagerName;
    private $shopOrganization;
    private $shopPhone;
    private $shopAddress;
    private $shipperName;
    private $shipperAddress;
    private $shipperEmail;
    private $shipperPhone;
    private $shopComment;
    private $rates=array();

    /**
     * Access Token
     */
    private $accessToken;

    /**
     * Конструктор
     * @param string    $url                    URL удаленного сервера
     * @param string    $apiid                  Логин
     * @param string    $secretkey               Пароль
     * @param string    $shopManagerName        Менеджер компании
     * @param string    $shopOrganization       Название организации
     * @param string    $shopPhone              Телефон организации
     * @param string    $shopComment            Комментарий организации
     */
    public function __construct( $url, $apiid, $secretkey, $shopManagerName, $shopOrganization, $shopAddress, $shopPhone, $shipperName, $shipperAddress, $shipperEmail, $shipperPhone, $shopComment )
    {
        $this->url = $url;
        $this->apiid = $apiid;
        $this->secretkey = $secretkey;
        $this->shopManagerName = $shopManagerName;
        $this->shopOrganization = $shopOrganization;
        $this->shopAddress = $shopAddress;
        $this->shopPhone = $shopPhone;
        $this->shipperName = $shipperName;
        $this->shipperAddress = $shipperAddress;
        $this->shipperEmail = $shipperEmail;
        $this->shipperPhone = $shipperPhone;
        $this->shopComment = $shopComment;
        $this->rates[2][] = 37;//Объемный вес 2,405
        $this->rates[2][] = 25;
        $this->rates[2][] = 13;
        $this->rates[3][] = 37;//2,6936
        $this->rates[3][] = 26;
        $this->rates[3][] = 14;
        $this->rates[5][] = 40;//3,808
        $this->rates[5][] = 28;
        $this->rates[5][] = 17;
        $this->rates[10][] = 43;//5,9856
        $this->rates[10][] = 29;
        $this->rates[10][] = 24;
        $this->rates[20][] = 43;//11,9196
        $this->rates[20][] = 33;
        $this->rates[20][] = 42;
        $this->rates[25][] = 43;//17,5956
        $this->rates[25][] = 33;
        $this->rates[25][] = 62;
        $this->rates[30][] = 43;//18,447
        $this->rates[30][] = 33;
        $this->rates[30][] = 65;
        $this->rates[50][] = 86;//140,7648
        $this->rates[50][] = 66;
        $this->rates[50][] = 124;
        $this->rates[100][] = 172;//1126,1184
        $this->rates[100][] = 132;
        $this->rates[100][] = 248;                                

        // Убираем из URL финальный слеш
        if ( substr( $this->url, -1 ) == '/' )
            $this->url = substr( $this->url, 0, strlen( $this->url ) -1 );
    }

    /**
     * Авторизуется на сервере и возвращает Access Token
     */
    private function getAccessToken()
    {
        if ( empty( $this->url) || empty( $this->apiid ) || empty( $this->secretkey ) )
        {         
            throw new NoСredentialsException( __( 'Не указаны данные подключения к серверу!', IN_WC_CRM ) );
        }

        Plugin::get()->log( __( 'Авторизация на сервере', IN_WC_CRM ), self::LOGFILE );
        /* Plugin::get()->log( $args, self::LOGFILE ); */
        Plugin::get()->log( $this->url . self::TOKEN_PATH. "?grant_type=client_credentials&client_id=" . $this->apiid. "&client_secret=" . $this->secretkey, self::LOGFILE );

        $response = wp_remote_post( $this->url . self::TOKEN_PATH. "?grant_type=client_credentials&client_id=" . $this->apiid. "&client_secret=" . $this->secretkey );
        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            Plugin::get()->log( __( 'Ошибка подключения к серверу: ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );             
            throw new apiidException( __( 'Ошибка подключения к серверу: ', IN_WC_CRM ) . $response->get_error_message() );
        }
        
        $responseObj = json_decode( $response['body'] );
        $bodyJson = wp_remote_retrieve_body($response);
        $body = json_decode($bodyJson);

        if ( isset( $responseObj->ErrorMessage ) || property_exists($body, 'error') )
        {
            Plugin::get()->log( __( 'Ошибка авторизации на сервере: ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );              
            throw new LoginException( __( 'Ошибка авторизации на сервере: ', IN_WC_CRM ) . $responseObj->ErrorMessage );
        }
        
        if ( ! isset( $body->access_token ) )
        {
            Plugin::get()->log( __( 'Token при авторизации не получен! ', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );            
            throw new apiidException( __( 'Token при авторизации не получен! ', IN_WC_CRM ) );
        } else {
            Plugin::get()->log( $body->access_token, self::LOGFILE );
        }

        return $body->access_token;             
    }

    /**
     * Отправляет данные на сервер
     * @param object $order Объкт заказа
     */
    public function send( $order )
    {
        if ( empty( $this->accessToken) ) 
        {
            $this->accessToken = $this->getAccessToken();
        }

        // Отправления
        $responses = array();

            try
            {

            // Данные для отправки
            $sendings = $this->createShipment( $order );

            \IN_WC_CRM\Extensions\CDEK::cdeklog( __( 'Данные для отправки', IN_WC_CRM ));
            \IN_WC_CRM\Extensions\CDEK::cdeklog( $sendings);

            // Формируем запрос
            $args = array(
                'timeout'   => 60,
                'blocking'  => true,   
                'headers'   => array(
                    'Authorization' => "Bearer ".$this->accessToken,
                    'Content-Type' => 'application/json'
                ),

                'body'      => json_encode($sendings),
            );

            // Отправляем запрос
            $response = wp_remote_post( $this->url . self::ORDERS_PATH, $args );
            $body = wp_remote_retrieve_body( $response );
            $message = wp_remote_retrieve_response_message($response);

            // Сохраняем ответ
                \IN_WC_CRM\Extensions\CDEK::cdeklog( __( 'Ответ сервера', IN_WC_CRM ));
                \IN_WC_CRM\Extensions\CDEK::cdeklog( $response );
            } 
            catch (Exception $e) // Ловим и логируем ошибки
            {
                // Возникли ошибки
                \IN_WC_CRM\Extensions\CDEK::cdeklog( __( 'Ошибки', IN_WC_CRM ) . ' ' . var_export( $e, true ));
                throw $e; 
            }
            $responses['message'] = $message;
            $responses['uuid'] = json_decode($body, false)->entity->uuid;

        // Возвращаем результат
        return $responses;
    }

    /**
     * Создает объект отправления
     */
    private function createShipment( $order )
    {
        // Данные
        $requestId = apply_filters( 'inwccrm_CDEK_requestId', sha1( microtime() . __CLASS__ ), $order );   //<Идентификатор запроса, используемый для ответа. Указывайте уникальное число (50 символов)>

        // Пользователь
        $clientName = apply_filters( 'inwccrm_CDEK_clientName', 
            ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(), 
            $order );

        $mobilePhone = preg_replace('/[\s\-\(\)\.]/', '', $order->get_billing_phone() );
        $mobilePhone = apply_filters( 'inwccrm_CDEK_mobilePhone', $mobilePhone, $order );
        $email = apply_filters( 'inwccrm_CDEK_email', $order->get_billing_email(), $order );

        // Заказ
        $orderId = apply_filters( 'inwccrm_CDEK_orderId', $order->get_order_number(), $order );
        $orderTitleRus = apply_filters( 'inwccrm_CDEK_orderTitleRus', esc_html__( 'Заказ №', IN_WC_CRM ) . $orderId, $order );
        $orderTitleEn = apply_filters( 'inwccrm_CDEK_orderTitleEn', esc_html__( 'Order #', IN_WC_CRM ) . $orderId, $order );
        $sum = apply_filters( 'inwccrm_CDEK_sum', $order->get_total(), $order );

        // Доставка TODO: Сделать чтение налогов из WC
        $DeliveryVat = apply_filters( 'inwccrm_CDEK_DeliveryVat', null, $order );
        $DeliveryFee = apply_filters( 'inwccrm_CDEK_DeliveryFee', 0, $order );
        $InsuareValue = apply_filters( 'inwccrm_CDEK_InsuareValue', 0, $order );
        $DeliveryMode = apply_filters( 'inwccrm_CDEK_DeliveryMode', 1, $order );
        $GettingType = apply_filters( 'inwccrm_CDEK_GettingType', '101', $order );

        // Постомат
        preg_match('/.*([\d]{4}-[\d]{3}).*/', $order->get_shipping_address_1(), $output_array);
        //$order_data = get_post_meta($order->get_order_number(),'order_data',true);
        $items = $order->get_items();
	    foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
            $pvzCode = $item->get_meta('_official_cdek_office_code', true);
            $tariffId = $item->get_meta('_official_cdek_tariff_code', true);
            Plugin::get()->log( '$tariffId='.$tariffId, self::LOGFILE );
            $cityCode = $item->get_meta('city_code', true);
            $currency = $item->get_meta('currency', true);

	    }
/*      $pvzCode = $order_data['pvz_code'];
        $tariffId = $order_data['_official_cdek_tariff_code'];
        $cityCode = $order_data['city_code'];
        $currency = $order_data['currency']; */
  
/*        $postageType = apply_filters( 'inwccrm_CDEK_postageType', ( $order->get_payment_method() == 'cod' ) ? '10003' : '10001', $order );*/

        $senderCityName = apply_filters( 'inwccrm_CDEK_senderCityName', $order->get_shipping_city(), $order );
        $senderRegionName = apply_filters( 'inwccrm_CDEK_senderRegionName', $order->get_shipping_state(), $order );

        // Магазин
        $shopName = apply_filters( 'inwccrm_CDEK_shopName', get_option( 'blogname' ) );
        $shopManagerName = apply_filters( 'inwccrm_CDEK_shopManagerName', $this->shopManagerName );
        $shopOrganization = apply_filters( 'inwccrm_CDEK_shopOrganization', $this->shopOrganization );
        $shopPhone = preg_replace('/[\s\-\(\)\.]/', '', $this->shopPhone );
        $shopPhone = apply_filters( 'inwccrm_CDEK_shopPhone', $shopPhone  );
        $shopComment = apply_filters( 'inwccrm_CDEK_shopComment', $this->shopComment );
        
        // The main address pieces: https://wordpress.stackexchange.com/questions/319346/woocommerce-get-physical-store-address
        /* $store_address     = apply_filters( 'inwccrm_CDEK_store_address', get_option( 'woocommerce_store_address' ) );
        $store_address_2   = apply_filters( 'inwccrm_CDEK_store_address_2', get_option( 'woocommerce_store_address_2' ) );
        $store_city        = apply_filters( 'inwccrm_CDEK_store_city', get_option( 'woocommerce_store_city' ) );
        $store_postcode    = apply_filters( 'inwccrm_CDEK_store_postcode', get_option( 'woocommerce_store_postcode' ) ); */

        // The country/state
        $store_raw_country = apply_filters( 'inwccrm_CDEK_store_raw_country', get_option( 'woocommerce_default_country' ) );

        // Split the country/state
        $split_country = explode( ":", $store_raw_country );
        if (! isset( $split_country[1]) ) $split_country[1] = '';

        // Country and state separated:
        $store_country = apply_filters( 'inwccrm_CDEK_store_country', $split_country[0] );
        $store_state   = apply_filters( 'inwccrm_CDEK_store_state', $split_country[1] );
        
        $delivery_recipient_cost = 0;
        if ($order->get_payment_method() == 'cod') {
            $delivery_recipient_cost = $order->get_shipping_total();
        }
        $paymentValue = 0;
        $length = 0;
        $width = 0;
        $height = 0;
        $totalWeight = 0;
        $total_weight = 0;

        $SubEncloses = array();  
        /* Collect data for all order items and calulate total weight*/     
        foreach ($order->get_items() as $item)
        {
            if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) continue;
            $product = wc_get_product( $item->get_product_id() );
            if ($product) {
                $weight = number_format($product->get_weight() * $item->get_quantity() * 1000); //вес товара в граммах
                $weight = $product->get_weight() * $item->get_quantity();
            } else {
                $weight = 500; //Если заказанный товар больше не существует в БД магазина
            }
            $totalWeight += $weight;
			if ( !$product ) continue;
            if ($order->get_payment_method() == 'cod') {
                $paymentValue = $product->get_price();
            }
            $SubEnclose[] = [
                "ware_key" => ($product)?$product->get_sku():'',
                "payment" => ["value" => $paymentValue],
                "name" => ($product)?$product->get_name():'',
                "cost" => $product->get_price(),
                "amount" => $item->get_quantity(),
                "weight" => $weight ,
                "weight_gross" => $weight + 1, //Вес брутто. Только для международных заказов/
            ];

        }

        /* Выбираем размер упаковки в зависимости от веса заказа */
        foreach( $this->rates as $index => $rate ) {
            if ( $totalWeight < $index ) {
                $length = $rate[0];
                $width = $rate[1];
                $height = $rate[2];
                break;
            }
        }

        $data = array (
            'number'            => $orderId,
            'tariff_code'       => $tariffId,
            /* 'developer_key'     => '7wV8tk&r6VH4zK:1&0uDpjOkvM~qngLl', */
            'from_location'     => array(
                'address'       => $this->shipperAddress,
                'postal_code'   => '105062б',
                'city'          => 'Москва'
            ),               
            'shipper_name'      => $this->shipperName,
            'shipper_address'   => $this->shipperAddress,
            'delivery_recipient_cost'   => array(
                'value'             => $delivery_recipient_cost

            ),
            'sender'            => array(
                'company'       => $this->shopOrganization,
                'name'          => $this->shopManagerName,
                'email'         => $this->shipperEmail,
                'phones'        => array(
                    'number'    => $this->shipperPhone
                )
            ),
            'seller'            => array(
                'name'          => $this->shipperName,
                'address'       => $this->shopAddress    
            ),
            'recipient'         => array (
                'name'          => $clientName,
                'email'         => $email,
                'phones'        => array(
                    'number'    => $mobilePhone
                )
            ),
            'packages'          => array (
                'number'        => $orderId,
                'length'        => $length,
                'width'         => $width,
                'height'        => $height,
                'weight'        => $totalWeight*1000,
                'items'         => $SubEnclose
            ),            
            'print'             => 'waybill'
        );

        if ( $pvzCode ) {
            $data += [ 'delivery_point' => $pvzCode ];
        } else {
            $data += [ 
                'to_location'     => array(
                'address'       => $order->get_formatted_shipping_address()
                ) ];
        }            
        
        return $data;

    }

}