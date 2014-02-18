<?php

/**
 * Display the history of the CI name and table type passed to the form.
 *
 * @author  Doug Burner <doug869@users.noreply.github.com> 2014-01-18
 *
 * @param   string  $INPUT->str('name')     The name of the CI.
 * @param   string  $INPUT->str('tabtype')  The table type of the CI. Can be server, user or group.
 *                                          Defaults to server.
 *
 */

if(!defined('DOKU_INC')) die();

# Import common functions.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'database.php';

/**
 * Display the history of the CI name and table type passed to the form.
 */
function Sot_Display_History(){
	global $INPUT;

	$DOC	= "";

    $SOT[CI_ID]             = $INPUT->str('ci_id', "0");
    $SOT[TabType]           = strtolower($INPUT->str('tabtype', "server"));
    $SOT[CiName]            = $INPUT->str('name', "no_server_selected");
    $SOT[DatabaseObject]    = new Sot_Database;
    $SOT[TableFieldsArray]  = $SOT[DatabaseObject]->Get_Table_Field_Details($SOT[TabType]."s");
    $SOT[FieldMetaArray]    = $SOT[DatabaseObject]->Get_Metadata_Array($SOT[TabType]."s");
    //$SOT[HilightArray]      = array();
    $SOT[FoundArray]        = array();
    list($SOT[CurrentCiArray])  = $SOT[DatabaseObject]->Get_CI_Records_From_Array(
                                  $SOT[TabType], array('id' => $SOT[CI_ID]), TRUE);
    $SOT[CanUserChangeCI]   = Can_User_Change_CI($SOT);

    if(!is_null($SOT[CurrentCiArray][name])) $SOT[CiName] = $SOT[CurrentCiArray][name];

	# Query the database for the history records.
    # Only display the action logs to users in the admin or author groups.
	$Sot_Database = new Sot_Database;
	$SOT[FoundArray] = $Sot_Database->Get_CI_History2($SOT, Is_Super_User(), TRUE);

     # Create the page heading.
    $DOC .= CreateHtmlHeading("History for {$SOT[TabType]} {$SOT[CiName]}", $SOT[TabType]."_history");

    # Draw top nav buttons.
    $DOC .= Draw_Top_Nav_Links($SOT,"",Get_Nav_Target($SOT),TRUE,$SOT[CanUserChangeCI],$SOT[CanUserChangeCI]);

	# Print in a standard table.
	$DOC .= PrintStandardTable2($SOT[FoundArray]);

    # Add a home page button. Use a button instead of a link to try to keep the pages consistent.
    $DOC .= Draw_Nav_Button("OK", SOT_HOME_PAGE."start");
    return $DOC.$SOT[DatabaseObject]->Show_DB_Connection_Details();

} //Sot_Display_History


