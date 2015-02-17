<?php

namespace SmartCore\Bundle\FelibBundle\Twig;

use SmartCore\Bundle\FelibBundle\Service\FelibService;
use SmartCore\Bundle\FelibBundle\Twig\TokenParser\FelibTokenParser;

class FelibExtension extends \Twig_Extension
{
    /**
     * @var FelibService
     */
    protected $felib;

    /**
     * @param FelibService $felibService
     */
    public function __construct(FelibService $felibService)
    {
        $this->felib = $felibService;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'felib_css'     => new \Twig_Function_Method($this, 'getCss'),
            'felib_js'      => new \Twig_Function_Method($this, 'getJs'),
            'felib_use'     => new \Twig_Function_Method($this, 'call'),
            'felib_get_all' => new \Twig_Function_Method($this, 'getAll'),
        ];
    }

    /**
     * Returns the token parser instance to add to the existing list.
     *
     * @return array An array of Twig_TokenParser instances
     */
    public function getTokenParsers()
    {
        return [
            // {% felib 'jquery' %}
            //new FelibTokenParser(),
        ];
    }

    /**
     * @param string $libName
     * @param string $version
     * @param string $media
     * @return FelibService
     */
    public function call($libName, $version = null)
    {
        return $this->felib->call($libName, $version);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->felib->all();
    }

    /**
     * @param string $libName
     * @param string $version
     * @param string $media
     */
    public function getCss($libName, $version = null, $media = 'all')
    {
        foreach ($this->felib->getCss($libName, $version) as $key => $file) {
            if ($key > 0) {
                echo '    ';
            }

            echo "<style media=\"$media\" type=\"text/css\"> @import url($file); </style>\n";
        }
    }

    /**
     * @param string $libName
     * @param string $version
     */
    public function getJs($libName, $version = null)
    {
        foreach ($this->felib->getJs($libName, $version) as $key => $file) {
            if ($key > 0) {
                echo '    ';
            }

            echo "<script src=\"$file\"></script>\n";
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'smart_core_felib_twig_extension';
    }
}
