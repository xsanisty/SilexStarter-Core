<?php

namespace SilexStarter\Asset;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simple asset manager for queueing and rendering asset link.
 */
class AssetManager
{
    protected $assetBasePath;
    protected $aliases;
    protected $request;
    protected $js   = [];
    protected $css  = [];
    protected $jsVar= [];

    public function __construct(RequestStack $request, $assetBasePath = '')
    {
        $this->request  = $request;
        $this->assetBasePath = $assetBasePath;
    }

    public function setBasePath($assetBasePath)
    {
        $this->assetBasePath = $assetBasePath;
    }

    public function getBasePath()
    {
        return $this->assetBasePath;
    }

    /**
     * Export server variable into javascript world to be rendered later.
     *
     * @param   string|array    $varName   Variable name to be exported
     * @param   mixed           $varValue  Variable value
     */
    public function exportVariable($varName, $varValue = null)
    {
        if (is_array($varName)) {
            $this->jsVar = array_merge($this->jsVar, $varName);
        } else {
            $this->jsVar[$varName] = $varValue;
        }
    }

    /**
     * Render exported variable into valid javascript variable under specified namespace.
     *
     * @return string   Json encoded variable with specified variable name, default is "global = {}"
     */
    public function renderExportedVariable($namespace = 'global')
    {
        return '<script type="text/javascript">' . 'var ' . $namespace . ' = ' . json_encode($this->jsVar) .';' . '</script>';
    }

    /**
     * Queue the javascript file into the asset manager.
     *
     * @param string $jsfile the javascript file need to be loaded,
     */
    public function js($jsfile)
    {
        if (is_array($jsfile)) {
            foreach ($jsfile as $js) {
                $this->js[] = $js;
            }
        } else {
            $this->js[] = $jsfile;
        }
    }

    /**
     * Queue the css file into the asset manager.
     *
     * @param string $cssfile the css file need to be loaded,
     */
    public function css($cssfile)
    {
        if (is_array($cssfile)) {
            foreach ($cssfile as $css) {
                $this->css[] = $css;
            }
        } else {
            $this->css[] = $cssfile;
        }
    }

    /**
     * alias for js method.
     */
    public function javascript($jsfile)
    {
        $this->js($jsfile);
    }

    /**
     * alias for css method.
     */
    public function stylesheet($cssfile)
    {
        $this->css($cssfile);
    }

    /**
     * Queue the asset file into the asset manager.
     *
     * @param string $assetFile the asset file need to be loaded,
     */
    public function load($assetFile)
    {
        if ('.js' == substr($assetFile, -3, 3)) {
            $this->js($assetFile);
        }

        if ('.css' == substr($assetFile, -4, 4)) {
            $this->css($assetFile);
        }
    }

    /**
     * Render js path into proper <script> tag.
     *
     * @param array|string|null $file The file or array of file need to be rendered
     *
     * @return string Html link tag to specified js file
     */
    public function renderJs($file = null)
    {
        $tagFormat = "<script src=\"%s\"></script>\n";

        $file = ($file) ? $file : $this->js;

        return $this->render($file, $tagFormat);
    }

    /**
     * Render css path into proper <link> tag.
     *
     * @param array|string|null $file The file or array of file need to be rendered
     *
     * @return string Html link tag to specified css file
     */
    public function renderCss($file = null)
    {
        $tagFormat = "<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\">\n";

        $file = ($file) ? $file : $this->css;

        return $this->render($file, $tagFormat);
    }

    /**
     * Render image file into proper img tag
     * @param  string $image   The image location
     * @param  array  $options Option for the image tag, class, style, and id
     *
     * @return string          rendered img tag
     */
    public function renderImage($image, array $options = [])
    {
        $tagFormat = "<img src=\"%s\" class=\"%s\" id=\"%s\" style=\"%s\">\n";

        return sprintf(
            $tagFormat,
            $this->resolvePath($image),
            isset($options['class']) ? $options['class'] : '',
            isset($options['id']) ? $options['id'] : '',
            isset($options['style']) ? $options['style'] : ''
        );
    }

    /**
     * Render the path, or array of path into given tag format.
     *
     * @param array|string $files     the asset path, or array of asset path
     * @param string       $tagFormat the tag format used for rendering the asset tag
     *
     * @return string the formatted tag with proper asset path
     */
    protected function render($files, $tagFormat)
    {
        /* if file is array of file, render each file respectively */
        if (is_array($files)) {
            $tag = '';
            foreach ($files as $file) {
                $tag .= sprintf($tagFormat, $this->resolvePath($file));
            }

            return $tag;
        }

        /* if file is single file, render immediately */
        return sprintf($tagFormat, $this->resolvePath($files));
    }

    /**
     * Resolve the public path of the asset, and determine if it link to external server or not.
     *
     * @param string $file     The asset file need to be resolved
     * @param bool   $absolute Flag to generate web absolute path or relative path
     *
     * @return string The proper path to the asset file
     */
    public function resolvePath($file, $absolute = false)
    {
        /* if refer to external path, return immediately (begin with //, http://, https://) */
        if ('http:' == substr($file, 0, 5) || '//' == substr($file, 0, 2) || 'https:' == substr($file, 0, 6)) {
            return $file;
        }

        $namespace = [];
        preg_match("/@(.*?)\//s", $file, $namespace);

        /* if namespace exists, resolve the namespace */
        if ($namespace) {
            $file = str_replace($namespace[0], $this->assetBasePath.'/'.$namespace[1].'/', $file);
        } else {
            $file = $this->assetBasePath.'/'.$file;
        }

        return  (
                    ($absolute)
                    ? $this->request->getCurrentRequest()->getScheme().'://'.$this->request->getCurrentRequest()->getHost()
                    : ''
                ).
                $this->request->getCurrentRequest()->getBasePath() .'/'. ltrim($file, '/');
    }
}
