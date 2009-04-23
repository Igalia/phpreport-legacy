<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2009 Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Andrés Maneiro Boga <amaneiro@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  Jacobo Aragunde Pérez <jaragunde@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  María Fernández Rodríguez <maria.fdez.rguez@gmail.com>
//  Mario Sánchez Prada <msanchez@igalia.com>
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


/** html.php
 *
 * Html needed help functions
 *
 */


// Creates an error table with the received message

function ErrorTable ($error)
{
    ?>
    <br>
    <table border="0" width="85%" cellpadding="4" cellspacing="1" bgcolor="#000000">
    <tr>
        <th class="error"><?=_("Error")?></th>
    </tr>
    <tr>
        <td align="left" class="content_error">
            <?
            if (!empty($error)) echo "$error";
            else echo _("Error undefined, contact with the administrator, ;-).");
            ?>
        </td>
    </tr>
    </table>
    <br>
    <?
}


// Create a 3 row table (and the function that closes it) to show information
// in a general way. It receives the table title and the documentation of what
// it does.

function IniGeneralTable ($title)
{
    ?>
    <table border="0" width="85%" cellspacing="1" bgcolor="#000000">
    <tr>
	<!-- <th class="title"> -->
        <td class="title_box">
        <? echo $title; ?>
	   </th>
    </tr>

    <? if (func_num_args() == 2) { ?>

    <tr>
	   <td class="info">
	   <? echo func_get_arg(1); ?>
	   </td>
    </tr>

    <? } ?>

    <tr>
       	   <!-- <td align="center" class="content"> -->
           <td align="center" class="text_box_xml">
    <?
}


// Close the table created by the previous function. Optionally,
// it receives the footer (similar to page footer, it corresponds
// with the 3rd row)

function EndGeneralTable ()
{
    if (func_num_args() == 1)
    {
        ?>
           </td>
        </tr>
        <tr>
	       <td class="foot"><?
        echo func_get_arg(0);
    }
    ?>
        </td>
    </tr>
    </table>
    <br>
    <?
}



// Create a link for being opened in a new window to edit the file

function LinkEditorXML ($xmldir, $entry)
{
    // *** REVIEW REPORT EDITOR
    echo "\t<td align=\"left\"><a href=\"editorxml.php?file=".urlencode($entry)."&path=".
    urlencode($xmldir)."\" onclick=\"javascript:window.open(this.href,'"
    ._("XML report editor")."',".
    "'width=800,height=600,location=no, menubar=no,status=no,toolbar=no,scrollbars=yes,".
    "resizable=yes'); return false;\">".$entry."</a></td>";
}


?>
