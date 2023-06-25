<?php
/*
 * Convert a TOPdesk CSV file (Export from TOPdesk in CSV-format
 *
 * @author Toine Schijvenaars (XL&Knowledge), may 2023
 * @version 0.1
 */


include __DIR__ . "/../../csvreader/CSVReader.php";
include __DIR__ . "/../../archicsvwriter/ArchiCSVWriter.php";

if ( $_FILES ) {
	if ($_FILES["fileObjects"]["error"][0] > 0) {
		echo "Error: " . $_FILES["fileObjects"]["error"][0] . " No source file entered<br>";
		die;
	}


	$mode                	 	= 'FORM';
	$sourceObjectDataFile    	= $_FILES["fileObjects"]["tmp_name"][0];
	$sourcePropertiesDataFile   = '';//$_FILES["fileProperties"]["tmp_name"][0];
	$sourceRelationsDataFile 	= $_FILES["fileRelations"]["tmp_name"][0];
	$archiZipFile               = $_FILES["fileArchi"]["tmp_name"][0];
	// only when the ckeckbox is checked is has usable value
	if (isset( $_POST["test"][0])) {
		$test = $_POST["test"][0];
	} else {
		$test = false;
	}

} else {
	$mode       		 	  = 'CLI';
	$sourceObjectDataFile     = '';
	$sourcePropertiesDataFile = '';
	$sourceRelationsDataFile  = '';
	$archiZipFile 			  = '';
	$modelName  			  = '';
	$test       			  = '';

	if ( isset($argv[1])) {
		$sourceObjectDataFile     = $argv[1];  // source file data
	}
	if ( isset($argv[2])) {
		$sourcePropertiesDataFile = $argv[3]; // source file relations
	}
	if ( isset($argv[3])) {
		$sourceRelationsDataFile  = $argv[3]; // source file relations
	}
	if ( isset($argv[4])) {
		$modelName  = $argv[4];
	}
}

$class = new GGM2ARCHI( $mode, $sourceObjectDataFile, $sourcePropertiesDataFile, $sourceRelationsDataFile, $archiZipFile, $test );

$class->run();

class GGM2ARCHI {
	private $mode;
	private $sourceObjectDataFile;
	private $sourcePropertiesDataFile;
	private $sourceRelationsData;
	private $messageCounter;
	private $elementName;


	private $listOfElements = array();
	private $listOfProperties = array();
	private $listOfRelations = array();
	private $listOfMessages = array();
	private $listOfReplacements = array();



	public function __construct( $mode, $sourceObjectDataFile, $sourcePropertiesDataFile, $sourceRelationsData, $archiZipFile, $test ) {
		$this->mode = $mode;
		$this->sourceObjectDataFile = $sourceObjectDataFile;
		$this->sourcePropertiesDataFile = $sourcePropertiesDataFile;
		$this->sourceRelationsData = $sourceRelationsData;
		$this->archiZipFile = $archiZipFile;
		$this->exportDate = date( "\"dmY-h:m:s\"" );
		$this->test = $test;
	}

