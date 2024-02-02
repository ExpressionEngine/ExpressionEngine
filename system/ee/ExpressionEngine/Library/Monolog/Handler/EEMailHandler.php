<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Handler;

use ExpressionEngine\Dependency\Monolog\Handler\MailHandler;
use ExpressionEngine\Dependency\Monolog\Formatter\LineFormatter;
use ExpressionEngine\Dependency\Monolog\Logger;

/**
 * Send alert via e-mail
 */
class EEMailHandler extends MailHandler
{
    public $requireBoot = true;

    protected $recepients = [];

    public function __construct($recepients = [], $level = Logger::DEBUG, bool $bubble = \true)
    {
        parent::__construct($level, $bubble);
        if (empty($recepients)) {
            $recepients[] = config_item('webmaster_email');
        }
        $this->recepients = $recepients;
    }

    public function addRecepient(string $email)
    {
        $this->recepients[] = $email;
    }

    protected function send(string $content, array $records): void
    {
        ee()->load->library('email');

        $subjectFormatter = new LineFormatter('%channel% %level_name%: %message%');
        $subject = substr($subjectFormatter->format($this->getHighestRecord($records)), 0, 78);
        $subject = substr($subject, 0, strrpos($subject, ' '));

        foreach ($this->recepients as $recepient) {
            ee()->email->EE_initialize();
            ee()->email->wordwrap = false;
            ee()->email->mailtype = 'html';
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
            ee()->email->to($recepient);
            ee()->email->reply_to(ee()->config->item('webmaster_email'));
            ee()->email->subject($subject);
            ee()->email->message($content);
            ee()->email->send();
        }
    }
}