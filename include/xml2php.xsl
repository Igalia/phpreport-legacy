<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


<!-- xml2php.xsl: Este fichero define todas las sentencias PHP necesarias -->
<!-- para introducir los datos de un informe semanal en XML en la BD.     -->
<!-- Para ello es necesario tener iniciada una sesion en la BD definida   -->
<!-- en la variable "$cnx" junto con la variable "$session_uid" la cual   -->
<!-- identifica al usuario que introduce los datos.			  -->
<!-- Esta hoja de estilo necesita recibir 6 parametros: el año inicial,   -->
<!-- el mes inicial, el dia inicial y el año final, el mes final y el dia -->
<!-- final, es decir, las fechas (semana) que abarca el informe (estan    -->
<!-- en el nombre del informe XML). Este XSL no verifica si los datos son -->
<!-- coherentes, y si se produce algun error durante la interpretacion    -->
<!-- del codigo php generado, aparecera definida una variable "$error"    -->
<!-- con el correspondiente mensaje.                                      -->

<!-- Antes de introducir las tareas del informe en la BD, se borran todas -->
<!-- las que existan en la BD para ese periodo.                           -->

<!-- Esto esta diseñado para su uso con la directiva "eval" de PHP, la    -->
<!-- cual evalua el codigo PHP que contiene una variable.                 -->

<!-- xml2php.xsl,  José Riguera, 2003 <jriguera@igalia.com>               -->



<xsl:output method="text" encoding="ISO-8859-1"/>

<xsl:param name="Iusuario" select="0"/>

<xsl:param name="Ianoini" select="0"/>
<xsl:param name="Imesini" select="0"/>
<xsl:param name="Idiaini" select="0"/>

<xsl:param name="Ianofin" select="3000"/>
<xsl:param name="Imesfin" select="12"/>
<xsl:param name="Idiafin" select="31"/>



