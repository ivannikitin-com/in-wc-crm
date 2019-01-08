<?php
/**
 * Класс Таблица клиентов
 */
namespace IN_WC_CRM;

class CustomerList extends Base
{
	/**
	 * Таблица вывода клиентов
	 */
	private $customerTable;
	
	/**
	 * Конструктор плагина
	 * @param Plugin	$pligin	Ссылка на объект плагина
	 */
	public function __construct( $plugin )
	{	
		parent::__construct( $plugin );
		
		// Создаем меню
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		
		
		//$this->customerTable = new CustomerTable();
	}
	
	public static function set_screen( $status, $option, $value ) {
		return $value;
	}
	
	/**
	 * Screen options
	 */
	public function screen_option() {
		$option = 'per_page';
		$args   = [
			'label'   => 'Customers',
			'default' => 5,
			'option'  => 'customers_per_page'
		];
		add_screen_option( $option, $args );
		
		// Таблица со списком клиентов
		$this->customerTable = new CustomerTable();
	}	
	
	/**
	 * Создает меню в админке
	 */
	public function addAdminMenu()
	{
		$hook = add_menu_page(
			__( 'IN WooCommerce CRM', IN_WC_CRM),		// Название страницы
			__( 'Клиенты', IN_WC_CRM),					// Название меню
			'manage_options',							// Права на управление
			IN_WC_CRM,									// Слаг меню
			[ $this, 'showCustomerList' ],				// Функция вывода страницы
			'dashicons-money',							// Иконка меню
			58											// Позиция пункта
		);
		add_action( "load-$hook", [ $this, 'screen_option' ] );		
	}

	/**
	 * Выводит страницу CRM со списком клиентов
	 */
	public function showCustomerList()
	{
		include( $this->plugin->getView( 'showCustomerList' ) );
	}		
	
} 