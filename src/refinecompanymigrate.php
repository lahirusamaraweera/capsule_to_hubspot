<?php

trait refinecompanymigrate
{

    private $demo_booked_accounts = [];

    public function refinecompanies()
    {
        $files = scandir(CAPSULES_PARTIES_LOCATION, 1);
        $hubspot = $this->gethubspot();
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_PARTIES_LOCATION . $file));
            if (!is_array($notes)) {
                continue;
            }
            $this->refineCompanyBulk($notes, $hubspot);
        }

    }

    private function refineCompanyBulk($notes, $hubspot)
    {
        foreach ($notes as $note) {
            if ($this->checkIfMigrated($note->id, EXECUTED_COMPANY_REFINE_CACHE)) {
                echo 'Already updated ' . $note->id . PHP_EOL;
                continue;
            }
            if ('person' == $note->type) {
                continue;
            }
            $payload = [
                "properties" => []
            ];

            $hubspot_company_id = $this->getHubspotIDForCompany($note->id);
            if (is_null($hubspot_company_id)) {
                echo 'Skipped | due to not having company reference' . PHP_EOL;
                continue;
            }

            $life_cycle_state = 'subscriber';

            if(count($note->phoneNumbers) > 0 || count($note->emailAddresses) > 0 ){
                $life_cycle_state = 'lead';
            }

            if($this->checkIfDemoBooked($note->id)){
                $life_cycle_state = 'opportunity';
            }
            // business type
            $life_cycle_mapping_rules = [
                [
                    'c_field' => 'Client Status',
                    'applicable_values' => ['Client OABP', 'Client_OABP/WA'],
                    'preferred_value' => 'customer'
                ],
                [
                    'c_field' => 'Company type',
                    'applicable_values' => ['PMS Company'],
                    'preferred_value' => 'evangelist'
                ]
            ];

            $matched_lc_state = $this->getConditionalMappedValue($note->fields, $life_cycle_mapping_rules);
            if($matched_lc_state){
                $life_cycle_state = $matched_lc_state;
            }
            
            $payload['properties'][] = [
                'property' => 'lifecyclestage',
                'value' => $life_cycle_state
            ];

            // var_dump($payload);
            continue;
            // exit(0);
            // $response = $hubspot->request("/companies/v2/companies/{$hubspot_company_id}", 'put', $payload);
            $body = $response->body;
            if (isset($body->status) && $body->status == 'error') {
                echo 'Error : ' . $body->message . PHP_EOL;
            } else {
                echo 'Processed a company life cycle stage update ' . $hubspot_company_id . ' as ' . $life_cycle_state . PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_COMPANY_REFINE_CACHE);
            }

        }
    }

    private function checkIfDemoBooked($organisation_id){
        $this->cacheDemoBookedAccounts();
        return in_array($organisation_id, $this->demo_booked_accounts);

    }

    public function cacheDemoBookedAccounts($applicable_types = ['task', 'note'])
    {
        if (count($this->demo_booked_accounts) == 0) {
            $files = scandir(CAPSULES_NOTES_LOCATION, 1);
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                $notes = json_decode(file_get_contents(CAPSULES_NOTES_LOCATION . $file));
                if (!is_array($notes)) {
                    continue;
                }
                foreach ($notes as $note) {
                    $note_type = $note->type;
                    if (!in_array($note_type, $applicable_types)) {
                        continue;
                    }
                    var_dump(strpos(strtolower($note->content), 'demo'));
                    $st = strpos(strtolower($note->content), 'demo');
                    if ($st != false) {
                        if ($note_type == 'email') {
                            foreach ($note->parties as $party) {
                                $this->demo_booked_accounts[] = $party->id;
                            }
                        } else {
                            $this->demo_booked_accounts[] = $note->party->id;
                        }
                    }

                }
            }
        }
        $this->demo_booked_accounts = array_unique($this->demo_booked_accounts);
    }



}