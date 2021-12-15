<?php
require(__DIR__ . "/../../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));
}


$db = getDB();

$per_page = 10;
$user_id = get_user_id();
paginate("SELECT count(1) as total FROM Competitions where paid_out < 1");
    $query =
        "SELECT id, name, if(expires <= current_timestamp(),'expired', expires) as expires, current_reward, join_fee, current_participants, min_participants FROM Competitions WHERE paid_out < 1
         ORDER BY expires asc LIMIT " . $offset . ',' . $per_page;
    $title = "Edit Competition";


$stmt = $db->prepare($query);
$results = [];
try {
    //TODO add other filters for when there are a ton of competitions (i.e., filter by name or other attributes)
    $stmt->execute();
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
                        <td><a class="btn btn-primary" href="edit_competition.php?id=<?php se($row, 'id'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="100%">No Competitions to Edit</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table> 
    <?php if (count($results) != 0) : ?>
        <?php include(__DIR__ . "/../../../partials/pagination.php"); ?>
    <?php endif; ?>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
<?php
require(__DIR__ . "/../../../partials/footer.php");
?>

<style>
    table {
        background: #212529;
        border: solid white;
        color: white;
    }
</style>