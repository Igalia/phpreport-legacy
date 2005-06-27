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


/** html.php
 *
 * Funciones de apoyo necesarias para el html
 *
 */


// Crea una tabla de error con el mensaje que recibe

function TablaError ($error)
{
    ?>
    <br>
    <table border="0" width="85%" cellpadding="4" cellspacing="1" bgcolor="#000000">
    <tr>
        <th class="error">Error</th>
    </tr>
    <tr>
        <td align="left" class="contenido_error">
            <?
            if (!empty($error)) echo "$error";
            else echo "Error no definido, contacte con el administrador, ;-).";
            ?>
        </td>
    </tr>
    </table>
    <br>
    <?
}



// Crea una tabla con tres filas (junto con la funcion que la cierra) para
// presentar la informacion de forma general. Recibe el titulo que va a tener
// la tabla y la documentacion de lo que hace.

function IniTablaGeneral ($titulo)
{
    ?>
    <table border="0" width="85%" cellspacing="1" bgcolor="#000000">
    <tr>
	<!-- <th class="titulo"> -->
        <td class="titulo_caja">
        <? echo $titulo; ?>
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
       	   <!-- <td align="center" class="contenido"> -->
           <td align="center" class="texto_caja_xml">
    <?
}



// Cierra la tabla que la funcion anterior creo, recibe opcionalmente el
// pie (parecido al pie de pagina, se corresponde con la tercera fila)

function FinTablaGeneral ()
{
    if (func_num_args() == 1)
    {
        ?>
           </td>
        </tr>
        <tr>
	       <td class="pie"><?
        echo func_get_arg(0);
    }
    ?>
        </td>
    </tr>
    </table>
    <br>
    <?
}



// crea un enlace que se abrira en una nueva ventana para editar el fichero

function LinkEditorXML ($xmldir, $entrada)
{
    echo "\t<td align=\"left\"><a href=\"editorxml.php?fichero=".urlencode($entrada)."&ruta=".
    urlencode($xmldir)."\" onclick=\"javascript:window.open(this.href,'Editor de informes XML',".
    "'width=800,height=600,location=no, menubar=no,status=no,toolbar=no,scrollbars=yes,".
    "resizable=yes'); return false;\">".$entrada."</a></td>";
}


?>

