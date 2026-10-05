# Rules — Agent Operating Manual

> **Read this file first, every session.** Then read `Memory.md`, then `Tasks.md`. Read `PRD.md`, `Architecture.md`, `Design.md` **only the sections you need** for the current task.
> These rules are written for a fast, lightweight model: follow them literally, keep steps small, do not improvise scope.

---

## 1. Prime Directives

1. **Finish the project.** Execute `Tasks.md` top to bottom, phase by phase, without stopping to ask questions. All decisions are already made in `PRD.md` §13 and `Memory.md` (Decisions). If something is unspecified, choose the simplest option consistent with `Architecture.md`, log it in `Memory.md` → Decisions, and continue.
2. **One source of truth per topic:** product → `PRD.md` · technical → `Architecture.md` · visual → `Design.md` · progress → `Tasks.md` · state/decisions → `Memory.md`.
3. **No scope creep.** Anything not in `Tasks.md` goes to `Memory.md` → Backlog, not into the code.
4. **Stay inside the stack:** PHP 8.3 (no framework, no Composer), Supabase over REST, vanilla JS/CSS, Docker for Render. No Node tooling, no other databases, no paid services.

---

## 2. TESTING POLICY (most important rule)

The owner wants the project **built completely first and tested once at the end.** Repeated end-to-end testing during the build wastes time and tokens.

### 2.1 During Phases 0–8 — FORBIDDEN
- Starting a server (`php -S`, Apache, Docker) or opening the app in any browser/preview tool.
- Browser/UI agents, screenshots, recordings, "let me check how it looks".
- `curl`/HTTP smoke tests, calling Supabase from the terminal or via MCP (except as noted in §2.4).
- Writing or running automated test suites (PHPUnit, Playwright, Cypress, Jest, etc.).
- Re-running the same check "just to be sure", or re-reading files you just wrote.
- Saying "I'll verify by running it" — you will verify in Phase 9, not now.

### 2.2 During Phases 0–8 — ALLOWED (cheap static checks only)
1. **Syntax lint, once per phase** (at the phase gate), only if PHP CLI is installed:
   `find src views public -name '*.php' -print0 | xargs -0 -n1 php -l`
   (If PHP CLI is missing, skip lint and rely on careful self-review. Do **not** ask the user to install it before Phase 9.)
2. **One self-review pass per phase** against that phase's gate checklist in `Tasks.md` (read your own code once, fix what you find).
3. **Pattern greps** (static): e.g. `grep -rn "SUPABASE_SECRET_KEY" public views`, `grep -rn "<script>" views`, `grep -rn "style=" views`, `grep -rnE "echo \$|<\?= \$" views` (to find unescaped output).

### 2.3 Unsure about an API/behavior? Do NOT test — defer it
If you are unsure how something behaves at runtime (PostgREST syntax, Supabase Auth response shape, cURL headers, CSS rendering…): implement the documented approach from `Architecture.md`, add a line to `Memory.md` → **VERIFY-IN-QA**, and move on. Phase 9 will verify every item on that list.

### 2.4 Phase 9 — the ONLY time you run and test
- Boot the app **once**, run the QA checklist in `Tasks.md` in one continuous pass, collect **all** failures into a single list.
- Fix them **in one batch**, then re-test **only the failed items**. Maximum **2 fix rounds**; anything still failing goes to `Memory.md` → Known Issues.
- Browser/UI agent usage is allowed in Phase 9 only. Use a Supabase MCP only if the human has connected it, and only to apply `database/schema.sql` / `seed.sql`.
- Never create a testing framework. The checklist is the test plan.

---

## 3. Work Protocol (the loop)

```
At session start:  read Rules → Memory (Status + Verify-in-QA) → Tasks (find first unchecked task)
For each task:     implement → tick [x] in Tasks.md (batch ticks per file group, not per line)
At phase end:      run the phase gate (static checks, §2.2) → fix → update Memory.md (≤ 10 lines) → next phase
```

- Work **one phase at a time** and move to the next automatically.
- Create/edit files in batches (several related files per step); don't generate one tiny file per turn.
- **Edit, don't rewrite:** for changes use targeted edits; never regenerate a whole file for a small fix.
- **Context hygiene:** if the conversation gets long or you feel context pressure, finish the current task, update `Memory.md` → Status with `NEXT: T-x.xx`, and stop cleanly so a fresh session can resume.
- Do not paste large file contents back into chat; summarize in one or two lines.
- Do not run `git push` or any deploy command. Committing locally with message `phase N: <summary>` at each phase gate is fine if git is initialized.

### Human checkpoints (the ONLY times you may pause for the human)
| # | When | What the human does |
|---|---|---|
| H1 | Start of Phase 9 | Create Supabase project; run `database/schema.sql` + `seed.sql` in the SQL Editor; copy URL + keys into `.env`; install PHP 8.3 CLI if missing (or authorize QA on a deployed URL) |
| H2 | Phase 9 (after registering) | Register an account, run `database/make_admin.sql` with their email |
| H3 | Phase 10 | Push to GitHub, create Render service, set env vars, add `APP_URL` GitHub secret |

**Never ask for credentials, keys or accounts before Phase 9.** Build everything against `.env.example` placeholders.