	/**
	 * main function
	 * - reading the data
	 * - processing the data
	 * - write to Archi-csv format
	 */
	public function run() {

		$result = CSVReader::processArchiZipfile($this->archiZipFile);
		$this->archiElements = $result[0];
		$this->archiProperties = $result[1];
		$this->archiRelations = $result[2];


		//		TESTING Archi-lists
//		ArchiCSVWriter::dumpDataOnScreen($this->archiProperties);
//		ArchiCSVWriter::showOnScreen($this->archiElements, $this->archiProperties, $this->archiRelations);
//
//		die;

		// Processing objects
		$csvObjectsReader = new CSVReader();
		$this->ggmObjects = $csvObjectsReader::readCSVDataFromFile( $this->sourceObjectDataFile );
//		ArchiCSVWriter::dumpDataOnScreen($this->ggmObjects);
//		die;
		$this->processDataObjects();

//		TESTING Objects
//		ArchiCSVWriter::showOnScreen($this->listOfElements, $this->listOfProperties, $this->listOfRelations);
//		die;

		// Properties. We leave this out of the import. They also need to be Elements with relations to the objects
//		$csvPropertiesReader = new CSVReader();
//		$this->ggmProperties = $csvPropertiesReader::readCSVDataFromFile( $this->sourcePropertiesDataFile );

//		ArchiCSVWriter::dumpDataOnScreen($this->ggmProperties);
//		die;
//		$this->processDataProperties( 'Search' );

		//TESTING Properties
//		ArchiCSVWriter::dumpDataOnScreen($this->ggmProperties);
//		echo "<pre>"; var_dump($this->listOfProperties);die;
//		die;

		// Relations
		$csvRelationsReader = new CSVReader();
		$this->ggmRelations = $csvRelationsReader::readCSVDataFromFile( $this->sourceRelationsData );
//		echo "<pre>";var_dump($this->ggmRelations);
//		die;
		$this->processDataRelations();
		// clean up  lists
//		$this->checkingRelationsProperties();
//		$this->checkingRelationsReferences();
//		$this->checkingDataProperties();

//		Tests all new output to Archi
//		ArchiCSVWriter::showOnScreen($this->listOfElements, $this->listOfProperties, $this->listOfRelations);
//		ArchiCSVWriter::dumpDataOnScreen($this->listOfProperties);
//		ArchiCSVWriter::dumpDataOnScreen($this->listOfElements);
//		ArchiCSVWriter::dumpDataOnScreen($this->listOfRelations);
//		die;
//		echo "<pre>";var_dump($this->listOfReplacements);die;
//		echo "<pre>";var_dump($this->listOfProperties,);die;
		if ($this->test) {die;};

		// now write it to a Archi-CSV format
		$csvArchiWriter = new ArchiCSVWriter();
		$csvArchiWriter::writeData(
			$this->listOfElements,
			$this->listOfProperties,
			$this->listOfRelations,
			$this->listOfReplacements,
			"-GGM2ARCHI",
			$this->listOfMessages
		);

	}



	/**
	 * Here the magic stuff happens for objects
	 *
	 */
	private function processDataObjects () {
		// creating $listOfElements and $listOfProperties  from the $sourceData
		$totalRowNr = 0;
		$index= null ;
		$this->id = null;
		$this->newID = null;
		$this->elementName = null;
		$dataType = '';
		$searchID = true;
//		if ($this->test) {echo "<pre/>";};
		foreach ($this->ggmObjects as $row) {
			$rowNr = 0;
//			if ($this->test) {print_r($row);};
			// analyse the $row and put the content in the related lists


			if ($this->test) { echo "$totalRowNr - PROCESSING " . $row['naam'] . " with ggm-guid: " . $row['ggm-guid'] . "<br/>";};
			if (isset($row['ggm-guid']) && (!empty($row['ggm-guid']))) {

				$this->id = $this->createGEMMAid( $row['ggm-guid'] );
				$this->newID = $this->id;
				$searchID = false; // we found the ggm-guid, and we need to save it for analyzing the complete row
				//temp for building replacement list
				$this->listOfReplacements[$this->newID]['old'] = $this->id;
				$this->listOfReplacements[$this->newID]['new'] = $this->newID;
				$this->listOfReplacements[$this->newID]['ggm'] = $row['ggm-guid'];
				$this->listOfReplacements[$this->newID]['type'] = 'element';
			}

			foreach ( $row as $key => $value ) {
				// convert string to array so we can process the data

				$index = $this->arraySearchSafe( trim( $value ), 'Name', $this->archiElements );
				// the first column in the row sets the ID, the second the Type
				if ( $rowNr == 1 ) {
					if ($index) {
						$targetElement = $this->archiElements[$index];
						if ($searchID) { // check if we need to search fot it`we already found it
							$this->id = $targetElement["ID"];
						}
						if (isset($targetElement["Type"])) {
							$dataType = $targetElement["Type"];
						}
					} else { // not possible anymore but
//						if (!is_null($this->id)) {
//							$this->id = 'id-' . $this->create_guid();
//							$this->newID = $this->createGEMMAid($row['ggm-guid']);
//							$this->listOfReplacements[$this->newID]['old'] = $this->id;
//							$this->listOfReplacements[$this->newID]['new'] = $this->newID;
//							$this->listOfReplacements[$this->newID]['ggm'] = $row['ggm-guid'];
//							$this->listOfReplacements[$this->newID]['type'] = 'elementNew';
//						}
					}

				}

				// now add the values (should not contain empty values).
				if ( !empty ( $value ) || $key == "archimate-type" ) {
					$objectID = $this->checkObjectID( $this->id, $key);
					$this->processObjectDetails( $rowNr, $totalRowNr, $objectID, $key, $value, $dataType );
				}
				$rowNr ++;
				$totalRowNr++;
			}
			$totalRowNr ++;
			$this->addMessage($this->messageCounter++, "Element", "Toegevoegd",
				" ID: $objectID, Datatype: $dataType,  Eigenschap: $key ,  Waarde: $value");
		}

	}

