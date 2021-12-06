<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));

}

?>
<?php if (isset($_POST["name"])) {
    $id = se($_POST, "id", false, false);
    $name = se($_POST, "name", false, false);
    $duration = (int)se($_POST, "duration", 3, false);
    $expires = se($_POST, "expires", 1, false);
    $current_reward = (int)se($_POST, "current_reward", 1, false);
    $starting_reward = (int)se($_POST, "starting_reward", 1, false);
    $join_fee = (int)se($_POST, "join_fee", 0, false);
    $current_participants = (int)se($_POST, "current_participants", 0, false);
    $min_participants = (int)se($_POST, "min_participants", 3, false);
    $paid_out = false;
    $min_score = (int)se($_POST, "min_score", 1, false);
    $first_place_per = "1";  // by default, first place will get 100% of reward
    $second_place_per = "0";
    $third_place_per = "0";
    $payout_split = se($_POST, "payout", 1, false);
    $cost_to_create = $starting_reward + 1;
    $points = (int)se(get_account_points(), null, 0, false);
   
    if ($payout_split == 2){
        $first_place_per = "0.8";
        $second_place_per = "0.2";
        $third_place_per = "0";
    } else if ($payout_split == 3){
        $first_place_per = "0.7";
        $second_place_per = "0.2";
        $third_place_per = "0.1";
    } else if ($payout_split == 4){
        $first_place_per = "0.6";
        $second_place_per = "0.3";
        $third_place_per = "0.1";
    } 


    $isValid = true;
    //validate
    if (!!$name === false) {
        flash("Name must be set", "warning");
        $isValid = false;
    }
    if ($starting_reward < 0) {
        flash("Invalid Starting Reward", "warning");
        $isValid = false;
    }
    if ($cost_to_create < 1) {
        flash("Invalid Cost", "danger");
        $isValid = false;
    }
    if ($cost_to_create > $points) {
        flash("You can't afford this, it requires $cost_to_create points", "warning");
        $isValid = false;
    }
    if ($min_participants < 3) {
        flash("All competitions require at least 3 participants to payout", "warning");
    }
    
    if ($join_fee < 0) {
        flash("Entry fee must be free (0) or greater", "warning");
        $isValid = false;
    }

    if ($duration < 3 || is_nan($duration)) {
        flash("Competitions must be 3 or greater days", "warning");
        $isValid = false;
    }  

    if ($isValid) {
        //create competition and deduct cost
        $db = getDB();
        //setting 1 for participants since we'll be adding creator to the comp, this saves an update query
        //using sql to calculate the expires date by passing in a sanitized/validated $duration
        $query = "INSERT INTO Competitions (name, duration, expires, current_reward, starting_reward, join_fee, current_participants, min_participants, min_score, first_place_per, second_place_per, third_place_per, cost_to_create)
            values (:n, :d, DATE_ADD(NOW(), INTERVAL $duration day), :sr, :sr, :jf, 1, :mp, :ms, :fpp, :spp, :tpp, :ctc)";
        
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([
                ":n" => $name,
                ":d" => $duration,
                ":sr" => $starting_reward,
                ":jf" => $join_fee,
                ":mp" => $min_participants,
                ":ms" => $min_score,
                ":fpp" => $first_place_per,
                ":spp" => $second_place_per,
                ":tpp" => $third_place_per,
                ":ctc" => $cost_to_create,
            ]);
            $id = (int)$db->lastInsertId();
            if ($id > 0) {
                change_points(-$cost_to_create, "Created Competition #$id", $forceAllowZero = true);
                error_log("Attempt to join created competition: " . join_competition($id, true));
                flash("Successfully created Competition $name", "success");
            }
        } catch (PDOException $e) {
            error_log("Error creating competition: " . var_export($e->errorInfo, true));
            flash("There was an error creating the competition: " . var_export($e->errorInfo[2]), "danger");
        }
    }
}
?>
<h1> Create Competition </h1>
<div class="container-fluid">
    <form method="POST" autocomplete="off">
        <div>
            <label class="form-label" for="name">Name/Title</label>
            <input class="form-control" type="text" name="name" id="name" required />
        </div>
        <div>
            <label class="form-label" for="sr">Starting Reward</label>
            <input class="form-control" type="number" name="starting_reward" id="sr" min="1" value="1" oninput="document.getElementById('cost').innerText = 1 + (value*1)" required />
        </div>
        <div>
            <label class="form-label" for="ef">Join Fee</label>
            <input class="form-control" type="number" name="join_fee" id="ef" min="0" value="0" required />
        </div>
        <div>
            <label class="form-label" for="rp">Min. Required Participants</label>
            <input class="form-control" type="number" name="min_participants" id="rp" min="3" value="3" required />
        </div>
        <div>
            <label class="form-label" for="d">Duration in Days</label>
            <input class="form-control" type="number" name="duration" id="d" min="3" value="3" required />
        </div>
        <div>
            <label class="form-label" for="payout">Payout Split</label>
            <select class="form-control" name="payout" required>
                <option value="1">100% to First</option>
                <option value="2">80% to First, 20% to Second</option>
                <option value="3">70% to First, 20% to Second, 10% to Third</option>
                <option value="4">60% to First, 30% to Second, 10% to Third</option>
            </select>
        </div>
        <div>Cost: <span id="cost">2</span></div>
        <input class="btn btn-dark" type="submit" value="Create" />
    </form>
</div>
<script>
    function validate(form) {
        //TODO add all validations (basically match what you define at the html level for consistency)

        //client side balance validation (just used to reduce server load as we don't trust the client)
        let balance = <?php se(get_account_points(), null, 0); ?> * 1; //convert to int
        let cost = 1 + (form.starting_reward.value * 1);
        if (cost < 1) {
            cost = 1;
        }
        let isValid = true;
        if (cost > balance) {
            flash("You can't afford to create this competition, you need " + cost + " points");
            isValid = false;
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>