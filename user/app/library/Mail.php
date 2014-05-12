<?php
/*
 * User send mail helper
 */

class Mail extends \Phalcon\Mvc\User\Component {

    private function _getVerificationContent($username, $id) {
        $url = $this->config->url . 'session/verify?username=' . $username . '&id=' . $id;
        $content = '<!DOCTYPE html><html lang="en"><head><meta charset="utf_8" /><title>Verification mail</title></head><body>';
        $content .= '<p>Dear Media Cloud Customer,</p>';
        $content .= '<p>We have received a request to authorize this email address for use with Media Cloud.
		If you requested this verification, please go to the following URL to confirm that you are authorized to use this email address:</p>';
        $content .= '<p><a href="' . $url . '">' . $url . '</a></p>';
        $content .= '<p>Sincerely,</p>';
        $content .= '</body></html>';

        return $content;
    }

    private function _getResetPasswordContent($hash) {
        $url = $this->config->url . 'session/reset/' . $hash;
        $content = '<!DOCTYPE html><html lang="en"><head><meta charset="utf_8" /><title>Reset Password mail</title></head><body>';
        $content .= '<p>Dear Media Cloud Customer,</p>';
        $content .= '<p>Someone (hopefully you) has requested a password reset for your Heroku account.  Follow the link below to set a new password:</p>';
        $content .= '<p><a href="' . $url . '">' . $url . '</a></p>';
        $content .= "<p>If you don't wish to reset your password, disregard this email and no action will be taken.</p>";
        $content .= '</body></html>';

        return $content;
    }

    private function _getJoinGroupContent($ownerName, $groupName, $userName, $memberId, $groupId, $token) {
        $acceptLink = $this->config->url . 'session/verifymember/' . $groupId . '/' . $memberId . '?action=1&id=' . $token;
        $rejectLink = $this->config->url . 'session/verifymember/' . $groupId . '/' . $memberId . '?action=0&id=' . $token;

        $content = "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"utf_8\" /><title>Join group request mail</title></head><body>";
        $content .= "<p>Dear {$ownerName},</p>";
        $content .= "<p>User <span style=\"color:#d43f3a;\">{$userName}</span> has requested to join your group (<span style=\"color:#d43f3a;\">{$groupName}</span>). Please, click on either the link below to either approve or reject the request:</p>";
        $content .= "<ul>";
        $content .= "<li><a href=\"{$acceptLink}\" style=\"text-decoration='none';\">Click on this link to ACCEPT the request to join group from user {$userName}</a></li>";
        $content .= "<li><a href=\"{$rejectLink}\" style=\"text-decoration='none';\">Click on this link to REJECT the request to join group from user {$userName}</a></li>";
        $content .= "</ul>";
        $content .= "<p>Thank you.</p>";
        $content .= "</body></html>";

        return $content;
    }

    public function sendVerificationMail($to, $username, $id) {
        $subject = "Media Cloud verification";
        $message = $this->_getVerificationContent($username, $id);

        return $this->sendMail($to, $subject, $message);
    }

    public function sendResetPasswordMail($to, $hash) {
        $subject = "Reset your Media Cloud password";
        $message = $this->_getResetPasswordContent($hash);

        return $this->sendMail($to, $subject, $message);
    }

    public function sendJoinRequestMail($to, $ownerName, $groupName, $userName, $memberId, $groupId, $token) {
        $subject = "Media Cloud notification";
        $message = $this->_getJoinGroupContent($ownerName, $groupName, $userName, $memberId, $groupId, $token);
        
        return $this->sendMail($to, $subject, $message);
    }

    public function sendMail($to, $subject, $message) {
        $mailSettings = $this->config->mail;

        $header = "From: " . $mailSettings->from . "\r\n";
        $header.= $mailSettings->mime . "\r\n";
        $header.= $mailSettings->contentType . "\r\n";
        $header.= $mailSettings->priority . "\r\n";

        return mail($to, $subject, $message, $header);
    }

}
