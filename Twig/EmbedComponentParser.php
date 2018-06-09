<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Twig;

use Twig\TokenParser\EmbedTokenParser;

/**
 * Overrides twig's embed parser to enable using twig-components for the following functionality:
 *  - allow using a component name instead of a twig template-path
 *  - enable passing arguments as if a method were used.
 *  - allow content without using any block - this content will be automatically wrapped by a main-block.
 *
 * Example:
 *      {% embed 'some/path/to/my_component.html.twig' with {title: 'Some Title', bool_var: true} %}
 *          {% block main %}
 *              <p>Some content here</p>
 *          {% endblock %}
 *      {% endembed %}
 * becomes:
 *      {% embed my_component('Some Title', true) %}
 *          <p>Some content here</p>
 *      {% endembed %}
 *
 *      For this example to happen the components-array in the constructor must contain the following component:
 *      new Component('my_component', 'some/path/to/my_component.html.twig', ['title', 'bool_var'])
 *
 * Note that the traditional embed can still be used and could even be combined with new functionality.
 * For example the following would also work:
 *      {% embed my_component with {bool_var: true} %}
 */
class EmbedComponentParser extends IncludeComponentParser
{
    // If you don't like 'embed' to be overwritten, you could use a different tag-name.
    protected CONST TAG_NAME = 'embed';

    protected $mainBlockName;

    public function __construct(array $components = [], string $mainBlockName = 'main')
    {
        parent::__construct($components);
        $this->mainBlockName = $mainBlockName;
    }

    public function parse(\Twig_Token $token)
    {
        $this->skipObjectPart();
        if ($component = $this->getMatchingComponent()) {
            $this->replaceComponentNameWithTemplate($component);
            $this->replaceArguments($component);
        }

        $this->wrapBlockIfMissing();

        $embedParser = new EmbedTokenParser();
        $embedParser->setParser($this->parser);
        return $embedParser->parse($token);
    }

    public function getEndTag()
    {
        return 'end'.$this->getTag();
    }

    public function getTag()
    {
        return static::TAG_NAME;
    }

    /**
     * Check if the initial content is wrapped inside a block. If this content is not inside a block, then inject
     * tokens to wrap this content inside a 'main' block
     */
    protected function wrapBlockIfMissing()
    {
        $stream = $this->parser->getStream();

        $n = 0;
        // Make sure we increment 'n' to right after the block end: %}
        while (true) {
            $startToken = $stream->look($n+=1);
            if ($startToken->test(\Twig_Token::BLOCK_END_TYPE)) {
                break;
            }
        }
        // Normally the following two tokens after the block end should decide whether a '{% block' is used
        $nextToken = $stream->look($n + 1);
        $secondNextToken = $stream->look($n + 2);
        // However, empty spaced can result in a token as well, so if that is the case we shift the next and second token
        if ($nextToken->test(\Twig_Token::TEXT_TYPE) && trim($nextToken->getValue()) === '') {
            $nextToken = $stream->look($n + 2);
            $secondNextToken = $stream->look($n + 3);
        }

        // If these next two tokens define a '{% block', then further steps aren't needed, since we then assume that
        // the content has been wrapped already
        if ($nextToken->test(\Twig_Token::BLOCK_START_TYPE) && $secondNextToken->test(\Twig_Token::NAME_TYPE, 'block')) {
            return;
        }

        // If no starting '{% block' was set, then we need to wrap this block, but first we need to decide until which
        // token we need to wrap everything inside this block.
        $subComponentLevel = 0;
        do {
            $streamToken = $stream->look($n+=1);
            // Whenever a token indicates the start of a new embed-component, we increment the $subComponentLevel
            if ($streamToken->test(\Twig_Token::NAME_TYPE, $this->getTag())) {
                $subComponentLevel++;
                // Whenever a token indicates the end of a new embed-component, we decrement the $subComponentLevel
            } elseif ($streamToken->test(\Twig_Token::NAME_TYPE, $this->getEndTag())) {
                $subComponentLevel--;
            }
            // The final token will be decided when we either find a starting '{% block' or when the $subComponentLevel
            // becomes lower than 0 (since that would indicate the end of the actual component we're dealing with.
            if ($subComponentLevel === 0 && $streamToken->test(\Twig_Token::NAME_TYPE, 'block')) {
                break;
            }
        } while ($subComponentLevel >= 0);

        // After the loop found the final token, the $n is 1 position ahead, since we tested for 'block' or 'endembed' but
        // but right before that we need to consider the token's BLOCK_END_TYPE
        $this->wrapMainBlock($startToken, $stream->look($n-1));
    }

    protected function wrapMainBlock(\Twig_Token $startToken, \Twig_Token $endToken)
    {
        $this->injectAfterToken([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $startToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'block', $startToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, $this->mainBlockName, $startToken->getLine()),
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $startToken->getLine()),
        ], $startToken);

        $this->injectBeforeToken([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $endToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'endblock', $endToken->getLine()),
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $endToken->getLine()),
        ], $endToken);
    }

    protected function findEndToken(): \Twig_Token
    {
        $currentToken = $this->parser->getStream()->getCurrent();
        $tokens = $this->getStreamTokens();
        //track subcomponents to prevent mistaking an endtag of a subcomponent to be the endtag of the currentToken
        $subComponentLevel = 0;
        $nrOfTokens = count($tokens);
        $startIndex = array_search($currentToken, $tokens);
        for ($i = $startIndex; $i < $nrOfTokens; $i++) {
            $token = $tokens[$i];
            if ($token->test(\Twig_Token::NAME_TYPE, $this->getTag())) {
                $subComponentLevel++;
            } elseif ($token->test(\Twig_Token::NAME_TYPE, $this->getEndTag())) {
                $subComponentLevel--;
                if ($subComponentLevel < 0) {
                    return $token;
                }
            }
        }
        throw new \Twig_Error_Runtime(sprintf('Could not find end tag "%s"', $this->getEndTag()));
    }
}
