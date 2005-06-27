<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


<!-- xmlcoherente.xsl: Este XSL realiza una serie de comprobaciones sobre -->
<!-- un informe XML, para decidir si la información que contiene el       -->
<!-- XML es coherente, es decir, comprueba: si se solapan tareas, si      -->
<!-- abarca más de siete días, si contiene dos meses no consecutivos,     -->
<!-- el número de días de un mes, etc. No comprueba valores de los        -->
<!-- atributos definidos en el DTD, por lo que, antes de aplicar este XSL,-->
<!-- es necesario comprobar que el informe esta bien formado y es válido. -->
<!-- Esta hoja de estilo necesita recibir 6 parámetros: el año inicial,   -->
<!-- el mes inicial, el día inicial y el año final, el mes final y el día -->
<!-- final, es decir, las fechas (semana) que abarca el informe (están    -->
<!-- en el nombre del informe XML).                                       -->

<!-- xmlcoherente.xsl:  José Riguera, 2003 <jriguera@igalia.com>          -->


<!-- P1: adaptacion para cambiar el rango de horas del día de 0 a 23 h a  -->
<!-- 1 a 24 horas, es decir las tareas deben tener fechas de inicio y fin -->
<!-- 0 < fecha <= 24.  (José Riguera <jriguera@igalia.com>, 10-01-2004)   --> 



<xsl:output method="text" encoding="ISO-8859-1"/>
	
<xsl:param name="Ianoini" select="0"/>
<xsl:param name="Imesini" select="0"/>
<xsl:param name="Idiaini" select="0"/>

<xsl:param name="Ianofin" select="3000"/>
<xsl:param name="Imesfin" select="12"/>
<xsl:param name="Idiafin" select="31"/>



<xsl:variable name="n">
<xsl:text>.
</xsl:text>
</xsl:variable>



<xsl:template name="Programa" match="/">

    	<xsl:variable name="retorno">
    		<xsl:apply-templates select="dedicacionSemanal" />
        </xsl:variable>

     	<xsl:choose>
	     	<xsl:when test="starts-with($retorno, 'Error')" >
                	<xsl:value-of select="$retorno"/>
      		</xsl:when>
      		<xsl:otherwise>
			<xsl:text>OK</xsl:text>
      		</xsl:otherwise>
     	</xsl:choose>

</xsl:template>



