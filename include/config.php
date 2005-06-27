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


// Conexión con la base de datos
require_once("include/config_db.php");
require_once("include/conecta_db.php");

$ruta_absoluta = "/tmp/phpreport/";
$limite_fichero = 500000;
$modo_autenticacion = "sql";
//$modo_autenticacion = "ldap";
$LDAP_SERVER = "localhost";
$LDAP_BASE = "dc=igalia,dc=com";

// RECUPERACIÓN DE LAS TABLAS DE CÓDIGOS DE LA BD

$tabla_tipo=array(""=>"---");
$tabla_nombre=array(""=>"---");
$tabla_fase=array(""=>"---");
$tabla_ttipo=array(""=>"---");

$result=@pg_exec($cnx,$query="SELECT tipo,codigo,descripcion FROM etiqueta"
 ." WHERE activacion='t' ORDER BY descripcion")
or die("No se ha podido completar la operación: $query");

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $n="tabla_".$row['tipo'];
 ${$n}[$row['codigo']]=$row['descripcion'];
}
@pg_freeresult($result);
?>
