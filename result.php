<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['trivia'])) {
    redirect('index.php');
}

$trivia = $_SESSION['trivia'];
$total = count($trivia['question_ids']);
$isWinner = $trivia['score'] === $total;
$eliminated = array_key_exists('loss_hint', $trivia);
$lossHint = $eliminated ? trim((string) $trivia['loss_hint']) : '';
unset($_SESSION['trivia']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> - Resultado</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ldl_css_href(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page page-result">
<?php ldl_decor_render('result'); ?>
<div class="container">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo" width="200">
        </div>
        <p class="header-slogan">¡Soñá y Ganá!</p>
    </div>
    <div class="card <?php echo $isWinner ? 'result-winner' : 'result-loser'; ?>">
        <div class="result-icon"><?php echo $isWinner ? '&#127881;&#127942;&#127881;' : '&#128532;'; ?></div>
        <div class="result-score"><?php echo $trivia['score']; ?> / <?php echo $total; ?></div>
        <div class="result-message">
            <?php if ($isWinner): ?>
                <strong>&iexcl;Felicidades, <?php echo sanitize($trivia['user_name']); ?>!</strong><br>
                &iexcl;Acertaste todas las preguntas! &#127882;
            <?php elseif ($eliminated): ?>
                <strong><?php echo sanitize($trivia['user_name']); ?></strong>, la partida termin&oacute;.<br>
                En esta trivia <strong>un solo error elimina</strong>: no pod&eacute;s fallar ninguna pregunta.
                <?php if ($lossHint !== ''): ?>
                    <div class="result-hint"><?php echo nl2br(sanitize($lossHint)); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <strong><?php echo sanitize($trivia['user_name']); ?></strong>, no lograste acertar todas.<br>
                Obtuviste <?php echo $trivia['score']; ?> de <?php echo $total; ?> respuestas correctas.
            <?php endif; ?>
        </div>
        <a href="index.php" class="btn btn-primary">Volver al inicio</a>
    </div>
</div>
</body>
</html>
