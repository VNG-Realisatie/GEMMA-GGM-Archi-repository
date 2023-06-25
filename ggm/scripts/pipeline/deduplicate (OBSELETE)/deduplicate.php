<?php
/*
 * Convert a TOPdesk CSV file (Export from TOPdesk in CSV-format
 *
 * @author Toine Schijvenaars (XL&Knowledge), may 2023
 * @version 0.01
 */


include __DIR__ . "/../csvreader/CSVReader.php";
include __DIR__ . "/../archicsvwriter/ArchiCSVWriter.php";

if ( $_FILES ) {
	if ($_FILES["fileZip"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No archi file entered<br>";
		die;
	}

	$mode               = 'FORM';
	$archiFile          = $_FILES["fileZip"]["tmp_name"][0];
	if (isset( $_POST["test"][0])) {
		$test = $_POST["test"][0];
	} else {
		$test = false;
	}

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

$class = new DEDUPLICATE( $mode, $archiFile, $test );

$class->run();

class DEDUPLICATE {
	private $mode;
	private $archiZipFile;
	private $test;
	private $exportDate;
	private $messageCounter;

	private $archiElements = array();
	private $archiProperties = array();
	private $archiRelations = array();


	private $listOfElements;
	private $listOfProperties;
	private $listOfRelations;
	private $listOfMessages = array();


//	private $smartcoreModel;


	public function __construct( $mode, $archiFile, $test ) {
		$this->mode = $mode;
		$this->archiZipFile = $archiFile;
		$this->test = $test;
		$this->exportDate = date( "\"dmY-h:m:s\"" );
	}

	public function run() {

		$result = CSVReader::processArchiZipfile($this->archiZipFile);
		$this->archiElements = $result[0];
		$this->archiProperties = $result[1];
		$this->archiRelations = $result[2];
		//ShowOnScreen werkt niet goed meer.
//		ArchiCSVWriter::showOnScreen($this->archiElements, $this->archiProperties, $this->archiRelations);

		// process elements
		$this->processElements();

		//analyse elements
//		$this->analyseElements();


		// compose update list
		$this->composeUpdateList();

		if(!$this->test) {
			// now write it to a GGM-CSV format
//			$csvArchiWriter = new ArchiCSVWriter();
			//ShowOnScreen werkt niet goed meer.
//			ArchiCSVWriter::showOnScreen($this->listOfElements, null,  $this->listOfRelations);die;
//			echo "<pre>"; var_dump($this->listOfReplacements); die;
//			$csvArchiWriter::writeData2GGM( $this->listOfElements, $this->listOfRelations, null, '-ARCHI2GGM', $this->listOfMessages );
		}
	}


	/**
	 */
	private function processElements() {
		$nr = 1000;
		$prevSearchValue = 'none';
		$nextSearchValue = 'doe';
		$totalDuplicates = 0;
		$nrOfDuplicates = 0;
		$prevID = 'none';
		$firstPrevID = "zero";
		$newID = true;
		if($this->test){echo "<br/><b>ANALYZING ALL THE ELEMENTS</b><br/>";};
		if($this->test){echo "<br/><b>================================</b><br/>";};

		foreach ( $this->archiElements as $key => $row ) {
			$currentID = $row["ID"];
			// if Name and Type are present
			if (isset($row["Name"]) && isset($row["Type"])) {
				$currentSearchValue = $row["Name"] . $row["Type"];
				if (isset($this->archiElements[$key+1]["Name"]) &&
					isset($this->archiElements[$key+1]["Type"])
				) {
					$nextSearchValue = $this->archiElements[$key + 1]["Name"] . $this->archiElements[$key + 1]["Type"];
					$nextID = $this->archiElements[$key + 1]["ID"];
				}
				if (isset($this->archiElements[$key-1]["Name"]) &&
					isset($this->archiElements[$key-1]["Type"])
				) {
					$prevSearchValue = $this->archiElements[$key - 1]["Name"] . $this->archiElements[$key - 1]["Type"];
					$prevID = $this->archiElements[$key - 1]["ID"];
				}
//				$dateTime = $this->getArchiPropertyValue( $row["ID"] , 'datum-tijd-export' );

				// if we have a new ID for comparison of occurrences
				if ( $newID ) {
					if($this->test){echo "<b>S: ANALYZING:  $currentSearchValue versus $nextSearchValue (FIRST: $currentID - NEXT: $nextID)</b><br/>";};
					$firstPrevID = $currentID;
				}

				if($this->test){echo "<b>$key</b> - COMPARING CurrentSearch: <b>$currentSearchValue: " . ($key+1) . "</b> - NextSearch: <b>$nextSearchValue</b> <br/>";};

				// search for an equal value, by comparing it to the previous record
				if ( $currentSearchValue == $nextSearchValue  ) {
					$ggm_guid = $this->getArchiPropertyValue ( $prevID, 'ggm-guid' );
					$objectid = $this->getArchiPropertyValue ( $prevID, 'Object ID' );
					$bedrijfsobject = $this->getArchiPropertyValue ( $prevID, 'Business Object' );
					if($this->test) { echo "== EQUALS-1:  $currentSearchValue EQUALS $nextSearchValue <br/>"; } ;
					if($this->test) {$this->duplicateElements[$firstPrevID ][$nrOfDuplicates] = $currentID . ", Name: " . $row["Name"] . ", Type: " . $row["Type"] . ", GGM-GUID: $ggm_guid" . ", BO: $bedrijfsobject" . ", ID: $objectid";} ;
					if(!$this->test) {$this->duplicateElements[$firstPrevID ][$nrOfDuplicates] = $currentID;} ;
					$nrOfDuplicates++;

					$newID = false; // we will continue to compare with the next row
				} else { // nothing found so we continue start with a new item

					if ( !$newID ) { // we finalize the comparison for this item with a new ID to compare
						// check if we have to start a new one, or finish the previous one by checking the searchvalue
						$ggm_guid = $this->getArchiPropertyValue ( $currentID, 'ggm-guid' );
						$objectid = $this->getArchiPropertyValue ( $currentID, 'Object ID' );
						$bedrijfsobject = $this->getArchiPropertyValue ( $currentID, 'Business Object' );
						if ($currentSearchValue==$prevSearchValue) {
							if($this->test) {echo "== UNEQUALS-2.1: $currentSearchValue ($currentID) UNEQUALS $nextSearchValue ($nextID) <br/>";};
							if($this->test) {$this->duplicateElements[$firstPrevID][$nrOfDuplicates] = $currentID  . ", Name: " . $row["Name"] . ", Type: " . $row["Type"] . ", GGM-GUID: $ggm_guid" . ", BO: $bedrijfsobject" . ", ID: $objectid";} ;
							if(!$this->test) {$this->duplicateElements[$firstPrevID][$nrOfDuplicates] = $currentID  ;} ;
						} else {
							if($this->test) {echo "== UNEQUALS-2.2: $currentSearchValue ($currentID) UNEQUALS $prevSearchValue ($prevID) <br/>";};
							if($this->test) {$this->duplicateElements[$currentID][$nrOfDuplicates] = $prevID  . ", Name: " . $row["Name"] . ", Type: " . $row["Type"] . ", GGM-GUID: $ggm_guid" . ", BO: $bedrijfsobject" . ", ID: $objectid";} ;
							if(!$this->test) {$this->duplicateElements[$currentID][$nrOfDuplicates] = $prevID;} ;
						}


					}
					if($this->test){echo "Nr of duplicates: $nrOfDuplicates for $currentSearchValue<br/>";};
					if($this->test){echo "<b>F: ANALYZING: $currentSearchValue ($firstPrevID)</b><br/>";};
					if($this->test){echo "==================================<br/>";};
					$totalDuplicates ++;
					$nrOfDuplicates = 0;
					$newID = true;

				}

			} else {
				$currentID = $row["ID"];
				$prevID = $this->archiElements[$key - 1]["ID"];
				$nextID = $this->archiElements[$key + 1]["ID"];
				if($this->test){echo "<b>NO ACTION: $currentID - $prevID -  $nextID</b><br/>";};
				if ($nextSearchValue != $prevSearchValue) {
					$newID = true;
				}
				// what to do?
//				if($this->test){echo $row["Name"] . $row["Type"];}; // TESTING the values
			}


		}
		if($this->test){echo "<br/>Total duplicates: $totalDuplicates<br/>";};



	}

	private function composeUpdateList() {
		if($this->test){echo "<pre>";var_dump($this->duplicateElements);};
		$this->listOfReplacements = array();
		foreach ($this->duplicateElements as $pkey => $elements){
			$n = 1;
			foreach ($elements as $element) {
				if ($pkey != $element) {
					$this->listOfReplacements[$pkey]['old']  = $element;
					$this->listOfReplacements[$pkey]['new']  = $pkey;
					$this->listOfReplacements[$pkey]['ggm']  = $this->getArchiPropertyValue ( $pkey, 'ggm-guid' );
					$this->listOfReplacements[$pkey]['type'] = 'element';
				}
			}

		}
		if($this->test){echo "<pre>";var_dump($this->listOfReplacements);};
//		echo "<pre>";var_dump($this->listOfReplacements);die;
		return true;
	}
//die;
	private function analyseElements () {
//		foreach ( $this->archiElements as $key => $row ) {
//			// if Name and Type are present
//			if ( isset( $row["Type"] ) && isset( $row["Name"] ) ) {
//				$search = $row["Type"] . $row["Name"];
//				$result = array_count_values(array_column(archiElements, 'Search'));
//
//			}
//		}
		$result = array_count_values(array_column($this->archiElements, 'Search'));
//		echo "<pre>"; var_dump($result); die;
		$duplicatesFile = ArchiCSVWriter::createCsvFileFromArray( $result );
//		var_dump($duplicatesFile);die;
// 		Open a file in write mode ('w')
//		$tmpFile = tempnam( sys_get_temp_dir(), uniqid() . '.csv' );
//
//		$duplicatesFile = fopen($tmpFile, 'w');

// 		Loop through file pointer and a line

		foreach ($result as $items) {
//			fputcsv($duplicatesFile, $items );
			foreach ($result as $key => $value ) {
				fputcsv( $duplicatesFile, array( $key, $value ) );
//			echo "$key --> $value . <br/>";
			}
		}
//die;
//		var_dump($duplicatesFile);die;
		fclose($duplicatesFile);

		$fname = 'ArchiDuplicatesfile_' . date('m-d-Y_H:i:s') . '.csv';
		header( 'Content-type: application/text' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		echo  $duplicatesFile ;

	}


	private function find_key_value( $array, $key, $val )
	{
		foreach ($array as $item)
		{
			if (is_array($item) && $this->find_key_value($item, $key, $val)) return true;

			if (isset($item[$key]) && $item[$key] == $val) return true;
		}

		return false;
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
//			var_dump($row);
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
//		die;
	}

	private function getArchiPropertyValue ( $objectID, $property ) {
		if ($this->test) {$retValue='NO VALUE';} else {$retValue = '';};
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
