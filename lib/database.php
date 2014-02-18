<?php

/**
 * The SoT database class definition.
 * You must install php-mysql and restart apache.
 * Load from the top of the requesting php file outside of any functions
 * to allow "$this->getConf()" to work from the parent syntax class.
 * Load with "include_once SOT_LIB.'database.php';"
 *
 * @author  Doug Burner <doug869@users.noreply.github.com>
 *
 */

if(!defined('DOKU_INC')) die();

/**
 * Put database connection admin setting into global variables
 * allowing them to accessed inside the class methods.
 */
$GLOBALS['db_login']['db_type'] =$this->getConf('sot_db_type');
$GLOBALS['db_login']['db_host'] =$this->getConf('sot_db_host');
$GLOBALS['db_login']['db_name'] =$this->getConf('sot_db_name');
$GLOBALS['db_login']['username']=$this->getConf('sot_db_user');
$GLOBALS['db_login']['password']=$this->getConf('sot_db_pw');

/**
 * The database access class.
 */
class Sot_Database{

    /**
     * The database handle property Sot_Database::DB_Handle is set in the
     * class constructor and destroyed in the class destructor.
     */
    private $DB_Handle;
    private $DB_Status_Message;
    private $Sot_Metadata;

    /**
     * The class constructor sets up a new database connection using
     * the admin connection settings saved to global array.
     */
    function __construct(){
        if(SOT_DEBUG) echo "Sot_Database::__construct<BR>";
        $DB_Login = $GLOBALS['db_login'];

        $dsn        = "{$DB_Login['db_type']}:host={$DB_Login['db_host']};dbname={$DB_Login['db_name']}";
        $username   = $DB_Login['username'];
        $password   = $DB_Login['password'];

        # Catch any connection errors.
        try {

            # Set up a database connection object and assign to the
            # class property Sot_Database::DB_Handle
            $this->DB_Handle = new PDO($dsn, $username, $password);

        } catch(PDOException $exception) {

            # Format and display the error text and die.
            include_once SOT_LIB.'common.php';
            print(Display_Error_Box("Failed to connect to the database: " . $exception->getMessage()));
            print($exception->getMessage());
            print("\n</div></div></div></body>");
            die($exception->getCode());
        }

        # Save connection details.
        $this->DB_Status_Message = "Connected to database: {$DB_Login['db_name']}";

        # Load SoT Metadata.
        $sot_metadata = array();
        include SOT_CONF.'sot_metadata.php';
        $this->Sot_Metadata = &$sot_metadata;

    } //__construct

    /**
     * Close and reclaim the class database connection property. Any
     * errors are sent directly to the form.
     */
    function __destruct(){
        if(SOT_DEBUG) echo "Sot_Database::__destruct<BR>";
        try {
            $this->DB_Handle = null;
        } catch(PDOException $exception) {
            if(SOT_DEBUG) {
                include_once SOT_LIB.'common.php';
                $vResult = Display_Error_Box("Error DB disconnect: ".$exception->getMessage())."<BR>";
                echo $vResult;
            }
        }

    } //__destruct

    /**
     * Show database connection details
     */
    function Show_DB_Connection_Details(){
        return "<div><BR><font size=\"1\">".$this->DB_Status_Message."</font></div>";
    }

    /**
     * Return the next available field value starting from $StartValue.
     */
    function Get_Next_Available($TabType, $FieldName, $StartValue){
        $sqlquery = "SELECT count(*) as count FROM {$TabType}s WHERE {$FieldName} = ?";
        $sth = $this->DB_Handle->prepare($sqlquery);

        for ($count = $StartValue; $count < ($StartValue + 5000); $count++) {
            $sth->execute(array($count));
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            if(!$result[count]){
                return $count;
            }
        }
        return "";
    }

