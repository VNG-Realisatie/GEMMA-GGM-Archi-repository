<?php
/*
 * Convert a TOPdesk CSV file (Export from TOPdesk in CSV-format)
 *
 * @author Toine Schijvenaars (XL&Knowledge), may 2023
 * @version 0.01
 */


include __DIR__ . "/../../csvreader/CSVReader.php";
include __DIR__ . "/../../archicsvwriter/ArchiCSVWriter.php";


if ( $_FILES ) {
	if ($_FILES["replacements"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No source file entered<br>";
		die;
	}
	if ($_FILES["fileArchi"]["error"][0] > 0) {
		echo "Error: " . $_FILES["file"]["error"][0] . " No target file entered<br>";
		die;
	}



	$mode               = 'FORM';
	$replacements       = $_FILES["replacements"]["tmp_name"][0];
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
	$mode         = 'CLI';
	$replacements = '';
	$target		  = '';
	$delimeter    = ',';
	$test         = false;

	if ( isset($argv[1])) {
		$replacements     = $argv[1];  // $replacements file
	}
	if ( isset($argv[2])) {
		$target     = $argv[2];
	}

	if ( isset($argv[3])) {
		$test   = $argv[3];
	}

}

$class = new replaceIds( $mode, $replacements, $target, $delimeter, $test );
$class->run();

class replaceIds {
	// worker variables
	private $mode;
	private $replacements;
	private $test;
	private $delimeter;
	private $exportDate;
	private $messageCounter;

	// contains the data of the replacements
	private $replacementData;

	public function __construct( $mode, $replacements, $target, $delimeter, $test ) {
		$this->mode 	 	= $mode;
		$this->replacements	= $replacements;
		$this->target	 	= $target;
		$this->test 	 	= $test;
		$this->delimeter 	= $delimeter;
		$this->exportDate 	= date( "\"dmY-h:m:s\"" );
		$this->messageCounter = 0;

	}

	public function run() {
		$csvReplacementsReader = new CSVReader();
		$replacements = $csvReplacementsReader::readCSVDataFromFile($this->replacements, $this->delimeter );

//		echo "<pre>";
//		var_dump ($replacements);

		$xmlstring = file_get_contents($this->target);
//		echo "<html>";
//		var_dump ($xmlstring);


		$newXMLstring = $this->processReplacements($replacements, $xmlstring);
//		var_dump ($newXMLstring);

		$fname = 'replacedIds_' . date('m-d-Y_H:i:s') . $this->replacements . '.archimate';
		header( 'Content-type: application/xml' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );

		echo $newXMLstring;
		exit(0);


	}

	private function  processReplacements($replacements, $xmlstring) {
		foreach ($replacements as $replacement) {
//			echo 'Replace ' . $replacement['old'];
//			echo ' with: ' . $replacement['new'];
//			echo ' ggm-guid:' . $replacement['ggm'];
//			echo ' type: '. $replacement['type'];
//			echo '<br/>';

			$xmlstring = str_replace( $replacement['old'],$replacement['new'], $xmlstring );
		}

		return $xmlstring;
	}



}