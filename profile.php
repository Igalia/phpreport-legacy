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
 * HTTP PARAMETERS RECEIVED BY THIS PAGE:
 *
 * day = Report day. DD/MM/YYYY format.
 * change = Change button has been pressed
 * current_password = Current password (for security)
 * new password = New password
 * new_password2 = New password confirmation 
 */

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

if (!($authentication_mode=="sql")) {
 header("Location: login.php");
}

if (!empty($new_password) || !empty($change)) {
	do {
		if (empty($current_password)) {
		 $error=_("You must specify the current password");
			break;
		}
		if (empty($new_password)) {
		 $error=_("You must specify the new password");
			break;
		}
		if ($new_password!=$new_password2) {
		 $error=_("The new password and the confirmation password don't agree");
			break;
		}
		
		$die=_("Can't finalize the operation: ");
		$result=@pg_exec($cnx,$query="SELECT uid FROM users"
		." WHERE uid='$session_uid' AND password=md5('$current_password')")
		or die("$die $query");
		
		if (!($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC))) {
		 $error=_("The current password is incorrect");
			break;
		}
	 @pg_freeresult($result);
		
		$result=@pg_exec($cnx,$query="UPDATE users SET password=md5('$new_password')"
		 ." WHERE uid='$session_uid'")
			or die("$die $query");
		$confirmation=_("The password has been changed correctly");
		@pg_freeresult($result);
		
	} while (false);

}

require_once("include/close_db.php");

$title=_("Password change");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Input the new password")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<center>
<form method="post">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
 <td><?=_("Current password")?></td>
 <td><input type="password" name="current_password"></td>
</tr>
<tr>
 <td><?=_("New password")?></td>
 <td><input type="password" name="new_password"></td>
</tr>
<tr>
 <td><?=_("Confirm password")?></td>
 <td><input type="password" name="new_password2"></td>
</tr>
</table>
<input type="submit" name="change" value="<?=_("Change")?>">
</form>
</center>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>
</td>
</tr>
</table>
<?
require("include/template-post.php");
?>
