<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));
}

$results = [];
$db = getDB();
$title = "Active Competitions";

$query =
        "SELECT id, name, expires, current_reward, join_fee, current_participants, min_participants,
(select IFNULL(count(1),0) FROM CompetitionParticipants cp WHERE cp.comp_id = c.id AND cp.user_id = :uid) as joined FROM Competitions c 
WHERE paid_out = 0 ORDER BY expires asc limit 10";
    $title = "Active Competitions";

$stmt = $db->prepare($query);
try {
    //TODO add other filters for when there are a ton of competitions (i.e., filter by name or other attributes)
    $stmt->execute([":uid" => get_user_id()]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching active competitons: " . var_export($e->errorInfo, true));
}
?>


<div class="container-fluid">
    <div class="fw-bold fs-3">
        <?php se($title); ?>
    </div>
    <!-- Note, this "table-like" layout doesn't scale well for mobile-->
    <div class="list-group">
        <div class="list-group-item">
            <div class="row fw-bold">
                <div class="col">Name</div>
                <div class="col">Reward</div>
                <div class="col">Participants</div>
                <div class="col">Ends</div>
                <div class="col">Join Fee</div>
                <div class="col">Actions</div>
            </div>
        </div>
        <?php if (!!$results === false || count($results) == 0) : ?>
            <div class="list-group-item">
                <div class="row">
                    <div class="col-12">No <?php se($filter);?> competitions</div>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($results as $result) : ?>
                <div class="list-group-item">
                    <div class="row">
                        <div class="col"><?php se($result, "name"); ?></div>
                        <div class="col"><?php se($result, "current_reward"); ?></div>
                        <div class="col"><?php se($result, "current_participants"); ?>/<?php se($result, "min_participants"); ?></div>
                        <div class="col"><?php se($result, "expires"); ?></div>
                        <div class="col"><?php se($result, "join_fee"); ?></div>
                        <div class="col">
                            <a class="btn btn-primary" href="view_competition.php?id=<?php se($result, "id"); ?>">Details</a>
                            <?php if ((int)se($result, "joined", 0, false) > 0) : ?>
                                <button class="btn btn-secondary" disabled><em>Joined</em></button>
                            <?php elseif (se($result, "expires","expired", false) !== "expired") : ?>
                                <button class="btn btn-success" onclick="joinCompetition(<?php se($result, 'id'); ?>,this)">Join</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
        function joinCompetition(comp_id, ele) {
            if (!!window.jQuery === true) {
                $.post("api/join_competition.php", {
                    comp_id: comp_id
                }, (data) => {
                    let json = JSON.parse(data);
                    flash(json.message);
                    $(ele).attr("disabled", "true");
                    $(ele).html("<em>Joined</em>");
                });
            } else {
                //fetch api version of purchase call
                fetch("api/join_competition.php", {
                    method: "POST",
                    headers: {
                        "Content-type": "application/x-www-form-urlencoded",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: "comp_id=" + comp_id
                }).then(async res => {
                    console.log(res);
                    let data = await res.json();
                    flash(json.message);
                    ele.disabled = true;
                    ele.innerHTML = "<em>Joined</em>";
                });
            }
        }
    </script>
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>


<style>
    .list-group-item {
        background: #212529;
        border: solid white;
        color: white;
    }
</style>