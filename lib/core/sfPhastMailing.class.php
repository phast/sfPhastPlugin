<?php

class sfPhastMailing{

    /**
     * @param $recipient
     * @param $subject
     * @param $template
     * @param array $parameters
     */
    public static function send($recipient, $subject, $template, $parameters = []){
        $from = self::processAddress((isset($parameters['from']) && $parameters['from']) ? $parameters['from'] : sfConfig::get('app_mailing_from'));
        $recipient = self::processAddress($recipient);
        $body = self::render($template, $parameters);

        $context = sfContext::getInstance();

        $mailer = $context->getMailer();
        if(($transport = $mailer->getTransport()) instanceOf Swift_MailTransport){
			$transport->setExtraParams(null);
		}
		$mail = $mailer->compose($from, $recipient, $subject, $body)->setContentType('text/html');
		if(isset($parameters['attach']) && is_array($parameters['attach'])){
            foreach($parameters['attach'] as $key => $value){
                $mail->attach(
					is_integer($key)
						? Swift_Attachment::fromPath($value)
						: Swift_Attachment::fromPath($key)->setFilename($value)
				);
            }
        }
        return $mailer->send($mail);
    }

    public static function render($template, $parameters = []){
        if(is_array($template)){
            if($key = key($template)){
                $decorator = $template[$key];
                $template = $key;
            }else{
                $decorator = null;
                $template = $template[$key];
            }

            $context = sfContext::getInstance();
            $view = new sfTwigView($context, '', '', '');
            $view->setExtension('.twig');
            $view->setTemplate(sfConfig::get('sf_lib_dir') . '/mailing/templates/' . $template . '.twig');
            $view->getAttributeHolder()->add($parameters);
            if($decorator){
                $view->setDecoratorTemplate('_' . $decorator);
                $view->setDecoratorDirectory(sfConfig::get('sf_lib_dir') . '/mailing/templates');
            }

            return $view->render();

        }else{
            return $template;
        }

    }

    public static function push($recipient, $subject, $template, $parameters = []){
        $from = self::processAddress(isset($parameters['from']) ? $parameters['from'] : '', true);
        $priority = isset($parameters['priority']) ? $parameters['priority'] : MailingMessage::PRIORITY_NORMAL;


        if(is_array($recipient) && count($recipient) > 1){
            foreach($recipient as $email => $title){
                self::push([$email => $title], $subject, $template, $parameters);
            }

        }else{

            $recipient = self::processAddress($recipient, true);

            $message = new MailingMessage();
            $message->setMode(isset($parameters['mode']) ? $parameters['mode'] : MailingMessage::MODE_EMAIL);
            $message->setFrom($from);
            $message->setTo($recipient);
            $message->setSubject($subject);
            $message->setBody(self::render($template, $parameters));
            $message->setPriority($priority);
            $message->save();

            if(isset($parameters['priority']) && $parameters['priority'] == MailingMessage::PRIORITY_INSTANT){
                $message->send();
            }

        }

    }

    public static function pushAndSend($recipient, $subject, $template, $parameters = []){
        $parameters['priority'] = MailingMessage::PRIORITY_INSTANT;
        self::push($recipient, $subject, $template, $parameters);
    }

    public static function sendSms($phone, $message, $parameters = []){
        return MailingMessage::sendSms($phone, $message, $parameters);
    }

    public static function pushSms($phone, $message, $parameters = []){
        $parameters['mode'] = MailingMessage::MODE_SMS;
        self::push($phone, '', $message, $parameters);
    }

    public static function pushSmsAndSend($phone, $message, $parameters = []){
        $parameters['mode'] = MailingMessage::MODE_SMS;
        self::push($phone, '', $message, $parameters);
    }

    protected static function processAddress($address, $pack = false){
        return true === $pack
            ? (is_array($address)
                ? $address[key($address)] .' <'. key($address) .'>'
                : $address
            )
            : (is_array($address)
                ? $address
                : preg_match('#^([^<]+)\s<([^>]+)>$#ui', $address, $match)
                    ? [$match[2] => $match[1]]
                    : $address
            );
    }

}
