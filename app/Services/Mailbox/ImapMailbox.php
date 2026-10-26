<?php

namespace App\Services\Mailbox;

use RuntimeException;

final class ImapMailbox
{
    private $stream = null;
    private int $tag = 0;

    private array $credentials;

    public function __construct(?array $credentials = null)
    {
        $this->credentials = $credentials ?? [
            'host' => config('mailbox.host'), 'port' => config('mailbox.port', 993),
            'username' => config('mailbox.username'), 'password' => config('mailbox.password'),
            'timeout' => config('mailbox.timeout', 12),
        ];
        $host = (string) ($this->credentials['host'] ?? '');
        if ($host === '' || (string) ($this->credentials['username'] ?? '') === '' || (string) ($this->credentials['password'] ?? '') === '') {
            throw new RuntimeException('تنظیمات اتصال صندوق ایمیل کامل نشده است.');
        }
        $port = (int) ($this->credentials['port'] ?? 993);
        $timeout = (int) ($this->credentials['timeout'] ?? 12);
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true]]);
        $this->stream = @stream_socket_client('ssl://'.$host.':'.$port, $errno, $error, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (! is_resource($this->stream)) throw new RuntimeException('اتصال امن به سرور ایمیل برقرار نشد.');
        stream_set_timeout($this->stream, $timeout);
        $greeting = fgets($this->stream);
        if (! is_string($greeting) || ! str_starts_with(strtoupper($greeting), '* OK')) throw new RuntimeException('سرور ایمیل پاسخ معتبر نداد.');
        $this->command('LOGIN '.$this->quote((string) $this->credentials['username']).' '.$this->quote((string) $this->credentials['password']));
    }

    public function listMessages(string $search = '', int $limit = 50, string $folder = 'INBOX'): array
    {
        $folder = $this->safeFolder($folder);
        // A newly provisioned mailbox may not have a Sent folder yet. Create it
        // before SELECT so opening the folder never looks like an IMAP login failure.
        if ($folder === 'Sent') {
            $this->sentFolder();
        }
        $this->command('SELECT '.$this->quote($folder));
        $search = trim($search);
        $criteria = $search === '' ? 'ALL' : ($folder === 'INBOX' ? 'OR SUBJECT '.$this->quote($search).' FROM '.$this->quote($search) : 'OR SUBJECT '.$this->quote($search).' TO '.$this->quote($search));
        $response = $this->command('UID SEARCH '.$criteria);
        preg_match('/\* SEARCH([^\r\n]*)/i', $response, $match);
        $uids = preg_split('/\s+/', trim($match[1] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $uids = array_slice(array_reverse(array_filter($uids, fn ($uid) => ctype_digit($uid))), 0, max(1, min(100, $limit)));
        $messages = [];
        foreach ($uids as $uid) {
            $raw = $this->command('UID FETCH '.(int) $uid.' (UID FLAGS INTERNALDATE RFC822.SIZE BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE TO)])');
            $header = $this->literal($raw, true);
            $headers = $this->headers((string) $header);
            preg_match('/INTERNALDATE\s+"([^"]+)"/i', $raw, $date);
            preg_match('/RFC822\.SIZE\s+(\d+)/i', $raw, $size);
            preg_match('/FLAGS\s+\(([^)]*)\)/i', $raw, $flags);
            $timestamp = isset($date[1]) ? strtotime($date[1]) : false;
            $messages[] = [
                'uid' => (int) $uid,
                'subject' => $this->decodeHeader($headers['subject'] ?? '(بدون موضوع)'),
                'from' => $this->decodeHeader($folder === 'INBOX' ? ($headers['from'] ?? 'فرستنده نامشخص') : ($headers['to'] ?? 'گیرنده نامشخص')),
                'date' => $timestamp ? date(DATE_ATOM, $timestamp) : ($headers['date'] ?? ''),
                'size' => (int) ($size[1] ?? 0),
                'seen' => str_contains(strtolower($flags[1] ?? ''), '\\seen'),
            ];
        }
        return $messages;
    }

    public function getMessage(int $uid, string $folder = 'INBOX'): array
    {
        $folder = $this->safeFolder($folder);
        if ($uid < 1) {
            throw new RuntimeException('شناسه ایمیل معتبر نیست.');
        }
        $this->command('SELECT '.$this->quote($folder));
        $raw = $this->command('UID FETCH '.$uid.' (UID INTERNALDATE BODY.PEEK[])');
        if (! preg_match('/\bUID\s+'.preg_quote((string) $uid, '/').'\b/', $raw)) {
            throw new RuntimeException('ایمیل موردنظر پیدا نشد.');
        }
        $message = $this->literal($raw, false);
        if (! is_string($message) || $message === '') {
            throw new RuntimeException('محتوای ایمیل قابل خواندن نیست.');
        }
        [$headerText, $body] = array_pad(preg_split("/\r\n\r\n|\n\n/", $message, 2) ?: [], 2, '');
        $headers = $this->headers($headerText);
        $text = $this->extractText($body, $headers);
        preg_match('/INTERNALDATE\s+"([^"]+)"/i', $raw, $date);
        $timestamp = isset($date[1]) ? strtotime($date[1]) : false;
        return [
            'uid' => $uid,
            'subject' => $this->decodeHeader($headers['subject'] ?? '(بدون موضوع)'),
            'from' => $this->decodeHeader($headers['from'] ?? ''),
            'to' => $this->decodeHeader($headers['to'] ?? ''),
            'date' => $timestamp ? date(DATE_ATOM, $timestamp) : ($headers['date'] ?? ''),
            'message' => trim($text),
        ];
    }

    public function appendSentMessage(string $from, string $to, string $subject, string $body): void
    {
        $folder = $this->sentFolder();
        $message = 'From: '.$from."\r\n"
            .'To: '.$to."\r\n"
            .'Subject: =?UTF-8?B?'.base64_encode($subject)."?=\r\n"
            .'Date: '.date(DATE_RFC2822)."\r\n"
            ."MIME-Version: 1.0\r\n"
            ."Content-Type: text/plain; charset=UTF-8\r\n"
            ."Content-Transfer-Encoding: base64\r\n\r\n"
            .chunk_split(base64_encode($body), 76, "\r\n");
        $tag = 'A'.str_pad((string) ++$this->tag, 4, '0', STR_PAD_LEFT);
        $command = $tag.' APPEND '.$this->quote($folder).' (\\Seen) {'.strlen($message)."}\r\n";
        if (fwrite($this->stream, $command) === false) {
            throw new RuntimeException('ارسال نسخهٔ ذخیره‌شده به صندوق ناموفق بود.');
        }
        $line = fgets($this->stream);
        if (! is_string($line) || ! str_starts_with(trim($line), '+')) {
            throw new RuntimeException('سرور ایمیل ذخیرهٔ نسخهٔ ارسالی را نپذیرفت.');
        }
        if (fwrite($this->stream, $message."\r\n") === false) {
            throw new RuntimeException('ذخیرهٔ نسخهٔ ارسالی ناقص بود.');
        }
        while (($line = fgets($this->stream)) !== false) {
            if (preg_match('/^'.preg_quote($tag, '/').'\s+(OK|NO|BAD)\b/i', $line, $status)) {
                if (strtoupper($status[1]) !== 'OK') {
                    throw new RuntimeException('سرور ایمیل نتوانست نسخهٔ ارسالی را ذخیره کند.');
                }
                return;
            }
        }
        throw new RuntimeException('ارتباط هنگام ذخیرهٔ نسخهٔ ارسالی قطع شد.');
    }

    private function sentFolder(): string
    {
        $response = $this->command('LIST "" "*"');
        preg_match_all('/^\* LIST \([^)]*\)\s+(?:"[^"]+"\s+)?(?:"((?:[^"\\\\]|\\\\.)+)"|([^\r\n]+))\r?$/mi', $response, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = stripcslashes(trim($match[1] !== '' ? $match[1] : $match[2], '"'));
            if (strtolower($name) === 'sent') {
                return $name;
            }
        }
        $this->command('CREATE "Sent"');
        return 'Sent';
    }

    private function safeFolder(string $folder): string
    {
        if (! in_array($folder, ['INBOX', 'Sent', 'Sent Messages', 'INBOX.Sent'], true)) {
            throw new RuntimeException('پوشهٔ ایمیل معتبر نیست.');
        }
        return $folder;
    }

    private function command(string $command): string
    {
        $tag = 'A'.str_pad((string) ++$this->tag, 4, '0', STR_PAD_LEFT);
        if (fwrite($this->stream, $tag.' '.$command."\r\n") === false) {
            throw new RuntimeException('ارسال دستور به سرور ایمیل ناموفق بود.');
        }
        $response = '';
        while (($line = fgets($this->stream)) !== false) {
            $response .= $line;
            if (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
                $length = (int) $m[1];
                $literal = '';
                while (strlen($literal) < $length && ! feof($this->stream)) {
                    $chunk = fread($this->stream, min(8192, $length - strlen($literal)));
                    if ($chunk === false || $chunk === '') break;
                    $literal .= $chunk;
                }
                if (strlen($literal) !== $length) throw new RuntimeException('دریافت محتوای ایمیل ناقص بود.');
                $response .= $literal;
                continue;
            }
            if (preg_match('/^'.preg_quote($tag, '/').'\s+(OK|NO|BAD)\b/i', $line, $status)) {
                if (strtoupper($status[1]) !== 'OK') throw new RuntimeException('سرور ایمیل درخواست را نپذیرفت.');
                return $response;
            }
        }
        throw new RuntimeException('ارتباط با سرور ایمیل قطع شد.');
    }

    private function literal(string $response, bool $header): ?string
    {
        $pattern = $header
            ? '/BODY\[HEADER\.FIELDS\s+\(FROM SUBJECT DATE\)\]\s+\{(\d+)\}\r?\n/i'
            : '/BODY\[\]\s+\{(\d+)\}\r?\n/i';
        if (! preg_match($pattern, $response, $m, PREG_OFFSET_CAPTURE)) {
            if (! $header || ! preg_match('/BODY\[[^\]]*HEADER[^\]]*\]\s+\{(\d+)\}\r?\n/i', $response, $m, PREG_OFFSET_CAPTURE)) return null;
        }
        return substr($response, $m[0][1] + strlen($m[0][0]), (int) $m[1][0]);
    }

    private function headers(string $raw): array
    {
        $raw = preg_replace("/\r?\n[ \t]+/", ' ', $raw) ?? $raw;
        $result = [];
        foreach (preg_split("/\r?\n/", $raw) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $result[strtolower(trim($key))] = trim($value);
            }
        }
        return $result;
    }

    private function extractText(string $body, array $headers): string
    {
        $type = $headers['content-type'] ?? 'text/plain';
        if (preg_match('/boundary="?([^";]+)"?/i', $type, $m)) {
            $parts = explode('--'.$m[1], $body);
            $fallback = '';
            foreach ($parts as $part) {
                $part = ltrim($part, "\r\n-");
                if (trim($part) === '') continue;
                [$partHeader, $partBody] = array_pad(preg_split("/\r\n\r\n|\n\n/", $part, 2) ?: [], 2, '');
                $partHeaders = $this->headers($partHeader);
                $partType = strtolower($partHeaders['content-type'] ?? 'text/plain');
                if (str_contains($partType, 'text/plain')) return $this->decodePart($partBody, $partHeaders);
                if (str_contains($partType, 'text/html')) $fallback = $this->decodePart($partBody, $partHeaders);
            }
            return $fallback !== '' ? html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/li)\b[^>]*>/i', "\n", $fallback) ?? $fallback), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        }
        $text = $this->decodePart($body, $headers);
        if (str_contains(strtolower($type), 'text/html')) {
            $text = html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/li)\b[^>]*>/i', "\n", $text) ?? $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $text;
    }

    private function decodePart(string $body, array $headers): string
    {
        $encoding = strtolower(trim($headers['content-transfer-encoding'] ?? ''));
        if ($encoding === 'base64') $body = base64_decode(preg_replace('/\s+/', '', $body) ?: '', true) ?: '';
        elseif ($encoding === 'quoted-printable') $body = quoted_printable_decode($body);
        if (preg_match('/charset=["\']?([^;"\'\s]+)/i', $headers['content-type'] ?? '', $m)) {
            $charset = strtoupper($m[1]);
            if ($charset !== 'UTF-8' && function_exists('iconv')) {
                $converted = @iconv($charset, 'UTF-8//IGNORE', $body);
                if ($converted !== false) $body = $converted;
            }
        }
        return $body;
    }

    private function decodeHeader(string $value): string
    {
        $decoded = function_exists('iconv_mime_decode') ? @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : false;
        return $decoded !== false ? $decoded : $value;
    }

    private function quote(string $value): string
    {
        return '"'.addcslashes($value, "\\\"").'"';
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            @fwrite($this->stream, "A9999 LOGOUT\r\n");
            fclose($this->stream);
        }
    }
}
