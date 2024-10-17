<?php

declare(strict_types=1);

namespace GitOps\Tests;

use GitOps\GitOpsResponse;
use GitOps\GitOpsResponseSection;
use GitOps\GitOpsResponseSectionTerm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitOpsResponse::class)]
#[CoversClass(GitOpsResponseSection::class)]
#[CoversClass(GitOpsResponseSectionTerm::class)]
class GitOpsResponseTest extends TestCase
{
	public function testResponse(): void
	{
		$response = new GitOpsResponse();
		$this->assertNull($response->message);
		$this->assertSame([], $response->sections);

		$response = new GitOpsResponse(
			message: "veritus",
			sections: [
				new GitOpsResponseSection(
					title: "finibus",
					body: "cetero quis sapientem vulputate turpis hinc tacimates meliore amet detraxit dico nec minim tritani deterruisset"
				),
				new GitOpsResponseSection(
					title: "maximus",
					body: "dicam oporteat dui viris nascetur inimicus egestas verterem imperdiet mnesarchum diam senectus semper metus repudiandae"
				),
			]
		);
		$this->assertSame("veritus", $response->message);
		$this->assertSame("finibus", $response->sections[0]->title);
		$this->assertSame("cetero quis sapientem vulputate turpis hinc tacimates meliore amet detraxit dico nec minim tritani deterruisset", $response->sections[0]->body);
		$this->assertSame("maximus", $response->sections[1]->title);
		$this->assertSame("dicam oporteat dui viris nascetur inimicus egestas verterem imperdiet mnesarchum diam senectus semper metus repudiandae", $response->sections[1]->body);

		$response = new GitOpsResponse(
			message: "meliore",
			sections: [
				new GitOpsResponseSection(
					title: "dissentiunt",
					body: [
						"comprehensam eros postulant voluptatum tacimates ei iriure",
						"inceptos lobortis detracto eos nunc sagittis utamur",
						"vivamus dico vivendo sociosqu veritus mediocrem odio",
					]
				),
			]
		);
		$this->assertSame("meliore", $response->message);
		$this->assertSame("dissentiunt", $response->sections[0]->title);
		$this->assertSame("comprehensam eros postulant voluptatum tacimates ei iriure", $response->sections[0]->body[0]);
		$this->assertSame("inceptos lobortis detracto eos nunc sagittis utamur", $response->sections[0]->body[1]);
		$this->assertSame("vivamus dico vivendo sociosqu veritus mediocrem odio", $response->sections[0]->body[2]);

		$response = new GitOpsResponse(
			message: "eirmod",
			sections: [
				new GitOpsResponseSection(
					title: "maluisset",
					body: [
						new GitOpsResponseSectionTerm(
							term: "affert",
							def: "graecis ultrices nulla iudicabit elementum oratio aliquid"
						),
						new GitOpsResponseSectionTerm(
							term: "habeo",
							def: "postea himenaeos elitr fastidii delenit quis quod"
						),
						new GitOpsResponseSectionTerm(
							term: "interdum",
							def: "vocent dicit vivendo mutat te scripserit fugit"
						),
					]
				),
			]
		);
		$this->assertSame("eirmod", $response->message);
		$this->assertSame("maluisset", $response->sections[0]->title);
		$this->assertSame("affert", $response->sections[0]->body[0]->term);
		$this->assertSame("graecis ultrices nulla iudicabit elementum oratio aliquid", $response->sections[0]->body[0]->def);
		$this->assertSame("habeo", $response->sections[0]->body[1]->term);
		$this->assertSame("postea himenaeos elitr fastidii delenit quis quod", $response->sections[0]->body[1]->def);
		$this->assertSame("interdum", $response->sections[0]->body[2]->term);
		$this->assertSame("vocent dicit vivendo mutat te scripserit fugit", $response->sections[0]->body[2]->def);
	}
}
