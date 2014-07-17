<?php

class sfPhastMailing{

    /**
     * @param $recipient
     * @param $subject
     * @param $template
     * @param array $parameters
     */
    public static function send($recipient, $subject, $template, $parameters = []){
        $from = isset($parameters['from']) ? $parameters['from'] : sfConfig::get('app_mailing_from');
        $body = self::render($template, $parameters);

        $context = sfContext::getInstance();

        $mailer = $context->getMailer();
        if(($transport = $mailer->getTransport()) instanceOf Swift_MailTransport){
			$transport->setExtraParams(null);
		}
		$mail = $mailer->compose($from, $recipient, $subject, $body);
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
            $view->getParameterHolder()->add($parameters);
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
        $from = isset($parameters['from']) ? $parameters['from'] : sfConfig::get('app_mailing_from');
        $priority = isset($parameters['priority']) ? $parameters['priority'] : MailingMessage::PRIORITY_NORMAL;

        $message = new MailingMessage();
        $message->setMode(1);
        $message->setFrom($from);
        $message->setTo($recipient);
        $message->setSubject($subject);
        $message->setBody(self::render($template, $parameters));
        $message->setPriority($priority);
        $message->save();

        return $message;
    }

    public static function pushAndSend($recipient, $subject, $template, $parameters = []){
        $parameters['priority'] = MailingMessage::PRIORITY_INSTANT;
        $message = self::push($recipient, $subject, $template, $parameters);
        $message->send();
    }


}