<?php

 /**
 * Display table field descriptions.
 *
 * @author  Doug Burner <doug.869@gmail.com> 2013-12-05
 *
 * @param   string  tabtype     Table type from form submit
 *
 */

if(!defined('DOKU_INC')) die();

 # Ensure common functions and database class defenition have been imported.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'database.php';


 /**
 * Return table field descriptions.
 *
 * @param   string  tabtype     Table type from form submit
 *
 * @return  string  HTML encoded table field data.
 *
 */
function Sot_List_Table_Fields(){
	global $INPUT;

    # The table type defaults to "server".
    $vTabType = strtolower($INPUT->str('tabtype', "server"));

	# Print the form heading.
	$vPrint	= CreateHtmlHeading("Field descriptions for the $vTabType table", "field_decriptions");

	# Get an array of assoc arrays of table field descriptions.
	$SOT[DatabaseObject] = new Sot_Database;
	$vTableLines = $SOT[DatabaseObject]->Get_Sot_Table_Fields($vTabType."s");

	# Format and print in a standard table.
	$vPrint .= PrintStandardTable2($vTableLines);

	$vPrint .= "<button onclick=\"history.go(-1);return true;\">Back</button>";

	return $vPrint.$SOT[DatabaseObject]->Show_DB_Connection_Details();

} //Sot_List_Table_Fields

