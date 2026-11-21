Kohana console

Usage:
  php <?= basename($_SERVER['argv'][0]) ?> <command> [arguments] [--option=value]

Available commands:
<?php $width = max(array_map('strlen', array_keys($tasks)) ?: [0]); ?>
<?php foreach ($tasks as $task => $description): ?>
  <?= str_pad($task, $width + 2) . $description ?>

<?php endforeach; ?>

Run "php <?= basename($_SERVER['argv'][0]) ?> <command> --help" for a command's options.
