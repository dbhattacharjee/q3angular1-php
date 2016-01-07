<?php

function pop3_login($host, $port, $user, $pass, $folder = "INBOX", $ssl = false) {
    $ssl = ($ssl == false) ? "/novalidate-cert" : "";
    $mailbox = false;
    if ((imap_open("{" . "$host:$port/pop3$ssl" . "}$folder", $user, $pass))) {
        $attachmentDir = BASE_DIR . '/attachments/' . $user;
        if (!is_dir($attachmentDir)) {
            mkdir($attachmentDir, 0777, true);
        }
        $mailbox = new PhpImap\Mailbox('{' . MAIL_HOST . ':' . MAIL_PORT . '/pop3' . $ssl . '}INBOX', $user, $pass, $attachmentDir);
    }
    return $mailbox;
}

function pop3_stat($connection) {
    $check = imap_mailboxmsginfo($connection);
    return ((array) $check);
}

function pop3_list($connection, $message = "") {
    if ($message) {
        $range = $message;
    } else {
        $MC = imap_check($connection);
        $range = "1:" . $MC->Nmsgs;
    }
    $response = imap_fetch_overview($connection, $range);
    foreach ($response as $msg)
        $result[$msg->msgno] = (array) $msg;
    return $result;
}

function pop3_retr($connection, $message) {
    return(imap_fetchheader($connection, $message, FT_PREFETCHTEXT));
}

function pop3_dele($connection, $message) {
    return(imap_delete($connection, $message));
}


function pop3_get_mail_header($imap, $mailId) {
 return imap_rfc822_parse_headers(imap_fetchheader($imap->getImapStream(), $mailId, FT_UID));

}
function pop3_fetch_emails($imap, $username, $excludeMessageIds = array()) {

    if ($mailsIds = $imap->searchMailbox('UNSEEN')) {
        foreach ($mailsIds as $mailId) {
            $header = pop3_get_mail_header($imap, $mailId);
            //do not process already fetched messages
            if(in_array($header->message_id, $excludeMessageIds)) {
                continue;
            }
            $fromInfo = $toInfo = $ccInfo = '';
            $attachments = '';
            $mail = $imap->getMail($mailId);
            
            $fromInfo = '<a href="mailto:' . $mail->fromAddress . '" target="_top">' . $mail->fromName . '</a>';
            if ($to = $mail->to) {
                foreach ($to as $key => $val) {
                    if ($toInfo !='') {
                        $toInfo .= ', ';
                    }
                    if (trim($val) != '') {
                        $toInfo .= '<a href="mailto:' . $key . '" target="_top">' . $val . '</a>';
                    } else {
                        $toInfo .= '<a href="mailto:' . $key . '" target="_top">' . $key . '</a>';
                    }
                }
            }
            if ($cc = $mail->cc) {
                foreach ($cc as $key => $val) {
                    if ($ccInfo !='') {
                        $ccInfo .= ', ';
                    }
                    if (trim($val) != '') {
                        $ccInfo .= '<a href="mailto:' . $key . '" target="_top">' . $val . '</a>';
                    } else {
                        $ccInfo .= '<a href="mailto:' . $key . '" target="_top">' . $key . '</a>';
                    }
                }
            }
            if ($attachment = $mail->getAttachments()) {
                foreach ($attachment as $val) {
                    if($val->disposition != 'ATTACHMENT') {
                        continue;
                    }
                    if ($attachments !='') {
                        $attachments .= '<br/>';
                    }
                    $attachments .= '<a href="' . ATTACHMENT_URL . $username . '/' . basename($val->filePath) . '" target="_blank">' . $val->name . '</a>';
                }
            }

            $body = $mail->textPlain;
            if (trim($mail->textHtml) != '') {
                $body = $mail->textHtml;
            }

            $details[] = array(
                "body" => $body,
                "attachment" => $attachments,
                "from" => $fromInfo,
                "to" => $toInfo,
                "cc" => $ccInfo,
                "subject" => $mail->subject,
                "udate" => strtotime($mail->date),
                "uid" => $mailId,
                "message_id" => $mail->messageId
            );
            //yield $details;
            //$imap->markMailAsRead($mailId);
        }
    }



    return $details;
}