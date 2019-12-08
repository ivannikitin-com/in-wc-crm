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
                value="<?php echo $this->getParam( 'pickpoint-api-endpoint', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-login"><?php esc_html_e( 'API Логин', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-login" type="text" id="pickpoint-api-login"  
                value="<?php echo $this->getParam( 'pickpoint-api-login', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-password"><?php esc_html_e( 'API Пароль', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-password" type="password" id="pickpoint-api-password"  
                value="<?php echo $this->getParam( 'pickpoint-api-password', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-api-ikn"><?php esc_html_e( 'ИКН', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-api-ikn" type="text" id="pickpoint-api-ikn"  
                value="<?php echo $this->getParam( 'pickpoint-api-ikn', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-order-status"><?php esc_html_e( 'Статус заказов для обработки', IN_WC_CRM) ?></label></th>
            <td>
                <select name="pickpoint-order-status" id="pickpoint-order-status">
                <?php
                    $currentStatus = $this->getParam( 'pickpoint-order-status', 'wc-processing' );
                    $statuses = wc_get_order_statuses();
                    foreach ($statuses as $status => $title ): ?>
                        <option value="<?php echo $status ?>" <?php selected( $status, $currentStatus ); ?>><?php echo $title ?></option>
                    <?php endforeach ?>
                </select>    
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-shopOrganization"><?php esc_html_e( 'Организация магазина', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-shopOrganization" type="text" id="pickpoint-shopOrganization"  
                value="<?php echo $this->getParam( 'pickpoint-shopOrganization', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-shopPhone"><?php esc_html_e( 'Телефон магазина', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-shopPhone" type="text" id="pickpoint-shopPhone"  
                value="<?php echo $this->getParam( 'pickpoint-shopPhone', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-shopManagerName"><?php esc_html_e( 'Отвественный менеджер', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-shopManagerName" type="text" id="pickpoint-shopManagerName"  
                value="<?php echo $this->getParam( 'pickpoint-shopManagerName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="pickpoint-shopComment"><?php esc_html_e( 'Комментарий для ПикПоинта', IN_WC_CRM) ?></label></th>
            <td><input name="pickpoint-shopComment" type="text" id="pickpoint-shopComment"  
                value="<?php echo $this->getParam( 'pickpoint-shopComment', '' ) ?>" ></td>
        </tr>                                
    </tbody>
</table>