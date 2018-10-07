<?php

trait partyMigrate{

    public function cacheCapsuleParties(){
        $capsules = $this->getCapsules();
        $entries = [];
        $page = 1;
        while(true){
            $response = $capsules->request('api/v2/parties?embed=fields,tags,organisation&perPage=100&page='.$page);
            $entries_temp = $response->body->parties;
            if(count($entries_temp) == 0){
                break;
            }    
            echo "Capsule Parties Caching - Received ".count($entries_temp)." entries | page - ".$page.PHP_EOL;
            file_put_contents(CAPSULES_PARTIES_LOCATION.'party_'.$page.'.json', json_encode($entries_temp, JSON_PRETTY_PRINT));
            $page ++;
        }
        return $page-1;
    }

    public function processCacheParties(){
        $files = scandir(CAPSULES_PARTIES_LOCATION,1); 
        $hubspot = $this->getHubspot();
        foreach($files as $file){
            if(in_array($file,['.','..'])){
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_PARTIES_LOCATION.$file));
            if(!is_array($notes)){
                continue;
            }
            $this->processPartyBulk($hubspot,$notes);
        }
       
    }

    private function processPartyBulk($hubspot, $notes){
        $count = 0;
        foreach($notes as $note){
            if($this->checkIfMigrated($note->id, EXECUTED_PARTIES_CACHE)){
                echo 'Already migrated '.$note->id.PHP_EOL;
                continue;
            }

            $payload = [];
            $response = false;
            switch ($note->type) {
                case "person":
                    $payload = $this->prepareContactCreatePayload($note);
                    $payload = [
                        "properties" => $payload
                    ];
                    // continue;
                    $response = $hubspot->request('/contacts/v1/contact','post',  $payload);
                    break;
                case "organisation":
                    $payload = $this->prepareCompanyCreatePayload($note);
                    $payload = [
                        "properties" => $payload
                    ];
                    $response = $hubspot->request('/companies/v2/companies','post',$payload);
                    break;
            } 
            
            if(isset($response->body->status) && $response->body->status == 'error' ){
                if(isset($response->body->validationResults)){
                    $error = json_encode(
                        [ 'id' => $note->id, 'error' => $response->body ], JSON_PRETTY_PRINT );
                    echo $error.PHP_EOL;
                    file_put_contents(ERROR_LOG_PATH, $error, FILE_APPEND);
                }
                echo 'Error : '.$response->body->message.PHP_EOL;
            }
            elseif(!$response){
                echo 'Something went wrong'.PHP_EOL;
            }
            else{
                echo 'Party Created - '.$note->id.PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_PARTIES_CACHE);
            }
            
            
        }
    }

    
    //company mapping
    private function prepareCompanyCreatePayload($note){
        $payload = [];
        $payload[] = $this->getBindValue('name', $note->name);
        $payload[] = $this->getBindValue('id', $note->id);

        if(count($note->addresses)> 0){
            $address = $note->addresses[0];
            $payload[] = $this->getBindValue('country', $address->country);
            $payload[] = $this->getBindValue('city', $address->city);
            $payload[] = $this->getBindValue('zip', $address->zip);
            $payload[] = $this->getBindValue('street', $address->street);
            $payload[] = $this->getBindValue('state', $address->state);
        }
        if(count($note->phoneNumbers)>0){
            $phone = $note->phoneNumbers[0];
            $payload[] = $this->getBindValue('phone', $phone->number);
        }
        $consumed_phone_types = [];
        $consumed_phone_numbers = [];
        foreach( $note->phoneNumbers as $phone ){
            if(in_array($phone->number , $consumed_phone_numbers)){
                continue;
            }
            $consumed_phone_numbers[] = $phone->number;
            if('Work' == $phone->type){
                if(!in_array('work_phone', $consumed_phone_types)){
                    $consumed_phone_types[] = 'work_phone';
                    $payload[] = $this->getBindValue('work_phone', $phone->number);
                    continue;
                }
                elseif(!in_array('work_phone_2', $consumed_phone_types)){
                    $consumed_phone_types[] = 'work_phone_2';
                    $payload[] = $this->getBindValue('work_phone_2', $phone->number);
                }
            }
        }

        if (count($note->emailAddresses) > 0) {
            $email_1st = $note->emailAddresses[0];
            $payload[] = $this->getBindValue('email', $email_1st->address);

            $consumed_emails = [];
            $unique_emails = array_values(
                array_unique(
                    array_map(
                        function($x){
                            return $x->address;
                        }, $note->emailAddresses)));

            foreach ($unique_emails as $email) {
                if($email_1st->address == $email){
                    continue;
                }
                if (!in_array('email_1', $consumed_emails)) {
                    $consumed_emails[] = 'email_1';
                    $payload[] = $this->getBindValue('email_1', $email);
                    continue;
                } elseif (!in_array('email_2', $consumed_emails)) {
                    $consumed_emails[] = 'email_2';
                    $payload[] = $this->getBindValue('email_2', $email);
                }
                
            }
        }


        $consumed_urls = [];
        foreach($note->websites as $website){
            if('URL' == $website->service && !in_array('URL', $consumed_urls)){
                $payload[] = $this->getBindValue('website', $website->address);
                $domain = parse_url($website->address, PHP_URL_HOST);
                if($domain != $website->address ){
                    $payload[] = $this->getBindValue('domain', $domain);
                }
                $consumed_urls[] = 'URL';
                
            }
            elseif('LINKED_IN' == $website->service && !in_array('URL', $consumed_urls)){
                $payload[] = $this->getBindValue('linkedin_company_page', $website->address);
                $consumed_urls[] = 'LINKED_IN';
            }
            elseif('FACEBOOK' == $website->service && !in_array('URL', $consumed_urls)){
                $payload[] = $this->getBindValue('facebook_company_page', $website->address);
                $consumed_urls[] = 'FACEBOOK';
                
            }
            
        }

        foreach($note->fields as $field_data ){
            $row = $this->getBindValue( $field_data->definition->name, $field_data->value );
            if(!$row){
               continue; 
            }
            $payload[] = $this->getBindValue( $field_data->definition->name, $field_data->value );
        } 


        //conditional field value population
        $conditional_rules = $this->getConditionalPopulationRules(COMPANY_CONDITIONAL_POPULATION_RULES_PATH);
        // todo: complete conditional rule based populations
        
        return $this->getFilteredPayload($payload);
    }

    private function getConditionalMappedValue($dataset, $rules){
        foreach( $rules as $rule ){
            if(in_array($this->getFilteredValue($dataset, $rule['c_field'] ), $rule['applicable_values'] )){
                return $rule['preferred_value'];
            } 
        }

        return false;
    }

    // contact mapping 
    private function prepareContactCreatePayload($note){
        $payload = [];
        
        $payload[] = $this->getBindValue('jobtitle', $note->jobTitle, false);
        $payload[] = $this->getBindValue('firstname', $note->firstName, false);
        $payload[] = $this->getBindValue('lastname', $note->lastName, false);
        $payload[] = $this->getBindValue('id', $note->id, false);

        if(count($note->addresses)> 0){
            $address = $note->addresses[0];
            $payload[] = $this->getBindValue('country', $address->country, false);
            $payload[] = $this->getBindValue('city', $address->city, false);
            $payload[] = $this->getBindValue('zip', $address->zip, false);
            $payload[] = $this->getBindValue('street', $address->street, false);
            $payload[] = $this->getBindValue('state', $address->state, false);
        }
        if(count($note->phoneNumbers)>0){
            $phone = $note->phoneNumbers[0];
            $payload[] = $this->getBindValue('phone', $phone->number, false);
        }
        $consumed_phone_types = [];
        
        foreach( $note->phoneNumbers as $phone ){
            if('Work' == $phone->type){
                if(!in_array('work_phone', $consumed_phone_types)){
                    $consumed_phone_types[] = 'work_phone';
                    $payload[] = $this->getBindValue('work_phone', $phone->number, false);
                    // continue;
                }
                elseif(!in_array('work_phone_2', $consumed_phone_types)){
                    $consumed_phone_types[] = 'work_phone_2';
                    $payload[] = $this->getBindValue('work_phone_2', $phone->number, false);
                }
            }
        }

        if (count($note->emailAddresses) > 0) {
            $email_1st = $note->emailAddresses[0];
            $payload[] = $this->getBindValue('email', $email_1st->address, false);

            $consumed_emails = [];
            $unique_emails = array_values(
                array_unique(
                    array_map(
                        function($x){
                            return $x->address;
                        }, $note->emailAddresses)));
            foreach ($unique_emails as $email) {
                if($email_1st->address == $email){
                    continue;
                }
                if (!in_array('email_1', $consumed_emails)) {
                    $consumed_emails[] = 'email_1';
                    $payload[] = $this->getBindValue('email_1', $email, false);
                    continue;
                } elseif (!in_array('email_2', $consumed_emails)) {
                    $consumed_emails[] = 'email_2';
                    $payload[] = $this->getBindValue('email_2', $email, false);
                }
                
            }
        }

        foreach($note->websites as $website){
            if('URL' == $website->service ){
                $payload[] = $this->getBindValue('website', $website->address, false );
            }
            elseif('LINKED_IN' == $website->service){
                $payload[] = $this->getBindValue('linkedinbio', $website->address, false);
            }
            elseif('FACEBOOK' == $website->service){
                $payload[] = $this->getBindValue('facebook', $website->address, false);
            }
            
        }

        foreach($note->fields as $field_data ){
            $row = $this->getBindValue( $field_data->definition->name, $field_data->value , false );
            if(!$row){
               continue; 
            }
            $payload[] = $this->getBindValue( $field_data->definition->name, $field_data->value , false );
        }

        
        // $lastContacted = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $note->lastContactedAt);
        // $payload[] = $this->getBindValue( 'notes_last_contacted', (!$lastContacted) ? null : ((int)$lastContacted->getTimestamp())*1000 , false );

        //current position
        return $this->getFilteredPayload($payload, 'property');

    }

    private function getFilteredPayload($payload, $property = 'name'){
        $payload_filtered = [];
        foreach($payload as $x){
            if(is_array($x) && !is_null($x[$property]) && !is_null($x['value'])){
                $payload_filtered[] = $x;
            }
        }
        return $payload_filtered;
    }

    private function getBindValue($property, $value, $is_company = true){
        $mapped_field_ = $this->getMappedFieldName($property, $is_company);
        if(!$mapped_field_){
            return false;
        }
        return [
            ($is_company) ? 'name': 'property' => $mapped_field_->hubspot_property,
            'value' => isset($mapped_field_->mapping->{$value}) ? $mapped_field_->mapping->{$value} : $value
        ];
    }
    

    private function getMappedFieldName($name, $is_company){
        if($is_company){
            foreach($this->companyFieldsMapping as $key => $data ){
                if($name == $key ){
                    return $data;
                }
            }
        }else{
            foreach($this->contactFieldsMapping as $key => $data ){
                if($name == $key ){
                    return $data;
                }
            }
        }
        return false;
    }

    private function getFilteredValue($data_set, $property){
        foreach( $data_set as $data){
            if($data->definition->name == $property){
               return $data->value;
            }
        }
        return null;
    }

    private function getConditionalPopulationRules($path){
        return json_decode(file_get_contents($path));
    }
}