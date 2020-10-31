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
    <input type="hidden" name="order_rule_condition_<?php echo $ruleN ?>" value="<?php echo $ruleN ?>" />
    <select name="param_<?php echo $ruleN ?>">
        <?php foreach( $this->conditionManager->getParams() as $param => $title): ?>
            <option value="<?php echo $param ?>" <?php selected( $param, $paramValue )?>><?php echo $title ?></option>
        <?php endforeach ?>
    </select>

    <select name="equal_<?php echo $ruleN ?>">
        <?php foreach( $this->conditionManager->getEquals() as $equal => $title): ?>
                <option value="<?php echo $equal ?>" <?php selected($equal, $equalValue )?>><?php echo $title ?></option>
        <?php endforeach ?>
    </select>

    <input type="text" name="value_<?php echo $ruleN ?>" value="<?php echo $valueValue?>">
</div>
