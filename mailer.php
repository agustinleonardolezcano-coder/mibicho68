<?php
/**
 * Cooperativa FLB — Mailer simple SMTP con soporte Gmail
 * No requiere librerías externas.
 */

class FlbMailer {
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    private $socket = null;
    private array  $log = [];

    public function __construct() {
        $this->host      = getSetting('smtp_host',      'smtp.gmail.com');
        $this->port      = (int)getSetting('smtp_port', '587');
        $this->user      = getSetting('smtp_user',      '');
        $this->pass      = getSetting('smtp_pass',      '');
        $this->fromEmail = getSetting('smtp_from',      '');
        $this->fromName  = getSetting('smtp_from_name', 'Cooperativa FLB');
    }

    private function cmd(string $cmd): string {
        fwrite($this->socket, $cmd . "\r\n");
        $resp = '';
        while (($line = fgets($this->socket, 512)) !== false) {
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $this->log[] = '→ ' . trim($cmd) . ' | ← ' . trim($resp);
        return $resp;
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        if (!$this->user || !$this->pass || !$this->fromEmail) {
            error_log('FlbMailer: SMTP no configurado (settings vacíos)');
            return false;
        }

        try {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $this->socket = stream_socket_client(
                "tcp://{$this->host}:{$this->port}", $errno, $errstr, 15,
                STREAM_CLIENT_CONNECT, $ctx
            );
            if (!$this->socket) throw new \Exception("Conexión fallida: $errstr ($errno)");
            fgets($this->socket, 512); // banner

            $this->cmd("EHLO " . gethostname());
            $this->cmd("STARTTLS");
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd("EHLO " . gethostname());
            $this->cmd("AUTH LOGIN");
            $this->cmd(base64_encode($this->user));
            $resp = $this->cmd(base64_encode($this->pass));
            if (strpos($resp, '235') === false) throw new \Exception("Auth fallida: $resp");

            $this->cmd("MAIL FROM:<{$this->fromEmail}>");
            $this->cmd("RCPT TO:<{$toEmail}>");
            $this->cmd("DATA");

            $toNameEnc   = '=?UTF-8?B?' . base64_encode($toName)       . '?=';
            $fromNameEnc = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
            $subjectEnc  = '=?UTF-8?B?' . base64_encode($subject)       . '?=';
            $boundary    = md5(uniqid());
            $textBody    = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

            $message  = "From: {$fromNameEnc} <{$this->fromEmail}>\r\n";
            $message .= "To: {$toNameEnc} <{$toEmail}>\r\n";
            $message .= "Subject: {$subjectEnc}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "Date: " . date('r') . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
            $message .= "--{$boundary}--\r\n.";

            $resp = $this->cmd($message);
            $this->cmd("QUIT");
            fclose($this->socket);

            return strpos($resp, '250') !== false;
        } catch (\Exception $e) {
            error_log('FlbMailer error: ' . $e->getMessage());
            if ($this->socket) @fclose($this->socket);
            return false;
        }
    }

    // ── Templates de email ─────────────────────────────────────
    public static function templateServicio(array $solicitud, string $estado): string {
        $talleres = [
            'taller1' => '💻 Taller 1 — Tecnología y Computación',
            'taller2' => '🔌 Taller 2 — Electrodomésticos y Eléctrico',
            'taller3' => '🚗 Taller 3 — Automotores y Pintura',
        ];
        $taller = $talleres[$solicitud['taller']] ?? $solicitud['taller'];
        $isOk   = $estado === 'approved';
        $color  = $isOk ? '#1565C0' : '#DC2626';
        $emoji  = $isOk ? '✅' : '❌';
        $titulo = $isOk ? 'Tu solicitud fue APROBADA' : 'Tu solicitud fue rechazada';
        $msg    = $isOk
            ? 'Nos pondremos en contacto a la brevedad para coordinar el servicio. Podés seguir el estado desde tu panel.'
            : 'Lamentamos no poder atender tu solicitud en este momento.';

        $razon = '';
        if (!$isOk && !empty($solicitud['rejection_reason'])) {
            $razon = "<p style='background:#FEE2E2;border-left:4px solid #DC2626;padding:12px;border-radius:4px;margin:16px 0'><strong>Motivo:</strong> " . htmlspecialchars($solicitud['rejection_reason']) . "</p>";
        }
        $notas = '';
        if ($isOk && !empty($solicitud['admin_notes'])) {
            $notas = "<p style='background:#DBEAFE;border-left:4px solid #1565C0;padding:12px;border-radius:4px;margin:16px 0'><strong>Notas:</strong> " . htmlspecialchars($solicitud['admin_notes']) . "</p>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F0F4FF;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:32px auto">
    <tr><td style="background:{$color};padding:28px 32px;border-radius:12px 12px 0 0">
      <div style="font-size:28px;margin-bottom:8px">{$emoji}</div>
      <h1 style="color:#fff;margin:0;font-size:22px">{$titulo}</h1>
      <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:14px">Cooperativa Fray Luis Beltrán</p>
    </td></tr>
    <tr><td style="background:#fff;padding:28px 32px;border-radius:0 0 12px 12px">
      <p style="font-size:15px;color:#1E293B">Hola <strong>{$solicitud['nombre_contacto']}</strong>,</p>
      <p style="color:#334155;line-height:1.7">{$msg}</p>
      <table width="100%" style="background:#F0F4FF;border-radius:8px;padding:16px;margin:16px 0;border-collapse:collapse">
        <tr><td style="padding:6px 12px;color:#64748B;font-size:13px;width:40%">Servicio solicitado</td>
            <td style="padding:6px 12px;color:#1E293B;font-size:13px;font-weight:bold">{$taller}</td></tr>
        <tr><td style="padding:6px 12px;color:#64748B;font-size:13px">N° de solicitud</td>
            <td style="padding:6px 12px;color:#1E293B;font-size:13px">#{$solicitud['id']}</td></tr>
        <tr><td style="padding:6px 12px;color:#64748B;font-size:13px">Descripción</td>
            <td style="padding:6px 12px;color:#1E293B;font-size:13px">{$solicitud['descripcion']}</td></tr>
      </table>
      {$razon}
      {$notas}
      <div style="text-align:center;margin:24px 0">
        <a href="https://cooperativafrayluisbeltran.iceiy.com/dashboard.php"
           style="background:{$color};color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:bold;font-size:14px">
          Ver mi panel →
        </a>
      </div>
      <p style="color:#94A3B8;font-size:12px;text-align:center;margin-top:24px;border-top:1px solid #E2E8F0;padding-top:16px">
        Cooperativa Escolar Fray Luis Beltrán · Este es un correo automático.
      </p>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
