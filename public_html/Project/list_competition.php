<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));
}

$results = [];
$db = getDB();
$per_page = 10;
paginate("SELECT count(1) as total FROM Competitions where expires > current_timestamp()");
$query =
        "SELECT id, name, expires, current_reward, join_fee, current_participants, min_participants,
(select IFNULL(count(1),0) FROM CompetitionParticipants cp WHERE cp.comp_id = c.id AND cp.user_id = :uid) as joined FROM Competitions c 
WHERE expires > current_timestamp() ORDER BY expires asc LIMIT " . $offset . ',' . $per_page;
    $title = "Active Competitions";

$stmt = $db->prepare($query);
try {
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
    <div class="list-group">
        <table class="table text-light">
            <thead>
                <th>Name</th>
                <th>Reward</th>
                <th>Participants</th>
                <th>Ends</th>
                <th>Join Fee</th>
                <th>Actions</th>
            </thead>
            <tbody>
                <?php if (count($results) > 0) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php se($row, "name"); ?></td>
                            <td><?php se($row, "current_reward"); ?></td>
                            <td><?php se($row, "current_participants"); ?>/<?php se($row, "min_participants"); ?></td>
                            <td><?php se($row, "expires"); ?></td>
                            <td><?php se($row, "join_fee"); ?></td>
                            <td>
                                <div class="col">
                                <a class="btn btn-primary" href="view_competition.php?id=<?php se($row, "id"); ?>">Details</a>
                                <?php if ((int)se($row, "joined", 0, false) > 0) : ?>
                                    <button class="btn btn-secondary" disabled><em>Joined</em></button>
                                <?php elseif (se($row, "expires","expired", false) !== "expired") : ?>
                                    <button class="btn btn-success" onclick="joinCompetition(<?php se($row, 'id'); ?>,this)">Join</button>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="100%">No Active Competitions</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div> 
    <script>
        function joinCompetition(comp_id, ele) {
            if (!!window.jQuery === true) {
                $.post("api/join_competition.php", {
                    comp_id: comp_id
                }, (data) => {
                    let json = JSON.parse(data);
                    //flash(json.message);
                    $(ele).attr("disabled", "true");
                    $(ele).html("<em>Joined</em>");
                    window.location.reload();
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
                    //flash(json.message);
                    ele.disabled = true;
                    ele.innerHTML = "<em>Joined</em>";
                    window.location.reload();
                });
            }
        }
    </script>
    <?php if (count($results) != 0) : ?>
        <?php include(__DIR__ . "/../../partials/pagination.php"); ?>
    <?php endif; ?>  
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>


<style>
    table {
        background: #212529;
        border: solid white;
        color: white;
    }
</style>