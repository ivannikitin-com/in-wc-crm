<?php
/**
 * Интерфейс страницы админки
 * Декларирует обятельные свойства и методы страницы админки
 * Любое расширение, которое отображается как страница админки, должно его реализовывать
 */
namespace IN_WC_CRM\Extensions;

interface IAdminPage
{
    /**
     * Возвращает заголовок страницы
     * @return string
     */
    public function getAdminPageTitle();
    
    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function getAdminPageMenuTitle();

     /**
     * Возвращает возможность пользователя, чтобы иметь доступ к этой странице
     * @return string
     */
    public function getAdminPageСapability();
    
    /**
     * Возвращает слаг страницы
     * @return string
     */
    public function getAdminPageSlug();

    /**
     * Отрисовывает содержимое страницы
     */
    public function renderAdminPage();    
}