# Темы публичного сайта

Для нового дизайна помещайте Blade-файлы в `resources/views/themes/new`,
сохраняя путь и имя исходного шаблона.

Примеры:

- `home.blade.php` заменит главную страницу;
- `main_layots.blade.php` заменит общий layout;
- `catalog/index.blade.php` заменит каталог;
- `components/home-product-card.blade.php` заменит карточку товара.

Если файла в теме нет, Laravel автоматически использует старый шаблон.
