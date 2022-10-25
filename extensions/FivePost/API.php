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
    

    /**
     * Параметры удаленного сервера, подключения и других параметров
     */
    private $token;             // API Токен


    /**
     * Конструктор
     * @param string    $apiKey            API ключ

     */
    public function __construct( $apiKey )
    {
        $this->token = $this->getToken( $apiKey );
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
        // Элементы заказа
        $items = array();
        $summTotal = 0;
        $weghtTotal = 0;    
        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            $product = $orderItem->get_product();
            $sku = ( ! empty( $product->get_sku() ) ) ? $product->get_sku() : 'SKU_' .  $product->get_id();
            $itemQuantity = $orderItem->get_quantity();
            $itemTotalPrice = $orderItem->get_total();
            $itemPrice = ($itemQuantity > 0 ) ? round( $itemTotalPrice / $itemQuantity, 2 ) : $itemTotalPrice;
            $summTotal += $itemTotalPrice;
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
            'senderLocation'   => apply_filters( 'inwccrm_fivepost_senderlocation', '', $order ), // TODO senderLocation
            'returnLocation'   => apply_filters( 'inwccrm_fivepost_returnlocation', '', $order ), // TODO returnLocation
            'receiverLocation' => apply_filters( 'inwccrm_fivepost_receiverlocation', '', $order ), // TODO receiverLocation
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
                'deliveryCost' => apply_filters( 'inwccrm_fivepost_deliverycost', $order->get_shipping_total(), $order ),
                'deliveryCostCurrency' => apply_filters( 'inwccrm_fivepost_deliverycostcurrency', 'RUB', $order ),
                'deliveryCostCurrency' => apply_filters( 'inwccrm_fivepost_deliverycostcurrency', 'RUB', $order ),
                // prepaymentSum
                'paymentValue' => apply_filters( 'inwccrm_fivepost_paymentvalue', 
                    ( empty( $order->get_transaction_id() ) ) ? $order->get_total() : 0, $order ),
                'paymentCurrency' => apply_filters( 'inwccrm_fivepost_paymentcurrency', 'RUB', $order ),
                // paymentType
                'price' => apply_filters( 'inwccrm_fivepost_paymentprice', $summTotal, $order ),       
                'priceCurrency' => apply_filters( 'inwccrm_fivepost_pricecurrency', 'RUB', $order ),                
            ), $order ),
            // Пока делаем заказ одноместным!
            'cargoes' => apply_filters( 'inwccrm_fivepost_cargoes', array(
                // Объект грузоместа
                array(
                    'barcodes' => apply_filters( 'inwccrm_fivepost_barcodes', array(), $order ),
                    'senderCargoId' => apply_filters( 'inwccrm_fivepost_sendercargoid', $order->get_order_number() . '-1', $order ),
                    'height'    => apply_filters( 'inwccrm_fivepost_height', 0, $order ),
                    'length'    => apply_filters( 'inwccrm_fivepost_length', 0, $order ),
                    'width'     => apply_filters( 'inwccrm_fivepost_width', 0, $order ),
                    'weight'    => apply_filters( 'inwccrm_fivepost_weight', $weghtTotal, $order ),
                    'price'     => apply_filters( 'inwccrm_fivepost_price', $summTotal, $order ),
                    'priceCurrency' => apply_filters( 'inwccrm_fivepost_pricecurrency', 'RUB', $order ), 
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
        Plugin::get()->log( __( 'FivePost log: send orders', IN_WC_CRM ) . ': ' . self::URL, self::LOGFILE );

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

        $orderData = array();
        foreach ( $orders as $order )
        {
            try
            {
                // Данные заказа для передачи
                $data = $this->getOrderData( $order );
                Plugin::get()->log( __( 'Запрос', IN_WC_CRM ), self::LOGFILE );
                Plugin::get()->log( $data, self::LOGFILE ); 

                // Передача заказа
                $args = array(
                    'headers' => array(
                        'Authorization' => $this->token,
                        'Content-type' => 'application/json',
                    ),                    
                    'body' => array(
                        'partnerOrders' => json_encode($data)
                    ) 
                    
                );
                $response = wp_remote_post( self::URL, $args );

                Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ), self::LOGFILE );
                Plugin::get()->log( $response, self::LOGFILE );                   
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