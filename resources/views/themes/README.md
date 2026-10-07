# Темы публичного сайта

Для нового дизайна помещайте Blade-файлы в `resources/views/themes/new`,
сохраняя путь и имя исходного шаблона.

Примеры:

- `home.blade.php` заменит главную страницу;
- `main_layots.blade.php` заменит общий layout;
- `catalog/index.blade.php` заменит каталог;
- `components/home-product-card.blade.php` заменит карточку товара.

Доступные публичные темы: `legacy`, `new`, `joom`. Тема `joom` использует отдельную
плотную e-commerce витрину с промо-блоком, горизонтальными товарными рядами и
акцентом на скидки, доставку и локальных продавцов.

Если файла в теме нет, Laravel автоматически использует старый шаблон.

## Кабинет продавца: оранжевая тема

Новый кабинет (`resources/views/themes/new/backend/cabinet`) подключает в `layout_cabinet.blade.php`:

1. Bootstrap (`cabinet/assets/vendor_assets/css/bootstrap/bootstrap.css`) — сетка и утилиты;
2. `public/css/cabinet-common.css` — общие компоненты кабинета (карточки товаров, формы,
   заказы и т.д.) для обеих тем кабинета. Правила, одинаковые в темах, записаны одним списком
   селекторов `body.seller-cabinet-body …, :where(body.cabinet-new) …`; отличающиеся (цветовые
   акценты) — отдельными правилами. Файл правится один раз, дублировать его не нужно;
3. `public/css/theme-cabinet-new.css` — оформление темы. Блок «Cabinet polish (orange)» в конце файла
   имеет приоритет над предыдущими слоями.

Скрипты (jQuery, Bootstrap JS, SweetAlert2) подключены внизу layout: без них не работают меню «⋮»,
подтверждения удаления и выхода из кабинета.
