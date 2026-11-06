# AGENTS.md — Moloni for WHMCS

Guidance for AI coding agents (Claude Code, Cursor, Copilot, …) and new developers working in this plugin. This is the canonical context file; `CLAUDE.md` imports it.

## What this plugin is

**Moloni** (`moloni.php`, WHMCS addon module `moloni`, version in `MOLONI_ADDON_VERSION`) is a WHMCS **addon module** that connects a WHMCS installation to Moloni (moloni.pt) invoicing. It turns **paid WHMCS invoices** into Moloni documents (invoice, invoice-receipt, estimate or bill of lading, per settings), creating on the fly the Moloni customers, products, product category, taxes and payment methods those documents need. It is a PHP module that talks to Moloni **only over the public API v1** (`https://api.moloni.pt/v1/`). It does not run alongside any Moloni platform or call internal services.

## Responsibilities & boundaries

**Owns** (reacts to WHMCS hooks and admin actions, never webhooks; there is **no cron**):
- **Automatic document generation:** the WHMCS `InvoicePaid` hook (`hooks.php`, priority 1) → `Services\Invoices\CreateDocumentFromInvoice` with `setIsHook(true)`, only when the **Gerar automaticamente** setting (`INVOICE_AUTO`) is on and a valid company + token exist. Failures are logged to `moloni_logs`, never shown.
- **Manual document generation:** the addon page's pending list (`templates/index.php`, paid invoices not yet in `moloni_invoices`) → `?module=moloni&action=invoice&command=gen&id=<invoiceid>` → `Admin\Dispatcher::invoicePage()` → the same service.
- **Discard / redo:** `action=invoice&command=delete` marks an invoice as "not generated" (row in `moloni_invoices`, `invoice_id = -1`); `action=docs&command=redo` deletes that row so the invoice is pending again (`Dispatcher::documentsPage()`).
- **Admin UI:** login → company select → pending invoices, generated documents (`action=docs`), settings (`action=config`, `command=save`), logs (`action=logs`, Ajax DataTable via `&ajax=1`, `command=delete` purges old logs), logout. All routed by `moloni_output()` → `Admin\Dispatcher`.
- **Module lifecycle:** `moloni_activate` / `moloni_upgrade` / `moloni_deactivate` → `Installer\Installer` (tables `moloni`, `moloni_configs`, `moloni_invoices`, `moloni_logs`).

**Must not:**
- **double-issue a document.** The guards are the `moloni_invoices` row (`WhmcsDB::getInvoice()` refuses any invoice already in it) and the remote `documents/getOne` lookup by `your_reference` = WHMCS `invoicenum`. Keep both.
- invent tax/exemption/document-type/product-type mappings. Those are fiscal-legal.
- hard-code more credentials, a company or an endpoint (see Gotchas on the existing ones in `Curl.php`).
- issue credit notes or react to WHMCS refunds/cancellations. **Not supported, out of scope by design**: no hook, action or service handles them, and none should be added as a side effect of other work.

**Moloni API surface used** (v1, `Curl::simple('<resource>/<method>')`, form-encoded POST, `access_token` in the query string):
- **Auth:** `grant` (`password` and `refresh_token` grants).
- **Document flow:** `companies/getOne`, `countries/getAll`, `currencies/getAll`, `currencyExchange/getAll`, `customers/getByVat` | `getByEmail` | `getByNumber` (`9990` = generic consumer) | `getNextNumber` | `insert` | `update`, `products/getByReference` (`exact=1`) | `insert`, `productCategories/getAll` | `insert` (category `WHMCS`), `taxes/getAll` | `insert`, `paymentMethods/getAll` | `insert`, `<documentType>/insert` | `update` (type = `DOCUMENT_TYPE`: `invoices`, `invoiceReceipts`, `estimates`, `billsOfLading`), `documents/getOne`, `documents/getPDFLink`.
- **Settings/UI:** `companies/getAll`, `documentSets/getAll`, `taxExemptions/getAll`, `measurementUnits/getAll`, `maturityDates/getAll`, `paymentMethods/getAll`.
- `src/Moloni/Api/` also wraps endpoints nothing calls (deletes/updates of sets, units, delivery methods, alternate addresses, product counts/searches…). Don't treat a wrapper's existence as proof the flow uses it.

## Architecture map

