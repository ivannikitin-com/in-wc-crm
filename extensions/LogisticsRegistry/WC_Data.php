<?php
/**
 * Базовый класс возвращает данные WooCommerce
 * Он же является фабрикой классов для различных условий
 */
namespace IN_WC_CRM\Extensions;

class WC_Data
{
    /**
     * Метод фабрика классов
     * Возвращает объект WC_Data нужного типа
     */
    public static function get()
    {
        // Пока возвращаем SQL 
        return new WC_Data_SQL();
    } 

    /**
     * Массив заказов
     * @var mixed
     */
    protected $orders;

    /**
     * Массив элементов текущего заказа
     * @var mixed
     */
    protected $items;    


    /**
     * Возвращает заказы с требуемыми статусами
     * @param mixed $statuses   Массив со статусами заказов
     * @return mixed            Массив с объектами заказов
     * 
     * Схема возврата
     * [
     *  { id, shipping_method, customer_name, customer_addr, items: [ { sku, title, quo} ] }
     * ]
     */
    public function getActiveOrders( $statuses = ['processing'] )
    {
        // Этот метод должен быть перекрыт в дочернем классе
        return $this->orders;
    }

    /**
     * Очищает массив заказов
     */
    protected function clearOrders()
    {
        $this->orders = array();
    }

    /**
     * Очищает массив элементов
     */
    protected function clearItems()
    {
        $this->items = array();
    }

    /**
     * Добавляет новый заказ в массив
     * @param int      $id                     ID заказа
     * @param string   $shipping_method        Способ доставки
     * @param string   $customer_name          Имя пользователя
     * @param string   $customer_addr          Адрес пользователя
     * 
     */
    protected function addOrder( $id, $shipping_method, $customer_name, $customer_addr )
    {
        $this->orders[] = array(
            'id'                => $id,
            'shipping_method'   => $shipping_method,
            'customer_name'     => $customer_name,
            'customer_addr'     => $customer_addr,
            'items'             => $this-items
        );
    }   

    /**
     * Добавляет новый элемент заказа
     * @param string    $sku        Артикул
     * @param string    $title      Наименование
     * @param int       $quo        Количество
     */
    protected function addItem( $sku, $title, $quo )
    {
        $this->items[] = array(
            'sku'       => $sku,
            'title'     => $title,
            'quo'       => $quo
        );
    }
}