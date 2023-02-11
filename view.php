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
$lng = $exam['meta']['langcode'];
if (!isset(LANG[$lng])) $lng = 'en';
define('T', LANG[$lng]);
?>
<html lang="<?= $lng ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlentities($exam['meta']['name']) ?> - <?= htmlentities(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="/"><?= htmlentities(APP_NAME) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col">
                <div>
                    <span><?= htmlentities($exam['meta']['shortname']) ?></span>
                    <h1><?= htmlentities($exam['meta']['name']) ?></h1>
                </div>
                <div>
                    <?php if ($exam['status']['take'] === true) : ?>
                        <span class="badge bg-primary"><?= T['VIEW.Active'] ?></span>
                    <?php endif; ?>
                    <?php if ($exam['status']['check'] === true) : ?>
                        <span class="badge bg-info text-dark"><?= T['VIEW.Answers'] ?></span>
                    <?php endif; ?>
                    <?php if ($exam['status']['false'] === true) : ?>
                        <span class="badge bg-danger"><?= T['VIEW.Invalid'] ?></span>
                    <?php endif; ?>

                    <span class="badge bg-dark"><?= $exam['meta']['lang'] ?></span>
                    <span class="badge bg-secondary"><?= $exam['meta']['time'] ?? '∞' ?> <?= T['VIEW.mins'] ?></span>
                </div>
                <hr />
                <div>
                    <?= $exam['meta']['description'] ?>
                </div>
                <div>
                    <?php if ($exam['status']['take'] === true) : ?>
                        <form action="exam.php" method="POST">
                            <button class="btn btn-primary"><?= T['VIEW.TakeExam'] ?></button>
                            <input type="hidden" name="id" value="<?= $id ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>