<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
$config = require('env.php');

// 必須項目チェック
$required = ['お名前', 'メールアドレス', 'ご相談内容'];
$errors = [];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $errors[] = "【{$field}】は必須項目です。";
    }
}
if (!empty($errors)) {
    echo 'error';
    exit;
}

// 値取得
$name = $_POST['お名前'] ?? '';
$email = $_POST['メールアドレス'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$now = date('Y/m/d (D) H:i:s');

// メール本文作成
$message = '';
foreach ($_POST as $key => $value) {
    if ($key !== 'submit') {
        $message .= "【{$key}】 {$value}\n";
    }
}

$adminBody = <<<EOM
「ランディングページのお問い合わせ」からメールが届きました

＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
{$message}
＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

送信された日時：{$now}
送信者のIPアドレス：{$ip}
問い合わせのページURL：{$referer}
EOM;

$userBody = <<<EOM
{$name} 様

ハチマルライティングにお問い合わせいただき、ありがとうございます。

ご入力いただいた内容を確認の上、
担当より折り返しご連絡をさせていただきます。
今しばらくお待ちいただけますよう、お願いいたします。

送信内容は、以下の通りです。

＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
{$message}
＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

送信日時：{$now}
EOM;

// SMTP設定関数
function setupMailer($config)
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_pass'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    return $mail;
}

try {
    // 管理者宛メール送信
    $mail = setupMailer($config);
    $mail->setFrom($config['smtp_user'], 'ハチマルライティング');
    $mail->addAddress('egaku-career@pdc.co.jp');
    $mail->addReplyTo($email, $name);
    $mail->Subject = 'LPから新しい応募がありました';
    $mail->Body = $adminBody;
    $mail->send();

    // 自動返信
    $mail2 = setupMailer($config);
    $mail2->setFrom($config['smtp_user'], 'ハチマルライティング');
    $mail2->addAddress($email, $name);
    $mail2->Subject = 'ハチマルライティングにお問い合わせありがとうございます。';
    $mail2->Body = $userBody;
    $mail2->send();

    echo 'success'; // JS側で判定に使用
    exit;
} catch (Exception $e) {
    echo 'error';
    exit;
}