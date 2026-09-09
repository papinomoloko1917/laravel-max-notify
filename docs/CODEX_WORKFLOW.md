# Codex + Git Workflow for Three Machines

This project is developed on multiple computers.

The repository is the durable project memory.

Codex conversation history is useful locally, but it is not the source of truth.

---

## 1. Files that carry context

### `AGENTS.md`

Permanent instructions to Codex:

- mentor role;
- learning mode;
- do not write the application for me;
- review workflow.

Change this rarely.

### `docs/ROADMAP.md`

The planned learning/development progression.

Change when the overall plan changes.

### `docs/ARCHITECTURE.md`

Architecture decisions that future sessions need to know.

Change when a meaningful technical decision is made.

### `docs/DEVELOPMENT.md`

Short handoff between sessions/machines.

This is the file to update most often.

---

## 2. Before starting work on any machine

From the repository root:

```bash
git status
git pull --rebase
```

If `git status` is not clean, understand why before pulling or making new changes.

Then start Codex from the repository root.

Codex should read `AGENTS.md` automatically, but the first prompt should explicitly ask it to read the project context.

---

## 3. Recommended first prompt for a new Codex session

```text
Прочитай AGENTS.md, docs/DEVELOPMENT.md, docs/ROADMAP.md и при необходимости docs/ARCHITECTURE.md.

Затем изучи git status, последние коммиты и текущее состояние проекта.

Мы работаем в учебном режиме: код пишу я. Не изменяй файлы приложения и не реализуй задачу за меня без моего явного запроса.

Сначала кратко скажи:
1. где мы остановились;
2. что сейчас важно понять;
3. есть ли расхождения между документацией и реальным состоянием репозитория.

После этого дай мне только ОДНО следующее практическое задание в формате AGENTS.md и остановись.
```

---

## 4. During a session

Preferred cycle:

```text
Codex explains
      ↓
Codex gives one task
      ↓
I implement it
      ↓
I ask for review
      ↓
Codex inspects git diff/tests
      ↓
I fix issues
      ↓
Codex verifies
      ↓
I commit
```

Useful review request:

```text
Я закончил текущее задание.

Не исправляй код сам.
Посмотри git diff и проведи review по правилам AGENTS.md.
Сначала перечисли проблемы по важности и дай мне возможность исправить их самостоятельно.
```

---

## 5. Before changing machines

Make the code state understandable.

Check:

```bash
git status
```

Run the relevant tests.

Update `docs/DEVELOPMENT.md` if:

- the current task changed;
- a task was completed;
- an important decision was made;
- the next machine would otherwise lack important context.

Then create a small logical commit and push it:

```bash
git add ...
git commit -m "..."
git push
```

Do not rely on an unpushed local stash as the handoff mechanism.

---

## 6. Starting on another machine

```bash
git status
git pull --rebase
```

Then start a new Codex session and use the prompt from section 3.

It is fine if the new session has none of the previous conversation history.

The authoritative context is:

1. repository contents;
2. current Git history;
3. `docs/DEVELOPMENT.md`;
4. architecture/roadmap documentation.

---

## 7. Local Codex resume

On the same machine, Codex may support resuming a local session.

Use this as convenience, not as project storage.

Even when resuming a session, the repository state remains authoritative.

Never commit the entire local Codex home/history directory into the project just to synchronize conversations.

---

## 8. When documentation and code disagree

Code and tests tell us what currently exists.

Documentation tells us what we intended.

Do not silently choose one.

Ask Codex to identify the mismatch, then decide whether:

- the implementation should change;
- documentation should change;
- the discrepancy is temporary because a task is unfinished.

---

## 9. Suggested Git habits for this project

Prefer commits roughly at the size of one learning outcome.

Good examples:

```text
Configure Sail PostgreSQL service
Add initial cameras migration
Add Camera Eloquent model
Define camera client relationship
Add webhook request validation
Add duplicate event cache test
```

Avoid giant commits such as:

```text
Implement backend
Finish project
Lots of fixes
```

Small commits make learning, review, reverting, and moving between machines easier.

---

## 10. End-of-session prompt

Use this before finishing a meaningful session:

```text
Мы заканчиваем текущую сессию.

Не изменяй код.

На основе реального git status, git diff и выполненной работы предложи:
1. что нужно записать в docs/DEVELOPMENT.md;
2. нужно ли обновить docs/ARCHITECTURE.md;
3. какие проверки мне выполнить перед commit;
4. одно подходящее commit message.

Не выполняй commit и push за меня.
```
