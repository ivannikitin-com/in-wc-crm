<?php
/**
 * Интерфейс расширения
 * Декларирует обятельные свойства и методы расширения
 * Любое расширение должно его реализовывать
 */
namespace IN_WC_CRM\Extensions;

interface IExtension
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle();
 
     /**
     * Возвращает блок настроек в виде массима. Пустой массив -- настроек нет
     * @return string
     */   
    public function getSettings();
}