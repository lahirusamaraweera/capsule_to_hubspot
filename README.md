# README #

Capsule to Hubspot CRM Data migration Tool

### How do I get set up? ###

* Generate API Access token and store them token folder as follows
* Capsule token -> ./token/capsules.key
* Hubspot token -> ./token/hubspot.key


### How do I run? ###
* Cache capsule parties ( person & organisation )           ->  php execute.php cachecapsule
* Define Field Mapping information ( company and contact field mapping & user ID mapping ) at .data/*
* Start migration                                           ->  php execute.php startMigration




