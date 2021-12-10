<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
$db = getDB();
//handle join
if (isset($_POST["join"])) {
    $comp_id = se($_POST, "comp_id", 0, false);
    join_competition($comp_id);
}
$id = se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid competition", "danger");
    redirect("list_competitions.php");
}
//handle page load
/* $stmt = $db->prepare("SELECT BGD_Competitions.id, title, min_participants, current_participants, current_reward, expires, creator_id, min_score, join_cost, IF(competition_id is null, 0, 1) as joined,  CONCAT(first_place,'% - ', second_place, '% - ', third_place, '%') as place FROM BGD_Competitions
JOIN BGD_Payout_Options on BGD_Payout_Options.id = BGD_Competitions.payout_option
LEFT JOIN BGD_UserComps on BGD_UserComps.competition_id = BGD_Competitions.id WHERE user_id = :uid AND BGD_Competitions.id = :cid");
*/
$stmt = $db->prepare("SELECT Competitions.id, name, expires, current_reward, join_fee, current_participants, min_participants, min_score 
FROM Competitions 
LEFT JOIN CompetitionParticipants on CompetitionParticipants.comp_id = Competitions.id WHERE Competitions.id = :cid AND user_id = :uid");
$row = [];
$comp = "";
try {
    $stmt->execute([":cid" => $id, ":uid" => get_user_id()]);
    $r = $stmt->fetch();
    if ($r) {
        $row = $r;
        $comp = se($r, "name", "N/A", false);
    }
} catch (PDOException $e) {
    flash("There was a problem fetching competitions, please try again later", "danger");
    error_log("List competitions error: " . var_export($e, true));
}
$scores = get_top_scores_for_comp($id);
//echo '<pre>'; print_r($scores); echo '</pre>';
?>
<div class="container-fluid">
    <h1>View Competition: <?php se($comp); ?></h1>
    <table class="table text-light">
        <thead>
            <th>Name</th>
            <th>Participants</th>
            <th>Reward</th>
            <th>Min Score</th>
            <th>Expires</th>
        </thead>
        <tbody>
            <?php if (count($row) > 0) : ?>
                <td><?php se($row, "name"); ?></td>
                <td><?php se($row, "current_participants"); ?>/<?php se($row, "min_participants"); ?></td>
                <td><?php se($row, "current_reward"); ?></td>
                <td><?php se($row, "min_score"); ?></td>
                <td><?php se($row, "expires", "-"); ?></td>
            <?php else : ?>
                <tr>
                    <td colspan="100%">No active competitions</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    //$scores is defined above
    $title = $comp . " Top Scores";
    include(__DIR__ . "/../../partials/score_table.php");
    ?>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>


