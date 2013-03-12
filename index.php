<?php

require 'Parser.php';
require 'Cache/Manager.php';
require 'Cache/Memcache.php';

/* example 1 */
$view = new \View\Parser('views/example1.html');

//assignment of template variables
$view->TITLE = 'Greet the User';

$userLoggedIn = false;

if ($userLoggedIn) {
  $view->parse('page.session');
} else {
  $view->parse('page.anonymous');
}

$view->parse('page');
$view->render('page');

/* example 2 */
/*
$view = new \View\Parser('views/example2.html');

//assignment of template variables
$view->TITLE = 'Table &amp; Row example';

for ($i = 1; $i <= 10; $i++) {
  $view->ID = $i;
  $view->parse('page.table.row');
}
$view->parse('page.table');

$view->parse('page');
$view->render('page');
*/

/* example 3 */
/*
$view = new \View\Parser('views/example3.html');

//assignment of template variables
$view->TITLE = 'File include example';
$view->GREETING = 'hola';

$view->parse('page.script');

$view->parse('page');
$view->render('page');
*/