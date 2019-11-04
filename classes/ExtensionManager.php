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
	private $extensions;

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
            $extensionClass = '\IN_WC_CRM\Extensions\\' . $extension;
            if ( ! class_exists( $extensionClass ) )
            {
                error_log( IN_WC_CRM . ': ' . $extension . __('.php отсуствует. Расширение игнорируется.', IN_WC_CRM ) );
                continue;
            }

            // Проверка интерфейса класса
            if ( ! in_array( 'IN_WC_CRM\Extensions\IExtension' , class_implements( $extensionClass ) ) )
            {
                error_log( IN_WC_CRM . ': ' . $extension . __(' не рекализует интерфейс IExtension. Расширение игнорируется.', IN_WC_CRM ) );
                continue;
            }
            
            // Пытаемся создать эккземпляр класса
            $this->extensions[$extension]['obj'] = new $extensionClass();

        }
    }
}