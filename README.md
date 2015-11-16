# NB-autoprecinct
precinct mapping script for Nationbuilder based on postal code

to get it running there are three things to do:
1) copy config.php.sample to config.php and enter your NationBuilder API key,
2) in functions.php, replace the hardcoded precinct IDs with the IDs for your nation (precincts with accents get duplicated - remember the whack-a-mole!)
3) set up the webhooks in NationBuilder (settings -> developer -> webhooks), I set up two hooks, one for person created and one for person changed (both pointing to the ridingmap.php script)

To batch-update past records use ridingmap_manual.php
Pull all contacts to update into a Nationbuilder list and note the ID of that list; then on your server, run
php ridingmap_manual.php <list id> true
