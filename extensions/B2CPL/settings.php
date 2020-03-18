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
                <label for="B2CPL-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="B2CPL-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="B2CPL-api-endpoint"><?php esc_html_e( 'API URL', IN_WC_CRM) ?></label></th>
            <td><input name="B2CPL-api-endpoint" type="url" id="B2CPL-api-endpoint"  style="width:80%"
                value="<?php echo $this->getParam( 'B2CPL-api-endpoint', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="B2CPL-api-login"><?php esc_html_e( 'API Логин', IN_WC_CRM) ?></label></th>
            <td><input name="B2CPL-api-login" type="text" id="B2CPL-api-login"  
                value="<?php echo $this->getParam( 'B2CPL-api-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="B2CPL-api-password"><?php esc_html_e( 'API Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="B2CPL-api-password" type="password" id="B2CPL-api-password"  
                value="<?php echo $this->getParam( 'B2CPL-api-password', '' ) ?>" ></td>
        </tr>
    </tbody>
</table>