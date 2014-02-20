<?php
/**
 * Plugin Sot: SoT Web Portal Plugin.
 *
 * @license		GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author		Doug Burner
 */

// must be run within DokuWiki hey.
if(!defined('DOKU_INC')) die();


define('PLUGIN_NAME', 	'sot');
define('SOT_RUN', 		'/usr/bin/sudo /usr/local/admin/bin/');
define('SOT_DEBUG', 	FALSE);
ini_set('display_errors', 1);

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
define('SOT_PLUGIN', 	DOKU_PLUGIN.PLUGIN_NAME.'/');
define('SOT_LIB', 		SOT_PLUGIN.'lib/');
define('SOT_CONF',      SOT_PLUGIN.'conf/');

require_once DOKU_PLUGIN.'syntax.php';

// Define some page pointers.
define('SOT_HOME_PAGE', 'doku.php?id=');
define('SOT_TABLE_FIELDS_FORM',	SOT_HOME_PAGE.'sot:table_fields');
define('SOT_MODIFY_CI_FORM',	SOT_HOME_PAGE.'sot:edit_ci');
define('SOT_NEW_CI_FORM',		SOT_HOME_PAGE.'sot:new_ci');
define('SOT_REPORT_CI_FORM',	SOT_HOME_PAGE.'sot:report');
define('SOT_SEARCH_FORM',		SOT_HOME_PAGE.'sot:search');
define('SOT_CI_HISTORY_FORM',	SOT_HOME_PAGE.'sot:history');
define('SOT_DOWNLOAD_FORM',		'/bin/sot_download_csv.php?');
define('SOT_FETCH_MEDIA',		'lib/exe/fetch.php?media=');
define('SOT_DATE_FORMAT',       'Y-m-d H:i:s');

$GLOBALS['SOT_LINK']['sot_link01']     = 'report:sot:report';
$GLOBALS['SOT_LINK']['sot_link02']     = 'base';
$GLOBALS['SOT_LINK']['sot_link03']     = 'base:lib/plugins/sot/bin/sot_download_csv.php?';
$GLOBALS['SOT_LINK']['sot_link04']     = 'http';
$GLOBALS['SOT_LINK']['sot_link05']     = 'wiki:sot:search';
$GLOBALS['SOT_LINK']['sot_link06']     = 'wiki:sot:new_ci';
$GLOBALS['SOT_LINK']['sot_link07']     = '';
$GLOBALS['SOT_LINK']['sot_link08']     = '';
$GLOBALS['SOT_LINK']['sot_link09']     = '';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_sot extends DokuWiki_Syntax_Plugin {

	function getInfo() {
		return array(	'author' 	=> 'Doug Burner',
						'email'		=> 'doug869@users.noreply.github.com',
						'date'		=> '2014-02-20',
						'name'		=> 'SoT Web Portal Plugin',
						'desc'		=> 'A web portal to the UMO-UNIX SoT database',
						'url'		=> 'https://github.com/doug869/dokuwiki_plugin_sot');
	}

 	/**
     * @return string Syntax mode type
     */
	function getType() { return 'substition'; }

	/**
     * @return int Sort order - Low numbers go before high numbers
     */
	function getSort() { return 33; }

	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\[SOT_TEST\]',				$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[SOT_ADD_NEW_CI\]',		$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[SOT_MODIFY_CI\]',		$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[SOT_SEARCH_CI\]',		$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[GENERATE_REPORT\]',		$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[LIST_TABLE_FIELDS\]',	$mode,'plugin_sot');
		$this->Lexer->addSpecialPattern('\[SOT_DISPLAY_HISTORY\]',	$mode,'plugin_sot');
		$this->Lexer->addEntryPattern('<sot_link[0-9][0-9].*?>(?=.*?</sot_link>)',$mode,'plugin_sot');
	}

	/**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
	function postConnect() {
				$this->Lexer->addExitPattern('</sot_link>', 'plugin_sot');
	}

	/**
     * Handle matches of the sot syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
	function handle($match, $state, $pos, Doku_Handler &$handler){
		switch ($state) {
			case DOKU_LEXER_ENTER:
				$vLinkType = $GLOBALS['SOT_LINK'][substr($match, 1, 10)];
				$vLinkTarget = substr($match, 11, -1);
				return array($state, array($vLinkType, $vLinkTarget));

			case DOKU_LEXER_UNMATCHED:
				return array($state, $match);

			case DOKU_LEXER_EXIT:
				return array($state, '');
		}
		return array($match, $state, $pos);
	}

    //function GetLang($id){return $this->getLang($id);}

	/**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
	function render($mode, &$renderer, $indata) {
		if (empty($indata)) return false;
		list($state, $data) = $indata;
		if($mode == 'xhtml'){

            $GLOBALS['sot_full_screen_reports'] =$this->getConf('full_screen_reports');
            $GLOBALS['sot_max_csv_cache_age'] =$this->getConf('sot_max_csv_cache_age');
            $GLOBALS['sot_super_users'] =$this->getConf('sot_super_users');
            $GLOBALS['sot_use_lmt'] =$this->getConf('sot_use_lmt');

            switch($state){
				case '[SOT_TEST]':
					//echo gmdate("Y-m-d H:i:s")."<BR>";
                    include_once SOT_LIB.'test.php';
                    $renderer->doc .= sot_test();
					break;
				case '[SOT_ADD_NEW_CI]':
					include_once SOT_LIB.'form_new.php';
					$renderer->doc .= Sot_Add_New_CI();
					break;
				case '[SOT_MODIFY_CI]':
					include_once SOT_LIB.'form_edit.php';
					$renderer->doc .= Sot_Modify_CI();
					break;
				case '[SOT_SEARCH_CI]':
					include_once SOT_LIB.'form_search.php';
					$renderer->doc .= Sot_Search_CI();
					break;
				case '[GENERATE_REPORT]':
					include_once SOT_LIB.'form_report.php';
					$renderer->doc .= Sot_Generate_Report();
					break;
				case '[LIST_TABLE_FIELDS]':
					include_once SOT_LIB.'form_table_fields.php';
					$renderer->doc .= Sot_List_Table_Fields();
					break;
				case '[SOT_DISPLAY_HISTORY]':
					include_once SOT_LIB.'form_history.php';
					$renderer->doc .= Sot_Display_History();
					break;
				case DOKU_LEXER_ENTER:
					//$renderer->doc .= "DOKU_LEXER_ENTER: ".var_export($data, true)."<BR>";
					include_once SOT_LIB.'links.php';
					$renderer->doc .= Sot_Link_Open($data[0], $data);
					break;
				case DOKU_LEXER_UNMATCHED:
					//$renderer->doc .= "DOKU_LEXER_UNMATCHED: ".var_export($data, true)."<BR>";
					//include_once SOT_LIB.'links.php';
					//$renderer->doc .= Sot_Link_Text($data);
                    $renderer->doc .= $data;
					break;
				case DOKU_LEXER_EXIT:
					//$renderer->doc .= "DOKU_LEXER_EXIT: ".var_export($data, true)."<BR>";
					//$helper_class = new sot_links_class;
					//$renderer->doc .= $helper_class->Sot_Link_Close();
					$renderer->doc .= "</a>";
					break;
				//DEFAULT:
				//	$renderer->doc .= "DEFAULT: ".var_export($data, true)."<BR>";
				//break;
			}
			return true;
		}
		return false;
	}
}


