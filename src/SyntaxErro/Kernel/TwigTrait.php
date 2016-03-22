<?php

namespace SyntaxErro\Kernel;

/**
 * Trait TwigTrait
 *
 * Use this trait for rendering from templates.
 * @package SyntaxErro\Kernel
 */
trait TwigTrait
{
    /**
     * @var \Twig_Environment|null
     */
    private $twig;

    /**
     * Call this func first - before use render method. It's for initializing Twig engine.
     *
     * @return $this
     */
    public function initTwig()
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."tpl");
        $cachePath = __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."cache";
        $this->twig = new \Twig_Environment($loader, [
            'cache' => $cachePath
        ]);
        return $this;
    }

    /**
     * Render string from template and transfer variables.
     *
     * @param $fileName
     * @param array $variables
     * @return string
     */
    public function render($fileName, array $variables)
    {
        if(!($this->twig instanceof \Twig_Environment)) throw new \RuntimeException("Not initialized Twig engine. Call to initTwig func, please.");
        return $this->twig->render($fileName, $variables);
    }
}
