<?php
/**
 * API FivePost
 */
namespace IN_WC_CRM\Extensions\FivePost;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;


require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'FivePost-rest.log';

    /**
     * Точка отправки запроса
     * URL = 'https://api.FivePost.ru/json.php';
     * API Партнера 5post для тестирования интеграции доступно по адресу:
     * {среда}/api/{Версия API}/{Endpoint},
     *  где {среда}:
     *      - https://api-preprod-omni.x5.ru – для тестовой среды; 
     *      - https://api-omni.x5.ru – для продуктивной среды.

     */
    //const URL = 'https://api-preprod-omni.x5.ru/';
    const URL = 'https://api-omni.x5.ru/';
    const METHOD_ORDERS = 'api/v3/orders';

    /**
     * Кэш ключа
     */
    const TOKEN_CACHE = 'in-wc-crm_fivepost_token';


    /**
     * Конструктор
     * @param string    $apiKey            API ключ

     */
    public function __construct( $apiKey )
    {
        $this->token = get_transient( self::TOKEN_CACHE );
        if ( false === $this->token ) {
            $this->token = $this->getToken( $apiKey );
            set_transient( self::TOKEN_CACHE, $this->token, 30 * MINUTE_IN_SECONDS );
        }
    }

    /**
     * Получает Bearer токен
     */
    private function getToken( $apiKey ){
        Plugin::get()->log( 'Получение токена для API Key: ' . $apiKey, self::LOGFILE ); 
        
        // Запрос токена
        $url = self::URL . 'jwt-generate-claims/rs256/1?apikey=' . $apiKey;
        
        $args = array(
            'body' => 'subject=OpenAPI&audience=A122019!'
        );
        $response = wp_remote_post( $url, $args );

        // Проверка результата
        if ( is_wp_error( $response ) ) {
            throw new GetTokenException( 
                __( 'Ошибка получения токена FivePost' , IN_WC_CRM ) . 
                ': ' . $response->get_error_message()
            );
        }

        // Ответ получен
        if ( !$response || !isset( $response['body'] ) ) {
            Plugin::get()->log( 'Ответ сервера: ' . var_export($response, true), self::LOGFILE );
            throw new GetTokenException( 
                __( 'Токен FivePost не получен' , IN_WC_CRM ) . 
                ': ' . var_export($response, true)
            );
        };

        $responseObj = json_decode( $response['body'] );
        Plugin::get()->log( 'Токен получен: ' . $responseObj->jwt, self::LOGFILE );
        return $responseObj->jwt;
    }


    /**
     * Возвращает структуру заказа для отправки
     * @param WC_Order  $order  Заказ WooCommerce
     * @return mixed
     */
    private function getOrderData( $order )
    {
        // Проверки обязательных параметров

        // Склад отправителя
        $senderLocation = 'medknigaservis.ru';  // TODO: Сделать параметр в настройках
        if ( empty( $senderLocation ) )
        {
            throw new NoRequiredParameter( __( 'Не определен параметр senderLocation (склад отправителя)', IN_WC_CRM ) );
        }        

        // Точка получения
        /*preg_match('/UUID:\s?([a-fA-F0-9\-]+)(?:\s|$)/', $order->get_formatted_shipping_address(), $matches);*/
        preg_match('/UUID:\s?([a-fA-F0-9\-]+)(?=[,\s]|$)/', $order->get_formatted_shipping_address(), $matches);
        
        $receiverLocation = ($matches) ? $matches[1] : false ;  
        Plugin::get()->log( __( 'FivePost Адрес доставки ', IN_WC_CRM ) . ': ' . $order->get_formatted_shipping_address(), self::LOGFILE );
        Plugin::get()->log( __( 'FivePost Точка получения ', IN_WC_CRM ) . ': ' . var_export( $matches, true ), self::LOGFILE );
        Plugin::get()->log( __( 'receiverLocation ', IN_WC_CRM ) . ': ' . var_export( $receiverLocation, true ), self::LOGFILE );

        if ( empty( $receiverLocation ) )
        {
            throw new NoRequiredParameter( __( 'Не определен параметр receiverLocation (точка получения)', IN_WC_CRM ) );
        } 

        // Элементы заказа
        $items = array();
        $summTotal = 0;
        $weghtTotal = 0;    
        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            if ( ! $product = $orderItem->get_product() ) continue; // Продукт на предзаказе!
            if ($product && $product->is_virtual()) {
                // Пропускаем виртуальый товар
                continue;
            }
            //Plugin::get()->log( __( 'FivePost Продукт ', IN_WC_CRM ) . ': ' . var_export( $orderItem, true ), self::LOGFILE );
            $sku = ( ! empty( $product->get_sku() ) ) ? $product->get_sku() : 'SKU_' .  $product->get_id();
            $itemQuantity = $orderItem->get_quantity();
            $itemTotalPrice = round($orderItem->get_total() + $orderItem->get_total_tax(), 2);
            $itemPrice = ($itemQuantity > 0 ) ? round( $itemTotalPrice / $itemQuantity, 2 ) : $itemTotalPrice;
            $summTotal += round($itemTotalPrice, 2);
            $itemWeghtTotal = $itemQuantity * floatval( $product->get_weight() );
            $weghtTotal += $itemWeghtTotal;

            //  Массив товарных вложений
            $items[] = array(
                // Наименование товара
                'name'      => apply_filters( 'inwccrm_fivePost_orderitem_name', $product->get_name(), $order, $orderItem ),                
                // Количество единиц товара
                'value'     => apply_filters( 'inwccrm_fivePost_orderitem_value', $itemQuantity, $order, $orderItem ),
                // Цена за единицу товара
                'price'     => apply_filters( 'inwccrm_fivePost_orderitem_price', $itemPrice, $order, $orderItem ),
                // Валюта цены
                'currency'  => apply_filters( 'inwccrm_fivePost_orderitem_currency', 'RUB', $order, $orderItem ),
                // Ставка НДС в %. Возможные значения: 0 (=без НДС), 10, 20
                'vat'       => apply_filters( 'inwccrm_fivePost_orderitem_vat', 0, $order, $orderItem ),
            /*    
                // Код маркировки товара согласно Честному Знаку.  Принимаемый формат значений только base64
                'upiCode'   => apply_filters( 'inwccrm_fivepost_orderitem_upicode', '', $order, $orderItem ),
                // Артикул товара
                'vendorCode' => apply_filters( 'inwccrm_fivepost_orderitem_vendorcode', $sku, $order, $orderItem ),
                // Страна производства
                'originCountry' => apply_filters( 'inwccrm_fivepost_orderitem_origincountry', 'Russia', $order, $orderItem ),
                // Штрихкод товара (штрихкод, нанесенный на товар производителем)
                'barcode'   => apply_filters( 'inwccrm_fivepost_orderitem_barcode', '', $order, $orderItem ),
                // Номер Грузовой Таможенной Декларации.
                'codeGTD'   => apply_filters( 'inwccrm_fivepost_orderitem_codegtd', '', $order, $orderItem ),
                // Код Товарной Номенклатуры Внешне Экономической Деятельности
                'codeTNVED' => apply_filters( 'inwccrm_fivepost_orderitem_codetnved', '', $order, $orderItem ),
                // Данные о поставщике товара
                'vendor' => apply_filters( 'inwccrm_fivepost_orderitem_vendor', '', $order, $orderItem ),
                // Данные о поставщике товара
                'vendor' => apply_filters( 'inwccrm_fivepost_orderitem_vendor', array(
                    // Полное наименование юридического лица поставщика отправителя.
                    'name' => apply_filters( 'inwccrm_fivepost_orderitem_vendor_name', '', $order, $orderItem ),
                    // ИНН поставщика. При заполнении параметра данные о поставщике будут применяться для текущей товарной позиции
                    'inn' => apply_filters( 'inwccrm_fivepost_orderitem_vendor_inn', '', $order, $orderItem ),
                    // Полное наименование юридического лица поставщика отправителя.
                    'phone' => apply_filters( 'inwccrm_fivepost_orderitem_vendor_phone', '', $order, $orderItem )
                ), $order, $orderItem ),
            */
            );
        }

        // Проверка массива $items
        if (0 == count( $items) ) 
        {
            throw new NoRequiredParameter( __( 'В заказе нет товаров для отправки в FivePost', IN_WC_CRM ) );
        } 

        // В параметр weight нужно передавать вес в граммах, т.е. целое число
        $weghtTotal = intval( $weghtTotal * 1000 );
        //  вес первого или единственного тарного места, в граммах. Минимальное значение 5 г, максимальное – 31000 г.
        if ( $weghtTotal < 5 ) $weghtTotal = 5;
        if ( $weghtTotal > 31000 ) $weghtTotal = 31000;

        // Дефолтовое значение delivery_date
        $delivery_date = date( 'c', time() + DAY_IN_SECONDS );

        // Формирование и возврат заказа
        return array(
            'senderOrderId'   => apply_filters( 'inwccrm_fivepost_senderorderid', $order->get_order_number(), $order ),
            'clientOrderId'   => apply_filters( 'inwccrm_fivepost_clientorderid', $order->get_order_number(), $order ),
            'brandName'       => apply_filters( 'inwccrm_fivepost_brandname', get_bloginfo( 'name' ), $order ),
            'clientName'      => apply_filters( 'inwccrm_fivepost_clientname', 
                ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                    $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                    $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(),
                $order ),
            'clientPhone'      => apply_filters( 'inwccrm_fivepost_clientphone', $order->get_billing_phone(), $order ),
            'clientEmail'      => apply_filters( 'inwccrm_fivepost_clientemail', $order->get_billing_email(), $order ),
            'senderLocation'   => apply_filters( 'inwccrm_fivepost_senderlocation', $senderLocation, $order ), // TODO senderLocation
            'returnLocation'   => apply_filters( 'inwccrm_fivepost_returnlocation', $senderLocation, $order ), // TODO returnLocation
            'receiverLocation' => apply_filters( 'inwccrm_fivepost_receiverlocation', $receiverLocation, $order ), // TODO receiverLocation
            'undeliverableOption' => apply_filters( 'inwccrm_fivepost_undeliverableoption', 'RETURN', $order ),
            'senderCreateDate' => apply_filters( 'inwccrm_fivepost_sendercreatedate', $order->get_date_created()->__toString(), $order ),
            'shipmentDate' => apply_filters( 'inwccrm_fivepost_shipmentdate', date( 'c', time() + 2 * DAY_IN_SECONDS ), $order ),
            'plannedReceiveDate' => apply_filters( 'inwccrm_fivepost_plannedreceivedate', date( 'c', time() + DAY_IN_SECONDS ), $order ),
            // rateTypeCode
            // vendor
            // name
            // inn
            // phone
            'cost' => apply_filters( 'inwccrm_fivepost_cost', array(
                'deliveryCost' => apply_filters( 'inwccrm_fivepost_deliverycost', floatval( $order->get_shipping_total() ), $order ),
                'deliveryCostCurrency' => apply_filters( 'inwccrm_fivepost_deliverycostcurrency', 'RUB', $order ),
                'deliveryCostCurrency' => apply_filters( 'inwccrm_fivepost_deliverycostcurrency', 'RUB', $order ),
                // prepaymentSum
                'paymentValue' => apply_filters( 'inwccrm_fivepost_paymentvalue', 
                    ( empty( $order->get_transaction_id() ) ) ? floatval( $order->get_total() ) : 0, $order ),
                'paymentCurrency' => apply_filters( 'inwccrm_fivepost_paymentcurrency', 'RUB', $order ),
                // paymentType
                'price' => apply_filters( 'inwccrm_fivepost_paymentprice', floatval( $summTotal ), $order ),       
                'priceCurrency' => apply_filters( 'inwccrm_fivepost_pricecurrency', 'RUB', $order ),                
            ), $order ),
            // Пока делаем заказ одноместным!
            'cargoes' => apply_filters( 'inwccrm_fivepost_cargoes', array(
                // Объект грузоместа
                array(
                    'barcodes' => apply_filters( 'inwccrm_fivepost_barcodes', array(), $order ),
                    'senderCargoId' => apply_filters( 'inwccrm_fivepost_sendercargoid', $order->get_order_number() . '-1', $order ),
                    'height'    => apply_filters( 'inwccrm_fivepost_cargo_height', 1, $order ),
                    'length'    => apply_filters( 'inwccrm_fivepost_cargo_length', 1, $order ),
                    'width'     => apply_filters( 'inwccrm_fivepost_cargo_width', 1, $order ),
                    'weight'    => apply_filters( 'inwccrm_fivepost_cargo_weight', $weghtTotal, $order ),
                    'price'     => apply_filters( 'inwccrm_fivepost_cargo_price', $summTotal, $order ),
                    'currency' => apply_filters( 'inwccrm_fivepost_cargo_currency', 'RUB', $order ), 
                    'productValues' => $items
                )
            ), $order )
        );
    }


    /**
     * Отправляет данные на сервер
     * @param mixed $orders Массив заказов
     */
    public function send( $orders )
    {   
         // URL отправки заказа
        $url = self::URL . self::METHOD_ORDERS;
        Plugin::get()->log( __( 'FivePost log: send orders', IN_WC_CRM ) . ': ' . $url, self::LOGFILE );

        // Проверим данные для входа
        if ( empty( $this->token ) )
        {
            throw new NoСredentialsException( __( 'Не получен API токен', IN_WC_CRM ) );
        }

        // Соберем массив заказов
        if ( empty( $orders ) )
        {
            throw new NoOrdersException( __( 'Не переданы заказы для отправки', IN_WC_CRM ) );
        }
            

        $responses = array();


        // TODO: Переписать передачу заказов одной передачей 
        $orderData = array();
        foreach ( $orders as $order )
        {
            try
            {
                // Данные заказа для передачи
                $data = $this->getOrderData( $order );
                Plugin::get()->log( __( 'Запрос', IN_WC_CRM ) . ': ' .  var_export( $data, true ), self::LOGFILE );

                // Формируем JSON
                $json_data = json_encode( array(
                    'partnerOrders' => array( $data) 
                ) );
                Plugin::get()->log( 'JSON: ' . $json_data, self::LOGFILE );

                 // Передаем заказ
                $response = wp_remote_post( $url, array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->token,
                        'Content-type' => 'application/json',
                    ),                    
                    'body' => $json_data
                ) );

                Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ) . ': ' . var_export($response, true), self::LOGFILE );              
            } 
            catch (Exception $e) // Ловим и логируем ошибки
            {
                // Возникли ошибки
                Plugin::get()->log( __( 'Ошибки', IN_WC_CRM ) . ' ' . var_export( $e, true ), self::LOGFILE );
                throw $e; 
            }
            $responses[$order->get_ID()] = $response;
        }      
        return $responses;
    }
}