	/**
	 * creates a GEMMA id if no id is available. Should not occur, because we have a GGM id
	 * @param $ggmId
	 * @return mixed|string
	 */
	private function createGEMMAid ( $ggmId) {

		$newId = str_replace('{', '', $ggmId);
		$newId = str_replace('}', '', $newId);
		$newId = strtolower($newId);
		$newId = 'id-' . $newId;

		return $newId;
	}

	/**
	 * Checks the ID whether it is empty or not
	 * If it is empty generate a new GUID-id
	 *
	 * @param $objectID
	 * @return string
	 */
	private function checkObjectID ( $objectID ) {
		if ($key = 'archimate-type') {
			return $objectID;
		}
		if ( empty( trim( $objectID ) ) ) {
		   die ('ROWID-elements LEEG<br/>');
		}

		if ( strpos( $objectID, " " ) > 0 ) {
			$objectID = 'id-' . $this->create_guid();// . "-new-Spatie";;
		}
		return $objectID;
	}

	/**
	 * Processing of the values of the GGM-element and put it in Archi-format (elements and properties)
	 *
	 *
	 * @param $rowNr
	 * @param $totalRowNr
	 * @param $objectID
	 * @param $dataKey
	 * @param $dataValue
	 * @param $dataType
	 *
	 * 	//0 nr (SLAAN WE OVER)
	//1 naam (label)
	//2 definitie
	//3 uml-type
	//4 ggm-guid
	//5 bron
	//6 domein-iv3 (Indeling GGM)
	//7 archimate-type (business-object/data-object)
	//8 toelichting
	//9 gemma-guid
	//10 synoniemen (comma seperated)
	//11 domein-dcat
	//12 domein-gemma
	//13 datum-tijd-export (ddmmyyyy-hh:mm:ss)

	 */
	private function processObjectDetails ($rowNr, $totalRowNr, $objectID, $dataKey, $dataValue, $dataType = 'NOTYPE' ) {

		switch ( $rowNr ) {
			case 0:
				// do nothing, we skip the nr
				break;
			case 1:  //naam and the set the right order of columns
				$this->elementName = $dataValue;
				$this->listOfElements[$objectID]["ID"] = $objectID; //ID
				$this->listOfElements[$objectID]["Type"] = 'BusinessObject'; //Default Type
				$this->listOfElements[$objectID]["Name"] = $dataValue; //Name. Later we add the iv3 domain
				$this->Objectname = $dataValue;
				$this->listOfElements[$objectID]["Documentation"] = ''; //Documentation
				$this->listOfElements[$objectID]['Specialization'] = '';
				if ($this->test) { echo "-- ID: $objectID<br/>";};
				// we also deal here with the gemma-guid
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = 'gemma-guid';
				$this->listOfProperties[$totalRowNr]["Value"] = $objectID;
				if ($this->test) { echo "-- GEMMA-PROPERTY: $objectID, gemma-guid, $objectID<br/>";};
				// we also deal here with the original name
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = 'ggm-naam';
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				break;
			case 2:  // definitie
				$this->listOfElements[$objectID]["Documentation"] = $dataValue;
				if ($this->test) { echo "-- DOCUMENTATION: $dataValue<br/>";};
				break;
			case 3:  // archimate-type
				switch ( $dataValue ) {
					case "business-object":
					case "BusinessObject":
						$objectType = "BusinessObject";
						break;
					case "DataType":
					case "Enumeration":
					case "data-object":
					case "DataObject":
						// we ignore the data objects for now,
						// in a later phase we will use this info more specific
						$objectType = "BusinessObject";
						break;
					case empty($dataValue):
						$objectType = "BusinessObject";
						break;
					default:
						$objectType = $dataType;
						break;
				}
				$objectType = $this->checkDataType($objectID, $objectType );
				$this->listOfElements[$objectID]["Type"] = $objectType;
				if ($this->test) { echo "-- OBJECTTYPE: $objectType ($dataValue)<br/>";};
				break;
			case 10: //domein-iv3 (Indeling GGM)
				// check for other occurences of the name, other whise, put the domain-iv3
				// between parantheses

				if ($this->checkMultipleNameOccurence($this->elementName ) > 1 ) {
					$this->elementName = $this->elementName . " ($dataValue)"; // add the main to the name
					$this->listOfElements[$objectID]["Name"] = $this->elementName; //Name. including iv3 domain
					if ( $this->test ) {echo "-- NEW NAME: " . $this->elementName . "<br/>";};
				}

				// but also add the original name as an attribute
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				if ($this->test) { echo "-- PROPERTY($rowNr)[$totalRowNr]: $objectID, $dataKey, $dataValue<br/>";};
				break;
			case 5: //gemma-guid
				// we ignore this one here, we deal with this value in the first column
				break;
				// the other GGM-attributes are processed into Archi-properties. We could leave the other cases out here
				// because thea all lead to the default case
			case 4: //uml-type
			case 6: //ggm-guid
			case 7: //bron
			case 8: //toelichting
			case 9: //synoniemen
			case 11: //domein-dcat
			case 12: //domein-gemma
			case 13: //datum-tijd-export (ddmmyyyy-hh:mm:ss)
			default:
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				if ($this->test) { echo "-- PROPERTY($rowNr)[$totalRowNr]: $objectID, $dataKey, $dataValue<br/>";};
				break;
		}
	}

