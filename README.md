# Modified Guestbook k8s Example

Guestbook k8s example is meant to showcase how k14s tools work together with a realistic application.

This example is based on [guestbook example from kubernetes/examples](https://github.com/kubernetes/examples/blob/d94a4484e1f73a277df25b13153f54cc60773eb5/guestbook/all-in-one/guestbook-all-in-one.yaml). Changes were done to remove unused functionality.

## Install k14s Tools

Head over to [k14s.io](https://k14s.io/) for installation instructions.

## Deploy

Using k14s tools, deploy via:

```bash
ytt t -R -f config/ | kbld -f - | kapp deploy -a guestbook -f - --diff-changes -y
```

### Deploying to online playground

If you want to use online playground instead of your own cluster, head over to [Katacoda Kubernetes Playground](https://www.katacoda.com/courses/kubernetes/playground). You will have to set `katacoda` flag in [`config/values.yml`](config/values.yml) to `true` and untaint master node, before proceeding with the above command. See comments in [`config/katacoda.yml`](config/katacoda.yml) for additional details.

```bash
kubectl taint nodes master node-role.kubernetes.io/master-
```

### Viewing frontend app

You can access frontend service at `127.0.0.1:8080` in your browser via `kubectl port-forward` command:

```bash
kubectl port-forward svc/frontend 8080:80
```

You will have to restart port forward command after making changes as pods are recreated. Alternatively consider using [k14s' kwt tool](https://github.com/k14s/kwt) which exposes cluser IP subnets and cluster DNS to your machine:

```bash
sudo -E kwt net start
```

and open [`http://frontend.default.svc.cluster.local/`](http://frontend.default.svc.cluster.local/).

### Making changes

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

and then deploy again:

```bash
ytt t -R -f config/ | kbld -f - | kapp deploy -a guestbook -f - --diff-changes -y
```

Note that during second deploy each tool will try to be optimal based on changes made:

- kbld (via Docker) will only rebuild affected layers/images
- kapp will only deploy resources that changes or were affected by the change

## Layout

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
  - see `kapp inspect -a guestbook`
- label selectors on Service and Deployment resources are scoped to this application automatically
  - example: [`config/frontend.yml`](config/frontend.yml) only specifies `frontend: ""` label, and kapp augments it with an application specific label
- `kapp.k14s.io/update-strategy: fallback-on-replace` annotation on Deployment resources allows to easily change any part of Deployment
  - by default if update is allowed by k8s, no forceful action will be taken
  - example: `frontend` Deployment
- `kapp.k14s.io/versioned: ""` annotation on ConfigMap resource allows us to change ConfigMap data and be certain that related Deployment resources will be updated with new values
  - example: `frontend` Deployment picks up env variables from `frontend-config` ConfigMap
- all pod logs from this application could be found via `kapp logs -f -a guestbook`
