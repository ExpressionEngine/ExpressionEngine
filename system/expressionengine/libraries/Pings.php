<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Pings {

	protected $ping_result;

	/**
	 * Is Registered?
	 *
	 * @return bool
	 **/
	public function is_registered()
	{
		if ( ! IS_CORE && ee()->config->item('license_number') == '')
		{
			return FALSE;
		}

		if ($this->_developer_domain_bypass(array(
			ee()->config->item('site_url'),
			ee()->config->item('cp_url'),
			ee()->config->item('theme_folder_url')
			)
		))
		{
			return TRUE;
		}

		$cached = ee()->cache->get('software_registration', Cache::GLOBAL_SCOPE);

		if ( ! $cached OR $cached != ee()->config->item('license_number'))
		{
			// restrict the call to certain pages for performance and user experience
			$class = ee()->router->fetch_class();
			$method = ee()->router->fetch_method();

			if ($class == 'homepage' OR ($class == 'admin_system' && $method == 'software_license'))
			{
				$payload = array(
					'username'			=> ee()->config->item('ellislab_username'),
					'license_number'	=> (IS_CORE) ? 'CORE LICENSE' : ee()->config->item('license_number'),
					'domain'			=> ee()->config->item('site_url')
				);

				if ( ! $registration = $this->_do_ping('http://ping.ellislab.com/register.php', $payload))
				{
					// save the failed request for a day only
					ee()->cache->save('software_registration', ee()->config->item('license_number'), 60*60*24, Cache::GLOBAL_SCOPE);
				}
				else
				{
					if ($registration != ee()->config->item('license_number'))
					{
						// may have been a server error, save the failed request for a day
						ee()->cache->save('software_registration', ee()->config->item('license_number'), 60*60*24, Cache::GLOBAL_SCOPE);
					}
					else
					{
						// keep for a week
						ee()->cache->save('software_registration', $registration, 60*60*24*7, Cache::GLOBAL_SCOPE);
					}
				}

			}
		}

		// hard fail only when no valid license is entered or it doesn't even match a valid pattern
		if (ee()->config->item('license_number') == '' OR ! valid_license_pattern(ee()->config->item('license_number')))
		{
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * EE Version Check function
	 *
	 * Checks the current version of ExpressionEngine available from EllisLab
	 *
	 * @access	private
	 * @return	string
	 */
	public function get_version_info()
	{
		// Attempt to grab the local cached file
		$cached = ee()->cache->get('current_version', Cache::GLOBAL_SCOPE);

		if ( ! $cached)
		{
			$version_file = array();

			if ( ! $version_info = $this->_do_ping('http://versions.ellislab.com/versions_ee2.txt'))
			{
				$version_file['error'] = TRUE;
			}
			else
			{
				$version_info = explode("\n", trim($version_info));

				if (empty($version_info))
				{
					$version_file['error'] = TRUE;
				}
				else
				{
					foreach ($version_info as $version)
					{
						$version_file[] = explode('|', $version);
					}
				}
			}

			// Cache version information for a day
			ee()->cache->save(
				'current_version',
				$version_file,
				60 * 60 * 24,
				Cache::GLOBAL_SCOPE
			);
		}
		else
		{
			$version_file = $cached;
		}

		// one final check for good measure
		if ( ! $this->_is_valid_version_file($version_file))
		{
			return FALSE;
		}

		if (isset($version_file['error']) && $version_file['error'] == TRUE)
		{
			return FALSE;
		}

		return $version_file;
	}

	// --------------------------------------------------------------------

	/**
	 * Is developer environment?
	 *
	 * Bypasses registration for non-public / in development sites
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _developer_domain_bypass($domains)
	{
		if ( ! is_array($domains))
		{
			$domains = array($domains);
		}

		$dev_tlds = 0;

		foreach ($domains as $domain)
		{
			// I'd user filter_var() here, but FILTER_VALIDATE_URL will not
			// validate internationalized URLs that contain non-ASCII characters
			if (strncasecmp($domain, 'http', 4) !== 0)
			{
				$domain = 'http://'.$domain;
			}

			$parsed_url = parse_url($domain);

			if ($parsed_url === FALSE OR ! isset($parsed_url['host']))
			{
				$dev_tlds++;
				continue;
			}

			$parts = explode('.', $parsed_url['host']);
			$tld = strtoupper(end($parts));

			// ignore anything that isn't a valid TLD
			$valid_tlds = $this->_get_valid_tld_array();

			if ( ! in_array($tld, $valid_tlds))
			{
				$dev_tlds++;
			}
		}

		return ($dev_tlds == count($domains));
	}

	// --------------------------------------------------------------------

	/**
	 * Validate version file
	 * Prototype:
	 *  0 =>
	 *    array
	 *      0 => string '2.1.0' (length=5)
	 *      1 => string '20100805' (length=8)
	 *      2 => string 'normal' (length=6)
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _is_valid_version_file($version_file)
	{
		if ( ! is_array($version_file))
		{
			return FALSE;
		}

		foreach ($version_file as $version)
		{
			if ( ! is_array($version) OR count($version) != 3)
			{
				return FALSE;
			}

			foreach ($version as $val)
			{
				if ( ! is_string($val))
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Do the Ping
	 *
	 * @param string		$url		The URL to ping
	 * @param array			$payload	The POST payload, if any
	 * @return string|bool	The response from the web server or FALSE on failure to connect
	 **/
	private function _do_ping($url, $payload = null)
	{
		$target = parse_url($url);

		$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);

		if ( ! $fp)
		{
			return FALSE;
		}

		if ( ! empty($payload))
		{
			$postdata = http_build_query($payload);

			fputs($fp, "POST {$target['path']} HTTP/1.1\r\n");
			fputs($fp, "Host: {$target['host']}\r\n");
			fputs($fp, "User-Agent: EE/EllisLab PHP/\r\n");
			fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-Length: ".strlen($postdata)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, "{$postdata}\r\n\r\n");
		}
		else
		{
			fputs($fp,"GET {$url} HTTP/1.1\r\n" );
			fputs($fp,"Host: {$target['host']}\r\n");
			fputs($fp,"User-Agent: EE/EllisLab PHP/\r\n");
			fputs($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n");
			fputs($fp,"Connection: close\r\n\r\n");
		}

		$headers = TRUE;
		$response = '';
		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);

			if ($headers === FALSE)
			{
				$response .= $line;
			}
			elseif (trim($line) == '')
			{
				$headers = FALSE;
			}
		}

		fclose($fp);

		return $response;
	}

	// --------------------------------------------------------------------

	/**
	 * Return the valid TLD list as an array
	 * - works in conjunction with get_valid_tld_list()
	 *
	 * @return void
	 **/
	private function _get_valid_tld_array()
	{
		$tld_list = $this->_get_valid_tld_list();
		$tld_array = explode("\n", trim($tld_list));

		if (strncmp($tld_array[0], '#', 1) === 0)
		{
			unset($tld_array[0]);
		}

		return $tld_array;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Valid TLD list
	 *
	 * Cut and pasted from http://data.iana.org/TLD/tlds-alpha-by-domain.txt
	 * to make this function easy to update. We'll construct the array on the fly.
	 *
	 * When updating, make sure the list is not indented or no TLDs will match.
	 * We break on simple newlines only, and use in_array(), both for speed.
	 *
	 * @return string
	 **/
	private function _get_valid_tld_list()
	{
		return <<<EOFFENHEIMER
# Version 2014032601, Last Updated Thu Mar 27 07:07:01 2014 UTC
AC
ACADEMY
ACTOR
AD
AE
AERO
AF
AG
AGENCY
AI
AL
AM
AN
AO
AQ
AR
ARPA
AS
ASIA
AT
AU
AW
AX
AXA
AZ
BA
BAR
BARGAINS
BB
BD
BE
BERLIN
BEST
BF
BG
BH
BI
BID
BIKE
BIZ
BJ
BLUE
BM
BN
BO
BOUTIQUE
BR
BS
BT
BUILD
BUILDERS
BUZZ
BV
BW
BY
BZ
CA
CAB
CAMERA
CAMP
CARDS
CAREERS
CAT
CATERING
CC
CD
CENTER
CEO
CF
CG
CH
CHEAP
CHRISTMAS
CI
CK
CL
CLEANING
CLOTHING
CLUB
CM
CN
CO
CODES
COFFEE
COLOGNE
COM
COMMUNITY
COMPANY
COMPUTER
CONDOS
CONSTRUCTION
CONTRACTORS
COOL
COOP
CR
CRUISES
CU
CV
CW
CX
CY
CZ
DANCE
DATING
DE
DEMOCRAT
DIAMONDS
DIRECTORY
DJ
DK
DM
DNP
DO
DOMAINS
DZ
EC
EDU
EDUCATION
EE
EG
EMAIL
ENTERPRISES
EQUIPMENT
ER
ES
ESTATE
ET
EU
EVENTS
EXPERT
EXPOSED
FARM
FI
FISH
FJ
FK
FLIGHTS
FLORIST
FM
FO
FOUNDATION
FR
FUTBOL
GA
GALLERY
GB
GD
GE
GF
GG
GH
GI
GIFT
GL
GLASS
GM
GN
GOV
GP
GQ
GR
GRAPHICS
GS
GT
GU
GUITARS
GURU
GW
GY
HK
HM
HN
HOLDINGS
HOLIDAY
HOUSE
HR
HT
HU
ID
IE
IL
IM
IMMOBILIEN
IN
INDUSTRIES
INFO
INK
INSTITUTE
INT
INTERNATIONAL
IO
IQ
IR
IS
IT
JE
JETZT
JM
JO
JOBS
JP
KAUFEN
KE
KG
KH
KI
KIM
KITCHEN
KIWI
KM
KN
KOELN
KP
KR
KRED
KW
KY
KZ
LA
LAND
LB
LC
LI
LIGHTING
LIMO
LINK
LK
LONDON
LR
LS
LT
LU
LUXURY
LV
LY
MA
MAISON
MANAGEMENT
MANGO
MARKETING
MC
MD
ME
MENU
MG
MH
MIL
MK
ML
MM
MN
MO
MOBI
MODA
MONASH
MP
MQ
MR
MS
MT
MU
MUSEUM
MV
MW
MX
MY
MZ
NA
NAGOYA
NAME
NC
NE
NET
NEUSTAR
NF
NG
NI
NINJA
NL
NO
NP
NR
NU
NYC
NZ
OKINAWA
OM
ONL
ORG
PA
PARTNERS
PARTS
PE
PF
PG
PH
PHOTO
PHOTOGRAPHY
PHOTOS
PICS
PINK
PK
PL
PLUMBING
PM
PN
POST
PR
PRO
PRODUCTIONS
PROPERTIES
PS
PT
PUB
PW
PY
QA
QPON
RE
RECIPES
RED
RENTALS
REPAIR
REPORT
REVIEWS
RICH
RO
RS
RU
RUHR
RW
SA
SB
SC
SD
SE
SEXY
SG
SH
SHIKSHA
SHOES
SI
SINGLES
SJ
SK
SL
SM
SN
SO
SOCIAL
SOHU
SOLAR
SOLUTIONS
SR
ST
SU
SUPPLIES
SUPPLY
SUPPORT
SV
SX
SY
SYSTEMS
SZ
TATTOO
TC
TD
TECHNOLOGY
TEL
TF
TG
TH
TIENDA
TIPS
TJ
TK
TL
TM
TN
TO
TODAY
TOKYO
TOOLS
TP
TR
TRADE
TRAINING
TRAVEL
TT
TV
TW
TZ
UA
UG
UK
UNO
US
UY
UZ
VA
VACATIONS
VC
VE
VENTURES
VG
VI
VIAJES
VILLAS
VISION
VN
VOTE
VOTING
VOTO
VOYAGE
VU
WANG
WATCH
WEBCAM
WED
WF
WIEN
WIKI
WORKS
WS
XN--3BST00M
XN--3DS443G
XN--3E0B707E
XN--45BRJ9C
XN--55QW42G
XN--55QX5D
XN--6FRZ82G
XN--6QQ986B3XL
XN--80AO21A
XN--80ASEHDB
XN--80ASWG
XN--90A3AC
XN--C1AVG
XN--CG4BKI
XN--CLCHC0EA0B2G2A9GCD
XN--D1ACJ3B
XN--FIQ228C5HS
XN--FIQ64B
XN--FIQS8S
XN--FIQZ9S
XN--FPCRJ9C3D
XN--FZC2C9E2C
XN--GECRJ9C
XN--H2BRJ9C
XN--I1B6B1A6A2E
XN--IO0A7I
XN--J1AMH
XN--J6W193G
XN--KPRW13D
XN--KPRY57D
XN--L1ACC
XN--LGBBAT1AD8J
XN--MGB9AWBF
XN--MGBA3A4F16A
XN--MGBAAM7A8H
XN--MGBAB2BD
XN--MGBAYH7GPA
XN--MGBBH1A71E
XN--MGBC0A9AZCG
XN--MGBERP4A5D4AR
XN--MGBX4CD0AB
XN--NGBC5AZD
XN--NQV7F
XN--NQV7FS00EMA
XN--O3CW4H
XN--OGBPF8FL
XN--P1AI
XN--PGBS0DH
XN--Q9JYB4C
XN--RHQV96G
XN--S9BRJ9C
XN--UNUP4Y
XN--WGBH1C
XN--WGBL6A
XN--XKC2AL3HYE2A
XN--XKC2DL3A5EE0H
XN--YFRO4I67O
XN--YGBI2AMMX
XN--ZFR164B
XXX
XYZ
YE
YT
ZA
ZM
ZONE
ZW
EOFFENHEIMER;
	}

	// --------------------------------------------------------------------
}
// END CLASS

/* End of file Pings.php */
/* Location: ./system/expressionengine/libraries/Pings.php */
