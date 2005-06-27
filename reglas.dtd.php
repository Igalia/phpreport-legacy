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

$tipo=array();
foreach(array_keys((array)$tabla_tipo) as $i) {
 if (!empty($i)) {
  $tipo[]=strtoupper($i);
 }
}
foreach(array_keys((array)$tabla_tipo) as $i) {
 if (!empty($i)) {
  $tipo[]=$i;
 }
}

$nombre=array();
foreach(array_keys((array)$tabla_nombre) as $i) {
 if (!empty($i)) {
  $nombre[]=$i;
 }
}

$fase=array();
foreach(array_keys((array)$tabla_fase) as $i) {
 if (!empty($i)) {
  $fase[]=$i;
 }
}

$ttipo=array();
foreach(array_keys((array)$tabla_ttipo) as $i) {
 if (!empty($i)) {
  $ttipo[]=$i;
 }
}

echo("<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>");
?>

 <!ELEMENT dedicacionSemanal (dedicacion+)>
 <!ELEMENT dedicacion (dedicacionDiaria+)>
 <!ELEMENT dedicacionDiaria (tarea+)>
 <!ELEMENT tarea (#PCDATA)>
 
 <!ATTLIST dedicacion 
  mes (Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre) #REQUIRED>
 <!ATTLIST dedicacionDiaria dia CDATA #REQUIRED>
 <!ATTLIST tarea 
  tipo (<?=implode("|",$tipo)?>) #REQUIRED
  inicio CDATA #REQUIRED
  fin CDATA #REQUIRED
  nombre (<?=implode("|",$nombre)?>) #IMPLIED
  fase (<?=implode("|",$fase)?>) #IMPLIED
  ttipo (<?=implode("|",$ttipo)?>) #IMPLIED
  story CDATA #IMPLIED>
