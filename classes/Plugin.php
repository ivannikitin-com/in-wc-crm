<?php
/**
 * Класс Plugin
 * Основной класс плагина. 
 * Служит платформой для всех остальных, отвечает за правильную инициализацию. 
 * Конструкторы всех остальных классов вызываются в правильное время.
 */
namespace IN_WC_CRM;

class Plugin
{
	/**
	 * Путь к папке плагина
	 */
	public $path;
	
	/**
	 * URL к папке плагина
	 */
	public $url;
	
	/**
	 * Название плагина
	 */
	public $name;

	/**
	 * Версия плагина
	 */
	public $version;	
	
	/**
	 * Конструктор плагина
	 * @param string	$path	Путь к папке плагина
	 * @param string	$url	URL к папке плагина
	 * @param string	$meta	Мета-данные плагина
	 */
	public function __construct( $path, $url, $meta )
	{
		// Инициализация свойств
		$this->path 	= $path;
		$this->url 		= $url;
		$this->name 	= $meta[ 'Name' ];
		$this->version 	= $meta[ 'Version' ];
		
		// Хуки
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );
	}
	
	/**
	 * Плагины загружены
	 */
	public function plugins_loaded()
	{
		// Локализация
		load_plugin_textdomain( IN_WC_CRM, false, basename( dirname( __FILE__ ) ) . '/lang' );
	}
	
	/**
	 * Список клиентов
	 */
	public $customerList;
	
	/**
	 * Инициализация компонентов плагина
	 */
	public function init()
	{
		// Проверка наличия WC		
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
		{
			add_action( 'admin_notices', array( $this, 'showNoticeNoWC' ) );
			return;
		}
		
		// Подключаем классы плагина. 
		// Это делается только после проверки включения WooCommerce, 
		// потому что в коде используются его классы
		require( $this->path . 'classes/Base.php' );
		require( $this->path . 'classes/Customer.php' );
		require( $this->path . 'classes/CustomerList.php' );
		require( $this->path . 'classes/CustomerTable.php' );		
		
		// Создаем таблицу клиентов
		$this->customerList = new CustomerList( $this );
	}
	
	/**
	 * Предупреждение об отсутствии WooCommerce
	 */
	public function showNoticeNoWC()
	{
		echo '<div class="notice notice-warning no-woocommerce"><p>';
		printf( esc_html__( 'Для работы плагина %s требуется установить и активировать плагин WooCommerce.', IN_WC_CRM ), 
			$this->name . ' ' . $this->version  );
		_e( 'В настоящий момент все функции плагина деактивированы.', IN_WC_CRM );
		echo '</p></div>';
	}
	
	/**
	 * Метод возвращает имя файала с HTML шаблоном для вывода
	 * @param string	$name	Название метода или название шаблона для вывода
	 * @return string	полный путь к файлу представления
	 */
	public function getView( $name )
	{	
		$viewFile = $this->path . 'views/' . $name . '.php';
		if ( ! file_exists( $viewFile ) )
			throw new FileNotFoundException( __( 'Не найден файл представления' . ' ' . $viewFile, IN_WC_CRM ) );
		return $viewFile;
	}
	
	
}
