# Forest OS 2.0 — план рефакторинга

## Этап 1. Реанимация

- Docker
- MariaDB
- Bootstrap вместо старого веб-инсталлятора
- Генерация файлов пользователя
- Совместимость PHP 8 вместо PHP 5.6/mcrypt

## Этап 2. Безопасность

- `readbd` → PDO prepared statements
- `crypt()` legacy → `password_hash()` / `password_verify()`
- `session_regenerate_id(true)` после входа
- CSRF для POST-операций
- нормальная проверка путей в Explorer/trash/copy

## Этап 3. Forest API

- `/api/v1/me`
- `/api/v1/users`
- `/api/v1/apps`
- `/api/v1/notifications`
- `/api/v1/files`

## Этап 4. Cloud/FUID

- центральная база логинов
- резервирование логина
- выдача FUID
- синхронизация инсталляций
- режим standalone/cloud-linked

## Этап 5. Магазин приложений

- manifest.json для приложения
- версия приложения
- зависимости
- подпись архива
- установка/обновление/удаление

## Этап 6. UI без потери души

- сохранить desktop/window/dock-концепцию
- заменить jQuery UI постепенно
- PWA
- мобильный режим
- темы
