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


require_once("include/autentificado.php");

require_once("include/conecta_db.php");

$result=pg_exec($cnx,"SELECT * FROM informe");
$tabla=array();
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++)
 $tabla[]=$row;

require_once("include/cerrar_db.php");

$title="Página de prueba";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
?>
<table border="1">
<?
foreach ($tabla as $fila) {
?>
 <tr>
<?
 foreach ((array)$fila as $campo) {
?>
  <td>
   <?=$campo?>
  </td>
<?
 }
?>
 </tr>
<?
}
?>
</table>

<?
require("include/plantilla-post.php");
?>
