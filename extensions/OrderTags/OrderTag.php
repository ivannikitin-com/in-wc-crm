<?php
/**
 * Класс OrderTag регистрирует и назначает таксономию на заказы WC
 * Реализует управление метками в админке WC
 * Реализует сервисные методы добавления и удаления меток
 */

namespace IN_WC_CRM\Extensions\OrderTags;
class OrderTag{
    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Регистрация таксономии
        $this->registerTaxonomy();

		if ( is_admin() ) {

			add_action( self::TAXOMOMY . '_add_form_fields',  array( $this, 'create_screen_fields'), 10, 1 );
			add_action( self::TAXOMOMY . '_edit_form_fields', array( $this, 'edit_screen_fields' ),  10, 2 );

			add_action( 'created_' . self::TAXOMOMY, array( $this, 'save_data' ), 10, 1 );
			add_action( 'edited_' . self::TAXOMOMY,  array( $this, 'save_data' ), 10, 1 );

		}        
    }

    /**
     * Таксономия
     */
    const TAXOMOMY = 'inwccrm_wc_order_tag';

    /**
     * Регистрация таксономии
     */
    public function registerTaxonomy() {
        
        $labels = array(
            'name'                       => _x( 'Метки заказов', 'Taxonomy General Name', IN_WC_CRM ),
            'singular_name'              => _x( 'Метка заказа', 'Taxonomy Singular Name', IN_WC_CRM ),
            'menu_name'                  => __( 'Метки заказов', IN_WC_CRM ),
            'all_items'                  => __( 'Все метки', IN_WC_CRM ),
            'parent_item'                => __( 'Родительская метка', IN_WC_CRM ),
            'parent_item_colon'          => __( 'Родительская метка:', IN_WC_CRM ),
            'new_item_name'              => __( 'Новая метка', IN_WC_CRM ),
            'add_new_item'               => __( 'Добавить новую метку', IN_WC_CRM ),
            'edit_item'                  => __( 'Релактировать', IN_WC_CRM ),
            'update_item'                => __( 'Обновить', IN_WC_CRM ),
            'view_item'                  => __( 'Просмотр', IN_WC_CRM ),
            'separate_items_with_commas' => __( 'Разделите метки запятыми', IN_WC_CRM ),
            'add_or_remove_items'        => __( 'Добавить или удалить метки', IN_WC_CRM ),
            'choose_from_most_used'      => __( 'Выберите самые популярные метки', IN_WC_CRM ),
            'popular_items'              => __( 'Часто используемые метки', IN_WC_CRM ),
            'search_items'               => __( 'Поиск меток', IN_WC_CRM ),
            'not_found'                  => __( 'Не найдено', IN_WC_CRM ),
            'no_terms'                   => __( 'Нет меток', IN_WC_CRM ),
            'items_list'                 => __( 'Список меток', IN_WC_CRM ),
            'items_list_navigation'      => __( 'Список меток', IN_WC_CRM ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
        );
        register_taxonomy( self::TAXOMOMY, array( 'shop_order' ), $args );
    }




    /**
     * Поля для ввода мета-полей метки
     */
	public function create_screen_fields( $taxonomy ) {

		// Set default values.
		$custom_inwccrm_wc_order_tag_color = '';
		$custom_inwccrm_wc_order_tag_color_bg = '';

		// Form fields.
		echo '<div class="form-field term-custom_inwccrm_wc_order_tag_color-wrap">';
		echo '	<label for="custom_inwccrm_wc_order_tag_color">' . __( 'Цвет', 'text_domain' ) . '</label>';
		echo '	<input type="text" id="custom_inwccrm_wc_order_tag_color" name="custom_inwccrm_wc_order_tag_color" placeholder="' . esc_attr__( '', 'text_domain' ) . '" value="' . esc_attr( $custom_inwccrm_wc_order_tag_color ) . '">';
		echo '	<p class="description">' . __( 'Цвет текста метки', 'text_domain' ) . '</p>';
		echo '</div>';

		echo '<div class="form-field term-custom_inwccrm_wc_order_tag_color_bg-wrap">';
		echo '	<label for="custom_inwccrm_wc_order_tag_color_bg">' . __( 'Фон', 'text_domain' ) . '</label>';
		echo '	<input type="text" id="custom_inwccrm_wc_order_tag_color_bg" name="custom_inwccrm_wc_order_tag_color_bg" placeholder="' . esc_attr__( '', 'text_domain' ) . '" value="' . esc_attr( $custom_inwccrm_wc_order_tag_color_bg ) . '">';
		echo '	<p class="description">' . __( 'Цвет фона метки', 'text_domain' ) . '</p>';
		echo '</div>';

    }
    
	public function edit_screen_fields( $term, $taxonomy ) {

		// Retrieve an existing value from the database.
		$custom_inwccrm_wc_order_tag_color = get_term_meta( $term->term_id, 'custom_inwccrm_wc_order_tag_color', true );
		$custom_inwccrm_wc_order_tag_color_bg = get_term_meta( $term->term_id, 'custom_inwccrm_wc_order_tag_color_bg', true );

		// Set default values.
		if( empty( $custom_inwccrm_wc_order_tag_color ) ) $custom_inwccrm_wc_order_tag_color = '';
		if( empty( $custom_inwccrm_wc_order_tag_color_bg ) ) $custom_inwccrm_wc_order_tag_color_bg = '';

		// Form fields.
		echo '<tr class="form-field term-custom_inwccrm_wc_order_tag_color-wrap">';
		echo '<th scope="row">';
		echo '	<label for="custom_inwccrm_wc_order_tag_color">' . __( 'Цвет', 'text_domain' ) . '</label>';
		echo '</th>';
		echo '<td>';
		echo '	<input type="text" id="custom_inwccrm_wc_order_tag_color" name="custom_inwccrm_wc_order_tag_color" placeholder="' . esc_attr__( '', 'text_domain' ) . '" value="' . esc_attr( $custom_inwccrm_wc_order_tag_color ) . '">';
		echo '	<p class="description">' . __( 'Цвет текста метки', 'text_domain' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr class="form-field term-custom_inwccrm_wc_order_tag_color_bg-wrap">';
		echo '<th scope="row">';
		echo '	<label for="custom_inwccrm_wc_order_tag_color_bg">' . __( 'Фон', 'text_domain' ) . '</label>';
		echo '</th>';
		echo '<td>';
		echo '	<input type="text" id="custom_inwccrm_wc_order_tag_color_bg" name="custom_inwccrm_wc_order_tag_color_bg" placeholder="' . esc_attr__( '', 'text_domain' ) . '" value="' . esc_attr( $custom_inwccrm_wc_order_tag_color_bg ) . '">';
		echo '	<p class="description">' . __( 'Цвет фона метки', 'text_domain' ) . '</p>';
		echo '</td>';
		echo '</tr>';

	}

	public function save_data( $term_id ) {

		// Sanitize user input.
		$custom_new_inwccrm_wc_order_tag_color = isset( $_POST[ 'custom_inwccrm_wc_order_tag_color' ] ) ? sanitize_text_field( $_POST[ 'custom_inwccrm_wc_order_tag_color' ] ) : '';
		$custom_new_inwccrm_wc_order_tag_color_bg = isset( $_POST[ 'custom_inwccrm_wc_order_tag_color_bg' ] ) ? sanitize_text_field( $_POST[ 'custom_inwccrm_wc_order_tag_color_bg' ] ) : '';

		// Update the meta field in the database.
		update_term_meta( $term_id, 'custom_inwccrm_wc_order_tag_color', $custom_new_inwccrm_wc_order_tag_color );
		update_term_meta( $term_id, 'custom_inwccrm_wc_order_tag_color_bg', $custom_new_inwccrm_wc_order_tag_color_bg );

	}


}
