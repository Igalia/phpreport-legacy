<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andr�s G�mez Garc�a <agomez@igalia.com>
//  Enrique Oca�a Gonz�lez <eocanha@igalia.com>
//  Jos� Riguera L�pez <jriguera@igalia.com>
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
 * PAR�METROS HTTP QUE RECIBE ESTA P�GINA:
 *
 * dia = D�a del informe. Formato DD/MM/AAAA
 * cambiar = Se ha pulsado CAMBIAR
 * actual_password = Password actual (por seguridad)
 * nueva_password = Nueva password
	* nueva_password2 = Confirmaci�n de password
 */

require_once("include/autentificado.php");
require_once("include/conecta_db.php");
require_once("include/prepara_calendario.php");

if (!($modo_autenticacion=="sql")) {
 header("Location: login.php");
}

if (!empty($nueva_password) || !empty($cambiar)) {
	do {
		if (empty($actual_password)) {
		 $error="Debe especificar la password actual";
			break;
		}
		if (empty($nueva_password)) {
		 $error="Debe especificar la nueva password";
			break;
		}
		if ($nueva_password!=$nueva_password2) {
		 $error="La nueva password y la password de confirmaci�n no coinciden";
			break;
		}
		
		$result=@pg_exec($cnx,$query="SELECT uid FROM usuario"
		." WHERE uid='$session_uid' AND password=md5('$actual_password')")
		or die("No se ha podido completar la operaci�n: $query");
		
		if (!($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC))) {
		 $error="La password actual es incorrecta";
			break;
		}
	 @pg_freeresult($result);
		
		$result=@pg_exec($cnx,$query="UPDATE usuario SET password=md5('$nueva_password')"
		 ." WHERE uid='$session_uid'")
			or die("No se ha podido completar la operaci�n: $query");
		$confirmacion="La contrase�a se ha cambiado correctamente";
		@pg_freeresult($result);
		
	} while (false);

}

require_once("include/cerrar_db.php");

$title="Cambio de contrase�a";
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
<!-- t�tulo caja -->
Introduzca su nueva contrase�a
<!-- fin t�tulo caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<center>
<form method="post">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
 <td>Password actual</td>
 <td><input type="password" name="actual_password"></td>
</tr>
<tr>
 <td>Nueva password</td>
 <td><input type="password" name="nueva_password"></td>
</tr>
<tr>
 <td>Confirmar password</td>
 <td><input type="password" name="nueva_password2"></td>
</tr>
</table>
<input type="submit" name="cambiar" value="Cambiar">
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
