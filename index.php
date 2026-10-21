<?php
// Group 5: Mobile Data Bundle Selector

// Constant: the currency label used for all bundle prices and totals.
define("CURRENCY", "UGX");

// Associative array: each bundle name maps to its data allowance (GB) and price.
$bundles = [
    "Daily" => ["data" => 1, "price" => 2000],
    "Weekly" => ["data" => 5, "price" => 10000],
    "Monthly" => ["data" => 20, "price" => 35000],
    "Heavy User" => ["data" => 50, "price" => 70000]
];

// Variables: request state, current selections, and the default budget.
$isSubmitted = $_SERVER["REQUEST_METHOD"] === "POST";
// Indexed array: the bundles preselected when the page first loads.
$selectedBundles = ["Daily", "Monthly"];
$budget = 40000;

// Variables: preserve the form value and report invalid budget input.
$budgetInput = (string) $budget;
$hasValidBudget = true;
$error = "";

if ($isSubmitted) {
    // Reset the selections so only bundles submitted by the customer are counted.
    $selectedBundles = [];
    // Array: customer-submitted bundle names, or an empty array if none were selected.
    $submittedSelections = isset($_POST["bundles"]) && is_array($_POST["bundles"])
        ? $_POST["bundles"]
        : [];

    // Add only valid, unique bundle names to the selections array.
    foreach ($submittedSelections as $name) {
        if (is_string($name) && isset($bundles[$name]) && !in_array($name, $selectedBundles, true)) {
            $selectedBundles[] = $name;
        }
    }

    // Variable: validated integer value used as the customer's budget.
    $submittedBudget = isset($_POST["budget"]) && is_string($_POST["budget"])
        ? trim($_POST["budget"])
        : "";
    $budgetInput = $submittedBudget;
    // Options array: sets the minimum accepted budget to zero.
    $validatedBudget = filter_var(
        $submittedBudget,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 0]]
    );

    if ($validatedBudget === false) {
        $error = "Enter a valid budget of 0 or more UGX.";
        $hasValidBudget = false;
    } else {
        $budget = $validatedBudget;
    }
}

// Variables: totals calculated from the selected bundles.
$totalCost = 0;
$totalData = 0;

foreach ($selectedBundles as $name) {
    if (isset($bundles[$name])) {
        $totalCost += $bundles[$name]["price"];
        $totalData += $bundles[$name]["data"];
    }
}

// Variables: whether the budget covers the total and the difference.
$budgetIsSufficient = $hasValidBudget && $totalCost <= $budget;
$budgetDifference = $budgetIsSufficient
    ? $budget - $totalCost
    : $totalCost - $budget;

