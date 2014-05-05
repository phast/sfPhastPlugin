<?php

/**
 * A view that uses Twig as the templating engine.
 *
 * @package    sfTwigPlugin
 * @subpackage view
 * @author     Henrik Bjornskov <henrik@bearwoods.dk>
 */
class sfTwigView extends sfPHPView
{
    protected $twig = null;
    protected $loader = null;
    protected $configuration = null;
    protected $extension = '.twig';

    public function configure()
    {
        parent::configure();
        $this->configuration = $this->context->getConfiguration();

        $this->loader = new Twig_Loader_Filesystem(array());

        $this->twig = new sfTwigEnvironment($this->loader, array(
            'cache' => sfConfig::get('sf_template_cache_dir'),
            'debug' => sfConfig::get('sf_debug', false),
            'sf_context' => $this->context,
        ));

        if ($this->twig->isDebug()) {
            $this->twig->enableAutoReload();
            $this->twig->setCache(null);
        }

        $this->loadExtensions();
        $this->twig->addExtension(new Twig_Extension_Escaper(false));
    }

    public function getEngine()
    {
        return $this->twig;
    }

    protected function loadExtensions()
    {
        $prefixes = array_merge(array('Helper', 'Url', 'Asset', 'Tag', 'Escaping', 'Partial', 'I18N'), sfConfig::get('sf_standard_helpers'));

        foreach ($prefixes as $prefix) {
            $class_name = $prefix . '_Twig_Extension';
            if (class_exists($class_name)) {
                $this->twig->addExtension(new $class_name());
            }
        }

        $this->configuration->loadHelpers($prefixes);

        foreach (sfConfig::get('sf_twig_extensions', array()) as $extension) {
            if (!class_exists($extension)) {
                throw new InvalidArgumentException(sprintf('Unable to load "%s" as an Twig_Extension into Twig_Environment', $extension));
            }

            $this->twig->addExtension(new $extension());
        }
    }

    protected function renderFile($file)
    {
        if (sfConfig::get('sf_logging_enabled', false)) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Render "%s"', $file))));
        }

        $this->loader->setPaths((array)realpath(dirname($file)));

        $event = $this->dispatcher->filter(new sfEvent($this, 'template.filter_parameters'), $this->attributeHolder->getAll());

        return $this->twig->loadTemplate(basename($file))->render($event->getReturnValue());
    }

    protected function decorate($content)
    {
        if (sfConfig::get('sf_logging_enabled')) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Decorate content with "%s/%s"', $this->getDecoratorDirectory(), $this->getDecoratorTemplate()))));
        }

        $attributeHolder = $this->attributeHolder;

        $this->attributeHolder = $this->initializeAttributeHolder(array('sf_content' => new sfOutputEscaperSafe($content)));
        $this->attributeHolder->set('sf_type', 'layout');
        $this->attributeHolder->add($attributeHolder->getAll());

        if (!is_readable($this->getDecoratorDirectory() . '/' . $this->getDecoratorTemplate())) {
            throw new sfRenderException(sprintf('The decorator template "%s" does not exist or is unreadable in "%s".', $this->decoratorTemplate, $this->decoratorDirectory));
        }

        $ret = $this->renderFile($this->getDecoratorDirectory() . '/' . $this->getDecoratorTemplate());

        $this->attributeHolder = $attributeHolder;

        return $ret;
    }
}