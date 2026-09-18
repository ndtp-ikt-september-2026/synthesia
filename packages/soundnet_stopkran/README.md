# SoundNet Stop-Kran Emergency Circuit Breaker (`soundnet_stopkran`)

**SoundNet Stop-Kran** is a mission-critical watchdog, fault isolation, and circuit breaker subsystem for OpenCart 3.0.3.x. It prevents total store outages (white screen / HTTP 500) caused by broken OCMOD modifications, syntax errors, or runtime exceptions.

---

## Architectural Purpose

In OpenCart 3, modifications are compiled dynamically into `system/storage/modification/`. If a developer or 3rd-party extension introduces a fatal error into a core file (e.g. `catalog/controller/product/product.php`), OpenCart crashes immediately. Since the admin panel is rendered by the same PHP runtime, administrators are locked out and unable to disable the offending extension.

**Stop-Kran solves this at the kernel level:**
1. **Early Bootstrap Interceptor:** Injected into `system/startup.php` before the autoloader initializes.
2. **Fatal Error Interception:** Hooks `register_shutdown_function`, `set_error_handler`, and `set_exception_handler`.
3. **Modification Identification:** Analyzes the call stack and AST file path to pinpoint which OCMOD modification caused the failure.
4. **Automated Circuit Breaker:**
   - Increments the failure counter for the offending modification.
   - If consecutive failures cross the safety threshold (default: 3), Stop-Kran automatically disables the modification in `oc_modification`.
   - Purges `system/storage/modification/*` cache.
   - Restores store functionality within milliseconds, rendering an informative fallback message or administrative error alert instead of a fatal crash.
5. **Safe Mode:** Provides a bypass switch to boot the store with modifications temporarily suppressed.
6. **Headless Emergency CLI:** `cli/stopkran.php` allows complete inspection, repair, safe-mode toggling, and cache purging directly from the terminal without HTTP access.

---

## Package Structure

```
packages/soundnet_stopkran/
├── install.xml            # Injects early Watchdog hook into system/startup.php
├── cli/
│   └── stopkran.php       # Emergency command-line interface
├── upload/
│   ├── admin/             # Control panel controller, language, model, and Twig dashboard
│   └── system/
│       └── library/
│           └── stopkran/  # Watchdog, Recovery, and Crash Tester engines
└── README.md
```

---

## Emergency CLI Usage

```bash
# Check circuit breaker status and active crash alerts
php cli/stopkran.php status

# Emergency clear of modification cache and rebuild
php cli/stopkran.php clear-cache

# Toggle storewide Safe Mode (bypasses all OCMOD modifications)
php cli/stopkran.php safe-mode on
php cli/stopkran.php safe-mode off

# Isolate / disable a faulty modification by code
php cli/stopkran.php disable --code=faulty_extension

# Re-enable a modification after fixing bugs
php cli/stopkran.php enable --code=faulty_extension

# Run automated circuit breaker diagnostic tests
php cli/stopkran.php test
```

---

## Admin Control Panel

Navigate to **Admin Panel -> Extensions -> Extensions -> Modules -> SoundNet Stop-Kran**:
- **Live Health Status:** Watchdog state, memory headroom, modification integrity.
- **Circuit Breaker Configuration:** Failure threshold, safe-mode bypass key, notification email.
- **Crash Log Journal:** Exact stack trace, affected file, offending modification, and timestamps.
- **One-Click Recovery Actions:** Rebuild modifications, clear cache, reset circuit breaker counters.
