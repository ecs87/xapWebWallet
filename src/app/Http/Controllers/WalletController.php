<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

class WalletController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
			return view('welcome');
    }
    public function account_login() {
    	return view('account_login');
    }
    public function lookup_transaction() {
			return view('lookuptx');
    }
    public function send_coins(Request $request) {
    	$walletFunctions = new walletFunctions();
    	$walletVerification = new walletVerification();
    	if (!isset($request->privKey) || empty($request->privKey))
    		return "Failed"; //we have no time for script kiddies
    	$account_privkey = json_decode($walletFunctions->dumpPrivKey($request->currentAddr))->result; //privKey for address currently showing in /account
    	$verifyPrivKey = $walletVerification->verifyPayload($request->privKey, $request->userWalletPW); //privKey for original address from /create_wallet
			//Get all labels
			$all_accounts = json_decode($walletFunctions->listAccounts())->result;
			$all_labels = array();
			foreach ($all_accounts as $account_label => $account_label_value)
				$all_labels[] = $account_label;	
    	//get all addresses belonging to those labels
    	$all_labels_addrs = array();
			foreach ($all_labels as $account_label) {
				$allAddrsAssocWithCurrentLabel = $walletFunctions->getAddressesByAccount($account_label);
				$all_labels_addrs[$account_label] = json_decode($allAddrsAssocWithCurrentLabel)->result;
			}
    	//get all privkeys belonging to a label
    	$all_privKeys = array();
			foreach ($all_labels as $account_label) {
				$allAddrsAssocWithCurrentLabel = json_decode($walletFunctions->getAddressesByAccount($account_label))->result;
				foreach ($allAddrsAssocWithCurrentLabel as $currentLabelAddress)
					$all_privKeys[$account_label][] = json_decode($walletFunctions->dumpPrivKey($currentLabelAddress))->result;
			}
    	//determine if accound_privkey AND verifyPrivKey belong to a label
    	$verifyOne = false;
    	$verifyTwo = false;
    	
    	foreach ($all_privKeys as $account_label => $accountPrivKeyArray) {
    		foreach ($accountPrivKeyArray as $accountPrivKey) {
	    		if ($accountPrivKey === $account_privkey)
	    			$verifyOne = true;
	    		if ($accountPrivKey === $verifyPrivKey)
	    			$verifyTwo = true;
    		}
    	}
    	//if they BOTH don't belong to a label, return failed
    	if ($verifyOne !== true || $verifyTwo !== true || $account_privkey === null)
    		return "Failed";
    	$currentLabel = json_decode($walletFunctions->getLabelFromAddress($request->currentAddr))->result;
    	$ret = $walletFunctions->sendToAddr($currentLabel, $request->sendToAddr, (float)$request->sendToAddrAmt);
    	if (isset(json_decode($ret)->error))
    		return json_decode($ret)->error->message;
    	else if (isset(json_decode($ret)->result))
    		return json_decode($ret)->result;
    }
    
    /*
		//Outputs (where and how much)
		for ($i = 0; $i < count($txInfoRet['values']); $i++) {
			implode (", ", $txInfoRet['addresses'][$i]); //addresses (always going to be an array)
			$txInfoRet['values'][$i]; //values of each address that got coins
		}
    */
    
    public function get_transactionInfo(Request $request) {
    	$walletFunctions = new walletFunctions();
    	$txOut = $walletFunctions->getOutputsOfTx($request->transaction_id);
    	if ($txOut === false) //didn't get a response for a VALID tx id
    		return view('lookuptx');
    	$txIn = $walletFunctions->getInputsOfTx($request->transaction_id);
    	if ($txIn['addr'][0] === "Reward & fees")
    		$txIn['value'][0] = array_sum($txOut['values']);
    	$tx_info['txIn'] = $txIn;
    	$tx_info['txOut'] = $txOut;
    	return view('lookuptx', compact('tx_info'));
    }
    
    public function access_account(Request $request) {
    	if (!isset($request->loginWithPrivKey) || empty($request->loginWithPrivKey)) {
    		Session::flash('wallet_login_failed','A wallet private key is required.');
    		return redirect()->back(); 
    	}
    	else if (!isset($request->userWalletPW) || empty($request->userWalletPW)) {
				Session::flash('wallet_login_failed','A wallet password is required.');
				return redirect()->back(); 
    	}
    	$walletFunctions = new walletFunctions();
    	$privKey = $request->loginWithPrivKey;
    	$privKeyPW = $request->userWalletPW;
    	$initLoginRet = $walletFunctions->initLogin($privKey, $privKeyPW);
		//var_dump(json_decode(getBalance($new_wallet_addr))->result);
    	//need to get balance too!
    	if ($initLoginRet === false || $initLoginRet === null) {
    		sleep(5); //we have no time for spammers
    		Session::flash('wallet_login_failed','Verification Failed.');
    		return back();
    		//die();
    	}
    	else {
    		//if $initLoginRet is not in the array, then the privkey (account) does not exist on the server (must be imported or a new wallet created)
    		$all_accounts = json_decode($walletFunctions->listAccounts());
    		$all_transactions = json_decode($walletFunctions->listTransactions())->result;
    		
    		//first loop through all accounts
    		foreach ($all_accounts->result as $account_label => $account_value) {
    			$account_address = json_decode($walletFunctions->listAccountsAddress($account_label))->result;
    			$account_privkey = json_decode($walletFunctions->dumpPrivKey($account_address))->result;
    			/*
    			var_dump($account_address);
    			var_dump($account_label);
    			var_dump($account_privkey);
    			echo "</br>";
    			echo "</br>";
    			echo "</br>";
    			*/
    			if ($account_privkey === $initLoginRet) { //we've found a match in the current addresses!
    				$initLoginRet = $account_address;
    				$initLoginBalanceRet = json_decode($walletFunctions->getBalance($account_label))->result;
    		
		    		$transaction_array = array();	
		    		foreach ($all_transactions as $transaction) {
		    			if ($transaction->address == $initLoginRet)
		    				$transaction_array[] = $transaction;
		    		}

    				return view('account', compact('initLoginRet', 'initLoginBalanceRet', 'transaction_array', 'account_label', 'account_privkey', 'privKey', 'privKeyPW'));
    			}
    		}
    		//then if nothing was found in the current addresses, then loop through the groupings
    		$all_wallet_groupings_array = json_decode($walletFunctions->listAddressGroupings())->result;
    		//var_dump(json_encode($all_wallet_groupings_array)); die();
    		//var_dump($all_wallet_groupings_array); die();
    		$totalGroupedBalance = 0;
    		$account_label = $account_privkey = "";
    		$foundMatches = false;
    		foreach ($all_wallet_groupings_array as $all_wallet_groupings) {
    			foreach ($all_wallet_groupings as $wallet_grouping) {
    				$account_privkey = json_decode($walletFunctions->dumpPrivKey($wallet_grouping[0]))->result;
    				if ($account_privkey === $initLoginRet) { //if user-inputted private key exists in groupings
    					$account_label = $wallet_grouping[2];
    					$foundMatches = true;
    				}
    			}
    		}
    		
    		foreach ($all_wallet_groupings_array as $all_wallet_groupings) {
    			foreach ($all_wallet_groupings as $wallet_grouping) {
    				if (isset($wallet_grouping[2])) {
	    				if ($wallet_grouping[2] === $account_label) {
								$account_address = json_decode($walletFunctions->listAccountsAddress($account_label))->result;
								$initLoginRet = $account_address;
	    				}
    				}
    			}
    		}
    		
				if ($foundMatches === true) {
					$initLoginBalanceRet = json_decode($walletFunctions->getBalance($account_label))->result;
	    		$transaction_array = array();	
	    		foreach ($all_transactions as $transaction) {
	    			if ($transaction->account == $account_label)
	    				$transaction_array[] = $transaction;
	    		}
	    		return view('account', compact('initLoginRet', 'initLoginBalanceRet', 'transaction_array', 'account_label', 'account_privkey', 'privKey', 'privKeyPW'));
				}
				
    		sleep(5); //we have no time for spammers (obviously we're making them have no time for us either)
    		Session::flash('wallet_login_failed','Verification Failed.');
    		return back();
    	}
    }
    
    public function wallet_created(Request $request) {
    	if (!isset($request->walletPW) || empty($request->walletPW)) {
    		Session::flash('wallet_create_failed','A wallet password is required.');
    		return redirect()->back(); 
    	}
    	else if (strlen($request->walletPW) < 6) {
				Session::flash('wallet_create_failed','Your wallet password must be at least 6 characters.');
				return redirect()->back(); 
    	}
    	$walletFunctions = new walletFunctions();
    	$createWalletRet = $walletFunctions->createWallet($request->walletPW);
    	return view('created_wallet', compact('createWalletRet'));
    }
    public function create_new_wallet() {
    	return view('create_wallet');
    }
}

