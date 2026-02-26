<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreatePullRequestParams;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreatePullRequestParams::class)]
class CreatePullRequestParamsTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Add new feature',
            head: 'feature-branch',
            base: 'main'
        );

        $this->assertSame('Add new feature', $params->title);
        $this->assertSame('feature-branch', $params->head);
        $this->assertSame('main', $params->base);
        $this->assertSame('', $params->body);
        $this->assertFalse($params->draft);
        $this->assertTrue($params->maintainerCanModify);
    }

    public function testConstructorWithAllParameters(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Fix bug in authentication',
            head: 'fix/auth-bug',
            base: 'develop',
            body: 'This PR fixes the authentication bug\n\nCloses #123',
            draft: true,
            maintainerCanModify: false
        );

        $this->assertSame('Fix bug in authentication', $params->title);
        $this->assertSame('fix/auth-bug', $params->head);
        $this->assertSame('develop', $params->base);
        $this->assertSame('This PR fixes the authentication bug\n\nCloses #123', $params->body);
        $this->assertTrue($params->draft);
        $this->assertFalse($params->maintainerCanModify);
    }

    public function testToArrayWithRequiredParametersOnly(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main'
        );

        $array = $params->toArray();

        $this->assertSame([
            'title' => 'Test PR',
            'head' => 'test',
            'base' => 'main',
            'maintainer_can_modify' => true
        ], $array);
    }

    public function testToArrayWithAllParameters(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main',
            body: 'Test description',
            draft: true,
            maintainerCanModify: false
        );

        $array = $params->toArray();

        $this->assertSame([
            'title' => 'Test PR',
            'head' => 'test',
            'base' => 'main',
            'maintainer_can_modify' => false,
            'body' => 'Test description',
            'draft' => true
        ], $array);
    }

    public function testToArrayExcludesEmptyBody(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main',
            body: ''
        );

        $array = $params->toArray();

        $this->assertArrayNotHasKey('body', $array);
    }

    public function testToArrayExcludesDraftWhenFalse(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main',
            draft: false
        );

        $array = $params->toArray();

        $this->assertArrayNotHasKey('draft', $array);
    }

    public function testToArrayIncludesDraftWhenTrue(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main',
            draft: true
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('draft', $array);
        $this->assertTrue($array['draft']);
    }

    public function testMaintainerCanModifyDefaultTrue(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main'
        );

        $array = $params->toArray();

        $this->assertTrue($array['maintainer_can_modify']);
    }

    public function testMaintainerCanModifyCanBeFalse(): void
    {
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'test',
            base: 'main',
            maintainerCanModify: false
        );

        $array = $params->toArray();

        $this->assertFalse($array['maintainer_can_modify']);
    }

    public function testHeadBranchFormats(): void
    {
        // Simple branch name
        $params1 = new CreatePullRequestParams('Test', 'feature', 'main');
        $this->assertSame('feature', $params1->head);

        // Branch with owner prefix
        $params2 = new CreatePullRequestParams('Test', 'username:feature', 'main');
        $this->assertSame('username:feature', $params2->head);

        // Branch with slashes
        $params3 = new CreatePullRequestParams('Test', 'feature/add-auth', 'main');
        $this->assertSame('feature/add-auth', $params3->head);
    }

    public function testBaseBranchFormats(): void
    {
        // main branch
        $params1 = new CreatePullRequestParams('Test', 'feature', 'main');
        $this->assertSame('main', $params1->base);

        // develop branch
        $params2 = new CreatePullRequestParams('Test', 'feature', 'develop');
        $this->assertSame('develop', $params2->base);

        // release branch
        $params3 = new CreatePullRequestParams('Test', 'hotfix', 'release/1.0');
        $this->assertSame('release/1.0', $params3->base);
    }
}
