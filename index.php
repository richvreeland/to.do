<?php require_once 'globals.php'; ?>
<html>
    <title>💧️💧️</title>
    <link rel="shortcut icon" type="image/png" href="favicon.png"/>
    <link rel="stylesheet" type="text/css" href="todo.css?v2.2">
    <form action="todo.php" method="post">

    <div id="container">
    <?php

    $list = fread(fopen('list.txt', 'r'), 1024*1024);

    class Task {

        function __construct($desc, $timestamp, $category, $period, $done)
        {
            $this->desc = $desc;
            $this->timestamp = $timestamp;

            $this->category = $category;
            $this->period = $period;

            $this->done = $done;
        }
    }

    if ($list) {

        $entries = explode("\n\n", $list); //sort($entries); sorting causes some issues with keeping 1:1 b/w files, need to fix
        $task_tree = array();
        $tasks = array();

        $i = 0;

        foreach ($entries as &$e) {

            if (substr_count($e, "\n") < 3)
                continue;

            list($cat, $desc, $timestamp, $done) = array_pad(explode("\n", $e), 4, null);

                $period = 6;
            if ($timestamp < NEXT_WEEK)
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

    $params0 = "\t\t".'<input type="text" ';
    $params1 = 'placeholder=">to.do" title="Shortcut: Press \'/\'"';
    $params2 = ' name="desc" size="35">'."\n\t\t".'<select name="cat">'."\n\t\t\t".'<option disabled';
    $params3 = ' selected';
    $params4 = '>Category</option>'."\n";
    $params5 = '';

    foreach($CATEGORIES as $k => $v)
        $params5 .= "\t\t\t".'<option value="'.$k.'">' . $v->emoji . ' ' . $k . '</option>' . "\n";

    $params6 = "\t\t".'</select>'."\n\t\t".'<select name="period">'."\n\t\t\t".'<option disabled selected>Period</option>'."\n";
    
    $params7 = '';

    for ($i = 0; $i < sizeof(PERIODS); $i++)
        $params7 .= "\t\t\t".'<option value='.$i.'>' . PERIODS[$i] . '</option>' . "\n";

    $params8 = "\t\t".'</select>';
    $params9 =  '<input type="submit" name="submit" value="+">';
    $paramsA = '';
    $paramsB = "\n\t".'</form>'."\n\n";

    $editMode = isset($_GET['editRecord']);

    if($editMode) {

        $id = $_GET['editRecord'];
        $r = $tasks[$id];

        $params1 = 'value="'.$r->desc.'"';
        $params3 = '';
        $params5 = '';
        $params7 = '';

        // set up category pulldown
        foreach($CATEGORIES as $k => $v)
            $params5 .= "\t\t\t".'<option' . ($r->category->name == $k ? ' selected' : '') . ' value="'.$k.'">' . $v->emoji . ' ' . $k . '</option>' . "\n";
        // set up period pulldown
        for ($p = 0; $p < sizeof(PERIODS); $p++)
            $params7 .= "\t\t\t".'<option ' . ($r->period == $p ? 'selected ' : '') . 'value='.$p.'>' . PERIODS[$p] . '</option>' . "\n";
            
        $params9 = '<input type="hidden" name="id" value="'.$id.'"><input type="submit" name="edit" value="~">';
        $paramsA = '<input type="button" value="cancel" onclick="location.href=\'/\';">';
    }

    echo $params0.$params1.$params2.$params3.$params4.$params5.$params6.$params7.$params8.$params9.$paramsA.$paramsB;

        $command = escapeshellcmd('python cool-time.py');
        $output = shell_exec($command);
        $output = substr_replace($output, '<span id="blink">¯</span>', 8, 0);
        echo '<div id="time" title="'.date('l, F dS @ g:ia', TIME).'">'.$output.'</div>'."\n\n";

        // keep periods in the right order.
        ksort($task_tree);

        if ($list) {

            $thisWeekDone = false;

            if (!isset($task_tree[0])) {

                echo '<div class="period">'."\n".'<ul>'."\n\t".'<li class="pTitleDone done">'.PERIODS[0].'</li>'."\n\t";
                $thisWeekDone = true;

                echo '<li><a href="todo.php?toggleFuturePeriods=1" title="Shortcut: Press \'f\'" id="toggle">'. (SHOW_FUTURE ? 'Hide' : 'Show') . ' Future Events</a></li>'."\n\t".'</ul>'."\n".'</div>';
            }

            function cSort($a, $b) {

                global $CATEGORIES;

                if ($CATEGORIES[$a]->sortPriority == $CATEGORIES[$b]->sortPriority )
                    return 0;
                return ($CATEGORIES[$a]->sortPriority < $CATEGORIES[$b]->sortPriority ? 1 : -1);
            }

            foreach ($task_tree as $pkey => &$p) {

                reset($CATEGORIES);

                uksort($p, "cSort");

                echo '<div class="future period" ' . (SHOW_FUTURE ? '' : 'style="display: none').'"><ul>'."\n\t".'<li class="pTitle">'.PERIODS[$pkey].'</li>'."\n\t";

                foreach($p as $ckey => &$c) {

                    if (!isset($c))
                        continue;

                    echo '<ul class="category" style="background-image: linear-gradient('.$CATEGORIES[$ckey]->color.', #00000000);">'."\n\t".'<li class="cTitle">'.$CATEGORIES[$ckey]->emoji.' '.$ckey.'</li>'."\n\t".'<ul>';

                    foreach($c as $tkey => $task) {

                        $doneMark = '<li class="task"><a class="checkbox" href="todo.php?markDone='.$tkey.'">[ ]</a> ';
                        if ($task->done)
                            $doneMark = '<li class="task"><span class="done"><a class="checkbox" href="todo.php?markUndone='.$tkey.'">[X]</a> ';

                        echo $doneMark.$task->desc.'</span>'.'
                        <a class="edit" href="?editRecord='.$tkey.'">(~)</a><a class="delete" href="todo.php?deleteRecord='.$tkey.'">(x)</a></li>'."\n";
                    }

                    echo '</ul></ul>';
                }

                echo '</ul></div>';
            }

            // debug
            //echo '<pre>'.nl2br(print_r($task_tree, true)).'</pre>'."\n";
        }
        else
            echo 'list is empty.';

    ?>
    </div>
    <script type="text/javascript">
        document.addEventListener('keydown', function(event) {
            const key = event.key; // Or const {key} = event; in ES6+

            var inputInFocus = document.activeElement === document.getElementsByName("desc")[0];

            if (key === "Escape" && !inputInFocus)
                window.location = "http://to.do";
            else if (key === "f" && !inputInFocus)
                window.location = "http://to.do/todo.php?toggleFuturePeriods=1";
            else if (key === "/") {

                event.preventDefault();
                document.getElementsByName("desc")[0].focus();
            }
                
        });
    </script>
</html>