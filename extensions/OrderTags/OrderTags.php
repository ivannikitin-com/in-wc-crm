<?php
/**
 * Расширение OrderTags формирует таксономию меток на заказы WooCommerce 
 * и автоматически назначает эти метки по правилам
 * Также метки на заказы можно добавлять и в ручном режиме
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Extensions\OrderTags\OrderTag as OrderTag;

require 'OrderTag.php';

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
     * Конструктор класса
     * Инициализирует свойства класса и хуки
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) return;

        // Метки заказа
        $this->orderTag = new OrderTag();

        // Хуки
        add_action( 'admin_menu', array( $this, 'modifyAdminMenu' ) );
        add_filter( 'inwccrm_orderlist_columns', array( $this, 'getOrderListColumns' ) );
        add_filter( 'inwccrm_orderlist_column_data', array( $this, 'getOrderListData' ), 10, 3 );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'getWooCommerceOrderColumns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'showWooCommerceOrderColumnData' ) );
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
     * Модификация меню CRM
     */
    public function modifyAdminMenu()
    {
        global $submenu;
        // Метки заказов
        $orderTagTaxonomyEdit = admin_url( 'edit-tags.php' ).'?taxonomy=' . OrderTag::TAXOMOMY;
        $submenu[ IN_WC_CRM ][] = array( __( 'Метки заказов', IN_WC_CRM ), 'manage_woocommerce', $orderTagTaxonomyEdit );
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
        if ( $column != self::ORDER_LIST_COLUMN ) return $data;

        return '--';
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

        if ( $column == self::ORDER_LIST_COLUMN ) {

            $order    = wc_get_order( $post->ID );
            $currency = is_callable( array( $order, 'get_currency' ) ) ? $order->get_currency() : $order->order_currency;
            $profit   = '';
            //$cost     = sv_helper_get_order_meta( $order, '_wc_cog_order_total_cost' );
            $total    = (float) $order->get_total();

            // don't check for empty() since cost can be '0'
            if ( '' != $cost || false != $cost ) {

                // now we can cast cost since we've ensured it was calculated for the order
                $cost   = (float) $cost;
                $profit = $total - $cost;
            }

            //echo wc_price( $profit, array( 'currency' => $currency ) );
        }
    }

}