<xsl:template name="Programa" match="/">

	<xsl:text>
	
	/* INSERCION DE TODAS LAS TAREAS DE UN INFORME SEMANAL */
	
        $num_tareas = 0;
        $num_dias = 0;
        
        if (empty($session_uid)) $error = "sin sesión o sesión desconocida.";
        if (empty($cnx)) $error = "no hay sesión iniciada en la BD.";
        
        $usuario = "</xsl:text><xsl:value-of select="$Iusuario"/><xsl:text>";
        
        if (empty($usuario)) $error = "sin sesión o sesión desconocida.";
        
 	/* GESTION DE BLOQUEOS */

	if (empty($error))
        {        
          	/* TRANSACCION DE ALMACENAMIENTO DE INFORME Y TAREAS */

		$query = "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; BEGIN TRANSACTION; ";
  		if (!@pg_exec($cnx, $query)) 
                {
   			$error = "imposible comenzar transacción.";
  		}
                else
                {
			$query = "SELECT fecha FROM bloqueo WHERE uid='$usuario'";
			if (!$result = @pg_exec($cnx, $query)) $error = "imposible determinar fecha de bloqueo.";
	    		else
	    		{
	   			if (@pg_numrows($result) == 0)
	   			{
	    				/* ESTABLECER BLOQUEO POR DEFECTO */
	    				@pg_freeresult($result);
	    				$query = "INSERT INTO bloqueo (uid,fecha) VALUES ('$usuario', '1999-12-31')";
	    				if (!$result = @pg_exec($cnx, $query)) $error = "imposible establecer fecha de bloqueo por defecto.";
	    				else @pg_freeresult($result);
	   			}
	   			else
	   			{
	   				$bloqueado = false;
					if ($row = @pg_fetch_row($result)) $bloqueado = !('</xsl:text><xsl:call-template name="FechaInf2Sql"/><xsl:text>' > $row[0]);
					@pg_freeresult($result);
					if ($bloqueado) $error = "la introducción de informes está bloqueada para fechas anteriores a ".$row[0]." .";
	   			}
			}
                        
                        /* ANTES HAY QUE BORRAR LAS POSIBLES TAREAS YA INTRODUCIDAS */
                        
			if (empty($error))
                        {
				$query = "DELETE FROM tarea WHERE uid='$usuario' AND fecha BETWEEN DATE('</xsl:text>
				<xsl:call-template name="Fecha2Sql">
					<xsl:with-param name="dia" select="$Idiaini"/>
					<xsl:with-param name="mes" select="$Imesini"/>
					<xsl:with-param name="ano" select="$Ianoini"/>
				</xsl:call-template><xsl:text>') AND DATE('</xsl:text>
				<xsl:call-template name="Fecha2Sql">
					<xsl:with-param name="dia" select="$Idiafin"/>
					<xsl:with-param name="mes" select="$Imesfin"/>
					<xsl:with-param name="ano" select="$Ianofin"/>
				</xsl:call-template><xsl:text>')";
                                
	    			if (!$result = @pg_exec($cnx, $query)) $error = "imposible borrar tareas previas ...";
	    			else @pg_freeresult($result);
                        }                                               
                        
                        /* Obtiene la tabla de traduccion de entidades HTML a sus simbolos */
                        $trans_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);                                                
                        $trans_table = array_flip($trans_table);
                        $trans_table["&amp;apos;"] = "'"; 
                        $trans_table["&amp;divide;"] = "\n"; 
                        
			/* INSERCION DE TODAS LAS TAREAS DEL INFORME */
		
	</xsl:text>

	<xsl:variable name="mesesinforme" select="count(dedicacionSemanal/dedicacion)"/>

	<xsl:variable name="abarcameses">
  		<xsl:choose>
    			<xsl:when test="$Imesfin = $Imesini">
  				<xsl:value-of select="1"/>
      			</xsl:when>
    			<xsl:otherwise>
				<xsl:value-of select="2"/>
      			</xsl:otherwise>
    		</xsl:choose>
  	</xsl:variable>

	<xsl:choose>
		<xsl:when test="$mesesinforme = 2">

  			<xsl:apply-templates select="dedicacionSemanal/dedicacion[1]">
				<xsl:with-param name="ano" select="$Ianoini"/>
  			</xsl:apply-templates>
  
  			<xsl:apply-templates select="dedicacionSemanal/dedicacion[2]">
				<xsl:with-param name="ano" select="$Ianofin"/>
  			</xsl:apply-templates>
    
    		</xsl:when>
		<xsl:when test="$mesesinforme = 1"> 
    			<xsl:choose>
    				<xsl:when test="$mesesinforme != $abarcameses">
      
					<xsl:variable name="numeromes">
						<xsl:call-template name="Mes2numero">
							<xsl:with-param name="mes" select="dedicacionSemanal/dedicacion[1]/@mes"/>
						</xsl:call-template>
					</xsl:variable>
          
 					<xsl:choose>
	 					<xsl:when test="$numeromes = $Imesini">
  	 					<xsl:apply-templates select="dedicacionSemanal/dedicacion[1]">
    							<xsl:with-param name="ano" select="$Ianoini"/>
      						</xsl:apply-templates>
						</xsl:when>
						<xsl:otherwise>
						 	<xsl:apply-templates select="dedicacionSemanal/dedicacion[1]">
								<xsl:with-param name="ano" select="$Ianofin"/>
							</xsl:apply-templates>
 						</xsl:otherwise>
 					</xsl:choose>
          
				</xsl:when>
    				<xsl:otherwise>
        
	  				<xsl:apply-templates select="dedicacionSemanal/dedicacion[1]">
						<xsl:with-param name="ano" select="$Ianoini"/>
  					</xsl:apply-templates>
          
      				</xsl:otherwise>
    			</xsl:choose>
    		</xsl:when>                           
		<xsl:otherwise>
    
			<xsl:text>
      
  			$error = "informe incorrecto";
        
      			</xsl:text>
      
    		</xsl:otherwise>
	</xsl:choose>

	<xsl:text>

  			if (empty($error)) $query = "COMMIT TRANSACTION";
  			else $query = "ROLLBACK TRANSACTION";
  
   			if (!@pg_exec($cnx,$query)) $error = "imposible cerrar transacción.";
		}
		/* FIN INSERCION TAREAS */
        }
	
	</xsl:text>

</xsl:template>



