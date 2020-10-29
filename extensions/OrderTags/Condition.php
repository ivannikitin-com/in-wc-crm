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
            'order_items'           =>  __( 'Число товаров в заказе', IN_WC_CRM ),
            'order_items_count'     =>  __( 'Число позиций в заказе', IN_WC_CRM ),
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

    /**
     * Проверяет, соотвествует ли заказ условиям
     * @param WC_Order  $order          Заказ WC
     * @param mixed     $conditions     Массив условий
     * @return bool
     */
    public function check($order, $conditions)
    {
        // Пройдем по каждому условию
        foreach( $conditions as $condition )
        {
            $param =  $this->getParam( $condition['param'], $order );
            $value =  $condition['value'];

            switch ($condition['equal']){
                case 'eq':
                    // Возвращаем false в случае НЕ РАВЕНСТВА
                    if ($param != $value) return false;
                    break;

                case '!eq':
                    // Возвращаем false в случае РАВЕНСТВА
                    if ($param == $value) return false;
                    break;

                case 'lt':
                    // Возвращаем false в случае НЕ МЕНЬШЕ
                    if (!($param < $value)) return false;                    
                    break;

                case '!lt':
                    // Возвращаем false в случае МЕНЬШЕ
                    if ($param < $value) return false;                      
                    break;

                case 'gt':
                    // Возвращаем false в случае НЕ БОЛЬШЕ
                    if (!($param > $value)) return false;                        
                    break;

                case '!gt':
                    // Возвращаем false в случае БОЛЬШЕ
                    if ($param > $value) return false;                     
                    break;

                case 're':
                    // Возвращаем false в случае НЕ СООТВЕТСТВИЯ регулярному выражению
                    if (!(preg_match('/' . $value . '/', $param ))) return false;                  
                    break;

                case '!re':
                    // Возвращаем false в случае НЕ СООТВЕТСТВИЯ регулярному выражению
                    if (preg_match('/' . $value . '/', $param )) return false;                     
                    break;
            }
        }
        // Все правила сработали!
        return true;
    }
    
    /**
     * Функция возвращает треубуемый параметр по имени
     * @param string    $param  Имя параметра
     * @param WC_order  $order  Заказ WC
     * @return int|float|string
     */
    private function getParam($param, $order)
    {
        switch($param)
        {
            case 'order_items':  
                return $order->get_item_count();
                
            case 'order_items_count':  
                return count( $order->get_items() );
        }
        return false;
    }


}