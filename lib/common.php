<?php

/**
 * Common functions for the SoT Web Portal.
 * Load from anywhere with "include_once SOT_LIB.'common.php';"
 *
 * @author  Doug Burner <doug869@users.noreply.github.com> 2014-01-18
 *
 */

if(!defined('DOKU_INC')) die();

# Hack for IE8.
$GLOBALS['JComboBoxscript'] = <<<EOS
<script type="text/javascript">
function combo(thelist, theinput){
  theinput = document.getElementById(theinput);
  theinput.value = thelist.options[thelist.selectedIndex].innerHTML;
  thelist.value = "";
}
</script>
EOS;

function Load_IE8_Hack(){
    return strpos(" ".$_SERVER['HTTP_USER_AGENT'], 'MSIE 8') ? $GLOBALS['JComboBoxscript'] : "";
}

/**
 * Some functions to assist with debugging.
 */
function sotprintr($text){return Display_Code_Box(print_r($text,TRUE));}
function sotecho($text){return Display_Code_Box($text."<BR>\n");}

/**
 * Return the CI Owner by finding the value of the field with 'ci_owner' metadata set to true.
 * Default to 'os_support' if not found.
 */
function Get_CI_Owner(&$SOT){

    # Scan each field of the metadata array.
    foreach($SOT[FieldMetaArray] as $Field => $Metadata){
        if($Metadata['ci_owner']){return $Field;}
    }

    # Not found, return a default.
    return 'os_support';
}

/**
 * Return TRUE if the current user make any changes to this CI.
 * Check if the user is in the admin, author, os_support or apps group.
 */
function Can_User_Change_CI(&$SOT){
    $CanChange = (Is_Super_User() or Is_In_CI_Owner_Group($SOT));
    //echo Is_In_Group($SOT[CurrentCiArray][Get_CI_Owner($SOT)])."xx<BR>";
    //echo sotecho(Get_CI_Owner($SOT));
    //echo sotecho($SOT[CurrentCiArray][os_support]);
    //echo sotprintr($SOT[CurrentCiArray]);

    if(!$CanChange){

        # Scan each field of the metadata array.
        foreach($SOT[FieldMetaArray] as $Field => $Metadata){;
            if(!is_null($Metadata[appgroup]) and Is_In_Group($Metadata[appgroup])){
                return TRUE;
            }
        }
    }

    return $CanChange;
}

/**
 * Return TRUE if the current user is in the CI Owner
 */
function Is_In_CI_Owner_Group(&$SOT){
    return Is_In_Group($SOT[CurrentCiArray][Get_CI_Owner($SOT)]);
}

/**
 * Return link params with TabType, CI_ID and CiName. Used to identify CIs
 * when creating links.
 * @param   array   $SOT                The great $SOT array.
 * @param   string  $CI_ID_Override     Override $SOT[CI_ID]
 * @param   string  $CiName_Override    Override $SOT[CiName]
 * @return  string
 */
function Get_Nav_Target(&$SOT, $CI_ID_Override=""){
    $CI_ID=$CI_ID_Override?$CI_ID_Override:$SOT[CI_ID];
    //$CiName=$CiName_Override?$CiName_Override:$SOT[CiName];

    return "&tabtype={$SOT[TabType]}&ci_id={$CI_ID}";
}

/**
 * Return TRUE if the current user is a member of the passed group.
 *
 * @param   string  The name of the group.
 * @return  bool    TRUE the current user belongs the passed group.
 */
function Is_In_Group($pGroup){
    global $INFO;

    if(SOT_DEBUG) {
        echo "Is_In_Group($pGroup) - ";
        echo in_array(strtolower($pGroup), $INFO['userinfo']['grps'])?" YES":" NO"."<BR>";
    }
    return in_array(strtolower($pGroup), $INFO['userinfo']['grps']);
} //Is_In_Group

/**
 * Return TRUE if the current user is a super user. A super user is
 * someone in the admin or author groups.
 *
 * @return  bool    TRUE the current user belongs to a super user group.
 */
function Is_Super_User(){

    $SU_Array = explode(",", $GLOBALS['sot_super_users']);
    foreach($SU_Array as $SuperUser){
        if(Is_In_Group(trim($SuperUser))) return TRUE;
    }
    return FALSE;
}

