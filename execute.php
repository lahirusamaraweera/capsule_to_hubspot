<?php
require __DIR__ . '/vendor/autoload.php';
include('./src/constants.php');


/**
 * Cache capsule data
 */
function cachecapsule(){
    $nwe = new capToHubspot();
    
    //cache capsule parties
    $notes_page = $nwe->cacheCapsuleParties();
    echo ">>>>> >>>>>> Cached Capsule Accounts {$notes_page} pages" . PHP_EOL;
    
    //cache capsule notes
    $notes_page = $nwe->cacheCapsuleNotes();
    echo ">>>>> >>>>>> cached {$notes_page} note pages" . PHP_EOL;

    // cache capsule tasks
    $task_page = $nwe->cacheCapsuleTasks();
    echo ">>>>> >>>>>> cached {$task_page} task pages" . PHP_EOL;
    
    echo ">>>>> Please define field mapping and run -> php execute.php startmigration" . PHP_EOL;
    

}

/**
 * Start migration
 */
function startMigration(){
    $nwe = new capToHubspot();
    
    // party migration
    echo ">>>>> >>>>>> Creating notes and emails on Hubspot" . PHP_EOL;
    $nwe->processCacheParties();

    // cache contact IDs
    $count = $nwe->cacheContactsCapsulesIDsWithHubspotIDs();
    echo ">>>>> >>>>>> Read and cached {$count} Hubspot contacts" . PHP_EOL;

    // cache company IDs
    $count = $nwe->cacheCompaniesCapsulesIDsWithHubspotIDs();
    echo ">>>>> >>>>>> Read and cached {$count} Hubspot companies" . PHP_EOL;

    echo ">>>>> >>>>>> Migrating notes and emails to the Hubspot" . PHP_EOL;
    $nwe->processCacheNotes( ['email', 'note']);

    echo ">>>>> >>>>>> Migrating Tasks to the Hubspot" . PHP_EOL;
    $nwe->processCacheTask();

    echo ">>>>> >>>>>> Associating contacts with companies" . PHP_EOL;
    $nwe->processContactCompanyAssociation();
}


/**
 * Cache owner details
 */
function cacheownerdetails(){
    $nwe = new capToHubspot();
    $nwe->cacheOwnerDetails();
    echo ">>>>> >>>>>> Read and cached owner details at ./cache/owner_details" . PHP_EOL;
}

/**
 * delete huspot tasks
 */
function deletehubspottasks(){
    $nwe = new capToHubspot();  
    $count = $nwe->deletehubspottasks();
    echo ">>>>> >>>>>> Deleted hubspot tasks" . PHP_EOL;
    
}

/**
 * Search hubspot contact by email
 */
function searchcontact($email){
    $nwe = new capToHubspot();
    $contact = $nwe->readHubspotContactsByEmail($email);
    echo json_encode($contact, JSON_PRETTY_PRINT);
    if(isset($contact->vid)){
        file_put_contents(ID_CACHE_PATH, $contact->vid);
        echo 'Cached Contact ID '. $contact->vid .PHP_EOL;
    }else{
        echo json_encode($contact, JSON_PRETTY_PRINT).PHP_EOL;
    }
    
}
/**
 * Search hubspot company by domain
 */
function searchcompany($domain){
    $nwe = new capToHubspot();
    $companies = $nwe->readHubspotComanyByDomain($domain);
    foreach($companies as $company){
        echo 'company : '.$company->companyId.PHP_EOL;
    }
   
    
}


if(!isset($argv[1])){
    die('At least function name is required.');
}
// execution
try {
    call_user_func_array($argv[1], array_slice($argv, 2));
} catch (\Exception $e) {
    echo 'ERROR : '.$e->getMessage().PHP_EOL;
}