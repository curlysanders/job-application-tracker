<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Tooling;

require_once dirname(__DIR__, 2).'/tools/PhpCsFixer/ParameterLayoutFixer.php';

use CurlySanders\JobApplicationTracker\PhpCsFixer\ParameterLayoutFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParameterLayoutFixerTest extends TestCase
{
    #[DataProvider('parameterLists')]
    public function testFormatsParameterLists(
        string $input,
        string $expected,
    ): void {
        $tokens = Tokens::fromCode($input);
        new ParameterLayoutFixer()->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($expected, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function parameterLists(): iterable
    {
        yield 'one parameter stays on one line' => [
            '<?php function example($a) {}',
            '<?php function example($a) {}',
        ];

        yield 'two parameters become multiline' => [
            '<?php function example($a, $b) {}',
            "<?php function example(\n    \$a,\n    \$b,\n) {}",
        ];

        yield 'three parameters become multiline' => [
            "<?php function example(\$a, \$b, \$c): void\n{}",
            "<?php function example(\n    \$a,\n    \$b,\n    \$c,\n): void {}",
        ];

        yield 'one attributed parameter becomes multiline' => [
            '<?php function example(#[A] $a) {}',
            "<?php function example(\n    #[A]\n    \$a,\n) {}",
        ];

        yield 'attribute starts after a blank line' => [
            '<?php function example($a, #[A] $b) {}',
            "<?php function example(\n    \$a,\n\n    #[A]\n    \$b,\n) {}",
        ];

        yield 'multiple attributes stay together' => [
            '<?php function example($a, #[A] #[B] $b) {}',
            "<?php function example(\n    \$a,\n\n    #[A]\n    #[B]\n    \$b,\n) {}",
        ];

        yield 'following plain parameters do not add a blank line' => [
            '<?php function example($a, #[A] $b, $c) {}',
            "<?php function example(\n    \$a,\n\n    #[A]\n    \$b,\n    \$c,\n) {}",
        ];
    }
}
