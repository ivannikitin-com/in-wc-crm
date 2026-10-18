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
                value="<?php echo $this->getParam( 'FivePost-api-token', '' ) ?>" >
                <p class="description"><?php esc_html_e( 'Для заказов, сделанных на medknigaservis', IN_WC_CRM) ?></p></td>
        </tr>
        <tr>
            <th scope="row"><label for="FivePost-api-token-rosmedlib"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td>
                <input name="FivePost-api-token-rosmedlib" type="password" id="FivePost-api-token-rosmedlib"  
                    value="<?php echo $this->getParam( 'FivePost-api-token-rosmedlib', '' ) ?>" >
                <p class="description"><?php esc_html_e( 'Для заказов, сделанных на rosmedlib', IN_WC_CRM) ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="FivePost-default-weight"><?php esc_html_e( 'Вес товара по умолчанию', IN_WC_CRM) ?></label></th>
            <td>
                <input name="FivePost-default-weight" type="number" id="FivePost-default-weight" min="0" step="any"
                    value="<?php echo esc_attr( $this->getParam( 'FivePost-default-weight', '' ) ) ?>">
                <?php echo esc_html( get_option( 'woocommerce_weight_unit' ) ); ?>
            </td>
        </tr>

    </tbody>
</table>