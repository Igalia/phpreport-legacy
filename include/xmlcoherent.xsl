<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- xmlcoherent.xsl: This XSL performs some checks over an XML report        -->
<!-- in order to decide if the information it contains is coherent, that      -->
<!-- is, it checks: if tasks overlap, if it ranges more than 7 days, if       -->
<!-- it contains two non consecutive months, the number of days in a          -->
<!-- month, etc... It doesn't check values of attributes defined in the       -->
<!-- DTD, so, before applying this XSL it's necessary to test if the          -->
<!-- report is well formed and valid.                                         -->

<!-- This stylesheet needs to receive 6 parameters: start year, start month,  -->
<!-- start day, end year, end month and end day, that is, the dates (week)    -->
<!-- for which the report ranges (they are in the XML report filename).       -->

<!-- xmlcoherent.xsl:  José Riguera, 2003 <jriguera@igalia.com>               -->

<!-- P1: Adaptation to change hour range in a day from 0 to 23 h to           -->
<!-- 1 to 24 hours, that is, the tasks must have start and end dates          -->
<!-- 0 < date <= 24 (José Riguera <jriguera@igalia.com>, 10-01-2004)          -->

<xsl:output method="text" encoding="UTF-8"/>
	
<xsl:param name="Iyearini" select="0"/>
<xsl:param name="Imonthini" select="0"/>
<xsl:param name="Idayini" select="0"/>

<xsl:param name="Iyearend" select="3000"/>
<xsl:param name="Imonthend" select="12"/>
<xsl:param name="Idayend" select="31"/>



<xsl:variable name="n">
<xsl:text>.
</xsl:text>
</xsl:variable>



<xsl:template name="Program" match="/">

    	<xsl:variable name="return">
    		<xsl:apply-templates select="weeklyDedication" />
        </xsl:variable>

     	<xsl:choose>
	     	<xsl:when test="starts-with($return, 'Error')" >
                	<xsl:value-of select="$return"/>
      		</xsl:when>
      		<xsl:otherwise>
			<xsl:text>OK</xsl:text>
      		</xsl:otherwise>
     	</xsl:choose>

</xsl:template>



