@extends('layouts.app')

@section('content')

<div class="container">
	<div class="entry-body">
		@if ( Session::has('wallet_create_failed') )
			<div class="container">
				<div class="row alert alert-info" style="display: block;">
					<a class="close" data-dismiss="alert">X</a>
					{!! Session::get('wallet_create_failed') !!}
				</div>
			</div>
		@endif
	  <form action="/created_wallet" method="post">
	  	<h4>Please enter a password:</h4>
	  	<div class="form-group">
		    <input class="form-control" name="walletPW" type="text">
		  </div>
		  <div class="form-group">
		    <input class="form-control btn btn-primary" type="submit">
		  </div>
	    @csrf
	  </form>
	  <div style="text-align: center;"><b>(DO NOT SHARE YOUR PRIVATE KEY NOR LOSE THIS, PLEAES MAKE A BACKUP! WE ARE NOT RESPONSIBLE FOR YOUR WALLET PRIVATE KEY OR YOUR WALLET PASSWORD)</b></div>
	</div>
</div>

@stop