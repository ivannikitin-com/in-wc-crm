<?php
/**
 * Настройки расширения
 */
namespace IN_WC_CRM;
?>
<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><?php esc_html_e( 'Включение', IN_WC_CRM) ?></th>
            <td>
                <label for="TopDelivery-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="TopDelivery-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-api-login"><?php esc_html_e( 'API Логин', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-api-login" type="text" id="TopDelivery-api-login"  
                value="<?php echo $this->getParam( 'TopDelivery-api-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-api-password"><?php esc_html_e( 'API Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-api-password" type="password" id="TopDelivery-api-password"  
                value="<?php echo $this->getParam( 'TopDelivery-api-password', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-inn"><?php esc_html_e( 'ИНН поставщика', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-inn" type="text" id="TopDelivery-inn"  
                value="<?php echo $this->getParam( 'TopDelivery-inn', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-jurName"><?php esc_html_e( 'Юридическое лицо', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-jurName" type="text" id="TopDelivery-jurName"  
                value="<?php echo $this->getParam( 'TopDelivery-jurName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-jurAddress"><?php esc_html_e( 'Юридический адрес', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-jurAddress" type="text" id="TopDelivery-jurAddress"  
                value="<?php echo $this->getParam( 'TopDelivery-jurAddress', '' ) ?>" ></td>
        </tr>        
        <tr>
            <th scope="row"><label for="TopDelivery-commercialName"><?php esc_html_e( 'Коммерческое наименование', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-commercialName" type="text" id="TopDelivery-commercialName"  
                value="<?php echo $this->getParam( 'TopDelivery-commercialName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="TopDelivery-phone"><?php esc_html_e( 'Номер телефона', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-phone" type="text" id="TopDelivery-phone"  
                value="<?php echo $this->getParam( 'TopDelivery-phone', '' ) ?>" ></td>
        </tr>
    </tbody>
</table>