<?php

/**
 * Functions to dynamically generate a modify CI user input form based on table
 * field data and functions to process the input.
 *
 * Load from anywhere with "include_once SOT_LIB.'form_edit.php';"
 *
 * @author  Doug Burner <doug869@users.noreply.github.com> 2013-12-18
 *
 */

if(!defined('DOKU_INC')) die();

# Import common functions.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'database.php';

/**
 * The edit CI form entry point. Attempt to modify the CI from form data
 *  if this is a submit. Generate a user input form regardless of action mode.
 *
 */
function Sot_Modify_CI(){
    global $INPUT;

    $DOC = "";
    $PageHeading = "";
    $InputFormHead = "";
    $vSuccess = FALSE;

    $SOT[CI_ID]             = $INPUT->str('ci_id', "0");
    $SOT[TabType]           = strtolower($INPUT->str('tabtype', "server"));
    //$SOT[CiName]            = $INPUT->str('name');
    $SOT[DatabaseObject]    = new Sot_Database;
    $SOT[FieldMetaArray]    = $SOT[DatabaseObject]->Get_Metadata_Array($SOT[TabType]."s");
    $SOT[TableFieldsArray]  = $SOT[DatabaseObject]->Get_Table_Field_Details($SOT[TabType]."s");
    $SOT[HilightArray]      = array();
    $SOT[FoundArray]        = array();

    # Get the CI's current data without the headings and de-arrayifycate (lol)
    # it by one level so we are left with a 1D assoc array.
    list($SOT[CurrentCiArray])  = $SOT[DatabaseObject]->Get_CI_Records_From_Array(
                                  $SOT[TabType], array('id' => $SOT[CI_ID]), TRUE);
    $SOT[CanUserChangeCI]   = Can_User_Change_CI($SOT);
    $SOT[CiName] = $SOT[CurrentCiArray][name];

    # Check if this is a submit.
    if($INPUT->str('action') == "submit" and $SOT[CanUserChangeCI]) {
        if(!checkSecurityToken()) return "";

        # Attempt to modify the CI if name and os_support fields have been passed and validated.
        $ModResults .= Modify_CI($SOT, $vSuccess);

        if($vSuccess){

            if($INPUT->str('editmode') == "comment"){

                # Display the new CI as listed in the SoT and display an OK button.
                $DOC .= CreateHtmlHeading("Comment added to {$SOT[CiName]}'s history", $SOT[TabType]."_modified");
                $DOC .= Draw_Top_Nav_Links($SOT,"",Get_Nav_Target($SOT),FALSE,$SOT[CanUserChangeCI],$SOT[CanUserChangeCI]);
                $DOC .= $ModResults;
                $DOC .= DisplayCiHistory($SOT);
                $InputFormHead = "Add another comment to '{$SOT[CiName]}'";
            } else {

                # Display the CI as listed in the SoT and display an OK button.
                $DOC .= CreateHtmlHeading(ucfirst($SOT[CiName])."'s record has been updated", $SOT[TabType]."_modified");
                $DOC .= Draw_Top_Nav_Links($SOT,Get_Nav_Target($SOT),"",FALSE,$SOT[CanUserChangeCI],$SOT[CanUserChangeCI]);
                $DOC .= $ModResults;
                $DOC .= DisplayThisCI2($SOT);
                $InputFormHead = "Keep Editing {$SOT[CiName]}";
            }

            # Add a "Done" button to take the user back to the history page.
            $DOC .= "<DIV>";
            $DOC .= Draw_Nav_Button("Finished", SOT_CI_HISTORY_FORM."&tabtype={$SOT[TabType]}&ci_id={$SOT[CI_ID]}");
            $DOC .= "</DIV><BR>";
            $DOC .= CreateHtmlHeading($InputFormHead, $SOT[TabType]."_edit", FALSE);

            # Blank the comment forcing the user to add a new comment if they update again.
            Clear_EnterOnce_Fields($SOT, "edit_once");

        } else {

            # Create the failed page heading.
            $DOC .= CreateHtmlHeading("Error Updating the SoT", $SOT[TabType]."_error");
            $DOC .= Draw_Top_Nav_Links($SOT,"","",FALSE,FALSE,FALSE).$PageHeading;
            $DOC .= $ModResults;
            $DOC .= CreateHtmlHeading("Try Again", $SOT[TabType]."_edit", FALSE);
        }
    } else {
        $DOC .= CreateHtmlHeading("Edit CI {$SOT[CiName]}", $SOT[TabType]."_edit", FALSE);
        $DOC .= Draw_Top_Nav_Links($SOT,"","",FALSE,FALSE,FALSE).$PageHeading;
    }

    # Regardless of the action mode, set up and print the CI modification input form
    # entering any passed form data. Create the page heading.
    //$DOC .= CreateHtmlHeading($InputFormHead, $SOT[TabType]."_edit", FALSE);

    # Print a small table lising the user's groups.
    $DOC .= PrintUsersGroups();

    # Print the user input form.
    $DOC .= PrintEditForm($SOT, "Submit");

    return $DOC.$SOT[DatabaseObject]->Show_DB_Connection_Details();
} //Sot_Modify_CI

