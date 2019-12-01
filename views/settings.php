<?php
/**
 * Настройки плагина и расширений
 * Выводится в контексте метода ExtensionManager::adminMenuContent()
 */
namespace IN_WC_CRM;
?>
<style>
#in-wc-crm-settings fieldset{
    border: 1px solid gray;
    padding: 10px;
    margin: 10px;
    margin-left: 0px;
    margin-right: 40px;
    border-radius: 4px;
}
#in-wc-crm-settings fieldset legend span{
    font-weight: bold;
}
</style>
<h1><?php esc_html_e( 'Настройки', IN_WC_CRM) ?> <?php echo Plugin::get()->name?></h1>
<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" id="in-wc-crm-settings">
    
    <?php foreach( $this->extensions as $name => $data ): 
        $extension = $data['obj'];
        if ( ! is_object( $extension ) ) continue;
        if ( ! $extension->hasSettings() )  continue; ?>
    
        <fieldset>
            <legend> [ <span><?php echo $extension->getTitle() ?></span> ] </legend>
            <?php $extension-> showSettings() ?>
        </fieldset>

    <?php endforeach ?>

    <button type="submit" class="button button-primary"><?php esc_html_e( 'Сохранить', IN_WC_CRM) ?></button>
</form>