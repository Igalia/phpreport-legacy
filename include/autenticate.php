<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2009 Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Andrés Maneiro Boga <amaneiro@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  Jacobo Aragunde Pérez <jaragunde@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  María Fernández Rodríguez <maria.fdez.rguez@gmail.com>
//  Mario Sánchez Prada <msanchez@igalia.com>
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

require_once("include/util.php");
require_once("include/config.php");

unset($session_uid);
unset($session_groups);
session_register("session_uid");
session_register("session_groups");
session_register("session_users");
if (empty($session_uid)) {
 header ("Location: login.php?origin=".urlencode(stripslashes($_SERVER["REQUEST_URI"])));
}
?>
