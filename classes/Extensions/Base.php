<?php
/**
 * Базовый класс расширения
 */
namespace IN_WC_CRM\Extensions;

class Base implements IExtension
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return '';
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