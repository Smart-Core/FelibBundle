<?php

namespace SmartCore\Bundle\FelibBundle\Twig\Node;

class FelibNode extends \Twig_Node
{
    public function __construct(\Twig_Node_Expression $expr, $lineno = 0, $tag = null)
    {
        parent::__construct(array('expr' => $expr), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param \Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        // noop as this node is just a marker for FelibNodeVisitor
    }
}
