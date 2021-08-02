<?php
/**
 * Класс Менеджера расширений
 * Обеспечивает сканирование папки с расширениями, их проверку и инициализацию каждого из них
 */
namespace IN_WC_CRM;

class ExtensionManager
{
    /**
     * Папка в которой происходит поиск расширений
     */
    const EXTENSION_FOLDER = 'extensions';

	/**
	 * Массив с расширениями
	 */
	public $extensions;

	/**
	 * Конструктор
	 * @param string	$path	Путь к папке плагина 
	 */
	public function __construct( $path )
	{
        // Посмтроение списка расширений
        $folder = $path . self::EXTENSION_FOLDER;
        $files = array_diff( scandir( $folder ), array( '..', '.') );
        foreach ( $files as $file )
        {
            $fullName = $folder . '/' . $file;
            $extension = $file;
            if ( is_dir( $fullName ) )
            {
                // Это расширение в папке, определяем путь к файлу
                $fullName .= '/' . $file . '.php';
                if ( ! file_exists( $fullName ) )
                {
                    // Нет файла в папке с расширением
                    error_log( IN_WC_CRM . ': ' . $file . __('.php отсуствует. Расширение игнорируется.', IN_WC_CRM ) );
                    $extension = '';
                }
            }
            else
            {
                // Уберем '.php' из имени расширения.
                $extension = substr( $file, 0, -4);
            }

            // Подключаем расширение
            if ( $extension )
            {
                // Подключаем файл
                include_once( $fullName );
                // Запоминаем расширение
                $this->extensions[$extension] = array(
                    'path'  => $fullName,   // Полный путь к файлу
                    'obj'   => null,        // Объект расширения
                );
            }
        }

        // Хук на инициализацию расширения
        add_action( 'init', array( $this, 'init' ) );
        
        // Хук на создание или добавление основного меню
        add_action( 'admin_menu', array( $this, 'addMenu' ) );

        // Хук на подключение скриптов для админки
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    }

    public function admin_scripts(){
        wp_enqueue_script( 'savesettings', plugins_url( '../asserts/js/savesettings.js' , __FILE__ ), array( 'jquery'), null, true );
    }    

	/**
	 * Инициализация расширений
	 */
	public function init()
	{
        // Пройдем по всем расширениям
        foreach ( $this->extensions as $extension => $data )
        {
            // Проверка наличия класса
            $extensionClass = '\\IN_WC_CRM\Extensions\\' . $extension;
            if ( ! class_exists( $extensionClass ) )
            {
                error_log( IN_WC_CRM . ': ' . $extension . __('.php отсуствует. Расширение игнорируется.', IN_WC_CRM ) );
                continue;
            }

            // Проверка интерфейса класса
            if ( ! in_array( 'IN_WC_CRM\Extensions\IExtension' , class_implements( $extensionClass ) ) )
            {
                error_log( IN_WC_CRM . ': ' . $extension . __(' не реализует интерфейс IExtension. Расширение игнорируется.', IN_WC_CRM ) );
                continue;
            }
            
            // Пытаемся создать экземпляр класса
            $this->extensions[$extension]['obj'] = new $extensionClass();

        }
    }

	/**
	 * СОздание основного меню и добавление в него пунктов
	 */
	public function addMenu()
	{
        $menu_slug = IN_WC_CRM;
        add_menu_page(
            __( 'IN WC CRM', IN_WC_CRM),    // Текст, который будет использован в теге <title> на странице, относящейся к пункту меню
            __( 'CRM', IN_WC_CRM),          // Название пункта меню в сайдбаре админ-панели
            'manage_woocommerce',           // Права пользователя (возможности), необходимые чтобы пункт меню появился в списке 
            $menu_slug,                     // Уникальное название (slug), по которому затем можно обращаться к этому меню
            array( $this, 'adminMenuContent' ),    // Функция, которая выводит контент страницы пункта меню
            'dashicons-id',                 // Иконка для пункта меню
            58                              // Число определяющее позицию меню (после WooCommere)
        );

        // Обрабатываем все расширения и добавляем нужные в меню
        foreach( $this->extensions as $name => $data )
        {
            $extension = $data['obj'];
            if ( ! is_object( $extension ) )
                continue;

            // Какие интерфейсы реализует это расширение?
            $interfaces = class_implements( $extension );

            // Проверим ожидаемые интерфейсы
            if ( in_array( 'IN_WC_CRM\Extensions\IAdminPage' , $interfaces) )  
            {
                // Это расширение реализует админ.страницу

                if ( ! $extension->isEnabled() ) 
                    continue;
                
                add_submenu_page(
                    $menu_slug,                             // Название (slug) родительского меню
                    $extension->getAdminPageTitle(),        // Текст, который будет использован в теге title на странице
                    $extension->getAdminPageMenuTitle(),    // Текст, который будет использован как называние пункта меню
                    $extension->getAdminPageСapability(),   // Возможность пользователя, чтобы иметь доступ к меню
                    $extension->getAdminPageSlug(),         // Уникальное название (slug)
                    array( $extension, 'renderAdminPage' )  // Название функции которая будет вызваться, чтобы вывести контент создаваемой страницы
                );
            }
        }

        // Страница настроек
        add_submenu_page(
            $menu_slug,                                                 // Название (slug) родительского меню
            __( 'Настройки', IN_WC_CRM)  . ' ' . Plugin::get()->name,   // Текст, который будет использован в теге title на странице
            __( 'Настройки', IN_WC_CRM),                                // Текст, который будет использован как называние пункта меню
            'manage_woocommerce',                                       // Возможность пользователя, чтобы иметь доступ к меню
            IN_WC_CRM . '-settings',                                    // Уникальное название (slug)
            array( $this, 'showSettings' )                              // Название функции которая будет вызваться, чтобы вывести контент создаваемой страницы
        );
    }

	/**
	 * Вывод контента админ.меню
	 */
    public function adminMenuContent()
    {
        @include( Plugin::get()->path . 'views/extensions-list.php' );
    }

	/**
	 * Вывод контента страницы настроек
	 */
    public function showSettings()
    {
        // Если была передача, сохраняем все настройки
        if ( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' )
        {
            // Обрабатываем все расширения и добавляем нужные в меню
            foreach( $this->extensions as $name => $data )
            {
                $extension = $data['obj'];
                if ( ! is_object( $extension ) ) continue;
                $extension->saveSettings();               
            }
            
            // Перерисовываем страницу
            //wp_safe_redirect(  $_SERVER[ 'REQUEST_URI' ], 303 );
            //exit;
        }

        @include( Plugin::get()->path . 'views/settings.php' );
    }    
}