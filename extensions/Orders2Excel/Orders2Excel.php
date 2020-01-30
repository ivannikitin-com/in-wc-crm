<?php
/**
 * Расширение выводит списко пользователей
 */
namespace IN_WC_CRM\Extensions;
use \WC_Order as WC_Order;
use \Exception as Exception;
use \IN_WC_CRM\Plugin as Plugin;
use \IN_WC_CRM\Extensions\Orders2Excel\EmptyOrderIDsException as EmptyOrderIDsException;
use \PHPExcel as PHPExcel;
use \PHPExcel_Writer_Excel5 as PHPExcel_Writer_Excel5;


require __DIR__ . '/Exceptions.php';
require __DIR__ . '/../../asserts/PHPExcel-1.8/Classes/PHPExcel.php';
require __DIR__ . '/../../asserts/PHPExcel-1.8/Classes/PHPExcel/Writer/Excel5.php';

class Orders2Excel extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) 
            return;
		
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ) );
        add_action( 'wp_ajax_orders2excel_prepare_orders', array( $this, 'prepareOrders' ) );
        add_action( 'wp_ajax_orders2excel_get_orders', array( $this, 'getFile' ) );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Заказы в Excel';
    }
 
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка выбранных заказов в Excel', IN_WC_CRM );
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
            'error' => __( 'Ошибка!', IN_WC_CRM ),
            'nonce' => wp_create_nonce( self::NONCE ),
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
     * AJAX запрос на обработку данных
     */
    public function prepareOrders()
    {
        try
        {
            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) ); 
               
            $orders = $this->getOrders( explode(',', $idsString ) );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            echo json_encode( array(
                'status' => 'success',
                'url' => admin_url( 'admin-ajax.php?action=orders2excel_get_orders' ) . '&ids=' . $idsString,
            ));
            wp_die();
        }
        catch (Exception $e) 
        {
            // Возникли ошибки
            echo json_encode( array(
                'status' => 'error',
                'message' => $e->getMessage()
            ));            
            wp_die();  
        }        
    } 

    /**
     * AJAX запрос на генерацию файла
     * https://habr.com/ru/post/245233/
     */
    public function getFile()
    {
        // Имя файла
        $fileName = 'orders-' . date('Y-m-d--H-i') . '.xls';
        
        // Получаем заказы
        $idsString = ( isset( $_GET['ids'] ) ) ? trim( sanitize_text_field( $_GET['ids'] ) ) : '';
        $orders = $this->getOrders( explode(',', $idsString ) );

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();
        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();
        // Подписываем лист
        $sheet->setTitle('Orders');
        // Вставляем текст в ячейку A1
        $sheet->setCellValue("A1", 'Таблица умножения');

        // Заголовки
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $fileName );
        header( 'Content-Transfer-Encoding: binary' );
        
        // Выводим содержимое файла
        $objWriter = new PHPExcel_Writer_Excel5($xls);
        $objWriter->save('php://output');
        wp_die();
    }    

}