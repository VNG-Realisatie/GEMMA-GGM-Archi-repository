<!--
    Document   : sparx.xsl
    Created on : October 8, 2019, 11:25 AM
    Author     : Toine Schijvenaars, Remco C. de Boer
    Description:
        Input Native format Sparx Export
        Output UML based general XML (ArchiXL internal format)
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  xmlns:exslt="http://exslt.org/common" version="1.0">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:strip-space elements="*"/>
    <!-- customize transformation rules
         syntax recommendation http://www.w3.org/TR/xslt
    -->


    <xsl:template match="/">
        <UMLModel>
            <xsl:apply-templates select="Package"/>
            <xsl:apply-templates select="Package/Table"/>
        </UMLModel>
    </xsl:template>


    <!-- START PACKAGE -->
    <xsl:template match="Package">
        <!-- MODEL ATTRIBUTES -->
        <xsl:attribute name="id">
            <xsl:value-of select="translate(@guid,'{}','')"/>
        </xsl:attribute>

        <!-- MODEL NODES -->
        <name>
            <xsl:value-of select="@name"/>
        </name>

        <documentation>
            <xsl:value-of select="translate(@documentation,'&#xA;','')"/>
        </documentation>
    </xsl:template>
    <!-- END PACKAGE -->

    <!-- START PACKAGE/TABLE -->
    <xsl:template match="Package/Table">
        <!-- OBJECTS -->
        <xsl:if test="@name='t_object'">
            <UMLClassifiers>
                <xsl:variable name="table"/>
                <xsl:apply-templates select="Row">
                    <xsl:with-param name="table" select="@name" />
                </xsl:apply-templates>
            </UMLClassifiers>
        </xsl:if>

        <!-- CONNECTORS -->
        <xsl:if test="@name='t_connector'">
            <UMLConnectors>
                <xsl:variable name="table"/>
                <xsl:apply-templates select="Row">
                    <xsl:with-param name="table" select="@name" />
                </xsl:apply-templates>
            </UMLConnectors>
        </xsl:if>

        <!-- DIAGRAMS -->
        <xsl:if test="@name='t_diagram'">
            <UMLDiagrams>
                <xsl:variable name="table"/>
                <xsl:apply-templates select="Row">
                    <xsl:with-param name="table" select="@name" />
                </xsl:apply-templates>
            </UMLDiagrams>
        </xsl:if>
    </xsl:template>
    <!-- END PACKAGE/TABLE -->

    <!-- START ROW -->
    <xsl:template match="Row">
        <xsl:param name="table"/>
        <!-- START TABLE OBJECTS -->
        <xsl:if test="$table='t_object'">
            <UMLClassifier>
                <!-- UMLClassifier attributes -->
                <xsl:apply-templates select="Column">
                    <xsl:with-param name="mode" select="'xmlclassifierattributes'"/>
                </xsl:apply-templates>
                <!-- UMLClassifier relation to other elements -->
                <xsl:apply-templates select="Extension"/>
                <!-- UMLClassifier nodes -->
                <xsl:apply-templates select="Column">
                    <xsl:with-param name="mode" select="'classifiernodes'"/>
                </xsl:apply-templates>

                <!-- START PROCESSING CLASSIFIER ATTRIBUTES FOR OBJECTS -->
                <!-- SEARCH THE GUID.wiki OF THIS OBJECT FOR SEARCHING THE ATTRIBUTES -->
                <xsl:variable name="umlclassifierid">
                    <xsl:value-of select="Column[@name='ea_guid']/@value"/>
                </xsl:variable>

                <UMLClassifierAttributes>
                    <!-- VOORBEELD QUERY /Package/Table[@name='t_attribute']/Row[Extension[@Object_ID='{0E5C61C2-24FF-4f71-A7FF-D7B4CA327A36}']]   -->
                    <xsl:for-each select="/Package/Table[@name='t_attribute']/Row[Extension[@Object_ID=$umlclassifierid]]">
                        <xsl:sort select="Column[@name='Pos']/@value"/>
                        <xsl:variable name="umlclassifierattributeid"><xsl:value-of select="Column[@name='ea_guid']/@value"/></xsl:variable>
                        <xsl:variable name="strippedumlclassifierattributeid"><xsl:value-of select="translate($umlclassifierattributeid,'{}','')"/></xsl:variable>
                        <UMLClassifierAttribute id="{$strippedumlclassifierattributeid}">
                            <name><xsl:value-of select="Column[@name='Name']/@value"/></name>
                            <documentation><xsl:value-of select="translate(Column[@name='Notes']/@value, '&#xA;','')"/></documentation>
                            <type><xsl:value-of select="Column[@name='Type']/@value"/></type>
                            <multiplicity><xsl:value-of select="Column[@name='LowerBound']/@value"/>..<xsl:value-of select="Column[@name='UpperBound']/@value"/></multiplicity>
                            <stereotype><xsl:value-of select="Column[@name='Stereotype']/@value"/></stereotype>
                        </UMLClassifierAttribute>
                    </xsl:for-each>
                </UMLClassifierAttributes>
                <UMLClassifierOperations>
                    <xsl:for-each select="/Package/Table[@name='t_operation']/Row[Extension[@Object_ID=$umlclassifierid]]">
                        <xsl:sort select="Column[@name='Pos']/@value"/>
                        <xsl:variable name="umloperationattributeid"><xsl:value-of select="Column[@name='ea_guid']/@value"/></xsl:variable>
                        <xsl:variable name="strippedumlclassifieroperationid"><xsl:value-of select="translate($umloperationattributeid,'{}','')"/></xsl:variable>
                        <UMLClassifierOperation id="{$strippedumlclassifieroperationid}">
                            <name><xsl:value-of select="Column[@name='Name']/@value"/></name>
                            <type><xsl:value-of select="Column[@name='Type']/@value"/></type>
                            <concurrency><xsl:value-of select="Column[@name='Concurrency']/@value"/></concurrency>
                            <UMLClassifierOperationParameters>
                                <!-- <TestForOperationID><xsl:value-of select="{$umloperationattributeid}"/></TestForOperationID> -->
                                <xsl:for-each select="/Package/Table[@name='t_operationparams']/Row[Extension[@OperationID=$umloperationattributeid]]">
                                    <xsl:sort select="Column[@name='Pos']/@value"/>
                                    <xsl:variable name="umloperationparamterattributeid"><xsl:value-of select="Column[@name='ea_guid']/@value"/></xsl:variable>
                                    <xsl:variable name="strippedumloperationparamterattributeid"><xsl:value-of select="translate($umloperationparamterattributeid,'{}','')"/></xsl:variable>
                                    <UMLClassifierOperationParameter id="{$strippedumloperationparamterattributeid}">
                                        <name><xsl:value-of select="Column[@name='Name']/@value"/></name>
                                        <type><xsl:value-of select="Column[@name='Type']/@value"/></type>
                                        <const><xsl:value-of select="Column[@name='Const']/@value"/></const>
                                        <kind><xsl:value-of select="Column[@name='Kind']/@value"/></kind>
                                    </UMLClassifierOperationParameter>
                                </xsl:for-each>
                            </UMLClassifierOperationParameters>
                        </UMLClassifierOperation>
                    </xsl:for-each>
                </UMLClassifierOperations>

                <UMLCLassifierReceptions/>

            </UMLClassifier>
        </xsl:if>

        <!-- START TABLE CONNECTORS -->
        <xsl:if test="$table='t_connector'">
            <UMLConnector>
                <xsl:apply-templates select="Column">
                    <xsl:with-param name="mode" select="'xmlconnectorattributes'"/>
                </xsl:apply-templates>
                <xsl:apply-templates select="Extension"/>


                <xsl:apply-templates select="Column">
                    <xsl:with-param name="mode" select="'connectornodes'"/>
                </xsl:apply-templates>

            </UMLConnector>
        </xsl:if>

        <!-- START TABLE DIAGRAMS -->
        <xsl:if test="$table='t_diagram'">
            <!-- De x,y-waarden drukken een coördinaat uit in het vierde kwadrant (x=positief, y=negatief).
                 We willen dit omkatten naar een coördinaat in het eerste kwadrant (x en y beide positief),
                 zodat we de x,y-coördinaten op reguliere wijze kunnen verwerken. We verschuiven hiervoor
                 het coördinatenstelsel zó dat de meest negatieve y-waarde in de XML het nulpunt wordt. -->
            <xsl:variable name="lowObjectY">
                <xsl:for-each select="//Column[@name='RectBottom']/@value">
                    <xsl:sort data-type="number" order="ascending"/>
                    <xsl:if test="position()=1"><xsl:value-of select="."/></xsl:if>
                </xsl:for-each>
            </xsl:variable>

            <!-- now collect the y-coordinates from the bendpoints-->
            <xsl:variable name="allBendpoints">
                <xsl:for-each select="//Column[@name='Path']/@value">
                    <xsl:value-of select="."/>
                </xsl:for-each>
            </xsl:variable>
            <xsl:variable name="allYcoordinates">
                <xsl:call-template name="filterYcoordinates">
                    <xsl:with-param name="pText" select="$allBendpoints"/>
                </xsl:call-template>
            </xsl:variable>
            <xsl:variable name="ys" select="exslt:node-set($allYcoordinates)"/>
            <xsl:variable name="lowBendpointY">
                <xsl:for-each select="$ys//yC">
                    <xsl:sort data-type="number" order="ascending"/>
                    <xsl:if test="position()=1"><xsl:value-of select="."/></xsl:if>
                </xsl:for-each>
            </xsl:variable>
            <lowBendpointY><xsl:value-of select="$lowBendpointY"/></lowBendpointY>
            <!-- now determine which one is the lowest -->

            <xsl:variable name="lowY">
                <xsl:choose>
                    <xsl:when test="number($lowObjectY) > number($lowBendpointY)">
                        <xsl:value-of select="$lowBendpointY"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="$lowObjectY"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:variable>

            <UMLDiagram>
                <xsl:apply-templates select="Column">
                    <xsl:with-param name="mode" select="'xmldiagramattributes'"/>
                </xsl:apply-templates>
                <xsl:apply-templates select="Extension"/>

                <UMLDiagramObjects>



                    <!-- START PROCESSING CLASSIFIER ATTRIBUTES FOR OBJECTS -->
                    <!-- SEARCH THE GUID.wiki OF THIS OBJECT FOR SEARCHING THE ATTRIBUTES -->
                    <xsl:variable name="umldiagramid">
                        <xsl:value-of select="Column[@name='ea_guid']/@value"/>
                    </xsl:variable>

                    <!-- VOORBEELD QUERY /Package/Table[@name='t_diagramobjects']/Row[Extension[@Diagram_ID='{B1F5F7DD-9A00-45da-A2AE-E0F0BCAD10FE}']]   -->
                    <xsl:for-each select="/Package/Table[@name='t_diagramobjects']/Row[Extension[@Diagram_ID=$umldiagramid]]">
                        <xsl:sort select="Column[@name='Sequence']/@value"/>
                        <xsl:variable name="x"><xsl:value-of select="Column[@name='RectLeft']/@value"/></xsl:variable>
                        <xsl:variable name="y"><xsl:value-of select="Column[@name='RectTop']/@value"/></xsl:variable>
                        <xsl:variable name="z"><xsl:value-of select="Column[@name='Sequence']/@value"/></xsl:variable>
                        <xsl:variable name="r"><xsl:value-of select="Column[@name='RectRight']/@value"/></xsl:variable>
                        <xsl:variable name="b"><xsl:value-of select="Column[@name='RectBottom']/@value"/></xsl:variable>
                        <!--<xsl:variable name="umldiagramobjectid"><xsl:value-of select="generate-id()"/></xsl:variable>-->
                        <!-- use the Diagram UID from EA -->
                       <!--  <xsl:variable name="umldiagramobjectid"><xsl:value-of select="substring-before(substring-after(Column[@name='ObjectStyle']/@value, 'DUID='), ';')"/></xsl:variable> -->
                        <xsl:variable name="umldiagramobjectid"><xsl:value-of select="Column[@name='Instance_ID']/@value"/></xsl:variable>
                        <xsl:variable name="objectid"><xsl:value-of select="Extension/@Object_ID"/></xsl:variable>
                        <xsl:variable name="strippedobjectid"><xsl:value-of select="translate($objectid,'{}','')"/></xsl:variable>

                        <UMLDiagramObject id="{$umldiagramobjectid}" x="{$x}" y="{$y - $lowY}" z="{$z}" w="{$r - $x}" h="{$y - $b}" objectid="{$strippedobjectid}">
                            <xsl:variable name="objectStyle"><xsl:value-of select="Column[@name='ObjectStyle']/@value"/></xsl:variable>
                            <style><xsl:value-of select="$objectStyle"/></style>


                            <!-- EXAMPLE STRING DUID=2E7B547D;NSL=0;BCol=-1;BFol=-1;LCol=-1;LWth=-1;fontsz=0;bold=0;black=0;italic=0;ul=0;charset=0;pitch=0;"/> -->
                            <xsl:call-template name="tokenizeStyleObject">
                                <xsl:with-param name="pText" select="$objectStyle"/>
                                <xsl:with-param name="separator" select="';'"/>
                                <xsl:with-param name="fontsize" select="'fontsz'"/>
                                <xsl:with-param name="bold" select="'bold'"/>
                                <xsl:with-param name="black" select="'black'"/>
                                <xsl:with-param name="italic" select="'italic'"/>
                                <xsl:with-param name="ul" select="'ul'"/>
                            </xsl:call-template>



                        </UMLDiagramObject>
                    </xsl:for-each>
                </UMLDiagramObjects>
                <UMLDiagramRelations>
                    <xsl:variable name="umldiagramid">
                        <xsl:value-of select="Column[@name='ea_guid']/@value"/>
                    </xsl:variable>
                    <xsl:for-each select="/Package/Table[@name='t_diagramlinks']/Row[Extension[@DiagramID=$umldiagramid]]">
                        <xsl:sort select="Column[@name='Instance_ID']/@value"/>
                        <xsl:variable name="umldiagramrelationid"><xsl:value-of select="generate-id()"/></xsl:variable>
                        <xsl:variable name="connectorid"><xsl:value-of select="Extension/@ConnectorID"/></xsl:variable>
                        <xsl:variable name="strippedconnectorid"><xsl:value-of select="translate($connectorid,'{}','')"/></xsl:variable>
                        <UMLDiagramRelation id="{$umldiagramrelationid}" connectionid="{$strippedconnectorid}">

                            <xsl:variable name="style"><xsl:value-of select="Column[@name='Style']/@value"/></xsl:variable>
                            <style><xsl:value-of select="$style"/></style>
                            <!-- EXAMPLE STRING Mode=3;EOID=6EBE7B49;SOID=3F158D12;Color=-1;LWidth=0;TREE=OS; -->
                            <xsl:call-template name="tokenizeStyleConnection">
                                <xsl:with-param name="pText" select="$style"/>
                                <xsl:with-param name="separator" select="';'"/>
                                <xsl:with-param name="mode" select="'Mode'"/>
                                <xsl:with-param name="endOId" select="'EOID'"/>
                                <xsl:with-param name="startOId" select="'SOID'"/>
                                <xsl:with-param name="color" select="'Color'"/>
                                <xsl:with-param name="lwidth" select="'LWidth'"/>
                                <xsl:with-param name="tree" select="'TREE'"/>
                            </xsl:call-template>
                            <hidden><xsl:value-of select="Column[@name='Hidden']/@value"/></hidden>
                            <!-- TO DO: extract from geometry data -->
                            <xsl:variable name="geo"><xsl:value-of select="Column[@name='Geometry']/@value"/></xsl:variable>
                            <!-- analyze the geometry for
                                <sourceAttachment x="" y="" />
                             -->
                            <!-- De x,y-waarden drukken een coördinaat uit in het vierde kwadrant (x=positief, y=negatief).
                                 We willen dit omkatten naar een coördinaat in het eerste kwadrant (x en y beide positief),
                                 zodat we de x,y-coördinaten op reguliere wijze kunnen verwerken. We verschuiven hiervoor
                                 het coördinatenstelsel zó dat de meest negatieve y-waarde in de XML het nulpunt wordt.

                                  DIT PROCES ZOU MAAR 1x MOETEN PLAATSVINDEN MAAR HET GAAT MIS MET DE REIKWIJDTE VAN VARIABLE lowY(2)-->
                            <xsl:variable name="lowObjectY2">
                                <xsl:for-each select="//Column[@name='RectBottom']/@value">
                                    <xsl:sort data-type="number" order="ascending"/>
                                    <xsl:if test="position()=1"><xsl:value-of select="."/></xsl:if>
                                </xsl:for-each>
                            </xsl:variable>

                            <!-- now collect the y-coordinates from the bendpoints-->
                            <xsl:variable name="allBendpoints2">
                                <xsl:for-each select="//Column[@name='Path']/@value">
                                    <xsl:value-of select="."/>
                                </xsl:for-each>
                            </xsl:variable>
                            <xsl:variable name="allXcoordinates2">
                                <xsl:call-template name="filterXcoordinates">
                                    <xsl:with-param name="pText" select="$allBendpoints2"/>
                                </xsl:call-template>
                            </xsl:variable>
                            <!-- <xpoints><xsl:value-of select="$allXcoordinates2"/></xpoints> -->
                            <xsl:variable name="allYcoordinates2">
                                <xsl:call-template name="filterYcoordinates">
                                    <xsl:with-param name="pText" select="$allBendpoints2"/>
                                </xsl:call-template>
                            </xsl:variable>
                            <!-- <ypoints><xsl:value-of select="$allYcoordinates2"/></ypoints> -->
                            <xsl:variable name="xs2" select="exslt:node-set($allXcoordinates2)"/>
                            <xsl:variable name="lowBendpointX">
                                <xsl:for-each select="$xs2//xC">
                                    <xsl:sort data-type="number" order="ascending"/>
                                    <xsl:if test="position()=1"><xsl:value-of select="."/></xsl:if>
                                </xsl:for-each>
                            </xsl:variable>
                            <!-- <lowx><xsl:value-of select="$lowBendpointX"/></lowx> -->

                            <xsl:variable name="ys2" select="exslt:node-set($allYcoordinates2)"/>
                            <xsl:variable name="lowBendpointY2">
                                <xsl:for-each select="$ys2//yC">
                                    <xsl:sort data-type="number" order="ascending"/>
                                    <xsl:if test="position()=1"><xsl:value-of select="."/></xsl:if>
                                </xsl:for-each>
                            </xsl:variable>
                            <!-- <lowy><xsl:value-of select="$lowBendpointY2"/></lowy> -->

                            <!--  <lowx><xsl:value-of select="$lowBendpointX2"/></lowx>
                            if $lowBendpointX2 is not a negative number, we should give the value 0
                            -->
                            <!--                           <xsl:choose>
                                                            <xsl:when test="number($lowBendpointX) &lt; 0">
                                                                <xsl:variable name="lowX2">
                                                                    <xsl:value-of select="$lowBendpointX"/>
                                                                </xsl:variable>
                                                            </xsl:when>
                                                            <xsl:otherwise>
                                                                <xsl:variable name="lowX2">
                                                                    <xsl:value-of select="'0'"/>
                                                                </xsl:variable>
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                        -->
                            <xsl:variable name="lowX2">
                                <xsl:value-of select="'0'"/>
                            </xsl:variable>
                            <!-- now determine which one is the lowest Y-->




                            <xsl:variable name="lowY2">
                                <xsl:choose>
                                    <xsl:when test="number($lowObjectY2) > number($lowBendpointY2)">
                                        <xsl:value-of select="$lowBendpointY2"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="$lowObjectY2"/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:variable>



                            <sourceAttachement>
                                <xsl:call-template name="tokenizeGeo">
                                    <xsl:with-param name="pText" select="$geo"/>
                                    <xsl:with-param name="separator" select="';'"/>
                                    <xsl:with-param name="xcoordinate" select="'SX'"/>
                                    <xsl:with-param name="ycoordinate" select="'SY'"/>
                                    <xsl:with-param name="lowestX" select="'0'"/>
                                    <xsl:with-param name="lowestY" select="$lowY2"/>
                                    <xsl:with-param name="edgeposition" select="'EDGE'"/>
                                </xsl:call-template>
                            </sourceAttachement>


                            <!-- analyze the geometry for
                            <bendpoint x="" y="" />
                            -->
                            <xsl:variable name="bendpoints"><xsl:value-of select="Column[@name='Path']/@value"/></xsl:variable>
                            <xsl:variable name="convertedbendpoints"><xsl:value-of select="translate($bendpoints,':',',')"/></xsl:variable>
                            <!-- Variable for the WIKI VARIANT
                             <xsl:variable name="listofbendpoints"><xsl:call-template name="normaliseBendpoints">
                                 <xsl:with-param name="pText" select="$convertedbendpoints"/>
                                 <xsl:with-param name="separator" select="','"/>
                             </xsl:call-template>
                             </xsl:variable>
                             -->
                            <bendpoints>
                                <xsl:call-template name="normaliseBendpoints">
                                    <xsl:with-param name="pText" select="$convertedbendpoints"/>
                                    <xsl:with-param name="separator" select="','"/>
                                    <xsl:with-param name="lowestY" select="$lowY2"/>
                                </xsl:call-template>
                                <!-- WIKI VARIANT  <xsl:value-of select="substring($listofbendpoints, 1, string-length($listofbendpoints) - 1)"/> -->
                            </bendpoints>
                            <!-- analyze the geometry for
                                <targetAttachment x="" y="" />
                             -->
                            <!--    FOUTMELDING BIJ targets ... -->
                            <targetAttachement>
                                <xsl:call-template name="tokenizeGeo">
                                    <xsl:with-param name="pText" select="$geo"/>
                                    <xsl:with-param name="separator" select="';'"/>
                                    <xsl:with-param name="xcoordinate" select="'EX'"/>
                                    <xsl:with-param name="ycoordinate" select="'EY'"/>
                                    <xsl:with-param name="lowestX" select="'0'"/>
                                    <xsl:with-param name="lowestY" select="$lowY2"/>
                                    <xsl:with-param name="edgeposition" select="'NONE'"/>
                                </xsl:call-template>
                            </targetAttachement>

                        </UMLDiagramRelation>
                    </xsl:for-each>
                </UMLDiagramRelations>
            </UMLDiagram>
        </xsl:if>
    </xsl:template>
    <!-- END ROW -->

    <xsl:template name="filterYcoordinates">
        <xsl:param name="pText"/>
        <xsl:if test="string-length($pText)">
            <xsl:variable name="coordinates"><xsl:value-of select="substring-before($pText, ';')"/></xsl:variable>
            <xsl:variable name="ycoordinate"><xsl:value-of select="substring-after($coordinates, ':')"/></xsl:variable>
            <yC><xsl:value-of select="$ycoordinate"/></yC>
            <xsl:call-template name="filterYcoordinates">
                <xsl:with-param name="pText" select="substring-after($pText, ';')"/>
            </xsl:call-template>
        </xsl:if>

    </xsl:template>


    <xsl:template name="filterXcoordinates">
        <xsl:param name="pText"/>

        <!-- 797:-659; -->
        <xsl:if test="string-length($pText)">
            <xsl:variable name="coordinates"><xsl:value-of select="substring-before($pText, ';')"/></xsl:variable>
            <xsl:variable name="xcoordinate"><xsl:value-of select="substring-before($coordinates, ':')"/></xsl:variable>
            <xC><xsl:value-of select="$xcoordinate"/></xC>
            <xsl:call-template name="filterXcoordinates">
                <xsl:with-param name="pText" select="substring-after($pText, ';')"/>
            </xsl:call-template>
        </xsl:if>

    </xsl:template>

    <xsl:template name="normaliseBendpoints">
        <xsl:param name="pText"/>
        <xsl:param name="separator"/>
        <xsl:param name="lowestY"/>

        <xsl:if test="string-length($pText)">
            <xsl:variable name="bendpoint"><xsl:value-of select="substring-before($pText, ';')"/></xsl:variable>
            <xsl:variable name="xcoordinate"><xsl:value-of select="substring-before($bendpoint, ',')"/></xsl:variable>
            <xsl:variable name="ycoordinate"><xsl:value-of select="substring-after($bendpoint, ',')"/></xsl:variable>
            <bendpoint x="{$xcoordinate}" y="{$ycoordinate - $lowestY}"/>
            <!-- THIS IS THE WIKI VARIANT
            <xsl:value-of select="'('"/>
            <xsl:value-of select="$bendpoint"/>
            <xsl:value-of select="')'"/>
            <xsl:value-of select="$separator"/>
            -->
            <xsl:call-template name="normaliseBendpoints">
                <xsl:with-param name="pText" select="substring-after($pText, ';')"/>
                <xsl:with-param name="separator" select="','"/>
                <xsl:with-param name="lowestY" select="$lowestY"/>
            </xsl:call-template>

        </xsl:if>



    </xsl:template>

    <xsl:template name="tokenizeStyleObject">
        <xsl:param name="pText"/>
        <xsl:param name="separator"/>
        <xsl:param name="fontsize"/>
        <xsl:param name="bold"/>
        <xsl:param name="black"/>
        <xsl:param name="italic"/>
        <xsl:param name="ul"/>


        <xsl:if test="string-length($pText)">
            <xsl:variable name="tagString"><xsl:value-of select="substring-before($pText, $separator)"/></xsl:variable>
            <xsl:variable name="tagName"><xsl:value-of select="substring-before($tagString, '=')"/></xsl:variable>
            <xsl:variable name="strippedtagName"><xsl:value-of select="translate($tagName,'$','')"/></xsl:variable>
            <xsl:variable name="tagValue"><xsl:value-of select="substring-after($tagString, '=')"/></xsl:variable>
            <xsl:if test="string-length($strippedtagName)">
                <xsl:choose>
                    <xsl:when test="$strippedtagName = $fontsize">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$fontsize}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $bold">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$bold}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $black">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$black}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $italic">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$italic}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $ul">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$ul}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                </xsl:choose>
                <!--   </xsl:otherwise>
                </xsl:choose> -->
            </xsl:if>
            <xsl:call-template name="tokenizeStyleObject">
                <xsl:with-param name="pText" select="substring-after($pText, $separator)"/>
                <xsl:with-param name="separator" select="$separator"/>
                <xsl:with-param name="fontsize" select="$fontsize"/>
                <xsl:with-param name="bold" select="$bold"/>
                <xsl:with-param name="black" select="$black"/>
                <xsl:with-param name="italic" select="$italic"/>
                <xsl:with-param name="ul" select="$ul"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>

    <xsl:template name="tokenizeStyleConnection">
        <xsl:param name="pText"/>
        <xsl:param name="separator"/>
        <xsl:param name="mode"/>
        <xsl:param name="endOId"/>
        <xsl:param name="startOId"/>
        <xsl:param name="color"/>
        <xsl:param name="lwidth"/>
        <xsl:param name="tree"/>

        <xsl:if test="string-length($pText)">
            <xsl:variable name="tagString"><xsl:value-of select="substring-before($pText, $separator)"/></xsl:variable>
            <xsl:variable name="tagName"><xsl:value-of select="substring-before($tagString, '=')"/></xsl:variable>
            <xsl:variable name="strippedtagName"><xsl:value-of select="translate($tagName,'$','')"/></xsl:variable>
            <xsl:variable name="tagValue"><xsl:value-of select="substring-after($tagString, '=')"/></xsl:variable>
            <xsl:if test="string-length($strippedtagName)">
                <xsl:choose>
                    <xsl:when test="$strippedtagName = $mode">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$mode}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $endOId">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$endOId}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $startOId">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$startOId}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $color">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$color}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $lwidth">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$lwidth}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $tree">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:element name="{$tree}">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:element>
                        </xsl:if>
                    </xsl:when>
                </xsl:choose>
                <!--   </xsl:otherwise>
                </xsl:choose> -->
            </xsl:if>
            <xsl:call-template name="tokenizeStyleConnection">
                <xsl:with-param name="pText" select="substring-after($pText, $separator)"/>
                <xsl:with-param name="separator" select="$separator"/>
                <xsl:with-param name="mode" select="$mode"/>
                <xsl:with-param name="endOId" select="$endOId"/>
                <xsl:with-param name="startOId" select="$startOId"/>
                <xsl:with-param name="color" select="$color"/>
                <xsl:with-param name="lwidth" select="$lwidth"/>
                <xsl:with-param name="tree" select="$tree"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>


    <xsl:template name="tokenizeGeo">
        <xsl:param name="pText"/>
        <xsl:param name="separator"/>
        <xsl:param name="xcoordinate"/>
        <xsl:param name="ycoordinate"/>
        <xsl:param name="lowestX"/>
        <xsl:param name="lowestY"/>
        <xsl:param name="edgeposition"/>

        <xsl:if test="string-length($pText)">
            <xsl:variable name="tagString"><xsl:value-of select="substring-before($pText, $separator)"/></xsl:variable>
            <xsl:variable name="tagName"><xsl:value-of select="substring-before($tagString, '=')"/></xsl:variable>
            <xsl:variable name="strippedtagName"><xsl:value-of select="translate($tagName,'$','')"/></xsl:variable>
            <xsl:variable name="tagValue"><xsl:value-of select="substring-after($tagString, '=')"/></xsl:variable>
            <xsl:if test="string-length($strippedtagName)">
                <!--<xsl:choose>
                    <xsl:when test="$strippedtagName='LMT'">
                        <xsl:call-template name="tokenizeGeo">
                            <xsl:with-param name="pText" select="$tagValue"/>
                            <xsl:with-param name="separator" select="':'"/>
                            <xsl:with-param name="xcoordinate" select="'none'"/>
                            <xsl:with-param name="ycoordinate" select="'none'"/>
                             <xsl:with-param name="edgeposition" select="'none'"/>
                        </xsl:call-template>
                    </xsl:when>

                    <xsl:otherwise> -->
                <xsl:choose>
                    <xsl:when test="$strippedtagName = $xcoordinate">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:attribute name="x">
                                <xsl:value-of select="$tagValue - $lowestX"/>
                            </xsl:attribute>
                        </xsl:if>
                    </xsl:when>

                    <xsl:when test="$strippedtagName = $ycoordinate">
                        <!-- manier vinden om lowestY van deze coordinate af te halen, krijg nu foutmelding -->
                        <xsl:if test="string-length($tagValue)">
                            <xsl:attribute name="y">
                                <xsl:value-of select="$tagValue - $lowestY"/>
                            </xsl:attribute>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="$strippedtagName = $edgeposition">
                        <xsl:if test="string-length($tagValue)">
                            <xsl:attribute name="edge">
                                <xsl:value-of select="$tagValue"/>
                            </xsl:attribute>
                        </xsl:if>
                    </xsl:when>
                </xsl:choose>
                <!--   </xsl:otherwise>
                </xsl:choose> -->
            </xsl:if>
            <xsl:call-template name="tokenizeGeo">
                <xsl:with-param name="pText" select="substring-after($pText, $separator)"/>
                <xsl:with-param name="separator" select="$separator"/>
                <xsl:with-param name="xcoordinate" select="$xcoordinate"/>
                <xsl:with-param name="ycoordinate" select="$ycoordinate"/>
                <xsl:with-param name="lowestX" select="$lowestX"/>
                <xsl:with-param name="lowestY" select="$lowestY"/>
                <xsl:with-param name="edgeposition" select="$edgeposition"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>

    <!-- START COLUMN -->
    <xsl:template match="Column">
        <xsl:param name="mode"/>

        <!-- UMLClassifier XMLATTRIBUTES -->
        <xsl:if test="$mode='xmlclassifierattributes'">
            <xsl:call-template name="ea_guid"/>
            <xsl:call-template name="Connector_Type"/>
            <xsl:call-template name="Abstract"/>
            <xsl:call-template name="Object_Type"/>
            <xsl:call-template name="IsSpec"/>
        </xsl:if>

        <!-- UMLClassifier NODES -->
        <xsl:if test="$mode='classifiernodes'">
            <xsl:call-template name="Name"/>
            <xsl:call-template name="Note"/>
            <xsl:call-template name="Stereotype"/>
        </xsl:if>

        <!-- UMLClassifierAttributes --><!--
                <xsl:if test="$mode='UMLClassifierAttributes'">
                     <xsl:call-template name="GUID.wiki"/>
                </xsl:if>
