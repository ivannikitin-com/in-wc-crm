<?php
/**
 * Класс Таблица клиентов
 */
namespace IN_WC_CRM;

class CustomerTag extends Base
{
	/**
	 * Конструктор плагина
	 * @param Plugin	$pligin	Ссылка на объект плагина
	 */
	public function __construct( $plugin )
	{	
		parent::__construct( $plugin );
		
		// Регистрация таксономии
		$this->register();
		
		// Добавляем в меню
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		
		// Мета-поля таксономии
		// https://www.smashingmagazine.com/2015/12/how-to-use-term-meta-data-in-wordpress/
		add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'addFormFields' ] );
		add_action( 'created_' . self::TAXONOMY, [ $this, 'saveFields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'editFormFields' ] );
		// Displaying The Term Meta Data In The Term List
	}

	/**
	 * Таксономия метки клиентов
	 */
	const TAXONOMY = 'in-wc-crm-tag';
	
	/**
	 * Регистрация таксономии
	 */
	public function register()
	{	
		$labels = array(
			'name'                       => _x( 'Метки клиентов', 'Taxonomy General Name', IN_WC_CRM ),
			'singular_name'              => _x( 'Метка клиента', 'Taxonomy Singular Name', IN_WC_CRM ),
			'menu_name'                  => __( 'Метки', IN_WC_CRM ),
			'all_items'                  => __( 'Все метки', IN_WC_CRM ),
			'parent_item'                => __( 'Родительская метка', IN_WC_CRM ),
			'parent_item_colon'          => __( 'Родительская метка:', IN_WC_CRM ),
			'new_item_name'              => __( 'Название метки', IN_WC_CRM ),
			'add_new_item'               => __( 'Добавить метку', IN_WC_CRM ),
			'edit_item'                  => __( 'Редактировать метку', IN_WC_CRM ),
			'update_item'                => __( 'Обновить метку', IN_WC_CRM ),
			'view_item'                  => __( 'Просмотр метки', IN_WC_CRM ),
			'separate_items_with_commas' => __( 'Метки, разделенные запятой', IN_WC_CRM ),
			'add_or_remove_items'        => __( 'Добавить или удалить метку', IN_WC_CRM ),
			'choose_from_most_used'      => __( 'Выберите из популярных меток', IN_WC_CRM ),
			'popular_items'              => __( 'Часто используемые метки', IN_WC_CRM ),
			'search_items'               => __( 'Поиск меток', IN_WC_CRM ),
			'not_found'                  => __( 'Метки не найдены', IN_WC_CRM ),
			'no_terms'                   => __( 'Меток нет', IN_WC_CRM ),
			'items_list'                 => __( 'Список меток', IN_WC_CRM ),
			'items_list_navigation'      => __( 'Навигация по списку меток', IN_WC_CRM ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => false,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'rewrite'                    => false,
		);
		register_taxonomy( self::TAXONOMY , array( 'user' ), $args );
	}
	
	/**
	 * Добавление в меню
	 */
	public function addAdminMenu()
	{
		add_submenu_page(
			IN_WC_CRM,
			__( 'Метки клиентов', IN_WC_CRM ),
			__( 'Метки', IN_WC_CRM ),
			'manage_options',
			'edit-tags.php?taxonomy=' . self::TAXONOMY
		);
	}
	
	/**
	 * Добавление полей в форму
	 */
	public function addFormFields()
	{
		
	}

	/**
	 * Сохранение полей
	 */
	public function saveFields()
	{
		
	}
	
	/**
	 * Сохранение полей
	 */
	public function editFormFields()
	{
		
	}	
	
} 