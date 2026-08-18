# Категории — руководство для фронтенда

Гайд по работе с категориями сущностей (ежедневные задания, челленджи, предметы питомца) через GraphQL: как читать, создавать, обновлять, удалять и фильтровать по категории.

## Contents

1. [Главное (TL;DR)](#1-главное-tldr)
2. [Что такое категория](#2-что-такое-категория)
3. [`id` vs `slug` — что и когда использовать](#3-id-vs-slug--что-и-когда-использовать)
4. [Чтение списков категорий](#4-чтение-списков-категорий)
5. [Админский CRUD категорий](#5-админский-crud-категорий)
   - [5.1 Создание](#51-создание-create)
   - [5.2 Обновление](#52-обновление-update)
   - [5.3 Удаление](#53-удаление-delete)
   - [5.4 Правила валидации](#54-правила-валидации)
   - [5.5 Обработка ошибок](#55-обработка-ошибок)
6. [Фильтрация по категории (`category_id`)](#6-фильтрация-по-категории-category_id)
7. [Привязка сущностей к категории](#7-привязка-сущностей-к-категории)
8. [Что изменилось: миграция со `slug` на `id`](#8-что-изменилось-миграция-со-slug-на-id)

> Общий формат ответов и ошибок GraphQL описан в [`frontend-guide.md`](./frontend-guide.md). Здесь — только специфика категорий.

---

## 1. Главное (TL;DR)

- **Везде в операциях используется числовой `id` категории, а не `slug`.** Это касается фильтров, связей сущность↔категория, аналитики.
- **`slug` — только для фронтенда:** человекочитаемый ключ для i18n-подписей, иконок, роутинга, стабильных ссылок в UI. Не отправляйте `slug` в аргументы операций — его там больше нет.
- **CRUD категорий — только в админской схеме** (`POST /graphql/admin`, роль администратора). Обычные пользователи категории только читают.
- Есть три независимых вида категорий: для **заданий**, **челленджей** и **предметов питомца**. У каждого — свой набор операций.

---

## 2. Что такое категория

Категория — это справочник для группировки сущностей. Три независимых типа:

| Вид | GraphQL-тип | Таблица | Пример |
|---|---|---|---|
| Категории заданий | `DailyTaskCategory` | `daily_task_categories` | Гигиена, Порядок, Еда, Учеба |
| Категории челленджей | `ChallengeCategory` | `challenge_categories` | Гигиена, Порядок, Еда, Учеба |
| Категории предметов питомца | `PetItemCategory` | `pet_item_categories` | Шапки, Куртки, Ботинки |

Все три имеют одинаковую структуру полей:

| Поле | Тип | Описание |
|---|---|---|
| `id` | `Int!` | Уникальный числовой идентификатор. **Используется во всех операциях.** |
| `slug` | `String!` | URL-safe идентификатор (`[a-z0-9_-]`). Для UI/фронтенда. |
| `title` | `String!` | Человекочитаемое название (то, что на фронте называлось `label`). |
| `order_column` | `Int` | Позиция сортировки (меньше — выше). Может быть `null`. |

> `label` с фронтенда сохраняется на бэке в поле `title`. Отдельного `label` в API нет.

---

## 3. `id` vs `slug` — что и когда использовать

| | `id` (`Int`) | `slug` (`String`) |
|---|---|---|
| Назначение | связи, фильтры, аналитика, все операции API | отображение в UI, i18n, иконки, роутинг |
| Стабильность | неизменен | может быть изменён админом |
| Где передаётся | **аргументы всех операций** | **нигде** в аргументах — только читается из ответа |

**Почему `id`, а не `slug`:** `id` — первичный ключ и целевой столбец внешних ключей в БД. Фильтрация и связи по `id` целостны и быстры, а `slug` может измениться (тогда ссылки по `slug` сломались бы). Поэтому `slug` остаётся как удобный человекочитаемый ключ для фронтенда, но идентификация в операциях — всегда по `id`.

**Практика на фронте:** получите список категорий один раз (см. §4), держите мапу `id → {slug, title}`. По `id` из ответов сущностей находите `slug`/`title` для показа; в запросы всегда отправляйте `id`.

---

## 4. Чтение списков категорий

Доступно любому авторизованному пользователю в **основной схеме** (`POST /graphql`). Аргументов нет — возвращается весь список, отсортированный по `order_column`, затем `title`. Списки кэшируются на бэке.

```graphql
query Categories {
  dailyTaskCategories { id slug title order_column }
  challengeCategories { id slug title order_column }
  petItemCategories   { id slug title order_column }
}
```

```json
{
  "data": {
    "dailyTaskCategories": [
      { "id": 1, "slug": "hygiene", "title": "Гигиена", "order_column": 0 },
      { "id": 2, "slug": "order",   "title": "Порядок", "order_column": 1 }
    ]
  }
}
```

| Операция | Схема | Возвращает |
|---|---|---|
| `dailyTaskCategories` | default | `[DailyTaskCategory!]!` |
| `challengeCategories` | default | `[ChallengeCategory!]!` |
| `petItemCategories` | default | `[PetItemCategory!]!` |

---

## 5. Админский CRUD категорий

Все операции ниже — **только в админской схеме**: `POST /graphql/admin` с JWT администратора. Для каждого вида категории — по три мутации:

| Вид | Create | Update | Delete |
|---|---|---|---|
| Задания | `createDailyTaskCategory` | `updateDailyTaskCategory` | `deleteDailyTaskCategory` |
| Челленджи | `createChallengeCategory` | `updateChallengeCategory` | `deleteChallengeCategory` |
| Предметы питомца | `createPetItemCategory` | `updatePetItemCategory` | `deletePetItemCategory` |

Все мутации возвращают payload-объект с полями `success` и `errors` (см. [`frontend-guide.md` §4](./frontend-guide.md#4-payload-ошибки-мутации)). Create/Update дополнительно возвращают `category`.

### 5.1 Создание (create)

Аргументы: `slug: String!`, `title: String!`, `order_column: Int` (необязательно).

```graphql
mutation CreateCat($slug: String!, $title: String!, $order: Int) {
  createDailyTaskCategory(slug: $slug, title: $title, order_column: $order) {
    success
    errors { __typename ... on ValidationError { message fields { field messages } } }
    category { id slug title order_column }
  }
}
```

```json
// variables
{ "slug": "sport", "title": "Спорт", "order": 4 }
```

```json
// ответ
{
  "data": {
    "createDailyTaskCategory": {
      "success": true,
      "errors": [],
      "category": { "id": 5, "slug": "sport", "title": "Спорт", "order_column": 4 }
    }
  }
}
```

Для челленджей и предметов питомца — то же самое, только имя мутации и поля `category` берутся из соответствующего типа (`ChallengeCategory` / `PetItemCategory`).

### 5.2 Обновление (update)

Аргументы: `id: Int!` (обязателен), `slug: String`, `title: String`, `order_column: Int`. **Обновляются только переданные поля** (частичное обновление). Не передавайте поле, если не хотите его менять.

```graphql
mutation UpdateCat($id: Int!, $title: String, $order: Int) {
  updateChallengeCategory(id: $id, title: $title, order_column: $order) {
    success
    errors { __typename ... on ValidationError { message fields { field messages } } }
    category { id slug title order_column }
  }
}
```

```json
// variables — меняем только название и порядок
{ "id": 3, "title": "Питание", "order": 2 }
```

### 5.3 Удаление (delete)

Аргумент: `id: Int!`. Возвращает `MutationPayload` (только `success` и `errors`, без `category`).

```graphql
mutation DeleteCat($id: Int!) {
  deleteDailyTaskCategory(id: $id) {
    success
    errors {
      __typename
      ... on ValidationError { message fields { field messages } }
    }
  }
}
```

> **Защита от удаления:** нельзя удалить категорию, к которой привязаны сущности (задания/челленджи/предметы) — в том числе мягко удалённые (soft-deleted). В этом случае `success: false` и в `errors` придёт `ValidationError` с полем `id`. Сначала перенесите или удалите привязанные сущности.

```json
// попытка удалить непустую категорию
{
  "data": {
    "deleteDailyTaskCategory": {
      "success": false,
      "errors": [
        {
          "__typename": "ValidationError",
          "message": "Данные не прошли проверку.",
          "fields": [
            { "field": "id", "messages": ["Невозможно удалить категорию: к ней привязаны элементы. Сначала перенесите или удалите их."] }
          ]
        }
      ]
    }
  }
}
```

### 5.4 Правила валидации

| Поле | Create | Update | Правила |
|---|---|---|---|
| `id` | — | обязателен | должен существовать в таблице категорий |
| `slug` | обязателен | опционален | `string`, ≤ 32 символа, шаблон `^[a-z0-9_-]+$`, уникален среди категорий этого вида |
| `title` | обязателен | опционален | `string`, ≤ 64 символа |
| `order_column` | опционален | опционален | целое, `0 … 65535`, допускает `null` |

При обновлении проверка уникальности `slug` игнорирует саму редактируемую запись.

### 5.5 Обработка ошибок

Ошибки бизнес-уровня приходят **внутри payload** в `errors[]` (union-тип). Разбирайте по `__typename`:

- `ValidationError` — не прошла валидация аргументов (`slug`/`title`/…), либо сработала защита от удаления непустой категории (поле `id`).
- `RateLimitError` — слишком частые вызовы.
- `InvalidActionError` — действие запрещено в текущем состоянии.

Полное описание структуры каждого типа — в [`frontend-guide.md` §4](./frontend-guide.md#4-payload-ошибки-мутации). Ошибки авторизации (`UNAUTHENTICATED` / `FORBIDDEN` — например, не-админ дергает админскую мутацию) приходят на **top-level** `errors[]`, см. §3 того же гайда.

---

## 6. Фильтрация по категории (`category_id`)

Во всех операциях фильтрации передавайте **`category_id: Int`** (необязательный аргумент). Если не передан — фильтр не применяется (возвращается всё). Несуществующий `category_id` даёт пустой результат / нулевой ряд, а не ошибку.

**Основная схема (`/graphql`):**

| Операция | Аргумент | Что фильтрует |
|---|---|---|
| `availableDailyTasks` | `category_id: Int` | доступные задания |
| `selectedDailyTasks` | `category_id: Int` | выбранные ребёнком задания |
| `availableChallenges` | `category_id: Int` | доступные челленджи |
| `selectedChallenges` | `category_id: Int` | выбранные ребёнком челленджи |
| `petCatalog` | `category_id: Int` | каталог предметов питомца |
| `dailyTaskAnalytics` | `category_id: Int` | аналитика по заданиям |
| `challengeAnalytics` | `category_id: Int` | аналитика по челленджам |

**Админская схема (`/graphql/admin`):**

| Операция | Аргумент |
|---|---|
| `dailyTasks` | `category_id: Int` |
| `challenges` | `category_id: Int` |
| `petItems` | `category_id: Int` |
| `dailyTaskAnalytics` | `category_id: Int` |
| `challengeAnalytics` | `category_id: Int` |

Пример:

```graphql
query TasksByCategory($cid: Int) {
  availableDailyTasks(category_id: $cid, per_page: 50) {
    data { id title category_id }
  }
}
```

```json
{ "cid": 1 }
```

---

## 7. Привязка сущностей к категории

При создании/редактировании самих сущностей категория задаётся через **`category_id: Int`**:

| Мутация | Схема | `category_id` |
|---|---|---|
| `createDailyTask` / `updateDailyTask` | admin | `Int!` при создании |
| `createChallenge` / `updateChallenge` | admin | `Int!` при создании |
| `createPetItem` / `updatePetItem` | admin | `Int!` при создании |
| `createCustomDailyTaskForChild` | default (родитель) | `Int` |

В ответах сущностей категория доступна как поле `category_id: Int!` (и вложенный объект `category { ... }`, если он определён в типе):

```graphql
query {
  dailyTasks(per_page: 20) {
    data { id title category_id }
  }
}
```

---

## 8. Что изменилось: миграция со `slug` на `id`

Если вы обновляетесь со старой версии API:

| Было | Стало |
|---|---|
| `petCatalog(category: String)` | `petCatalog(category_id: Int)` |
| `dailyTaskAnalytics(category: String)` (slug) | `dailyTaskAnalytics(category_id: Int)` |
| `challengeAnalytics(category: String)` (slug) | `challengeAnalytics(category_id: Int)` |
| `category_id` в типах сущностей отдавался как `String` | теперь `Int!` |
| `id` категории отдавался как `String` | теперь `Int!` |

**Что делать на фронте:**

1. Замените все аргументы `category: "<slug>"` на `category_id: <int>`.
2. Считайте `category_id` из ответов как число.
3. Для отображения ведите мапу `id → {slug, title}` из `dailyTaskCategories` / `challengeCategories` / `petItemCategories` и берите `slug`/`title` оттуда.
4. `slug` продолжайте использовать только в UI (иконки, переводы, ссылки) — но не в аргументах операций.
