<?php

include_once SOT_LIB.'common.php';
//include_once SOT_LIB.'database.php';



function sot_test(){
    global $INPUT;
    global $INFO;

    //$UserAgent = $_SERVER['HTTP_USER_AGENT'];
    echo sotprintr($_POST);
    echo $INPUT->str('thefield')."<BR>";

    echo "<!--[if lte IE 8]>Less than or equal to IE8<BR><![endif]-->";

    $DOC  = "<form name=\"inputform\" method=\"POST\" action=\"".SOT_HOME_PAGE.$INPUT->str('id')."&action=submit&tabtype={$SOT[TabType]}\">\n";
    $DOC .=  strpos(" ".$_SERVER['HTTP_USER_AGENT'], 'MSIE 8') ? $GLOBALS['JComboBoxscript'] : "ddddd<BR>";
    $DOC .= "<div class=\"table\">\n";
    $DOC .= "<table class=\"inline\">\n";
    $DOC .= "<tr><th>Field</th><th>Value</th><th>Description</th></tr>\n";

    # ---------- Combo Box -------------
    $ValueArray = array("one", "Microsoft Windows Server 2008 R2 (64-bit)", "Three");
    $InputName = "thefield";
    $DOC .= "<tr ><th>Testing Input Types</th><td>";
    if(strpos(" ".$_SERVER['HTTP_USER_AGENT'], 'MSIE 8') !== FALSE){
        $DOC .= "<input type=\"text\" id=\"{$InputName}\" name=\"{$InputName}\" style=\"width: 280px;\" /><BR>\n";
        $DOC .= "<select name=\"{$InputName}list\" style=\"width: 280px;\" onChange=\"combo(this, '{$InputName}')\" >\n";
        $DOC .= "<option></option>\n";
        foreach($ValueArray as $Value){
            $DOC .= "<option>{$Value}</option>\n";
        }
        $DOC .= "</select>\n";
    } else {
        $DOC .= "<input type=\"text\" id=\"{$InputName}\" name=\"{$InputName}\" list=\"{$InputName}list\" style=\"width: 280px;\" /><BR>\n";
        $DOC .= "<datalist id=\"{$InputName}list\" >\n";
        foreach($ValueArray as $Value){
            $DOC .= "<option value=\"{$Value}\">\n";
        }
        $DOC .= "</datalist>\n";
    }
    $DOC .= "</td><td>Description text goes here</td></tr>\n";
    # ---------- End Combo Box -------------

    $DOC .= "</table></div>\n";

    $pBtnName = "Submit hey";
    $DOC .= "<input type=\"submit\" value=\"$pBtnName\">\n";
    $DOC .= "</form>\n";
    return $DOC;
}