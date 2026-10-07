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
2. `public/css/theme-cabinet-shared.css` — общие компоненты кабинета (карточки товаров, формы,
   заказы и т.д.). Файл получен из `public/css/seller-cabinet.css`: селекторы
   `body.seller-cabinet-body` заменены на `:where(body.cabinet-new)`, фиолетовые акценты — на оранжевые.
   Если `seller-cabinet.css` меняется, этот файл нужно обновить так же;
3. `public/css/theme-cabinet-new.css` — оформление темы. Блок «Cabinet polish (orange)» в конце файла
   имеет приоритет над предыдущими слоями.

Скрипты (jQuery, Bootstrap JS, SweetAlert2) подключены внизу layout: без них не работают меню «⋮»,
подтверждения удаления и выхода из кабинета.
