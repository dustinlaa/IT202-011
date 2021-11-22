<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<h1>Home</h1>

<div class="container-fluid">
    <form method="POST" action="home.php">
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="weekly()" value="week" />
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="monthly()"value="month" />
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="lifetime()"value="lifetime" />
    </form>
</div>

<?php

    /*
    if (is_logged_in(true)) {
        echo "Welcome home, " . get_username();
        //comment this out if you don't want to see the session variables
        //echo "<pre>" . var_export($_SESSION, true) . "</pre>";
    }
    */
    if (isset($_POST["time"])) {
        $duration = $_POST["time"];
    } else {
        $duration = "week";
    }

?>
    <?php require(__DIR__ . "/../../partials/score_table.php"); ?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>


