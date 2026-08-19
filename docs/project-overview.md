# Chistulya Backend — полное описание проекта

> Документ предназначен для другого ИИ-агента: даёт исчерпывающий контекст по кодовой базе, архитектуре, домену, API, бизнес-логике, инфраструктуре и соглашениям. Все идентификаторы (классы, поля, операции) приведены как в коде. Точки истины: `config/graphql.php`, `app/**`, `database/migrations/**`.

## Содержание

1. [Что это за проект](#1-что-это-за-проект)
2. [Технологический стек](#2-технологический-стек)
3. [Архитектура](#3-архитектура)
4. [Структура каталогов](#4-структура-каталогов)
5. [Аутентификация и авторизация](#5-аутентификация-и-авторизация)
6. [Доменная модель](#6-доменная-модель)
7. [База данных](#7-база-данных)
8. [GraphQL API](#8-graphql-api)
9. [Сервисный слой и бизнес-потоки](#9-сервисный-слой-и-бизнес-потоки)
10. [Кэширование](#10-кэширование)
11. [Планировщик и фоновые задачи](#11-планировщик-и-фоновые-задачи)
12. [WebSockets (Reverb)](#12-websockets-reverb)
13. [Медиа и файловое хранилище](#13-медиа-и-файловое-хранилище)
14. [Подписки и платежи](#14-подписки-и-платежи)
15. [Аналитика](#15-аналитика)
16. [Тестирование](#16-тестирование)
17. [Конфигурация, окружение, деплой](#17-конфигурация-окружение-деплой)
18. [Соглашения разработки](#18-соглашения-разработки)
19. [Известные нюансы (gotchas)](#19-известные-нюансы-gotchas)
20. [Существующая документация](#20-существующая-документация)

---

## 1. Что это за проект

**Chistulya (Чистюля/Чистуля)** — бэкенд геймифицированного мобильного/веб-приложения, помогающего детям формировать полезные привычки (гигиена, порядок, еда, учёба) через игровые механики. Родители управляют детьми, дети выполняют задания и получают награды.

**Ключевые доменные механики:**
- **Ежедневные задания (Daily Tasks)** — выбрал → выполнил → забрал награду (XP + монеты). Сбрасываются в полночь по таймзоне ребёнка.
- **Челленджи (Challenges)** — многодневные задачи с ежедневным прогрессом; пропуск дня = провал. Полный сброс результатов в начале месяца.
- **Достижения (Achievements)** — авто-выдаются при выполнении набора заданий/челленджей (+ опционально подписка).
- **Ежедневная награда (Daily Reward)** — стрик за ежедневный вход.
- **Напоминания (Reminders)** — расписания (once/daily/weekly), доставляются push-уведомлением через WebSocket.
- **Магазин питомца (Pet Shop)** — покупка предметов гардероба за монеты, экипировка.
- **Экономика** — XP/уровни (100 XP = уровень) и монеты (Wallet).
- **Семья** — родитель↔ребёнок связываются через одноразовый токен; родитель действует от имени ребёнка.
- **Подписки/платежи** — платные планы; дети наследуют подписку родителя.
- **Аналитика** — агрегаты выборов/выполнений по категориям и датам для админки/родителя.
- **Админка** — отдельная GraphQL-схема для управления контентом, пользователями, статами.

Аудитория ролей: **дети**, **родители**, **администраторы**.

---

## 2. Технологический стек

| Слой | Технология |
|---|---|
| Язык | PHP 8.3 |
| Фреймворк | Laravel 13 |
| API | GraphQL через `rebing/graphql-laravel` ^9.17 (НЕ REST) |
| Аутентификация | JWT (`tymon/jwt-auth` ^2.3) поверх собственных Session/RefreshToken |
| WebSockets | Laravel Reverb ^1.10 (протокол Pusher) |
| БД (prod) | PostgreSQL; тесты — SQLite; код поддерживает MySQL/pgsql/sqlite |
| Кэш/очереди/сессии (prod) | Redis (`predis`) |
| Файлы | S3-совместимое хранилище (MinIO в prod) через `league/flysystem-aws-s3-v3` |
| Почта | SMTP + очередь |
| Детект устройств | `jenssegers/agent` (парсинг User-Agent) |
| Тесты | PHPUnit ^12 (НЕ Pest) |
| Стиль кода | Laravel Pint |
| Локаль | русская (`ru`) по умолчанию |

`routes/api.php` существует, но **не подключён** (закомментирован в `bootstrap/app.php`). Весь публичный трафик идёт через GraphQL-эндпоинты. `routes/web.php` содержит только 404-fallback.

---

## 3. Архитектура

Многослойная, «толстые сервисы — тонкие резолверы»:

```
HTTP → GraphQL endpoint (/graphql | /graphql/admin)
   → HTTP middleware (request.expects_json, throttle:graphql)
   → GraphQL execution middleware (validate params, APQ, add auth user)
   → Query/Mutation resolver (тонкий; middleware auth.jwt / roles на уровне поля)
       → Service (бизнес-логика, транзакции, блокировки, кэш, валидация)
           → Eloquent Models → БД
   → PayloadType / Type (форматирование ответа)
   → ErrorFormatter (единый формат ошибок)
```

**Две GraphQL-схемы** (в одном приложении, `config/graphql.php`):
- `default` — приложение для детей/родителей + публичная авторизация. Эндпоинт `POST/GET /graphql`.
- `admin` — админ-панель (CRUD контента, пользователи, статы, аналитика). Эндпоинт `POST/GET /graphql/admin`.

Схемы делят все зарегистрированные **типы** глобально, но имеют непересекающиеся наборы queries/mutations.

**Принципы:**
- Вся бизнес-логика — в `app/Services/*`. Резолверы только валидируют вход, вызывают сервис, оборачивают результат в payload.
- Мутации возвращают payload-объект `{ success, errors, ...данные }` и почти никогда не бросают ошибку на top-level (клиент всегда получает HTTP 200).
- Идентичность сущностей — UUID для «крупных» сущностей, автоинкремент для справочников/аналитики.

---

## 4. Структура каталогов

```
app/
  Console/Commands/SendReminderNotifications.php   # крон-команда рассылки напоминаний
  Enums/                                           # 14 backed-энумов (роли, статусы, scope, платежи)
  Events/ReminderNotificationSent.php              # единственный broadcast-эвент
  GraphQL/
    Queries/       (Account/, Admin/, Child/, Parent/, + корневые)
    Mutations/     (Account/, Admin/, Admin/Category/, Child/, Parent/, + корневые)
    Types/         (доменные типы, Types/Payloads/, Types/Errors/)
    Support/ErrorFormatter.php                     # единый форматтер ошибок
    Middleware/GraphQLThrottleOperations.php       # пооперационный троттлинг
  Http/Middleware/  AuthenticateWithJwt, EnsureEmailVerified,
                    EnsureUserRole, EnsureUserProfileRole, RequestExpectsJson
  Models/          (+ Auth/, Child/, Child/Assignment/, User/)
  Notifications/ActionMailNotification.php          # письма (verify email / reset password), queued
  Providers/       AppServiceProvider, GraphQLServiceProvider
  Services/        16 сервисов — ядро бизнес-логики
config/            graphql.php (схемы), jwt, broadcasting, media, filesystems, services, ...
database/
  migrations/      17 миграций (0001_* базовые + 2026_* аналитика)
  seeders/         DatabaseSeeder + DailyTaskCategorySeeder/ChallengeCategorySeeder/PetItemCategorySeeder
  factories/
routes/            web.php (404), api.php (не подключён), console.php (планировщик), channels.php (WS)
tests/             Feature/ (+ Admin/, GraphQL/, Services/), Unit/, Concerns/ (тест-трейты)
docs/              frontend-guide.md, categories-guide.md, project-overview.md (этот файл)
bootstrap/app.php  # алиасы middleware, подключение роутов
```

---

## 5. Аутентификация и авторизация

### JWT поверх собственных сессий
- При логине/регистрации `AuthTokenService::issueTokens()` создаёт запись `Session`, хранит **SHA-256 хэш** refresh-токена (`RefreshToken`), выдаёт JWT с claim `sid` (= id сессии). TTL: access — `jwt.ttl` (60 мин), refresh — `jwt.refresh_ttl` (~14 дней).
- Plain-токены отдаются клиенту только один раз; в БД лежат только хэши (относится и к verification/reset/link токенам).
- `refreshToken` ротирует refresh-токен (помечает `used_at`, отзывает остальные на сессии), выдаёт новый access.
- `logout` отзывает текущую/указанную/все сессии. Смена пароля (`resetPassword`) отзывает все сессии.

### Middleware (алиасы в `bootstrap/app.php`)
| Алиас | Класс | Назначение |
|---|---|---|
| `auth.jwt` | `AuthenticateWithJwt` | Проверяет bearer JWT, наличие валидного `sid`, не отозванную сессию и хотя бы один живой refresh-токен. Устанавливает пользователя. |
| `user.email.verified` | `EnsureEmailVerified` | Требует подтверждённый email. |
| `user.profile.role` | `EnsureUserProfileRole` | Требует роль профиля (`child`/`parent`). |
| `user.role` | `EnsureUserRole` | Требует системную роль (`admin`,`sudo_admin`). |
| `request.expects_json` | `RequestExpectsJson` | Форсирует JSON-контекст. |

Параметризуемые: `user.role:admin,sudo_admin`, `graphql.throttle:3,1`.

### Двухуровневая модель ролей (ВАЖНО)
- **`users.role`** → `UserRole` = `user | admin | sudo_admin` — системный/авторизационный уровень. Админ определяется здесь (`User::isAdminUser()`).
- **`profiles.role`** → `ProfileRole` = `child | parent` — «семейная» персона. Ребёнок/родитель определяется через профиль (`User::isChild()/isParent()`), НЕ через `users.role`.
- Каждый аккаунт — это `User`; child/parent — это 1:1-расширение `Profile`.

### Матрица доступа
| Кто | Стек middleware |
|---|---|
| Публичная мутация (register/login/verify/reset) | нет |
| Любой авторизованный | `auth.jwt` |
| Родитель | JWT + verified + `profile.role:parent` |
| Ребёнок | JWT + verified + `profile.role:child` |
| Админка | JWT + verified + `user.role:admin,sudo_admin` |
| `deleteUser` | JWT + verified + `user.role:sudo_admin` |

Базовые классы резолверов инкапсулируют эти стеки: `AuthedQuery/AuthedMutation`, `ParentAuthed*`, `ChildAuthed*`, `AdminQuery`, `AdminMutation`, `PayloadMutation`.

---

## 6. Доменная модель

33 Eloquent-модели. UUID PK у «крупных» сущностей (`HasUuids`); справочники категорий/аналитика/уведомления — автоинкремент.

### Пользователи и авторизация
- **User** (`users`, UUID) — `HasUuids`, `MustVerifyEmail`, `JWTSubject`. Поля: `email` (unique), `password` (hashed), `role` (`UserRole`). Отношения: `profile` (HasOne), `sessions`, `refreshTokens` (HasManyThrough), `verificationTokens`, `linkTokens`, `parents`/`children` (BelongsToMany self через `user_links`), `exp`/`wallet`/`dailyReward` (HasOne, по `child_id`), `child*` прогресс-таблицы, `userSubscription`, `payments`, `uploadedMedia`.
- **Profile** (`profiles`, PK=`user_id`) — `name`, `sex`, `role` (`ProfileRole`), `date_of_birth`, `city`, `timezone` (default `Europe/Moscow`).
- **Session / RefreshToken / VerificationToken** (`Auth/*`, UUID) — сессии, ротация refresh, одноразовые токены (verify email / reset password).

### Семья
- **LinkToken** (`link_tokens`, UUID) — одноразовый токен приглашения, который генерирует ребёнок.
- **UserLink** (`User/UserLink`, композитный PK `[child_id, parent_id]`, без timestamps) — связь родитель↔ребёнок, pivot `linked_at`.

### Статы ребёнка
- **Exp** (`exps`, PK=`child_id`) — `level` (default 1), `xp`.
- **Wallet** (`wallets`, PK=`child_id`) — `coins` (bigint).
- **ChildDailyReward** (`child_daily_rewards`, PK=`child_id`) — `current_day`, `last_claimed_at`.

### Каталог контента (шаблоны, UUID, SoftDeletes)
- **DailyTask** (`daily_tasks`) — `scope` (`DailyTaskScope`), `category_id`, `title`, описания, `reward_xp/coins`. Scope: `global|parent|assigned`.
- **Challenge** (`challenges`) — то же + `duration_days`. Scope: `global|parent|assigned`.
- **Achievement** (`achievements`) — `is_available`, `requirements` (кастуется в `AchievementRequirements`), награды.
- **PetItem** (`pet_items`) — `category_id`, `is_available`, `requirements` (json), `price`.
- **Reminder** (`reminders`) — `scope` (`ReminderScope`: `global|parent|assigned|child`), `repeating_pattern` (`once|daily|weekly`), `date`, `time`, `repeating_days` (char(7) битовая маска Пн–Вс), `status`.
- **Subscription** (`subscriptions`) — `is_available`, `duration_days`, `price` (decimal).
- **DailyReward** (`daily_rewards`, PK=`day` smallint) — награды за дни стрика.
- **Категории**: **DailyTaskCategory**, **ChallengeCategory**, **PetItemCategory** — автоинкремент `id`, `slug` (unique), `title`, `order_column`.
- **Media** (`media`, UUID) — полиморфная привязка (`mediable`), S3 `disk`/`path`, `order_column`.

### Assignment-пивоты (scope = assigned)
- **DailyTaskAssignment**, **ChallengeAssignment**, **ReminderAssignment** — композитный PK `[entity_id, child_id]`, без timestamps, каскад. Определяют, какому ребёнку виден `assigned`-контент.

### Прогресс/состояние ребёнка (per-child)
- **ChildDailyTask** (`child_daily_tasks`) — `status` (`selected|completed|reward_claimed`), `completed_at`, `reward_claimed_at`.
- **ChildChallenge** (`child_challenges`) — `status` (`selected|in_progress|completed|failed|reward_claimed`), `progress_days`, `last_progress_at`, метод `hasSkippedDay(tz)`.
- **ChildAchievement** (`child_achievements`) — `status` (`in_progress|completed|reward_claimed`).
- **ChildPetItem** (`child_pet_items`) — `is_equipped`, `purchased_at`.
- **ChildReminder** (`child_reminder_notifications`, автоинкремент) — инбокс уведомлений: `sent_at`, `seen_at`.

### Подписки/платежи
- **UserSubscription** (PK=`user_id`) — `subscription_id`, `auto_renew`, `started_at`, `expires_at`.
- **Payment** (UUID) — полиморфный `payable`, `method` (`PaymentMethod`), `invoice_id`, `currency` (`PaymentCurrency`), `amount`, `status` (`PaymentStatus`), `payload`, тайминги. Unique `[method, invoice_id]`.

### Аналитика
- **DailyTaskAnalytic** / **ChallengeAnalytic** (автоинкремент) — агрегаты по `(child_id, category_id, date)`; счётчики `selected_count`/`completed_count`(/`failed_count`). Unique `[child_id, category_id, date]`.

### Enums (`app/Enums/`, 14 шт.)
`UserRole`, `ProfileRole`, `DailyTaskScope`, `ChallengeScope`, `ReminderScope`, `DailyTaskStatus`, `ChallengeStatus`, `AchievementStatus`, `ReminderStatus`, `ReminderRepeatPattern`, `VerificationTokenType`, `PaymentMethod`, `PaymentCurrency`, `PaymentStatus`.

### Видимость контента по scope
| Scope | Кому виден |
|---|---|
| `global` | всем детям |
| `parent` | детям, привязанным к автору-родителю (через `user_links`) |
| `assigned` | ребёнку из соответствующего `*_assignments` пивота |
| `child` (только напоминания) | автору (`created_by = child.id`) |

Реализовано в model scope `scopeAvailableFor($child)` у `DailyTask`/`Challenge` (у `Challenge` дополнительно исключаются уже выбранные), и в `ReminderService::listForChild()`.

---

## 7. База данных

17 миграций. Базовые датируются `0001_01_01_*` (порядок задаёт нумерацию таблиц), аналитика — `2026_05_02_153744`.

**Ключевые правила целостности:**
- FK на пользователя (`child_id`/`user_id`) — почти всегда **cascadeOnDelete**.
- `daily_tasks.category_id`, `challenges.category_id`, `pet_items.category_id` → категории с **restrictOnDelete** (нельзя удалить категорию с привязанными сущностями — это отдельно проверяется в `AbstractDeleteCategoryMutation`, учитывая и soft-deleted).
- `media.created_by`, `*.created_by` → **nullOnDelete**.
- `*_analytics.category_id` → **nullOnDelete**; `child_id` → cascade.
- Уникальные индексы: `users.email`, `*_categories.slug`, `payments[method,invoice_id]`, `*_analytics[child_id,category_id,date]`, хэши токенов.

**Инфраструктурные таблицы** (без Eloquent-моделей): `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

**Кроссбазовость:** сервисы, использующие сырой SQL (аналитика upsert, месячная группировка), ветвятся по драйверу (`mysql`/`pgsql`/`sqlite`); таймстемпы биндятся параметрами (не `NOW()`), чтобы работать в SQLite тестах.

Полная поколоночная спецификация каждой таблицы — в миграциях `database/migrations/**`.

---

## 8. GraphQL API

Пакет `rebing/graphql-laravel`. Точка истины по регистрации операций и типов — `config/graphql.php`.

### Эндпоинты и middleware
- `default`: `POST/GET /graphql`, HTTP-middleware `request.expects_json`, `throttle:graphql` (5 rps/IP, задаётся в `AppServiceProvider`).
- `admin`: `POST/GET /graphql/admin`, те же HTTP-middleware.
- Execution middleware (обе схемы): `ValidateOperationParamsMiddleware` → `AutomaticPersistedQueriesMiddleware` → `AddAuthUserContextValueMiddleware`.
- Батчинг включён.

### Формат payload мутаций
Все мутации возвращают payload с обязательными `success: Boolean!` и `errors: [MutationError!]!` (+ данные операции). `MutationPayload` — только `success/errors`. Базовый класс — `Types/Payloads/PayloadType`.

`MutationError` — union из трёх типов (различать по `__typename`):
- `ValidationError { message, fields { field, messages } }`
- `RateLimitError { message, retryAfter }`
- `InvalidActionError { message }`

`PayloadMutation` ловит исключения в резолвере и конвертит их в payload-`errors` (валидация → ValidationError; троттлинг → RateLimitError; `InvalidActionException` → InvalidActionError). В резолверах для этого используется хелпер `wrapPayload(callable)`.

### Top-level ошибки (`ErrorFormatter`)
Формат: `{ message, extensions: { code, ... } }`. Коды (`ErrorCode`): `UNAUTHENTICATED`, `FORBIDDEN`, `INVALID_ACTION`, `VALIDATION`, `RATE_LIMITED`, `BAD_REQUEST`, `INTERNAL_SERVER_ERROR`. Ошибки авторизации всегда top-level (выбрасываются middleware до резолвера). Детали — `docs/frontend-guide.md`.

### Пагинация
`GraphQL::paginate('Type')` → тип `{Type}Paginator` с `data: [Type!]!` и `paginatorInfo`. Аргументы `page` (1-based), `per_page` (в резолверах ограничивается 100). Дефолтный `per_page` варьируется: 10–20 (default-схема), 30 (admin-схема).

### Пооперационный троттлинг (`graphql.throttle` → `GraphQLThrottleOperations`)
Ключ `graphql:throttle:{operation}:{userId|ip}`. Применён только к auth-мутациям: `register` 3/мин, `login` 3/мин, `refreshToken` 10/мин, `verifyEmail` 5/мин, `requestPasswordReset` 3/мин (+ доп. лимит по email в резолвере), `requestEmailVerification` 1/мин.

### Операции — `default` схема

**Queries** (имя `name` — как в GraphQL):
- Account/Auth: `me`, `mySessions`, `myActiveSubscription`.
- Категории (нужен JWT): `dailyTaskCategories`, `challengeCategories`, `petItemCategories`.
- Семья: `myChildren` (parent), `myParents` (child), `childDashboard` (child; кошелёк/XP/день награды/флаг подписки), `childProgressSummary(child_id!)` (parent).
- Ежедневные задания: `availableDailyTasks`, `selectedDailyTasks` (оба: `page`,`per_page`,`child_id?`,`category_id?`).
- Челленджи: `availableChallenges`, `selectedChallenges` (те же аргументы).
- Достижения: `myAchievements(child_id?)`.
- Напоминания: `myReminders(completed?,page,per_page,child_id?)`, `notifications(unread_only?,page,per_page)` (child).
- Магазин: `petCatalog(category_id?,page,per_page)`, `myPetItems(child_id?)`.
- Аналитика: `dailyTaskAnalytics(days?,category_id?,child_id?)`, `challengeAnalytics(months?,category_id?,child_id?)`.
- Подписки/платежи: `subscriptions` (parent, кэш), `myPayments(page,per_page)` (parent).

**Mutations:**
- Auth (public): `register`, `login`, `refreshToken`, `verifyEmail`, `requestPasswordReset`, `resetPassword`.
- Account (JWT): `logout(session_id?)`, `requestEmailVerification`, `upsertProfile`, `updatePassword`, `requestMediaUpload`, `confirmMediaUpload`.
- Семья: `generateFamilyLinkToken(ttl_minutes?)` (child), `linkChildByToken(token!)` (parent), `unlinkChild(child_id!)` (parent).
- Задания (child): `selectDailyTask`, `unselectDailyTask`, `completeDailyTask`, `claimDailyTaskReward`.
- Кастомные задания (parent): `createCustomDailyTaskForChild`, `updateCustomDailyTaskForChild`, `deleteCustomDailyTaskForChild`.
- Челленджи (child): `selectChallenge`, `startChallenge`, `logChallengeProgress`, `claimChallengeReward`.
- Награды/достижения (child): `claimDailyReward`, `claimAchievementReward`.
- Напоминания: `createReminder` (child self или parent для ребёнка), `completeReminder`, `activateReminder`, `updateReminder`, `deleteReminder`, `createCustomReminderForChild` (parent), `markNotificationsAsRead`.
- Магазин (child): `purchasePetItem`, `equipPetItem`, `unequipPetItem`.
- Подписки/платежи (parent): `subscribe`, `renewSubscription`, `cancelSubscription`, `createSubscriptionPayment`, `confirmSubscriptionPayment`.

### Операции — `admin` схема (JWT + verified + admin/sudo_admin)

**Queries:** `users(profile_role?,page,per_page)`, `user(user_id!)`, `subscriptions`, `dailyTasks(…,category_id?)`, `challenges(…,category_id?)`, `achievements`, `petItems(…,category_id?)`, `reminders`, `dailyRewards`, `dailyTaskAnalytics(child_id!,…)`, `challengeAnalytics(child_id!,…)`. Списки контента включают soft-deleted.

**Mutations:**
- Пользователи: `createUser`, `updateUser`, `upsertUserProfile`, `deleteUser` (**sudo_admin**), `revokeUserSessions`.
- Статы: `setExp`, `adjustExp`, `setCoins`, `adjustCoins`.
- Питомец: `grantPetItem`, `revokePetItem`, `clearPetItems`.
- Семья: `linkChild(parent_id!,child_id!)`, `unlinkChild(parent_id!,child_id!)`.
- Подписки: `grantUserSubscription`, `revokeUserSubscription`, `createSubscription`, `updateSubscription`, `deleteSubscription`.
- Контент CRUD: `createDailyTask`/`updateDailyTask`/`deleteDailyTask`, `createChallenge`/`updateChallenge`/`deleteChallenge`, `createAchievement`/`updateAchievement`/`deleteAchievement`, `createPetItem`/`updatePetItem`/`deletePetItem`, `createReminder`/`updateReminder`/`deleteReminder`, `createDailyReward`/`updateDailyReward`.
- Категории: `create/update/deleteDailyTaskCategory`, `create/update/deleteChallengeCategory`, `create/update/deletePetItemCategory`.
- Медиа (общие с default): `requestMediaUpload`, `confirmMediaUpload`.

> Полный справочник аргументов и семантики каждой операции — в коде `app/GraphQL/**` и `config/graphql.php`. По категориям есть отдельный гайд: `docs/categories-guide.md`.

**Важно (категории):** во ВСЕХ операциях категория указывается числовым `category_id: Int`, а `slug` — только для фронтенда (отображение). Подробности и миграция со slug→id — в `docs/categories-guide.md`.

---

## 9. Сервисный слой и бизнес-потоки

16 сервисов в `app/Services/`. Каталоги `app/Jobs`, `app/Listeners`, `app/Observers` пусты.

| Сервис | Ответственность |
|---|---|
| `AuthTokenService` | Выпуск/ротация/отзыв JWT+сессий+refresh. Транзакции, `lockForUpdate`. |
| `VerificationTokenService` | Одноразовые токены verify email / reset password (SHA-256, отзыв прежних). |
| `CaptchaService` | Проверка hCaptcha (no-op если выключено). |
| `DailyTaskService` | Жизненный цикл заданий; сброс просроченных по таймзоне; CRUD категорий. |
| `ChallengeService` | Жизненный цикл челленджей; timezone-aware провал/сброс; CRUD категорий. |
| `RewardService` | Единая точка начисления XP/уровня/монет (`XP_PER_LEVEL=100`). |
| `AchievementService` | Авто-синхронизация и выдача достижений. |
| `DailyRewardService` | Стрик ежедневной награды. |
| `ReminderService` | CRUD напоминаний, видимость по scope, инбокс, лимит 20 завершённых. |
| `PetShopService` | Покупка/экипировка; версионирование кэша каталога. |
| `FamilyService` | Токены привязки, линк/анлинк, проверки доступа родителя к ребёнку. |
| `SubscriptionService` | Активная/наследуемая подписка, subscribe/renew. |
| `PaymentService` | Создание/подтверждение платежа-инвойса. |
| `AnalyticsService` | Инкремент счётчиков (атомарный upsert) + чтение плотных рядов (кэш 10 мин). |
| `MaintenanceService` | Плановые сбросы заданий/челленджей, очистка «сиротской» медиа. |
| `MediaService` | Presigned S3-загрузки, привязка/финализация/очистка медиа. |

### Ключевые потоки

**Auth:** `register` → captcha → `User::create` → письмо верификации (queued) → `issueTokens`. `login` → captcha → отказ, если уже есть активная сессия (`guardAlreadyLoggedIn`) → проверка пароля → `issueTokens`. `refreshToken` → ротация. `resetPassword` → смена пароля + отзыв всех сессий.

**Daily Task:** `available` (scope global/parent/assigned) → `select` (firstOrCreate `SELECTED`; аналитика selected при первом создании) → `complete` (`COMPLETED`+`completed_at`; аналитика completed) → `claim` (TX+lock; `REWARD_CLAIMED` + `RewardService::grant`). Сброс: лениво в `listSelected` (`resetStaleForChild`) и по крону в полночь таймзоны ребёнка.

**Challenge:** `select` (`SELECTED`,`progress_days=0`) → `start` (`IN_PROGRESS`) → `logChallengeProgress` (TX+lock; если пропущен день → `FAILED`+аналитика failed+ошибка; иначе +1 день, при достижении `duration_days` → `COMPLETED`) → `claim`. Таймзона ребёнка из `profiles.timezone`. Провал просроченных и удаление FAILED прошлого дня — крон; полный месячный сброс — 1-го числа.

**Rewards:** единый `RewardService::grant($child,$xp,$coins)` — накопительный XP, `level=floor(totalXp/100)+1` (мин. 1), монеты в Wallet. Источники: клейм задания/челленджа/достижения/ежедневной награды.

**Achievements:** при запросе `myAchievements` → `syncAndList`: для каждого не-`REWARD_CLAIMED` достижения проверяются требования (все указанные задания+челленджи выполнены/заклеймлены + опц. наследуемая подписка) → upsert `COMPLETED`/`IN_PROGRESS`. Клейм переводит в `REWARD_CLAIMED` и начисляет награду.

**Reminders + рассылка:** команда `reminders:send` каждую минуту: по каждой таймзоне ребёнка UNION-SQL находит активные напоминания, совпадающие с локальным `H:i` (все 4 scope) → PHP-фильтр по паттерну (daily/weekly-битмаск/once-дата) → дедуп по `(reminder, child)` за сутки → вставка `ChildReminder` + broadcast `ReminderNotificationSent`.

**Pet Shop:** `petCatalog` (версионированный кэш) → `purchase` (проверка наследуемой подписки при `subscription_required`; идемпотентно; блокировка Wallet, списание монет) → `equip` (снимает все в той же категории, надевает целевой).

**Family:** ребёнок `generateFamilyLinkToken` → родитель `linkChildByToken` (`UserLink::firstOrCreate`, токен одноразовый) → `unlinkChild`. Доступ родителя к ребёнку — `assertParentAccessToChild`.

**Подписки/платежи:** `createSubscriptionPayment` (PENDING-инвойс, TTL 30 мин) → `confirmSubscriptionPayment` (TX; `renew` + `PAID`). Дети наследуют подписку родителя (`childHasInheritedSubscription`).

---

## 10. Кэширование

| Ключ | TTL | Кто пишет | Инвалидция |
|---|---|---|---|
| `categories:daily_tasks` | 1 день | `DailyTaskCategoriesQuery` | `DailyTaskService` CRUD → `Cache::forget` |
| `categories:challenges` | 1 день | `ChallengeCategoriesQuery` | `ChallengeService` CRUD |
| `categories:pet_items` | 1 день | `PetItemCategoriesQuery` | `PetShopService` CRUD |
| `pet_catalog:version` | счётчик | — | `Cache::increment` при admin CRUD питомца/категории |
| `graphql:pet_catalog:v{ver}:{cat}:{page}:{perPage}` | 30 мин | `PetCatalogQuery` | инкремент версии |
| `graphql:subscriptions:available` | 1 час | `SubscriptionsQuery` | admin CRUD подписок → forget |
| `daily_rewards:all` | 1 день | `DailyRewardService` | `flushCache()` при admin CRUD наград |
| `analytics:tasks:{child}:{days}:{cat\|all}` | 10 мин | `AnalyticsService` | только по TTL |
| `analytics:challenges:{child}:{months}:{cat\|all}` | 10 мин | `AnalyticsService` | только по TTL |

**Паттерн кэша категорий (важно!):** кэшируются **plain-массивы** (`->toArray()`), а модели восстанавливаются `Model::hydrate()`. Это сделано специально, чтобы устаревшая сериализованная Eloquent-коллекция не десериализовалась в `__PHP_Incomplete_Class` (была такая прод-ошибка). Не откатывать на кэширование самих моделей.

---

## 11. Планировщик и фоновые задачи

`routes/console.php` (нужен `php artisan schedule:run` раз в минуту в prod):

| Команда | Расписание | Действие |
|---|---|---|
| `maintenance:daily-reset` | каждую минуту | `resetDailyTasksAtMidnight` + `failSkippedChallenges` + `resetFailedChallengesAtMidnight` (по таймзонам) |
| `maintenance:monthly-reset` | 1-го числа 00:10 | `resetMonthlyChallengeResults` (глобальный сброс результатов челленджей) |
| `reminders:send` | каждую минуту | `SendReminderNotifications` (рассылка напоминаний) |

`MaintenanceService::cleanupOrphanMedia` существует, но НЕ в расписании.

Очереди — Redis; письма (`ActionMailNotification`) отправляются через очередь (`ShouldQueue`).

---

## 12. WebSockets (Reverb)

- Конфиг `config/broadcasting.php`; prod `BROADCAST_CONNECTION=reverb`.
- Авторизация канала: `POST /broadcasting/auth` под `auth.jwt` (`AppServiceProvider::boot` → `Broadcast::routes`).
- Канал: `routes/channels.php` — приватный `child.{childId}`, разрешён если `$user->id === $childId`.
- Событие: `ReminderNotificationSent` (`ShouldBroadcast`), имя события `.reminder.notification`, канал `PrivateChannel("child.{childId}")`.
- Payload: `id`, `reminder_id`, `title`, `short_description`, `description`, `time`, `date`, `repeating_pattern`, `repeating_days`, `scope`, `sent_at`.
- Клиент: Laravel Echo + pusher-js. Подробности подключения — `docs/frontend-guide.md` §7.

---

## 13. Медиа и файловое хранилище

- Диск `s3` (в prod — MinIO, path-style). `MediaService` выдаёт presigned URL на загрузку (TTL 1ч), создаёт `Media` в pending-состоянии (tmp-префикс, `uploaded_at = null`).
- Двухшаговый флоу: `requestMediaUpload` → клиент грузит на S3 → `confirmMediaUpload`/`attachToEntity` (проверка mime/размера из `config/media`, перенос tmp→permanent, полиморфная привязка).
- Медиа полиморфно привязывается к DailyTask/Challenge/Achievement/PetItem/Subscription (`mediable`).
- «Сиротские» медиа (pending старше N часов) чистятся `MediaService::cleanupOrphans` (не в расписании).

---

## 14. Подписки и платежи

- Планы (`Subscription`) с `duration_days` и `price`. Список доступных кэшируется.
- Прямая подписка (`UserSubscription`, активна если `expires_at > now()`). `renew` наращивает длительность от текущего будущего срока; `subscribe` создаёт/сбрасывает.
- Платёж: PENDING-инвойс (`invoice_id`, TTL 30 мин) → подтверждение → `renew` + `PAID`. Просроченный PENDING помечается `EXPIRED`.
- **Наследование:** ребёнок считается имеющим подписку, если активная подписка есть у любого привязанного родителя (`SubscriptionService::childHasInheritedSubscription`). Используется в достижениях и покупках питомца.

---

## 15. Аналитика

- Запись: атомарный upsert в `daily_task_analytics`/`challenge_analytics` по ключу `(child_id, category_id, date)` — счётчики selected/completed(/failed). Инкременты вызываются инлайн из `DailyTaskService`/`ChallengeService`. SQL кроссбазовый (mysql/pgsql/sqlite), таймстемпы биндятся параметрами.
- Чтение: `dailyTasksByLastDays` (плотный ряд по дням, 1–90), `challengesByLastMonths` (по месяцам, 1–12), zero-filled, кэш 10 мин. Фильтр — по `category_id` (Int).
- Доступ: пользовательские queries (ребёнок про себя / родитель по `child_id`) и админские (`child_id!` — любой ребёнок).

---

## 16. Тестирование

- PHPUnit ^12 (не Pest). Прогон: `php artisan test --compact`, фильтры `--filter=` / путь к файлу.
- Тесты используют **SQLite** (`RefreshDatabase`), троттлинг отключают `withoutMiddleware(ThrottleRequests::class)`.
- Трейты-хелперы: `tests/Concerns/InteractsWithGraphql` (методы `graphql()`/`adminGraphql()`, `actingAsJwt(User)`), `tests/Concerns/AuthenticatesWithJwt`.
- Покрытие (Feature): категории (`CategoryFilteringTest`, `CategoryListCacheTest`, `CategorySeederTest`, `Admin/AdminCategoryMutationTest`), аналитика (`Admin/AnalyticsTest`), обслуживание (`MaintenanceServiceTest`), напоминания (`ReminderServiceTest`), платежи (`PaymentServiceTest`), подписки (`SubscriptionAccessServiceTest`), middleware ролей (`ProfileRoleMiddlewareTest`, `UserRoleMiddlewareTest`), GraphQL (`GraphQL/Account/*`, `GraphQL/MyAchievementsQueryTest`), медиа (`Services/MediaServiceTest`), CORS.
- Требование проекта: каждая правка сопровождается тестом; запускать минимально необходимый набор.

---

## 17. Конфигурация, окружение, деплой

- Продовое окружение (`.env.example`): PostgreSQL, Redis (cache/queue/session), Reverb, S3/MinIO, SMTP, локаль `ru`, таймзона `Europe/Moscow`. Домены на кириллице (punycode `xn--h1agrefu5d.xn--p1ai`).
- Секреты пустые в примере: `APP_KEY`, `JWT_SECRET`, `REVERB_*`, `AWS_*`, `DB_PASSWORD`.
- Инфраструктура контейнеризована (хосты `postgres`, `redis`, `reverb`, `minio`), фронт ходит через nginx (wss на 443, путь `/broadcasting`).
- GraphiQL включается `GRAPHIQL_ENABLED`.
- Требуются воркеры: `queue:work` (Redis), `schedule:run` (крон), `reverb:start` (WS).

---

## 18. Соглашения разработки

Установить MCP / Skills:
```bash
php artisan boost:install
```

Из Laravel Boost guidelines:
- PHP 8: promoted constructor properties, строгие типы возвратов/параметров, всегда фигурные скобки, PHPDoc с array-shape, TitleCase для ключей enum.
- Создавать файлы через `php artisan make:*` с `--no-interaction`; generic-классы — `make:class`.
- Все GraphQL-типы/операции регистрируются в `config/graphql.php`.
- Мутации — паттерн payload `{ success, errors, ... }`; ошибки бизнес-логики — через union `MutationError`, не top-level.
- Форматирование: `vendor/bin/pint --dirty --format agent` перед завершением.
- Тесты обязательны; PHPUnit; фабрики и состояния фабрик для моделей.
- Не менять зависимости/структуру каталогов без согласования. Документацию создавать только по явному запросу.
- Для БД-запросов и схемы использовать Laravel Boost MCP (`database-query`, `database-schema`), доки — `search-docs`.

---

## 19. Известные нюансы (gotchas)

- **Роль ≠ роль:** админ — в `users.role`; ребёнок/родитель — в `profiles.role`. Не путать.
- **Категории только по `id`:** `slug` — фронтендовый; во всех аргументах операций и фильтрах — `category_id: Int`.
- **Кэш категорий — массивы, не модели** (иначе `__PHP_Incomplete_Class`). См. §10.
- **Аналитика/сырой SQL кроссбазовый:** не использовать `NOW()` (нет в SQLite) — таймстемпы биндить параметрами.
- **restrictOnDelete на категориях:** удаление категории с привязанными сущностями (в т.ч. soft-deleted) блокируется на уровне мутации `ValidationError`.
- **Таймзоны критичны:** сбросы заданий/челленджей и рассылка напоминаний считаются по `profiles.timezone` каждого ребёнка (default `UTC`/`Europe/Moscow`).
- **Одна активная сессия на логин:** `login` отклоняется, если уже есть валидная активная сессия (нужен logout/refresh).
- **Токены — только хэши в БД:** plain-значение возвращается один раз.
- **Имя операции ≠ имя класса:** напр., `ProgressChallengeMutation` → `logChallengeProgress`, `MyChildCabinetQuery` → `childDashboard`, `CreateChildLinkTokenMutation` → `generateFamilyLinkToken`, `AdminForceLogoutMutation` → `revokeUserSessions`. Точка истины — атрибут `name` и `config/graphql.php`.
- **`routes/api.php` не активен** — REST нет, только GraphQL.
- **Коллизии имён между схемами:** `subscriptions`, `createReminder`/`updateReminder`/`deleteReminder` есть в обеих схемах с разной семантикой/доступом.

---

## 20. Существующая документация

- `docs/frontend-guide.md` — формат ответов/ошибок GraphQL, коды ошибок, payload-ошибки, WebSocket-интеграция (Echo/Reverb).
- `docs/categories-guide.md` — категории для фронтенда: CRUD, фильтрация по `category_id`, миграция со slug→id.
- `docs/project-overview.md` — этот документ.

**Как читать код для деталей:** операции — `app/GraphQL/**` + `config/graphql.php`; бизнес-логика — `app/Services/**`; схема БД — `database/migrations/**`; модель домена — `app/Models/**` + `app/Enums/**`.
