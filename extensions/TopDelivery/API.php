<?php
/**
 * API B2CPL
 */
namespace IN_WC_CRM\Extensions\TopDelivery;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;
use \SoapClient as SoapClient;
use \SoapFault as SoapFault;

require 'Exceptions.php';

class API
{
    /**
     * WSDL
     */
    const WSDL = 'https://is-test.topdelivery.ru/api/soap/w/2.1/?WSDL';


    /**
     * Лог файл
     */
    const LOGFILE = 'topdelivery-sending.log';

    /**
     * Параметры удаленного сервера, подключения и других параметров
     */
    private $login;             // Login
    private $password;          // Password
    private $inn;               // ИНН поставщика
    private $jurName;           // Юридическое лицо
    private $jurAddress;        // Юридический адрес
    private $commercialName;    // Коммерческое наименование
    private $phone;             // Номер телефона

    /**
     * Конструктор
     * @param string    $login            Логин
     * @param string    $password         Пароль
     * @param string    $inn              ИНН поставщика
     * @param string    $jurName          Юридическое лицо
     * @param string    $jurAddress       Юридический адрес
     * @param string    $commercialName   Коммерческое наименование
     * @param string    $phone            Номер телефона
     */
    public function __construct( $login, $password, $inn, $jurName, $jurAddress, $commercialName, $phone )
    {
        $this->login = $login;
        $this->password = $password;
        $this->inn = $inn;
        $this->jurName = $jurName;
        $this->jurAddress = $jurAddress;
        $this->commercialName = $commercialName;
        $this->phone = $phone;
    }

