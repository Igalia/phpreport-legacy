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
require_once("include/connect_db.php");

error_reporting(); 
//Detección del idioma que utiliza el navegador para mostrar la página en
//el idioma correspondiente
$sup_lang=array("es", "en"); //idiomas soportados
 
/* recogemos el valor almacenado, de haberlo */
if($_SESSION["lang"]!="")
  $lang=$_SESSION["lang"];
else  /* sino, a ver que nos dice el navegador */
{
  $cli_lang=explode(",", $HTTP_ACCEPT_LANGUAGE);
  /* ejemplo: si el cliente indica 'es-es', seleccionaremos 'es' */
  /* en el momento que un idioma coincide, dejamos de buscar */
  for($i=0;$i<count($cli_lang) && !isset($lang); $i++)
    for($j=0;$j<count($sup_lang); $j++)
      if(!strncmp($cli_lang[$i],$sup_lang[$j],strlen($sup_lang[$j])))
      {
        $lang=$sup_lang[$j];
        break;
      }
}
/* podemos cambiar de lenguaje con un GET */
/* y esta decisión manda sobre lo que diga el navegador */
if($_GET["lang"]!="")
  $lang=$_GET["lang"];
switch($lang)
{
  /* por defecto hemos quedado que es castellano */
  default:
  case "es":
    $_SESSION["lang"]="es";
    setlocale(LC_ALL, 'es_ES');
  break;
 
  case "en":
    $_SESSION["lang"]="en";
    setlocale(LC_ALL, 'en_EN');
  break;
}

bindtextdomain('messages', './locale');

$absolut_path = "/tmp/phpreport/";
$file_limit = 500000;
$authentication_mode = "sql";
//$authentication_mode = "ldap";
$LDAP_SERVER = "localhost";
$LDAP_BASE = "dc=igalia,dc=com";

// RECUPERACIÓN DE LAS TABLAS DE CÓDIGOS DE LA BD

$table_type=array(""=>"---");
$table_name=array(""=>"---");
$table_phase=array(""=>"---");
$table_ttype=array(""=>"---");

$die=_("No se ha podido completar la operación: ");
$result=@pg_exec($cnx,$query="SELECT type,code,description FROM label"
 ." WHERE activation='t' ORDER BY description")
or die($die."$query");
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $n="table_".$row['type'];
 ${$n}[$row['code']]=$row['description'];
}
@pg_freeresult($result);
?>
