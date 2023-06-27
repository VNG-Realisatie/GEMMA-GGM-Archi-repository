<?php
/*
 * Convert a TOPdesk CSV file (Export from TOPdesk in CSV-format
 *
 * @author Toine Schijvenaars (XL&Knowledge), may 2023
 * @version 0.01
 */


include __DIR__ . "/../../csvreader/CSVReader.php";
include __DIR__ . "/../../archicsvwriter/ArchiCSVWriter.php";


if ( $_FILES ) {
	if ($_FILES["fileTOPdesk"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No source file entered<br>";
		die;
	}
	if ($_FILES["fileArchi"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No target file entered<br>";
		die;
	}



	$mode               = 'FORM';
	$source             = $_FILES["fileTOPdesk"]["tmp_name"][0];
	$target             = $_FILES["fileArchi"]["tmp_name"][0];
	$delimeter			= $_POST["delimeter"][0];
	// only when the ckeckbox is checked is has usable value
	if (isset( $_POST["test"][0])) {
		$test = $_POST["test"][0];
	} else {
		$test = false;
	}
//	var_dump($_FILES);
//	var_dump($_POST);
//	die;


} else {
	$mode       = 'CLI';
	$source     = '';
	$target		= '';
	$delimeter  = ',';
	$test       = false;

	if ( isset($argv[1])) {
		$source     = $argv[1];  // source file
	}
	if ( isset($argv[2])) {
		$target     = $argv[2];
	}

	if ( isset($argv[3])) {
		$test   = $argv[3];
	}

}

$class = new TD2ARCHI( $mode, $source, $target, $delimeter, $test );
$class->run();

class TD2ARCHI  {
	// worker variables
	private $mode;
	private $source;
	private $test;
	private $delimeter;
	private $exportDate;
	private $messageCounter;

	// contains the data of the csv
	private $sourceData;

	// the ArchiFiles to be created
	private $listOfElements = array();
	private $listOfProperties = array();
	private $listOfRelations = array();
	private $listOfMessages = array();

	// the current ArchiFiles to check for ID's etc.
	private $archiElements = array();
	private $archiProperties = array();
	private $archiRelations = array();


	public function __construct( $mode, $source, $target, $delimeter, $test ) {
		$this->mode 	 = $mode;
		$this->source	 = $source;
		$this->target	 = $target;
		$this->test 	 = $test;
		$this->delimeter = $delimeter;
		$this->sourceData = '';
		$this->exportDate = date( "\"dmY-h:m:s\"" );
		$this->messageCounter = 0;

	}

	public function run() {
		$result = CSVReader::processArchiZipfile($this->target);
		$this->archiElements = $result[0];
		$this->archiProperties = $result[1];
		$this->archiRelations = $result[2];
//		ArchiCSVWriter::dumpDataOnScreen($this->archiRelations);
//		ArchiCSVWriter::dumpDataOnScreen($this->archiElements);
//		ArchiCSVWriter::showOnScreen($this->archiElements, $this->archiProperties, $this->archiRelations);
//		die;

		$csvTopDeskReader = new CSVReader();
		$this->sourceData = $csvTopDeskReader::readCSVDataFromFile($this->source, $this->delimeter );

//		ArchiCSVWriter::dumpDataOnScreen($this->sourceData);
//		die;
		$this->processData();
//		ArchiCSVWriter::dumpDataOnScreen($this->listOfElements);
//		die;

		// test and show the elements on screen
//		ArchiCSVWriter::showOnScreen($this->listOfElements, $this->listOfProperties, $this->listOfRelations);
//		die;


		//final processing, delete the content of the column Specialization
		foreach ($this->listOfElements as $elements) {
			if (isset($elements["ID"])) {
				$this->listOfElements[$elements["ID"]]["Specialization"] = "";
			}
		}
//		echo "<pre>";var_dump($this->listOfElements); die;


		// now write it to a Archi-CSV format
		$csvArchiWriter = new ArchiCSVWriter();
		$csvArchiWriter::writeData($this->listOfElements, $this->listOfProperties, $this->listOfRelations, null,"-TD", $this->listOfMessages);

	}


	/**
	 * Here the magic stuff happens
	 *
	 */
	private function processData ( ) {
		// creating $listOfElements, $listOfProperties, $listOfRelations from the $sourceData
		$totalRowNr = 0;
		$recordNr = 1;
		$index= 0 ;
		$this->id = null;


//		$this->sourceHeaders = $this->sourceData[0];
//		var_dump($this->sourceHeaders);
		foreach ($this->sourceData as $row) {
			$colNr = 0;
			if ($this->test) {echo "<h4>Record: $recordNr</h4><br/>";};
			// analyse the $row and put the content in the related lists

			foreach ( $row as $key => $value ) {

				if ( !empty ( $value ) ) {
					if ( $this->test ) {
						echo "<b>$colNr Analyzing: $key: $value  </b>	<br/>";
					};
					if ( $colNr == 0 ) {

						// convert string to array so we can process the data
//					$index = array_search( $value, array_column( $this->archiElements,'Name' ), true  );
						$this->searchType = "ApplicationComponent";
						$index = $this->arraySearchSafe( $this->searchType . $value, 'Search', $this->archiElements );
						// attempt 2, maybe it is classified as SystemSoftware
						if ( !$index ) {
							if ( $this->test ) {
								echo "- NOTFOUND IN $this->searchType -$index-- ";
							};
							$this->searchType = "SystemSoftware";
							$index = $this->arraySearchSafe( $this->searchType . $value, 'Search', $this->archiElements );
						}

						if ( $index ) {
							if ( $this->test ) {
								echo "- FOUND IN $this->searchType -$index-- ";
							};
							$targetElement = $this->archiElements[$index];
							if ( $colNr == 0 ) { // the first column in the row
								$this->id = $targetElement["ID"];
								if ( $this->test ) {
									echo "- FOUNDKEY: colnr: $colNr: " . $this->id . " by value: $value with Type: $this->searchType<br/>";
								};
								$this->addMessage( $this->messageCounter ++, "Element", "Wijziging", "ID gevonden " . $this->id . ", obv waarde: $value" );
							}
						} else {
							if ( $this->test ) {
								echo "- NOTFOUND IN $this->searchType -$index-- ";
							};
							if ( $colNr == 0 ) { // the first column in the row
								$this->id = 'id-' . $this->create_guid(); //NEW GUID
								$this->searchType = "ApplicationComponent";
								if ( $this->test ) {
									echo "- NOTFOUNDKEY: colnr: $colNr:  by value: $value, new ID: " . $this->id . "<br/>";
								};
								$this->addMessage( $this->messageCounter ++, "Element", "Nieuw", "ID niet gevonden " . $this->id . ", obv waarde: $value" );
							}
						}
					} else {
						if ( $this->test ) {
							echo "- OTHER COLUMNS $colNr : $key: $value 	<br/>";
						};
					}
					if ($this->test) {echo "-- PROCESSING: colnr: $colNr ($totalRowNr), ID: " . $this->id . ", Prop: $key --> Value: $value<br/>";};
					$this->processDetails( $totalRowNr, $this->id, $key, $value, $this->searchType  );
					$totalRowNr ++;
				} else {
					$this->addMessage($this->messageCounter++, "Element", "Afgewezen", "ID niet gevonden ". $this->id . ", waarde ($value) niet aanwezig");
				}
				$colNr++;

			}
			$recordNr++;
			if ($this->test) {echo "<hr>";};
		}

		//final processing, delete the content of the column Specialization
		foreach ($this->listOfElements as $elements) {
			$this->listOfElements[$elements["ID"]]["Specialization"] = "";
		}
//		echo "<pre>";var_dump($this->listOfElements); die;





		if ($this->test) {
			echo "<pre>";
			var_dump($this->listOfElements);
			die();
		};
	}

	private function addMessage( $counter, $concepttype, $typeOfAction, $value )  {
		$this->listOfMessages[$counter]["Concept type"] = $concepttype;
		$this->listOfMessages[$counter]["Action"] = $typeOfAction;
		$this->listOfMessages [$counter]["Message"] = $value;
		$this->listOfMessages [$counter]["Date"] = $this->exportDate;
	}


	/**
	 * Here are the details
	 * in RID de Limers
	 * 0  Middelen-ID
	 * 1  Specification
	 * 2  Aanmaakdatum
	 * 3  Wijzigingsdatum
	 * 4  Samenvatting
	 * 5  Applicatiesoort
	 * 6  Bedrijfskritisch
	 * 7  Behandelaarsgroep
	 * 8  Cloud
	 * 9 Functioneel applicatiebeheerder 1
	 * 10 Functioneel applicatiebeheerder 2
	 * 11 Functioneel applicatiebeheerder 3
	 * 12 Functioneel applicatiebeheerder 4
	 * 13 Leverancier
	 * 14 Notities
	 * 15 Single Sign On
	 * 16 Technisch applicatiebeheerder 1
	 * 17 Technisch applicatiebeheerder 2
	 * 18 Versie
	 * 19 Toewijzingen - Locaties
	 * 20 Toewijzingen - Ruimtes
	 * 21 Toewijzingen - Ruimtes - Locatie
	 * 22 Toewijzingen - Personen
	 * 23 Toewijzingen - Persoonsgroepen
	 * 24 Applicatie hosting (Kind-middelen)
	 * 25 Applicatie hosting (Ouders)
	 * 26 Data hosting (Ouders)
	 * 27 Database hosting (Ouders)
	 * @param $rowNr
	 * @param $totalRowNr
	 * @param $objectID
	 * @param $dataKey
	 * @param $dataValue
	 */
	private function processDetails ( $totalRowNr, $objectID, $dataKey, $dataValue, $searchType  ) {
//		if ($this->test) {echo "MIDDELpos:". strpos($dataKey,"Middelen" ) . "<br/>";};
		switch ( $dataKey ) {
//		switch ( $rowNr ) {
//			case strpos($dataKey,"Middelen-ID" ) == 1:  //0 Middelen-ID
			case "Middelen-ID" :
				if ($this->test) {echo "-- FOUND Middelen-ID----$objectID-->$dataValue<br/>";};
				$this->listOfElements[$objectID]["ID"] = $objectID; //ID
				$this->listOfElements[$objectID]["Type"] = $searchType; //Default Type
				$this->listOfElements[$objectID]["Name"] = $dataValue; //Name
				$this->currentName = $dataValue;
				$this->listOfElements[$objectID]["Documentation"] = ''; //Documentation, default
				break;
			case "Samenvatting":  //  4 Samenvatting ==> DOCUMENTATION
				$this->listOfElements[$objectID]["Documentation"] = $dataValue; //Documentation
				break;
			case "Applicatiesoort":  // 5 Applicatiesoort ==>TYPE
				$this->listOfElements[$objectID]["Type"] = $searchType; //$this->getType($dataValue);
				// here we mis use the Specialization column to store search values
				$this->listOfEslements[$objectID]["Specialization"] = $searchType.$this->currentName;
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				break;
			case "Leverancier": //13 Leverancier
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				$this->addRelationAndElement(
					'source',
					'AssociationRelationship',
					$this->currentName . ' - '  . $dataValue ,
					'Leverancier',
					$objectID,
					$dataValue,
					'BusinessActor'
				);
				break;
			case "Toewijzingen - Locaties": //19 Toewijzingen - Locaties
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				$listOfLocations = preg_split("/\\r\\n|\\r|\\n/",$dataValue);
//				echo "<pre>";var_dump($listOfLocations);
				foreach ($listOfLocations as $location) {
					$this->addRelationAndElement(
						'source',
						'AssociationRelationship',
						$this->currentName . ' - '  . $location,
						'Toewijzingen - Locaties',
						$objectID,
						$location,
						'Location'
					);
				}
				break;
			case "Applicatie hosting (Ouders)": //25 Applicatie hosting (Ouders)

				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;

				$listOfNodes = preg_split("/\\r\\n|\\r|\\n/",$dataValue);
//				echo "<pre>";var_dump($listOfNodes);die;
				foreach ($listOfNodes as $node) {
						$this->addRelationAndElement(
							'source',
							'AssociationRelationship',
							$this->currentName . ' - '  . $node,
							'Applicatie hosting (Ouders)',
							$objectID,
							$node,
							'Node'
						);
					}
				break;
			case "Data hosting (Ouders)": //26 Database hosting (Ouders)
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				$listOfDataSources = preg_split("/\\r\\n|\\r|\\n/",$dataValue);
//				echo "<pre>";var_dump($listOfDataSources);
				foreach ($listOfDataSources as $datasource) {
//					echo $datasource . "<br/>";
					$this->addRelationAndElement(
						'source',
						'AccessRelationship',
						$this->currentName . ' - '  . $datasource,
						'Data hosting (Ouders)',
						$objectID,
						$datasource,
						'Artifact'
					);
				}
				break;
			case "Database hosting (Ouders)": //27 Database hosting (Ouders)
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				$listOfArtifacts = preg_split("/\\r\\n|\\r|\\n/",$dataValue);
//				echo "<pre>";var_dump($listOfArtifacts);die;
				foreach ($listOfArtifacts as $artifact) {
					$this->addRelationAndElement(
						'source',
						'AccessRelationship',
						$this->currentName . ' - '  . $artifact,
						'Database hosting (Ouders)',
						$objectID,
						$artifact,
						'Artifact'
					);
				}
				break;
			default:
				$this->listOfProperties[$totalRowNr]["ID"] = $objectID;
				$this->listOfProperties[$totalRowNr]["Key"] = $dataKey;
				$this->listOfProperties[$totalRowNr]["Value"] = $dataValue;
				break;
//
//				echo "Default: $dataKey<br/>";
//				break;
		}
		return ;
	}

	/**
	 * makes a mapping between TOPdesk - Applicatiesoort and ArchiMate elementtypes
	 * @param $value
	 * @return string
	 */
	private function getType($value) {
		switch ($value) { //CSV Import is not yet suited to change the ArchiMate-type. CSV-Import gives an error
//			case 'Systeem software':
//			case 'Systeemsoftware':
//			case 'Systeem Software':
//				$result = 'SystemSoftware';
//				break;
			default:
				$result = 'ApplicationComponent';
		}
		return $result ;
	}

	/**
	 * This function creates relations between elements and adds TOPdesk properties as Archimate-elements is necessary
	 *
	 * @param $direction
	 * @param $type
	 * @param $name
	 * @param $documentation
	 * @param $source
	 * @param $target
	 * @param $datatype
	 */
	private function addRelationAndElement ( $direction, $type, $name, $documentation, $source, $target, $datatype ) {
		$objectName = $target;
		$rowId = uniqid();
		if ($direction == 'source') {
			$searchValue = $target;
		} else {
			$searchValue = $source;
		}
		//search for existing combination $name and $datatype
		if ($this->test) {echo "-- SEARCHING: type: $datatype name: $searchValue<br/>";};
		$index = $this->arraySearchSafe(  $datatype . $searchValue ,  'Search', $this->archiElements );

		$newElement = false;
		// if found
		if ( $index ) {
			$elementID = $this->archiElements[$index]["ID"];
			if ($this->test) {echo "--- FOUNDIT: $elementID van $searchValue van type $datatype gevonden<br/>";};
			$this->addMessage($this->messageCounter++, "Relatie", "Wijziging",  $elementID. ", obv waarde: bestaand GEMMA GUID");

		} else { // not found --> create new elementid if necessary
			if ($this->test) {echo "--- NOT FOUND: $target van type $datatype NIET gevonden <br/>";};
			if ($this->test) {echo "--- LETS LOOK IN CURRENT DATA: $datatype$searchValue -- <br/>" ;};
			$elementIndex = array_search( $datatype . $searchValue, array_column( $this->listOfElements, 'Specialization' ) );

			//if found
			if ( $elementIndex ) {
					if (isset($this->listOfElements[$searchValue]["Type"] )) {
						"--- Type isset: " . $this->listOfElements[$searchValue]["Type"] . " comparing to $datatype <br/>";
						$elementDatatype = $this->listOfElements[$searchValue]["Type"];
						if ( $elementDatatype == $datatype ) {
							$elementID = $this->listOfElements[$searchValue]["ID"];
							if ($this->test) {echo "--- FOUNDIT: NEW $elementID van $searchValue van type $datatype gevonden<br/>";};
							$this->addMessage($this->messageCounter++, "Relatie", "Wijziging",  $elementID . ", obv waarde: $searchValue en $datatype");


						}
					} else { // NO TYPE AVAILABLE
						if ($this->test) {echo "--- TYPE: ". gettype($elementIndex) . "-->" . $elementIndex;};
						if ($this->test) {echo "--- NO DATATYPE SET: $searchValue -- <br/>" ;};
						if (isset($this->listOfElements[$searchValue]["ID"])) {
							$elementID = $this->listOfElements[$searchValue]["ID"];
							$action= "gevonden";
						} else {
							$elementID = 'id-' . $this->create_guid();
							$action= "toegevoegd";
						}
						if ($this->test) {echo "--- FOUNDIT: NEW $elementID van $searchValue van type $datatype $action<br/>";};
						$this->addMessage($this->messageCounter++, "Relatie", "Wijziging",  $elementID. ", obv waarde: $searchValue en $datatype");

					}
				} else {
					$elementID = 'id-' . $this->create_guid();
					$this->addMessage($this->messageCounter++, "Relatie", "Nieuw",  $elementID. ", obv waarde: $searchValue en $datatype");
					$newElement = true;
					if ($this->test) {echo "--------> CREATIE NIEUW ELEMENT $datatype $objectName ($elementID)<br/>";};
				}
		}

		// if new target or source object
		if ($newElement) {
			$this->listOfElements[$searchValue]["ID"]   = $elementID;
			$this->listOfElements[$searchValue]["Type"] = $datatype ;
			$this->listOfElements[$searchValue]["Name"] = $objectName;
			$this->listOfElements[$searchValue]["Documentation"] = "gegenereerd door de import.";
			$this->listOfElements[$searchValue]["Specialization"] = $datatype . $objectName;
		}


		// check if the relation exists @TODO: add NAME/LABEL to the searchValue
		// search for existing $name with Type, Source and Target
		if (isset($elementID)) {
			$source = ( $direction == 'source' ) ? $source : $elementID;
			$target = ( $direction == 'target' ) ? $target : $elementID;
			$search = $type . $name . $source . $target;
			// $index = array_search( $searchValue, array_column( $this->archiRelations, 'Search' ), true );
			$index = $this->arraySearchSafe(  $search,  'Search', $this->archiRelations );
		}


		// if new relation
		if ( !$index )  {
			$ID = 'id-' . $this->create_guid();
//			echo "RELATION FOUND: $searchValue == $type$source$target<br/>";
		} else {
			$ID = $this->archiRelations[$index]["ID"];
//			echo "RELATION $searchValue already exists<br/>";
		}
		$this->listOfRelations[$rowId]["ID"] = $ID;
		$this->listOfRelations[$rowId]["Type"] = $type;
		$this->listOfRelations[$rowId]["Name"] = $name;
		$this->listOfRelations[$rowId]["Documentation"] = $documentation;
		$this->listOfRelations[$rowId]["Source"] = $source;
		$this->listOfRelations[$rowId]["Target"] = $target;

		if ($type === "AssociationRelationship") {
			$this->listOfProperties[$rowId]["ID"] = $ID;
			$this->listOfProperties[$rowId]["Key"] = "Directed";
			$this->listOfProperties[$rowId]["Value"] = "true";
		}


	}

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

		// even testen of de index wel voorkomt in de array
		if (!isset( $array[ $index ])) {
			return null;
		}

		// We hebben alleen een valide index, als de kolom bestaat en ook daadwerkelijk
		// dezelfde value heeft als we verwachten
		if ( $index && array_key_exists( $column, $array[ $index ] ) &&
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
}