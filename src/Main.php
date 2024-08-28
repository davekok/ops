<?php

declare(strict_types=1);

namespace Operations;

use JsonException;
use RuntimeException;
use Throwable;

class Main
{
    #[Option("C", "change current directory")] public CurrentDirectory $currentDir;
    #[Option("c", "command")] public string $command;
    #[Option("i", "specify the image", filePattern: "etc/%.containerfile")] public string $image;
    #[Option("k", "the kustomization to use", env: true, filePattern: "etc/%/kustomization.yaml")] public string $kustomization;
    #[Option("m", "increase the major version component when building")] public bool $major = false;
    #[Option("P", "the container registry password to use", env: true)] public string $password;
    #[Option("p", "the project", env: true, pattern: "/^([a-z0-9-]+\/)*[a-z0-9-]+$/")] public string $project;
    #[Option("R", "the container registry to use", env: true)] public string $registry;
    #[Option(null, "specify the rings", env: true, pattern: "/^[a-z]+$/")] public array $rings;
    #[Option("r", "the ring to use", env: true, values: "%option:rings")] public string $ring;
    #[Option("U", "the container registry user to use", env: true)] public string $user;
    #[Option("W", "specify the wait time for the watch command", env: true)] public int $wait = 5;
    #[Option("v", "specify the version to use", pattern: "%method:getVersionPattern")] public string $version;
    #[Option("V", "print verbose output")] public bool $verbose = false;
    #[Option(null, "define a command", env: "CMD_")] public array $commands;

    /* Used by option $version */
    public function getVersionPattern(): string
    {
        return str_replace("/^", implode("|", $this->rings) . "|", Repository::VERSION_PATTERN);
    }

    /** @throws JsonException */
    #[Command("L", "login to container registry", "--login [--registry REGISTRY] [--user USER] [--password PASSWORD]")]
    public function login(): void
    {
        $regSecret = "/run/secrets/registry-secret";
        if (file_exists($regSecret)) {
            $registry = json_decode(file_get_contents($regSecret) ?: throw new RuntimeException($regSecret), false, 512, JSON_THROW_ON_ERROR);
            foreach ($registry->auths as $registry => $auth) {
                $user = $auth->username;
                $password = $auth->password;
                break;
            }
        } else {
            $registry = $this->registry;
            $user = $this->user ?? null;
            $password = $this->password ?? null;
        }

        if (! isset($user, $password)) {
            return;
        }

        echo "Logging in\n";
        echo "- buildah: ";
        $this->exec("buildah login -u '$user' -p '$password' '$registry'");
        echo "- skopeo: ";
        $this->exec("skopeo login -u '$user' -p '$password' '$registry'");
        echo "\n";
    }

    #[Command("e", "execute a defined command(s)", "--execute [--command COMMAND]")]
    public function execute(): void
    {
        $commands = isset($this->command) ? [$this->commands[$this->command]] : $this->commands;
        foreach ($commands as $command => $commandLine) {
            $commandLine = str_replace(['${CWD}'], [(string)$this->currentDir], $commandLine);
            echo "running $command: $commandLine\n";
            $this->exec($commandLine);
            echo "\n";
        }
    }

    #[Command("u", "update image tag to ring version in kustomizations", "--update [--ring RING]")]
    public function update(): void
    {
        $etc = $this->getEtcetera();
        foreach ($etc->getKustomizations() as $file) {
            $yaml = yaml_parse_file($file);

            if (!isset($yaml["images"])) {
                continue;
            }

            $images = [];
            foreach ($yaml["images"] as &$image) {
                if (!isset($image["newTag"])) {
                    continue;
                }
                $repo = new Repository($image["newName"] ?? $image["name"]);
                $version = $repo->getTag($this->ring);
                if (isset($version)) {
                    $image["newTag"] = $version;
                    $images[] = "$repo => $version";
                }
            }
            unset($image);

            echo "Updating ", str_replace("$etc/", "", $file), ":\n - ", implode("\n - ", $images), "\n\n";

            yaml_emit_file($file, $yaml, encoding: YAML_UTF8_ENCODING, linebreak: YAML_LN_BREAK);
        }
    }

    #[Command("b", "build a new version of a image", "--build [--ring RING] [ --image IMAGE [--major] [--version VERSION] ]")]
    public function build(): void
    {
        foreach ($this->getEtcetera()->getImages($this->image ?? null) as $image) {
            if ($image->requiresUpdate) {
                $this->update();
            }
            $repository = $image->getRepository();
            $currentVersion = $repository->getTag($this->ring);
            $targetVersion = $repository->bumpVersion($currentVersion, $this->major ? null : $this->getRing(1));
            echo "Building $image\n";
            echo " - ring: $this->ring\n";
            echo " - current version: $currentVersion\n";
            echo " - target version: $targetVersion\n";
            $this->exec("buildah bud --build-arg 'RING=$this->ring' --build-arg 'VERSION=$targetVersion' -f '$image->containerFile' -t '$repository:$targetVersion' $this->currentDir");
            $this->exec("buildah tag '$repository:$targetVersion' '$repository:$this->ring'");
            echo "Pushing $repository:$targetVersion\n";
            $this->exec("buildah push '$repository:$targetVersion'");
            $this->exec("buildah push '$repository:$this->ring'");
            echo "\n";
        }
    }

