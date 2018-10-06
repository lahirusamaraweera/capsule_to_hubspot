<?php

trait taskMigrate
{

    /**
     * Cache capsules tasks in json filess
     */
    public function cacheCapsuleTasks()
    {
        $capsules = $this->getCapsules();
        $entries = [];
        $page = 1;
        while (true) {
            $response = $capsules->request('api/v2/tasks?status=open,completed,pending&perPage=100&page=' . $page);
            $entries_temp = $response->body->tasks;
            if (count($entries_temp) == 0) {
                break;
            }
            echo "Capsule Tasks caching- Received " . count($entries_temp) . " entries | page - " . $page . PHP_EOL;
            file_put_contents(CAPSULES_TASKS_LOCATION . 'tasks_' . $page . '.json', json_encode($entries_temp, JSON_PRETTY_PRINT));
            $page++;
        }
        return $page - 1;
    }


    /**
     * Process Cached Capsule Tasks
     */
    public function processCacheTask()
    {

        $files = scandir(CAPSULES_TASKS_LOCATION, 1);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $notes = json_decode(file_get_contents(CAPSULES_TASKS_LOCATION . $file));
            if (!is_array($notes)) {
                continue;
            }
            $this->processTaskBulk($notes);
        }

    }

    /**
     * Process a single task file
     */
    private function processTaskBulk($notes)
    {
        $count = 0;
        foreach ($notes as $note) {

            if ($this->checkIfMigrated($note->id, EXECUTED_TASKS_CACHE)) {
                echo 'Skipped task : Already migrated ' . $note->id . PHP_EOL;
                continue;
            }

            if (!isset($note->party)) {
                echo 'Skipped task : Party references not found.' . PHP_EOL;
                continue;
            }


            $associations = [
                'contactIds' => [],
                "companyIds" => []
            ];
            $party = $note->party;
            switch ($party->type) {
                case "person":
                    $party_id = $this->getHubspotIDForContact($party->id);
                    if (!is_null($party_id)) {
                        $associations['contactIds'][] = $party_id;
                    }
                    break;
                case "organisation":
                    $party_id = $this->getHubspotIDForCompany($party->id);
                    if (!is_null($party_id)) {
                        $associations['companyIds'][] = $party_id;
                    }
                    break;
            }
            if ((count($associations['companyIds']) == 0) && ($associations['contactIds'] == 0)) {
                echo 'Skipped a task : No associations found' . PHP_EOL;
                continue;
            }


            $capsule_owner_id = 0;
            if (isset($note->owner->id)) {
                $capsule_owner_id = $note->owner->id;
            }
            $note->owner_id = $this->getHubspotOwnerID($capsule_owner_id);
            if (is_null($note->owner_id)) {
                echo " !!!! >> OWNER ID NOT FOUND FOR - " . $capsule_owner_id . PHP_EOL;
            }

            $note->type = 'task';

            switch ($note->status) {
                case "COMPLETED":
                    $note->status_hubs = 'COMPLETED';
                    break;
                case "PENDING":
                    $note->status_hubs = 'IN_PROGRESS';
                    break;
                default:
                    $note->status_hubs = 'NOT_STARTED';
            }


            $compiled_note = $this->getCompiledEngagement($note, $associations);
            $response = $this->createEngament($compiled_note);
            $body = $response->body;
            if (isset($body->status) && $body->status == 'error') {
                echo 'Error : ' . $body->message . PHP_EOL;
            } else {
                echo 'Processed a task' . PHP_EOL;
                $this->markAsMigrated($note->id, EXECUTED_TASKS_CACHE);
            }

        }
    }



    /**
     * delete tasks
     */
    public function deletehubspottasks()
    {
        $hubspot = $this->getHubspot();
        $hashMap = [];
        $vidOffset = null;
        while (true) {
            $params = [
                'limit' => 249
            ];
            if (!is_null($vidOffset)) {
                $params['offset'] = $vidOffset;
            }
            $response = $hubspot->request('/engagements/v1/engagements/paged', 'get', null, $params);
            $body = $response->body;

            if (!isset($body->results)) {
                break;
                echo 'body empty' . PHP_EOL;
            }
            $engagements = $body->results;
            foreach ($engagements as $engagement) {
                if ($engagement->engagement->type !== 'TASK') {
                    continue;
                }

                $id = $engagement->engagement->id;
                if (is_null($id)) {
                    continue;
                }
                try {
                    $response_d = $hubspot->request('/engagements/v1/engagements/' . $id, 'delete');
                    echo " Deleted Task ID -" . $engagement->engagement->id . PHP_EOL;
                } catch (\Exception $e) {
                    echo " Failed -" . $e->getMessage() . PHP_EOL;
                }
                

            }
            if (!(isset($body->{'hasMore'}) && $body->{'hasMore'})) {
                break;
            }
            if (!isset($body->{'offset'})) {
                break;
            }
            $vidOffset = $body->{'offset'};
        }
    }

}