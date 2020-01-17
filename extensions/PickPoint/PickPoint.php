<?php
/**
 * Расширение взаимодействует с PickPoint
 */
namespace IN_WC_CRM\Extensions;

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
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'views/controls.php';
    }

}