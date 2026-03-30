<?php
require_once __DIR__ . '/config.php';

$errors = array();
$old = array('full_name' => '', 'identity_number' => '', 'email' => '', 'phone' => '');

if (isPost()) {
    $full_name = getPost('full_name');
    $identity_number = getPost('identity_number');
    $email = getPost('email');
    $phone = getPost('phone');
    $old = compact('full_name', 'identity_number', 'email', 'phone');

    if (empty($full_name)) {
        $errors['full_name'] = 'El nombre completo es requerido.';
    }
    if (empty($identity_number)) {
        $errors['identity_number'] = 'El número de identidad es requerido.';
    }
    if (empty($phone)) {
        $errors['phone'] = 'El número de teléfono es requerido.';
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Correo no válido.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO users (full_name, identity_number, email, phone) VALUES (?, ?, ?, ?)');
        $emailVal = !empty($email) ? $email : null;
        $stmt->bind_param('ssss', $full_name, $identity_number, $emailVal, $phone);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare('INSERT INTO trivia_sessions (user_id, total_questions) VALUES (?, ?)');
        $total = TRIVIA_QUESTIONS_COUNT;
        $stmt->bind_param('ii', $userId, $total);
        $stmt->execute();
        $sessionId = $stmt->insert_id;
        $stmt->close();

        $result = $db->query('SELECT id FROM questions WHERE is_active = 1 ORDER BY RAND() LIMIT ' . TRIVIA_QUESTIONS_COUNT);
        $questionIds = array();
        while ($row = $result->fetch_assoc()) {
            $questionIds[] = (int) $row['id'];
        }

        if (count($questionIds) < TRIVIA_QUESTIONS_COUNT) {
            flash('error', 'No hay suficientes preguntas activas. Contacte al administrador.');
            redirect('index.php');
        }

        $_SESSION['trivia'] = array(
            'session_id' => $sessionId,
            'user_id' => $userId,
            'user_name' => $full_name,
            'question_ids' => $questionIds,
            'current_index' => 0,
            'score' => 0,
        );
        redirect('trivia.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> - Registro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ldl_css_href(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page page-index">
<?php ldl_decor_render('index'); ?>
<div class="container">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo" width="200">
        </div>
        <p class="header-slogan">¡Soñá y Ganá!</p>
    </div>
    <?php renderFlash(); ?>
    <div class="card">
        <h2>Registrate para participar</h2>
        <form method="POST">
            <div class="form-group">
                <label>Nombre Completo <span class="required">*</span></label>
                <input type="text" name="full_name" value="<?php echo sanitize($old['full_name']); ?>"
                       placeholder="Ej: Juan Alberto Pérez" class="<?php echo isset($errors['full_name']) ? 'error' : ''; ?>">
                <?php if (isset($errors['full_name'])): ?><div class="error-text"><?php echo $errors['full_name']; ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Número de Identidad <span class="required">*</span></label>
                <input type="text" name="identity_number" value="<?php echo sanitize($old['identity_number']); ?>"
                       placeholder="Ej: 0801-1990-12345" class="<?php echo isset($errors['identity_number']) ? 'error' : ''; ?>">
                <?php if (isset($errors['identity_number'])): ?><div class="error-text"><?php echo $errors['identity_number']; ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Correo Electrónico <span class="optional">(opcional)</span></label>
                <input type="email" name="email" value="<?php echo sanitize($old['email']); ?>"
                       placeholder="Ej: correo@ejemplo.com" class="<?php echo isset($errors['email']) ? 'error' : ''; ?>">
                <?php if (isset($errors['email'])): ?><div class="error-text"><?php echo $errors['email']; ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Número de Teléfono <span class="required">*</span></label>
                <input type="tel" name="phone" value="<?php echo sanitize($old['phone']); ?>"
                       placeholder="Ej: 9999-9999" class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>">
                <?php if (isset($errors['phone'])): ?><div class="error-text"><?php echo $errors['phone']; ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Participar en la Trivia</button>
        </form>
    </div>
</div>
</body>
</html>
