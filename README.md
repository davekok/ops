# ops

Operations for a software project.

## Goal

The goal of this project is to make it easier to work with Kubernetes. If you
have a professional software development process with a development, test,
staging and (multiple) production environments. Perhaps even rings for
innovators and early adaptors.

## Design

It is assumed you have one or more kustomizations using Kustomize. Please
note that the Kustomize version bundled with kubectl is assumed to be used.
Secondly you define one or more rings aimed at different user groups,
for instance:
 - developers
 - testers
 - stakeholders
 - innovators
 - early adaptors
 - users

Then for each site you specify a kustomization and user group:

| site               | kustomization | user group     |
|--------------------|---------------|----------------|
| your laptop        | dev           | developers     |
| colleague's laptop | dev           | developers     |
| tester's laptop    | test          | testers        |
| cannery            | stag          | stakeholders   |
| sandbox            | prod          | innovators     |
| demo               | prod          | early adaptors |
| prod               | prod          | users          |

On each site a watcher is run to check if there is an update. If so it
is installed.

## Versioning

For each container image a semantic version is maintained. Per ring is
retained which version of a container image is in use. For all images
including base and intermediate images.

Container images are always build against a ring. If the next ring has the
same minor version as this one. The minor version is bumped, otherwise the
patch version is bumped. The major version is only bumped on request.
