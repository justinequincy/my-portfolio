<?php

function scan_file_with_clamav(string $filePath): array
{
    $scannerPath = getenv('CLAMSCAN_PATH');
    if (!is_string($scannerPath) || trim($scannerPath) === '') {
        $scannerPath = 'C:\\Program Files\\ClamAV\\clamscan.exe';
    }

    if (!is_file($scannerPath)) {
        return [
            'status' => 'unavailable',
            'message' => 'ClamAV is not installed or CLAMSCAN_PATH is not configured.'
        ];
    }

    $process = proc_open(
        [$scannerPath, '--no-summary', '--infected', $filePath],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ],
        $pipes,
        null,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($process)) {
        return [
            'status' => 'error',
            'message' => 'Unable to start the ClamAV scanner.'
        ];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode === 0) {
        return ['status' => 'clean', 'message' => ''];
    }

    if ($exitCode === 1) {
        return ['status' => 'infected', 'message' => trim($output)];
    }

    return [
        'status' => 'error',
        'message' => trim($output) !== '' ? trim($output) : 'ClamAV returned an error.'
    ];
}
