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
class ApiException extends Exception
{
    // HTTP-код ответа API
    public $httpCode;

    /**
     * Конструктор
     * @param string    $message    Текст ошибки
     * @param int       $httpCode   HTTP-код ответа API
     */
    public function __construct( $message = '', $httpCode = 0 )
    {
        parent::__construct( $message );
        $this->httpCode = $httpCode;
    }
}
// В личном кабинете не создана отгрузка со склада
class NoScheduledPickupException extends Exception {}