    #[Command("s", "shift ring to next ring", "--shift --ring RING [--image IMAGE]")]
    public function shift(): void
    {
        $this->shiftRing(1);
    }

    #[Command("S", "unshift ring to previous ring", "--unshift --ring RING [--image IMAGE]")]
    public function unshift(): void
    {
        $this->shiftRing(-1);
    }

    private function shiftRing(int $direction): void
    {
        $target = $this->getRing($direction);

        foreach ($this->getEtcetera()->getImages($this->image ?? null) as $image) {
            $repository = $image->getRepository();
            $version = $repository->getTag($this->ring);
            if ($version !== null) {
                $repository->setTag($target, $version);
            }
        }
    }

    #[Command("l", "list all versions of a container", "--list [--image IMAGE]")]
    public function list(): void
    {
        $data = [];

        foreach ($this->getEtcetera()->getImages($this->image ?? null) as $image) {
            $repository = $image->getRepository();
            $data[$repository->name] = $repository->list();
        }

        echo str_replace(["---\n", "...\n"], "", yaml_emit($data, encoding: YAML_UTF8_ENCODING, linebreak: YAML_LN_BREAK));
    }

    #[Command("g", "get a image", "--get [--image IMAGE] [--version VERSION]")]
    public function getImage(): void
    {
        $data = [];
        $tag = $this->version ?? $this->ring;
        foreach ($this->getEtcetera()->getImages($this->image ?? null) as $image) {
            $repository = $image->getRepository();
            $data[$repository->name] = $repository->get($tag, $this->verbose);
        }

        echo str_replace(["---\n", "...\n"], "", yaml_emit($data, encoding: YAML_UTF8_ENCODING, linebreak: YAML_LN_BREAK));
    }

    #[Command("d", "deploy application", "--deploy --ring RING --kustomization KUSTOMIZATION")]
    public function deploy(): void
    {
        $repo = $this->getEtcetera()->getImage("deploy")->getRepository();

        echo "Check deploy ", date("Y-m-d H:i:s"), "\n";
        # Get current version
        [, $currentVersion] = explode(":", `kubectl get pod deploy -o=jsonpath='\{$.spec.containers[:1].image}' 2>/dev/null` ?? ":null");
        echo " - current version: $currentVersion\n";
        # Get new version for ring
        $newVersion = $repo->getTag($this->ring);
        echo " - new version: $newVersion\n";
        # If latest version is null or same then return.
        if (empty($newVersion) || $currentVersion === $newVersion) {
            echo " - no update\n";
            return;
        }
        # If deploy pod already exists then delete it.
        if ($currentVersion !== "null") {
            echo " - deleting previous deploy pod\n";
            $this->exec("kubectl delete pod deploy");
        }
        # Run deploy, deploy container is removed on next deploy.
        echo " - running deploy $repo:$newVersion\n";
        $this->exec(<<<CMD
            kubectl run deploy \
                --image='$repo:$newVersion' \
                --restart=Never \
                --overrides='{"spec":{"serviceAccount":"ops"}}' \
                --labels='app.kubernetes.io/name=ops' \
                -- \
                $this->kustomization
            CMD
        );
    }

    #[Command("w", "watch for updates", "--watch --ring RING --kustomization KUSTOMIZATION [--wait MINUTES]")]
    public function watch(): void
    {
        # Infinite loop
        do {
            try {
                $this->login();
                $this->deploy();
                sleep($this->wait * 60);
            } catch (Throwable $throwable) {
                $error = get_class($throwable);
                echo date("[Y-m-d H:i:s]"), "$error: {$throwable->getMessage()}\n##{$throwable->getFile()}::{$throwable->getLine()}\n{$throwable->getTraceAsString()}\n";
            }
        } while ($this->wait > 0);
    }

    private function getEtcetera(): Etcetera
    {
        return new Etcetera($this->currentDir, $this->registry, $this->project);
    }

    private function getRing(int $offset = 0): string
    {
        $index = array_search($this->ring, $this->rings, true) + $offset;
        assert(isset($this->rings[$index]), new RuntimeException("Can't move passed boundary."));
        return $this->rings[$index];
    }

    private function exec(string $command): void
    {
        $result = passthru($command, $result_code);
        if ($result === false || $result_code !== 0) {
            throw new RuntimeException("command failed: $command");
        }
    }
}
