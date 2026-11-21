# Console Setup

The `kohana` script in the project root needs no setup. Run it with PHP:

    php kohana

On Unix-like systems you can also make it executable and call it directly:

    chmod +x kohana
    ./kohana route:list

The older entry point still works when the Minion module is enabled in `app/bootstrap.php`:

    php public/index.php --task=route:list
