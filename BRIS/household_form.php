<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

start_page('Add Household');
?>

<div class="pagehead">
    <div class="wrap">
        <h1>Add Household</h1>
        <p class="muted">Register a household so residents can be assigned to it.</p>
    </div>
</div>

<main class="wrap">
    <div class="panel">
        <?php show_flash(); ?>

        <form action="household_save.php" method="post">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">

            <div class="formgrid">
                <div class="field">
                    <label for="household_code">Household Code *</label>
                    <input id="household_code" type="text" name="household_code" maxlength="30" required placeholder="e.g. HH-0003">
                    <small>Enter a unique code for this household.</small>
                </div>

                <div class="field">
                    <label for="head_name">Head of Household</label>
                    <input id="head_name" type="text" name="head_name" maxlength="160" placeholder="Full name (optional)">
                </div>

                <div class="field full">
                    <label for="address">Complete Address *</label>
                    <textarea id="address" name="address" maxlength="255" required></textarea>
                </div>
            </div>

            <div class="actions" style="justify-content:flex-start">
                <button type="submit" class="btn primary">Save Household</button>
                <a class="btn" style="background:#edf1f7" href="households.php">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php end_page(); ?>
