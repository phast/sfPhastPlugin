<?php

/**
 * Symfony specific Twig Environment.
 *
 * Enables the user to get sfContext without the getInstance method and later
 * on enable more symfony goodness
 *
 * @package    sfTwigPlugin
 * @subpackage environment
 * @author     Henrik Bjornskov <henrik@bearwoods.dk>
 */
class sfTwigEnvironment extends Twig_Environment
{

    protected $context = null;

    public function __construct(Twig_LoaderInterface $loader, array $options = array())
    {
        parent::__construct($loader, $options);

        $this->context = isset($options['sf_context']) && $options['sf_context'] instanceof sfContext ? $options['sf_context'] : sfContext::getInstance();
    }

    public function getContext()
    {
        return $this->context;
    }
}
