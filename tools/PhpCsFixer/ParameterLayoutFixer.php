<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\PhpCsFixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ParameterLayoutFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getName(): string
    {
        return 'JobApplicationTracker/parameter_layout';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Function and method parameter lists with more than one parameter, or parameter attributes, must be multiline.',
            [
                new CodeSample("<?php\nfunction example(\$first, \$second, \$third) {}\n"),
                new CodeSample("<?php\nfunction example(#[SensitiveParameter] \$value) {}\n"),
            ],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_FUNCTION, \T_FN]);
    }

    public function getPriority(): int
    {
        return -10;
    }

    protected function applyFix(
        \SplFileInfo $file,
        Tokens $tokens,
    ): void {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->isGivenKind([\T_FUNCTION, \T_FN])) {
                continue;
            }

            $openParenthesis = $tokens->getNextTokenOfKind($index, ['(', '{', ';', [\T_DOUBLE_ARROW]]);
            if (null === $openParenthesis || !$tokens[$openParenthesis]->equals('(')) {
                continue;
            }

            $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $openParenthesis);
            $parameters = $this->parameters($tokens, $openParenthesis, $closeParenthesis);

            if ([] === $parameters || $this->containsComment($tokens, $openParenthesis, $closeParenthesis)) {
                continue;
            }

            $hasAttributes = array_any($parameters, static fn (array $parameter): bool => [] !== $parameter['attributes']);
            if (1 === count($parameters) && !$hasAttributes) {
                $replacement = implode(', ', array_map(static fn (array $parameter): string => $parameter['parameter'], $parameters));
            } else {
                $replacement = $this->multilineReplacement($tokens, $openParenthesis, $parameters);
            }

            $tokens->overrideRange(
                $openParenthesis + 1,
                $closeParenthesis - 1,
                $this->tokensForParameterList($replacement),
            );

            if (count($parameters) > 1 || $hasAttributes) {
                $this->putOpeningBraceOnSignatureLine($tokens, $openParenthesis);
            }
        }
    }

    private function putOpeningBraceOnSignatureLine(
        Tokens $tokens,
        int $openParenthesis,
    ): void {
        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $openParenthesis);
        $bodyStart = $tokens->getNextTokenOfKind($closeParenthesis, ['{', ';', [\T_DOUBLE_ARROW]]);
        if (null === $bodyStart || !$tokens[$bodyStart]->equals('{')) {
            return;
        }

        $whitespace = $bodyStart - 1;
        if ($tokens[$whitespace]->isWhitespace()) {
            $tokens[$whitespace] = new Token([\T_WHITESPACE, ' ']);

            return;
        }

        $tokens->insertAt($bodyStart, new Token([\T_WHITESPACE, ' ']));
    }

    /**
     * @return list<array{attributes: list<string>, parameter: string}>
     */
    private function parameters(
        Tokens $tokens,
        int $openParenthesis,
        int $closeParenthesis,
    ): array {
        $ranges = [];
        $start = $openParenthesis + 1;

        for ($index = $start; $index < $closeParenthesis; ++$index) {
            if ($tokens[$index]->equals('(')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $index);

                continue;
            }

            if ($tokens[$index]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_BRACKET, $index);

                continue;
            }

            if ($tokens[$index]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_BRACE, $index);

                continue;
            }

            if ($tokens[$index]->equals(',')) {
                $ranges[] = [$start, $index - 1];
                $start = $index + 1;
            }
        }

        $ranges[] = [$start, $closeParenthesis - 1];

        $parameters = [];
        foreach ($ranges as [$start, $end]) {
            $parameter = $this->parameter($tokens, $start, $end);
            if (null !== $parameter) {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * @return array{attributes: list<string>, parameter: string}|null
     */
    private function parameter(
        Tokens $tokens,
        int $start,
        int $end,
    ): ?array {
        while ($start <= $end && $tokens[$start]->isWhitespace()) {
            ++$start;
        }

        while ($end >= $start && $tokens[$end]->isWhitespace()) {
            --$end;
        }

        if ($start > $end) {
            return null;
        }

        $attributes = [];
        while ($start <= $end && $tokens[$start]->isGivenKind(\T_ATTRIBUTE)) {
            $attributeEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ATTRIBUTE, $start);
            $attributes[] = $this->normalize($this->contents($tokens, $start, $attributeEnd));
            $start = $attributeEnd + 1;

            while ($start <= $end && $tokens[$start]->isWhitespace()) {
                ++$start;
            }
        }

        return [
            'attributes' => $attributes,
            'parameter' => $this->normalize($this->contents($tokens, $start, $end)),
        ];
    }

    /**
     * @param list<array{attributes: list<string>, parameter: string}> $parameters
     */
    private function multilineReplacement(
        Tokens $tokens,
        int $openParenthesis,
        array $parameters,
    ): string {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $indentation = $this->indentation($tokens, $openParenthesis);
        $parameterIndentation = $indentation.$this->whitespacesConfig->getIndent();
        $replacement = $lineEnding;

        foreach ($parameters as $index => $parameter) {
            if ($index > 0 && [] !== $parameter['attributes']) {
                $replacement .= $lineEnding;
            }

            foreach ($parameter['attributes'] as $attribute) {
                $replacement .= $parameterIndentation.$attribute.$lineEnding;
            }

            $replacement .= $parameterIndentation.$parameter['parameter'].','.$lineEnding;
        }

        return $replacement.$indentation;
    }

    private function tokensForParameterList(string $parameters): Tokens
    {
        $tokens = Tokens::fromCode('<?php function placeholder('.$parameters.') {}');
        $function = $tokens->getNextTokenOfKind(0, [[\T_FUNCTION]]);
        \assert(null !== $function);
        $openParenthesis = $tokens->getNextTokenOfKind($function, ['(']);
        \assert(null !== $openParenthesis);
        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $openParenthesis);

        $parameterTokens = [];
        for ($index = $openParenthesis + 1; $index < $closeParenthesis; ++$index) {
            $parameterTokens[] = clone $tokens[$index];
        }

        return Tokens::fromArray($parameterTokens, false);
    }

    private function contents(
        Tokens $tokens,
        int $start,
        int $end,
    ): string {
        $contents = '';
        for ($index = $start; $index <= $end; ++$index) {
            $contents .= $tokens[$index]->getContent();
        }

        return $contents;
    }

    private function normalize(string $contents): string
    {
        return trim((string) preg_replace('/\\s+/', ' ', $contents));
    }

    private function containsComment(
        Tokens $tokens,
        int $start,
        int $end,
    ): bool {
        for ($index = $start + 1; $index < $end; ++$index) {
            if ($tokens[$index]->isComment()) {
                return true;
            }
        }

        return false;
    }

    private function indentation(
        Tokens $tokens,
        int $index,
    ): string {
        $before = '';
        for (--$index; $index >= 0; --$index) {
            $before = $tokens[$index]->getContent().$before;
            $lastNewline = strrpos($before, "\n");
            if (false !== $lastNewline) {
                preg_match('/^[\\t ]*/', substr($before, $lastNewline + 1), $matches);

                return $matches[0] ?? '';
            }
        }

        return '';
    }
}
