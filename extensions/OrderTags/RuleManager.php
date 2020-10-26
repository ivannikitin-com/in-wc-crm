<?php
/**
 * Класс RuleManager регистрирует тип данных и выполняет правила для заказов, 
 * назначая выбранную метку заказу
 */

namespace IN_WC_CRM\Extensions\OrderTags;
class RuleManager{
    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Регистрация таксономии
        $this->registerCPT();
        //$this->custom_post_type();
    }

    /**
     * Тип данных
     */
    const CPT = 'inwccrm_order_rule';

    /**
     * Регистрация типа данных правил
     */
    private function registerCPT(){
        $labels = array(
            'name'                  => _x( 'Правила пометки заказов', 'Post Type General Name', IN_WC_CRM ),
            'singular_name'         => _x( 'Правило пометки заказов', 'Post Type Singular Name', IN_WC_CRM ),
            'menu_name'             => __( 'Правила пометки заказов', IN_WC_CRM ),
            'name_admin_bar'        => __( 'Правила пометки заказов', IN_WC_CRM ),
            'archives'              => __( 'Правила пометки заказов', IN_WC_CRM ),
            'attributes'            => __( 'Атрибуты правила', IN_WC_CRM ),
            'parent_item_colon'     => __( 'Родительское правило:', IN_WC_CRM ),
            'all_items'             => __( 'Все правила', IN_WC_CRM ),
            'add_new_item'          => __( 'Добавить правило', IN_WC_CRM ),
            'add_new'               => __( 'Добавить', IN_WC_CRM ),
            'new_item'              => __( 'Новое правило', IN_WC_CRM ),
            'edit_item'             => __( 'Редактировать', IN_WC_CRM ),
            'update_item'           => __( 'Обновить', IN_WC_CRM ),
            'view_item'             => __( 'Просмотр', IN_WC_CRM ),
            'view_items'            => __( 'Просмотр', IN_WC_CRM ),
            'search_items'          => __( 'Поиск', IN_WC_CRM ),
            'not_found'             => __( 'Не найдено', IN_WC_CRM ),
            'not_found_in_trash'    => __( 'Не найдено в корзине', IN_WC_CRM ),
            'featured_image'        => __( 'Изображение', IN_WC_CRM ),
            'set_featured_image'    => __( 'Установить изображение правила', IN_WC_CRM ),
            'remove_featured_image' => __( 'Удалить изображение', IN_WC_CRM ),
            'use_featured_image'    => __( 'Использовать изображение', IN_WC_CRM ),
            'insert_into_item'      => __( 'Вставить в правило', IN_WC_CRM ),
            'uploaded_to_this_item' => __( 'Загрузить', IN_WC_CRM ),
            'items_list'            => __( 'Вставить', IN_WC_CRM ),
            'items_list_navigation' => __( 'Правила', IN_WC_CRM ),
            'filter_items_list'     => __( 'Фильтр правил', IN_WC_CRM ),
        );
        $args = array(
            'label'                 => __( 'Правило пометки заказов', IN_WC_CRM ),
            'description'           => __( 'Правила пометки заказов', IN_WC_CRM ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            //'menu_position'         => 5,
            //'menu_icon'             => 'dashicons-welcome-add-page',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
        );
        register_post_type( self::CPT, $args );
    }
}