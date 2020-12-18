<div class="glossary-wrap">
	<ul class="arrow-list">
		<li><a href="">business</a></li>
		<li><a href="">receiver_email</a></li>
		<li><a href="">receiver_id</a></li>
		<li><a href="">item_name</a></li>
		<li><a href="">item_number</a></li>
		<li><a href="">quantity</a></li>
		<li><a href="">invoice</a></li>
		<li><a href="">memo</a></li>
		<li><a href="">tax</a></li>
		<li><a href="">mc_gross</a></li>
	</ul>
	<ul class="arrow-list">
		<li><a href="">mc_fee</a></li>
		<li><a href="">mc_currency</a></li>
		<li><a href="">first_name</a></li>
		<li><a href="">last_name</a></li>
		<li><a href="">member_id</a></li>
		<li><a href="">screen_name</a></li>
		<li><a href="">payer_business_name</a></li>
		<li><a href="">payer_id</a></li>
		<li><a href="">payer_email</a></li>
		<li><a href="">payer_status</a></li>
	</ul>
	<ul class="arrow-list">
		<li><a href="">address_name</a></li>
		<li><a href="">address_street</a></li>
		<li><a href="">address_country_code</a></li>
		<li><a href="">address_city</a></li>
		<li><a href="">address_state</a></li>
		<li><a href="">address_zip</a></li>
		<li><a href="">address_country</a></li>
		<li><a href="">address_status</a></li>
		<li><a href="">verify_sign</a></li>
		<li><a href="">payment_gross</a></li>
	</ul>
	<ul class="arrow-list">
		<li><a href="">payment_fee</a></li>
		<li><a href="">payment_status</a></li>
		<li><a href="">payment_type</a></li>
		<li><a href="">payment_date</a></li>
		<li><a href="">txn_id</a></li>
		<li><a href="">txn_type</a></li>
		<li><a href="">option_name1</a></li>
		<li><a href="">option_selection1</a></li>
		<li><a href="">option_name2</a></li>
		<li><a href="">option_selection2</a></li>
	</ul>
</div>
<script type="text/javascript">

$(document).ready(function () {

	$('.glossary-wrap a').click(function(){
		$('textarea[name="email_body"]').insertAtCursor("{"+$(this).text()+"}");
		return false;
	});
});

</script>
