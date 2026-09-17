# AGENTS.md — extension-tao-mediamanager (taoMediaManager)

> Shared pillars (standards, quality / `pr-ready-gate`, Make, commit/PR):
> [nextgen-stack `tao/AGENTS.md`](https://github.com/oat-sa/nextgen-stack/blob/main/tao/AGENTS.md)
> · local: [`../AGENTS.md`](../AGENTS.md).

## 01 — Project Context

**What / why:** `oat-sa/extension-tao-mediamanager` (id `taoMediaManager`) is the
**Assets / Media library**: upload/manage media, Shared Stimulus, relations,
import/export, preview.

**Critical:** Resource Manager modal / asset search chrome is
**`@oat-sa/tao-core-ui`**, not this package. Creator call sites live in
`taoQtiItem`.

**Key directories / stack / constraints:**

```text
manifest.php
controller/MediaManager.php, SharedStimulus.php, …
model/MediaService.php, model/sharedStimulus/, …
views/js/controller/
views/js/qtiCreator/          # Shared Stimulus (here, not taoQtiItem)
views/js/richPassage/
views/js/loader/
test/
```

Assets → `/taoMediaManager/MediaManager/index`.

- Stack: PHP tao-core/items/QTI + `lib-generis-search`; usually no
  `views/package.json`.
- Versions from composer/CI.

**Docs:** [`README.md`](README.md). Shared docs / decision-log rules → parent AGENTS.

## 02 — Standards & Conventions

Package-only below. Family patterns, quality SoT, `pr-ready-gate`, polar-star →
**parent AGENTS**.

**Patterns / structure:**

- Keep Assets vs Items vs RM ownership sharp.
- Shared Stimulus editors stay here unless scope explicitly moves.

**Never do (this package):**

- Implement RM search UI here; blur with `taoItems` ItemContent APIs.
- Fork `ui/resourcemgr`; hand-edit loaders.

**Ownership**

| Surface | Own? |
|---------|------|
| Assets library | **Yes** |
| Shared Stimulus authoring | **Yes** (here) |
| RM / asset search chrome | **No** |
| Creator call sites opening RM | **No** (`taoQtiItem`) |

## 03 — Build & Test Commands

Shared Make / CI / readiness / commit policy → **parent AGENTS**
([commit/PR policy](https://oat-sa.atlassian.net/wiki/x/_oXmqQ)).

**This package** (from Composer platform root):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist taoMediaManager/test
npx grunt eslint:extensionreport --extension=taoMediaManager --force
npx grunt taobundle --extension=taoMediaManager
```
