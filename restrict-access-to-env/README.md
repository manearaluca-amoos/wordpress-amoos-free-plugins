# Restrict Access to Environment Plugin

**Author:** Raluca Manea  
**Version:** 1.0.0  
**Description:** Restricts access to test environments with password protection and blocks SEO indexing.

## Overview

This plugin automatically detects test environments (when the URL contains "test" or "testt") and provides:
- Password protection for the entire website
- Complete SEO blocking to prevent search engine indexing
- Session-based authentication
- Admin interface for password management

## Features

### 🔒 **Automatic Environment Detection**
- Detects test environments by checking if the domain contains "test" or "testt"
- Only activates protection on test environments
- Production sites remain unaffected

### 🛡️ **Password Protection**
- Beautiful, responsive login form
- AJAX-powered authentication
- Session-based access control
- Secure nonce verification

### 🚫 **SEO Blocking**
- Comprehensive robots meta tags
- Blocks all search engine crawlers (Google, Bing, etc.)
- Prevents social media crawling
- Disables WordPress sitemaps

### ⚙️ **Admin Interface**
- Easy password management
- Environment status dashboard
- Settings page in WordPress admin

## Installation

### Method 1: Direct Installation (Recommended)
1. Copy the entire `restrict-access-to-env` folder to your child theme's plugins directory:
   ```
   wp-content/themes/astra-child-theme/plugins/restrict-access-to-env/
   ```

2. Add this line to your child theme's `functions.php`:
   ```php
   require_once get_stylesheet_directory() . '/plugins/restrict-access-to-env/restrict-access-to-env.php';
   ```

### Method 2: WordPress Plugins Directory
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin

## Usage

### Initial Setup
1. The plugin activates automatically on test environments
2. Default password is: `test123`
3. Change the password in **Settings > Restrict Access**

### Accessing the Site
1. Visit your test site URL
2. You'll see a password protection screen
3. Enter the admin password to access the site
4. You'll remain logged in for the session

### Admin Management
1. Go to **Settings > Restrict Access** in WordPress admin
2. View environment status
3. Change the access password
4. Monitor protection status

## Configuration

### Password Settings
- Navigate to **Settings > Restrict Access**
- Update the "Admin Password" field
- Click "Save Changes"

### Default Password
The default password is `test123`. **Change this immediately** for security.

## Environment Detection

The plugin considers an environment as "test" if the domain contains:
- `test.yoursite.com` (subdomain)
- `testt.yoursite.com` (subdomain)
- Any domain containing "test" or "testt"

### Examples of Detected Test Environments:
✅ `test.example.com`  
✅ `testt.mysite.org`  
✅ `mysite-test.com`  
✅ `staging-test.example.com`  

### Examples of Production (No Protection):
❌ `example.com`  
❌ `www.mysite.org`  
❌ `shop.example.com`  

## SEO Protection Features

When active, the plugin blocks SEO through:

### Meta Tags
```html
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
```

### WordPress Robots API
- Sets `noindex: true`
- Sets `nofollow: true`
- Blocks all crawler access

### Sitemap Blocking
- Disables WordPress XML sitemaps
- Returns 404 for sitemap requests

## File Structure

```
restrict-access-to-env/
├── restrict-access-to-env.php  # Main plugin file
├── assets/
│   ├── style.css               # Login form styles
│   └── script.js               # AJAX functionality
└── README.md                   # This documentation
```

## Security Features

### Session Security
- Uses PHP sessions for authentication
- Secure nonce verification
- AJAX-based login without page reloads

### Access Control
- Blocks frontend access completely
- Allows admin, AJAX, and cron requests
- Session-based authentication state

### Password Protection
- Configurable admin password
- Secure password comparison
- No password storage in cookies/localStorage

## Troubleshooting

### Plugin Not Activating
- Ensure your domain contains "test" or "testt"
- Check the environment status in **Settings > Restrict Access**

### Can't Access Admin
- The plugin allows admin access even when active
- Direct admin URLs bypass protection
- AJAX requests are not blocked

### Forgot Password
- Access WordPress admin directly
- Go to **Settings > Restrict Access**
- Update the password field

### Not Blocking SEO
- Verify environment detection in admin
- Check browser developer tools for robots meta tags
- Ensure the plugin is properly loaded

## Support

For support or feature requests, contact: **Raluca Manea**

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Environment detection
- Password protection
- SEO blocking
- Admin interface
- AJAX login form 