/**
 * Return TRUE if the current user is a member of the passed apps group.
 *
 * @param   string  $Apps_Group_Name    The name of the group.
 * @return  bool    TRUE the current user belongs the passed group.
 */
function Is_In_Apps_Group($Apps_Group_Name="apps"){
    return Is_In_Group($Apps_Group_Name);
}

/**
 * Return GMT or LMT timestamp in the following format:
 * 'Y-m-d H:i:s'
 */
function Get_Time_Stamp(){
    if($GLOBALS['sot_use_lmt']){
        return date(SOT_DATE_FORMAT);
    } else {
        return gmdate(SOT_DATE_FORMAT);
    }
}

/**
 * Return an HTML encoded navigation button.
 *
 * @param   string  $ButtonTitle    The text to display on the button.
 * @param   string  $NavTarget      The internal wiki target page.
 * @return  string  An HTML encoded navigation button.
 */
function Draw_Nav_Button($ButtonTitle, $NavTarget){
    return   "<input type=\"button\" value=\"{$ButtonTitle}\" onclick=\"javascript:window.location='{$NavTarget}'\">";
} //Draw_Nav_Button

/**
 * Return an HTML encoded table listing all the current user's groups.
 *
 * @return  string  An HTML encoded string.
 */
function PrintUsersGroups(){
	global $INFO;
	$DOC	 = "<div class=\"table\">\n";
	$DOC .= "<table class=\"inline\">\n";
	$DOC .= "<tr><th colspan=\"99\">Your groups:</th>\n";
	$DOC .= "<td>".implode("</td><td>",$INFO['userinfo']['grps'])."</td></tr>\n";
	$DOC .= "</table></div>\n";
	return $DOC;

} //PrintUsersGroups

/**
 * Get the passed CI from the SoT and display in a horizontal table.
 *
 * @param   object  $Sot_Database   Sot_Database class object.
 * @param   string  $pCiName        The name of the CI.
 * @param   string  $pTabType       Can be server, user or group.
 * @return  string  An HTML encoded table
 *
 */
function DisplayThisCI($Sot_Database, $pCiName, $pTabType){
	include_once SOT_LIB.'form_report.php';
    $vTableLines = $Sot_Database->Get_CI_Records_From_Array($pTabType, array('name' => $pCiName));
	$DOC .= PrintHorizontalTable2($vTableLines, $pTabType);
	return $DOC;

} //DisplayThisCI

function DisplayThisCI2(&$SOT){
    include_once SOT_LIB.'form_report.php';
    $SOT[FoundArray] = $SOT[DatabaseObject]->Get_CI_Records_From_Array($SOT[TabType], array('name' => $SOT[CiName]));
    $DOC .= PrintHorizontalTable2($SOT);
    return $DOC;

} //DisplayThisCI

# Query the database for the history records.
# Only display the action logs to users in the admin or author groups.
function DisplayCiHistory(&$SOT){
    #include_once SOT_LIB.'form_report.php';
    $HistoryArray = $SOT[DatabaseObject]->Get_CI_History($SOT[CiName], $SOT[TabType], Is_Super_User(), TRUE);
    $DOC .= PrintStandardTable2($HistoryArray);
    return $DOC;
}

/**
 * Return an HTML encoded string containing the passed text in a message box of
 * the passed style. The available styles are error, info, success and notify.
 * Note that infoutils.php supplies the msg() function however it gives little
 * control on how the message is printed.
 *
 * @param   string  $DisplayText    Text to display in the box.
 * @param   string  $MessageType    The style of message box.
 * @return  string  An HTML encoded string.
 */
function Display_Message_Box($DisplayText, $MessageType="info"){
    $DOC = "<div class=\"{$MessageType}\">{$DisplayText}</div>";
    return $DOC;
}

/**
 * Return an HTML encoded string containing the passed text in an appropriatly
 * styled message box.
 *
 * @param   string  $DisplayText    Text to display in the box.
 * @return  string  An HTML encoded string.
 */
function Display_Error_Box($DisplayText){return Display_Message_Box($DisplayText, "error");}
function Display_Info_Box($DisplayText){return Display_Message_Box($DisplayText, "info");}
function Display_Success_Box($DisplayText){return Display_Message_Box($DisplayText, "success");}
function Display_Notify_Box($DisplayText){return Display_Message_Box($DisplayText, "notify");}

