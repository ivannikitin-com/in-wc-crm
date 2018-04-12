<?php
/**
* Plugin Name: IN WooCommerce CRM
* Plugin URI: https://github.com/ivannikitin-com/in-wc-crm
* Description: Простая CRM система для WordPress и WooCOmmerce
* Version: 1.0
* Author: Иван Никитин и партнеры
* Author URI: https://ivannikitin.com
* License:     GPL3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: in-wc-crm
* Domain Path: /lang

Copyright 2018  Ivan Nikitin  (email: ivan.g.nikitin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* Глобальные константы плагина */
define( 'IN_WC_CRM', 		'in-wc-crm' );	// Text Domain

/* Основной класс плагина */
class IN_WC_CRM_Plugin
{
	/**
	 * Версия
	 */
	public $version;
	
	/**
	 * Путь к папке плагина
	 */
	public $path;
	
	/**
	 * URL к папке плагина
	 */
	public $url;
	
	/**
	 * Конструктор плагина
	 */
	public function __construct()
	{
		// Инициализация свойств
		$this->version = '1.0';
		$this->path = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );
		
		// Автозагрузка классов
		spl_autoload_register( array( $this, 'autoload' ) );
		
		// Хуки
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );
	}
	
    /**
     * Автозагрузка лассов по требованию
     *
     * @param string $class Требуемый класс
     */
    function autoload( $class ) 
	{
        $classPrefix = 'IN_WC_CRM_';
	
		// Если это не наш класс, ничего не делаем...
		if ( strpos( $class, $classPrefix ) === false ) 
			return;
		
		$fileName   = $this->path . 'inc/classes/' . strtolower( str_replace( $classPrefix, '', $class ) ) . '.php';
		if ( file_exists( $fileName ) ) 
		{
			require_once $fileName;
		}
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
		
	}
	
	/**
	 * Предупреждение об отсутствии WooCommerce
	 */
	public function showNoticeNoWC()
	{ ?>
    <div class="notice notice-warning no-woocommerce">
        <p><?php _e( 'Для работы плагина "Аукцион заказов WooCommerce" требуется установить и активировать плагин WooCommerce.', IN_WC_CRM ); ?></p>
        <p><?php _e( 'В настоящий момент все функции плагина деактивированы.', IN_WC_CRM ); ?></p>
    </div>		
<?php }	
	
}

/* Запуск плагина */
new IN_WC_CRM_Plugin();