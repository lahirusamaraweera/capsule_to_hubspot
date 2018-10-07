# README #

Capsule to Hubspot CRM Data migration Tool

### How do I get set up? ###

* Generate API Access token and store them as follows
* Capsule token -> ./token/capsules.key
* Hubspot token -> ./token/hubspot.key


### How do I run? ###
* Cache capsule parties( person & organisation ), Notes, Email, Tasks  [ command ==>  php execute.php cachecapsule ]
* Define Field Mapping information ( company and contact field mapping & user ID mapping ) at .data/*
* Define conditional population rules if required at .data/conditional_populations
* Start migration [ command ==>  php execute.php startMigration ]




