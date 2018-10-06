<?php

class capToHubspot{
    use partyMigrate;
    use noteMigrate;
    use taskMigrate;
    use refineMigrate;
    use refinecompanymigrate;

    const contact_cache = 'contact_cache';
    const company_cache = 'company_cache';

    private $capsuleToken = null;
    private $hubspotToken = null;

    private $companyFieldsMapping =  null;
    private $contactFieldsMapping =  null;

    private $testContactID = null;
    private $testCompanyID = null;
    private $testMode = false;

    function __construct($testId = null)
    {
        $this->setTokens();
        $this->loadMapping();
    }


    private function setTokens(){
        $this->capsuleToken = file_get_contents('./token/capsules.key');
        $this->hubspotToken = file_get_contents('./token/hubspot.key');
    }

    private function loadMapping(){
        $this->companyFieldsMapping =  json_decode(file_get_contents(COMPANY_MAPPING_PATH));
        $this->contactFieldsMapping =  json_decode(file_get_contents(CONTACT_MAPPING_PATH));

        $config = json_decode(file_get_contents(CONFIG_FILE_PATH));
        $this->testMode = $config->testMode;
        $this->testCompanyID = $config->testCompanyID;
        $this->testContactID = $config->testContactID;
        if($this->testMode){
            echo "<<<<<<<<<<<<<<<<<<<<<<<< Running in TEST MODE >>>>>>>>>>>>>>>>>>>>>>".PHP_EOL;
        }

    }

    public function setToken($service, $token){
        switch ($method) {
            case "capsules":
                $this->capsuleToken = $token;
                return true;
            case "hubspot":
                $this->hubspotToken = $token;
                return true;
            default:
                return false;
        }
    }

    public function getCapsules(){
        return new capsules($this->capsuleToken);
    }
    
    public function getHubspot(){
        return new hubspot($this->hubspotToken);
    }

    /**
     * Read hubspot contact by email
     */
    public function readHubspotContactsByEmail($email){
        $capsules = $this->getHubspot();
        $response = $capsules->request('/contacts/v1/contact/email/'.$email.'/profile');
        $contact = $response->body;
        return $contact;
    }

    public function readHubspotComanyByDomain($domain){
        $capsules = $this->getHubspot();
        $payload = [
            'limit' => 5,
            'requestOptions' => [
                'properties' => [
                    'domain', 'website'
                ]
            ]
        ];
        $response = $capsules->request('/companies/v2/domains/'.$domain.'/companies', 'post', $payload);
        // var_dump($response);exit(0);
        $contact = $response->body->results;
        return $contact;
    }

