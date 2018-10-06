SELECT 
c.`ID` AS `Capsule ID`,
c.`first Name` AS `First Name`,
c.`last Name` AS `Last Name`,
o.`Organization` AS `Company Name`,
'' as `Lead Stage`,
o.`Lead_Origin` AS `Source`,
'' as `Campaign`, 
'' as `Industry type`, 
o.`Company Type` AS `Business type`,
'' as `Unqalified reason`,
'' as `Product`,
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
t.`Owner` AS `Owner`,
'' As `Has authority`,
'' AS `Do not disturb`,
'' AS `Medium`,
'' AS `Keyword`,
c.`Time Zone` AS `Time zone`,
o.`Facebook` AS `Facebook`,
o.`Twitter` AS `Twitter`,
o.`LinkedIn` AS `LinkedIn`,
'' AS `Territory`,
c.`Address Street` AS `Company address`,
c.`City` AS `Company City`,
c.`State` AS `Company State`,
c.`Postcode` AS `Company Zipcode`,
c.`Country` AS `Company Country`,
'' AS `Number of employees`,
'' AS `Company annual revenue`,
o.`Work Website` AS `Company website`,
'' AS `Company phone`,
'' AS `Deal name`,
'' AS `Deal value`,
'' AS `Deal expected close date`,
-- c.`created` AS `Created at`,
DATE_FORMAT(LEFT(c.`created`,10), "%d-%m-%Y") AS `Created at`,
o.`Hospital Number` AS `Hospital number`,
DATE_FORMAT(LEFT(c.`Last Contacted`,10), "%d-%m-%Y") AS `Last contacted at`,
o.`Tags` AS `Parent Company`,
c.`reason` AS `Reason for contact`,
o.`Referrals` AS `Source`,
DATE_FORMAT(LEFT(c.`Updated`,10), "%d-%m-%Y") AS `Updated at`,
o.`Phone Number` AS `Phone Number`,
o.`Number of vets` AS `Number of vets`,
o.`PMS` AS `PIMS`,
o.`number_of_branches` AS `Number of Branches`,
o.`current_pms` AS `PIMS`,
o.`FTE Vets` AS `Number of FTE vets`,
t.`Milestone` AS `Milestone`

-- c.`History` AS `History`

FROM contacts AS c 
LEFT JOIN organizations AS o 
	ON c.Organization = o.Organization 
LEFT JOIN opportunities AS t 
	ON c.Organization = t.`Contact Name`
WHERE 
	o.`Client Status` IN ('Competition', 'Med_Lead', 'No_Reply', 'Poor_Lead', 'Strong_Lead','Demo_booked') 
AND 
	( Milestone NOT IN ('Demo done') OR Milestone IS NULL )
;
