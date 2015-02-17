<?php

namespace SmartCore\Bundle\FelibBundle\Twig\TokenParser;

use SmartCore\Bundle\FelibBundle\Twig\Node\FelibNode;

class FelibTokenParser extends \Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     *
     * @return \Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        //$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new FelibNode($expr, $token->getLine(), $this->getTag());
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'felib';
    }
}
