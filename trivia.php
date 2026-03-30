<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['trivia'])) {
    redirect('index.php');
}

$trivia = &$_SESSION['trivia'];

if (isPost() && getPost('continue_next') === '1') {
    if (empty($trivia['feedback']) || empty($trivia['feedback']['is_correct']) || !empty($trivia['feedback']['game_over'])) {
        redirect('trivia.php');
    }
    unset($trivia['feedback']);
    $trivia['current_index']++;
    if ($trivia['current_index'] >= count($trivia['question_ids'])) {
        $totalQ = count($trivia['question_ids']);
        $isWinner = ($trivia['score'] === $totalQ) ? 1 : 0;
        $db = getDB();
        $stmt = $db->prepare('UPDATE trivia_sessions SET score = ?, is_winner = ?, completed_at = NOW() WHERE id = ?');
        $stmt->bind_param('iii', $trivia['score'], $isWinner, $trivia['session_id']);
        $stmt->execute();
        $stmt->close();
        redirect('result.php');
    }
    redirect('trivia.php');
}

if (isPost() && getPost('answer_id') !== '' && getPost('answer_id') !== null) {
    $answerId = (int) getPost('answer_id', '0');
    if ($answerId === 0) {
        redirect('trivia.php');
    }
    if (!empty($trivia['feedback'])) {
        redirect('trivia.php');
    }

    $questionId = $trivia['question_ids'][$trivia['current_index']];
    $db = getDB();

    $stmt = $db->prepare('SELECT is_correct FROM answers WHERE id = ? AND question_id = ?');
    $stmt->bind_param('ii', $answerId, $questionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isCorrect = ($row && (int) $row['is_correct'] === 1) ? 1 : 0;

    $stmt = $db->prepare('INSERT INTO trivia_answers (session_id, question_id, answer_id, is_correct) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiii', $trivia['session_id'], $questionId, $answerId, $isCorrect);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare('SELECT question_text, explanation FROM questions WHERE id = ?');
    $stmt->bind_param('i', $questionId);
    $stmt->execute();
    $qrow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare('SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $questionId);
    $stmt->execute();
    $answersRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $explanationRaw = isset($qrow['explanation']) ? trim((string) $qrow['explanation']) : '';

    if ($isCorrect === 1) {
        $trivia['score']++;
        $trivia['feedback'] = array(
            'game_over' => false,
            'is_correct' => true,
            'explanation' => $explanationRaw !== '' ? $qrow['explanation'] : null,
            'question_text' => $qrow['question_text'],
            'answers' => $answersRows,
            'selected_answer_id' => $answerId,
            'question_id' => $questionId,
        );
    } else {
        $stmt = $db->prepare('UPDATE trivia_sessions SET score = ?, is_winner = 0, completed_at = NOW() WHERE id = ?');
        $stmt->bind_param('ii', $trivia['score'], $trivia['session_id']);
        $stmt->execute();
        $stmt->close();

        $trivia['loss_hint'] = $explanationRaw;
        $trivia['feedback'] = array(
            'game_over' => true,
            'is_correct' => false,
            'explanation' => $explanationRaw !== '' ? $qrow['explanation'] : null,
            'question_text' => $qrow['question_text'],
            'answers' => $answersRows,
            'selected_answer_id' => $answerId,
            'question_id' => $questionId,
        );
    }
    redirect('trivia.php');
}

if (!empty($trivia['feedback'])) {
    $fb = $trivia['feedback'];
    $idx = $trivia['current_index'];
    $total = count($trivia['question_ids']);
    $progress = (($idx + 1) / $total) * 100;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> - ¿Acertaste?</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ldl_css_href(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page page-trivia page-trivia-feedback <?php echo !empty($fb['game_over']) ? 'page-trivia-gameover' : ''; ?>">
<?php ldl_decor_render('trivia-feedback'); ?>
<div class="container">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo" width="200">
        </div>
        <p class="header-slogan">¡Soñá y Ganá!</p>
    </div>
    <div class="card card--feedback <?php echo !empty($fb['game_over']) ? 'card--game-over' : ''; ?>">
        <div class="question-counter">Pregunta <?php echo $idx + 1; ?> de <?php echo $total; ?></div>
        <div class="progress-bar"><div class="fill" style="width: <?php echo $progress; ?>%"></div></div>
        <div class="question-text"><?php echo sanitize($fb['question_text']); ?></div>

        <div class="feedback-banner <?php echo $fb['is_correct'] ? 'feedback-banner--ok' : 'feedback-banner--bad'; ?>">
            <?php if (!empty($fb['game_over'])): ?>
                <span class="feedback-banner__icon" aria-hidden="true">✗</span>
                <div class="feedback-banner__stack">
                    <span class="feedback-banner__title">Perdiste</span>
                    <span class="feedback-banner__subtitle">Un solo error termina la partida. No podés seguir.</span>
                </div>
            <?php elseif ($fb['is_correct']): ?>
                <span class="feedback-banner__icon" aria-hidden="true">✓</span>
                <span class="feedback-banner__title">¡Correcto!</span>
            <?php else: ?>
                <span class="feedback-banner__icon" aria-hidden="true">✗</span>
                <span class="feedback-banner__title">Incorrecto</span>
            <?php endif; ?>
        </div>

        <ul class="feedback-answers" role="list">
            <?php foreach ($fb['answers'] as $a): ?>
                <?php
                $cid = (int) $a['id'];
                $isSel = ($cid === (int) $fb['selected_answer_id']);
                $isOk = ((int) $a['is_correct'] === 1);
                $cls = 'feedback-answer';
                if ($isOk) {
                    $cls .= ' feedback-answer--correct';
                }
                if ($isSel && !$isOk) {
                    $cls .= ' feedback-answer--wrong';
                }
                if ($isSel && $isOk) {
                    $cls .= ' feedback-answer--picked feedback-answer--picked-good';
                }
                ?>
                <li class="<?php echo $cls; ?>">
                    <?php if ($isOk): ?><span class="feedback-answer__badge">Correcta</span><?php endif; ?>
                    <?php if ($isSel && $isOk): ?><span class="feedback-answer__badge feedback-answer__badge--good">Tu respuesta</span><?php endif; ?>
                    <?php if ($isSel && !$isOk): ?><span class="feedback-answer__badge feedback-answer__badge--bad">Tu respuesta</span><?php endif; ?>
                    <span class="feedback-answer__text"><?php echo sanitize($a['answer_text']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!$fb['is_correct']): ?>
            <div class="feedback-extra">
                <p class="feedback-extra__label"><?php echo !empty($fb['game_over']) ? '💬 Por qué no era' : '💬 Tip'; ?></p>
                <p class="feedback-extra__text">
                    <?php
                    $ex = isset($fb['explanation']) ? trim((string) $fb['explanation']) : '';
                    echo $ex !== '' ? nl2br(sanitize($ex)) : 'La opción correcta está marcada arriba.';
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($fb['game_over'])): ?>
            <a href="result.php" class="btn btn-primary feedback-continue-form">Ver resultado</a>
        <?php else: ?>
            <form method="POST" class="feedback-continue-form">
                <input type="hidden" name="continue_next" value="1">
                <button type="submit" class="btn btn-primary"><?php echo ($idx + 1) >= $total ? 'Ver resultado' : 'Siguiente pregunta'; ?></button>
            </form>
        <?php endif; ?>
    </div>
    <p class="playing-as">Jugando como: <?php echo sanitize($trivia['user_name']); ?></p>
</div>
</body>
</html>
    <?php
    exit;
}

$idx = $trivia['current_index'];
if ($idx >= count($trivia['question_ids'])) {
    redirect('result.php');
}

$db = getDB();
$qid = $trivia['question_ids'][$idx];

$stmt = $db->prepare('SELECT id, question_text FROM questions WHERE id = ?');
$stmt->bind_param('i', $qid);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare('SELECT id, answer_text FROM answers WHERE question_id = ? ORDER BY RAND()');
$stmt->bind_param('i', $qid);
$stmt->execute();
$answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = count($trivia['question_ids']);
$progress = ($idx / $total) * 100;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> - Trivia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ldl_css_href(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page page-trivia">
<?php ldl_decor_render('trivia'); ?>
<div class="container">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo" width="200">
        </div>
        <p class="header-slogan">¡Soñá y Ganá!</p>
    </div>
    <div class="card">
        <div class="question-counter">Pregunta <?php echo $idx + 1; ?> de <?php echo $total; ?></div>
        <div class="progress-bar"><div class="fill" style="width: <?php echo $progress; ?>%"></div></div>
        <div class="question-text"><?php echo sanitize($question['question_text']); ?></div>
        <form method="POST">
            <div class="ux-stagger">
            <?php foreach ($answers as $a): ?>
                <button type="submit" name="answer_id" value="<?php echo $a['id']; ?>" class="answer-option">
                    <?php echo sanitize($a['answer_text']); ?>
                </button>
            <?php endforeach; ?>
            </div>
        </form>
    </div>
    <p class="playing-as">Jugando como: <?php echo sanitize($trivia['user_name']); ?></p>
</div>
</body>
</html>
