<?php
/**
 * API Яндекс Доставки по России
 *
 * Документация: https://yandex.ru/support/delivery-profile/ru/api/other-day/
 */
namespace IN_WC_CRM\Extensions\YandexNew;
use \WC_Order as WC_Order;

require __DIR__ . '/Exceptions.php';

class API
{
    /**
     * Мета-поле заказа с ID созданной заявки
     */
    const META_REQUEST_ID = '_yandexnew_request_id';

    /**
     * Мета-поле заказа с ID станции ПВЗ Яндекса
     */
    const META_PLATFORM_STATION_ID = '_yandexnew_platform_station_id';

    /**
     * Хосты API
     */
    const HOST_PROD = 'https://b2b-authproxy.taxi.yandex.net';
    const HOST_TEST = 'https://b2b.taxi.tst.yandex.net';

    /**
     * Методы API
     */
    const METHOD_OFFERS_CREATE   = '/api/b2b/platform/offers/create';
    const METHOD_OFFERS_CONFIRM  = '/api/b2b/platform/offers/confirm';
    const METHOD_PICKUPS_LIST    = '/api/b2b/platform/pickups/scheduled/list';
    const METHOD_PICKUP_OPTIONS  = '/api/b2b/platform/pickups/pickup-options';
    const METHOD_PICKUPS_CREATE  = '/api/b2b/platform/pickups/create';

    /**
     * Значения по умолчанию
     */
    const DEFAULT_HOST   = 'prod';
    const DEFAULT_WEIGHT = 0.5;     // кг

    /**
     * Параметры подключения
     */
    private $host;                      // Хост API
    private $token;                     // API токен для заказов, сделанных на medknigaservis
    private $tokenRosmedlib;            // API токен для заказов, сделанных на rosmedlib
    private $sourceStation;             // Станция отгрузки для заказов, сделанных на medknigaservis
    private $sourceStationRosmedlib;    // Станция отгрузки для заказов, сделанных на rosmedlib
    private $defaultWeight;             // Вес товара по умолчанию, кг

    /**
     * Кэш запланированных отгрузок склада
     */
    private $pickupsCache = array();

    /**
     * Конструктор
     * @param string    $host                       Хост API (prod или test)
     * @param string    $token                      API токен для заказов, сделанных на medknigaservis
     * @param string    $tokenRosmedlib             API токен для заказов, сделанных на rosmedlib
     * @param string    $sourceStation              Станция отгрузки для заказов, сделанных на medknigaservis
     * @param string    $sourceStationRosmedlib     Станция отгрузки для заказов, сделанных на rosmedlib
     * @param mixed     $defaultWeight              Вес товара по умолчанию, кг
     */
    public function __construct( $host, $token, $tokenRosmedlib, $sourceStation, $sourceStationRosmedlib, $defaultWeight )
    {
        $this->host                     = ( 'test' === $host ) ? self::HOST_TEST : self::HOST_PROD;
        $this->token                    = $token;
        $this->tokenRosmedlib           = $tokenRosmedlib;
        $this->sourceStation            = $sourceStation;
        $this->sourceStationRosmedlib   = $sourceStationRosmedlib;
        $this->defaultWeight            = floatval( $defaultWeight );
    }

    /**
     * Записывает сообщение в лог WooCommerce
     * @param mixed $message Текст, массив или объект
     */
    private function log( $message )
    {
        $body = ( is_array( $message ) || is_object( $message ) ) ? print_r( $message, true ) : (string) $message;
        wc_get_logger()->info( $body, array( 'source' => 'yandexnew' ) );
    }

    /**
     * Возвращает API токен для заказа
     * @param WC_Order  $order  Заказ WooCommerce
     * @return string
     */
    private function getOrderToken( $order )
    {
        if ( ! empty( $this->tokenRosmedlib ) && 'rosmedlib' === $order->get_meta( '_order_src' ) )
        {
            return $this->tokenRosmedlib;
        }
        return $this->token;
    }

