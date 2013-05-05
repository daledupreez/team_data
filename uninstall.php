<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}


if ( file_exists( 'TeamDataInstaller.php' ) {
	include 'TeamDataInstaller.php';
}

if ( !class_exists( 'TeamDataInstaller' ) ) {
	exit();
}

$team_data_installer = new TeamDataInstaller();
$team_data_installer->uninstall();

?>