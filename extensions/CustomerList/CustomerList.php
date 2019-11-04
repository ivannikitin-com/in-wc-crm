<?php
/**
 * Расширение выводит списко пользователей
 */
namespace IN_WC_CRM\Extensions;

class CustomerList extends Base
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Клиенты';
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