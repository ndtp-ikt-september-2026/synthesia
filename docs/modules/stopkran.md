# SoundNet Stop-Kran Module Specification

`soundnet_stopkran` is an enterprise resilience, early watchdog, and circuit breaker subsystem designed specifically for OpenCart 3.0.3.x. It provides zero-downtime fault isolation, preventing catastrophic storewide outages caused by malformed or buggy OCMOD modifications.

---

## 1. The OpenCart 3 Modification Vulnerability

In OpenCart 3, the extension system (OCMOD) dynamically compiles modifications into shadow copies of core PHP files inside `system/storage/modification/`:

```
[ Developer XML ] ──▶ [ OpenCart Modification Engine ] ──▶ [ system/storage/modification/ ]
                                                                      │
                                                       (Syntax Error / Fatal Exception)
                                                                      │
                                                                      ▼
                                                            💥 Complete HTTP 500
                                                        Storefront & Admin DIE!
```

### The Fatal Flaw:
If an installed modification contains a syntax error, an undefined function call, or an uncaught exception in a shared controller (e.g. `catalog/controller/product/product.php` or `system/library/cart/cart.php`), **the entire application terminates immediately**.
- The storefront shows a blank white screen or `HTTP 500 Internal Server Error`.
- The admin panel **also crashes**, because it relies on the same shared PHP runtime and compiled storage.
- Store owners and administrators are completely locked out, unable to navigate to **Extensions -> Modifications** to disable the offending extension. Recovery historically required manual FTP access and deleting modification cache folders.

---

## 2. Stop-Kran Architectural Solution

Stop-Kran introduces a multi-tier resilience architecture:

```
                                [ HTTP / CLI Request ]
                                          │
                                          ▼
                                [ system/startup.php ]
                                          │
                                ┌─────────┴─────────┐
                                │ Stop-Kran Watchdog│  <-- Injected BEFORE autoloader
                                └─────────┬─────────┘
                                          │
                                          ▼
                                [ Normal Execution ]
                                          │
                                  ┌───────┴───────┐
                              (Success)        (Crash)
                                  │               │
                                  ▼               ▼
                           [ Response 200 ]  [ Watchdog Shutdown Interceptor ]
                                                  │
                                                  ▼
                                     [ Call Stack AST Inspection ]
                                      Identifies culprit modification
                                                  │
                                                  ▼
                                      [ Circuit Breaker Trigger ]
                                      • Increments failure counter
                                      • If failures >= threshold:
                                        Deactivates in oc_modification
                                      • Purges system/storage/modification/
                                                  │
                                                  ▼
                                     [ Graceful Fallback Render ]
                                     HTTP 200 with recovery banner
```

---

## 3. Core Subsystems

### 3.1 Early Bootstrap Guard (`system/startup.php`)
Stop-Kran hooks `system/startup.php` before the autoloader initializes:
```php
if (file_exists(DIR_SYSTEM . 'library/stopkran/watchdog.php')) {
    require_once(DIR_SYSTEM . 'library/stopkran/watchdog.php');
    \SoundNet\StopKran\Watchdog::init();
}
```
This guarantees that even if subsequent framework classes or autoloader mappings are damaged, Stop-Kran's interceptor is already armed in memory.

### 3.2 Error & Exception Interceptors
The watchdog registers three primary PHP handlers:
1. `register_shutdown_function([Watchdog::class, 'handleShutdown'])`: Captures fatal errors (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`).
2. `set_error_handler([Watchdog::class, 'handleError'])`: Traps runtime warnings and notices.
3. `set_exception_handler([Watchdog::class, 'handleException'])`: Traps uncaught `Throwable` instances.

### 3.3 Culprit Modification Identification
When a fatal event occurs, the watchdog inspects `error_get_last()` and the debug backtrace:
1. Checks whether the error originated inside `system/storage/modification/`.
2. Inspects modification metadata tables and file mappings to determine which OCMOD extension modified the crashing line.
3. Logs the failure to `oc_stopkran_crashes` with full stack trace, memory usage, request URI, and timestamp.

### 3.4 Automated Circuit Breaker & Safe Mode
1. **Failure Threshold:** Defaults to 3 consecutive crashes within a 60-second window.
2. **Auto-Deactivation:** Sets `status = 0` in `oc_modification` for the culprit record.
3. **Cache Invalidation:** Immediately clears `system/storage/modification/*`.
4. **Safe Mode:** An emergency operational mode that bypasses all compiled modifications, allowing administrators to access the dashboard even during complex multi-extension conflicts.

---

## 4. Headless Emergency CLI (`cli/stopkran.php`)

When web access is unavailable, administrators can manage and recover the system directly via CLI:

```bash
# Check watchdog health and modification status
php cli/stopkran.php status

# Clear modification cache and trigger recompile
php cli/stopkran.php clear-cache

# Turn on storewide Safe Mode (bypasses all OCMOD modifications)
php cli/stopkran.php safe-mode on

# Turn off Safe Mode
php cli/stopkran.php safe-mode off

# Isolate / disable a specific modification
php cli/stopkran.php disable --code=soundnet_storefront

# Re-enable a modification
php cli/stopkran.php enable --code=soundnet_storefront

# Run circuit breaker diagnostic simulation suite
php cli/stopkran.php test
```

---

## 5. Admin Control Panel

Located in **Admin -> Extensions -> Extensions -> Modules -> SoundNet Stop-Kran**:
- **System Metrics:** Current PHP memory consumption, watchdog active status, active modification count.
- **Circuit Breaker Settings:** Safety crash threshold (1–10), reset window (seconds), emergency email alert.
- **Crash Log Journal:** Interactive table of intercepted exceptions, complete with stack traces, file line numbers, and culprit modification tags.
- **One-Click Actions:** Safe mode toggle, cache purge, reset error counters.

---

## 6. Distribution & Packaging

- **Package Source:** [`packages/soundnet_stopkran/`](../../packages/soundnet_stopkran/)
- **Compiled Archive:** `dist/soundnet_stopkran.ocmod.zip`
- **Compiler Command:** `php cli/package_modules.php soundnet_stopkran`
