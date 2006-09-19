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

// Detection of the language used by the browser in order to show the page
// in the corresponding language
$sup_lang=array("es", "en"); // Supported languages by now
 
// Get the stored value, if there's one
if($_SESSION["lang"]!="")
  $lang=$_SESSION["lang"];
else // Else, let's see what the browser tells us
{
  $cli_lang=explode(",", $HTTP_ACCEPT_LANGUAGE);
  // Example: If the client tells us 'es-es', select 'es'
  // When a language matches, stop the search
  for($i=0;$i<count($cli_lang) && !isset($lang); $i++)
    for($j=0;$j<count($sup_lang); $j++)
      if(!strncmp($cli_lang[$i],$sup_lang[$j],strlen($sup_lang[$j])))
      {
        $lang=$sup_lang[$j];
        break;
      }
}

// We can change the language with GET parameters, and this decission
// takes precedence over what the browser says
if($_GET["lang"]!="")
  $lang=$_GET["lang"];

switch($lang) {
  // By default, choose spanish
  default:
  case "es":
    $_SESSION["lang"]="es";
    $locale="es_ES.UTF-8";
  break;

  case "en":
    $_SESSION["lang"]="en";
    $locale="en_EN";    
  break;
}

putenv("LANG=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain('messages', './locale');
bind_textdomain_codeset('messages','UTF-8');
textdomain('messages');
?>
