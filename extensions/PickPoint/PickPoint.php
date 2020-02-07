<?php
/**
 * Расширение взаимодействует с PickPoint
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use IN_WC_CRM\Extensions\PickPoint\API as API;
use \IN_WC_CRM\Extensions\PickPoint\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\PickPoint\NoOrdersException as NoOrdersException;
use \WC_Order as WC_Order;
use \Exception as Exception;

require 'API.php';

class PickPoint extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) 
            return;
		
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'wp_ajax_pickpoint_send_orders', array( $this, 'sendOrders' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'PickPoint';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка заказов в службу PickPoint', IN_WC_CRM );
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
        @include( Plugin::get()->path . 'extensions/PickPoint/views/settings.php' );
    }
    

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['pickpoint-api-endpoint'] = isset( $_POST['pickpoint-api-endpoint'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-endpoint'] ) ) : '';
        $this->settings['pickpoint-api-login'] = isset( $_POST['pickpoint-api-login'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-login'] ) )  : '';
        $this->settings['pickpoint-api-password'] = isset( $_POST['pickpoint-api-password'] ) ? sanitize_text_field( $_POST['pickpoint-api-password'] ) : '';
        $this->settings['pickpoint-api-ikn'] = isset( $_POST['pickpoint-api-ikn'] ) ? trim(sanitize_text_field( $_POST['pickpoint-api-ikn'] ) ) : '';
        $this->settings['pickpoint-order-status'] = isset( $_POST['pickpoint-order-status'] ) ? trim(sanitize_text_field( $_POST['pickpoint-order-status'] ) ) : 'wc-processing';
        $this->settings['pickpoint-shopOrganization'] = isset( $_POST['pickpoint-shopOrganization'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopOrganization'] ) ) : '';
        $this->settings['pickpoint-shopPhone'] = isset( $_POST['pickpoint-shopPhone'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopPhone'] ) ) : '';
        $this->settings['pickpoint-shopManagerName'] = isset( $_POST['pickpoint-shopManagerName'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopManagerName'] ) ) : '';
        $this->settings['pickpoint-shopComment'] = isset( $_POST['pickpoint-shopComment'] ) ? trim(sanitize_text_field( $_POST['pickpoint-shopComment'] ) ) : '';
        
        return parent::saveSettings();
    }    

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'views/controls.php';
    }

    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-pickpoint';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/PickPoint/js/pickpoint.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_Pickpoint';
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
                $this->getParam( 'pickpoint-api-endpoint', '' ),         // URL
                $this->getParam( 'pickpoint-api-login', '' ),            // Login
                $this->getParam( 'pickpoint-api-password', '' ),         // Password
                $this->getParam( 'pickpoint-api-ikn', '' ),              // IKN
                $this->getParam( 'pickpoint-shopManagerName', '' ),      // Менеджер
                $this->getParam( 'pickpoint-shopOrganization', '' ),     // Менеджер
                $this->getParam( 'pickpoint-shopPhone', '' ),            // Телефон
                $this->getParam( 'pickpoint-shopComment', '' )           // Комментарий
            );

            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) );
            

            // Запрос выбранных заказов
            $args = array(
                'limit'     => apply_filters( 'inwccrm_pickpoint_datatable_order_limit', self::ORDER_LIMIT ),
                'return'    => 'objects',
                'post__in'  => explode(',', $idsString )      
            );
            $orders = wc_get_orders( $args );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            // Передача заказов
            $result = $api->send( $orders );

            $resultStr = '';
            if ( count( $result['created'] > 0 ) ) 
            { 
                $resultStr .= __( 'Переданные заказы:', IN_WC_CRM );
				foreach( $result['created'] as $sending )
				{
					$currentOrder = new WC_Order( $sending->SenderCode );
                    // Добавим мета-поля
                    $currentOrder->add_order_note( 
						__( 'Pikpoint', IN_WC_CRM ) . ': ' . 
						__( 'Отправление создано', IN_WC_CRM ) . ': ' . 
						$sending->InvoiceNumber
					);
                    $currentOrder->add_meta_data( __( 'Pikpoint InvoiceNumber', IN_WC_CRM ),  $sending->InvoiceNumber );
                    
                    // Установим новый статус
                    $currentStatus = $currentOrder->get_status();
                    $newStatus = apply_filters( 'inwccrm_pickpoint_set_order_status', $currentStatus, $currentOrder );
                    if ( $currentStatus != $newStatus )
                    {
                        $orderNote = apply_filters( 'inwccrm_pickpoint_set_order_status_note', 
                            __( 'Статус заказа изменен после успешной отправки Pickpoint', IN_WC_CRM ),
                            $newStatus, $currentOrder );
                        $currentOrder->update_status( $newStatus, $orderNote );
                    }

                    // Результат в строку результата
                    $resultStr .= $sending->SenderCode . ',';
                }
                $resultStr .= PHP_EOL;             
            }

            if ( count( $result['rejected'] > 0 ) ) 
            { 
                $resultStr .= __( 'Отклоненные заказы:', IN_WC_CRM );
				foreach( $result['rejected'] as $sending )
				{
					$currentOrder = new WC_Order( $sending->SenderCode );
					$currentOrder->add_order_note( 
						__( 'Pikpoint', IN_WC_CRM ) . ': ' . 
						__( 'Ошибка', IN_WC_CRM ) . ': ' . 
						$sending->ErrorMessage
					);					
					$resultStr .= $sending->ErrorMessage .  ' ' . $sending->SenderCode . PHP_EOL;
				}
                $resultStr .= PHP_EOL;             
            }

            echo $resultStr;
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