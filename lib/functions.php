<?php
require_once(__DIR__ . "/db.php");
$BASE_PATH = '/Project/'; //This is going to be a helper for redirecting to our base project path since it's nested in another folder
function se($v, $k = null, $default = "", $isEcho = true)
{
    if (is_array($v) && isset($k) && isset($v[$k])) {
        $returnValue = $v[$k];
    } else if (is_object($v) && isset($k) && isset($v->$k)) {
        $returnValue = $v->$k;
    } else {
        $returnValue = $v;
        //added 07-05-2021 to fix case where $k of $v isn't set
        //this is to kep htmlspecialchars happy
        if (is_array($returnValue) || is_object($returnValue)) {
            $returnValue = $default;
        }
    }
    if (!isset($returnValue)) {
        $returnValue = $default;
    }
    if ($isEcho) {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        echo htmlspecialchars($returnValue, ENT_QUOTES);
    } else {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        return htmlspecialchars($returnValue, ENT_QUOTES);
    }
}
//TODO 2: filter helpers
function sanitize_email($email = "")
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}
function is_valid_email($email = "")
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
//TODO 3: User Helpers
function is_logged_in($redirect = false, $destination = "login.php")
{
    $isLoggedIn = isset($_SESSION["user"]);
    if ($redirect && !$isLoggedIn) {
        flash("You must be logged in to view this page", "warning");
        die(header("Location: $destination"));
    }
    return $isLoggedIn; //se($_SESSION, "user", false, false);
}
function has_role($role)
{
    if (is_logged_in() && isset($_SESSION["user"]["roles"])) {
        foreach ($_SESSION["user"]["roles"] as $r) {
            if ($r["name"] === $role) {
                return true;
            }
        }
    }
    return false;
}

function get_username()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "username", "", false);
    }
    return "";
}
function get_user_email()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "email", "", false);
    }
    return "";
}
function get_user_id()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "id", false, false);
    }
    return false;
}
//TODO 4: Flash Message Helpers
function flash($msg = "", $color = "info")
{
    $message = ["text" => $msg, "color" => $color];
    if (isset($_SESSION['flash'])) {
        array_push($_SESSION['flash'], $message);
    } else {
        $_SESSION['flash'] = array();
        array_push($_SESSION['flash'], $message);
    }
}

function getMessages()
{
    if (isset($_SESSION['flash'])) {
        $flashes = $_SESSION['flash'];
        $_SESSION['flash'] = array();
        return $flashes;
    }
    return array();
}
//TODO generic helpers
function reset_session()
{
    session_unset();
    session_destroy();
}
function users_check_duplicate($errorInfo)
{
    if ($errorInfo[1] === 1062) {
        //https://www.php.net/manual/en/function.preg-match.php
        preg_match("/Users.(\w+)/", $errorInfo[2], $matches);
        if (isset($matches[1])) {
            flash("The chosen " . $matches[1] . " is not available.", "warning");
        } else {
            //TODO come up with a nice error message
            flash("<pre>" . var_export($errorInfo, true) . "</pre>");
        }
    } else {
        //TODO come up with a nice error message
        flash("<pre>" . var_export($errorInfo, true) . "</pre>");
    }
}
function get_url($dest)
{
    global $BASE_PATH;
    if (str_starts_with($dest, "/")) {
        //handle absolute path
        return $dest;
    }
    //handle relative path
    return $BASE_PATH . $dest;
}

function save_score($score, $user_id, $showFlash = false)
{
    if ($user_id < 1) {
        flash("Error saving score, you may not be logged in", "warning");
        return;
    }
    if ($score <= 0) {
        flash("Scores of zero are not recorded", "warning");
        return;
    }
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Scores (score, user_id) VALUES (:score, :uid)");
    try {
        $stmt->execute([":score" => $score, ":uid" => $user_id]);
        
        if ($showFlash) {
            flash("Saved score of $score", "success");
        }
        
    } catch (PDOException $e) {
        flash("Error saving score: " . var_export($e->errorInfo, true), "danger");
    }
}

