# Публикация на GitHub и совместная работа

## 1. Что уже готово

Репозиторий собран и вычищен: секретов, личных файлов и базы данных в нём нет.

```
.env.example        шаблон настроек (реальный .env не коммитится)
.gitignore          исключения: .env, data/, system/users/*, bd_inc.php, instance.foc…
Dockerfile          PHP 8.3 + Apache, entrypoint с bootstrap
docker-compose.yml  web + MariaDB, пароли берутся из .env (без дефолтов)
bin/forest-bootstrap.php  первичная установка (БД, админ, каталоги)
README.md           описание, установка, структура
CONTRIBUTING.md     правила веток и кода
SECURITY.md         как сообщать об уязвимостях
CHANGELOG.md        история изменений
LICENSE             лицензия проекта
```

## 2. Создать репозиторий и запушить

Нужен установленный Git. На Windows:

```powershell
winget install --id Git.Git -e --source winget
```

Затем в папке проекта (`_repo`):

```bash
git init -b main
git add -A
git commit -m "Forest OS 4.2: revival, node mode, mobile shell"
git remote add origin https://github.com/<ваш-логин>/forest-os.git
git push -u origin main
```

Перед первым коммитом проверьте, что в индексе нет лишнего:

```bash
git status --short
git ls-files | grep -E '(^\.env$|bd_inc\.php|instance\.foc|^data/|^system/users/)'
```

Вторая команда должна ничего не вывести.

## 3. Как работаем вместе

Схема, при которой никто никому не мешает:

1. `main` — стабильная версия, туда попадает только проверенное.
2. Каждая задача — отдельная ветка: `feature/имя` или `fix/имя`.
3. Изменения приходят Pull Request'ом; в описании — что сделано и как проверить.
4. Я (ассистент) работаю в этой же папке `_repo` (или в её клоне), делаю свою ветку
   и присылаю изменения так же — патчем или пушем, если есть доступ.

Полезные команды:

```bash
git checkout -b feature/my-idea     # новая ветка
git add -A && git commit -m "…"     # коммит
git push -u origin feature/my-idea   # ветка на GitHub
git pull --rebase origin main        # подтянуть свежее перед PR
```

## 4. Локальный запуск для проверки

```bash
cp .env.example .env      # и поменяйте пароли
docker compose up -d --build
```

Приложение: `http://localhost:8080`. Первый вход — логин/пароль из `.env`.
Bootstrap выполняется автоматически при старте контейнера
(`FOREST_SKIP_BOOTSTRAP=1` — пропустить).

## 5. Чего нельзя коммитить

* `.env` — пароли БД и администратора;
* `system/users/*` — данные пользователей, загруженные файлы, обои;
* `data/` — том базы данных;
* `system/core/library/bd_inc.php`, `system/instance.foc` — генерируются при установке;
* резервные копии `*.bak-*`, архивы `*.tar.gz`, временные `system/apps/update/temp`.

Всё это уже в `.gitignore`. Если случайно закоммитили секрет — смените пароль и
напишите нам (см. `SECURITY.md`).
