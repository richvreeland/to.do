<?php require_once 'globals.php'; 

// to do
// recover feature for bringing cache posts back from the grave ?
// add an optional global deadline field for all categories
// flag a default category
// flag a default period
// UI for adding/deleting categories ? orphaned tasks should get re-assigned to the default category.
// move to AJAX, so we can do PHP stuff async and not have to reload page ?
// click on a period to focus on it (hide other periods)
// should probably add a regular search method (ie. in addition to acronym based)
// add better shorthand for periods ('tw, nw, 2w, nm, nq, nh, ny')
// option for inputting specific dates

?>
<html>
    <title>two.dew</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="favicon.png"/>
    <link rel="stylesheet" type="text/css" href="todo.css?v2.32">
    <div id="container">
        
    <?php

        $list = fread(fopen('list.txt', 'r'), 1024*1024);

        class Task {

            function __construct($desc, $timestamp, $category, $period, $done) {
                $this->desc = $desc;
                $this->timestamp = $timestamp;
                $this->category = $category;
                $this->period = $period;
                $this->done = $done;
            }
        }

        if ($list) {

            $entries = explode("\n\n", $list);
            $task_tree = array();
            $tasks = array();

            $i = 0;

            foreach ($entries as &$e) {

                if (substr_count($e, "\n") < 3)
                    continue;

                list($cat, $desc, $timestamp, $done) = array_pad(explode("\n", $e), 4, null);

                    $period = 6;
                if ($timestamp < THIS_WEEK && $done == 'closed' && $i++)
                    continue; // don't show tasks that are done, with past timestamps.
                else if ($timestamp < NEXT_WEEK)
                    $period = 0;
                else if ($timestamp < TWO_WEEKS)
                    $period = 1;
                else if ($timestamp < NEXT_MONTH)
                    $period = 2;
                else if ($timestamp < NEXT_QUARTER)
                    $period = 3;
                else if ($timestamp < NEXT_HALF)
                    $period = 4;
                else if ($timestamp < NEXT_YEAR)
                    $period = 5;

                if(!isset($task_tree[$period]))
                    $task_tree[$period] = array();
                if(!isset($task_tree[$period][$cat]))
                    $task_tree[$period][$cat] = array();
                    
                $tasks[$i++] = new Task($desc, $timestamp, $CATEGORIES[$cat], $period, $done == 'closed');
                $task_tree[$period][$cat][$i-1] = $tasks[$i-1];
            }
        }
    ?>
<!-- BUILD THE TASK FORM -->
        <form action="todo.php" method="post" id="taskForm">
            <input type="text" placeholder=">    💧️💧️" autocomplete="off" title="Shortcut: Press \'/\'" name="desc" size="35">
            <input type="hidden" name="cat">
            <input type="hidden" name="period">
            <input type="hidden" name="create">
        </form>