| Path | What it is |
| --- | --- |
| `moloni.php` | Addon entry: `MOLONI_ADDON_VERSION`, `moloni_config()`, `_activate` / `_upgrade` / `_deactivate` (→ `Installer`), `_output()` (→ `Dispatcher`, `&ajax=1` → `dispatchAjax()`) |
| `hooks.php` | WHMCS hooks — only `InvoicePaid` |
| `src/Moloni/Admin/Dispatcher.php` | Admin router: login, company select, `action`/`command` handling, picks the template |
| `src/Moloni/Start.php` | Loads tokens/company from the `moloni` table into `Core\Storage`; token validity + refresh; `variablesDefine()` |
| `src/Moloni/Services/Invoices/CreateDocumentFromInvoice.php` | The use case: fiscal zone → basics → exchange → customer → lines → payment → duplicate check → insert → close/email → `moloni_invoices` row + log |
| `src/Moloni/Settings.php` | `buildProduct()`: maps one WHMCS invoice item (by `type`) to a line `name` / `summary` / `reference` / `discount` / product `type`, or `skip` / `massPay` |
| `src/Moloni/Curl.php` | Moloni API client (`simple`, `login`, `refresh`) |
| `src/Moloni/Api/` | Thin static wrappers per API resource (`GlobalSettings/`, `Settings/` subfolders). `Api/Settings/Taxes::check()` finds **or creates** a tax |
| `src/Moloni/Model/WhmcsDB.php` | All DB access (WHMCS `Capsule`): addon tables + reads of WHMCS tables (`tblinvoices`, `tblinvoiceitems`, `tblclients`, `tblhosting`, `tbldomains`, `tblhostingaddons`, `tblupgrades`, `tblcustomfields*`, `tblpaymentgateways`, …) |
| `src/Moloni/Installer/Installer.php` | Table creation, default settings rows, upgrade, removal |
| `src/Moloni/Enums/` | `DocumentType`, `DocumentStatus`, `DocumentCreationStatus` (the `moloni_invoices.value` codes), `TaxType`, `SaftType`, `LogLevel` |
| `src/Moloni/Exceptions/` | `DocumentException` (error), `DocumentWarning` (inserted but totals mismatch), `APIException`, `GenericException` |
| `src/Moloni/Logs/Logger.php`, `src/Moloni/Facades/LoggerFacade.php` | PSR-3 logger into `moloni_logs` |
| `src/Moloni/Services/Logs/` | Logs page fetch / purge |
| `src/Moloni/Error.php`, `src/Moloni/Tools.php` | Flash messages; URL helpers (`genURL`, `getPublicUrl`), select helpers |
| `templates/` | Admin views (`index`, `document`, `config`, `logs`, `login`, `company`, `Blocks/`, `Modals/`) |
| `public/css/*.scss`, `public/js/Logs.js` | Asset sources |
| `public/compiled.*` | **Compiled** CSS/JS loaded by the templates (committed build output) |
| `vendor/` | Composer autoloader + `psr/log` — **not committed** (`.gitignore`); built by `composer install` / the release workflow |
| `.github/workflows/publish.yaml` | On `v*` tag: `composer install --no-dev`, `npm run build-prod`, zip `moloni/`, GitHub release |
| `build.bat` | Legacy local Windows zip script (see Gotchas) |

No git submodules (`.gitmodules` absent).

## Running locally

There is no Docker/compose stack in the repo. You need a licensed local WHMCS install.

