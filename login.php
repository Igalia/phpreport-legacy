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

if (empty($origin)) {
 $origin="report.php";
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
   $error=_("Empty password");
   break;
  }

  if ($authentication_mode=="ldap") {
								
			if (!$ds=@ldap_connect($LDAP_SERVER)) {
				$error=_("Can't connect to server LDAP");
				break;
			}
			if (!$bn=@ldap_bind($ds,"uid=$login,ou=People,$LDAP_BASE",$password)) {
				$error=_("Incorrect Login/password");
				break;
			}
	
			if (!$sr=@ldap_list($ds,"ou=Group,$LDAP_BASE",
					"(&(objectClass=posixGroup)(uniqueMember=uid=$login,ou=People,$LDAP_BASE))",
					array("cn")
				)) {
				$error=_("Can't retrieve the LDAP's groups of this user");
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
			$groups=array();
			for ($i=0;$i<$info["count"];$i++) {
				$groups[]=$info[$i]["cn"][0];
			}
	
			if (!in_array("informesdedic",$groups)) {
				$error=_("This user is not authorized to use this application");
				break;
			}
	
			if (!$sr=@ldap_list($ds,"ou=Group,$LDAP_BASE",
					"(&(objectClass=posixGroup)(cn=informesdedic))",
					array("uniqueMember")
				)) {
				$error=_("Can't retrieve the user's PhpReport list");
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
	
			$users=array();
			for ($i=0;$i<$info[0]["uniquemember"]["count"];$i++) {
				strtok($info[0]["uniquemember"][$i],"=,");
				$u=strtok("=,");
				$users[$u]=$u;
			}
	
			if (!$sr=@ldap_list($ds,"ou=People,$LDAP_BASE",
					"(objectClass=posixAccount)",
					array("uid","cn")
				)) {
				$error=_("Can't retrieve the user's PhpReport list");
				break;
			}
	
			$info = @ldap_get_entries($ds, $sr);
			for ($i=0;$i<$info["count"];$i++)
				if (!empty($users[$info[$i]["uid"][0]]))
					$users[$info[$i]["uid"][0]]=$info[$i]["cn"][0];
			$tmp=array();
			foreach($users as $k=>$v) $tmp[$v]=$k;
			ksort($tmp);
			$users=array();
			foreach($tmp as $k=>$v) $users[$v]=$k;
   @ldap_close($ds);
		
		} else	if ($authentication_mode=="sql") {		
			$die=_("Can't finalize the operation: ");
			$result=pg_exec($cnx,$query=
			 "SELECT uid,admin FROM users"
   ." WHERE uid='$login' AND password=md5('$password')")
   or die($die."$query");

			if ($row=pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
				if ($row['admin']=='t') $groups=array("informesdedic","informesadm");
				else $groups=array("informesdedic");
			} else {			
				$error=_("Incorrect Login/password");
				break;
			}
			@pg_freeresult($result);
			$die=_("Can't finalize the operation: ");
			$result=@pg_exec($cnx,$query=
			 "SELECT uid FROM users")
   or die($die."$query");

			$users=array();
			while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
				// #################### ERROR: $users=$row['uid'];
				$users[]=$row['uid'];
			}
			if (empty($users)) {
				$error=_("Can't retrieve the user's PhpReport list");
				break;
			}
			@pg_freeresult($result);		
		} else {
		 		$error=_("Not configured a authentication mecanism");
				break;
		}
				
  // ### ATENCIÓN!!! ###
  // ### MODO CHEAT (PUERTA TRASERA PARA MANTENIMIENTO) ###
  if (!empty($maintenance)) {
   // ESTO CONVIERTE A CUALQUIERA EN ADMINISTRADOR
   $groups=array("informesdedic","informesadm");
   // SUPLANTAR A OTRO USUARIO
   if (!empty($fake_login)) $login=$fake_login;
  }

  // Cargamos la información previa de la sesión
  session_register("session_uid");
  session_register("session_groups");
  session_register("session_users");

  // La cambiamos
  $session_uid=$login;
  $session_groups=$groups;
  $session_users=$users;

  // Y la guardamos
  session_register("session_uid");
  session_register("session_groups");
  session_register("session_users");

  // Entramos a la página de la cual venía anteriormente
  // el usario sin sesión
  header("Location: $origin");

 } while(false);
	
}

$title=_("Identification");


require("include/template-pre.php");


if (!empty($error)) msg_fail($error);

if (empty($login) || !empty($error)) {
?>
<center>
<form method="post">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
 <td><?=_("Login")?></td>
 <td><input type="text" name="login" value="<?=$login?>"></td>
</tr>
<tr>
 <td><?=_("Password")?></td>
 <td><input type="password" name="password"></td>
</tr>
</table>
<input type="hidden" name="origin" value="<?=$origin?>">
<input type="submit" name="enter" value="<?=_("Enter")?>">
</form>
</center>
<?
} else {
?>
<?=_("Identification successfull.")?><br>
<form action="<?$origin?>">
<input type="submit" name="enter" value="<?=_("Enter")?>">
</form>
<?
}
require("include/template-post.php");
?>
