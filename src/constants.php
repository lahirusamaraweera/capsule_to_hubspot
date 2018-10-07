<?php
include('./src/capsules.php');
include('./src/hubspot.php');
include('./src/partymigrate.php');
include('./src/notesmigrate.php');
include('./src/taskmigrate.php');
include('./src/deletecache.php');
include('./src/captohubspot.php');

define('CAPSULES_KEY', __DIR__.'/../token/capsule.key');
define('HUBSPOT_KEY', __DIR__.'/../token/hubspot.key');


define('CONFIG_FILE_PATH', __DIR__.'/../config.json');
define('EXECUTED_NOTES_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_notes.json');
define('EXECUTED_PARTIES_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_parties.json');
define('EXECUTED_TASKS_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_tasks.json');
define('EXECUTED_CCA_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_cc_association.json');
define('EXECUTED_PARTY_REFINE_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_party_refine_association.json');
define('EXECUTED_COMPANY_REFINE_CACHE', __DIR__.'/../cache/proceed_cache/proceeded_company_refine_association.json');

define('ERROR_LOG_PATH', __DIR__.'/../cache/proceed_cache/error_log.json');

define('CAPSULES_PARTIES_LOCATION', './cache/capsules_parties/');
define('CAPSULES_NOTES_LOCATION', './cache/capsules_notes/');
define('CAPSULES_TASKS_LOCATION', './cache/capsules_tasks/');
define('OWNER_DETAILS_LOCATION', './cache/owner_details/');

define('ID_CACHE_PATH', './cache/testid.value');
define('COMPANY_MAPPING_PATH', './data/company_fieldsmapping.json');
define('CONTACT_MAPPING_PATH', './data/contact_fieldsmapping.json');
define('CONTACT_CONDITIONAL_POPULATION_RULES_PATH', './data/conditional_populations/contact_field_rules.json');
define('COMPANY_CONDITIONAL_POPULATION_RULES_PATH', './data/conditional_populations/company_field_rules.json');
define('OWNER_MAPPING_PATH', './data/owner_id_mapping.json');