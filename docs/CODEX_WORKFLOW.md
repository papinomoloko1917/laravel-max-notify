# Codex + Git Workflow for Three Machines

The repository is the durable project memory.

Codex conversation history is convenient locally, but is not the source of truth.

## Context files

- `AGENTS.md` — permanent teaching rules.
- `docs/ROADMAP.md` — direction, not an immediate checklist.
- `docs/ARCHITECTURE.md` — decisions and reasons.
- `docs/DEVELOPMENT.md` — short session/machine handoff.

## Before working on any machine

From repository root:

```bash
git status
git pull --rebase
```

If `git status` is not clean, understand why before pulling or editing.

Then run:

```bash
codex
```

## First prompt for a new Codex session

```text
Прочитай AGENTS.md, docs/DEVELOPMENT.md, docs/ROADMAP.md
и при необходимости docs/ARCHITECTURE.md.

Затем самостоятельно изучи:
- git status;
- последние коммиты;
- composer.json;
- package.json;
- compose.yaml;
- .env.example;
- текущее состояние Laravel-проекта.

Не предполагай, что всё описанное в ROADMAP уже должно быть установлено:
ROADMAP описывает направление развития проекта.

Мы работаем в учебном режиме.
Код и конфигурацию по умолчанию пишу я.

Не изменяй файлы приложения, compose.yaml или конфигурацию и не реализуй
текущее учебное задание за меня без моего явного разрешения.

Сначала расскажи:

1. Что реально уже установлено и настроено.
2. Что из предполагаемого стека пока отсутствует.
3. Что из отсутствующего действительно необходимо СЕЙЧАС, а что понадобится позже.
4. Есть ли расхождения между документацией и реальным проектом.

Затем объясни только следующий необходимый концептуальный блок.

После объяснения дай мне 2–4 тесно связанных практических задания одним
учебным блоком по формату AGENTS.md и остановись. Одно задание допустимо,
только если тема новая, рискованная или блокирует следующие шаги.

Не показывай готовую реализацию задания.
```

## Normal learning cycle

```text
Codex explains
      ↓
Codex gives one coherent block of 2–4 related tasks
      ↓
I implement the block
      ↓
I request review
      ↓
Codex inspects git diff/tests
      ↓
I fix issues
      ↓
Codex verifies
      ↓
I commit
```

## Review prompt

```text
Я закончил текущее задание.

Не исправляй код сам.
Посмотри git diff и проведи review по правилам AGENTS.md.

Раздели замечания на:
- ошибки;
- безопасность;
- Laravel/PHP практики;
- необязательные улучшения.

Не показывай сразу готовую реализацию.
Сначала объясни проблемы и дай мне возможность исправить их самостоятельно.
```

## Before changing machines

1. `git status`
2. Run relevant tests.
3. Update `docs/DEVELOPMENT.md` if the current task, completed work, or an important decision changed.
4. Commit and push.

```bash
git add ...
git commit -m "..."
git push
```

Do not rely on an unpushed local stash as handoff.

## On another machine

```bash
git status
git pull --rebase
codex
```

Start a fresh Codex session and paste the first prompt.

It is fine if old conversation history is unavailable. Repository state is authoritative.

## Same-machine resume

If supported:

```bash
codex resume
```

or:

```bash
codex resume --last
```

Use this as convenience only. Do not synchronize the entire local Codex history directory through Git.

## HTTP/Postman learning flow

```text
Read API docs
    ↓
Create request manually in Postman
    ↓
Inspect response
    ↓
Understand HTTP contract
    ↓
Implement Laravel behavior
    ↓
Add Pest / Http::fake()
```

Do not commit secrets in Postman collections. Keep credentials in local environments.

## End-of-session prompt

```text
Мы заканчиваем текущую сессию.

Не изменяй код.

На основе реального git status, git diff и выполненной работы предложи:
1. что нужно записать в docs/DEVELOPMENT.md;
2. нужно ли обновить docs/ARCHITECTURE.md;
3. какие проверки мне выполнить перед commit;
4. одно или несколько подходящих commit messages для логически отдельных изменений.

Не выполняй commit и push за меня.
```
