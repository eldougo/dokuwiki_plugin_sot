<?php
/**
* Doug Burner 04/12/2013
*
* Allow CSV files generated by SoT Web Portal to be downloaded via the browser.
*
* @param    $_GET['downloadname'];   The file name of the download.
* @param    $_GET['tempfilename']   The temporary name of the file containing the CSV content.
*
*/

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename={$_GET['downloadname']}");

ob_clean();
flush();
readfile($_GET['tempfilename']);
exit;