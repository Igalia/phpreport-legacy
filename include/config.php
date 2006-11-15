<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


// Connection with database and some mandatory initialization stuff
require_once("include/config_db.php");
require_once("include/connect_db.php");
require_once("include/language.php");

// Hack this to enable errors...
// error_reporting(); 

$absolute_path = "/tmp/phpreport/";
$file_limit = 500000;
$authentication_mode = "sql";
//$authentication_mode = "ldap";
$LDAP_SERVER = "localhost";
$LDAP_BASE = "dc=igalia,dc=com";
$admin_group_name="informesadm";
$board_group_names=array("igaliamanager","igaliaprepartner","igaliapartner");
$user_group_name="informesdedic";

// Looking for database passwords? Have a look at config_db.php ;-)

// This is also mandatory, because all the pages expect it
require_once("include/codetables.php");
?>