1. **PHP deps:** `composer install` at the root (creates `vendor/`, required at runtime — the addon `require`s `vendor/autoload.php`). Use `--no-dev` to match the release; the dev tools in `composer.lock` need PHP ≥ 8.2.
2. **Assets** (only when touching CSS/JS): `npm run build-prod` at the root (it runs `npm i` itself). Use the Node version in `.nvmrc` (the release workflow uses the runner's default Node).
3. **Install into WHMCS:** place (or symlink) the checkout at `<whmcs>/modules/addons/moloni/` — the folder name must be `moloni` (`Tools::getPublicUrl()` hard-codes `/modules/addons/moloni/public/`). In WHMCS admin → System Settings → Addon Modules, **Activate** "Moloni" and grant your admin role access. Open Addons → Moloni (`addonmodules.php?module=moloni`).
4. Log in with a Moloni account, pick the company, then fill **Configurações** (document set, type, status, exemption reason, measure unit, …). Moloni credentials (tokens in `moloni`) and settings (`moloni_configs`) are configured in the addon's admin only — never hard-coded, never committed.
5. Mark an invoice as paid (auto mode) or use the pending list (manual) to generate a document. Use a Moloni test company and keep documents as **drafts** (`DOCUMENT_STATUS = 0`) unless closing them is the point of the test.

**Supported versions:** **WHMCS 7+/8** and **PHP 7.0–8.x**. Nothing in the repo declares this (no `php` constraint in `composer.json`, no WHMCS minimum in `moloni_config()`), so it is a maintained convention: the runtime code uses PHP 7.0 syntax (return types, scalar hints, `??`), the changelog lists PHP 8.1 fixes, and it relies on WHMCS `Capsule` and `WHMCS\Config\Setting`.

## Commands

| Command | Where | What it does |
| --- | --- | --- |
| `composer install` (`--no-dev` for release parity) | root | Install `psr/log` + the PSR-4 autoloader into `vendor/` |
| `npm run build-prod` | root | `npm i` + gulp `css:prod` (Sass → `public/compiled.min.css` + map) and `js:prod` (Babel: Materialize + DataTables + `public/js/Logs.js` → `public/compiled.js` / `compiled.min.js`) |
| `vendor/bin/phpcs` / `vendor/bin/php-cs-fixer` | root | Available as dev deps, but **no ruleset/config is committed** — only use them ad hoc, don't reformat untouched code |
| `php -l <file>` | root | Syntax check |

## Verification

**There is no automated test or lint gate.** No `phpunit`, `phpcs` or `php-cs-fixer` config, and the only workflow (`publish.yaml`) packages a release on `v*` tags. Nothing runs on PRs. To verify a change:

1. `php -l` every touched PHP file. Keep syntax **PHP 7.0-compatible**: no typed properties, arrow functions, `match`, enums, nullsafe `?->`, constructor promotion, `readonly`, nullable/`void` return types.
2. **Exercise it in a local WHMCS**: pay an invoice with auto on; generate manually; discard + redo; settings save; logs page. Cover the item types you touched (Hosting, Domain*, Addon, Upgrade, Setup, AddFunds, LateFee, Item, mass-pay `Invoice`), and a foreign-currency client if you touched prices/payments. Check the result in Moloni and in `moloni_invoices` / `moloni_logs`.
3. Rebuild assets if you touched `public/css` or `public/js`, and commit `public/compiled.*`.

## Conventions

- **Namespace `Moloni\`**, PSR-4 → `src/Moloni/`, one class per file. API wrappers are static (`Api\<Resource>::method`), throw `APIException` on write failures; the service converts them to `DocumentException`.
- **WHMCS idioms:** addon functions are `moloni_<name>` in `moloni.php`, hooks via `add_hook()` in `hooks.php`, DB through `WHMCS\Database\Capsule` (query builder or its PDO) — all of it inside `Model\WhmcsDB`.
- **Settings are PHP constants.** `WhmcsDB::variablesDefineMoloni()` turns every `moloni_configs` row into an upper-cased constant (`DOCUMENT_SET`, `DOCUMENT_TYPE`, `DOCUMENT_STATUS`, `INVOICE_AUTO`, `EMAIL_SEND`, `REMOVE_TAX`, `EXEMPTION_REASON`, `MEASURE_UNIT`, `MATURITY_DATE`, `PAYMENT_METHOD`, `UPDATE_CUSTOMER`, `AT_CATEGORY`, `CUSTOM_REFERENCE`, `CUSTOM_CLIENT`, `AFTER_DATE`, `AFTER_DATE_DOC`, …). Call `Start::variablesDefine()` first and read them with a `defined('X') && X` guard. A new setting needs a row in **both** `Installer::setDefaultSettings()` and `WhmcsDB::variablesUpdateMoloni()`, plus the field in `templates/config.php`.
- **API calls** go through `Curl::simple('<resource>/<method>', $values)`; `company_id` is injected from `Storage::$MOLONI_COMPANY_ID`.
- **Item mapping lives in `Settings::buildProduct()`**, one `case` per WHMCS item `type`. The returned `reference` is the Moloni product key (`products/getByReference`); `name`/`summary` go on the document line.
- **Merchant-facing UI follows what `templates/` already renders** (Materialize-styled pages inside the WHMCS admin addon area). Don't restyle or introduce another look.
- **User-facing strings are pt-PT**, hard-coded (no i18n layer).
- **Assets are compiled artifacts.** Edit `public/css/*.scss` / `public/js/*.js`, never hand-edit `public/compiled.*`, and commit the rebuilt output.
- **Branch flow:** cut feature branches from `devel` and open PRs against `devel`. `main` (the default branch) is the release branch: a release merges `devel` → `main` and pushes a `v*` tag, and GitHub Actions (`publish.yaml`) builds the zip and the GitHub release.
- **Every release carries its version bump:** `moloni.php` `MOLONI_ADDON_VERSION`, `package.json` `version`, and a new top entry in `composer.json` → `extra.changelog` (pt-PT). The publish workflow reads version + release notes from `composer.json`.
- **Idempotency + fiscal correctness are load-bearing.** Document creation must not double-issue, and tax/document-type/exemption mappings are fiscal-legal.

## Gotchas

Curated, verified conventions and traps. This is the default-trusted source — every agent reads it.

- **API v1, not v2.** `Curl::$apiUrl` is `https://api.moloni.pt/v1/`; endpoint names and payloads follow v1.
- **`Curl.php` hard-codes the API client id/secret** in the `grant` URLs (pre-existing). Don't copy them anywhere else, don't log full grant URLs, and don't add new secrets to the code.
- **Shared fallback references merge products.** `getParsedProduct()` looks the product up by `reference` and creates it only once, from that first item's `name`/`summary`/price/tax. Later items with the same reference reuse that Moloni product; only the **document line** gets the current item's `name`/`summary`. Fixed references: `Alojamento` (every Hosting product without the `CUSTOM_REFERENCE` custom field), `Addon` (addons whose info lookup fails), `UPGRADE`, `ADD-FUNDS`, `LATE-FEE`, `TAX-INSTALL`, `9999` (generic items). So the Moloni product name can differ from the line name.
- **`LateFee` asks for product `type = 3`, but `getParsedProduct()` only accepts `0/1/2`.** So type 3 is never sent: it falls back to `AT_CATEGORY` (`SS` → service 2, else product 1 with `has_stock = 1`). Likely a bug; confirm the intended Moloni product type before changing it.
- **Item types without a `discount` key** (Addon, Upgrade, Setup, AddFunds, LateFee) read `$invoicedItem['discount']` anyway → PHP 8 "undefined array key" warning. Harmless, but set the key when you touch a case.
- **Negative `Item` lines are dropped** (credits/discounts), and promo lines (`PromoHosting`/`PromoDomain`) are folded into the line `discount` as a **rounded integer percentage**, which can shift totals. Unknown item types are skipped silently.
- **Totals mismatch ≠ failure.** If the inserted document's `net_value` (or `exchange_total_value`) ≠ the WHMCS total, the document stays a **draft**, the row is saved with `INSERTED_WITH_ERROR` (3) and a `DocumentWarning` is raised. It is never closed or emailed.
- **The remote duplicate check needs `invoicenum`.** `documents/getOne` by `your_reference` only runs when the WHMCS invoice has a number; otherwise only the local `moloni_invoices` row protects against re-issue. That row is written **after** the API insert and is skipped (`Error::create`) when `DOCUMENT_STATUS` is undefined, so a failed write leaves an issued document with no local trace.
- **Discard reuses `-1`.** `Dispatcher::deleteInvoice()` stores `value = -1` (same as `DocumentCreationStatus::MASS_PAY`), not `DISCARDED` (4). `Dispatcher::invoicePage()` discards on **any** `action=invoice` request without `command=gen`.
- **Mass-pay invoices** (`Invoice` item type) are recorded with `document_id = -1` and no document when they have no other lines.
- **Taxes and payment methods are created in the merchant's Moloni company** when no match exists (`Taxes::check()` by rate + fiscal zone + country, `PaymentMethods::insert()` by gateway display name). A wrong rate/zone pair leaves a permanent tax there. The UK is special-cased to Moloni `country_id` 174.
- **Customer matching:** custom VAT field (`CUSTOM_CLIENT`) → `tax_id` → `999999990`. With the generic VAT it matches by email, else falls back to customer number `9990`. The generic customer is never updated.
- **Token handling:** `date_expire` = login + 3000 s; refresh is attempted up to 13 days after that. When both are stale, the **hook silently does nothing** (no log), and the next admin page load truncates the `moloni` table and shows login. Auto-generation stops until someone logs in again.
- **Deactivate drops `moloni` + `moloni_configs` only.** `moloni_invoices` and `moloni_logs` survive, so history (and the double-issue guard) persists across reinstalls. `moloni_upgrade` only creates `moloni_logs` — new tables or columns need code in `update()`.
- **The addon tables are unprefixed** (`moloni`, `moloni_configs`, `moloni_invoices`, `moloni_logs`), not `mod_*`.
- **The pending list shows only `Paid` invoices** (`WhmcsDB::$orderStatusToShow`) since `AFTER_DATE`.
- **`vendor/` is not committed.** A raw checkout won't load until `composer install` runs. The release zip ships `vendor/` from the workflow.
- **`build.bat` copies an `index.php` that doesn't exist** and runs `composer install --no-dev` without building assets. Prefer the `publish.yaml` flow.
- **No submodules** (`.gitmodules` absent).

> Recent, **unverified** findings live in `.claude/journal/` (one dated file per finding). They are promoted into this section by manual curation once re-verified against the code — treat them as leads, not yet rules.
