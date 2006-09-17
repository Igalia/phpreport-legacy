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


/** subirxml.php
 *
 * $reports XML user files are uploaded and uncompressed.
 * A unique temporal directory is created (in $absolute_path) and
 * all uploaded files are copied there.
 *
 * Received parameters:
 *
 * 
 * filedir: directory (inside $absolute_path) where XML $reports 
 * that are going to be validated/saved are located.
 * 
 * process_button: The button to process the $reports has been pressed
 * send_button: The button to upload the report has been pressed
 * name_button: The button to rename a file has been pressed
 * new_name: New file name
 * name_rename: File to rename
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/config.php");
require_once("include/autenticate.php");

require("include/infxml.php");
require("include/html.php");

//$cmd = "rm -rf $absolute_path";
//`$cmd`;
if (!is_dir($absolute_path)) mkdir($absolute_path, 0777);

$title = _('Weekly report input');

require("include/template-pre.php");

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
    <td style="text-align: center; vertical-align: top;">
    <center>

<?

$session_logins = array();
foreach ($session_users as $u=>$v) $session_logins[] = $u;

if (!empty($send_button) && ($send_button == _("Send")))
{
    if (empty($filedir))
    {
    	if (strlen($file_name) <= 4) $error = _("Invalid filename.");
    	elseif (!ereg('^weeklyreport([a-zA-Z0-9_-]+)\.xml$|([a-zA-Z0-9_-]+)\.tgz$', $file_name))
    	{
      $error = _("File format is invalid. Only XML reports or TGZ compressed groups of them are allowed. Examples: weeklyreport-john-2006-09-11-2006-09-17.xml, weeklyreport-john-2006-08-28-2006-10-01.tgz");
    	}
    	elseif ($file_size > $file_limit)
    	{
            $error = sprintf(_('Selected file exceeds %1$s bytes. It\'s rejected due to security reasons.'),$file_limit);
    	}
    	else
    	{
       	    // Generates a unique directory name
            do
            {
            	$seed = substr(md5(microtime().posix_getpid()), 0, 8);
            	$filedir = $file_name."_".$seed."_dir";
            	$filename = $absolute_path.$filedir;
            }
            while (is_dir($filename));

            mkdir($filename,0777);
            $xmldir = $filedir;
            $filedir .="/";
            $filename = $absolute_path.$filedir.$file_name;

            if (file_exists($filename)) @unlink($filename);
            if (!@move_uploaded_file($file, $filename))
            {
             	$error = _("Server error while processing the file. Try again later or get in touch with your administrador.");
            }
            else
            {
              	if (ereg('\.tgz$', $file_name))
              	{
              	    $ar = array();
                    $retval = 0;
                    $cmd = exec("tar zxvf ".EscapeShellCmd($filename)." -C ".
                    	   EscapeShellCmd($absolute_path.$filedir), $ar, $retval);
		    unset($cmd);
		    unset($ar);
		    if ($retval != 0) $error = _("Error uncompressing archive. Impossible to continue.");
		    unlink($filename);
            	}
	    }
	}
    }
    else
    {
        $xmldir = $filedir;
        $filedir .="/";
    }

    if (empty($error))
    {
	if (!empty($name_button) || ($name_button == _("Give a name")))
	{
    	    if (!(strstr($new_name, "/") || strstr($name_rename, "/")))
    	    {
                if (($name_rename != "empty") && (strlen($new_name) > 3))
                    @rename($absolute_path.$filedir.$name_rename, $absolute_path.$filedir.EscapeShellCmd($new_name));
    	    }
            unset($name_button);
	}
    	$dh = @opendir($absolute_path.$filedir);
	$files = array();
	while ($entry = @readdir($dh))
	{
		// Search only archives at the directory
		if (($entry != ".") && ($entry != "..")) $files[] = $entry;
	}
	@closedir($dh);
	usort($files, 'strnatcmp');

  IniGeneralTable(_("XML Reports"), _("Content uploaded to the server. Edit only incorrect files (incorrect name format, incorrect date, etc...)."));
?>

    <form name="editor" action="processxml.php" enctype="multipart/form-data" method="post">
    <table class="dir_table" cellpadding="4" cellspacing="1">
    <tr class="dir_title">
        <td align="center" width="70%"><?=_("Weekly report name")?></td>
        <td align="center" width="20%"><?=_("Size")?></td>
        <td align="center" width="10%"><?=_("Filter")?></td>
    </tr>

<?php
	    // Displays the information
     	$count = 0;
     	$count_valids = 0;
     	$size_cum = 0;
     	$fields = array();
        $reports_valids = array();
        $reports_possibles = array();
     	foreach($files as $entry)
     	{
            if ($count % 2 == 0) echo "<tr class=\"dir_even\">";
            else echo "<tr class=\"dir_odd\">";
         
            $size = filesize($absolute_path.$filedir.$entry);
            $ret = ValidName($session_logins, $entry, $fields);
            if ($ret == 1)
            {
              	if (in_array($admin_group_name,(array)$session_groups))
                {
		    LinkEditorXML($xmldir, $entry);
                    echo "\t<td>".$size."</td>";
                    echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                    $count_valids++;
                    $reports_valids[] = $entry;
             	}
             	else
             	{
	            if ($session_uid == $fields["user"])
                    {
		    	LinkEditorXML($xmldir, $entry);
                     	echo "\t<td>".$size."</td>";
                      	echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                      	$count_valids++;
	                $reports_valids[] = $entry;
                    }
                    else
                    {
		    	//LinkEditorXML($xmldir, $entry);
                        echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
                      	echo "\t<td>".$size."</td>";
                      	echo "\t<td><font color=\"red\" face=\"courier\"><b>NP</b></font></td>";
                    }
             	}
                $reports_possibles[] = $entry;
            }
            elseif ($ret == -1)
            {
                echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
              	echo "\t<td>".$size."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>IN</b></font></td>";
                if (ereg('\.xml$', $entry)) $reports_possibles[] = $entry;
            }
            elseif ($ret == 0)
            {
       	    	//LinkEditorXML($xmldir, $entry);
                echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
               	echo "\t<td>".$size."</td>";
               	echo "\t<td><font color=\"red\" face=\"courier\"><b>UD</b></font></td>";
                $reports_possibles[] = $entry;
            }
            else
            {
       	    	//LinkEditorXML($xmldir, $entry);
                echo "\t<td><font color=\"red\" face=\"courier\">".$entry."</font></td>";
              	echo "\t<td>".$size."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>FI</b></font></td>";
                $reports_possibles[] = $entry;
            }
            $size_cum += $size;
            $count++;
            echo "</tr>";
     	}
     	$count--;
?>

     </table>

     <table width="100%" border="0" cellpadding="5">
     <tr>
     	<td align="center">
      <? if ($count_valids > 0) echo "<input type=\"submit\" name=\"process_button\" value=\"".
        _("Process")."\"/>"; ?>
        </td>
        <td align="right">
	       	<table>
            	<tr><td>
			<font color="red"><b>FI</b></font> : <?=_("Incorrect date. The report doesn't correspond to a week.")?> <br>
			<font color="red"><b>IN</b></font> : <?=_("Incorrect. File format isn't recognized for that file name.")?> <br>
			<font color="red"><b>UD</b></font> : <?=_("User unknown. The user doesn't exist.")?> <br>
			<font color="red"><b>NP</b></font> : <?=_("You aren't the owner of the weekly report.")?> <br>
			<font color="blue"><b>OK</b></font> : <?=_("Report ready to be validated, verified and processed.")?> <br>
                </td></tr>
                </table>
        </td>
     </tr>
     </table>

     <input type="hidden" name="send_button" value="<? echo $send_button; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

     <form name="rename" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">
     <table width="100%" border="0" cellpadding="5">
     <tr>
        <td align="left">
       		<select name="name_rename" size="1">
       			<option value="empty"> ... </option>
                	<?
     			foreach($reports_possibles as $entry) echo "<option value=".$entry.">".$entry."</option>";
			?>
       		</select>
        </td>
     	<td align="center">
        	<input type="submit" name="name_button" value="<?=_("Rename")?>"/>
        </td>
        <td align="right">
        	<input type="text" name="new_name">
        </td>
     </tr>
     </table>

     <input type="hidden" name="send_button" value="<? echo $send_button; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

<?php

      EndGeneralTable(sprintf(_('%1$s correct filenames and %2$s incorrect.'),$count_valids,$count));
    }
    else unset($send_button);
}

if (empty($send_button) || ($send_button != _("Send")))
{
   IniGeneralTable(_("Collect reports in XML format"), _("Select an XML report or a set of them belonging the same user and compressed in tgz format, and press Send when ready.<br>Remember to use always the <a href=\"rules.dtd.php\">updated <b>rules.dtd</b></a>."));
?>

	<form name="upload" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">

	<table border="0" cellpadding="4" align="center">
   	<tr>
		<td align="left" width="30%">
		<?=_("Local file")?>
		</td>
      		<td align="left" width="70%">
      			<input type="file" name="file" size="50"/>
      		</td>
   	</tr>
   	<!-- <tr>
		<td align="left" width="30%">
		 <?=_("Description or complementary information.")?>
		</td>
      		<td align="left" width="70%">
      		<textarea name="description" cols="50" rows="3"><? echo $description; ?></textarea>
        </td>
   	</tr> -->
   	<tr>
    		<td align="left" width="100%" colspan="2">
        	<input type="submit" name="send_button" value=<?=_("Send")?> />
      		</td>
   	</tr>
   	</table>

   </form>
<?

     EndGeneralTable();
}

if (!empty($error)) ErrorTable($error);

?>

    </center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/show_sections.php") ?>
    </td>

</tr>
</table>

<?

require("include/template-post.php");

?>
