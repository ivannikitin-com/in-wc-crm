<?php
/**
 * Базовый класс расширения страницы админки
 */
namespace IN_WC_CRM\Extensions;

class BaseAdminPage extends Base implements IAdminPage
{
    /**
     * Возвращает заголовок страницы
     * @return string
     */
    public function getAdminPageTitle()
    {
        return $this->getTitle();
    }
    
    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function getAdminPageMenuTitle()
    {
        return $this->getTitle();
    }

     /**
     * Возвращает возможность пользователя, чтобы иметь доступ к этой странице
     * @return string
     */
    public function getAdminPageСapability()
    {
        return 'manage_options';
    }
    
    /**
     * Возвращает слаг страницы
     * @return string
     */
    public function getAdminPageSlug()
    {
        return strtolower( str_replace( '\\', '_', get_class( $this ) ) );
    }

    /**
     * Отрисовывает содержимое страницы
     */
    public function renderAdminPage()
    {
        // Отрисовка шапки
        $this->renderAdminPageHeader();
        
        // Отрисовка контента
        $this->renderAdminPageContent();
        
        // Отрисовка подвала
        $this->renderAdminPageFooter();
    }

    /**
     * Отрисовывает содержимое шапки страницы
     */
    protected function renderAdminPageHeader()
    {
        @include 'renderAdminPageHeader.php';
    }

    /**
     * Отрисовывает содержимое шапки страницы
     */
    protected function renderAdminPageContent()
    {
        @include 'renderAdminPageContent.php';
    }    
    
    /**
     * Отрисовывает содержимое подвала страницы
     */
    protected function renderAdminPageFooter()
    {
        @include 'renderAdminPageFooter.php';
    }       
}