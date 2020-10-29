<?php
/**
 * Класс RuleManager регистрирует тип данных и выполняет правила для заказов, 
 * назначая выбранную метку заказу
 */

namespace IN_WC_CRM\Extensions\OrderTags;
use \IN_WC_CRM\Extensions\OrderTags\Condition as Condition;
use \IN_WC_CRM\Extensions\OrderTags\OrderTag as OrderTag;

require 'Condition.php';

class RuleManager
{
    /**
     * Условия правила
     * @var Condition
     */
    private $conditionManager;

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $this->conditionManager = new Condition();

        // Регистрация типа данных
        $this->registerCPT();

        // Обработка новых заказов
        add_action( 'woocommerce_new_order', array( $this, 'process' ) );

        // Метабоксы правил
		if ( is_admin() ) {
			add_action( 'load-post.php',     array( $this, 'initMetabox' ) );
			add_action( 'load-post-new.php', array( $this, 'initMetabox' ) );
		}        
    }

    /** ===========================================================================================================
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
            'supports'              => array( 'title'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
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

    /** ===========================================================================================================
     * Метабокс
     */
    public function initMetabox() 
    {
		add_action( 'add_meta_boxes',        array( $this, 'addMetabox' )         );
		add_action( 'save_post',             array( $this, 'saveMetabox' ), 10, 2 );
	}

    public function addMetabox() 
    {

		add_meta_box(
            'order_rule_box',
			__( 'Условия правила', IN_WC_CRM ),
			array( $this, 'orderRuleMetabox' ),
			self::CPT,
			'advanced',
			'high'
		);

		add_meta_box(
            'order_tag_box',
			__( 'Назначение метки', IN_WC_CRM ),
			array( $this, 'orderTagMetabox' ),
			self::CPT,
			'advanced',
			'high'
		);

    }
    

    /**
     * Ключ сохранения условий в правиле
     */
    const RULE_CONDITIONS = 'order_tag_conditions';

    /**
     * Ключ сохранения метки в правиле
     */
    const RULE_TAG = 'order_tag_term_id';


    /**
     * Отрисовка метабокса правил
     */
    public function orderRuleMetabox( $post ) 
    {

        // Сохраненные условия правила
        foreach ( get_post_meta( $post->ID, self::RULE_CONDITIONS, false ) as $condition)
        {
            $this->orderRuleMetaboxCondition($condition);
        }

        // Новое правило
        $this->orderRuleMetaboxCondition(false);
    }
    
    /**
     * Отрисовка метабокса меток
     */
    public function orderTagMetabox( $post ) 
    {
        $currentTag = get_post_meta( $post->ID, self::RULE_TAG, true );
        $tags = get_terms(array(
            'taxonomy'   => OrderTag::TAXOMOMY,
            'hide_empty' => false,
        ));
        include('views/metabox-tag.php');
    }

    /**
     * Отрисовка одного условия
     * @param $condition    mixed   Данные условия
     */
    private function orderRuleMetaboxCondition($condition)
    {
        include('views/metabox-condition.php');
    }

    public function saveMetabox( $post_id, $post ) 
    {
        if ( isset( $_POST['params'] ) && isset( $_POST['equals'] ) && isset( $_POST['values'] ))
        {
            for($i=0; $i < count( $_POST['params'] ); $i++ )
            {
                $param = isset( $_POST['params'][$i] ) ? sanitize_text_field( $_POST['params'][$i] ) : '';
                $equal = isset( $_POST['equals'][$i] ) ? sanitize_text_field( $_POST['equals'][$i] ) : '';
                $value = isset( $_POST['values'][$i] ) ? sanitize_text_field( $_POST['values'][$i] ) : '';

                if ($param && $equal && $value)
                {
                    $condition = array(
                        'param' => $param,
                        'equal' => $equal,
                        'value' => $value
                    );

                    update_post_meta( $post_id, self::RULE_CONDITIONS, $condition );
                }
            }
        }
        
        if ( isset( $_POST['order_tag'] ) )
        {
            $orderTagId = $_POST['order_tag'];
            update_post_meta( $post_id, self::RULE_TAG, $orderTagId );
        }
    }

    /** =====================================================================================================
     */
    
    /**
     * Массив записей с ID правил
     * Сохраняем его, чтобы при массовой обработке заказов каждый раз не выбирать правила
     * @var mixed
     */
    private $allRules;

     /**
     * Обработка заказа WC
     * @param int|WC_Order  $order  Объект заказа WC
     */
    public function process($order)
    {
        // Если заказ передан через ID, получим этот заказ
        if ( is_int( $order ) ) $order = wc_get_order( $order );

        // Если найти заказ не удалось, ничего не делаем
        if ( ! $order ) return;

        // Проверяем, заполлен ли кэш-массив правил
        if ( empty( $this->allRules) )
        {
            $this->allRules = array();

            // Выбираем все правила
            $ruleIds = get_posts( array( 
                'post_type' => self::CPT,
                'fields'    => 'ids'
            ) );
            
            // Формируем кэш-массив правил
            foreach($ruleIds as $ruleId)
            {
                $this->allRules[$ruleId] = array(
                    'conditions' => get_post_meta( $ruleId, self::RULE_CONDITIONS, false ),
                    'tag'        => get_post_meta( $ruleId, self::RULE_TAG, true )
                );
            }
        }

        // Применяем все правила к заказу 
        foreach($this->allRules as $ruleId => $rule)
        {
            // Проверяем условия текущего правила
            if ($this->conditionManager->check( $order,  $rule['conditions'] ) )
            {
                // Ставим метку на заказ
                $tag = array( $rule['tag'] * 1 ); // https://developer.wordpress.org/reference/functions/wp_set_post_terms/
                wp_set_post_terms( $order->get_id(), $tag, OrderTag::TAXOMOMY, true );
            }
        }
    }
}