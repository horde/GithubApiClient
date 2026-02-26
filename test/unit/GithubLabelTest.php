<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubLabel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GithubLabel::class)]
class GithubLabelTest extends TestCase
{
    public function testConstructorWithDescription(): void
    {
        $label = new GithubLabel(
            name: 'bug',
            color: 'd73a4a',
            description: 'Something isn\'t working'
        );

        $this->assertSame('bug', $label->name);
        $this->assertSame('d73a4a', $label->color);
        $this->assertSame('Something isn\'t working', $label->description);
    }

    public function testConstructorWithoutDescription(): void
    {
        $label = new GithubLabel(
            name: 'enhancement',
            color: 'a2eeef'
        );

        $this->assertSame('enhancement', $label->name);
        $this->assertSame('a2eeef', $label->color);
        $this->assertNull($label->description);
    }

    public function testToStringReturnsName(): void
    {
        $label = new GithubLabel(
            name: 'feature',
            color: '0052cc'
        );

        $this->assertSame('feature', (string) $label);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = (object) [
            'name' => 'documentation',
            'color' => '0075ca',
            'description' => 'Improvements or additions to documentation'
        ];

        $label = GithubLabel::fromApiResponse($data);

        $this->assertSame('documentation', $label->name);
        $this->assertSame('0075ca', $label->color);
        $this->assertSame('Improvements or additions to documentation', $label->description);
    }

    public function testFromApiResponseWithoutDescription(): void
    {
        $data = (object) [
            'name' => 'wontfix',
            'color' => 'ffffff'
        ];

        $label = GithubLabel::fromApiResponse($data);

        $this->assertSame('wontfix', $label->name);
        $this->assertSame('ffffff', $label->color);
        $this->assertNull($label->description);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $label = GithubLabel::fromApiResponse($data);

        $this->assertSame('', $label->name);
        $this->assertSame('000000', $label->color);
        $this->assertNull($label->description);
    }
}
