<?php

/*
 * Convert a UML file (Export grom Enterprise architect in Native-format
 *
 * @author Toine Schijvenaars (XL&Knowledge), may 2023
 * @version 0.01
 */

if ( $_FILES ) {
	if ($_FILES["file"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No source file entered<br>";
		die;
	}


	$mode               = 'FORM';
	$source             = $_FILES["file"]["tmp_name"][0];
	$test               = '';


} else {
	$mode       = 'CLI';
	$source     = '';
	$test       = '';

	if ( isset($argv[1])) {
		$source     = $argv[1];  // source file
	}
	if ( isset($argv[2])) {
		$target     = $argv[2];
	}
	if ( isset($argv[3])) {
		$modelName  = $argv[3];
	}

}



$class = new AMEFF( $mode, $source, $test );
$class->run();

/**
 *
 */
class AMEFF {

	//ArchiMate variables
	//general
	const ARCHIMATE_ELEMENT = 'element';
	const ARCHIMATE_ELEMENTS = 'elements';
	const ARCHIMATE_RELATIONSHIP = 'relationship';
	const ARCHIMATE_RELATIONSHIPS = 'relationships';
	const ARCHIMATE_VIEW = 'view';
	const ARCHIMATE_VIEWS = 'views';
	const ARCHIMATE_DIAGRAMS = 'diagrams';
	const ARCHIMATE_PROPERTIES = 'properties';
	const ARCHIMATE_MODEL = 'model';
	const ARCHIMATE_DOCUMENTATION = 'documentation';
	const ARCHIMATE_PROPERTY = 'property';
	const ARCHIMATE_IDENTIFIER = 'identifier';
	const ARCHIMATE_NAME = 'name';
	const ARCHIMATE_TYPE = 'type';
	const ARCHIMATE_NODE = 'node';
	const ARCHIMATE_CONNECTION = 'connection';
	const ARCHIMATE_ELEMENTREF = 'elementRef';
	const ARCHIMATE_STYLE = 'style';
	const ARCHIMATE_FILLCOLOR = 'fillColor';
	const ARCHIMATE_LINECOLOR = 'lineColor';
	const ARCHIMATE_FONT = 'font';
	const ARCHIMATE_STYLING = 'styling';

	//ArchiMate 3 variables
	const ARCHIMATE3_PROPERTYDEFS = 'propertyDefinitions';
	const ARCHIMATE3_PROPERTYDEF = 'propertyDefinition';
	const ARCHIMATE3_INDENTIFIERREF = 'propertyDefinitionRef';
	const ARCHIMATE3_VIEWS = 'diagrams';


	// some constants
	const OBJECTID = 'Object ID';
	const ARCH_TOOL = 'Architectuurtool';
	const ELEMENTS = 'elements';
	const RELATIONSHIPS = 'relationships';
	const VIEWS = 'views';
	const VIEW = 'view';
	const TYPE = 'type';
	const ARCHIMATE_VERSION_2 = 'http://www.opengroup.org/xsd/archimate';
	const ARCHIMATE_VERSION_3 = 'http://www.opengroup.org/xsd/archimate/3.0/';
	const ARCHIMATE_VERSION_31 = 'http://www.opengroup.org/xsd/archimate/3.0/ http://www.opengroup.org/xsd/archimate/3.1/archimate3_Diagram.xsd';
	const VERSIONNR_2 = '2';
	const VERSIONNR_3 = '3';
	const UNKNOWN = 'onbekend';
	const CLI_MODE = 'CLI';
	const TEST_MODE = 'test';
	const NAME_SPACE = 'a';
	const NODE_TEXT_VALUE = '#text';
	const MERGE = 'merge';
	const XML_LANG = 'xml:lang';
	const NL = 'nl';

	// some private variables
	private $objectId;

	private $sourceFile;
	private $targetFile;
	private $modelName;
	private $archiMateVersion;

	private $sourceDOMDoc;

	private $sourceParser;

	// archimateversionvariables
	private $propertyDefs;
	private $propertyDef;

	// counters
	private $nrOfReplacedObjects = array();
	private $nrOfNewObjects = array();
	private $nrOfIgnoredObjects = array();

	// diff arrays
	private $changedElements = array();
	private $addedElements = array();

	private $changedViewNodeAttributes = array();
	private $changedViewNodeStyling = array();

	private $nodeId = '';
	private $refId = '';
	private $viewId = '';
	//output text
	private $output = '';
	// report analysis
	private $analysis = '';


	/**
	 *
	 * @param string $mode Form or CLI
	 * @param file $source UML-source file in ArchiMate format
	 * @param string $modelName Name of the ArchiMate model to be created
	 * @param string $test If value is 'test' in CLI-mode the there is more output to the screen
	 */
	public function __construct( $mode, $source, $test ) {

		$this->start = microtime( true );
		$this->mode = $mode;
		$this->sourceFile = $source;
		$this->test = $test;

		$this->prefix = '//' . self::NAME_SPACE . ':';
		$this->output .= "STATISTICS: \n";

		echo "Start: " . $this->getTime() . "\n";
		$this->output .= "Start: " . $this->getTime() . "\n";



	}

	/**
	 * this function will release the DOM documents variable
	 */
	public function __destruct() {
		unset( $this->sourceDOMDoc );
	}

	public function run() {

		// create source and target dom documents and parsers
		$this->createSource();

		// register namespaces for parser queries
		$this->registerNameSpaces();

		$umlContents = $this->processUMLinXML();

		//$result = $this->createAMEFF();

//		if ( $this->archiMateVersion ) {
//			if ( $this->archiMateVersion == self::UNKNOWN ) {
//				die ( "Unknown AMEFF-version: {$this->rootNamespaceSource}" );
//			} else {
//				// there are differences in the AMEFF versions so we need to map these
//				$this->setAMEFFVariables();
//
//				// create temporary list propertydef for fast searching (otherwise we need too many slow querypaths ....)
//				$this->listOfPropertydefsSource = $this->createPropertyDefArray();
//
//				// do the important stuff
//				//$result = $this->createAMEFF();
//			}
//		} else {
//			die ( "Domain model ({$this->rootNamespaceSource}) and " . "Local model ({$this->rootNamespaceTarget}) are of a different AMEFF-version." );
//		}
	}

	/*
	 * create source DOM document and XPATH parser
	 */
	private function createSource() {
		$sourceContent = $this->readContent( $this->sourceFile );
//		var_dump($sourceContent);
//		die;
		$this->sourceDOMDoc = new DOMDocument();
		if ( $this->sourceDOMDoc->loadXML( $sourceContent ) ) {
			$this->sourceParser = new DOMXpath( $this->sourceDOMDoc );
		} else {
			die ( 'Could not load XML string of ' . $this->sourceFile );
		}

	}


	/**
	 * register name spaces
	 */
	private function registerNameSpaces() {
		$this->rootNamespaceSource = $this->sourceDOMDoc->lookupNamespaceUri( $this->sourceDOMDoc->namespaceURI );
		$this->sourceParser->registerNamespace( self::NAME_SPACE, $this->rootNamespaceSource );
	}

	/**
	 * read file content in string
	 *
	 * @param filename $file
	 * @return string
	 */
	private function readContent( $file ) {
		if ( file_exists( $file ) ) {
			$content = file_get_contents( $file );
			if ( !$content ) {
				die ( "Could not load input source file" . PHP_EOL );
			}
		} else {
			die ( 'Could not find source file' . PHP_EOL );
		}
		echo "Content: ";
//		foreach ($content as $p) {
//			echo "P: ". $p;
//		};
		return $content;
	}


	/**
	 * This function is the main function for the merge of the two files.
	 */
	private function createAMEFF() {
		// Now we can start to generate AMEFF
		// collect data for processing
		echo "do your thing";
	}

	/**
	 * Calculation of the time in hours, minutes, seconds and microseconds
	 *
	 * @return string
	 */
	private function getTime() {
		$t = microtime(true);
		$micro = sprintf("%06d",($t - floor($t)) * 1000000);
		$d = new DateTime( date('H:i:s.'.$micro, $t) );

		return $d->format("H:i:s.u"); // note at point on "u"
	}

	/**
	 * Process the actual XML data of the UML file.
	 *
	 * @global type $wgRequest
	 * @global type $wgUMLReaderClass

	 *
	 * @param string $filename
	 *
	 * @return string $UMLinXMLstring
	 */
	private function processUMLinXML () {

		$model = null;
		$UMLinXMLstring = '';

		if ( ! ( $xmlFile = $this->writeTempFile($this->uploadedfilename ) ) ) {
die( "Couldn't write temp xml file!");
			//$this->smartcoreWarnings[] = "Couldn't write temp xml file!";
			wfDebugLog( 'SmartConnect',   __METHOD__ . ' #' . __LINE__ .  " Couldn't write temp xml file!." );
		} else {
			$this->result = new SmartConnectResult();

			// first we collect the xsl content
			$xslDoc = new DOMDocument();
			switch ($this->umlTool) {
				case wfMessage('scuml-fm-uml-tool-sparx')->parse():
					$xslfile = "/xls/sparx.xsl";
					break;
				case wfMessage('scuml-fm-uml-tool-star')->parse():
					//$xslfile = "/xls/star.xsl";
					die('not implemented yet');
					break;
				default:
					die('no UML Tool chosen');
			}
			$xslDoc->load(dirname(__FILE__) . $xslfile);


			// second we collect the xml content
			$xmlDoc = new DOMDocument();

			// remove unwanted characters etc. @TODO Check for necessity later
			$xmlContents = file_get_contents( $this->uploadedfilename );
			$xmlContents = $this->stripSparxXML ( $xmlContents );
			$strippedFile = $this->writeTempFile ($xmlContents, $ext = '.xml');
			$xmlDoc->load( $strippedFile ); //$filename

			// start XSLT processing
			$proc = new XSLTProcessor();
			$proc->importStylesheet($xslDoc);
			$UMLinXMLstring = $proc->transformToXML($xmlDoc);



			// TEST with this output we can view the WikiXL UML Format
			if ($this->state->testMode == 'test_xslt') {

				$downloadfname = 'UML_' . date("Y-m-d_h:i:s") . '_'. $this->basename  ;
				$wgOut->disable();
				$wgRequest->response()->header('Content-Type: application/xml; charset=utf-8');
				$wgRequest->response()->header("Content-disposition: attachment;filename={$downloadfname}");
				echo file_get_contents($this->writeTempFile($UMLinXMLstring, $ext = '.xml'));

				die();


			}
			// END TEST UML Format

		}
		return $UMLinXMLstring;

	}

	/**
	 * Strips the input file from different kinds of unwanted strings. Should be have been done by Sparx or Imvertor
	 *
	 * @param string $newXml
	 * @return string
	 */
	private function stripSparxXML  ( $newXml ) {

		$newXml = str_replace('value="#NOTES#', 'value="" notes="', $newXml);
		$newXml = str_replace('#NOTES#Description: ', '" notes="Description: ', $newXml);
		$newXml = str_replace('&lt;memo&gt;#NOTES#', '&lt;memo&gt;" notes="', $newXml);
		$newXml = str_replace('#NOTES#Values: ', '" notes="Values: ', $newXml);
		$newXml = str_replace('Default: &lt;memo&gt;&#xA;Description:', '', $newXml);
		$newXml = str_replace('Default: ', '', $newXml);
		$newXml = str_replace('Default:', '', $newXml);
		$newXml = str_replace('Description: ', '', $newXml);
		$newXml = str_replace('>NEE</imvert:value>', '>Nee</imvert:value>', $newXml);
		$newXml = str_replace('>JA</imvert:value>', '>Ja</imvert:value>', $newXml);


		return $newXml;

	}

	/**
	 * Writes content to a temporary file & returns file path
	 *
	 * @param string $contents
	 * @return bool|string
	 */
	private function writeTempFile ($contents, $ext = '.tmp') {

		global $wgTmpDirectory;
		// echo "TEMP: $wgTmpDirectory";
		// Create random file
//		$filePath = $wgTmpDirectory . uniqid() . $ext;
		// Create random file
		$filePath = uniqid() . $ext;
		if (file_put_contents($filePath, $contents) == false) {
		//	wfDebugLog( 'SmartConnect', __METHOD__ . ' #' . __LINE__ . "Couldn't write temporary file" );

			echo "gaat het NIET goed?";
			return false;

		}
		echo "gaat het goed?";
		die();
		return $filePath;

	}

}
