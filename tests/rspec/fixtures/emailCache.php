<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"ip-address:",
	"timestamp:",
	"timestamp-min:",
	"timestamp-max:",
	"from-name:",
	"from-email:",
	"recipient:",
	"cc:",
	"bcc:",
	"subject:",
	"message:",
	"total-sent:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                    This help message
	--count          <number> The number of developer logs to generate
	--timestamp      <number> The unix timestamp to use for cache_date
	--timestamp-min  <number> The minimum number of hours to subtract from "now"
	--timestamp-max  <number> The maximum number of hours to subtract from "now"
	--from-name      <string> The from_name to use
	--from-email     <string> The from_email to use
	--recipient      <string> The recipient to use (email address)
	--cc             <string> The CC to use (email address)
	--bcc            <string> The BCC to use (email address)
	--subject        <string> The subject to use
	--message        <string> The message to use
	--total-sent     <number> The total_sent to use
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$timestamp = isset($options['timestamp']) && is_numeric($options['timestamp']) ? (int) $options['timestamp'] : FALSE;
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months
$from_name = isset($options['from-name']) ? $options['from-name'] : FALSE;
$from_email = isset($options['from-email']) ? $options['from-email'] : FALSE;
$recipient = isset($options['recipient']) ? $options['recipient'] : FALSE;
$cc = isset($options['cc']) ? $options['cc'] : FALSE;
$bcc = isset($options['bcc']) ? $options['bcc'] : FALSE;
$subject = isset($options['subject']) ? $options['subject'] : FALSE;
$message = isset($options['message']) ? $options['message'] : FALSE;
$total_sent = isset($options['total-sent']) && is_numeric($options['total-sent']) ? (int) $options['total-sent'] : NULL;

$emails = array(
	"wes.baker@ellislab.com"      => "Wes Baker",
	"pascal.kriete@ellislab.com"  => "Pascal Kriete",
	"kevin.cupp@ellislab.com"     => "Kevin Cupp",
	"daniel.bingham@ellislab.com" => "Daniel Bingham",
	"quinn.chrzan@ellislab.com"   => "Quinn Chrzan",
	"seth.barber@ellislab.com"    => "Seth Barber",
	"james.mathias@ellislab.com"  => "James Mathias",
	"robin.sowell@ellislab.com"   => "Robin Sowell",
	"rick.ellis@ellislab.com"     => "Rick Ellis",
	"derek.jones@ellislab.com"    => "Derek Jones",
);

$subjects = array(
	'What does the fox say?',
	'Fee Fie Fo Fum',
	'About your latest commit...',
	'Once upon a time...',
	'CP 3.0: No pink?',
);

$messages = array(
	"<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Certe, nisi voluptatem tanti aestimaretis. Certe nihil nisi quod possit ipsum propter se iure laudari. Est enim effectrix multarum et magnarum voluptatum. Duo Reges: constructio interrete. Eadem nunc mea adversum te oratio est. Sin laboramus, quis est, qui alienae modum statuat industriae? </p>",
	"<p>Sin aliud quid voles, postea. Si enim ad populum me vocas, eum. Cur igitur easdem res, inquam, Peripateticis dicentibus verbum nullum est, quod non intellegatur? Si stante, hoc natura videlicet vult, salvam esse se, quod concedimus; Itaque fecimus. Unum nescio, quo modo possit, si luxuriosus sit, finitas cupiditates habere. Nam, ut sint illa vendibiliora, haec uberiora certe sunt. Quae cum magnifice primo dici viderentur, considerata minus probabantur. Iubet igitur nos Pythius Apollo noscere nosmet ipsos. Quae in controversiam veniunt, de iis, si placet, disseramus. </p>",
	"<p>Videsne quam sit magna dissensio? Cur id non ita fit? Si longus, levis. Uterque enim summo bono fruitur, id est voluptate. Quae cum praeponunt, ut sit aliqua rerum selectio, naturam videntur sequi; Quid, si etiam iucunda memoria est praeteritorum malorum? Nescio quo modo praetervolavit oratio. </p>",
	"<p>Facillimum id quidem est, inquam. Non est ista, inquam, Piso, magna dissensio. Non autem hoc: igitur ne illud quidem. Sed quid sentiat, non videtis. </p>",
	"<p>Sed nimis multa. Ut in geometria, prima si dederis, danda sunt omnia. Ego vero isti, inquam, permitto. Videamus igitur sententias eorum, tum ad verba redeamus. Sint modo partes vitae beatae. Ita multo sanguine profuso in laetitia et in victoria est mortuus. Estne, quaeso, inquam, sitienti in bibendo voluptas? Sed quod proximum fuit non vidit. Ut proverbia non nulla veriora sint quam vestra dogmata. Familiares nostros, credo, Sironem dicis et Philodemum, cum optimos viros, tum homines doctissimos. </p>",
);

$mailtypes = array(
	'xhtml' => 'html',
	'markdown' => 'html',
	'none' => 'text'
);

for ($x = 0; $x < $count; $x++)
{
	$f_email = array_rand($emails);
	$f_name = $emails[$f_email];

	$text_fmt = array_rand($mailtypes);
	$mailtype = $mailtypes[$text_fmt];

	$fixture = ee('Model')->make('EmailCache');
	if ($timestamp !== FALSE)
	{
		$fixture->cache_date = $timestamp;
	}
	else
	{
		$fixture->cache_date = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");
	}
	$fixture->from_name = ($from_name !== FALSE) ? $from_name : $f_name;
	$fixture->from_email = ($from_email !== FALSE) ? $from_email : $f_email;
	$fixture->recipient = ($recipient !== FALSE) ? $recipient : array_rand($emails);
	$fixture->cc = ($cc !== FALSE) ? $cc : array_rand($emails);
	$fixture->bcc = ($bcc !== FALSE) ? $bcc : array_rand($emails);
	$fixture->subject = ($subject !== FALSE) ? $subject : $subjects[rand(0, count($subjects)-1)];
	$fixture->message = ($message !== FALSE) ? $message : $messages[rand(0, count($messages)-1)];
	$fixture->total_sent = ($total_sent === NULL) ? rand(1, 250) : $total_sent;

	$fixture->plaintext_alt = '';
	$fixture->mailtype = $mailtype;
	$fixture->text_fmt = $text_fmt;
	$fixture->recipient_array = array();
	$fixture->attachments = array();
	$fixture->save();
}
