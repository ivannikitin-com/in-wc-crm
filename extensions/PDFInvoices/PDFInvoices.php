<?php
/**
 * Pасширение PDFInvoices добавляет возможность печати счетов заказов через плагин 
 * WooCommerce PDF Invoices & Packing Slips
 * https://ru.wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
 */
namespace IN_WC_CRM\Extensions;

use \IN_WC_CRM\Plugin as Plugin;

class PDFInvoices extends Base
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'PDF Invoices';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Печать PDF счетов заказов через плагин WooCommerce PDF Invoices & Packing Slips', IN_WC_CRM );
    }

    /**
     * Конструктор класса
     * Инициализирует свойства класса и ставит хуки
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) return;
        
        // Проверка установлен ли плагин "WooCommerce PDF Invoices & Packing Slips"
        if ( ! in_array( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php', 
                apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;
        
        // Добавляем кнопку на страницу заказов
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ), 21 );

        // Скрипт обработки кнопки и AJAX
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

        // Обработка AJAX запроса
        add_action( 'wp_ajax_pdfinvoices_get_invoices', array( $this, 'getInvoicesURL' ) );
    }

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'button.php';
    }
    
    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-pdfinvoices';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/PDFInvoices/pdfinvoices.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скрипта
        $objectName = 'IN_WC_CRM_PDFInvoices';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),
            'emptyResponse' => __( 'Получен пустой ответ', IN_WC_CRM )
        );
        wp_localize_script( $scriptID, $objectName, $data );        
    }

    /**
     * Вовзарщает URL PDF файлов со счетами в ответ на AJAX запрос
     */
    public function getInvoicesURL()
    {
        // ID заказов
        $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
        if ( empty( $idsString ) ) 
        {
            echo '';
            wp_die();
        }

        // Заменим "," на "x"
        $idsString = str_replace( ',', 'x', $idsString );

        // Формируем URL так же, как в плагине
        $url = wp_nonce_url( admin_url( "admin-ajax.php?action=generate_wpo_wcpdf&document_type=invoice&order_ids={$idsString}" ), 'generate_wpo_wcpdf' );
        $url = htmlspecialchars_decode( $url );

        echo $url;
        wp_die();
    } 
}