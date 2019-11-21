<?php
/**
 * Класс реализует получение данных WC средствами SQL
 */
namespace IN_WC_CRM\Extensions;


class WC_Data_SQL extends WC_Data
{
    /**
     * Возвращает заказы с требуемыми статусами
     * @param mixed $statuses   Массив со статусами заказов
     * @return mixed            Массив с объектами заказов
     */
    public function getActiveOrders( $statuses = ['processing'] )
    {
        // Данные БД
        global $wpdb;

        // Очистим массив заказов
        $this->clearOrders();
        $this->clearItems();

        // Выполним запрос
        $db_results = $wpdb->get_results( $this->getSQL() );

        return $db_results;
        
        // Проход по заказам
        $currentOrderId = 0;
        $previousOrder = null;
        foreach ( $db_results as $order )
        {
            $this->addItem( $sku, $title, $quo );

            // Сохраним текущую запись
            $previousOrder = $order;
        }

        return $this->orders;
    }

    /**
     * Формирует SQL запрос для получения данных по заказам и доставке
     */
    private function getSQL()
    {
        // Данные БД
        global $wpdb;

        // Имена таблиц
        $posts                          = $wpdb->prefix . 'posts';
        $postmeta                       = $wpdb->prefix . 'postmeta';
        $woocommerce_order_items        = $wpdb->prefix . 'woocommerce_order_items';
        $woocommerce_order_itemmeta     = $wpdb->prefix . 'woocommerce_order_itemmeta';

        // Формируем запрос
        $sql = <<<SQL
-- Получаем список всех заказов с элементами заказа и SKU
SELECT * 
    FROM (
        SELECT 
            -- Данные заказа
            order.id,
            order.post_status AS order_status,
            order.post_date AS order_date,
            -- Данные клиента
            order_meta._billing_email AS email,
            order_meta._billing_phone AS phone,
            order_meta._billing_first_name AS _billing_first_name,
            order_meta._billing_last_name AS billing_last_name,
            order_meta._billing_company AS billing_company,
            order_meta._billing_address_1 AS billing_address_1,
            order_meta._billing_city AS billing_city,
            order_meta._billing_state AS billing_state,
            order_meta._billing_postcode AS billing_postcode,
            order_meta._billing_country AS billing_country,
            -- Данные доставки
            order_meta._shipping_first_name AS shipping_first_name,
            order_meta._shipping_last_name AS shipping_last_name,
            order_meta._shipping_address_1 AS shipping_address,
            order_meta._shipping_city AS shipping_city,
            order_meta._shipping_state AS shipping_state,
            order_meta._shipping_postcode AS shipping_postcode,
            order_meta._shipping_country AS shipping_country
        FROM $posts AS `order`
            INNER JOIN 
                (
                    SELECT post_id AS id,
                        -- Поля клиента
                        MAX(CASE WHEN meta_key = '_billing_email' THEN meta_value END) AS '_billing_email',
                        MAX(CASE WHEN meta_key = '_billing_phone' THEN meta_value END) AS '_billing_phone',
                        MAX(CASE WHEN meta_key = '_billing_first_name' THEN meta_value END) AS '_billing_first_name',
                        MAX(CASE WHEN meta_key = '_billing_last_name' THEN meta_value END) AS '_billing_last_name',
                        MAX(CASE WHEN meta_key = '_billing_company' THEN meta_value END) AS '_billing_company',
                        MAX(CASE WHEN meta_key = '_billing_address_1' THEN meta_value END) AS '_billing_address_1',
                        MAX(CASE WHEN meta_key = '_billing_city' THEN meta_value END) AS '_billing_city',
                        MAX(CASE WHEN meta_key = '_billing_state' THEN meta_value END) AS '_billing_state',
                        MAX(CASE WHEN meta_key = '_billing_postcode' THEN meta_value END) AS '_billing_postcode',
                        MAX(CASE WHEN meta_key = '_billing_country' THEN meta_value END) AS '_billing_country',
                        -- Поля доставки
                        MAX(CASE WHEN meta_key = '_shipping_first_name' THEN meta_value END) AS '_shipping_first_name',
                        MAX(CASE WHEN meta_key = '_shipping_last_name' THEN meta_value END) AS '_shipping_last_name',
                        MAX(CASE WHEN meta_key = '_shipping_address_1' THEN meta_value END) AS '_shipping_address_1',
                        MAX(CASE WHEN meta_key = '_shipping_city' THEN meta_value END) AS '_shipping_city',
                        MAX(CASE WHEN meta_key = '_shipping_state' THEN meta_value END) AS '_shipping_state',
                        MAX(CASE WHEN meta_key = '_shipping_postcode' THEN meta_value END) AS '_shipping_postcode',
                        MAX(CASE WHEN meta_key = '_shipping_country' THEN meta_value END) AS '_shipping_country'
                    FROM $postmeta 
                    GROUP BY 1 
                ) AS `order_meta`
            ON order.id = order_meta.id
        WHERE order.post_type = 'shop_order'
          AND order.post_date BETWEEN '2019-11-04' AND '2019-11-15'                         -- Требуемый диапазон дат заказа
          AND post_status IN ('wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending')  -- Требуемый статус заказа
    ) AS orders
    INNER JOIN (
        SELECT 
        order_items.order_id,
        order_items.item_id,
        order_items.title,
        order_items.shipping,
        items_meta._qty AS qty,
        postmeta.sku
    FROM (
            SELECT 
                order_id,
                order_item_id AS item_id,
                MAX(CASE WHEN order_item_type = 'line_item' THEN order_item_name END) AS title,
                MAX(CASE WHEN order_item_type = 'shipping' THEN order_item_name END) AS shipping
            FROM $woocommerce_order_items  
            GROUP BY 1
        ) AS order_items 
    INNER JOIN (
            SELECT 
                order_item_id AS item_id,
                MAX(CASE WHEN meta_key = '_qty' THEN meta_value END) AS '_qty'
            FROM $woocommerce_order_itemmeta
            GROUP BY 1
        ) AS items_meta
        ON order_items.item_id = items_meta.item_id 
    -- Добавляем SKU если он есть
    LEFT JOIN (
            SELECT 
                post_id,
                MAX(CASE WHEN meta_key = '_sku' THEN meta_value END) AS 'sku'
            FROM $postmeta 
            GROUP BY 1        
    ) AS postmeta
        ON items_meta.item_id = postmeta.post_id
    -- 
    ) AS items
    ON orders.id = items.order_id
-- Конец запроса
SQL;
        return $sql;
    }
}