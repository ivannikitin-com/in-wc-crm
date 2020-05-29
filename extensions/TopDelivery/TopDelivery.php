<?php
/**
 * Расширение взаимодействует с TopDelivery
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order as WC_Order;
use \Exception as Exception;
use IN_WC_CRM\Extensions\TopDelivery\API as API;
use \IN_WC_CRM\Extensions\TopDelivery\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\TopDelivery\NoOrdersException as NoOrdersException;

require 'API.php';

class TopDelivery extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) return;
		
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ), 32 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'wp_ajax_topdelivery_send_orders', array( $this, 'sendOrders' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'TopDelivery';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка заказов в службу TopDelivery', IN_WC_CRM );
    }

    /**
     * Возвращает true если этому расширению требуются настройки
     * @return bool
     */
    public function hasSettings()
    {
        return true;
    }
    
    /**
     * Показывает секцию настроек
     */
    public function showSettings()
    {
        @include( Plugin::get()->path . 'extensions/TopDelivery/settings.php' );
    }
    

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['TopDelivery-api-login'] = isset( $_POST['TopDelivery-api-login'] ) ? trim(sanitize_text_field( $_POST['TopDelivery-api-login'] ) )  : '';
        $this->settings['TopDelivery-api-password'] = isset( $_POST['TopDelivery-api-password'] ) ? sanitize_text_field( $_POST['TopDelivery-api-password'] ) : '';
        $this->settings['TopDelivery-http-login'] = isset( $_POST['TopDelivery-http-login'] ) ? trim(sanitize_text_field( $_POST['TopDelivery-http-login'] ) )  : '';
        $this->settings['TopDelivery-http-password'] = isset( $_POST['TopDelivery-http-password'] ) ? sanitize_text_field( $_POST['TopDelivery-http-password'] ) : '';
        $this->settings['TopDelivery-inn'] = isset( $_POST['TopDelivery-inn'] ) ? sanitize_text_field( $_POST['TopDelivery-inn'] ) : '';
        $this->settings['TopDelivery-jurName'] = isset( $_POST['TopDelivery-jurName'] ) ? sanitize_text_field( $_POST['TopDelivery-jurName'] ) : '';
        $this->settings['TopDelivery-jurAddress'] = isset( $_POST['TopDelivery-jurAddress'] ) ? sanitize_text_field( $_POST['TopDelivery-jurAddress'] ) : '';
        $this->settings['TopDelivery-commercialName'] = isset( $_POST['TopDelivery-commercialName'] ) ? sanitize_text_field( $_POST['TopDelivery-commercialName'] ) : '';
        $this->settings['TopDelivery-phone'] = isset( $_POST['TopDelivery-phone'] ) ? sanitize_text_field( $_POST['TopDelivery-phone'] ) : '';

        return parent::saveSettings();
    }    

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'controls.php';
    }

    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-TopDelivery';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/TopDelivery/frontend.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_TopDelivery';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),		
        );
        wp_localize_script( $scriptID, $objectName, $data );        
    }
    
    /**
     * Максимальное число заказов из БД 
     */
    const ORDER_LIMIT = 250;

    /**
     * AJAX запрос на отправку данных
     */
    public function sendOrders()
    {
        try
        {
            // Подключение
            $api = new API(
                $this->getParam( 'TopDelivery-api-login', '' ),      // API Login
                $this->getParam( 'TopDelivery-api-password', '' ),   // API Password
                $this->getParam( 'TopDelivery-http-login', '' ),     // HTTP Basic Auth Login
                $this->getParam( 'TopDelivery-http-password', '' ),  // HTTP Basic Auth Password                
                $this->getParam( 'TopDelivery-inn', '' ),            // ИНН поставщика
                $this->getParam( 'TopDelivery-jurName', '' ),        // Юридическое лицо
                $this->getParam( 'TopDelivery-jurAddress', '' ),     // Юридический адрес
                $this->getParam( 'TopDelivery-commercialName', '' ), // Коммерческое наименование
                $this->getParam( 'TopDelivery-phone', '' )           // Номер телефона
            );

            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) );            

            // Запрос выбранных заказов
            $args = array(
                'limit'     => apply_filters( 'inwccrm_topdelivery_datatable_order_limit', self::ORDER_LIMIT ),
                'return'    => 'objects',
                'post__in'  => explode(',', $idsString )      
            );
            $orders = wc_get_orders( $args );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            // Передача заказов
            $result = $api->send( $orders );

            $responseStr = $result->requestResult->message . PHP_EOL;

            // Ответ записываем в админку WC если он есть
            if ( isset( $result->addOrdersResult ) )
            {
                // Если ответ не массив, делаем его массивом
                $resultOrders = ( is_array( $result->addOrdersResult ) ) ? $result->addOrdersResult : array( $result->addOrdersResult ); 

                // Запишем заказы в WooCommerce
				foreach( $resultOrders as $order )
				{
                    $currentOrder = new WC_Order( $order->orderIdentity->webshopNumber );
                    $orderMessage = ( $order->status == 0 ) ?
                        $order->message . ', ' . __( 'TopDelivery ID', IN_WC_CRM ) . ': ' . $order->orderIdentity->orderId :
                        $order->message;

                    // Добавим мета-поля
                    $currentOrder->add_order_note( $orderMessage );

                    if ( $order->status == 0 )
                    {
                        $currentOrder->add_meta_data( __( 'TopDelivery ID', IN_WC_CRM ),  $order->orderIdentity->orderId );
                        // Установим новый статус
                        $currentStatus = $currentOrder->get_status();
                        $newStatus = apply_filters( 'inwccrm_topdelivery_set_order_status', $currentStatus, $currentOrder );
                        if ( $currentStatus != $newStatus )
                        {
                            $orderNote = apply_filters( 'inwccrm_topdelivery_set_order_status_note', 
                                __( 'Статус заказа изменен после успешной отправки TopDelivery', IN_WC_CRM ),
                                $newStatus, $currentOrder );
                            $currentOrder->update_status( $newStatus, $orderNote );
                        }                        
                    }

                    // Результат в строку результата
                    $responseStr .= __( 'Заказ', IN_WC_CRM ) . ' ' . $order->orderIdentity->webshopNumber . ': ' . $order->message . PHP_EOL;
                }
            }

            echo $responseStr;
            wp_die();

        }
        catch (Exception $e) 
        {
            // Возникли ошибки
            esc_html_e( 'Ошибка!', IN_WC_CRM );
            echo ' ', get_class( $e ), ': ', $e->getMessage();
            wp_die();  
        }
    }    
}