    /**
     * Run the passed query and return all the results in an array with
     * the first row being the column names unless $pQuiet is set to true.
     * Return format: array(assoc_array()), access by array_name[n][col_name]
     *
     * @author  Doug Burner <doug869@users.noreply.github.com>
     * @param   string  $pSqlQuery      The SQL query.
     * @param   bool    $pQuiet         Don't put field headings in array[0].
     * @param   string  &$vResult       Any error text, Empty of no error.
     * @param   int     &$vRetCode      Return code, 0 if OK.
     * @return  array   An array of associative arrays of returned records.
     *
     */
    private function Get_Sot_Data($pSqlQuery, $pQuiet = FALSE, &$vResult = "", &$vRetCode = 0){
        $vReturn = array();

        # Run the passed query and get the return codes.
        $stmt        = $this->DB_Handle->query($pSqlQuery);
        $vRetCode    = $this->DB_Handle->errorCode();
        $ErrorArray  = $this->DB_Handle->errorInfo();
        $vResult     = $ErrorArray[2];

         # Return error text in the expected return format.
         if($vRetCode != 0){
            include_once SOT_LIB.'common.php';
            if(SOT_DEBUG) echo Display_Error_Box("Error: $vResult - $pSqlQuery");
            return array(array("Error: $vResult"));
         }

         # If any data was returned.
         if(is_object($stmt)) {

             # Get all the returned data in one go.
             $vReturn = $stmt->fetchAll(PDO::FETCH_ASSOC);

             # Create a colums' name array if not in quiet mode and
             # unshift it to the front of the table array.
             if(!$pQuiet){
                for ($i = 0; $i < $stmt->columnCount(); $i++) {
                    $col = $stmt->getColumnMeta($i);
                    $columns[$col['name']] = $col['name'];
                }
                array_unshift($vReturn,$columns);
            }
        } else {
            $vReturn = array(array());
         }

        # Return format: array(assoc_array())
        return $vReturn;

    } //Get_Sot_Data

    /**
     * Return an assoc array containing table field metadata for the passed table.
     * Return format: assoc_array[field][key] = value.
     *
     * @param   string  $TableName      The actual name of the table, eg 'servers', 'users', 'server_history', etc.
     * @return  array   assoc array: assoc_array[field][key] = value
     *
     */
    function Get_Metadata_Array($TableName){
        return $this->Sot_Metadata["$TableName"];
    }

    /**
     * Return a table containing table field information for the passed table.
     * Return format: array(assoc_array()), access by array_name[n][col_name]
     *
     * @param   string  $TableName      The actual name of the table, eg 'servers', 'users', 'server_history', etc.
     * @return  array   An array of associative arrays of returned records.
     *
     */
    function Get_Sot_Table_Fields($TableName){
        $vSelect = "SELECT column_name, column_default, column_comment"
                ."    FROM information_schema.columns "
                ."   WHERE table_name = '{$TableName}'";

        $TableFieldArray = $this->Get_Sot_Data($vSelect);

        # Add field comments from sot_metadata.
        $Sot_Metadata = $this->Get_Metadata_Array($TableName);
        foreach($TableFieldArray as $Index => $TableField){
            $TableFieldArray[$Index]['column_comment'] = $Sot_Metadata[$TableField['column_name']]['comment'];
        }

        # Return format: array(assoc_array())
        return $TableFieldArray;
    }

    /**
     * Return a table containing extra table field information for the passed table.
     * Similar to above except with more fields used to create data entry forms. An
     * extra 'comments' column is added to the end of the returned table array.
     * Return format: array(array(column_name,data_type,column_type,column_default,column_comment))
     *     column_name      The name of the data field.
     *     data_type        The MySQL data type, varchar, enum, etc.
     *     column_type      Data type details, varchar(255), enum(a,b,c), etc.
     *     column_default   The default column value if nothing passed.
     *     column_comment   Column description and meta data. "Comment<META:>ke1=val1,key2=val2..."
     *
     * @param   string  $TableName      The actual name of the table, eg 'servers', 'users', 'server_history', etc.
     * @return  array   Array(assoc_array()), access by array_name[n][col_name]
     *
     */
    function Get_Table_Field_Details($TableName, $With_Comment_Field=TRUE){
        $vSelect = "SELECT column_name, column_type, column_default"
                ."    FROM information_schema.columns "
                ."   WHERE table_name = '$TableName'";
        $vTableData = $this->Get_Sot_Data($vSelect);

        # Add the comments field.
        if($With_Comment_Field){
            $vMeta = $this->Get_Metadata_Array('table');
            array_push($vTableData, array("column_name" => $vMeta["$TableName"]['add_field']));
            //array_push($vTableData, array("column_name" => "comment"));
        }

        # Return format: array(assoc_array())
        return $vTableData;
    }

