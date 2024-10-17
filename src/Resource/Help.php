<?php

declare(strict_types=1);

namespace GitOps\Resource;

use GitOps\GitOpsResponse;
use GitOps\GitOpsResponseSection;
use GitOps\GitOpsResponseSectionTerm;

final readonly class Help
{
    public function help(): GitOpsResponse
    {
		return new GitOpsResponse(
			message: "GitOps 1.0 by Dave Kok.",
			sections: [
				new GitOpsResponseSection(
					title: "Usage",
					body: [
						new GitOpsResponseSectionTerm(
							term: "gitops <method> <resource> [<id> ...]",
							def: "",
						),
						new GitOpsResponseSectionTerm(
							term: "gitops help <resource>",
							def: "print help for resource",
						),
						new GitOpsResponseSectionTerm(
							term: "gitops setup config",
							def: "initialize the config file",
						),
						new GitOpsResponseSectionTerm(
							term: "gitops reconcile ring",
							def: "reconcile against ring",
						),
						new GitOpsResponseSectionTerm(
							term: "gitops setup site",
							def: "print setup yaml for a site",
						),
						new GitOpsResponseSectionTerm(
							term: "gitops deploy site",
							def: "print deploy yaml for a site",
						),
					]
				),
				new GitOpsResponseSection(
					title: "Resources",
					body: [
						new GitOpsResponseSectionTerm(
							term: "config",
							def: "the config file",
						),
						new GitOpsResponseSectionTerm(
							term: "credentials",
							def:  "the credentials for the registry",
						),
						new GitOpsResponseSectionTerm(
							term: "image",
							def: "a container image",
						),
						new GitOpsResponseSectionTerm(
							term: "ring",
							def: "a ring in deployment line",
						),
						new GitOpsResponseSectionTerm(
							term: "site",
							def: "a ring in deployment line",
						),
					]
				)
			]
		);
    }
}