---

## 4. Coding Standards (PHP)

- Every file: `<?php declare(strict_types=1);`. Namespaces `App\…` mapped to `src/`. One class per file, file name = class name.
- Type-hint parameters and return types. No globals except through `Support/helpers.php`.
- **Never** read `$_GET/$_POST/$_COOKIE/$_FILES/$_SERVER` outside `Core\Request` (and `Core\Session` for the cookie).
- **Output:** every dynamic value in views goes through `e()`. URLs via `url()`, assets via `asset()`. Never `echo $var` raw.
- **Input:** validate with `Core\Validator`; whitelist sort/filter keys; regex-validate route params; cast numbers; trim and length-limit strings.
- **Supabase:** only via `Supabase\Client`, `Auth`, `Storage`. Build queries per `Architecture.md` §8. The secret key is read only inside `Supabase\Client`/`Auth`/`Storage` — never in controllers or views.
- **POST flow:** validate → act → `Response::redirect()` (Post/Redirect/Get). Flash messages for feedback; old input via `old()`.
- **Errors:** throw/handle exceptions; log with `Logger`; show friendly messages. Never expose stack traces, SQL/REST errors, or keys (except `APP_ENV=local`).
- Forbidden: `eval`, `extract` outside `View`, `@` error suppression, `die/exit` outside `Response`, `var_dump/print_r` left in code, `md5/sha1` for security, `file_put_contents` to the app directory (disk is ephemeral).
- Size: files < 300 lines, functions < 50 lines. Split when exceeded. Comments explain *why*, not *what*.
- Money: integers only (`*_cents`). Never floats for prices.
- Time: UTC in DB; display dates formatted `j M Y, H:i`.

## 5. Frontend Standards

- Build only from `Design.md` tokens/components. No new colors/fonts/shadows. Both themes for every component.
- **CSP-safe:** no inline `<script>`, no `style=""`, no `onclick=""`. JS in `public/assets/js/*.js` with `defer`; theme init is the only blocking script (`theme-init.js`, tiny).
- **Progressive enhancement:** every action works with plain forms (POST + redirect). JS only upgrades (drawer, fetch add-to-cart, gallery, theme toggle).
- Vanilla ES2020, no libraries, no `eval`, no `innerHTML` with unescaped data (use `textContent` or server-rendered HTML fragments from `/cart/mini`).
- CSS: class names `block__element--modifier` (BEM-ish); no IDs for styling; no `!important`; mobile-first media queries; animate only `transform`/`opacity`.
- Semantic HTML, labels, alt text, aria as in `Design.md` §10.
- Every `<img>`: `alt`, `width`, `height`, `loading="lazy"` (except the first hero/product image).

## 6. Security Checklist (apply while writing, verify with greps at Phase 7)

- [ ] Secrets only from env; `.env` gitignored; `.env.example` has dummy values.
- [ ] `SUPABASE_SECRET_KEY` appears only in `src/Supabase/*` and `src/Core/Env.php` usage.
- [ ] CSRF check on **every** POST route (middleware declared in the route table).
- [ ] Admin routes use the `admin` middleware (DB role re-check).
- [ ] Order/account queries always filter by the session user id.
- [ ] Uploads validated with `finfo`, size-capped, random names.
- [ ] `next`/redirect targets validated (start with `/`, not `//`).
- [ ] Security headers sent on every response (`Architecture.md` §10).
- [ ] Generic login error; short delay on failure.

## 7. Performance Rules

- Select only needed columns; always `limit`; reuse the single cURL handle; use `Cache::remember` for settings, categories, featured, new arrivals (60 s) and `Cache::forget` after admin writes.
- No N+1: hydrate cart/order lines with one `id=in.(…)` query.
- Keep CSS/JS within budgets in `PRD.md` §8.

## 8. File & Naming Conventions

- PHP classes `PascalCase`; methods/variables `camelCase`; DB columns `snake_case`; CSS `kebab-case`; routes lowercase kebab.
- Views are plain PHP templates; no business logic in views (formatting only).
- Controllers are thin (≤ 15 lines per action where possible); logic in Services/Repositories.

## 9. Documentation Duties (cheap, mandatory)

- Tick tasks in `Tasks.md` as you finish them.
- Update `Memory.md` at each **phase end** (status, new decisions, new VERIFY-IN-QA items, backlog ideas). Max 10 lines per update.
- If you deviate from `Architecture.md`, update `Architecture.md` in the same step and log why.

## 10. Definition of Done (for the whole project)

All of `PRD.md` §10 satisfied · Phase 9 checklist passes (or leftovers documented) · README complete · no secrets in repo · deploy files present · `Memory.md` Status = "COMPLETE".

## 11. Quick Anti-Patterns List (never do)

- Running the app or tests before Phase 9.
- Asking the user questions that the docs already answer.
- Adding frameworks/libraries "to speed things up".
- Calling Supabase from browser JS.
- Storing prices in the cart/cookie, or trusting client-side totals.
- Using floats for money; using `select=*` on lists.
- Hard-coding store name/currency/shipping instead of `settings()`.
- Skipping the dark theme or mobile layout "for later".
- Leaving TODO/placeholder lorem text in shipped pages (write real copy for static pages and seed data).
