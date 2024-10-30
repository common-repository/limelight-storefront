=== LimeLight Storefront ===

Contributors: limelightcrm
Tags: subscription, affiliate marketing, limelight, lime light, limelightcrm, limelight crm, ecommerce, affiliate, marketing, limelight plugin, lime light plugin, limelight storefront
Requires at least: 4.6
Requires PHP: 5.4
Tested up to: 4.9.8
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Plugin to easily integrate LimeLight to your Wordpress site.

== Description ==

The LimeLight Storefront makes it easy to integrate your LimeLight campaigns with your WordPress site.
Just enter your information on the settings page after installing the plugin and configure the settings to what you prefer and you are all set!
LimeLight Storefront requires a LimeLight account. Plugin works best with themes using Bootstrap 4.
Please contact us if you need assistance at [support@limelightcrm.com](mailto:support@limelightcrm.com)

== Installation ==

1. From the Wordpress Dashboard click on "Plugins" on the left.
2. From the plugins page you will see "Add New" on the top left of the page, select that.
3. From the Add Plugins page, you will see a search bar on the right to search plugins. Enter "LimeLight".
4. Click on "LimeLight Storefront" and select Install Now.
5. Once the install is complete, select Activate
6. You will now see LimeLight Storefront on the left menu. Select that and configure your settings! All set!

== Screenshots ==

1. After plugin installation and activation, you will see "LimeLight Storefront" appear on the left admin menu, and a new dashboard widget on the admin homepage.
2. Enter your API "username" and "password" as well as your "appkey". (For example, if you login to LimeLight at https://mysite.limelightcrm.com/admin/login.php you would enter: mysite. Be sure that your API user has the proper permissions.)
3. Once your credentials are confirmed, you will be given the options to configure your campaign. Select your campaign and relevant offer then save. Upon saving the plugin will perform an import of your campaign's products, and generate all the necessary pages of your shop to function.
4. After your campaign has been setup, you can then configure "Advanced Settings" of your site. This includes options such as: Default Shipping, OnePage Checkout Products, Default Prospect Product, Add-To-Cart Behaviour, Value Added Services, etc.
5. If you have questions, comments or general concerns please feel free to leave feedback.
6. Configure the error responses, and how the messages are displayed. You can show the error code and the default messages along with custom messages, etc.
7. Define "Shop Settings" shortcodes for your shop that can be used throughout posts, pages and products on your site.
8. Product Custom Post Types
9. Subscriptions Preview: As admin you can preview the related subscriptions of your campaign.
10. Orders Preview: As admin you can preview the related orders of your campaign.
11. Customers Preview: As admin you can preview related customers of your campaign.
12. Products and their categories from your LimeLight Campaign will be imported under the top-level "Shop" category/taxonomy.
13. "OnePage Checkout" on the front-end shows a pre-defined cart which is configured from "Advanced Settings"
14. Cart Page Preview Example
15. Product Page Preview Example
16. Example of using "Shop Settings" shortcodes in a post.
17. Upsells Custom Post Type
18. Adding A Post-Checkout Upsell. You need to specify the corresponding options under "LimeLight Configuration" in the top right.
19. Setting "Front-Page" as a product or page under "Reading Settings"
20. Member Login Page Preview Example

== Changelog ==

= 1.1.0 =
* Initial Release

= 1.1.1 =
* Minor Updates and Bug Fixes
* Security and Speed Updates

= 1.1.2 =
* Security and Speed Updates
* Minor bug fixes
* Added the ability to import demo data to see how the plugin functions

= 1.1.3 =
* Security and Speed Updates
* Minor bug fixes
* Always have upsell(s) initialize a new subscription (where applicable)
* Functionality to select products from many campaigns for upsell(s) added
* Functionality to group upsell(s) to one API call added (the Main Campaign will be used)
* Automatically set the product categories in WP to match LimeLight on Settings Update
* Customize Transact API Error Responses

= 1.2.0 =
* New demo mode: This will automatically import products, images and settings to get a fully functional site. It helps you get a look-n-feel of the plugin's capabiities
* Cross-sells can now be added from a different campaign. Choose the campaign, product and shipping method for up to three cross-sells
* Membership: Customers can create an account, view their order history, subscriptions and even update their shipping address
* Added support for variants: You can now select product variants
* Coupon functionality added: Coupon codes can be entered at the cart or during checkout and will be included when the order is being processed
* Enhanced notes: We've added notes to each "New Prospect" and "New Order" call that is sent to LimeLight to easier track which prospects/orders were generated via Storefront
* Custom pricing: You can modify the prices in the Products section of the plugin and these will be used when processing an order
* Minor styling modifications on the cart, checkout and thank you pages
* Speed optimizations
* Security updates and enhancements

= 1.3.0 =
* There were significant changes in this version. To prevent loss of data, it is recommended to do a complete back up your site before updating or upgrading.
* Minor bug fixes
* Revamp Pages & Shortcodes
* Dashboard Preview/Overview Widget
* Admin - Customers/Orders/Subscriptions (CRM Previews)
* Offers/Billing Models Integration into Checkout Flow
* Build Campaign and Offers relationship
* Gift Order Functionality
* Basic Member Functionality - Create Acct/Login/Logout
* Simplifying Uninstall
* VAS Rework for WP Checkout

= 1.4.0 =
* BoltPay added to plugin
* Member functionality to start, stop, change billing model on active subscriptions
* Ability to add products to existing orders (Ninja Upsells)
* Minor checkout flow refactoring
* Disallow variant products in Upsells

= 1.5.0 =
* AmazonPay added to plugin
* Fallback CSS and Gridding added
* Dynamic Credit Card Icons
* BoltPay Enhancements
* Coupon Validation of Variants
* Refactored Billing Model Selector


== Upgrade Notice ==

= 1.5.0 =
* AmazonPay added to plugin
* Fallback CSS and Gridding added
* Dynamic Credit Card Icons
* BoltPay Enhancements
* Coupon Validation of Variants
* Refactored Billing Model Selector