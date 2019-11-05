<?php
/**
 * Пример расширения
 */
namespace IN_WC_CRM\Extensions;
use \IN_WC_CRM\Plugin as Plugin;

class Example extends BaseAdminPage
{
    /**
     * Возвращает название расширения
     * @return string
     */
    public function getTitle()
    {
        return __( 'Информация о плагине', IN_WC_CRM ) . ' ' . Plugin::get()->name;
    }

    /**
     * Возвращает название пункта меню
     * @return string
     */
    public function getAdminPageMenuTitle()
    {
        return __( 'Информация', IN_WC_CRM );
    }

    /**
     * Отрисовывает содержимое шапки страницы
     */
    protected function renderAdminPageContent()
    {
        // Так сделано исключительно для демонстрации расширения из одного файла. 
        // Намного лучше использовать подход с явными определениями view, как это сделано у других расширений
?>
    <section>
        <h2>
            <?php echo Plugin::get()->name ?>
            версия
            <?php echo Plugin::get()->version ?>
        </h2>

        <h3>Список доступных расширений</h3>
        <ul>
            <?php foreach ( Plugin::get()->extensionManager->extensions as $name => $data ): ?>
                <li>
                    <strong><?php echo $name ?></strong>
                    --
                    <?php echo $data['obj']->getTitle() ?>
                </li>
            <?php endforeach ?>
        </ul>
    </section>
<?php
    }     
}