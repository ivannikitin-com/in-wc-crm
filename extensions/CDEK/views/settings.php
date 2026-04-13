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
                <label for="CDEK-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="CDEK-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-api-endpoint"><?php esc_html_e( 'API URL', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-api-endpoint" type="url" id="CDEK-api-endpoint"  style="width:80%"
                value="<?php echo $this->getParam( 'CDEK-api-endpoint', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-api-id"><?php esc_html_e( 'API Идентификатор', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-api-id" type="text" id="CDEK-api-id"  
                value="<?php echo $this->getParam( 'CDEK-api-id', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-api-secretkey"><?php esc_html_e( 'API Секретный ключ', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-api-secretkey" type="password" id="CDEK-api-secretkey"  
                value="<?php echo $this->getParam( 'CDEK-api-secretkey', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-order-status"><?php esc_html_e( 'Статус заказов для обработки', IN_WC_CRM) ?></label></th>
            <td>
                <select name="CDEK-order-status" id="CDEK-order-status">
                <?php
                    $currentStatus = $this->getParam( 'CDEK-order-status', 'wc-processing' );
                    $statuses = wc_get_order_statuses();
                    foreach ($statuses as $status => $title ): ?>
                        <option value="<?php echo $status ?>" <?php selected( $status, $currentStatus ); ?>><?php echo $title ?></option>
                    <?php endforeach ?>
                </select>    
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shopOrganization"><?php esc_html_e( 'Организация магазина', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shopOrganization" type="text" id="CDEK-shopOrganization"  
                value="<?php echo $this->getParam( 'CDEK-shopOrganization', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shopAddress"><?php esc_html_e( 'Адрес продавца', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shopAddress" type="text" id="CDEK-shopAddress"  
                value="<?php echo $this->getParam( 'CDEK-shopAddress', '' ) ?>" ></td>
        </tr>        
        <tr>
            <th scope="row"><label for="CDEK-shopPhone"><?php esc_html_e( 'Телефон магазина', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shopPhone" type="text" id="CDEK-shopPhone"  
                value="<?php echo $this->getParam( 'CDEK-shopPhone', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shopManagerName"><?php esc_html_e( 'Отвественный менеджер', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shopManagerName" type="text" id="CDEK-shopManagerName"  
                value="<?php echo $this->getParam( 'CDEK-shopManagerName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shipperName"><?php esc_html_e( 'Грузоотправитель', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shipperName" type="text" id="CDEK-shipperName"  
                value="<?php echo $this->getParam( 'CDEK-shipperName', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shipperAddress"><?php esc_html_e( 'Адрес грузоотправителя', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shipperAddress" type="text" id="CDEK-shipperAddress"  
                value="<?php echo $this->getParam( 'CDEK-shipperAddress', '' ) ?>" ></td>
        </tr>
        <tr>
            <th scope="row"><label for="CDEK-shipperEmail"><?php esc_html_e( 'Email грузоотправителя', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shipperEmail" type="text" id="CDEK-shipperEmail"  
                value="<?php echo $this->getParam( 'CDEK-shipperEmail', '' ) ?>" ></td>
        </tr> 
        <tr>
            <th scope="row"><label for="CDEK-shipperPhone"><?php esc_html_e( 'Телефон грузоотправителя', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shipperPhone" type="text" id="CDEK-shipperPhone"  
                value="<?php echo $this->getParam( 'CDEK-shipperPhone', '' ) ?>" ></td>
        </tr>                         
        <tr>
            <th scope="row"><label for="CDEK-shopComment"><?php esc_html_e( 'Комментарий для CDEK', IN_WC_CRM) ?></label></th>
            <td><input name="CDEK-shopComment" type="text" id="CDEK-shopComment"  
                value="<?php echo $this->getParam( 'CDEK-shopComment', '' ) ?>" ></td>
        </tr>                                
    </tbody>
</table>