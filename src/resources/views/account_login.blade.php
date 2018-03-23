@extends('layouts.app')

@section('content')

<div class="container">
  <div class="entry-body">
		@if ( Session::has('wallet_login_failed') )
			<div class="container">
				<div class="row alert alert-info" style="display: block;">
					<a class="close" data-dismiss="alert">X</a>
					{!! Session::get('wallet_login_failed') !!}
				</div>
			</div>
		@endif  	
    <form action="/account" method="post">
    	<div class="form-group">
    		<h4>Please enter your XAP Web Wallet private key:</h4>
	    	<textarea class="form-control" name="loginWithPrivKey" rows=8 style="width: 100%;"></textarea>
	  	</div>
    	<div class="form-group">
    		<h4>Please enter your XAP Web Wallet password:</h4>
	    	<input class="form-control" name="userWalletPW" type="text">
	  	</div>
	  	<div class="form-group">
	    	<input class="form-control btn btn-primary" type="submit">
	  	</div>
	    @csrf
    </form>
  </div>
</div>

@stop