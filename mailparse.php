<?php

error_reporting(E_ALL);

require_once __DIR__ . '/ParensParser.php';
/* * ***********************************************************************************
 * ===================================================================================*
 * Software by: Danyuki Software Limited                                              *
 * This file is part of Plancake.                                                     *
 *                                                                                    *
 * Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
 * Support, News, Updates at:  http://www.plancake.com                                *
 * Licensed under the LGPL version 3 license.                                         *                                                       *
 * Danyuki Software Limited is registered in England and Wales (Company No. 07554549) *
 * *************************************************************************************
 * Plancake is distributed in the hope that it will be useful,                        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of                     *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                      *
 * GNU Lesser General Public License v3.0 for more details.                           *
 *                                                                                    *
 * You should have received a copy of the GNU Lesser General Public License           *
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.              *
 *                                                                                    *
 * *************************************************************************************
 *
 * Valuable contributions by:
 * - Chris 
 *
 * ************************************************************************************* */

/**
 * Extracts the headers and the body of an email
 * Obviously it can't extract the bcc header because it doesn't appear in the content
 * of the email.
 *
 * N.B.: if you deal with non-English languages, we recommend you install the IMAP PHP extension:
 * the Plancake PHP Email Parser will detect it and used it automatically for better results.
 * 
 * For more info, check:
 * https://github.com/plancake/official-library-php-email-parser
 * 
 * @author dan
 */
class PlancakeEmailParser {

    const PLAINTEXT = 1;
    const HTML = 2;

    /**
     *
     * @var boolean
     */
    private $isImapExtensionAvailable = false;

    /**
     *
     * @var string
     */
    private $emailRawContent;

    /**
     *
     * @var string
     */
    private $emailDuration;

    /**
     *
     * @var associative array
     */
    protected $rawFields;

    /**
     *
     * @var associative array
     */
    protected $rawHeaderFields;

    /**
     *
     * @var associative array
     */
    protected $rawHeaderFieldsIndex;

    /**
     *
     * @var array of string (each element is a line)
     */
    protected $recivedHeaders;

    /**
     *
     * @var array of string (each element is a line)
     */
    protected $rawBodyLines;

    /**
     *
     * @param string $emailRawContent
     */
    public function __construct($emailRawContent) {
        $this->emailRawContent = $emailRawContent;
        $this->extractHeadersAndRawBody();

        if (function_exists('imap_open')) {
            $this->isImapExtensionAvailable = true;
        }
    }