<!-- END TASK FORM -->
    <?php
        echo '<div id="taskHUD" style="left: -1000px;">👆enter: task \category \period</div>';

            // keep periods in the right order.
            ksort($task_tree);

            if ($list) {

                if (!isset($task_tree[0]))
                    echo '<div class="period">'."\n\t\t".'<ul>'."\n\t\t\t".'<li class="pTitleDone done">'.PERIODS[0].'</li>'."\n\t\t".'</ul>'."\n\t".'</div>'."\n\t";

                $onlyThisWeek = sizeof($task_tree) == 1 && isset($task_tree[0]);
                
                if (!$onlyThisWeek)
                    echo '<a href="todo.php?toggleFuturePeriods=0" title="Shortcut: Press \'f\'" id="toggle">'. (SHOW_FUTURE ? 'Hide' : 'Show') . ' Future Events</a>'."\n\t";

                // sort function to keep categories in priority order.
                function cSort($a, $b) {
                    global $CATEGORIES;
                    if ($CATEGORIES[$a]->sortPriority == $CATEGORIES[$b]->sortPriority )
                        return 0;
                    return ($CATEGORIES[$a]->sortPriority < $CATEGORIES[$b]->sortPriority ? 1 : -1);
                }

                $i = 0;

                foreach ($task_tree as $pkey => &$p) {

                    reset($CATEGORIES);
                    uksort($p, "cSort");
                    $thisWeek = $pkey == 'This Week';
                    echo '<div class="'.(!$thisWeek ? 'future ' : '').'period"' . (SHOW_FUTURE || $thisWeek ? '' : ' style="display: none"').'>'."\n\t\t".'<ul>'."\n\t\t\t".'<li class="pTitle">'.PERIODS[$pkey].'</li>'."\n\t";

                    foreach($p as $ckey => &$c) {

                        if (!isset($c))
                            continue;

                        echo '<ul class="category" style="background-image: linear-gradient('.$CATEGORIES[$ckey]->color.', #00000000);">'."\n\t".'<table><tr class="cTitle"><td>'.$CATEGORIES[$ckey]->emoji.'</td><td>'.$ckey.'</td></tr>'."\n\t";

                        foreach($c as $tkey => $task) {

                            $p1 = '<tr class="task"><td class="checkbox"><a title="mark ';

                            $doneMark = 'done." href="todo.php?markDone='.$tkey.'">[ ]</a></td>';
                            if ($task->done)
                                $doneMark = 'undone." href="todo.php?markUndone='.$tkey.'">[X]</a></td>';

                            echo $p1.$doneMark.'<td class="taskbody">'.'<span class="desc'.($task->done ? ' done' : '').'"><a href="#" onclick="showPrompt(\''.addslashes($task->desc),'\', '.$tkey.', \''.$ckey.'\', \''.$pkey.'\'); return false;">'.$task->desc.'</a></span>
                            </td></tr>'."\n";
                        }

                        echo '</table></ul>';
                    }

                    $i++;

                    echo '</ul></div>';

                    if(SHOW_FUTURE && $onlyThisWeek) {

                        echo '<div class="period">'."\n".'<ul>'."\n\t".'<li class="pTitleDone done">The Future</li>'."\n\t".'</ul>'."\n".'</div>';
                        break;
                    }
                }

                // debug
                //echo '<pre>'.nl2br(print_r($task_tree, true)).'</pre>'."\n";
            }
            else
                echo 'list is empty.';
    ?>
    </div>
    <div id="hiddenprompt" style="visibility: hidden;">
        <form action="todo.php" method="post" id="promptForm">
            <input type="text" id="promptDesc" name="desc"></input>
            <select name="cat" onchange="onPromptCatChange()" id="promptCat">
            <?php  
                foreach($CATEGORIES as $k => $v)
                    echo "\t\t\t".'<option value="'.$k.'">' . $v->emoji . ' ' . $k . '</option>' . "\n"; 
            ?>
            </select>
            <select name="period" id="promptPeriod">
            <?php
                for ($i = 0; $i < sizeof(PERIODS); $i++)
                    echo "\t\t\t".'<option value='.$i.'>' . PERIODS[$i] . '</option>' . "\n";
            ?>
            </select><br/>
            <p>
                <a name="editLink" href="#" onclick="submitPrompt();">Edit</a>
                <a name="deleteLink" href="">Delete</a>
                <a name="cancel" href="#" onclick="cancelDelete(); return false;">Cancel</a>
            </p>
            <input type="hidden" id="promptID" name="id"><input type="hidden" name="edit">
        </form>
    </div>
    <script type="text/javascript">

        var categories;
        var periods = ['This Week', 'Next Week', 'Two Weeks', 'Next Month', 'Next Quarter', 'Next Half', 'Next Year'];
        // secondary shorthand for better matching. (this week / two weeks conflict).
        var periodShorthand = ['0', '1', '2', 'm 3', 'q 4', 'h 5', 'y 6'];

        $taskOnly = '';
        $origHTML = document.getElementById('taskHUD').innerHTML;
        $category = '';

        var rawFile = new XMLHttpRequest();
        rawFile.open("GET", 'categories.txt', true);
        rawFile.onreadystatechange = function () {

            if(rawFile.readyState === 4)
                if(rawFile.status === 200 || rawFile.status == 0)
                    asyncParseCategories(rawFile.responseText);
        }
        rawFile.send(null);

        function asyncParseCategories(text) {

            // get each category as a separate string.
            categories = text.split(/\n/g);

            // get each cat property as a separate string.
            for (var i = 0; i < categories.length; i++)
                categories[i] = categories[i].split(', ');
        }

        function onInputFocus() {

            document.getElementById('taskHUD').style.left = document.activeElement === document.getElementsByName("desc")[0] ? '0' : '-1000'; 
        }

        document.addEventListener('click', function(event) { onInputFocus(); });

        document.addEventListener('keydown', function(event) {

            const key = event.key; // Or const {key} = event; in ES6+

            var taskBar   = document.getElementsByName('desc')[0];
            var promptBar = document.getElementById('promptDesc');

            var focusOnTaskInput   = document.activeElement === taskBar
            var focusOnPromptInput = document.activeElement === promptBar;

            var focusOnPrompt = document.activeElement == document.forms[1];

            if (focusOnPrompt)
                if (key === "Enter")
                    submitPrompt();

            if (!focusOnTaskInput && !focusOnPromptInput) {

                if (key === "Escape")
                    window.location = "./";
                else if (key === "f")
                    window.location = "todo.php?toggleFuturePeriods=0";
                else if (key === "/") {

                    event.preventDefault();
                    document.getElementsByName("desc")[0].focus();
                    onInputFocus();
                }
            }
            else if (key === "Enter" && focusOnTaskInput)
                document.getElementsByName('desc')[0].value = $taskOnly;
        });

        document.addEventListener('keyup', function(event) {

            if (document.activeElement === document.getElementsByName("desc")[0]) {

                $userInput = document.getElementsByName("desc")[0].value;

                $taskOnly = $userInput.replace(/\\.*/g, '').trim();

                // short-hand matches, ie. /hld or /tw
                $matches = $userInput.match(/\\.*?(?=\\|$)/g);

                //console.log($matches ? $matches.length + ' matches.' : 'no matches.');

                $catMatch = '';
                $periodMatch = 0;
                $mArray = new Array();

                if ($matches) {

                    for (var m = 0; m < $matches.length; m++) {

                        // remove leading slash, and trailing whitespace.
                        $matches[m] = $matches[m].substring(1).trim();
                        $boundaryRegex = '';

                        for (var i = 0; i < $matches[m].length; i++)
                            $boundaryRegex += '\\b[' + $matches[m].charAt(i) + '].*?';

                        $regex = new RegExp($boundaryRegex, 'i');

                        //console.log($regex);
                    
                        if ($matches.length == 1 || m == 0) {

                            // search custom shorthands first.
                            $catMatch = categories.find(x => x[4] ? x[4].includes($matches[m]) : null);
                            // if that turns up nil, use our acronym search method.
                            if (!$catMatch)
                                $catMatch = categories.find(x => $regex.test(x[3]));
                        }
                        if ($matches.length == 1 || m == 1)
                            $periodMatch = periodShorthand.findIndex(x => $regex.test(x));
                    }
                }

                //console.log($catMatch ? $catMatch[0] + ' ' + $catMatch[3] : 'nope');
                //console.log($periodMatch != null ? periods[$periodMatch] : 'nope');

                $category = $catMatch ? $catMatch[0] + ' ' + $catMatch[3] : '🦚️ Miscellaneous';
                $period = $periodMatch > -1 ? periods[$periodMatch] : 'This Week';

                document.getElementById('taskHUD').innerHTML = $userInput.length > 0 ? 'for ' + $category + ', ' + $period : $origHTML;
                document.getElementsByName('cat')[0].value = $catMatch ? $catMatch[3] : 'Miscellaneous';
                document.getElementsByName('period')[0].value = Math.max($periodMatch, 0);
            }
        });

        function onPromptCatChange() {

            var cat = document.getElementById('promptCat').value;
            document.getElementById('hiddenprompt').style.backgroundColor = categories.filter( (x) => x[3] == cat )[0][1];
        }

        function showPrompt(desc, id, cat, period) {

            document.getElementById('hiddenprompt').style.backgroundColor = categories.filter( (x) => x[3] == cat )[0][1];
            document.getElementById('promptID').value = id;
            document.getElementById('promptCat').value = cat;
            document.getElementById('promptPeriod').value = period;
            document.getElementById('hiddenprompt').style.visibility = 'visible';
            document.getElementsByName('deleteLink')[0].href = 'todo.php?deleteRecord='+id;

            var promptDesc = document.getElementById('promptDesc');            
            promptDesc.value = desc;
            promptDesc.focus();
            //promptDesc.select();
        }

        function submitPrompt() {

            document.getElementById('promptForm').submit();
        }

        function cancelDelete() {

            document.getElementById('hiddenprompt').style.visibility = 'hidden';
        }
    </script>
</html>