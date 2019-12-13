# Расширение PickPoint 
Производит передачу реестров заказов для отправки по API PickPoint.

## Фильтры и хуки
### Данные в DataTable (таблице данных заказов)
Выполняется для каждого ряда
* `inwccrm_pickpoint_datatable_id` -- ID заказа
* `inwccrm_pickpoint_datatable_date` -- Дата заказа
* `inwccrm_pickpoint_datatable_customer` -- Клиент
* `inwccrm_pickpoint_datatable_total` -- Общая стоиомость заказа
* `inwccrm_pickpoint_datatable_payment_method` -- Способ обплаты
* `inwccrm_pickpoint_datatable_shipping_method` -- Метод доставки
* `inwccrm_pickpoint_datatable_shipping_cost` -- Стоимость доставки


### Параметры запроса
* `inwccrm_pickpoint_datatable_order_limit` -- Число запоащшиваемых строк из БД
* `inwccrm_pickpoint_requestId` -- Идентификатор запроса, используемый для ответа
* `inwccrm_pickpoint_ikn` -- ИКН номер договора (10 символов)

### Данные запроса
* `inwccrm_pickpoint_postamatNumber` -- номер постомата
* `inwccrm_pickpoint_postageType` -- Тип отправления
* `inwccrm_pickpoint_senderCityName` -- Город доставки
* `inwccrm_pickpoint_senderRegionName` -- Регион доставки
* `inwccrm_pickpoint_json_SubEncloses` -- Состав отправления в JSON
* `inwccrm_pickpoint_json_places` -- Позиции отправления в JSON
* `inwccrm_pickpoint_json_shipment` -- Все данные отправления в JSON

### Данные магазина
* `inwccrm_pickpoint_shopName` -- Название магазина
* `inwccrm_pickpoint_store_address` -- Адрес магазина
* `inwccrm_pickpoint_store_address_2` -- Строка 2 адреса магазина
* `inwccrm_pickpoint_store_city` -- Город магазина
* `inwccrm_pickpoint_store_postcode` -- Индекс магазина
* `inwccrm_pickpoint_store_raw_country` -- Страна магазина без деления
* `inwccrm_pickpoint_store_country` -- Страна магазина
* `inwccrm_pickpoint_store_state` -- Область магазина
* `inwccrm_pickpoint_shopManagerName` -- Ответственное лицо в магазине
* `inwccrm_pickpoint_shopOrganization` -- Организация магазина
* `inwccrm_pickpoint_shopPhone` -- Телефон магазина
* `inwccrm_pickpoint_shopComment` --  Комментарий магазина




