=== Woocommerce Price by Country ===
Contributors: Sweet Homes
Tags: woocommerce, price by country, extension, geoprice
Donate link: http://www.sweethomes.es/
Requires at least: 3.0.1
Tested up to: 4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to define the price of a product according to the country of your customers.

== Description ==
**WooCommerce price by country** lets you set prices based on customer country. Your define your country groups (as many as you need), then set prices for those groups in each product.

From that point on when a shopper connect’s to site automatically detect country by google ClientLocation api and they’ll see the product prices that you’ve set for that group and be able to buy at those price.  Prices appear across the store like any other product, meaning in the main shop page, category pages, cart, and checkout page.

If the product has no country group price for the shopper’s  then shoppers can still buy at your regular price if you have a regular price set.

WooCommerce price by country  works with simple products and variable products.

Add as many groups as you like using the built-in mini group manager (see the settings screenshot)
Remove an added group at any time
Set individual prices for any of your groups (see the product edit screenshot)

0.2 added

You can choose whether the modifier price is based on the shipping adress or billing
You can add a country selector on your theme with the code: do_action ('get_pbc_country_dropdown').

0.3 added
the country groups are modificable

0.31
bug fixes

0.32
bug fixes

0.34
Solved bugfix with wpml and woocommerce multilingual

0.35
Applied GoGlobal program suggestions to our code

0.36
Solved DEPRECATED functions

* Requires WooCommerce 2.2.x or newer.

== Installation ==
1. Upload "woocommerce-price-by-country" folder to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. go to woocommerce > settings > integration tab > price by country option.
4. push add new group button an then add many country groups as you like.
5. go to product and define it's price by group.
6. if you enter a country selector on your theme adds this code: do_action('get_pbc_country_dropdown'),
7. In simple products regular price set to "empty" and in variable products set price to "0"

== Frequently Asked Questions ==

= This plugin supports simple and variable products =

Yes, of course.

= You can add a country selector somewhere template =

Yes, adding "do_action('get_pbc_country_dropdown')"; .

= If you use Woocomerce multilingual =

you must set "Go to the native WooCommerce product editing screen" in "Product Translation Interface"


== Screenshots ==
1. Settings Section
2. Product edit section

== Changelog ==
= 0.1 =
* Initial release.
= 0.2 =
- Added country selector by do_action('get_pbc_country_dropdown')
- If country isn't in any country defined group, price and buy button disapear and is replaced by a configurable message.
- compatibility with woocommerce 2.1
= 0.3 =
- Added ability to edit groups of countries.
- Added Choice if the factor that modifies the final price of the product is shipping or billing address.
- Added a message for when a user is in a country where we no have defined a price.
- Country selector only shows the countries in which the user has defined to be sold
= 0.31 =
- Dropdown bugfix.
= 0.32 =
- Dropdown bugfix.
= 0.33 =
- price fix in variable products.
= 0.34 =
- Woocommerce multilingual fix, and wpml fix.
= 0.35 =
- Applied GoGlobal program suggestions to our code.
= 0.36 =
- Solved DEPRECATED functions

== Upgrade Notice ==
= 0.1 =
* Initial release.
= 0.2 =
* Some improvements.
= 0.3 =
* Some Cool improvements.
= 0.31 =
* Bug Fixes.
= 0.32 =
* Bug Fixes.
= 0.33 =
* Bug Fixes.
= 0.34 =
- Woocommerce multilingual fix, and wpml fix.
= 0.35 =
- Applied GoGlobal program suggestions to our code.
= 0.36 =
- Solved DEPRECATED functions