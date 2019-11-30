<?php
/**
 * Базовый класс расширения страницы админки
 */
namespace IN_WC_CRM\Extensions;

class BaseAdminPage extends Base implements IAdminPage
{
    /**
     * Цвета профиля пользователя
     * @var mixed
     */
    protected $colors;

    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        // Хук на отрисовку админ страницы
        add_action('admin_head', array( $this, 'getAdminColors') );
    }

    /**
     * Читает цветовую схему, выбранную в профиле пользователя
     * Эти цвета могут использоваться при оформлении страницы
     * https://kolakube.com/admin-color-scheme/
     * https://wordpress.stackexchange.com/questions/127401/get-current-active-wp-color-scheme 
     */
    public function getAdminColors()
    {
        global $_wp_admin_css_colors;

        // Цвета, выбранные в профиле пользователя
        $admin_color = get_user_option( 'admin_color' );
        // Цвета палитры фона (4 шт)
        $this->colors = $_wp_admin_css_colors[$admin_color]->colors;
        // Цвет фона
        $this->colors[4] = $_wp_admin_css_colors[$admin_color]->icon_colors['base'];
        // Цвет текста 
        $this->colors[5] = $_wp_admin_css_colors[$admin_color]->icon_colors['current'];
        // Цвет выделенного элемента  
        $this->colors[6] = $_wp_admin_css_colors[$admin_color]->icon_colors['focus'];        
    }

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
     * Отрисовывает содержимое страницы
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