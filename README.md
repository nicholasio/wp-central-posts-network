WP Central Posts Network
===========

A WordPress Multisite Plugin that let you choose any posts on any site in the network to display on the main site.

## About ##

Contributors: nicholas_io
Donate link: 
Tags: multisite, posts-selector, global,posts
Requires at least: 4.1
Tested up to: 4.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description ##

This plugin adds a UI on the main site admin panel of a Network that let you choose posts of any site of your network and associate it with sections that you must define via a hook filter. Then you can show up this sections on the front of your main site via a simple API.

### How it works ####

#### Defining groups and sections ####
To actually use this plugins, first you must define the groups and the sections that you wanna put posts, in the example below, it defines two groups: `homepage_highlights` and `homepage_secondary`, the first group has two sections `news` and `old-news`, the second group has only one section called `other-news`. This groups and sections will show up in `Panel => Network Post Selector` on main site panel.
```php
add_filter('wpcpn_posts_section', 'mysite_wpcpn_posts_section');
function mysite_wpcpn_posts_section() {

  return array(
    //the key of this array defines a group, each group creates a tab
    'homepage_highlights' => array( 
      //the name of the group
      'name'  => 'Posts Highlights', 
      'sections'  => array( //the sections arrays holds all sections definitions
            'news' => array( //the key is the slug of the sections
               'name'               => 'News', //The Name
               'description'        => 'News section', //Descriptions
               'max_posts'          => 4, //Max Posts in this sections
               //sites => array(2, 3, 4) //'all' or specify the blogs_id that you canpull posts
               'sites'              => 'all',
               //should posts of main site be included?
               'include_main_site'  => false 
            ),
            'old-news' => array(
               'name'               => 'Old News',
               'description'        => 'Old News Posts',
               'max_posts'          => 3,
               'sites'              => 'all', 
               'include_main_site'  => false
            ),

         ), //sections
    ), //homepage_highliths
    'homepage_secondary' => array(
       'name' => 'Posts Secundários',
       'sections' => array(
          'other-news' => array(
             'name'               => 'Others News',
             'description'        => 'Others News Posts',
             'max_posts'          => 3,
             'site'              => 'all',
             'include_main_site'  => false
          ),

       ) //sections
    ) //homepage_secondary

  );
}
```

#### Displaying posts on the main site ####
To display posts on the main site you need to call a function named `wpcpn_show_posts_section` and pass the correct parameters, you should use this function on the theme used in the main site. If you have two sites with the same same theme, you can check if the current site is the main site, or you can create a child theme and use it only on the main site.
```php
if ( function_exists('wpcpn_show_posts_section')) {
      wpcpn_show_posts_section(
                      'homepage_highlights', //group slug
                      'news',  //sections slug
                      array(  //will load the file partials/content-featured.php
                          'template_slug' => 'partials/content', 
                          'template_name' => 'featured' 
                      ),
                      array( //Optional parameters
                          'limit'         => 3 //Show only 3 posts
                          'offset'        => 1 //Bypass the first post
                      ) 
      );
  }
```

Of course you need to define the file `partials/content-featured.php`, you can define it using regular WordPress functions, you don't need to perform the loop, the plugin already does it for you. 
```html
<article>
  <figure>
      <a href="<?php the_permalink(); ?>">            
        <?php the_post_thumbnail(); ?>
      </a>
  </figure>
  <section>
      <h5>
          <small><?php echo get_the_time('j F, Y'); ?></small>
      </h5>
      <a href="<?php the_permalink(); ?>">
          <h4><?php the_title(); ?></h4>            
      </a>
      <p><?php the_excerpt() ?></p>
  </section>
</article>
```
#### Feature Requests ####
By the default admins of the sites can request that a single post shows up on the main site. This plugins adds a link to the edit.php page (the page that list the posts) called `Request Feadured in Home`. When the user clicks on the link, a pop up will show up and the user must provide a text describring the request.

This functionality can be deactivate by using the following code:
```php
add_filter('wpcpn_activate_feature_requests', 'mysite_wpcpn_disable_featured_requests');
function mysite_wpcpn_disable_featured_requests( $status ) {
  return false;
}
```
#### Advanced Use ####
#####Post Types #####
You can specify which post_types the plugin should return when a particular site is chosen in the selector posts.
Eg:
```php
'old-news' => array(
   'name'               => 'Old News',
   'description'        => 'Old News Posts',
   'max_posts'          => 3,
   'sites'              => 'all', 
   'include_main_site'  => false,
   'post_types'         => array( 2 => array('banner') )
)
```

#####Restrictions #####
It's possible to define aditional restrictions to filter the posts of a site that can be selected for a given section.
At the moment we have one native restriction, but you can define your own custom restriction.
In the example above whe are telling that the plugin must return only posts of banner post_type for site with id = 2. All the others sites by default will retrieve only posts of the `post` post_type.
Eg:

```php
'old-news' => array(
   'name'               => 'Old News',
   'description'        => 'Old News Posts',
   'max_posts'          => 3,
   'sites'              => 'all', 
   'include_main_site'  => false,
   'restrictions'       => array( 
                           'taxonomy' => array(
                              'taxonomy_slug' => 'category',
                              'term_slug'     => 'news'
                           ),
                           'has_banner' => array( 'custom_params' ) //A custom restriction
                    ),
),
```

