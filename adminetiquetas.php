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

/**
 * PARÁMETROS HTTP QUE RECIBE ESTA PÁGINA:
 *
 * dia = Día del que mostrar el calendario. Formato DD/MM/AAAA
 * activacion[tipo][codigo] = La etiqueta [tipo][codigo] se encuentra activa
 * borrado[tipo][codigo] = Se ha pulsado BORRAR para la etiqueta [tipo][codigo]
 * tipo = Tipo de la etiqueta a agregar
 * codigo = Código de la etiqueta a agregar
 * descripcion = Descripción de la etiqueta a agregar
 * aplicar = Se ha pulsado APLICAR ACTIVACIONES
 * agregar = Se ha pulsado AGREGAR ETIQUETA
 */

require_once("include/autentificado.php");
require_once("include/util.php");
require_once("include/prepara_calendario.php");
require_once("include/conecta_db.php");

if (!in_array("informesadm",(array)$session_grupos)) {
 header("Location: login.php");
}

if (!empty($borrado)) {
 $tipo_borrado=array_pop(array_keys($borrado));
 $codigo_borrado=array_pop(array_keys($borrado[$tipo_borrado]));

 if (!$result=@pg_exec($cnx,$query=
   "DELETE FROM etiqueta"
  ." WHERE tipo='$tipo_borrado' AND codigo='$codigo_borrado'")) {
  $error="No se ha podido completar la operación";
 } else {
  $confirmacion="La etiqueta se ha borrado correctamente";
 }
}

if (!empty($agregar)) {
 if (!$result=@pg_exec($cnx,$query=
   "INSERT INTO etiqueta(tipo,codigo,descripcion)"
  ." VALUES ('$tipo','$codigo','$descripcion')")) {
  $error="No se ha podido completar la operación";
 } else {
  $confirmacion="La etiqueta se ha insertado correctamente";
  unset($tipo); unset($codigo); unset($descripcion);
 }
}

if (!empty($aplicar)) {

 // LISTAMOS TODOS LOS VALORES Y OBTENEMOS LOS QUE HAN CAMBIADO (e)

 $e=array();
 $result=@pg_exec($cnx,$query="SELECT tipo,codigo,activacion FROM etiqueta")
 or die("No se ha podido completar la operación");
 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  if ((checkbox_to_sql($activacion[$row["tipo"]][$row["codigo"]]))
   !=($row["activacion"])) {
   $e[]=array($row["tipo"],$row["codigo"]);
  }
 }
 @pg_freeresult($result);

 // ACTUALIZAMOS LOS CAMBIADOS

 $r=true;
 foreach ($e as $k) {
  $result=@pg_exec($cnx,$query=
   "UPDATE etiqueta SET activacion='".checkbox_to_sql($activacion[$k[0]][$k[1]])."'"
  ." WHERE tipo='${k[0]}' AND codigo='${k[1]}'");
  $r&=(!(!$result));
  @pg_freeresult($result);
 }

 if ($r) {
  $confirmacion="Las activaciones se han aplicado correctamente";
 } else {
  $error="No se ha podido completar la operación";
 }
}

$etiqueta=array(array('Tipo','Código','Descripción','Activación','Borrado'));
$result=@pg_exec($cnx,$query="SELECT * FROM etiqueta"
 ." ORDER BY tipo,codigo")
or die("No se ha podido completar la operación");

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $row["descripcion"]=stripslashes($row["descripcion"]);
 $row["activacion"]="<input type=\"checkbox\" name=\"activacion["
  .$row["tipo"]."][".$row["codigo"]."]\" "
  .sql_to_checkbox($row["activacion"])." >";
 $row["borrado"]="<input type=\"submit\" name=\"borrado["
  .$row["tipo"]."][".$row["codigo"]."]\" value=\"Borrar\">";
 $etiqueta[]=$row;
}
@pg_freeresult($result);
$etiqueta[]=array(
 'tipo'=>"<select name=\"tipo\" size=\"1\">"
  .array_to_option(array('tipo','nombre','fase','ttipo'),$tipo)."</select>",
 'codigo'=>"<input type=\"text\" name=\"codigo\" value=\"".stripslashes($codigo)."\">",
 'descripcion'=>"<input type=\"text\" name=\"descripcion\" value=\"".stripslashes($descripcion)."\">",
 'activacion'=>"<input type=\"submit\" name=\"aplicar\" value=\"Aplicar activaciones\">",
 'envio'=>"<input type=\"submit\" name=\"agregar\" value=\"Agregar etiqueta\">"
);

require_once("include/cerrar_db.php");

$title="Administración de etiquetas";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
if (!empty($confirmacion)) msg_ok($confirmacion);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name="etiquetas" method="post">

<br>

<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0" xwidth="95%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
<?
$primera=true;
foreach ((array)$etiqueta as $fila) {
 if ($primera) {
  $primera=false;
  $class="titulo_caja";
 } else {
  $class="texto_caja";
 }
?>
 <tr>
<?
 foreach ((array)$fila as $campo) {
?>
  <td bgcolor="#000000" class="<?=$class?>">
   <font color="#FFFFFF" class="<?=$class?>">
   <?=$campo?>
   </font>
  </td>
<?
 }
?>
 </tr>
<?
}
?>
</table>
</td></tr></table>
<!-- fin caja -->

</form>
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/muestra_secciones.php") ?>
<br>
<? require("include/muestra_calendario.php") ?>
</td>
</tr>
</table>

<?
require("include/plantilla-post.php");
?>
