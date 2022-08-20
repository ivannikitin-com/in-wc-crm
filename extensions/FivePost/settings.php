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
                <label for="FivePost-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="FivePost-enabled" 
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="FivePost-api-token"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td><input name="FivePost-api-token" type="password" id="FivePost-api-token"  
                value="<?php echo $this->getParam( 'FivePost-api-token', '' ) ?>" ></td>
        </tr>

    </tbody>
</table>