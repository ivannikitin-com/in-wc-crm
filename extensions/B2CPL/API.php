<?php
/**
 * API B2CPL
 */
namespace IN_WC_CRM\Extensions\B2CPL;
use \IN_WC_CRM\Plugin as Plugin;
use \WC_Order_Query as WC_Order_Query;
use \WC_Shipping as WC_Shipping;

require 'Exceptions.php';

class API
{
    /**
     * Лог файл
     */
    const LOGFILE = 'B2CPL-sending.log';

    /**
     * Параметры удаленного сервера и подключения
     */
    private $url;
    private $login;
    private $password;

    /**
     * Конструктор
     * @param string    $url                    URL удаленного сервера
     * @param string    $login                  Логин
     * @param string    $password               Пароль
     */
    public function __construct( $url, $login, $password )
    {
        $this->url = $url;
        $this->login = $login;
        $this->password = $password;

        // Убираем из URL финальный слеш
        if ( substr( $this->url, -1 ) == '/' )
            $this->url = substr( $this->url, 0, strlen( $this->url ) -1 );
    }


}