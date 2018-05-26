<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

use ReflectionClass;

/**
 * This component parser is an altered version of the 'embed' parser:
 * - Using a default block 'main_content', content can be embedded without using any block.
 *      Example:
 *          {% embed '/somedir/some_component.html/twig' %}{% block main_content %}text{% endblock %}{% endembed %}
 *          becomes:
 *          {% set '/somedir/some_component.html/twig' %}text{% endset %}
 * - By defining components, the compontName can be used instead of a tag.
 *      Example:
 *          {% set some_component %}text{% endset %}
 * - Defined components need to refer to a \Twig_SimpleFunction, parameters in that function are extracted and
 *   can be used in the component as well.
 *      Example:
 *          {% set some_component with {title: 'Some title'} %}text{% endset %}
 *          could be written as (if the referred method contains the $title parameter):
 *          {% set some_component('Some title') %}text{% endset %}
 */
class SetComponentParser extends \Twig_TokenParser_Include
{
    public const DEFAULT_MAIN_BLOCK = 'main_content';

    // The 'set' tag is used, because the symfony-plugin offers auto-complete for functions after '{% set'.
    protected $tagName = 'set';
    protected $mainBlock = self::DEFAULT_MAIN_BLOCK;

    protected $templateFile;
    protected $argumentNames;
    protected $functionArguments;

    protected $components;

    /** @var \Twig_SimpleFunction[] $twigFunctions */
    public function __construct(array $twigFunctions)
    {
        foreach ($twigFunctions as $template => $twigFunction) {
            if (is_int($template)) {
                continue;
            }
            $callable = $twigFunction->getCallable();
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
            $arguments  = $reflection->getParameters();
            if ($twigFunction->needsEnvironment()) {
                array_shift($arguments);
            }
            if ($twigFunction->needsContext()) {
                array_shift($arguments);
            }
            $argumentNames = [];
            if ($arguments) {
                $argumentNames = array_column($arguments, 'name');
            }
            $this->components[$twigFunction->getName()] = [$template, $argumentNames];
        }
    }

    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();

        // If we're not dealing with a component, use the Twig_TokenParser_Set instead
        if ($this->initComponent($stream) === false) {
            $coreSetParser = new \Twig_TokenParser_Set();
            $coreSetParser->setParser($this->parser);
            return $coreSetParser->parse($token);
        }
        // After component is initialized, step to the next token
        $stream->next();

        // Check if there are arguments to be parsed
        $this->setFunctionArguments($stream);

        $parent = new \Twig_Node_Expression_Constant($this->templateFile, $stream->getCurrent()->getLine());

        // Original code taken from \Twig_TokenParser_Embed
        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        // Variables can be fetched through 'with', like the original 'embed',
        // but call 'getVariables' to implement some additional ways of fetching variables.
        $variables = $this->getVariables($parent, $variables);

        $this->injectTokens($stream, $token);

        $module = $this->parser->parse($stream, function (\Twig_Token $token) {
            return $token->test('end'.$this->getTag());
        }, true);

        // override the parent with the correct one
        $module->setNode('parent', $parent);

