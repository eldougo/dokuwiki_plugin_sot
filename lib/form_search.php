<?php
/**
 * Doug Burner <doug869@users.noreply.github.com> 18/12/2013
 * Generate a form allowing users to add a new CI to the SoT
 * Parameters:
 *			tabtype - Report type, can be "server", "user" or "group". Defaults to "server".
 *								Note that this hasn't been fully implemented.
 *			 Others - CI data can be passed by both POST and GET and will override all
 *								default data.
 */

 if(!defined('DOKU_INC')) die();

# Ensure common functions have been imported.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'form_new.php';
include_once SOT_LIB.'form_report.php';
include_once SOT_LIB.'database.php';

function Sot_Search_CI() {
	global $INPUT;

	$DOC = "";
    $vPageTitle = "Search";
    $vButtonName = "Search";
    $vMainHeading   = "";
    $ParamArray = array();
	$vSuccess = FALSE;

    # Get table fields array. The format of the returned lines:
    # The table type defaults to "server".
    $SOT[CI_ID]             = $INPUT->str('ci_id', "0");
    $SOT[TabType]           = strtolower($INPUT->str('tabtype', "server"));
    $SOT[CiName]            = $INPUT->str('name');
    $SOT[DatabaseObject]    = new Sot_Database;
    $SOT[TableFieldsArray]  = $SOT[DatabaseObject]->Get_Table_Field_Details($SOT[TabType]."s");
    $SOT[FieldMetaArray]    = $SOT[DatabaseObject]->Get_Metadata_Array($SOT[TabType]."s");
    //$SOT[HilightArray]      = array();
    $SOT[FoundArray]        = array();

    # Check if this is a submit.
	if($INPUT->str('action') == "submit") {
		if(!checkSecurityToken()) return "";

        $SOT[FoundArray] = Search_CI($SOT, $ParamArray);

        # Get the number of records returned, remembering the first record is the field names.
        $vRowCount = count($SOT[FoundArray]) - 1;

        # Print the results in a horizontal table.
		$DOC .= PrintHorizontalTable2($SOT);

        # Show CSV link in the top menu.
		$vMainHeading   = CreateHtmlHeading("$vPageTitle - (found $vRowCount)", "found");
        $vMainHeading  .= Draw_Top_Nav_Links($SOT,"","",TRUE,FALSE,FALSE);
		$vButtonName    = "Search Again";
		$vSearchHeading ="</div>\n".CreateHtmlHeading("Search Again",  "search_again");

	} else {

        # Do not show CSV link in the top menu if there was an error.
        $vMainHeading   = CreateHtmlHeading($vPageTitle, "search");
        $vMainHeading  .= Draw_Top_Nav_Links($SOT,"","",FALSE,FALSE,FALSE);
		$vSearchHeading = "";
	}

	$DOC  = $vMainHeading.$DOC.$vSearchHeading;

    $DOC .= Display_Notify_Box("Hint: Pattern matching enables you to use “_” to match "
            ."any single character and “%” to match an arbitrary number of characters "
            ."(including zero characters).");

	$DOC .= PrintInputForm($SOT,TRUE,$vButtonName,"nosearch");
	return $DOC.$SOT[DatabaseObject]->Show_DB_Connection_Details();
}

/********************************************************************************
* Search for and return an HTML formatted table containg the found CIs.
* Input:
*	$pTableLines	An array of assoc arrays containing table field details.
* Output:
* 	$ParamArray		The args used built from passed form input used to search for the returned CIs.
*	Return			A string containing an HTML formatted table containg the found CIs.
* Format:
**********************************************************************************/
function Search_CI(&$SOT, &$ParamArray=""){

	# Match the fields with the passed values if present and assemble
	# the search args. The return format is an array of assoc arrays
	# containing key=>value pairs describing record data search fields.
	$ParamArray = AssembleSearchCommandArgs($SOT[TableFieldsArray]);

	# Attempt to execute the command.
	$vTableLines = $SOT[DatabaseObject]->Get_CI_Records_From_Array($SOT[TabType], $ParamArray);

	return $vTableLines;
}

/********************************************************************************
* Match the passed table fields with the values input via the form
* if present and assemble and return a parameter array. Insert a loginID
* parameter as well if $AddLoginID is TRUE. Empty values are skipped.
* Input
* 	$TableFieldsArray		An array assoc arrays containing table field data as sourced
* 					from Sot_Database::Get_Table_Field_Details().
*	$AddLoginID		Append the user's DW login name if TRUE.
* Return			An array of assoc arrays containing key=>value pairs describing
* 					record data fields. This can then be used to insert new CI records.
**********************************************************************************/
function AssembleSearchCommandArgs(&$TableFieldsArray){
	global $INPUT;

	foreach($TableFieldsArray as $TableField){
		$FieldName = $TableField['column_name'];
		$InputValue = $INPUT->str($FieldName);

        # The id field is the Dokuwiki page ID, not CI record ID.
        if($InputValue and $FieldName != "id"){
			$ParamArray[$FieldName] = trim($InputValue);
			if(SOT_DEBUG) echo "$FieldName => {$ParamArray[$FieldName]}<BR>";
		}
	}

	# Return format: An array of assoc arrays of key=>value pairs
	return $ParamArray;
}