<xsl:template name="InsertarTarea" match="dedicacion">
	<xsl:param name="ano"/>

	<xsl:variable name="nummes">
		<xsl:call-template name="Mes2numero">
			<xsl:with-param name="mes" select="@mes"/>
		</xsl:call-template>
	</xsl:variable>

	<xsl:for-each select="dedicacionDiaria">
		<xsl:variable name="dia" select="@dia"/>

               	<xsl:text>

		$num_dias++;
                $nuevo_dia = true;
                        
                </xsl:text>


		<xsl:for-each select="tarea">
		                
			<xsl:variable name="nombre">
				<xsl:choose>
					<xsl:when test="@nombre">
						<xsl:value-of select="@nombre"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="fase">
				<xsl:choose>
					<xsl:when test="@fase">
						<xsl:value-of select="@fase"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="ttipo">
				<xsl:choose>
					<xsl:when test="@ttipo">
						<xsl:value-of select="@ttipo"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<xsl:text>
			
			if (empty($error))
			{
				/* INSERCION DE UNA TAREA */

				$query = "SELECT fecha_modificacion FROM informe WHERE uid='$usuario' AND fecha='</xsl:text>
				<xsl:call-template name="Fecha2Sql">
					<xsl:with-param name="dia" select="$dia"/>
					<xsl:with-param name="mes" select="$nummes"/>
					<xsl:with-param name="ano" select="$ano"/>
				</xsl:call-template><xsl:text>'";
			
				if (!$result = @pg_exec($cnx, $query)) $error = "imposible determinar la última fecha de modificación.";
				else
				{
  			 		if (@pg_numrows($result) > 0)
   					{
   						@pg_freeresult($result);
						$query = "UPDATE informe SET fecha_modificacion=now() WHERE uid='$usuario' AND fecha='</xsl:text>
						<xsl:call-template name="Fecha2Sql">
							<xsl:with-param name="dia" select="$dia"/>
							<xsl:with-param name="mes" select="$nummes"/>
							<xsl:with-param name="ano" select="$ano"/>
						</xsl:call-template><xsl:text>'";
					
  		  				if (!$result = @pg_exec($cnx, $query)) $error = "imposible actualizar la última fecha de modificación.";
   					}
   					else
   					{
   						@pg_freeresult($result);
  						$query = "INSERT INTO informe (uid,fecha,fecha_modificacion) VALUES ('$usuario','</xsl:text>
						<xsl:call-template name="Fecha2Sql">
							<xsl:with-param name="dia" select="$dia"/>
							<xsl:with-param name="mes" select="$nummes"/>
							<xsl:with-param name="ano" select="$ano"/>
						</xsl:call-template><xsl:text>',now())";
					
						if (!$result = @pg_exec($cnx, $query)) $error = "imposible establecer fecha de modificación.";
    					}
				}
			
				if (empty($error))
				{                                
                                        /*
                                        // Si se activa esto, se permiten insertar informes complementarios en la
                                        // BD, pero esto no es lo deseado, ...
                                        
                                	if ($nuevo_dia)
                                        {
						$query = "DELETE FROM tarea WHERE uid='$usuario' AND fecha='</xsl:text>
                                        	<xsl:call-template name="Fecha2Sql">
					    		<xsl:with-param name="dia" select="$dia"/>
					    		<xsl:with-param name="mes" select="$nummes"/>
					      		<xsl:with-param name="ano" select="$ano"/>
						</xsl:call-template><xsl:text>'";

  						if (!@pg_exec($cnx, $query)) 
                                        	{
                                        		$error = "imposible realizar preinserción de datos en la BD.";
                                        	}
                                                $nuevo_dia = false;
                                        }
                                        */
                                        
  					if (empty($error))
  					{                                        
                                        	$texto_tarea = "</xsl:text><xsl:apply-templates select="."/><xsl:text>";
                                                
                                                $texto_tarea =  addslashes(strtr($texto_tarea, $trans_table));
                                        
						$query = "INSERT INTO tarea (uid,fecha,inicio,fin,nombre,tipo,fase,story,ttipo,texto) VALUES ('$usuario','</xsl:text>
						<xsl:call-template name="Fecha2Sql">
							<xsl:with-param name="dia" select="$dia"/>
							<xsl:with-param name="mes" select="$nummes"/>
							<xsl:with-param name="ano" select="$ano"/>
						</xsl:call-template><xsl:text>', '</xsl:text>
						<xsl:call-template name="Hora2Sqlm">
							<xsl:with-param name="hora" select="@inicio"/>
						</xsl:call-template><xsl:text>', '</xsl:text>
						<xsl:call-template name="Hora2Sql24m">
							<xsl:with-param name="hora" select="@fin"/>
						</xsl:call-template><xsl:text>', '</xsl:text>
						<xsl:value-of select="$nombre"/><xsl:text>', lower('</xsl:text>
						<xsl:value-of select="@tipo"/><xsl:text>'), '</xsl:text>
						<xsl:value-of select="$fase"/><xsl:text>', '</xsl:text>
						<xsl:value-of select="@story"/><xsl:text>', '</xsl:text>
						<xsl:value-of select="$ttipo"/><xsl:text>', '$texto_tarea')";
     
   				 		if (!@pg_exec($cnx, $query)) $error = "imposible insertar valores en la BD.";
                                                else $num_tareas++; 
					}
				}
				
				/* FIN INSERCCION TAREA	*/
			}
			
			</xsl:text>
		</xsl:for-each>
		
	</xsl:for-each>
