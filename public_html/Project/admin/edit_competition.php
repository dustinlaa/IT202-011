<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "home.php"));
}
?>
<?php 
$db = getDB();
$compid = se($_GET, "id", -1, false);
$stmt = $db->prepare("SELECT Competitions.id, name, duration, expires, current_reward, starting_reward, join_fee, current_participants, min_participants, min_score, first_place_per, second_place_per, third_place_per, created
FROM Competitions WHERE Competitions.id = :cid");
$orig_row = [];
$orig_comp = "";
try {
    $stmt->execute([":cid" => $compid]);
    $r = $stmt->fetch();
    if ($r) {
        $orig_row = $r;
        $orig_comp = se($r, "name", "N/A", false);
        $orig_duration = (int)se($r, "duration", 3, false);
        $orig_current_reward = (int)se($r, "current_reward", 1, false);
        $orig_starting_reward = (int)se($r, "starting_reward", 1, false);
        $orig_join_fee = (int)se($r, "join_fee", 0, false);
        $orig_min_participants = (int)se($r, "min_participants", 3, false);
        $orig_min_score = (int)se($r, "min_score", 1, false);
        $orig_first_place_per = (int)se($r, "first_place_per", 100, false);
        $orig_second_place_per = (int)se($r, "second_place_per", 0, false);
        $orig_third_place_per = (int)se($r, "third_place_per", 0, false);
        $creation_date = se($r, "created", date("Y-m-d H:i:s"), false);
    }
} catch (PDOException $e) {
    flash("There was a problem fetching competitions, please try again later", "danger");
    error_log("List competitions error: " . var_export($e, true));
}
if (isset($_POST["name"])) {
    $id = se($_POST, "id", false, false);
    $name = se($_POST, "name", false, false);
    $orig_comp = $name;
    $duration = (int)se($_POST, "duration", 3, false);
    $orig_duration = $duration;
    $expires = date("Y-m-d H:i:s", strtotime($creation_date.'+ ' . $duration. ' days'));
    $starting_reward = (int)se($_POST, "starting_reward", 1, false);
    $orig_starting_reward = $starting_reward;
    $current_reward = $starting_reward;
    $join_fee = (int)se($_POST, "join_fee", 0, false);
    $orig_join_fee = $join_fee;
    $current_participants = (int)se($_POST, "current_participants", 0, false);
    $min_participants = (int)se($_POST, "min_participants", 3, false);
    $orig_min_participants = $min_participants;
    $paid_out = false;
    $min_score = (int)se($_POST, "min_score", 1, false);
    $orig_min_score = $min_score;
    $first_place_per = 100;  // by default, first place will get 100% of reward
    $second_place_per = 0;
    $third_place_per = 0;
    $payout_split = se($_POST, "payout", 1, false);
    $points = (int)se(get_account_points(), null, 0, false);
   
    if ($payout_split == 2){
        $first_place_per = 80;
        $second_place_per = 20;
        $third_place_per = 0;
    } else if ($payout_split == 3){
        $first_place_per = 70;
        $second_place_per = 20;
        $third_place_per = 10;
    } else if ($payout_split == 4){
        $first_place_per = 60;
        $second_place_per = 30;
        $third_place_per = 10;
    } 
    $orig_first_place_per = $first_place_per;
    $orig_second_place_per = $second_place_per;
    $orig_third_place_per = $third_place_per;
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
        $params = [
            ":n" => $name,
            ":d" => $duration,
            ":e" => $expires,
            ":cr" => $current_reward,
            ":sr" => $starting_reward,
            ":jf" => $join_fee,
            ":mp" => $min_participants,
            ":ms" => $min_score,
            ":fpp" => $first_place_per,
            ":spp" => $second_place_per,
            ":tpp" => $third_place_per,
            ":id" => $compid
        ];
        $db = getDB();
        $query = "UPDATE Competitions 
            set name = :n, 
            duration = :d,
            expires = :e,
            current_reward = :cr, 
            starting_reward = :sr, 
            join_fee = :jf, 
            min_participants = :mp, 
            min_score = :ms, 
            first_place_per = :fpp, 
            second_place_per = :spp, 
            third_place_per = :tpp where id = :id";
    
        $stmt = $db->prepare($query);
        try {
            $stmt->execute($params);
            if ($compid > 0) {
                flash("Successfully Edited Competition $name", "success");
            }
        } catch (PDOException $e) {
            error_log("Error creating competition: " . var_export($e->errorInfo, true));
            flash("There was an error creating the competition: " . var_export($e->errorInfo[2]), "danger");
        }
    }
}
?>
<div class="container-fluid">
    <h1> Edit Competition: <?php se($orig_comp); ?> </h1>
    <form method="POST" autocomplete="off">
        <div>
            <label class="form-label" for="name">Name/Title</label>
            <input class="form-control" type="text" name="name" id="name" value = <?php se($orig_comp); ?> required />
        </div>
        <div>
            <label class="form-label" for="sr">Starting Reward</label>
            <input class="form-control" type="number" name="starting_reward" id="sr" min="1" value=<?php se($orig_starting_reward); ?> required />
        </div>
        <div>
            <label class="form-label" for="ef">Entry Fee</label>
            <input class="form-control" type="number" name="join_fee" id="ef" min="0" value=<?php se($orig_join_fee); ?> required />
        </div>
        <div>
            <label class="form-label" for="rp">Min. Required Participants</label>
            <input class="form-control" type="number" name="min_participants" id="rp" min="3" value=<?php se($orig_min_participants); ?> required />
        </div>
        <div>
            <label class="form-label" for="d">Duration in Days</label>
            <input class="form-control" type="number" name="duration" id="d" min="3" value=<?php se($orig_duration); ?> required />
        </div>
        <div class="mb-3">
            <label class="form-label" for = "ms">Minimum Score</label>
            <input class = "form-control" type="number" name = "min_score" id = :ms min = "0" value=<?php se($orig_min_score); ?> required />
        </div>
        <div> 
            <label class="form-label" for="payout">Payout Split</label>
            <div>Current Payout Split: <span id="payout"> First: <?php se($orig_first_place_per); ?>% Second: <?php se($orig_second_place_per); ?>% Third: <?php se($orig_third_place_per); ?>% </span></div>
            <select class="form-control" name="payout" required>
                <option value="1">100% to First</option>
                <option value="2">80% to First, 20% to Second</option>
                <option value="3">70% to First, 20% to Second, 10% to Third</option>
                <option value="4">60% to First, 30% to Second, 10% to Third</option>
            </select>
        </div>
        <br></br>
        <input class="btn btn-dark" type="submit" value="Update" />
    </form>
</div>
<style>
    br {
    line-height: 10px;
 }
 </style>
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
require(__DIR__ . "/../../../partials/footer.php");
?>