    /**
     * Return distinct values from the passed field from the passed table.
     */
    function Get_Distinct_Values($TabType, $Field, $Limit=300){
        $vSelect = "SELECT DISTINCT {$Field} from {$TabType}s order by {$Field} LIMIT {$Limit}";
        return $this->Get_Sot_Data($vSelect, TRUE);
    }

    /**
     * Return the passed CI's history records. If $DisplayLogs is true, add the log column
     * to the table. This is set if the user is an administrator in function Sot_Display_History().
     *
     * @param   string  $pCiName        The name of the CI
     * @param   string  $pTabType       The table type can be [server|user|group]. This is not the actual table
     *                                  because it gets an "s" and "_history" appended as required.
     * @param   bool    $DisplayLogs    Add the logs column if true.
     * @param   bool    $LastAtTop      Order the records by date, latest entry at the top.
     * @return  array   Array(assoc_array()), access by array_name[n][col_name]
     */
    function Get_CI_History($pCiName, $pTabType, $DisplayLogs, $LastAtTop=TRUE){
        if($DisplayLogs) $LogColumn = ", log";
        $vSelect = "SELECT timestamp, who, comment{$LogColumn} "
                ."    FROM {$pTabType}_history "
                ."   WHERE {$pTabType}_id = (SELECT id FROM {$pTabType}s WHERE name = '{$pCiName}')";
        if($LastAtTop) $vSelect .= " ORDER BY timestamp DESC";
        $vTableData = $this->Get_Sot_Data($vSelect);

        # Format the logs by putting a "<BR>" after commas and certain key words.
        if($DisplayLogs){
            array_walk($vTableData, get_class()."::Format_Logs", "log");
        }

        # Return format: array(assoc_array())
        return $vTableData;

    } //Get_CI_History

    function Get_CI_History2(&$SOT, $DisplayLogs, $LastAtTop=TRUE){
        if($DisplayLogs) $LogColumn = ", log";
        $vSelect = "SELECT timestamp, who, comment{$LogColumn} "
                ."    FROM {$SOT[TabType]}_history "
                ."   WHERE {$SOT[TabType]}_id = (SELECT id FROM {$SOT[TabType]}s WHERE id = '{$SOT[CI_ID]}')";
        if($LastAtTop) $vSelect .= " ORDER BY timestamp DESC";
        $vTableData = $this->Get_Sot_Data($vSelect);

        # Format the logs by putting a "<BR>" after commas and certain key words.
        if($DisplayLogs){
            array_walk($vTableData, get_class()."::Format_Logs", "log");
        }

        # Return format: array(assoc_array())
        return $vTableData;
    }

    /**
     * Callback to format the log entry in the passed array element by
     * putting a "<BR>" after each comma and certain other key words.
     * **TODO** This can be improved by setting up a regexpr to only replace
     * commas and key words outside of quotes.
     */
    private function Format_Logs(&$pSourceArray, $ArrayIndex, $Key){
            $pSourceArray[$Key] = str_replace(",", ",<BR>\n", $pSourceArray[$Key]);
            $pSourceArray[$Key] = str_replace(" WHERE ", " <BR>\nWHERE ", $pSourceArray[$Key]);
            $pSourceArray[$Key] = str_replace(" SET ", " <BR>\nSET ", $pSourceArray[$Key]);
    }

