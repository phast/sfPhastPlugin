<?php


/**
 * A partial view that uses Twig as the templating engine.
 *
 * @package    sfTwigPlugin
 * @subpackage view
 * @author     Henrik Bjornskov <henrik@bearwoods.dk>
 */
class sfTwigPartialView extends sfPhastTwigView
{
    protected $partialVars = array();

    public function setPartialVars(array $variables)
    {
        $this->partialVars = $variables;
        $this->getAttributeHolder()->add($variables);
    }

    public function configure()
    {
        parent::configure();

        $this->setDecorator(false);
        $this->setDirectory($this->configuration->getTemplateDir($this->moduleName, $this->getTemplate()));

        if ('global' == $this->moduleName) {
            $this->setDirectory($this->configuration->getDecoratorDir($this->getTemplate()));
        }
    }

    public function getCache()
    {
    }


}