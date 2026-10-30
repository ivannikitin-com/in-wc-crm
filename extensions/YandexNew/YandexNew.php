<?php
/**
 * Расширение взаимодействует с Яндекс Доставкой по России (новая)
 *
 * Документация API: https://yandex.ru/support/delivery-profile/ru/api/other-day/
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;
use \Exception as Exception;
use IN_WC_CRM\Extensions\YandexNew\API as API;
use \IN_WC_CRM\Extensions\YandexNew\EmptyOrderIDsException as EmptyOrderIDsException;
use \IN_WC_CRM\Extensions\YandexNew\NoOrdersException as NoOrdersException;

require __DIR__ . '/API.php';

class YandexNew extends Base
{
    /**
     * Конструктор класса
     * Инициализирует свойства класса
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! $this->isEnabled() ) return;

        add_action( 'inwccrm_orderlist_actions_after', array( $this, 'renderControl' ), 33 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
        add_action( 'wp_ajax_yandexnew_send_orders', array( $this, 'sendOrders' ) );
        add_action( 'woocommerce_before_order_object_save', array( $this, 'savePlatformStationId' ), 20 );
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return 'Яндекс Доставка (новая)';
    }

    /**
     * Возвращает описание расширения
     * @return string
     */
    public function getDescription()
    {
        return __( 'Выгрузка заказов в Яндекс Доставку по России', IN_WC_CRM );
    }

    /**
     * Возвращает true если этому расширению требуются настройки
     * @return bool
     */
    public function hasSettings()
    {
        return true;
    }

    /**
     * Показывает секцию настроек
     */
    public function showSettings()
    {
        @include( Plugin::get()->path . 'extensions/YandexNew/settings.php' );
    }

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings['YandexNew-api-url'] = isset( $_POST['YandexNew-api-url'] ) ? sanitize_text_field( $_POST['YandexNew-api-url'] ) : API::DEFAULT_HOST;

        $this->settings['YandexNew-api-token'] = isset( $_POST['YandexNew-api-token'] ) ? trim( sanitize_text_field( $_POST['YandexNew-api-token'] ) ) : '';
        $this->settings['YandexNew-api-token-rosmedlib'] = isset( $_POST['YandexNew-api-token-rosmedlib'] ) ? trim( sanitize_text_field( $_POST['YandexNew-api-token-rosmedlib'] ) ) : '';

        $this->settings['YandexNew-source-station'] = isset( $_POST['YandexNew-source-station'] ) ? trim( sanitize_text_field( $_POST['YandexNew-source-station'] ) ) : '';
        $this->settings['YandexNew-source-station-rosmedlib'] = isset( $_POST['YandexNew-source-station-rosmedlib'] ) ? trim( sanitize_text_field( $_POST['YandexNew-source-station-rosmedlib'] ) ) : '';

        $defaultWeight = isset( $_POST['YandexNew-default-weight'] ) ? trim( sanitize_text_field( $_POST['YandexNew-default-weight'] ) ) : '';
        if ( '' !== $defaultWeight )
        {
            if ( '.' === get_option( 'woocommerce_price_decimal_sep' ) )
            {
                $defaultWeight = str_replace( ',', '.', $defaultWeight );
            }
            if ( ! is_numeric( $defaultWeight ) || floatval( $defaultWeight ) < 0 )
            {
                $defaultWeight = '';
            }
        }
        $this->settings['YandexNew-default-weight'] = $defaultWeight;

        return parent::saveSettings();
    }

    /**
     * Отрисовывает кнопку
     */
    public function renderControl()
    {
        // Кнопка выгрузки доступна только администратору
        if ( ! in_array( 'administrator', (array) wp_get_current_user()->roles, true ) )
        {
            return;
        }

        @include 'controls.php';
    }

    /**
     * Сохраняет ID станции ПВЗ Яндекса из пункта выдачи, выбранного на чекауте
     * @param WC_Order $order Заказ WooCommerce
     */
    public function savePlatformStationId( $order )
    {
        foreach ( $order->get_shipping_methods() as $shippingMethod )
        {
            if ( 0 !== strpos( $shippingMethod->get_method_id(), 'boxberry_self' ) )
            {
                continue;
            }

            $code = (string) $order->get_meta( 'boxberry_code' );

            // Код пункта выдачи Яндекса — UUID, код пункта Boxberry короче
            if ( strlen( $code ) > 20 )
            {
                $order->update_meta_data( API::META_PLATFORM_STATION_ID, $code );
            }
            else
            {
                $order->delete_meta_data( API::META_PLATFORM_STATION_ID );
            }
        }
    }

    /**
     * Подключает скрипты
     */
    public function enqueueScripts()
    {
        $scriptID = IN_WC_CRM . '-YandexNew';
        wp_register_script(
            $scriptID,
            Plugin::get()->url . 'extensions/YandexNew/frontend.js',
            array( 'jquery' ),
            Plugin::get()->version,
            true );
        wp_enqueue_script( $scriptID );

        // Параметры для скриптов
        $objectName = 'IN_WC_CRM_YandexNew';
        $data = array(
            'noRowsSelected' => __( 'Необходимо выбрать один или несколько заказов', IN_WC_CRM ),
        );
        wp_localize_script( $scriptID, $objectName, $data );
    }

    /**
     * Максимальное число заказов из БД
     */
    const ORDER_LIMIT = 250;

    /**
     * AJAX запрос на отправку данных
     */
    public function sendOrders()
    {
        try
        {
            // Подключение
            $api = new API(
                $this->getParam( 'YandexNew-api-url', API::DEFAULT_HOST ),                   // хост API
                $this->getParam( 'YandexNew-api-token', '' ),                               // токен
                $this->getParam( 'YandexNew-api-token-rosmedlib', '' ),                     // токен для rosmedlib
                $this->getParam( 'YandexNew-source-station', '' ),                          // станция отгрузки
                $this->getParam( 'YandexNew-source-station-rosmedlib', '' ),                // станция отгрузки для rosmedlib
                $this->getParam( 'YandexNew-default-weight', API::DEFAULT_WEIGHT )          // вес по умолчанию
            );

            // ID заказов для отправки
            $idsString = ( isset( $_POST['ids'] ) ) ? trim( sanitize_text_field( $_POST['ids'] ) ) : '';
            if ( empty( $idsString ) ) throw new EmptyOrderIDsException( __( 'ID заказов не переданы', IN_WC_CRM ) );

            // Запрос выбранных заказов
            $args = array(
                'limit'     => apply_filters( 'inwccrm_yandexnew_datatable_order_limit', self::ORDER_LIMIT ),
                'return'    => 'objects',
                'post__in'  => explode( ',', $idsString )
            );
            $orders = wc_get_orders( $args );
            if ( empty( $orders ) ) throw new NoOrdersException( __( 'Указанные заказы не найдены:', IN_WC_CRM ) . ' ' . $idsString );

            // Передача заказов
            $results = $api->send( $orders );

            // Заказы по ID
            $ordersById = array();
            foreach ( $orders as $order )
            {
                $ordersById[ $order->get_id() ] = $order;
            }

            $responseStr = '';
            foreach ( $results as $order_id => $result )
            {
                // Ошибка по заказу
                if ( empty( $result['created'] ) )
                {
                    $responseStr .= __( 'Заказ', IN_WC_CRM ) . ' #' . $order_id . ': ' . $result['error'] . PHP_EOL;
                    continue;
                }

                // Сообщение
                $resultMessage = __( 'Заказ', IN_WC_CRM ) . ' #' . $order_id . ': ' . __( 'создан', IN_WC_CRM );
                if ( ! empty( $result['pricing'] ) )
                {
                    $resultMessage .= '. ' . __( 'Стоимость доставки', IN_WC_CRM ) . ': ' . $result['pricing'];
                }
                $resultMessage .= '. ' . __( 'ID заявки', IN_WC_CRM ) . ': ' . $result['request_id'];

                // Результат в строку результата
                $responseStr .= ' ' . $resultMessage . PHP_EOL . PHP_EOL;

                $currentOrder = $ordersById[ $order_id ];
                $currentOrder->add_order_note(
                    __( 'Яндекс Доставка', IN_WC_CRM ) . ': ' . $resultMessage
                );

                // ID заявки в мета-поле заказа
                $currentOrder->update_meta_data( API::META_REQUEST_ID, $result['request_id'] );
                $currentOrder->save();

                // Установим новый статус
                $currentStatus = $currentOrder->get_status();
                $newStatus = apply_filters( 'inwccrm_yandexnew_set_order_status', $currentStatus, $currentOrder );
                if ( $currentStatus != $newStatus )
                {
                    $orderNote = apply_filters( 'inwccrm_yandexnew_set_order_status_note',
                        __( 'Статус заказа изменен после успешной отправки в Яндекс Доставку', IN_WC_CRM ),
                        $newStatus, $currentOrder );
                    $currentOrder->update_status( $newStatus, $orderNote );
                }
            }

            echo $responseStr;
            wp_die();
        }
        catch ( Exception $e )
        {
            // Возникли ошибки
            esc_html_e( 'Ошибка!', IN_WC_CRM );
            echo ' ', $e->getMessage();
            wp_die();
        }
    }
}
