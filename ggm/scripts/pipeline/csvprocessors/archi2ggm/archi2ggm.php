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
	if ($_FILES["fileZip"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No archi file entered<br>";
		die;
	}

	$mode               = 'FORM';
	$archiFile          = $_FILES["fileZip"]["tmp_name"][0];
	$test               = '';

} else {
	$mode       = 'CLI';
	$archiFile  = '';
	$test       = '';

	if ( isset($argv[1])) {
		$archiFile     = $argv[1];  // source file
	}

	if ( isset($argv[2])) {
		$test   = $argv[2];
	}

}

$class = new ARCHI2GGM( $mode, $archiFile, $test );

$class->run();

class ARCHI2GGM {
	private $mode;
	private $archiZipFile;
	private $test;
	private $exportDate;
	private $messageCounter;

	private $archiElements = array();
	private $archiProperties = array();
	private $archiRelations = array();


	private $targetDataElements; // contains the data of the csv, objects
	private $targetDataProperties; // contains the data of the csv, relations
	private $targetDataRelations; // contains the data of the csv, relations

	private $listOfElements;
	private $listOfProperties;
	private $listOfRelations;
	private $listOfMessages = array();


//	private $smartcoreModel;


	public function __construct( $mode, $archiFile, $test ) {
		$this->mode = $mode;
		$this->archiZipFile = $archiFile;
		$this->targetDataElements = "";
		$this->targetDataRelations = "";
		$this->test = $test;
		$this->exportDate = date( "\"dmY-h:m:s\"" );
	}

	public function run() {

		$result = CSVReader::processArchiZipfile($this->archiZipFile);
		$this->archiElements = $result[0];
		$this->archiProperties = $result[1];
		$this->archiRelations = $result[2];

//		echo '<pre>'; var_dump($this->archiRelations);die;

		//ShowOnScreen werkt niet goed meer.
//		ArchiCSVWriter::showOnScreen($this->archiElements, $this->archiProperties, $this->archiRelations);

		$this->processElements();
//		$this->processProperties();
		$this->processRelations();

		// now write it to a GGM-CSV format
		$csvArchiWriter = new ArchiCSVWriter();
		//ShowOnScreen werkt niet goed meer.
//		ArchiCSVWriter::showOnScreen($this->listOfElements, null,  $this->listOfRelations);die;
//		echo "<pre>"; var_dump($this->listOfRelations); die;
		$csvArchiWriter::writeData2GGM( $this->listOfElements, $this->listOfRelations, '-ARCHI2GGM', $this->listOfMessages );
	}


	/**
	 * nr (technisch nummer, nodig voor de uitwisseling, wordt niet opgenomen in de modellen)
	 * naam (label)
	 * definitie
	 * uml-type
	 * ggm-guid
	 * bron
	 * domein-iv3 (Indeling GGM)
	 * archimate-type (business-object/data-object)
	 * toelichting
	 * gemma-guid
	 * synoniemen (comma seperated)
	 * domein-dcat
	 * domein-gemma
	 * datum-tijd-export (ddmmyyyy-hh:mm:ss)
	 */
	private function processElements() {
		$nr = 1;
		foreach ( $this->archiElements as $row ) {
			if ( isset( $row["Type"] ) ) {
				if ( in_array( $row["Type"], array( "BusinessObject", "DataObject" ) ) ) {
					$objectID = $row["ID"];
					$this->listOfElements[$objectID]["nr"] = $nr;
					if ( isset( $row["Name"] ) ) {
						$this->listOfElements[$objectID]["naam"] = $row["Name"];
					}
					if ( isset( $row["Documentation"] ) ) {
						$this->listOfElements[$objectID]["definitie"] = $row["Documentation"];
					}
					$this->listOfElements[$objectID]['uml-type'] = $this->getArchiPropertyValue( $objectID, 'uml-type' );
					$this->listOfElements[$objectID]['ggm-guid'] = $this->getArchiPropertyValue( $objectID, 'ggm-guid' );
					$this->listOfElements[$objectID]['bron'] = $this->getArchiPropertyValue( $objectID, 'Bron' );
					$this->listOfElements[$objectID]['domein-iv3'] = $this->getArchiPropertyValue( $objectID, 'domein-iv3' );
					$this->listOfElements[$objectID]['archimate-type'] = $row["Type"];
					$this->listOfElements[$objectID]['toelichting'] = $this->getArchiPropertyValue( $objectID, 'Toelichting' );
					$this->listOfElements[$objectID]['gemma-guid'] = $objectID;;
					$this->listOfElements[$objectID]['synoniemen'] = $this->getArchiPropertyValue( $objectID, 'Synoniem' );
					$this->listOfElements[$objectID]['domein-dcat'] = $this->getArchiPropertyValue( $objectID, 'Domein-dcat' );
					$this->listOfElements[$objectID]['domein-gemma'] = $this->getArchiPropertyValue( $objectID, 'Domein-gemma' );
					$this->listOfElements[$objectID]['datum-tijd-export'] = $this->exportDate;
					$nr ++;
					$this->addMessage($this->messageCounter++, "Element", "Toegevoegd", $row["ID"] . ', ' .$row["Type"] . ', ' .  $row["Name"]);

				} else {
					$this->addMessage($this->messageCounter++, "Element", "Niet toegevoegd", $row["Type"] . "  wordt niet ondersteund (Business of DataObject");

				}
			} else {
				$this->addMessage($this->messageCounter++, "Element", "Niet toegevoegd", " geen elementtype aanwezig");
			}
		}
	}

