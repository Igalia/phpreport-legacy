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


// NOTE: THIS IS A TEMPLATE FOR DEVELOPING NEW PAGES
// NOTHING TO DO HERE...
exit();

require("include/util.php");
?>
<html>
<head>
<link rel=StyleSheet href="css/styles.css" type="text/css">
<title>
PhpReport
</title>
</head>
<body bgcolor="#FFFFFF">
<table border="0" cellspacing="0" cellpadding="0" width="100%">
 <tr>
  <td align="left"><img src="images/title.gif"></td>
  <td align="right" class="title"><?=_("Report input")?></td>
 </tr>
</table>
<? msg_fail(_("The operation couldn't be completed")) ?>
<? msg_ok(_("Changes were made correctly")) ?>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Box title")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<?=_("Box text")?>
<br>
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum lobortis quam in sapien. Praesent nonummy sagittis sem. Cras sodales erat vel purus. In non sem at justo elementum consectetuer. Sed augue lacus, pretium eget, vehicula non, consectetuer congue, nulla. Curabitur pharetra sapien et sapien. Nam a lectus ac sem viverra aliquam. Maecenas pellentesque sem vel dolor. Integer tempor ante. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In imperdiet elit sed sem. Donec tristique egestas leo. Curabitur laoreet nulla vel diam. Curabitur vitae pede nec est consequat varius. Nulla in risus non tellus tristique pharetra. Cras lobortis, est sit amet luctus lacinia, augue ante hendrerit dolor, in molestie lorem nisi nec tortor.")
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

</body>
</html>
