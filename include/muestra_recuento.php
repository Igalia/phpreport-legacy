   <!-- caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   	<table border="0" cellspacing="1" cellpadding="0" width="100%">
	<tr><td bgcolor="#000000" class="titulo_minicaja">
		<font color="#FFFFFF" class="titulo_minicaja">
   		<!-- título caja -->
   		Recuento de horas
   		<!-- fin título caja -->
   		</font>
	</td></tr>

   	<tr><td bgcolor="#FFFFFF" class="texto_minicaja">
   		<table border="0" cellspacing="0" cellpadding="10">
		<tr><td align="left">
   			<font color="#000000" class="texto_minicaja">
			<!-- texto caja -->
			Hoy: <?=$minutos_diarios/60?>h
			(<?=hora_sql_to_web($minutos_diarios) ?>)
			<br>
			Semana: <?=$minutos_semanales/60?>h
			(<?=hora_sql_to_web($minutos_semanales) ?>)
			<!-- fin texto caja -->
			</font>
		</td></tr>
		</table>
	</td></tr>
   </table></td></tr></table>
   <!-- fin caja -->