    /**
     * Создает отгрузку на ближайший доступный интервал, если у склада нет запланированных отгрузок
     * @param string    $stationId  ID станции отгрузки
     * @param string    $token      Bearer токен
     */
    private function ensureScheduledPickup( $stationId, $token )
    {
        if ( empty( $stationId ) || ! empty( $this->getScheduledPickups( $stationId, $token ) ) )
        {
            return;
        }

        try
        {
            // Ближайший доступный интервал забора со склада
            $options = $this->request( self::METHOD_PICKUP_OPTIONS, $token, array( 'station_id' => $stationId ) );
            if ( empty( $options['pickup_options'][0]['pickup_local_date'] ) || empty( $options['pickup_options'][0]['pickup_local_time_intervals'][0] ) )
            {
                return;
            }

            $option   = $options['pickup_options'][0];
            $interval = $option['pickup_local_time_intervals'][0];

            $response = $this->request( self::METHOD_PICKUPS_CREATE, $token, array(
                'station_id'                 => $stationId,
                'pickup_local_date'          => $option['pickup_local_date'],
                'pickup_local_time_interval' => array(
                    'from' => $interval['from'],
                    'to'   => $interval['to'],
                ),
                'parameters'                 => array(
                    // Минимальный объем, который принимает API
                    'volume_m3'    => '0.25',
                    'weight_g'     => 1000,
                    'requirements' => array( 'loaders_required' => false ),
                ),
            ) );

            $this->log( __( 'Создана отгрузка со склада', IN_WC_CRM ) . ' ' . $stationId . ': ' .
                $option['pickup_local_date'] . ' ' . $interval['from'] . '-' . $interval['to'] .
                '. ' . __( 'ID отгрузки', IN_WC_CRM ) . ': ' . $response['pickup_id'] );

            // Перечитаем список отгрузок с учетом созданной
            unset( $this->pickupsCache[ $stationId . '|' . md5( $token ) ] );
        }
        catch ( \Exception $e )
        {
            $this->log( __( 'Не удалось создать отгрузку со склада', IN_WC_CRM ) . ': ' . $e->getMessage() );
        }
    }

    /**
     * Возвращает станцию отгрузки для заказа
     * @param WC_Order  $order  Заказ WooCommerce
     * @return string
     */
    private function getOrderSourceStation( $order )
    {
        if ( 'rosmedlib' === $order->get_meta( '_order_src' ) )
        {
            return $this->sourceStationRosmedlib;
        }
        return $this->sourceStation;
    }

    /**
     * Приводит телефон к виду +7XXXXXXXXXX
     * @param string $phone Телефон заказа
     * @return string
     */
    private function normalizePhone( $phone )
    {
        $phone = preg_replace( '/[^\d\+]/', '', (string) $phone );
        if ( 0 === strpos( $phone, '+7' ) )
        {
            return $phone;
        }
        if ( 0 === strpos( $phone, '8' ) )
        {
            return '+7' . substr( $phone, 1 );
        }
        if ( 0 === strpos( $phone, '7' ) && 11 === strlen( $phone ) )
        {
            return '+' . $phone;
        }
        return $phone;
    }

    /**
     * Переводит вес из единиц магазина в граммы
     * @param float $weight Вес в единицах магазина
     * @return float
     */
    private function weightToGrams( $weight )
    {
        switch ( get_option( 'woocommerce_weight_unit', 'kg' ) )
        {
            case 'g':   return $weight;
            case 'lbs': return $weight * 453.59237;
            case 'oz':  return $weight * 28.349523125;
            default:    return $weight * 1000;
        }
    }

    /**
     * Переводит габариты из единиц магазина в сантиметры
     * @param float $dim Габарит в единицах магазина
     * @return float
     */
    private function dimensionToCm( $dim )
    {
        switch ( get_option( 'woocommerce_dimension_unit', 'cm' ) )
        {
            case 'mm': return $dim / 10;
            case 'm':  return $dim * 100;
            case 'in': return $dim * 2.54;
            case 'yd': return $dim * 91.44;
            default:   return $dim;
        }
    }

