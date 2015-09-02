<?php

namespace SilexStarter\TwigExtension;

use SilexStarter\Menu\MenuManager;
use Twig_Extension;
use Twig_SimpleFunction;

class TwigTranslationExtension extends Twig_Extension
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getName()
    {
        return 'silex-starter-translator-ext';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('trans', [$this, 'translate'], []),
            new Twig_SimpleFunction('transchoice', [$this, 'translateChoice'], []),
        ];
    }

    public function translate()
    {
    }

    public function translateChoice()
    {
    }
}
