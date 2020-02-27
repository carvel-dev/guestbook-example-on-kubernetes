# Modified Guestbook k8s Example

Guestbook k8s example is meant to showcase how k14s tools work together with a realistic application.

This example is based on [guestbook example from kubernetes/examples](https://github.com/kubernetes/examples/blob/d94a4484e1f73a277df25b13153f54cc60773eb5/guestbook/all-in-one/guestbook-all-in-one.yaml). Changes were done to remove unused functionality.

## Install k14s Tools

Head over to [k14s.io](https://k14s.io/) for installation instructions.

## Deploy

```bash
git clone https://github.com/k14s/k8s-guestbook-example
cd k8s-guestbook-example/
```

Using k14s tools, deploy via:

```bash
kapp deploy -a guestbook -f <(ytt -f config/ | kbld -f-) --diff-changes
```

Above command does the following:

- generate configuration from `config/` via [ytt](https://get-ytt.io)
  - no network access
  - see [Directory Layout](#directory-layout) below for details
- build images (with Docker) from source directories (`frontend/` and `redis-slave/`) via [kbld](https://get-kbld.io)
  - talks to Docker via Docker CLI and directly to registries
- deploys configuration to k8s cluster via [kapp](https://get-kapp.io)
  - talks to k8s API server

If you are using Minikube as your deployment target, run `eval $(minikube docker-env)` beforehand so that kbld can successfully shell out to Docker CLI. By default images are kept locally (not pushed).

If you are using remote cluster as your deployment target you will have to provide registry destination where images could be pushed and be accessible to the cluster. You will still need to have access to Docker CLI and be logged in so that pushes are successful.

```bash
docker login ...
kapp deploy -a guestbook -f <(ytt -f config/ --data-value push_images=true --data-value push_images_repo=docker.io/dkalinin | kbld -f-) -c
```

(Even if you are deploying to remote cluster, Minikube could be used for its Docker daemon; just make sure that your `~/.kube/config` points to your remote cluster.)

### Deploying to Online Playground

If you want to use online playground instead of your own cluster, head over to [Katacoda Kubernetes Playground](https://www.katacoda.com/courses/kubernetes/playground). You will have to set `--data-value katacoda=true` flag when using ytt and untaint master node, before proceeding with the above command. See comments in [`config/katacoda.yml`](config/katacoda.yml) for additional details.

```bash
kubectl taint nodes master node-role.kubernetes.io/master-
```

(Command does end with a hyphen.)

### Viewing Frontend App

Once deployed successfully, you can access frontend service at `127.0.0.1:8080` in your browser via `kubectl port-forward` command:

```bash
kubectl port-forward svc/frontend 8080:80
```

You will have to restart port forward command after making changes as pods are recreated. Alternatively consider using [k14s' kwt tool](https://github.com/k14s/kwt) which exposes cluser IP subnets and cluster DNS to your machine and does not require any restarts:

```bash
sudo -E kwt net start
```

and open [`http://frontend.default.svc.cluster.local/`](http://frontend.default.svc.cluster.local/).

### Making Changes

Once deployed, feel free to make changes to the app, and re-run same command.

For example, change [`frontend/guestbook.php`](frontend/guestbook.php):

```diff
-$bg = getenv('GUESTBOOK_BG');
+$bg = 'yellow';
```

or change [`frontend/frontend.yml`](frontend/frontend.yml):

```diff
-  GUESTBOOK_BG: "#eee"
+  GUESTBOOK_BG: "yellow"
```

and run exactly same command as before:

```bash
kapp deploy -a guestbook -f <(ytt -f config/ ...any opts... | kbld -f -) --diff-changes
```

Note that during second deploy each tool will try to be as optimal as possible based on changes made:

- kbld (via Docker) will only rebuild affected layers/images
- kapp will only deploy resources that changed or were affected by the change

## Directory Layout

- [`frontend/`](frontend/): frontend app (Apache2 + PHP + Redis client)
- [`redis-slave/`](redis-slave/): Dockerfile to configure Redis as a slave
- [`config/build.yml`](config/build.yml): configuration for kbld to manage images
- [`config/frontend.yml`](config/frontend.yml): frontend configuration
- [`config/frontend-scale.yml`](config/frontend-scale.yml): separate configuration to scale up frontend
- [`config/redis-master.yml`](config/redis-master.yml): configuration to deploy Redis in master mode
- [`config/redis-slave.yml`](config/redis-slave.yml): configuration to deploy Redis as a slave
- [`config/katacoda.yml`](config/katacoda.yml): ytt overlays to customize deployment for Katacoda Playground
- [`config/values.yml`](config/values.yml): global configuration knobs

## Highlighted Features

Here are some features of k14s tools as used in this example:

ytt:

- several configuration files use `data.values.redis_port` value from [`config/values.yml`](config/values.yml)
  - this feature is useful for organizing shared configuration in one place
- separate overlay configuration that customizes another resource
  - example: [`config/frontend-scale.yml`](config/frontend-scale.yml)

kbld:

- easy to convert source code for `frontend` application and `redis-slave` into container images
  - source: [`config/build.yml`](config/build.yml)
- swap one image for another via `ImageOverrides` configuration

kapp:

- all configuration resources are tagged consistently, hence could be tracked
  - see `kapp inspect -a guestbook` and `kapp inspect -a guestbook --tree`
- label selectors on Service and Deployment resources are scoped to this application automatically
  - example: [`config/frontend.yml`](config/frontend.yml) only specifies `frontend: ""` label, and kapp augments it with an application specific label
- `kapp.k14s.io/update-strategy: fallback-on-replace` annotation on Deployment resources allows to easily change any part of Deployment
  - by default if update is allowed by k8s, no forceful action will be taken
  - example: `frontend` Deployment
- `kapp.k14s.io/versioned: ""` annotation on ConfigMap resource allows us to change ConfigMap data and be certain that related Deployment resources will be updated with new values
  - example: `frontend` Deployment picks up env variables from `frontend-config` ConfigMap
- all pod logs from this application could be found via `kapp logs -f -a guestbook`
