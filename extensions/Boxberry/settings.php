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
                <label for="Boxberry-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="Boxberry-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-wsdl"><?php esc_html_e( 'WSDL', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-wsdl" type="url" id="Boxberry-wsdl"  
                value="<?php echo $this->getParam( 'Boxberry-wsdl', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-api-login"><?php esc_html_e( 'API Логин', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-api-login" type="text" id="Boxberry-api-login"  
                value="<?php echo $this->getParam( 'Boxberry-api-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-api-password"><?php esc_html_e( 'API Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-api-password" type="password" id="Boxberry-api-password"  
                value="<?php echo $this->getParam( 'Boxberry-api-password', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-http-login"><?php esc_html_e( 'Basic Auth Логин', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-http-login" type="text" id="Boxberry-http-login"  
                value="<?php echo $this->getParam( 'Boxberry-http-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-http-password"><?php esc_html_e( 'Basic Auth Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-http-password" type="password" id="Boxberry-http-password"  
                value="<?php echo $this->getParam( 'Boxberry-http-password', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-inn"><?php esc_html_e( 'ИНН поставщика', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-inn" type="text" id="Boxberry-inn"  
                value="<?php echo $this->getParam( 'Boxberry-inn', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-jurName"><?php esc_html_e( 'Юридическое лицо', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-jurName" type="text" id="Boxberry-jurName"  
                value="<?php echo $this->getParam( 'Boxberry-jurName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-jurAddress"><?php esc_html_e( 'Юридический адрес', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-jurAddress" type="text" id="Boxberry-jurAddress"  
                value="<?php echo $this->getParam( 'Boxberry-jurAddress', '' ) ?>" ></td>
        </tr>        
        <tr>
            <th scope="row"><label for="Boxberry-commercialName"><?php esc_html_e( 'Коммерческое наименование', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-commercialName" type="text" id="Boxberry-commercialName"  
                value="<?php echo $this->getParam( 'Boxberry-commercialName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-phone"><?php esc_html_e( 'Номер телефона', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-phone" type="phone" id="Boxberry-phone"  
                value="<?php echo $this->getParam( 'Boxberry-phone', '' ) ?>" ></td>
        </tr>
    </tbody>
</table>