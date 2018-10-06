SELECT 
o.`ID` AS `Capsule ID`,
'' AS `Annual Revenue`,
'' AS `Industry Type`,
'' AS `Number of Employees`,
'' AS `Owner (Email)`,
'' as `Territory`,
o.`Address Street` AS `Address`,
o.`City` AS `City`,
o.`Company type` AS `Business Type`,
o.`Country` AS `Country`,
o.`Facebook` AS `Facebook`,
o.`LinkedIn` AS `LinkedIn`,
o.`Organization` AS `Account Name (Mandatory)`,
IF( '' = o.`Phone Number`, IF(''= o.`Work Phone`, Null, o.`Work Phone`), o.`Phone Number`) AS `Primary Phone`,
o.`Phone Number` AS `Phone`,
o.`Work Phone` AS `Work`,
o.`Postcode` AS `Zipcode`,
o.`State` AS `State`,
o.`Twitter` AS `Twitter`,
IF( '' = o.`Work Website`, IF(''= o.`Home Website`, IF(''= o.`Work Website`, Null, o.`Work Website`), o.`Home Website`), o.`Work Website`) AS `Primary Website`,
o.`Work Website` AS `website`,
CASE 
	WHEN  o.`Account Type` = 'Independent' THEN 'Independent'
    WHEN  o.`Account Type` = 'Corporate sub account' THEN 'Corporate Sub Account'
    WHEN  o.`Account Type` = 'Corporate master account' THEN 'Corporate Master Account'
    ELSE 'Independent'
END AS `Account type`,
-- o.`Account Type` AS `Account type`,
DATE_FORMAT(LEFT(o.`created`,10), "%d-%m-%Y") AS `Created at`,
IF( '' = o.`Work Email`, IF(''= o.`Email Address`, Null, o.`Email Address`), o.`Work Email`) AS `Primary Email`,
o.`Work Email` AS `Email 1`,
o.`Email Address` AS `Email 2`,
o.`Hospital Number` AS `Hospital Number`,
DATE_FORMAT(LEFT(o.`Last Contacted`,10), "%d-%m-%Y") AS `Last contacted at`,
o.`FTE Vets` AS `FTE Vets`,
o.`Number of vets` AS `Number of vets`,
o.`number_of_branches` AS `Number of branches`,
o.`Tags` AS `Parent Account`,
o.`PMS` AS `PIMS`,
o.`current_pms` AS `PIMS`,
o.`Reminders 1` AS `Reminder System`,
o.`Reminder 2` AS `Reminder System`,
o.`Capterra` AS `Reviewed`,
DATE_FORMAT(LEFT(o.`Updated`,10), "%d-%m-%Y") AS `Updated at`
-- FORMAT(LEFT(o.`Updated`,10), 'yyyy/MM/dd', 'en-US') AS `re`,
-- RIGHT(o.`History`, 20000) AS `History`

FROM organizations AS o
LEFT JOIN opportunities AS t 
	ON o.Organization = t.`Contact Name`
WHERE 
		o.`Client Status` IN ('Client OABP', 'Onboarding', 'Client_OABP/WA')
	-- AND
		-- need jessie's input 
		-- t.`Milestone` IN ('On boarding')  
;	