function get_latest_scores($user_id, $limit = 10)
{
    if ($limit < 1 || $limit > 50) {
        $limit = 10;
    }
    $query = "SELECT score, created from Scores where user_id = :id ORDER BY created desc LIMIT :limit";
    $db = getDB();
    //IMPORTANT: this is required for the execute to set the limit variables properly
    //otherwise it'll convert the values to a string and the query will fail since LIMIT expects only numerical values and doesn't cast
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    //END IMPORTANT

    $stmt = $db->prepare($query);
    try {
        $stmt->execute([":id" => $user_id, ":limit" => $limit]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            return $r;
        }
    } catch (PDOException $e) {
        error_log("Error getting latest $limit scores for user $user_id: " . var_export($e->errorInfo, true));
    }
    return [];
}

/** Gets the top 10 scores for valid durations (week, month, lifetime) */
function get_top_10($duration = "week")
{
    $d = "week";
    if (in_array($duration, ["week", "month", "lifetime"])) {
        //variable is safe
        $d = $duration;
    }
    $db = getDB();
    $query = "SELECT user_id,username, score, Scores.created from Scores join Users on Scores.user_id = Users.id";
    if ($d !== "lifetime") {
        //be very careful passing in a variable directly to SQL, I ensure it's a specific value from the in_array() above
        $query .= " WHERE Scores.created >= DATE_SUB(NOW(), INTERVAL 1 $d)";
    }
    //remember to prefix any ambiguous columns (Users and Scores both have created)
    $query .= " ORDER BY score Desc, Scores.created desc LIMIT 10"; //newest of the same score is ranked higher
    error_log($query);
    $stmt = $db->prepare($query);
    $results = [];
    try {
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching scores for $d: " . var_export($e->errorInfo, true));
    }
    return $results;
}


function get_account_points()
{
    if (is_logged_in() && isset($_SESSION["user"]["points"])) {
        return (int)se($_SESSION["user"]["points"], "points", 0, false);
    }
    return 0;
}


function points_update()
{
    if (is_logged_in()) {
        $query = "UPDATE Users SET points = (SELECT IFNULL(SUM(point_change), 0) from PointsHistory WHERE user_id = :uid) where id = :uid";
        $db = getDB();
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([":uid" => get_user_id()]);
        } catch (PDOException $e) {
            flash("Error refreshing account: " . var_export($e->errorInfo, true), "danger");
        }
    }
}