/**
 * Return an HTML encoded string containing the passed text in a code box.
 *
 * @param   string  $DisplayText    Text to display in the box.
 * @return  string  An HTML encoded string.
 */
function Display_Code_Box($DisplayText){
	$DOC  = "<div class=\"level3\"><pre class=\"code\">";
	$DOC .= $DisplayText;
	$DOC .= "</div>\n";
	return $DOC;
} //Display_Code_Box

/**
 * Return the background colour style
 */
function Get_Background_Style($Style="error"){
    $BgColors = array("error" => "#fcc", "info" => "#ccf", "success" => "#cfc", "notify" => "#ffc");
    return is_null($BgColors[$Style])?"":" style=\"background-color:{$BgColors[$Style]};\"";
}

/**
 * Callback for array_walk to trip HTML tags and then to replace cr with <BR>.
 */
function Strip_Tags_Callback(&$Text){
    $Text = nl2br(strip_tags($Text));
}

/**
 * Return an HTML encoded horizontally formatted table from the passed table array.
 *
 * @param   array   $pTableArray    An array of assoc arrays
 * @return  string  An HTML encoded table.
 */
function PrintStandardTable2($pTableArray){

	# Print the table's header.
	$DOC  = "<div class=\"table\"><table class=\"inline\">\n";

	# For each row.
	for($vFieldIndex = 0; $vFieldIndex < count($pTableArray) ; $vFieldIndex++){

        array_walk($pTableArray[$vFieldIndex],'Strip_Tags_Callback');

		# Set the table's first row as the header.
		$vCelTyp = $vFieldIndex == 0 ? "th" : "td";

		# Print the data row.
		$DOC .= "<tr class=\"row".$vFieldIndex."\">";
		$DOC .= "<{$vCelTyp}>"
				.implode("</$vCelTyp><$vCelTyp>",$pTableArray[$vFieldIndex])
				."</{$vCelTyp}>";
		$DOC .= "</tr>\n";
	}

	# Close the table.
	$DOC .= "</table></div>\n";

	# Return format: An HTML encoded string.
	return $DOC;

} //PrintStandardTable2

/**
 * Return the passed text as an HTML encoded H2 heading string.
 *
 * @param   string  $DisplayText    Text to display in the heading.
 * @param   string  $HtmlID         The unique HTML section ID.
 * @param   string  $AddL2Div       Append a new L2 div section if true.
 * @return  string  An HTML encoded string.
 */
function CreateHtmlHeading($DisplayText, $HtmlID = "search", $AddL2Div=TRUE){
	$vHead  = "<h2 class=\"sectionedit1\" id=\"sot_{$HtmlID}\">";
	$vHead .= $DisplayText;
	$vHead .= "</h2>\n";
	if($AddL2Div) $vHead .= "<div class=\"level2\">\n";
	return $vHead;
}

/**
 * Create a temporary CSV file containing the passed table array and return the file name
 * in relation to the wiki home directory, which defaults to {wiki-home}/data/temp/. Old
 * temp files in that directory that are more than two days old are first deleted.
 *
 * @param   array   $pTableArray    An array of assoc arrays to be converted into the CSV file.
 * @return  string  The file name of the temporary CSV file in relation to the wiki home directory.
 */
function Create_CSV_File_From_Array($TableArray){

    $FilePath   = "data/tmp";
    $FilePrefix = "sot_report.";

    # Remove report files that are 2+ days old.

    shell_exec("find {$FilePath}/{$FilePrefix}* -mtime +{$GLOBALS['sot_max_csv_cache_age']} -exec rm -f {} \;");

    # Ensure the temp file name is unique.
    $TempFileName = tempnam($FilePath, $FilePrefix);

    # Put each record including the header into the temp file in CSV format.
    $fh = fopen($TempFileName, "w");
    foreach ($TableArray as $CiRecordArray) {

        # Don't show the record id field in the CSV file.
        # **TODO** use metadata.
        unset($CiRecordArray['id']);
        fputcsv($fh, $CiRecordArray);
    }
    fclose($fh);

    return $TempFileName;
}

# Create a CSV File button, a Field Description button and a Naming Convention button
# using links inside table cells (they are not real buttons).

