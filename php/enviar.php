<?php
/**
 * BRICEÑO CANALES — Manejador de formulario de consulta
 * Recibe los datos del formulario, valida, procesa adjunto y envía el correo.
 *
 * Configuración necesaria:
 *   1. Ajusta $destinatario con el correo del estudio.
 *   2. Si tu hosting no entrega bien con mail(), instala PHPMailer vía Composer
 *      y descomenta el bloque PHPMailer al final (más confiable para adjuntos).
 */

declare(strict_types=1);

/* ── CORS / headers ───────────────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8');

/* Solo POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

/* ── Configuración ────────────────────────────────────────────── */
$destinatario = 'contacto@bricenocanales.com';
$uploadDir    = __DIR__ . '/../uploads/';

/* ── Honeypot ────────────────────────────────────────────────── */
$honeyPot = trim($_POST['website'] ?? '');
if ($honeyPot !== '') {
    /* bot detectado — responder OK para no revelar el mecanismo */
    echo json_encode(['ok' => true]);
    exit;
}

/* ── Función auxiliar: responder error ────────────────────────── */
function errJson(string $msg): void {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

/* ── Función auxiliar: sanitizar cadena ──────────────────────── */
function limpiar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

/* ── Leer y validar campos ───────────────────────────────────── */
$nombre   = limpiar($_POST['nombre']   ?? '');
$telefono = limpiar($_POST['telefono'] ?? '');
$correo   = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
$tipoCaso = limpiar($_POST['tipoCaso'] ?? '');
$comuna   = limpiar($_POST['comuna']   ?? '');
$etapa    = limpiar($_POST['etapa']    ?? '');
$mensaje  = limpiar($_POST['mensaje']  ?? '');
$consent  = !empty($_POST['consentimiento']);

$errores = [];

if (strlen($nombre) < 2)   $errores[] = 'Nombre inválido.';
if (!$correo)              $errores[] = 'Correo inválido.';
if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $_POST['telefono'] ?? '')) {
    $errores[] = 'Teléfono inválido.';
}
if (!$tipoCaso)  $errores[] = 'Tipo de caso requerido.';
if (!$comuna)    $errores[] = 'Comuna requerida.';
if (!$etapa)     $errores[] = 'Etapa requerida.';
if (!$consent)   $errores[] = 'Consentimiento requerido.';

if ($errores) {
    errJson(implode(' ', $errores));
}

/* ── Procesar adjunto (opcional) ─────────────────────────────── */
$adjuntoPath = null;
$adjuntoNombre = null;

if (!empty($_FILES['adjunto']['name'])) {
    $file    = $_FILES['adjunto'];
    $maxSize = 10 * 1024 * 1024; // 10 MB

    /* Validar error de subida */
    if ($file['error'] !== UPLOAD_ERR_OK) {
        errJson('Error al subir el archivo. Inténtalo de nuevo.');
    }

    /* Validar tamaño */
    if ($file['size'] > $maxSize) {
        errJson('El archivo supera el tamaño máximo de 10 MB.');
    }

    /* Validar tipo MIME real (no solo la extensión) */
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);
    $permitidos = ['application/pdf', 'image/jpeg', 'image/png'];

    if (!in_array($mimeReal, $permitidos, true)) {
        errJson('El archivo debe ser PDF, JPG o PNG.');
    }

    /* Nombre aleatorio para evitar colisiones y traversal */
    $ext = match ($mimeReal) {
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        default           => 'bin',
    };
    $nombreSeguro = bin2hex(random_bytes(16)) . '.' . $ext;
    $destino      = $uploadDir . $nombreSeguro;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        errJson('No se pudo guardar el archivo adjunto.');
    }

    $adjuntoPath   = $destino;
    $adjuntoNombre = basename($file['name']);
}

/* ── Construir asunto y cuerpo del correo ────────────────────── */
$asunto  = "Nueva consulta penal — {$tipoCaso} — {$nombre}";

$cuerpo  = "NUEVA CONSULTA DE CONTACTO — BRICEÑO CANALES\n";
$cuerpo .= str_repeat('═', 55) . "\n\n";
$cuerpo .= "Nombre:      {$nombre}\n";
$cuerpo .= "Teléfono:    " . limpiar($_POST['telefono'] ?? '') . "\n";
$cuerpo .= "Correo:      {$correo}\n";
$cuerpo .= "Comuna:      {$comuna}\n\n";
$cuerpo .= "Tipo de caso:  {$tipoCaso}\n";
$cuerpo .= "Etapa:         {$etapa}\n\n";

if ($mensaje) {
    $cuerpo .= "Mensaje del cliente:\n";
    $cuerpo .= str_repeat('─', 40) . "\n";
    $cuerpo .= "{$mensaje}\n\n";
}

if ($adjuntoNombre) {
    $cuerpo .= "Adjunto: {$adjuntoNombre} (guardado en /uploads/{$nombreSeguro})\n\n";
}

$cuerpo .= str_repeat('─', 55) . "\n";
$cuerpo .= "Enviado el: " . date('d/m/Y H:i:s') . "\n";

/* ── Enviar correo con mail() nativo ─────────────────────────── */
/*
 * Para mayor confiabilidad y soporte de adjuntos reales incrustados en el correo,
 * usa PHPMailer (ver bloque comentado más abajo).
 * La versión con mail() notifica que hay un adjunto guardado en /uploads/,
 * el equipo debe recuperarlo manualmente.
 */

$headers  = "From: noreply@bricenocanales.com\r\n";
$headers .= "Reply-To: {$correo}\r\n";
$headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$enviado = mail($destinatario, $asunto, $cuerpo, $headers);

if (!$enviado) {
    errJson('No se pudo enviar el correo. Por favor escríbenos directamente a contacto@bricenocanales.com');
}

echo json_encode(['ok' => true]);
exit;

/* ════════════════════════════════════════════════════════════════
   ALTERNATIVA CON PHPMailer (recomendada para adjuntos embebidos)
   ────────────────────────────────────────────────────────────────
   Instala PHPMailer: composer require phpmailer/phpmailer
   Ajusta credenciales SMTP de tu hosting.
   Luego reemplaza el bloque mail() anterior con esto:
   ════════════════════════════════════════════════════════════════

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'mail.bricenocanales.com'; // host SMTP de tu hosting
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contacto@bricenocanales.com';
    $mail->Password   = 'TU_CLAVE_SMTP';            // ⚠ usar variable de entorno en prod
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('contacto@bricenocanales.com', 'Briceño Canales Web');
    $mail->addAddress($destinatario);
    $mail->addReplyTo((string)$correo, $nombre);

    $mail->Subject = $asunto;
    $mail->Body    = $cuerpo;

    if ($adjuntoPath && file_exists($adjuntoPath)) {
        $mail->addAttachment($adjuntoPath, $adjuntoNombre ?? 'adjunto');
    }

    $mail->send();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    errJson('Error al enviar: ' . $mail->ErrorInfo);
}
*/
