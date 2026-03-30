<?php
require_once __DIR__ . '/config.php';
$db = getDB();

$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

if (isPost()) {
    $action = getPost('action');

    if ($action === 'save_question') {
        $qid = (int) getPost('question_id');
        $text = getPost('question_text');
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        $correct = (int) getPost('correct_answer');

        if (!empty($text) && count(array_filter($answers, 'trim')) >= 2) {
            if ($qid > 0) {
                $stmt = $db->prepare('UPDATE questions SET question_text = ? WHERE id = ?');
                $stmt->bind_param('si', $text, $qid);
                $stmt->execute();
                $stmt->close();
                $db->query('DELETE FROM answers WHERE question_id = ' . (int) $qid);
            } else {
                $stmt = $db->prepare('INSERT INTO questions (question_text) VALUES (?)');
                $stmt->bind_param('s', $text);
                $stmt->execute();
                $qid = $stmt->insert_id;
                $stmt->close();
            }
            $stmt = $db->prepare('INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)');
            $i = 0;
            foreach ($answers as $at) {
                $at = trim($at);
                if (empty($at)) {
                    continue;
                }
                $ic = ($i === $correct) ? 1 : 0;
                $stmt->bind_param('isi', $qid, $at, $ic);
                $stmt->execute();
                $i++;
            }
            $stmt->close();
            flash('success', 'Pregunta guardada.');
        } else {
            flash('error', 'Pregunta y al menos 2 respuestas son requeridas.');
        }
        redirect('admin.php?section=questions');
    }

    if ($action === 'toggle') {
        $id = (int) getPost('id');
        $db->query('UPDATE questions SET is_active = NOT is_active WHERE id = ' . (int) $id);
        flash('success', 'Estado actualizado.');
        redirect('admin.php?section=questions');
    }

    if ($action === 'delete') {
        $id = (int) getPost('id');
        $db->query('DELETE FROM questions WHERE id = ' . (int) $id);
        flash('success', 'Pregunta eliminada.');
        redirect('admin.php?section=questions');
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $db->query("
        SELECT u.full_name, u.identity_number, u.email, u.phone,
               ts.score, ts.total_questions,
               CASE WHEN ts.is_winner = 1 THEN 'Ganador' ELSE 'No gano' END as resultado,
               u.created_at as fecha_registro, ts.completed_at as fecha_trivia
        FROM trivia_sessions ts
        JOIN users u ON u.id = ts.user_id
        ORDER BY ts.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="participantes_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo "Nombre,Identidad,Email,Telefono,Puntaje,Total,Resultado,Registro,Trivia\n";
    foreach ($rows as $r) {
        $line = array();
        foreach ($r as $v) {
            $v = ($v === null) ? '' : $v;
            $line[] = '"' . str_replace('"', '""', $v) . '"';
        }
        echo implode(',', $line) . "\n";
    }
    exit;
}

$stats = array();
$stats['total_users'] = $db->query('SELECT COUNT(*) as c FROM users')->fetch_assoc();
$stats['total_users'] = $stats['total_users']['c'];
$stats['total_sessions'] = $db->query('SELECT COUNT(*) as c FROM trivia_sessions WHERE completed_at IS NOT NULL')->fetch_assoc();
$stats['total_sessions'] = $stats['total_sessions']['c'];
$stats['total_winners'] = $db->query('SELECT COUNT(*) as c FROM trivia_sessions WHERE is_winner = 1')->fetch_assoc();
$stats['total_winners'] = $stats['total_winners']['c'];
$r = $db->query('SELECT COALESCE(ROUND(AVG(score),1),0) as a FROM trivia_sessions WHERE completed_at IS NOT NULL')->fetch_assoc();
$stats['avg_score'] = $r['a'];

$questions = $db->query("SELECT q.*, COUNT(a.id) as answer_count FROM questions q LEFT JOIN answers a ON a.question_id = q.id GROUP BY q.id ORDER BY q.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$participants = $db->query('SELECT u.full_name, u.phone, ts.score, ts.total_questions, ts.is_winner, ts.completed_at FROM trivia_sessions ts JOIN users u ON u.id = ts.user_id WHERE ts.completed_at IS NOT NULL ORDER BY ts.completed_at DESC LIMIT 50')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#13a538">
    <title><?php echo APP_NAME; ?> - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ldl_css_href(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page page-admin page-admin-<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
<?php ldl_decor_render('admin-' . $section); ?>
<div class="container admin">
    <div class="header">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars(ldl_asset('assets/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo APP_NAME; ?>" class="logo logo--trazo logo--sm" width="160">
        </div>
        <h1><?php echo APP_NAME; ?> — Admin</h1>
    </div>
    <?php renderFlash(); ?>

    <div class="admin-nav">
        <a href="admin.php?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="admin.php?section=questions" class="<?php echo $section === 'questions' ? 'active' : ''; ?>">Preguntas</a>
        <a href="admin.php?section=participants" class="<?php echo $section === 'participants' ? 'active' : ''; ?>">Participantes</a>
        <a href="index.php" style="margin-left:auto;">&larr; Ir al sitio</a>
    </div>

    <?php if ($section === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_users']; ?></div><div class="stat-label">Usuarios</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_sessions']; ?></div><div class="stat-label">Trivias</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_winners']; ?></div><div class="stat-label">Ganadores</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['avg_score']; ?></div><div class="stat-label">Puntaje Prom.</div></div>
        </div>
        <div class="card">
            <h2>Ultimos Participantes</h2>
            <?php if (empty($participants)): ?>
                <p style="color:#999;">Aun no hay participantes.</p>
            <?php else: ?>
                <div class="table-wrap"><table>
                    <thead><tr><th>Nombre</th><th>Telefono</th><th>Puntaje</th><th>Resultado</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php foreach ($participants as $u): ?>
                        <tr>
                            <td><?php echo sanitize($u['full_name']); ?></td>
                            <td><?php echo sanitize($u['phone']); ?></td>
                            <td><?php echo $u['score']; ?>/<?php echo $u['total_questions']; ?></td>
                            <td><span class="badge <?php echo $u['is_winner'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $u['is_winner'] ? 'Ganador' : 'No gano'; ?></span></td>
                            <td><?php echo $u['completed_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($section === 'questions'): ?>
        <div class="card edit-form" id="questionForm">
            <h3 id="formTitle">Nueva Pregunta</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_question">
                <input type="hidden" name="question_id" id="questionId" value="0">
                <div class="form-group">
                    <label>Texto de la pregunta <span class="required">*</span></label>
                    <input type="text" name="question_text" id="questionText" placeholder="Ej: Cual es la capital de Honduras?" required>
                </div>
                <div id="answersContainer">
                    <label style="font-weight:600;font-size:0.9rem;margin-bottom:8px;display:block;">Respuestas (marca la correcta)</label>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="answer-row">
                            <input type="text" name="answers[]" placeholder="Respuesta <?php echo $i + 1; ?>">
                            <label><input type="radio" name="correct_answer" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>> Correcta</label>
                        </div>
                    <?php endfor; ?>
                </div>
                <div style="margin-top:16px;display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary btn-small">Guardar</button>
                    <button type="button" class="btn btn-secondary btn-small" onclick="resetForm()">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="card">
            <h2>Preguntas (<?php echo count($questions); ?>)</h2>
            <?php if (empty($questions)): ?>
                <p style="color:#999;">No hay preguntas.</p>
            <?php else: ?>
                <div class="table-wrap"><table>
                    <thead><tr><th>#</th><th>Pregunta</th><th>Resp.</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td><?php echo sanitize($q['question_text']); ?></td>
                            <td><?php echo $q['answer_count']; ?></td>
                            <td><span class="badge <?php echo $q['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $q['is_active'] ? 'Activa' : 'Inactiva'; ?></span></td>
                            <td><div class="actions">
                                <button class="btn btn-secondary btn-small" onclick="editQ(<?php echo $q['id']; ?>,'<?php echo addslashes($q['question_text']); ?>')">Editar</button>
                                <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo $q['id']; ?>"><button class="btn btn-secondary btn-small"><?php echo $q['is_active'] ? 'Desactivar' : 'Activar'; ?></button></form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Eliminar?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $q['id']; ?>"><button class="btn btn-danger btn-small">Eliminar</button></form>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
        <script>
        function editQ(id, text) {
            document.getElementById('formTitle').textContent = 'Editar Pregunta #' + id;
            document.getElementById('questionId').value = id;
            document.getElementById('questionText').value = text;
            document.getElementById('questionForm').scrollIntoView({behavior:'smooth'});
        }
        function resetForm() {
            document.getElementById('formTitle').textContent = 'Nueva Pregunta';
            document.getElementById('questionId').value = '0';
            document.getElementById('questionText').value = '';
            var inputs = document.querySelectorAll('#answersContainer input[type=text]');
            for (var i = 0; i < inputs.length; i++) inputs[i].value = '';
        }
        </script>
    <?php endif; ?>

    <?php if ($section === 'participants'): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h2 style="border-bottom:none;margin-bottom:0;padding-bottom:0;">Participantes</h2>
                <a href="admin.php?export=csv" class="btn btn-primary btn-small" style="width:auto;">&#11015; Exportar CSV</a>
            </div>
            <?php if (empty($participants)): ?>
                <p style="color:#999;margin-top:16px;">Aun no hay participantes.</p>
            <?php else: ?>
                <div class="table-wrap" style="margin-top:16px;"><table>
                    <thead><tr><th>Nombre</th><th>Telefono</th><th>Puntaje</th><th>Resultado</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php foreach ($participants as $u): ?>
                        <tr>
                            <td><?php echo sanitize($u['full_name']); ?></td>
                            <td><?php echo sanitize($u['phone']); ?></td>
                            <td><?php echo $u['score']; ?>/<?php echo $u['total_questions']; ?></td>
                            <td><span class="badge <?php echo $u['is_winner'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $u['is_winner'] ? 'Ganador' : 'No gano'; ?></span></td>
                            <td><?php echo $u['completed_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