    /**
     * Decode command line parameters and tokens into a SQL WHERE clause.
     * Input format: <name1>:<value1>,<name2>:<value2>,...
     *
     * Parameter encoding is used by the report links on the main reports page.
     *
     * @param   string  $EncodedParams  Encoded parameters string.
     * @return  string  A string containing a SQL WHERE clause.
     *
     */
    private function Decode_Params_To_Where_Clause($EncodedParams, $IncludeDecom=FALSE){

        # By default, decommissioned servers or left accounts are excluded from the results.
        $vExcludeDecom = $IncludeDecom ? "" : "status != 'decom' AND status != 'left'";

        # Split out individual parameters by comma.
        $vEncodedParamArray = explode(",", $EncodedParams);

        # Decode the command lines. Add more here as required using others as templates.
        foreach($vEncodedParamArray as $vEncodedParam){

            # Split out key and value by colon ":".
            $vKeyVal = explode(":", $vEncodedParam);
            switch(trim($vKeyVal[0])){
                case "decom_only":
                    # Decommissioned servers only.
                    $vExcludeDecom = "";
                    $vSqlWhereArray[] = "status='decom'";
                    break;
                case "decom_include":
                    # Include decommissioned servers and accounts that have left.
                    $vExcludeDecom = "";
                    break;
                case "hw_only":
                    # No virtual servers, hardware only.
                    $vSqlWhereArray[] = "type != 'virtual'";
                    break;
                case "vi_only":
                    # Virtual servers only, no hardware.
                    $vSqlWhereArray[] = "type = 'virtual'";
                    break;
                case "os_exclude":
                    # Exclude this operating system (used to exclude IRIS from the UNIX list).
                    $vSqlWhereArray[] = "os not like '%{$vKeyVal[1]}%'";
                    break;
                case "rcat";
                    # Evaluate the passed cat value using regexpr.
                    $vSqlWhereArray[] = "cat regexp '{$vKeyVal[1]}'";
                    break;
                case "tabformat":
                    # Ignore because decoded elsewhere.
                    break;
                case "":
                    # Ignore blank lines.
                    break;
                default:
                    # All other commands are interpereted as "Key like 'Value'"
                    # This allows the use of certain SQL reqexpr characters
                    # such as '%', '^' , '$' etc.
                    $vSqlWhereArray[] = "{$vKeyVal[0]} like '".trim($vKeyVal[1])."'";
                    if(SOT_DEBUG) echo "<BR>Default: {$vKeyVal[0]}='{$vKeyVal[1]}'<BR><BR>";
            }
        }

        # Join the individual parameters with 'AND' between each.
        if($vExcludeDecom) $vSqlWhereArray[] = $vExcludeDecom;
        $vWhereClause .= implode(" AND ", $vSqlWhereArray);

        # Return format: SQL_where1 AND SQL_where1 AND ... AND SQL_whereN
        return $vWhereClause;

    } //Decode_Params_To_Where_Clause

    /**
     * Get table data from the passed parameter array. Return
     * a 2D array; an array of assoc arrays. Each assoc array contains
     * one CI record. Format: array(assoc_array()), access by array_name[n][col_name]
     *
     * @param   string  $pTabType   The table type can be [server|user|group]. This
     *                              is not the actual table because it gets an "s" appended.
     * @param   array   $ParamArray An array of assoc arrays containg parameter vey value pairs.
     * @param   bool    $pQuiet     Do not put the column names in the top of the return array.
     * @return  array   A 2d array containg the found table data.
     *
     */
    function Get_CI_Records_From_Array($pTabType, $ParamArray, $pQuiet=FALSE){

        # Prevent error messages if nothing was passed.
        if(!count($ParamArray)) $ParamArray[] = "";

        # Set each parameter in the array to a SQL WHERE clause: "param like 'value'".
        foreach($ParamArray as $Key => $Value){
            $vSqlWhereArray[] = "{$Key} like '".trim($Value)."'";
        }

        # Create a SQL query by imploding the list of SQL WHERE clauses delimited by "AND".
        $vSqlQuery = "SELECT * FROM {$pTabType}s WHERE ".implode(" AND ", $vSqlWhereArray);

        if(SOT_DEBUG) echo "<BR>Function Get_CI_Records_From_Array() Sql query: $vSqlQuery<BR><BR>";

        # Run the SQL query string against the database.
        $vTableData = $this->Get_Sot_Data($vSqlQuery, $pQuiet);

        # Return format: array(assoc_array())
        return $vTableData;

    } //Get_CI_Records_From_Array

