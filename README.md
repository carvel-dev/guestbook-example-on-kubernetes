# Modified Guestbook k8s Example

Guestbook k8s example is meant to showcase how k14s tools work together with a realistic application.

This example is based on [guestbook example from kubernetes/examples](https://github.com/kubernetes/examples/blob/d94a4484e1f73a277df25b13153f54cc60773eb5/guestbook/all-in-one/guestbook-all-in-one.yaml). Changes were done to remove unused functionality.

## Deploy

Using [k14s tools](https://github.com/k14s), deploy via:

```bash
ytt t -R -f . | kbld -f - | kapp deploy -a guestbook -f - --diff-changes -y
```

If you want to use online playground, head over to [Katacoda Kubernetes Playground](https://www.katacoda.com/courses/kubernetes/playground). You will have to set `katacoda` flag in `values.yml` to `true` and untaint master node, before proceeding with the above command. See comments in `katacoda.yml` for additional details.

```bash
kubectl taint nodes master node-role.kubernetes.io/master-
```

## Layout

- `php-redis/`: frontend app (Apache2 + PHP + Redis client)
- `redis-slave/`: Dockerfile to configure Redis as a slave
- `build.yml`: configuration for kbld to manage images
- `frontend.yml`: frontend configuration
- `redis-master.yml`: configuration to deploy Redis in master mode
- `redis-slave.yml`: configuration to deploy Redis as a slave
- `katacoda.yml`: ytt overlays to customize deployment for Katacoda Playground
- `values.yml`: global configuration knobs

## Highlighted Features

Here are some features of k14s tools as used in this example:

ytt:

- several configuration files use `data.values.redis_port` value from `values.yml`
  - this feature is useful for organizing shared configuration in one place

kbld:

- easy to convert source code for `frontend` application and `redis-slave` into container images
  - source: `build.yml`
- swap one image for another via `ImageOverrides` configuration

kapp:

- all configuration resources are tagged consistently, hence could be tracked
  - see `kapp inspect -a guestbook`
- label selectors on Service and Deployment resources are scoped to this application automatically
  - example: `frontend.yml` only specifies `frontend: ""` label, and kapp augments it with an application specific label
- `kapp.k14s.io/update-strategy: fallback-on-replace` annotation on Deployment resources allows to easily change any part of Deployment
  - by default if update is allowed by k8s, no forceful action will be taken
  - example: `frontend` Deployment
- `kapp.k14s.io/versioned: ""` annotation on ConfigMap resource allows us to change ConfigMap data and be certain that related Deployment resources will be updated with new values
  - example: `frontend` Deployment picks up env variables from `frontend-config` ConfigMap
- all pod logs from this application could be found via `kapp logs -f -a guestbook`