/******
*
* Account List (Loop)
*
******/
class walletVerification {
	public function verifyPayload($encryptedText, $userWalletPW) {
		$ret = self::AESDecrypt($encryptedText, $userWalletPW);
		return $ret;
	}
	public function createPayload($plaintext, $userWalletPW) {
		$ret = self::AESEncrypt($plaintext, $userWalletPW);
		return $ret;
	}
	private static function getAllAcctInfo() {
		$walletFunctions = new walletFunctions();
		$privKeyArray = $addressArray = array(); //reinitialize them to clear them out, we don't need duplicates
		$accountsArray = json_decode($walletFunctions->listAccounts())->result;
		foreach ($accountsArray as $accountLabel => $accountValue) {
			$address = json_decode($walletFunctions->listAccountsAddress($accountLabel))->result;
			$addressArray['addresses'][] = $address;
			$addressArray['privKeys'][] = json_decode($walletFunctions->dumpPrivKey($address))->result;
		}
		return($addressArray);
	}
  private function AESEncrypt($stringToEncrypt, $userWalletPW) {
		$encryptionMethod = "aes-128-gcm"; 
		$secretHash = $userWalletPW;
		$ivlen = openssl_cipher_iv_length($cipher=$encryptionMethod);
		$iv = openssl_random_pseudo_bytes($ivlen);

		$ciphertext_raw = openssl_encrypt($stringToEncrypt, $encryptionMethod, $secretHash, $options=OPENSSL_RAW_DATA, $iv, $tag);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $secretHash, $as_binary=true);
		$ciphertext = base64_encode( $iv.$hmac.$tag.$ciphertext_raw );
		return $ciphertext;
  }
  private function AESDecrypt($stringToDecrypt, $userWalletPW) {
		$encryptionMethod = "aes-128-gcm"; 
		$secretHash = $userWalletPW;
		$ivlen = openssl_cipher_iv_length($cipher=$encryptionMethod);
		
		$c = base64_decode($stringToDecrypt);
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len=32);
		$tag = substr($c, $ivlen + strlen($hmac), $taglen=16);
		$ciphertext_raw = substr($c, $ivlen+$sha2len+$taglen);
  	
  	$decryptedMessage = openssl_decrypt($ciphertext_raw, $encryptionMethod, $secretHash, $options=OPENSSL_RAW_DATA, $iv, $tag);
  	
		$calcmac = hash_hmac('sha256', $ciphertext_raw, $secretHash, $as_binary=true);
		if (hash_equals($hmac, $calcmac))
			return $decryptedMessage;
  }
}