    /**
     * Get table data from the passed encoded parameter string. Return
     * a 2D array; an array of assoc arrays. Each assoc array contains
     * one CI record. Return format: array(assoc_array()), access by array_name[n][col_name]
     *
     * @param   string  $pTabType       The table type can be [server|user|group]. This
     *                                  is not the actual table because it gets an "s" appended.
     * @param   string  $EncodedParams  Encoded parameters string.
     * @param   bool    $pQuiet         Do not put the column names in the top of the return array.
     * @param   bool    $IncludeDecom   Include status decom and left in the search.
     * @return  array   A 2d array containg the found table data.
     *
     */
    function Get_CI_Records_From_Encoded($pTabType, $EncodedParams, $pQuiet=FALSE, $IncludeDecom=FALSE){

        # Decoded the WHERE clauses and create a SQL query string.
        $vSqlQuery = "SELECT * FROM {$pTabType}s WHERE "
                    .$this->Decode_Params_To_Where_Clause($EncodedParams, $IncludeDecom);

        if(SOT_DEBUG) echo "<BR>Function Get_CI_Records_From_Encoded() Sql query: $vSqlQuery<BR><BR>";

        # Run the SQL query string against the database.
        $vTableData = $this->Get_Sot_Data($vSqlQuery, $pQuiet);

        # Return format: array(assoc_array())
        return $vTableData;

    } //Get_CI_Records_From_Encoded

    /**
     * Count the number of occurences of $pCiName in the $pTabType table type.
     *
     * @param   string  $pCiName    The name of the target CI.
     * @param   string  $pTabType   The table type can be [server|user|group]. This
     *                              is not the actual table because it gets an "s" appended.
     * @param   string  &$vResult   Any error text, empty of no error.
     * @param   int     &$vRetCode  Return code, 0 if OK.
     * @return  int     The number of matching records found.
     *
     */
    private function Count_CI_Records_On_Name($pCiName, $pTabType, &$vResult = "", &$vRetCode = 0){
        $CountQuery = "SELECT count(*) as count "
                     ."  FROM {$pTabType}s "
                     ." WHERE name = '{$pCiName}'";
        $CountResult = $this->Get_Sot_Data($CountQuery, TRUE, $vResult, $vRetCode);
        return $CountResult[0]['count'];

    } //Count_CI_Records_On_Name

    private function Count_CI_Records_On_ID($CI_ID, $pTabType, &$vResult = "", &$vRetCode = 0){
        $CountQuery = "SELECT count(*) as count "
                     ."  FROM {$pTabType}s "
                     ." WHERE id = '{$CI_ID}'";
        $CountResult = $this->Get_Sot_Data($CountQuery, TRUE, $vResult, $vRetCode);
        return $CountResult[0]['count'];

    } //Count_CI_Records_On_ID

    /**
     * Return the number of times the passed value appears in the passed field
     * of the passed tabtype.
     *
     */
    function Count_CI_Records_With_Field_Value($TabType, $Field, $Value){
        $CountQuery = "SELECT count(*) as count "
                     ."  FROM {$TabType}s "
                     ." WHERE {$Field} = '{$Value}'";
        $CountResult = $this->Get_Sot_Data($CountQuery, TRUE);
        return $CountResult[0]['count'];
    }

