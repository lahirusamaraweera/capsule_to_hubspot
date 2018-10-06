# README #

Capsule to Hubspot CRM Data migration Tool

### How do I get set up? ###

* Generate API Access token and store them token folder as follows
* Capsule token -> ./token/capsules.key
* Hubspot token -> ./token/hubspot.key

* For caching make sure to have following folders
* ./cache/capsules
* ./cache/capsules_notes
* ./cache/capsules_tasks
* ./cache/capsules_entries
* ./cache/capsules_parties

### How do I run? ###
* cache capsule parties ( person & organisation )           ->  php execute.php cachecapsuleparties
* Create Companies and Contacts in Hubspot                  ->  php execute.php migrateparties
* cache notes, tasks, ID mapping and capsules entries       ->  php execute.php cachedata
* Search and cache a tempory ID                             ->  php execute.php searchcontact nikki@vetsto.comnet
* Migrate Notes for cached contact ID                        ->  php execute.php createNotes cached 200


### Procedure ###
* cache capsule parties
* create companies without life cycle state
* create contacts
* cache ID mapping
* create engagements ( notes , tasks , emails )
* calculate opportunities
* update opportunities

