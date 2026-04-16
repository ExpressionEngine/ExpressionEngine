<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureSitemapTest extends StructureTestBase
{
	private function setSitemapEnvironment(array $sitePages, array $pages): void
	{
		// Structure::sitemap() reads from $this->site_pages directly
		$this->structure->site_pages = $sitePages;

		// Stub only get_data() used by sitemap()
		$this->structure->sql = new class($pages) {
			private $pages;
			public function __construct($pages) { $this->pages = $pages; }
			public function get_data() { return $this->pages; }
		};
	}

	public function testSitemapXmlModeFiltersByStatus()
	{
		$sitePages = [
			'uris' => [
				1 => '/',
				2 => '/about',
				3 => '/about/team',
				4 => '/hidden'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Team', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
			['entry_id' => 4, 'title' => 'Hidden', 'status' => 'closed', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'xml',
			'status' => 'open',
		]);

		$xml = $this->structure->sitemap();
		$this->assertStringStartsWith('<?xml', $xml);
		$this->assertStringContainsString('<loc>/about</loc>', $xml);
		$this->assertStringContainsString('<loc>/about/team</loc>', $xml);
		$this->assertStringNotContainsString('/hidden', $xml);
	}

	public function testSitemapTextModeWithExclude()
	{
		$sitePages = [
			'uris' => [
				2 => '/about',
				3 => '/about/team'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Team', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'exclude' => '3',
		]);

		$text = $this->structure->sitemap();
		$this->assertStringContainsString("/about\n", $text);
		$this->assertStringNotContainsString('/about/team', $text);
	}

	public function testSitemapHtmlModeWithCssIdAndClass()
	{
		$sitePages = [
			'uris' => [
				2 => '/about',
				3 => '/about/team'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Team', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'css_id' => 'my-map',
			'css_class' => 'nav',
		]);

		$html = $this->structure->sitemap();
		$this->assertStringContainsString('<ul id="my-map"', $html);
		$this->assertStringContainsString("<a href='/about'>About</a>", $html);
		$this->assertStringContainsString("<a href='/about/team'>Team</a>", $html);
		$this->assertStringContainsString('class="page-3 last"', $html);
	}

	public function testSitemapNegativeStatusFilter()
	{
		$sitePages = [
			'uris' => [
				2 => '/open-page',
				3 => '/closed-page'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'Open Page', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Closed Page', 'status' => 'closed', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'status' => 'not closed',
		]);

		$text = $this->structure->sitemap();
		$this->assertStringContainsString('/open-page', $text);
		$this->assertStringNotContainsString('/closed-page', $text);
	}

	public function testSitemapIncludeAndExcludeStatusLegacy()
	{
		$sitePages = [
			'uris' => [
				2 => '/draft',
				3 => '/open'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'Draft Page', 'status' => 'draft', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Open Page', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);

		// include_status should add to default 'open'
		$this->setTemplateParams([
			'mode' => 'text',
			'include_status' => 'draft',
		]);
		$text = $this->structure->sitemap();
		$this->assertStringContainsString('/draft', $text);
		$this->assertStringContainsString('/open', $text);

		// exclude_status in combination with status list should filter out 'open'
		$this->setTemplateParams([
			'mode' => 'text',
			'status' => 'open|draft',
			'exclude_status' => 'open',
		]);
		$text2 = $this->structure->sitemap();
		$this->assertStringContainsString('/draft', $text2);
		$this->assertStringNotContainsString('/open', $text2);
	}

	public function testExplicitEmptyStatusRemovesAllPages()
	{
		$sitePages = [
			'uris' => [
				2 => '/about',
				3 => '/draft-page'
			]
		];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Draft', 'status' => 'draft', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'status' => '',
		]);

		$this->assertSame('', $this->structure->sitemap());
	}

	public function testCssIdNoneRemovesIdButKeepsClass()
	{
		$sitePages = [ 'uris' => [ 2 => '/about' ] ];
		$pages = [ ['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1] ];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'css_id' => 'none',
			'css_class' => 'list',
		]);

		$html = $this->structure->sitemap();
		$this->assertStringContainsString('<ul id=""', $html);
		$this->assertStringContainsString('class="', $html);
		$this->assertStringNotContainsString('id="sitemap"', $html);
	}

	public function testDefaultsProduceHtmlWithDefaultId()
	{
		$sitePages = [ 'uris' => [ 2 => '/about' ] ];
		$pages = [ ['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1] ];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([]);

		$html = $this->structure->sitemap();
		$this->assertStringContainsString('<ul id="', $html);
	}

	public function testCaseInsensitiveStatuses()
	{
		$sitePages = [ 'uris' => [ 2 => '/about', 3 => '/draft-page' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'OpEn', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Draft', 'status' => 'DrAfT', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'status' => 'open|draft',
		]);

		$text = $this->structure->sitemap();
		$this->assertStringContainsString('/about', $text);
		$this->assertStringContainsString('/draft-page', $text);
	}

	public function testClosedParentCascadesRemoval()
	{
		$sitePages = [ 'uris' => [ 2 => '/parent', 3 => '/parent/child' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'Parent', 'status' => 'closed', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Child', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'status' => 'not closed',
		]);

		$text = $this->structure->sitemap();
		$this->assertStringNotContainsString('/parent', $text);
		$this->assertStringNotContainsString('/parent/child', $text);
	}

	public function testExcludingParentAlsoExcludesChildren()
	{
		$sitePages = [ 'uris' => [ 2 => '/parent', 3 => '/parent/child' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'Parent', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'Child', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams([
			'mode' => 'text',
			'exclude' => '2',
		]);

		$text = $this->structure->sitemap();
		$this->assertStringNotContainsString('/parent', $text);
		$this->assertStringNotContainsString('/parent/child', $text);
	}

	public function testSkipsEntriesMissingUri()
	{
		$sitePages = [ 'uris' => [ 2 => '/about' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'About', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 999, 'title' => 'Orphan', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams(['mode' => 'xml']);

		$xml = $this->structure->sitemap();
		$this->assertStringContainsString('<loc>/about</loc>', $xml);
		$this->assertStringNotContainsString('Orphan', $xml);
	}

	public function testEscapesTitlesWhenAutoConvertHighAsciiIsDisabled()
	{
		$sitePages = [ 'uris' => [ 2 => '/about' ] ];
		$pages = [ ['entry_id' => 2, 'title' => 'About & <Team>', 'status' => 'open', 'depth' => 1, 'parent_id' => 1] ];
		$this->setSitemapEnvironment($sitePages, $pages);
		// Disable auto_convert_high_ascii to trigger htmlspecialchars branch
		ee()->config->items['auto_convert_high_ascii'] = 'n';

		$html = $this->structure->sitemap();
		$this->assertStringContainsString('About &amp; &lt;Team&gt;', $html);
	}

	public function testNestedHtmlAddsLastClassWhenDepthDecreases()
	{
		$sitePages = [ 'uris' => [ 2 => '/a', 3 => '/a/b', 4 => '/c' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'A', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'B', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
			['entry_id' => 4, 'title' => 'C', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);

		$html = $this->structure->sitemap();
		// Ensure that when closing the sublist, previous <li> gets class "last"
		$this->assertStringContainsString('class="page-3 last"', $html);
	}

	public function testBalancedUlTags()
	{
		$sitePages = [ 'uris' => [ 2 => '/a', 3 => '/a/b', 4 => '/a/b/c' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'A', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'B', 'status' => 'open', 'depth' => 2, 'parent_id' => 2],
			['entry_id' => 4, 'title' => 'C', 'status' => 'open', 'depth' => 3, 'parent_id' => 3],
		];
		$this->setSitemapEnvironment($sitePages, $pages);

		$html = $this->structure->sitemap();
		$openCount = substr_count($html, '<ul');
		$closeCount = substr_count($html, '</ul>');
		$this->assertGreaterThanOrEqual($openCount, $closeCount);
	}

	public function testTextModeNewlines()
	{
		$sitePages = [ 'uris' => [ 2 => '/a', 3 => '/b' ] ];
		$pages = [
			['entry_id' => 2, 'title' => 'A', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
			['entry_id' => 3, 'title' => 'B', 'status' => 'open', 'depth' => 1, 'parent_id' => 1],
		];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams(['mode' => 'text']);

		$text = $this->structure->sitemap();
		$this->assertStringContainsString("/a\n", $text);
		$this->assertStringContainsString("/b\n", $text);
	}

	public function testXmlModeUsesSiteIndexPrefix()
	{
		// Override fetch_site_index to return a base prefix
		$functions = new class {
			public function fetch_site_index($a = 0, $b = 0) { return '/base/'; }
		};
		ee()->setMock('functions', $functions);

		$sitePages = [ 'uris' => [ 2 => '/a' ] ];
		$pages = [ ['entry_id' => 2, 'title' => 'A', 'status' => 'open', 'depth' => 1, 'parent_id' => 1] ];
		$this->setSitemapEnvironment($sitePages, $pages);
		$this->setTemplateParams(['mode' => 'xml']);

		$xml = $this->structure->sitemap();
		$this->assertStringContainsString('<loc>/base/a</loc>', $xml);
	}
}