function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Data Bundle Selector</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>Mobile Data Bundle Selector</h1>
    <p class="muted">Choose one or more bundles and enter your budget to see your total.</p>

    <form method="post" class="panel">
        <h2>Available bundles</h2>
        <?php foreach ($bundles as $name => $bundle): ?>
            <div class="bundle">
                <label for="bundle-<?php echo escapeHtml(strtolower(str_replace(" ", "-", $name))); ?>">
                    <input
                        type="checkbox"
                        id="bundle-<?php echo escapeHtml(strtolower(str_replace(" ", "-", $name))); ?>"
                        name="bundles[]"
                        value="<?php echo escapeHtml($name); ?>"
                        <?php echo in_array($name, $selectedBundles, true) ? "checked" : ""; ?>
                    >
                    <span class="bundle-details">
                        <strong><?php echo escapeHtml($name); ?></strong>
                        <span class="muted"><?php echo escapeHtml($bundle["data"]); ?> GB</span>
                    </span>
                </label>
                <span class="bundle-price">
                    <strong><?php echo CURRENCY; ?> <?php echo number_format($bundle["price"]); ?></strong>
                    <span class="muted">per bundle</span>
                </span>
            </div>
        <?php endforeach; ?>

        <label class="budget-field" for="budget">
            <strong>Your budget (<?php echo CURRENCY; ?>)</strong>
            <input
                type="number"
                id="budget"
                name="budget"
                min="0"
                step="1"
                value="<?php echo escapeHtml($budgetInput); ?>"
                required
            >
        </label>

        <button type="submit">Calculate my bundles</button>
    </form>

    <section class="panel" aria-live="polite">
        <h2>Your selection summary</h2>
        <?php if ($error !== ""): ?>
            <p class="error"><?php echo escapeHtml($error); ?></p>
        <?php endif; ?>

        <?php if (count($selectedBundles) > 0): ?>
            <ul class="summary-list">
                <?php foreach ($selectedBundles as $name): ?>
                    <li>
                        <strong><?php echo escapeHtml($name); ?></strong> —
                        <?php echo escapeHtml($bundles[$name]["data"]); ?> GB,
                        <?php echo CURRENCY; ?> <?php echo number_format($bundles[$name]["price"]); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No bundles selected.</p>
        <?php endif; ?>

        <dl class="totals">
            <div>
                <dt>Total data allowance</dt>
                <dd><strong><?php echo number_format($totalData); ?> GB</strong></dd>
            </div>
            <div>
                <dt>Total cost</dt>
                <dd><strong><?php echo CURRENCY; ?> <?php echo number_format($totalCost); ?></strong></dd>
            </div>
            <div>
                <dt>Your budget</dt>
                <dd>
                    <strong>
                        <?php if ($hasValidBudget): ?>
                            <?php echo CURRENCY; ?> <?php echo number_format($budget); ?>
                        <?php else: ?>
                            Not valid
                        <?php endif; ?>
                    </strong>
                </dd>
            </div>
        </dl>

        <?php if ($hasValidBudget): ?>
            <p class="status <?php echo $budgetIsSufficient ? "sufficient" : "insufficient"; ?>">
                <?php if ($budgetIsSufficient): ?>
                    Your budget is enough. Remaining budget:
                    <?php echo CURRENCY; ?> <?php echo number_format($budgetDifference); ?>.
                <?php else: ?>
                    Your budget is not enough. You are over budget by:
                    <?php echo CURRENCY; ?> <?php echo number_format($budgetDifference); ?>.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </section>
</main>

<footer class="group-footer">
    <h2>Group 5</h2>
    <ol class="member-grid">
        <li><strong>NUWASIIMA LIBBY</strong><span>252-571 · BSE</span></li>
        <li><strong>WOGUNYA EMMANUEL</strong><span>252-620 · BSE</span></li>
        <li><strong>ANINGA SARAH</strong><span>258-961 · BSE</span></li>
        <li><strong>KISAKYE MERCY NABUDUWA</strong><span>259-572 · BSE</span></li>
        <li><strong>MUZUNI JOHN</strong><span>241-482 · BSE</span></li>
        <li><strong>MATOVU Afford</strong><span>277-383 · BCS</span></li>
        <li><strong>MANGAR BOL MAKUEI</strong><span>237-273 · BIT</span></li>
        <li><strong>SEKANJAKO EZRA</strong><span>268-823 · BIT</span></li>
        <li><strong>KYIBUYE RONALD</strong><span>275-371 · BIT</span></li>
        <li><strong>BAINGANA Julius</strong><span>253-166 · BSE</span></li>
        <li><strong>MASSOUD MAHAMAT</strong><span>277-251 · BCS</span></li>
        <li><strong>MUTIBUWA Tracy Vivian</strong><span>213-849 · BIT</span></li>
        <li><strong>Biyinzika Daniel</strong><span>269-594 · BCS</span></li>
        <li><strong>PETER OBURAH MATHEW</strong><span>276-985 · BIT</span></li>
        <li><strong>BEINGANA DANIEL</strong><span>275-316 · BIT</span></li>
    </ol>
</footer>
</body>
</html>