/**
 * Draw navigation links across the top of the page.
 *
 * @param   array   $TableArray     An array of assoc arrays to be converted into the CSV file.
 * @param   string  $HistoryTarget  Target for the History link.
 * @param   string  $DetailsTarget  Target for the Details link.
 * @param   bool    $DrawCSV        Draw a CSV link.
 * @param   bool    $DrawEdit       Draw an edit link.
 * @param   bool    $DrawAddComment Draw a Comment link.
 * @return  string  The file name of the temporary CSV file in relation to the wiki home directory.
 */
function Draw_Top_Nav_Links(&$SOT,
                            $HistoryTarget="",
                            $DetailsTarget="",
                            $DrawCSV=TRUE,
                            $DrawEdit=TRUE,
                            $DrawAddComment=FALSE){
    global $INPUT;

    $RowCount = count($SOT[FoundArray]) - 1;
    $TD = "td";
    $FontDisabled = "font color=\"#ccc\"";

    //$DOC  = "<table class=\"inline\"><tr>";
    $DOC  = "<table style=\"border:0px\" ><tr>";

    # Home
    $DOC .= "<{$TD}><u><a href=\"".SOT_HOME_PAGE."start\">";
    $DOC .= "Home";
    $DOC .= "</a></u></{$TD}>\n";

    # Naming Convention
    /*
    $DOC .= "<{$TD}><u><a href=\"".SOT_FETCH_MEDIA."add2013_1477273_dibp_itias_server_naming_convention.docx\" ";
    $DOC .= "title=\"add2013_1477273_dibp_itias_server_naming_convention.docx (1.2 MB)\">";
    $DOC .= "Naming Convention";
    $DOC .= "</a></u></{$TD}>\n";
    */

    # Field Descriptions. Only show on report pages without a top tab bar.
    $DOC .= "<{$TD}><u><a href=\"".SOT_TABLE_FIELDS_FORM."&tabtype={$SOT[TabType]}\" >";  #target=\"_blank\"
    $DOC .= ucfirst($SOT[TabType])." Field Descriptions";
    $DOC .= "</a></u></{$TD}>\n";

    # Download CSV
    $LinkName = "Download CSV File";
    if($DrawCSV != ""){

        # Save the returned records to a CSV file that can be downloaded by the end user.
        $Temp_File_Name = Create_CSV_File_From_Array($SOT[FoundArray]);

        $DOC .= "<{$TD}><u><a href=\"lib/plugins/".PLUGIN_NAME
                    .SOT_DOWNLOAD_FORM."downloadname=sot_{$SOT[TabType]}_report.csv"
                    ."&tempfilename={$Temp_File_Name}\">";
        $DOC .= $LinkName;
        $DOC .= "</a></u></{$TD}>\n";
    } else {
        $DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # **TODO** Show both CI details and CI History links.
    # CI History.
    $LinkName = ucfirst($SOT[TabType])." History";
    if($HistoryTarget != ""){
        $DOC .= "<{$TD}><u><a href=\"".SOT_CI_HISTORY_FORM."{$HistoryTarget}\">";
        $DOC .= $LinkName;
        $DOC .= "</a></u></{$TD}>\n";
    } else {
        //$DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # CI Details.
    $LinkName = ucfirst($SOT[TabType])." Details";
    if($DetailsTarget != ""){
        $DOC .= "<{$TD}><u><a href=\"".SOT_REPORT_CI_FORM."{$DetailsTarget}\">";
        $DOC .= $LinkName;
        $DOC .= "</a></u></{$TD}>\n";
    } else {
        //$DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # If neither history or detail links were shown, show a deactivated link.
    if($HistoryTarget == "" and $DetailsTarget == ""){
        $DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # Edit CI if there is only one record.
    $LinkName = "Edit Record";
    if($DrawEdit){
        $DOC .= "<{$TD}><u><a href=\"".SOT_MODIFY_CI_FORM.Get_Nav_Target($SOT)."\">";
        $DOC .= $LinkName;
        $DOC .= "</a></u></{$TD}>\n";
    } else {
        $DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # Add a Comment.
    $LinkName = "Add a Comment";
    if($DrawAddComment){
        $DOC .= "<{$TD}><u><a href=\"".SOT_MODIFY_CI_FORM.Get_Nav_Target($SOT)."&editmode=comment\">";
        $DOC .= $LinkName;
        $DOC .= "</a></u></{$TD}>\n";
    } else {
        $DOC .= "<{$TD}><$FontDisabled>$LinkName</font></{$TD}>\n";
    }

    # Close the table
    $DOC .= "</tr></table>\n";
    return $DOC;
}

# Enum list box. Enter available options and set passed data, if not,
# set a default value if available.
function DrawOptionInput($FieldName, &$TableFields, $pBlankDefaults, $CurrentFieldValue, $DisabledStyle=""){

    $DOC  = "<select name=\"{$FieldName}\" {$DisabledStyle} style=\"width: 280px;\">";

    # Massage and explode the enum members into selectable options.
    $EnumString = str_replace(array("enum(",")","'"), "", $TableFields["column_type"]);
    if($pBlankDefaults) $EnumString = str_replace(",,", ",", ",".$EnumString);
    $vOptions = explode(",", $EnumString);

    # Add the options and select the passed value or the default value.
    foreach($vOptions as $vOption){
        $DOC .= "<option ";
        if($vOption == $CurrentFieldValue){
            $DOC .= "selected ";
        }
        $DOC .= "value=$vOption>$vOption ";
        $DOC .= "</option>\n";
    }
    $DOC .= "</select>";
    return $DOC;
}

# List box. Fill out the list then enter passed value, if not,
# enter a default value if available.
# Need to use a java hack for IE 8.
function DrawListInput(&$SOT, $FieldName, $CurrentFieldValue, $DisabledStyle=""){
    $OtherValuesArray = $SOT[DatabaseObject]->Get_Distinct_Values($SOT[TabType], $FieldName);

    if(strpos(" ".$_SERVER['HTTP_USER_AGENT'], 'MSIE 8')){
        $DOC .= "<input type=\"text\" id=\"{$FieldName}\" name=\"{$FieldName}\" style=\"width: 280px;\" value=\"{$CurrentFieldValue}\" /><BR>\n";
        $DOC .= "<select name=\"{$FieldName}list\" style=\"width: 280px;\" onChange=\"combo(this, '{$FieldName}')\" >\n";
        $DOC .= "<option></option>\n";
        foreach($OtherValuesArray as $Value){
            if(trim($Value[$FieldName]) != "")$DOC .= "<option>{$Value[$FieldName]}</option>\n";
        }
        $DOC .= "</select>\n";
    } else {
        $DOC .= "<input type=\"text\" id=\"{$FieldName}\" name=\"{$FieldName}\" list=\"{$FieldName}list\" style=\"width: 280px;\" value=\"{$CurrentFieldValue}\" /><BR>\n";
        $DOC .= "<datalist id=\"{$FieldName}list\" >\n";
        foreach($OtherValuesArray as $Value){

            $DOC .= "<option value=\"{$Value[$FieldName]}\">\n";
        }
        $DOC .= "</datalist>\n";
    }

    return $DOC;
}

# Large text box. Enter passed value, if not, enter a default value if available.
function DrawTextAreaInput($FieldName, $CurrentFieldValue, $DisabledStyle=""){
    $DOC  = "<textarea rows=\"4\" style=\"width: 280px;\" name=\"{$FieldName}\" {$DisabledStyle} >";
    $DOC .= $CurrentFieldValue."</textarea>";
    return $DOC;
}

# Small text box. Enter passed value, if not, enter a default value if available.
function DrawTextInput($FieldName, $CurrentFieldValue, $DisabledStyle=""){
    $DOC  = "<input type=\"text\" style=\"width: 280px;\" name=\"{$FieldName}\" {$DisabledStyle} ";
    $DOC .= "value=\"".$CurrentFieldValue."\">";
    return $DOC;
}

# Blank fields with the 'enter_once' metadata token to ensure these fields cannot be modified.
function Clear_EnterOnce_Fields(&$SOT, $ClearToken){
    global $INPUT;

    foreach($SOT[TableFieldsArray] as $TableFields){
        $FieldName = $TableFields["column_name"];
        if($SOT[FieldMetaArray][$FieldName]["$ClearToken"]){$INPUT->set($FieldName, "");}
    }
}

