<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/30
 * Time: 10:58
 */

namespace EP\Library\Email;


use EP\Exception\EE;

class Mail
{
    // connection
    protected $connection;
    protected $localhost;
    protected $timeout = 30;
    public $debug = false;

    // auth
    protected $host;
    protected $port;
    protected $secure; // null, 'ssl', or 'tls'
    protected $auth; // true if authorization required
    protected $user;
    protected $pass;

    // email
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $from;
    protected $reply;
    protected $body;
    protected $text;
    protected $subject;
    protected $attachments = [];
    protected $text_mode = false;

    // misc
    protected $charset = 'UTF-8';
    protected $newline = "\r\n";
    protected $encoding = '7bit';
    protected $wordwrap = 70;


    function __construct($host, $port = 25)
    {
        switch ($port) {
            case 465:
                $this->secure = 'ssl';
                break;
            case 587:
                $this->secure = 'tls';
                break;
            default:
                $this->secure = null;
        }

        // set connection vars
        $this->host = $host;
        $this->port = $port ?? 25;


        // set localhost
        $this->localhost = 'localhost';
    }


    function login($user, $pwd)
    {
        $this->auth = true;
        $this->user = $user;
        $this->pass = $pwd;
        return $this;
    }

    /**
     * 发件邮箱
     *
     * @param $email
     * @param null $name
     *
     * @return $this
     */
    function from($email, $name = null)
    {
        $this->from = array(
            'email' => $email,
            'name' => $name,
        );
        return $this;
    }

    /**
     * 回复邮件时发送到此邮箱
     *
     * @param $email
     * @param null $name
     *
     * @return $this
     */
    function replyTo($email, $name = null)
    {
        $this->reply = array(
            'email' => $email,
            'name' => $name,
        );
        return $this;
    }

    /**
     * 发送到这些邮箱
     *
     * @param string|array $email
     * @param null $name
     *
     * @return $this
     */
    function to($email, $name = null)
    {
        if (is_array($email)) {
            $this->to = $email;
        } else {
            $this->to[] = array(
                'email' => $email,
                'name' => $name,
            );
        }

        return $this;
    }

    function cc($email, $name = null)
    {
        $this->cc[] = array(
            'email' => $email,
            'name' => $name,
        );
        return $this;

    }

    public function bcc($email, $name = null)
    {
        $this->bcc[] = array(
            'email' => $email,
            'name' => $name,
        );
        return $this;
    }

    function body($html)
    {
        $this->body = $html;
        return $this;
    }

    function text($text)
    {
        $this->text = $this->normalize(wordwrap(strip_tags($text), $this->wordwrap));
        return $this;
    }

    function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    function attach($path)
    {
        $this->attachments[] = $path;
        return $this;
    }

    /**
     * @return bool
     * @throws EE
     */
    public function sendText()
    {
        // text mode
        $this->text_mode = true;

        // return
        return $this->send();
    }

