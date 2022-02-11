<?php
/**
 * Расширение добавляет в заказ поля со временем и датой желаемой доставки
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;

class DeliveryCheck extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса и ставит хуки
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) 
            return;

        // Отрисовка кнопки
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ), 52 );
        
        // JS
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

        // Ajax
        add_action( 'wp_ajax_inwccrm_delivery_check', array( $this, 'getAjaXResponse' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'Статусы доставки заказов', IN_WC_CRM );
    }

    /**
     * Возвращает описание расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Проверка и обновление статусов доставки заказов', IN_WC_CRM );
    }    
 
     /**
     * Возвращает блок настроек в виде массива. Пустой массив -- настроек нет
     * @return mixed
     */   
    public function getSettings()
    {
        return array();
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
        $scriptID = IN_WC_CRM . '-DeliveryCheck';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/DeliveryCheck/frontend.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_DeliveryCheck';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),		
        );
        wp_localize_script( $scriptID, $objectName, $data ); 
    }

    // Ответ на AJAX запрос
    public function getAjaXResponse()
    {
        // Собираем данные об обновлениях статусов, которые возвращают расширения
        echo apply_filters( 'inwccrm_delivery_check', '' );
        wp_die();
    }
}