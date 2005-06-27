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
 * login = Login del usuario (coincide con uid en LDAP)
 * password = Password del usuario
 */
require_once("include/config.php");
require_once("include/util.php");

// Si el usuario no venía previamente de ninguna página,
// se indica a cual debe ir en caso
// de autentificarse correctamente

if (empty($procedencia)) {
 $procedencia="informe.php";
}

if (!empty($logout)) {
 session_start();
	session_destroy();
	$_SESSION=array();
}

if (empty($login) && empty($password)) {
 $login=$_SERVER['PHP_AUTH_USER'];
 $password=$_SERVER['PHP_AUTH_PW'];
}

if (!empty($login)) {
	do {	 
  if (empty($password)) {
   $error="Password vacío";
   break;
  }

  if ($modo_autenticacion=="ldap") {
								
			if (!$ds=@ldap_connect($LDAP_SERVER)) {
				$error="No se puede conectar con el servidor LDAP";
				break;
			}
			if (!$bn=@ldap_bind($ds,"uid=$login,ou=People,$LDAP_BASE",$password)) {
				$error="Login/password incorrectos";
				break;
			}
	
			if (!$sr=@ldap_list($ds,"ou=Group,$LDAP_BASE",
					"(&(objectClass=posixGroup)(uniqueMember=uid=$login,ou=People,$LDAP_BASE))",
					array("cn")
				)) {
				$error="No se pueden recuperar los grupos LDAP a los que pertenece el usuario";
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
			$grupos=array();
			for ($i=0;$i<$info["count"];$i++) {
				$grupos[]=$info[$i]["cn"][0];
			}
	
			if (!in_array("informesdedic",$grupos)) {
				$error="El usuario no está autorizado a utilizar la aplicación";
				break;
			}
	
			if (!$sr=@ldap_list($ds,"ou=Group,$LDAP_BASE",
					"(&(objectClass=posixGroup)(cn=informesdedic))",
					array("uniqueMember")
				)) {
				$error="No se puede recuperar la lista de usuarios de PhpReport";
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
	
			$usuarios=array();
			for ($i=0;$i<$info[0]["uniquemember"]["count"];$i++) {
				strtok($info[0]["uniquemember"][$i],"=,");
				$u=strtok("=,");
				$usuarios[$u]=$u;
			}
	
			if (!$sr=@ldap_list($ds,"ou=People,$LDAP_BASE",
					"(objectClass=posixAccount)",
					array("uid","cn")
				)) {
				$error="No se puede recuperar la lista de usuarios de PhpReport";
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
			for ($i=0;$i<$info["count"];$i++)
				if (!empty($usuarios[$info[$i]["uid"][0]]))
					$usuarios[$info[$i]["uid"][0]]=$info[$i]["cn"][0];
			$tmp=array();
			foreach($usuarios as $k=>$v) $tmp[$v]=$k;
			ksort($tmp);
			$usuarios=array();
			foreach($tmp as $k=>$v) $usuarios[$v]=$k;
   @ldap_close($ds);
		
		} else	if ($modo_autenticacion=="sql") {		
			
			$result=pg_exec($cnx,$query=
			 "SELECT uid,admin FROM usuario"
   ." WHERE uid='$login' AND password=md5('$password')")
   or die("No se ha podido completar la operación: $query");

			if ($row=pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
				if ($row['admin']=='t') $grupos=array("informesdedic","informesadm");
				else $grupos=array("informesdedic");
			} else {			
				$error="Login/password incorrectos";
				break;
			}
			@pg_freeresult($result);
			
			$result=@pg_exec($cnx,$query=
			 "SELECT uid FROM usuario")
   or die("No se ha podido completar la operación: $query");

			$usuarios=array();
			while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
				$usuarios=$row['uid'];
			}
			if (empty($usuarios)) {
				$error="No se puede recuperar la lista de usuarios de PhpReport";
				break;
			}
			@pg_freeresult($result);		
		} else {
		  $error="No se ha configurado un mecanismo de autenticación";
				break;
		}
				
  // ### ATENCIÓN!!! ###
  // ### MODO CHEAT (PUERTA TRASERA PARA MANTENIMIENTO) ###
  if (!empty($mantenimiento)) {
   // ESTO CONVIERTE A CUALQUIERA EN ADMINISTRADOR
   $grupos=array("informesdedic","informesadm");
   // SUPLANTAR A OTRO USUARIO
   if (!empty($fake_login)) $login=$fake_login;
  }

  // Cargamos la información previa de la sesión
  session_register("session_uid");
  session_register("session_grupos");
  session_register("session_usuarios");

  // La cambiamos
  $session_uid=$login;
  $session_grupos=$grupos;
  $session_usuarios=$usuarios;

  // Y la guardamos
  session_register("session_uid");
  session_register("session_grupos");
  session_register("session_usuarios");

  // Entramos a la página de la cual venía anteriormente
  // el usario sin sesión
  header("Location: $procedencia");

 } while(false);
	
}

$title="Identificación";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);

if (empty($login) || !empty($error)) {
?>
<center>
<form method="post">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
 <td>Login</td>
 <td><input type="text" name="login" value="<?=$login?>"></td>
</tr>
<tr>
 <td>Password</td>
 <td><input type="password" name="password"></td>
</tr>
</table>
<input type="hidden" name="procedencia" value="<?=$procedencia?>">
<input type="submit" name="entrar" value="Entrar">
</form>
</center>
<?
} else {
?>
Identificación correcta.<br>
<form action="<?$procedencia?>">
<input type="submit" name="entrar" value="Entrar">
</form>
<?
}
require("include/plantilla-post.php");
?>
