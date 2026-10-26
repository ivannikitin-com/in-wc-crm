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
     * Значение по умолчанию для заказов, сделанных на medknigaservis
     */
    const DEFAULT_SENDER_LOCATION = 'medknigaservis.ru';

    /**
     * API ключи
     */
    private $apiKey;                        // API ключ
    private $apiKeyRosmedlib;               // API ключ для заказов, сделанных на rosmedlib
    private $senderLocation;                // Склад отправителя для заказов, сделанных на medknigaservis
    private $senderLocationRosmedlib;       // Склад отправителя для заказов, сделанных на rosmedlib

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
     * Записывает лог FivePost независимо от WP_DEBUG.
     *
     * @param mixed $message
     */
    private function logAlways( $message )
    {
        if ( ! WP_DEBUG ) return;
        $logfile = Plugin::get()->path . self::LOGFILE;
        $body = ( is_array( $message ) || is_object( $message ) ) ? print_r( $message, true ) : (string) $message;
        file_put_contents(
            $logfile,
            '[' . date('d.m.Y H:i:s') . '] ' . ': ' . $body . PHP_EOL,
            FILE_APPEND
        );
    }


    /**
     * Конструктор
     * @param string    $apiKey                     API ключ
     * @param string    $apiKeyRosmedlib            API ключ для заказов, сделанных на rosmedlib
     * @param string    $senderLocation             Склад отправителя для заказов, сделанных на medknigaservis
     * @param string    $senderLocationRosmedlib    Склад отправителя для заказов, сделанных на rosmedlib

     */
    public function __construct( $apiKey, $apiKeyRosmedlib, $senderLocation, $senderLocationRosmedlib )
    {
        $this->apiKey = $apiKey;
        $this->apiKeyRosmedlib = $apiKeyRosmedlib;
        $this->senderLocation = $senderLocation;
        $this->senderLocationRosmedlib = $senderLocationRosmedlib;
    }

    /**
     * Возвращает API ключ для заказа
     * @param WC_Order  $order  Заказ WooCommerce
     * @return string
     */
    private function getOrderApiKey( $order )
    {
        if ( ! empty( $this->apiKeyRosmedlib ) && 'rosmedlib' === $order->get_meta( '_order_src' ) )
        {
            return $this->apiKeyRosmedlib;
        }
        return $this->apiKey;
    }

    /**
     * Возвращает склад отправителя для заказа
     * @param WC_Order  $order  Заказ WooCommerce
     * @return string
     */
    private function getOrderSenderLocation( $order )
    {
        if ( 'rosmedlib' === $order->get_meta( '_order_src' ) )
        {
            return $this->senderLocationRosmedlib;
        }
        return $this->senderLocation;
    }

    /**
     * Получает Bearer токен (кэшируется отдельно для каждого API ключа)
     */
    private function getToken( $apiKey )
    {
        $cacheKey = self::TOKEN_CACHE . '_' . md5( $apiKey );
        $token = get_transient( $cacheKey );
        if ( false === $token ) {
            $token = $this->requestToken( $apiKey );
            set_transient( $cacheKey, $token, 30 * MINUTE_IN_SECONDS );
        }
        return $token;
    }

    /**
     * Запрашивает Bearer токен
     */
    private function requestToken( $apiKey ){
        $this->logAlways( 'Получение токена для API Key: ' . $apiKey ); 
        
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
            $this->logAlways( 'Ответ сервера: ' . var_export($response, true) );
            throw new GetTokenException( 
                __( 'Токен FivePost не получен' , IN_WC_CRM ) . 
                ': ' . var_export($response, true)
            );
        };

        $responseObj = json_decode( $response['body'] );
        $this->logAlways( 'Токен получен: ' . $responseObj->jwt );
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
        $senderLocation = $this->getOrderSenderLocation( $order );
        if ( empty( $senderLocation ) )
        {
            throw new NoRequiredParameter( __( 'Не определен параметр senderLocation (склад отправителя)', IN_WC_CRM ) );
        }        

        // Точка получения: сперва берем мету от плагина fivepost, затем fallback на UUID в адресе.
        $receiverLocation = $order->get_meta( '_fivepost_point_id' );
        $receiverLocationSource = 'meta';
        if ( empty( $receiverLocation ) || 'NoPoint' === $receiverLocation ) {
            preg_match('/UUID:\s?([a-fA-F0-9\-]+)(?=[,\s]|$)/', $order->get_formatted_shipping_address(), $matches);
            $receiverLocation = ( $matches ) ? $matches[1] : false;
            $receiverLocationSource = 'shipping_address_uuid';
        } else {
            $matches = array( 'meta' => $receiverLocation );
        }

        $this->logAlways( __( 'FivePost Адрес доставки ', IN_WC_CRM ) . ': ' . $order->get_formatted_shipping_address() );
        $this->logAlways( __( 'FivePost Точка получения ', IN_WC_CRM ) . ': ' . var_export( $matches, true ) );
        $this->logAlways( __( 'receiverLocation ', IN_WC_CRM ) . ': ' . var_export( $receiverLocation, true ) );
        $this->logAlways( __( 'receiverLocation source ', IN_WC_CRM ) . ': ' . $receiverLocationSource );

        if ( empty( $receiverLocation ) )
        {
            throw new NoRequiredParameter( __( 'Не определен параметр receiverLocation (точка получения)', IN_WC_CRM ) );
        } 

        // Элементы заказа
        $items = array();
        $itemTotals = array();
        $summTotal = 0;
        $weghtTotal = 0;
        $decimals = wc_get_price_decimals();
        
        $fivePostSettings = get_option( 'IN_WC_CRM-Extensions-FivePost', array() );
        $defaultItemWeight = isset( $fivePostSettings['FivePost-default-weight'] )
            ? floatval( $fivePostSettings['FivePost-default-weight'] )
            : 0.0;

        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            $product = $orderItem->get_product();
            if ( $product && $product->is_virtual() ) {
                continue;
            }

            $itemQuantity = $orderItem->get_quantity();
            $itemTotalPrice = round($orderItem->get_total() + $orderItem->get_total_tax(), $decimals);
            $itemPrice = ($itemQuantity > 0 ) ? round( $itemTotalPrice / $itemQuantity, $decimals ) : $itemTotalPrice;
            $summTotal += $itemTotalPrice;
            $itemWeight = $product ? floatval( $product->get_weight() ) : $defaultItemWeight;
            $itemWeghtTotal = $itemQuantity * $itemWeight;
            $weghtTotal += $itemWeghtTotal;

            //  Массив товарных вложений
            $items[] = array(
                // Наименование товара
                'name'      => apply_filters( 'inwccrm_fivePost_orderitem_name', $orderItem->get_name(), $order, $orderItem ),                
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
            $itemTotals[] = array(
                'index' => count( $items ) - 1,
                'line_total' => $itemTotalPrice,
                'quantity' => max( 1, (int) $itemQuantity ),
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

        // Получаем сумму сборов (fees) - бонусы, скидки и другие дополнительные сборы
        $feesTotal = $order->get_total_fees();

        // Стоимость доставки с НДС: API FivePost принимает не более 2 знаков после запятой.
        $deliveryCost = round( floatval( $order->get_shipping_total() ) + abs( floatval( $order->get_shipping_tax() ) ), min( 2, (int) $decimals ) );
        $paymentValue = ( empty( $order->get_transaction_id() ) ) ? floatval( $order->get_total() ) : 0;

        $servicesTotal = ( $feesTotal > 0 ) ? round( $feesTotal, $decimals ) : 0.0;

        // Единая подгонка товарной части для всех заказов:
        // sum(productValues.price * productValues.value) = paymentValue - deliveryCost - servicesTotal.
        if ( ! empty( $itemTotals ) )
        {
            $multiplier = (int) pow( 10, $decimals );
            $lineCents = array();
            $sumLineCents = 0;
            foreach ( $itemTotals as $row )
            {
                $cents = (int) round( $row['line_total'] * $multiplier );
                $lineCents[] = $cents;
                $sumLineCents += $cents;
            }

            if ( $sumLineCents > 0 )
            {
                $targetItemsTotal = max( 0, round( $paymentValue - $deliveryCost - $servicesTotal, $decimals ) );
                $targetCents = (int) round( $targetItemsTotal * $multiplier );
                $allocated = 0;
                $newLineCents = array();
                $fractions = array();

                foreach ( $lineCents as $i => $cents )
                {
                    $exact = ( $targetCents * $cents ) / $sumLineCents;
                    $floor = (int) floor( $exact );
                    $newLineCents[$i] = $floor;
                    $fractions[$i] = $exact - $floor;
                    $allocated += $floor;
                }

                $remainder = $targetCents - $allocated;
                if ( $remainder > 0 )
                {
                    arsort( $fractions );
                    foreach ( array_keys( $fractions ) as $i )
                    {
                        if ( $remainder <= 0 ) break;
                        $newLineCents[$i]++;
                        $remainder--;
                    }
                }

                $summTotal = 0;
                foreach ( $itemTotals as $i => $row )
                {
                    $lineTotal = $newLineCents[$i] / $multiplier;
                    $quantity = max( 1, (int) $row['quantity'] );
                    $unitPrice = round( $lineTotal / $quantity, $decimals );
                    $items[$row['index']]['price'] = apply_filters( 'inwccrm_fivePost_orderitem_price', $unitPrice, $order );
                    $summTotal += round( $unitPrice * $quantity, $decimals );
                }
                $summTotal = round( $summTotal, $decimals );

                // Финальная компенсация накопленной погрешности округления unit price.
                $diff = round( $targetItemsTotal - $summTotal, $decimals );
                if ( 0.0 !== (float) $diff )
                {
                    $adjustIdx = -1;
                    foreach ( $itemTotals as $row )
                    {
                        if ( 1 === (int) $row['quantity'] )
                        {
                            $adjustIdx = $row['index'];
                            break;
                        }
                    }
                    if ( -1 === $adjustIdx )
                    {
                        $last = end( $itemTotals );
                        $adjustIdx = $last['index'];
                    }
                    $items[$adjustIdx]['price'] = round( $items[$adjustIdx]['price'] + $diff, $decimals );
                    $summTotal = round( $targetItemsTotal, $decimals );
                }
            }
        }
        
        $cost = array(
            'deliveryCost' => apply_filters( 'inwccrm_fivepost_deliverycost', $deliveryCost, $order ),
            'deliveryCostCurrency' => apply_filters( 'inwccrm_fivepost_deliverycostcurrency', 'RUB', $order ),
            // prepaymentSum
            'paymentValue' => apply_filters( 'inwccrm_fivepost_paymentvalue',
                $paymentValue, $order ),
            'paymentCurrency' => apply_filters( 'inwccrm_fivepost_paymentcurrency', 'RUB', $order ),
            // paymentType
            'price' => apply_filters( 'inwccrm_fivepost_paymentprice', round( $summTotal, $decimals ), $order ),
            'priceCurrency' => apply_filters( 'inwccrm_fivepost_pricecurrency', 'RUB', $order ),
        );

        if ( $servicesTotal > 0 )
        {
            $cost['services'] = apply_filters( 'inwccrm_fivepost_services', array(
                array(
                    'paymentValue' => apply_filters( 'inwccrm_fivepost_services_paymentvalue', $servicesTotal, $order ),
                ),
            ), $order );
        }

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
            'cost' => apply_filters( 'inwccrm_fivepost_cost', $cost, $order ),
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
                    'price'     => apply_filters( 'inwccrm_fivepost_cargo_price', round( $summTotal, $decimals ), $order ),
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
        $this->logAlways( __( 'FivePost log: send orders', IN_WC_CRM ) . ': ' . $url );

        // Проверим данные для входа
        if ( empty( $this->apiKey ) && empty( $this->apiKeyRosmedlib ) )
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
                $this->logAlways( __( 'Запрос', IN_WC_CRM ) . ': ' .  var_export( $data, true ) );

                // Получаем токен для заказа
                $token = $this->getToken( $this->getOrderApiKey( $order ) );

                // Формируем JSON
                $json_data = json_encode( array(
                    'partnerOrders' => array( $data) 
                ) );
                $this->logAlways( 'JSON: ' . $json_data );

                 // Передаем заказ
                $response = wp_remote_post( $url, array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Content-type' => 'application/json',
                    ),                    
                    'body' => $json_data
                ) );

                $this->logAlways( __( 'Ответ сервера', IN_WC_CRM ) . ': ' . var_export($response, true ) );              
            } 
            catch (Exception $e) // Ловим и логируем ошибки
            {
                // Возникли ошибки
                $this->logAlways( __( 'Ошибки', IN_WC_CRM ) . ' ' . var_export( $e, true ) );
                throw $e; 
            }
            $responses[$order->get_ID()] = $response;
        }      
        return $responses;
    }
}