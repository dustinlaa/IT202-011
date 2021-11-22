<?php
//requires functions.php
//requires a duration to be set
if (!isset($duration)) {
    $duration = "week"; //choosing to default to week
}

if ($duration == "Weekly") {
    $duration = "week";
}
if ($duration == "Monthly") {
    $duration = "month";
}
if ($duration == "Lifetime") {
    $duration = "lifetime";
}
$results = get_top_10($duration);

switch ($duration) {
    case "week":
        $title = "Top 10 Weekly";
        $time = "weekly";
        break;
    case "month":
        $title = "Top 10 Monthly";
        $time = "monthly";
        break;
    case "lifetime":
        $title = "Top 10 Lifetime";
        $time = "lifetime";
        break;
    default:
        $title = "Invalid Scoreboard";
        break;
}
?>
<div class="card mb-4 border-0 bg-transparent">
    <div class="card-body">
        <div class="card-title">
            <div class="fw-bold fs-3">
                <?php se($title); ?>
            </div>
        </div>
        <div class="card-text">
            <table class="table text-light">
                <thead>
                    <th>User</th>
                    <th>Score</th>
                    <th>Achieved</th>
                </thead>
                <tbody>
                    <?php if (!$results || count($results) == 0) : ?>
                        <tr>
                            <td colspan="100%"> No <?php se($time); ?> scores to display</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($results as $result) : ?>
                            <tr>
                                <td>
                                    <!--<a href="profile.php?id=<?php se($result, 'user_id'); ?>"><?php se($result, "username"); ?></a>-->
                                    <?php se($result, "username"); ?>
                                </td>
                                <td><?php se($result, "score"); ?></td>
                                <td><?php se($result, "created"); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>