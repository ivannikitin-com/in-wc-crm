<?php
/**
 * Класс расширения "Реестры для логистики"
 * Подробную информацию см. в файле info.md
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;

/**
 * Подключаем дополнительные классы
 */
require_once 'WC_Data.php';
require_once 'WC_Data_SQL.php';

class LogisticsRegistry extends BaseAdminPage
{
    /**
     * Объект получения данных WC
     * @var WC_Data
     */
    private $wc;

    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();

        // Инициализиуем объект данных
        $this->wc = WC_Data::get();
    }



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