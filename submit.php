<!DOCTYPE html>
<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/langpack.php';

putenv('GDFONTPATH=' . realpath('./fonts'));

if (!isset($_POST['id']) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\-\.]*[a-zA-Z0-9]$/', $_POST['id'])) {
    die('Examination not found');
}

$id = $_POST['id'];
$idpath = __DIR__ . '/questions/' . $id . '.json';
if (!is_file($idpath)) {
    die('Examination not found');
}

$exam = json_decode(file_get_contents($idpath), true);
$lng = $exam['meta']['langcode'];
if (!isset(LANG[$lng])) $lng = 'en';
define('T', LANG[$lng]);

$correntAry = [];
$correntCnt = 0;
foreach ($exam['questions'] as $i => $q) {
    if (!isset($_POST['q-' . $i])) {
        $correntAry[$i] = false;
        continue;
    }

    if (in_array($_POST['q-' . $i], $q['ans'], true)) {
        $correntAry[$i] = true;
        $correntCnt++;
    } else {
        $correntAry[$i] = false;
    }
}


$uname = $_POST['name'] ?? null;
$time = time();

if ($uname === null || empty($uname)) {
    die('Unable to validate answers');
}

$clicense = null;

if ($correntCnt >= $exam['meta']['mincorrect']) {
    $validate = $id . CERTKEY1 . $uname . $time . $exam['meta']['shortname'];
    $clicense = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash('sha256', $validate, true)));
}
?>
<html lang="<?= $lng ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlentities($exam['meta']['name']) ?> - <?= htmlentities(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        div.img-h>img {
            max-width: 100%;
            margin: 10px 0;
        }

        .loadhide {
            display: none;
        }

        .loadhide::before {
            content: "Loading...";
            display: block;
        }
    </style>
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
                    <?php if ($exam['status']['check'] === true) : ?>
                        <span class="badge bg-info text-dark"><?= T['VIEW.Answers'] ?></span>
                    <?php endif; ?>

                    <?php if ($clicense !== null) : ?>
                        <span class="badge bg-success">Pass</span>
                    <?php else : ?>
                        <span class="badge bg-danger">Fail</span>
                    <?php endif; ?>
                </div>
                <hr />
            </div>
        </div>
        <?php if ($exam['status']['check'] === true) : ?>
            <div class="row">
                <div class="col">
                    <?php foreach ($exam['questions'] as $i => $q) : ?>
                        <div class="mb-3">
                            <h3>
                                No. <?= $i + 1 ?>
                                <?php if ($correntAry[$i] === true) : ?>
                                    <span class="badge bg-success">Corrent</span>
                                <?php else : ?>
                                    <span class="badge bg-danger">Incorrect</span>
                                <?php endif; ?>
                            </h3>
                            <p><?= htmlentities($q['qtext']) ?></p>
                            <?php if ($q['imgsize'] ?? null !== false) : ?>
                                <div class="img-h">
                                    <?php
                                    $imgsize = $q['imgsize'] ?? [];
                                    if (count($imgsize) !== 2) $imgsize = [400, 50];
                                    $fsize = $q['fsize'] ?? 30;
                                    $font = './fonts/' . ($q['font'] ?? $exam['meta']['font'] ?? '');
                                    $img = imagecreatetruecolor($imgsize[0], $imgsize[1]);

                                    $blk = imagecolorallocate($img, 0, 0, 0);
                                    $bg = imagecolorallocate($img, 240, 240, 240);

                                    imagefill($img, 0, 0, $bg);
                                    imagettftext($img, $fsize, 0, 0, $fsize + 10, $blk, $font, $q['text']);

                                    ob_start();
                                    imagewebp($img);
                                    $img_b64 = base64_encode(ob_get_contents());
                                    ob_end_clean();
                                    imagedestroy($img);
                                    ?>
                                    <img src="data:image/webp;base64,<?= $img_b64 ?>" alt="Question No.<?= $i + 1 ?>">
                                </div>
                            <?php endif; ?>

                            <div>
                                You: <code><?= htmlentities($_POST['q-' . $i]) ?></code><br />
                                Answers:
                                <ul>
                                    <?php foreach ($q['ans'] as $a) : ?>
                                        <li><?= htmlentities($a) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col">
                <?php if ($clicense !== null) : ?>
                    <form action="/certificate.v1.php" method="get">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="time" value="<?= $time ?>">
                        <input type="hidden" name="license" value="<?= $clicense ?>">
                        <input type="hidden" name="name" value="<?= htmlentities($uname) ?>">
                        <button type="submit" class="btn btn-primary">Generate Certificate</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>