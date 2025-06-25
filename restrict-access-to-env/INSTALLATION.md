# Quick Installation Guide

## Step 1: Plugin is Already Installed
The plugin is already set up in your child theme at:
```
wp-content/themes/astra-child-theme/plugins/restrict-access-to-env/
```

## Step 2: Plugin is Activated
The plugin is automatically loaded via your `functions.php` file.

## Step 3: How to Use

### For Test Environments:
1. **Automatic Detection**: If your URL contains "test" or "testt", the plugin activates automatically
2. **Default Password**: `test123`
3. **Change Password**: Go to **Settings > Restrict Access** in WordPress admin

### For Production:
- The plugin does nothing on production sites
- No performance impact
- No interference with normal operations

## Step 4: Test the Plugin

### To Test on Current Site:
1. If your current URL doesn't contain "test", the plugin won't activate
2. You can temporarily modify the detection logic in the plugin file if needed for testing

### Admin Access:
- Go to **Settings > Restrict Access** to see plugin status
- Change the password from the default `test123`
- View environment detection status

## Environment Examples

**Plugin Will Activate On:**
- `test.yoursite.com`
- `testt.yoursite.com` 
- `yoursite-test.com`
- `staging-test.yoursite.com`

**Plugin Will NOT Activate On:**
- `yoursite.com`
- `www.yoursite.com`
- `shop.yoursite.com`
- `staging.yoursite.com` (no "test" in URL)

## Features Summary

✅ **Automatic environment detection**  
✅ **Password protection for entire site**  
✅ **SEO blocking (noindex, nofollow)**  
✅ **Beautiful responsive login form**  
✅ **Admin settings page**  
✅ **Session-based authentication**  
✅ **Zero impact on production sites**  

## Quick Settings

1. **WordPress Admin** → **Settings** → **Restrict Access**
2. Change default password from `test123`
3. Save settings

That's it! The plugin is ready to use. 