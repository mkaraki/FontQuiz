<!DOCTYPE html>
<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/langpack.php';

if (!isset($_GET['id']) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\-\.]*[a-zA-Z0-9]$/', $_GET['id'])) {
    die('Examination not found');
}

$id = $_GET['id'];
$idpath = __DIR__ . '/questions/' . $id . '.json';
if (!is_file($idpath)) {
    die('Examination not found');
}

$exam = json_decode(file_get_contents($idpath), true);
$lng = $_GET['lang'] ?? $exam['meta']['langcode'];
if (!isset(LANG[$lng])) $lng = 'en';
define('T', LANG[$lng]);

$uname = $_GET['name'] ?? '';
$time = is_numeric($_GET['time'] ?? 'a') ? intval($_GET['time']) : 0;
$license = $_GET['license'] ?? '';

$validate = $id . CERTKEY1 . $uname . $time . $exam['meta']['shortname'];
$clicense = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash('sha256', $validate, true)));
$disphash = hash('crc32', $validate);

if ($license !== $clicense) {
    die('Invalid license');
}
?>
<html lang="<?= $lng ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= T['CERT.Certificate'] ?> - <?= htmlentities($exam['meta']['name']) ?> - </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            max-width: 450px;
            margin: 5px auto;
            padding: 15px;
        }

        @media print {

            .no-print,
            .no-print * {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-white">
    <div class="mb-5">
        <h1 class="text-center"><?= T['CERT.Certificate'] ?></h1>
    </div>
    <div class="mb-4">
        <p><?= htmlentities(str_replace(['%1', '%u'], [$exam['meta']['name'], $uname], T['CERT.YouPassed'])) ?></p>
    </div>
    <div class="mb-1">
        <?= htmlentities(gmdate('Y-m-d', time())) ?>
    </div>
    <div class="mb-1">
        <?= htmlentities(str_replace(['%u', '%a'], [$exam['meta']['author'], APP_NAME], T['CERT.SignedBy'])) ?>
    </div>
    <div class="bg-white text-white no-print">
        Certificate Hash: <?= $disphash ?>
    </div>
</body>

</html>