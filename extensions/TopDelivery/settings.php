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
            <th scope="row"><label for="TopDelivery-api-endpoint"><?php esc_html_e( 'API URL', IN_WC_CRM) ?></label></th>
            <td><input name="TopDelivery-api-endpoint" type="url" id="TopDelivery-api-endpoint"  style="width:80%"
                value="<?php echo $this->getParam( 'TopDelivery-api-endpoint', '' ) ?>" ></td>
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
    </tbody>
</table>