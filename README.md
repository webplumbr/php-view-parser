Yet another PHP view parser that is meant to be simple and cacheable. No more ugly PHP tags and logic within the view template.
Let your frontend JavaScript/CSS developer breathe some fresh air!

Requirement(s)
-------------
PHP 5.3.0 or above

This view parser makes use of PHP namespaces.

Optional Requirement(s)
-----------------------
Memcached extension for PHP and related library files

Getting Started
---------------
Take a look at *index.php* file and associated HTML example templates. You can uncomment examples in *index.php* and see how the parser 
works for iteration and included files.

The view parser employs a Cache interface and is offered with Memcached driver. So, if you are testing the Cacheability, make 
sure you have Memcached PHP5 module and related library files installed on your Server and set the Memcached Server IP/name and port 
according to your Host settings.

