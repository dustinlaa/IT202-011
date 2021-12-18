<?php
require(__DIR__ . "/../../partials/nav.php");
?>


<div class="container-fluid">
    <h1>Home</h1>
    <form method="POST" action="home.php">
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="weekly()" value="Weekly" />
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="monthly()"value="Monthly" />
        <input type="submit" name="time" class="mt-3 btn btn-dark" onClick="lifetime()"value="Lifetime" />
    </form>
    <form method="POST" action="game.php">
        <input type="submit" name="game" class="mt-3 btn btn-success" value="Play Game" action = "game.php" />
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

if (is_logged_in()) {
    require(__DIR__ . "/../../partials/footer.php");
}
?>

<style>form{ display: inline-block; }</style>