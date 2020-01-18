<?php
/**
 * Расширение взаимодействует с PickPoint
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;

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

    }
}