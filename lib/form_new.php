<?php

/**
 * Functions to dynamically generate a add CI user input form based on table
 * field data and functions to process the input.
 *
 * Load from anywhere with "include_once SOT_LIB.'form_new.php';"
 *
 * @author  Doug Burner <doug869@users.noreply.github.com> 2013-12-18
 *
 */

if(!defined('DOKU_INC')) die();

# Import common functions.
include_once SOT_LIB.'common.php';
include_once SOT_LIB.'database.php';

/**
 * The Add CI entry form. Attempt to add CI from form data if this is
 * a submit. Generate a user input form regardless of the action mode.
 *
 */
function Sot_Add_New_CI(){
	global $INPUT;

	$DOC = "";
	$vHeading = "";
	$vFormHeading = "Add a New CI";
	$vSuccess = FALSE;

	# Get table fields array. The format of the returned lines:
    # The table type defaults to "server".
    $SOT[CI_ID]             = $INPUT->str('ci_id', "0");
    $SOT[TabType]           = strtolower($INPUT->str('tabtype', "server"));
    $SOT[CiName]            = $INPUT->str('name');
    $SOT[DatabaseObject]    = new Sot_Database;
    $SOT[TableFieldsArray]  = $SOT[DatabaseObject]->Get_Table_Field_Details($SOT[TabType]."s");
    $SOT[FieldMetaArray]    = $SOT[DatabaseObject]->Get_Metadata_Array($SOT[TabType]."s");
    $SOT[HilightArray]      = array();

	# Check if this is a submit.
	if($INPUT->str('action') == "submit") {
        if(!checkSecurityToken()) return "";

        # Attempt to insert the new CI if name and os_support fields have been passed and validated.
        $DOC .= Insert_New_CI($SOT, $vSuccess);

        if($vSuccess){

            # Display the new CI as listed in the SoT and display an OK button.
            $DOC .= DisplayThisCI2($SOT);

            # Create the success page heading.
			$vHeading .= CreateHtmlHeading("New CI Successfully Added to the SoT", $SOT[TabType]."_inserted");

            # Add a "Done" button to take the user back to the home page.
            $DOC .= "<DIV>";
            $DOC .= Draw_Nav_Button("Finished", SOT_HOME_PAGE."start");
            $DOC .= "</DIV><BR>";

			$vFormHeading = "Add another CI";

            # Blank fields with the 'enter_once' metadata token to ensure these fields cannot be modified.
            Clear_EnterOnce_Fields($SOT, "enter_once");

		} else {

            # Create the failed page heading.
			$vHeading .= CreateHtmlHeading("Error Adding the New CI", $SOT[TabType]."_error");
			$vFormHeading = "Try Again";
		}


	}

    $DOC    = $vHeading.$DOC;

	# Regardless of the action mode, set up and print the add CI input form
    # entering any passed form data. Create the page heading.
	$DOC   .= "<h2 class=\"sectionedit1\" id=\"sot_".$SOT[TabType]."_input\">"
				.$vFormHeading
				."</h2>\n";
    $DOC .= Draw_Top_Nav_Links($SOT,"","",FALSE,FALSE,FALSE);

    # Print a small table lising the user's groups.
	$DOC .= PrintUsersGroups();

    # Print the user input form.
	$DOC .= PrintInputForm($SOT, FALSE, "Submit");

	return $DOC.$SOT[DatabaseObject]->Show_DB_Connection_Details();

} //Sot_Add_New_CI

/**
 * Attempt to add a new CI and return an HTML encoded result listing.
 *
 * @param   object  $Sot_Database       Sot_Database class object.
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $pTabType           Can be server, user or group.
 * @param   string  &$vHilight          An assoc array returning a listing of fields that need
 *                                      to be hilighted in the input form
 * @param   string  &$vSuccess          Returns TRUE if the CI was successfully modified.
 *
 * @return  string  HTML encoded error or success report.
 */