-->
        <!-- UMLConnector XMLATTRIBUTES -->
        <xsl:if test="$mode='xmlconnectorattributes'">
            <xsl:call-template name="ea_guid"/>
            <xsl:call-template name="Connector_Type"/>
        </xsl:if>

        <!-- UMLConnector NODES -->
        <xsl:if test="$mode='connectornodes'">
            <xsl:call-template name="Name"/>
            <xsl:call-template name="Notes"/>
            <xsl:call-template name="sourceRole"/>
            <xsl:call-template name="targetRole"/>
            <xsl:call-template name="sourceCardinality"/>
            <xsl:call-template name="targetCardinality"/>
        </xsl:if>

        <!-- UML Diagrams XMLATTRIBUTES -->
        <xsl:if test="$mode='xmldiagramattributes'">
            <!--    <xsl:call-template name="Name"/>-->
            <xsl:call-template name="ea_guid"/>
        </xsl:if>


    </xsl:template>
    <!-- END COLUMN -->


    <xsl:template name="GUID">
        <!-- <xsl:if test="@name='Author'">
             <UMLClassifierAttribute>
              <xsl:element name="author">
                 <xsl:value-of select="@value"/>
             </xsl:element>
             </UMLClassifierAttribute>
         </xsl:if>  -->
    </xsl:template>


    <!-- START COLUMN NODE TEMPLATES -->
    <xsl:template name="Name">
        <xsl:if test="@name='Name'">
            <xsl:element name="name">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>


    <xsl:template name="Stereotype">
        <xsl:if test="@name='Stereotype'">
            <xsl:element name="stereotype">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>


    <xsl:template name="Note">
        <xsl:if test="@name='Note'">
            <xsl:element name="documentation">
                <xsl:value-of select="translate(@value,'&#xA;','')"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <xsl:template name="Notes">
        <xsl:if test="@name='Notes'">
            <xsl:element name="documentation">
                <xsl:value-of select="translate(@value,'&#xA;','')"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <xsl:template name="sourceRole">
        <xsl:if test="@name='SourceRole'">
            <xsl:element name="sourceRole">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>
    <xsl:template name="targetRole">
        <xsl:if test="@name='DestRole'">
            <xsl:element name="targetRole">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>
    <xsl:template name="sourceCardinality">
        <xsl:if test="@name='SourceCard'">
            <xsl:element name="sourceCardinality">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>
    <xsl:template name="targetCardinality">
        <xsl:if test="@name='DestCard'">
            <xsl:element name="targetCardinality">
                <xsl:value-of select="@value"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>


    <!-- START COLUMN PROPERTY TEMPLATES -->
    <xsl:template name="ea_guid">
        <xsl:if test="@name='ea_guid'">
            <xsl:attribute name="id">
                <xsl:variable name="guid" select="@value"/>
                <xsl:value-of select="translate($guid,'{}','')"/>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>


    <xsl:template name="Connector_Type">
        <xsl:if test="@name='Connector_Type'">
            <xsl:attribute name="type">
                <xsl:value-of select="@value"/>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>

    <xsl:template name="Abstract">
        <xsl:if test="@name='Abstract'">
            <xsl:variable name="true" select="'true'"/>
            <xsl:variable name="false" select="'false'"/>
            <xsl:attribute name="abstract">
                <xsl:if test="@value='0'">
                    <xsl:value-of select="$false"/>
                </xsl:if>
                <xsl:if test="@value='1'">
                    <xsl:value-of select="$true"/>
                </xsl:if>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>

    <xsl:template name="Object_Type">
        <xsl:if test="@name='Object_Type'">
            <xsl:attribute name="type">
                <xsl:value-of select="@value"/>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>

    <xsl:template name="IsSpec">
        <xsl:if test="@name='IsSpec'">
            <xsl:variable name="true" select="'true'"/>
            <xsl:variable name="false" select="'false'"/>
            <xsl:attribute name="finalSpecialization">
                <xsl:if test="@value='FALSE'">
                    <xsl:value-of select="$false"/>
                </xsl:if>
                <xsl:if test="@value='TRUE'">
                    <xsl:value-of select="$true"/>
                </xsl:if>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>
    <!-- END COLUMN PROPERTY TEMPLATES -->


    <!-- START EXTENSION -->
    <xsl:template match="Extension">

        <!-- <Extension Start_Object_ID="{E79E8DD6-652B-4341-B6AD-A18DC1F7009B}" End_Object_ID="{9743EF26-B4CB-49c9-ADB4-F0B9C402FC7E}"/> -->
        <xsl:if test="@Start_Object_ID">
            <xsl:attribute name="sourceID">
                <xsl:value-of select="translate(@Start_Object_ID, '{}','')"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="@End_Object_ID">
            <xsl:attribute name="targetID">
                <xsl:value-of select="translate(@End_Object_ID, '{}','')"/>
            </xsl:attribute>
        </xsl:if>

        <!--<Extension Object_ID="{18E43596-2F0A-4e34-9A4A-89D72E9B0C37}"/> -->
        <xsl:if test="@Object_ID">
            <xsl:attribute name="objectID">
                <xsl:value-of select="translate(@Object_ID, '{}','')"/>
            </xsl:attribute>
        </xsl:if>

        <!-- <Extension Package_ID="{5A1BF7CE-4879-41bd-81DD-0BDDC7F0BA53}"/> -->
        <xsl:if test="@Package_ID">
            <xsl:attribute name="packageID">
                <xsl:value-of select="translate(@Package_ID, '{}','')"/>
            </xsl:attribute>
        </xsl:if>




    </xsl:template>
    <!-- END EXTENSION -->

</xsl:stylesheet>


