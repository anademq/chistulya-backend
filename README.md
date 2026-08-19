# Chistulya Backend API

GraphQL API детского приложения: ребёнок выполняет ежедневные задания и челленджи,
получает опыт, монеты и достижения, тратит монеты в магазине питомца. Родитель
привязывает детские аккаунты и следит за прогрессом.

Laravel 13 · PHP 8.3 · PostgreSQL · Redis · MinIO (S3) · Reverb (WebSocket)
Авторизация — JWT (`tymon/jwt-auth`) с refresh-сессиями.

## Эндпоинты

| Путь | Назначение |
|---|---|
| `POST /graphql` | Основная схема (ребёнок и родитель) |
| `POST /graphql/admin` | Админская схема |
| `wss://…/broadcasting/app/{key}` | Reverb, push-уведомления |
| `GET /up` | Health-check |

Всё остальное отдаёт `404 {"message": "Not Found"}` — это чистое API, HTML-страниц нет.

## Структура

- `app/GraphQL/Queries` · `Mutations` · `Types` — операции и типы схемы
- `app/GraphQL/Middleware` — guard-логика (роли, подписка, лимиты)
- `app/GraphQL/Support/ErrorFormatter.php` — единый формат ошибок
- `app/Services` — бизнес-логика домена
- `app/Console/Commands` — фоновые задачи и обслуживание
- `lang/ru` — тексты API, валидации и ошибок
- `docker/` — образ, nginx, entrypoint

## Документация

- [docs/frontend-guide.md](docs/frontend-guide.md) — форматы ответов и коды ошибок для клиента
- [docs/project-overview.md](docs/project-overview.md) — обзор домена
- [docs/categories-guide.md](docs/categories-guide.md) — работа с категориями

---

# Деплой

Стек поднимается одной командой: `app` (php-fpm), `nginx`, `queue`, `scheduler`,
`reverb`, `postgres`, `redis`, `minio`. Вся конфигурация — в одном файле `.env`.

## Первый запуск на чистом сервере

```bash
git clone https://github.com/anademq/chistulya-backend.git
cd chistulya-backend

cp .env.example .env
```

Заполнить в `.env` секреты. Минимум: `APP_KEY`, `POSTGRES_PASSWORD`,
`REDIS_PASSWORD`, `JWT_SECRET`, `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`,
`REVERB_APP_*`.

> `DB_*` и `AWS_*` **не заполняются вручную** — они выводятся из `POSTGRES_*` и
> `MINIO_ROOT_*` прямо в `.env`. Один источник правды, разъехаться не может.

Проверить, что ничего не забыто:

```bash
make check
```

Выпустить сертификат (без него nginx не стартует):

```bash
certbot certonly --webroot -w /var/www/ssl \
  -d api.xn--h1agrefu5d.xn--p1ai \
  -d storage.xn--h1agrefu5d.xn--p1ai
```

Поднять стек:

```bash
make up
```

Миграции, создание бакета MinIO и прогрев кешей произойдут сами при старте
контейнера `app`. Ручных шагов нет.

## Обычный деплой

```bash
git pull && make deploy
```

Последовательность: проверка конфига → дамп БД в `./backups` → сборка образа →
перезапуск → ожидание `healthy`. Если приложение не поднялось за 200 секунд,
команда падает с ненулевым кодом.

## Откат

```bash
make restore f=backups/db-20260818-120000.sql.gz
git checkout <прошлый-коммит>
make deploy
```

## Команды

```
make help       список целей
make ps         статус контейнеров
make logs       логи всех сервисов (make logs s=app — одного)
make stats      потребление CPU и памяти
make shell      шелл в контейнере app
make backup     дамп БД (хранятся последние 14)
make migrate    накатить миграции
make provision  пересоздать бакет и политики MinIO
```

Makefile — не обязателен, всё то же делается через `docker compose`. Он лишь
добавляет предполётную проверку секретов, правильный порядок шагов в деплое и
бэкапы с ротацией.

## Требования к серверу

- Docker с Compose v2
- ~4 ГБ RAM. Лимиты памяти проставлены в `docker-compose.yml` — **подгоните под
  свою машину**, значения выставлены с запасом.
- Опционально: включённый swap (см. ниже)

## Swap

Swap — файл на диске, который ядро использует как «запасную» оперативную память:
когда RAM заканчивается, редко используемые страницы вытесняются туда.

Без него нехватка памяти означает мгновенный OOM-kill: ядро выбирает самый
«жирный» процесс и убивает его. На этом сервере самый жирный — почти всегда
postgres. То есть пик нагрузки роняет базу.

Со swap система в такой момент просто замедляется, а не падает. Это не замена
памяти (диск на порядки медленнее), а страховка от внезапной смерти.

Проверить, есть ли:

```bash
free -h
```

Если в строке `Swap` нули — создать 4 ГБ:

```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Последняя строка нужна, чтобы swap подключался после перезагрузки.

## Логи

Пишутся в stderr, забираются Docker, ротируются автоматически (10 МБ × 3 файла).

```bash
make logs s=app
```

