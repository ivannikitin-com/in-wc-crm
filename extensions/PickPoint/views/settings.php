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
                <label for="pickpoint-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="pickpoint-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-endpoint"><?php esc_html_e( 'API URL', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-endpoint" type="url" id="pickpoint-api-endpoint"  style="width:80%"
                value="<?php $this->getParam( 'pickpoint-api-endpoint', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-login"><?php esc_html_e( 'API Логин', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-login" type="text" id="pickpoint-api-login"  
                value="<?php $this->getParam( 'pickpoint-api-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-password"><?php esc_html_e( 'API Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-password" type="password" id="pickpoint-api-password"  
                value="<?php $this->getParam( 'pickpoint-api-password', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-ikn"><?php esc_html_e( 'ИКН', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-ikn" type="text" id="pickpoint-api-ikn"  
                value="<?php $this->getParam( 'pickpoint-api-ikn', '' ) ?>" ></td>
        </tr>        
    </tbody>
</table>
