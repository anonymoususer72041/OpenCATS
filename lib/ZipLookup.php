<?php
/**
* OpenStreetMap Nominatim Zip Code Lookup library
*/
class ZipLookup
{

     public static function makeSearchableUSZip($zipString)
     {

	return str_replace(' ', '', $zipString);
     }

    public function getCityStateByZip($zip)
    {


	$aAddress[0] = 0;
	$aAddress[1] = '';
	$aAddress[2] = '';
	$aAddress[3] = '';

	$loc_level_1 = '';
	$loc_level_2 = '';
	$loc_level_3 = '';
	$loc_level_4 = '';

	/* Switched from legacy Google XML to Nominatim JSON; User-Agent is required. */
	$sUrl = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=1&postalcode=';

	if ($zip != '') {
		$response = $this->_fetchZipLookupResponse($sUrl . urlencode($zip));
		if ($response === false) {
			$aAddress[0] = 1;
		} else {
			$data = json_decode($response, true);
			if (is_array($data) && !empty($data) && isset($data[0]['address']) && is_array($data[0]['address'])) {
				$address = $data[0]['address'];
				if (isset($address['road'])) {
					$aAddress[1] = $address['road'];
				}
				if (isset($address['city'])) {
					$loc_level_1 = $address['city'];
				} else if (isset($address['town'])) {
					$loc_level_1 = $address['town'];
				} else if (isset($address['village'])) {
					$loc_level_1 = $address['village'];
				} else if (isset($address['municipality'])) {
					$loc_level_1 = $address['municipality'];
				} else if (isset($address['hamlet'])) {
					$loc_level_1 = $address['hamlet'];
				} else if (isset($address['county'])) {
					$loc_level_1 = $address['county'];
				}

				if (isset($address['state'])) {
					$loc_level_2 = $address['state'];
				} else if (isset($address['region'])) {
					$loc_level_2 = $address['region'];
				} else if (isset($address['state_district'])) {
					$loc_level_2 = $address['state_district'];
				} else if (isset($address['county'])) {
					$loc_level_2 = $address['county'];
				}

				$loc_level_3 = $loc_level_2;

				if (isset($address['country'])) {
					$loc_level_4 = $address['country'];
				}
			}
		}
	} else {
		$aAddress[0] = 2;
	}

	// Set the state based on US or non-US location
	$aAddress[2] = $loc_level_1;
	if ($loc_level_4 == 'United States') {
		$aAddress[3] = $loc_level_3;
	} else {
		$aAddress[3] = $loc_level_2;
	}
	    
	return $aAddress;

    }

    private function _fetchZipLookupResponse($url)
    {
	/* Use a Nominatim User-Agent to comply with their usage policy. */
	$userAgent = 'OpenCATS ZipLookup (https://github.com/opencats/OpenCATS)';

	if (function_exists('curl_init')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_TIMEOUT, 6);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $response;
	}

	$context = stream_context_create(
		array(
			'http' => array(
				'timeout' => 6,
				'header' => "User-Agent: " . $userAgent . "\r\n" .
					"Accept: application/json\r\n"
			)
		)
	);

	$response = @file_get_contents($url, false, $context);
	if ($response === false) {
		return false;
	}

	return $response;
    }
    
    /**
     * Returns an array of SQL clauses that returns the distance from a zipcode for each record.
     *
     * @param integer United States Zip code (55303)
     * @param string record Zip Code Column (candidate.zip)
     * @return string SQL select clause
     */
    public function getDistanceFromPointQuery($zipcode, $zipcodeColumn)
    {
        //based on kilometers = (3958*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
        
        $select = "(3958*3.1415926*sqrt((zipcode_searching.lat-zipcode_record.lat)*(zipcode_searching.lat-zipcode_record.lat) + cos(zipcode_searching.lat/57.29578)*cos(zipcode_record.lat/57.29578)*(zipcode_searching.lng-zipcode_record.lng)*(zipcode_searching.lng-zipcode_record.lng))/180) as distance_km";
        $join = "LEFT JOIN zipcodes as zipcode_searching ON zipcode_searching.zipcode = ".$zipcode." LEFT JOIN zipcodes as zipcode_record ON zipcode_record.zipcode = ".$zipcodeColumn;
        return array("select" => $select, "join" => $join);
    }
}
?>
