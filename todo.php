<?php

require_once 'globals.php';

function getTimeStamp($period) {

    $time = THIS_WEEK;

    if (isset($period))
    switch($period) {

        case 1: $time = NEXT_WEEK;    break;
        case 2: $time = TWO_WEEKS;    break;
        case 3: $time = NEXT_MONTH;   break;
        case 4: $time = NEXT_QUARTER; break;
        case 5: $time = NEXT_HALF;    break;
        case 6: $time = NEXT_YEAR;    break;
    }

    return $time;
}

function postNewEntry($desc, $cat, $period, $done = false) {

    // default values (Miscellaneous, This Week)
    if(!isset($cat))
    $cat = 'Miscellaneous';
    $time = getTimeStamp($period);

    file_put_contents('list.txt',
        // category
        $cat . "\n" .
        // description
        $desc . "\n" .
        // timestamp
        $time . "\n" .
        // done
        ($done ? 'closed' : 'open') . "\n" .
        // separator
        "\n",

        FILE_APPEND
    );
}

function markRecord($done, $id) {

    $entries = getRecords();
    $entry = explode("\n", $entries[$id]);
    $entry[2] = time();
    $entry[3] = $done ? 'closed' : 'open';
    
    // cache deleted task.
    file_put_contents('edit-cache.txt', $entries[$id]);

    $entry = implode("\n", $entry);
    $entries[$id] = $entry;

    // update list.txt
    $output = implode("\n\n", $entries);
    file_put_contents('list.txt', $output);
}

function getRecords() {

    $list = fread(fopen('list.txt', 'r'), 1024*1024);
    $records = explode("\n\n", $list);
    //sort($records); sorting causes an issue with keeping records aligned properly. need to fix
    return $records;
}

function deleteRecord($id, $cachefile) {

    $entries = getRecords();

    // cache deleted task.
    file_put_contents($cachefile, $entries[$id] . "\n\n", FILE_APPEND);
    // remove task.
    array_splice($entries, $id, 1);
    // update list.txt
    $output = implode("\n\n", $entries);
    file_put_contents('list.txt', $output);
}

if (isset($_GET['toggleFuturePeriods'])) {

    $file = fread(fopen('settings.txt', 'r'), 1024*1024);
    $settings = explode("\n", $file);
    $setting = $settings[0];

    list($k, $v) = explode(': ', $setting);

    $v = $v == 1 ? 0 : 1;

    $settings[0] = $k . ': ' . $v;

    $output = implode("\n", $settings);
    file_put_contents('settings.txt', $output);
}
// find requested record and remove it from list.txt
else if(isset($_GET['deleteRecord'])) {

    deleteRecord($_GET['deleteRecord'], 'delete-cache.txt', FILE_APPEND);
}
else if(isset($_GET['markDone'])) {

    markRecord(true, $_GET['markDone']);
}
else if(isset($_GET['markUndone'])) {

    markRecord(false, $_GET['markUndone']);
}
// modify a pre-existing record.
else if(isset($_POST['edit']) && strlen($_POST['desc']) > 0) {

    $entries = getRecords();
    $id = $_POST['id'];
    $entry = explode("\n", $entries[$id]);

    // cache pre-edited task.
    file_put_contents('edit-cache.txt', $entries[$id] . "\n\n", FILE_APPEND);

    $entry[0] = isset($_POST['cat']) ? $_POST['cat'] : 'Miscellaneous';
    $entry[1] = $_POST['desc'];
    $entry[2] = getTimeStamp($_POST['period']);

    $entry = implode("\n", $entry);
    $entries[$id] = $entry;

    // cache post-edited task.
    file_put_contents('edit-cache.txt', $entries[$id] . "\n\n", FILE_APPEND);

    // update list.txt
    $output = implode("\n\n", $entries);
    file_put_contents('list.txt', $output);
}
// add a new record if we actually submitted something useful.
else if(isset($_POST['create']) && strlen($_POST['desc']) > 0)
    postNewEntry($_POST['desc'], $_POST['cat'], $_POST['period']);

header('Location: ./');

?>