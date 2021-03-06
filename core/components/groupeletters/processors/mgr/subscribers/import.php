<?php
/**
 * Import the CSV:
 */

$newData = array();
$total = $imported = $exists = $invalid = 0;

// get possible group ids:
$my_groups = array();
foreach ( $scriptProperties as $property=>$value ) {
    if ( !is_string($property) ) {
        continue;
    }
    // $modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] Property: ('.$property .')');
    if ( strpos($property,'groups_') === 0 ) {
        // $modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] Add to Group: ('.$property .')');
        $my_groups[] = str_replace('groups_', '', $property);
    }
    
    
}

// $modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] Individual: start!');
$groups = $modx->getCollection('EletterGroups', array('id:IN'=>$my_groups));

if (!empty($_FILES['csv']['name']) && !empty($_FILES['csv']['tmp_name'])) {
    require_once MODX_CORE_PATH.'components/groupeletters/model/groupeletters/csv.class.php';
    $csv = new CSV();
    // returns array( name=>value)
    
    $csv->autoDetect(TRUE);// this just sets ini_set('auto_detect_line_endings', VALUE);
    
    $data = $csv->import($_FILES['csv']['tmp_name']);// return the data without the header column but in name(column)=>value pairs
    if ( $data === FALSE ) {
        return $modx->error->failure($modx->lexicon('groupeletters.subscribers.importcsv.err.cantopenfile') );
    } else {
        $total = count($data);
        foreach ( $data as $individual ) {
            // $modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] Individual: ('.print_r($individual,TRUE) .')');
            if (!isset($individual['email']) || empty($individual['email'])) {
                // if there is no email do not import
                $invalid++;
                continue;
            }
            $subscriber = $modx->getObject('EletterSubscribers', array('email'=>$individual['email']));
            if ( is_object($subscriber) ) {
                // they all ready exist!
                $exists++;
                continue;
            }
            
            $subscriber = $modx->newObject('EletterSubscribers');
            $subscriber->fromArray($individual);
            
            $subscriber->set('code', md5( time().$individual['email'] ));
            //$subscriber->set('active', 0);
            $subscriber->set('date_created', date('Y-m-d H:i:s'));
            $subscriber->set('active', $scriptProperties['active']);
            
            if ($subscriber->save()) {
                
                //add new groups
                foreach($groups as $group) {
                    $id = $group->get('id');
                    // $myGroup = $subscriber->getOne('Groups', array('group' => $id) );
                    $myGroup = $modx->getObject('EletterGroupSubscribers', array('group'=>$id,'subscriber'=>$subscriber->get('id')));
                            // add subsciber to group
                    // $modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] add Subscriber for group ('.$subscriber->get('id') .') to GroupID: '.$id);
                    $GroupSubscribers = $modx->newObject('EletterGroupSubscribers');
                    $GroupSubscribers->set('group', $id);
                    $GroupSubscribers->set('subscriber', $subscriber->get('id'));
                    $GroupSubscribers->set('date_created', date('Y-m-d h:i:s'));
                    $GroupSubscribers->save();
                    unset($myGroup);
                    // $this->modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters->signup()] GroupID: '.$groupID);
                }
                $imported++;
            } else {
                // there was an error!
            }
        }
        $status = 'success';
        $message = 'Data was imported';
    }
} else {
    $status = 'failed';
    $message = 'File did not upload';
    $newData[] = array();
    
    return $modx->error->failure($modx->lexicon('groupeletters.subscribers.importcsv.err.uploadfile') );
}
//$item = $item->toArray();
//$data[] = $item;
 
//return $modx->error->failure('');

// return $modx->error->failure($total.' imported, '.$invalid.' and '.$exists.' already existed');
/**
 * Total record on CSV
 * Total imported
 * Total existed
 * Total invalid
 */

$modx->log(modX::LOG_LEVEL_ERROR,'[Group ELetters/Process/Import()] Imported: '.$total.' imported, '.$invalid.' were invalid and '.$exists.' already existed');