</xsl:template>



<xsl:template name="FechaInf2Sql">
	<xsl:value-of select="$Ianoini"/><xsl:text>-</xsl:text>

	<xsl:variable name="len1">
		<xsl:value-of select="string-length($Imesini)" />
	</xsl:variable>
        <xsl:if test="2 != $len1">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$Imesini"/><xsl:text>-</xsl:text>

	<xsl:variable name="len2">
		<xsl:value-of select="string-length($Idiaini)" />
	</xsl:variable>
        <xsl:if test="2 != $len2">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$Idiaini"/>
</xsl:template>



<xsl:template name="Fecha2Sql">
	<xsl:param name="dia"/>
	<xsl:param name="mes"/>
	<xsl:param name="ano"/>

	<xsl:value-of select="$ano"/><xsl:text>-</xsl:text>
	<xsl:variable name="len1">
		<xsl:value-of select="string-length($mes)" />
	</xsl:variable>
        <xsl:if test="2 != $len1">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$mes"/><xsl:text>-</xsl:text>
	<xsl:variable name="len2">
		<xsl:value-of select="string-length($dia)" />
	</xsl:variable>
        <xsl:if test="2 != $len2">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$dia"/>
</xsl:template>



<xsl:template name="Hora2Sql">
	<xsl:param name="hora"/>

	<xsl:variable name="m" select="$hora mod 100"/>
	<xsl:variable name="h" select="($hora - $m) div 100"/>

        <xsl:if test="10 > $h">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$h"/>
	<xsl:text>:</xsl:text>
        <xsl:if test="10 > $m">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$m"/>
	<xsl:text>:00</xsl:text>
</xsl:template>



<xsl:template name="Hora2Sqlm">
	<xsl:param name="hora"/>

	<xsl:variable name="m" select="$hora mod 100"/>
	<xsl:variable name="h" select="($hora - $m) div 100"/>

	<xsl:value-of select="($h * 60) + $m"/>
</xsl:template>



<xsl:template name="Hora2Sql24m">
	<xsl:param name="hora"/>

	<xsl:variable name="m" select="$hora mod 100"/>
	<xsl:variable name="h" select="($hora - $m) div 100"/>
        
        
      	<xsl:choose>
      		<xsl:when test="($m = $h) and (0 = $m)">
                	<xsl:value-of select="24 * 60"/>
		</xsl:when>
		<xsl:otherwise>
                	<xsl:value-of select="($h * 60) + $m"/>
     		</xsl:otherwise>
     	</xsl:choose>
</xsl:template>



<xsl:template name="Mes2numero">
	<xsl:param name="mes"/>

	<xsl:variable name="mesminuscula">
		<xsl:value-of select="translate($mes,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="'enero' = $mesminuscula">
			<xsl:text>1</xsl:text>
		</xsl:when>
		<xsl:when test="'febrero' = $mesminuscula">
			<xsl:text>2</xsl:text>
		</xsl:when>
		<xsl:when test="'marzo' = $mesminuscula">
			<xsl:text>3</xsl:text>
		</xsl:when>
		<xsl:when test="'abril' = $mesminuscula">
			<xsl:text>4</xsl:text>
		</xsl:when>
		<xsl:when test="'mayo' = $mesminuscula">
			<xsl:text>5</xsl:text>
		</xsl:when>
		<xsl:when test="'junio' = $mesminuscula">
			<xsl:text>6</xsl:text>
		</xsl:when>
		<xsl:when test="'julio' = $mesminuscula">
			<xsl:text>7</xsl:text>
		</xsl:when>
		<xsl:when test="'agosto' = $mesminuscula">
			<xsl:text>8</xsl:text>
		</xsl:when>
		<xsl:when test="'septiembre' = $mesminuscula">
			<xsl:text>9</xsl:text>
		</xsl:when>
		<xsl:when test="'octubre' = $mesminuscula">
			<xsl:text>10</xsl:text>
		</xsl:when>
		<xsl:when test="'noviembre' = $mesminuscula">
			<xsl:text>11</xsl:text>
		</xsl:when>
		<xsl:when test="'diciembre' = $mesminuscula">
			<xsl:text>12</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>-1</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>



