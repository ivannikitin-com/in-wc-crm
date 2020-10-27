<?php
/**
 * Отрисовка строки условия в метабоксе
 * выполняется в контексте метода RuleManager::orderRuleMetaboxCondition
 */

$paramValue =  ($condition) ? $condition['param'] : '';
$equalValue =  ($condition) ? $condition['equal'] : '';
$valueValue =  ($condition) ? $condition['value'] : '';
?>

<div class="order-rule-condition">
    <select name="params[]">
        <?php foreach( $this->condition->getParams() as $param => $title): ?>
            <option value="<?php echo $param ?>" <?php selected( $param, $paramValue )?>><?php echo $title ?></option>
        <?php endforeach ?>
    </select>

    <select name="equals[]">
        <?php foreach( $this->condition->getEquals() as $equal => $title): ?>
                <option value="<?php echo $equal ?>" <?php selected($equal, $equalValue )?>><?php echo $title ?></option>
        <?php endforeach ?>
    </select>

    <input type="text" name="values[]" value="<?php echo $valueValue?>">
</div>
