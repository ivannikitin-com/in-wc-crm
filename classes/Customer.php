<?php
/**
 * Класс Клиент
 * Расширяет класс WC_Customer
 */
namespace IN_WC_CRM;
use \WC_Customer as WC_Customer;

class Customer extends WC_Customer
{
	/**
	 * Конструктор
	 * @param int	$id		Идентификатор клиента
	 */
	public function __construct( $id )
	{	
		parent::__construct( $id );
	}
	
	/**
	 * Возвращает адрес в виде строки
	 * если указан адрес доставки, возвращается именно он, иначе юридический адрес
	 */
	public function getAddress()
	{
		$address = array();
		$result = array();
		if ( empty( $this->get_shipping_address ) )
		{
			// Используем юридический адрес
			$address = $this->get_billing();			
		}
		else
		{
			// Используем адрес доставки
			$address = $this->get_shipping();		
		}
		
		if ( ! empty( $address['postcode'] ) ) $result[] = $address['postcode'];
		if ( ! empty( $address['country'] ) ) $result[] = $address['country'];
		if ( ! empty( $address['state'] ) ) $result[] = $address['state'];
		if ( ! empty( $address['city'] ) ) $result[] = $address['city'];
		if ( ! empty( $address['address_1'] ) ) $result[] = $address['address_1'];
		if ( ! empty( $address['address_2'] ) ) $result[] = $address['address_2'];
		
		return implode( ', ', $result );
	}
} 