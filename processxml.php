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


/** processxml.php
 *
 * Here the XML report files in a directory are validated, that is,
 * checked if they are well formed, valid and coherent. For this task,
 * help functions defined at "infxml.php" and XSL files are used.
 * When report validation is finished, they are inserted into the DB.
 *
 * Received parameters:
 * 
 * filedir: directory (inside $absolute_path) where XML reports to be
 * validated or inserted are located
 *
 * validate_button: Validate reports button has been pressed.
 * save_button: Save reports button has been pressed.
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/config.php");
require_once("include/autenticate.php");

if (empty($filedir)) header("Location: uploadxml.php");
if (strstr($filedir, "/")) header("Location: uploadxml.php");

require("include/infxml.php");
require("include/html.php");

$title = _("Validate XML weekly reports");
require("include/template-pre.php");

require_once("include/prepare_calendar.php");

$xmldir = $filedir;
$filedir = $filedir."/";

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
    <td style="text-align: center; vertical-align: top;">
    <center>
<?

unset($error);
unset($session_logins);
foreach ($session_users as $u=>$v) $session_logins[] = $u;
$count_v = 0;
$count_g = 0;


IniGeneralTable(_("XML reports"), _("Process XML reports. You can edit and revalidate the reports as many times as needed in order to be finally inserted in the DB"));

if (is_dir($absolute_path.$filedir))
{
    $valid_reports = DirValidReports($session_uid, $session_groups, $session_logins, $absolute_path.$filedir, $count_valids);
}
else $count_valids = 0;

if ($count_valids > 0)
{

?>
    <form name="editor" action="<? echo "$PHP_SELF"; ?>" enctype="multipart/form-data" method="post">

    <table class="dir_table" cellpadding="4" cellspacing="1">
    <tr class="dir_title">
        <td align="center" width="40%"><?=_("Weekly report")?></td>
        <td align="center" width="20%"><?=_("Valid")?></td>
        <td align="center" width="20%"><?=_("Coherent")?></td>
        <td align="center" width="20%"><?=_("Saved")?></td>
    </tr>
<?php

    $count = 0;
    $error_sql = false;

    require_once("include/connect_db.php");

    foreach($valid_reports as $entry)
    {
      	if ($count % 2 == 0) echo "<tr class=\"dir_even\">";
     	else echo "<tr class=\"dir_odd\">";

	LinkEditorXML($xmldir, $entry);
        unset($msg);

    	if (ValidateDTD($absolute_path.$filedir.$entry, $msg) == 1)
        {
            echo "<td><font class=\"f_correct\">OK</font></td>";
	    $ret = ValidateXML($entry, $absolute_path.$filedir, $session_logins, $msg);

            if ($ret == 1)
            {
                echo "<td><font class=\"f_correct\">OK</font></td>";
    		$count_v++;

                if (!empty($save_button))
                {
                    if (!$error_sql)
                    {
                    	unset($mesg);
                    	$retg = SaveXML2SQL($cnx, $session_uid, $session_logins, $absolute_path.$filedir, $entry, $mesg);
                    	if ($retg == 1)
                        {
                            echo "<td><font class=\"f_correct\">"._("OK")."</font></td>";
    			    $count_g++;
                        }
                    	elseif ($retg == 0)
                    	{
                     	    echo "<td><font class=\"f_incorrect\">"._("XSLT Error")."</font></td>";
                            $error = $mesg;
		    	}
                    	else
                    	{
                     	    echo "<td><font class=\"f_incorrect\">"._("BD SQL Error")."</font></td>";
                            $error_sql = true;
                            $error = $mesg;
                    	}
                    }
                    else echo "<td><font class=\"f_incorrect\">"._("BD SQL Error")."</font></td>";
                }
            }
            elseif ($ret == 0)
            {
            	echo "<td><font class=\"f_incorrect\">".$msg."</font></td>";
	        echo "<td><font class=\"f_incorrect\">---</font></td>";
            }
            else
            {
                $error = $msg;
            	echo "<td><font class=\"f_incorrect\">".("XSLT Error")."</font></td>";
	        echo "<td><font class=\"f_incorrecto\">---</font></td>";
            }
  	}
	else
        {
            echo "<td><font class=\"f_incorrect\">".$msg."</font></td>";
            echo "<td><font class=\"f_incorrect\">---</font></td>";
            echo "<td><font class=\"f_incorrect\">---</font></td>";
        }
        echo "</tr>";
        $count++;
    }

?>
    </table>

    <? if ($count_v != $count_g) { ?>

    <table width="100%" border="0" cellpadding="6">
    <tr>
     	<td align="center" width="30%">
	     	<input type="submit" name="validate_button" value="Revalidate"/>
      	</td>
        <td align="left">
          <?_("Tests if the reports are well formed, valid against the DTD and coherent (overlapping tasks, incorrect days...).")?>
        </td>
    </tr>
    <tr>
      	<td align="center" width="30%">
    	  	<?
		$temp=_("Save");
                if ($count_valids > 0)
                	echo "<input type=\"submit\" name=\"save_button\" value=\"$temp\"/>";
                ?>
        </td>
        <td align="left">
                <?_("Inserts into the DB the data of correct (coherent and valid) XML reports.")?>
        </td>
    </tr>
    </table>

    <? } ?>

    <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
    </form>

<?php

}
else $error = _("It's impossible to find reports, or all the submitted ones are incorrect.");

require_once("include/close_db.php");

if (!empty($save_button))
{
    if ($count_v == $count_g)
    {
	$temp=_("All valid reports have been correctly saved");
    	$msg = "<font color=\"#00FF00\"> $temp </font>";
    }
    else $msg = "<font color=\"#FF0000\">"
    .sprintf(_('WARNING, only %1$s of %2$s reports were valid and had been inserted'),$count_g,$count_v)
    ."</font>";
    EndGeneralTable($msg);

    unset($save_button);
}
else
{

    if ($count_v == $count_valids)
    {
    	$msg = "<font color=\"#00FF00\">"._("All reports are valid and coherent. They are ready to be inserted.").".</font>";
    }
    else $msg = "<font color=\"#FF0000\">"
    .sprintf(_('WARNING, only %1$s of %2$s reports were valid and had been inserted. Edit the ones you like'),
    $count_g,$count_v)."</font>";
    EndGeneralTable($msg);
}


if (!empty($error)) ErrorTable($error);

?>
	</center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/show_sections.php"); ?>
	<br>
	<? if ($count_v == $count_g) require("include/show_calendar.php"); ?>
    </td>
</tr>
</table>

<?

require("include/template-post.php");

?>

