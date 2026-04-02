=== Affilicart Light ===
Contributors: mmacfadden
Author: Michael Macfadden
Author URI: https://mmacfadden.com
Plugin URI: https://affilicartpro.com
Tags: amazon, affiliate, shopping cart, ecommerce, monetization
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: affilicart-light
Domain Path: /languages

A simple Amazon Affiliate product manager with shopping cart. Add unlimited products and build your affiliate store with ease.

== Description ==

Affilicart Light is a lightweight WordPress plugin that makes it easy to create and manage an Amazon affiliate product store on your website. Perfect for bloggers, content creators, and small businesses who want to monetize their content with Amazon affiliate links.

**Important Note:** Pricing transparency is a core feature. By default, product prices are NOT displayed unless you enable the Amazon Product Advertising API (available with Affilicart Pro). This ensures compliance with Amazon's affiliate program requirements and prevents outdated pricing information from confusing customers.

= Free Version Features =

* **Unlimited Products** - Add as many products as you want
* **Full Shopping Cart** - Customers add products to cart before purchasing
* **Product Images** - High-quality image display
* **Amazon Affiliate Integration** - Automatic tracking ID insertion
* **Responsive Design** - Mobile, tablet, and desktop compatible
* **Grid & Shortcode Support** - Multiple display options

= Pro Version Adds =

* **Product Categories** - Organize by topic
* **Single Product Pages** - Dedicated product detail pages with lightbox image viewer
* **Image Lightbox** - Fullscreen image viewer with high-resolution images
* **Custom Branding** - Personalized accent colors and cart display options
* **Priority Support** - Faster response times
* **Amazon Price Sync (Beta)** - Real-time pricing via Amazon Product Advertising API

== External Dependencies ==

This plugin uses the following external services:

= Amazon Services =
**Purpose:** Product affiliate link redirection and commission tracking
**Service:** Amazon.com (https://amazon.com)
**When data is sent:** When users click product links
**Type:** Your Amazon Associates ID (required for affiliate tracking)
**Compliance:** You must comply with Amazon Associates Operating Agreement

User browsing data is NOT tracked or collected by Affilicart. UI components use WordPress built-in styles and dashicons.

== Installation ==

1. Download and extract the plugin
2. Upload to `/wp-content/plugins/`
3. Activate from WordPress admin
4. Go to Products > Settings
5. Enter your Amazon Associates tracking ID
6. Add products and use shortcodes

== Pro Only Features (API Pricing) ==

### Amazon Product Advertising API (Pro Feature)

Affilicart Pro can sync real-time prices from Amazon:

**Requirements:**
- Affiliate account must be registered for Product Advertising API
- Valid AWS credentials (Access Key ID and Secret)
- API subscription confirmed with Amazon
account
**What happens:**
- When enabled: Product prices update automatically every 24 hours
- When disabled: Prices are NOT displayed (only "View on Amazon" link shown)
- This ensures pricing accuracy and Amazon compliance

This is a Pro-only feature to maintain API quota limits.

== Frequently Asked Questions ==

= Do I need an Amazon Associates account? =

Yes, but you can sign up free at https://amazon.com/associates

= How do I enable price syncing? =

Install Affilicart Pro and configure your Amazon Product Advertising API credentials in the Price Sync settings tab.

= Why aren't prices showing? =

Prices only display if:
1. You have Affilicart Pro enabled
2. Amazon Price Sync is enabled
3. You've configured valid API credentials
4. The API has been able to fetch price data

Otherwise, users can still click "View on Amazon" to see current pricing.

= How do I display products? =

Use shortcodes like:
- `[affilicart_grid]` - Display all products in grid
- `[affilicart_grid id="123,456"]` - Display specific products
- Hide price: `[affilicart_grid show_price="no"]`

= What happens to user data? =

Affilicart does NOT:
- Track users
- Collect personal information
- Store browsing behavior
- Send data to third parties

Shopping carts are stored in browser localStorage only.

= How is the freemium model used? =

Affilicart follows a clear freemium model:
- **Free:** Basic affiliate store with no pricing/no API
- **Pro:** Real-time Amazon price syncing and advanced features

Free version is fully functional independently.

= Do you phone home? =

No. This plugin does not send any data to external servers except:
- Amazon when users click affiliate links (required for tracking)

= Is this GDPR compliant? =

Yes. Affilicart doesn't collect personal data and doesn't use cookies or tracking.

= Getting Started =

1. Install and activate the plugin
2. Go to **Products** in the admin menu
3. Click **Settings** to add your Amazon Associates ID
4. Click **Add New Product** to start adding products
5. Use the shortcodes to display your products on pages or posts

= Shortcodes =

Use these shortcodes to display your products on the frontend:

* `[affilicart_grid]` - Display all products in a grid layout
* `[affilicart_button]` - Display products as buttons
* `[affilicart_link id="123"]` - Display a product link with hover card

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* A valid Amazon Associates account

= Amazon Associates Requirement =

== Changelog ==

= 1.0 =
* Initial public release
* Unlimited Amazon affiliate products
* Full shopping cart functionality
* Mobile responsive design
* Amazon Associates integration
* Internationalization support

== Support & Docs ==

Website: https://affilicartpro.com
Email support available through website

== Code Standards ==

Affilicart follows WordPress best practices:

✓ GPL v2 license compliant
✓ All inputs sanitized
✓ All outputs escaped
✓ Security nonces on forms
✓ Capability checks on admin functions
✓ Internationalization ready
✓ No user tracking
✓ No unrequested data collection
✓ Clear freemium model
✓ Transparent affiliate disclosure

== Security & Privacy ==

= Data Collection =
- NO user tracking
- NO analytics
- NO cookies set by plugin
- Shopping carts stored in browser only (localStorage)
- Product data stored in WordPress database only

= Data Sharing =
- Only Amazon Associates ID shared with Amazon (for affiliate links)
- No data shared with plugin developer
- No third-party tools or trackers

= Website Privacy =
For privacy policy regarding Amazon affiliate relationships:
See: https://amazon.com/associates (Associates Program terms)

== Third Party Notices ==

= Amazon Associates =
This plugin integrates with Amazon Associates Program. You must have an active Associates account and comply with their Operating Agreement.

== License ==

Affilicart Light is licensed under GPL v2 or later.

See: https://www.gnu.org/licenses/gpl-2.0.html

== Credits ==

**Developer:** Michael Macfadden

**Built with:**
- WordPress (GPL) - https://wordpress.org
- Vanilla JavaScript
- Custom CSS

== Contribution Guidelines ==

Want to contribute? Visit: https://affilicartpro.com
