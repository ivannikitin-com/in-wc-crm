<?php
/**
 * Расширение взаимодействует с B2CPL
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order as WC_Order;
use \Exception as Exception;

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
}