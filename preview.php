<?php
/**
 * Vista previa de diseño — sin base de datos.
 * Activación: touch dev.enable o LDL_DEV=1
 */
require_once __DIR__ . '/config.php';

if (!LDL_PREVIEW_ALLOWED) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Preview desactivada. Creá el archivo dev.enable en la raíz del proyecto o ejecutá con LDL_DEV=1.\n";
    exit;
}

$screens = array('index', 'trivia', 'feedback-ok', 'feedback-bad', 'result-win', 'result-lose');
$screen = isset($_GET['screen']) ? $_GET['screen'] : 'index';
if (!in_array($screen, $screens, true)) {
    $screen = 'index';
}

$q = function ($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> — Preview (<?php echo $screen; ?>)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $q(ldl_css_href()); ?>">
</head>
<body class="page page-preview page-<?php echo $q($screen); ?>">
<?php ldl_decor_render('preview-' . $screen); ?>
<div class="container">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo" width="200">
        </div>
        <p class="header-slogan">¡Soñá y Ganá!</p>
    </div>

<?php if ($screen === 'index'): ?>
    <div class="card">
        <h2>Registrate para participar</h2>
        <p style="color:#888;font-size:.85rem;margin:-8px 0 16px;">(preview — formulario no envía)</p>
        <div class="form-group">
            <label>Nombre Completo <span class="required">*</span></label>
            <input type="text" readonly value="Nombre de ejemplo" placeholder="Ej: Juan Alberto Pérez">
        </div>
        <div class="form-group">
            <label>Número de Identidad <span class="required">*</span></label>
            <input type="text" readonly value="0801-1990-12345">
        </div>
        <div class="form-group">
            <label>Correo Electrónico <span class="optional">(opcional)</span></label>
            <input type="email" readonly value="correo@ejemplo.com">
        </div>
        <div class="form-group">
            <label>Número de Teléfono <span class="required">*</span></label>
            <input type="tel" readonly value="9999-9999">
        </div>
        <a href="<?php echo $q('preview.php?screen=trivia'); ?>" class="btn btn-primary">Ir a trivia (preview)</a>
    </div>

<?php elseif ($screen === 'trivia'): ?>
    <div class="card">
        <div class="question-counter">Pregunta 2 de 6</div>
        <div class="progress-bar"><div class="fill" style="width:16.666%"></div></div>
        <div class="question-text">Si jugás L30 en La Diaria y tu número sale favorecido, ¿cuánto ganás?</div>
        <div class="ux-stagger">
            <a href="<?php echo $q('preview.php?screen=feedback-ok'); ?>" class="answer-option">L50</a>
            <a href="<?php echo $q('preview.php?screen=feedback-bad'); ?>" class="answer-option">L100</a>
            <a href="<?php echo $q('preview.php?screen=feedback-ok'); ?>" class="answer-option">L1,800</a>
            <a href="<?php echo $q('preview.php?screen=feedback-bad'); ?>" class="answer-option">L1,000</a>
        </div>
    </div>
    <p class="playing-as">Jugando como: Participante Demo</p>

<?php elseif ($screen === 'feedback-ok'): ?>
    <div class="card card--feedback">
        <div class="question-counter">Pregunta 2 de 6</div>
        <div class="progress-bar"><div class="fill" style="width:33.333%"></div></div>
        <div class="question-text">Si jugás L30 en La Diaria y tu número sale favorecido, ¿cuánto ganás?</div>
        <div class="feedback-banner feedback-banner--ok">
            <span class="feedback-banner__icon" aria-hidden="true">✓</span>
            <span class="feedback-banner__title">¡Correcto!</span>
        </div>
        <ul class="feedback-answers" role="list">
            <li class="feedback-answer feedback-answer--correct feedback-answer--picked feedback-answer--picked-good">
                <span class="feedback-answer__badge">Correcta</span>
                <span class="feedback-answer__badge feedback-answer__badge--good">Tu respuesta</span>
                <span class="feedback-answer__text">L1,800</span>
            </li>
            <li class="feedback-answer"><span class="feedback-answer__text">L50</span></li>
            <li class="feedback-answer"><span class="feedback-answer__text">L100</span></li>
            <li class="feedback-answer"><span class="feedback-answer__text">L1,000</span></li>
        </ul>
        <a href="<?php echo $q('preview.php?screen=trivia'); ?>" class="btn btn-primary feedback-continue-form">Siguiente pregunta (preview)</a>
    </div>
    <p class="playing-as">Jugando como: Participante Demo</p>

<?php elseif ($screen === 'feedback-bad'): ?>
    <div class="card card--feedback card--game-over">
        <div class="question-counter">Pregunta 3 de 6</div>
        <div class="progress-bar"><div class="fill" style="width:33.333%"></div></div>
        <div class="question-text">¿Cuál es el slogan oficial de La Diaria Loto?</div>
        <div class="feedback-banner feedback-banner--bad">
            <span class="feedback-banner__icon" aria-hidden="true">✗</span>
            <div class="feedback-banner__stack">
                <span class="feedback-banner__title">Perdiste</span>
                <span class="feedback-banner__subtitle">Un solo error termina la partida. No podés seguir.</span>
            </div>
        </div>
        <ul class="feedback-answers" role="list">
            <li class="feedback-answer"><span class="feedback-answer__text">Jugá y Multiplicá</span></li>
            <li class="feedback-answer feedback-answer--wrong">
                <span class="feedback-answer__badge feedback-answer__badge--bad">Tu respuesta</span>
                <span class="feedback-answer__text">Tu número, tu suerte</span>
            </li>
            <li class="feedback-answer feedback-answer--correct">
                <span class="feedback-answer__badge">Correcta</span>
                <span class="feedback-answer__text">¡Soñá y Ganá!</span>
            </li>
            <li class="feedback-answer"><span class="feedback-answer__text">Elegí y Ganá</span></li>
        </ul>
        <div class="feedback-extra">
            <p class="feedback-extra__label">💬 Por qué no era</p>
            <p class="feedback-extra__text">¡Todo empieza con un sueño! Por eso existe la Guía de los Sueños 😴✨</p>
        </div>
        <a href="<?php echo $q('preview.php?screen=result-lose'); ?>" class="btn btn-primary feedback-continue-form">Ver resultado (preview)</a>
    </div>
    <p class="playing-as">Jugando como: Participante Demo</p>

<?php elseif ($screen === 'result-win'): ?>
    <div class="card result-winner">
        <div class="result-icon">&#127881;&#127942;&#127881;</div>
        <div class="result-score">6 / 6</div>
        <div class="result-message">
            <strong>¡Felicidades, Participante Demo!</strong><br>
            ¡Acertaste todas las preguntas! &#127882;
        </div>
        <a href="<?php echo $q('preview.php?screen=index'); ?>" class="btn btn-primary">Volver al inicio</a>
    </div>

<?php elseif ($screen === 'result-lose'): ?>
    <div class="card result-loser">
        <div class="result-icon">&#128532;</div>
        <div class="result-score">2 / 6</div>
        <div class="result-message">
            <strong>Participante Demo</strong>, la partida terminó.<br>
            En esta trivia <strong>un solo error elimina</strong>: no podés fallar ninguna pregunta.
            <div class="result-hint">¡Todo empieza con un sueño! Por eso existe la Guía de los Sueños 😴✨</div>
        </div>
        <a href="<?php echo $q('preview.php?screen=index'); ?>" class="btn btn-primary">Volver al inicio</a>
    </div>
<?php endif; ?>

</div>

<nav class="preview-nav" aria-label="Vistas de preview">
    <span>Demo</span>
    <a href="<?php echo $q('preview.php?screen=index'); ?>" <?php echo $screen === 'index' ? 'aria-current="page"' : ''; ?>>Registro</a>
    <a href="<?php echo $q('preview.php?screen=trivia'); ?>" <?php echo $screen === 'trivia' ? 'aria-current="page"' : ''; ?>>Trivia</a>
    <a href="<?php echo $q('preview.php?screen=feedback-ok'); ?>" <?php echo $screen === 'feedback-ok' ? 'aria-current="page"' : ''; ?>>OK</a>
    <a href="<?php echo $q('preview.php?screen=feedback-bad'); ?>" <?php echo $screen === 'feedback-bad' ? 'aria-current="page"' : ''; ?>>Perdió</a>
    <a href="<?php echo $q('preview.php?screen=result-win'); ?>" <?php echo $screen === 'result-win' ? 'aria-current="page"' : ''; ?>>Ganó</a>
    <a href="<?php echo $q('preview.php?screen=result-lose'); ?>" <?php echo $screen === 'result-lose' ? 'aria-current="page"' : ''; ?>>Perdió final</a>
</nav>
</body>
</html>
