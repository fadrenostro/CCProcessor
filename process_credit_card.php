function process_credit_card( $card_num, $exp_date, $card_ccv, $amount, $journalid, $clientid, $cc_holder_name, $cc_address, $cc_postal ) {

        $client = null;
        $error = '';
        $post_string = '';
        $post_response = '';
        $response = null;
        $auth_resp = 2;
        $auth_resp2 = 37;
        $auth_resp_text = "";
        $trans_id = 0;

        /* Create security token */
        try{
                /* For live server use 'www' for test server use 'sandbox' */
                $wsdl = 'https://www.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl';

                /* Instantiate SoapClient object as $client */
                $client = new SoapClient($wsdl);

                /* Security token */
                $sourcekey = '8gBy397AiqC3Cdmt6B3337X9159Wq4S6';
                $pin = '2521291';

                /* Generate random seed value */
                $seed = time() . rand();

                /* Make hash value using sha1 function */
                $clear = $sourcekey . $seed . $pin;
                $hash = sha1($clear);

                /* Assembly ueSecurityToken as an array */
                $token = array(
                        'SourceKey' => $sourcekey,
                        'PinHash' => array(
                        'Type' => 'sha1',
                                'Seed' => $seed,
                                'HashValue' => $hash
                        ),
                        'ClientIP' => $_SERVER['REMOTE_ADDR'],
                                        );

                /* Create the request */
                $request = array(
                        'AccountHolder' => $cc_holder_name,
                        'Details' => array(
                                'Description' => 'Transaction',
                                'Amount' => $amount,
                                'Invoice' => $journalid
                        ),
                        'CreditCardData' => array(
                                'CardNumber' => $card_num,
                                'CardExpiration' => $exp_date,
                                'AvsStreet' => $cc_address,
                                'AvsZip' => $cc_postal,
                                'CardCode' => $card_ccv
                        )
                );

                $response = $client->runTransaction($token, $request);
                $post_string = $client->__getLastRequest();
                $post_response = $client->__getLastResponse();

                if (is_object($response)){
                        switch(strtolower((string)$response->Result)){
                                case "approved": $auth_resp = 1; break;
                                case "declined": $auth_resp = 2; $auth_resp2 = 2; break;
                        }
                        $auth_resp_text = (string)$response->Result;
                        $trans_id = (int)$response->RefNum;
                }
        }catch(Exception $e){
                if ($client != null){
                        $post_string = $client->__getLastRequest();
                        $post_response = $client->__getLastResponse();
                }
                $error = $e->getMessage();
        }

        /* log everything */
        $log_date = date('Y-m-d H:i:s');
        $h = fopen("../log/process_credit_card_usaepay.log", "a");
        fputs($h, "---------------------------\n");
        fputs($h, "$log_date\n" );
        fputs($h, "$post_string\n");
        fputs($h, "$post_response\n");
        fputs($h, "$error\n");
        fclose($h);

        return array( $auth_resp, $auth_resp2, $auth_resp_text, $trans_id, $log_date );
}
