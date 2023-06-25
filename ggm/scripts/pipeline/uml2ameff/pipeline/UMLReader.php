	<?php
/**
 * UMLReader is the default reader pipeline component for SmartConnectUML
 *
 * @author Toine Schijvenaars, 2019
 */
class UMLReader extends SmartConnectReader {

    // Properties
    const PROPERTY_IMPORTDATETIME = 'ImportDateTime';
    const PROPERTY_IMPORTSOURCE = 'ImportSource';
    const PROPERTY_IMPORTJOBID = 'ImportJobID';
    const PROPERTY_UML_VERSION = 'UMLVersion';
    const PROPERTY_CUSTOM_PROPERTIES = 'CustomProperties';
    const PROPERTY_DOCUMENTATION = 'Documentation';
    const PROPERTY_NAME = 'Name';
    const PROPERTY_LEFT = 'Left';
    const PROPERTY_TOP = 'Top';
    const PROPERTY_RIGHT = 'Right';
    const PROPERTY_BOTTOM = 'Bottom';
    const PROPERTY_Z_ORDER = 'Z-coordinate';
    const PROPERTY_WIDTH = 'Width';
    const PROPERTY_HEIGHT = 'Height';
    const PROPERTY_COORDINATES= 'Coordinates';
    const PROPERTY_SEMANTICTITLE = "Semantictitle";

    // UML Properties
    const PROPERTY_ORIGINALID = 'OrginalID';
    const PROPERTY_HIDDEN = 'Hidden';
    const PROPERTY_STYLE = 'Style';

    const PROPERTY_MODE = 'StyleMode';
    const PROPERTY_SOID = 'StyleStartObjectID';
    const PROPERTY_EOID = 'StyleEndObjectID';
    const PROPERTY_COLOR = 'StyleColor';
    const PROPERTY_LABEL_WIDTH = 'StyleLabelWidth';
    const PROPERTY_TREE = 'StyleTree';

    const PROPERTY_FONT_SIZE = "StyleFontSize";
    const PROPERTY_FONT_BOLD ='StyleFontBold';
    const PROPERTY_FONT_BLACK = 'StyleFontBlack';
    const PROPERTY_FONT_ITALIC= 'StyleFontItalic';
    const PROPERTY_FONT_UNDERLINE = 'StyleFontUnderline';

    const PROPERTY_SOURCEATTACHMENT = 'SourceAttachment';
    const PROPERTY_SOURCEATTACHMENT_X = 'SourceAttachment_X';
    const PROPERTY_SOURCEATTACHMENT_Y = 'SourceAttachment_Y';
    const PROPERTY_SOURCEATTACHMENT_EDGE = 'SourceAttachment_Edge';
    const PROPERTY_TARGETATTACHMENT = '$targetAttachment';
    const PROPERTY_TARGETATTACHMENT_X = '$targetAttachment_X';
    const PROPERTY_TARGETATTACHMENT_Y = '$targetAttachment_Y';


    //Relations
    const RELATION_OCCURS_IN_MODEL = 'Occurs in model';
    const RELATION_BELONGS_TO_PACKAGE = 'Refers to package';
    const RELATION_BELONGS_TO_DIAGRAM = 'Belongs to diagram';
    const RELATION_BELONGS_TO_CLASS = 'Belongs to class';
    const RELATION_BELONGS_TO_OPERATION = 'Belongs to operation';
    const RELATION_REFERS_TO_RELATION = 'Refers to relation';
    const RELATION_REFERS_TO_ELEMENT = 'Refers to element';
    const RELATION_REFERS_TO_SOURCE = 'Refers tot source';
    const RELATION_REFERS_TO_TARGET = 'Refers tot target';
    const RELATION_REFERS_TO_PARENT = 'Refers to parent';

    const PARAM_IMPORTFILE = 'file';
    const PARAM_DRYRUN = 'dryrun';

    //Tags
    const TAG_NAME = 'name';
    const TAG_DOCUMENTATION = 'documentation';

