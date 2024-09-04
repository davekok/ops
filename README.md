# GitOps

A GitOps implementation.

## Goal

The goal of this application is to make it easier to do GitOps. The corner stones
of GitOps are state [reconciliation](https://github.com/open-gitops/documents/blob/v1.0.0/GLOSSARY.md#reconciliation)
and immutably storing versioned artifacts in a [state store](https://github.com/open-gitops/documents/blob/v1.0.0/GLOSSARY.md#state-store).

Since reconciliation is already handled by Kubernetes. This application simply makes the appropriate calls to Kubernetes.

However, building artifacts in an immutable versioned matter is not handled out of the box by any
tool I know of. Thus, this application focuses primarily on that aspect.

## Design

### State store

In GitOps you are free to choose the [state store](https://github.com/open-gitops/documents/blob/v1.0.0/GLOSSARY.md#state-store)
that best fits your projects needs. Since we will be using Kubernetes and Kubernetes interfaces with
container registries rather git. A private container registry is used as the state store.

### Root image

This implementation requires that you have one container image that is the root image of the project.
It is assumed that once this root image is run on a site. Everything is handled from there. The
root image could, for instance, contain the Kubernetes files and database migrations to install, upgrade or
downgrade the application. This complies with the constraint that
[admin processes](https://12factor.net/admin-processes) should one-offs.

On the root image, labels are added, to record the versions of all dependent container images.

### Environments

It is assumed Kustomize is used to define your environments. However, that does not exclude the use of
helm. [Here is a great article about the using Helm and Kustomize together.](https://trstringer.com/helm-kustomize/)
Since the whole point of using Helm is to make reusable Helm charts across projects. It makes little sense
to bake environmental differences into the Helm chart directly. Especially for dev environments that often
require extensive customization, which may even be specific to a developer. Trying to put all this in a
Helm chart, will make it hard to maintain. Also, if you ever wish to release the application is best to
have a clean Helm chart.

### Rings

Often you wish to have more than one version deployed. To maintain this, rings may be defined.
Versions are assumed to be shifted from ring to ring in the order defined. It is customary to define
your rings by user groups. For instance:

 - developers
 - testers
 - stakeholders
 - innovators
 - early-adaptors
 - users
 - long-term-support-users

The pseudo rings ante and post are always added. Ante before the first rings. Allowing you to do prep work
to make new base images for instance. A post to hold the version after it has be shifted off the chain. So
you can unshift it. If things do go wrong.

### Sites

A site is a specific location where a version is deployed. It is a combination of environment + ring.
The ring determines the version. The environment determines the kustomization to apply.

### Versioning

For each container image a semantic version is maintained. Per ring is
retained which version of a container image is in use. For all images
including base and intermediate images.

Container images are always build against a ring. If the next ring has the
same minor version as this one. The minor version is bumped, otherwise the
patch version is bumped. The major version is only bumped on request.

On each container image labels are maintained. By standard the following labels will get added:

org.opencontainers.image.version
: The semantic version of the image.

org.opencontainers.image.revision
: A sha256 hash of the container file and sources extracted from COPY commands.

org.opencontainers.image.ref.name
: The reference name **project**/**image**, **project** may contain slashes.

org.opencontainers.image.vendor
: A optional vendor label, if specified

dependency.*
: The semantic version of dependencies. One label per dependency.

Within the container file you may specify more labels.

A container will get rebuild if a dependent container has a different version than the current build. Or
the revision hash does not match.
