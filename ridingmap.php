<?php
require("config.php");
require("functions.php");

// Retrieve webhook content
$data = file_get_contents('php://input');
$person_json = json_decode($data);
$person = $person_json->payload->person;
$fname = $person->first_name;
$lname = $person->last_name;

/*
// When in testing, only work on @leadnow.ca emails
if (strpos($person->email, "@leadnow.ca") === false) {
  die();
}
*/

// Find nb_id and postal code
$nb_id = $person->id;
$pcode = get_nationbuilder_pcode($person);

//$nb_id = 137125;      // francis
//$nb_id = 157551;      // francistest
//$pcode = 'm6p 4a6';

logger("request received for " . $nb_id . " - " . $pcode . " | " . $person->email . " | " . $person->phone);

if ($pcode) {
  // Look up postal code via Open North
  $riding = get_riding($pcode);

  if ($riding) {
    $riding_id = $riding['id'];
    $riding_name = $riding['name'];
    
    // Don't waste an API request if there's no change...    
    if ($person->precinct_name == $riding_name) {
      logger("already has precinct set; get outta here!");
      logger(" ");
      die();
    }

    
    // Update the NB record
    $params = set_person_params($nb_id, $riding_id, $riding_name);
    
//    $result = nationbuilder_put('https://votetogether.nationbuilder.com/api/v1/people/' . $nb_id, array('person' => $params));
    $result = nationbuilder_put('https://votetogether.nationbuilder.com/api/v1/people/push', array('person' => $params));

    if ($result['status'] == '200') {
      logger("success!");
      logger(" ");
    } else {
      logger("uh oh... " . $result['status'] . $result['body']);
      logger(" ");
    }

    sleep(1);

    
  }
}


?>