<xsl:template name="principal" match="dedicacionSemanal">
	
	<xsl:variable name="mesesinforme" select="count(dedicacion)"/>
        
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
		<xsl:when test="$mesesinforme = 1">
			<xsl:choose>
                       		<xsl:when test="$mesesinforme != $abarcameses">
                                	<xsl:choose>
						<xsl:when test="count(dedicacion[1]/dedicacionDiaria) > 6">
							<xsl:text>Error, primer mes del informe con muchos días</xsl:text>
							<xsl:value-of select="$n"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:variable name="mesdias1">
								<xsl:call-template name="DiasMes">
									<xsl:with-param name="mes" select="dedicacion[1]/@mes"/>
									<xsl:with-param name="ano" select="$Ianoini"/>
								</xsl:call-template>
							</xsl:variable>
							<xsl:variable name="numeromes">
								<xsl:call-template name="Mes2numero">
									<xsl:with-param name="mes" select="dedicacion[1]/@mes"/>
								</xsl:call-template>
							</xsl:variable>

							<xsl:choose>
								<xsl:when test="$numeromes = $Imesini">
     									<xsl:apply-templates select="dedicacion[1]">
      										<xsl:with-param name="iano" select="$Ianoini"/>
      										<xsl:with-param name="idiaini" select="$Idiaini"/>
      										<xsl:with-param name="idiafin" select="$mesdias1"/>
      									</xsl:apply-templates>
								</xsl:when>
								<xsl:otherwise>
									<xsl:apply-templates select="dedicacion[1]">
										<xsl:with-param name="iano" select="$Ianofin"/>
										<xsl:with-param name="idiaini" select="1"/>
										<xsl:with-param name="idiafin" select="$Idiafin"/>
									</xsl:apply-templates>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
                                	</xsl:choose>
                                </xsl:when>
                                
      				<xsl:otherwise>
                                
      					<xsl:variable name="numeromes">
    						<xsl:call-template name="Mes2numero">
     							<xsl:with-param name="mes" select="dedicacion[1]/@mes"/>
     						</xsl:call-template>
      					</xsl:variable>
					<xsl:variable name="mesdias1">
						<xsl:call-template name="DiasMes">
							<xsl:with-param name="mes" select="dedicacion[1]/@mes"/>
							<xsl:with-param name="ano" select="$Ianoini"/>
						</xsl:call-template>
					</xsl:variable>

					<xsl:choose>
						<xsl:when test="$numeromes != $Imesini">
							<xsl:text>Error, mes del informe incorrecto</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:when test="$Idiafin > $mesdias1">
							<xsl:text>Error, dias de mes incorrectos</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:when test="($Idiafin - $Idiaini) != 6">
							<xsl:text>Error, el informe no abarca 7 días</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="dedicacion">
								<xsl:with-param name="iano" select="$Ianoini"/>
								<xsl:with-param name="idiaini" select="$Idiaini"/>
								<xsl:with-param name="idiafin" select="$Idiafin"/>
							</xsl:apply-templates>
			       			</xsl:otherwise>
					</xsl:choose>
      				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:when test="$mesesinforme = 2">
			<xsl:variable name="mes1">
				<xsl:call-template name="Mes2numero">
					<xsl:with-param name="mes" select="dedicacion[1]/@mes"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:variable name="mes2">
				<xsl:call-template name="Mes2numero">
					<xsl:with-param name="mes" select="dedicacion[2]/@mes"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:choose>
				<xsl:when test="(($mes1 + 1) mod 12) != ($mes2 mod 12)">
					<xsl:text>Error, meses incorrectos para este informe</xsl:text>
					<xsl:value-of select="$n"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="diasmes1" select="count(dedicacion[1]/dedicacionDiaria)"/>
					<xsl:variable name="diasmes2" select="count(dedicacion[2]/dedicacionDiaria)"/>
					<xsl:variable name="mesdias1">
						<xsl:call-template name="DiasMes">
							<xsl:with-param name="mes" select="/dedicacion[1]/@mes"/>
							<xsl:with-param name="ano" select="$Ianoini"/>
						</xsl:call-template>
					</xsl:variable>
					<xsl:if test="($diasmes1 + $diasmes2) > 7">
						<xsl:text>Error, informe con más de 7 días</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>
					<xsl:if test="(($mesdias1 - $Idiaini) + $Idiafin) != 6">
						<xsl:text>Mes1:</xsl:text><xsl:value-of select="$diasmes1"/>
						<xsl:value-of select="$n"/>
						<xsl:text>Error, el informe no abarca 7 días</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>

					<xsl:apply-templates select="dedicacion[1]">
						<xsl:with-param name="iano" select="$Ianoini"/>
						<xsl:with-param name="idiaini" select="$Idiaini"/>
						<xsl:with-param name="idiafin" select="$mesdias1"/>
					</xsl:apply-templates>
					<xsl:apply-templates select="dedicacion[2]">
						<xsl:with-param name="iano" select="$Ianofin"/>
						<xsl:with-param name="idiaini" select="1"/>
						<xsl:with-param name="idiafin" select="$Idiafin"/>
					</xsl:apply-templates>

				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>Error, abarca varios meses</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:otherwise>
	</xsl:choose>
        
</xsl:template>



<xsl:template name="ControlDiasMes" match="dedicacion">
	<xsl:param name="iano"/>
	<xsl:param name="idiaini"/>
	<xsl:param name="idiafin"/>

	<xsl:variable name="mesnumdias">
		<xsl:call-template name="DiasMes">
			<xsl:with-param name="mes" select="@mes"/>
			<xsl:with-param name="ano" select="$iano"/>
		</xsl:call-template>
	</xsl:variable>

	<xsl:for-each select="dedicacionDiaria">
		<xsl:sort select="@dia" data-type="number"/>
		
		<xsl:variable name="tindice" select="position()"/>
		<xsl:variable name="ult" select="@dia"/>

		<xsl:for-each select="following-sibling::dedicacionDiaria">
			<xsl:if test="$ult = @dia">
				<xsl:text>Error, informe con dos días iguales</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:for-each>
	
	<xsl:variable name="mesdini">
		<xsl:for-each select="dedicacionDiaria">
			<xsl:sort select="@dia" data-type="number"/>
			<xsl:if test="position() = 1">
				<xsl:value-of select="@dia"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:variable>
	
	<xsl:variable name="mesdfin">
		<xsl:for-each select="dedicacionDiaria">
			<xsl:sort select="@dia" data-type="number"/>
			<xsl:if test="position() = last()">
				<xsl:value-of select="@dia"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="$mesnumdias = 0">
			<xsl:text>Error, mes desconocido</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$idiaini > $mesdini">
			<xsl:text>Error, fecha (día) inicial incorrecto</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$mesdfin > $idiafin">
			<xsl:text>Error, fecha (día) final incorrecto</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="($mesdfin - $mesdini) > 6">
			<xsl:text>Error, mas de siete días en el informe</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="1 > $mesdini">
			<xsl:text>Error, día inicial del mes ¡negativo!</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$mesdfin > $mesnumdias">
			<xsl:text>Error, ¡el mes no tiene tantos días!</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:for-each select="dedicacionDiaria">
				<xsl:call-template name="ControlTareas">
					<xsl:with-param name="dia" select="@dia"/>
				</xsl:call-template>
			</xsl:for-each>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>



