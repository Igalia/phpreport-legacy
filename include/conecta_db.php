<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andr�s G�mez Garc�a <agomez@igalia.com>
//  Enrique Oca�a Gonz�lez <eocanha@igalia.com>
//  Jos� Riguera L�pez <jriguera@igalia.com>
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

$cnx = pg_connect("host=$hostname_db 
port=$port_db user=$login_db dbname=$dbname_db password=$password_db")
 or die("La base de datos no se encuentra operativa en este momento. Por "
  ."favor, intenta la operaci�n m�s tarde.");
?>
