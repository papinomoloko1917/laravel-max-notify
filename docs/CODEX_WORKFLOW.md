# Codex + Git Workflow for Three Machines

The repository is the durable project memory.

Codex conversation history is convenient locally, but is not the source of truth.

## Context files

- `AGENTS.md` — permanent agent collaboration and implementation rules.
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

Мы работаем в режиме совместной агентной разработки.
Самостоятельно реализуй следующий согласованный цельный блок, включая код, тесты и документацию.
Не запрашивай отдельное подтверждение для обычных правок в рамках этого блока.
Не делай commit и push без моей явной просьбы.

Сначала расскажи:

1. Что реально уже установлено и настроено.
2. Что из предполагаемого стека пока отсутствует.
3. Что из отсутствующего действительно необходимо СЕЙЧАС, а что понадобится позже.
4. Есть ли расхождения между документацией и реальным проектом.

Затем сопоставь checkpoint с реальным кодом, выбери следующий необходимый цельный блок,
реализуй его, добавь тесты, выполни проверки и актуализируй документацию.
В конце кратко объясни результат, риски и предложи commit message.
```

## Normal agent development cycle

```text
Codex inspects checkpoint and repository
      ↓
Codex implements one coherent block with tests
      ↓
Codex reviews the diff and runs verification
      ↓
Codex updates handoff/architecture documentation
      ↓
I commit
```

## Review prompt

```text
Я закончил текущее задание.

Посмотри git diff и проведи review по правилам AGENTS.md.

Раздели замечания на:
- ошибки;
- безопасность;
- Laravel/PHP практики;
- необязательные улучшения.

Не изменяй код в этом режиме review, если я явно не попросил исправить найденные проблемы.
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

If the pulled commits changed `compose.yaml`, a Dockerfile, or files copied into an image, rebuild the affected Sail image before starting work. For the current project runtime:

```bash
./vendor/bin/sail build laravel.test
./vendor/bin/sail up -d
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

## HTTP/Postman exploration flow

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