    /**
     * Возвращает структуру заказа для отправки
     * https://docs.topdelivery.ru/pages/soapapi/w/?v=2.1#method-addOrders
     * @param WC_Order  $order  Заказ WooCommerce
     * @return mixed
     */
    private function getOrder( $order )
    {
        // Элементы заказа
        $items = array();
        $summTotal = 0;
        $weghtTotal = 0;    
        foreach( $order->get_items() as $orderItemId => $orderItem )
        {
            $product = $orderItem->get_product();
            $itemQuantity = $orderItem->get_quantity();
            $itemPrice = $orderItem->get_total();
            $itemTotalPrice = $itemQuantity * $itemPrice;
            $summTotal += $itemTotalPrice;
            $itemWeghtTotal = $itemQuantity * $product->get_weight() * 1000;
            $weghtTotal += $itemWeghtTotal;
            $items[] = array(
                'itemId'        => apply_filters( 'inwccrm_topdelivery_orderitem_id', $orderItemId, $order, $orderItem ),
                'name'          => apply_filters( 'inwccrm_topdelivery_orderitem_name', $product->get_name(), $order, $orderItem ),
                'article'       => apply_filters( 'inwccrm_topdelivery_orderitem_article', $product->get_sku(), $order, $orderItem ),
                'count'         => apply_filters( 'inwccrm_topdelivery_orderitem_count', $itemQuantity, $order, $orderItem ),
                'declaredPrice' => apply_filters( 'inwccrm_topdelivery_orderitem_declaredprice', $itemTotalPrice, $order, $orderItem ),
                'clientPrice'   => apply_filters( 'inwccrm_topdelivery_orderitem_clientprice', $itemTotalPrice, $order, $orderItem ),
                'weight'        => apply_filters( 'inwccrm_topdelivery_orderitem_weight', $itemWeghtTotal, $order, $orderItem ),
                'push'          => apply_filters( 'inwccrm_topdelivery_orderitem_push', 1, $order, $orderItem ),
                'status' => array(
                    'id'            => apply_filters( 'inwccrm_topdelivery_orderitem_status_id', NULL, $order, $orderItem ),
                    'name'          => apply_filters( 'inwccrm_topdelivery_orderitem_status_name', NULL, $order, $orderItem ),
                    'deliveryCount' => apply_filters( 'inwccrm_topdelivery_orderitem_status_deliveryCount', NULL, $order, $orderItem ),
                    'vat'           => apply_filters( 'inwccrm_topdelivery_orderitem_status_vat', NULL, $order, $orderItem ),
                    'trueMark'      => apply_filters( 'inwccrm_topdelivery_orderitem_status_deliveryCount', NULL, $order, $orderItem ),
                )
            );
        }        

        // Формирование и возврат заказа
        return array(
            'serviceType'             => apply_filters( 'inwccrm_topdelivery_servicetype', 'DELIVERY', $order ),
            'deliveryType'            => apply_filters( 'inwccrm_topdelivery_deliverytype', 'COURIER', $order ),
            'orderSubtype'            => apply_filters( 'inwccrm_topdelivery_ordersubtype', 'SIMPLE', $order ),
            'deliveryCostPayAnyway'   => apply_filters( 'inwccrm_topdelivery_deliverycostpayanyway', 0, $order ),
            'webshopNumber'           => apply_filters( 'inwccrm_topdelivery_webshopnumber', $order->get_order_number(), $order ),
            'webshopBarcode'          => apply_filters( 'inwccrm_topdelivery_webshopbarcode', NULL, $order ),
            'orderUrl'                => apply_filters( 'inwccrm_topdelivery_orderurl', $order->get_view_order_url(), $order ),
            'desiredDateDelivery'      => apply_filters( 'inwccrm_topdelivery_desireddatedelivery', NULL, $order ),
            'deliveryAddress' => array(
                'type'    => apply_filters( 'inwccrm_topdelivery_deliveryaddress_type', 'zip', $order ),
                'region'  => apply_filters( 'inwccrm_topdelivery_deliveryaddress_region', ( ! empty( $order->get_shipping_state() ) ) ? $order->get_shipping_state() : $order->get_billing_state(), $order ),
                'city'    => apply_filters( 'inwccrm_topdelivery_deliveryaddress_city', ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city(), $order ),
                'zipcode' => apply_filters( 'inwccrm_topdelivery_deliveryaddress_zipcode', ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode(), $order ),
                'inCityAddress' => array(
                    'zipcode'   => apply_filters( 'inwccrm_topdelivery_deliveryaddress_incityaddress_zipcode', ( ! empty( $order->get_shipping_postcode() ) ) ? $order->get_shipping_postcode() : $order->get_billing_postcode(), $order ),
                    'address'   => apply_filters( 'inwccrm_topdelivery_deliveryaddress_incityaddress_address', 
                        ( ! empty( $order->get_shipping_address_1() ) ) ? 
                        $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2() : 
                        $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(), $order ),
                    'pickupAddress' => apply_filters( 'inwccrm_topdelivery_deliveryaddress_incityaddress_pickupaddress', NULL, order),
                ),
            ),
            'clientInfo' => array(
                'fio'    => apply_filters( 'inwccrm_topdelivery_clientinfo_fio', 
                    ( ! empty( $order->get_shipping_last_name() ) && ! empty( $order->get_shipping_first_name() ) ) ?
                    $order->get_shipping_last_name() . ' '  . $order->get_shipping_first_name() :
                    $order->get_billing_last_name() . ' '  . $order->get_billing_first_name(),
                    $order ),
                'phone'   => apply_filters( 'inwccrm_topdelivery_clientinfo_phone', preg_replace('/[\s\-\(\)\.]/', '', $order->get_billing_phone() ), $order ),
                'email'   => apply_filters( 'inwccrm_topdelivery_clientinfo_email', $order->get_billing_email(), $order ),
                'comment' => apply_filters( 'inwccrm_topdelivery_clientinfo_comment', $order->get_customer_note(), $order ),
            ),
            'paymentByCard' => 0, // Не использутеся, всегда = '0'
            'clientCosts' => array(
                'clientDeliveryCost' => apply_filters( 'inwccrm_topdelivery_clientcosts_clientdeliverycost', $order->get_shipping_total(), $order ),
                'recalcDelivery'     => 0,  // Не используется, всегда 0
                'discount'  => array(        // Не используется
                    'type'  => 'SUM',        // Не использутеся, всегда = 'SUM' 
                    'value' => 0            // Не использутеся, всегда = '0'
                ),
            ),
            'services' => array(
                'notOpen'   => apply_filters( 'inwccrm_topdelivery_services_notopen', 0, $order ),
                'marking'   => apply_filters( 'inwccrm_topdelivery_services_marking', 0, $order ),
                'smsNotify' => apply_filters( 'inwccrm_topdelivery_services_smsnotify', 1, $order ),
                'forChoise' => apply_filters( 'inwccrm_topdelivery_services_forchoise', 1, $order ),
                'places'    => apply_filters( 'inwccrm_topdelivery_services_places', 1, $order ),
                'pack' => array(
                    'need'  => apply_filters( 'inwccrm_topdelivery_services_pack_need', 0, $order ),
                    'type'  => apply_filters( 'inwccrm_topdelivery_services_pack_type', '', $order ),
                ),
            ),
            'deliveryWeight' => array(
                'weight' => apply_filters( 'inwccrm_topdelivery_deliveryweight_weight', $weghtTotal, $order ),
                'volume' => array(
                    'length' => apply_filters( 'inwccrm_topdelivery_deliveryweight_volume_length', 0, $order ),
                    'width'  => apply_filters( 'inwccrm_topdelivery_deliveryweight_volume_width', 0, $order ),
                    'height' => apply_filters( 'inwccrm_topdelivery_deliveryweight_volume_height', 0, $order ),
                ),
            ),    
            'intakeWeight' => array(
                'weight' => apply_filters( 'inwccrm_topdelivery_intakeweight_weight', $weghtTotal, $order ),
                'volume' => array(
                    'length' => apply_filters( 'inwccrm_topdelivery_intakeweight_volume_length', 0, $order ),
                    'width'  => apply_filters( 'inwccrm_topdelivery_intakeweight_volume_width', 0, $order ),
                    'height' => apply_filters( 'inwccrm_topdelivery_intakeweight_volume_height', 0, $order ),
                ),
            ),
            'items' => $items,
            'supplierSummary' => array(
                'INN'            => apply_filters( 'inwccrm_topdelivery_suppliersummary_inn', $this->inn, $order ),
                'jurName'        => apply_filters( 'inwccrm_topdelivery_suppliersummary_jurname', $this->jurName, $order ),
                'jurAddress'     => apply_filters( 'inwccrm_topdelivery_suppliersummary_juraddress', $this->jurAddress, $order ),
                'commercialName' => apply_filters( 'inwccrm_topdelivery_suppliersummary_commercialname', $this->commercialName, $order ),
                'phone'          => apply_filters( 'inwccrm_topdelivery_suppliersummary_phone', $this->phone, $order ),
            ),
        );
    }