<xsl:template match="tarea">

	<xsl:variable name="txt">
	  	<xsl:call-template name="escape">
	    		<xsl:with-param name="arg" select="text()"/>
	  	</xsl:call-template>
	</xsl:variable>

<!--	<xsl:variable name="txtreplace">
		<xsl:call-template name="chop">	
			<xsl:with-param name="texto" select="$txt" />
		</xsl:call-template>
	</xsl:variable>
-->
	<xsl:value-of select="$txt" /> 
	
</xsl:template>



<xsl:template name="chop">
	<xsl:param name="texto"/>

	<xsl:variable name="len">
		<xsl:value-of select="string-length($texto)" />
	</xsl:variable>

	<xsl:variable name="fuera">
		<xsl:value-of select="substring($texto, $len)" />
	</xsl:variable>

	<xsl:call-template name="escape-one">
        	<xsl:with-param name="arg" select="$texto"/>
             	<xsl:with-param name="target" select="$fuera"/>
             	<xsl:with-param name="replace" select="'&amp;divide;'"/>
        </xsl:call-template>

</xsl:template>



<!-- Sacado y corregido de http://www.tackline.demon.co.uk/xslt/escape/xslt/escape.xsl -->

<xsl:template name="escape">
  <xsl:param name="arg"/>
  <xsl:call-template name="escape-one">
    <xsl:with-param name="arg">
      <xsl:call-template name="escape-one">
        <xsl:with-param name="arg">
          <xsl:call-template name="escape-one">
            <xsl:with-param name="arg">
              <xsl:call-template name="escape-one">
                <xsl:with-param name="arg">
                  <xsl:call-template name="escape-one">
                    <xsl:with-param name="arg" select="$arg"/>
                    <xsl:with-param name="target" select="'&amp;'"/>
                    <xsl:with-param name="replace" select="'&amp;amp;'"/>
                  </xsl:call-template>
                </xsl:with-param>
                <xsl:with-param name="target" select="'&lt;'"/>
                <xsl:with-param name="replace" select="'&amp;lt;'"/>
              </xsl:call-template>
            </xsl:with-param>
            <xsl:with-param name="target" select="'&gt;'"/>
            <xsl:with-param name="replace" select="'&amp;gt;'"/>
          </xsl:call-template>
        </xsl:with-param>
        <xsl:with-param name="target" select="'&quot;'"/>
        <xsl:with-param name="replace" select="'&amp;quot;'"/>
      </xsl:call-template>
    </xsl:with-param>
    <xsl:with-param name="target" select='"&apos;"'/>
    <xsl:with-param name="replace" select='"&amp;apos;"'/>
  </xsl:call-template>
</xsl:template>



<xsl:template name="escape-one">
	<xsl:param name="arg"/>
  	<xsl:param name="target"/>
  	<xsl:param name="replace"/>

  	<xsl:choose>
    		<xsl:when test="contains($arg, $target)">
      			<xsl:variable name="before" select="substring-before($arg, $target)"/>
      			<xsl:variable name="after" select="substring-after($arg, $target)"/>

      			<xsl:value-of select="$before"/>
      			<xsl:value-of select="$replace"/>   

      			<xsl:call-template name="escape-one">
			        <xsl:with-param name="arg" select="$after"/>
			        <xsl:with-param name="target" select="$target"/>
			        <xsl:with-param name="replace" select="$replace"/>
		      </xsl:call-template>
	    	</xsl:when>
	    	<xsl:otherwise>
		      <xsl:value-of select="$arg"/>
    		</xsl:otherwise>
  	</xsl:choose>
</xsl:template>



</xsl:stylesheet>

