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
                <label for="YandexNew-enabled">
                    <input name="<?php echo $this->enabledPamam ?>" type="checkbox" id="YandexNew-enabled"
                        value="1"<?php checked( $this->getParam( $this->enabledPamam, true ) ); ?>>
                    <?php esc_html_e( 'Расширение активно', IN_WC_CRM) ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-api-url"><?php esc_html_e( 'Хост API', IN_WC_CRM) ?></label></th>
            <td>
                <?php $apiUrl = $this->getParam( 'YandexNew-api-url', \IN_WC_CRM\Extensions\YandexNew\API::DEFAULT_HOST ); ?>
                <select name="YandexNew-api-url" id="YandexNew-api-url">
                    <option value="prod"<?php selected( $apiUrl, 'prod' ); ?>><?php esc_html_e( 'Боевой', IN_WC_CRM) ?></option>
                    <option value="test"<?php selected( $apiUrl, 'test' ); ?>><?php esc_html_e( 'Тестовый', IN_WC_CRM) ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'В тестовом контуре обрабатываются только адреса Москвы', IN_WC_CRM) ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-api-token"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td><input name="YandexNew-api-token" type="password" id="YandexNew-api-token"
                value="<?php echo esc_attr( $this->getParam( 'YandexNew-api-token', '' ) ) ?>" >
                <p class="description"><?php esc_html_e( 'Bearer токен из личного кабинета Яндекс Доставки (раздел Профиль). Для заказов, сделанных на medknigaservis', IN_WC_CRM) ?></p></td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-source-station"><?php esc_html_e( 'Станция отгрузки', IN_WC_CRM) ?></label></th>
            <td>
                <input name="YandexNew-source-station" type="text" id="YandexNew-source-station" class="regular-text"
                    value="<?php echo esc_attr( $this->getParam( 'YandexNew-source-station', '' ) ) ?>">
                <p class="description"><?php esc_html_e( 'Идентификатор склада отгрузки (platform_station_id). Для заказов, сделанных на medknigaservis', IN_WC_CRM) ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-api-token-rosmedlib"><?php esc_html_e( 'API токен', IN_WC_CRM) ?></label></th>
            <td>
                <input name="YandexNew-api-token-rosmedlib" type="password" id="YandexNew-api-token-rosmedlib"
                    value="<?php echo esc_attr( $this->getParam( 'YandexNew-api-token-rosmedlib', '' ) ) ?>" >
                <p class="description"><?php esc_html_e( 'Для заказов, сделанных на rosmedlib', IN_WC_CRM) ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-source-station-rosmedlib"><?php esc_html_e( 'Станция отгрузки', IN_WC_CRM) ?></label></th>
            <td>
                <input name="YandexNew-source-station-rosmedlib" type="text" id="YandexNew-source-station-rosmedlib" class="regular-text"
                    value="<?php echo esc_attr( $this->getParam( 'YandexNew-source-station-rosmedlib', '' ) ) ?>">
                <p class="description"><?php esc_html_e( 'Идентификатор склада отгрузки (platform_station_id). Для заказов, сделанных на rosmedlib', IN_WC_CRM) ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="YandexNew-default-weight"><?php esc_html_e( 'Вес товара по умолчанию', IN_WC_CRM) ?></label></th>
            <td>
                <input name="YandexNew-default-weight" type="number" id="YandexNew-default-weight" min="0" step="any"
                    value="<?php echo esc_attr( $this->getParam( 'YandexNew-default-weight', '' ) ) ?>">
                <?php echo esc_html( get_option( 'woocommerce_weight_unit' ) ); ?>
                <p class="description"><?php esc_html_e( 'Используется, если у товара не указан вес', IN_WC_CRM) ?></p>
            </td>
        </tr>
    </tbody>
</table>
