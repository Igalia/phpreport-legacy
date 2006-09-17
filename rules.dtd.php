<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
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

require_once("include/config.php");

$type=array();
foreach(array_keys((array)$table_type) as $i) {
 if (!empty($i)) {
  $type[]=strtoupper($i);
 }
}
foreach(array_keys((array)$table_type) as $i) {
 if (!empty($i)) {
  $type[]=$i;
 }
}

$name=array();
foreach(array_keys((array)$table_name) as $i) {
 if (!empty($i)) {
  $name[]=$i;
 }
}

$phase=array();
foreach(array_keys((array)$table_phase) as $i) {
 if (!empty($i)) {
  $phase[]=$i;
 }
}

$ttype=array();
foreach(array_keys((array)$table_ttype) as $i) {
 if (!empty($i)) {
  $ttype[]=$i;
 }
}
$months=array("January","February","March","April","May","June","July",
  "August","September","October","November","December");

echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
?>

 <!ELEMENT weeklyDedication (dedication+)>
 <!ELEMENT dedication (dailyDedication+)>
 <!ELEMENT dailyDedication (task+)>
 <!ELEMENT task (#PCDATA)>
 
 <!ATTLIST dedication 
  month (<?=implode("|",$months)?>|<?=strtolower(implode("|",$months))?>)  #REQUIRED>
 <!ATTLIST dailyDedication day CDATA #REQUIRED>
 <!ATTLIST task 
  type (<?=implode("|",$type)?>) #REQUIRED
  start CDATA #REQUIRED
  end CDATA #REQUIRED
  name (<?=implode("|",$name)?>) #IMPLIED
  phase (<?=implode("|",$phase)?>) #IMPLIED
  ttype (<?=implode("|",$ttype)?>) #IMPLIED
  story CDATA #IMPLIED
  telework (true) #IMPLIED>
