<?php

 /**
 * Display CI listings in a report based on passed search parameters.
 *
 * Load from anywhere with "include_once SOT_LIB.'form_report.php';"
 *
 * @author  Doug Burner <doug.869@gmail.com> 2013-12-18
 *
 */

if(!defined('DOKU_INC')) die();

 # Ensure common functions have been imported.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'database.php';


/**
 * Generate a list of CIs and display in a report format.
 *
 * @return  string  HTML encoded report.
 *
 */
function Sot_Generate_Report(){
	global $INPUT;
	$vTitle = $INPUT->str('title');
	$EncodedParams = $INPUT->str('params');

    $SOT[CI_ID]             = $INPUT->str('ci_id', "0");
    $SOT[TabType]           = strtolower($INPUT->str('tabtype', "server"));
    $SOT[CiName]            = $INPUT->str('name');
    $SOT[DatabaseObject]    = new Sot_Database;
    //$SOT[TableFieldsArray]  = $SOT[DatabaseObject]->Get_Table_Field_Details($SOT[TabType]."s");
    $SOT[FieldMetaArray]    = $SOT[DatabaseObject]->Get_Metadata_Array($SOT[TabType]."s");
    //$SOT[HilightArray]      = array();
    $SOT[FoundArray]        = array();
    list($SOT[CurrentCiArray])  = $SOT[DatabaseObject]->Get_CI_Records_From_Array(
                                  $SOT[TabType], array('id' => $SOT[CI_ID]), TRUE);
    $SOT[CanUserChangeCI]   = Can_User_Change_CI($SOT);

    if(!is_null($SOT[CurrentCiArray][name])) $SOT[CiName] = $SOT[CurrentCiArray][name];

	$DOC	= "";

	# Create the heading and the top of the table here so we can take different
	# actions depending on the CI count in the table body.
	$vHead	= "";

    # Get the requested CI records from the database.
	if($SOT[CI_ID] != 0){
        $SOT[FoundArray] = $SOT[DatabaseObject]->Get_CI_Records_From_Array($SOT[TabType], array("id" => $SOT[CI_ID]));
        if(!$vTitle) $vTitle = "Details for {$SOT[TabType]} {$SOT[CiName]}";
    } else {
        $SOT[FoundArray] = $SOT[DatabaseObject]->Get_CI_Records_From_Encoded($SOT[TabType], $EncodedParams);
    }

    $vRowCount = count($SOT[FoundArray]) - 1;
    $OneRow = $vRowCount == 1;

    $TableMeta = $SOT[DatabaseObject]->Get_Metadata_Array("table");
    $PrintVert = $TableMeta[$SOT[TabType]."s"]['orientation'] == 'vert';

    # Select a horizontal or vertical table. Only server tables are printed vertically.
	if($vRowCount == 1 and $PrintVert){
		$DOC .= PrintVerticalTable($SOT);
		$vHead  .= CreateHtmlHeading("$vTitle", $SOT[TabType]."_report");

        # Enable the edit button and history button if there is only one row returned.
        $vEnableEdit = $SOT[CanUserChangeCI];
        $HistoryLink = Get_Nav_Target($SOT);

	} else {
		$DOC .= PrintHorizontalTable2($SOT);
		$vHead  .= CreateHtmlHeading("$vTitle - (found $vRowCount)", $SOT[TabType]."_report");

        # Disable the edit button and history button.
        $vEnableEdit = ($OneRow and $SOT[CanUserChangeCI]);
        $HistoryLink = $vEnableEdit?Get_Nav_Target($SOT):"";
	}

    # Create the CSV file and field descriptions links.
    $vHead .= Draw_Top_Nav_Links($SOT, $HistoryLink, "", TRUE, $vEnableEdit, $vEnableEdit);

	# Create the bottom CSV file link.
	if($vRowCount > 10) $DOC .= Draw_Top_Nav_Links($SOT, "", "", TRUE, FALSE, FALSE);
	$DOC .= "\n</div>\n";

    # Add a home button.
	$DOC .= "<button onclick=\"javascript:window.location='".SOT_HOME_PAGE."start'\" default>OK</button>";

	return $vHead.$DOC.$SOT[DatabaseObject]->Show_DB_Connection_Details();
}

# Print CI data in a horizontally formatted table.
function PrintHorizontalTable2(&$SOT){

    # Print the table's header.
    $DOC = "<div class=\"table\"><table class=\"inline\">\n";

    # Set the first row as a table headder.
    $vCelTyp = "th";

    # Create each row.
    foreach($SOT[FoundArray] as $CiRecord){

        array_walk($CiRecord,'Strip_Tags_Callback');

        # Add a link to the server/user/group name to list the history in reverse date
        # order making the most recent record at the top.
        if($CiRecord['name']  != "name"){
            $CiRecord['name'] = "<a href=\"".SOT_CI_HISTORY_FORM.Get_Nav_Target($SOT, $CiRecord['id'])."\">"
                               ."{$CiRecord['name']}";
        }

        # Remove the id field from the printed table.
        unset($CiRecord['id']);

        # Print the data row.
        $DOC .= "<tr class=\"row\">\n";
        $DOC .= "<$vCelTyp>".implode("</$vCelTyp><$vCelTyp>",$CiRecord)."</$vCelTyp>\n";
        $DOC .= "</tr>\n";

        # Set all the other rows as normal non headder rows.
        $vCelTyp = "td";
    }

    # Close the table.
    $DOC .= "</table></div>\n";
    return $DOC;
} //PrintHorizontalTable2

# Print CI data in a vertically formatted table.
function PrintVerticalTable(&$SOT){

	# Print the table's header.
	$DOC  = "<div class=\"table\"><table class=\"inline\">\n";

	$SOT[CI_ID]  = $SOT[FoundArray][1]['id'];
    $SOT[CiName] = $SOT[FoundArray][1]['name'];

    # Copy the array because we don't want to change the actual data.
    $FoundArray = $SOT[FoundArray];
    array_walk($FoundArray[1], 'Strip_Tags_Callback');
	$FoundArray[1]['name'] = "<a href=\"".SOT_CI_HISTORY_FORM.Get_Nav_Target($SOT)."\">{$SOT[CiName]}";

	foreach($FoundArray[0] as $Field => $Value){

        # Don't print the id field.
        if(!$SOT[FieldMetaArray][$Field]["hide"]){

            # Print the field row.
			$DOC .= "<tr class=\"row\">";
			$DOC .= "<th>".$FoundArray[0][$Field]."</th>";
			$DOC .= "<td>".$FoundArray[1][$Field]."</td>";
			$DOC .= "</tr>\n";
		}
	}

	# Close the table.
	$DOC .= "</table></div>";
	return $DOC;
} //PrintVerticalTable

