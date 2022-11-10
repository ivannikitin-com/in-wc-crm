# Простая CRM для WooCommerce

## Версия 1.8.0

Цель данного плагина -- реализация простого способа управления клиентами, заказами и т.п. в WooCommerce.

## Схема плагина
Плагин условно разделен на ядро и расширения. Ядро плагина выполняет следующие функции:
1. Правильную загрузку базовых классов
2. Общее управление системой настроек
3. Загрузку и инициализацию расширений

**Все функции должны реализовываться расширениями плагина!**

### Ядро плагина###

Ядро располагается в папке classes.

### Расширения

Расширения могут быть построены двумя способами:
1. Наследование от класса ```\IN_WC_CRM\Extensions\Base``` -- рекомендуется
2. Своим собственным произвольным классом, реализующим интерфейс ```\IN_WC_CRM\Extensions\IExtension```

Все расширения должны располагаться в папке extensions. Если для расширения требуется более одного файла, то можно создать папку с именем расширения и в ней файл с именем расширения, например  
extensions\MyExt\MyExt.php

Расширения инициализируются в правильное время, то есть конструктор расширения будет вызван во время исполнения хука ```init```.

## История версий

### 1.8.1
* Скорректирована реализация фильтра способов доставки в списке заказов

### 1.8.0
* Интеграция с сервисом 5Post

### 1.7.1
* Коррекция хука для расширения OrderTags

### 1.7
* Интеграция с сервисом Boxberry

### 1.6
* Добавлено расширение OrderTags

### 1.5
* Добавлено расширение TopDelivery

### 1.4
* Добавлено расширение PDFInvoices, которое добавляет возможность печати счетов заказов через плагин [WooCommerce PDF Invoices & Packing Slips](https://ru.wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/)
* Добавлено расширение B2CPL, которое добавляет возможность выгрузки заказов в [B2CPL](https://b2cpl.ru/)

### 1.3
* Добавлено расширение DeliveryFields, которое добавляет в заказ поля со временем и датой желаемой доставки

### 1.2
* Добавлено расширение Orders2Excel для выгрузки элементов заказа в таблицу Excel.

### 1.1
* Полностью переделана передача в PickPoint
* Таблица с заказами формируется отдельным расширением, PickPoint -- отдельным.

### 1.0
* Базовый код плагина и передача в PickPoint