    /**
     * Create an engagement in Hubspot
     */
    public function createEngament($engagement)
    {
        $hubspot = $this->getHubspot();
        try {
            return $hubspot->request('/engagements/v1/engagements', 'post', $engagement);
        } catch (\Exception $e) {
            return (object)[
                'body' => [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Read Hubspot Contacts
     */
    public function getHubspotContacts($params = []){
        $hubspot = $this->getHubspot();
        try {
            return $hubspot->request('/contacts/v1/lists/all/contacts/all', 'get', null, $params);
        } catch (\Exception $e) {
            return (object)[
                'body' => [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            ];
        }

    }

    /**
     * Read Hubspot Companies
     */
    public function getHubspotCompanies($params = []){
        $hubspot = $this->getHubspot();
        return $hubspot->request('/companies/v2/companies/paged','get', null, $params);

    }

    /**
    * Search Hubspot Contacts
    */
    public function searchHubspotContacts($email){
        $hubspot = $this->getHubspot();
        return $hubspot->request('/contacts/v1/contact/email/'.$email.'/profile');
    }

    /**
     * Read all hubspot contacts and cache the ID mapping between capusles and hubspot 
     */
    public function cacheContactsCapsulesIDsWithHubspotIDs(){
        $hubspot = $this->getHubspot();
        $hashMap = [];
        $vidOffset = null;
        while(true){
            
            $params = [
                'property' => 'capsulecrm_id'
            ];
            if(!is_null($vidOffset)){
                $params['vidOffset'] = $vidOffset;
            }
            echo 'Caching Contact ID mapping-> Current offset '.$vidOffset.' | Total Ids '.count($hashMap).PHP_EOL;
            $response = $this->getHubspotContacts($params);
            $body = $response->body;

            $contacts = $body->contacts;
            
            foreach($contacts as $contact){
                if(!isset($contact->properties->capsulecrm_id)){
                    continue;
                }
                $hashMap[$contact->properties->capsulecrm_id->value] = $contact->vid;
            }
            if( !(isset($body->{'has-more'}) && $body->{'has-more'})){
                break;
            }
            $vidOffset = $body->{'vid-offset'};
        }
        file_put_contents("./cache/capsules/".self::contact_cache.".json", json_encode($hashMap, JSON_PRETTY_PRINT));
        return count($hashMap);

    }

    /**
     * Read all hubspot companies and cache the ID mapping between capusles and hubspot 
     */
    public function cacheCompaniesCapsulesIDsWithHubspotIDs(){
        $hubspot = $this->getHubspot();
        $hashMap = [];
        $vidOffset = null;
        while(true){
            
            $params = [
                'properties' => 'capsulecrm_id'
            ];
            if(!is_null($vidOffset)){
                $params['offset'] = $vidOffset;
            }
            echo 'Caching Company ID Mapping-> Current offset '.$vidOffset.' | Total Ids '.count($hashMap).PHP_EOL;
            $response = $this->getHubspotCompanies($params);
            $body = $response->body;

            $companies = $body->companies;
            foreach($companies as $contact){
                if(isset($contact->properties->capsulecrm_id->value)){
                    $hashMap[$contact->properties->capsulecrm_id->value] = $contact->companyId;
                }
            }
            if( !(isset($body->{'has-more'}) && $body->{'has-more'})){
                break;
            }
            $vidOffset = $body->{'offset'};
        }
        file_put_contents("./cache/capsules/".self::company_cache.".json", json_encode($hashMap, JSON_PRETTY_PRINT));
        return count($hashMap);

    }

    /** Return hubspot contact for Capsules Contact ID */
    public function getHubspotIDForContact($id){
        if($this->testMode){
            return $this->testContactID;
        }
        $hashmap = json_decode(file_get_contents("./cache/capsules/".self::contact_cache.".json"));
        return isset($hashmap->{$id}) ? $hashmap->{$id} :  null;
    }

    /** Return hubspot company for Capsules Account ID */
    public function getHubspotIDForCompany($id){
        if($this->testMode){
            return $this->testCompanyID;
        }
        $hashmap = json_decode(file_get_contents("./cache/capsules/".self::company_cache.".json"));
        return isset($hashmap->{$id}) ? $hashmap->{$id} :  null;
    }

    /** Return hubspot owner ID for Capsules Owner ID */
    public function getHubspotOwnerID($id){
        if($this->testMode){
            return $this->testContactID;
        }
        $owners = json_decode(file_get_contents(OWNER_MAPPING_PATH));
        foreach($owners->mapping as $owner){
            if($owner->capsule_id === $id ){
                return $owner->hubspot_id;
            }
        }
        return $owners->default;
    }
    
    
    // read capsules custom fields
    public function readCapsuleCustomField(){
        $capsules = $this->getCapsules();
        $entries = [];
        $page = 1;
        while(true){
            $response = $capsules->request('api/v2/parties/fields/definitions?perPage=100&page='.$page);
            $entries_temp = $response->body->definitions;
            if(count($entries_temp) == 0){
                break;
            }    
            echo "Capsule Custom Properties - Received ".count($entries_temp)." entries | page - ".$page.PHP_EOL;
            file_put_contents('./cache/capsules_custom_properties/custom_fields_'.$page.'.json', json_encode($entries_temp, JSON_PRETTY_PRINT));
            $page ++;
        }
        return $page-1;
    }
    // read and cache hubspot custom fields
    public function readHubspotField(){
        $hubspot = $this->getHubspot();
        $response = $hubspot->request('/properties/v1/contacts/properties');
        $entries_temp = $response->body;
        echo "Hubspot Contact  Custom Properties - Received ".count($entries_temp).PHP_EOL;
        file_put_contents('./cache/hubspot_custom_properties/contact.json', json_encode($entries_temp, JSON_PRETTY_PRINT));

        $response = $hubspot->request('/properties/v1/companies/properties/');
        $entries_temp = $response->body;
        echo "Hubspot Company  Custom Properties - Received ".count($entries_temp).PHP_EOL;
        file_put_contents('./cache/hubspot_custom_properties/company.json', json_encode($entries_temp, JSON_PRETTY_PRINT));
        
    }

    public function checkIfMigrated($id, $path){
        if(!file_exists($path)){
            return false;
        }
        $cached = json_decode(file_get_contents($path));
        if(!$cached ){
            return false;
        }else{
            if(!is_array($cached->note_ids)){
                $cached->note_ids = [];
            }
            if(in_array($id, $cached->note_ids)){
                return true;
            }
            return false;
        }
    }

    public function markAsMigrated($id, $path){
        if(!file_exists($path)){
            file_put_contents($path , json_encode([], JSON_PRETTY_PRINT));
        }
        $cached = json_decode(file_get_contents($path));
        if(!$cached ){
            $cached = (object)[
                'note_ids' => []
            ];
            $cached->note_ids = array_merge($cached->note_ids, [ $id ]);
            file_put_contents($path , json_encode($cached, JSON_PRETTY_PRINT));
            return true;
        }else{
            if(!is_array($cached->note_ids)){
                $cached->note_ids = [];
            }
            if(in_array($id, $cached->note_ids)){
                return true;
            }
            $cached->note_ids = array_merge($cached->note_ids, [ $id ]);
            file_put_contents($path , json_encode($cached, JSON_PRETTY_PRINT));
            return true;
        }
    }

    /**
     * cache owner details
     */
    public function cacheOwnerDetails(){
        $capsule = $this->getCapsules();
        $response = $capsule->request('api/v2/users?embed=party');

        if(isset($response->body)){
            $body = $response->body;
            echo 'Cached capsule owner details >> '.PHP_EOL;
            file_put_contents(OWNER_DETAILS_LOCATION.'capsule_owners.json', json_encode($body, JSON_PRETTY_PRINT));
        }

        $hubspot = $this->getHubspot();
        $response = $hubspot->request('/owners/v2/owners/');

        if(isset($response->body)){
            $body = $response->body;
            echo 'Cached hubspot owner details >> '.PHP_EOL;
            file_put_contents(OWNER_DETAILS_LOCATION.'hubspot_owners.json', json_encode($body, JSON_PRETTY_PRINT));
        }

    }
}
