<?php
/**
 * Класс Plugin
 * Основной класс плагина. 
 * Является singleton, то есть обращение из любого места должно быть таким Plugin::get()
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
     * @var Plugin
     */
    private static $instance = null;

	/**
     * @var ExtensionManager
     */
    public $extensionManager = null;


	/**
	 * Иницмиализация плагина
	 * Должна вызываться только один раз. 
	 * @param string	$path	Путь к папке плагина
	 * @param string	$url	URL к папке плагина
	 * @param string	$meta	Мета-данные плагина
	 */
	public static function init( $path, $url, $meta )
	{
		if ( static::$instance !== null )
			throw new \Exception( __('Объект Plugin уже инициализирован!', IN_WC_CRM) );
		
		static::$instance = new static( $path, $url, $meta );
	}

	/**
	 * Возвращает объект плагина
	 * @return	Plugin
	 */
	public static function get()
	{
		if ( static::$instance === null )
			throw new \Exception( __('Объект Plugin не инициализирован!', IN_WC_CRM) ); 
			
		return static::$instance;
	}
	
	/**
	 * Конструктор плагина
	 * @param string	$path	Путь к папке плагина
	 * @param string	$url	URL к папке плагина
	 * @param string	$meta	Мета-данные плагина
	 */
	private function __construct( $path, $url, $meta )
	{
		// Инициализация свойств
		$this->path 	= $path;
		$this->url 		= $url;
		$this->name 	= $meta[ 'Name' ];
		$this->version 	= $meta[ 'Version' ];
		$this->extensionManager = new ExtensionManager( $path );
		
		// Хуки
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'wp_init' ) );
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
	 * Хук init
	 */
	public function wp_init()
	{
		// Проверка наличия WC		
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
		{
			add_action( 'admin_notices', array( $this, 'showNoticeNoWC' ) );
			return;
		}
	}
	
	/**
	 * Предупреждение об отсутствии WooCommerce
	 */
	public function showNoticeNoWC()
	{
		echo '<div class="notice notice-warning no-woocommerce"><p>';
		printf( 
			esc_html__( 'Для работы плагина "%s" требуется установить и активировать плагин WooCommerce.', IN_WC_CRM ), 
			$this->name . ' ' . $this->version  
		);
		_e( 'В настоящий момент все функции плагина деактивированы.', IN_WC_CRM );
		echo '</p></div>';
	}

	/**
	 * Имя файла лога
	 */
	const LOGFILE = 'in-wc-crm.log';
	
	/**
	 * Записывает сообщение в лог, если включена отладка
	 * @param mixed $message	Сообщение или объект
	 * @param string $logfile	Имя лога в которй пишется сообщение. Если пусто -- в общий лог WP
	 */
	public function log( $message, $logfile = self::LOGFILE )
	{
		if ( WP_DEBUG )
		{
			if ( !empty( $logfile ) ) $logfile = $this->path . $logfile;
			error_log( IN_WC_CRM . 'LOG: ' . $logfile );
			
			if (is_array($message) || is_object($message)) 
			{
                if ( empty( $logfile ) )
				{
					error_log( IN_WC_CRM . ': ' . print_r( $message, true ) );
				}
				else
				{
					file_put_contents( $logfile, 
						'[' . date('d.m.Y H:i:s') . '] ' . ': ' . print_r( $message, true ) . PHP_EOL, 
						FILE_APPEND );	
				}
			} 
			else 
			{
                if ( empty( $logfile ) )
				{
					error_log( IN_WC_CRM . ': ' . $message );
				}
				else
				{
					file_put_contents( $logfile, 
						'[' . date('d.m.Y H:i:s') . '] ' . ': ' . $message . PHP_EOL, 
						FILE_APPEND );	
				}				
            }			
		}
	}

}
