<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Helper;

/**
 *
 * Helper to generate a complete element.
 *
 * @package Aura.Html
 *
 */
class Element extends AbstractHelper
{
    /**
     *
     * Returns any kind of complete HTML element with attributes.
     *
     * @param string $tag The tag to generate.
     *
     * @param string $content The element content
     *
     * @param array $attr Attributes for the tag.
     *
     * @return string
     *
     */
    public function __invoke($tag, $content, array $attr = array())
    {
        $attr = $this->escaper->attr($attr);
        $out = $attr ? "<{$tag} $attr>" : "<{$tag}>";
        $out .= $this->escaper->html($content);
        $out .= "</$tag>";
        return $out;
    }
}
