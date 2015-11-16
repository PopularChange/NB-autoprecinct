<?php

// Helper for debug logging
$log = null;
function logger($msg) {
  global $log;

  if (DEBUG) {
    if (defined('LOG_ECHO') && LOG_ECHO) {
      echo $msg . "\n";
    
    } else {
      if (!$log)
        $log = fopen(LOGFILE, 'a');
        
      if ($log)
        fwrite($log, getmypid() . ': ' . strftime('%c') . ' - ' . $msg . "\n");
    }
  }
}

// Helper for setting NB person params
function set_person_params($nb_id, $riding_id, $riding_name) {
  $params = array();

  if ($riding_name == 'LaSalle—Émard—Verdun') {
    $params['precinct_id'] = 1638;
  } else if ($riding_name == 'Ville-Marie—Le Sud-Ouest—Île-des-Soeurs') {
    $params['precinct_id'] = 1938;
  } else if ($riding_name == "Mégantic—L'Érable") {
    $params['precinct_id'] = 1980;
  } else if ($riding_name == 'Laval—Les Îles') {
    $params['precinct_id'] = 1991;
  } else if ($riding_name == "La Pointe-de-l'Île") {
    $params['precinct_id'] = 1987;
  } else if ($riding_name == 'Rivière-des-Mille-Iles') {
    $params['precinct_id'] = 1897;
  } else if ($riding_name == 'Gaspsie—Les les-de-la-Madeleine') {
    $params['precinct_id'] = 1872;
  } else {
    $params['precinct_name'] = $riding_name;
    $params['precinct_code'] = null;
    $params['precinct_id'] = null;
  }
            
  $params['federal_riding_name_2015'] = $riding_name;
  $params['federal_riding_id_2015'] = $riding_id;
  $params['nbec_guid'] = $nb_id;
  $params['id'] = $nb_id;

  return $params;
}

// Helpers for parsing NationBuilder API returns
function get_nationbuilder_pcode($person) {
  $pcode = null;
  
  if (property_exists($person, 'primary_address') && $person->primary_address && $person->primary_address->zip != '') {
    $pcode = $person->primary_address->zip;
    
  } else if (property_exists($person, 'submitted_address') && $person->submitted_address && $person->submitted_address->zip != '') {
    $pcode = $person->submitted_address->zip;
    
  } else if (property_exists($person, 'user_submitted_address') && $person->user_submitted_address && $person->user_submitted_address->zip != '') {
    $pcode = $person->user_submitted_address->zip;
  }

  if ($pcode)
    $pcode = str_replace(' ', '', strtoupper($pcode));
  
  return $pcode;
}
  

// OpenNorth API call
// (returns an assoc array with keys id and name, or null)
function get_riding($pcode) {
  $curl = curl_init('https://represent.opennorth.ca/postcodes/' . urlencode($pcode) . '/?sets=federal-electoral-districts-next-election');
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($curl);

  $riding_id = null;
  $riding_name = null;
  if ($result) {
    $riding = json_decode($result);

    $curlinfo = curl_getinfo($curl);
    if ($curlinfo['http_code'] == '200') {
      if ($riding->boundaries_concordance) {
        $riding_id = $riding->boundaries_concordance[0]->external_id;
        $riding_name = $riding->boundaries_concordance[0]->name;
      } else if ($riding->boundaries_centroid) {
        $riding_id = $riding->boundaries_centroid[0]->external_id;
        $riding_name = $riding->boundaries_centroid[0]->name;
      }
    } else {
      logger("OpenNorth call failed: " . $curlinfo['http_code']);
    }
  }
  
  if ($riding_id && $riding_name) {
    logger("riding - " . $riding_id . ': ' . $riding_name);
    return array('id' => $riding_id, 'name' => $riding_name);
  } else {
    logger("riding lookup failed");
    return null;
  }
}


// NationBuilder "put" call
// returns an assoc array, with status => HTTP status code, body => response body
function nationbuilder_put($url, $params) {
  $curl = curl_init($url . '?access_token=' . NB_TOKEN . '&fire_webhooks=false');

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

  $result = curl_exec($curl);
  
  $curlinfo = curl_getinfo($curl);
  return array('status' => $curlinfo['http_code'], 'body' => $result);
}


// NationBuilder "get" call
// returns an assoc array, with status => HTTP status code, body => response body
function nationbuilder_get($url, $params) {
  if (!$params)
    $params = array();
  $params['access_token'] = NB_TOKEN;
  $params['fire_webhooks'] = false;
  $urlparams = http_build_query($params);

  if (strpos($url, '?') === false)
    $curl = curl_init($url . '?' . $urlparams);
  else
    $curl = curl_init($url . '&' . $urlparams);

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

  $result = curl_exec($curl);
  
  $curlinfo = curl_getinfo($curl);
  return array('status' => $curlinfo['http_code'], 'body' => $result);
}


?>