<xsl:template name="main" match="weeklyDedication">
	
	<xsl:variable name="monthsreport" select="count(dedication)"/>
        
	<xsl:variable name="rangesmonths">
      		<xsl:choose>
      			<xsl:when test="$Imonthend = $Imonthini">
                        	<xsl:value-of select="1"/>
      			</xsl:when>
      			<xsl:otherwise>
				<xsl:value-of select="2"/>
      			</xsl:otherwise>
     		</xsl:choose>
        </xsl:variable>
	
	<xsl:choose>
		<xsl:when test="$monthsreport = 1">
			<xsl:choose>
                       		<xsl:when test="$monthsreport != $rangesmonths">
                                	<xsl:choose>
						<xsl:when test="count(dedication[1]/dailyDedication) > 6">
							<xsl:text>_("Error report first month has too many days")</xsl:text>
							<xsl:value-of select="$n"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:variable name="monthdays1">
								<xsl:call-template name="DaysMonth">
									<xsl:with-param name="month" select="dedication[1]/@month"/>
									<xsl:with-param name="year" select="$Iyearini"/>
								</xsl:call-template>
							</xsl:variable>
							<xsl:variable name="numbermonth">
								<xsl:call-template name="Month2Number">
									<xsl:with-param name="month" select="dedication[1]/@month"/>
								</xsl:call-template>
							</xsl:variable>

							<xsl:choose>
								<xsl:when test="$numbermonth = $Imonthini">
     									<xsl:apply-templates select="dedication[1]">
      										<xsl:with-param name="iyear" select="$Iyearini"/>
      										<xsl:with-param name="idayini" select="$Idayini"/>
      										<xsl:with-param name="idayend" select="$monthdays1"/>
      									</xsl:apply-templates>
								</xsl:when>
								<xsl:otherwise>
									<xsl:apply-templates select="dedication[1]">
										<xsl:with-param name="iyear" select="$Iyearend"/>
										<xsl:with-param name="idayini" select="1"/>
										<xsl:with-param name="idayend" select="$Idayend"/>
									</xsl:apply-templates>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
                                	</xsl:choose>
                                </xsl:when>
                                
      				<xsl:otherwise>
                                
      					<xsl:variable name="numbermonth">
    						<xsl:call-template name="Month2number">
     							<xsl:with-param name="month" select="dedication[1]/@month"/>
     						</xsl:call-template>
      					</xsl:variable>
					<xsl:variable name="monthdays1">
						<xsl:call-template name="DaysMonth">
							<xsl:with-param name="month" select="dedication[1]/@month"/>
							<xsl:with-param name="year" select="$Iyearini"/>
						</xsl:call-template>
					</xsl:variable>

					<xsl:choose>
						<xsl:when test="$numbermonth != $Imonthini">
							<xsl:text>_("Error, wrong report month")</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:when test="$Idayend > $monthdays1">
							<xsl:text>_("Error, wrong month days")</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:when test="($Idayend - $Idayini) != 6">
							<xsl:text>_("Error, report doesn't range 7 days")</xsl:text>
							<xsl:value-of select="$n"/>
			       			</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="dedication">
								<xsl:with-param name="iyear" select="$Iyearini"/>
								<xsl:with-param name="idayini" select="$Idayini"/>
								<xsl:with-param name="idayend" select="$Idayend"/>
							</xsl:apply-templates>
			       			</xsl:otherwise>
					</xsl:choose>
      				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:when test="$monthsreport = 2">
			<xsl:variable name="month1">
				<xsl:call-template name="Month2Number">
					<xsl:with-param name="month" select="dedication[1]/@month"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:variable name="month2">
				<xsl:call-template name="Month2Number">
					<xsl:with-param name="month" select="dedication[2]/@month"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:choose>
				<xsl:when test="(($month1 + 1) mod 12) != ($month2 mod 12)">
					<xsl:text>_("Error, incorrect months in this report")</xsl:text>
					<xsl:value-of select="$n"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="daysmonth1" select="count(dedication[1]/dailyDedication)"/>
					<xsl:variable name="daysmonth2" select="count(dedication[2]/dailyDedication)"/>
					<xsl:variable name="monthdays1">
						<xsl:call-template name="DaysMonth">
							<xsl:with-param name="month" select="/dedication[1]/@month"/>
							<xsl:with-param name="year" select="$Iyearini"/>
						</xsl:call-template>
					</xsl:variable>
					<xsl:if test="($daysmonth1 + $daysmonth2) > 7">
						<xsl:text>_("Error, report has more than 7 days")</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>
					<xsl:if test="(($monthdays1 - $Idayini) + $Idayend) != 6">
						<xsl:text>Month1:</xsl:text><xsl:value-of select="$daysmonth1"/>
						<xsl:value-of select="$n"/>
						<xsl:text>_("Error, report doesn't range 7 days")</xsl:text>
						<xsl:value-of select="$n"/>
					</xsl:if>

					<xsl:apply-templates select="dedication[1]">
						<xsl:with-param name="iyear" select="$Iyearini"/>
						<xsl:with-param name="idayini" select="$Idayini"/>
						<xsl:with-param name="idayend" select="$monthdays1"/>
					</xsl:apply-templates>
					<xsl:apply-templates select="dedication[2]">
						<xsl:with-param name="iyear" select="$Iyearend"/>
						<xsl:with-param name="idayini" select="1"/>
						<xsl:with-param name="idayend" select="$Idayend"/>
					</xsl:apply-templates>

				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>_("Error, it ranges some months")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:otherwise>
	</xsl:choose>
        
</xsl:template>



