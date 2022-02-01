<?php
namespace ltajniaa\DecentroCKYC;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use Ramsey\Uuid\Uuid;


class Integrator
{
    private $baseUri;
    private $clientId;
    private $clientSecret;
    private $moduleSecret;

    function __construct($clientId, $clientSecret, $moduleSecret, $env = "prod")
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->moduleSecret = $moduleSecret;
        if($env === "test")
            $this->baseUri = "https://in.staging.decentro.tech/v2/kyc/ckyc/";
        else
            $this->baseUri = "https://in.decentro.tech/v2/kyc/ckyc/";
    }


    /**
     * searchCkycIdforPAN method
     *
     * @param string $pan 
     * @param string $consentPurpose At least 20 character 
     *
     */
    function searchCkycIdforPAN($pan, $consentPurpose, $customerConsent = true)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 5.0]);
        $headerData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'module_secret' => $this->moduleSecret,
            'content-type' => 'application/json'

        ];
        $rawData = [
            "reference_id" => Uuid::uuid4()->toString(), 
            "document_type" => "PAN", 
            "id_number" => $pan, 
            "consent" => $customerConsent, 
            "consent_purpose" => $consentPurpose
        ];

        $bodyData = json_encode($rawData);
        $bodyData = str_replace('"consent":1', '"consent":true', $bodyData);

        $rtApiCallData['endpoint'] =  $this->baseUri . 'search';
        $rtApiCallData['request'] = $bodyData;

        // pr($bodyData);
        // die();
        $code = 0;
        try {
            $response = $client->request('POST', 'search', ['headers' => $headerData, 'body' => $bodyData]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            pr($e);
            $rtStatus = "error";
        }
        //die;

        
        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);

                if (!$res->status) {
                    throw new \Exception('invalid data');
                }

                if($res->data->kycStatus == "SUCCESS")
                    $rtStore['ckycId'] = $res->data->kycResult->ckycId;
                else
                    $rtStatus = "failure";
              
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }


    /**
     * searchCkycIdforPAN method
     *
     * @param string $ckycId ID obtained in search function
     * @param string $dob Date of Birth in yyyy-mm-dd format
     * @param string $consentPurpose At least 20 character 
     *
     */
    function downloadCkycIdwithDob($ckycId, $dob, $consentPurpose, $customerConsent = true)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 5.0]);
        $headerData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'module_secret' => $this->moduleSecret,
            'content-type' => 'application/json'

        ];
        $rawData = [
            "reference_id" => Uuid::uuid4()->toString(), 
            "id_number" => $ckycId, 
            "auth_factor_type" => 1,
            "auth_factor" => $dob, 
            "consent" => $customerConsent, 
            "consent_purpose" => $consentPurpose
        ];

        $bodyData = json_encode($rawData);
        $bodyData = str_replace('"consent":1', '"consent":true', $bodyData);

        $rtApiCallData['endpoint'] =  $this->baseUri . 'download';
        $rtApiCallData['request'] = $bodyData;

        // pr($bodyData);
        // die();
        $code = 0;
        try {
            $response = $client->request('POST', 'download', ['headers' => $headerData, 'body' => $bodyData]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            pr($e);
            $rtStatus = "error";
        }
        //die;

        
        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);

                if (!$res->status) {
                    throw new \Exception('invalid data');
                }

                if($res->data->kycStatus == "SUCCESS"){
                   // $rtStore['ckycId'] = $res->data->kycResult->ckycId;
                }else{
                    $rtStatus = "failure";
                }
              
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }

}