/******
*
* General Wallet Functions
*
******/
class walletFunctions {
	public function initLogin($privKey, $userWalletPW) {
		$walletVerification = new walletVerification();
		$verification = $walletVerification->verifyPayload($privKey, $userWalletPW);
		return $verification;
	}
	
	public function getAccountFromAddr($addr) {
		$params = array($addr);
		return sendCmdToRPC("getaccount", $params);
	}
	
	public function getAddressesByAccount($account) {
		$params = array($account);
		return sendCmdToRPC("getaddressesbyaccount", $params);
	}
	
	public function createWallet($userWalletPW) {
		$alias = (string)time(); //could make this a user-inputted string...but should be unique, so I made this time()
		$params = array($alias);
		
		$all_accounts = self::listAccounts();
		$existingLabels = array();
		
		//Get all the labels of all the accounts, and see if this matches any of them, if so, increment the new label by 1
		foreach (json_decode($all_accounts) as $existingLabel => $existingLabelValue)
			$existingLabels[] = $existingLabel;
		if (in_array($alias, $existingLabels))
			$alias = $alias + 1;
		
		$walletRet = array();
		$new_wallet_addr = json_decode(sendCmdToRPC("getaccountaddress", $params))->result;
		$new_wallet_privKey = json_decode(self::dumpPrivKey($new_wallet_addr))->result;
		$walletRet['walletAddr'] = $new_wallet_addr;
		$walletRet['walletPrivKey'] = $new_wallet_privKey;
		
		$walletVerification = new walletVerification();
		$walletRet['walletPrivKey'] = $walletVerification->createPayload($walletRet['walletPrivKey'], $userWalletPW);
		
		return $walletRet;
	}
	
	public function getBalance($addr) {
		$params = array($addr);
		return sendCmdToRPC("getbalance", $params);
	}
	
	public function getLabelFromAddress($accountAddress) {
		$params = array($accountAddress);
		return sendCmdToRPC("getaccount", $params);
	}

	public function listAccounts() {
		$params = array();
		return sendCmdToRPC("listaccounts", $params);
	}

	public function listAccountsAddress($accountLabel) {
		$params = array($accountLabel);
		return sendCmdToRPC("getaccountaddress", $params);
	}
	
	public function listAddressGroupings() {
		$params = array();
		return sendCmdToRPC("listaddressgroupings", $params);
	}

	public function importWalletFromPrivKey($privKey) {
		$params = array($privKey);
		return sendCmdToRPC("importprivkey", $params);
	}
	
	public function dumpPrivKey($new_wallet_addr) {
		$params = array($new_wallet_addr);
		return sendCmdToRPC("dumpprivkey", $params);
	}
	
	public function getRawTransaction($txid) {
		$params = array($txid);
		$rawTXHex = json_decode(sendCmdToRPC("getrawtransaction", $params))->result;
		$params = array($rawTXHex);
		return sendCmdToRPC("decoderawtransaction", $params);
	}
	
