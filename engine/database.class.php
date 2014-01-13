<?php

//BEGIN CHANGE (IN 1.2)
defined('security') or exit("NICE TRY!!");

if ( extension_loaded('mysqli') )
{
	require_once( engine_dir."mysqli.class.php" );
}
else
{
	require_once( engine_dir."mysql.class.php" );
}
//END CHANGE (IN 1.2)
?>
