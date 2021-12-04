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
        } catch (PDOException $e) {
            flash("Transfer error occurred: " . var_export($e->errorInfo, true), "danger");
        }
    }
}

function join_competition($comp_id, $isCreator = false) {
    if ($comp_id <= 0) {
        return "Invalid Competition";
    }
    $db = getDB();
    $query = "SELECT current_reward, join_fee, paid_out, FROM Competitions where id = :id";
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
        return "Error looking up competition";
    }
    if ($comp && count($comp) > 0) {
        $paid_out = (int)se($comp, "paid_out", 0, false) > 0;
        //$is_expired = (int)se($comp, "is_expired", 0, false) > 0;
        if ($paid_out) {
            return "You can't join a completed competition";
        }
        /*
        if ($is_expired) {
            return "You can't join an expired competition";
        }
        */
        $points = (int)se(get_account_points(), null, 0, false);
        $join_fee = (int)se($comp, "join_fee", 0, false);
        $current_reward = (int)se($comp, "current_reward", 0, false);
        if ($join_fee > $points) {
            return "You can't afford to join this competition";
        }
        $query = "INSERT INTO UserCompetitions (comp_id, user_id) VALUES (:cidm :uid)";
        $stmt = $db->prepare($query);
        $joined = false;
        try {
            $stmt->execute([":cid" => $comp_id, ":uid" => get_user_id()]);
            $joined = true;
        } catch (PDOException $e) {
            $err = $e->errorInfo;
            if ($err[1] === 1062) {
                return "You already joined this competition";
            }
            error_log("Error joining competition (UserCompetitions): " . var_export($err, true));
        }
        if ($joined) {
            //+1 for the current_reward calculation may be needed as current_participants at that point
            // may not see the latest changed value from the current_participants calculation in the same query
            // so using a +1 since really that's all it should be doing and this should yield an accurate reward value
            if ($join_fee == 0){
                $reward_increase = 1;
            } else {
                $reward_increase = ceil(0.5 * $join_fee);
            }
            $query = "UPDATE Competitions set 
            current_participants = (SELECT count(1) from UserCompetitions WHERE comp_id = :cid),
            current_reward = $reward_increase
            WHERE id = :cid";
            $stmt = $db->prepare($query);
            try {
                $stmt->execute([":cid" => $comp_id]);
            } catch (PDOException $e) {
                error_log("Error updating competition stats: " . var_export($e->errorInfo, true));
                //I'm choosing not to let failure here be a big deal, only 1 successful update periodically is required
            }
            //this won't record free competitions due to the inner logic of change_points()
            if ($isCreator) {
                $fee = 0;
            }
            change_points($fee, "join-comp", get_user_id(), -1, "Joined Competition #" . $comp_id, true);
            return "Successfully joined Competition #$comp_id";
        } else {
            return "Unknown error joining competition, please try again";
        }
    } else {
        return "Competition not found.";
    }
}
?>