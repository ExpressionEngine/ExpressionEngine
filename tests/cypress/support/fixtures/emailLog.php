<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"site-id:",
	"member-id:",
	"member-name:",
	"ip-address:",
	"timestamp-min:",
	"timestamp-max:",
	"recipient:",
	"recipient-name:",
	"subject:",
	"message:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                    This help message
	--count          <number> The number of developer logs to generate
	--member-id      <number> The member_id to use
	--member-name    <string> The member_name to use
	--ip-address     <string> The ip_address to use
	--timestamp-min  <number> The minimum number of hours to subtract from "now"
	--timestamp-max  <number> The maximum number of hours to subtract from "now"
	--recipient      <string> The recipient to use
	--recipient-name <string> The recipient_name to use
	--subject        <string> The subject to use
	--message        <string> The message to use
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$member_id = isset($options['member-id']) && is_numeric($options['member-id']) ? (int) $options['member-id'] : 1;
$member_name = isset($options['member-name']) ? $options['member-name'] : 'admin';
$ip_address = isset($options['ip-address']) ? $options['ip-address'] : '127.0.0.1';
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months
$recipient = isset($options['recipient']) ? $options['recipient'] : FALSE;
$recipient_name = isset($options['recipient-name']) ? $options['recipient-name'] : FALSE;
$subject = isset($options['subject']) ? $options['subject'] : FALSE;
$message = isset($options['message']) ? $options['message'] : FALSE;

$recipients = array(
	"wes"    => "Wes Baker",
	"pascal" => "Pascal Kriete",
	"kevin"  => "Kevin Cupp",
	"daniel" => "Daniel Bingham",
	"quinn"  => "Quinn Chrzan",
	"seth"   => "Seth Barber",
	"james"  => "James Mathias",
	"robin"  => "Robin Sowell",
	"rick"   => "Rick Ellis",
	"derek"  => "Derek Jones",
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

for ($x = 0; $x < $count; $x++)
{
	$r_short = array_rand($recipients);
	$r_name = $recipients[$r_short];

	$fixture = ee('Model')->make('EmailConsoleCache');
	$fixture->member_id = $member_id;
	$fixture->member_name = $member_name;
	$fixture->ip_address = $ip_address;
	$fixture->cache_date = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");
	$fixture->recipient = ($recipient !== FALSE) ? $recipient : $r_short;
	$fixture->recipient_name = ($recipient_name !== FALSE) ? $recipient_name : $r_name;
	$fixture->subject = ($subject !== FALSE) ? $subject : $subjects[rand(0, count($subjects)-1)];
	$fixture->message = ($message !== FALSE) ? $message : $messages[rand(0, count($messages)-1)];
	$fixture->save();
}
