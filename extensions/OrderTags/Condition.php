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
            'customer_registration' =>  __( 'Пользователь зарегистрирован (Да/Нет)', IN_WC_CRM ),
            'customer_name'         =>  __( 'Имя пользователя', IN_WC_CRM ),
            'customer_email'        =>  __( 'E-mail пользователя', IN_WC_CRM ),
            'customer_phone'        =>  __( 'Телефон пользователя', IN_WC_CRM ),
            'billing_type'          =>  __( 'Способ оплаты', IN_WC_CRM ),
            'shipping_type'         =>  __( 'Способ доставки', IN_WC_CRM ),
            'billing_country'       =>  __( 'Страна оплаты', IN_WC_CRM ),
            'billing_city'          =>  __( 'Город оплаты', IN_WC_CRM ),
            'shipping_country'      =>  __( 'Страна доставки', IN_WC_CRM ),
            'shipping_city'         =>  __( 'Город доставки', IN_WC_CRM ),
            'order_total'           =>  __( 'Сумма заказа', IN_WC_CRM ),
            'order_items'           =>  __( 'Число единиц товаров в заказе', IN_WC_CRM ),
            'order_items_count'     =>  __( 'Число товарных позиций в заказе', IN_WC_CRM ),
            'order_item_sku'        =>  __( 'Артикул товара', IN_WC_CRM ),
            'order_item_title'      =>  __( 'Название товара', IN_WC_CRM ),
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
        return apply_filters( 'inwccrm_ordertags_params', $this->params );
    }

    /**
     * Возвращает операции
     * @return mixed
     */
    public function getEquals()
    {
        return apply_filters( 'inwccrm_ordertags_equals', $this->equals );
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

            $result = false;
            switch ($condition['equal'])
            {
                case 'eq':
                    $result = ($param == $value);
                break;

                case '!eq':
                    $result = ($param != $value);
                break;

                case 'lt':
                    $result = ($param < $value);
                break;

                case '!lt':
                    $result = ! ($param < $value);
                break;

                case 'gt':
                    $result = ($param > $value);
                break;

                case '!gt':
                    $result = ! ($param > $value);
                break;

                case 're':
                    $result = mb_ereg_match( $value, $param, 'iz' ); 
                break;

                case '!re':
                    $result = ! mb_ereg_match( $value, $param, 'iz' ); 
                break;
            }

            // Новые операции, возможно определенные фильром
            $result = apply_filters( 'inwccrm_ordertags_check', $result, $order, $param, $condition, $value );

            // Если результат false, дальше проверку можно не делать (ленивое вычисление)
            if ( ! $result ) return false;
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
        $paramValue = '';
        switch($param)
        {
            // ---------------------- Заказчик ----------------------
            case 'customer_registration':
                $user = get_user_by( 'email', $order->get_billing_email() );
                $paramValue = ( isset($user->ID) ) ? __( 'Да', IN_WC_CRM ) : __( 'Нет', IN_WC_CRM );
            break;

            case 'customer_name':  
                $paramValue = $order->get_formatted_billing_full_name();
            break;

            case 'customer_email':  
                $paramValue = $order->get_billing_email();
            break;

            case 'customer_phone':  
                $paramValue = $order->get_billing_phone();
            break;

            // ------------------------- Заказ -----------------------
            case 'billing_type':  
                $paramValue = $order->get_payment_method_title();
            break;

            case 'shipping_type':  
                $paramValue = $order->get_shipping_method();
            break;

            case 'billing_country':  
                $paramValue = $order->get_billing_country();
            break;

            case 'billing_city':  
                $paramValue = $order->get_billing_city();
            break;

            case 'shipping_country':  
                $paramValue = $order->get_shipping_country();
            break;

            case 'shipping_city':  
                $paramValue = $order->get_shipping_city();
            break;

            case 'order_total':  
                $paramValue = $order->calculate_totals();
            break;            

            // -------------------- Элементы заказа ------------------
            case 'order_items':  
                $paramValue = $order->get_item_count();
            break;
                
            case 'order_items_count':  
                $paramValue = count( $order->get_items() );
            break;

            case 'order_item_sku':
                $skuArray = array();
                foreach( $order->get_items() as $item_id => $item )
                {
                    $product = $item->get_product();
                    $skuArray[] = $product->get_sku();
                }
                $paramValue = implode("\t", $skuArray);
            break;
            
            case 'order_item_title':
                $titleArray = array();
                foreach( $order->get_items() as $item_id => $item )
                {
                    $titleArray[] = $item->get_name();
                }
                $paramValue = implode("\t", $titleArray);
            break;           

        }

        return apply_filters( 'inwccrm_ordertags_get_param', $paramValue, $order, $param );
    }


}