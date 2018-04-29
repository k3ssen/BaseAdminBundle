<?php
declare(strict_types = 1);

namespace K3ssen\BaseAdminBundle\Twig;

use ReflectionClass;

/**
 * This component parser is an altered version of the 'embed' parser.
 */
class ComponentParser extends \Twig_TokenParser_Include
{
    public const TAG_NAME = 'component';
    public const DEFAULT_MAIN_BLOCK = 'content';
    public const DEFAULT_TEMPLATE_DIR = 'layout/components';

    protected $mainBlock;
    protected $templateDir;

    public function __construct(
        string $mainBlock = self::DEFAULT_MAIN_BLOCK,
        string $templateDir = self::DEFAULT_TEMPLATE_DIR
    ) {
        $this->mainBlock = $mainBlock;
        $this->templateDir = $templateDir;
    }

    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();

        $originalParent = $parent = $this->parser->getExpressionParser()->parseExpression();
        if ($originalParent->hasAttribute('name')) {
            $value = $this->templateDir.'/'.$originalParent->getAttribute('name').'.html.twig';
            $parent = new \Twig_Node_Expression_Constant($value, $originalParent->getTemplateLine());
        }

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        if (!$variables && $originalParent->hasNode('arguments')) {
            $variables = $originalParent->getNode('arguments')->getNode(0);
        }


        $parentToken = $fakeParentToken = new \Twig_Token(\Twig_Token::STRING_TYPE, '__parent__', $token->getLine());
        if ($parent instanceof \Twig_Node_Expression_Constant) {
            $parentToken = new \Twig_Token(\Twig_Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof \Twig_Node_Expression_Name) {
            $parentToken = new \Twig_Token(\Twig_Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());
        }

        if (!$this->hasContentBlock($stream)) {
            $this->wrapContentBlock($stream);
        }

        // inject a fake parent to make the parent() function work
        $stream->injectTokens([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $token->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'extends', $token->getLine()),
            $parentToken,
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $token->getLine()),
        ]);


        $module = $this->parser->parse($stream, function (\Twig_Token $token) {
            return $token->test('end'.$this->getTag());
        }, true);

        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }

        $this->parser->embedTemplate($module);

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new \Twig_Node_Embed($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    protected function hasContentBlock(\Twig_TokenStream $stream): bool
    {
        try {
            $n = 0;
            while (true) {
                $token = $stream->look($n++);
                if ($token->test(\Twig_Token::NAME_TYPE, $this->mainBlock)) {
                    return true;
                }
            }
        } catch (\Twig_Error_Syntax $e) {
            // If no 'box_body' is found, then 'unexpected end of template' will be thrown
        }
        return false;
    }

    protected function wrapContentBlock(\Twig_TokenStream $stream)
    {
        // We need to inject/edit tokens, but normally this can only be done at the position of 'current' token
        // This is too late for the 'endblock', since at that point rendering will throw an error.
        // Instead, we make the tokens-attribute accessible to edit this attribute directly
        $class = new ReflectionClass($stream);
        $property = $class->getProperty("tokens");
        $property->setAccessible(true);
        $tokens = $property->getValue($stream);

        $afterRender = false;
        /** @var \Twig_Token $token*/
        foreach ($tokens as $index => $token) {
            // We only want to check tokens within the render-block
            if (!$afterRender) {
                $afterRender = $token->test($this->getTag());
                continue;
            }
            //Inject the 'endblock' right before the first '{% block %}' or '{% endrender %}' occurence
            if ($token->test($this->getCloseTag()) || $token->test('block')) {
                array_splice( $tokens, $index-1, 0, [
                    new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $token->getLine()),
                    new \Twig_Token(\Twig_Token::NAME_TYPE, 'endblock', $token->getLine()),
                    new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $token->getLine()),
                ]);
                break;
            }
        }
        $property->setValue($stream, $tokens);

        $currentToken = $stream->getCurrent();
        // We inject the starting block after the endblock, because for injecting the endblock we need to check
        // if a token with 'block' exist. If we add this starting block earlier,
        // then this check will render true when we don't want to.
        $stream->injectTokens([
            new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, 'block', $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::NAME_TYPE, $this->mainBlock, $currentToken->getLine()),
            new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', $currentToken->getLine()),
        ]);
    }

    public function getTag()
    {
        return static::TAG_NAME;
    }

    public function getCloseTag()
    {
        return 'end'.static::TAG_NAME;
    }
}