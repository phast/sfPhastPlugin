<?php

class sfPhastWebResponse extends sfWebResponse{

    /**
     * Retrieves a normalized Header.
     *
     * @param  string $name  Header name
     *
     * @return string Normalized header
     */
    protected function normalizeHeaderName($name)
    {
        return preg_replace_callback('/\-(.)/', function($match){return '-'.strtoupper($match[1]);}, strtr(ucfirst(strtolower($name)), '_', '-'));
    }

}