<?php
/**
 * Расширение выводит позиции заказов в Excel
 */
namespace IN_WC_CRM\Extensions;
use \WC_Order as WC_Order;
use \IN_WC_CRM\Plugin as Plugin;
use \IN_WC_CRM\Extensions\Orders2Excel\EmptyOrderIDsException as EmptyOrderIDsException;
use \PHPExcel as PHPExcel;
use \PHPExcel_Writer_Excel5 as PHPExcel_Writer_Excel5;

require __DIR__ . '/../../asserts/PHPExcel-1.8/Classes/PHPExcel.php';
require __DIR__ . '/../../asserts/PHPExcel-1.8/Classes/PHPExcel/Writer/Excel5.php';

class Orders2Excel extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса и хуки
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) 
            return;
		
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ) );
        add_action( 'wp_ajax_orders2excel_get_orders', array( $this, 'getFile' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Позиции заказов в Excel';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка позиций выбранных заказов в Excel', IN_WC_CRM );
    }

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        @include 'views/controls.php';
    }

    /**
     * Максимальное число заказов из БД 
     */
    const NONCE = 'Orders2Excel';

    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-orders2excel';
        wp_register_script( 
            $scriptID, 
            Plugin::get()->url . 'extensions/Orders2Excel/js/orders2excel.js', 
            array( 'jquery' ),
            Plugin::get()->version, 
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_Orders2Excel';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),
            'downloadUrl' => admin_url( 'admin-ajax.php?action=orders2excel_get_orders' ) . '&ids='
        );
        wp_localize_script( $scriptID, $objectName, $data );        
    }

    /**
     * Максимальное число заказов из БД 
     */
    const ORDER_LIMIT = 250;

    /**
     * Возвращает список заказов по выбранным ID
     * @param mixed $ids    Массив с выбранными ID
     * @return mixed
     */
    private function getOrders( $ids )
    {
        // Запрос выбранных заказов
        $args = array(
            'limit'     => apply_filters( 'inwccrm_pickpoint_datatable_order_limit', self::ORDER_LIMIT ),
            'return'    => 'objects',
            'post__in'  => $ids     
        );
        return wc_get_orders( $args );
    }

    /**
     * Возвращает массив элементов для вывода в Эксель
     * Группирует элементы по SKU каждого элемента
     * @param mixed $orders    Массив заказов
     * @return mixed 
     */
    private function getOrderItems( $orders )
    {
        $items = array();
        foreach ( $orders as $order )
        {
            $orderItems = $order->get_items();
            foreach( $orderItems as $item_id => $item_data )
            {
                $product_name = $item_data['name'];
                $item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
                $product = wc_get_product( $item_data->get_product_id() );
                $item_sku = $product->get_sku();

                if ( ! array_key_exists( $item_sku, $items ) )
                {
                    $items[$item_sku] = array(
                        'sku' => $item_sku,
                        'name' => $product_name,
                        'quantity'  => $item_quantity
                    );
                }
                else
                {
                    $items[$item_sku]['quantity'] += $item_quantity;
                }
            }
        }
        return $items;
    }

    /**
     * AJAX запрос на генерацию файла
     * https://habr.com/ru/post/245233/
     */
    public function getFile()
    {
        // Имя файла
        $fileName = apply_filters( 'inwccrm_orders2excel_filename', 'orders-' . date('Y-m-d--H-i') . '.xls' );

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();
        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();
        // Подписываем лист
        $sheet->setTitle('Order Items');

        // Заголовок таблицы
        $headers = apply_filters( 'inwccrm_orders2excel_table_header', array(
            'A1' => __( 'Артикул', IN_WC_CRM ),
            'B1' => __( 'Наименование', IN_WC_CRM ),
            'C1' => __( 'Количество', IN_WC_CRM )
        ) );
        foreach ( $headers as $cell => $value )
        {
            $sheet->setCellValue( $cell, $value );
        }

        // Получаем заказы
        $idsString = ( isset( $_GET['ids'] ) ) ? trim( sanitize_text_field( $_GET['ids'] ) ) : '';
        $orders = $this->getOrders( explode(',', $idsString ) );

        // Получим массив элементов для вывода
        $items = apply_filters( 'inwccrm_orders2excel_table_data', $this->getOrderItems( $orders ), $orders );

        // Заполним данные со второго ряда
        $row = 2;
        foreach ( $items as $orderItem )
        {
            $rowData = apply_filters( 'inwccrm_orders2excel_table_row_data', array(
                'A' . $row => $orderItem['sku'],
                'B' . $row => $orderItem['name'],
                'C' . $row => $orderItem['quantity']
            ), $orderItem, $row );

            // Внесем эти данные
            foreach ( $rowData as $cell => $value )
            {
                $sheet->setCellValue( $cell, $value );
            } 
            $row++;
        }

        // Заголовки ответа
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $fileName );
        header( 'Content-Transfer-Encoding: binary' );
        
        // Выводим содержимое файла
        $objWriter = new PHPExcel_Writer_Excel5($xls);
        $objWriter->save('php://output');
        wp_die();
    }    

}