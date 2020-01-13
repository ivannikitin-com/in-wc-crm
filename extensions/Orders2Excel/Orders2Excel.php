<?php
/**
 * Расширение выводит списко пользователей
 */
namespace IN_WC_CRM\Extensions;

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
		
        //add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ) );
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
        return __( 'Выггузка выбранных заказов в Excel', IN_WC_CRM );
    }

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'views/controls.php';
    }

}