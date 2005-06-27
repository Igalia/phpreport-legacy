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
 * dia = Día del informe. Formato DD/MM/AAAA	
	* nueva_password[uid] = Array de passwords cambiadas
	* administrador[uid] = Array de atributos administrador cambiados (a 1 cuando true)
	* cambiar[uid] = Array de botones CAMBIAR. Habrá como mucho un elemento, cuyo uid indicará
 *                el usuario que se quiere cambiar.
	* borrar[uid] = Array de botones BORRAR. Habrá como mucho un elemento, cuyo uid indicará
	*                el usuario que se quiere borrar.
	* nuevo_usuario_login = Login del nuevo usuario a crear.
	* nuevo_usuario_password = Password del nuevo usuario a crear.
	* nuevo_usuario_administrador = Flag administrador del nuevo usuario a crear.
	* crear = Se ha pulsado CREAR
 */

require_once("include/autentificado.php");
require_once("include/conecta_db.php");
require_once("include/prepara_calendario.php");

if (!(in_array("informesadm",(array)$session_grupos) 
 && ($modo_autenticacion=="sql") )) {
 header("Location: login.php");
}

if (!empty($cambiar)) {
 $k=array_keys($cambiar);
	$uid=$k[0];
	if (!empty($nueva_password[$uid])) {
		$query_password=", password=md5('$nueva_password[$uid]')";
	} else {
	 $query_password="";
	}
	$result=@pg_exec($cnx,$query="UPDATE usuario SET admin='".
	 (($administrador[$uid]==1)?"t":"f")
	 ."' ".$query_password
		." WHERE uid='$uid'")
		or die("No se ha podido completar la operación: $query");
	$confirmacion="Los datos se han cambiado correctamente";
	@pg_freeresult($result);
}

if (!empty($borrar)) {
 $k=array_keys($borrar);
	$uid=$k[0];
	if ($uid==$session_uid) {
	 $error="Un administrador no se puede borrar a sí mismo";
	} else {
		$result=@pg_exec($cnx,$query="DELETE FROM usuario"
			." WHERE uid='$uid'")
			or die("No se ha podido completar la operación: $query");
		$confirmacion="El usuario se ha borrado correctamente";
		@pg_freeresult($result);
 }
}

if (!empty($crear)) {
		if (!$result=@pg_exec($cnx,$query="INSERT INTO usuario (uid,password,admin)"
			." VALUES('$nuevo_usuario_login',md5('$nuevo_usuario_password'),'"
			.(($nuevo_usuario_admin==1)?"t":"f")
			."')")) {
		 $error="No se ha podido completar la operación";
		} else {
		 $confirmacion="El usuario se ha creado correctamente";
		}
		@pg_freeresult($result);
}

$result=@pg_exec($cnx,$query="SELECT uid,admin FROM usuario")
or die("No se ha podido completar la operación: $query");
	
$usuarios=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$usuarios[]=$row;
}
@pg_freeresult($result);

require_once("include/cerrar_db.php");

$title="Gestión de usuarios";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
if (!empty($confirmacion)) msg_ok($confirmacion);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Lista de usuarios
<!-- fin título caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<center>
<form method="post">
<input type="hidden" name="dia" value="<?=$dia?>">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
 <th>Login</th>
 <th>Password</th>
	<th>Administrador</th>
	<td>&nbsp</td>
	<td>&nbsp</td>
</tr>
<?
 foreach ($usuarios as $usuario) {
?>
<tr>
 <td><?=$usuario["uid"]?></td>
 <td><input type="password" name="nueva_password[<?=$usuario["uid"]?>]" value=""></td>
	<td>
	 <input type="checkbox" name="administrador[<?=$usuario["uid"]?>]" 
		 value="1" <?=($usuario['admin']=='t'?"checked":"")?>>
	</td>
	<td><input type="submit" name="cambiar[<?=$usuario["uid"]?>]" value="Cambiar"></td>
	<td><input type="submit" name="borrar[<?=$usuario["uid"]?>]" value="Borrar"></td>
</tr>
<?
 }
?>
<tr>
 <td><input type="text" name="nuevo_usuario_login"></td>
 <td><input type="password" name="nuevo_usuario_password"></td>
	<td><input type="checkbox" name="nuevo_usuario_administrador" value="1"></td>
	<td><input type="submit" name="crear" value="Crear"></td>
	<td>&nbsp;</td>
</tr>
</table>
</form>
</center>
<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/muestra_recuento.php") ?>
<br>
<? require("include/muestra_secciones.php") ?>
<br>
<? require("include/muestra_calendario.php") ?>
</td>
</tr>
</table>
<?
require("include/plantilla-post.php");
?>
