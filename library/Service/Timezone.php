<?php
class Service_Timezone{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		if (!Zend_Auth::getInstance()->hasIdentity()){
			return -1;
		}
	}
	public function get(){
	   	$db = Zend_Db_Table::getDefaultAdapter();
	    $db->query('set @timezone_rank=0;');
	    $db->query('set @current_timezone=1;');
	    
	    $sql="
	    select timezone.country,timezone.country_code,timezone.city, x.* from
			(select timezone_id, offset, is_dst,
			@country_rank:= IF(@current_country = timezone_id, @country_rank + 1, 1) AS country_rank,
			@current_country := timezone_id as current_timezone
			from timezone_period
			where change_utc_time< now()
			order by timezone_id asc, change_utc_time desc) as x
			right join timezone on timezone.id = x.timezone_id
		where country_rank=1
		order by timezone.country,timezone.city";
	    
	    //only the basic mysql handling can the session variable be assigned in a correct order and thus correct result
	    return Common::phpMysqlQuery($sql);
	}
	
	public function formatOffset($offset){
	    $offset*=3600;
	    $offsetHours = round(abs($offset)/3600);
	    $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
	    return ($offset < 0 ? '-' : '+') 
                . ($offsetHours < 10 ? '0' : '') . $offsetHours 
                . ':' 
                . ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;
	}
	
	public function getCountry(){
		$select=
			$this->db->select()
				->union(array(
					$this->db->select()
						->distinct()			
						->from(array('timezone'),array('country', 'country_code'))
						->where('is_oversea=0 AND is_sar=0')			
					,
					$this->db->select()
						->distinct()			
						->from(array('timezone'),array('district', 'country_code'))
						->where('is_oversea=1 OR is_sar=1')
					))
				->order('country ASC');;
		$result['country']=$this->db->fetchAll($select);
		return $result;
	}

	public function getCountryCodebyCountry($country_name){
		$select=
			$this->db->select()
				->from(array('timezone'),array('country_code'))
				->where('country="'.$country_name.'"');
		$result['country_code']=$this->db->fetchAll($select);
		$resultCountryCode = $result['country_code'][0]['country_code'];
		return $resultCountryCode;
	}	
	
	public function getCity($country_code){
		$select=
			$this->db->select()
				->from(array('timezone'),array('city'))
				->where('country_code="'.$country_code.'"')
				->order('city ASC');
		$result['city']=$this->db->fetchAll($select);
		return $result;
	}
	
	//it includes deleting entries of empty countries, updating continent, country code and city names by php_timezone, as well as some special treatments for some sar/oversea regions
	public function addTimezonePatch($table='timezone'){
		set_time_limit(0);
		//delete entries of empty country
	    //$this->db->delete('timezone',"country=''");
	    //echo "deleted entries of empty country <br />";
	    /*
	     * code to country
	     */
	    $codeToCountry=array(
	    	'AF'=>'Afghanistan',
		   'AX'=>'Aland Islands',
		   'AL'=>'Albania',
		   'DZ'=>'Algeria',
		   'AS'=>'American Samoa',
		   'AD'=>'Andorra',
		   'AO'=>'Angola',
		   'AI'=>'Anguilla',
		   'AQ'=>'Antarctica',
		   //'AG'=>'Antigua and Barbuda',
		   'AG'=>'Antigua and Barbuda',
		   'AR'=>'Argentina',
		   'AM'=>'Armenia',
		   'AW'=>'Aruba',
		   'AU'=>'Australia',
		   'AT'=>'Austria',
		   'AZ'=>'Azerbaijan',
		   //'BS'=>'Bahamas the',
		   'BS'=>'Bahamas',
		   'BH'=>'Bahrain',
		   'BD'=>'Bangladesh',
		   'BB'=>'Barbados',
		   'BY'=>'Belarus',
		   'BE'=>'Belgium',
		   'BZ'=>'Belize',
		   'BJ'=>'Benin',
		   'BM'=>'Bermuda',
		   'BT'=>'Bhutan',
		   'BO'=>'Bolivia',
		   'BA'=>'Bosnia and Herzegovina',
		   'BW'=>'Botswana',
		   'BV'=>'Bouvet Island (Bouvetoya)',
		   'BR'=>'Brazil',
		   'IO'=>'British Indian Ocean Territory (Chagos Archipelago)',
		   'VG'=>'British Virgin Islands',
		   //'BN'=>'Brunei Darussalam',
		   'BN'=>'Brunei (Brunei Darussalam)',
		   'BG'=>'Bulgaria',
		   'BF'=>'Burkina Faso',
		   'BI'=>'Burundi',
		   'KH'=>'Cambodia',
		   'CM'=>'Cameroon',
		   'CA'=>'Canada',
		   'CV'=>'Cape Verde',
		   'KY'=>'Cayman Islands',
		   'CF'=>'Central African Republic',
		   'TD'=>'Chad',
		   'CL'=>'Chile',
		   'CN'=>'China',
		   'CX'=>'Christmas Island',
		   'CC'=>'Cocos (Keeling) Islands',
		   'CO'=>'Colombia',
		   //'KM'=>'Comoros the',
		   'KM'=>'Comoros (Comores)',
		   'CD'=>'Congo',
		   'CG'=>'Congo, Democratic Republic of the',
		   'CK'=>'Cook Islands',
		   'CR'=>'Costa Rica',
		   'CI'=>"Côte d'Ivoire",
		   'HR'=>'Croatia',
		   'CU'=>'Cuba',
		   'CY'=>'Cyprus',
		   'CZ'=>'Czech Republic',
		   'DK'=>'Denmark',
		   'DJ'=>'Djibouti',
		   'DM'=>'Dominica',
		   'DO'=>'Dominican Republic',
		   'EC'=>'Ecuador',
		   'EG'=>'Egypt',
		   'SV'=>'El Salvador',
		   'GQ'=>'Equatorial Guinea',
		   'ER'=>'Eritrea',
		   'EE'=>'Estonia',
		   'ET'=>'Ethiopia',
		   'FO'=>'Faroe Islands',
		   'FK'=>'Falkland Islands (Malvinas)',
		   //'FJ'=>'Fiji the Fiji Islands',
		   'FJ'=>'Fiji',
		   'FI'=>'Finland',
		   //'FR'=>'France, French Republic',
		   'FR'=>'France',
		   'GF'=>'French Guiana',
		   'PF'=>'French Polynesia',
		   'TF'=>'French Southern Territories',
		   'GA'=>'Gabon',
		   //'GM'=>'Gambia the',
		   'GM'=>'Gambia',
		   'GE'=>'Georgia',
		   'DE'=>'Germany',
		   'GH'=>'Ghana',
		   'GI'=>'Gibraltar',
		   'GR'=>'Greece',
		   'GL'=>'Greenland',
		   'GD'=>'Grenada',
		   'GP'=>'Guadeloupe',
		   'GU'=>'Guam',
		   'GT'=>'Guatemala',
		   'GG'=>'Guernsey',
		   'GN'=>'Guinea',
		   'GW'=>'Guinea-Bissau',
		   'GY'=>'Guyana',
		   'HT'=>'Haiti',
		   'HM'=>'Heard Island and McDonald Islands',
		   //'VA'=>'Holy See (Vatican City State)',
		   'VA'=>'Vatican City',
		   'HN'=>'Honduras',
		   'HK'=>'Hong Kong',
		   'HU'=>'Hungary',
		   'IS'=>'Iceland',
		   'IN'=>'India',
		   'ID'=>'Indonesia',
		   'IR'=>'Iran',
		   'IQ'=>'Iraq',
		   'IE'=>'Ireland',
		   'IM'=>'Isle of Man',
		   'IL'=>'Israel',
		   'IT'=>'Italy',
		   'JM'=>'Jamaica',
		   'JP'=>'Japan',
		   'JE'=>'Jersey',
		   'JO'=>'Jordan',
		   'KZ'=>'Kazakhstan',
		   'KE'=>'Kenya',
		   'KI'=>'Kiribati',
		   //'KP'=>'Korea',
		   'KP'=>'North Korea',
		   //'KR'=>'Korea',
		   'KR'=>'South Korea',
		   'KW'=>'Kuwait',
		   //'KG'=>'Kyrgyz Republic',
		   'KG'=>'Kyrgyzstan',
	       //'LA'=>'Lao',
	       'LA'=>'Laos',
		   'LV'=>'Latvia',
		   'LB'=>'Lebanon',
		   'LS'=>'Lesotho',
		   'LR'=>'Liberia',
		   //'LY'=>'Libyan Arab Jamahiriya',
		   'LY'=>'Libya',
		   'LI'=>'Liechtenstein',
		   'LT'=>'Lithuania',
		   'LU'=>'Luxembourg',
		   //'MO'=>'Macao',
		   'MO'=>'Macau',
		   'MK'=>'Macedonia',
		   //'MG'=>'Madagascar',
		   'MG'=>'Madagascar (Madagasikara)',
		   'MW'=>'Malawi',
		   'MY'=>'Malaysia',
		   'MV'=>'Maldives',
		   'ML'=>'Mali',
		   'MT'=>'Malta',
		   'MH'=>'Marshall Islands',
		   'MQ'=>'Martinique',
		   'MR'=>'Mauritania',
		   'MU'=>'Mauritius',
		   'YT'=>'Mayotte',
		   'MX'=>'Mexico',
		   'FM'=>'Micronesia',
		   'MD'=>'Moldova',
		   'MC'=>'Monaco',
		   'MN'=>'Mongolia',
		   'ME'=>'Montenegro',
		   'MS'=>'Montserrat',
		   'MA'=>'Morocco',
		   'MZ'=>'Mozambique',
		   'MM'=>'Myanmar (Burma)',
		   'NA'=>'Namibia',
		   'NR'=>'Nauru',
		   'NP'=>'Nepal',
		   'AN'=>'Netherlands Antilles',
		   //'NL'=>'Netherlands the',
		   'NL'=>'Netherlands',
		   'NC'=>'New Caledonia',
		   'NZ'=>'New Zealand',
		   'NI'=>'Nicaragua',
		   'NE'=>'Niger',
		   'NG'=>'Nigeria',
		   'NU'=>'Niue',
		   'NF'=>'Norfolk Island',
		   'MP'=>'Northern Mariana Islands',
		   'NO'=>'Norway',
		   'OM'=>'Oman',
		   'PK'=>'Pakistan',
		   'PW'=>'Palau',
		   'PA'=>'Panama',
		   'PG'=>'Papua New Guinea',
		   'PY'=>'Paraguay',
		   'PE'=>'Peru',
		   'PH'=>'Philippines',
		   //'PN'=>'Pitcairn Islands',
		   'PN'=>'Pitcairn',
		   'PL'=>'Poland',
		   //'PT'=>'Portugal, Portuguese Republic',
		   'PS'=>'Palestinian Territories',
		   'PT'=>'Portugal',
		   'PR'=>'Puerto Rico',
		   'QA'=>'Qatar',
		   'RE'=>'Reunion',
		   'RO'=>'Romania',
		   //'RU'=>'Russian Federation',
		   'RU'=>'Russia',
		   'RW'=>'Rwanda',
		   'BL'=>'Saint Barthelemy',
		   'SH'=>'Saint Helena',
		   'KN'=>'Saint Kitts and Nevis',
		   'LC'=>'Saint Lucia',
		   'MF'=>'Saint Martin',
		   'PM'=>'Saint Pierre and Miquelon',
		   'VC'=>'Saint Vincent and the Grenadines',
		   'WS'=>'Samoa',
		   'SM'=>'San Marino',
		   'ST'=>'Sao Tome and Principe',
		   'SA'=>'Saudi Arabia',
		   'SN'=>'Senegal',
		   'RS'=>'Serbia',
		   'SC'=>'Seychelles',
		   'SL'=>'Sierra Leone',
		   'SG'=>'Singapore',
		   //'SK'=>'Slovakia (Slovak Republic)',
		   'SK'=>'Slovakia',
		   'SI'=>'Slovenia',
		   'SB'=>'Solomon Islands',
		   'SO'=>'Somalia, Somali Republic',
		   'ZA'=>'South Africa',
		   'GS'=>'South Georgia and the South Sandwich Islands',
		   'ES'=>'Spain',
		   'LK'=>'Sri Lanka',
		   'SD'=>'Sudan',
		   'SR'=>'Suriname',
		   'SJ'=>'Svalbard & Jan Mayen Islands',
		   'SZ'=>'Swaziland',
		   'SE'=>'Sweden',
		   //'CH'=>'Switzerland, Swiss Confederation',
		   'CH'=>'Switzerland',
		   //'SY'=>'Syrian Arab Republic',
		   'SY'=>'Syria',
		   'TW'=>'Taiwan',
		   'TJ'=>'Tajikistan',
		   'TZ'=>'Tanzania',
		   'TH'=>'Thailand',
		   'TL'=>'Timor-Leste',
		   'TG'=>'Togo',
		   'TK'=>'Tokelau',
		   'TO'=>'Tonga',
		   'TT'=>'Trinidad and Tobago',
		   'TN'=>'Tunisia',
		   'TR'=>'Turkey',
		   'TM'=>'Turkmenistan',
		   'TC'=>'Turks and Caicos Islands',
		   'TV'=>'Tuvalu',
		   'UG'=>'Uganda',
		   'UA'=>'Ukraine',
		   'AE'=>'United Arab Emirates',
		   'GB'=>'United Kingdom',
		   //'US'=>'United States of America',
		   'US'=>'United States',
		   //'UM'=>'United States Minor Outlying Islands',
		   'UM'=>'United States minor outlying islands',
		   'VI'=>'United States Virgin Islands',
		   //'UY'=>'Uruguay, Eastern Republic of',
		   'UY'=>'Uruguay',
		   'UZ'=>'Uzbekistan',
		   'VU'=>'Vanuatu',
		   'VE'=>'Venezuela',
		   'VN'=>'Vietnam',
		   'WF'=>'Wallis and Futuna',
		   'EH'=>'Western Sahara',
		   'YE'=>'Yemen',
		   'ZM'=>'Zambia',
	   		'ZW'=>'Zimbabwe'
	   	);
	   	
   		/*
    	 * code => continent 
    	 **/
	   	$codeToContinent=array(
		   'AF'=>'Asia',
		   'AX'=>'Europe',
		   'AL'=>'Europe',
		   'DZ'=>'Africa',
		   'AS'=>'Oceania',
		   'AD'=>'Europe',
		   'AO'=>'Africa',
		   'AI'=>'North America',
		   'AQ'=>'Antarctica',
		   'AG'=>'North America',
		   'AR'=>'South America',
		   'AM'=>'Asia',
		   'AW'=>'North America',
		   'AU'=>'Oceania',
		   'AT'=>'Europe',
		   'AZ'=>'Asia',
		   'BS'=>'North America',
		   'BH'=>'Asia',
		   'BD'=>'Asia',
		   'BB'=>'North America',
		   'BY'=>'Europe',
		   'BE'=>'Europe',
		   'BZ'=>'North America',
		   'BJ'=>'Africa',
		   'BM'=>'North America',
		   'BT'=>'Asia',
		   'BO'=>'South America',
		   'BA'=>'Europe',
		   'BW'=>'Africa',
		   'BV'=>'Antarctica',
		   'BR'=>'South America',
		   'IO'=>'Asia',
		   'VG'=>'North America',
		   'BN'=>'Asia',
		   'BG'=>'Europe',
		   'BF'=>'Africa',
		   'BI'=>'Africa',
		   'KH'=>'Asia',
		   'CM'=>'Africa',
		   'CA'=>'North America',
		   'CV'=>'Africa',
		   'KY'=>'North America',
		   'CF'=>'Africa',
		   'TD'=>'Africa',
		   'CL'=>'South America',
		   'CN'=>'Asia',
		   'CX'=>'Asia',
		   'CC'=>'Asia',
		   'CO'=>'South America',
		   'KM'=>'Africa',
		   'CD'=>'Africa',
		   'CG'=>'Africa',
		   'CK'=>'Oceania',
		   'CR'=>'North America',
		   'CI'=>'Africa',
		   'HR'=>'Europe',
		   'CU'=>'North America',
		   'CY'=>'Asia',
		   'CZ'=>'Europe',
		   'DK'=>'Europe',
		   'DJ'=>'Africa',
		   'DM'=>'North America',
		   'DO'=>'North America',
		   'EC'=>'South America',
		   'EG'=>'Africa',
		   'SV'=>'North America',
		   'GQ'=>'Africa',
		   'ER'=>'Africa',
		   'EE'=>'Europe',
		   'ET'=>'Africa',
		   'FO'=>'Europe',
		   'FK'=>'South America',
		   'FJ'=>'Oceania',
		   'FI'=>'Europe',
		   'FR'=>'Europe',
		   'GF'=>'South America',
		   'PF'=>'Oceania',
		   'TF'=>'Antarctica',
		   'GA'=>'Africa',
		   'GM'=>'Africa',
		   'GE'=>'Asia',
		   'DE'=>'Europe',
		   'GH'=>'Africa',
		   'GI'=>'Europe',
		   'GR'=>'Europe',
		   'GL'=>'North America',
		   'GD'=>'North America',
		   'GP'=>'North America',
		   'GU'=>'Oceania',
		   'GT'=>'North America',
		   'GG'=>'Europe',
		   'GN'=>'Africa',
		   'GW'=>'Africa',
		   'GY'=>'South America',
		   'HT'=>'North America',
		   'HM'=>'Antarctica',
		   'VA'=>'Europe',
		   'HN'=>'North America',
		   'HK'=>'Asia',
		   'HU'=>'Europe',
		   'IS'=>'Europe',
		   'IN'=>'Asia',
		   'ID'=>'Asia',
		   'IR'=>'Asia',
		   'IQ'=>'Asia',
		   'IE'=>'Europe',
		   'IM'=>'Europe',
		   'IL'=>'Asia',
		   'IT'=>'Europe',
		   'JM'=>'North America',
		   'JP'=>'Asia',
		   'JE'=>'Europe',
		   'JO'=>'Asia',
		   'KZ'=>'Asia',
		   'KE'=>'Africa',
		   'KI'=>'Oceania',
		   'KP'=>'Asia',
		   'KR'=>'Asia',
		   'KW'=>'Asia',
		   'KG'=>'Asia',
		   'LA'=>'Asia',
		   'LV'=>'Europe',
		   'LB'=>'Asia',
		   'LS'=>'Africa',
		   'LR'=>'Africa',
		   'LY'=>'Africa',
		   'LI'=>'Europe',
		   'LT'=>'Europe',
		   'LU'=>'Europe',
		   'MO'=>'Asia',
		   'MK'=>'Europe',
		   'MG'=>'Africa',
		   'MW'=>'Africa',
		   'MY'=>'Asia',
		   'MV'=>'Asia',
		   'ML'=>'Africa',
		   'MT'=>'Europe',
		   'MH'=>'Oceania',
		   'MQ'=>'North America',
		   'MR'=>'Africa',
		   'MU'=>'Africa',
		   'YT'=>'Africa',
		   'MX'=>'North America',
		   'FM'=>'Oceania',
		   'MD'=>'Europe',
		   'MC'=>'Europe',
		   'MN'=>'Asia',
		   'ME'=>'Europe',
		   'MS'=>'North America',
		   'MA'=>'Africa',
		   'MZ'=>'Africa',
		   'MM'=>'Asia',
		   'NA'=>'Africa',
		   'NR'=>'Oceania',
		   'NP'=>'Asia',
		   'AN'=>'North America',
		   'NL'=>'Europe',
		   'NC'=>'Oceania',
		   'NZ'=>'Oceania',
		   'NI'=>'North America',
		   'NE'=>'Africa',
		   'NG'=>'Africa',
		   'NU'=>'Oceania',
		   'NF'=>'Oceania',
		   'MP'=>'Oceania',
		   'NO'=>'Europe',
		   'OM'=>'Asia',
		   'PK'=>'Asia',
		   'PW'=>'Oceania',
		   'PA'=>'North America',
		   'PG'=>'Oceania',
		   'PS'=>'Asia',
		   'PY'=>'South America',
		   'PE'=>'South America',
		   'PH'=>'Asia',
		   'PN'=>'Oceania',
		   'PL'=>'Europe',
		   'PT'=>'Europe',
		   'PR'=>'North America',
		   'QA'=>'Asia',
		   'RE'=>'Africa',
		   'RO'=>'Europe',
		   'RU'=>'Europe',
		   'RW'=>'Africa',
		   'BL'=>'North America',
		   'SH'=>'Africa',
		   'KN'=>'North America',
		   'LC'=>'North America',
		   'MF'=>'North America',
		   'PM'=>'North America',
		   'VC'=>'North America',
		   'WS'=>'Oceania',
		   'SM'=>'Europe',
		   'ST'=>'Africa',
		   'SA'=>'Asia',
		   'SN'=>'Africa',
		   'RS'=>'Europe',
		   'SC'=>'Africa',
		   'SL'=>'Africa',
		   'SG'=>'Asia',
		   'SK'=>'Europe',
		   'SI'=>'Europe',
		   'SB'=>'Oceania',
		   'SO'=>'Africa',
		   'ZA'=>'Africa',
		   'GS'=>'Antarctica',
		   'ES'=>'Europe',
		   'LK'=>'Asia',
		   'SD'=>'Africa',
		   'SR'=>'South America',
		   'SJ'=>'Europe',
		   'SZ'=>'Africa',
		   'SE'=>'Europe',
		   'CH'=>'Europe',
		   'SY'=>'Asia',
		   'TW'=>'Asia',
		   'TJ'=>'Asia',
		   'TZ'=>'Africa',
		   'TH'=>'Asia',
		   'TL'=>'Asia',
		   'TG'=>'Africa',
		   'TK'=>'Oceania',
		   'TO'=>'Oceania',
		   'TT'=>'North America',
		   'TN'=>'Africa',
		   'TR'=>'Asia',
		   'TM'=>'Asia',
		   'TC'=>'North America',
		   'TV'=>'Oceania',
		   'UG'=>'Africa',
		   'UA'=>'Europe',
		   'AE'=>'Asia',
		   'GB'=>'Europe',
		   'US'=>'North America',
		   'UM'=>'Oceania',
		   'VI'=>'North America',
		   'UY'=>'South America',
		   'UZ'=>'Asia',
		   'VU'=>'Oceania',
		   'VE'=>'South America',
		   'VN'=>'Asia',
		   'WF'=>'Oceania',
		   'EH'=>'Africa',
		   'YE'=>'Asia',
		   'ZM'=>'Africa',
		   'ZW'=>'Africa'
	   	);
	   	
	   	$update=array();
	   	foreach ($codeToCountry as $key=>$value){
	   		$where=$this->db->quoteInto("country=?", $value);
	   		$update['country_code']=$key;
	   		$update['continent']=$codeToContinent[$key];
	   		$update['update_time']=date('Y/m/d H:i:s');
	   		$this->db->update($table,$update,$where);
	   		echo 'updated continent '.$update['continent'].' and country code '.$update['country_code'].' in country='.$value.' <br />';
	   	}
		
		// change sar region to their region code instead of the country code
		$select=$this->db->select()
			->from($table,'district')
			->where('is_sar=1');
		$result=$this->db->fetchCol($select);
		$update=array();
		foreach($result as $value){
	   		$where=$this->db->quoteInto("district=?", $value);
			$key=array_keys($codeToCountry,$value);
	   		$update['country_code']=$key[0];
	   		$update['update_time']=date('Y/m/d H:i:s');
	   		$this->db->update($table,$update,$where);
	   		echo 'updated country code '.$update['country_code'].' in district='.$value.' <br />';
		}
		
	   	//add city based on php_timezone
		$select=$this->db->select()
			->from($table,'id');
		$result=$this->db->fetchCol($select);
		
		$pattern = "/\w+$/";
		$update=array();
		foreach($result as $value){
			preg_match($pattern,$value,$match);
			$update['city']=ucwords(str_replace('_',' ',$match[0]));
	   		$update['update_time']=date('Y/m/d H:i:s');
			$where=$this->db->quoteInto("id=?", $value);
	   		$this->db->update($table,$update,$where);
	   		echo 'updated city name '.$update['city'].' in id='.$value.' <br />';
		}
		
		//to-do: finetune some records, e.g. SAR to be same in country and city
	}
	
	//at least php 5.2.2 is required fro using DateTime function
	public function addTimezonePeriodDump(){
		set_time_limit(0);
		$select=$this->db->select()
			->from('timezone','id');
		$result=$this->db->fetchCol($select);
		$update=array();
		$now = new DateTime('now');
		
		$this->db->query("TRUNCATE TABLE timezone_period");
		foreach($result as $value){
			$timezone = new DateTimeZone($value);
			$transitions = array_reverse($timezone->getTransitions());
			$update=array();
			$current_time=1;
			$now=new DateTime('now');
			foreach($transitions as $transition){
				$transit_time=new DateTime($transition['time']);
				if ($transit_time>=$now){
					$insert['timezone_id']=$value;
					$insert['change_utc_time']=$transit_time->format('Y-m-d H:i:s');
					$insert['offset']=$transition['offset']/3600;
					$insert['is_dst']= $transition['isdst'] =='' ? null : $transition['isdst'];
					$insert['timezone_abbr']=$transition['abbr'];
					$insert['create_time']=date('Y-m-d H:i:s');
					$result=$this->db->insert('timezone_period',$insert);
					//echo $result.' timezone period information for timezone '.$value.'. <br />';
				}else{
					if ($current_time){
						$insert['timezone_id']=$value;
						$insert['change_utc_time']=$transit_time->format('Y-m-d H:i:s');
						$insert['offset']=$transition['offset']/3600;
						$insert['is_dst']= $transition['isdst'] =='' ? null : $transition['isdst'];
						$insert['timezone_abbr']=$transition['abbr'];
						$insert['create_time']=date('Y-m-d H:i:s');
						$this->db->insert('timezone_period',$insert);
						$current_time=0;
						//echo '(current)inserted information for timezone '.$value.'. <br />';
					}else{
						break;
					}
				}
			}
		}
	}
}