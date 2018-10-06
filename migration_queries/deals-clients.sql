SELECT 
o.`ID` AS `Capsule ID`,
o.`Organization` AS `Deal Name (Mandatory)`,
'' AS `Type`,
DATE_FORMAT(LEFT(t.`Expected Closed Date`,10), "%d-%m-%Y") AS `Expected close date`,
DATE_FORMAT(LEFT(t.`Closed Date`,10), "%d-%m-%Y") AS `Close date`,
'' AS `Product`,
'' AS `Payment status`,
t.`Probability` AS `Probability`,
'' AS `Owner email Id`,
t.`Milestone` AS `Deal stage`,
'' AS `Account name`,
'' AS `Related contact email Id`,
'' AS `Source`,
'' AS `Campaign`,
'' AS `Deal Pipeline`,
o.`Monthly fee` AS `Deal Value (Mandatory)`,
DATE_FORMAT(LEFT(o.`Billing Start Date`,10), "%d-%m-%Y") AS `Billing start date`,
o.`Discount` AS `Discount`,
o.`Invoicing` AS `Invoicing`,
o.`Set-up Fee` AS `Set-up Fee`,
o.`Payment Plan` AS `Payment Plan`,
o.`Payment note` AS `Discount`,
t.`Opportunity Description` AS `Opportunity Description`,
DATE_FORMAT(LEFT(t.`created`,10), "%d-%m-%Y") AS `Created at`,
DATE_FORMAT(LEFT(t.`Updated`,10), "%d-%m-%Y") AS `Updated at`,
t.`Owner` AS `Opportunity Owner`,
t.`Currency` AS `Opportunity Currency`,
t.`Value per Duration`  AS `Value per month`,
t.`Milestone` AS `Milestone`,
t.`Probability` AS `Probability`,
t.`Currency` AS `Currency`,
o.`Tags` AS `Tags`
-- ,o.`History` AS `History`

FROM `organizations` AS o 
LEFT JOIN opportunities AS t 
	ON o.Organization = t.`Contact Name`
WHERE 
	o.`Client Status` IN ('Client OABP', 'Onboarding', 'Client_OABP/WA', 'Stopped')
AND
	( 	Milestone NOT IN ('Lead') 
		OR 
        Milestone IS NULL 
	)
;