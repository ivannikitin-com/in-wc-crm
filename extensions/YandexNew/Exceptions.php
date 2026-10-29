<?php
/**
 * Исключения API Яндекс Доставки
 */
namespace IN_WC_CRM\Extensions\YandexNew;
use \Exception as Exception;

// Не указаны данные для подключения
class NoCredentialsException extends Exception {}
// Не переданы ID заказов
class EmptyOrderIDsException extends Exception {}
// Заказы не найдены
class NoOrdersException extends Exception {}
// Не заполнен обязательный параметр заказа
class NoRequiredParameter extends Exception {}
// Ошибка API Яндекс Доставки
class ApiException extends Exception {}
