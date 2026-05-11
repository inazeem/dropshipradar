# Deployment Build Fixes - Nixpacks & Docker Issues

## Problem Summary
Your Laravel 13 app with PHP 8.3 was failing to deploy with Nixpacks, encountering multiple package conflicts and missing dependencies during the build phase.

## Root Causes

### 1. **Nix Package Collision** (Initial Error: exit code 100, 25)
**Error:**
```
error: collision between `/nix/store/rkh77vjs1p54257ws66sqdfq55rpz8kz-pnpm/LICENSE' and 
`/nix/store/i8f67ms9l574ffwycz1gbjkq1dx2ha0p-composer-2.8.4/LICENSE'
```

**Root Cause:** Nixpacks was attempting to install both `pnpm` and `composer` via Nix package manager. Both packages contain a LICENSE file, causing a collision in the Nix store when trying to create a unified environment.

**Why it happened:** 
- Your project used `pnpm-lock.yaml` (v9.0)
- Nixpacks auto-detected both Node and PHP needs
- It tried to install pnpm as a standalone Nix package instead of using npm (which ships with Node)

### 2. **pnpm Undefined Variable** (exit code 1)
**Error:**
```
error: undefined variable 'composer'
at /app/.nixpacks/nixpkgs-e24b4c09e963677b1beea49d411cd315a024ad3a.nix:19:9
```

**Root Cause:** Attempted to add `composer` to `nixPkgs` array in nixpacks.toml, but `composer` is not a top-level nixpkgs variable. It's bundled with PHP.

### 3. **pnpm Install Failures** (exit code 1)
**Errors:**
- `pnpm install` without explicit version spec
- Missing `packageManager` field in package.json
- Corepack couldn't determine which pnpm version to enable

**Root Cause:** pnpm v9.0 lockfile format wasn't compatible with the pnpm version Corepack was installing from Nix.

### 4. **npm ci Requires Lockfile** (exit code 1)
**Error:**
```
ERROR: failed to build: failed to solve: process "/bin/bash -ol pipefail -c npm ci"
```

**Root Cause:** After switching from pnpm, we deleted `pnpm-lock.yaml` but used `npm ci`, which requires `package-lock.json` to exist. There was no lockfile to work with.

### 5. **Composer Not Found** (exit code 127)
**Error:**
```
ERROR: failed to solve: process "/bin/bash ... composer install ..." did not complete successfully: exit code: 127
```

**Root Cause:** Specified `php83` in nixPkgs, but:
- `php83` doesn't include composer by default in the Nix overlay
- Composer wasn't in the PATH during the build

---

## Solutions Applied

### Step 1: Removed pnpm Entirely
**Files Modified:**
- `package.json` — removed `packageManager: "pnpm@9.1.0"`
- Deleted `pnpm-lock.yaml`

**Why:** npm ships with Node.js, eliminating extra Nix package conflicts. Standard, no special tooling needed.

### Step 2: Switched to npm for Dependency Management
**Files Modified:**
- `nixpacks.toml` — changed install phase to `npm install`
- `Dockerfile` — changed Node build stage to use `npm install`

**Result:** npm generates `package-lock.json` automatically on first install.

### Step 3: Fixed Nixpacks PHP Package
**Files Modified:**
- `nixpacks.toml` — changed `nixPkgs = ["php83", "nodejs_22"]` → `["php", "nodejs_22"]`

**Why:** Generic `php` package in nixpkgs includes composer by default. `php83` doesn't.

### Step 4: Created Fallback Dockerfile
**Files Created:**
- `Dockerfile` — multi-stage build (vendor, assets, app)
- `.dockerignore` — exclude build context bloat

**Why:** Allows deployment platform to bypass Nixpacks entirely if needed by switching builder to "Dockerfile" mode. Cleaner, more predictable builds.

---

## Current Working Configuration

### nixpacks.toml
```toml
[phases.setup]
nixPkgs = ["php", "nodejs_22"]

[phases.install]
cmds = [
  "npm install",
  "composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader"
]

[phases.build]
cmds = [
  "npm run build",
  "php artisan config:cache",
  "php artisan route:cache",
  "php artisan view:cache"
]

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
```

### package.json
- Removed `packageManager` field
- Using `npm` exclusively (no pnpm)

### Dockerfile (Fallback Option)
- Three-stage build: Composer deps → Node assets → PHP runtime
- PHP 8.3 Apache with MySQL support
- Production-ready configuration

---

## Deployment Checklist

### If Using Nixpacks (Current Default):
1. ✅ `nixpacks.toml` exists in root with correct `php` package name
2. ✅ `npm install` and `composer install` in install phase
3. ✅ No `pnpm-lock.yaml` in repo
4. ✅ All env secrets (APP_KEY, DB_PASSWORD, etc.) set as **runtime** env vars, not build args
5. ✅ Commit and push all changes before deploying

### If Switching to Dockerfile Mode (Recommended):
1. Go to deployment platform settings
2. Change builder: **Nixpacks** → **Dockerfile**
3. Dockerfile path: `Dockerfile` (root)
4. Deploy
5. Set runtime env vars in platform dashboard

---

## Future Deploys: Quick Troubleshooting

| Error | Likely Cause | Fix |
|-------|--------------|-----|
| `undefined variable 'X'` | Invalid nixPkgs name | Use generic names: `php`, `nodejs_22`, not `php83`, `composer` |
| `exit code 1` during npm/composer | Missing lockfile | Use `npm install` (generates package-lock.json), not `npm ci` |
| `exit code 127` (command not found) | Package not in PATH | Check nixPkgs array; use `php` not `php83` for composer |
| LICENSE collision | Multiple pkg managers in Nix | Use only npm (not pnpm); use only one Node manager |
| Build takes forever, then fails | Nix downloading/compiling | Switch to Dockerfile mode instead |

---

## Files Modified

1. **nixpacks.toml** — ✅ Created & Fixed
   - Removed `pnpm` conflicts
   - Fixed PHP package name to include composer
   - Added npm install, removed pnpm commands

2. **package.json** — ✅ Modified
   - Removed `packageManager` field
   - Kept npm scripts (`build`, `dev`)

3. **Dockerfile** — ✅ Created (Fallback)
   - Multi-stage build
   - Production-ready

4. **.dockerignore** — ✅ Created
   - Reduces build context

5. **pnpm-lock.yaml** — ✅ Deleted
   - No longer needed

---

## Key Takeaways

- **Avoid pnpm in Nixpacks deployments**: npm works out-of-the-box with Node, zero conflicts
- **Always set secrets as runtime env vars**: Never bake into build
- **Dockerfile is your escape hatch**: If Nixpacks fails, switch to Dockerfile builder
- **Test locally first**: Run `npm install && npm run build && composer install` locally to catch dependency issues before deploying