    //Class Types
    const ATTRIBUTE_CLASS = 'Class';
    const ATTRIBUTE_ENUMERATION = 'Enumeration';
    const ATTRIBUTE_PART = 'Part';
    const ATTRIBUTE_PROXYCONNECTOR = 'ProxyConnector';


    const XML = 'xml';
    const PNG = 'png';


    const UML_MODEL = 'UMLModel';
    const UML_ATTRIBUTE = 'UMLAttribute';
    const UML_DIAGRAM = 'UMLView';
    const UML_DIAGRAMELEMENT = 'UMLViewNode';
    const UML_DIAGRAMCONNECTION = 'UMLViewConnection';
    const UML_RELATIONSHIP = 'UMLRelationship';
    const UML_OPERATION = 'UMLOperation';
    const UML_OPERATIONPARAMETER = 'UMLOperationParameter';
    const UML_VERSION_10 = '1.0';



    /* @var $model SmartCoreModel */
    public $model;

    /* @var $parser DOMXPath */
    public $parser;
    public $modelId;
    public $result;

    private $importJobID;
    private $umlTool;
    private $importMode;
    private $basename;


    /**
     * Here we instantiate a DomDocument and import the XML content for further processing
     *
     * @param SmartConnectResult $result
     * @param string $xmlContents
     * @param string $state
     */
    public function __construct( SmartConnectResult $result, $xmlContents, $state) {
        parent::__construct($result, $state->getID() );

        // Set default timezone to prevent errors
        date_default_timezone_set ( 'Europe/Amsterdam' );

        // get the state variables for use in this class
        $this->importJobID = $state->getID() ;//$importJobID;
        $this->umlTool = $state->umlTool;
        $this->importMode = $state->testMode;
        $this->basename = $state->basename;

        $DOMDocument = new DOMDocument ();
        $DOMDocument->preserveWhiteSpace = false;
        wfDebugLog('SmartConnect', __METHOD__ . ' #' . __LINE__ . " Loading DOM");
        $libxml_previous_state = libxml_use_internal_errors(true);
        if ($DOMDocument->loadXML($xmlContents, LIBXML_NOWARNING + LIBXML_NOERROR)) {

            $parser = new DOMXPath ($DOMDocument);
            wfDebugLog('SmartConnect', __METHOD__ . ' #' . __LINE__ . " Parsing.");

            $this->parser = $parser;

            // here we start processing
            $this->processModel();
            wfDebugLog('SmartConnect', __METHOD__ . ' #' . __LINE__ . " Parsing done.");
            // for memory efficiency: throw the domdocument and xpath parser away again. -
            // there's no need to keep the XML in memory, only the SmartCore model is needed.
            $this->parser = null;
        }
        //Clear error queue and restore error settings
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous_state);
        //If successful, move the smartcore model to an instance variable for future reference

