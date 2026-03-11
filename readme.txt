=== Affilicart Light ===
Contributors: michaelmacfadden
Author: Michael Macfadden
Author URI: https://mmacfadden.com
Plugin URI: https://affilicartpro.com
Tags: amazon, affiliate, shopping cart, ecommerce, monetization
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: affilicart
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

* **Amazon Price Sync** - Real-time pricing via Amazon Product Advertising API
* **Product Categories** - Organize by topic
* **Single Product Pages** - Dedicated product detail pages with lightbox image viewer
* **Image Lightbox** - Fullscreen image viewer with high-resolution images
* **Custom Branding** - Personalized accent colors and cart display options
* **Priority Support** - Faster response times

== External Dependencies ==

This plugin uses the following external services and CDN resources:
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
- Affiliate cate must be registered for Product Advertising API
- Valid AWS credentials (Access Key ID and Secret)
- API subscription confirmed with Amazon

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
- **Free:** Basic affiliate store with manual pricing/no API
- **Pro:** Real-time Amazon price syncing and advanced features

Free version is fully functional independently.

= Do you phone home? =

No. This plugin does not send any data to external servers except:
- jsDelivr for CSS/JS/Icons (standard CDN use)
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

== Screenshots ==

1. Product Management Interface
2. Add/Edit Product Screen
3. Shopping Cart Widget
4. Settings & Configuration Page
5. Product Grid Display

== Changelog ==

= 1.3 =
* Added complete plugin header with author and plugin URIs
* Implemented internationalization (i18n) with translation support
* Added API-gated pricing system (Pro feature)
* Integrated Amazon Product Advertising API support
* Added Price Sync tab in settings (Pro only)
* Improved code documentation and inline comments
* Enhanced WordPress.org compliance
* Added version numbers to CDN resource enqueues
* Implemented filter for managing CDN usage
* Better security with proper nonces and escaping

= 1.2 =
* Added product categories taxonomy
* Improved admin interface
* Better error handling
* Performance optimizations

= 1.1 =
* Initial public release
* Core affiliate product management
* Shopping cart functionality

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

= jsDelivr CDN =
Bootstrap CSS, JS, and Icons are served via jsDelivr's CDN. See: https://www.jsdelivr.com

Both services have no data sharing with this plugin's developer.

== License ==

Affilicart Light is licensed under GPL v2 or later.

See: https://www.gnu.org/licenses/gpl-2.0.html

== Credits ==

**Developer:** Michael Macfadden

**Built with:**
- Bootstrap (MIT License) - https://getbootstrap.com
- Bootstrap Icons (MIT License) - https://icons.getbootstrap.com

== Contribution Guidelines ==

Want to contribute? Visit: https://affilicartpro.com

== Frequently Asked Questions ==

= Do I need an Amazon Associates account? =

Yes, you need an active Amazon Associates account to use this plugin. Sign up for free at [amazon.com/associates](https://amazon.com/associates).

= How do I add my Amazon Associates ID? =

Go to **Products > Settings** and enter your Amazon Associates tracking ID (Associate Tag) in the "Amazon Associate ID" field.

= How do I display products on my site? =

Use the shortcodes provided:
* `[affilicart_grid]` for a grid layout
* `[affilicart_button]` for button layout
* `[affilicart_link id="123"]` for a text link with hover card

= Is there a product limit? =

No! Affilicart Light allows unlimited products. Add as many as you want.

= How do affiliate links work? =

When you add your Amazon Associates ID in the settings, all product links will include your tracking ID. When visitors click the links and purchase on Amazon, you earn a commission.

= Will my affiliate links work? =

Yes, as long as you have a valid Amazon Associates account and your tracking ID is entered in the plugin settings. Make sure your Associates account status is "Active".

= Can I customize the look and feel? =

In the free version, you can customize basic settings. With Affilicart Pro, you unlock custom accent colors to match your brand perfectly.

= What is a shopping cart? =

The shopping cart allows customers to add products they're interested in and review them before clicking through to Amazon. When they're ready, they can proceed to Amazon to complete their purchase.

= Can I organize products into categories? =

Yes! Upgrade to Affilicart Pro to unlock product categories, which let you organize your store by topic and help customers browse more easily.

= What is the single product page feature in Pro? =

Single product pages give each of your products a dedicated page with full details, larger images, and a prominent "Buy Now" button. This helps increase conversions.

= How do I get Affilicart Pro? =

Visit the Affilicart website or upgrade directly from the plugin settings page. Pro is installed as a separate plugin that works alongside the free version.

== Screenshots ==

1. Product Management Dashboard
2. Add/Edit Product Screen
3. Product Grid Display
4. Shopping Cart Widget
5. Settings Page
6. Product Categories (Pro)

== Changelog ==

= 1.3 =
* Changed to unlimited free tier model
* Made product categories a Pro feature
* Made custom accent color a Pro feature
* Made cart display options a Pro feature
* Made single product pages a Pro feature
* Made image lightbox a Pro feature
* Removed product limit enforcement
* Improved settings UI with premium feature callouts
* Enhanced Pro value proposition

= 1.2 =
* Added product categories feature
* Improved product admin interface
* Better error handling
* Performance optimizations

= 1.1 =
* Initial public release
* Basic product management
* Shopping cart functionality
* Amazon affiliate integration

== Support ==

For support, documentation, and more information, visit [affilicartwp.com](https://affilicartwp.com)

== License ==

This plugin is licensed under the GPL v2 or later. See the LICENSE file for details.

== Credits ==

Developed by Michael Macfadden

== Third Party Services ==

This plugin relies on Amazon.com for:
* Product affiliate links
* Commission tracking through your Associates account

By using this plugin, you agree to Amazon's Associates Program Operating Agreement.

== Disclaimer ==

This plugin is provided "AS IS" without warranty. Users are responsible for:
* Maintaining an active Amazon Associates account
* Complying with Amazon's Associates Program policies
* Ensuring proper disclosure of affiliate relationships
* Following FTC guidelines for affiliate marketing

The developer is not responsible for lost commissions or affiliate account suspensions due to policy violations.