    private function extractHeadersAndRawBody() {
        $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);
        $currentHeader = '';
        $currentHeader = '';
        $i = 0;
        $v = 0;
        $this->rawHeaderFieldsIndex = 0;
        foreach ($lines as $line) {
            if (self::isNewLine($line)) {
                // end of headers
                $this->rawBodyLines = array_slice($lines, $i);
                break;
            }

            if ($this->isLineStartingWithPrintableChar($line)) { // start of new header
                preg_match('/([^:]+): ?(.*)$/', $line, $matches);
                $newHeader = strtolower($matches[1]);
                $value = $matches[2];

                if ($currentHeader == 'date') {
                    $this->rawHeaderFieldsIndex++;
                    $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['key'] = 'date(Formatted)';
                    $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['value'] = gmdate('m/d/Y H:i:s', strtotime($this->rawFields['date'])) . ' UTC ';
                    
                }

                $this->rawFields[$newHeader] = $value;
                $this->rawHeaderFieldsIndex++;
                $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['key'] = $newHeader;
                $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['value'] = $value;
                
                if ($newHeader == 'received') {
                    $v++;
                    $this->recivedHeaders[$v] = $value;
                }


                $currentHeader = $newHeader;
            } else { // more lines related to the current header
                if ($currentHeader) { // to prevent notice from empty lines
                    $this->rawFields[$currentHeader] .= substr($line, 1);
                    $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['value'] .= substr($line, 1);
                    if ($newHeader == 'received') {
                        $this->recivedHeaders[$v] .= substr($line, 1);
                    }
                }
            }
            $i++;
        }
    }

    public function get_string_between($string, $start, $end) {

        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function ip_is_private($ip) {
        $pri_addrs = array(
            '10.0.0.0|10.255.255.255', // single class A network
            '172.16.0.0|172.31.255.255', // 16 contiguous class B network
            '192.168.0.0|192.168.255.255', // 256 contiguous class C network
            '169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
            '127.0.0.0|127.255.255.255' // localhost
        );

        $long_ip = ip2long($ip);
        if ($long_ip != -1) {

            foreach ($pri_addrs AS $pri_addr) {
                list ($start, $end) = explode('|', $pri_addr);

                // IF IS PRIVATE
                if ($long_ip >= ip2long($start) && $long_ip <= ip2long($end)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function delete_all_between($beginning, $end, $string) {
        $beginningPos = strpos($string, $beginning);
        $endPos = strpos($string, $end);
        if ($beginningPos === false || $endPos === false) {
            return $string;
        }

        $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

        return str_replace($textToDelete, '', $string);
    }
    
    public function data_between_paranthesis($string) {
        $regex = '#((([^()]+|(?R))*))#';
        $a;
        if (preg_match_all($regex, $string, $matches)) {
            $a = implode(' ', $matches[1]);
        } else {
            //no parenthesis
            $a = $string;
        }

        return $a;
    }

    public function get_header_data($string, $start, $end, $index, $num) {
        
        
        
        
        $data = $this->get_string_between($string, $start, $end);
        $senderIp = '';
        if ($data) {
            $senderIp = $this->get_string_between($data, '[', ']');
            $senderHostName = $this->get_string_between($this->delete_all_between('[', ']', $data), '(', ')');
            $senderName = $this->get_string_between($this->delete_all_between('[', ']', $data), '(', ')');

            if (!$senderHostName) {
                $senederDataInfo = explode(' ', $data);
                $FromInfo = $senederDataInfo[1];
                $senderHostName = $senederDataInfo[1];
            }
            $s = explode('(', $data);

            $senderAdreess = $this->delete_all_between('(', ')', $this->delete_all_between('[', ']', $data));
        }

        $timeArray = explode(';', $string);
        $time = (count($timeArray) > 1 ) ? gmdate('m/d/Y H:i:s', strtotime(end($timeArray))) . ' UTC ' : 'Invalid receive time';
        $rowData = explode(' ', $string);

        $for = trim($this->get_string_between($string,'for', ';'));
        
       
        $by = '';
        $byExt = $this->data_between_paranthesis(trim($this->get_string_between($string,'by', 'for')));
        $With = '';
        for ($i = 0; $i < count($rowData); $i++) {

            if ($rowData[$i] == 'by') {
                $i++;
                $by = $rowData[$i];
            }
            if ($rowData[$i] == 'with') {
                $i++;
                $With = $rowData[$i];
            }
        }
        $entryKey = 'Entry(Line' . ($num + 1 - $index) . ')';
        $duration = 'NA';
//    print_r($time.'---'.$this->emailDuration.'\n');

        if ($time != '' && $this->emailDuration != '') {
            $duration = (strtotime($time) - strtotime($this->emailDuration)) . 's';
//        die($duration);
        } else if ($time) {

            $this->emailDuration = $time;
        } else {
            $this->emailDuration = '';
        }
        $warn = array();
        $noticeCount = 0;

        if (trim($senderName) == trim($senderHostName)) {
            $warn[$noticeCount]['msg'] = 'The host names are the same.';
            $warn[$noticeCount]['type'] = 'Notice';
            $warn[$noticeCount]['class'] = 'green-notice';
            $noticeCount++;
        }
        if (($num + 1 - $index) == 1) {
            $warn[$noticeCount]['msg'] = 'This entry was generated by the mail server of your provider..';
            $warn[$noticeCount]['type'] = 'Notice';
            $warn[$noticeCount]['class'] = 'green-notice';
            $noticeCount++;
        }
        if ($senderIp && ($this->ip_is_private($senderIp))) {
            $warn[$noticeCount]['msg'] = 'The IP address of the sender (' . $senderIp . ') is not a public address';
            $warn[$noticeCount]['type'] = 'Notice';
            $warn[$noticeCount]['class'] = 'yello-notice';
            $noticeCount++;
            $warn[$noticeCount]['msg'] = 'The sender and/or the recipient seems to be from a non-public network';
            $warn[$noticeCount]['type'] = 'Notice';
            $warn[$noticeCount]['class'] = 'yello-notice';
            $noticeCount++;
        }

        $overview = array(
            'Entry' => $index,
            'Sender' => ($senderHostName) ? htmlspecialchars($senderHostName) : 'Not available',
            'Received By' => ($by) ? htmlspecialchars($by) : 'Not available',
            'Receive Times' => ($time) ? $time : 'Not available',
            'Duration' => $duration
        );
        
        $details[$entryKey]=htmlspecialchars($string);
        $details['Sender']=($senderName) ? htmlspecialchars($senderName) : 'Not available';
        $details['Sender Host Name']=($senderHostName) ? htmlspecialchars($senderHostName) : 'Not available';
        $details['Sender IP Address']=($senderIp) ? $senderIp : 'Not available';
        if(trim($senderAdreess))
        $details['Sender (from)']=htmlspecialchars($senderAdreess);
        $details['Received By']=($by) ? htmlspecialchars($by) : 'Not available';
        $details['Received With']=($With) ? htmlspecialchars($With) : '';
        $details['Receive Times']=($time) ? $time : 'Not available';
        $details['Receive Duration']=$duration;
        $details['Received For']= htmlspecialchars($for);
        $details['for ext']= htmlspecialchars($byExt);
        $details['msg']=$warn;

        return array($overview, $details);
    }

    /**
     *
     * @return string (in UTF-8 format)
     * @throws Exception if a subject header is not found
     */
    public function getSubject() {
        if (!isset($this->rawFields['subject'])) {
            throw new Exception("Couldn't find the subject of the email");
        }

        $ret = '';

        if ($this->isImapExtensionAvailable) {
            foreach (imap_mime_header_decode($this->rawFields['subject']) as $h) { // subject can span into several lines
                $charset = ($h->charset == 'default') ? 'US-ASCII' : $h->charset;
                $ret .= iconv($charset, "UTF-8//TRANSLIT", $h->text);
            }
        } else {
            $ret = utf8_encode(iconv_mime_decode($this->rawFields['subject']));
        }

        return $ret;
    }

    /**
     *
     * @return array
     */
    public function getCc() {
        if (!isset($this->rawFields['cc'])) {
            return array();
        }
        return explode(',', $this->rawFields['cc']);
    }

    /**
     *
     * @return array
     * @throws Exception if a to header is not found or if there are no recipient
     */
    public function getTo() {
        if ((!isset($this->rawFields['to'])) || (!count($this->rawFields['to']))) {
            throw new Exception("Couldn't find the recipients of the email");
        }
        return explode(',', $this->rawFields['to']);
    }

    /**
     * return string - UTF8 encoded
     * 
     * Example of an email body
     * 
      --0016e65b5ec22721580487cb20fd
      Content-Type: text/plain; charset=ISO-8859-1
      Hi all. I am new to Android development.
      Please help me.
      --
      My signature
      email: myemail@gmail.com
      web: http://www.example.com
      --0016e65b5ec22721580487cb20fd
      Content-Type: text/html; charset=ISO-8859-1
     */
    public function getBody($returnType = self::PLAINTEXT) {
        $body = '';
        $detectedContentType = false;
        $contentTransferEncoding = null;
        $charset = 'ASCII';
        $waitingForContentStart = true;
        if ($returnType == self::HTML)
            $contentTypeRegex = '/^Content-Type: ?text\/html/i';
        else
            $contentTypeRegex = '/^Content-Type: ?text\/plain/i';

        // there could be more than one boundary
        preg_match_all('!boundary=(.*)$!mi', $this->emailRawContent, $matches);
        $boundaries = $matches[1];
        // sometimes boundaries are delimited by quotes - we want to remove them
        foreach ($boundaries as $i => $v) {
            $boundaries[$i] = str_replace(array("'", '"'), '', $v);
        }

        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {

                if (preg_match($contentTypeRegex, $line, $matches)) {
                    $detectedContentType = true;
                }

                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }
            } else if ($detectedContentType && $waitingForContentStart) {

                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }

                if ($contentTransferEncoding == null && preg_match('/^Content-Transfer-Encoding: ?(.*)/i', $line, $matches)) {
                    $contentTransferEncoding = $matches[1];
                }

                if (self::isNewLine($line)) {
                    $waitingForContentStart = false;
                }
            } else {  // ($detectedContentType && !$waitingForContentStart)
                // collecting the actual content until we find the delimiter
                // if the delimited is AAAAA, the line will be --AAAAA  - that's why we use substr
                if (is_array($boundaries)) {
                    if (in_array(substr($line, 2), $boundaries)) {  // found the delimiter
                        break;
                    }
                }
                $body .= $line . "\n";
            }
        }
        if (!$detectedContentType) {
            // if here, we missed the text/plain content-type (probably it was
            // in the header), thus we assume the whole body is what we are after
            $body = implode("\n", $this->rawBodyLines);
        }
        // removing trailing new lines
        $body = preg_replace('/((\r?\n)*)$/', '', $body);
        if ($contentTransferEncoding == 'base64')
            $body = base64_decode($body);
        else if ($contentTransferEncoding == 'quoted-printable')
            $body = quoted_printable_decode($body);

        if ($charset != 'UTF-8') {
            // FORMAT=FLOWED, despite being popular in emails, it is not
            // supported by iconv
            $charset = str_replace("FORMAT=FLOWED", "", $charset);

            $bodyCopy = $body;
            $body = iconv($charset, 'UTF-8//TRANSLIT', $body);

            if ($body === FALSE) { // iconv returns FALSE on failure
                $body = utf8_encode($bodyCopy);
            }
        }
        return $body;
    }

    /**
     * @return string - UTF8 encoded
     * 
     */
    public function getPlainBody() {
        return $this->getBody(self::PLAINTEXT);
    }

    /**
     * return string - UTF8 encoded
     */
    public function getHTMLBody() {
        return $this->getBody(self::HTML);
    }

    /**
     * N.B.: if the header doesn't exist an empty string is returned
     *
     * @param string $headerName - the header we want to retrieve
     * @return string - the value of the header
     */
    public function getHeader($headerName) {
        $headerName = strtolower($headerName);
        if (isset($this->rawFields[$headerName])) {
            return $this->rawFields[$headerName];
        }
        return '';
    }

    /**
     * 
     * @return array - all header values
     */
    public function getAllHeader() {
        $senderDetails;
        $index = 1;
        $countOf = count($this->recivedHeaders);
        foreach (array_reverse($this->recivedHeaders) as $value) {
            $senderDetails[] = $this->get_header_data($value, 'from', 'by', $index, $countOf);
            $index++;
        }

        
        $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['key'] = details;
        $this->rawHeaderFields[$this->rawHeaderFieldsIndex]['value'] = $senderDetails;
//    print_r($this->rawHeaderFields);
//    die();
        return $this->rawHeaderFields;
    }

    /**
     *
     * @param string $line
     * @return boolean
     */
    public static function isNewLine($line) {
        $line = str_replace("\r", '', $line);
        $line = str_replace("\n", '', $line);
        return (strlen($line) === 0);
    }

    /**
     *
     * @param string $line
     * @return boolean
     */
    private function isLineStartingWithPrintableChar($line) {
        return preg_match('/^[A-Za-z]/', $line);
    }

}

?>