    /**
     * Attempt to modify an existing CI in the database with the passed data.
     *
     * @param   string  $pCiName    The name of the target CI.
     * @param   string  $pTabType   The table type can be [server|user|group]. This
     *                              is not the actual table because it gets an "s" appended.
     * @param   array   $pParams    An assoc array of key value pairs of record data for the new CI
     * @param   string  &$vResult   Any error text, empty of no error.
     * @param   int     &$vRetCode  Return code, 0 if OK.
     * @return  string  Any error or success text.
     *
     */
    function Modify_CI_Record($CI_ID, $pCiName, $pTabType, $pParams, &$vResult="", &$vRetCode=0){

        # Set up the history log array.
        $vLog['ci_id'] = $CI_ID;
        $vLog['ci_name'] = $pCiName;
        $vLog['comment'] = "";
        $vLog['log'] = "";
        $vLog['login_id'] = "";
        $vLog['tabtype'] = $pTabType;

        $vReturn = "";

        # Make sure the CI's name is in the database.
        $vCiRecordCount = $this->Count_CI_Records_On_ID($CI_ID, $pTabType, $vResult, $vRetCode);

        if($vCiRecordCount == 0){
            $vRetCode = -1;
            $vReturn = "CI '{$pCiName}' is not in the {$pTabType}s table.";
            $vResult = $vReturn;

        } elseif ($vCiRecordCount > 1){
            $vRetCode = -2;
            $vReturn = "CI '{$pCiName}' has multiple entries in the {$pTabType}s table.";
            $vResult = $vReturn;

        # Proceed if these was no database error returned from the count above.
        } elseif ($vRetCode == 0) {

            # Remove comments and login_id params from the new record data array.
            # These sent to the history tables instead of the CI tables. Everything
            # else is quoted and escaped using the PDO::quote method.
            $vSqlSet = $this->Parse_Param_Array($pParams, $vLog);

            # If there were CI modification params passed.
            if(count($vSqlSet) > 0) {

                #Build the CI's SQL update string.
                $SqlInsert = "UPDATE {$pTabType}s"
                            ."   SET ".implode(",",$vSqlSet)
                            ." WHERE id   = '{$CI_ID}'";

                $vLog['log'] = $SqlInsert;
                //echo $SqlInsert."<BR>";

                # Attempt to update the record in the assocciated table.
                $vReturn = $this->Insert_Sot_Data($SqlInsert, $vResult, $vRetCode);

                # If the record was successfully updated.
                if($vRetCode == 0){
                    $vReturn .= "CI '$pCiName' successfully updated. ";
                }

            } else {

                # Nothing to modify, assume only a comment was passed.
                $vLog['log'] = "";
                $vRetCode = 0;
            }

            # If successful, log the event and user comments to the associated history table.
            if($vRetCode == 0){
                $vReturn .= $this->Insert_Sot_History($vLog, $vResult, $vRetCode);
            }

        } else {

            # There was an error.Return error text.
            $vReturn .= $vResult;
        }

        # Any error text, empty of no error.
        return $vReturn;

    } //Modify_CI_Record

    /**
     * Parse the passes parameter array and return an array of SQL SET clauses.
     * The comments and login_id params are placed in the corresponding LogArray
     * for insertion into the corresponding History table.
     *
     * @param   array   $ParamArray     The array of assoc arrays of parameters to be parsed.
     * @param   array   &$LogArray      An assoc array of data to be sent to the history table.
     * @return  array   A string array of SQL SET clauses
     *
     */
    private function Parse_Param_Array($ParamArray, &$LogArray){

        # Remove comments and login_id params from the return string array.
        # Everything else is quoted and escaped using the PDO::quote method.
        foreach($ParamArray as $Key => $Value){
            $Key = trim($Key);
            $Value = trim($Value);
            if($Key == "comment") {
                $LogArray['comment'] .= $Value;
            } elseif($Key == "login_id") {
                $LogArray['login_id'] = $Value;
            } else {
                $vSqlSet[] = "{$Key}=".$this->DB_Handle->quote($Value);
            }
        }

        # Return format: string.
        return $vSqlSet;

    } //Parse_Param_Array

