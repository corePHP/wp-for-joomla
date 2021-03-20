<?php
/**
* @version		$Id: index.php 10381 2008-06-01 03:35:53Z rafa $
* @package		WordPress for Joomla
* @copyright	Copyright (C) 2010 'corePHP' LLC, www.corephp.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/

$_SERVER['WPPRE_SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'];
$_SERVER['WPPRE_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
$_SERVER['WPPRE_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['WPPRE_PHP_SELF'] = $_SERVER['PHP_SELF'];

$_SERVER['SCRIPT_FILENAME'] = dirname( dirname( $_SERVER['SCRIPT_FILENAME'] ) )
	. DIRECTORY_SEPARATOR . 'index.php';
// $_SERVER['REQUEST_URI'] = dirname( dirname(  $_SERVER['REQUEST_URI'] ) ) . '/index.php';
$_SERVER['SCRIPT_NAME'] = dirname( dirname(  $_SERVER['SCRIPT_NAME'] ) ) . '/index.php';
$_SERVER['PHP_SELF'] = dirname( dirname(  $_SERVER['PHP_SELF'] ) ) . '/index.php';

if ( !isset( $_REQUEST['option'] ) ) {
	$_REQUEST['option'] = 'com_wordpress';
}

include( '../index.php' );