<xsl:template name="ControlDaysMonth" match="dedication">
	<xsl:param name="iyear"/>
	<xsl:param name="idayini"/>
	<xsl:param name="idayend"/>

	<xsl:variable name="monthnumdays">
		<xsl:call-template name="DaysMonth">
			<xsl:with-param name="month" select="@month"/>
			<xsl:with-param name="year" select="$iyear"/>
		</xsl:call-template>
	</xsl:variable>

	<xsl:for-each select="dailyDedication">
		<xsl:sort select="@day" data-type="number"/>
		
		<xsl:variable name="tindex" select="position()"/>
		<xsl:variable name="last" select="@day"/>

		<xsl:for-each select="following-sibling::dailyDedication">
			<xsl:if test="$last = @day">
				<xsl:text>_("Error, report with two equal days")</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:for-each>
	
	<xsl:variable name="monthdini">
		<xsl:for-each select="dailyDedication">
			<xsl:sort select="@day" data-type="number"/>
			<xsl:if test="position() = 1">
				<xsl:value-of select="@day"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:variable>
	
	<xsl:variable name="monthdend">
		<xsl:for-each select="dailyDedication">
			<xsl:sort select="@day" data-type="number"/>
			<xsl:if test="position() = last()">
				<xsl:value-of select="@day"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="$monthnumdays = 0">
			<xsl:text>_("Error, unknown month")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$idayini > $monthdini">
			<xsl:text>_("Error, wrong initial date (day)")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$monthdend > $idayend">
    <xsl:text>_("Error, wrong final date (day)")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="($monthdend - $monthdini) > 6">
			<xsl:text>_("Error, more than 7 days in the report")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="1 > $monthdini">
			<xsl:text>_("Error, negative initial month day!")</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:when test="$monthdend > $monthnumdays">
			<xsl:text>_("Error, month hasn't so many days!</xsl:text>
			<xsl:value-of select="$n"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:for-each select="dailyDedication">
				<xsl:call-template name="TaskControl">
					<xsl:with-param name="day" select="@day"/>
				</xsl:call-template>
			</xsl:for-each>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>



<xsl:template name="DaysMonth">
	<xsl:param name="month"/>
	<xsl:param name="year"/>

	<xsl:variable name="monthlowercase">
		<xsl:value-of select="translate($month,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
	</xsl:variable>
	<xsl:choose>
		<xsl:when test="'january' = $monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'february' = $monthlowercase">
			<xsl:choose>
				<xsl:when test="($year mod 4) = 0">
					<xsl:choose>
						<xsl:when test="($year mod 400) = 0">
							<xsl:text>29</xsl:text>
						</xsl:when>
						<xsl:when test="($year mod 100) = 0">
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
		<xsl:when test="'march' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'april' = monthlowercase">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'may' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'june' = monthlowercase">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'july' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'august' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
    <xsl:when test="'september' = monthlowercase">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'october' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:when test="'november' = monthlowercase">
			<xsl:text>30</xsl:text>
		</xsl:when>
		<xsl:when test="'december' = monthlowercase">
			<xsl:text>31</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>0</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<xsl:template name="Month2number">
	<xsl:param name="month"/>
	
	<xsl:variable name="monthlowercase">
		<xsl:value-of select="translate($month,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="'january' = monthlowercase">
			<xsl:text>1</xsl:text>
		</xsl:when>
		<xsl:when test="'february' = monthlowercase">
			<xsl:text>2</xsl:text>
		</xsl:when>
		<xsl:when test="'march' = monthlowercase">
			<xsl:text>3</xsl:text>
		</xsl:when>
		<xsl:when test="'april' = monthlowercase">
			<xsl:text>4</xsl:text>
		</xsl:when>
		<xsl:when test="'may' = monthlowercase">
			<xsl:text>5</xsl:text>
		</xsl:when>
		<xsl:when test="'june' = monthlowercase">
			<xsl:text>6</xsl:text>
		</xsl:when>
		<xsl:when test="'july' = monthlowercase">
			<xsl:text>7</xsl:text>
		</xsl:when>
		<xsl:when test="'august' = monthlowercase">
			<xsl:text>8</xsl:text>
		</xsl:when>
		<xsl:when test="'september' = monthlowercase">
			<xsl:text>9</xsl:text>
		</xsl:when>
		<xsl:when test="'october' = monthlowercase">
			<xsl:text>10</xsl:text>
		</xsl:when>
		<xsl:when test="'november' = monthlowercase">
			<xsl:text>11</xsl:text>
		</xsl:when>
		<xsl:when test="'december' = monthlowercase">
			<xsl:text>12</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>-1</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


	
<xsl:template name="TaskControl">
	<xsl:param name="day"/>
	
	<xsl:for-each select="task">
		<xsl:variable name="tinit" select="@init"/>
		<xsl:variable name="tend" select="@end"/>
		<xsl:variable name="tminit" select="$tinit mod 100"/>
		<xsl:variable name="thinit" select="($tinit - $tminit) div 100"/>
		<xsl:variable name="tmend" select="$tend mod 100"/>
		<xsl:variable name="thend" select="($tend - $tmend) div 100"/>
		
		<xsl:choose>
			<xsl:when test="($thinit = 'NaN') or ($thend = 'NaN') or ($tminit = 'NaN') or ($tmend = 'NaN')">
        <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect time format'),"</xsl:text>
        <xsl:value-of select="position()"/>
        <xsl:text>","</xsl:text>
        <xsl:value-of select="$dia"/>
				<xsl:text>")</xsl:text>
				<xsl:value-of select="$n"/>
			</xsl:when>
			<xsl:when test="$tinit >= $tend">
				<xsl:if test="($thend != 0) or (($thend = 0) and ($tmend != 0))">
          <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect time format'),"</xsl:text>
          <xsl:value-of select="position()"/>
          <xsl:text>","</xsl:text>
          <xsl:value-of select="$dia"/>
          <xsl:text>")</xsl:text>
          <xsl:value-of select="$n"/>
				</xsl:if>
			</xsl:when>
			<xsl:when test="($thinit > 23) or (0 > $thinit)">
				<xsl:choose>
				<xsl:when test="$thinit = 24">
					<xsl:if test="$tminit != 0">
            <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect start hour'),"</xsl:text>
            <xsl:value-of select="position()"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$dia"/>
            <xsl:text>")</xsl:text>
            <xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
          <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect start hour'),"</xsl:text>
          <xsl:value-of select="position()"/>
          <xsl:text>","</xsl:text>
          <xsl:value-of select="$dia"/>
          <xsl:text>")</xsl:text>
          <xsl:value-of select="$n"/>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="($thend > 23) or (0 > $thend)">
				<xsl:choose>
				<xsl:when test="$thend = 24">
					<xsl:if test="$tmend != 0">
            <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect start hour'),"</xsl:text>
            <xsl:value-of select="position()"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$dia"/>
            <xsl:text>")</xsl:text>
            <xsl:value-of select="$n"/>                    					
          </xsl:if>
				</xsl:when>
				<xsl:otherwise>
          <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect start hour'),"</xsl:text>
          <xsl:value-of select="position()"/>
          <xsl:text>","</xsl:text>
          <xsl:value-of select="$dia"/>
          <xsl:text>")</xsl:text>
          <xsl:value-of select="$n"/>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
      <xsl:when test="($tminit > 59) or (0 > $tminit)">
        <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect start minute'),"</xsl:text>
        <xsl:value-of select="position()"/>
        <xsl:text>","</xsl:text>
        <xsl:value-of select="$dia"/>
        <xsl:text>")</xsl:text>
        <xsl:value-of select="$n"/>
			</xsl:when>
			<xsl:when test="($tmend > 59) or (0 > $tmend)">
        <xsl:text>sprintf(_('Error, task %1$s of day %2$s, incorrect end minute'),"</xsl:text>
        <xsl:value-of select="position()"/>
        <xsl:text>","</xsl:text>
        <xsl:value-of select="$dia"/>
        <xsl:text>")</xsl:text>
        <xsl:value-of select="$n"/>
			</xsl:when>
		</xsl:choose>
		
		<xsl:variable name="tindex" select="position()"/>

		<xsl:for-each select="../tarea">
			<xsl:if test="position() != $tindex">
				<xsl:choose>
				<xsl:when test="$tinit >= @init">
					<xsl:if test="@end > $tinit">
            <xsl:text>sprintf(_('Error, task %1$s of day %2$s, overlapped with %3$s'),"</xsl:text>
            <xsl:value-of select="position()"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$dia"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$tindex"/>
            <xsl:text>")</xsl:text>
            <xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<xsl:if test="$tend > @init">
            <xsl:text>sprintf(_('Error, task %1$s of day %2$s, overlapped with %3$s'),"</xsl:text>
            <xsl:value-of select="position()"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$dia"/>
            <xsl:text>","</xsl:text>
            <xsl:value-of select="$tindex"/>
            <xsl:text>")</xsl:text>
            <xsl:value-of select="$n"/>
					</xsl:if>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
		</xsl:for-each>
	</xsl:for-each>
</xsl:template>

	
</xsl:stylesheet>

