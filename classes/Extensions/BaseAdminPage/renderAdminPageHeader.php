<?php
/**
 * Шаблон стандартной шапки админ-страницы
 * Выполняется в контексте метода  BaseAdminPage::renderAdminPageHeader()
 */
?>
<style>
    #in-wc-crm_header {
        background-color: <?php echo $this->colors[1] ?>;
        margin-left: -160px;
        margin-top: -15px;

    }
    #in-wc-crm_header > div {
        margin-left: 160px;
        padding: 10px;
    }
    #in-wc-crm_header > div > h1 {
        color: <?php echo $this->colors[5] ?>;
        font-size: 16px;
    }
</style>
<section id="in-wc-crm_header">
    <div>
        <h1><?php echo $this->getTitle() ?></h1>
    </div>
</section>
