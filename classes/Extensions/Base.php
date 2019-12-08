<?php
/**
 * Базовый класс расширения
 */
namespace IN_WC_CRM\Extensions;

class Base implements IExtension
{
    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Читаем настройки
        $this->paramSection = str_replace( '\\', '-',  get_class( $this ) );
        $this->enabledPamam = $this->paramSection . '-enabled';
        $this->settings = ( $this->hasSettings() ) ? $this->getSettings() : array();
    }

    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return '';
    }

    /* -------------------- Реализация настроек расширения -------------------- */
    /**
     * Параметры расширения
     * @var mixed
     */
    protected $settings;

    /**
     * Секция параметров для сохранения
     * @var mixed
     */
    protected $paramSection;

    /**
     * Параметр доступности расширения
     * @var mixed
     */
    protected $enabledPamam;


    /**
     * Возвращает true если этому расширению требуются настройки
     * @return bool
     */
    public function hasSettings()
    {
        return false;
    }

    /**
     * Возвращает true если расширение активно
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->getParam( $this->enabledPamam, true);
    }

    /**
     * Возвращает массив настроек
     * @return mixed
     */
    public function getSettings()
    {
        $this->settings = get_option( $this->paramSection );
        return ( $this->settings ) ? $this->settings : array( $this->enabledPamam => true );
    }

    /**
     * Сохраняет массив настроек
     * @paran mixed $settings массив настроек
     */
    public function saveSettings()
    {
        $this->settings[ $this->enabledPamam ] = isset( $_POST[ $this->enabledPamam ] ); 
        return update_option( $this->paramSection, $this->settings );
    }
    
    /**
     * Возвращает параметр или указанное дефолтовое значение
     * @paran string $paramName Название параметра
     * @param mixed $default Знавчение по умолчанию
     */
    public function getParam( $paramName, $default = '' )
    {
        // Если параметра нет в настройках, вернем default
        if ( ! array_key_exists( $paramName, $this->settings ) )
            return $default;

        // Если параметр пустой вернем дефолтовое значение
        if ( empty( $this->settings[ $paramName ] ) && ! is_bool( $this->settings[ $paramName ] ) )
            return $default;

        return $this->settings[ $paramName ];
    }

    /**
     * Показывает секцию настроек
     */
    public function showSettings()
    {
        // Этот метод перекрывается потомками
    } 
}