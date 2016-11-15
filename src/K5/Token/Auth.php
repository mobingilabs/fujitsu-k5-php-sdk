<?php

namespace K5\Token;


class Auth
{


    public $username;
    public $password;
    public $contract;
    public $global;


    public function __construct($username, $password, $contract, $global = false) {
        $this->useranme = $username;
        $this->password = $password;
        $this->contract = $contract;
        $this->global = $global;
    }



    /**
     * Get the authentication token from K5 API
     *
     * Get the authentication token for interacting with each API call.
     * By default, this token expires in 1 hour, and you can customize a local session storage (1hr)
     * Some API calls (eg. Create, Delete global resources) will require global token, and needs to set the last parameter to TRUE
     *
     * @see _https://identity.cloud.global.fujitsu.com/v3/auth/tokens_
     *
     * @param $k5_username          Customer's Username
     * @param $k5_password          Customer's Password
     * @param $k5_contract_number   Customer's Contract Number
     * @param $global               If sets TRUE, will get global token
     *
     * @\K5\Token\Auth()
     *
     * @return array
     */
    public function getAuthToken()
    {


        $this->global ? $gls = 'gls.' : $gls = '';

        $c = '\
        curl -i -X "POST" "https://identity.'.$gls.'cloud.global.fujitsu.com/v3/auth/tokens" \
        	-H "Content-Type: application/json" \
        	-H "Accept: application/json" \
        	-d \'{
              "auth": {
                "identity": {
                  "methods": [
                    "password"
                  ],
                  "password": {
                    "user": {
                      "domain": {
                        "name": "' .$this->contract. '"
                      },
                      "name": "' .$this->useranme. '",
                      "password": "' .$this->password. '"
                    }
                  }
                }
              }
            }\'
        ';

        $respond = shell_exec($c);

        $respond = explode("\r\n",$respond);

        array_filter($respond);

        foreach($respond as $value){
        	if(strpos($value, "X-Subject-Token:") !== false){
        		$token = trim(substr($value,strpos($value,":")+1));
        	}else{
        		json_decode($value);
        		if (json_last_error() == JSON_ERROR_NONE) {
        			$return = $value; //the actual body data in Json format
                    $data = $return; //return this at the bottom of this function
        		}
        	}
        }

        // if(empty($token)) $token = '';

        if(!$token) $token = '';

        $return = json_decode($return, true);

        $project_id = $this->getProjectId(json_decode($data));
        $domain_id = $this->getDomainId(json_decode($data));

        return array(
            "token"=>$token,
            "project_id"=>$project_id,
            "domain_id"=>$domain_id,
            "response"=>$data
        );


    }



    private function getProjectId($response){

        return $response->token->project->id;

    }



    private function getDomainId($response){

        return $response->token->project->domain->id;

    }

}
