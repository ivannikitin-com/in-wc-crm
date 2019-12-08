<?php
/**
 * Описание плагина
 * Выводится в контексте метода ExtensionManager::adminMenuContent()
 */
?>
<h1><?php esc_html_e( 'Добро пожаловать в простую систему IN WC CRM', IN_WC_CRM) ?></h1>
<?php if ( WP_DEBUG ): ?>
	<div style="background-color:#ffc;margin-left:-80px;padding:5px;padding-left:80px">
		<?php esc_html_e( 'Включен режим отладки', IN_WC_CRM) ?>
	</div>
<?php endif ?>