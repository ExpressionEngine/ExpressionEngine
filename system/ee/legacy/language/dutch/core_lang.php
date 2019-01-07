<?php

$lang = array(

/* General word list */
'and' => 'en',

'and_n_others' => 'en %d anderen...',

'at' => 'op',

'auto_redirection' => 'U wordt automatisch omgeleid in %x seconden',

'back' => 'Terug',

'by' => 'door',

'click_if_no_redirect' => 'Klik hier als u niet automatisch wordt omgeleid',

'disabled' => 'disabled',

'dot' => 'punt',

'enabled' => 'enabled',

'encoded_email' => '(JavaScript moet zijn ingeschakeld om dit e-mailadres te bekijken)',

'first' => 'Eerste',

'id' => 'ID',

'last' => 'Laatste',

'next' => 'Volgende',

'no' => 'Nee',

'not_authorized' => 'U bent niet gemachtigd om deze actie uit te voeren',

'not_available' => 'Niet beschikbaar',

'of' => 'van',

'off' => 'uit',

'on' => 'aan',

'or' => 'of',

'pag_first_link' => '&lsaquo; Eerste',

'pag_last_link' => 'Laatste &rsaquo;',

'page' => 'Pagina',

'preference' => 'Voorkeur',

'prev' => 'Vorige',

'return_to_previous' => 'Terugkeren naar de Vorige Pagina',

'search' => 'Zoeken',

'setting' => 'Instelling',

'site_homepage' => 'Site Homepagina',

'submit' => 'Submit',

'system_off_msg' => 'Deze site is momenteel inactief.',

'thank_you' => 'Bedankt!',

'update' => 'Update',

'updating' => 'Updating',

'yes' => 'Ja',


/* Errors */
'captcha_incorrect' => 'U hebt het woord niet exact ingediend zoals het in de afbeelding wordt weergegeven',

'captcha_required' => 'U moet het woord inzenden dat in de afbeelding wordt weergegeven',

'checksum_changed_accept' => 'Wijzigingen Accepteren',

'checksum_changed_warning' => 'Een of meer kernbestanden zijn gewijzigd:',

'checksum_email_message' => 'ExpressionEngine heeft de wijziging van een kernbestand gedetecteerd op: {url}

De volgende bestanden worden beïnvloed:
{changed}

Als u deze wijzigingen hebt aangebracht, accepteert u de wijzigingen op de startpagina van het bedieningspaneel. Als u deze bestanden niet hebt gewijzigd, kan dit wijzen op een hackpoging. Controleer de bestanden op verdachte inhoud (JavaScript of iFrames) en zie: '.DOC_URL.'troubleshooting / error_messages / expressionengine_has_detected_the_modification_of_a_core_file.html',

'checksum_email_subject' => 'Een kernbestand is aangepast op uw site.',

'csrf_token_expired' => 'Dit formulier is verlopen. Vernieuw en probeer het opnieuw.',

'current_password_incorrect' => 'Uw huidige wachtwoord is niet correct ingediend.',

'current_password_required' => 'Uw huidige wachtwoord is verplicht.',

'curl_not_installed' => 'cURL is niet geïnstalleerd op uw server',

'error' => 'Error',

'file_not_found' => 'Bestand %s bestaat niet.',

'general_error' => 'De volgende fouten zijn aan het licht gekomen',

'generic_fatal_error' => 'Er is iets misgegaan en deze URL kan op dit moment niet worden verwerkt.',

'invalid_action' => 'De actie die u heeft aangevraagd is ongeldig.',

'invalid_url' => 'De URL die u hebt verzonden, is niet geldig.',

'missing_encryption_key' => 'U hebt geen waarden ingesteld voor <code>%s</code> in uw config.php. Dit kan uw installatie openlaten voor beveiligingskwetsbaarheden. Herstel de sleutels of <a href="%s">contact op met ondersteuning</a> voor hulp.',

'missing_mime_config' => 'Kan de whitelist van het type mime niet importeren: het bestand %s bestaat niet of kan niet worden gelezen.',

'new_version_error' => 'Er is een onverwachte fout opgetreden bij het downloaden van het huidige ExpressionEngine-versienummer. Zie dit <a href="%s" rel="external noreferrer">document voor het oplossen van problemen</a> voor meer informatie.',

'nonexistent_page' => 'De door u opgevraagde pagina is niet gevonden',

'redirect_xss_fail' => 'De link waarnaar u wordt doorgestuurd bevat mogelijk kwaadaardige of gevaarlijke code. We raden u aan op de knop Vorige te klikken en %s te e-mailen om de link te melden die dit bericht heeft gegenereerd.',

'submission_error' => 'Het formulier dat u heeft ingediend bevat de volgende fouten',

'theme_folder_wrong' => 'Het pad van je themamap is onjuist. Ga naar <a href="%s">URL- en padinstellingen</a> en vink het <mark>Thema\'s pad</mark>aan en<mark>Thema\'s URL</mark>.',

'unable_to_load_field_type' => 'Kan aangevraagd veldtype bestand niet laden: %s.<br/> Bevestig dat het bestandstype bestand zich bevindt in de /system/user/addons/map',

'unwritable_cache_folder' => 'Uw cachemap beschikt niet over de juiste machtigingen.<br/>Op te lossen: stel de cachemap (/system/user/cache/) in op 777 (of een equivalent voor uw server).',

'unwritable_config_file' => 'Uw configuratiebestand heeft niet de juiste rechten. <br/>Op te lossen: stel de configuratiebestanden (/'.SYSDIR.'/user/config/config.php) in op 666 (of equivalent voor uw server).',

'version_mismatch' => 'De versie van uw ExpressionEngine-installatie (%s) komt niet overeen met de gerapporteerde versie (% s). <a href="'.DOC_URL.'installation/update.html" rel="external">Update uw installatie van ExpressionEngine opnieuw</a>.',


/* Member Groups */
'banned' => 'Verboden',

'guests' => 'Gasten',

'members' => 'Members',

'pending' => 'In afwachting',

'super_admins' => 'Super Admins',


/* Template.php */
'error_fix_module_processing' => 'Controleer of de module \'%x\' is geïnstalleerd en dat \'%y\' een beschikbare methode van de module is',

'error_fix_syntax' => 'Corrigeer de syntaxis in uw sjabloon.',

'error_invalid_conditional' => 'U hebt een ongeldige voorwaarde in uw sjabloon. Controleer uw conditionals voor een niet-gesloten string, ongeldige operatoren, een ontbrekende }, of een ontbrekende {/if}.',

'error_layout_too_late' => 'Plug-in of module-tag gevonden vóór lay-outverklaring. Verplaats de lay-outtag naar de bovenkant van uw sjabloon.',

'error_multiple_layouts' => 'Meerdere lay-outs gevonden, zorg er voor dat u slechts één lay-outtag per sjabloon heeft',

'error_tag_module_processing' => 'De volgende tag kan niet worden verwerkt:',

'error_tag_syntax' => 'De volgende tag kan niet worden verwerkt:',

'layout_contents_reserved' => 'De naam "inhoud" is gereserveerd voor de sjabloongegevens en kan niet worden gebruikt als een lay-outvariabele (dat wil zeggen {layout: set name = "contents"} of {layout = "foo/bar" contents = ""}).',

'template_load_order' => 'Sjabloon laadvolgorde',

'template_loop' => 'U hebt een sjabloon-lus veroorzaakt door onjuist geneste sub-sjablonen (\'%s\' recursief genoemd)',


/* Email */
'error_sending_email' => 'Kan momenteel geen e-mail verzenden.',

'forgotten_email_sent' => 'Als dit e-mailadres aan een account is gekoppeld, zijn er zojuist instructies voor het opnieuw instellen van uw wachtwoord per e-mail naar u verzonden.',

'no_email_found' => 'Het e-mailadres dat u heeft opgegeven, is niet gevonden in de database.',

'password_has_been_reset' => 'Your password was reset and a new one has been emailed to you.',

'password_reset_flood_lock' => 'You have tried to reset your password too many times today. Please check your inbox and spam folders for previous requests, or contact the site administrator.',

'your_new_login_info' => 'Login Informatie',


/* Timezone */
'invalid_date_format' => 'Het datumnotatie dat je hebt ingediend, is ongeldig.',

'invalid_timezone' => 'De tijdzone die u heeft opgegeven, is ongeldig.',

'no_timezones' => 'Geen Tijdzones',

'select_timezone' => 'Selecteer Tijdzone',


/* Date */
'singular' => 'één',

'less_than' => 'minder dan',

'about' => 'over',

'past' => '%s geleden',

'future' => 'in %s',

'ago' => '%x geleden',

'year' => 'jaar',

'years' => 'jaren',

'month' => 'maand',

'months' => 'maanden',

'fortnight' => 'fortnight',

'fortnights' => 'fortnights',

'week' => 'week',

'weeks' => 'weken',

'day' => 'dag',

'days' => 'dagen',

'hour' => 'uur',

'hours' => 'uren',

'minute' => 'minuut',

'minutes' => 'minuten',

'second' => 'seconde',

'seconds' => 'seconden',

'am' => 'am',

'pm' => 'pm',

'AM' => 'AM',

'PM' => 'PM',

'Sun' => 'Zon',

'Mon' => 'Maa',

'Tue' => 'Din',

'Wed' => 'Woe',

'Thu' => 'Don',

'Fri' => 'Vrij',

'Sat' => 'Zat',

'Su' => 'Z',

'Mo' => 'M',

'Tu' => 'D',

'We' => 'W',

'Th' => 'D',

'Fr' => 'V',

'Sa' => 'Z',

'Sunday' => 'Zondag',

'Monday' => 'Maandag',

'Tuesday' => 'Dinsdag',

'Wednesday' => 'Woensdag',

'Thursday' => 'Donderdag',

'Friday' => 'Vrijdag',

'Saturday' => 'Zaterdag',

'Jan' => 'Jan',

'Feb' => 'Feb',

'Mar' => 'Maa',

'Apr' => 'Apr',

'May' => 'Mei',

'Jun' => 'Jun',

'Jul' => 'Jul',

'Aug' => 'Aug',

'Sep' => 'Sep',

'Oct' => 'Okt',

'Nov' => 'Nov',

'Dec' => 'Dec',

'January' => 'Januari',

'February' => 'Februari',

'March' => 'Maart',

'April' => 'April',

'May_l' => 'Mei',

'June' => 'Juni',

'July' => 'Juli',

'August' => 'Augustus',

'September' => 'September',

'October' => 'Oktober',

'November' => 'November',

'December' => 'December',

'UM12' => '(UTC -12:00) Baker/Howland Island',

'UM11' => '(UTC -11:00) Niue',

'UM10' => '(UTC -10:00) Hawaii-Aleutian Standard Time, Cook Islands, Tahiti',

'UM95' => '(UTC -9:30) Marquesas Islands',

'UM9' => '(UTC -9:00) Alaska Standard Time, Gambier Islands',

'UM8' => '(UTC -8:00) Pacific Standard Time, Clipperton Island',

'UM7' => '(UTC -7:00) Mountain Standard Time',

'UM6' => '(UTC -6:00) Central Standard Time',

'UM5' => '(UTC -5:00) Eastern Standard Time, Western Caribbean Standard Time',

'UM45' => '(UTC -4:30) Venezuelan Standard Time',

'UM4' => '(UTC -4:00) Atlantic Standard Time, Eastern Caribbean Standard Time',

'UM35' => '(UTC -3:30) Newfoundland Standard Time',

'UM3' => '(UTC -3:00) Argentina, Brazil, French Guiana, Uruguay',

'UM2' => '(UTC -2:00) South Georgia/South Sandwich Islands',

'UM1' => '(UTC -1:00) Azores, Cape Verde Islands',

'UTC' => '(UTC) Greenwich Mean Time, Western European Time',

'UP1' => '(UTC +1:00) Central European Time, West Africa Time',

'UP2' => '(UTC +2:00) Central Africa Time, Eastern European Time, Kaliningrad Time',

'UP3' => '(UTC +3:00) East Africa Time, Arabia Standard Time',

'UP35' => '(UTC +3:30) Iran Standard Time',

'UP4' => '(UTC +4:00) Moscow Time, Azerbaijan Standard Time',

'UP45' => '(UTC +4:30) Afghanistan',

'UP5' => '(UTC +5:00) Pakistan Standard Time, Yekaterinburg Time',

'UP55' => '(UTC +5:30) Indian Standard Time, Sri Lanka Time',

'UP575' => '(UTC +5:45) Nepal Time',

'UP6' => '(UTC +6:00) Bangladesh Standard Time, Bhutan Time, Omsk Time',

'UP65' => '(UTC +6:30) Cocos Islands, Myanmar',

'UP7' => '(UTC +7:00) Krasnoyarsk Time, Cambodia, Laos, Thailand, Vietnam',

'UP8' => '(UTC +8:00) Australian Western Standard Time, Beijing Time, Irkutsk Time',

'UP875' => '(UTC +8:45) Australian Central Western Standard Time',

'UP9' => '(UTC +9:00) Japan Standard Time, Korea Standard Time, Yakutsk Time',

'UP95' => '(UTC +9:30) Australian Central Standard Time',

'UP10' => '(UTC +10:00) Australian Eastern Standard Time, Vladivostok Time',

'UP105' => '(UTC +10:30) Lord Howe Island',

'UP11' => '(UTC +11:00) Magadan Time, Solomon Islands, Vanuatu',

'UP115' => '(UTC +11:30) Norfolk Island',

'UP12' => '(UTC +12:00) Fiji, Gilbert Islands, Kamchatka Time, New Zealand Standard Time',

'UP1275' => '(UTC +12:45) Chatham Islands Standard Time',

'UP13' => '(UTC +13:00) Samoa Time Zone, Phoenix Islands Time, Tonga',

'UP14' => '(UTC +14:00) Line Islands',

);

// EOF