	public function txOutRaw($txid) {		
		$txInfoArray = self::getRawTransaction($txid);
		$txInfoArray = json_decode($txInfoArray);
		$txInfoRet = array();
		if ($txInfoArray->result === null) //didn't get any output
			return false;
		$txInfoRet['txid'] = $txInfoArray->result->txid;
		//outputs
		for ($i = 0; $i < count($txInfoArray->result->vout); $i++) {
			$txAddrArray = array();
			if (isset(($txInfoArray->result->vout[$i])->scriptPubKey->addresses)) {
				foreach (($txInfoArray->result->vout[$i])->scriptPubKey->addresses as $address)
					$txInfoRet['addresses'][$i][] = $address;
			}
			else {
				$txInfoRet['addresses'][$i][] = "Non standard";
			}
			$txInfoRet['values'][$i] = $txInfoArray->result->vout[$i]->value;
			$txInfoRet['outputIndex'][$i] = $txInfoArray->result->vout[$i]->n;
		}
		return $txInfoRet;
	}
	
	public function txInRaw($txid) {		
		$txInfoArray = self::getRawTransaction($txid);
		$txInfoArray = json_decode($txInfoArray);
		//var_dump($txInfoArray);
		$vinArrayHex = array();
		//inputs
		foreach ($txInfoArray->result->vin as $txHex) {
			if (isset($txHex->coinbase)) {
				return false;
			}
			else {
				$vinArrayHex['txid'][] = $txHex->txid;
				$vinArrayHex['vout'][] = $txHex->vout;
			}
		}
		return $vinArrayHex;
	}
	
	public function getOutputsOfTx($txid) {
		$txInfoRet = self::txOutRaw($txid);
		$txInfoSend = array();
		return $txInfoRet;
	}

	public function getInputsOfTx($txid) {
		//Inputs (where and how much)
		$txInfoRet = self::txInRaw($txid);
		$txInfoInput = array();
		$txInputFinal = array();
		
		if ($txInfoRet === false) { //transaction input is a reward (cannot track)
			$txInputFinal['addr'][] = "Reward & fees";
			$txInputFinal['value'][] = 0;
			return $txInputFinal;
		}
		else { //transaction input is normal (can track)
			foreach ($txInfoRet['txid'] as $txIn) {
				$txInfoInput[] = self::txOutRaw($txIn);
			}

			for ($i = 0; $i < count($txInfoInput); $i++) {	
				if (isset($txInfoInput[$i]["values"][$txInfoRet['vout'][$i]])) {
					$txInputFinal['addr'][] = $txInfoInput[$i]["addresses"][$txInfoRet['vout'][$i]][0];
					$txInputFinal['value'][] = $txInfoInput[$i]["values"][$txInfoRet['vout'][$i]];
				}
			}

			$finalAdd = 0;
			for ($i = 0; $i < count($txInputFinal['value']); $i++) {
				$finalAdd = $finalAdd + $txInputFinal['value'][$i];
			}
			$txInputFinal['total'] = $finalAdd;
			return $txInputFinal;
		}
	}
	
	public function sendToAddr($fromLabel, $toAddr, $amount) {
		$params = array($fromLabel, array($toAddr => $amount));
		return sendCmdToRPC("sendmany", $params);
	}
	
	public function listTransactions() {
		$params = array();
		return sendCmdToRPC("listtransactions", $params);
	}
	
	/*
	function importWalletFromFile($walletFile) {
		$params = array($walletFile);
		return sendCmdToRPC("importwallet", $params);
	}
	*/
}

/**********
*
* General (global) functions
*
**********/

function sendCmdToRPC($method, $params) {
	$url = ""; //initialization
	
	if ( env('XAPRPCHTTPS') === "false" || null !== (env('XAPRPCHTTPS')) )
		$url = "http://".env('XAPRPCUSERNAME', 'XAPUser').":".env('XAPRPCPASSWORD', 'XAPPassword')."@".env('XAPRPCIP', '127.0.0.1').":".env('XAPRPCPORT', '12117')."/";
	else
		$url = "http://".env('XAPRPCUSERNAME', 'XAPUser').":".env('XAPRPCPASSWORD', 'XAPPassword')."@".env('XAPRPCIP', '127.0.0.1').":".env('XAPRPCPORT', '12117')."/";
	
	$header[] = "Content-type: text/xml";

	$postData = array();
	$postData['method'] = $method;
	$postData['params'] = $params; //must always be an array

	$ch = curl_init();   
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData)); //must send as JSON
	$data = curl_exec($ch);       
	if (curl_errno($ch)) {
		return(curl_error($ch));
		//var_dump(curl_error($ch));
	} else {
		curl_close($ch);
		//var_dump($data);
		return $data;
	}
}
