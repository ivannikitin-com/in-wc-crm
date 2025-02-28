<?php
/**
 * API B2CPL
 */
namespace IN_WC_CRM\Extensions\B2CPL;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;

require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'B2CPL-sending.log';

    /**
     * Параметры удаленного сервера и подключения
     */
    private $url;
    private $login;
    private $password;

    /**
     * Конструктор
     * @param string    $url                    URL удаленного сервера
     * @param string    $login                  Логин
     * @param string    $password               Пароль
     */
    public function __construct( $url, $login, $password )
    {
        $this->url = $url;
        $this->login = $login;
        $this->password = $password;

        // Убираем из URL финальный слеш
        if ( substr( $this->url, -1 ) == '/' )
            $this->url = substr( $this->url, 0, strlen( $this->url ) -1 );
    }

    /**
     * Возвращает структуру заказа для отправки
     * @param WC_Order  $order  Заказ WooCommerce
     * @return mixed
     */
    private function getOrder( $order )
    {
        // Массив элементов заказа
        $items = array();
        //$summTotal = 0;
        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            $product = $orderItem->get_product();
            $itemQuantity = $orderItem->get_quantity();
            $itemPrice = ( $itemQuantity != 0 ) ? ($orderItem->get_total()+  $orderItem->get_total_tax()) / $itemQuantity : 0;
            //$summTotal += $itemQuantity * $itemPrice;
            $items[] = array(
                'prodcode'=> apply_filters( 'inwccrm_b2cpl_item_prodcode', $product->get_sku(), $order, $product ),     // артикул
                'prodname'=> apply_filters( 'inwccrm_b2cpl_item_prodname', $product->get_name(), $order, $product ),    // наименование, обязательное поле
                'quantity'=> apply_filters( 'inwccrm_b2cpl_item_quantity', $itemQuantity, $order, $product ),  // количество, обязательное поле
                'weight'=> apply_filters( 'inwccrm_b2cpl_item_weight', $product->get_weight() * 1000, $order, $product ),  // вес единицы товара в граммах, обязательное поле
                'price'=> apply_filters( 'inwccrm_b2cpl_item_price', $itemPrice, $order, $product ),   // стоимость единицы товара, обязательное поле
                'price_pay'=> apply_filters( 'inwccrm_b2cpl_item_price_pay', $itemPrice, $order, $product ), // стоимость единицы товара к оплате, обязательное поле
                //'price_assess'=> apply_filters( 'inwccrm_b2cpl_item_price_assess', $itemPrice, $order, $product ), // страховая стоимость единицы товара, необязательное поле
                'vat'=> apply_filters( 'inwccrm_b2cpl_item_vat', 0, $order, $product ) // НДС товара, необязательное поле                
            );
        }

        // Массив посылок. Сейчас один заказ - одна посылка
        $parcels = array( array(
            'number'=> apply_filters( 'inwccrm_b2cpl_parcel_number', 1, $order ),
            'code'=> apply_filters( 'inwccrm_b2cpl_parcel_code', $order->get_order_number() . '/1' , $order ),
            'weight'=> apply_filters( 'inwccrm_b2cpl_parcel_weight', 1000, $order ),
            'dim_x'=> apply_filters( 'inwccrm_b2cpl_parcel_dim_x', 0, $order ),
            'dim_y'=> apply_filters( 'inwccrm_b2cpl_parcel_dim_y', 0, $order ),
            'dim_z'=> apply_filters( 'inwccrm_b2cpl_parcel_dim_z', 0, $order ),
            'items'=> $items
        ) );  

       return array(
            'invoice_number'=> apply_filters( 'inwccrm_b2cpl_invoice_number', '', $order ),         // Номер накладной, необязательное поле
            'sender'=> apply_filters( 'inwccrm_b2cpl_sender', get_option( 'blogname' ), $order ),   // Отправитель -- Название магазина
            'code'=> apply_filters( 'inwccrm_b2cpl_code', $order->get_order_number(), $order ),     // уникальный код заказа в системе заказчика, обязательное поле
            'code_b2cpl'=> apply_filters( 'inwccrm_b2cpl_code_b2cpl', '', $order ),     // код B2CPL (если есть)
            'code_client'=> apply_filters( 'inwccrm_b2cpl_code_client', $order->get_billing_email(), $order),   // код клиента в системе заказчика
            'fio'=> apply_filters( 'inwccrm_b2cpl_fio',  // ФИО, обязательное поле
                ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                    $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                    $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(), 
                $order ),
            'date_order'=> apply_filters( 'inwccrm_b2cpl_date_order', $order->get_date_created()->__toString(), $order ), // '2016-06-03T00=>00=>00', дата заказа, необязательное поле
            'date_supply'=> apply_filters( 'inwccrm_b2cpl_date_supply', '', $order ), // дата поставки заказа на СЦ, необязательное поле
            'zip'=> apply_filters( 'inwccrm_b2cpl_zip',     // индекс, обязательное поле
                ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode(), 
                $order ),
            'region'=> apply_filters( 'inwccrm_b2cpl_region',     // регион, необязательное поле
                ( ! empty( $order->get_shipping_state() ) ) ? $order->get_shipping_state() : $order->get_billing_state(), 
                $order ),
            'city'=> apply_filters( 'inwccrm_b2cpl_city',         // город
                ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city(), 
                $order ),
            'address'=> apply_filters( 'inwccrm_b2cpl_address',   // адрес
                ( ! empty( $order->get_shipping_address_1() ) ) ? 
                    $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2() : 
                    $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), 
                $order ),
            'phone1'=> apply_filters( 'inwccrm_b2cpl_phone1', preg_replace('/[\s\-\(\)\.]/', '', $order->get_billing_phone() ), $order ),    // телефон, обязательное поле
            'phone2'=> apply_filters( 'inwccrm_b2cpl_phone2', '', $order ),     // дополнительный телефон, необязательное поле
            'email'=> apply_filters( 'inwccrm_b2cpl_email', $order->get_billing_email(), $order ),  // e-mail адрес, необязательное поле
            'price_assess'=> apply_filters( 'inwccrm_b2cpl_price_assess', $order->get_total(), $order ), // оценочная стоимость заказа
            'price_delivery'=> apply_filters( 'inwccrm_b2cpl_price_delivery', $order->get_shipping_total(), $order ), // полная стоимость доставки
            'price_delivery_pay'=> apply_filters( 'inwccrm_b2cpl_price_delivery_pay', $order->get_total(), $order ), // стоимость доставки к оплате
            'delivery_type'=> apply_filters( 'inwccrm_b2cpl_delivery_type', __( 'Курьером', IN_WC_CRM ), $order ),  // тип доставки (code из функции TARIF), обязательное поле
            'delivery_term'=> apply_filters( 'inwccrm_b2cpl_delivery_term', __( 'Можно вскрывать', IN_WC_CRM ), $order ),   // условия доставки, необязательное поле
            'delivery_date'=> apply_filters( 'inwccrm_b2cpl_delivery_date', '', $order ),   // дата доставки, необязательное поле
            'delivery_time'=> apply_filters( 'inwccrm_b2cpl_delivery_time', '', $order ),   // интервал доставки, необязательное поле
            'flag_open'=> apply_filters( 'inwccrm_b2cpl_flag_open', 2, $order ),  // возможность вскрытия (по умолчанию 2)
            'flag_fitting'=> apply_filters( 'inwccrm_b2cpl_flag_fitting', false, $order ), // возможность примерки
            'flag_call'=> apply_filters( 'inwccrm_b2cpl_flag_call', false, $order ), // требуется подтверждение заказа
            'flag_delivery'=> apply_filters( 'inwccrm_b2cpl_flag_delivery', true, $order ), // требуется доставка B2CPL
            'flag_return'=> apply_filters( 'inwccrm_b2cpl_flag_return', true, $order ), // возможность возврата
            'flag_packing'=> apply_filters( 'inwccrm_b2cpl_flag_packing', false, $order ), // требует упаковки
            'flag_partial_reject'=> apply_filters( 'inwccrm_b2cpl_flag_partial_reject', false, $order ), // возможность частичного отказа
            'comment'=> apply_filters( 'inwccrm_b2cpl_comment', $order->get_customer_note(), $order ),
            'parcels'=> $parcels                      
        );
    }

    /**
     * Отправляет данные на сервер
     * @param midex $orders Массив заказов
     */
    public function send( $orders )
    {
        // Проверим данные для входа
        if ( empty( $this->login ) || empty( $this->password ) )
        {
            throw new NoСredentialsException( __( 'Не указаны данные для входа', IN_WC_CRM ) );
        }

        // Соберем массив заказов
        if ( empty( $orders ) )
        {
            throw new NoOrdersException( __( 'Не переданы заказы для отправки', IN_WC_CRM ) );
        }
            
        $orderData = array();
        foreach ( $orders as $order )
        {
            $orderData[] = $this->getOrder( $order );
        } 

        // Структура отправки
        $data = array(
            'client'=> $this->login,
            'key'=> $this->password,
            'func'=> 'order',
            'region'=> apply_filters( 'inwccrm_b2cpl_region', 77 ),
            'flag_update'=> apply_filters( 'inwccrm_b2cpl_flag_update', 0 ),
            'partner'=> apply_filters( 'inwccrm_b2cpl_partner', '' ),
            'orders'=>$orderData
        );

        // Отправка
        $sendData = json_encode( $data );
        Plugin::get()->log( __( 'Данные для отправки', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $data, self::LOGFILE );

        // Формируем запрос
        $args = array(
            'timeout'   => 60,
            'blocking'  => true,   
            'headers'   => array('Content-Type' => 'application/json'),
            'body'      => $sendData,
        );

        // Отправляем запрос
        $response = wp_remote_post( $this->url, $args );

        // проверка ошибки
        if ( is_wp_error( $response ) ) 
        {
            throw new SendException( __( 'Ошибка отправки данных: ', IN_WC_CRM ) . $response->get_error_message() );
        }
        
        // Расшифровавываем ответ
        $responseObj = json_decode( $response['body'] );
        if ( ! $responseObj )
        {        
            throw new EmptyResponseException( __( 'Пустой ответ сервера!', IN_WC_CRM ) );
        }
        
        Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ), self::LOGFILE );
        Plugin::get()->log( $responseObj, self::LOGFILE ); 
        
        return $responseObj;
    }


}