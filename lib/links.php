<?php

/**
 * Doug Burner <doug869@users.noreply.github.com> 05/01/2013
 * Place a link contining the passed link parameters to sotreport. You can escape pipes "|"
 * using a back slash "\|" to pass unmodified. You can also escape the ampersands "&" if you
 * want it to appear in the link text, but it is just as easy to use "%26".
 *
 * Use:
 * Config Settings:
 *		[wiki|base|http]{:[page|link]}
 *				'wiki': Internal Dokuwiki page id
 *				'base': Relative to Dokuwiki home URL
 *				'http': A web URL
 * Wiki text:
 *		<sot_link[nn] {link parameters}>[Link text]</sot_link>
 *
 * Examples:
 * Config setting:
 *		sot_link01 = 'wiki:sot:sotreport'
 * Wiki text:
 *		<sot_link01 tabtype=server|params=--os_support UMO-UNIX --env E7|title=UMO-UNIX E7 CIs>
 *		All Active E7 CIs</sot_link>
 *
 * Config setting:
 *		sot_link02 = 'base'
 * Wiki text:
 *		<sot_link02 lib/plugins/sot/bin/sot_download_csv.php?tabtype=server|params=--cat cyclades --decom_only>
 *		Decom Cyclades Servers
 *		</sot_link>
 *
 * Config setting:
 *		sot_link03 = 'base:lib/plugins/sot/bin/sot_download_csv.php?'
 * Wiki text:
 *		<sot_link03 tabtype=server|params=--cat cyclades --decom_only>Download CSV file</sot_link>
 *
 * Config setting:
 *		sot_link04 = 'http'
 * Wiki text:
 *		<sot_link04 www.redhat.com>Redhat.com</sot_link>
 *
 * Config setting:
 *		sot_link05 = 'http://www.redhat.com'
 * Wiki text:
 *		<sot_link05 >Redhat.com</sot_link>
 */

if(!defined('DOKU_INC')) die();

/**
 * Printout the link tag <a href=....>.
 *
 * @param   string  $vLinkMeta  An array of assoc arrays describing the table fields.
 * @param   string  $pData      An assoc array of current CI data.
 *
 * @return  string  HTML link tag "<a href=....>".
 */
function Sot_Link_Open($vLinkMeta, $pData){

	$vLinkTarget = explode(":", $vLinkMeta);
	$vLinkType = array_shift($vLinkTarget);
	$vPrint	= "";

	switch($vLinkType) {
		case 'wiki':
			$vPrint .=	"<a href=\"".SOT_HOME_PAGE.implode(":", $vLinkTarget)."&";
		break;
		case 'report':
            $FullScreen = $GLOBALS['sot_full_screen_reports']?"&vecdo=print":"";
            $vPrint .=  "<a href=\"".SOT_HOME_PAGE.implode(":", $vLinkTarget).$FullScreen."&";
        break;
        case 'base':
			$vPrint .=	"<a href=\"".implode(":", $vLinkTarget);
		break;
		case 'http':
			$vPrint .=	"<a href=\"http://".implode(":", $vLinkTarget);
		break;
		DEFAULT:
			$vPrint .=	"<a href=\"".$vLinkMeta;
		break;

	}

	# Replace escaped ampersands "\&" with "%26", then replace the pipe "|" with "&"
	# and then bring back escaped pipes by replacing new escaped ampersands with a pipe
	# pipe "\&" with "|". This allowes escaping pipes so they don't get interpreated as
	# delimiters. Done this way to save a step and I couldn't be bothered working out
	# the preg_replace() reg expression.
	$vHold = str_replace("\\&", "%26", trim($pData[1]));
	$vHold = str_replace("|", "&", $vHold);
	$vPrint .= str_replace("\\&", "|", $vHold);
	$vPrint .= "\" class=\"wikilink1\">";
	return $vPrint;
}

/**
 * Return the link text that appears between th eopening and closing tags.
 *
 * @param   string  $LinkText   An array of assoc arrays describing the table fields.
 *
 * @return  string  Simply return the link text.
 */
function Sot_Link_Text($LinkText){
	return $LinkText;
}

/**
 * Return the link text that appears between th eopening and closing tags.
 * Not actually used but here for completeness.
 *
 * @return  string  Simply return the link close tag.
 */
function Sot_Link_Close(){
	return "</a>";
}

