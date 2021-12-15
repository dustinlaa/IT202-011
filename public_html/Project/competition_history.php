<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));
}


$db = getDB();
$filter = "joined";

$per_page = 10;
$user_id = get_user_id();
paginate("SELECT count(1) as total FROM Competitions c JOIN CompetitionParticipants cp where cp.user_id = $user_id AND cp.comp_id = c.id");
    $query =
        "SELECT c.id, name, if(expires <= current_timestamp(),'expired', expires) as expires, current_reward, join_fee, current_participants, min_participants, 1 as joined FROM Competitions c 
 JOIN CompetitionParticipants cp WHERE cp.user_id = :uid AND cp.comp_id = c.id ORDER BY expires asc LIMIT " . $offset . ',' . $per_page;
    $title = "Competition History";


$stmt = $db->prepare($query);
$results = [];
try {
    //TODO add other filters for when there are a ton of competitions (i.e., filter by name or other attributes)
    $stmt->execute([":uid" => get_user_id()]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching joined competitons: " . var_export($e->errorInfo, true));
}

?>


<div class="container-fluid">
    <div class="fw-bold fs-3">
        <?php se($title); ?>
    </div>
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
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <br></br>
    <?php if (count($results) != 0) : ?>
        <?php include(__DIR__ . "/../../partials/pagination.php"); ?>
    <?php endif; ?>
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
<?php
require(__DIR__ . "/../../partials/footer.php");
?>

<style>
    .list-group-item {
        background: #212529;
        border: solid white;
        color: white;
    }
</style>