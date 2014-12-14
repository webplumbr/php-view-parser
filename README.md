A PHP view parser that is meant to be simple, cacheable and adaptable to your MVC framework. No more ugly PHP tags and logic within the view template. Let your frontend JavaScript/CSS developer breathe some fresh air!

Requirement(s)
-------------
PHP 5.3.0 or above

This view parser makes use of PHP namespaces.

Optional Requirement(s)
-----------------------
Memcached extension for PHP and related library files

Getting Started
---------------
Take a look at *index.php* file and associated HTML example templates. You can uncomment examples in *index.php* and see how the parser works for iteration and included files.

The view parser employs a Cache interface and is offered with Memcached driver. So, if you are testing the Cacheability, make sure you have Memcached PHP5 module and related library files installed on your Server and set the Memcached Server IP/name and port according to your Host settings.

Usage
-----
There is no denying that this simple PHP view template parser was inspired by a less known Xtemplate. You can specify a cache driver of your choice that implements the Cache Manager interface. I have included Memcached driver in the package.

The idea is to make your view template code clean from any PHP logic and make it more readable to your frontend CSS developer. Let HTML be HTML as much as possible! This enables PHP logic to be moved over to the controller layer thereby creating very little dependency with the view layer.

*Tips on usage*

The constructor call expects a mandatory file name (with a relative path from document root) and an optional cache key as a second parameter. If the cache key is null, lookup for a cached version of the template will be ignored. Parent - Children relationship is maintained through qualified naming format with each node separated by the dot operator.

Example: page.table.row

Where, page is the parent of table and table in turn is the parent of row.

Use the parse method to parse the template from the deepest element first. You will essentially parse all the child nodes before calling the parse method on the parent element. The section delimiter names can be alphanumeric and words may be separated by underscore. Allowable range of characters is [a-zA-Z0-9_]

You can assign a scalar,simple object and array variables to the template. Assignment of variables is made simple using the magic __set() call.

Object with members and arrays with key = value pairs are interpreted as follows:

      $view->ITEM = array('link' => 'seo-friendly-link', 'title' => 'Human readable title');

On the template the variable is represented as:

      <a href="{ITEM.link}" title="{ITEM.title}">{ITEM.title}</a>

You can parse and render the output to the browser as follows:

      $view->parse('page');
      $view->render('page');

If you would like to cache and then render,then it is as simple as follows:

      $view->parse('page');
      
      $timeToLiveInSeconds = 3600;
      
      $view->cache($timeToLiveInSeconds)->render('page');

NOTE:The cache expiry is represented in number of seconds and should not exceed thirty days equivalent seconds.