    /**
     * Attempt to insert a new CI into the database with the passed data.
     *
     * @param   string  $pCiName    The name of the target CI.
     * @param   string  $pTabType   The table type can be [server|user|group]. This
     *                              is not the actual table because it gets an "s" appended.
     * @param   array   $pParams    An assoc array of key value pairs of record data for the new CI
     * @param   string  &$vResult   Any error text, empty of no error.
     * @param   int     &$vRetCode  Return code, 0 if OK.
     * @return  string  Any error text, empty of no error.
     *
     */
    function Insert_New_CI_Record($pCiName, $pTabType, $pParams, &$vResult = "", &$vRetCode=0){

        # Set up the history log array.
        $vLog['ci_name'] = "$pCiName";
        $vLog['comment'] = "";
        $vLog['log'] = "";
        $vLog['login_id'] = "";
        $vLog['tabtype'] = $pTabType;

        $vReturn = "";

        # Make sure the new CI's name is not already in the database.
        if($this->Count_CI_Records_On_Name($pCiName, $pTabType, $vResult, $vRetCode) != 0){
            $vRetCode = -1;
            $vReturn = "CI '{$pCiName}' is already in the {$pTabType}s table.";
            $vResult = $vReturn;

        # Proceed if these was no database error returned from the count above.
        } elseif ($vRetCode == 0) {

            # Remove comments and login_id params from the new record data array.
            # These sent to the history tables instead of the CI tables. Everything
            # else is quoted and escaped using the PDO::quote method.
            $vSqlSet = $this->Parse_Param_Array($pParams, $vLog);

            #Build the new CI's SQL insert string.
            $SqlInsert = "INSERT INTO {$pTabType}s SET ".implode(",",$vSqlSet);
            $vLog['log'] = $SqlInsert;

            # Attempt to insert the new record into the assocciated table.
            $vReturn = $this->Insert_Sot_Data($SqlInsert, $vResult, $vRetCode);

            # If successful, log the event to the associated history table.
            if($vRetCode == 0){
                $vReturn .= "CI '$pCiName' has been added to the SoT. ";
                $vReturn .= $this->Insert_Sot_History($vLog, $vResult, $vRetCode);
            }

        } else {

            # There was an error.Return error text.
            $vReturn = $vResult;
        }

        # Any error text, empty of no error.
        return $vReturn;

    } //Insert_New_CI_Record

    /**
     * Insert a new CI record into the associated CI table.
     *
     * @param   string  $SqlInsert        The SQL insert query.
     * @param   string  &$vResult       Any error text, empty of no error.
     * @param   int     &$vRetCode      Return code, 0 if OK.
     * @return  string  Any error text, empty of no error.
     *
     */
    private function Insert_Sot_Data($SqlInsert, &$vResult="", &$vRetCode=0){
        $vReturn = array();

        # Run the passed query.
        $this->DB_Handle->exec($SqlInsert);

        # Return any errors.
        $vRetCode = $this->DB_Handle->errorCode();
        $ErrorArray = $this->DB_Handle->errorInfo();
        $vResult = $ErrorArray[2];
        return $ErrorArray[2];

    } //Insert_Sot_Data


    /**
     * Add a log to the appropriate history table.
     * @param   array   $LogArray   An array containg the following input parameters:
     *                  ci_name        The CI's name.
     *                  comment        The comment text to add to the log.
     *                  log            The SQL command run on the CI's record.
     *                  login_id       The user's login ID.
     *                  tabtype        The table type can be [server|user|group].
     *
     * @param   string  &$vResult   Any error text, empty of no error. Extra text is prepended
     *                              to identfy the source of the error.
     * @param   int     $vRetCode   Return code, 0 if OK.
     * @return  string  Any error text, empty of no error.
     *
     */
    private function Insert_Sot_History($LogArray, &$vResult="", &$vRetCode=0){

        # Add a history record.
        $SqlHistory =
             "INSERT INTO {$LogArray['tabtype']}_history "
            ."  SET {$LogArray['tabtype']}_id = (SELECT id"
            ."                     FROM {$LogArray['tabtype']}s "
            ."                    WHERE name = '{$LogArray['ci_name']}'), "
            ."       timestamp = '".gmdate("Y-m-d H:i:s")."', "
            ."       who = '{$LogArray['login_id']}', "
            ."       log = ?, "
            ."       comment = ?";

        # Run the passed query.
        $stmt = $this->DB_Handle->prepare($SqlHistory);
        $stmt->execute(array($LogArray['log'], $LogArray['comment']));

        # Return any errors.
        $vRetCode = $stmt->errorCode();
        $ErrorArray = $stmt->errorInfo();

        # Prepend the error source to the error text.
        if($vRetCode == 0){
            $vResult = "Comment inserted into the CI's history table.";
        } else {
            $vResult = "Error updating the {$LogArray['tabtype']}_history table: ".$ErrorArray[2];
        }

        # Return error string.
        return $vResult;

    } //Insert_Sot_History

} //class Sot_Database





