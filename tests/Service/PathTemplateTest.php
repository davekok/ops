<?php

declare(strict_types=1);

namespace GitOps\Tests\Service;

use GitOps\Service\PathTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathTemplate::class)]
class PathTemplateTest extends TestCase
{
    public static function paths(): array
    {
        return [
            ["/path/{var1}/subpath", ["var1" => "foo"], "/path/foo/subpath"],
            ["/path/{var1}/subpath", ["var1" => ""], "/path//subpath"],
            ["/path/{var1}/{var2}/subpath", ["var1" => "foo", "var2" => "bar"], "/path/foo/bar/subpath"],
            ["/path{/var1}/subpath", ["var1" => "foo"], "/path/foo/subpath"],
            ["/path{/var1}/subpath", ["var1" => ""], "/path/subpath"],
            ["/path{/var1,var2}/subpath", ["var1" => "foo", "var2" => "bar"], "/path/foo/bar/subpath"],
            ["{var1}/subpath", ["var1" => "foo"], "foo/subpath"],
            ["{var1}/{var2}/subpath", ["var1" => "foo", "var2" => "bar"], "foo/bar/subpath"],
            ["{/var1}/subpath", ["var1" => "foo"], "/foo/subpath"],
            ["{/var1,var2}/subpath", ["var1" => "foo", "var2" => "bar"], "/foo/bar/subpath"],
            ["/path/{var1}", ["var1" => "foo"], "/path/foo"],
            ["/path/{var1}/{var2}", ["var1" => "foo", "var2" => "bar"], "/path/foo/bar"],
            ["/path{/var1}", ["var1" => "foo"], "/path/foo"],
            ["/path{/var1,var2}", ["var1" => "foo", "var2" => "bar"], "/path/foo/bar"],
        ];
    }

    #[DataProvider('paths')]
    public function testPathExpand(string $template, array $vars, string $expended): void
    {
        $pathTemplate = new PathTemplate($template);
        $this->assertSame($expended, $pathTemplate->expand($vars));
		$this->assertEquals($vars, $pathTemplate->extract($expended));
    }
}
