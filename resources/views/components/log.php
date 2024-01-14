<div class="mb-1 mt-<?php echo $marginTop ?>">
    <?php if (isset($timestamp)) { ?>
        <span class="text-gray mr-1">
            <?php echo "[{$timestamp}]" ?>
        </span>
    <?php } ?>

    <span class="px-1 bg-<?php echo $bgColor ?> text-<?php echo $fgColor ?>">
        <?php echo strtoupper($title) ?>
    </span>

    <span class="<?php if ($title) {
        echo 'ml-1';
    } ?>">
        <?php echo htmlspecialchars($content) ?>
    </span>
</div>
