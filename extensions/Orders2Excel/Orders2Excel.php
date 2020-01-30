<?php
/**
 * Расширение выводит списко пользователей
 */
namespace IN_WC_CRM\Extensions;
use \WC_Order as WC_Order;
use \Exception as Exception;
use \IN_WC_CRM\Plugin as Plugin;
use \IN_WC_CRM\Extensions\Orders2Excel\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\Orders2Excel\NoOrdersException as NoOrdersException;

require 'Exceptions.php';

class Orders2Excel extends Base
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
		
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ) );
        add_action( 'wp_ajax_orders2excel_send_orders', array( $this, 'processOrders' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Заказы в Excel';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка выбранных заказов в Excel', IN_WC_CRM );
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
        $scriptID = IN_WC_CRM . '-orders2excel';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/Orders2Excel/js/orders2excel.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_Orders2Excel';
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
     * AJAX запрос на обработку данных
     */
    public function processOrders()
    {
        try
        {
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

            echo var_export( $orders, true );
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