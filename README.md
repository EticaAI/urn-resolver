# urn-resolver
[draft] URN Resolver urn.etica.ai; See https://github.com/EticaAI/HXL-Data-Science-file-formats/issues/13

## Quickstart

### How to run local node

Be sure to have something such as php 8.1. But PHP 7.4 also know to work

```bash
# Get a copy
git clone https://github.com/EticaAI/urn-resolver.git
cd urn-resolver/public

# Run PHP build-in server
php -S localhost:8000

# Visit home page: http://localhost:8000/
```

### How to run a production server(s)

#### Your first high-availability cluster deployment

```bash
# Get a recent copy from some place.
git clone https://github.com/EticaAI/urn-resolver.git

# Configure your node
cp urnresolver.dist.conf.json urnresolver.conf.json
vim urnresolver.conf.json

# Replace DRY_RUN="1" with DRY_RUN="0" (disable rsync --dry-run) and remote
DRY_RUN="1" RSYNC_REMOTE="user@server-a.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh
DRY_RUN="1" RSYNC_REMOTE="user@server-b.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh
DRY_RUN="1" RSYNC_REMOTE="user@server-c.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh

```
> **TL;DR:**
> On this example, `server-a.urn.example.org`, `server-b.urn.example.org` and `server-b.urn.example.org` are assumed to be direct access to any type of web hosting able to run PHP (inclusive cheap shared hosting) which also respond for `urn.example.org`.
> This example allows [High-availability cluster](https://en.wikipedia.org/wiki/High-availability_cluster) with [https://en.wikipedia.org/wiki/Round-robin_DNS](https://en.wikipedia.org/wiki/Round-robin_DNS) as load balancing strategy: just make sure `urn.example.org` points to IPs of server-a, server-b and server-c.

> **Warning**: if you run a cluster behind free solution which also does cache but hide the true IPs from end user (like free Cloudflare; which we do recommend) you will need to check manually if servers are online and [STONITH - ("Shoot The Offending Node In The Head")](https://en.wikipedia.org/wiki/STONITH) and remove nodes not working.

> **TIP**: (...)

#### Your first production single node URN server (recommended, good enough)

Use the same strategy for the high-availability cluster, but with single node.

<!--

ssh://urn.etica.ai/home/urneticaai/urn.etica.ai/
-->

## Test cases
### URN:DOI
- https://urn.etica.ai/urn:doi:10.1000/182

### URN:GEO
- https://urn.etica.ai/urn:geo:-19.9026,-44.0340;u=100000

### URN:IETF
- https://urn.etica.ai/urn:ietf:rfc:2141
- https://urn.etica.ai/urn:ietf:bcp:47

### URN:ISSN
- https://urn.etica.ai/urn:issn:1476-4687

### URN:LEX:BR
- https://urn.etica.ai/urn:lex:br:federal:lei:2008-06-19;11705
- https://urn.etica.ai/urn:resolver:about:urn:lex:br:federal:lei:2008-06-19;11705

### /.well-known/urn/
> @see https://en.wikipedia.org/wiki/Well-known_URI

<!--
## @TODO
### Load testing
- https://gist.github.com/denji/8333630
-->