function Insert_New_CI(&$SOT, &$vSuccess){
	global $INPUT;
	$DOC = "";
	$vRetCode = -1;
	$vResult = "";

	$CiName = $SOT[CiName];

	# Verify that name, os_support, comments were passed.
	$RawErrorText = CheckPassedInsertFormParams(&$SOT);

	# Check for any errors found above.
	if(strlen($RawErrorText) > 0){

		# Display an error box with a red background.
		$DOC .= Display_Error_Box($RawErrorText);

	} else {

		# Match the fields with the passed values (if present) and assemble
        # the databse query parameters.
		$ParamArray = BuildCiInsertParamArray($SOT[TableFieldsArray]);

		# Print the command for debugging purposes.
		if(SOT_DEBUG){$DOC .= Display_Code_Box("Insert command: ".print_r($ParamArray, TRUE)."<BR>");}

		# Attempt to execute the command.
		$RawResultsText = $SOT[DatabaseObject]->Insert_New_CI_Record($CiName, $SOT[TabType], $ParamArray, $vResult, $vRetCode);

		if(SOT_DEBUG) $DOC .= Display_Code_Box("Return code: $RawResultsText - $vRetCode - $vResult");

		# Process the result.
		if($vRetCode != 0){

            # Display an error box with a red background.
            $DOC .= Display_Error_Box("Error inserting CI '{$CiName}' in the SoT:<BR>{$vResult}");

		} else {

			# Success.
            $DOC .= Display_Success_Box($RawResultsText);

			$vSuccess = TRUE;
		}
	}
	return $DOC;

} //Insert_New_CI

/**
 * Match the passed table fields with the values input via the form
 * if present and assemble and return a CI update parameter array. Append a
 * loginID parameter as well.
 *
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 *
 * @return  string  HTML encoded error or success report.
 */
function BuildCiInsertParamArray($TableFieldArray){
	global $INPUT;
	global $INFO;

	# Iterate through each field in the table.
	foreach($TableFieldArray as $Values){
		$FieldName = $Values['column_name'];

		# Get the new form value. If the value wasn't passed
		# or is empty, replace it with a default if one exists.
		$NewFieldValue = $INPUT->str($FieldName, $Values['column_default']);

		# Only insert fields that are different from the default
		if($FieldName   != "id"
		and $FieldName  != "column_name"
		and $NewFieldValue != $Values['column_default']){

			# Add the key and new value to the parameter array.
			$vParamArray[$FieldName] = $NewFieldValue;
		}
	}

    # Append the user's login ID to the param array.
    $vParamArray['login_id'] = $INFO['client'];

	# Return format: An array of assoc arrays of key=>value pairs
	return $vParamArray;

} //BuildCiInsertParamArray

/**
 * Verify that a CI name, os_support, comments were passed.
 *
 * @param   array   &$vHilight  An assoc boolean array containing fields that need highlighting.
 * @return  string  Error text if any.
 *
 */
function CheckPassedInsertFormParams(&$SOT){
    global $INPUT;

    $vError = "";
    $vCanEnterCi = Is_Super_User();

    # Scan metadata for mandatory fields and user permissions.
    foreach($SOT[FieldMetaArray] as $Field => $MetaData){

        $PassedValue = $INPUT->str($Field, NULL);
        $NoMoreChecks = FALSE;

        # Check mandatatory fields are present and sufficient.
        if($MetaData[mandatory]){
            if(is_null($PassedValue) or trim($PassedValue == "")){
                $vError .= "Error: {$Field} not set.<BR>\n";
                $SOT[HilightArray][$Field] = "error";
                $NoMoreChecks = TRUE;
            } elseif(strlen(trim($PassedValue)) < $MetaData[mandatory]){
                $vError .= "Error: {$MetaData[mandatory_text]}<BR>\n";
                $SOT[HilightArray][$Field] = "error";
            }
        }

        # Check user permissions.
        if($MetaData[ci_owner]){
            $PassedCiOwner = $INPUT->str($Field, NULL);
            if(!Is_Super_User() and !Is_In_Group($PassedCiOwner)){
                $vError .= "Error: only staff in the 'admin', 'author' or '{$PassedCiOwner}' "
                          ."group can add this CI to the '{$PassedCiOwner}' {$Field} field.<BR>\n";
                $SOT[HilightArray][$Field] = "error";
            } else {
                $vCanEnterCi = TRUE;
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

                # Check if autosearch and find next available.
                if($MetaData['autosearch']){
                    $NewFieldValue = $SOT[DatabaseObject]->Get_Next_Available($SOT[TabType], $Field, $PassedValue);
                    $INPUT->set($Field, "$NewFieldValue");
                    $vError .= "Warning: {$MetaData[unique_text]} The next available {$Field} has been selected for you. Submit again to accept the new value.<BR>\n";
                    $SOT[HilightArray][$Field] = "notify";
                } else {
                    $vError .= "Error: {$MetaData[unique_text]}<BR>\n";
                    $SOT[HilightArray][$Field] = "error";
                }
            }
         }
    }

    # Check user can enter this CI into the database.
    if(!$vCanEnterCi){
        $vError .= "Error: You do not have the correct permissions to add this {$SOT[TabType]} to the database.<BR>\n";
    }

    return $vError;

} //CheckPassedInsertFormParams