        return $this->model;
    }

    /**
     * Returns the SmartCore Model
     *
     * @return SmartCoreModel
     */
    public function getSmartCoreModel() {
        return $this->model;
    }


    /**
     * sets the SmartCoreModel
     *
     * @param SmartCoreModel $scm
     */
    public function setSmartCoreModel(SmartCoreModel $scm) {
        $this->model = $scm;
    }


    /**
     * Here starts the processing of the model.
     *
     * @return boolean
     */

    private function processModel()
    {

        $this->model = new SmartCoreModel();
        $this->modelId = $this->parser->query('/UMLModel/@id')->item(0)->nodeValue;
        //echo $this->modelId . PHP_EOL;
        $umlModelSCElement = $this->createNewSCElement($this->modelId, self::UML_MODEL);
        $umlModelSCElement->addPropertyByID(self::PROPERTY_IMPORTDATETIME, date('c'));
        $umlModelSCElement->addPropertyByID(self::PROPERTY_IMPORTSOURCE, $this->umlTool);
        $umlModelSCElement->addPropertyByID(self::PROPERTY_IMPORTJOBID, $this->importJobID);
        $umlModelSCElement->addPropertyByID(self::PROPERTY_UML_VERSION, self::UML_VERSION_10); //always set to 1.0
        $this->processModelNodes($umlModelSCElement);
        //TESTING!!!
        if ($this->importMode == SmartConnectUML::VALUE_TEST_PAGE) {
            $this->wikipagesOutputforTesting();
        }
        return true;

    }

    /**
     * produces texts for wiki pages as test output end send result as file to the browser
     */
    private function wikipagesOutputforTesting(){
        global $wgOut, $wgRequest;
        $downloadfname = 'WP_' . date("Y-m-d_h:i:s") . '_' . $this->basename  ;
        $wgOut->disable();
        $wgRequest->response()->header('Content-Type: application/xml; charset=utf-8');
        $wgRequest->response()->header("Content-disposition: attachment;filename={$downloadfname}");
        $smartCoreElements = $this->model->getElements();
        $html = "==SmartCore Elements ==\n";
        foreach ($smartCoreElements as $element) {
            $html .= "<pre>\n" . $element->toWikiText() . "\n</pre>";
        }

        die($html);
    }

    /**
     * processes all nodes in the model. Elements, Relations and Diagrams
     * @param SmartCoreElement $umlModelSCElement
     */
    private function processModelNodes(SmartCoreElement $umlModelSCElement)
    {
        $nodeList = array(self::TAG_NAME, self::TAG_DOCUMENTATION);
        $this->processNodeAttributes($nodeList, $umlModelSCElement);
        $this->processElements(self::ATTRIBUTE_CLASS);
        $this->processElements(self::ATTRIBUTE_ENUMERATION);
        $this->processElements(self::ATTRIBUTE_PART);
        $this->processElements(self::ATTRIBUTE_PROXYCONNECTOR);
        $this->processRelations();
        $this->processDiagrams();
    }

    /**
     * processes all diagrams
     */
    private function processDiagrams()
    {
        $diagramNodes = $this->parser->query('//UMLDiagram');
        foreach ($diagramNodes as $diagramNode) {
            $diagramId = $this->parser->query('@id', $diagramNode)->item(0)->nodeValue;
            $diagramPackageId = $this->parser->query('@packageID', $diagramNode)->item(0)->nodeValue;
            $diagramElement = $this->createNewSCElement($diagramId, self::UML_DIAGRAM);
            $diagramElement->addPropertyByID(self::RELATION_BELONGS_TO_PACKAGE, $diagramPackageId);
            foreach ($diagramNode->childNodes as $childNode) {
                $this->processDiagramNodes($diagramNode, $diagramId);
                $this->processDiagramRelations($diagramNode, $diagramId);
            }
        }

    }

    /**
     * Processes the diagram relations of a given diagram
     * @param DOMNode $diagramRelation
     * @param string $diagramId
     */
    private function processDiagramRelations($diagramRelation, $diagramId)
    {
        $diagramRelationNodes = $this->parser->query('UMLDiagramRelations/UMLDiagramRelation', $diagramRelation);

        foreach ($diagramRelationNodes as $diagramRelationNode)
        {
            // get id
            $nodeId = $this->parser->query('@id', $diagramRelationNode)->item(0)->nodeValue;
            $nodeRefersToConnectionId = $this->parser->query('@connectionid', $diagramRelationNode)->item(0)->nodeValue;
            $newNodeid = $this->createUUID($diagramId . $nodeRefersToConnectionId  );  //only unique if element occurs once in a diagram
            // create relationElement
            $nodeElement = $this->createNewSCElement($newNodeid, self::UML_DIAGRAMCONNECTION);
            // get necessary values
            $hidden = $this->parser->query('hidden', $diagramRelationNode)->item(0)->nodeValue;


            // initialise
            $style = false;
            $mode = false;
            $eoid = false;
            $soid = false;
            $color = false;
            $lwidth = false;
            $tree = false;

            // get the style properties if available
            $styleNode = $this->parser->query('style', $diagramRelationNode);
            $modeNode = $this->parser->query('Mode', $diagramRelationNode);
            $eoidNode = $this->parser->query('EOID', $diagramRelationNode);
            $soidNode = $this->parser->query('SOID', $diagramRelationNode);
            $colorNode = $this->parser->query('Color', $diagramRelationNode);
            $lwidthNode = $this->parser->query('LWidth', $diagramRelationNode);
            $treeNode = $this->parser->query('TREE', $diagramRelationNode);

            if ( $styleNode->length > 0 ) {
                $style = $this->parser->query('style', $diagramRelationNode)->item(0)->nodeValue;
            };
            if ( $modeNode->length > 0 ) {
                $mode = $this->parser->query('Mode', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $eoidNode->length > 0 ) {
                $eoid = $this->parser->query('EOID', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $soidNode->length > 0 ) {
                $soid = $this->parser->query('SOID', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $colorNode->length > 0 ) {
                $color = $this->parser->query('Color', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $lwidthNode->length > 0 ) {
                $lwidth = $this->parser->query('LWidth', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $treeNode->length > 0 ) {
                $tree = $this->parser->query('TREE', $diagramRelationNode)->item(0)->nodeValue;
            }


            // get the connection coordinates if available
            $sourceAttachmentXNode = $this->parser->query('sourceAttachement/@x', $diagramRelationNode);
            $sourceAttachmentYNode = $this->parser->query('sourceAttachement/@y', $diagramRelationNode);
            $sourceAttachmentENode = $this->parser->query('sourceAttachement/@edge', $diagramRelationNode);
            $targetAttachmentXNode = $this->parser->query('targetAttachement/@x', $diagramRelationNode);
            $targetAttachmentYNode = $this->parser->query('targetAttachement/@y', $diagramRelationNode);

            // initialise
            $sourceAttachmentX = false;
            $sourceAttachmentY = false;
            $sourceAttachmentE = false;
            $targetAttachmentX = false;
            $targetAttachmentY = false;

            // and set the values
            if ( $sourceAttachmentXNode->length > 0 ) {
                $sourceAttachmentX = $this->parser->query('sourceAttachement/@x', $diagramRelationNode)->item(0)->nodeValue;
            };
            if ( $sourceAttachmentYNode->length > 0 ) {
                $sourceAttachmentY = $this->parser->query('sourceAttachement/@y', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $sourceAttachmentENode->length > 0 ) {
                $sourceAttachmentE = $this->parser->query('sourceAttachement/@edge', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $targetAttachmentXNode->length > 0 ) {
                $targetAttachmentX = $this->parser->query('targetAttachement/@x', $diagramRelationNode)->item(0)->nodeValue;
            }
            if ( $targetAttachmentYNode->length > 0 ) {
                $targetAttachmentY = $this->parser->query('targetAttachement/@y', $diagramRelationNode)->item(0)->nodeValue;
            }

            $coordinateString = '';
            $bendPoints = $this->parser->query('bendpoints/bendpoint', $diagramRelationNode);
            foreach ($bendPoints as $bendPoint) {
                $x = $this->parser->query('@x', $bendPoint)->item(0)->nodeValue;
                $y = $this->parser->query('@y', $bendPoint)->item(0)->nodeValue;
                $coordinateString .= '(' . $x . ";" . $y . '),';
            }
            $coordinateString = rtrim($coordinateString,',');;

            // put all the variables in properties
            // default
            if ($nodeId) { $nodeElement->addPropertyByID(self::PROPERTY_ORIGINALID, $nodeId); };
            if ($diagramId) { $nodeElement->addPropertyByID(self::RELATION_BELONGS_TO_DIAGRAM, $diagramId); };
            if ($nodeRefersToConnectionId) { $nodeElement->addPropertyByID(self::RELATION_REFERS_TO_RELATION, $nodeRefersToConnectionId); };
            if ($hidden) { $nodeElement->addPropertyByID(self::PROPERTY_HIDDEN, strtolower($hidden)); };

            // styling properties
            if ($style) { $nodeElement->addPropertyByID(self::PROPERTY_STYLE, $style); };
            if ($mode) { $nodeElement->addPropertyByID(self::PROPERTY_MODE, $mode); };
            if ($soid) { $nodeElement->addPropertyByID(self::PROPERTY_SOID, $soid); };
            if ($nodeId) { $nodeElement->addPropertyByID(self::PROPERTY_EOID, $eoid); };
            if ($color) { $nodeElement->addPropertyByID(self::PROPERTY_COLOR, $color); };
            if ($lwidth) { $nodeElement->addPropertyByID(self::PROPERTY_LABEL_WIDTH, $lwidth); };
            if ($tree ) { $nodeElement->addPropertyByID(self::PROPERTY_TREE, $tree); };

            //node connection properties
            if ($sourceAttachmentX) { $nodeElement->addPropertyByID(self::PROPERTY_SOURCEATTACHMENT_X, $sourceAttachmentX); };
            if ($sourceAttachmentY) { $nodeElement->addPropertyByID(self::PROPERTY_SOURCEATTACHMENT_Y, $sourceAttachmentY); };
            if ($sourceAttachmentE) { $nodeElement->addPropertyByID(self::PROPERTY_SOURCEATTACHMENT_EDGE, $sourceAttachmentE); };
            if ($sourceAttachmentX & $sourceAttachmentY) { $nodeElement->addPropertyByID(self::PROPERTY_SOURCEATTACHMENT, "($sourceAttachmentX;$sourceAttachmentY)"); };
            if ($coordinateString) { $nodeElement->addPropertyByID(self::PROPERTY_COORDINATES, $coordinateString); };
            if ($targetAttachmentX) { $nodeElement->addPropertyByID(self::PROPERTY_TARGETATTACHMENT_X, $targetAttachmentX); };
            if ($targetAttachmentY) { $nodeElement->addPropertyByID(self::PROPERTY_TARGETATTACHMENT_Y, $targetAttachmentY); };
            if ($targetAttachmentX & $targetAttachmentY) { $nodeElement->addPropertyByID(self::PROPERTY_TARGETATTACHMENT, "($targetAttachmentX;$targetAttachmentY)"); };



        }
    }

    /**
     * Processes the nodes of a given diagram
     *
     * @param DOMNode $diagramNode
     * @param string $diagramId
     */
    private function processDiagramNodes($diagramNode, $diagramId)
    {
        $diagramNodes = $this->parser->query('UMLDiagramObjects/UMLDiagramObject', $diagramNode);

        foreach ($diagramNodes as $diagramNode)
        {
            $nodeId = $this->parser->query('@id', $diagramNode)->item(0)->nodeValue;
            $nodeRefersToClassId = $this->parser->query('@objectid', $diagramNode)->item(0)->nodeValue;
            $newNodeId = $this->createUUID( $diagramId . $nodeRefersToClassId ); // only unique if element occurs once in a diagram
            $nodeElement = $this->createNewSCElement($newNodeId, self::UML_DIAGRAMELEMENT);

            $nodeLeft = $this->parser->query('@x', $diagramNode)->item(0)->nodeValue;
            $nodeTop = $this->parser->query('@y', $diagramNode)->item(0)->nodeValue;
            $nodeZOrder = $this->parser->query('@z', $diagramNode)->item(0)->nodeValue;
            $nodeWidth = $this->parser->query('@w', $diagramNode)->item(0)->nodeValue;
            $nodeHeight = $this->parser->query('@h', $diagramNode)->item(0)->nodeValue;

            // initialise
            $fontsz = false;
            $bold = false;
            $black = false;
            $italic = false;
            $ul = false;

            // get the style properties if available
            $fontszNode = $this->parser->query('fontsz', $diagramNode);
            $boldnode = $this->parser->query('bold', $diagramNode);
            $blacknode = $this->parser->query('black', $diagramNode);
            $italicnode = $this->parser->query('italic', $diagramNode);
            $ulmode = $this->parser->query('ul', $diagramNode);

            // and set the values
            if ( $fontszNode->length > 0 ) {
                $fontsz = $this->parser->query('fontsz', $diagramNode)->item(0)->nodeValue;
            };
            if ( $boldnode->length > 0 ) {
                $bold = $this->parser->query('bold', $diagramNode)->item(0)->nodeValue;
            };
            if ( $blacknode->length > 0 ) {
                $black = $this->parser->query('black', $diagramNode)->item(0)->nodeValue;
            };
            if ( $italicnode->length > 0 ) {
                $italic = $this->parser->query('italic', $diagramNode)->item(0)->nodeValue;
            };
            if ( $ulmode->length > 0 ) {
                $ul = $this->parser->query('ul', $diagramNode)->item(0)->nodeValue;
            };

            // add set the properties
            $nodeElement->addPropertyByID(self::PROPERTY_ORIGINALID, $nodeId);
            $nodeElement->addPropertyByID(self::RELATION_BELONGS_TO_DIAGRAM, $diagramId);
            $nodeElement->addPropertyByID(self::PROPERTY_LEFT, $nodeLeft);
            $nodeElement->addPropertyByID(self::PROPERTY_TOP, $nodeTop);
            /* calculate the right and bottom coordinates */
            $nodeElement->addPropertyByID(self::PROPERTY_RIGHT, $nodeLeft +  $nodeWidth);
            $nodeElement->addPropertyByID(self::PROPERTY_BOTTOM, $nodeTop - $nodeHeight);
            $nodeElement->addPropertyByID(self::PROPERTY_Z_ORDER, $nodeZOrder);
            /* are these necessary?? */
            $nodeElement->addPropertyByID(self::PROPERTY_WIDTH, $nodeWidth);
            $nodeElement->addPropertyByID(self::PROPERTY_HEIGHT, $nodeHeight);
            /* are these necessary?? */
            $nodeElement->addPropertyByID(self::RELATION_REFERS_TO_ELEMENT, $nodeRefersToClassId);

            if ($fontsz) { $nodeElement->addPropertyByID(self::PROPERTY_FONT_SIZE, $fontsz); };
            if ($bold) { $nodeElement->addPropertyByID(self::PROPERTY_FONT_BOLD, $bold); };
            if ($black) { $nodeElement->addPropertyByID(self::PROPERTY_FONT_BLACK, $black); };
            if ($italic) { $nodeElement->addPropertyByID(self::PROPERTY_FONT_ITALIC, $italic); };
            if ($ul) { $nodeElement->addPropertyByID(self::PROPERTY_FONT_UNDERLINE, $ul); };

            $customProperties = array();
            foreach ($diagramNode->childNodes as $childNode)
            {
                $customProperties[] = $this->addKeyValue($nodeElement, $childNode);
            }
            $nodeElement->addPropertyByID(SELF::PROPERTY_CUSTOM_PROPERTIES, implode( ', ', $customProperties ) );
        }
    }

    /**
     *
     * process the model relations and adds it to the DOMModel
     */
    private function processRelations () {
        $relationNodes =  $this->parser->query ( '//UMLConnector' );

        foreach ( $relationNodes as $relationNode ) {
            $relationId  = $this->parser->query ( '@id', $relationNode )->item(0)->nodeValue;
            $relationType = $this->parser->query ( '@type', $relationNode )->item(0)->nodeValue;
            $relationSourceId = $this->parser->query ( '@sourceID', $relationNode )->item(0)->nodeValue;
            $relationTargetId = $this->parser->query ( '@targetID', $relationNode )->item(0)->nodeValue;

            $relationElement = $this->createNewSCElement($relationId, self::UML_RELATIONSHIP);
            if ($relationType) {
                $relationElement->addPropertyByID('Stereotype', $relationType);
            }
            $relationElement->addPropertyByID(self::RELATION_REFERS_TO_SOURCE, $relationSourceId);
            $relationElement->addPropertyByID(self::RELATION_REFERS_TO_TARGET, $relationTargetId);
            $customProperties = array();
            foreach ($relationNode->childNodes as $childNode) {
                $customProperties[] = $this->addKeyValue($relationElement, $childNode);
            }
            $relationElement->addPropertyByID(SELF::PROPERTY_CUSTOM_PROPERTIES, implode( ', ', $customProperties ) );
        }
    }

    /**
     * Processes a list of child nodes and adds the values to the SmartCoreElement
     *
     * @param array $nodeList
     * @param modeElement $contextNode
     * @return bool
     */
    private function processNodeAttributes ( $nodeList, $modelElement )  {
        foreach ($nodeList as $modelNode) {
            $modelNodeValue = $this->parser->query ( '/UMLModel/' .  $modelNode  )->item(0)->nodeValue;
            $modelElement->addPropertyByID($modelNode, $modelNodeValue);
            if (  strtoupper( $modelNode ) == strtoupper( self::PROPERTY_NAME ) ) {
                $modelElement->addPropertyByID(self::PROPERTY_SEMANTICTITLE, $modelNodeValue);
            }
        }
        return true;
    }

    /**
     * process the elements
     *
     * @param string $elementType
     * @return bool
     */
    private function processElements ($elementType) {
        $classifierNodes =  $this->parser->query ( '//UMLClassifier[@type="' . $elementType . '"]' );
        wfDebugLog( 'SmartConnect',  __METHOD__ . ' #' . __LINE__ . " Processing " . $classifierNodes->length . $elementType. " nodes." );
        foreach ($classifierNodes as $classNode) {

            $classId  = $this->parser->query ( '@id', $classNode )->item(0)->nodeValue;
            $classElement =  $this->createNewSCElement($classId, 'UML' . $elementType);
            if ($elementType==self::ATTRIBUTE_PART) {
                $parentIdNode = $this->parser->query('@parentID', $classNode);
                if (!is_object($parentIdNode)) {
                    $parentId = $parentIdNode->item(0)->nodeValue;
                    $classElement->addPropertyByID(self::RELATION_REFERS_TO_PARENT, $parentId);
                }
            }

            // get the other nodes except the attributes
            foreach ($classNode->childNodes as $childNode) {
                $label = ucfirst($childNode->nodeName);
                $value = $childNode->nodeValue;



                switch ($label) {
                    case 'UMLClassifierAttributes':
                        $attributeNodes = $this->parser->query('UMLClassifierAttributes/UMLClassifierAttribute', $classNode);

                        foreach ($attributeNodes as $attributeNode) {
                            $attributeId = $this->parser->query('@id', $attributeNode)->item(0)->nodeValue;
                            $attributeElement = $this->createNewSCElement($attributeId, self::UML_ATTRIBUTE);
                            $attributeElement->addPropertyByID(self::RELATION_BELONGS_TO_CLASS, $classId);
                            $customProperties = array();
                            foreach ($attributeNode->childNodes as $childNode) {
                                $customProperties[] = $this->addKeyValue($attributeElement, $childNode);
                            }
                            $attributeElement->addPropertyByID(self::PROPERTY_CUSTOM_PROPERTIES, implode( ', ', $customProperties ) );
                        }
                        break;
                    case 'UMLClassifierOperations': //@TODO
                        $operationNodes = $this->parser->query('UMLClassifierOperations/UMLClassifierOperation', $classNode);

                        foreach ($operationNodes as $operationNode) {
                            $operationId = $this->parser->query('@id', $operationNode)->item(0)->nodeValue;
                            $operationElement = $this->createNewSCElement($operationId, self::UML_OPERATION);
                            $operationElement->addPropertyByID(self::RELATION_BELONGS_TO_CLASS, $classId);
                            $customProperties = array();
                            foreach ($operationNode->childNodes as $childNode) {
                                $customProperties[] = $this->addKeyValue($operationElement, $childNode);
                            }
                            $operationElement->addPropertyByID(self::PROPERTY_CUSTOM_PROPERTIES, implode( ', ', $customProperties ) );

                            // and we also pick up the Operation Parameters here if available
                            $operationParamNodes = $this->parser->query('UMLClassifierOperationParameters/UMLClassifierOperationParameter', $operationNode);

                            foreach ($operationParamNodes as $operationParamNode) {
                                $operationParamId = $this->parser->query('@id', $operationParamNode)->item(0)->nodeValue;
                                $operationParamElement = $this->createNewSCElement($operationParamId, self::UML_OPERATIONPARAMETER);
                                $operationParamElement->addPropertyByID(self::RELATION_BELONGS_TO_OPERATION, $operationId);
                                $customParamProperties = array();
                                foreach ($operationParamNode->childNodes as $childNode) {
                                    $customParamProperties[] = $this->addKeyValue($operationParamElement, $childNode);
                                }
                                $operationElement->addPropertyByID(self::PROPERTY_CUSTOM_PROPERTIES, implode( ', ', $customParamProperties ) );
                            }
                        }
                        break;

                    case 'UMLCLassifierReceptions': //@TODO
                        break;

                    default:
                        $classElement->addPropertyByID($label, $value);
                        if ($label == self::PROPERTY_NAME ) {
                            $classElement->addPropertyByID(self::PROPERTY_SEMANTICTITLE, $value);
                        }
                        break;
                }
            }
        }
        return true;

    }

    /**
     * add name as the semantic value of this node
     * @param DOMNode $element
     * @param DOMNode $childNode
     * @return string
     */
    private function addKeyValue ($element, $childNode) {
        $attrLabel = ucfirst($childNode->nodeName);
        $attrValue = $childNode->nodeValue;
        $element->addPropertyByID($attrLabel, $attrValue);
        if ($attrLabel == self::PROPERTY_NAME ) {
            $element->addPropertyByID(self::PROPERTY_SEMANTICTITLE, $attrValue);
        }
        return $attrLabel;
    }



    /**
     * This function creates a new SmartCore element for the given ID and type, and sets the default import properties
     *
     * @param String $elementID the ID of the new SmartCore element
     * @param String $elementType the type of the new SmartCore element
     * @return SmartCoreElement the newly created SmartCore element
     */
    private function createNewSCElement($elementID, $elementType) {

        //wfDebugLog('SmartConnect', __METHOD__ . ' #' . __LINE__ . ' Entering method (' . $elementType . '>' . $elementID . ')');

        if (!empty($elementID) && !empty($elementType)) {
            //Create SCElement and set title and type
            $scElement = $this->model->createElement($elementID);
            $scElement->setTitle($elementID);
            $scElementType = $this->model->createElementType($elementType);
            $scElement->setType($scElementType);

            //Set basic import properties for all SCElements

            $scElement->addPropertyByID(self::RELATION_OCCURS_IN_MODEL, $this->modelId);

        } else {
            $scElement = false;
        }
        //wfDebugLog('SmartConnect', __METHOD__ . ' #' . __LINE__ . ' Completing method (' . !!$scElement . ')');

        return $scElement;
    }

    /**
     * creates an UUID, universal unique identifier on the basis of a given value, default based on current time value
     *
     * @param string $id
     * @return string
     */
    private function createUUID($id = null){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            if (is_null( $id )) {
                $charid = strtoupper( md5( uniqid( rand(), true ) ) );
            } else {
                $charid =strtoupper( md5( $id ) );
            }

            $hyphen = chr(45);
            $uuid =
                substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }



}