    /**
     * 发送邮件
     * @return bool
     * @throws EE
     */
    public function send()
    {
        if ($this->connect()) {
            if ($this->deliver()) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        $this->disconnect();
        return $result;
    }

    /**
     * @return bool
     * @throws EE
     */
    protected function connect()
    {
        if ($this->secure === 'ssl') {
            $this->host = 'ssl://' . $this->host;
        }
        $err_no = $err_str = '';
        $this->connection = fsockopen($this->host, $this->port, $err_no, $err_str, $this->timeout);

        if (false === $this->connection) {
            if ($this->debug) {
                throw new EE(EE::ERROR, "{$err_no}:{$err_str}");
            }
            return false;
        }
        if ($this->code() !== 220) {
            return false;
        }

        $this->request(($this->auth ? 'EHLO' : 'HELO') . ' ' . $this->localhost . $this->newline);

        $this->response();

        if ($this->secure === 'tls') {
            $this->request('STARTTLS' . $this->newline);

            if ($this->code() !== 220) {
                return false;
            }
            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->request(($this->auth ? 'EHLO' : 'HELO') . ' ' . $this->localhost . $this->newline);
            if ($this->code() !== 250) {
                return false;
            }
        }

        if ($this->auth) {
            $this->request('AUTH LOGIN' . $this->newline);
            if ($this->code() !== 334) {
                return false;
            }
            $this->request(base64_encode($this->user) . $this->newline);
            if ($this->code() !== 334) {
                return false;
            }
            $this->request(base64_encode($this->pass) . $this->newline);
            if ($this->code() !== 235) {
                return false;
            }
        }
        return true;
    }

    protected function buildContent()
    {
        $boundary = md5(uniqid(time()));
        $headers[] = 'From: ' . $this->format($this->from);
        $headers[] = 'Reply-To: ' . $this->format($this->reply ? $this->reply : $this->from);
        $headers[] = 'Subject: ' . $this->subject;
        $headers[] = 'Date: ' . date('r');
        if (!empty($this->to)) {
            $string = '';
            foreach ($this->to as $r) {
                $string .= $this->format($r) . ', ';
            }
            $string = substr($string, 0, -2);
            $headers[] = 'To: ' . $string;
        }
        if (!empty($this->cc)) {
            $string = '';
            foreach ($this->cc as $r) {
                $string .= $this->format($r) . ', ';
            }
            $string = substr($string, 0, -2);
            $headers[] = 'CC: ' . $string;
        }
        if (empty($this->attachments)) {
            if ($this->text_mode) {
                $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->text;
            } else {
                // add multipart
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                $headers[] = '';
                $headers[] = 'This is a multi-part message in MIME format.';
                $headers[] = '--' . $boundary;

                // add text
                $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->text;
                $headers[] = '--' . $boundary;

                // add html
                $headers[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->body;
                $headers[] = '--' . $boundary . '--';
            }
        } else {
            // add multipart
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            $headers[] = '';
            $headers[] = 'This is a multi-part message in MIME format.';
            $headers[] = '--' . $boundary;

            // add text
            $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
            $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
            $headers[] = '';
            $headers[] = $this->text;
            $headers[] = '--' . $boundary;

            if (!$this->text_mode) {
                // add html
                $headers[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->body;
                $headers[] = '--' . $boundary;
            }

            foreach ($this->attachments as $path) {
                if (file_exists($path)) {
                    $contents = @file_get_contents($path);
                    if ($contents) {
                        $contents = chunk_split(base64_encode($contents));
                        $headers[] = 'Content-Type: application/octet-stream; name="' . basename($path) . '"'; // use different content types here
                        $headers[] = 'Content-Transfer-Encoding: base64';
                        $headers[] = 'Content-Disposition: attachment';
                        $headers[] = '';
                        $headers[] = $contents;
                        $headers[] = '--' . $boundary;
                    }
                }
            }
            $headers[sizeof($headers) - 1] .= '--';
        }

        $headers[] = '.';
        $email = '';
        foreach ($headers as $header) {
            $email .= $header . $this->newline;
        }
        return $email;
    }

    protected function deliver()
    {
        $this->request('MAIL FROM: <' . $this->from['email'] . '>' . $this->newline);
        $this->response();
        $recipients = array_merge($this->to, $this->cc, $this->bcc);
        foreach ($recipients as $r) {
            $this->request('RCPT TO: <' . $r['email'] . '>' . $this->newline);
            $this->response();
        }

        $this->request('DATA' . $this->newline);
        $this->response();
        $this->request($this->buildContent());

        // response
        if ($this->code() === 250) {
            return true;
        } else {
            return false;
        }
    }

    protected function disconnect()
    {
        $this->request('QUIT' . $this->newline);
        $this->response();
        fclose($this->connection);
    }

    protected function code()
    {
        return (int)substr($this->response(), 0, 3);
    }

    protected function request($string)
    {
        if ($this->debug) {
            echo "<code><h3>{$string}</h3></code><br/>\n";
        }
        fwrite($this->connection, $string);
    }

    protected function response()
    {
        $response = '';
        while ($str = fgets($this->connection, 4096)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        if ($this->debug) {
            echo "<code>{$response}</code><br/>\n";
        }
        return $response;
    }

    protected function format($recipient)
    {
        if ($recipient['name']) {
            return "{$recipient['name']} <{$recipient['email']}>";
        } else {
            return "<{$recipient['email']}>";
        }
    }

    private function normalize($lines)
    {
        $lines = str_replace("\r", "\n", $lines);
        $content = '';
        foreach (explode("\n", $lines) as $line) {
            foreach (str_split($line, 998) as $result) {
                $content .= $result . $this->newline;
            }
        }

        return $content;
    }

}