    /**
     * Возвращает точку получения заказа
     * @param WC_Order  $order  Заказ WooCommerce
     * @return array    Массив: destination, last_mile_policy
     */
    private function getDestination( $order )
    {
        // Доставка в ПВЗ: ID станции хранится в мета-поле заказа
        $stationId = apply_filters(
            'inwccrm_yandexnew_destination_platform_station_id',
            (string) $order->get_meta( self::META_PLATFORM_STATION_ID ),
            $order
        );

        if ( ! empty( $stationId ) )
        {
            return array(
                'destination' => array(
                    'type'              => 'platform_station',
                    'platform_station'  => array( 'platform_id' => $stationId ),
                ),
                'last_mile_policy' => 'self_pickup',
            );
        }

        // Курьерская доставка до двери
        $city = $order->get_shipping_city();
        if ( empty( $city ) ) $city = $order->get_billing_city();

        $address = trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() );
        if ( empty( trim( $order->get_shipping_address_1() ) ) )
        {
            $address = trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() );
        }

        $fullAddress = trim( implode( ', ', array_filter( array( $city, $address ) ) ) );
        if ( empty( $fullAddress ) )
        {
            throw new NoRequiredParameter( __( 'Не определен адрес доставки заказа', IN_WC_CRM ) );
        }

        // Регион: в заказе хранится код, а API ожидает название
        $region = ( ! empty( $order->get_shipping_state() ) ) ? $order->get_shipping_state() : $order->get_billing_state();
        if ( ! empty( $region ) )
        {
            $states = WC()->countries->get_states( 'RU' );
            if ( ! empty( $states[ $region ] ) )
            {
                // В названиях регионов этого сайта есть префикс «РФ, »
                $region = preg_replace( '/^РФ,\s*/u', '', $states[ $region ] );
            }
        }

        $details = array(
            'country'       => 'Россия',
            'region'        => $region,
            'locality'      => $city,
            'full_address'  => $fullAddress,
        );

        $postcode = ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode();
        if ( ! empty( $postcode ) )
        {
            $details['postal_code'] = $postcode;
        }

        return array(
            'destination' => array(
                'type'              => 'custom_location',
                'custom_location'   => array( 'details' => $details ),
            ),
            'last_mile_policy' => 'time_interval',
        );
    }

    /**
     * Возвращает список запланированных отгрузок склада (кэшируется в рамках запроса)
     * @param string    $stationId  ID станции отгрузки
     * @param string    $token      Bearer токен
     * @return array
     */
    private function getScheduledPickups( $stationId, $token )
    {
        $cacheKey = $stationId . '|' . md5( $token );
        if ( array_key_exists( $cacheKey, $this->pickupsCache ) )
        {
            return $this->pickupsCache[ $cacheKey ];
        }

        try
        {
            $response = $this->request( self::METHOD_PICKUPS_LIST, $token, array(
                'filter' => array( 'station_id' => $stationId ),
            ) );
            $this->pickupsCache[ $cacheKey ] = isset( $response['pickups'] ) ? $response['pickups'] : array();
        }
        catch ( \Exception $e )
        {
            $this->log( __( 'Не удалось получить отгрузки склада', IN_WC_CRM ) . ': ' . $e->getMessage() );
            $this->pickupsCache[ $cacheKey ] = array();
        }

        return $this->pickupsCache[ $cacheKey ];
    }

    /**
     * Возвращает ближайший запланированный интервал отгрузки склада в UTC
     * Для курьерской доставки со склада переданный интервал должен совпадать
     * с созданной отгрузкой, иначе Яндекс отклоняет заявку.
     * @param string    $stationId  ID станции отгрузки
     * @param string    $token      Bearer токен
     * @return array|null   Массив с ключами from и to либо null
     */
    private function getSourceIntervalUtc( $stationId, $token )
    {
        $pickups = $this->getScheduledPickups( $stationId, $token );
        if ( empty( $pickups ) )
        {
            return null;
        }

        $timezone = wp_timezone();
        $now = new \DateTimeImmutable( 'now', $timezone );
        $best = null;

        foreach ( $pickups as $pickup )
        {
            if ( empty( $pickup['pickup_status'] ) || 'scheduled' !== $pickup['pickup_status'] )
            {
                continue;
            }
            if ( empty( $pickup['pickup_local_date'] ) || empty( $pickup['pickup_local_time_interval']['from'] ) || empty( $pickup['pickup_local_time_interval']['to'] ) )
            {
                continue;
            }

            $from = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $pickup['pickup_local_date'] . ' ' . $pickup['pickup_local_time_interval']['from'],
                $timezone
            );
            $to = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $pickup['pickup_local_date'] . ' ' . $pickup['pickup_local_time_interval']['to'],
                $timezone
            );
            if ( ! $from || ! $to || $to < $now )
            {
                continue;
            }

            if ( null === $best || $from < $best['from'] )
            {
                $best = array( 'from' => $from, 'to' => $to );
            }
        }

        if ( null === $best )
        {
            return null;
        }

        $utc = new \DateTimeZone( 'UTC' );
        return array(
            'from' => $best['from']->setTimezone( $utc )->format( 'Y-m-d\TH:i:s.000000\Z' ),
            'to'   => $best['to']->setTimezone( $utc )->format( 'Y-m-d\TH:i:s.000000\Z' ),
        );
    }

    /**
     * Возвращает структуру заявки для отправки
     * @param WC_Order  $order  Заказ WooCommerce
     * @return array
     */
    private function getOrderData( $order )
    {
        // Станция отгрузки
        $sourceStation = $this->getOrderSourceStation( $order );
        if ( empty( $sourceStation ) )
        {
            throw new NoRequiredParameter( __( 'Не определен параметр platform_station_id (станция отгрузки)', IN_WC_CRM ) );
        }

        $decimals = max( 2, (int) wc_get_price_decimals() );

        // Штрихкод грузоместа (по умолчанию Яндекс подменяет его на свой)
        $barcode = apply_filters( 'inwccrm_yandexnew_place_barcode', $order->get_order_number() . '-1', $order );

        // Товарные позиции
        $items = array();
        $weightTotal = 0.0;
        $dx = 10;
        $dy = 10;
        $dz = 10;

        foreach ( $order->get_items() as $orderItem )
        {
            $product = $orderItem->get_product();

            // Пропускаем виртуальные товары
            if ( $product && $product->is_virtual() )
            {
                continue;
            }

            $quantity = (int) $orderItem->get_quantity();
            if ( $quantity < 1 )
            {
                $quantity = 1;
            }

            $itemTotal = (float) $orderItem->get_total() + (float) $orderItem->get_total_tax();
            // Цена за единицу с НДС, округленная до знаков после запятой магазина:
            // итог заказа считается так же, поэтому сумма к получению совпадет с ним
            $unitPrice = (int) round( round( $itemTotal / $quantity, wc_get_price_decimals() ) * 100, 0 );

            $article = ( $product && ! empty( $product->get_sku() ) )
                ? $product->get_sku()
                : 'SKU_' . $orderItem->get_product_id();

            // Вес и габариты
            if ( $product )
            {
                $weightTotal += $quantity * $this->weightToGrams( floatval( $product->get_weight() ) );
                $dx = max( $dx, (int) round( $this->dimensionToCm( floatval( $product->get_length() ) ) ) );
                $dy = max( $dy, (int) round( $this->dimensionToCm( floatval( $product->get_width() ) ) ) );
                $dz = max( $dz, (int) round( $this->dimensionToCm( floatval( $product->get_height() ) ) ) );
            }
            else
            {
                $weightTotal += $quantity * $this->weightToGrams( $this->defaultWeight );
            }

            $items[] = array(
                'count'         => $quantity,
                'name'          => apply_filters( 'inwccrm_yandexnew_orderitem_name', $orderItem->get_name(), $order, $orderItem ),
                'article'       => apply_filters( 'inwccrm_yandexnew_orderitem_article', $article, $order, $orderItem ),
                'place_barcode' => $barcode,
                'billing_details' => array(
                    // Ставка НДС: 0, 5, 7, 10, 22, для товаров без НДС — -1
                    'nds'                   => (int) apply_filters( 'inwccrm_yandexnew_orderitem_nds', 0, $order, $orderItem ),
                    // Цены передаются в копейках
                    'unit_price'            => (int) apply_filters( 'inwccrm_yandexnew_orderitem_unit_price', $unitPrice, $order, $orderItem ),
                    'assessed_unit_price'   => (int) apply_filters( 'inwccrm_yandexnew_orderitem_assessed_unit_price', $unitPrice, $order, $orderItem ),
                ),
            );
        }

        if ( empty( $items ) )
        {
            throw new NoRequiredParameter( __( 'В заказе нет товаров для отправки в Яндекс Доставку', IN_WC_CRM ) );
        }

        // Вес грузоместа в граммах
        $weightGross = (int) round( $weightTotal );
        if ( $weightGross < 1 )
        {
            $weightGross = (int) round( $this->weightToGrams( max( $this->defaultWeight, 0.1 ) ) );
        }
        $weightGross = (int) apply_filters( 'inwccrm_yandexnew_place_weight_gross', $weightGross, $order );

        // Точка получения
        $destinationData = $this->getDestination( $order );
        $lastMilePolicy = apply_filters( 'inwccrm_yandexnew_last_mile_policy', $destinationData['last_mile_policy'], $order );

        // Источник отправки
        $source = array(
            'platform_station' => array( 'platform_id' => $sourceStation ),
        );
        if ( 'time_interval' === $lastMilePolicy )
        {
            // Интервал отгрузки со склада обязателен для курьерской доставки
            $sourceInterval = apply_filters(
                'inwccrm_yandexnew_source_interval_utc',
                $this->getSourceIntervalUtc( $sourceStation, $this->getOrderToken( $order ) ),
                $order
            );
            if ( ! empty( $sourceInterval ) )
            {
                $source['interval_utc'] = $sourceInterval;
            }
        }

        // Оплата
        $paymentMethod = apply_filters(
            'inwccrm_yandexnew_payment_method',
            ( 'cod' === $order->get_payment_method() ) ? 'card_on_receipt' : 'already_paid',
            $order
        );
        // Стоимость доставки уходит та, которую платит получатель, то есть с НДС:
        // в заказе стоимость доставки хранится без налога, налог — отдельно.
        // Округляем до знаков после запятой магазина, как Woocommerce считает итог заказа.
        $deliveryGross = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
        $deliveryCost  = ( 'already_paid' === $paymentMethod ) ? 0
            : (int) round( round( $deliveryGross, wc_get_price_decimals() ) * 100 );

        // Получатель
        $firstName = $order->get_shipping_first_name();
        $lastName  = $order->get_shipping_last_name();
        if ( empty( $firstName ) && empty( $lastName ) )
        {
            $firstName = $order->get_billing_first_name();
            $lastName  = $order->get_billing_last_name();
        }
        if ( empty( $firstName ) )
        {
            $firstName = $lastName;
        }
        if ( empty( $firstName ) )
        {
            throw new NoRequiredParameter( __( 'Не определено имя получателя заказа', IN_WC_CRM ) );
        }
        $phone = $this->normalizePhone( $order->get_billing_phone() );
        if ( empty( $phone ) )
        {
            throw new NoRequiredParameter( __( 'Не определен телефон получателя заказа', IN_WC_CRM ) );
        }

        // Формирование заявки
        return array(
            'info' => array(
                'operator_request_id' => apply_filters( 'inwccrm_yandexnew_operator_request_id', $order->get_order_number(), $order ),
                'comment'             => apply_filters( 'inwccrm_yandexnew_comment', $order->get_customer_note(), $order ),
            ),
            'source' => $source,
            'destination' => $destinationData['destination'],
            'last_mile_policy' => $lastMilePolicy,
            'items' => $items,
            'places' => array(
                array(
                    'barcode' => $barcode,
                    'physical_dims' => array(
                        'weight_gross' => $weightGross,
                        'dx' => (int) apply_filters( 'inwccrm_yandexnew_place_dx', $dx, $order ),
                        'dy' => (int) apply_filters( 'inwccrm_yandexnew_place_dy', $dy, $order ),
                        'dz' => (int) apply_filters( 'inwccrm_yandexnew_place_dz', $dz, $order ),
                    ),
                ),
            ),
            'billing_info' => array(
                'payment_method' => $paymentMethod,
                'delivery_cost'  => $deliveryCost,
            ),
            'recipient_info' => array(
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => apply_filters( 'inwccrm_yandexnew_recipient_phone', $phone, $order ),
                'email'      => $order->get_billing_email(),
            ),
        );
    }

    /**
     * Выполняет запрос к API
     * @param string    $method     Метод API
     * @param string    $token      Bearer токен
     * @param array     $data       Тело запроса
     * @return array    Декодированный ответ
     * @throws ApiException
     */
    private function request( $method, $token, $data )
    {
        $url = $this->host . $method;

        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body' => wp_json_encode( $data ),
        ) );

        if ( is_wp_error( $response ) )
        {
            throw new ApiException( __( 'Ошибка запроса к Яндекс Доставке', IN_WC_CRM ) . ': ' . $response->get_error_message() );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        $this->log( $method . PHP_EOL . 'Запрос: ' . wp_json_encode( $data ) . PHP_EOL . 'Ответ (' . $code . '): ' . $body );

        $decoded = json_decode( $body, true );
        if ( 200 !== $code )
        {
            $message = ( is_array( $decoded ) && ! empty( $decoded['message'] ) ) ? $decoded['message'] : $body;
            $errorCode = ( is_array( $decoded ) && ! empty( $decoded['code'] ) ) ? ' ' . $decoded['code'] : '';
            throw new ApiException(
                __( 'Ошибка API Яндекс Доставки', IN_WC_CRM ) . ' (' . $code . $errorCode . '): ' . $message
            );
        }
        if ( ! is_array( $decoded ) )
        {
            throw new ApiException( __( 'Непредвиденный ответ API Яндекс Доставки', IN_WC_CRM ) . ': ' . $body );
        }
        return $decoded;
    }

    /**
     * Создает заявку на сервере Яндекс Доставки
     * @param WC_Order  $order  Заказ WooCommerce
     * @return array    Результат: created, request_id, pricing, error
     */
    private function sendOrder( $order )
    {
        // Заказ уже отправлен
        $requestId = $order->get_meta( self::META_REQUEST_ID );
        if ( ! empty( $requestId ) )
        {
            return array(
                'created' => false,
                'error'   => __( 'Заказ уже отправлен в Яндекс Доставку', IN_WC_CRM ) . '. ' .
                             __( 'ID заявки', IN_WC_CRM ) . ': ' . $requestId,
            );
        }

        try
        {
            $token = $this->getOrderToken( $order );

            // Если у склада нет запланированных отгрузок, создадим ближайшую
            $this->ensureScheduledPickup( $this->getOrderSourceStation( $order ), $token );

            $data = $this->getOrderData( $order );

            // Получение варианта доставки
            $createResponse = $this->request( self::METHOD_OFFERS_CREATE, $token, $data );
            if ( empty( $createResponse['offers'] ) )
            {
                throw new ApiException( __( 'Яндекс Доставка не предложила вариантов доставки', IN_WC_CRM ) );
            }

            $offer = apply_filters( 'inwccrm_yandexnew_offer', $createResponse['offers'][0], $order );
            if ( empty( $offer['offer_id'] ) )
            {
                throw new ApiException( __( 'Яндекс Доставка не вернула идентификатор варианта доставки', IN_WC_CRM ) );
            }

            // Подтверждение заявки
            $confirmResponse = $this->request( self::METHOD_OFFERS_CONFIRM, $token, array( 'offer_id' => $offer['offer_id'] ) );
            if ( empty( $confirmResponse['request_id'] ) )
            {
                throw new ApiException( __( 'Яндекс Доставка не вернула идентификатор заявки', IN_WC_CRM ) );
            }

            return array(
                'created'    => true,
                'request_id' => $confirmResponse['request_id'],
                'pricing'    => isset( $offer['offer_details']['pricing'] ) ? $offer['offer_details']['pricing'] : '',
            );
        }
        catch ( \Exception $e )
        {
            $this->log( __( 'Ошибка заказа', IN_WC_CRM ) . ' ' . $order->get_order_number() . ': ' . $e->getMessage() );
            return array(
                'created' => false,
                'error'   => $e->getMessage(),
            );
        }
    }

    /**
     * Отправляет заказы на сервер
     * @param array $orders Массив заказов
     * @return array Результаты по ID заказа
     */
    public function send( $orders )
    {
        // Проверим данные для входа
        if ( empty( $this->token ) && empty( $this->tokenRosmedlib ) )
        {
            throw new NoCredentialsException( __( 'Не указан API токен', IN_WC_CRM ) );
        }

        // Соберем массив заказов
        if ( empty( $orders ) )
        {
            throw new NoOrdersException( __( 'Не переданы заказы для отправки', IN_WC_CRM ) );
        }

        $results = array();
        foreach ( $orders as $order )
        {
            $results[ $order->get_id() ] = $this->sendOrder( $order );
        }
        return $results;
    }
}
