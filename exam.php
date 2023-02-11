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
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <span class="navbar-brand"><?= htmlentities(APP_NAME) ?></span>
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
                    <?php if ($exam['meta']['time'] !== null) : ?>
                        <span class="badge bg-secondary"><?= T['EXAM.Remain'] ?> <span id="e-time">0:00</span></span>
                    <?php endif; ?>
                </div>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col">
                <form action="submit.php" method="post" id="e-f-f" class="loadhide">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="time" id="e-time">

                    <div id="e-f-q">
                        <div class="mb-3">
                            <label for="e-q-uname-f" class="form-label">Username for certificate</label>
                            <input type="text" id="e-q-uname-f" name="name" class="form-control" required>
                        </div>
                        <?php foreach ($exam['questions'] as $i => $q) : ?>
                            <div class="mb-3">
                                <h3>No. <?= $i + 1 ?></h3>
                                <label for="e-q-<?= $i ?>-f" class="form-label"><?= htmlentities($q['qtext']) ?></label>
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
                                <?php if ($q['type'] === 'select') : ?>
                                    <select name="q-<?= $i ?>" id="e-q-<?= $i ?>-f" class="form-select">
                                        <?php shuffle($q['option']); ?>
                                        <?php foreach ($q['option'] as $o) : ?>
                                            <option value="<?= htmlentities($o) ?>"><?= htmlentities($o) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <input type="text" name="q-<?= $i ?>" id="e-q-<?= $i ?>-f" class="form-control">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <input type="checkbox" name="sys-cfm" id="confirm-check" required>
                        <label for="confirm-check">Confirm submit</label>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary" id="e-f-s" onclick="s()">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            const eTime = document.getElementById('e-time');
            let time = <?= ($exam['meta']['time'] ?? 0) * 60 ?>;
            if (<?= $exam['meta']['time'] !== null ? 'true' : 'false' ?>) {
                setInterval(() => {
                    time--;
                    const m = Math.floor(time / 60);
                    let s = time % 60;
                    if (s < 10) s = '0' + s;
                    eTime.innerText = `${m}:${s}`;
                    if (time < 0) {
                        if (document.getElementById('e-q-uname-f').value === '')
                            document.getElementById('e-q-uname-f').value = 'Unnamed user';
                        document.getElementById('e-f-q').classList.add('loadhide');
                        document.getElementById('confirm-check').checked = true;
                        document.getElementById('e-f-s').click();
                    }
                }, 1000);
            }

            document.getElementById('e-f-f').classList.remove('loadhide');
        }
        const s = function() {
            const sing = document.createElement('div');
            sing.innerText = 'Submitting...';
            document.getElementById('e-time').value = Math.floor(((new Date()).getTime()) / 1000);
            document.getElementById('e-f-f').parentElement.appendChild(sing);
            document.getElementById('e-f-f').classList.add('loadhide');
            setTimeout(() => {
                document.getElementById('e-f-s').remove();
            }, 0);
        }
    </script>
</body>

</html>