<?php
/**
 * Класс Таблица клиентов
 */
namespace IN_WC_CRM;
use \WP_User_Query as WP_User_Query;

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
		
		// Сброс кэша клиентов
		do_action('user_register', 	[ $this, 'flushCustomersCache' ], 10, 1 );
		do_action('profile_update', [ $this, 'flushCustomersCache' ], 10, 2 );
		
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
		$this->customerTable = new CustomerTable( $this->plugin );
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
	
	
	const CACHE_CUSTOMERS = 'in-wc-crm-customers';
	
	/**
	 * Возвращает список клиентов
	 * @param string $search 	Строка поиска клиента
	 */
	public function getCustomers( $search='' )
	{
		// Набор пользователей по результату поиска
		$cacheSet = md5( $search );
		
		// Запрос кэша
		$customers = wp_cache_get( self::CACHE_CUSTOMERS );
		if ( $customers !== false && isset( $customers[ $cacheSet ] ) )
			return $customers[ $cacheSet ];
		
		// Массив результатов для кэша
		if ( ! is_array( $customers ) ) 
			$customers = array();
		
		// WP_User_Query arguments
		$args = array(
			'role'           => 'Customer',
			'fields'         => 'ID',
		);

		// The User Query
		$user_query = new WP_User_Query( $args );
		$ids = $user_query->get_results();
		
		// Создаем массив результатов
		foreach ( $ids as $id )
		{
			$customers[ $cacheSet ][] = new Customer( $id );
		}
		
		// Сохранение к кэш
		wp_cache_set( self::CACHE_CUSTOMERS, $customers );
		
		// Возврат результатов
		return $customers[ $cacheSet ];
	}	
	
	/**
	 * Сбрасывсает кэш клиентов
	 */
	public function flushCustomersCache( $arg1='', $arg2='')
	{
		wp_cache_delete( self::CACHE_CUSTOMERS );
	}
}