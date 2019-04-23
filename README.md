# Modified Guestbook k8s Example

Based on [guestbook example from kubernetes/examples](https://github.com/kubernetes/examples/blob/d94a4484e1f73a277df25b13153f54cc60773eb5/guestbook/all-in-one/guestbook-all-in-one.yaml).

## Layout

- `php-redis/`: frontend app (Apache2 + PHP + Redis client)
- `redis-slave/`: Dockerfile to configure Redis as a slave
- `build.yml`: configuration for kbld to manage images
- `frontend.yml`: frontend configuration
- `redis-master.yml`: configuration to deploy Redis in master mode
- `redis-slave.yml`: configuration to deploy Redis as a slave
- `values.yml`: global configuration knobs

## Deploy

Using [k14s tools](https://github.com/k14s), deploy via:

```bash
ytt t -R -f . | kbld -f - | kapp deploy -a guestbook -f - --diff-changes -y
```
