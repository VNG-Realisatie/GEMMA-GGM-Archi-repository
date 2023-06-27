<?php

 class CSVReader {

	 public static function readCSVDataFromFile ( $dataFile, $delimeter = ',', $fromZip = false ) {
		 if ( $fromZip ) {
			 $dataFile = sprintf( "zip://%s#%s", $fromZip, $dataFile);
		 }
		 if ( ( $handle = fopen( $dataFile, "r" ) ) !== FALSE) {
			 $csvHeaders = [];
			 $row = 0;
			 while ( ($data = fgetcsv($handle, 1000, trim( $delimeter ), '"')) !== FALSE) {
				 $num = count( $data );
				 if ( $row == 0 ) {
					 for ( $c = 0; $c < $num; $c ++ ) {
						 $csvHeaders[] = $data[$c];
					 }
				 } else {
					 for ( $c = 0; $c < $num; $c ++ ) {
						 $csvData[$csvHeaders[$c]] = $data[$c];
					 }
					 $dataItems[] = $csvData;
				 }
				 $row++;
			 }
		 }
		 fclose($handle);

		 return $dataItems;

	 }


	 /**
	  * @param $zip
	  *
	  * Read the zip file
	  * extract the CSV files and put them in to array
	  * so that we can use them for references
	  *
	  */
	 public static function processArchiZipfile( $archiZipFile ) {
		 $zip = zip_open( $archiZipFile );
		 if ( $zip ) {
			 $targetElements = CSVReader::readCSVDataFromFile( 'elements.csv', ', ', $archiZipFile );
			 foreach ( $targetElements as &$propRow ) {
				 $propRow["Search"] =
					 ( isset( $propRow["Type"] ) ? $propRow["Type"] : "" ) .
					 ( isset( $propRow["Name"] ) ? $propRow["Name"] : "" );
			 }

			 $targetProperties = CSVReader::readCSVDataFromFile( 'properties.csv', ', ', $archiZipFile );
			 foreach ( $targetProperties as &$propRow ) {
				 $propRow["Search"] =
					 ( isset( $propRow["ID"] ) ? $propRow["ID"] : "" ) .
					 ( isset( $propRow["Key"] ) ? $propRow["Key"] : "" );
			 }
			 $targetRelations = CSVReader::readCSVDataFromFile( 'relations.csv', ', ', $archiZipFile );
			 foreach ( $targetRelations as &$propRow ) {
				 $propRow["Search"] =
					 ( isset( $propRow["Type"] ) ? $propRow["Type"] : "" ) .
					 ( isset( $propRow["Name"] ) ? $propRow["Name"] : "" ) .
					 ( isset( $propRow["Source"] ) ? $propRow["Source"] : "" ) .
					 ( isset( $propRow["Target"] ) ? $propRow["Target"] : "" );
			 }
		 } else {
			 die ("Target is not a ZIP file");
		 }
		 return array($targetElements, $targetProperties, $targetRelations);
	 }

}
