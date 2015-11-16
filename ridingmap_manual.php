<?php
require("config.php");
require("functions.php");

define('LOG_ECHO', true);

// Does a NB riding-mapping for an arbitrary list.
// First arg on the command line should be the ID of the list
// Including a second arg (option) will force a remap, even if those records already appear mapped
//
// USAGE: php ridingmap_manual.php <id of list> <true if you want a force remap>


// Get list of people to update
//$list_id = 211;     // "needs_mapping"
//$list_id = 209;       // "testusers"

$list_id = null;
$force_remap = false;
if ($argv && sizeof($argv) >= 2)
  $list_id = $argv[1];
if ($argv && sizeof($argv) >= 3)
  $force_remap = $argv[2];
  
if (!$list_id) {
  echo "You didn't provide me a list ID!\n\n";
  die();
}

// Fetch list from NationBuilder and run through it (using their fancy pagination too)
$people = array();

$i = 1;
$url = 'https://votetogether.nationbuilder.com/api/v1/lists/' . $list_id . '/people';
while ($url != null) {
  $result = nationbuilder_get($url, array('limit' => '100'));
  
  if ($result['status'] != '200') {
    echo "Couldn't get NationBuilder list!  A bad ID, maybe?\n";
    echo $result['status'] . ": " . $result['body'] . "\n\n";
    die();
  }

  $lists = json_decode($result['body']);

  foreach ($lists->results as $person) {

    // Find nb_id and postal code
    $nb_id = $person->id;
    $pcode = get_nationbuilder_pcode($person);

    if ($person->federal_riding_name_2015 && !$force_remap) {
      logger("$i - skip");
      $i++;
      continue;
    }

    if ($pcode) {
      logger($i . ": " . $nb_id . " - " . $pcode . " | " . $person->email . " | " . $person->phone);


      // Look up postal code via Open North
      $riding = get_riding($pcode);

      if ($riding) {
        $riding_id = $riding['id'];
        $riding_name = $riding['name'];
        
        // Don't waste an API request if there's no change...    
        //if ($person->precinct_name == $riding_name) {
        if ($person->federal_riding_name_2015 == $riding_name && !$force_remap) {
          logger("already has precinct set; get outta here!");
          
        } else {
          // Update the NB record
          $params = set_person_params($nb_id, $riding_id, $riding_name);
          
//          $result = nationbuilder_put('https://votetogether.nationbuilder.com/api/v1/people/' . $nb_id, array('person' => $params));
          $result = nationbuilder_put('https://votetogether.nationbuilder.com/api/v1/people/push', array('person' => $params));
          
          if ($result['status'] == '200') {
            logger("success!");
          } else {
            logger("uh oh... " . $result['status'] . $result['body']);
          }

          sleep(1);
        }
        
      }
    }

    $i++;

  }

  $url = null;
  if ($lists->next && $lists->next != '') {
    $url = 'https://votetogether.nationbuilder.com' . $lists->next;
  }
}



?>