        $this->parser->embedTemplate($module);

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new \Twig_Node_Embed(
            $module->getTemplateName(),
            $module->getAttribute('index'),
            $variables, $only,
            $ignoreMissing,
            $token->getLine(),
            $this->getTag()
        );
    }

    protected function injectTokens(\Twig_TokenStream $stream, \Twig_Token $token)
    {
        // Inject the 'mainBlock' if not found. This way we do not require a block to be defined
        if (!$this->hasMainContentBlock($stream)) {
            $this->wrapContentBlock($stream);
        }

        // Original code taken from \Twig_TokenParser_Embed
        // inject a fake parent to make the parent() function work
        $stream->injectTokens([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $token->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'extends', $token->getLine()),
            new \Twig_Token(\Twig_Token::STRING_TYPE, '__parent__', $token->getLine()),
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $token->getLine()),
        ]);
    }

    /**
     * Check initial tokens to check what component we're dealing with + set some variables depending on component.
     * If we're not dealing with a component, then return false.
     *
     * @param \Twig_TokenStream $stream
     * @return bool
     * @throws \Twig_Error_Syntax
     */
    protected function initComponent(\Twig_TokenStream $stream): bool
    {
        $value = $stream->getCurrent()->getValue();

        if ($stream->getCurrent()->getType() === \Twig_Token::STRING_TYPE) {
            $this->templateFile = $value;
            foreach ($this->components as $componentName => [$templateName, $argumentNames]) {
                if ($value === $templateName) {
                    $this->argumentNames = $argumentNames;
                    break;
                }
            }
            return true;
        }
        if (array_key_exists($value, $this->components)) {
            $this->templateFile = $this->components[$value][0];
            $this->argumentNames = $this->components[$value][1];
            // If an '=' is found right after the 'componentName', then the original setter-functionality should be used.
            // Therefore, return false to fallback to Twig_TokenParser_Set
            if ($stream->look(1)->test(\Twig_Token::OPERATOR_TYPE, '=')) {
                return false;
            }
            return true;
        }
        return false;
    }

    protected function setFunctionArguments(\Twig_TokenStream $stream)
    {
        if ($stream->getCurrent()->test(\Twig_Token::PUNCTUATION_TYPE, '(')) {
            $stream->next();
            $functionArguments[] = $this->parser->getExpressionParser()->parseExpression();
            while ($stream->getCurrent()->test(\Twig_Token::PUNCTUATION_TYPE, ',')) {
                $stream->next();
                $functionArguments[] = $this->parser->getExpressionParser()->parseExpression();
            }
            if ($stream->getCurrent()->test(\Twig_Token::PUNCTUATION_TYPE, ')')){
                $stream->next();
                $this->functionArguments = $functionArguments;
            }
        } else {
            $this->functionArguments = [];
        }
    }

    /**
     * @param \Twig_Node $originalParent the initial node
     * @param \Twig_Node_Expression_Array $withVariables set of variables extracted from 'with', e.g. "with {param:value}"
     * @return \Twig_Node_Expression_Array
     */
    protected function getVariables(\Twig_Node $originalParent, $withVariables)
    {
        $variables = null;
        //If templateFile is set, then the originalParent should contain information for variables (or no info at all)
        if ($this->templateFile) {
            if($originalParent instanceof \Twig_Node_Expression_Array) {
                $variables = $originalParent;
            } else {
                $variables = new \Twig_Node_Expression_Array([], $originalParent->getTemplateLine());;
            }
            foreach ($this->argumentNames as $index => $argumentName) {
                $argumentExpressionNode = $this->functionArguments[$index] ?? null;
                if (!$argumentExpressionNode) {
                    break;
                }
                $argumentKeyNode = new \Twig_Node_Expression_Constant($argumentName, $originalParent->getTemplateLine());
                $variables->addElement($argumentExpressionNode, $argumentKeyNode);
            }
        }
        //Merge variables extracted from originalParent and the 'withVariables'
        if ($variables instanceof \Twig_Node_Expression_Array && $withVariables instanceof \Twig_Node_Expression_Array) {
            foreach ($withVariables->getKeyValuePairs() as $keyValuePair){
                $variables->addElement($keyValuePair['value'], $keyValuePair['key']);
            }
        }
        //Return variables or 'withVariables' if variables is still empty
        return $variables ?: $withVariables;
    }

    /**
     * Check if the main content block has been defined.
     *
     * @param \Twig_TokenStream $stream
     * @return bool
     */
    protected function hasMainContentBlock(\Twig_TokenStream $stream): bool
    {
        $n = 0;
        while (true) {
            $token = $stream->look($n++);
            if ($token->test($this->mainBlock)) {
                return true;
            }
            if ($token->test($this->getCloseTag())) {
                return false;
            }
        }
        return false;
    }

    /**
     * The embed-functionally (or rather the extends functionality) requires that content resides in blocks.
     * In this method, we check if there's content that does not reside in a block and wrap that very content inside
     * the main content block.
     *
     * NOTE: only content in the beginning can be wrapped. If content without block is being used after another block has
     * been used, then things get too complex.
     *
     * @param \Twig_TokenStream $stream
     * @throws \ReflectionException
     * @throws \Twig_Error_Syntax
     */
    protected function wrapContentBlock(\Twig_TokenStream $stream)
    {
        // We need to inject/edit tokens, but normally this can only be done at the position of 'current' token
        // This is too late for the 'endblock', since at that point rendering will throw an error.
        // To make things work, we make the tokens-attribute accessible to edit this attribute directly
        $class = new ReflectionClass($stream);
        $property = $class->getProperty("tokens");
        $property->setAccessible(true);
        $tokens = $property->getValue($stream);

        $n = 0;
        while (true) {
            // The tokens-array will contain all tokens, while the $stream->look only returns tokens after 'render'
            // this is what we need, because otherwise we may get false positives when the same tag is used multiple times.
            $streamToken = $stream->look($n++);
            if ($streamToken->test($this->getCloseTag()) || $streamToken->test('block')) {
                $tokenIndex = array_search($streamToken, $tokens, true);
                // Here we inject "{% endblock %}" right before the found "{% block ... %}" or "{% endyourTag %}"
                array_splice( $tokens, $tokenIndex-1, 0, [
                    new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $streamToken->getLine()),
                    new \Twig_Token(\Twig_Token::NAME_TYPE, 'endblock', $streamToken->getLine()),
                    new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $streamToken->getLine()),
                ]);
                break;
            }
        }
        $property->setValue($stream, $tokens);

        $currentToken = $stream->getCurrent();
        // We inject the starting block after the endblock, because for injecting the endblock we need to check if a
        // token with 'block' exist.
        // If we add this starting block earlier, then this check will render true when we don't want to.
        $stream->injectTokens([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'block', $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, $this->mainBlock, $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $currentToken->getLine()),
        ]);
    }

    public function getTag()
    {
        return $this->tagName;
    }

    public function getCloseTag()
    {
        return 'end'.$this->getTag();
    }
}