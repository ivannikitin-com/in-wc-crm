<?php
/**
 * Расширение взаимодействует с B2CPL
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order as WC_Order;
use \Exception as Exception;
use IN_WC_CRM\Extensions\B2CPL\API as API;
use \IN_WC_CRM\Extensions\B2CPL\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\B2CPL\NoOrdersException as NoOrdersException;

require 'API.php';

class B2CPL extends Base
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
        add_action( 'wp_ajax_b2cpl_send_orders', array( $this, 'sendOrders' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'B2CPL';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка заказов в службу B2CPL', IN_WC_CRM );
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
        @include( Plugin::get()->path . 'extensions/B2CPL/settings.php' );
    }
    

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['B2CPL-api-endpoint'] = isset( $_POST['B2CPL-api-endpoint'] ) ? trim(sanitize_text_field( $_POST['B2CPL-api-endpoint'] ) ) : '';
        $this->settings['B2CPL-api-login'] = isset( $_POST['B2CPL-api-login'] ) ? trim(sanitize_text_field( $_POST['B2CPL-api-login'] ) )  : '';
        $this->settings['B2CPL-api-password'] = isset( $_POST['B2CPL-api-password'] ) ? sanitize_text_field( $_POST['B2CPL-api-password'] ) : '';

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
        $scriptID = IN_WC_CRM . '-B2CPL';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/B2CPL/frontend.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_B2CPL';
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
                $this->getParam( 'B2CPL-api-endpoint', '' ),         // URL
                $this->getParam( 'B2CPL-api-login', '' ),            // Login
                $this->getParam( 'B2CPL-api-password', '' )          // Password
            );

            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) );            

            // Запрос выбранных заказов
            $args = array(
                'limit'     => apply_filters( 'inwccrm_b2cpl_datatable_order_limit', self::ORDER_LIMIT ),
                'return'    => 'objects',
                'post__in'  => explode(',', $idsString )      
            );
            $orders = wc_get_orders( $args );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            // Передача заказов
            $result = $api->send( $orders );

            // Ответ в админку
            if ( $result->success )
            {
                $responseStr = __( 'Заказы приняты:', IN_WC_CRM );

                // Запишем заказы в WooCommerce
				foreach( $result->orders as $order )
				{
					$currentOrder = new WC_Order( $order->code );
                    // Добавим мета-поля
                    $currentOrder->add_order_note( 
						__( 'B2CPL', IN_WC_CRM ) . ': ' . 
						__( 'Отправление создано', IN_WC_CRM ) . ': ' . 
						$order->code_b2cpl
					);
                    $currentOrder->add_meta_data( __( 'B2CPL код', IN_WC_CRM ),  $order->code_b2cpl );
                    
                    // Установим новый статус
                    $currentStatus = $currentOrder->get_status();
                    $newStatus = apply_filters( 'inwccrm_b2cpl_set_order_status', $currentStatus, $currentOrder );
                    if ( $currentStatus != $newStatus )
                    {
                        $orderNote = apply_filters( 'inwccrm_b2cpl_set_order_status_note', 
                            __( 'Статус заказа изменен после успешной отправки B2CPL', IN_WC_CRM ),
                            $newStatus, $currentOrder );
                        $currentOrder->update_status( $newStatus, $orderNote );
                    }

                    // Результат в строку результата
                    $responseStr .= ' ' . $order->code;
                }
            }
            else
            {
                $responseStr = $result->message;
            }

            echo $responseStr;
            wp_die();

        }
        catch (Exception $e) 
        {
            // Возникли ошибки
            esc_html_e( 'Ошибка!', IN_WC_CRM );
            echo ' ', $e->getMessage();
            wp_die();  
        }
    }    
}