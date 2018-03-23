@extends('layouts.app')

@section('content')

<div class="container">
	<div class="entry-body">
		<div class="info-holder">
		  <h4>Address: {{ $initLoginRet }}</h4>
		  <h4>Balance: {{ sprintf('%f', $initLoginBalanceRet) }}</h4>
		  <h4>Recent TX: <div class="recent_tx"></div></h4>
	  </div>
	  
  	<div class="form-group">
    	<h5>Amount To Send:</h5>
    	<input class="form-control" type="text" name="sendToAddrAmt">
    	<input class="form-control" type="hidden" name="currentAddr" @if (isset($initLoginRet)) value={{ $initLoginRet }} @endif>
    	<input class="form-control" type="hidden" name="loginWithPrivKey" @if (isset($privKey)) value={{ $privKey }} @endif>
    	<input class="form-control" type="hidden" name="userWalletPW" @if (isset($privKeyPW)) value={{ $privKeyPW }} @endif>
  	</div>
  	<div class="form-group">
    	<h5>Address To Send To:</h5>
    	<input class="form-control" type="text" name="sendToAddr">
    </div>
    <div class="form-group">
    	<button class="send_coins form-control btn btn-primary" type="button">Send</button>
  	</div>

	  <div class="recent_transactions_wrapper">
	  	<h3>Recent Transactions</h3>
	  	@if (isset($transaction_array))
	  		@foreach ($transaction_array as $transaction)
	  			<div class="transaction">
		  			<div>Type of transaction: {{ $transaction->category }}</div>
		  			<div>Transaction amount: {{ $transaction->amount }}</div>
		  			<div>Transaction confirmations: {{ $transaction->confirmations }}</div>
		  			<div>Transaction ID: {{ $transaction->txid }}</div>
		  			<div>Address involved in TX: {{ $transaction->address }}</div>
		  		</div>
	  		@endforeach
	  	@endif
	  </div>
	  <div class="disclaimer" style="text-align: center;">
	  	<i>Why does my address keep changing?!</i>
	  	<p>
	  		This is because each time you send or recieve a transaction, your address changes. 
	  		This site will display your most current address (at the time).
	  		Should you ever send from or recieve payments to an old address, the balance will be reflected on the new address, so no worries!
	  	</p>
	  </div>
	</div>
</div>

<script>
jQuery(document).ready(function() {
	$(".send_coins").click(function() {
		
		var currentAddr = $("input[name=currentAddr]").val();
		var sendToAddrAmt = $("input[name=sendToAddrAmt]").val();
		var sendToAddr = $("input[name=sendToAddr]").val();
		var privKey = $("input[name=loginWithPrivKey]").val();
		var userWalletPW = $("input[name=userWalletPW]").val();
		$.ajax({
			headers: {
      	'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  		},
		  url: "/send_coins",
		  type: "POST",
		  data: {'currentAddr' : currentAddr, 'sendToAddrAmt' : sendToAddrAmt, 'sendToAddr' : sendToAddr, 'privKey' : privKey, 'userWalletPW' : userWalletPW},
		  cache: false,
		  //dataType: "JSON",
		  success: function(data) {
		  	$("h4 .recent_tx").text(data);
				$("input[name=sendToAddrAmt]").val("");
				$("input[name=sendToAddr]").val("");
		  }
		});
	});
});
</script>

@stop