    /**
     * Отправляет данные на сервер
     * @param mixed $orders Массив заказов
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
            'auth'  => array(
                'login'     => $this->login,
                'password'  => $this->password
            ), 
            'addedOrders' => $orderData
        );

        Plugin::get()->log( __( 'Инициализация SoapClient', IN_WC_CRM ) . ' ' . self::WSDL, self::LOGFILE );

        try
        {
            // Создаем SOAP клиента
            $soap = new SoapClient( self::WSDL, array(
                'trace'         => WP_DEBUG, 
                'exceptions'    => WP_DEBUG
            ) );

            Plugin::get()->log( __( 'Запрос', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $data, self::LOGFILE );         

            // Отправка данных
            $soap->addOrders( $data );

            Plugin::get()->log( __( 'Ответ сервера', IN_WC_CRM ), self::LOGFILE );
            Plugin::get()->log( $response, self::LOGFILE );   
        }
        catch (SoapFault $e) 
        {
            // Возникли ошибки
            Plugin::get()->log( __( 'Ошибки SOAP', IN_WC_CRM ) . ' ' . var_export( $e, true ), self::LOGFILE );
            throw $e; 
        }

        // Расшифровавываем ответ
        if ( ! $response )
        {        
            throw new EmptyResponseException( __( 'Пустой ответ сервера!', IN_WC_CRM ) );
        }
        
        return $response;
    }
}