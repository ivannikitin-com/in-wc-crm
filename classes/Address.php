<?php
/**
 * Класс реализует хранение и отображение адресов
 */
namespace IN_WC_CRM;

class Address
{
	/**
	 * Почтовый индекс
	 */
	public $postcode;
	
	/**
	 * Страна
	 */
	public $country;
	
	/**
	 * Регион
	 */
	public $state;
	
	/**
	 * Город
	 */
	public $city;
	
	/**
	 * Строка 1
	 */
	public $address1;

	/**
	 * Строка 2
	 */
	public $address2;

	
	/**
	 * Конструктор плагина
	 * @param string	$postcode	Почтовый индекс
	 * @param string	$country	Страна
	 * @param string	$state		Регион
	 * @param string	$city		Город
	 * @param string	$address1	Строка 1
	 * @param string	$address2	Строка 2
	 */
	public function __construct( $postcode='', $country='', $state='', $city='', $address1='', $address2='' )
	{	
		$this->postcode	= $postcode;	
		$this->country	= $country;	
		$this->state	= $state;	
		$this->city		= $city;	
		$this->address1	= $address1;	
		$this->address2	= $address2;	
	}
	
	/**
	 * Возвращает true если адрес незаполнен
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty( $city ) && empty( $address1 );
	}
	
	/**
	 * Возвращает адрес в виде строки
	 * @return string
	 */
	public function __toString()
	{
		$parts = array();
		if ( ! empty( $this->postcode ) ) $parts[] = $this->postcode;
		if ( ! empty( $this->country ) ) $parts[] = $this->country;
		if ( ! empty( $this->state ) ) $parts[] = $this->state;
		if ( ! empty( $this->city ) ) $parts[] = $this->city;
		if ( ! empty( $this->address1 ) ) $parts[] = $this->address1;
		if ( ! empty( $this->address2 ) ) $parts[] = $this->address2;
		
		return implode(', ', $parts);
	}
} 