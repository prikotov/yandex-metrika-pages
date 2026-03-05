---
name: yandex-metrika-pages
description: Статистика по страницам сайта из Яндекс.Метрики
---

## Когда использовать

- Анализ популярных страниц
- Поиск страниц с высокими отказами
- Определение точек входа и выхода

## Запуск

```bash
php .opencode/skills/yandex-metrika-pages/pages.php [опции] [дата_от] [дата_до]
```

### Параметры дат

- `дата_от` — начало периода (формат: YYYY-MM-DD), по умолчанию 30 дней назад
- `дата_до` — конец периода (формат: YYYY-MM-DD), по умолчанию сегодня

### Опции

| Опция | Сокращение | Описание | Значения | По умолчанию |
|-------|------------|----------|----------|--------------|
| `--type` | `-t` | Тип страниц | `page`, `entry`, `exit` | `page` |
| `--sort` | `-s` | Поле сортировки | `visits`, `visitors`, `pageviews`, `bounce_rate`, `page_depth`, `avg_duration` | `visits` |
| `--order` | `-o` | Направление сортировки | `asc`, `desc` | `desc` |
| `--limit` | `-l` | Лимит записей | число (например: 10, 20, 50) | все записи |

### Типы страниц

| Параметр | Описание | Метрики |
|----------|----------|---------|
| `page` | Все страницы (по умолчанию) | pageviews, visitors |
| `entry` | Страницы входа — с которых начинаются визиты | все метрики |
| `exit` | Страницы выхода — на которых заканчиваются визиты | все метрики |

### Примеры

```bash
# Топ страниц по визитам
php .opencode/skills/yandex-metrika-pages/pages.php

# Топ-20 страниц входа
php .opencode/skills/yandex-metrika-pages/pages.php -t entry -l 20

# Страницы с наибольшим процентом отказов
php .opencode/skills/yandex-metrika-pages/pages.php -s bounce_rate -l 15

# Страницы выхода с наихудшим временем на сайте
php .opencode/skills/yandex-metrika-pages/pages.php --type exit --sort avg_duration --order asc -l 10

# Топ страниц по просмотрам
php .opencode/skills/yandex-metrika-pages/pages.php -s pageviews -l 30

# За определённый период
php .opencode/skills/yandex-metrika-pages/pages.php -l 10 2025-01-01 2025-01-31
```

## Результат

`yandex_metrika_reports/YYYY-MM-DD/`:
- `yandex_metrika_pages_YYYY-MM-DD_HH-MM-SS.csv` / `.md` — статистика по страницам

### Поля в отчете

**Для типа `page`:**

| Поле | Описание |
|------|----------|
| `url` | URL страницы |
| `pageviews` | Просмотры |
| `visitors` | Посетители |

**Для типов `entry` и `exit`:**

| Поле | Описание |
|------|----------|
| `url` | URL страницы |
| `visits` | Визиты |
| `visitors` | Посетители |
| `pageviews` | Просмотры |
| `bounce_rate` | Процент отказов |
| `page_depth` | Глубина просмотра |
| `avg_duration` | Среднее время на странице (сек) |
