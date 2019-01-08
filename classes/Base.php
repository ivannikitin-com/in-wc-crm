<?php
/**
 * Базовый класс компонентов клиентов
 */
namespace IN_WC_CRM;

class Base
{
	/**
	 * Объект плагина
	 */
	protected $plugin;
	
	/**
	 * Конструктор плагина
	 * @param Plugin	$plugin	Ссылка на объект плагина
	 */
	public function __construct( $plugin )
	{	
		$this->plugin = $plugin;	
	}
} 