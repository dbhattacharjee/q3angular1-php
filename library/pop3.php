<?php
function pop3_login($host,$port,$user,$pass,$folder="INBOX",$ssl=false)
{
    $ssl=($ssl==false)?"/novalidate-cert":"";
    return (imap_open("{"."$host:$port/pop3$ssl"."}$folder",$user,$pass));
}
function pop3_stat($connection)       
{
    $check = imap_mailboxmsginfo($connection);
    return ((array)$check);
}
function pop3_list($connection,$message="")
{
    if ($message)
    {
        $range=$message;
    } else {
        $MC = imap_check($connection);
        $range = "1:".$MC->Nmsgs;
    }
    $response = imap_fetch_overview($connection,$range);
    foreach ($response as $msg) $result[$msg->msgno]=(array)$msg;
        return $result;
}
function pop3_retr($connection,$message)
{
    return(imap_fetchheader($connection,$message,FT_PREFETCHTEXT));
}
function pop3_dele($connection,$message)
{
    return(imap_delete($connection,$message));
}
//function mail_parse_headers($headers)
//{
//    $headers=preg_replace('/\r\n\s+/m', '',$headers);
//    preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
//    foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key];
//    return($result);
//}
//function mail_mime_to_array($imap,$mid,$parse_headers=false)
//{
//    $mail = imap_fetchstructure($imap,$mid);
//    $mail = mail_get_parts($imap,$mid,$mail,0);
//    if ($parse_headers) $mail[0]["parsed"]=mail_parse_headers($mail[0]["data"]);
//    return($mail);
//}
//function mail_get_parts($imap,$mid,$part,$prefix)
//{   
//    $attachments=array();
//    $attachments[$prefix]=mail_decode_part($imap,$mid,$part,$prefix);
//    if (isset($part->parts)) // multipart
//    {
//        $prefix = ($prefix == "0")?"":"$prefix.";
//        foreach ($part->parts as $number=>$subpart)
//            $attachments=array_merge($attachments, mail_get_parts($imap,$mid,$subpart,$prefix.($number+1)));
//    }
//    return $attachments;
//}
//function mail_decode_part($connection,$message_number,$part,$prefix)
//{
//    $attachment = array();
//
//    if($part->ifdparameters) {
//        foreach($part->dparameters as $object) {
//            $attachment[strtolower($object->attribute)]=$object->value;
//            if(strtolower($object->attribute) == 'filename') {
//                $attachment['is_attachment'] = true;
//                $attachment['filename'] = $object->value;
//            }
//        }
//    }
//
//    if($part->ifparameters) {
//        foreach($part->parameters as $object) {
//            $attachment[strtolower($object->attribute)]=$object->value;
//            if(strtolower($object->attribute) == 'name') {
//                $attachment['is_attachment'] = true;
//                $attachment['name'] = $object->value;
//            }
//        }
//    }
//
//    $attachment['data'] = imap_fetchbody($connection, $message_number, $prefix);
//    if($part->encoding == 3) { // 3 = BASE64
//        $attachment['data'] = base64_decode($attachment['data']);
//    }
//    elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
//        $attachment['data'] = quoted_printable_decode($attachment['data']);
//    }
//    return($attachment);
//}

function pop3_fetch_emails($imap, $limit = 20) {
    $numMessages = imap_num_msg($imap);
    $details = array();
    for ($i = $numMessages; $i > ($numMessages - $limit); $i--) {
        $header = imap_header($imap, $i);
        

        $from = $header->from;
        $fromInfo = '';
        foreach($from as $value) {
            if($fromInfo !='') {
                $fromInfo .= ', ';
            }
            $temp = $value->mailbox.'@'.$value->host;
            if(property_exists($value, 'personal')) {
                $temp = '<a href="mailto:'.$temp.'" target="_top">'.$value->personal.'</a>';
            }else {
                $temp = '<a href="mailto:'.$temp.'" target="_top">'.$temp.'</a>';
            }
            $fromInfo .= $temp;
        }
        $to = $header->to;
        $toInfo = '';
        foreach($to as $value) {
            if($toInfo !='') {
                $toInfo .= ', ';
            }
            $temp = $value->mailbox.'@'.$value->host;
            if(property_exists($value, 'personal')) {
                $temp = '<a href="mailto:'.$temp.'" target="_top">'.$value->personal.'</a>';
            } else {
                $temp = '<a href="mailto:'.$temp.'" target="_top">'.$temp.'</a>';
            }
            $toInfo .= $temp;
        }
        $ccInfo = '';
        if (property_exists($header, 'cc')) {
            $cc = $header->cc;
            $ccInfo = '';
            foreach ($cc as $value) {
                if ($ccInfo != '') {
                    $ccInfo .= ', ';
                }
                $temp = $value->mailbox . '@' . $value->host;
                if (property_exists($value, 'personal')) {
                    $temp = '<a href="mailto:' . $temp . '" target="_top">' . $value->personal . '</a>';
                } else {
                    $temp = '<a href="mailto:' . $temp . '" target="_top">' . $temp . '</a>';
                }
                $ccInfo .= $temp;
            }
        }
        
        $fromInfo = str_replace("'", "", $fromInfo);
        $toInfo = str_replace("'", "", $toInfo);
        $ccInfo = str_replace("'", "", $ccInfo);

        $details[] = array(
            "from" => $fromInfo,
            "to" => $toInfo,
            "cc" => $ccInfo,
            "subject" => (isset($header->subject)) ? trim($header->subject) : "",
            "udate" => (isset($header->udate)) ? $header->udate : time(),
            "uid"=>imap_uid($imap, $i),
            "message_id"=>$header->message_id
        );
    }
    return $details;
}

function pop3_get_body($uid, $imap) {
    $body = get_part($imap, $uid, "TEXT/HTML");
    // if HTML body is empty, try getting text body
    if ($body == "") {
        $body = get_part($imap, $uid, "TEXT/PLAIN");
    }
    return $body;
}

function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
    if (!$structure) {
           $structure = imap_fetchstructure($imap, $uid, FT_UID);
    }
    if ($structure) {
        if ($mimetype == get_mime_type($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
            switch ($structure->encoding) {
                case 3: return imap_base64($text);
                case 4: return imap_qprint($text);
                default: return $text;
           }
       }

        // multipart 
        if ($structure->type == 1) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $data = get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}

function get_mime_type($structure) {
    $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");

    if ($structure->subtype) {
       return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}   