//
$message = 'New imported records: '.$imported.
        '<br>Existing untouched records: '.$exists.
        '<br>Invalid CSV records:  '.$invalid.
        '<br>Total records in CSV file: '.$total;

        $message = $modx->lexicon('groupeletters.subscribers.importcsv.msg.complete', array('newCount' => $imported, 'existCount' => $exists, 'invalidCount' => $invalid, 'csvCount' => $total, ));
return $modx->error->success($message);// $total.' imported, '.$invalid.' were invalid and '.$exists.' already existed', $subscriber);

        
/**
 * groupeletters.subscribers.importcsv.err.uploadfile'] = 'Please, upload a file';
$_lang['groupeletters.subscribers.importcsv.err.cantopenfile'] = 'Can\'t open file';
$_lang['groupeletters.subscribers.importcsv.err.firstrow'] = 'First row must contain column names (first column must be email)';
$_lang['groupeletters.subscribers.importcsv.err.cantsaverow'] = 'Can\'t save row [[+rownum]]';
$_lang['groupeletters.subscribers.importcsv.err.skippedrow'] = 'Skipped row [[+rownum]]';
$_lang['groupeletters.subscribers.importcsv.msg.complete
 */





/**
 * 
$messages = array();
$importCount = 0;
$newCount = 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(is_uploaded_file($_FILES['csv']['tmp_name']) ) {
        if (($handle = fopen($_FILES['csv']['tmp_name'], "r")) !== FALSE) {
            $columns =  fgetcsv($handle, 0, ";");
            if( !is_array($columns) || $columns[0] != 'email' ) {
                $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.err.firstrow'); //first row must contain column names and start with email
            } else {
                $i = 2;
                while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                    if( count($data) == count($columns) ) {
                        $new = false;
                        $subscriber = $modx->getObject('EletterSubscribers', array('email' => $data[0]));
                        if(!is_object($subscriber))
                        {
                            $new = true;
                            $subscriber = $modx->newObject('EletterSubscribers');
                            $subscriber->set('code', md5($data[0].time())  );
                            $subscriber->set('active', 1 );
                        }

                        $num = count($columns);
                        for ($c=0; $c < $num; $c++) {
                            if($columns[$c] != 'groups')
                            {
                                $subscriber->set( $columns[$c], trim($data[$c]) );
                            }
                            else
                            {
                                $groups = $data[$c];
                            }
                        }
                        if ($subscriber->save() == false) {
                            $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.err.cantsaverow', array('rownum' => $i)); //can't save row
                            //$messages[] =  implode(',', $subscriber->get( array('firstname', 'lastname', 'code', 'email', 'active') ) );
                        } else {
                            //remove current groups and add new groups
                            $modx->removeCollection('EletterGroupSubscribers', array('subscriber' => $subscriber->get('id')));
                            $groups = preg_split('/[\s,]+/', $groups);
                            foreach($groups as $group)
                            {
                                if($group) { //prevent 0
                                    $subgroup = $modx->newObject('EletterGroupSubscribers');
                                    $subgroup->set('subscriber', $subscriber->get('id'));
                                    $subgroup->set('group', (int)$group);
                                    $subgroup->save();
                                }
                            }
                            $importCount++;
                            if($new) {
                                $newCount++;
                            }
                        }
                    } else {
                        $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.err.skippedrow', array('rownum' => $i)); //slipped invalid row
                    }
                    $i++;
                }
                $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.msg.complete', array('importCount' => $importCount, 'newCount' => $newCount)); //import complete + totals
            }
        } else {
            $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.err.cantopenfile'); exit; //can't open file
        }
    } else {
        $messages[] = $modx->lexicon('groupeletters.subscribers.importcsv.err.uploadfile'); //please upload file
    }

    $msg = htmlentities( implode('<br>', $messages) );
    return $modx->error->success($msg);
}
*/