	/**
	 * check the multiple occurence of the name in the source data
	 *
	 * @param $elementName
	 * @return mixed
	 */
	private function checkMultipleNameOccurence( $elementName ){
		$occ = array_count_values(array_column($this->ggmObjects, 'naam'))[$elementName];
		if ($this->test) { echo "-- OCCURRENCES: " . $occ . "<br/>";}; // outputs: 2
		return $occ;
	}

	/**
	 * Checks whether the calculated archimate-type differs from the Archi-repo
	 * should be the same otherwise it give errors during import
	 *
	 * @param $objectID
	 * @param $objectType
	 * @return mixed
	 */
	private function checkDataType ( $objectID, $objectType ){

		$retVal = $objectType;
		if ($this->test) {echo "-- Checking $objectID, naam: $this->Objectname met type: $objectType: ";};
		$index = $this->arraySearchSafe( $objectID,  'ID', $this->archiElements );
		if ($index) {
			$element = $this->archiElements[$index];
			$retVal = $element['Type'];
		}
		if ($this->test) {echo "UITKOMST: $retVal<br/>";};
		return $retVal;

	}



	/**
	 * Here the magic stuff happens for relations
	 *
	 */
	/**
	 * @param $searchKey
	 */
	private function processDataRelations () {
		// creating $listOfRelations and $listOfProperties  from the $sourceData
		$rowNr = 0;
		$attrNr = 100000;
		if ($this->test) {echo "<br/><br/>DATA RELATIONS<br/>";};
		$index= null ;
		$this->id = null;
		foreach ($this->ggmRelations as $row) {
			// Determine ID
			if (!empty($row['ggm-guid'])) { // ggm guid present!
				$relationID = $this->createGEMMAid($row['ggm-guid']);
				$this->newID = $relationID ;
				$this->listOfReplacements[$this->newID]['old'] = $this->id;
				$this->listOfReplacements[$this->newID]['new'] = $this->newID;
				$this->listOfReplacements[$this->newID]['ggm'] = $row['ggm-guid'];
				$this->listOfReplacements[$this->newID]['type'] = 'relation';
			} else { // no guid's available, so we seach the long way by other properties, should not happen anymore
//				$relationID = $this->searchRelIDinArchiElements(
//					$row['uml-type'],
//					$row['naam'],
//					$row['ggm-source-guid'],
//					$row['ggm-target-guid']  );  //Type, Name, Source, Target equal to Search column
//				$this->newID = $this->createGEMMAid($row['ggm-guid']);
//				$this->listOfReplacements[$this->newID]['old'] = $this->id;
//				$this->listOfReplacements[$this->newID]['new'] = $this->newID;
//				$this->listOfReplacements[$this->newID]['ggm'] = $row['ggm-guid'];
//				$this->listOfReplacements[$this->newID]['type'] = 'relationNew';
			}
			if ($relationID == true) {
				$this->listOfRelations[$rowNr]['ID'] = $relationID;
			}
			if ($this->test) {echo "$rowNr - PROCESSING $relationID<br/>";};

			// Determine Type
			if (!empty($row['archimate-type'])) {
				$type = $row['archimate-type'];
			} else { // derive ArchiMate-type from uml-type
				if (!empty($row['uml-type'])) {
					$relationType = $this->getArchiRelationType( $row['uml-type'] );
					$type = $relationType;
				} else {
					$type = 'AssociationRelationship';
				}
			}
			$this->listOfRelations[$rowNr]['Type'] = $type;
			if ($this->test) {echo "-- TYPE: $type<br/>";};


			// Determine Name
			if ($this->test) {echo "-- NAAM: ";};
			if (isset($row['naam'])) {
				$this->listOfRelations[$rowNr]['Name'] = $row['naam'];
				if ($this->test) {echo  $row['naam'] . "<br/>";};
			}



			// Determine Documentation
			$this->listOfRelations[$rowNr]['Documentation'] = $row['definitie'];
			if (isset($row['definitie'])) {
				$this->listOfRelations[$rowNr]['Documentation'] = $row['definitie'];
				if ($this->test) { echo "-- DOCUMENTATIE: " . $row['definitie'] . "<br/>";};
			}


			// Determine Source
			if ($row['ggm-source-guid']) {
				$sourceID = $this->createGEMMAid($row['ggm-source-guid']);
				$this->listOfRelations[$rowNr]['Source'] = $sourceID;

				if ($this->test) { echo "-- SOURCE (GGM): " . $sourceID . "<br/>";};
			} else { //should not happen anymore
//				$sourceID = $this->searchIDinArchiElements( $row['ggm-source-guid'] );
			}

			// Determine Target
			if  ($row['ggm-target-guid']) {
				$targetID = $this->createGEMMAid($row['ggm-target-guid']);
				$this->listOfRelations[$rowNr]['Target'] = $targetID;
				if ($this->test) { echo "-- TARGET (GGM): " . $targetID . "<br/>";};
			} else { //should not happen anymore
//				$targetID = $this->searchIDinArchiElements($row['ggm-target-guid']);
			}



			// Determine Specialization
			$this->listOfRelations[$rowNr]['Specialization'] = '';

			// Determine other attributes of this relation
			$list = array (
				'ggm-guid', 'ggm-object-guid', 'ggm-source-guid', 'ggm-target-guid',
				'uml-type', 'bron','toelichting',
				'synoniemen', 'datum-tijd-export'
			);
			foreach ($list as $item) {
				if (!empty($row["$item"])) {
					$this->listOfProperties[$attrNr]['ID'] = $relationID;
					$this->listOfProperties[$attrNr]['Key'] = $item;
					$this->listOfProperties[$attrNr]['Value'] = $row["$item"];
					if ($this->test) { echo "-- PROPERTY (GGM): $item, met als waarde: " . $row["$item"] . "<br/>";};
					$this->addMessage($this->messageCounter++, "Eigenschap", "Toegevoegd",
						" ID: $relationID, Eigenschap: $item,  Waarde: ". $row["$item"] );
				}

				$attrNr++;
			}
			// here we handle the gemma-guids
			$list = array ('gemma-guid', 'gemma-object-guid', 'gemma-source-guid', 'gemma-target-guid'); //'gemma-object-guid weggelaten
			foreach ($list as $item) {


				switch ($item) {
					case 'gemma-guid':
						if ( isset($row['ggm-guid']) && !empty($row['ggm-guid'] ) ) {
							$this->listOfProperties[$attrNr]['ID'] = $relationID;
							$this->listOfProperties[$attrNr]['Key'] = $item;
							$this->listOfProperties[$attrNr]['gemma-guid'] = $this->createGEMMAid( $row['ggm-guid'] );
							if ( $this->test ) {echo "-- PROPERTY (GEMMA-GUID): " . $this->createGEMMAid( $row['ggm-guid'] ) . "<br/>";};
						}
						break;
					case 'gemma-object-guid':
						if ( isset($row['ggm-object-guid']) && !empty($row['ggm-object-guid'] ) ){
							$this->listOfProperties[$attrNr]['ID'] = $relationID;
							$this->listOfProperties[$attrNr]['Key'] = $item;
							$this->listOfProperties[$attrNr]['gemma-object-guid'] = $this->createGEMMAid($row['ggm-object-guid'] );
							if ($this->test) { echo "-- PROPERTY (GEMMA-OBJECT-GUID): " . $this->createGEMMAid($row['ggm-object-guid'] ) . "<br/>";};
						}
						break;
					case 'gemma-source-guid':
						if ( isset($row['ggm-source-guid']) && !empty($row['ggm-source-guid'] ) ) {
							$this->listOfProperties[$attrNr]['ID'] = $relationID;
							$this->listOfProperties[$attrNr]['Key'] = $item;
							$this->listOfProperties[$attrNr]['gemma-source-guid'] = $this->createGEMMAid($row['ggm-source-guid'] );
							if ($this->test) { echo "-- PROPERTY (GEMMA-SOURCE-GUID): " . $this->createGEMMAid($row['ggm-source-guid'] ) . "<br/>";};
						}
						break;
					case 'gemma-target-guid':
						if ( isset($row['ggm-target-guid']) && !empty($row['ggm-target-guid'] ) ) {
							$this->listOfProperties[$attrNr]['ID'] = $relationID;
							$this->listOfProperties[$attrNr]['Key'] = $item;
							$this->listOfProperties[$attrNr]['gemma-target-guid'] = $this->createGEMMAid($row['ggm-target-guid'] );
							if ($this->test) { echo "-- PROPERTY (GEMMA-TARGET-GUID): " . $this->createGEMMAid($row['ggm-target-guid'] ) . "<br/>";};
						}
						break;
					default:
						break;
				}
				$attrNr++;

			}

			// add direction in case of Associations
			if ( $type == 'AssociationRelationship') {
				$this->listOfProperties[$attrNr]['ID'] = $relationID;
				$this->listOfProperties[$attrNr]['Key'] = 'Directed';
				$this->listOfProperties[$attrNr]['Value'] = 'true';
				$attrNr++;
				$this->addMessage($this->messageCounter++, "Eigenschap", "Toegevoegd",
					" ID: $relationID, Eigenschap: Directed,  Waarde: true" );
			}
			$this->addMessage($this->messageCounter++, "Relatie", "Toegevoegd",
				" ID: $relationID, Type: $type, Bron: $sourceID, Doel: $targetID,   Label: " . $row['naam'] );
			$rowNr ++;
		}
	}