The native restriction `taxonomy` receives a `taxonomy_slug`and `term_slug` and check if the post has the `term_slug` in the `taxonomy_slug`. In the example we are loading only the posts that has the `news` category, so if the site has posts without the `news` category, they wont be selectable for that section.

To use a custom restriction, you must define it in the sections config array and create a filter hook with the tag: ` wpcpn_restriction_{your_custom_unique_restriction}` Eg:

```php
add_filter('wpcpn_restriction_has_banner', 'mysite_wpcpn_has_banner', 1, 4);
function mysite_wpcpn_has_banner($pass, $post, $blog_id, $restrictions_params) {
   // if the post failed in previews restrictions, we do not need to check this restriction anymore
   if ( ! $pass ) return false; 

   //we don't need to execute swtich_to_blog, we're already on the right context.
   $bannerimg = get_post_meta($post->ID, '_inner_banner_image');
   if ( $bannerimg )
      return true;
   else
      return false;
}
```

The `$restrictions_params` are the `array('custom_params')` defined in the configuration of restrictions.

##### Before Select Filter #####
If you want to perform an action when the user try do add a post to a given section, you can define a ajax call with the following tag: `wp_ajax_wpcpn_before_select_{group}_{section}`. Eg:
```php
add_action('wp_ajax_wpcpn_before_select_homepage_highlights_news', 'mysite_wpcpn_banner_on_select');
function mysite_wpcpn_banner_on_select() {
      $blog_id = $_GET['blog_id'];
      $post_id = $_GET['post_id'];

      //with ajax we need to switch_to_blog
      switch_to_blog($blog_id);
      $bannerimg = get_post_meta($post_id, '_inner_banner_image');
      restore_current_blog();
      
      if ( $bannerimg )
         echo 1; //can add
      else
         echo 0; //cant't add
      
      die(); //Good practice finish ajax calls with die
}
```
If the ajax call echoes 1, then the post can be added, if not, it can't. 
You can use the before select filter instead a custom restriction if you want. The main difference to restrictions are that with the before select filter, the post is still shown for selection even it can't be added to a section.

#### Cache ####
WordPress Multisite is a heavy system and you may consider using a cache system if you have load issues. This plugins integrates with W3 Total Cache and WP Super Cache (basically it flushes the cache when the posts list of a given section changes).

It also comes with a simple fragment caching system, this system will not speed up you site, but it will eliminate the aditional load added by the plugin.

As this plugin can add an aditional load to you site, consider using a good server too.

To use one of the cache choices you must tell to the plugin.
```php
add_filter('wpcpn_cache_config', 'mysite_wpcpn_cache_config');

function mysite_wpcpn_cache_config( $config ) {
  //return false; //return false to deactivate
  //return array('type' => 'w3-total-cache'); // Use this to integrate to w3-total-cache
  //return array('type' => 'wp-super-cache'); // Use this to integrate to wp-super-cache
  return array( // Use this to use simple fragment-caching
    'type'       => 'fragment-caching',
    'expiration' => 12 * HOUR_IN_SECONDS, //in seconds
    'cache'      => array( //define what sections to cache
      'homepage_highlights' => array('news','old-news'),
      'homepage_secondary'  => array('other-news')
    )
  );
}
```


## Installation ##

To install just follow the installation steps of most WordPress plugin's:

e.g.

1. Download the file wp-central-posts-network.zip;
2. Unzip the file on your computer;
3. Upload folder post-useful, you just unzip to `/wp-content/plugins/` directory;
4. Activate the plugin on the `Network` through the `Plugins` menu of `Network Panel` in WordPress;
5. Be happy.

THIS PLUGIN ONLY WORKS WITH MULTISITE AND MUST BE NETWORK ACTIVATED

## Screenshots ##

![Choosing the posts for a given section.](/screenshot-1.png?raw=true)
Choosing the posts for a given section.

![The posts displaying on the main site.](/screenshot-2.png?raw=true)
The posts displaying on the main site

![The code needed.](/screenshot-3.png?raw=true)
The code needed.

## Want to Collaborate? ##

1. Take a [fork](https://help.github.com/articles/fork-a-repo/) repository;
3. [Set your fork](https://help.github.com/articles/configuring-a-remote-for-a-fork/);
2. Check the [issues](https://github.com/WordPressBeloHorizonte/horizon-theme/issues) and choose one that does not have anyone working;
4. [Synchronize your fork](https://help.github.com/articles/syncing-a-fork/);
2. Create a branch to work on the issue of responsibility: `git checkout -b issue-17`;
3. Commit the changes you made: `git commit -m 'fix issue #17'`;
4. Make a push to branch: `git push origin issue-17`;
5. Make a [Pull Request](https://help.github.com/articles/using-pull-requests/) :D

**Note:** If you want to contribute something that was not registered in [issues](https://github.com/leobaiano/post-useful/issues) you can create. It is important to sign the issue that you will work to prevent someone else to start working on the same task.
