Расширение «Яндекс Доставка (новая)» (YandexNew)

Выгружает заказы WooCommerce в Яндекс Доставку по России (API «доставка в другой день»).

Документация API
    https://yandex.ru/support/delivery-profile/ru/api/other-day/
    https://yandex.ru/support/delivery-profile/ru/api/other-day/access.md
    https://yandex.ru/support/delivery-profile/ru/api/other-day/ref/3.-Osnovnye-zaprosy/apib2bplatformofferscreate-post.md
    https://yandex.ru/support/delivery-profile/ru/api/other-day/ref/3.-Osnovnye-zaprosy/apib2bplatformoffersconfirm-post.md
    https://yandex.ru/support/delivery-profile/ru/api/other-day/ref/6.-Upravlenie-skladami-i-otgruzkami/apib2bplatformpickupsscheduledlist-post.md

Порядок выгрузки
    1. offers/create — Яндекс возвращает варианты доставки и их стоимость.
    2. offers/confirm — подтверждение первого варианта, Яндекс возвращает request_id.
    request_id сохраняется в мета-поле заказа _yandexnew_request_id и в комментарии к заказу.
    Повторная выгрузка заказа с уже сохраненным request_id не выполняется.

Настройки
    - Хост API: боевой (b2b-authproxy.taxi.yandex.net) или тестовый (b2b.taxi.tst.yandex.net).
    - API токен и станция отгрузки (platform_station_id) отдельно для medknigaservis и rosmedlib.
    - Вес товара по умолчанию (если у товара не заполнен вес).

Данные заказа
    - Цены товаров передаются в копейках с НДС, ставка НДС по умолчанию 0.
    - Вес пересчитывается из единиц магазина в граммы, габариты — в сантиметры.
    - Оплата: заказ с оплатой при получении (способ оплаты cod) уходит как card_on_receipt
      со стоимостью доставки, остальные — как already_paid.
    - Оператор заявки (operator_request_id) — номер заказа.

Доставка в ПВЗ
    Если в мета-поле заказа _yandexnew_platform_station_id указан ID станции ПВЗ,
    заказ уходит как self_pickup (доставка в пункт выдачи). Иначе оформляется
    курьерская доставка до двери (time_interval) по адресу заказа.
    Для курьерской доставки интервал отгрузки со склада берется из ближайшей
    запланированной отгрузки (pickups/scheduled/list): переданный интервал должен
    совпадать с созданной отгрузкой, иначе Яндекс отклоняет заявку.

Токены и домены
    Bearer токен не привязан к домену сайта: это токен аккаунта Яндекс Доставки,
    запросы идут с сервера на сервер. Один и тот же боевой токен можно использовать
    и на medknigaservis.ru, и на dev.medknigaservis.ru. При этом заказы, созданные
    на dev-сайте с боевым токеном, будут реальными. Тестовый контур использует
    отдельный тестовый токен и отдельный тестовый склад, совместимы только с
    тестовым хостом.

Проверка на тестовом контуре (29.10.2026)
    - Заявка в ПВЗ (self_pickup) проходит полностью: offers/create -> offers/confirm,
      возвращается request_id и стоимость доставки.
    - Курьерская доставка в тестовом контуре вариантов не предлагает
      (no_delivery_options): у тестового склада не настроены отгрузки под курьера.
