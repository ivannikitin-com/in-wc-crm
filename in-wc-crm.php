<?php
/**
* Plugin Name: IN WooCommerce CRM
* Plugin URI: https://ivannikitin-com.github.io/in-wc-crm/
* Description: Простая CRM система для WordPress и WooCommerce
* Version: 1.8.1
* Author: Иван Никитин и партнеры
* Author URI: https://ivannikitin.com
* License:     GPL3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: in-wc-crm
* Domain Path: /lang
* Namespace: IN_WC_CRM

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

/* Файлы ядра плагина */
require_once( 'classes/Plugin.php' );
require_once( 'classes/ExtensionManager.php' );
require_once( 'classes/Extensions/IExtension.php' );
require_once( 'classes/Extensions/IAdminPage.php' );
require_once( 'classes/Extensions/Base.php' );
require_once( 'classes/Extensions/BaseAdminPage/BaseAdminPage.php' );

/* Запуск плагина */
\IN_WC_CRM\Plugin::init( 
	plugin_dir_path( __FILE__ ), 			// Путь к папке плагина
	plugin_dir_url( __FILE__ ), 			// URL к папке плагина
	get_file_data( __FILE__, array(			// Мета-данные из заголовка плагина
			'Name' 		=> 'Plugin Name',	// Название Пдагина
			'Version' 	=> 'Version',		// Версия плагина
		) ) );