<?php
/**
 * Расширение OrderTags формирует таксономию меток на заказы WooCommerce 
 * и автоматически назначает эти метки по правилам
 * Также метки на заказы можно добавлять и в ручном режиме
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \IN_WC_CRM\Extensions\OrderTags\OrderTag as OrderTag;
use \IN_WC_CRM\Extensions\OrderTags\RuleManager as RuleManager;

require 'OrderTag.php';
require 'RuleManager.php';

/**
 * Класс собирает все классы расширения и справляет ими
 */
class OrderTags extends Base
{
    /**
     * Управление таксономией меток
     */
    private $orderTag;

    /**
     * Управление правилами меток
     */
    private $ruleManager;    

    /**
     * Конструктор класса
     * Инициализирует свойства класса и хуки
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) return;

        // Метки заказа
        $this->orderTag = new OrderTag();

        // Менеджер правил
        $this->ruleManager = new RuleManager();
        // Хуки
        add_action( 'admin_enqueue_scripts', array( $this, 'loadStyles' ) );
        add_action( 'admin_menu', array( $this, 'modifyAdminMenu' ) );
        add_filter( 'inwccrm_orderlist_columns', array( $this, 'getOrderListColumns' ) );
        add_filter( 'inwccrm_orderlist_column_data', array( $this, 'getOrderListData' ), 10, 3 );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'getWooCommerceOrderColumns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'showWooCommerceOrderColumnData' ) );
        add_action( 'inwccrm_orderlist_controls_after', array( $this, 'showSearchControl' ) );
        add_filter( 'inwccrm_orderlist_custom_filter', array( $this, 'filterOrder' ), 10, 3 );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Метки заказов';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Расширение OrderTags формирует и управляет метками заказов WooCommerce', IN_WC_CRM );
    }

    /**
     * Стили CSS этого модуля
     */
    public function loadStyles()
    {
        if( ! is_admin() ) return;

        wp_enqueue_style( IN_WC_CRM . '-orderTags', Plugin::get()->url . 'extensions/OrderTags/ordertags.css' );
    }

    /**
     * Модификация меню CRM
     */
    public function modifyAdminMenu()
    {
        global $submenu;
        
        // Метки заказов
        $orderTagTaxonomyEdit = admin_url( 'edit-tags.php' ).'?taxonomy=' . OrderTag::TAXONOMY;
        $submenu[ IN_WC_CRM ][] = array( __( 'Метки заказов', IN_WC_CRM ), 'manage_woocommerce', $orderTagTaxonomyEdit );

        // Правила обработки заказов
        $orderTagRuleEdit =  admin_url( 'edit.php' ).'?post_type=' . RuleManager::CPT;
        $submenu[ IN_WC_CRM ][] = array( __( 'Правила пометки заказов', IN_WC_CRM ), 'manage_woocommerce', $orderTagRuleEdit );
    }

    /**
     * Колонка в таблице заказов
     */
    const ORDER_LIST_COLUMN = 'order_tags';

    /**
     * Добавляет колонку в список заказов CRM
     * @param string[] $columns
     */
    public function getOrderListColumns( $columns )
    {
        $columns[ self::ORDER_LIST_COLUMN ] = __( 'Метки', IN_WC_CRM );
        return $columns;
    }

    /**
     * Возвращает данные для колонки в список заказов CRM
     */
    public function getOrderListData( $data, $column, $order )
    {
        if ( $column != self::ORDER_LIST_COLUMN ) 
            return $data;
        else
            return $this->getTags( $order );
    }

    /**
     * Добавляем колонку меток в список заказов WooCommerce
     *
     * @param string[] $columns
     */
    function getWooCommerceOrderColumns( $columns ) 
    {
        $columns[ self::ORDER_LIST_COLUMN ] = __( 'Метки', IN_WC_CRM );
        return $columns;
    }

    /**
     * Вывод меток в список заказов WooCommerce
     *
     * @param string[] $column name of column being displayed
     */
    function showWooCommerceOrderColumnData( $column ) {
        global $post;

        if ( $column == self::ORDER_LIST_COLUMN ) 
        {
            $order = wc_get_order( $post->ID );
            echo $this->getTags( $order );
        }
    }

    /**
     * Форматирует метку
     * @param WP_Term    $term     Объект метки
     * @return string   HTML представление
     */
    private function formatTag( $term )
    {
        // CSS стиль
        $style = '';

        // Цвет метки
        $color = get_term_meta( $term->term_id, OrderTag::META_COLOR, true );
        if ( $color ) $style .= "color:{$color};";

        // Цвет фона метки
        $colorBg = get_term_meta( $term->term_id, OrderTag::META_COLOR_BG, true );
        if ( $colorBg ) $style .= "background-color:{$colorBg}";
        
        // CSS стиль (атрибут)
        if ( $style ) $style = " style='{$style}'";

        // HTML метки
        return "<span class='in_wc_crm_order_tag'{$style}>{$term->name}</span>";
    }

    /**
     * Возвращает список метод для указанного заказа
     * @param WC_Order  $order  Объект заказа
     * @return string   HTML представление
     */
    private function getTags( $order )
    {
        // Получим все метки заказа (массив ID)
        $terms = wp_get_post_terms( $order->get_id(), OrderTag::TAXONOMY, array('fields' => 'all') );
        
        // Сформируем результат
        $result = '';
        foreach( $terms as $term )
        {
            $result .= $this->formatTag( $term ) . ' ';
        }

        return $result;
    }

    /**
     * Показывает элемент фильтра для поиска по меткам
     */
    public function showSearchControl()
    {
        @include 'views/search-control.php';
    }

    /**
     * Функция выполняет фильтрацию заказов
     * @param mixed $value      Если true Запись уходит на выдачу
     * @param WC_Order $order   Текущий заказ
     * @param mixed $postData   Данные $_POST AJAX запроса 
     */
    public function filterOrder( $value, $order, $postData )
    {
        $selectedTagId = ( isset( $postData['order_tag'] ) ) ? (int) sanitize_text_field( $postData['order_tag'] )  : 0;
        if (! $selectedTagId ) return true;
        
        $terms = wp_get_post_terms( $order->get_id(), OrderTag::TAXONOMY, array('fields' => 'ids') );
        return in_array( $selectedTagId, $terms ) ;
    }

}