   <!-- caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   	<table border="0" cellspacing="1" cellpadding="0" width="100%">
	<tr><td bgcolor="#000000" class="titulo_minicaja">
		<font color="#FFFFFF" class="titulo_minicaja">
   		<!-- título caja -->
   		Secciones
   		<!-- fin título caja -->
   		</font>
	</td></tr>

   	<tr><td bgcolor="#FFFFFF" class="texto_minicaja">
   		<table border="0" cellspacing="0" cellpadding="10">
		<tr><td align="left">
   			<font color="#000000" class="texto_minicaja">
			<!-- texto caja -->
   			<a href="informe.php?dia=<?=$dia?>" style="font-weight: bold;">- Edición de informes</a>
			<br>
   			<a href="subirxml.php" style="font-weight: bold;">- Importación desde XML</a>    			
                        <br>
                        <a href="consulta.php?dia=<?=$dia?>" style="font-weight: bold;">- Extracción de resultados</a>
<?
if ($modo_autenticacion=="sql") {
?>
			<br>
			   <a href="perfil.php?dia=<?=$dia?>" style="font-weight: bold;">- Cambio contraseña</a>
<?
}
?>
			<br>
			   <a href="login.php?logout=1" style="font-weight: bold;">- Salir</a>
			<!-- fin texto caja -->
			</font>
		</td></tr>
		</table>
	</td></tr>
<?
if (in_array("informesadm",(array)$session_grupos)) {
?>
   <tr><td bgcolor="#FFFFFF" class="texto_minicaja">
   <table border="0" cellspacing="0" cellpadding="10"><tr><td
    align="left">
   <font
    color="#000000" class="texto_minicaja">
   <!-- texto caja -->
    <a href="bloqueo.php?dia=<?=$dia?>"
     style="font-weight: bold;">- Bloqueo de informes</a>
   <br>
    <a href="adminetiquetas.php?dia=<?=$dia?>"
     style="font-weight: bold;">- Etiquetas</a>
<?
if ($modo_autenticacion=="sql") {
?>
   <br>
    <a href="usuarios.php?dia=<?=$dia?>"
     style="font-weight: bold;">- Gestion de usuarios</a>
<?
}
?>
			<!-- fin texto caja -->
   </font></td></tr></table></td></tr>
<?
}
?>
   </table></td></tr></table>
   <!-- fin caja -->
