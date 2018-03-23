@extends('layouts.app')

@section('content')

<div class="container">
	<div class="entry-body">
		<div class="form-group">
		  <h4>Your wallet address is:</h4>
		  <textarea class="form-control" rows=2 style="width: 100%;">{{ $createWalletRet['walletAddr'] }}</textarea>
		</div>
		<div class="form-group"> 
	  	<h4>Your wallet private key is:</h4>
	  	<textarea class="form-control" rows=6 style="width: 100%;">{{ $createWalletRet['walletPrivKey'] }}</textarea>
	  </div>
	  <div class="text-danger"><b>(DO NOT SHARE YOUR PRIVATE KEY NOR LOSE THIS, PLEAES MAKE A BACKUP! WE ARE NOT RESPONSIBLE FOR YOUR WALLET PRIVATE KEY OR YOUR WALLET PASSWORD)</b></div>
	</div>
</div>

@stop