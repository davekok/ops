<?php

declare(strict_types=1);

namespace GitOps\Tests\Service;

use Exception;
use GitOps\GitOpsResponse;
use GitOps\GitOpsResponseSection;
use GitOps\GitOpsResponseSectionTerm;
use GitOps\Service\TextFormatter;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(TextFormatter::class)]
class TextFormatterTest extends TestCase
{
	public function testMimeType(): void
	{
		$formatter = new TextFormatter();
		$this->assertSame("text/plain; charset=UTF-8", $formatter->mimeType());
	}

	#[DataProvider('responses')]
	public function testFormat(GitOpsResponse|Throwable $response, string $expected): void
	{
		$formatter = new TextFormatter();
		$expected = addcslashes($expected, "\x00..\x1F\x7F..\xFF");
		$actual = $formatter->format($response);
		$actual = addcslashes($actual, "\x00..\x1F\x7F..\xFF");

		$this->assertEquals($expected, $actual);
	}

	public static function responses(): array
	{
		$reallyBadStuff = new Exception("Really bad stuff");

		return [
			[
				$reallyBadStuff,
				<<<TEXT
				\033[31;49mException\033[39;49m: {$reallyBadStuff->getMessage()}

				## {$reallyBadStuff->getFile()}({$reallyBadStuff->getLine()})
				{$reallyBadStuff->getTraceAsString()}


				TEXT,
			],
			[
				new LogicException("Bad stuff"),
				"\033[31;49mError\033[39;49m: Bad stuff\n\n",
			],
			[
				new GitOpsResponse(message: "Hello, world!"),
				"\033[39;49mHello, world!\n\n"
			],
			[
				new GitOpsResponse(message: "Hello, world!", sections: [
					new GitOpsResponseSection(title: "Lorem ipsum", body: "Iudicabit magna saepe consectetur graecis. Dolores novum orci legere ubique."),
					new GitOpsResponseSection(title: "Feugiat adversarium", body: "Faucibus consectetur invenire reque fusce propriae in offendit. Sadipscing accommodare contentiones prompta mentitum moderatius aliquet."),
				]),
				<<<TEXT
				\033[39;49mHello, world!

				\033[33;49mLorem ipsum:\033[39;49m

				Iudicabit magna saepe consectetur graecis. Dolores novum orci legere ubique.

				\033[33;49mFeugiat adversarium:\033[39;49m

				Faucibus consectetur invenire reque fusce propriae in offendit. Sadipscing accommodare contentiones
				prompta mentitum moderatius aliquet.


				TEXT
			],
			[
				new GitOpsResponse(sections: [
					new GitOpsResponseSection(title: "Lorem ipsum", body: "Iudicabit magna saepe consectetur graecis. Dolores novum orci legere ubique."),
					new GitOpsResponseSection(title: "Feugiat adversarium", body: "Faucibus consectetur invenire reque fusce propriae in offendit. Sadipscing accommodare contentiones prompta mentitum moderatius aliquet."),
				]),
				<<<TEXT
				\033[33;49mLorem ipsum:\033[39;49m

				Iudicabit magna saepe consectetur graecis. Dolores novum orci legere ubique.

				\033[33;49mFeugiat adversarium:\033[39;49m

				Faucibus consectetur invenire reque fusce propriae in offendit. Sadipscing accommodare contentiones
				prompta mentitum moderatius aliquet.


				TEXT
			],
			[
				new GitOpsResponse(message: "Hello, world!", sections: [
					new GitOpsResponseSection(title: "Lorem ipsum", body: [
						"Iudicabit magna saepe consectetur graecis.",
						"Vulputate nostra magna oporteat a arcu.",
						"Aliquet adipisci ne quam signiferumque viris lectus.",
						"Graecis utinam consul ornare diam fermentum urna.",
					]),
				]),
				<<<TEXT
				\033[39;49mHello, world!

				\033[33;49mLorem ipsum:\033[39;49m

				  Iudicabit magna saepe consectetur graecis.
				  Vulputate nostra magna oporteat a arcu.
				  Aliquet adipisci ne quam signiferumque viris lectus.
				  Graecis utinam consul ornare diam fermentum urna.


				TEXT
			],
			[
				new GitOpsResponse(message: "Hello, world!", sections: [
					new GitOpsResponseSection(title: "Lorem ipsum", body: [
						new GitOpsResponseSectionTerm(term: "Mentitum", def: "Netus deserunt metus quam natum viderer suscipit."),
						new GitOpsResponseSectionTerm(term: "Dolore", def: "Offendit eruditi consul euismod sententiae pertinax praesent."),
						new GitOpsResponseSectionTerm(term: "Dicat", def: "Quem fabulas blandit graecis propriae tota iusto."),
						new GitOpsResponseSectionTerm(term: "Scripta", def: "Penatibus errem nam sit splendide idque ne."),
						new GitOpsResponseSectionTerm(term: "Detraxit", def: "Nomero magna vis quod prompta taciti epicuri."),
					]),
				]),
				<<<TEXT
				\033[39;49mHello, world!

				\033[33;49mLorem ipsum:\033[39;49m

				  \033[32;49mMentitum\033[39;49m   Netus deserunt metus quam natum viderer suscipit.
				  \033[32;49mDolore\033[39;49m     Offendit eruditi consul euismod sententiae pertinax praesent.
				  \033[32;49mDicat\033[39;49m      Quem fabulas blandit graecis propriae tota iusto.
				  \033[32;49mScripta\033[39;49m    Penatibus errem nam sit splendide idque ne.
				  \033[32;49mDetraxit\033[39;49m   Nomero magna vis quod prompta taciti epicuri.


				TEXT
			],
		];
	}
}
