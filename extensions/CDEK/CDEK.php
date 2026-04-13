<?php
/**
 * Расширение взаимодействует с CDEK
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use IN_WC_CRM\Extensions\CDEK\API as API;
use \IN_WC_CRM\Extensions\CDEK\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\CDEK\NoOrdersException as NoOrdersException;
use \WC_Order as WC_Order;
use \Exception as Exception;

require 'API.php';

class CDEK extends Base
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
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ), 31 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
        /* add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) ); */
        add_action( 'wp_ajax_CDEK_send_orders', array( $this, 'sendOrders' ) );
/*         Plugin::get()->log( $this->paramSection, 'CDEK-sending.log' );
        Plugin::get()->log( get_option($this->paramSection), 'CDEK-sending.log' );
        Plugin::get()->log( get_option('CDEK-shipperName'), 'CDEK-sending.log' ); */
    }

    /**
     * Возвращает название расширения
     * 
     * @return string
     */
    public function getTitle()
    {
        return 'CDEK';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка заказов в службу CDEK', IN_WC_CRM );
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
        @include( Plugin::get()->path . 'extensions/CDEK/views/settings.php' );
    }
    

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['CDEK-api-endpoint'] = isset( $_POST['CDEK-api-endpoint'] ) ? trim(sanitize_text_field( $_POST['CDEK-api-endpoint'] ) ) : '';
        $this->settings['CDEK-api-id'] = isset( $_POST['CDEK-api-id'] ) ? trim(sanitize_text_field( $_POST['CDEK-api-id'] ) )  : '';
        $this->settings['CDEK-api-secretkey'] = isset( $_POST['CDEK-api-secretkey'] ) ? sanitize_text_field( $_POST['CDEK-api-secretkey'] ) : '';
        $this->settings['CDEK-api-developerkey'] = isset( $_POST['CDEK-developerkey'] ) ? sanitize_text_field( $_POST['developerkey'] ) : '';
        $this->settings['CDEK-order-status'] = isset( $_POST['CDEK-order-status'] ) ? trim(sanitize_text_field( $_POST['CDEK-order-status'] ) ) : 'wc-processing';
        $this->settings['CDEK-shopOrganization'] = isset( $_POST['CDEK-shopOrganization'] ) ? trim(sanitize_text_field( $_POST['CDEK-shopOrganization'] ) ) : '';
        $this->settings['CDEK-shopAddress'] = isset( $_POST['CDEK-shopAddress'] ) ? trim(sanitize_text_field( $_POST['CDEK-shopAddress'] ) ) : '';
        $this->settings['CDEK-shopPhone'] = isset( $_POST['CDEK-shopPhone'] ) ? trim(sanitize_text_field( $_POST['CDEK-shopPhone'] ) ) : '';
        $this->settings['CDEK-shopManagerName'] = isset( $_POST['CDEK-shopManagerName'] ) ? trim(sanitize_text_field( $_POST['CDEK-shopManagerName'] ) ) : '';
        $this->settings['CDEK-shipperName'] = isset( $_POST['CDEK-shipperName'] ) ? trim(sanitize_text_field( $_POST['CDEK-shipperName'] ) ) : '';
        $this->settings['CDEK-shipperAddress'] = isset( $_POST['CDEK-shipperAddress'] ) ? trim(sanitize_text_field( $_POST['CDEK-shipperAddress'] ) ) : '';
        $this->settings['CDEK-shipperEmail'] = isset( $_POST['CDEK-shipperEmail'] ) ? trim(sanitize_text_field( $_POST['CDEK-shipperEmail'] ) ) : '';
        $this->settings['CDEK-shipperPhone'] = isset( $_POST['CDEK-shipperPhone'] ) ? trim(sanitize_text_field( $_POST['CDEK-shipperPhone'] ) ) : '';
        $this->settings['CDEK-shopComment'] = isset( $_POST['CDEK-shopComment'] ) ? trim(sanitize_text_field( $_POST['CDEK-shopComment'] ) ) : '';
        
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
    public function enqueueAdminScripts()
    {
        $scriptID = IN_WC_CRM . '-CDEK';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/CDEK/js/CDEK.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_CDEK';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),		
        );
        wp_localize_script( $scriptID, $objectName, $data );        
    }

    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-CDEK-temp';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/CDEK/js/temp.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );
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
        $result = ['created' => array(), 'rejected' => array()];
        try
        {
            // Подключение
            $api = new API(
                $this->getParam( 'CDEK-api-endpoint', '' ),         // URL
                $this->getParam( 'CDEK-api-id', '' ),               // Идентификатор клиента
                $this->getParam( 'CDEK-api-secretkey', '' ),        // Пароль
                $this->getParam( 'CDEK-shopManagerName', '' ),      // Менеджер
                $this->getParam( 'CDEK-shopOrganization', '' ),     // Организация магазина
                $this->getParam( 'CDEK-shopAddress', '' ),          // Адрес магазина
                $this->getParam( 'CDEK-shopPhone', '' ),            // Телефон
                $this->settings[ 'CDEK-shipperName' ],          // Грузоотправитель
                $this->getParam( 'CDEK-shipperAddress', '' ),       // Адрес грузоотправителя
                $this->getParam( 'CDEK-shipperEmail', '' ),         // Email грузоотправителя
                $this->getParam( 'CDEK-shipperPhone', '' ),         // Телефон грузоотправителя
                $this->getParam( 'CDEK-shopComment', '' )           // Комментарий
            );

            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) );
            

            // Запрос выбранных заказов
            $args = array(
                'limit'     => apply_filters( 'inwccrm_CDEK_datatable_order_limit', self::ORDER_LIMIT ),
                'return'    => 'objects',
                'post__in'  => explode(',', $idsString )      
            );
            $orders = wc_get_orders( $args );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            // Передача заказов
            
        foreach( $orders as $order )
        {
            $response = $api->send( $order );
            if ('Accepted' === $response['message']) {
                $result['created'][$order->get_id()] = $response['uuid'];
            } else {
                $result['rejected'][$order->get_id()] = $response['message'] ;
            }

        }

            $resultStr = '';
            if ( count( $result['created'] > 0 ) ) 
            { 
                $resultStr .= __( 'Переданные заказы: ', IN_WC_CRM );
				foreach( $result['created'] as $order_id => $sending )
				{
					$currentOrder = new WC_Order( $order_id );
                    // Добавим мета-поля
                    $currentOrder->add_order_note( 
						__( 'CDEK', IN_WC_CRM ) . ': ' . 
						__( 'Отправление создано', IN_WC_CRM ) . ': ' . 
						$sending
					);
                    $currentOrder->add_meta_data( __( 'CDEK InvoiceNumber', IN_WC_CRM ),  $sending );
                    
                    // Установим новый статус
                    $currentStatus = $currentOrder->get_status();
                    $newStatus = apply_filters( 'inwccrm_CDEK_set_order_status', $currentStatus, $currentOrder );
                    Plugin::get()->log( __( 'Новый статус заказа: '.$newStatus, IN_WC_CRM ), 'CDEK-sending.log' );
                    if ( $currentStatus != $newStatus )
                    {
                        $orderNote = apply_filters( 'inwccrm_CDEK_set_order_status_note', 
                            __( 'Статус заказа изменен после успешной отправки CDEK', IN_WC_CRM ),
                            $newStatus, $currentOrder );
                        $currentOrder->update_status( $newStatus, $orderNote );
                    }

                    // Результат в строку результата
                    $resultStr .= $order_id . ',';
                }
                $resultStr .= PHP_EOL;             
            }

            if ( count( $result['rejected'] > 0 ) ) 
            { 
                $resultStr .= __( 'Отклоненные заказы: ', IN_WC_CRM );
				foreach( $result['rejected'] as $order_id => $sending )
				{
					$currentOrder = new WC_Order( $order_id );
					$currentOrder->add_order_note( 
						__( 'CDEK', IN_WC_CRM ) . ': ' . 
						__( 'Ошибка', IN_WC_CRM ) . ': ' . 
						$sending
					);					
					$resultStr .= $sending .  ' ' . $order_id . PHP_EOL;
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


    public function cdeklog( $message, $logfile = 'CDEK-sending.log' )
    {
        if ( !empty( $logfile ) ) {
            $logfile = WP_PLUGIN_DIR . '/wc-mbasegeotar/' . $logfile;
            //error_log( WP_PLUGIN_DIR . '/wc-mbasegeotar/' . 'MEDBASEGEOTARLOG: ' . $logfile );
        }
        if (is_array($message) || is_object($message)) 
        {
            if ( empty( $logfile ) )
            {
                //error_log( WP_PLUGIN_DIR . '/wc-mbasegeotar/' . ': ' . print_r( $message, true ) );
            }
            else
            {
                file_put_contents( $logfile, 
                    '[' . date('d.m.Y H:i:s') . '] ' . ': ' . print_r( $message, true ) . PHP_EOL, 
                    FILE_APPEND );  
            }
        } 
        else 
        {
            if ( empty( $logfile ) )
            {
                //error_log( WP_PLUGIN_DIR . '/wc-mbasegeotar/' . ': ' . $message );
            }
            else
            {
                file_put_contents( $logfile, 
                    '[' . date('d.m.Y H:i:s') . '] ' . ': ' . $message . PHP_EOL, 
                    FILE_APPEND );  
            }               
        }           
    }
}