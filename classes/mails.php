<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

class mails
{

    var $mail_template;

    public function __construct()
    {

        $this->mail_template = '';
        $this->to = '';
        $this->to_name = '';
        $this->from = '';
        $this->from_name = '';
        $this->subject = '';
        $this->message = '';
        $this->logArray = array();
        $this->send_error = '';
        $this->debug_message = '';

        global $config_abs_path;

        require_once $config_abs_path . '/libs/PHPMailer/Exception.php';
        require_once $config_abs_path . '/libs/PHPMailer/PHPMailer.php';
        require_once $config_abs_path . '/libs/PHPMailer/SMTP.php';

        $this->mail = new PHPMailer(true);

    }

    function init($mail = '', $name = '', $from = '', $from_name = '')
    {

        global $settings, $mail_settings;
        if ($mail) $this->to = $mail; else $this->to = $settings["admin_email"];
        if ($name) $this->to_name = $name; else $this->to_name = $settings["admin_name"];
        if ($from) $this->from = $from; else $this->from = $settings["admin_email"];
        if ($from_name) $this->from_name = $from_name; else $this->from_name = $settings["admin_name"];

        // send bcc to admin
        if ($mail_settings['bcc_to'] && $this->to != $settings["admin_email"] && $this->from != $settings["admin_email"]) {
            $this->bcc = $mail_settings["bcc_to"];
        } else $this->bcc = '';

        if ($mail_settings['use_smtp_auth']) {
            $this->mail->isSMTP();
            $this->mail->SMTPDebug = 3;
            $this->mail->Debugoutput = function ($str, $level) {
                $this->debug_message .= $str . "<br/>";
            };
            $this->mail->SMTPAuth = true;
            if (!$mail_settings['encryption']) {
                $this->mail->SMTPSecure = false;
                $this->mail->SMTPAutoTLS = false;
            } else
                $this->mail->SMTPSecure = $mail_settings['encryption'];

            $this->mail->Host = $mail_settings['smtp_server'];
            $this->mail->Username = $mail_settings['username'];
            $this->mail->Password = $mail_settings['password'];
            $this->mail->Port = $mail_settings['port'];
        } else $this->mail->isMail();
        //!!!!!!!!
        //$mail->setLanguage('fr', '/optional/path/to/language/directory/');
    }

    function setSubject($subject)
    {

        $this->subject = $subject;

    }

    function setMessage($msg)
    {

        $this->message = $msg;

    }

    function setFrom($str)
    {

        $this->from = $str;

    }

    function setFromName($str)
    {

        $this->from_name = $str;

    }


    function getVal($val)
    {

        return $this->array[$val];

    }

    function getSendError()
    {

        return $this->send_error;

    }

    function getDebugMessage()
    {

        return $this->debug_message;

    }

    function makeHTMLEmail($newLine)
    {

        $email_css = $this->readEmailCSS();
        //$message .= "--$mime_boundary".$newLine;
        $message = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
   \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
        $message .= '<html>' . $newLine . '<head></head>' . $newLine . '<body>';

        $message .= '<style type="text/css"><!--';
        $message .= $email_css;
        $message .= '--></style>';

        $message .= stripslashes($this->message);
        $message .= '</body>' . $newLine . '</html>' . $newLine;

        $this->message = $message;

    }

    function send()
    {

        global $lng;
        global $appearance_settings, $settings, $mail_settings;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') $newLine = "\r\n";
        else $newLine = "\n";
        try {
            //Recipients
            $this->mail->setFrom($mail_settings["username"], 'Carpass');
            $this->mail->addReplyTo($mail_settings["username"], 'Carpass');
            $this->mail->addAddress($this->to, $this->to_name);     // Add a recipient

            if ($mail_settings["html_mails"]) {
                $this->mail->isHTML(true);                                  // Set email format to HTML
                $this->makeHTMLEmail($newLine);                                // add CSS and html start tags
            }

            //Content
            $this->mail->CharSet = $appearance_settings["charset"];
            $this->mail->Subject = $this->subject;
            if ($mail_settings["html_mails"])
                $this->mail->Body = $this->message;
            else
                $this->mail->Body = $this->html2plain($this->message, $newLine);
            if ($mail_settings["html_mails"])
                $this->mail->AltBody = $this->html2plain($this->message, $newLine);

            $this->mail->send();
        } catch (Exception $e) {
            $this->send_error = $this->mail->ErrorInfo;
            //echo "Error: ".$this->send_error;
        }
        if (!$this->send_error) return 1;
        return 0;

    }

    function sendBCC($default_message)
    {

        global $lng;
        global $mail_settings;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') $newLine = "\r\n";
        else $newLine = "\n";

        $this->mail->clearAddresses();
        $this->mail->addAddress($this->bcc);

        $this->mail->Subject = $lng['bcc_mails']['subject'] . $this->subject;

        $this->message = $default_message;
        // add sender ip for the admin
        $this->message .= "<br/><br/>IP: " . getRemoteIp();

        if ($mail_settings["html_mails"]) {
            $this->makeHTMLEmail($newLine);
            $this->mail->Body = $this->message;
        } else
            $this->mail->Body = $this->html2plain($this->message, $newLine);

        $this->mail->send();

    }

    function html2plain($str, $newLine)
    {

        $final = str_replace("<br>", $newLine, $str);
        $final = str_replace("<br/>", $newLine, $final);
        $final = str_replace("<br />", $newLine, $final);
        $final = str_replace("&amp;", '&', $final);
        $final = strip_tags($final);
        return $final;

    }

    function readEmailCSS()
    {

        global $lng;
        global $config_abs_path;
        $this->file_path = $config_abs_path . '/templates/style_emails.css';
        if (!file_exists($this->file_path)) return '';

        if ($fp = fopen($this->file_path, 'r')) {

            $content = fread($fp, filesize($this->file_path));
            fclose($fp);
            return $content;
        } else return '';

    }

    function setMailTemplate($template_name)
    {

        $this->mail_template = mail_templates::getVal($template_name);

    }


    function composeAndSend($template_name, $array_message, $array_subject)
    {

        // get email template
        if (empty($this->mail_template) && $template_name) $this->mail_template = mail_templates::getVal($template_name);

        $smarty_info = new Smarty;
        $smarty_info = smartyShowDBVal($smarty_info);

        // make email subject
        $smarty_info->assign("value", $this->mail_template['subject']);

        foreach ($array_subject as $key => $value) {
            $smarty_info->assign($key, $value);
        }

        $subject = $smarty_info->fetch("db_template.html");

        // make email content
        $smarty_info->assign("value", $this->mail_template['content']);
        foreach ($array_message as $key => $value) {
            $smarty_info->assign($key, $value);
        }

        $message = $smarty_info->fetch("db_template.html");

        $default_message = $message;
        $this->setSubject($subject);
        $this->setMessage($message);

        $response = $this->send();

        // send copy message to admin
        if ($this->bcc)
            $this->sendBCC($default_message);

        return $response;

    }

}

?>
