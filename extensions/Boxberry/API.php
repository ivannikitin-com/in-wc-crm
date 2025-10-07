<?php
/**
 * API Boxberry
 */
namespace IN_WC_CRM\Extensions\Boxberry;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;


require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'boxberry-rest.log';


    /**
     * Точка отправки запроса
     */
    const URL = 'https://api.boxberry.ru/json.php';
    

    /**
     * Параметры удаленного сервера, подключения и других параметров
     */
    private $token;             // API Токен


    /**
     * Конструктор
     * @param string    $token            API Токен

     */
    public function __construct( $token )
    {
        $this->token = $token;
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
        
        // Получаем настройки округления WooCommerce
        $decimals = wc_get_price_decimals();    
        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            $product = $orderItem->get_product();

            if ( ! $product ) {
                $_products = get_posts( array(  
                    'post_status' => 'publish', 
                    'post_type' => 'product',
                    'title' => $orderItem->get_name()
                ));
                if (! empty( $_products )) {
                    $product_got_by_title = $_products[0];
                    $product = wc_get_product($product_got_by_title->ID);
                } else {
                    $product = NULL;
                }
            }               
            if ( ! empty($product) )  {
                $sku = ( ! empty( $product->get_sku() ) ) ? $product->get_sku() : 'SKU_' .  $product->get_id();
                
            } else {
                $sku = '';
                // Логируем отсутствующий товар
                Plugin::get()->log( __( 'Boxberry товар не найден', IN_WC_CRM ) . ': ' . $orderItem->get_name() . ' (ID: ' . $orderItem->get_product_id() . ')', self::LOGFILE );
            }
            if ($product && $product->is_virtual()) {
                // Пропускаем виртуальый товар
                continue;
            }
            //$sku = ( ! empty( $product->get_sku() ) ) ? $product->get_sku() : 'SKU_' .  $product->get_id();
            $itemQuantity = $orderItem->get_quantity();
                       
            $itemTotalPrice = $orderItem->get_total() + $orderItem->get_total_tax();
            // Используем стандартные настройки округления WooCommerce
            $itemPrice = ($itemQuantity > 0 ) ? round( $itemTotalPrice / $itemQuantity, $decimals ) : round( $itemTotalPrice, $decimals );
            
             $summTotal += round( $itemTotalPrice, $decimals );
            
            if ( ! empty($product) ) {
            $itemWeghtTotal = $itemQuantity * floatval( $product->get_weight() );
            } 
            $weghtTotal += $itemWeghtTotal;

            //  Массив товарных вложений
            $items[] = array(
                // Артикул товара
                'id'        => apply_filters( 'inwccrm_boxberry_orderitem_id', $sku, $order, $orderItem ),
                // Наименование товара
                'name'      => apply_filters( 'inwccrm_boxberry_orderitem_name', ( $product ? $product->get_name() : $orderItem->get_name() ), $order, $orderItem ),
                // Единица измерения
                'UnitName'  => apply_filters( 'inwccrm_boxberry_orderitem_unitname', 'шт', $orderItemId, $order, $orderItem ),
                // Процент НДС (число от 0 до 20)
                'nds'       => apply_filters( 'inwccrm_boxberry_orderitem_nds', 0, $orderItemId, $order, $orderItem ),
                // Цена за единицу товара
                'price'     => apply_filters( 'inwccrm_boxberry_orderitem_price', $itemPrice, $order, $orderItem ),
                // Количество единиц товара
                'quantity'  => apply_filters( 'inwccrm_boxberry_orderitem_count', $itemQuantity, $order, $orderItem )
            );
        }

        // В параметр weight нужно передавать вес в граммах, т.е. целое число
        $weghtTotal = intval( $weghtTotal * 1000 );
        //  вес первого или единственного тарного места, в граммах. Минимальное значение 5 г, максимальное – 31000 г.
        if ( $weghtTotal < 5 ) $weghtTotal = 5;
        if ( $weghtTotal > 31000 ) $weghtTotal = 31000;

        // они просят передавать 9001122333 без +7 или +8
        // а для казахстана и беларуси 12 последних цифр
        $phone = preg_replace( '/[\s\-\(\)\.]/', '', $order->get_billing_phone() );
        if ( strpos( $phone, '+7') == 0 ) $phone = str_replace( '+7', '', $phone );
        $phone = str_replace( '+', '', $phone );

        // Дефолтовое значение delivery_date
        $delivery_date = date( 'Y-m-d', time() + DAY_IN_SECONDS );

        // Получаем сумму сборов (fees) - бонусы, скидки и другие дополнительные сборы
        $feesTotal = $order->get_total_fees();
        
        // Логируем детальную информацию о сборах
        $fees = $order->get_fees();
        if (!empty($fees)) {
            foreach ($fees as $fee) {
                Plugin::get()->log( __( 'Boxberry сбор', IN_WC_CRM ) . ': ' . $fee->get_name() . ' = ' . $fee->get_total(), self::LOGFILE );
            }
        }
        
        // Логируем итоговые суммы для сравнения
        Plugin::get()->log( __( 'Boxberry итоговые суммы', IN_WC_CRM ) . ': summTotal=' . $summTotal . ', feesTotal=' . $feesTotal . ', order->get_total()=' . $order->get_total() . ', order->get_shipping_total()=' . $order->get_shipping_total(), self::LOGFILE );
        
        // Формирование и возврат заказа
        return array(
            'updateByTrack' => apply_filters( 'inwccrm_boxberry_updatebytrack', '', $order ),
            'order_id'      => apply_filters( 'inwccrm_boxberry_order_id', $order->get_order_number(), $order ),
            'PalletNumber'  => apply_filters( 'inwccrm_boxberry_palletnumber', '', $order ),
            'barcode'       => apply_filters( 'inwccrm_boxberry_barcode', '', $order ),
            // Объявленная стоимость посылки БЕЗ доставки
            'price'         => apply_filters( 'inwccrm_boxberry_price', round( $summTotal, $decimals ), $order ),
            // Сумма к оплате (сумма, которую необходимо взять с получателя).
            // Для полностью предоплаченного заказа указывать 0.
            'payment_sum'   => apply_filters( 'inwccrm_boxberry_payment_sum', $order->get_total(), $order ), 
            // Стоимость доставки объявленная получателю 
            'delivery_sum'  => apply_filters( 'inwccrm_boxberry_delivery_sum', $order->get_shipping_total(), $order ),
            // Сумма сборов (бонусы, скидки и другие дополнительные сборы)
            'fees_sum'      => apply_filters( 'inwccrm_boxberry_fees_sum', round( $feesTotal, $decimals ), $order ),
            // Вид выдачи заказа, возможные значения:
            //  0 - выдача без вскрытия, 
            //  1 - выдача со вскрытием и проверкой комплектности,
            //  2 - выдача части вложения.
            'issue'         => apply_filters( 'inwccrm_boxberry_issue', '', $order ),
            // Вид доставки:
            //  1 - Доставка до пункта выдачи (ПВЗ, «Экспорт из РФ»)
            //  2 - Курьерская доставка (КД)
            //  3 - доставка Почтой России (ПР)
            'vid'           => apply_filters( 'inwccrm_boxberry_vid', 3, $order ),

            // 	Блок с информацией о курьерской доставке и доставке Почтой России
            'kurdost'  => apply_filters( 'inwccrm_boxberry_kurdost', array(
                    'index'     => apply_filters( 'inwccrm_boxberry_kurdost_index', ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode(), $order ),
                    'citi'      => apply_filters( 'inwccrm_boxberry_kurdost_citi', ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city(), $order ),
                    'addressp'  => apply_filters( 'inwccrm_boxberry_kurdost_addressp', 
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
                    'delivery_date' => apply_filters( 'inwccrm_boxberry_kurdost_delivery_date', $delivery_date, $order ),
                    // Время курьерской доставки ОТ (формат чч:мм)
                    'timesfrom1' => apply_filters( 'inwccrm_boxberry_kurdost_timesfrom1', '', $order ),
                    // Время курьерской доставки ДО (формат чч:мм)
                    'timesto1'   => apply_filters( 'inwccrm_boxberry_kurdost_timesto1', '', $order ),
                    // Альтернативное время, от
                    'timesfrom2' => apply_filters( 'inwccrm_boxberry_kurdost_timesfrom2', '', $order ),
                    // Альтернативное время, до
                    'timesto2'   => apply_filters( 'inwccrm_boxberry_kurdost_timesto2', '', $order ),
                    // Время доставки текстовый формат  (не используется)
                    'timep'      => apply_filters( 'inwccrm_boxberry_kurdost_timep', '', $order ),
                    // Комментарий по доставке (не используется)
                    'comentk'    => apply_filters( 'inwccrm_boxberry_kurdost_comentk', '', $order ),
                    // Тип упаковки: 
                    //  1 - упаковка ИМ, 
                    //  2 - упаковка Boxberry
                    'packing_type'  => apply_filters( 'inwccrm_boxberry_packing_type', 1, $order ),
                    // Строгая упаковка: 
                    //  1 - изменение упаковки в процессе транспортировки запрещено, 
                    //  0 - разрешено
                    'packing_strict'=> apply_filters( 'inwccrm_boxberry_packing_strict', 1, $order ),                
                ), $order), // inwccrm_boxberry_kurdost

            // Блок с информацией о пункте приема и пункте выдачи отправления
            'shop'  => array(
                // Код пункта выдачи 
                'name'   => apply_filters( 'inwccrm_boxberry_shop_name',  '', $order ),
                // Код пункта поступления
                'name1'  => apply_filters( 'inwccrm_boxberry_shop_name1', '0262bd9d-5837-4209-8f09-e3217a765198', $order ),
            ),
            
            // Блок с информацией о получателе отправления
            'customer' => array(
                'fio'    => apply_filters( 'inwccrm_boxberry_customer_fio', 
                    ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                    $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                    $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(),
                    $order ),
                'phone'   => apply_filters( 'inwccrm_boxberry_customer_phone', $phone, $order ),
                'phone2'  => apply_filters( 'inwccrm_boxberry_customer_phone2', '', $order ),
                'email'   => apply_filters( 'inwccrm_boxberry_customer_email', $order->get_billing_email(), $order ),
                'name'    => apply_filters( 'inwccrm_boxberry_customer_name', $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(), $order ),
                'address' => apply_filters( 'inwccrm_boxberry_customer_address', 
                ( ! empty( $order->get_shipping_address_1() ) ) ? 
                        $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2() : 
                        $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                // $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                'inn'     => apply_filters( 'inwccrm_boxberry_customer_inn', '', $order ),
                'kpp'     => apply_filters( 'inwccrm_boxberry_customer_kpp', '', $order ),
                'r_s'     => apply_filters( 'inwccrm_boxberry_customer_r_s', '', $order ),
                'bank'    => apply_filters( 'inwccrm_boxberry_customer_bank', '', $order ),
                'kor_s'   => apply_filters( 'inwccrm_boxberry_customer_kor_s', '', $order ),
                'bik'     => apply_filters( 'inwccrm_boxberry_customer_bik', '', $order ),              
            ),

            //  Массив товарных вложений
            'items' => $items,

            // Примечание к заказу.
            'notice' => apply_filters( 'inwccrm_boxberry_notice', $order->get_customer_note(), $order ),

            'weights' => array(
                'weight'   => apply_filters( 'inwccrm_boxberry_weights_weight', $weghtTotal, $order ), //В параметр weight нужно передавать вес в граммах, т.е. целое число,
                'x'        => apply_filters( 'inwccrm_boxberry_weights_x', 1, $order ),
                'y'        => apply_filters( 'inwccrm_boxberry_weights_y', 1, $order ),
                'z'        => apply_filters( 'inwccrm_boxberry_weights_z', 1, $order ),
                'barcode'  => apply_filters( 'inwccrm_boxberry_weights_barcode', '', $order ),
            ),
        );
    }


    /**
     * Отправляет данные на сервер
     * @param mixed $orders Массив заказов
     */
    public function send( $orders )
    {        
        Plugin::get()->log( __( 'BoxBerry log: send orders', IN_WC_CRM ) . ': ' . self::URL, self::LOGFILE );

        // Проверим данные для входа
        if ( empty( $this->token ) )
        {
            throw new NoСredentialsException( __( 'Не указан API токен', IN_WC_CRM ) );
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
                Plugin::get()->log( __( 'Способ доставки', IN_WC_CRM ) . 
                    ': ' . $order->get_shipping_method(), self::LOGFILE );
                    
                Plugin::get()->log( __( 'Адрес доставки', IN_WC_CRM ) . 
                    ': ' . $order->get_shipping_address_1(), self::LOGFILE );

                Plugin::get()->log( __( 'Заказ', IN_WC_CRM ) . 
                    ': ' . var_export($order, true), self::LOGFILE );

                // Данные заказа для передачи
                $data = $this->getOrderData( $order );
                Plugin::get()->log( __( 'Запрос', IN_WC_CRM ), self::LOGFILE );
                Plugin::get()->log( $data, self::LOGFILE ); 

                // Передача заказа
                $args = array(
                    'body' => array( 
                        'token' => $this->token, 
                        'method' => 'ParselCreate',
                        'sdata' => json_encode($data)
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