<?php
/**
 * this file is insipred by the original Yii \yii\web\UrlRule
 */

namespace dolphiq\redirect\helpers;

/**
 * @inheritdoc
 *
 */
class UrlRule extends \yii\web\UrlRule
{


    // Properties
    // =========================================================================

    /**
     * @var array Parameters that should be passed to the controller.
     */
    public $params = [];

    // Public Methods
    // =========================================================================

    public function parseRequestParams($request)
    {
        $pathInfo = $request->getPathInfo();
        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }

        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }

        $matches = $this->substitutePlaceholderNames($matches);

        foreach ($this->defaults as $name => $value) {
            if (!isset($matches[$name]) || $matches[$name] === '') {
                $matches[$name] = $value;
            }
        }

        $params = $this->defaults;
        $tr = [];
        foreach ($matches as $name => $value) {
            if (isset($this->getParamRules()[$name])) {
                $params[$name] = $value;
            }
        }

        $this->params = $params;
        return $this->params;
    }
}
