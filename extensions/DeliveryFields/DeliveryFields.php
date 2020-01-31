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
        
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Поля желаемой доставки';
    }
 
     /**
     * Возвращает блок настроек в виде массима. Пустой массив -- настроек нет
     * @return string
     */   
    public function getSettings()
    {
        return array();
    }
}