<div style="text-align: center;">
  <img src="https://jcadima.dev/images/vaultcheck_bg.png" alt="A PHP CLI tool that audits environment variable and secrets hygiene across your project">
</div>


# VaultCheck

A PHP CLI tool that audits environment variable and secrets hygiene across your project. Think of it as a spell-checker for your `.env` files it catches security problems before they become incidents.

```
  CRITICAL  [E008]  APP_DEBUG=true in a production environment exposes stack traces.
  CRITICAL  [G008]  STRIPE_KEY: current value was found in git history  never rotated.
  HIGH      [E007]  APP_KEY is empty. Laravel cannot encrypt sessions without it.
  MEDIUM    [E011]  Duplicate key 'DB_PASSWORD' on line 14 (first seen on line 8).
  LOW       [C001]  Environment variable 'LEGACY_KEY' is defined but never referenced.

  5 finding(s): 2 CRITICAL, 1 HIGH, 1 MEDIUM, 1 LOW
```

---

## Requirements

- PHP 8.2+
- `git` binary (for G001–G008 git history checks)

---

## Installation

**Via Composer (recommended):**
```bash
composer global require jcadima/vaultcheck
```

After install, make sure Composer's global `bin/` directory is in your `$PATH`. The directory differs between Composer v1 and v2, so let Composer resolve it for you:

```bash
# Works for both Composer v1 and v2 — detects the correct bin dir automatically
echo "export PATH=\"\$PATH:$(composer global config bin-dir 2>/dev/null)\"" >> ~/.bashrc
source ~/.bashrc
```

If you use **zsh**, replace `~/.bashrc` with `~/.zshrc`.

**VPS / server alternative — symlink to `/usr/local/bin` (no shell profile changes needed):**
```bash
sudo ln -sf "$(composer global config bin-dir)/vaultcheck" /usr/local/bin/vaultcheck
```

This makes `vaultcheck` available to all users and works in cron jobs, scripts, and CI pipelines without any PATH configuration.

Verify the install:
```bash
vaultcheck --version
vaultcheck audit /path/to/your/project
```

**Via Docker Compose (local build):**
```bash
git clone https://github.com/jcadima/vaultcheck.git
cd vaultcheck

# Build and start the container
docker compose -f docker-compose-local.yml up -d --build

# Install dependencies (once)
docker compose -f docker-compose-local.yml exec vaultcheck composer install

# Run the audit against a target project
docker compose -f docker-compose-local.yml exec vaultcheck php bin/vaultcheck audit /path/to/project

# Stop when done
docker compose -f docker-compose-local.yml down
```

**From source:**
```bash
git clone https://github.com/jcadima/vaultcheck.git
cd vaultcheck && composer install
php bin/vaultcheck audit /path/to/your/project
```

---

## Usage

### `vaultcheck audit` : Run a full security audit

> By default, only **CRITICAL** and **HIGH** findings are shown — the genuine red flags that need immediate action. Lower-severity findings are still detected and a count is printed at the bottom. Use `--min-severity` to reveal them when you're ready to dig deeper.

```bash
# Scan current directory (shows CRITICAL + HIGH only by default)
vaultcheck audit

# Scan a specific path
vaultcheck audit /path/to/project

# See MEDIUM findings too (e.g. weak secrets, missing .env.example keys)
vaultcheck audit --min-severity=MEDIUM

# Show all findings including LOW-priority items
vaultcheck audit --min-severity=LOW

# Show only the most critical issues
vaultcheck audit --min-severity=CRITICAL

# Output as JSON (useful for CI pipelines and dashboards)
vaultcheck audit --output=json

# Output as Markdown (useful for reports and documentation)
vaultcheck audit --output=markdown

# Exit with code 1 if any MEDIUM or higher finding exists (for CI/CD gates)
vaultcheck audit --strict

# Skip git history scanning (faster for local dev)
vaultcheck audit --skip-history

# Scan entire git history instead of just the last 500 commits
vaultcheck audit --full-history
```

Valid values for `--min-severity`: `CRITICAL`, `HIGH` *(default)*, `MEDIUM`, `LOW`, `INFO`.

### `vaultcheck keys` : List all environment variables and their status

```bash
vaultcheck keys /path/to/project
```

```
Key              Status           Value (masked)   References
APP_KEY          MISSING_DEFAULT  ba**********h=   0 ref(s)
DB_PASSWORD      UNUSED           ch**me           3 ref(s)
UNDEFINED_KEY    EMPTY            (empty)          —
```

| Status | Meaning |
|--------|---------|
| `DEFINED` | Has a value and is referenced in code |
| `EMPTY` | In `.env` but has no value |
| `EXAMPLE_ONLY` | Only in `.env.example`, not in `.env` |
| `UNUSED` | In `.env` but never called in PHP code |
| `MISSING_DEFAULT` | Called via `env('KEY')` without a fallback |

### `vaultcheck snapshot` - Save a baseline

```bash
# Save current state (key hashes + findings)
vaultcheck snapshot /path/to/project

# Include git history checks in the snapshot
vaultcheck snapshot --include-history /path/to/project
```

Saves to `.vaultcheck/snapshot.json`. Secret values are **never stored** — only SHA-256 hashes.

### `vaultcheck drift` : Detect what changed since the snapshot

```bash
vaultcheck drift /path/to/project
```

