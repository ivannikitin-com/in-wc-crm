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

<section id="extensionList">
	<style>
		#extensionList div { width: 150px; height: 150px; border: 1px solid black; border-radius: 5px; margin: 10px; padding: 10px; float: left; }
		#extensionList div h3 { margin: 0; padding: 0; font-size: 12pt; }
		#extensionList div input { float: left; margin: 2px; margin-right: 10px; }
	</style>
	<h2><?php esc_html_e( 'Расширения системы', IN_WC_CRM) ?></h2>
	<?php foreach( $this->extensions as $name => $data ): 
		$extension = $data['obj']
	?>
		<div>
			<input type="checkbox" <?php checked( $extension->isEnabled() ) ?> data-extension="<?php echo $name ?>">
			<h3><?php echo $extension->getTitle() ?></h3>
			<p><?php echo $extension->getDescription() ?></p>
		</div>
	<?php endforeach ?>
</section>