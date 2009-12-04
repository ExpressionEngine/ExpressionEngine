<html>
<head><title>Email Test</title><head>
<style type="text/css">

body { 
background-color:	#ffffff; 
margin:				20px; 
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			12px;
color:				#000;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		bold;
}

p {
margin-top: 12px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
}

li {
list-style:			square;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
margin-bottom:		4px;
color: 				#000;
}

.error {
margin-top: 12px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
color:  #990000;
}

.success {
margin-top: 12px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
color:  #339900;
}

</style>


<body>

<h1>Email Test Script</h1>

<?php
	error_reporting(0);
	
	if ( ! isset($_POST['sendit']))
	{
		email_form();
	}
	else
	{
		$required = array('recipient', 'subject', 'message');
	
		foreach ($required as $val)
		{
			if ( ! isset($_POST[$val]) OR $_POST[$val] == '')
			{
				echo "<div class='error'>Error: You must fill out all the form fields</div>";
				email_form();
				footer();
				exit;
			}
		}
	
		sendit();
	}
	
	footer();
	
	
	function email_form()
	{
		$action 	= ( ! isset($_SERVER['PHP_SELF']))	? 'email_test.php' : htmlentities($_SERVER['PHP_SELF']); 
		$recipient	= ( ! isset($_POST['recipient']))	? '' : $_POST['recipient']; 
		$subject 	= ( ! isset($_POST['subject']))		? '' : $_POST['subject']; 
		$message 	= ( ! isset($_POST['message']))		? '' : $_POST['message']; 
	
	?>	
		<form method="post" action="<?php echo $action; ?>">
		<input type="hidden" name="sendit" value="true" />
		<p>Email Address<br /><input type="text" name="recipient" value="<?php echo htmlentities($recipient); ?>" size="32" /></p>
		<p>Email Subject<br /><input type="text" name="subject" value="<?php echo htmlentities($subject); ?>"  size="32" /></p>
		<p>Email Message<br /><textarea name="message" cols="30" rows="10" ><?php echo htmlentities($message); ?></textarea></p>
		<p><input type="submit" value=" Send Email " /></p>
		</form>
	<?php
	}
	
	function sendit()
	{
		if ( ! mail($_POST['recipient'], $_POST['subject'], $_POST['message']))
		{
			echo "<p>Error: Unable to send your email</p>";
		}
		else
		{
			echo "<div class='success'><p>No PHP errors were encountered, which indicates that PHP correctly attempted to send your email message.</p></div>
				  <p>If you do not receive the email it could be due to one of the following problems:</p>
				  <ul>
				  <li>Your hosting provider might not permit email to be sent using PHP mail</li>
				  <li>The path to Sendmail is incorrect in your server's PHP ini file</li>
				  <li>Your server does not have Sendmail configured correctly.</li>
				  <li>The recipient's email server has spam blocking software which is incorrectly identifying messages send using PHP as spam</li>
				  </ul>
				  ";
		}
	}
	
	
	function footer()
	{
		echo "</body></html>";
	}
	
?>