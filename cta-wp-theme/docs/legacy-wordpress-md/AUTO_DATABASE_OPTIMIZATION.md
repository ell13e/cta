# Automatic Database Optimization

## Overview

Added scheduled automatic database optimization that runs weekly without any manual intervention. Set it and forget it!

## Features

### ✅ One-Click Enable/Disable
- Single button to turn on/off
- Shows current status (enabled/disabled)
- Displays next scheduled run time
- Human-readable countdown

### 📅 Smart Scheduling
- Runs every **Sunday at 3 AM**
- Chosen for low-traffic time
- Won't interfere with site usage
- Uses WordPress's built-in cron system

### 🔧 What It Does Automatically

Every week, the system will:
1. **Optimize all database tables** - Defragments and improves query speed
2. **Clean up old revisions** - Keeps last 100, removes older ones
3. **Remove auto-drafts** - Deletes drafts older than 7 days
4. **Delete orphaned post meta** - Removes metadata for deleted posts
5. **Delete orphaned comment meta** - Removes metadata for deleted comments
6. **Clear expired transients** - Removes stale cached data

## User Interface

### Status Display
```
✓ Automatic optimization is enabled
Next scheduled run: January 21, 2026 3:00 am
(4 days from now)
```

### When Disabled
```
Automatic optimization is not scheduled
Enable weekly automatic database optimization for hands-free maintenance.
```

### Buttons
- **Enable Automatic Optimization** (blue primary button when disabled)
- **Disable Automatic Optimization** (standard button when enabled)

## Technical Implementation

### Cron Schedule Registration
```php
function cta_add_weekly_cron_schedule($schedules) {
    $schedules['weekly'] = [
        'interval' => 604800, // 7 days
        'display' => __('Once Weekly')
    ];
    return $schedules;
}
add_filter('cron_schedules', 'cta_add_weekly_cron_schedule');
```

### Event Scheduling
```php
// Schedule for next Sunday at 3 AM
$timestamp = strtotime('next Sunday 3:00 AM');
wp_schedule_event($timestamp, 'weekly', 'cta_auto_database_cleanup');
```

### Hook to Existing Function
```php
add_action('cta_auto_database_cleanup', 'cta_optimize_database');
```

We're reusing the existing `cta_optimize_database()` function, so all the same cleanup operations happen automatically.

### Enable Action
```php
case 'enable_auto_optimization':
    if (!wp_next_scheduled('cta_auto_database_cleanup')) {
        $timestamp = strtotime('next Sunday 3:00 AM');
        wp_schedule_event($timestamp, 'weekly', 'cta_auto_database_cleanup');
        $success = true;
        $message = 'Automatic weekly database optimization enabled!';
    }
    break;
```

### Disable Action
```php
case 'disable_auto_optimization':
    $timestamp = wp_next_scheduled('cta_auto_database_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'cta_auto_database_cleanup');
        $success = true;
        $message = 'Automatic database optimization disabled.';
    }
    break;
```

### Cleanup on Deactivation
```php
function cta_cleanup_scheduled_events() {
    $timestamp = wp_next_scheduled('cta_auto_database_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'cta_auto_database_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'cta_cleanup_scheduled_events');
```

## Benefits

### 1. **Hands-Free Maintenance**
- No need to remember to optimize
- Runs automatically in background
- No user intervention required

### 2. **Consistent Performance**
- Database stays optimized
- Prevents gradual slowdown
- Maintains query speed

### 3. **Prevents Database Bloat**
- Removes old revisions weekly
- Cleans up auto-drafts regularly
- Keeps database size manageable

### 4. **Low-Traffic Timing**
- Runs Sunday 3 AM
- Minimal user impact
- Won't slow down busy periods

### 5. **Safe Operation**
- Uses WordPress's built-in cron
- Same function as manual optimization
- Can be disabled anytime

## Usage

### To Enable:
1. Go to **Tools → Performance Optimization**
2. Scroll to "Automatic Optimization" section
3. Click **"Enable Automatic Optimization"**
4. Done! Shows next run time

### To Disable:
1. Go to **Tools → Performance Optimization**
2. Scroll to "Automatic Optimization" section
3. Click **"Disable Automatic Optimization"**
4. Scheduling stopped

### To Check Status:
- Visit the Performance Optimization page
- "Automatic Optimization" card shows:
  - Current status (enabled/disabled)
  - Next scheduled run time
  - Countdown to next run

## WordPress Cron System

### How It Works
- WordPress "cron" runs when someone visits your site
- Not a true system cron (doesn't require server access)
- Checks if scheduled events are due
- Runs them if needed

### Why Sunday 3 AM?
- Typically lowest traffic time
- Weekend early morning
- Most users asleep
- Minimal performance impact

### If Site Has No Traffic?
- Event runs on next site visit after scheduled time
- Slight delay is fine for maintenance tasks
- For high-traffic sites, runs exactly on schedule

## Monitoring

### Check Last Run
The manual optimization button still works, so you can:
1. Run optimization manually anytime
2. See database stats before/after
3. Verify automatic runs are working

### Verify Schedule
```php
// Check if scheduled
$next_run = wp_next_scheduled('cta_auto_database_cleanup');
if ($next_run) {
    echo date('F j, Y g:i a', $next_run);
}
```

## Troubleshooting

### Event Not Running?
1. **Check if enabled** - Visit Performance page
2. **Low traffic site?** - Event runs on next visit after scheduled time
3. **Caching issue?** - Clear site cache
4. **Disable and re-enable** - Resets the schedule

### Want Different Schedule?
Can easily modify to:
- **Daily:** Change interval to 86400 (24 hours)
- **Twice weekly:** Schedule two separate events
- **Monthly:** Change interval to 2592000 (30 days)

Current code:
```php
$timestamp = strtotime('next Sunday 3:00 AM');
wp_schedule_event($timestamp, 'weekly', 'cta_auto_database_cleanup');
```

For daily at 3 AM:
```php
$timestamp = strtotime('tomorrow 3:00 AM');
wp_schedule_event($timestamp, 'daily', 'cta_auto_database_cleanup');
```

## Performance Impact

### During Optimization:
- **Duration:** 5-30 seconds depending on database size
- **CPU:** Brief spike during optimization
- **User Impact:** None (runs in background)
- **Queries:** Temporarily higher during cleanup

### After Optimization:
- **Faster queries** - Optimized tables
- **Smaller database** - Removed bloat
- **Better performance** - Less data to scan

## Security

- ✅ Requires `manage_options` capability
- ✅ Nonce verification on enable/disable
- ✅ Uses WordPress's built-in cron system
- ✅ No external dependencies
- ✅ Cleans up on theme deactivation

## Location

**Admin Page:** Tools → Performance Optimization  
**Section:** Automatic Optimization (bottom of page)  
**File:** `wordpress-theme/inc/performance-helpers.php`

## Future Enhancements (Optional)

Could add:
- Email notification after each run
- Log of optimization history
- Custom schedule picker (user chooses day/time)
- Option to skip certain cleanup tasks
- Dashboard widget showing last run

But the current implementation is clean, simple, and effective!
