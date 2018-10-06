<?php

trait noteMigrate{

    /**
     * Cache capsule notes in json files
     */
    public function cacheCapsuleNotes(){
        $capsules = $this->getCapsules();
        $entries = [];
        $page = 1;
        while(true){
            $response = $capsules->request('api/v2/entries?perPage=100&page='.$page);
            $entries_temp = $response->body->entries;
            if(count($entries_temp) == 0){
                break;
            }    
            echo "Capsule entries caching- Received ".count($entries_temp)." entries | page - ".$page.PHP_EOL;
            file_put_contents(CAPSULES_NOTES_LOCATION.'notes_'.$page.'.json', json_encode($entries_temp, JSON_PRETTY_PRINT));
            $page ++;
        }
        return $page-1;
    }

    /**
     * Process Cached Capsule notes
     */
    public function processCacheNotes($applicable_array = []){
        $files = scandir(CAPSULES_NOTES_LOCATION,1); 
        foreach($files as $file){
            if(in_array($file,['.','..'])){
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_NOTES_LOCATION.$file));
            if(!is_array($notes)){
                continue;
            }
            $this->processNotesBulk($notes, $applicable_array);
        }
       
    }

    private function processNotesBulk($notes, $applicable_array){
        $count = 0;
        foreach($notes as $note){
            if($this->checkIfMigrated($note->id, EXECUTED_NOTES_CACHE)){
                echo 'Already migrated '.$note->id.PHP_EOL;
                continue;
            }
            if(($note->type == 'email' && !isset($note->parties)) || ( !($note->type == 'email') &&  !isset($note->party)) || !in_array( $note->type, $applicable_array) ){
                echo 'Skipped '.$note->type.' : either a task or Party references not found.'.PHP_EOL;
                continue;
            }

            $associations = [
                'contactIds' => [],
                "companyIds" => []
            ];
            $parties = !('email' == $note->type ) ? $note->party : $note->parties;
            if(('email' == $note->type )){
                foreach($parties as $party){
                    switch ($party->type) {
                        case "person":
                            $party_id = $this->getHubspotIDForContact($party->id);
                            if(!is_null($party_id)){
                                $associations['contactIds'][] =  $party_id;
                            }
                            break;
                        case "organisation":
                            $party_id = $this->getHubspotIDForCompany($party->id);
                            if(!is_null($party_id)){
                                // $associations['companyIds'][] = $party_id;
                                $associations['companyIds'][] = $party_id;
                            }
                            break;
                    } 
                }
            }else{
                switch ($parties->type) {
                    case "person":
                        $party_id = $this->getHubspotIDForContact($parties->id);
                        if(!is_null($party_id)){
                            $associations['contactIds'][] = $party_id;
                        }
                        break;
                    case "organisation":
                        $party_id = $this->getHubspotIDForCompany($parties->id);
                        if(!is_null($party_id)){
                            $associations['companyIds'][] = $party_id;
                        }
                        break;
                }  
            }
            
            if((count($associations['companyIds']) == 0) && ( $associations['contactIds'] == 0)){
                echo 'Skipped a note : No associations found'.PHP_EOL;
                continue;
            }

            $compiled_note = $this->getCompiledEngagement($note, $associations);
          
            $response = $this->createEngament($compiled_note);
            $body = $response->body;
            if(isset($body->status) && $body->status == 'error' ){
                echo 'Error : '.$body->message.PHP_EOL;
            }else{
                echo 'Processed a note'.PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_NOTES_CACHE);
            }
            
        }
    }

    private function getFilteredParticipants($participants, $criteria ){
        $filtered = [];
        foreach($participants as $participant){
            if($participant->role == $criteria ){
                $filtered[] = $participant;
            }
        }
        return $filtered;
    }

    private function getCompiledEngagement($note, $associations){
        $engagement = [];
        
        if($note->type == 'task'){
            $timestamp = \DateTime::createFromFormat('Y-m-d', $note->dueOn);
            echo (((int)$timestamp->getTimestamp())*1000).PHP_EOL;
        }else{
            $timestamp = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $note->createdAt);
        }
        // var_dump($timestamp);exit(0);
        $engagement['engagement'] = [
            "active" => true,
            'timestamp' =>  ((int)$timestamp->getTimestamp())*1000
        ];

        $engagement['associations'] = $associations;
        switch ($note->type) {
            case "email":
                $engagement['engagement']["type"] = "EMAIL";
                $engagement['metadata'] = [
                    "from" => [],
                    "to" => [],
                    "cc" => [],
                    "subject" => $note->subject,
                    "html" => str_replace("\r\n","<br/>", $note->content)
                    // "html" => $note->content
                ];

                $from_entry = $this->getFilteredParticipants($note->participants, 'FROM');
                $engagement['metadata']['from']['email'] = $from_entry[0]->address;
                $engagement['metadata']['from']['firstName'] = $from_entry[0]->name;

                $to_entries = $this->getFilteredParticipants($note->participants, 'TO');
                foreach($to_entries as $to_entry){
                    $engagement['metadata']['to'][] = ['email' =>  $to_entry->address ];
                }

                $cc_entries = $this->getFilteredParticipants($note->participants, 'CC');
                foreach($cc_entries as $cc_entry){
                    $engagement['metadata']['cc'][] = ['email' =>  $cc_entry->address ];
                }

                break;
            case "note":
                $engagement['engagement']["type"] = "NOTE";
                $engagement['metadata'] = [
                    "body" => str_replace("\r\n","\n\n",$note->content)
                ];
                break;
            case "task":
                $engagement['engagement']["type"] = "TASK";
                $engagement['engagement']["ownerId"] = $note->owner_id;
                $engagement['metadata'] = [
                    "status" => $note->status_hubs,
                    "subject" => $note->description,
                    "body" => $note->detail,
                ];
                break;
        }
        
        return $engagement;
    }

    

}