<?php

$startTime = 0;
//$startTime = 1677776400;
$data = json_decode(base64_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/data/tests.txt")), true);
$message = base64_decode(trim(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/data/message.txt")));

require_once $_SERVER['DOCUMENT_ROOT'] . "/Parsedown.php"; $Parsedown = new Parsedown();

function pkcs7_unpad($data) {
    return substr($data, 0, -ord($data[strlen($data) - 1]));
}

function decrypt($message, $key, $id) {
    global $data;

    $iv = "";
    if ($id === 0) $iv = base64_decode($data[0]["iv"]);
    if ($id === 1) $iv = base64_decode($data[1]["iv"]);

    $decrypted = openssl_decrypt($message, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($decrypted !== false) $unpadded = pkcs7_unpad($decrypted);
    if (isset($unpadded) && !is_string($unpadded)) return false;

    return $unpadded ?? false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html {
            background: lightblue;
        }

        body {
            background: white;
            padding: 20px;
            border: 5px ridge deepskyblue;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <h1 style="margin-top: 0;">Message</h1>

    <?php

    if (isset($_POST["part1"])) {
        $tested = decrypt(base64_decode($data[0]["test"]), trim($_POST["part1"]), 0);
    }

    if (isset($_POST["part1"]) && isset($tested) && $tested !== false):

        setcookie("Password_Part1", $_POST["part1"], time() + 86400 * 90, "/", "", true, true);

        ?>
    <?php if (time() > $startTime || (isset($_POST["ignore-invalid-time"]) && $_POST["ignore-invalid-time"] === "yes-please")): ?>
        <?php

        if (isset($_POST["part2"])) {
            $tested2 = decrypt(base64_decode($data[1]["test"]), trim($_POST["part2"]), 1);
        }

        if (isset($_POST["part2"]) && isset($tested2) && $tested2 !== false):

            ?>
        <?php if (isset($_POST["view"])): ?>
        <p>Here is the whole message:</p>
        <div style="font-family: serif; background-color: whitesmoke; border: 2px dotted lightgray; padding: 0 1em;">
            <?= $Parsedown->parse(decrypt($message, base64_encode(json_encode([
                trim($_POST["part1"]),
                trim($_POST["part2"])
            ])), 0)); ?>
        </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="ignore-invalid-time" value="<?= $_POST["ignore-invalid-time"] ?? "" ?>">
                <input type="hidden" name="part1" value="<?= $_POST["part1"] ?? "" ?>">
                <input type="hidden" name="part2" value="<?= $_POST["part2"] ?? "" ?>">
                <input type="hidden" name="view" value="">
                <p>You are now all set! Make sure you do not close this tab/window or your information will be discarded. Click on the button below to view the message. If you are not willing to see this message, close this tab and read it later.</p>
                <button>View message</button>
            </form>
        <?php endif; ?>
        <?php else: ?>
            <?php if (isset($tested2) && $tested2 === false): ?>
                <div style="background-color: khaki; border: 2px dotted red; padding: 10px 20px;">
                    The second part of password you entered is not correct. Make sure you enter the correct one entirely, with all characters, and without changing it.
                </div>
            <?php endif; ?>
        <form method="post">
            <input type="hidden" name="ignore-invalid-time" value="<?= $_POST["ignore-invalid-time"] ?? "" ?>">
            <input type="hidden" name="part1" value="<?= $_POST["part1"] ?? "" ?>">
            <p>
                <label>
                    Enter the second part of the password:
                    <input type="password" name="part2">
                </label>
            </p>
            <button>Continue</button>
        </form>
        <?php endif; ?>
    <?php else: ?>
        <div style="background-color: khaki; border: 2px dotted red; padding: 10px 20px;">
            The first part of password you entered is correct, but the message is not yet available. Please come back (with the second part of the password) on <?= date('l j F Y', $startTime) ?> at <?= date('g:ia (e)', $startTime) ?>.
        </div>
    <?php endif; ?>
    <?php else: ?>
    <?php if (isset($tested) && $tested === false): ?>
        <div style="background-color: khaki; border: 2px dotted red; padding: 10px 20px;">
            The first part of password you entered is not correct. Make sure you enter the correct one entirely, with all characters, and without changing it.
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="ignore-invalid-time" value="<?= isset($_GET["ignore-invalid-time"]) ? "yes-please" : "" ?>">
        <p>
            <label>
                Enter the first part of the password:
                <input type="password" name="part1" value="<?= $_COOKIE["Password_Part1"] ?? "" ?>">
            </label>
        </p>
        <button>Continue</button>
    </form>
    <?php endif; ?>

    <hr>
    <p style="color: gray;margin-bottom:0;"><i>© <?= date('Y') ?> Equestria.dev, All rights reserved. Confidential, do not disclose.</i></p>
</body>
</html>