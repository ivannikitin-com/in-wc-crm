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
     * https://documenter.getpostman.com/view/7354859/SzezaWVs?version=latest#190f1627-c943-4b26-a3ac-58db8e7355e0
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
                // Артикул товара
                'id'        => apply_filters( 'inwccrm_FivePost_orderitem_id', $sku, $order, $orderItem ),
                // Наименование товара
                'name'      => apply_filters( 'inwccrm_FivePost_orderitem_name', $product->get_name(), $order, $orderItem ),
                // Единица измерения
                'UnitName'  => apply_filters( 'inwccrm_FivePost_orderitem_unitname', 'шт', $orderItemId, $order, $orderItem ),
                // Процент НДС (число от 0 до 20)
                'nds'       => apply_filters( 'inwccrm_FivePost_orderitem_nds', 0, $orderItemId, $order, $orderItem ),
                // Цена за единицу товара
                'price'     => apply_filters( 'inwccrm_FivePost_orderitem_price', $itemPrice, $order, $orderItem ),
                // Количество единиц товара
                'quantity'  => apply_filters( 'inwccrm_FivePost_orderitem_count', $itemQuantity, $order, $orderItem )
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
            ) $order ),
            // Пока делаем заказ одноместным!
            'cargoes' => apply_filters( 'inwccrm_fivepost_cargoes', array(
                // Объект грузоместа
                array(
                    'barcodes' => apply_filters( 'inwccrm_fivepost_barcodes', array(), $order )
                    'senderCargoId' => apply_filters( 'inwccrm_fivepost_sendercargoid', $order->get_order_number() . '-1', $order ),
                    'height': apply_filters( 'inwccrm_fivepost_height', 0, $order ),
                    'length': apply_filters( 'inwccrm_fivepost_length', 0, $order ),
                    'width': apply_filters( 'inwccrm_fivepost_width', 0, $order ),
                    'weight': apply_filters( 'inwccrm_fivepost_weight', $weghtTotal, $order ),
                    'price' => apply_filters( 'inwccrm_fivepost_price', $summTotal, $order ),
                    'priceCurrency' => apply_filters( 'inwccrm_fivepost_pricecurrency', 'RUB', $order ), 
                    'productValues' => $items
                    // ...
                )
            ), $order )




            'updateByTrack' => apply_filters( 'inwccrm_FivePost_updatebytrack', '', $order ),
            'order_id'      => apply_filters( 'inwccrm_FivePost_order_id', $order->get_order_number(), $order ),
            'PalletNumber'  => apply_filters( 'inwccrm_FivePost_palletnumber', '', $order ),
            'barcode'       => apply_filters( 'inwccrm_FivePost_barcode', '', $order ),
            // Объявленная стоимость посылки БЕЗ доставки
            'price'         => apply_filters( 'inwccrm_FivePost_price', $summTotal, $order ),
            // Сумма к оплате (сумма, которую необходимо взять с получателя).
            // Для полностью предоплаченного заказа указывать 0.
            'payment_sum'   => apply_filters( 'inwccrm_FivePost_payment_sum', $order->get_total(), $order ), 
            // Стоимость доставки объявленная получателю 
            'delivery_sum'  => apply_filters( 'inwccrm_FivePost_delivery_sum', $order->get_shipping_total(), $order ),
            // Вид выдачи заказа, возможные значения:
            //  0 - выдача без вскрытия, 
            //  1 - выдача со вскрытием и проверкой комплектности,
            //  2 - выдача части вложения.
            'issue'         => apply_filters( 'inwccrm_FivePost_issue', '', $order ),
            // Вид доставки:
            //  1 - Доставка до пункта выдачи (ПВЗ, «Экспорт из РФ»)
            //  2 - Курьерская доставка (КД)
            //  3 - доставка Почтой России (ПР)
            'vid'           => apply_filters( 'inwccrm_FivePost_vid', 3, $order ),

            // 	Блок с информацией о курьерской доставке и доставке Почтой России
            'kurdost'  => apply_filters( 'inwccrm_FivePost_kurdost', array(
                    'index'     => apply_filters( 'inwccrm_FivePost_kurdost_index', ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode(), $order ),
                    'citi'      => apply_filters( 'inwccrm_FivePost_kurdost_citi', ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city(), $order ),
                    'addressp'  => apply_filters( 'inwccrm_FivePost_kurdost_addressp', 
                        ( ! empty( $order->get_shipping_address_1() ) ) ? 
                        $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2() : 
                        $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                    // Следующие значения передаются только для Курьерской доставки по направлениям: 
                    //      Москва - Москва
                    //      Москва - Санкт-Петербург
                    //      Санкт-Петербург - Москва
                    //      Санкт-Петербург - Санкт-Петербург
                    //
                    // Дата курьерской доставки (формат ГГГГ-ММ-ДД). 
                    // Может принимать значения +1 +5 дней от текущей даты.
                    // Значение по умолчанию - текущая дата + 1 день.

                    // Время курьерской доставки ОТ (формат чч:мм)
                    'timesfrom1' => apply_filters( 'inwccrm_FivePost_kurdost_timesfrom1', '', $order ),
                    // Время курьерской доставки ДО (формат чч:мм)
                    'timesto1'   => apply_filters( 'inwccrm_FivePost_kurdost_timesto1', '', $order ),
                    // Альтернативное время, от
                    'timesfrom2' => apply_filters( 'inwccrm_FivePost_kurdost_timesfrom2', '', $order ),
                    // Альтернативное время, до
                    'timesto2'   => apply_filters( 'inwccrm_FivePost_kurdost_timesto2', '', $order ),
                    // Время доставки текстовый формат  (не используется)
                    'timep'      => apply_filters( 'inwccrm_FivePost_kurdost_timep', '', $order ),
                    // Комментарий по доставке (не используется)
                    'comentk'    => apply_filters( 'inwccrm_FivePost_kurdost_comentk', '', $order ),
                    // Тип упаковки: 
                    //  1 - упаковка ИМ, 
                    //  2 - упаковка FivePost
                    'packing_type'  => apply_filters( 'inwccrm_FivePost_packing_type', 1, $order ),
                    // Строгая упаковка: 
                    //  1 - изменение упаковки в процессе транспортировки запрещено, 
                    //  0 - разрешено
                    'packing_strict'=> apply_filters( 'inwccrm_FivePost_packing_strict', 1, $order ),                
                ), $order), // inwccrm_FivePost_kurdost

            // Блок с информацией о пункте приема и пункте выдачи отправления
            'shop'  => array(
                // Код пункта выдачи 
                'name'   => apply_filters( 'inwccrm_FivePost_shop_name',  '', $order ),
                // Код пункта поступления
                'name1'  => apply_filters( 'inwccrm_FivePost_shop_name1', '010', $order ),
            ),
            
            // Блок с информацией о получателе отправления
            'customer' => array(
                'fio'    => apply_filters( 'inwccrm_FivePost_customer_fio', 
                    ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                    $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                    $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(),
                    $order ),
                'phone'   => apply_filters( 'inwccrm_FivePost_customer_phone', $phone, $order ),
                'phone2'  => apply_filters( 'inwccrm_FivePost_customer_phone2', '', $order ),
                'email'   => apply_filters( 'inwccrm_FivePost_customer_email', $order->get_billing_email(), $order ),
                'name'    => apply_filters( 'inwccrm_FivePost_customer_name', $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(), $order ),
                'address' => apply_filters( 'inwccrm_FivePost_customer_address', 
                ( ! empty( $order->get_shipping_address_1() ) ) ? 
                        $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2() : 
                        $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                // $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                'inn'     => apply_filters( 'inwccrm_FivePost_customer_inn', '', $order ),
                'kpp'     => apply_filters( 'inwccrm_FivePost_customer_kpp', '', $order ),
                'r_s'     => apply_filters( 'inwccrm_FivePost_customer_r_s', '', $order ),
                'bank'    => apply_filters( 'inwccrm_FivePost_customer_bank', '', $order ),
                'kor_s'   => apply_filters( 'inwccrm_FivePost_customer_kor_s', '', $order ),
                'bik'     => apply_filters( 'inwccrm_FivePost_customer_bik', '', $order ),              
            ),

            //  Массив товарных вложений
            'items' => $items,

            // Примечание к заказу.
            'notice' => apply_filters( 'inwccrm_FivePost_notice', $order->get_customer_note(), $order ),

            'weights' => array(
                'weight'   => apply_filters( 'inwccrm_FivePost_weights_weight', $weghtTotal, $order ), //В параметр weight нужно передавать вес в граммах, т.е. целое число,
                'x'        => apply_filters( 'inwccrm_FivePost_weights_x', 1, $order ),
                'y'        => apply_filters( 'inwccrm_FivePost_weights_y', 1, $order ),
                'z'        => apply_filters( 'inwccrm_FivePost_weights_z', 1, $order ),
                'barcode'  => apply_filters( 'inwccrm_FivePost_weights_barcode', '', $order ),
            ),
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
                /*
                Plugin::get()->log( __( 'Способ доставки', IN_WC_CRM ) . 
                    ': ' . $order->get_shipping_method(), self::LOGFILE );
                    
                Plugin::get()->log( __( 'Адрес доставки', IN_WC_CRM ) . 
                    ': ' . $order->get_shipping_address_1(), self::LOGFILE );

                Plugin::get()->log( __( 'Заказ', IN_WC_CRM ) . 
                    ': ' . var_export($order, true), self::LOGFILE );
                */

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