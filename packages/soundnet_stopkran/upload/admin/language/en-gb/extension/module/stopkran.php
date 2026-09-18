<?php
// Heading
$_['heading_title']                 = 'SoundNet Stop-Kran &mdash; Emergency Circuit Breaker';

// Text
$_['text_extension']                = 'Extensions';
$_['text_success']                  = 'Success: You have updated SoundNet Stop-Kran settings!';
$_['text_edit']                     = 'Circuit Breaker Console';
$_['text_enabled']                  = 'Enabled';
$_['text_disabled']                 = 'Disabled';
$_['text_confirm_blackout']         = 'CRITICAL WARNING: Are you sure you want to engage EMERGENCY STOP-KRAN (TOTAL BLACKOUT)? All modifications, custom events, and extension statuses will be immediately deactivated, and all caches flushed. The store will revert to native core.';
$_['text_confirm_toggle']           = 'Are you sure you want to change the status of this item?';
$_['text_log_cleared']              = 'Success: Crash diagnostic log has been cleared.';
$_['text_test_log_cleared']         = 'Success: Module testing log has been cleared.';
$_['text_cache_purged']             = 'Success: Storage modification and system caches have been purged.';
$_['text_blackout_success']         = 'Success: TOTAL BLACKOUT executed. All extensions deactivated and caches flushed.';
$_['text_no_logs']                  = 'No crash entries recorded. System is operating normally.';
$_['text_no_test_logs']             = 'No testing entries recorded. Run a module diagnostic test.';
$_['text_no_modifications']         = 'No modifications found.';
$_['text_no_events']                = 'No events found.';
$_['text_no_test_history']          = 'No module test history recorded yet.';
$_['text_copy_url']                 = 'Copy URL';
$_['text_copied']                   = 'Copied!';
$_['text_select_module']            = '--- Select module to test ---';
$_['text_test_passed']              = 'Passed';
$_['text_test_failed']              = 'Failed';
$_['text_test_warning']             = 'Warning';
$_['text_testing_in_progress']      = 'Testing in progress...';
$_['text_auto_test_on']             = 'Auto-testing enabled';
$_['text_auto_test_desc']           = 'Stop-Kran automatically runs syntax, class, and template checks whenever a module is installed, added, or updated.';

// Breaker Callout Panel
$_['text_breaker_heading']          = 'Emergency Circuit Breaker (Total Blackout)';
$_['text_breaker_status_armed']     = 'Circuit Breaker: Armed & Ready';
$_['text_breaker_subtext']          = 'Direct mechanical fallback. Deactivates all modifications, non-core events, and module statuses in MySQL, then unlinks all storage caches. Instantly reverts OpenCart to an unmodified core state.';

// Telemetry & Stat Tiles
$_['text_stat_modifications']       = 'Modifications (OCMOD)';
$_['text_stat_mods_desc']           = 'Active / Total in system';
$_['text_stat_events']              = 'Custom Events';
$_['text_stat_events_desc']         = 'Non-core registered';
$_['text_stat_cache_size']          = 'Modification Cache';
$_['text_stat_system_cache']        = 'System Cache';
$_['text_stat_maintenance']         = 'Cache Maintenance';
$_['text_stat_purge_desc']          = 'Flush modification and storage cache';

// Buttons
$_['button_save']                   = 'Save';
$_['button_cancel']                 = 'Cancel';
$_['button_kill_all']               = 'EMERGENCY STOP-KRAN (TOTAL BLACKOUT)';
$_['button_purge_cache']            = 'Purge Caches Now';
$_['button_clear_log']              = 'Clear Crash Log';
$_['button_clear_test_log']         = 'Clear Test Log';
$_['button_refresh']                = 'Refresh';
$_['button_generate_token']         = 'Generate Token';
$_['button_run_test']               = 'Test Module';
$_['button_test_all']               = 'Test All Modules';

// Tabs
$_['tab_settings']                  = 'Settings';
$_['tab_testing']                   = 'Module Testing';
$_['tab_modifications']             = 'Modifications (OCMOD)';
$_['tab_events']                    = 'System Events';
$_['tab_logs']                      = 'Crash Diagnostics';
$_['tab_test_logs']                 = 'Testing Logs';

// Columns
$_['column_name']                   = 'Name';
$_['column_code']                   = 'Identifier Code';
$_['column_author']                 = 'Author';
$_['column_version']                = 'Version';
$_['column_trigger']                = 'Trigger Route';
$_['column_action']                 = 'Handler Action';
$_['column_status']                 = 'Status';
$_['column_action_toggle']          = 'Action';
$_['column_test_module']            = 'Module';
$_['column_test_trigger']           = 'Trigger';
$_['column_test_date']              = 'Timestamp';
$_['column_test_result']            = 'Result';
$_['column_test_files']             = 'Files';
$_['column_test_time']              = 'Time (ms)';
$_['column_test_details']           = 'Diagnostics';

// Entry
$_['entry_status']                  = 'Circuit Breaker Watchdog Status';
$_['entry_auto_test']               = 'Auto-test on Add/Update';
$_['entry_secret_token']            = 'Emergency Secret Bypass Token';
$_['entry_bypass_url_admin']        = 'Direct Admin Bypass URL';
$_['entry_bypass_url_catalog']      = 'Direct Storefront Bypass URL';

// Help
$_['help_status']                   = 'Enables the low-level crash interceptor and automated module isolation.';
$_['help_auto_test']                = 'Automatically tests PHP syntax, classes, and templates when any module is added or updated.';
$_['help_secret_token']             = 'Secret token required to authenticate emergency total blackout triggers via HTTP GET query.';
$_['help_bypass_url']               = 'Direct bypass link for emergency rollback without requiring administrative session or login.';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify SoundNet Stop-Kran!';
$_['error_token_empty']             = 'Warning: Emergency secret token cannot be empty!';
$_['error_invalid_request']         = 'Warning: Invalid AJAX request payload.';
$_['error_module_empty']            = 'Warning: Please select a module to test!';
