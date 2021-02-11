<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\TokenParser;

use FSi\Bundle\DataSourceBundle\Twig\Node\DataSourceRouteNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class DataSourceRouteTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();
        $dataSource = $this->parser->getExpressionParser()->parseExpression();
        $route = $this->parser->getExpressionParser()->parseExpression();
        $additional_parameters = new ArrayExpression([], $stream->getCurrent()->getLine());

        if ($this->parser->getStream()->test(Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();

            if (
                $this->parser->getStream()->test(Token::PUNCTUATION_TYPE)
                || $this->parser->getStream()->test(Token::NAME_TYPE)
            ) {
                $additional_parameters = $this->parser->getExpressionParser()->parseExpression();
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new DataSourceRouteNode($dataSource, $route, $additional_parameters, $token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'datasource_route';
    }
}
