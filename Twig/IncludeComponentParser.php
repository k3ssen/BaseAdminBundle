<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Twig;

/**
 * Overrides twig's include parser to enable using twig-components.
 * Additionally, component argumentNames can be provided as if a method were used.
 * Example:
 *      {% include 'some/path/to/my_component.html.twig' with {title: 'Some Title', bool_var: true} %}
 *      becomes:
 *      {% include my_component('Some Title', true) %}
 *
 *      For this example to happen the components-array in the constructor must contain the following component:
 *      new Component('my_component', 'some/path/to/my_component.html.twig', ['title', 'bool_var'])
 */
class IncludeComponentParser extends \Twig_TokenParser_Include
{
    /** @var array|Component[] */
    protected $components;

    public function __construct(array $components = [])
    {
        $this->components = $components;
    }

    public function parse(\Twig_Token $token)
    {
        $this->skipObjectPart();
        if ($component = $this->getMatchingComponent()) {
            $this->replaceComponentNameWithTemplate($component);
            $this->replaceArguments($component);
        }
        return parent::parse($token);
    }

    protected function getMatchingComponent(): ?Component
    {
        $currentToken = $this->parser->getStream()->getCurrent();
        // If the token is a name, then search components by name
        if ($currentToken->getType() === \Twig_Token::NAME_TYPE) {
            $componentName = $currentToken->getValue();
            foreach ($this->components as $component) {
                if ($component->getName() === $componentName) {
                    return $component;
                }
            }
            return null;
        }
        //If no name is used, then search components by twig template.
        foreach ($this->components as $component) {
            if ($component->getTwigTemplatePath() === $currentToken->getValue()) {
                return $component;
            }
        }
        return null;
    }

    /**
     * For auto-completion and references, mapping an object to include could be helpful.
     * E.G. By mapping ComponentsAsMethods to 'include', using "{% include." gives auto-completion
     */
    protected function skipObjectPart()
    {
        $stream = $this->parser->getStream();
        if ($stream->getCurrent()->test(\Twig_Token::PUNCTUATION_TYPE, '.')) {
            $stream->next();
        }
    }

    protected function replaceComponentNameWithTemplate(Component $component)
    {
        $currentToken = $this->parser->getStream()->getCurrent();
        $newToken = new \Twig_Token(\Twig_Token::STRING_TYPE, $component->getTwigTemplatePath(), $currentToken->getLine());
        $this->replaceStreamToken($currentToken, $newToken);
    }

