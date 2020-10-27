<?php
/**
 * Класс Condition декларирует условия проверки
 * и реализует их выполнение
 */

namespace IN_WC_CRM\Extensions\OrderTags;
class Condition
{
    /**
     * Параметры проверки
     * @var mixed 
     */
    private $params;

    /**
     * Операции сравнения
     * @var mixed
     */
    private $equals;


    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Параметры проверки
        $this->params = array(
            'order_items'   =>  __( 'Число позиций в заказе', IN_WC_CRM ),
        );

        // Операции сравнения
        $this->equals = array(
            'eq'   =>  __( 'Равно', IN_WC_CRM ),
            '!eq'  =>  __( 'Не равно', IN_WC_CRM ),
            'gt'   =>  __( 'Больше', IN_WC_CRM ),
            '!gt'  =>  __( 'Не больше', IN_WC_CRM ),
            'lt'   =>  __( 'Меньше', IN_WC_CRM ),
            '!lt'  =>  __( 'Не меньше', IN_WC_CRM ),
            're'   =>  __( 'Соотвествует регулярному выражению', IN_WC_CRM ),
            '!re'  =>  __( 'Не соотвествует регулярному выражению', IN_WC_CRM ),
        );        
    }

    /**
     * Возвращает параметры
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Возвращает операции
     * @return mixed
     */
    public function getEquals()
    {
        return $this->equals;
    }    

}