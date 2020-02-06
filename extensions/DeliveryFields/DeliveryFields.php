<?php
/**
 * Расширение добавляет в заказ поля со временем и датой желаемой доставки
 */
namespace IN_WC_CRM\Extensions;

class DeliveryFields extends Base
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
            add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'addShippingFields' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'Поля желаемой доставки', IN_WC_CRM );
    }

    /**
     * Возвращает описание расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Добавление полей времени доставки в заказ', IN_WC_CRM );
    }    
 
     /**
     * Возвращает блок настроек в виде массима. Пустой массив -- настроек нет
     * @return mixed
     */   
    public function getSettings()
    {
        return array();
    }

     /**
     * Добавляет в массив полей доставки новые поля
     * @param mixed $shippingFileds Стандартные поля доставки
     * @return string
     */   
    public function addShippingFields( $shippingFileds )
    {
        return array_merge( $shippingFileds, array(
            'delivery_date' => array(
                'label' => __( 'Дата доставки', IN_WC_CRM ),
                'show'  => true,
            ),
            'delivery_time' => array(
                'label' => __( 'Время доставки', IN_WC_CRM ),
                'show'  => true,
            ),                        
        ) );
    }    
}