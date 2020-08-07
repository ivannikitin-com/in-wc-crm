<?php
/**
 * Показыват список меток заказа для выбора и фильрации
 */

$terms = get_terms( 'inwccrm_wc_order_tag', [
    'hide_empty' => false,
    'fields'     => 'all',
] );

?>
  <span>
      <label for="order_tag"><?php esc_html_e( 'Метка заказа', IN_WC_CRM ); ?></label>
      <select id="order_tag" class="customFilter">
        <option value="0"><?php esc_html_e( 'Все заказы', IN_WC_CRM ); ?></option>
        <?php foreach ( $terms as $term ): ?>
            <option value="<?php echo $term->term_id?>"><?php echo $term->name ?></option>
        <?php endforeach ?>
      </select>
  </span>