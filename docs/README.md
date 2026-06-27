# Документация проекта

Актуализировано: 25.06.2026.

В корне `docs` находятся только документы, которыми можно пользоваться при
разработке и эксплуатации текущей версии приложения. Завершённые планы,
первоначальные требования и старые аудиты находятся в `docs/archive`.

## Основные документы

| Документ | Назначение |
|---|---|
| [project_documentation_ru.md](project_documentation_ru.md) | Текущая архитектура, подсистемы и точки входа |
| [production_deployment_ru.md](production_deployment_ru.md) | Установка, обновление и production-эксплуатация |
| [local_runtime_ru.md](local_runtime_ru.md) | Единая команда локального запуска workers, scheduler, сайта и Vite |
| [order_system_ru.md](order_system_ru.md) | Фактическая реализация заказов и её ограничения |
| [moderation_operations_ru.md](moderation_operations_ru.md) | Настройка и эксплуатация модерации товаров |
| [product_moderation_policy_ru.md](product_moderation_policy_ru.md) | Бизнес-политика проверки товаров |
| [architecture_hardening_plan_ru.md](architecture_hardening_plan_ru.md) | Оставшиеся архитектурные улучшения |
| [site_design_audit_ru.md](site_design_audit_ru.md) | Аудит интерфейса и статус исправлений |
| [mobile_navigation_redesign_plan_ru.md](mobile_navigation_redesign_plan_ru.md) | Поэтапный план переделки мобильной навигации клиента и входа продавца |
| [design_rules_ru.md](design_rules_ru.md) | Обязательные правила публичного интерфейса и изображений |
| [filament_manual_acceptance_checklist_ru.md](filament_manual_acceptance_checklist_ru.md) | Ручная приёмка административной панели |
| [BACKLOG.md](BACKLOG.md) | Нерешённые пользовательские и технические задачи |

## Правила актуальности

1. Код и автоматические тесты являются источником истины.
2. Документы с планами после завершения переносятся в `docs/archive`.
3. Не хранить в активных документах устаревшие количества тестов и маршрутов.
4. Не хранить пароли, API-ключи, Telegram-токены и персональные данные.
5. При изменении заказов, модерации, региональности или deployment обновлять
   соответствующий документ в том же изменении.

## Архив

`docs/archive` содержит исторический контекст. Эти файлы не являются
инструкциями к текущей версии и не должны использоваться без сверки с кодом.