	/**
	 * Map the Archi-relation on the UML-relationship
	 *
	 * @param $value
	 * @return string
	 */
	private function getArchiRelationType( $value ) {
		switch ($value) {
			case 'Generalisation':
				$relationType = 'SpecializationRelationship';
				break;
			case 'Aggregation';
				$relationType = 'AggregationRelationship';
				break;
			case 'Association':
			default:
				$relationType = 'AssociationRelationship';
				break;
		}
		return $relationType;
	}

	/**
	 * if one of the values ID, Type, Source, Target is empty
	 * it will result in an incorrect relations.csv
	 */
	private function checkingRelationsProperties() {
		if ($this->test) {echo "<br/><br/>CHECKING ALL RELATIONS:  <br/>";};
			foreach ($this->listOfRelations as $key => $relation ) {
				if ($this->test) {echo "ANALYZING-- RELATION->SOURCE: " . $relation['Source'] . "<br/>";};
				if (empty($relation['Source']) ) {
					if ($this->test) {echo  ("-- REMOVE RELATION->EMPTY SOURCE: ".substr($relation['Source'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}

				if (substr($relation['Source'], 0 , 3) != 'id-' ) {
					if ($this->test) {echo  ("-- REMOVE RELATION->WRONG SOURCE: ".substr($relation['Source'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}
				if ($this->test) {echo "ANALYZING-- RELATION->TARGET: " . $relation['Target'] . "<br/>";};
				if (empty($relation['Target']) ) {
					if ($this->test) {echo  ("-- REMOVE RELATION->EMPTY TARGET: ".substr($relation['Target'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}
				if (substr($relation['Target'], 0 , 3) != 'id-' ) {
					if ($this->test) {echo  ("-- REMOVE RELATION->WRONG TARGET: ".substr($relation['Target'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}
				if (empty($relation['Type']) ) {
					if ($this->test) {echo  ("-- REMOVE EMPTY TYPE: ".substr($relation['Type'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}
				if (empty($relation['ID']) ) {
					if ($this->test) {echo  ("-- REMOVE EMPTY ID: ".substr($relation['ID'], 0 , 3) ) . "<br/>";};
					unset($this->listOfRelations[$key]);
					continue;
				}

		}
//		echo "<pre>";var_dump($this->listOfRelations);die;

	}


	/**
	 * if one a property reference does nog exist, remove it from the listOfProperties
	 * it will result in an incorrect properties.csv
	 */
	private function checkingRelationsReferences() {

		if ($this->test) {echo  "<br/><br/>CHECKING RELATION REFERENCES<br/>" ;};
		foreach ( $this->listOfRelations as $key => $relation ) {
			//die(var_dump($this->listOfElements));
			if (isset($relation['Source'])) {
				$sourceID = $relation['Source'];
				if ( $this->missingID( $sourceID ) ) {
					unset( $this->listOfRelations[$key] );
					if ( $this->test ) {echo "-- REMOVE RELATION with Source-id: $sourceID<br/>";};
					continue;
				} else {
					if ( $this->test ) {echo "-- FOUND RELATION with Source-id: $sourceID<br/>";};
				}
			}
			if (isset($relation['Target'])) {
				$targetID = $relation['Target'];
				if ( $this->missingID( $targetID )) {
					unset( $this->listOfRelations[$key] );
					if ($this->test) {echo  "-- REMOVE RELATION with Target-id: $targetID<br/>" ;};
					continue;
				} else {
					if ( $this->test ) {echo "-- FOUND RELATION with Target-id: $sourceID<br/>";};
				}
			}
		}
//		die;
	}

	private function missingID ( $id ) {
		$result = true;
		// look in elements

//		echo"ID: $id - ";
		if (!empty($id)) {
			$indexElement = $this->arraySearchSafe( $id, 'ID', $this->listOfElements );
//			$indexElement = array_search( $id, array_column( $this->listOfElements, 'ID' ) );
//			echo "$indexElement<br/>";
			if ( !$indexElement ) {
				$result = false;
			}
		}
		return $result;
	}



	/**
	 * if one a property reference does nog exist, remove it from the listOfProperties
	 * it will result in an incorrect properties.csv
	 */
	private function checkingDataProperties() {
		if ($this->test) {echo  "<br/><br/>CHECKING PROPERTIES REFERENCES<br/>" ;};
		foreach ( $this->listOfProperties as $key => $property ) {
			$searchID = $property['ID'];
			// look in relations
			$indexRelation = $this->arraySearchSafe( $searchID, 'ID', $this->listOfRelations );
//			$indexRelation = array_search( $searchID, array_column( $this->listOfRelations, 'ID' ) );

			if ( !$indexRelation ) {
				// not found: look in elements
//				$indexObject = array_search( $searchID, array_column( $this->listOfElements, 'ID' ) );
				$indexObject = $this->arraySearchSafe( $searchID, 'ID', $this->listOfElements );
				if ( !$indexObject ) {
					//also not found in elements, so we have to remove it, otherwise we get an import error in Archi
					unset( $this->listOfProperties[$key] );
					if ($this->test) {echo  ("-- REMOVE POPERTY ID: not found in Elements: ". $property['ID'] ) . "<br/>";};
				} else {
					if ($this->test) {echo  ("-- FOUND POPERTY ID in Elements: ". $property['ID'] ) . "<br/>";};
				}
			} else {
				if ($this->test) {echo  ("-- FOUND POPERTY ID in Relations: ". $property['ID'] ) . "<br/>";};
			}
		}
	}

	/**
	 * Check the existence of an existing relationship
	 *  if true: return the key/ID of this relationship
	 *  if false: return the key/ID of a new relationship
	 *
	 * since we search by ID, we don't really need this anymore
	 *
	 * @param $searchGGMtype
	 * @param $searchGGMname
	 * @param $searchGGMsource
	 * @param $searchGGMtarget
	 * @return bool|string
	 */
	private function searchRelIDinArchiElements ($searchGGMtype, $searchGGMname, $searchGGMsource, $searchGGMtarget) {
		// collect the given properties and put them in to one search string
		$relID = false;
		// check the parameters, should 3 of them should  have values, name is optional. If not true, it is not a valid relation
		if ($this->checkParameters($searchGGMtype, $searchGGMsource, $searchGGMtarget)) {
			$searchValue =
				$this->getArchiRelationType($searchGGMtype) .
				(isset($searchGGMname)?$searchGGMname:'') .
				$this->searchIDinArchiElements( $searchGGMsource ) .
				$this->searchIDinArchiElements( $searchGGMtarget )
			;

//			echo "<span style=\"color:mediumpurple\"> $searchValue</span>" ;

			// find the searchValue in the Archi-relations array
			//$index = $this->arraySearchSafe( $searchValue,  'Search', $this->archiRelations );
			$index = array_search( $searchValue, array_column( $this->archiRelations, 'Search' ) );
//			echo "<span style=\"color:rosybrown\"> INDEX:_ $index _:INDEX</span>" ;

			if ( $index ) {
				$relIDarray = $this->archiRelations[$index];
				$relID = $relIDarray["ID"];
//				echo " <span style=\"color:green\">is found</span>";
			} else { // add the ID as an extra element in the new relations array
				// first check if source and target guid are supplied
				if (!empty($searchGGMsource) && !empty($searchGGMtarget) ) {
					$relID = 'id-' . $this->create_guid();
//					echo "<span style=\"color:red\">NOT found</span>";
				}
			}
		}
//		echo "--> $relID <--<br/>";
		return $relID;
	}

	/**
	 * checks the paramerters. Each one should have a value,
	 * otherwise searching doesn't make sense
	 *
	 * @param $searchGGMtype
	 * @param $searchGGMsource
	 * @param $searchGGMtarget
	 * @return bool
	 */
	private function checkParameters($searchGGMtype, $searchGGMsource, $searchGGMtarget) {
		if ( empty( $searchGGMtype ) ) {
			return false;
		}
		if ( empty( $searchGGMsource ) ) {
			return false;
		}
		if ( empty( $searchGGMtarget ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Search ID: $idValue in
	 *  - archiProperties, by Value
	 *  - ggmObjects, by Name
	 *  - newArchiObjects, by Name
	 *
	 *
	 * @param $idValue
	 * @return mixed|string
	 */
	private function searchIDinArchiElements ( $idValue ) {
		// search for a value in archiProperties
		$index = $this->arraySearchSafe( $idValue,  'Value', $this->archiProperties );
		if ($index) {
			$targetProperty = $this->archiProperties[$index];
			$ID = $targetProperty["ID"];// . "-FOUNDinArhiElements-$value";
		} else {
			// not found, search in ggmObjects
			// niet gevonden, zoek naar de naam van het object en daarna naar het element en ID
			$index = $this->arraySearchSafe( $idValue,  'ggm-guid', $this->ggmObjects ); // zoeken naar ggm-guid
			if ($index) { // als deze gevonden is
				$targetProperty = $this->ggmObjects[$index]; // selecteer de rij
				$targetNaam = $targetProperty["naam"];
				$index = $this->arraySearchSafe( $targetNaam,  'Name', $this->archiElements ); // zoek deze naam in de archi-objecten
				if ($index) { // als deze gevonden is
					$targetObject = $this->archiElements[$index]; // selecteer de rij
					$ID = $targetObject["ID"];
				} else { // not found in Archi gevonden, so ID will be empty and filtered out in the remainder of the process
					$ID = '';//"ERROR-NOTFOUND in ARCHI-IMPORT!!!!";
				}
			} else { // not found in ggm-files, so ID will be empty and filtered out in the remainder of the process
				$ID = '';//"ERROR-NOTFOUND in GGM-IMPORT!!!!";
			}
		}
		return $ID;
	}


	/**
	 * create a new guid
	 *
	 * @return string
	 */
	private function create_guid() {
		// OSX/Linux
		if (function_exists('openssl_random_pseudo_bytes') === true) {
			$data = openssl_random_pseudo_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		}
	}


	/**
	 * Search an array for a value in a specific column, and return the index of the first match
	 *
	 * @param string $value
	 * @param string $column
	 * @param array $array
	 * @return int|null
	 */
	private function arraySearchSafe( string $value, string $column, array $array ) { //: ?int
		// Array search gaat niet kapot als de kolom niet bestaat,
		// dus let daar op.

		$index = array_search( $value, array_column( $array, $column ) );

		// We hebben alleen een valide index, als de kolom bestaat en ook daadwerkelijk
		// dezelfde value heeft als we verwachten
		if ( $index && isset($array[ $index ]) && array_key_exists( $column, $array[ $index ] ) &&
			$array[$index][$column] === $value ) {
			return $index;
		}

		foreach ( $array as $index => $item ) {
			// If the column doesn't exist, skip this item
			if ( !array_key_exists( $column, $item ) ) {
				continue;
			}

			if ( $item[ $column ] === $value ) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * creates a row in the message array
	 *
	 * @param $counter
	 * @param $concepttype
	 * @param $typeOfAction
	 * @param $value
	 */
	private function addMessage( $counter, $concepttype, $typeOfAction, $value )  {
		$this->listOfMessages [$counter]["Concept type"] = $concepttype;
		$this->listOfMessages [$counter]["Action"] 		 = $typeOfAction;
		$this->listOfMessages [$counter]["Message"]		 = $value;
		$this->listOfMessages [$counter]["Date"]		 = $this->exportDate;
	}
}