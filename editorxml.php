<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
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

/** editorxml.php
 *

 * This page allows for an XML file to be edited in a textarea. It allows
 * storage and validation of the changes performed. It's good to open it in
 * a different window in order to avoid disturbing.
 * 
 * Received parameters:
 *
 * file: XML file to be opened and edited
 * path: Temporary path where the file has been stored
 *
 * validbutton: Validate report button has been pressed
 * savebutton: Save button has been pressed
 * closebutton: Close window button has been pressed
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/autenticate.php");

if (empty($file) || empty($path)) header("Location: uploadxml.php");

require("include/infxml.php");
require("include/html.php");

$title = _("XML report editor");
require("include/template-pre.php");

?>

<center>

<?

$path_file = $absolute_path.$path;

unset($session_logins);
foreach ($session_users as $u=>$v) $session_logins[] = $u;

if (strstr($path, "/") || strstr($file, "/"))
{
    $valid_path = false;
    $error = _("Invalid path");
}
else $valid_path = true;

if ((!empty($path_file)) && (!empty($file)) && $valid_path)
{
    $show = false;
    $info = array();
    $dirfile = $path_file."/".$file;
    $write = true;
    $mesg = "OK";

    if (!is_dir($path_file)) $error = _("File path not found");
    elseif (!ValidName($session_logins, $file, $info))
    {
     	if (!empty($info["error"])) $error = $info["error"];
      	else $error = _("File has an invalid name");
    }
    elseif (!is_file($dirfile)) $error = _("File not recognized");
    elseif (!is_readable($dirfile)) $error = _("File can't be opened");
    else
    {
     	$write = is_writeable($dirfile);
      	$show = true;

       	if ((!empty($close_button)) && ($close_button == _("Close")))
       	{
    		// Cerrar
      	}
     	elseif ((!empty($save_button)) && ($save_button == _("Save")))
      	{
     	    if (($fp = @fopen($dirfile, "w")) && $write)
            {
	     	$bytes = @fwrite($fp, stripslashes($report));
	      	@fclose($fp);
	      	$mesg = $mesg.", ".$bytes._(" bytes correctly written.");
	    }
	    else $mesg = _("The changes couldn't be saved");
   	}
   	elseif ((!empty ($valid_button)) && ($valid_button == _("Validate")))
   	{
            $mesg = _("Validating report ").$file." ... ";
            $tmpfname = fileTemp($absolute_path, "tmp_", "_".$file);
            //$tmpfname = tempnam($absolute_path, $file."_");
            $fp = @fopen($tmpfname, "w");
            @fwrite($fp, stripslashes($report));
   	    @fclose($fp);
            //chmod($tmpfname, 0755);
            $rt = ValidateDTD($tmpfname, $mesg);
	    if ($rt == 1)
	    {  
  		$temp=_("Report valid and coherent, prepared to be saved.");
            	$ret = ValidateXMLReport($tmpfname, $info, $msg);
                if ($ret == 1) $mesg = "<font color=\"#00FF00\"><b>$temp</b></font>";
        	elseif ($ret == -1)
                {
                	$error = $msg;
                        $mesg = "<font color=\"#FF0000\"><b>"._("Error ...")." </b></font>";
                }
                else $mesg = $msg;
   	    }
	    elseif ($rt == -4)
            {
            	$error = $mesg;
              $mesg = "<font color=\"#FF0000\"><b>"._("Error ...")."</b></font>";
            }
            @unlink($tmpfname);
	}
    }
    unset ($save_button);
    unset ($valid_button);
}


if ($show)
{

    IniGeneralTable(_("XML report editor"));

?>
	<form name="editor" action="<? echo "$PHP_SELF"; ?>" enctype="multipart/form-data" method="post">
   	<table border="0" width="80%" cellpadding="2">
      	<tr>
		<td align="left" colspan="2">
			<font face="Verdana" color="#000000"><? echo "$file"; ?>
			</font>
		</td>
		<? if (!$write) { ?>
		<td align="right" colspan="1">
			<font color="#990000">ReadOnly</font>
		</td>
		<? } else { ?>
		<td align="right" colspan="1">
			<font color="#339900">ReadWrite</font>
		</td>
		<? } ?>
      	</tr>
      	<tr>
      		<td align="center" colspan="4">
         		<textarea <? if (!$write) echo "readonly"; ?> name="report" cols="70" rows="18"><?
			if (empty ($report)) @readfile($dirfile); else echo stripslashes($report); ?>
			</textarea>
         	</td>
      	</tr>
      	<tr>
        	<td align="left" colspan="1">
          		<input type="submit" name="valid_button" value=<?=_("Validate")?> />
			<? if ($write) { ?>
          		<input type="submit" name="save_button" value=<?=_("Save")?> />
			<? } ?>
         	</td>
         	<td align="right" colspan="2">
                	<input type="button" name="close_button" value=<?=_("Close")?> onclick="window.close()"/>
         	</td>
      	</tr>
	</table>

    	<input type="hidden" name="file" value="<? echo $file; ?>"/>
    	<input type="hidden" name="path" value="<? echo $path; ?>"/>

	</form>
 
<?php

    EndGeneralTable($mesg);
}

if (!empty($error)) ErrorTable($error);

?>

</center>

<?

require("include/template-post.php");

?>

