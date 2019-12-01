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
     * Возвращает true если этому расширению требуются настройки
     * @return bool
     */
    public function hasSettings();

    /**
     * Возвращает массив настроек
     * @return mixed
     */
    public function getSettings();

    /**
     * Сохраняет массив настроек
     */
    public function saveSettings();

     /**
     * Возвращает параметр или указанное дефолтовое значение
     * @paran string $paramName Название параметра
     * @param mixed $default Знавчение по умолчанию
     */
    public function getParam( $paramName, $default );   
    
    /**
     * Показывает секцию настроек
     */
    public function showSettings();

    /**
     * Возвращает true если расширение активно
     * @return bool
     */
    public function isEnabled();
}