	/**
	 * nr (technisch nummer, nodig voor de uitwisseling, wordt niet opgenomen in de modellen)
	 * naam (label)
	 * definitie
	 * uml-type
	 * ggm-guid
	 * bron
	 * domein-iv3 (Indeling GGM)
	 * archimate-type (business-object/data-object)
	 * toelichting
	 * gemma-guid
	 * synoniemen (comma seperated)
	 * domein-dcat
	 * domein-gemma
	 * datum-tijd-export (ddmmyyyy-hh:mm:ss)
	 */
	private function processProperties() {
		$nr = 1;
		foreach ( $this->archiProperties as $row ) {

			if ( isset( $row["ID"] ) ) {
				$propID = $row["ID"];
				{
					$this->listOfProperties[$propID]["ggm-guid"] = $this->getArchiPropertyValue( $propID, 'ggm-guid' );
					$this->listOfProperties[$propID]['gemma-guid'] = $row['ID'];
					$this->listOfProperties[$propID]['eigenschap'] = $row['Key'];
					$this->listOfProperties[$propID]['waarde'] = $row['Value'];
					$this->addMessage($this->messageCounter++, "Eigenschap", "Toegevoegd",
						$this->listOfProperties[$propID]["ggm-guid"] . ', ' .
						$this->listOfProperties[$propID]['gemma-guid'] . ', ' .
						$this->listOfProperties[$propID]['eigenschap']  . ', ' .
						$this->listOfProperties[$propID]['waarde']
					);

				}
				$nr ++;
			}
		}
	}


	/**
	 * nr (technisch nummer, nodig voor de uitwisseling, wordt niet opgenomen in de modellen)
	 * naam (label)
	 * definitie
	 * uml-type ( Associatie, Generalisatie, Aggregatie)
	 * ggm-guid
	 * ggm-source-guid
	 * ggm-target-guid
	 * toelichting
	 * archimate-type (Association, Specialisatie, Aggregation)
	 * gemma-guid
	 * gemma-source-guid
	 * gemma-target-guid
	 * datum-tijd-export (ddmmyyyy-hh:mm:ss)
	 */
	private function processRelations() {
		$nr = 1;
		foreach ( $this->archiRelations as $row ) {
			$rowID = $row["ID"];
			$this->listOfRelations[$rowID]["nr"] = $nr;
			if ( isset( $row["Name"] ) ) {
				$this->listOfRelations[$rowID]["naam"] = $row["Name"];
			}
			$this->listOfRelations[$rowID]['uml-type'] = $this->getArchiPropertyValue( $rowID, 'uml-type' );
			$this->listOfRelations[$rowID]['ggm-guid'] = $this->getArchiPropertyValue( $rowID, 'ggm-guid' );
			$this->listOfRelations[$rowID]['ggm-source-guid'] = $this->getArchiPropertyValue( $rowID, 'ggm-source-guid' );
			$this->listOfRelations[$rowID]['ggm-target-guid'] = $this->getArchiPropertyValue( $rowID, 'ggm-target-guid' );
			$this->listOfRelations[$rowID]['uml-type'] = $this->getArchiPropertyValue( $rowID, 'uml-type' );
			if ( isset( $row["Documentation"] ) ) {
				$this->listOfRelations[$rowID]["toelichting"] = $row["Documentation"];
			}
			if ( isset( $row["Type"] ) ) {
				$this->listOfRelations[$rowID]["archimate-type"] = $row["Type"];
			}
			$this->listOfRelations[$rowID]["gemma-guid"] = $rowID;
			if ( isset( $row["Source"] ) ) {
				$this->listOfRelations[$rowID]["gemma-source-guid"] = $row["Source"];
			}
			if ( isset( $row["Target"] ) ) {
				$this->listOfRelations[$rowID]["gemma-target-guid"] = $row["Target"];
			}
			$this->listOfRelations[$rowID]["datum-tijd-export"] = $this->exportDate;
			$this->addMessage($this->messageCounter++, "Relatie", "Toegevoegd",
				$this->listOfRelations[$rowID]["nr"]  . ', ' .
				$this->listOfRelations[$rowID]['uml-type'] . ', ' .
				$this->listOfRelations[$rowID]['ggm-guid'] . ', ' .
				$this->listOfRelations[$rowID]['ggm-source-guid'] . ', ' .
				$this->listOfRelations[$rowID]['ggm-target-guid']
			);
			$nr++;
		}
	}

	private function getArchiPropertyValue ( $objectID, $property ) {
		$retValue = '';
		$searchValue = $objectID . $property;
		$index = array_search( $searchValue, array_column( $this->archiProperties,'Search' ), true  );
		if ( $index ) {
			$retValue = $this->archiProperties[$index]["Value"];
		}
		return $retValue;
	}
	private function addMessage( $counter, $concepttype, $typeOfAction, $value )  {
		$this->listOfMessages[$counter]["Concept type"] = $concepttype;
		$this->listOfMessages[$counter]["Action"] = $typeOfAction;
		$this->listOfMessages [$counter]["Message"] = $value;
		$this->listOfMessages [$counter]["Date"] = $this->exportDate;
	}
}