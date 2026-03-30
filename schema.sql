-- =============================================================================
-- La Diaria Loto — Trivia (MySQL 5.7+ / MariaDB 10+)
-- Codificación: utf8mb4 (emojis y tildes)
--
-- Uso (elegí la base antes: USE nombre_bd; o -D nombre_bd en CLI):
--   mysql -u USUARIO -p -D NOMBRE_BD < schema.sql
--
-- phpMyAdmin: seleccioná la base e importá este archivo.
--
-- Tras importar: en config.php ajustá TRIVIA_QUESTIONS_COUNT (1–15; máximo el total
-- de preguntas activas; el sistema elige al azar).
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    identity_number VARCHAR(64) NOT NULL,
    email VARCHAR(255) NULL DEFAULT NULL,
    phone VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_identity (identity_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_text TEXT NOT NULL,
    explanation TEXT NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS answers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id INT UNSIGNED NOT NULL,
    answer_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_question (question_id),
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    total_questions INT UNSIGNED NOT NULL,
    score INT UNSIGNED NULL DEFAULT NULL,
    is_winner TINYINT(1) NULL DEFAULT NULL,
    completed_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_completed (completed_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_answers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_id INT UNSIGNED NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_session (session_id),
    KEY idx_question (question_id),
    CONSTRAINT fk_ta_session FOREIGN KEY (session_id) REFERENCES trivia_sessions (id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_question FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_answer FOREIGN KEY (answer_id) REFERENCES answers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migración: tablas antiguas sin columna `explanation`
SET @dbname = DATABASE();
SET @tablename = 'questions';
SET @columnname = 'explanation';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE questions ADD COLUMN explanation TEXT NULL DEFAULT NULL AFTER question_text'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM trivia_answers;
DELETE FROM answers;
DELETE FROM questions;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Preguntas + respuestas (orden A–D; is_correct = 1 en la opción válida)
-- -----------------------------------------------------------------------------

INSERT INTO questions (id, question_text, explanation, is_active) VALUES
(1,
'¡Anoche soñaste con un PERRO que te perseguía por la calle! ¿Qué número le corresponde en la Guía de los Sueños de La Diaria?',
'¡El perro ladrador en tus sueños es el 11! ¿Lo hubieras jugado?',
1),
(2,
'Si jugás L30 en La Diaria y tu número sale favorecido, ¿cuánto ganás?',
'¡L30 × 60 = L1,800! La Diaria siempre paga 60 veces lo invertido 🤑',
1),
(3,
'¿Cuál es el slogan oficial de La Diaria Loto?',
'¡Todo empieza con un sueño! Por eso existe la Guía de los Sueños 😴✨',
1),
(4,
'Multi-X te permite multiplicar tu premio. ¿Cuál es la multiplicación MÁS GRANDE que podés ganar?',
'¡Con Multi-X podés ganar 5 veces más! Si tu número de La Diaria sale con 5x y jugaste L10... ¡ganás L3,000! 🔥',
1),
(5,
'Más 1 es especial porque además del número de La Diaria, elegís algo extra. ¿Qué es ese algo?',
'¡Más 1 = tu número de Diaria + un dígito extra! Por eso se llama MÁS 1 😎',
1),
(6,
'¡Soñaste que bailabas con un PÁJARO gigante en medio de la calle! ¿Qué número jugás en La Diaria?',
'¡Pájaro = número 21! 🐦 ¿A quién se le aparecen pájaros gigantes en sueños?',
1),
(7,
'Revés tiene un superpoder único: si elegís el número 34, ¿con cuál otro número TAMBIÉN estás jugando automáticamente sin pagar más?',
'¡El 34 al revés es el 43! Con Revés comprás uno y jugás DOS. ¡Como un 2x1 de la suerte! 🛒',
1),
(8,
'¿Cómo funciona Multi-X? ¿Cuánto tenés que invertir en él comparado con La Diaria?',
'¡Invertís igual en ambos! Si ponés L20 en Diaria, ponés L20 en Multi-X. Simple y poderoso 💪',
1),
(9,
'Tu amigo iba a jugar el 19 en La Diaria. Le agregó Revés. ¿Qué número extra está jugando ahora sin darse cuenta?',
'¡El 19 al revés es el 91! Tu amigo ahora juega DOS números casi al precio de uno 😄',
1),
(10,
'Jugás Más 1 con L5 en Diaria y L1 extra. Tu número de Diaria sale ganador, pero solo acertás los primeros 2 dígitos, no el tercero. ¿Cuánto ganás?',
'¡Si acertás los 2 primeros dígitos en Más 1, ganás 60x tu inversión de Diaria! L5 × 60 = L300. 😄 ¡No es L800 pero tampoco es nada!',
1),
(11,
'Tu mamá quiere jugar La Diaria por primera vez con L20. Si su número sale favorecido, ¿cuánto gana?',
'¡L20 × 60 = L1,200! ¡Tu mamá merece ganar! 💚',
1),
(12,
'Soñaste que eras piloto y manejabas un CARRO de lujo a toda velocidad por la autopista. ¿Qué número arrancás a jugar?',
'¡Carro = número 94! 🚗 Si soñaste manejando, ¡ese es tu número en La Diaria!',
1),
(13,
'¡Soñaste una historia COMPLETA! Primero apareció un BOLO cantando, luego llegó la POLICÍA, ¡y al final resultó que era un MUERTO! ¿Cuáles son los 3 números de ese sueño loco?',
'¡30, 51 y 03! 🎯 ¿Ven cómo cada sueño tiene su número? ¡Eso es la magia de la Guía de los Sueños de La Diaria!',
1),
(14,
'Jugás Revés con L7. Tu número exacto sale favorecido. ¿Cuánto ganás de premio?',
'¡Revés paga L400 cuando acertás el número exacto! Diferente a La Diaria que paga 60x. ¡Cada jugada tiene su propio premio!',
1),
(15,
'¡Anoche soñaste con ORO puro (número 70)! Te despertás emocionado, agarrás L500 y jugás La Diaria con Multi-X agregado. Tu número 70 sale FAVORECIDO y encima te toca la multiplicación 5x de Multi-X. ¿Cuánto dinero total te ganás con ese sueño?',
NULL,
1);

INSERT INTO answers (question_id, answer_text, is_correct) VALUES
-- 1
(1, 'El 50', 0),
(1, 'El 90', 0),
(1, 'El 11', 1),
(1, 'El 30', 0),
-- 2
(2, 'L50', 0),
(2, 'L100', 0),
(2, 'L1,000', 0),
(2, 'L1,800', 1),
-- 3
(3, 'Jugá y Multiplicá', 0),
(3, 'Tu número, tu suerte', 0),
(3, '¡Soñá y Ganá!', 1),
(3, 'Elegí y Ganá', 0),
-- 4
(4, '2x', 0),
(4, '3x', 0),
(4, '4x', 0),
(4, '5x', 1),
-- 5
(5, 'Un color', 0),
(5, 'Un símbolo de la Guía de los Sueños', 0),
(5, 'Un dígito adicional del 0 al 9', 1),
(5, 'Otro número de dos dígitos', 0),
-- 6
(6, 'El 72', 0),
(6, 'El 94', 0),
(6, 'El 50', 0),
(6, 'El 21', 1),
-- 7
(7, 'El 33', 0),
(7, 'El 44', 0),
(7, 'El 43', 1),
(7, 'El 30', 0),
-- 8
(8, 'El doble de lo que invertiste en Diaria', 0),
(8, 'Una cantidad fija de L10 siempre', 0),
(8, 'La mitad de lo que invertiste en Diaria', 0),
(8, 'La misma cantidad que invertiste en Diaria', 1),
-- 9
(9, 'El 10', 0),
(9, 'El 90', 0),
(9, 'El 99', 0),
(9, 'El 91', 1),
-- 10
(10, 'L800 (premio completo)', 0),
(10, 'L0 — necesitas los 3 dígitos', 0),
(10, 'L500', 0),
(10, 'L300 (60 veces los L5 de Diaria)', 1),
-- 11
(11, 'L600', 0),
(11, 'L800', 0),
(11, 'L1,200', 1),
(11, 'L2,000', 0),
-- 12
(12, 'El 72', 0),
(12, 'El 73', 0),
(12, 'El 29', 0),
(12, 'El 94', 1),
-- 13
(13, '30, 69 y 08', 0),
(13, '43, 72 y 89', 0),
(13, '51, 68 y 96', 0),
(13, '30, 51 y 03', 1),
-- 14
(14, 'L300', 0),
(14, 'L600', 0),
(14, 'L400', 1),
(14, 'L800', 0),
-- 15
(15, 'L30,000', 0),
(15, 'L60,000', 0),
(15, 'L90,000', 0),
(15, 'L150,000', 1);

-- Sincronizar autoincrementos
ALTER TABLE questions AUTO_INCREMENT = 16;
ALTER TABLE answers AUTO_INCREMENT = 61;
