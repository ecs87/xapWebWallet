@extends('layouts.app')

@section('content')

<div class="container">
	<div class="entry-body">
		<form action="/lookupTX" method="post">
			<div class="form-group"> 
		  	<h4>Transaction ID:</h4>
		  	<input class="form-control" name="transaction_id" type="text">
		  </div>
	  	<div class="form-group">
	    	<input class="form-control btn btn-primary" type="submit">
	  	</div>
	    @csrf
		</form>
		<div class="transactions-wrapper">
			@if (isset($tx_info))
				<h3 class="transaction_id">Transaction ID: <p>{{ $tx_info['txOut']['txid'] }}</p></h3>
				<div class="transactions-holder">
					<h4>Inputs</h4>
					@for ($i = 0; $i < count($tx_info['txIn']['addr']); $i++)
						<div class="txOutHolder"> {{ $tx_info['txIn']['addr'][$i] }} <b>{{ $tx_info['txIn']['value'][$i] }} XAP</b> </div>
					@endfor
				</div>
				<div class="transactions-holder">
					<h4>Outputs</h4>
					@for ($i = 0; $i < count($tx_info['txOut']['values']); $i++)
						@foreach ($tx_info['txOut']['addresses'][$i] as $txOutAddr)
							<div class="txOutHolder"> {{ $txOutAddr }} <b>{{ $tx_info['txOut']['values'][$i] }} XAP</b> </div>
						@endforeach
					@endfor
				</div>
			@endif
		</div>
	</div>
</div>

@stop