function get_user()
{
    if (is_logged_in()) {
        $user = ["id" => -1, "points" => 0];
        //this should always be 0 or 1, but being safe
        $query = "SELECT id, points from Users where id = :uid LIMIT 1";
        $db = getDB();
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([":uid" => get_user_id()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $result;
            $user["id"] = $result["id"]; //don't need to save id again
            $user["points"] = $result["points"];
            //$created = true;
        } catch (PDOException $e) {
            flash("Technical error: " . var_export($e->errorInfo, true), "danger");
        }
        $_SESSION["user"]["points"] = $user; //storing the user info as a key under the user session
    } else {
        flash("You're not logged in", "danger");
    }
}

function change_points($points, $reason, $forceAllowZero = false) {

    if ($points > 0 || $forceAllowZero) {
        $query = "INSERT INTO PointsHistory (user_id, point_change, reason) 
            VALUES (:uid, :pc, :r)";
        $params[":uid"] = get_user_id();
        $params[":pc"] = $points;
        $params[":r"] = $reason;
        $db = getDB();
        $stmt = $db->prepare($query);
        try {
            $stmt->execute($params);
            points_update();
            get_user();
            return true;
        } catch (PDOException $e) {
            flash("Transfer error occurred: " . var_export($e->errorInfo, true), "danger");
        }
    }
}

function join_competition($comp_id, $isCreator = false) {
    if ($comp_id <= 0) {
        flash("Invalid Competition", "warning");
        return;
    }
    $db = getDB();
    $query = "SELECT name, current_reward, join_fee, paid_out FROM Competitions where id = :id";
    $stmt = $db->prepare($query);
    $comp = [];
    try {
        $stmt->execute([":id" => $comp_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $comp = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching competition to join $comp_id: " . var_export($e->errorInfo, true));
        flash("Error looking up competition", "warning");
        return;
    }
    if ($comp && count($comp) > 0) {
        $paid_out = (int)se($comp, "paid_out", 0, false) > 0;
        if ($paid_out) {
            flash("You can't join a completed competition", "warning");
            return;
        }
        $points = (int)se(get_account_points(), null, 0, false);
        $join_fee = (int)se($comp, "join_fee", 0, false);
        $name = se($comp, "name", 0, false);
        if ($join_fee >= $points) {
            flash("You can't afford to join this competition", "danger");
            return;
        }
        $query = "INSERT INTO CompetitionParticipants (comp_id, user_id) VALUES (:cid, :uid)";
        $stmt = $db->prepare($query);
        $joined = false;
        try {
            $stmt->execute([":cid" => $comp_id, ":uid" => get_user_id()]);
            $joined = true;
        } catch (PDOException $e) {
            $err = $e->errorInfo;
            if ($err[1] === 1062) {
                flash("You already joined this competition", "warning");
                return;
            }
            error_log("Error joining competition (CompetitionParticipants): " . var_export($err, true));
        }
        if ($joined) {
            if ($join_fee == 0){
                $reward_increase = 1;
            } else {
                $reward_increase = ceil(0.5 * $join_fee);
            }
            $query = "UPDATE Competitions set 
            current_participants = (SELECT count(1) from CompetitionParticipants WHERE comp_id = :cid),
            current_reward = current_reward + $reward_increase
            WHERE id = :cid";
            $stmt = $db->prepare($query);
            try {
                $stmt->execute([":cid" => $comp_id]);
            } catch (PDOException $e) {
                error_log("Error updating competition stats: " . var_export($e->errorInfo, true));
                //I'm choosing not to let failure here be a big deal, only 1 successful update periodically is required
            }
            if ($isCreator) {
                $join_fee = 0;
            }
            change_points(-$join_fee, "Joined Competition " . $comp_id, -1, true);
            flash("Successfully joined Competition \"$name\"");
            return;
        } else {
            flash("Unknown error joining competition, please try again", "danger");
            return;
        }
    } else {
        flash("Competition not found.", "warning");
        return;
    }
}

function redirect($path)
{ //header headache
    //https://www.php.net/manual/en/function.headers-sent.php#90160
    /*headers are sent at the end of script execution otherwise they are sent when the buffer reaches it's limit and emptied */
    if (!headers_sent()) {
        //php redirect
        die(header("Location: " . get_url($path)));
    }
    //javascript redirect
    echo "<script>window.location.href='" . get_url($path) . "';</script>";
    //metadata redirect (runs if javascript is disabled)
    echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . get_url($path) . "\"/></noscript>";
    die();
}

function get_top_scores_for_comp($comp_id, $limit = 10)
{
    $db = getDB();

    //Below if a user can't win more than one place
    
    $stmt = $db->prepare("SELECT * FROM (SELECT s.user_id, s.score, s.created, u.username as username, DENSE_RANK() OVER 
    (PARTITION BY s.user_id ORDER BY s.score desc) as 'rank' FROM Scores s
    JOIN CompetitionParticipants cp on cp.user_id = s.user_id
    JOIN Competitions c on cp.comp_id = c.id
    JOIN Users u on u.id = s.user_id
    WHERE c.id = :cid AND s.created BETWEEN cp.created AND c.expires AND s.score >= c.min_score
    )as t where `rank` = 1 ORDER BY score desc LIMIT :limit");

    $scores = [];
    try {
        $stmt->bindValue(":cid", $comp_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $scores = $r;
        }
    } catch (PDOException $e) {
        flash("There was a problem fetching scores, please try again later", "danger");
        error_log("List competition scores error: " . var_export($e, true));
    }
    return $scores;
}

function elog($data)
{
    echo "<br>" . var_export($data, true) . "<br>";
    error_log(var_export($data, true));
}

function calc_winners()
{
    $db = getDB();
    elog("Starting winner calc");
    $calced_comps = [];
    $stmt = $db->prepare("SELECT id, name, current_reward, first_place_per, second_place_per, third_place_per FROM Competitions WHERE expires <= CURRENT_TIMESTAMP() and paid_out = 0 AND current_participants >= min_participants LIMIT 10");
    try {
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $rc = $stmt->rowCount();
            elog("Validating $rc comps");
            foreach ($r as $row) {
                $fp = floatval(se($row, "first_place_per", 0, false) / 100);
                $sp = floatval(se($row, "second_place_per", 0, false) / 100);
                $tp = floatval(se($row, "third_place_per", 0, false) / 100);
                $reward = (int)se($row, "current_reward", 0, false);
                $name = se($row, "name", "-", false);
                $fpr = ceil($reward * $fp);
                $spr = ceil($reward * $sp);
                $tpr = ceil($reward * $tp);
                $comp_id = se($row, "id", -1, false);
                
                try {
                    $r = get_top_scores_for_comp($comp_id, 3);
                    echo '<pre>'; print_r($r); echo '</pre>';
                    if ($r) {
                        $atleastOne = false;
                        foreach ($r as $index => $row) {
                            $score = se($row, "score", 0, false);
                            $user_id = se($row, "user_id", -1, false);
                            if ($index == 0) {
                                if (change_points($fpr, "First place in $name with score of $score")) {
                                    $atleastOne = true;
                                }
                                elog("User $user_id First place in $name with score of $score");
                            } else if ($index == 1) {
                                if (change_points($spr, "Second place in $name with score of $score")) {
                                    $atleastOne = true;
                                }
                                elog("User $user_id Second place in $name with score of $score");
                            } else if ($index == 2) {
                                if (change_points($tpr, "Third place in $name with score of $score")) {
                                    $atleastOne = true;
                                }
                                elog("User $user_id Third place in $name with score of $score");
                            }
                        }
                        if ($atleastOne) {
                            array_push($calced_comps, $comp_id);
                        }
                    } else {
                        elog("No eligible scores");
                    }
                } catch (PDOException $e) {
                    error_log("Getting winners error: " . var_export($e, true));
                }
            }
        } else {
            elog("No competitions ready");
        }
    } catch (PDOException $e) {
        error_log("Getting Expired Comps error: " . var_export($e, true));
    }
    //closing calced comps
    if (count($calced_comps) > 0) {
        foreach ($calced_comps as $compId) {
            $query = "UPDATE Competitions set paid_out = 1 WHERE id = " . $compId;
            elog("Close query: $query");
            $stmt = $db->prepare($query);
            try {
                $stmt->execute();
                $updated = $stmt->rowCount();
                elog("Marked $updated comps complete and calced");
            } catch (PDOException $e) {
                error_log("Closing valid comps error: " . var_export($e, true));
            }
        }
    } else {
        elog("No competitions to calc");
    }
    //close invalid comps
    $stmt = $db->prepare("UPDATE Competitions set paid_out = 1 WHERE expires <= CURRENT_TIMESTAMP() AND current_participants < min_participants");
    try {
        $stmt->execute();
        $rows = $stmt->rowCount();
        elog("Closed $rows invalid competitions");
    } catch (PDOException $e) {
        error_log("Closing invalid comps error: " . var_export($e, true));
    }
    elog("Done calc winners");
}

?>