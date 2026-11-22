# Как выпускаются обновления Forest OS

Документ для владельца инсталляции и мейнтейнеров. В README этой информации нет —
там только то, что нужно пользователю.

## Сборка релиза

```bash
php bin/release.php --version=2.0 --notes="Первый публичный релиз" --codename="Wet Stone"
```

или в интерфейсе: **Update → «Опубликовать релиз»** (видно только суперпользователю).

Скрипт:

1. скачивает архив ветки `master` из GitHub (`FOREST_REPO_OWNER`, `FOREST_REPO_NAME`,
   `FOREST_REPO_BRANCH`);
2. перепаковывает его в `releases/forest-os-<версия>.zip`, исключая пользовательские
   данные (`.env`, `system/users`, `data`, `bd_inc.php`, `instance.foc`,
   `docs/screenshots`, резервные копии);
3. считает `sha256`, пишет `releases/update.json`;
4. оставляет 5 последних архивов.

## Как ставится обновление

Приложение **Update** читает `releases/update.json` этого сервера (если файла нет —
ленту GitHub Releases), показывает версию и описание. Суперпользователь нажимает
«Установить»:

* архив скачивается, проверяется по `sha256`;
* распаковывается поверх, не трогая `.env`, `system/users`, `data`,
  `system/instance.foc`, `system/core/library/bd_inc.php`;
* `system/core/osinfo.foc` обновляется (версия, сборка, кодовое имя), старая копия
  сохраняется как `osinfo.foc.bak-<дата>`.

## Права

* `install` и `release` доступны только суперпользователю и требуют CSRF-токен;
* можно ограничить одним логином: `FOREST_RELEASE_LOGIN=<логин>` в `.env`.

## Переменные окружения

| Переменная | Зачем |
|---|---|
| `FOREST_REPO_OWNER` | владелец репозитория (по умолчанию `hobbytes`) |
| `FOREST_REPO_NAME` | имя репозитория (по умолчанию `ForestOS`) |
| `FOREST_REPO_BRANCH` | ветка для сборки (по умолчанию `master`) |
| `FOREST_PUBLIC_HOST` | адрес, по которому сервер отдаёт `releases/` |
| `FOREST_RELEASES_URL` | явный базовый URL архива обновлений |
| `FOREST_UPDATE_FEED` | явная ссылка на `update.json` |
| `FOREST_RELEASE_LOGIN` | логин, которому разрешены релизы (по умолчанию — суперпользователь) |

## Альтернатива: GitHub Releases

В репозитории есть `.github/workflows/release.yml` — он собирает тот же архив и
`update.json` и прикладывает к релизу (запуск ручной, из вкладки Actions).

Чтобы внешние установки обновлялись напрямую из GitHub, укажите в
`system/core/update.json`:

```json
{ "feed": "https://github.com/<owner>/<repo>/releases/latest/download/update.json" }
```