    /**
     * If arguments are passed (similar to using a method), these need to be replaced by 'with {param: value, etc..}'
     * in order to be compatible with the original include-parser.
     *
     * For example, this method replaced this:
     *      {% include some_component(value1, value2) %}
     *      with this:
     *      {% include some_component with {arg1: value2, arg2: value2} %}
     *      where arg1 and arg2 are taken from the argumentNames in Component.
     *
     * @param Component $component
     */
    protected function replaceArguments(Component $component)
    {
        $stream = $this->parser->getStream();
        $lookupToken = $stream->look(1);
        if ($lookupToken->test(\Twig_Token::PUNCTUATION_TYPE) === false) {
            return;
        }
        $argumentNames = $component->getArgumentNames();
        if (count($argumentNames) === 0) {
            return;
        }
        $this->injectAfterToken([
            new \Twig_Token(\Twig_Token::PUNCTUATION_TYPE, '{', $lookupToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, $argumentNames[0], $lookupToken->getLine()),
            new \Twig_Token(\Twig_Token::PUNCTUATION_TYPE, ':', $lookupToken->getLine()),
        ], $lookupToken);
        $this->replaceStreamToken($lookupToken, new \Twig_Token(\Twig_Token::NAME_TYPE, 'with', $lookupToken->getLine()));

        $n = 4;
        $subLevel = 0;
        $argumentNr = 0;
        while ($subLevel >= 0) {
            $nextLookupToken = $stream->look($n+=1);
            if ($nextLookupToken->test(\Twig_Token::PUNCTUATION_TYPE, ['[', '(', '{'])) {
                $subLevel++;
                continue;
            }
            if ($nextLookupToken->test(\Twig_Token::PUNCTUATION_TYPE, [']', ')', '}'])) {
                $subLevel--;
                continue;
            }
            if ($subLevel > 0) {
                continue;
            }
            if ($nextLookupToken->test(\Twig_Token::PUNCTUATION_TYPE, ',')) {
                $argumentName = $argumentNames[$argumentNr+=1] ?? null;
                if (!$argumentName) {
                    throw new \Twig_Error_Runtime(
                        sprintf(
                            'Too many arguments provided for component "%s"; this component takes up to %d arguments, but more were provided: "%s" in line %d',
                            $component->getName(),
                            count($argumentNames),
                            $stream->look($n+=1)->getValue(),
                            $stream->look($n)->getLine()
                        )
                    );
                }
                $this->injectAfterToken([
                    new \Twig_Token(\Twig_Token::NAME_TYPE, $argumentName, $nextLookupToken->getLine()),
                    new \Twig_Token(\Twig_Token::PUNCTUATION_TYPE, ':', $nextLookupToken->getLine()),
                ], $nextLookupToken);
                $n+=2; //Since 2 tokens are injected, we need to increment the number by an additional 2.
            }
        }
        // Since the while-loops breaks when the last punctuation_type is found, we know the $nextLookupToken is this last punctuation token.
        // To make sure this token is compatible with the 'with', this token is replaced by '}'
        $this->replaceStreamToken($nextLookupToken, new \Twig_Token(\Twig_Token::PUNCTUATION_TYPE, '}', $nextLookupToken->getLine()));
    }

    protected function replaceStreamToken(\Twig_Token $currentToken, \Twig_Token $newToken)
    {
        $tokens = $this->getStreamTokens();
        $tokenIndex = array_search($currentToken, $tokens, true);
        $tokens[$tokenIndex] = $newToken;
        $this->overwriteStreamTokens($tokens);
    }

    /**
     * @param array|\Twig_Token[] $injectTokens
     * @param \Twig_Token $token
     */
    protected function injectAfterToken(array $injectTokens, \Twig_Token $token)
    {
        $tokens = $this->getStreamTokens();
        $tokenIndex = array_search($token, $tokens, true);
        array_splice( $tokens, $tokenIndex+1, 0, $injectTokens);
        $this->overwriteStreamTokens($tokens);
    }

    /**
     * @param array|\Twig_Token[] $injectTokens
     * @param \Twig_Token $token
     */
    protected function injectBeforeToken(array $injectTokens, \Twig_Token $token)
    {
        $tokens = $this->getStreamTokens();
        $tokenIndex = array_search($token, $tokens, true);
        array_splice( $tokens, $tokenIndex, 0, $injectTokens);
        $this->overwriteStreamTokens($tokens);
    }

    /**
     * @return \Twig_Token[]
     */
    protected function getStreamTokens(): array
    {
        return $this->getStreamTokensReflectionProperty()->getValue($this->parser->getStream());
    }

    /**
     * @param \Twig_Token[] $tokens
     */
    protected function overwriteStreamTokens(array $tokens)
    {
        $this->getStreamTokensReflectionProperty()->setValue($this->parser->getStream(), $tokens);
    }

    /**
     * The TokenStream normally only allows to manipulate/inject tokens at the current token.
     * In order to manipulate tokens at any position, we'll use a reflectionProperty for the tokens-property.
     *
     * @return \ReflectionProperty
     * @throws \ReflectionException
     */
    protected function getStreamTokensReflectionProperty(): \ReflectionProperty
    {
        if (isset($this->tokensReflectionProperty)) {
            return $this->tokensReflectionProperty;
        }
        $stream = $this->parser->getStream();
        $class = new \ReflectionClass($stream);
        $property = $class->getProperty("tokens");
        $property->setAccessible(true);
        return $this->tokensReflectionProperty = $property;
    }
}
