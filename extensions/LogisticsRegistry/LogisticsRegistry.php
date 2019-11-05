<?php
/**
 * Класс расширения "Реестры для логистики"
 * Подробную информацию см. в файле info.md
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;

class LogisticsRegistry extends BaseAdminPage
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'Реестры товаров по логистическим методам доставки', IN_WC_CRM );
    }

    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function getAdminPageMenuTitle()
    {
        return __( 'Реестры для логистики', IN_WC_CRM );
    }

    /**
     * Отрисовывает содержимое страницы
     */
    protected function renderAdminPageContent()
    {
        @include 'renderAdminPageContent.php';
    }

}