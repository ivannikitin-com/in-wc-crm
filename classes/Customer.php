<?php
/**
 * Класс реализует хранение и отображение адресов
 */
namespace IN_WC_CRM;

class Customer
{
	/**
	 * ID клиента
	 */
	public $id;
	
	/**
	 * Имя клиента
	 */
	public $name;
	
	/**
	 * E-mail
	 */
	public $email;
	
	/**
	 * Телефон
	 */
	public $phone;
	
	/**
	 * Юридический адрес
	 */
	public $billingAddress;

	/**
	 * Адрес доставки
	 */
	public $shippingAddress;

	
	/**
	 * Конструктор плагина
	 * @param int		$id				ID клиента
	 */
	public function __construct( $id=0 )
	{	
		$this->id = $id;		
	}
	
}