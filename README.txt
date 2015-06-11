=== WP Central Posts Network ===
Contributors: nicholas_io
Tags: multisite, posts-selector, global,posts
Requires at least: 4.1
Tested up to: 4.2.2
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress Multisite Plugin that let you choose any posts on any site in the network to display on the main site (or any other site of the network).

== Description ==

This plugin adds a UI on the main site (actually, in any site of the network) admin panel of a Network that let you choose posts of any site of your network and associate it with sections that you must define via a hook filter. Then you can show up this sections on the front of your main site via a simple API.

This plugin only works with multisite and must be network activated

= How it works and How to Use =
To use the plugin you need do write some code in a hook filter. Please read our README.md file in our [GitHub](https://github.com/nicholasio/wp-central-posts-network/blob/master/README.md)

== Installation ==

To install just follow the following installation steps:

NOTE: This plugin only works with multisite and must be network activated
e.g.

1. Download the file wp-central-posts-network.zip;
2. Unzip the file on your computer;
3. Upload folder wp-central-posts-network, you just unzip to `/wp-content/plugins/` directory;
4. Activate the plugin on the `Network` through the `Plugins` menu of `Network Panel` in WordPress;
5. Be happy.

== Frequently Asked Questions ==

= Can I use this plugins without multisite =

No, It does't make sense to use this plugins without multisite.

= But this plugin use switch_to_blogs, will my site become slow? =
Not necessarily, switch_to_blogs() is much faster than before, and the plugin has a native fragment cache that removes the load added by the plugin. If you still
have issues with perfomance try to use a cache plugin like WP Super Cache and W3 Total Cache, this plugin is integrated with them. See instructions on [GitHub](https://github.com/nicholasio/wp-central-posts-network) page.


== Screenshots ==

1. Choosing the posts for a given section.

2. The posts displaying on the main site

3. The code needed.


== Contribute ==

You can contribute to the source code in our [GitHub](https://github.com/nicholasio/wp-central-posts-network) page.
