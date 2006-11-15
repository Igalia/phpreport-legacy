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

// LOADING OF CODE TABLES FROM DATABASE

$table_type=array(""=>"---");
$table_name=array(""=>"---");
$table_phase=array(""=>"---");
$table_ttype=array(""=>"---");

$die=_("The operation couldn't be completed: ");
$result=@pg_exec($cnx,$query="SELECT type,code,description FROM label"
  ." WHERE activation='t' ORDER BY description")
  or die($die."$query");
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $n="table_".$row['type'];
  ${$n}[$row['code']]=$row['description'];
}
@pg_freeresult($result);
?>
