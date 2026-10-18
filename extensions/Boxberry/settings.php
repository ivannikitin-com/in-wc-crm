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
            <th scope="row"><label for="Boxberry-api-token"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td><input name="Boxberry-api-token" type="password" id="Boxberry-api-token"  
                value="<?php echo $this->getParam( 'Boxberry-api-token', '' ) ?>" >
                <p class="description"><?php esc_html_e( 'Для заказов, сделанных на medknigaservis', IN_WC_CRM) ?></p></td>
        </tr>
        <tr>
            <th scope="row"><label for="Boxberry-api-token-rosmedlib"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td>
                <input name="Boxberry-api-token-rosmedlib" type="password" id="Boxberry-api-token-rosmedlib"  
                    value="<?php echo $this->getParam( 'Boxberry-api-token-rosmedlib', '' ) ?>" >
                <p class="description"><?php esc_html_e( 'Для заказов, сделанных на rosmedlib', IN_WC_CRM) ?></p>
            </td>
        </tr>

    </tbody>
</table>