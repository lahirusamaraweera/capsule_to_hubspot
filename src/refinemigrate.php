<?php

trait refinemigrate{

    private $account_cache = [];
    private $note_cache = [];
    /**
     * Process Cached Capsule notes
     */
    public function processContactCompanyAssociation($applicable_array = []){
        $files = scandir(CAPSULES_PARTIES_LOCATION,1); 
        $hubspot = $this->gethubspot();
        foreach($files as $file){
            if(in_array($file,['.','..'])){
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_PARTIES_LOCATION.$file));
            if(!is_array($notes)){
                continue;
            }
            $this->processCCABulk($notes, $hubspot);
        }
       
    }

    private function processCCABulk($notes, $hubspot){
        $count = 0;
        foreach($notes as $note){
            if($this->checkIfMigrated($note->id, EXECUTED_CCA_CACHE)){
                echo 'Already updted '.$note->id.PHP_EOL;
                continue;
            }
            if('organisation' == $note->type ){
                continue;
            }
            if(!isset($note->organisation->id) ){
                echo 'Skipped | organisation refererence not found';
                continue;
            }
            $hubspot_contact_id = $this->getHubspotIDForContact($note->id);
            $hubspot_company_id = $this->getHubspotIDForCompany($note->organisation->id);
            
            if(is_null($hubspot_company_id) || is_null($hubspot_contact_id)){
                echo 'Skipped | due to not having contact reference'.PHP_EOL;
                continue;
            }

            $payload = [
                "properties" => [ 
                    [
                        "property" => "associatedcompanyid",
                        "value" => $hubspot_company_id
                    ]
                ]
            ];
            
            $rules_client_status = [
                [
                    'values' => ['No_Reply'],
                    'preferred_value' => 'attempting'
                ],
                [
                    'values' => ['Not_supported'],
                    'preferred_value' => 'Incompatible'
                ],
                [
                    'values' => ['Not_supported'],
                    'preferred_value' => 'UNQUALIFIED'
                ],
                [
                    'values' => ['Not_interested'],
                    'preferred_value' => 'not_interested'
                ]
            ];


            $response = $hubspot->request("/contacts/v1/contact/vid/{$hubspot_contact_id}/profile", 'post', $payload);
            $body = $response->body;
            if(isset($body->status) && $body->status == 'error' ){
                echo 'Error : '.$body->message.PHP_EOL;
            }else{
                echo 'Processed a contact company association for contact ID '.$note->id.PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_CCA_CACHE);
            }
            
        }
    }

    public function refineParties(){
        $files = scandir(CAPSULES_PARTIES_LOCATION,1); 
        $hubspot = $this->gethubspot();
        foreach($files as $file){
            if(in_array($file,['.','..'])){
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_PARTIES_LOCATION.$file));
            if(!is_array($notes)){
                continue;
            }
            $this->refinePartiesBulk($notes, $hubspot);
        }
       
    }

    private function refinePartiesBulk($notes, $hubspot){
        $count = 0;
        foreach($notes as $note){
            if($this->checkIfMigrated($note->id, EXECUTED_PARTY_REFINE_CACHE)){
                echo 'Already updated '.$note->id.PHP_EOL;
                continue;
            }
            if('organisation' == $note->type ){
                continue;
            }
            $payload = [
                "properties" => []
            ];
            $lead_status = null;

            if(!isset($note->organisation->id) ){
                echo 'Company reference not found '.PHP_EOL;
                $lead_status = 'NEW';
                if ($this->checkifHasEmails($note->id)) {
                    $lead_status = 'CONTACTED';
                }
            } else {
                $organisation_id = $note->organisation->id;
                $rules_client_status = [
                    [
                        'values' => ['No_Reply'],
                        'preferred_value' => 'attempting'
                    ],
                    [
                        'values' => ['Competition'],
                        'preferred_value' => 'Using Competitor Product'
                    ],
                    [
                        'values' => ['Not_supported'],
                        'preferred_value' => 'UNQUALIFIED'
                    ],
                    [
                        'values' => ['Not_interested', 'Stopped'],
                        'preferred_value' => 'not_interested'
                    ],
                    [
                        'values' => ['Client OABP', 'Client_OABP/WA', 'Onboarding'],
                        'preferred_value' => 'CONTACTED'
                    ],
                    [
                        'values' => ['new','Poor_Lead', 'Strong_Lead', 'Med_Lead'],
                        'preferred_value' => 'NEW'
                    ],

                ];

                $organization_client_status = $this->getFieldValueOfOrganization($organisation_id, 413739, 'new');
                $lead_status = $this->evaluateCondition($rules_client_status, $organization_client_status);
                if ('NEW' == $lead_status && ( $this->checkifHasEmails($organisation_id) || $this->checkifHasEmails($note->id) )) {
                    $lead_status = 'CONTACTED';
                }
            }
            $hubspot_contact_id = $this->getHubspotIDForContact($note->id);
            
            
            if(is_null($hubspot_contact_id)){
                echo 'Skipped | due to not having contact reference'.PHP_EOL;
                
            }
           
            if(is_null($lead_status)){
                echo 'Error >>>> lead status is null - '.$organization_client_status.PHP_EOL;
                exit(0);
                $lead_status = 'NEW';
            }
            $payload['properties'][] = [
                'property' => 'hs_lead_status',
                'value' => $lead_status
            ];

            $response = $hubspot->request("/contacts/v1/contact/vid/{$hubspot_contact_id}/profile", 'post', $payload);
            $body = $response->body;
            if(isset($body->status) && $body->status == 'error' ){
                echo 'Error : '.$body->message.PHP_EOL;
            }else{
                echo 'Processed a contact lead status '.$hubspot_contact_id.' as '.$lead_status.PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_PARTY_REFINE_CACHE);
            }
            
        }
    }



    private function getFieldValueOfOrganization($organisation_id, $field_name, $fallBack = null){
        
        if(count($this->account_cache) == 0){
            $files = scandir(CAPSULES_PARTIES_LOCATION,1);
            foreach($files as $file){
                if(in_array($file,['.','..'])){
                    continue;
                }
                $notes = json_decode(file_get_contents(CAPSULES_PARTIES_LOCATION.$file));
                if(!is_array($notes)){
                    continue;
                }
                $this->account_cache = array_merge($this->account_cache, $notes );
            }
        }
        foreach ($this->account_cache as $note) {
            
            if ($note->id != $organisation_id) {
                continue;
            }
            $value = $this->getFieldValue($note->fields, $field_name);
            if ($value) {
                return $value;
            }
        }
        

        return $fallBack;
    }
    private function getFieldValue($fields, $field_name){
        // var_dump($field_name);exit(0);
        foreach($fields as $field ){
            if($field->definition->id == $field_name ){
                return $field->value;
            }
        }
        return false;
    }
    
    private function evaluateCondition($rules, $value ){
        foreach($rules as $rule ){
            if(in_array( $value, $rule['values'])){
                return $rule['preferred_value'];
            }
        }
        return null;
    }



    public function getFilteredNotes($organisation_id, $types_array){
        
        if(count($this->note_cache) == 0){
            $files = scandir(CAPSULES_NOTES_LOCATION,1);
            foreach($files as $file){
                if(in_array($file,['.','..'])){
                    continue;
                }
                $notes = json_decode(file_get_contents(CAPSULES_NOTES_LOCATION.$file));
                if(!is_array($notes)){
                    continue;
                }
                $this->note_cache = array_merge($this->note_cache, $notes );
            }
        }
        $filtered_notes = [];
        foreach ($this->note_cache as $note) {
            $note_type = $note->type;
            if( in_array($note_type, $types_array ) ){
                continue;
            }

            if( $note_type == 'email'){
                foreach($note->parties as $party){
                    if($party->id == $organisation_id ){
                        $filtered_notes[] = $note;
                    }
                }
            }else{
                if($note->party->id == $organisation_id ){
                    $filtered_notes[] = $note;
                }
            }
           

        }
        return $filtered_notes;
    }

    private function checkifHasEmails($organisation_id){
        $emails = $this->getFilteredNotes($organisation_id, ['email']);
        return (count($emails) > 0);
    }

    

}