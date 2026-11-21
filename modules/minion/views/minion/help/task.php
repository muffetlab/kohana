Usage:
  php kohana <?= $task ?> [arguments] [--option=value]

<?= $description ?>

<?php if ($tags): ?>

<?php foreach ($tags as $tag_name => $tag_content): ?>
<?= ucfirst($tag_name) ?>: <?= $tag_content ?>

<?php endforeach; ?>
<?php endif; ?>
