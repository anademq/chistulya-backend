# Frontend Integration Guide

## Contents

1. [GraphQL — формат ответа](#1-graphql--формат-ответа)
2. [Ошибки — где появляются](#2-ошибки--где-появляются)
3. [Top-level ошибки](#3-top-level-ошибки)
4. [Payload ошибки (мутации)](#4-payload-ошибки-мутации)
5. [HTTP-уровень (не GraphQL)](#5-http-уровень-не-graphql)
6. [Полная таблица ошибок](#6-полная-таблица-ошибок)
7. [WebSocket — подключение и использование](#7-websocket--подключение-и-использование)

---

## 1. GraphQL — формат ответа

Каждый ответ GraphQL — это HTTP 200 с телом:

```json
{
  "data": { ... },
  "errors": [ ... ]
}
```

`data` и `errors` могут присутствовать одновременно.

**Мутации** не выбрасывают большинство ошибок на top-level — они возвращают их внутри `data` в поле `errors` самого payload. Это осознанное архитектурное решение: клиент всегда получает HTTP 200 и разбирает `data`.

---

## 2. Ошибки — где появляются

| Тип ситуации | Где ошибка |
|---|---|
| Невалидный JWT / нет токена | **top-level** `errors[]` |
| Нет прав (роль, email) | **top-level** `errors[]` |
| Синтаксическая ошибка в запросе | **top-level** `errors[]` |
| Неизвестное поле/тип в запросе | **top-level** `errors[]` |
| Упал роут-лимит на query | **top-level** `errors[]` |
| Валидация аргументов query (`rules()`) | **top-level** `errors[]` |
| Внутренняя ошибка сервера | **top-level** `errors[]` |
| Валидация аргументов мутации | **payload** `errors[]` |
| Бизнес-правило нарушено (мутация) | **payload** `errors[]` |
| Роут-лимит на мутацию | **payload** `errors[]` |
| Превышен HTTP rate limit nginx (30 rps) | **HTTP 429**, не GraphQL |

---

## 3. Top-level ошибки

Появляются в `response.errors[]`. Каждый объект имеет фиксированную структуру:

```json
{
  "message": "Строка с описанием ошибки",
  "extensions": {
    "code": "КОД_ОШИБКИ",
    ...доп. поля зависят от кода...
  }
}
```

### 3.1 `UNAUTHENTICATED`

Токен отсутствует, невалиден или сессия завершена. Выбрасывается middleware `auth.jwt`.

```json
{
  "message": "Требуется авторизация.",
  "extensions": {
    "code": "UNAUTHENTICATED"
  }
}
```

С дополнительным полем `reason` когда причина известна:

```json
{
  "message": "Токен истёк.",
  "extensions": {
    "code": "UNAUTHENTICATED",
    "reason": "token_expired"
  }
}
```

Возможные значения `reason`:

| `reason` | Значение |
|---|---|
| `token_invalid` | Токен не прошёл верификацию подписи |
| `token_expired` | Токен просрочен |
| `session_inactive` | Сессия отозвана / refresh token использован или истёк |
| *(отсутствует)* | Токен не передан вообще |

**Действие:** редиректить на логин / обновлять токен в зависимости от `reason`.

---

### 3.2 `FORBIDDEN`

Авторизация прошла, но у пользователя нет прав выполнить операцию. Выбрасывается middleware проверки роли / email.

```json
{
  "message": "Доступ запрещён.",
  "extensions": {
    "code": "FORBIDDEN"
  }
}
```

С уточнением `reason`:

```json
{
  "message": "Подтвердите email перед использованием сервиса.",
  "extensions": {
    "code": "FORBIDDEN",
    "reason": "email_not_verified"
  }
}
```

Возможные значения `reason`:

| `reason` | Значение |
|---|---|
| `email_not_verified` | Email не подтверждён |
| `profile_not_completed` | Профиль не заполнен |
| `only_child` | Операция доступна только детям |
| `only_parent` | Операция доступна только родителям |
| *(отсутствует)* | Общий отказ в доступе |

---

### 3.3 `BAD_REQUEST`

GraphQL-парсер отклонил запрос: синтаксическая ошибка, неизвестное поле, неправильный тип переменной и т.п. Генерируется самим GraphQL-движком.

```json
{
  "message": "Cannot query field \"unknownField\" on type \"Query\".",
  "extensions": {
    "code": "BAD_REQUEST"
  }
}
```

Поля `extensions` кроме `code` нет.

---

### 3.4 `VALIDATION` (только для queries)

Аргументы query не прошли серверную валидацию (`rules()`). Для мутаций валидация идёт в payload (см. раздел 4).

```json
{
  "message": "Данные не прошли проверку.",
  "extensions": {
    "code": "VALIDATION",
    "fields": [
      {
        "field": "email",
        "messages": ["Поле email обязательно для заполнения.", "Поле email должно быть корректным адресом."]
      },
      {
        "field": "password",
        "messages": ["Поле password обязательно для заполнения."]
      }
    ]
  }
}
```

| Поле | Тип | Описание |
|---|---|---|
| `extensions.code` | `string` | `"VALIDATION"` |
| `extensions.fields` | `array` | Список полей с ошибками |
| `extensions.fields[].field` | `string` | Имя поля из аргументов запроса |
| `extensions.fields[].messages` | `string[]` | Все сообщения по этому полю |

---

### 3.5 `RATE_LIMITED` (только для queries)

Query вызывается слишком часто (middleware `graphql.throttle` на конкретном поле). Для мутаций rate-limit идёт в payload.

```json
{
  "message": "Слишком много запросов. Попробуйте позже.",
  "extensions": {
    "code": "RATE_LIMITED",
    "retry_after": 42
  }
}
```

| Поле | Тип | Описание |
|---|---|---|
| `extensions.retry_after` | `int` | Секунд до следующей доступной попытки |

---

### 3.6 `INTERNAL_SERVER_ERROR`

Необработанная ошибка на сервере. Детали намеренно скрыты от клиента и пишутся в серверные логи.

```json
{
  "message": "Внутренняя ошибка сервера. Попробуйте позже.",
  "extensions": {
    "code": "INTERNAL_SERVER_ERROR"
  }
}
```

---

## 4. Payload ошибки (мутации)

Мутации **всегда** возвращают payload-объект с полями `success` и `errors`, независимо от результата. HTTP-статус всегда 200.

```json
{
  "data": {
    "someOperation": {
      "success": true,
      "errors": [],
      ...специфичные поля операции...
    }
  }
}
```

При ошибке:

```json
{
  "data": {
    "someOperation": {
      "success": false,
      "errors": [ ...один или несколько объектов ошибки... ]
    }
  }
}
```

Поле `errors` — union-тип: каждый элемент может быть одним из трёх типов. Для разбора используй GraphQL `__typename`.

### 4.1 `ValidationError`

Провалилась валидация входных аргументов мутации.

```graphql
# Запрашивай __typename чтобы различать типы ошибок
mutation {
  someOperation(input: "...") {
    success
    errors {
      __typename
      ... on ValidationError {
        message
        fields {
          field
          messages
        }
      }
      ... on RateLimitError {
        message
        retryAfter
      }
      ... on InvalidActionError {
        message
      }
    }
  }
}
```

JSON-ответ:

```json
{
  "data": {
    "someOperation": {
      "success": false,
      "errors": [
        {
          "__typename": "ValidationError",
          "message": "Данные не прошли проверку.",
          "fields": [
            {
              "field": "email",
              "messages": ["Поле email обязательно для заполнения."]
            },
            {
              "field": "password",
              "messages": ["Минимальная длина пароля — 8 символов."]
            }
          ]
        }
      ]
    }
  }
}
```

| Поле | Тип | Описание |
|---|---|---|
| `__typename` | `"ValidationError"` | |
| `message` | `string` | Общее сообщение об ошибке валидации |
| `fields` | `array` | Список полей с проблемами |
| `fields[].field` | `string` | Имя поля из входных аргументов |
| `fields[].messages` | `string[]` | Все сообщения для этого поля |

---

### 4.2 `RateLimitError`

Мутация вызывается слишком часто (middleware `graphql.throttle` на конкретном поле).

```json
{
  "data": {
    "someOperation": {
      "success": false,
      "errors": [
        {
          "__typename": "RateLimitError",
          "message": "Слишком много запросов. Попробуйте позже.",
          "retryAfter": 30
        }
      ]
    }
  }
}
```

| Поле | Тип | Описание |
|---|---|---|
| `__typename` | `"RateLimitError"` | |
| `message` | `string` | Сообщение |
| `retryAfter` | `int` | Секунд до следующей доступной попытки |

---

### 4.3 `InvalidActionError`

Действие не разрешено в текущем состоянии системы (например: попытка оформить подписку когда она уже активна, отозвать несуществующую подписку и т.п.).

```json
{
  "data": {
    "someOperation": {
      "success": false,
      "errors": [
        {
          "__typename": "InvalidActionError",
          "message": "У пользователя нет активной подписки."
        }
      ]
    }
  }
}
```

| Поле | Тип | Описание |
|---|---|---|
| `__typename` | `"InvalidActionError"` | |
| `message` | `string` | Описание причины |

---

## 5. HTTP-уровень (не GraphQL)

Эти ответы не являются GraphQL-ответами и содержат обычное HTTP-тело.

### HTTP 429 — Nginx rate limit

Срабатывает когда клиент превышает 30 запросов/сек (burst 60) на уровне nginx. Тело не является JSON.

```
HTTP/1.1 429 Too Many Requests
```

### HTTP 401 — Broadcasting auth

При попытке подписаться на приватный WebSocket-канал без корректного JWT-токена сервер вернёт HTTP 401 на эндпоинт `/broadcasting/auth`.

---

## 6. Полная таблица ошибок

| Код | Где появляется | Причина | Доп. поля в extensions |
|---|---|---|---|
| `UNAUTHENTICATED` | Top-level | JWT отсутствует/невалиден/сессия мертва | `reason?` |
| `FORBIDDEN` | Top-level | Нет роли, email не подтверждён | `reason?` |
| `BAD_REQUEST` | Top-level | Синтаксис/схема GraphQL | — |
| `VALIDATION` | Top-level (только queries) | Провалились `rules()` у query | `fields[]` |
| `RATE_LIMITED` | Top-level (только queries) | `graphql.throttle` на query | `retry_after` |
| `INTERNAL_SERVER_ERROR` | Top-level | Необработанное исключение | — |
| `ValidationError` | Payload (только мутации) | Провалились `rules()` у мутации | — |
| `RateLimitError` | Payload (только мутации) | `graphql.throttle` на мутации | — |
| `InvalidActionError` | Payload (только мутации) | Бизнес-логика запрещает действие | — |

> **Примечание:** `UNAUTHENTICATED` и `FORBIDDEN` всегда top-level — они выбрасываются middleware до того, как resolver успевает сработать, поэтому payload-перехватчик их не видит.

---

## 7. WebSocket — подключение и использование

### Стек

- **Сервер:** [Laravel Reverb](https://laravel.com/docs/reverb) — совместим с протоколом Pusher
- **Клиент:** [Laravel Echo](https://github.com/laravel/echo) + [pusher-js](https://github.com/pusher/pusher-js)
- **Транспорт:** WebSocket (wss://)
- **Канал:** приватный `child.{childId}`
- **Событие:** `.reminder.notification`

### 7.1 Установка зависимостей

```bash
npm install laravel-echo pusher-js
```

### 7.2 Инициализация Echo

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',

  // Публичный ключ приложения Reverb (из .env фронтенда)
  key: import.meta.env.VITE_REVERB_APP_KEY,

  // Хост и порт Reverb (через nginx)
  wsHost:  import.meta.env.VITE_REVERB_HOST,   // например: api.чистуля.рф
  wsPort:  443,
  wssPort: 443,

  // Путь к WebSocket на nginx — должен совпадать с конфигурацией сервера
  wsPath: '/broadcasting',

  forceTLS: true,
  enabledTransports: ['ws', 'wss'],

  // Эндпоинт авторизации приватного канала
  authEndpoint: 'https://api.чистуля.рф/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${yourJwtToken}`,
    },
  },
})
```

> `wsPath: '/broadcasting'` обязателен — nginx проксирует WebSocket по пути `/broadcasting/app/{key}`.

### 7.3 Как работает авторизация

Reverb сам по себе ничего не знает о JWT. Авторизация происходит в два этапа:

```
1. Echo → WebSocket → Reverb             (публичное подключение по app_key)
2. Echo → POST /broadcasting/auth → Laravel  (авторизация приватного канала)
         Authorization: Bearer <jwt_token>
         Body: channel_name=private-child.xxx&socket_id=12345.67890
3. Laravel проверяет JWT → проверяет channel rule → возвращает HMAC-подпись
4. Echo → WebSocket → Reverb             (подписка с подписью)
```

`/broadcasting/auth` обрабатывается обычным Laravel middleware `auth.jwt` — тот же механизм, что и для GraphQL запросов.

Канальное правило: пользователь может подписаться только на свой канал (`child.{id}` где `id == auth()->id()`).

### 7.4 Подписка на уведомления ребёнка

```js
// childId — UUID ребёнка из профиля (поле id в GraphQL)
echo
  .private(`child.${childId}`)
  .listen('.reminder.notification', (payload) => {
    console.log('Новое уведомление:', payload)
  })
```

### 7.5 Структура события `.reminder.notification`

```json
{
  "id": 42,
  "reminder_id": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Почисть зубы",
  "short_description": "Не забудь почистить зубы перед сном",
  "description": "Чистить зубы нужно 2 минуты — утром и вечером.",
  "time": "21:00",
  "date": null,
  "repeating_pattern": "daily",
  "repeating_days": null,
  "scope": "global",
  "sent_at": "2026-05-18T18:00:00+00:00"
}
```

| Поле | Тип | Описание |
|---|---|---|
| `id` | `number` | ID записи об отправке уведомления |
| `reminder_id` | `string` (UUID) | ID самого напоминания |
| `title` | `string` | Название напоминания |
| `short_description` | `string \| null` | Краткое описание |
| `description` | `string \| null` | Полное описание |
| `time` | `string` | Время срабатывания, формат `HH:MM` (по местному времени ребёнка) |
| `date` | `string \| null` | Дата для `once`-напоминаний, формат `YYYY-MM-DD`; `null` для повторяющихся |
| `repeating_pattern` | `"daily" \| "weekly" \| "once"` | Паттерн повторения |
| `repeating_days` | `string \| null` | 7 символов `0`/`1` для `weekly` (Пн–Вс), `null` для остальных. Пример: `"1010100"` = Пн, Ср, Пт |
| `scope` | `"global" \| "parent" \| "assigned" \| "child"` | Источник напоминания |
| `sent_at` | `string` (ISO 8601 UTC) | Момент отправки |

### 7.6 Пример полного использования (React)

```js
import { useEffect } from 'react'

function useReminderNotifications(childId, jwtToken, onNotification) {
  useEffect(() => {
    if (!childId || !jwtToken) return

    const echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost:  import.meta.env.VITE_REVERB_HOST,
      wsPort:  443,
      wssPort: 443,
      wsPath:  '/broadcasting',
      forceTLS: true,
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
      auth: {
        headers: { Authorization: `Bearer ${jwtToken}` },
      },
    })

    const channel = echo
      .private(`child.${childId}`)
      .listen('.reminder.notification', onNotification)

    return () => {
      channel.stopListening('.reminder.notification')
      echo.disconnect()
    }
  }, [childId, jwtToken])
}
```

### 7.7 Переменные окружения фронтенда

```env
VITE_REVERB_APP_KEY=your-reverb-app-key
VITE_REVERB_HOST=api.чистуля.рф
VITE_API_URL=https://api.чистуля.рф
```

`VITE_REVERB_APP_KEY` должен совпадать с `REVERB_APP_KEY` из `.env.production` бэкенда.

### 7.8 Отключение от канала

```js
echo.leave(`child.${childId}`)
// или полное отключение:
echo.disconnect()
```

### 7.9 Обработка ошибок подключения

```js
echo.connector.pusher.connection.bind('error', (err) => {
  if (err.data?.code === 4009) {
    // Достигнут лимит подключений
  }
})

echo.connector.pusher.connection.bind('unavailable', () => {
  // Reverb недоступен, Echo будет пробовать переподключиться автоматически
})
```

Echo (через pusher-js) автоматически переподключается при разрыве соединения — дополнительной логики не требуется.
