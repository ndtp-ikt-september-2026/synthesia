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
$_['text_cache_purged']             = 'Success: Storage modification and system caches have been purged.';
$_['text_blackout_success']         = 'Success: TOTAL BLACKOUT executed. All extensions deactivated and caches flushed.';
$_['text_no_logs']                  = 'No crash entries recorded. System is operating normally.';
$_['text_no_modifications']         = 'No modifications found.';
$_['text_no_events']                = 'No events found.';
$_['text_copy_url']                 = 'Copy URL';
$_['text_copied']                   = 'Copied!';

// Buttons
$_['button_kill_all']               = 'EMERGENCY STOP-KRAN (TOTAL BLACKOUT)';
$_['button_purge_cache']            = 'Purge Caches Now';
$_['button_clear_log']              = 'Clear Crash Log';
$_['button_refresh']                = 'Refresh Console';
$_['button_generate_token']         = 'Generate New Token';

// Tabs & Panels
$_['tab_dashboard']                 = 'Circuit Overview';
$_['tab_modifications']             = 'Modifications (OCMOD)';
$_['tab_events']                    = 'System Events';
$_['tab_logs']                      = 'Crash Diagnostics';
$_['tab_settings']                  = 'Emergency Bypass Configuration';

// Columns
$_['column_name']                   = 'Modification / Event Name';
$_['column_code']                   = 'Identifier Code';
$_['column_author']                 = 'Author';
$_['column_version']                = 'Version';
$_['column_trigger']                = 'Trigger Route';
$_['column_action']                 = 'Handler Action';
$_['column_status']                 = 'Status';
$_['column_action_toggle']          = 'Toggle Action';

// Entry
$_['entry_status']                  = 'Circuit Breaker Watchdog Status';
$_['entry_secret_token']            = 'Emergency Secret Bypass Token';
$_['entry_bypass_url_admin']        = 'Direct Admin Bypass URL';
$_['entry_bypass_url_catalog']      = 'Direct Storefront Bypass URL';

// Help
$_['help_secret_token']             = 'Secret token required to authenticate emergency total blackout triggers via HTTP GET query.';
$_['help_bypass_url']               = 'Direct bypass link for emergency rollback without requiring administrative session or login.';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify SoundNet Stop-Kran!';
$_['error_token_empty']             = 'Warning: Emergency secret token cannot be empty!';
$_['error_invalid_request']         = 'Warning: Invalid AJAX request payload.';
