SELECT
c.`ID` AS `Capsule ID`, 
c.`first Name` AS `First Name`,
IF(''= c.`last Name`,'Unknown', c.`last Name`) AS `Last Name`,
'' as `Source`,
'' as `Campaign`,
c.`Job Title` AS `Job title`,
'' as `Department`,
IF( '' = c.`Work Email`, IF(''= c.`Email Address`, IF(''= o.`Email Address`, IF(''= o.`Work Email`, Null, o.`Work Email`), o.`Email Address`), c.`Email Address`), c.`Work Email`) AS `Primary Email`,
c.`Work Email` AS `Email 1`,
c.`Email Address` AS `Email 2`,
o.`Work Email` AS `Email 3`,
o.`Email Address` AS `Email 4`,
IF( '' = o.`Phone Number`, IF(''= o.`Work Phone`, IF(''= c.`Work Phone`, IF(''= c.`Phone Number`, Null, c.`Phone Number`), c.`Work Phone`), o.`Work Phone`), o.`Phone Number`) AS `Primary Phone`,
o.`Work Phone` AS `Work 1`,
o.`Phone Number` As `Work 2`,
c.`Mobile Phone` AS `Mobile`,
c.`Phone Number` As `Phone`,
o.`Address Street` AS `Address`,
o.`City` AS `City`,
o.`State` AS `State`,
o.`Postcode` AS `Zipcode`,
o.`Country` AS `Country`,
'' AS `Owner`,
'' As `Has authority`,
'' AS `Do not disturb`,
'' AS `Medium`,
'' AS `Keyword`,
c.`Time Zone` AS `Time zone`,
o.`Facebook` AS `Facebook`,
o.`Twitter` AS `Twitter`,
o.`LinkedIn` AS `LinkedIn`,
o.`Organization` AS `Account Name`,
o.`Address Street` AS `Account address`,
o.`City` AS `Account City`,
o.`State` AS `Account State`,
o.`Country` AS `Account Country`,
o.`Postcode` AS `Account Zipcode`,
'' as `Account Industry Type`,
'' as `Account Business Type`,
'' as `Account Number of Employees`,
'' as `Account Annual Revenue`,
'' as `Account Website`,
'' as `Account Phone`,
'' as `Account Facebook`,
'' as `Account Twitter`,
'' as `Account Linkedin`,
'' as `Account Territory`,
'' as `Account Owner`,
DATE_FORMAT(LEFT(c.`created`,10), "%d-%m-%Y") AS `Created at`,
DATE_FORMAT(LEFT(c.`Updated`,10), "%d-%m-%Y") AS `Updated at`,
DATE_FORMAT(LEFT(c.`Last Contacted`,10), "%d-%m-%Y") AS `Last contacted at`,
c.`reason` AS `Reason for contact`,
o.`Referrals` AS `Source`,
o.`Lead_origin` AS `Source`
-- , c.`History` AS `History`

FROM contacts AS c 
LEFT JOIN organizations AS o 
	ON c.Organization = o.Organization
WHERE 
		o.`Client Status` NOT IN ('Client OABP', 'Onboarding', 'Client_OABP/WA', 'Not_interested', 'Not_supported','Stopped')
	AND
		(
			o.`History` LIKE '%demo %'
        OR 
			o.`History` LIKE '%Demo %'
        )
	AND 
		c.`History` NOT LIKE '%lost%'
	AND 
		c.`History` NOT LIKE '%won%'
        
;