/**
 * Set up and print the CI data entry form, entering any passed form data or default field data.
 *
 * @param   string  $TableFieldsArray   An array of assoc arrays describing the table fields.
 * @param   string  $pTabType           Can be server, user or group.
 * @param   string  $pHilight           An assoc array containing a listing of fields that need
 *                                      to be hilighted in the input form
 * @param   bool    $pBlankDefaults     Enter blanks instead of defaults into the input boxes.
 * @param   string  $pBtnName           Submit button text/label.
 * @param   array   $pSkipFields        Skip fields listed in this array.
 *
 * @return  string  HTML encoded error or success report.
 */
function PrintInputForm(&$SOT, $pBlankDefaults=FALSE, $pBtnName="Submit", $SkipFields=""){
	global $INPUT;

	# Setup the form.
	$DOC  = "<form name=\"inputform\" method=\"POST\" action=\"".SOT_HOME_PAGE.$INPUT->str('id')."&action=submit&tabtype={$SOT[TabType]}\">\n";
	$DOC .= "<input type=\"hidden\" name=\"sectok\" value=\"".getSecurityToken()."\" />";
    $DOC .= Load_IE8_Hack();
    $DOC .= "<div class=\"table\">\n";
	$DOC .= "<table class=\"inline\">\n";

	# Print the topic line of the table.
	$DOC .= "<tr><th>Field</th><th>Value</th><th>Description</th></tr>\n";

	# For each field in the table.
    foreach($SOT[TableFieldsArray] as $TableFields){

        $FieldName = $TableFields["column_name"];
        if(!$SOT[FieldMetaArray][$FieldName]['hide'] and !$SOT[FieldMetaArray][$FieldName][$SkipFields]){

			# Hilight the table row if it is in the hilight list.
            $vBackground = Get_Background_Style($SOT[HilightArray][$FieldName]);

			# Start building the table row.
			$DOC .= "<tr {$vBackground}><th>{$FieldName}</th><td>";

            # Get the field's current value or default value if not passed.
            if(!$pBlankDefaults and $SOT[FieldMetaArray][$FieldName]['autosearch']){

                # Automatically search for a unique field value.
                $DefaultFieldValue = $SOT[DatabaseObject]->Get_Next_Available($SOT[TabType], $FieldName, $SOT[FieldMetaArray][$FieldName]['autosearch']);
            } else {

                # Set a normal default value.
                $DefaultFieldValue = $pBlankDefaults ? "" : $TableFields["column_default"];
            }

            # Use the default value if no input value was passed to the form.
            $CurrentFieldValue = $INPUT->str($FieldName, $DefaultFieldValue);

			# Determine the type of input box to display.
			if($SOT[FieldMetaArray][$FieldName]["input"] == "enum"){

				# Enum list box. Enter available options and set passed data, if not,
				# set a default value if available.
				$DOC .= DrawOptionInput($FieldName, $TableFields, $pBlankDefaults, $CurrentFieldValue);

			} else if($SOT[FieldMetaArray][$FieldName]["input"] == "list"){

				# List box. Fill out the list then enter passed value, if not,
                # enter a default value if available.
                $DOC .= DrawListInput($SOT, $FieldName, $CurrentFieldValue);

			} else if($SOT[FieldMetaArray][$FieldName]["input"] == "large"){

				# Large text box. Enter passed value, if not, enter a default value if available.
				$DOC .= DrawTextAreaInput($FieldName, $CurrentFieldValue);

			} else {

				# Assume small text box. Enter passed value, if not, enter a default value if available.
				$DOC .= DrawTextInput($FieldName, $CurrentFieldValue);
			}

        # Add the field description.
		$DOC .= "</td><td>{$SOT[FieldMetaArray][$FieldName]['comment']}</td>";
		$DOC .= "</tr>\n";
        }
	}

	# Complete the table.
	$DOC .= "</table>\n";
	$DOC .= "</div>\n";

	# Complete the form with a submit and cancel button.
	$DOC .= "<input type=\"submit\" value=\"$pBtnName\">\n";
	$DOC .= Draw_Nav_Button("Cancel", SOT_HOME_PAGE."start");
	$DOC .= "</form>\n";

	return $DOC;

} //PrintInputForm