/**
 * Attempt to update the CI and return an HTML encoded result listing.
 *
 * @param   object  $Sot_Database       Sot_Database class object.
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $CurrentCiArray     An assoc array of current CI data.
 * @param   string  $pTabType           Can be server, user or group.
 * @param   string  &$vHilight          An assoc array returning a listing of fields that need
 *                                      to be hilighted in the input form
 * @param   string  &$vSuccess          Returns TRUE if the CI was successfully modified.
 *
 * @return  string  HTML encoded error or success report.
 */
function Modify_CI(&$SOT, &$vSuccess){
    $DOC = "";
    $vRetCode = -1;
    $vResult = "";


    # Verify that name, and comments were passed.
    $RawResultsText = CheckPassedModFormParams($SOT);

    # Check for any errors found above.
    if(strlen($RawResultsText) > 0){

        # Display an error box with a red background.
        $DOC .= Display_Error_Box($RawResultsText);

    } else {

        # Match the fields with the passed values (if present) and assemble
        # the databse query parameters.
        $ParamArray = BuildCiModifyParamArray($SOT);

        # Print the command for debugging purposes.
        if(SOT_DEBUG){
            $DOC .= Display_Code_Box("{$SOT[CiName]}, {$SOT[TabType]}, ".print_r($ParamArray,true).",$vResult,$vRetCode");
        }

        # Attempt to execute the command.
        $RawResultsText = $SOT[DatabaseObject]->Modify_CI_Record($SOT[CI_ID], $SOT[CiName], $SOT[TabType], $ParamArray,$vResult,$vRetCode);

        if(SOT_DEBUG) $DOC .= Display_Code_Box("Return code: $RawResultsText - $vRetCode - $vResult");

        # Process the result.
        if($vRetCode != 0){

            # Display an error box with a red background.
            $DOC .= Display_Error_Box("Error modifying CI '{$SOT[CiName]}' in the SoT:<BR>$vResult");

        } else {

            # Success.
            $DOC .= Display_Success_Box($RawResultsText);
            $vSuccess = TRUE;
        }
    }
    return $DOC;

} //Modify_CI

/**
 * Verify that a CI name and comments were passed.
 *
 * @param   array   &$vHilight          An assoc boolean array containing fields that need highlighting.
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $CurrentCiArray     An assoc array of current CI data.
 * @return  string  Error text if any.
 *
 */