```
Key Changes:
  [+] NEW     STRIPE_KEY  (added)
  [=] same    APP_KEY
  [~] CHANGED DB_PASSWORD (value changed)

Finding Changes:
  [+] NEW     [CRITICAL] G002  Stripe key found in history
  [-] RESOLVED [HIGH]    E015  .env.bak backup file found
```

### `vaultcheck fix` : Auto-fix safe issues

```bash
# Preview what would be fixed (no changes applied)
vaultcheck fix --safe --dry-run /path/to/project

# Apply all safe fixes with confirmation prompt
vaultcheck fix --safe /path/to/project

# Apply without confirmation
vaultcheck fix --safe --yes /path/to/project
```

| Issue fixed | Action |
|------------|--------|
| P001 — world-readable `.env` | `chmod 600 .env` |
| P002 — world-writable `.env` | `chmod 600 .env` |
| P003 — group-writable `.env` | `chmod 640 .env` |
| E010 — Windows CRLF line endings | Convert `\r\n` → `\n` |
| E011 — duplicate keys | Remove duplicates, keep first |

---

## CI/CD Integration

Add VaultCheck to your pipeline to block deployments if secrets hygiene regresses:

```yaml
# GitHub Actions example
- name: Audit secrets hygiene
  run: |
    composer global require jcadima/vaultcheck
    vaultcheck audit --strict --skip-history
```

The `--strict` flag causes the process to exit with code `1` if any `MEDIUM` or higher finding exists, failing the pipeline step.

---

## Check Reference

### Environment (E001–E015)

| ID | Severity | What it catches |
|----|----------|----------------|
| E001 | HIGH | `.env` file is missing |
| E002 | MEDIUM | `.env.example` is missing |
| E003 | MEDIUM | Key in `.env` but missing from `.env.example` |
| E004 | LOW | Key in `.env.example` but absent from `.env` |
| E005 | HIGH | Empty value in production |
| E006 | MEDIUM | Placeholder value (`changeme`, `your-key-here`, etc.) |
| E007 | HIGH | `APP_KEY` missing, empty, or malformed |
| E008 | CRITICAL | `APP_DEBUG=true` in production |
| E009 | HIGH | `DB_HOST` set to `localhost` in production |
| E010 | LOW | Windows CRLF line endings |
| E011 | MEDIUM | Duplicate key in `.env` |
| E012 | HIGH | Real-looking secret value in `.env.example` |
| E013 | LOW | No log level configured |
| E014 | MEDIUM | Development driver (`file`, `sync`, `array`) in production |
| E015 | HIGH | Backup `.env` file found (`.env.bak`, `.env.old`, etc.) |

### Codebase (C001–C005)

| ID | Severity | What it catches |
|----|----------|----------------|
| C001 | LOW | Env var defined in `.env` but never referenced in code |
| C002 | HIGH / MEDIUM / LOW | Code calls `env('KEY')` for a key not defined in `.env`. Severity depends on call origin: **HIGH** when application code (e.g. `app/`) has no fallback default; **MEDIUM** when application code has a fallback default; **LOW** when only `config/` files reference it (optional framework integrations). |
| C003 | MEDIUM | `env('KEY')` called without a fallback default in application code. Calls originating only from `config/` files are suppressed — Laravel framework configs intentionally omit defaults for optional integrations. |
| C004 | MEDIUM | `env()` called outside a `config/` file (breaks `config:cache`) |
| C005 | LOW | Casing mismatch between `.env` key and `env()` call |

### Permissions (P001–P004)

| ID | Severity | What it catches |
|----|----------|----------------|
| P001 | CRITICAL | `.env` is world-readable |
| P002 | CRITICAL | `.env` is world-writable |
| P003 | MEDIUM | `.env` is group-writable |
| P004 | CRITICAL | `.env` inside `public/`, `web/`, or other web-accessible directory |

### Consistency (X001–X005)

| ID | Severity | What it catches |
|----|----------|----------------|
| X001 | HIGH | `DB_PASSWORD` identical across environment files |
| X002 | CRITICAL | `APP_KEY` shared between environments |
| X003 | HIGH | Sensitive key has the same value in production and non-production |
| X004 | MEDIUM | `APP_ENV` value doesn't match what the filename implies |
| X005 | LOW | Key in `.env.staging` / `.env.testing` not in `.env.example` |

### Strength (S001–S006)

| ID | Severity | What it catches |
|----|----------|----------------|
| S001 | MEDIUM | Secret shorter than 16 characters |
| S002 | LOW | Secret is all lowercase (low entropy) |
| S003 | HIGH | Secret matches a known-weak password |
| S004 | MEDIUM | `APP_KEY` set but missing the `base64:` prefix |
| S005 | HIGH | `JWT_SECRET` shorter than 32 characters |
| S006 | HIGH | `DB_PASSWORD` is the same as `DB_USERNAME` |

### Git History (G001–G008)

| ID | Severity | What it catches |
|----|----------|----------------|
| G001 | CRITICAL | `.env` was ever committed to git history |
| G002 | CRITICAL | Known service credential (Stripe, AWS, GitHub, etc.) found in a commit |
| G003 | HIGH | High-entropy token found in a commit (likely secret, unknown format) |
| G004 | HIGH | `.env.bak` or `.env.backup` was ever committed |
| G005 | HIGH | Hard-coded credential found in `config/` directory history |
| G006 | CRITICAL | `.env` not listed in `.gitignore` |
| G007 | HIGH | `.env` committed before `.gitignore` was set up |
| G008 | CRITICAL | Current `.env` value found in git history — leaked and not rotated |

## License

MIT
