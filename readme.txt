=== Shopwarden - Automated WooCommerce monitoring & testing ===
Author URI: https://shopwarden.com
Plugin URI: https://shopwarden.com
Contributors: shopwarden
Tags: woocommerce, monitoring, testing
Requires at least: 5.9
Tested up to: 6.4
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable Tag: 1.0.11

Make sure your WooCommerce store is fully operational. Shopwarden automatically monitors your store's uptime, important user flows and Wordpress installation. 

== Description ==

[Shopwarden](https://shopwarden.com) makes sure that your WooCommerce store is fully operational and you're not losing out on orders. With a broad range of checks we make sure that every aspect of your shop is working correctly. We monitor your uptime, use robots to monitor all your important flows like the checkout, make sure your plugins are up-to-date & secure and more.

This plugin allows Shopwarden to hook into your WooCommerce installation to monitor your plugins, make sure our test orders don't show up in your order list and gather metrics.

= Pricing = 

Shopwarden has a free plan so everybody can get some peace of mind. Our $29 Pro plan will allow you to fully and extensivly monitor every aspect of your store. Check out the full details [here](https://shopwarden.com/pricing/).

= Data sharing =

After you link your Shopwarden account with this plugin, the following data will be shared:
- Wordpress installation general info & plugin list
- Order metadata (no personal data)
- Product names & images (to select a product for checkout testing)

== Screenshots ==

1. WooCommerce shop overview
2. Checkout test overview
3. Configuring user flow tests

== Changelog ==

= 1.0.11 (8 november 2023) =
- Some small fixes and improvements

= 1.0.10 (8 november 2023) =
- Enabled payment gateway for subscriptions and other products

= 1.0.9 (18 october 2023) =
- Improved test payment gateway initialization  

= 1.0.8 (22 september 2023) =
- Improved plugin onboarding flow

= 1.0.7 (19 september 2023) =
- Added option to choose to delete test user account or not
- Removed unused order API routes

= 1.0.6 (27 february 2023) =
- Delete test customer account when available

= 1.0.5 (20 february 2023) =
- Enable automatically testing subscription checkout flows

= 1.0.4 (15 january 2023) =
- Add method to delete test orders when cronjobs are disabled

= 1.0.3 (29 october 2022) =
- Disable custom webhook topics for test orders 

= 1.0.2 (24 august 2022) =
- Extend order sync with more meta data to allow better monitoring (no personal data is transfered)

= 1.0.1 (4 june 2022) =
- Make sure test orders with physical products skip 'processing' state
- Improve PHP8 compatibility 

= 1.0.0 (12 march 2022) =
Initial release
