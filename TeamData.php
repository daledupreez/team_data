<?php
/*
Plugin Name: Team Data
Plugin URI: http://github.com/daledupreez/team_data/
Description: An initial attempt to modularize the custom data needs of the Mystic River site in a plugin.
Version: 0.31
Author: Dale du Preez
License: GPL2
*/
/*  Copyright 2013  Dale du Preez  (email: daledupreez+teamdataplugin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once 'TeamDataBase.php';

$team_data = new TeamDataBase();
$team_data->add_actions();
?>