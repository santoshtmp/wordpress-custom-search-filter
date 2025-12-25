=== CSF - Custom Search Filter ===
Contributors: younginnovations, santoshtmp7
Tags: CSF, form, query
Author: YIPL santoshtmp7
Author URI: https://github.com/santoshtmp
Plugin URI: https://github.com/santoshtmp/wordpress-custom-search-filter
Requires WP: 6.5
Requires at least: 6.5
Tested up to: 6.6.1
Requires PHP: 8.0
Domain Path: languages
Text Domain: csf-search-filter
Stable tag: 1.0
Version: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

To make search filter form and query easy.

== Description ==
CSF - Custom Search Filter library plugin purpose to make search filter easier for admin and developer by providing form and query it.

== Screenshots ==
1. csf-admin-setting-page.png

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/csf-search-filter` directory, or install the plugin through the WordPress plugins screen directly.
or
Use as theme functions after integration it must be build.
By require_once dirname(__FILE__) . '/csf-search-filter/csf-search-filter.php'; in theme function.php file.

2. Navigate to the settings > WP Required Post Title or link `/wp-admin/options-general.php?page=search-filter-csf` to the pugin setting page

== Frequently Asked Questions ==
= Can we apply to particular post type? =
Yes, we can apply to only selected post type.

== Release ==
1. Initial release 1.0.0

== Changelog ==
= 1.0.0 =
* Initial release.
= 1.1.0 =
* Added filter option to changes form on filter action.
* Added filter option to to add result info with filter form
= 1.2.0 =
* Added filter option "radio_always_active" for search_field_type===radio
* Added filter option "hidden_field" to hide field
* Added filter option "update_url" to update url on filter form result
= 1.3.0 =
* Added filter option "item_orderby" ASC, DESC, null; To re-arrange the filter items in dropdown, radio or checkbox options; Where, filter_term_type === 'metadata'
* Add hook "set_csf_search_fields" to modify fields from other plugins or theme
* Add hook "search_filter_form_{filtername}_{post_type}" to modify "Search Form" fields with other plugins or theme
* Add hook "search_filter_result_{filtername}_{post_type}" to modify result result template for shortcode '[csf_searchfilter filter_name="default" result_show="true"]'

== Upgrade Notice ==
= 1.0.0 =
Initial release.

== License ==
This plugin is licensed under the GPLv2 or later. For more information, see http://www.gnu.org/licenses/gpl-2.0.html.