<xsl:template name="DiasMes">
	<xsl:param name="mes"/>
	<xsl:param name="ano"/>

	<xsl:variable name="mesminuscula">
		<xsl:value-of select="translate($mes,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
	</xsl:variable>
	<xsl:choose>
		<xsl:when test="'enero' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'febrero' = $mesminuscula">
			<xsl:choose>
				<xsl:when test="($ano mod 4) = 0">
					<xsl:choose>
						<xsl:when test="($ano mod 400) = 0">
							<xsl:text>29</xsl:text>
						</xsl:when>
						<xsl:when test="($ano mod 100) = 0">
							<xsl:text>28</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>29</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>28</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:when test="'marzo' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'abril' = $mesminuscula">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'mayo' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'junio' = $mesminuscula">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'julio' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'agosto' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'septiembre' = $mesminuscula">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'octubre' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'noviembre' = $mesminuscula">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'diciembre' = $mesminuscula">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>0</xsl:text>
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


	
<xsl:template name="ControlTareas">
	<xsl:param name="dia"/>
	
	<xsl:for-each select="tarea">
		<xsl:variable name="tinicio" select="@inicio"/>
		<xsl:variable name="tfin" select="@fin"/>
		<xsl:variable name="tminicio" select="$tinicio mod 100"/>
		<xsl:variable name="thinicio" select="($tinicio - $tminicio) div 100"/>
		<xsl:variable name="tmfin" select="$tfin mod 100"/>
		<xsl:variable name="thfin" select="($tfin - $tmfin) div 100"/>
		
		<xsl:choose>
			<xsl:when test="($thinicio = 'NaN') or ($thfin = 'NaN') or ($tminicio = 'NaN') or ($tmfin = 'NaN')">
				<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
				<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
				<xsl:text>, formato de horario incorrecto</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:when>
			<xsl:when test="$tinicio >= $tfin">
				<xsl:if test="($thfin != 0) or (($thfin = 0) and ($tmfin != 0))">
					<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
					<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
					<xsl:text>, horario incorrecto</xsl:text>
					<xsl:value-of select="$n"/>
				</xsl:if>
			</xsl:when>
			<xsl:when test="($thinicio > 23) or (0 > $thinicio)">
				<xsl:choose>
				<xsl:when test="$thinicio = 24">
					<xsl:if test="$tminicio != 0">
						<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
						<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
						<xsl:text>, hora de inicio incorrecta</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
					<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
					<xsl:text>, hora de inicio incorrecta</xsl:text>
					<xsl:value-of select="$n"/>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="($thfin > 23) or (0 > $thfin)">
				<xsl:choose>
				<xsl:when test="$thfin = 24">
					<xsl:if test="$tmfin != 0">
						<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
						<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
						<xsl:text>, hora de fin incorrecta</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
					<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
					<xsl:text>, hora de fin incorrecta</xsl:text>
					<xsl:value-of select="$n"/>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="($tminicio > 59) or (0 > $tminicio)">
				<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
				<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
				<xsl:text>, minuto de inicio incorrecto</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:when>
			<xsl:when test="($tmfin > 59) or (0 > $tmfin)">
				<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
				<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
				<xsl:text>, minuto de fin incorrecto</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:when>
		</xsl:choose>
		
		<xsl:variable name="tindice" select="position()"/>

		<xsl:for-each select="../tarea">
			<xsl:if test="position() != $tindice">
				<xsl:choose>
				<xsl:when test="$tinicio >= @inicio">
					<xsl:if test="@fin > $tinicio">
						<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
						<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
						<xsl:text> solapada con la </xsl:text><xsl:value-of select="$tindice"/>
						<xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<xsl:if test="$tfin > @inicio">
						<xsl:text>Error, tarea </xsl:text><xsl:value-of select="position()"/>
						<xsl:text> del día </xsl:text><xsl:value-of select="$dia"/>
						<xsl:text> solapada con la </xsl:text><xsl:value-of select="$tindice"/>
						<xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
		</xsl:for-each>
	</xsl:for-each>
</xsl:template>

	
</xsl:stylesheet>