function CheckPassedModFormParams(&$SOT){
    global $INPUT;

    $vError = "";
    $CiOwner = Get_CI_Owner($SOT);

    # Just in case.
    if(!$SOT[CanUserChangeCI]) return "You do not have permissions to change this CI.";

    # Scan metadata for mandatory fields and user permissions.
    foreach($SOT[FieldMetaArray] as $Field => $MetaData){

        $PassedValue = $INPUT->str($Field, NULL);
        $NoMoreChecks = FALSE;

        # Check mandatatory fields are present and sufficient if they are changing.
        # If the field is manditory.
        if($MetaData[mandatory]){

            # If the field was passed.
            If(!is_null($PassedValue)){

                # If it is different to current or doesn't exist in current.
                if($PassedValue != $SOT[CurrentCiArray][$Field]){

                    # Check the passed value wasn't removed.
                    if(trim($PassedValue) == ""){
                        $vError .= "Error: {$Field} not set.<BR>\n";
                        $SOT[HilightArray][$Field] = "error";

                    # Check the passed value's length.
                    } elseif(strlen(trim($PassedValue)) < $MetaData[mandatory]){

                        $vError .= "Error: {$MetaData[mandatory_text]}<BR>\n";
                        $SOT[HilightArray][$Field] = "error";
                    }

                # Manditory and not in the current CI array (ie a comment).
                } elseif(is_null($SOT[CurrentCiArray][$Field])) {
                    $vError .= "Error: {$Field} not set.<BR>\n";
                    $SOT[HilightArray][$Field] = "error";
                }

            # Field was not passed and not in the current array, something went wrong.
            } elseif(is_null($SOT[CurrentCiArray][$Field])){
                $vError .= "Ooops! Only staff in the 'admin', 'author' and "
                        .  "'{$SOT[CurrentCiArray][$CiOwner]}' "
                        .  "groups can change this CI.<BR>\n";
                        $SOT[HilightArray][$Field] = "error";
            }
        }

        # If the field was passed.
        If(!is_null($PassedValue)){

            # If it is different to current or doesn't exist in current.
            if($PassedValue != $SOT[CurrentCiArray][$Field]){

                # Check that the field can be changed.
                if($MetaData[no_modify]){
                    $vError .= "Error: {$Field} cannot be changed.<BR>\n";
                    $SOT[HilightArray][$Field] = "error";
                }

                # If this is the CI Owner field.
                if($MetaData[ci_owner]){

                    # Check permissions.
                    if(!Is_Super_User() and !Is_In_Group($PassedValue)){
                        $vError .= "Error: only staff in the 'admin', 'author' or both "
                        .  "'{$PassedValue}' and '{$SOT[CurrentCiArray][$Field]}' {$Field} "
                        .  "groups can change the {$Field} field of this CI.<BR>\n";
                        $SOT[HilightArray][$Field] = "error";
                    }
                }

                # Check Numeric
                if($MetaData[numeric]){
                    if(trim($PassedValue) != "" and !is_numeric($PassedValue)){
                        $vError .= "Error: {$Field} must be numeric.<BR>\n";
                        $SOT[HilightArray][$Field] = "error";
                        $NoMoreChecks = TRUE;
                    }
                }

                # Check uniqueness.
                if(!$NoMoreChecks and $MetaData[uniqueness]){
                    $MaxNumber = $MetaData[uniqueness];
                    $CurrentCount = $SOT[DatabaseObject]->Count_CI_Records_With_Field_Value($SOT[TabType], $Field, $PassedValue);
                    if(trim($PassedValue) != "" and $CurrentCount >= $MaxNumber){
                        $vError .= "Error: {$MetaData[unique_text]}<BR>\n";
                        $SOT[HilightArray][$Field] = "error";
                    }
                }
            }
        }
    }

    return $vError;

} //CheckPassedModFormParams

/**
 * Match the passed table fields with the values input via the form
 * if present and assemble and return a CI update parameter array. Append a
 * loginID parameter as well.
 *
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $CurrentCiArray     An assoc array of current CI data.
 *
 * @return  string  HTML encoded error or success report.
 */
function BuildCiModifyParamArray(&$SOT){
    global $INPUT;
    global $INFO;

    # Iterate through each field in the table.
    foreach($SOT[TableFieldsArray] as $FieldArray){
        $FieldName = $FieldArray['column_name'];

        # Get the current value and new value passed to the form.
        $CrntFieldValue = $SOT[CurrentCiArray][$FieldName];
        $NewFieldValue = $INPUT->str($FieldName,NULL);
        if(SOT_DEBUG) echo "$FieldName to '$NewFieldValue', currently '$CrntFieldValue'<BR>";

        # Only update field values that are changing from the CI's currentfield values.
        if( !is_null($NewFieldValue)
        and !$SOT[FieldMetaArray][$FieldName]['hide']
        and $NewFieldValue != $CrntFieldValue){

            # Add the key and new value to the parameter array.
            $vParamArray[$FieldName] = $NewFieldValue;
            if(SOT_DEBUG) echo "**Update $FieldName => '$NewFieldValue'<BR>";
        }
    }

    # Append the user's login ID to the param array.
    $vParamArray['login_id'] = $INFO['client'];

    # Return format: An asso arrays of key=>value pairs
    return $vParamArray;

} //BuildCiModifyParamArray

/**
 * Set up and print the CI data editing form, entering any passed form data or current CI data.
 *
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $CurrentCiArray     An assoc array of current CI data.
 * @param   string  $pTabType           Can be server, user or group.
 * @param   string  $pHilight           An assoc array containing a listing of fields that need
 *                                      to be hilighted in the input form
 * @param   string  $pBtnName           Submit button text/label.
 *
 * @return  string  HTML encoded error or success report.
 */
