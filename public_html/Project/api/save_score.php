<?php
$response = ["message" => "There was a problem saving your score"];
http_response_code(400);
$contentType = $_SERVER["CONTENT_TYPE"];

error_log("Content Type $contentType");
if ($contentType === "application/json") {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true)["data"];
} else if ($contentType === "application/x-www-form-urlencoded") {
    $data = $_POST;
}

error_log(var_export($data, true));

session_start();
$reject = false;
require_once(__DIR__ . "/../../../lib/functions.php");
$user_id = get_user_id();
if ($user_id <= 0) {
    $reject = true;
    error_log("User not logged in");
    http_response_code(403);
    $response["message"] = "You must be logged in to save your score";
    flash($response["message"], "warning");
}
if (!$reject) {
    $user_id = get_user_id();
    $score = (int)se($data, "score", 0, false);
    $score_reward = $score/20;
    $point_reward = 0;
    while ($score_reward > 1) {
        $point_reward++;
        $score_reward--;
    }
    change_points($point_reward, "Earned " . $point_reward . " points for earning a score of " . $score, $user_id);
    flash("Earned " . $point_reward . " points for earning a score of " . $score, "success");
    save_score($score, $user_id, true);
    $response["message"] = "Score Saved!";
    error_log("Score of $score saved successfully for $user_id");

    http_response_code(200);
}
echo json_encode($response);

?>