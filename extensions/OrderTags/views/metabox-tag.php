<?php
/**
 * Отрисовка списка меток
 * выполняется в контексте метода RuleManager::orderTagMetabox
 */
?>

<div class="order-rule-condition">
    <select name="order_tag">
        <?php foreach( $tags as $term): ?>
            <option value="<?php echo $term->term_id ?>" <?php selected( $term->term_id, $currentTag )?>><?php echo $term->name ?></option>
        <?php endforeach ?>
    </select>
</div>