function PrintEditForm(&$SOT, $pBtnName="Submit"){
    global $INPUT;

    # Setup the form.
    $DOC  = "<form name=\"inputform\" method=\"POST\" action=\"".SOT_HOME_PAGE.$INPUT->str('id')."&action=submit"
           ."&tabtype={$SOT[TabType]}&ci_id={$SOT[CI_ID]}&editmode={$INPUT->str('editmode')}\">\n";
    $DOC .= "<input type=\"hidden\" name=\"sectok\" value=\"".getSecurityToken()."\" />";
    $DOC .= Load_IE8_Hack();
    $DOC .= "<div class=\"table\">\n";
    $DOC .= "<table class=\"inline\">\n";

    # Print the topic line of the table.
    # Print the topic line of the table.
    $DOC .= "<tr><th>Field</th><th>Value</th><th>Description</th></tr>\n";

    # Determine if the current user can modify this CI.
    $IsInOsSupport = (Is_In_CI_Owner_Group($SOT) or Is_Super_User());

    # Only show the comment input field if the user only want to add a comment.
    $Comment_Only = $INPUT->str('editmode') == "comment";

    # For each field in the table.
    foreach($SOT[TableFieldsArray] as $TableFields){

        $FieldName = $TableFields["column_name"];
        if(!$SOT[FieldMetaArray][$FieldName]['hide']){

            # Disable the name field and display it that way regardless.
            if($SOT[FieldMetaArray][$FieldName]['no_modify']){
                $DisabledStyle = "readonly style=\"color:#888;\"";

                # Safeguard to stop the user from changing the field by URL _GET[].
                $INPUT->set($FieldName, $SOT[CurrentCiArray][$FieldName]);

            # User only wants to add a comment.
            } elseif($Comment_Only and !in_array("comment", explode(" ", $SOT[FieldMetaArray][$FieldName]['displaymode']))) {
                continue;

            # Don't display this field if this user can't modify it.
            } elseif(!$IsInOsSupport and !Is_In_Apps_Group($SOT[FieldMetaArray][$FieldName]['appgroup'])) {
                continue;

            # Display this field.
            } else {
                $DisabledStyle = "";
            }

            # Hilight the table row if it is in the hilight list.
            $vBackground = $SOT[HilightArray][$FieldName] ? " style=\"background-color:#FCC;\"" : "";

            # Start building the table row.
            $DOC .= "<tr {$vBackground}><th>{$FieldName}</th><td>";

            # Get the CI's current field value.
            $CurrentFieldValue = $INPUT->str($FieldName, $SOT[CurrentCiArray][$FieldName]);

            # Determine if this field should have a text box or a list box.
            if($SOT[FieldMetaArray][$FieldName]["input"] == "enum"){

                # Enum list box. Enter available options and set passed data, if not,
                # set a default value if available.
                $DOC .= DrawOptionInput($FieldName, $TableFields, FALSE, $CurrentFieldValue, $DisabledStyle);

            } else if($SOT[FieldMetaArray][$FieldName]["input"] == "list"){

                # List box. Fill out the list then enter passed value, if not,
                # enter a default value if available.
                $DOC .= DrawListInput($SOT, $FieldName, $CurrentFieldValue, $DisabledStyle);

            } else if($SOT[FieldMetaArray][$FieldName]["input"] == "large"){

                # Large text box. Enter passed value, if not, enter a default value if available.
                $DOC .= DrawTextAreaInput($FieldName, $CurrentFieldValue, $DisabledStyle);

            } else {

                # Assume small text box. Enter passed value, if not, enter a default value if available.
                $DOC .= DrawTextInput($FieldName, $CurrentFieldValue, $DisabledStyle);
            }

            # Add the field description.
            $DOC .= "</td><td>{$SOT[FieldMetaArray][$FieldName]['comment']}</td>";
            $DOC .= "</tr>\n";
        }
    }

    # Complete the table.
    $DOC .= "</table>\n";
    $DOC .= "</div>\n";

    # Complete the form with a submit and cancel button. The cancel
    # button will take the user back to the CI's history page.

    if(!$SOT[CanUserChangeCI]){
        $DOC .= Display_Error_Box("You cannot make any changes to this CI.");
    } else {
        $DOC .= "<input type=\"submit\" value=\"$pBtnName\">\n";
    }
    $DOC .= Draw_Nav_Button("Cancel", SOT_CI_HISTORY_FORM."&tabtype={$SOT[TabType]}&ci_id={$SOT[CI_ID]}");
    $DOC .= "</form>\n";
    return $DOC;

} //PrintEditForm
